<?php
session_start();

if (!isset($_SESSION["user"]) || $_SESSION["user"] == "" || $_SESSION["usertype"] != "a") {
    header("location: ../login.php");
    exit();
}

include("../connection.php");

$test_id = $_GET['test_id'] ?? '';
$test = null;
$questions = [];

if ($test_id !== '') {
    $stmt = $database->prepare("SELECT test_category, test_name, test_description FROM test WHERE test_id = ?");
    $stmt->bind_param("s", $test_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $test = $result->fetch_assoc();
    $stmt->close();

    $stmt = $database->prepare("SELECT question_id, question_text FROM test_question WHERE test_id = ? ORDER BY question_id");
    $stmt->bind_param("s", $test_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $questions[] = $row;
    }
    $stmt->close();
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $category = $_POST['test_category'];
    $name = $_POST['test_name'];
    $description = $_POST['test_description'];

    $stmt = $database->prepare("UPDATE test SET test_category=?, test_name=?, test_description=? WHERE test_id=?");
    $stmt->bind_param("ssss", $category, $name, $description, $test_id);
    $stmt->execute();
    $stmt->close();

    // Get all existing question IDs for this test
    $existing_question_ids = [];
    $stmt = $database->prepare("SELECT question_id FROM test_question WHERE test_id=?");
    $stmt->bind_param("s", $test_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $existing_question_ids[] = $row['question_id'];
    }
    $stmt->close();

    // Update existing questions and track which ones to keep
    $questions_to_keep = [];
    if (isset($_POST['existing_questions'])) {
        foreach ($_POST['existing_questions'] as $qid => $text) {
            $questions_to_keep[] = $qid;
            $stmt = $database->prepare("UPDATE test_question SET question_text=? WHERE question_id=? AND test_id=?");
            $stmt->bind_param("sss", $text, $qid, $test_id);
            $stmt->execute();
            $stmt->close();
        }
    }

    // Delete questions that were removed
    foreach ($existing_question_ids as $qid) {
        if (!in_array($qid, $questions_to_keep)) {
            $stmt = $database->prepare("DELETE FROM test_question WHERE question_id=? AND test_id=?");
            $stmt->bind_param("ss", $qid, $test_id);
            $stmt->execute();
            $stmt->close();
        }
    }

    // Insert new questions
    if (!empty($_POST['new_questions'])) {
        foreach ($_POST['new_questions'] as $text) {
            if (trim($text) !== '') {
                $prefix = "Q";
                $stmt = $database->query("SELECT MAX(RIGHT(question_id, 4)) AS max_id FROM test_question");
                $row = $stmt->fetch_assoc();
                $next_id = str_pad(((int)$row['max_id']) + 1, 4, "0", STR_PAD_LEFT);
                $new_qid = $prefix . $next_id;

                $stmt = $database->prepare("INSERT INTO test_question (question_id, test_id, question_text) VALUES (?, ?, ?)");
                $stmt->bind_param("sss", $new_qid, $test_id, $text);
                $stmt->execute();
                $stmt->close();
            }
        }
    }

    header("Location: viewPsychTest.php?test_id=$test_id&updated=1");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Edit Psychological Test</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="../css/animations.css">
  <link rel="stylesheet" href="../css/main.css">
  <link rel="stylesheet" href="../css/admin.css">
  <!-- Font Awesome for icons -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <style>
    /* Header styles */
    .dash-body {
      padding: 20px;
    }

    .dash-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 15px 25px;
      background-color: #fff;
      border-radius: 10px;
      box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
      margin-bottom: 30px;
    }

    .dash-header-left {
      display: flex;
      align-items: center;
      gap: 20px;
    }

    .dash-header-left {
      display: flex;
      align-items: center;
      gap: 20px;
    }

    .btn-back {
      background-color: #f0f0f0;
      color: #555;
      border: none;
      border-radius: 6px;
      padding: 10px 16px;
      font-size: 16px;
      cursor: pointer;
      transition: all 0.3s ease;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .btn-back:hover {
      background-color: #e0e0e0;
      color: #333;
    }

    .page-title {
      font-size: 24px;
      margin: 0;
      color: #333;
      font-weight: 600;
    }

    .dash-header-right {
      display: flex;
      align-items: center;
      gap: 15px;
    }

    .date-display {
      text-align: right;
    }

    .date-label {
      font-size: 14px;
      color: #888;
      margin: 0 0 4px 0;
    }

    .date-value {
      font-size: 16px;
      font-weight: 600;
      color: #333;
      margin: 0;
    }

    .calendar-icon {
      background-color: #f0f0f0;
      width: 40px;
      height: 40px;
      border-radius: 50%;
      display: flex;
      justify-content: center;
      align-items: center;
    }

    .calendar-icon img {
      width: 24px;
      height: 24px;
    }

    /* Form styling */
    .form-container {
      max-width: 900px;
      margin: 0 auto;
      padding: 30px;
      background-color: #fff;
      border-radius: 12px;
      box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
      animation: fadeIn 0.5s ease;
    }

    @keyframes fadeIn {
      from {
        opacity: 0;
        transform: translateY(20px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    .form-title {
      font-size: 28px;
      font-weight: 600;
      margin-bottom: 30px;
      color: #008f7a;
      padding-bottom: 15px;
      border-bottom: 2px solid #eee;
    }

    .form-section {
      margin-bottom: 25px;
    }

    .form-label {
      font-weight: 600;
      margin-bottom: 8px;
      display: block;
      font-size: 16px;
      color: #444;
    }

    .form-input, .form-textarea, .form-select {
      width: 100%;
      padding: 12px 15px;
      border: 1px solid #ddd;
      border-radius: 8px;
      font-size: 16px;
      transition: all 0.3s ease;
      box-sizing: border-box;
    }

    .form-input:focus, .form-textarea:focus, .form-select:focus {
      border-color: #008f7a;
      box-shadow: 0 0 0 2px rgba(0, 143, 122, 0.1);
      outline: none;
    }

    .form-textarea {
      height: 120px;
      resize: vertical;
      line-height: 1.5;
    }

    .form-select {
      appearance: none;
      background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%23555' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E");
      background-repeat: no-repeat;
      background-position: right 15px center;
      background-size: 16px;
    }

    /* Questions Section */
    .questions-section {
      margin-top: 30px;
    }

    .questions-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 20px;
    }

    .questions-title {
      font-size: 20px;
      font-weight: 600;
      color: #333;
    }

    .question-count {
      background: #e6f7f4;
      color: #008f7a;
      padding: 5px 12px;
      border-radius: 20px;
      font-size: 14px;
      font-weight: 500;
    }

    .question-box {
      background-color: #f9f9f9;
      border: 1px solid #eee;
      border-radius: 8px;
      padding: 15px;
      margin-bottom: 15px;
      position: relative;
      transition: all 0.3s ease;
    }

    .question-box:hover {
      box-shadow: 0 3px 8px rgba(0, 0, 0, 0.05);
    }

    .question-controls {
      display: flex;
      justify-content: space-between;
      margin-bottom: 10px;
    }

    .question-number {
      font-weight: 600;
      color: #008f7a;
      display: flex;
      align-items: center;
      gap: 5px;
    }

    .remove-btn {
      background-color: #ff5252;
      color: white;
      border: none;
      border-radius: 6px;
      padding: 6px 12px;
      font-size: 14px;
      cursor: pointer;
      transition: all 0.2s ease;
      display: flex;
      align-items: center;
      gap: 5px;
    }

    .remove-btn:hover {
      background-color: #ff3333;
    }

    /* Action buttons */
    .button-group {
      display: flex;
      gap: 15px;
      margin-top: 30px;
    }

    .add-btn, .submit-btn {
      padding: 12px 20px;
      border: none;
      border-radius: 8px;
      font-size: 16px;
      font-weight: 500;
      cursor: pointer;
      transition: all 0.3s ease;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .add-btn {
      background-color: #e6f7f4;
      color: #008f7a;
      border: 1px solid #008f7a;
    }

    .add-btn:hover {
      background-color: #d0f0ea;
    }

    .submit-btn {
      background-color: #008f7a;
      color: #fff;
      padding: 12px 25px;
    }

    .submit-btn:hover {
      background-color: #007066;
      transform: translateY(-2px);
      box-shadow: 0 4px 8px rgba(0, 139, 122, 0.2);
    }

    .empty-message {
      text-align: center;
      padding: 30px;
      color: #888;
      font-size: 18px;
    }

    /* Drag handle */
    .handle {
      cursor: move;
      margin-right: 10px;
      color: #aaa;
    }

    /* Responsive design */
    @media (max-width: 768px) {
      .dash-header {
        flex-direction: column;
        gap: 15px;
        align-items: flex-start;
      }

      .dash-header-right {
        width: 100%;
        justify-content: space-between;
      }

      .form-container {
        padding: 20px 15px;
      }

      .button-group {
        flex-direction: column;
      }

      .add-btn, .submit-btn {
        width: 100%;
        justify-content: center;
      }
    }
  </style>
</head>
<body>
  <div class="container">
    <?php include("adminMenu.php"); ?>
    <div class="dash-body">
      <table border="0" width="100%" style=" border-spacing: 0;margin:0;padding:0;margin-top:25px; ">
        <tr>
          <td width="13%">
            <a href="tests.php"><button class="login-btn btn-primary-soft btn btn-icon-back" style="padding-top:11px;padding-bottom:11px;margin-left:20px;width:125px">
                <font class="tn-in-text">Back</font>
              </button></a>
          </td>
          <td>
            <p style="font-size: 23px;padding-left:12px;font-weight: 600;">Psychological Test Manager</p>

          </td>
          <td width="15%">
            <p style="font-size: 14px;color: rgb(119, 119, 119);padding: 0;margin: 0;text-align: right;">
              Today's Date
            </p>
            <p class="heading-sub12" style="padding: 0;margin: 0;">
              <?php
              date_default_timezone_set('Asia/Kolkata');
              $today = date('Y-m-d');
              echo $today;
              ?>
            </p>
          </td>
          <td width="10%">
            <button class="btn-label" style="display: flex;justify-content: center;align-items: center;"><img src="../img/calendar.svg" width="100%"></button>
          </td>


        </tr>
      </table>

      <div class="form-container">
        <?php if ($test): ?>
          <h1 class="form-title">Edit Test: <?php echo htmlspecialchars($test['test_name']); ?></h1>
          
          <form method="POST" id="editForm">
            <div class="form-section">
              <label class="form-label">Category</label>
              <select name="test_category" class="form-select" required>
                <?php
                  $categories = ["Stress", "Anxiety", "Depression", "Aggression", "Self-Esteem"];
                  foreach ($categories as $cat) {
                    $selected = $cat === $test['test_category'] ? 'selected' : '';
                    echo "<option value=\"$cat\" $selected>$cat</option>";
                  }
                ?>
              </select>
            </div>

            <div class="form-section">
              <label class="form-label">Test Name</label>
              <input type="text" name="test_name" class="form-input" value="<?= htmlspecialchars($test['test_name']) ?>" required>
            </div>

            <div class="form-section">
              <label class="form-label">Description</label>
              <textarea name="test_description" class="form-textarea" required><?= htmlspecialchars($test['test_description']) ?></textarea>
            </div>

            <div class="questions-section">
              <div class="questions-header">
                <div class="questions-title">Questions</div>
                <div class="question-count" id="questionCounter">
                  <span id="questionCount"><?= count($questions) ?></span> Questions
                </div>
              </div>

              <div id="questionContainer">
                <?php $num = 1; ?>
                <?php foreach ($questions as $q): ?>
                  <div class="question-box" id="question-<?= $q['question_id'] ?>">
                    <div class="question-controls">
                      <div class="question-number">
                        <i class="fas fa-grip-lines handle"></i>
                        <span class="q-num"><?= $num++ ?></span>.
                      </div>
                      <button type="button" class="remove-btn" onclick="removeQuestion('<?= $q['question_id'] ?>')">
                        <i class="fas fa-trash-alt"></i> Remove
                      </button>
                    </div>
                    <input type="text" name="existing_questions[<?= $q['question_id'] ?>]" class="form-input" value="<?= htmlspecialchars($q['question_text']) ?>" required>
                  </div>
                <?php endforeach; ?>
              </div>

              <div id="newQuestionContainer"></div>

              <div class="button-group">
                <button type="button" class="add-btn" onclick="addQuestion()">
                  <i class="fas fa-plus"></i> Add New Question
                </button>
                <button type="submit" class="submit-btn">
                  <i class="fas fa-save"></i> Save Changes
                </button>
              </div>
            </div>
          </form>
        <?php else: ?>
          <div class="empty-message">
            <i class="fas fa-exclamation-circle" style="font-size: 48px; color: #ff5252; margin-bottom: 15px;"></i>
            <p>Test not found or invalid test ID.</p>
            <a href="tests.php" style="display: inline-block; margin-top: 20px;">
              <button class="btn-back">
                <i class="fas fa-list"></i> Go to Tests List
              </button>
            </a>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <script>
    // Track total questions (existing + new)
    let questionCount = <?= count($questions) ?>;
    let newQuestionCount = 0;
    let removedQuestions = [];

    // Function to update question numbers
    function updateQuestionNumbers() {
      const allQuestions = document.querySelectorAll('.question-box');
      let count = 1;
      allQuestions.forEach(question => {
        question.querySelector('.q-num').textContent = count++;
      });
      
      // Update counter in header
      document.getElementById('questionCount').textContent = allQuestions.length;
    }

    // Function to add a new question
    function addQuestion() {
      newQuestionCount++;
      questionCount++;
      
      const container = document.getElementById('newQuestionContainer');
      const div = document.createElement('div');
      div.className = 'question-box';
      div.id = `new-question-${newQuestionCount}`;
      
      div.innerHTML = `
        <div class="question-controls">
          <div class="question-number">
            <i class="fas fa-grip-lines handle"></i>
            <span class="q-num">${questionCount}</span>.
          </div>
          <button type="button" class="remove-btn" onclick="removeNewQuestion(${newQuestionCount})">
            <i class="fas fa-trash-alt"></i> Remove
          </button>
        </div>
        <input type="text" name="new_questions[]" class="form-input" placeholder="Enter question text..." required>
      `;
      
      container.appendChild(div);
      updateQuestionNumbers();
      
      // Focus the new input field
      const newInput = div.querySelector('input');
      newInput.focus();
    }

    // Function to remove an existing question
    function removeQuestion(questionId) {
      if (confirm('Are you sure you want to remove this question?')) {
        // Mark as removed without actually removing from DOM yet
        const questionElement = document.getElementById(`question-${questionId}`);
        
        // Visual feedback for removal
        questionElement.style.backgroundColor = '#ffebee';
        questionElement.style.opacity = '0.5';
        
        // Hide the remove button to prevent multiple clicks
        questionElement.querySelector('.remove-btn').style.display = 'none';
        
        // Create a message to show the question is being removed
        const msg = document.createElement('div');
        msg.className = 'remove-message';
        msg.innerHTML = '<i class="fas fa-info-circle"></i> This question will be removed when you save changes.';
        msg.style.color = '#f44336';
        msg.style.fontSize = '14px';
        msg.style.marginTop = '5px';
        
        questionElement.appendChild(msg);
        
        // Add an undo button
        const undoBtn = document.createElement('button');
        undoBtn.innerHTML = '<i class="fas fa-undo"></i> Undo';
        undoBtn.style.marginLeft = '10px';
        undoBtn.style.border = 'none';
        undoBtn.style.background = 'none';
        undoBtn.style.color = '#2196F3';
        undoBtn.style.cursor = 'pointer';
        undoBtn.style.fontWeight = 'bold';
        msg.appendChild(undoBtn);
        
        undoBtn.onclick = function() {
          // Restore the question's appearance
          questionElement.style.backgroundColor = '';
          questionElement.style.opacity = '';
          questionElement.querySelector('.remove-btn').style.display = '';
          msg.remove();
          
          // Re-enable the input
          const input = questionElement.querySelector('input');
          input.disabled = false;
        };
        
        // Disable the input field but keep it in the form submission
        const input = questionElement.querySelector('input');
        input.disabled = true;
        
        // Mark as removed in our tracking array
        removedQuestions.push(questionId);
        
        // Update question numbers
        updateQuestionNumbers();
      }
    }

    // Function to remove a new question
    function removeNewQuestion(index) {
      const questionElement = document.getElementById(`new-question-${index}`);
      questionElement.remove();
      questionCount--;
      updateQuestionNumbers();
    }

    // Handle form submission
    document.getElementById('editForm').addEventListener('submit', function(e) {
      // For removed questions, remove their inputs from the form
      removedQuestions.forEach(qid => {
        const input = document.querySelector(`input[name="existing_questions[${qid}]"]`);
        if (input) {
          input.removeAttribute('name');
        }
      });
    });
  </script>
</body>
</html>