<?php
session_start();

// ---------------------------------------------------------------------
// 1) Check if user is logged in as patient
// ---------------------------------------------------------------------
if (isset($_SESSION["user"])) {
  if (($_SESSION["user"]) == "" || $_SESSION['usertype'] != 'p') {
    header("location: ../login.php");
    exit;
  } else {
    $useremail = $_SESSION["user"];
  }
} else {
  header("location: ../login.php");
  exit;
}

// ---------------------------------------------------------------------
// 2) Include database connection and fetch user details
// ---------------------------------------------------------------------
include("../connection.php");
$userrow   = $database->query("SELECT * FROM patient WHERE pemail='$useremail'");
$userfetch = $userrow->fetch_assoc();
$userid    = $userfetch["pid"];
$username  = $userfetch["pname"];

// ---------------------------------------------------------------------
// 2a) Prepare today's date (in YYYY-MM-DD) for DB
// ---------------------------------------------------------------------
date_default_timezone_set('Asia/Kolkata');
$mysqlDate = date('Y-m-d');  // e.g. "2025-03-08"

// Get the test_id from GET parameter
if (!isset($_GET['test_id']) || empty($_GET['test_id'])) {
  die("No test specified.");
}
$testId = $_GET['test_id'];

// Retrieve test details
$testStmt = $database->prepare("SELECT test_name, test_description FROM test WHERE test_id = ?");
$testStmt->bind_param("s", $testId);
$testStmt->execute();
$testResult = $testStmt->get_result();
if ($testResult->num_rows == 0) {
  die("Test not found.");
}
$testRow = $testResult->fetch_assoc();
$testName = $testRow['test_name'];
$testDescription = $testRow['test_description'];
$testStmt->close();

// Retrieve questions for this test
$questionStmt = $database->prepare("SELECT question_id, question_text FROM test_question WHERE test_id = ? ORDER BY question_id ASC");
$questionStmt->bind_param("s", $testId);
$questionStmt->execute();
$questionResult = $questionStmt->get_result();
$questions = [];
while ($qRow = $questionResult->fetch_assoc()) {
  $questions[] = $qRow;
}
$questionStmt->close();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  // Generate a unique patient test ID
  $patientTestId = uniqid('PT_', true);

  //Use $userid (defined earlier from session) instead of undefined $patientId
  $insertTestStmt = $database->prepare("INSERT INTO patient_test (patient_test_id, pid, test_id) VALUES (?, ?, ?)");
  $insertTestStmt->bind_param("sis", $patientTestId, $userid, $testId);
  $insertTestStmt->execute();
  $insertTestStmt->close();

  // Save each answer
  foreach ($questions as $question) {
    $qId = $question['question_id'];
    $postName = "answer_" . $qId;
    if (isset($_POST[$postName])) {
      $answerValue = (int) $_POST[$postName];
      if ($answerValue < 1 || $answerValue > 5) continue;

      $answerId = uniqid('A_', true);
      $insertAnswerStmt = $database->prepare("INSERT INTO patient_answer (answer_id, patient_test_id, question_id, answer_value) VALUES (?, ?, ?, ?)");
      $insertAnswerStmt->bind_param("sssi", $answerId, $patientTestId, $qId, $answerValue);
      $insertAnswerStmt->execute();
      $insertAnswerStmt->close();
    }
  }

  // Mark test as completed
  $updateTestStmt = $database->prepare("UPDATE patient_test SET completed_at = NOW() WHERE patient_test_id = ?");
  $updateTestStmt->bind_param("s", $patientTestId);
  $updateTestStmt->execute();
  $updateTestStmt->close();

  // Redirect to result page
  header("Location: testResult.php?patient_test_id=" . urlencode($patientTestId));
  exit;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title><?php echo htmlspecialchars($testName); ?> - Psychological Test</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="../css/animations.css">
  <link rel="stylesheet" href="../css/main.css">
  <link rel="stylesheet" href="../css/admin.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
  <style>
    :root {
      --primary: #2c3e50;
      --secondary: #3498db;
      --accent: #1abc9c;
      --light: #ecf0f1;
      --dark: #2c3e50;
      --danger: #e74c3c;
      --success: #2ecc71;
      --warning: #f39c12;
      --gray: #95a5a6;
    }

    /* body {
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      background-color: #f5f7fa;
      color: #333;
      line-height: 1.6;
    } */

    .test-container {
      max-width: 900px;
      margin: 0 auto;
      padding: 20px;
    }

    .test-header {
      text-align: center;
      margin-bottom: 30px;
      background: var(--primary);
      color: white;
      padding: 20px;
      border-radius: 10px;
      box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
    }

    .test-header h1 {
      font-size: 2.2rem;
      margin-bottom: 10px;
      color: white;
    }

    .test-header p {
      font-size: 1.1rem;
      color: rgba(255, 255, 255, 0.9);
      max-width: 700px;
      margin: 0 auto;
    }

    .instructions {
      background: #fff;
      padding: 20px;
      border-radius: 10px;
      margin-bottom: 30px;
      box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
    }

    .instructions h2 {
      color: var(--primary);
      font-size: 1.5rem;
      margin-bottom: 10px;
    }

    .instructions p {
      margin-bottom: 10px;
    }

    .instructions ul {
      padding-left: 20px;
    }

    .instructions li {
      margin-bottom: 5px;
    }

    .question-container {
      background: #fff;
      border-radius: 10px;
      box-shadow: 0 2px 15px rgba(0, 0, 0, 0.1);
      overflow: hidden;
    }

    .question {
      padding: 25px;
      border-bottom: 1px solid #eee;
      transition: background-color 0.3s;
    }

    .question:last-child {
      border-bottom: none;
    }

    .question:hover {
      background-color: rgba(236, 240, 241, 0.3);
    }

    .question-number {
      display: inline-block;
      background: var(--secondary);
      color: white;
      width: 30px;
      height: 30px;
      text-align: center;
      line-height: 30px;
      border-radius: 50%;
      margin-right: 10px;
      font-weight: bold;
    }

    .question h3 {
      font-size: 1.2rem;
      margin-bottom: 20px;
      color: var(--dark);
      display: flex;
      align-items: center;
    }

    .answer-options {
      display: flex;
      justify-content: space-between;
      align-items: center;
      flex-wrap: wrap;
      margin-left: 40px;
    }

    .rating-option {
      position: relative;
      margin-bottom: 10px;
      width: 18%;
      text-align: center;
    }

    /* Custom radio buttons */
    .rating-option input[type="radio"] {
      position: absolute;
      opacity: 0;
      width: 0;
      height: 0;
    }

    .rating-option label {
      display: flex;
      flex-direction: column;
      align-items: center;
      cursor: pointer;
      padding: 12px 0;
      border-radius: 5px;
      transition: all 0.3s;
      border: 2px solid #eee;
    }

    .rating-option .rating-value {
      font-size: 1.5rem;
      font-weight: bold;
      color: var(--dark);
      margin-bottom: 5px;
    }

    .rating-option .rating-text {
      font-size: 0.8rem;
      color: var(--gray);
    }

    .rating-option input[type="radio"]:checked+label {
      background-color: var(--secondary);
      color: white;
      border-color: var(--secondary);
      transform: translateY(-3px);
      box-shadow: 0 5px 10px rgba(52, 152, 219, 0.3);
    }

    .rating-option input[type="radio"]:checked+label .rating-value,
    .rating-option input[type="radio"]:checked+label .rating-text {
      color: white;
    }

    .rating-option label:hover {
      background-color: rgba(52, 152, 219, 0.1);
      border-color: var(--secondary);
    }

    .progress-container {
      width: 100%;
      background-color: #eee;
      border-radius: 5px;
      margin-bottom: 30px;
      overflow: hidden;
    }

    .progress-bar {
      height: 10px;
      background-color: var(--accent);
      width: 0%;
      transition: width 0.3s ease;
    }

    .actions {
      display: flex;
      justify-content: space-between;
      margin-top: 30px;
      padding: 0 25px 25px;
    }

    .btn1 {
      padding: 12px 30px;
      border: none;
      border-radius: 50px;
      font-size: 1rem;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.3s;
      display: inline-flex;
      align-items: center;
      justify-content: center;
    }

    .btn-prev {
      background-color: var(--light);
      color: var(--dark);
    }

    .btn-prev:hover {
      background-color: #ddd;
    }

    .btn-next {
      background-color: var(--secondary);
      color: white;
    }

    .btn-next:hover {
      background-color: #2980b9;
    }

    .btn-submit {
      background-color: var(--success);
      color: white;
    }

    .btn-submit:hover {
      background-color: #27ae60;
    }

    .btn i {
      margin-right: 8px;
    }

    .btn-submit i {
      margin-left: 8px;
      margin-right: 0;
    }

    .question-navigation {
      display: flex;
      flex-wrap: wrap;
      justify-content: center;
      margin: 20px 0;
    }

    .nav-dot {
      width: 12px;
      height: 12px;
      border-radius: 50%;
      background: #ddd;
      margin: 0 5px;
      cursor: pointer;
      transition: all 0.3s;
    }

    .nav-dot.active {
      background: var(--secondary);
      transform: scale(1.3);
    }

    .nav-dot.answered {
      background: var(--success);
    }

    /* Responsive styles */
    @media (max-width: 768px) {
      .answer-options {
        flex-direction: column;
        align-items: stretch;
      }

      .rating-option {
        width: 100%;
        margin-bottom: 10px;
      }

      .rating-option label {
        flex-direction: row;
        justify-content: space-between;
        padding: 12px 15px;
      }

      .question-number {
        margin-bottom: 10px;
      }

      .question h3 {
        flex-direction: column;
        align-items: flex-start;
      }

      .actions {
        flex-direction: column;
        gap: 10px;
      }

      .btn1 {
        width: 100%;
      }
    }

    /* Animation for transitions */
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

    .fade-in {
      animation: fadeIn 0.5s ease forwards;
    }
  </style>
</head>

<body>
  <div class="container">
    <?php include(__DIR__ . '/patientMenu.php'); ?>
    <div class="dash-body">
      <table border="0" width="100%" style="border-spacing:0;margin:0;padding:0;margin-top:25px;">
        <tr>
          <td width="13%">
            <a href="psychologicalTest.php">
              <button class="login-btn btn-primary-soft btn btn-icon-back"
                style="padding-top:11px;padding-bottom:11px;margin-left:20px;width:125px">
                <font class="tn-in-text">Back</font>
              </button>
            </a>
          </td>
      </table>
      <div class="test-container">
        <div class="test-header">
          <h1><?php echo htmlspecialchars($testName); ?></h1>
          <p><?php echo htmlspecialchars($testDescription); ?></p>
        </div>

        <div class="instructions">
          <h2><i class="fas fa-info-circle"></i> Instructions</h2>
          <p>Please read each question carefully and select the answer that best represents how you feel or think. There are no right or wrong answers.</p>
          <ul>
            <li>1 = Strongly Disagree</li>
            <li>2 = Disagree</li>
            <li>3 = Neither Agree nor Disagree</li>
            <li>4 = Agree</li>
            <li>5 = Strongly Agree</li>
          </ul>
          <p>You can navigate between questions using the buttons at the bottom or the progress dots.</p>
        </div>

        <!-- Progress bar -->
        <div class="progress-container">
          <div class="progress-bar" id="progress"></div>
        </div>

        <form action="" method="POST" id="testForm">
          <div class="question-container">
            <?php if (!empty($questions)): ?>
              <?php foreach ($questions as $index => $question): ?>
                <?php $qId = $question['question_id']; ?>
                <div class="question" id="question-<?php echo $index; ?>" <?php echo $index > 0 ? 'style="display:none;"' : ''; ?>>
                  <h3>
                    <span class="question-number"><?php echo $index + 1; ?></span>
                    <?php echo htmlspecialchars($question['question_text']); ?>
                  </h3>
                  <div class="answer-options">
                    <?php
                    $ratingTexts = [
                      1 => 'Strongly Disagree',
                      2 => 'Disagree',
                      3 => 'Neutral',
                      4 => 'Agree',
                      5 => 'Strongly Agree'
                    ];

                    for ($i = 1; $i <= 5; $i++):
                    ?>
                      <div class="rating-option">
                        <input type="radio" id="q<?php echo $qId; ?>_a<?php echo $i; ?>" name="answer_<?php echo $qId; ?>" value="<?php echo $i; ?>" required>
                        <label for="q<?php echo $qId; ?>_a<?php echo $i; ?>">
                          <span class="rating-value"><?php echo $i; ?></span>
                          <span class="rating-text"><?php echo $ratingTexts[$i]; ?></span>
                        </label>
                      </div>
                    <?php endfor; ?>
                  </div>
                </div>
              <?php endforeach; ?>
            <?php else: ?>
              <p>No questions available for this test.</p>
            <?php endif; ?>

            <!-- Navigation buttons -->
            <div class="actions">
              <button type="button" class="btn1 btn-prev" id="prevBtn" style="display:none;">
                <i class="fas fa-arrow-left"></i> Previous
              </button>
              <div style="flex-grow:1;"></div>
              <button type="button" class="btn1 btn-next" id="nextBtn">
                Next <i class="fas fa-arrow-right"></i>
              </button>
              <button type="submit" class="btn1 btn-submit" id="submitBtn" style="display:none;">
                Submit Test <i class="fas fa-check-circle"></i>
              </button>
            </div>
          </div>

          <!-- Question navigation dots -->
          <div class="question-navigation">
            <?php if (!empty($questions)): ?>
              <?php foreach ($questions as $index => $question): ?>
                <div class="nav-dot <?php echo $index === 0 ? 'active' : ''; ?>" data-question="<?php echo $index; ?>"></div>
              <?php endforeach; ?>
            <?php endif; ?>
          </div>
        </form>
      </div>
    </div>
  </div>

  <script>
    document.addEventListener('DOMContentLoaded', function() {
      const questions = document.querySelectorAll('.question');
      const totalQuestions = questions.length;
      const prevBtn = document.getElementById('prevBtn');
      const nextBtn = document.getElementById('nextBtn');
      const submitBtn = document.getElementById('submitBtn');
      const progressBar = document.getElementById('progress');
      const navDots = document.querySelectorAll('.nav-dot');
      const form = document.getElementById('testForm');

      let currentQuestion = 0;
      const answeredQuestions = new Set();

      // Update progress bar
      function updateProgress() {
        const width = (answeredQuestions.size / totalQuestions) * 100;
        progressBar.style.width = width + '%';
      }

      // Show question by index
      function showQuestion(index) {
        questions.forEach((q, i) => {
          q.style.display = i === index ? 'block' : 'none';
          q.classList.add('fade-in');
        });

        // Update navigation dots
        navDots.forEach((dot, i) => {
          dot.classList.toggle('active', i === index);
        });

        // Update buttons
        prevBtn.style.display = index > 0 ? 'flex' : 'none';
        nextBtn.style.display = index < totalQuestions - 1 ? 'flex' : 'none';
        submitBtn.style.display = index === totalQuestions - 1 ? 'flex' : 'none';

        currentQuestion = index;
      }

      // Next button click handler
      nextBtn.addEventListener('click', function() {
        // Check if current question is answered
        const qId = questions[currentQuestion].querySelector('.answer-options').querySelector('input').name;
        const isAnswered = document.querySelector(`input[name="${qId}"]:checked`);

        if (isAnswered) {
          answeredQuestions.add(currentQuestion);
          navDots[currentQuestion].classList.add('answered');
          updateProgress();

          if (currentQuestion < totalQuestions - 1) {
            showQuestion(currentQuestion + 1);
          }
        } else {
          alert('Please answer the current question before proceeding.');
        }
      });

      // Previous button click handler
      prevBtn.addEventListener('click', function() {
        if (currentQuestion > 0) {
          showQuestion(currentQuestion - 1);
        }
      });

      // Navigation dots click handler
      navDots.forEach((dot, index) => {
        dot.addEventListener('click', function() {
          showQuestion(index);
        });
      });

      // Form submission validation
      form.addEventListener('submit', function(e) {
        let allAnswered = true;

        // Check if all questions are answered
        questions.forEach((question, index) => {
          const inputs = question.querySelectorAll('input[type="radio"]');
          const name = inputs[0].name;
          const answered = document.querySelector(`input[name="${name}"]:checked`);

          if (!answered) {
            allAnswered = false;
            showQuestion(index);
            e.preventDefault();
            return;
          }
        });

        if (!allAnswered) {
          alert('Please answer all questions before submitting the test.');
        }
      });

      // Radio button change handler to track answers
      document.querySelectorAll('input[type="radio"]').forEach(radio => {
        radio.addEventListener('change', function() {
          const questionIndex = this.closest('.question').id.split('-')[1];
          answeredQuestions.add(parseInt(questionIndex));
          navDots[questionIndex].classList.add('answered');
          updateProgress();
        });
      });

      // Initialize
      showQuestion(0);
      updateProgress();
    });
  </script>
</body>

</html>