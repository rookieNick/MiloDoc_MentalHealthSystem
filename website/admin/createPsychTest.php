<?php
session_start();

if (isset($_SESSION["user"])) {
    if ($_SESSION["user"] == "" || $_SESSION['usertype'] != 'a') {
        header("location: ../login.php");
    }
} else {
    header("location: ../login.php");
}

include("../connection.php");

// Generate new ID like T0001, Q0001
function generateId($conn, $table, $prefix, $column)
{
    $sql = "SELECT $column FROM $table WHERE $column LIKE '$prefix%' ORDER BY $column DESC LIMIT 1";
    $result = $conn->query($sql);
    if ($row = $result->fetch_assoc()) {
        $num = (int)substr($row[$column], 1) + 1;
    } else {
        $num = 1;
    }
    return $prefix . str_pad($num, 4, '0', STR_PAD_LEFT);
}

$success = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $test_id = generateId($database, 'test', 'T', 'test_id');
    $test_name = $_POST["test_name"];
    $test_description = $_POST["test_description"];
    $category = $_POST["category"];
    $questions = $_POST["questions"];

    // Insert into test table
    $stmt = $database->prepare("INSERT INTO test (test_id, test_name, test_description, test_category) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $test_id, $test_name, $test_description, $category);
    $stmt->execute();

    // Insert each question
    foreach ($questions as $question_text) {
        if (!empty(trim($question_text))) {
            $question_id = generateId($database, 'test_question', 'Q', 'question_id');
            $stmt_q = $database->prepare("INSERT INTO test_question (question_id, test_id, question_text) VALUES (?, ?, ?)");
            $stmt_q->bind_param("sss", $question_id, $test_id, $question_text);
            $stmt_q->execute();
        }
    }

    $success = "Test and questions successfully created!";
}
?>

<!DOCTYPE html>
<html>

<head>
    <title>Create Psychological Test</title>
    <link rel="stylesheet" href="../css/main.css">
    <link rel="stylesheet" href="../css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        :root {
            --primary-color: #4361ee;
            --secondary-color: #3f37c9;
            --success-color: #4cc9f0;
            --light-bg: #f8f9fa;
            --border-radius: 8px;
            --box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            --transition: all 0.3s ease;
        }

        /* body {
            display: flex;
            margin: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f0f2f5;
        } */

        .main-content {
            flex: 1;
            padding: 30px;
            min-height: 100vh;
            box-sizing: border-box;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .page-header {
            text-align: center;
            max-width: 800px;
            width: 100%;
        }

        .page-header h1 {
            font-size: 28px;
            color: #333;
            margin-bottom: 10px;
        }

        .page-header p {
            color: #666;
            font-size: 16px;
        }

        .form-container {
            max-width: 800px;
            width: 100%;
            background: white;
            padding: 30px;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            background-color: #f0f2f5;
        }

        .form-header {
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 1px solid #e9ecef;
        }

        .form-header h2 {
            color: #333;
            font-size: 24px;
            margin: 0;
        }

        .form-section {
            margin-bottom: 30px;
        }

        .form-section-title {
            font-size: 18px;
            font-weight: 600;
            color: #555;
            margin-bottom: 15px;
            padding-bottom: 8px;
            border-bottom: 1px dashed #e0e0e0;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            font-weight: 600;
            margin-bottom: 8px;
            display: block;
            color: #444;
        }

        input[type="text"],
        textarea,
        select {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: var(--border-radius);
            box-sizing: border-box;
            transition: var(--transition);
            font-size: 15px;
        }

        input[type="text"]:focus,
        textarea:focus,
        select:focus {
            border-color: var(--primary-color);
            outline: none;
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.2);
        }

        .question-container {
            background-color: var(--light-bg);
            padding: 20px;
            border-radius: var(--border-radius);
            margin-bottom: 20px;
        }

        .questions-list {
            max-height: 400px;
            overflow-y: auto;
            padding: 15px;
            background-color: var(--light-bg);
            border-radius: var(--border-radius);
        }

        .question-item {
            position: relative;
            margin-bottom: 15px;
            padding-right: 40px;
        }

        .question-input {
            width: 100%;
            margin-bottom: 0;
        }

        .remove-question {
            position: absolute;
            right: 0;
            top: 12px;
            background: none;
            border: none;
            color: #dc3545;
            cursor: pointer;
            font-size: 18px;
        }

        .remove-question:hover {
            color: #c82333;
        }

        .btn-add {
            background: var(--primary-color);
            color: white;
            padding: 10px 20px;
            border: none;
            cursor: pointer;
            border-radius: var(--border-radius);
            display: flex;
            align-items: center;
            gap: 8px;
            transition: var(--transition);
            font-weight: 600;
        }

        .btn-add:hover {
            background: var(--secondary-color);
        }

        .btn-primary {
            background: var(--primary-color);
            color: white;
            padding: 14px 24px;
            border: none;
            border-radius: var(--border-radius);
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            transition: var(--transition);
            width: 100%;
        }

        .btn-primary:hover {
            background: var(--secondary-color);
            transform: translateY(-2px);
        }

        .success-message {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
            padding: 15px;
            border-radius: var(--border-radius);
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .counter {
            font-size: 14px;
            color: #666;
            margin-top: 8px;
        }

        .form-footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e9ecef;
        }

        @media (max-width: 768px) {
            .form-container {
                padding: 20px;
            }
        }
    </style>
</head>

<body>
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
                    <p style="font-size: 23px;padding-left:12px;font-weight: 600;">Appointment Manager</p>

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
        <div class="main-content">
            <div class="page-header">
                <h1>Psychological Test Management</h1>
                <p>Create and manage psychological assessment tests for patients</p>
            </div>

            <div class="form-container">
                <div class="form-header">
                    <h2><i class="fas fa-clipboard-list"></i> Create New Test</h2>
                </div>

                <?php if ($success): ?>
                    <div class="success-message">
                        <i class="fas fa-check-circle"></i>
                        <span><?php echo $success; ?></span>
                    </div>
                <?php endif; ?>

                <form method="POST" id="test-form">
                    <div class="form-section">
                        <div class="form-section-title">Test Information</div>
                        <div class="form-group">
                            <label for="test_name">Test Name:</label>
                            <input type="text" id="test_name" name="test_name" required placeholder="Enter a descriptive name for this test">
                        </div>

                        <div class="form-group">
                            <label for="test_description">Test Description:</label>
                            <textarea id="test_description" name="test_description" rows="4" required placeholder="Provide a detailed description of what this test evaluates"></textarea>
                            <div class="counter"><span id="desc-counter">0</span>/500 characters</div>
                        </div>

                        <div class="form-group">
                            <label for="category">Test Category:</label>
                            <select id="category" name="category" required>
                                <option value="">-- Select Category --</option>
                                <option value="Stress">Stress</option>
                                <option value="Anxiety">Anxiety</option>
                                <option value="Depression">Depression</option>
                                <option value="Aggression">Aggression</option>
                                <option value="Self-Esteem">Self-Esteem</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-section">
                        <div class="form-section-title">Test Questions</div>
                        <p>Add questions that will appear in this psychological assessment</p>

                        <div class="questions-list" id="question-list">
                            <div class="question-item">
                                <input type="text" name="questions[]" class="question-input" required placeholder="Enter question text">
                                <button type="button" class="remove-question" title="Remove question" onclick="removeQuestion(this)" disabled><i class="fas fa-times"></i></button>
                            </div>
                        </div>

                        <div style="margin-top: 15px;">
                            <button type="button" class="btn-add" onclick="addQuestion()">
                                <i class="fas fa-plus"></i> Add Question
                            </button>
                            <div class="counter" style="margin-top: 10px;"><span id="question-counter">1</span> questions added</div>
                        </div>
                    </div>

                    <div class="form-footer">
                        <button type="submit" class="btn-primary">
                            <i class="fas fa-save"></i> Create Test
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Add new question
        function addQuestion() {
            const list = document.getElementById("question-list");
            const items = list.querySelectorAll('.question-item');

            // Enable delete buttons on all existing questions if this will be the second question
            if (items.length === 1) {
                items[0].querySelector('.remove-question').disabled = false;
            }

            const questionDiv = document.createElement("div");
            questionDiv.className = "question-item";

            const input = document.createElement("input");
            input.type = "text";
            input.name = "questions[]";
            input.className = "question-input";
            input.required = true;
            input.placeholder = "Enter question text";

            const removeBtn = document.createElement("button");
            removeBtn.type = "button";
            removeBtn.className = "remove-question";
            removeBtn.title = "Remove question";
            removeBtn.innerHTML = '<i class="fas fa-times"></i>';
            removeBtn.onclick = function() {
                removeQuestion(this);
            };

            questionDiv.appendChild(input);
            questionDiv.appendChild(removeBtn);
            list.appendChild(questionDiv);

            updateQuestionCounter();
            input.focus();
        }

        // Remove question
        function removeQuestion(button) {
            const list = document.getElementById("question-list");
            const item = button.parentNode;
            list.removeChild(item);

            // If only one question remains, disable its delete button
            const items = list.querySelectorAll('.question-item');
            if (items.length === 1) {
                items[0].querySelector('.remove-question').disabled = true;
            }

            updateQuestionCounter();
        }

        // Update question counter
        function updateQuestionCounter() {
            const count = document.querySelectorAll('.question-item').length;
            document.getElementById('question-counter').textContent = count;
        }

        // Character counter for description
        document.getElementById('test_description').addEventListener('input', function() {
            const count = this.value.length;
            document.getElementById('desc-counter').textContent = count;

            if (count > 500) {
                this.value = this.value.substring(0, 500);
                document.getElementById('desc-counter').textContent = 500;
            }
        });

        // Form validation
        document.getElementById('test-form').addEventListener('submit', function(e) {
            const questions = document.querySelectorAll('input[name="questions[]"]');
            let valid = true;

            questions.forEach(question => {
                if (!question.value.trim()) {
                    valid = false;
                    question.classList.add('error');
                } else {
                    question.classList.remove('error');
                }
            });

            if (!valid) {
                e.preventDefault();
                alert('Please fill in all question fields');
            }
        });
    </script>
</body>

</html>