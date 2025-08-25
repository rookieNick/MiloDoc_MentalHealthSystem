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

// Check if patient_test_id is passed
if (!isset($_GET['patient_test_id']) || empty($_GET['patient_test_id'])) {
    die("Test attempt not specified.");
}

$patientTestId = $_GET['patient_test_id'];
if (empty($patientTestId)) {
    die("Invalid test ID.");
}


// ---------------------------------------------------------------------
// 3) Fetch test details for this patient
// ---------------------------------------------------------------------
$testStmt = $database->prepare("
    SELECT pt.patient_test_id,
           pt.test_id,
           pt.started_at,
           pt.completed_at,
           t.test_name,
           t.test_description,
           t.test_category
      FROM patient_test pt
      JOIN test t ON pt.test_id = t.test_id
     WHERE pt.patient_test_id = ?
       AND pt.pid = ?
");
$testStmt->bind_param("si", $patientTestId, $userid);
$testStmt->execute();
$testResult = $testStmt->get_result();

if ($testResult->num_rows === 0) {
    die("Test not found or you do not have access to this test.");
}
$testDetails = $testResult->fetch_assoc();
$testStmt->close();

$testId        = (int) $testDetails['test_id'];
$testName      = trim($testDetails['test_name']);
$testDesc      = $testDetails['test_description'];
$testCategory  = trim($testDetails['test_category']);
$startedAt     = $testDetails['started_at'];
$completedAt   = $testDetails['completed_at'];
// ---------------------------------------------------------------------
// 4) Fetch only the answers for this attempt & this test
// ---------------------------------------------------------------------
$answers = [];

$answerSql = "
    SELECT
      tq.question_id,
      tq.question_text,
      pa.answer_value
    FROM patient_answer pa
    JOIN test_question tq
      ON pa.question_id = tq.question_id
     AND tq.test_id = ?
    WHERE pa.patient_test_id = ?
";
$answerStmt = $database->prepare($answerSql);
$answerStmt->bind_param("is", $testId, $patientTestId);
$answerStmt->execute();
$answerResult = $answerStmt->get_result();

while ($row = $answerResult->fetch_assoc()) {
    $answers[] = $row;
}
$answerStmt->close();

// ---------------------------------------------------------------------
// 5) Calculate total score and average
// ---------------------------------------------------------------------
$totalScore = 0;
$answerCount = count($answers);
foreach ($answers as $ans) {
    $totalScore += (int)$ans['answer_value'];
}
$averageScore = ($answerCount > 0) ? round($totalScore / $answerCount, 2) : 0;

// ---------------------------------------------------------------------
// 6) Calculate seriousness level
// ---------------------------------------------------------------------
$level = "";
if ($answerCount > 0) {
    $maxScore = $answerCount * 5;  // Assuming maximum score per question is 5
    $scorePercentage = round(($totalScore / $maxScore) * 100);

    if ($scorePercentage <= 25) {
        $level = "Level 1"; // Least serious
        $severityText = "Minimal";
        $severityColor = "#4caf50"; // Green
    } elseif ($scorePercentage <= 50) {
        $level = "Level 2";
        $severityText = "Mild";
        $severityColor = "#8bc34a"; // Light green
    } elseif ($scorePercentage <= 75) {
        $level = "Level 3";
        $severityText = "Moderate";
        $severityColor = "#ff9800"; // Orange
    } else {
        $level = "Level 4"; // Most serious
        $severityText = "Significant";
        $severityColor = "#f44336"; // Red
    }
} else {
    $scorePercentage = 0;
    $level = "N/A";
    $severityText = "N/A";
    $severityColor = "#9e9e9e"; // Gray
}

// ---------------------------------------------------------------------
// 7) Fetch level description based on seriousness level
// ---------------------------------------------------------------------
// We compare test_category (not test_name) with test_result_description.category

$testCategory = trim($testCategory);
$level        = trim($level);

// Try to fetch based on category + level
$stmt = $database->prepare("
    SELECT description
      FROM test_result_description
     WHERE LOWER(category) = LOWER(?)
       AND LOWER(level) = LOWER(?)
");
$stmt->bind_param("ss", $testCategory, $level);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    $description = $row['description'];
} else {
    $description = "No description found.";
}

// Transform answer values to more meaningful responses
function getAnswerText($value)
{
    switch ($value) {
        case 1:
            return "Strongly Disagree";
        case 2:
            return "Disagree";
        case 3:
            return "Neutral";
        case 4:
            return "Agree";
        case 5:
            return "Strongly Agree";
        default:
            return $value; // Fallback to original value if not 1-5
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Test Result - <?php echo htmlspecialchars($testName); ?></title>
    <link rel="stylesheet" href="../css/animations.css">
    <link rel="stylesheet" href="../css/main.css">
    <link rel="stylesheet" href="../css/admin.css">
    <style>
        .result-container {
            max-width: 1000px;
            margin: 40px auto;
            background: #ffffff;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.1);
        }

        .result-container h1 {
            margin-bottom: 24px;
            color: #2c3e50;
            text-align: center;
            font-size: 28px;
        }

        .result-summary {
            display: flex;
            justify-content: center;
            gap: 40px;
            margin: 30px 0;
        }

        .score-card {
            text-align: center;
            padding: 15px 25px;
            background: #f8f9fa;
            border-radius: 8px;
            min-width: 140px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }

        .severity-card {
            text-align: center;
            padding: 20px 25px;
            border-radius: 8px;
            min-width: 200px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            color: white;
        }

        .severity-label {
            font-size: 16px;
            margin: 0 0 5px 0;
            font-weight: 500;
        }

        .severity-value {
            font-size: 32px;
            font-weight: bold;
            margin: 5px 0;
        }

        .score-value {
            font-size: 36px;
            font-weight: bold;
            color: #3498db;
            margin: 0;
        }

        .score-label {
            font-size: 16px;
            color: #7f8c8d;
            margin: 5px 0 0 0;
        }

        .assessment-section {
            background: #f1f8ff;
            border-left: 4px solid #3498db;
            padding: 15px 20px;
            margin: 30px 0;
            border-radius: 4px;
        }

        .assessment-section h2 {
            margin-top: 0;
            color: #3498db;
            font-size: 20px;
        }

        .assessment-content p {
            margin: 10px 0;
            font-size: 16px;
            line-height: 1.6;
            color: #34495e;
        }

        .completion-time {
            text-align: right;
            color: #7f8c8d;
            font-size: 14px;
            margin-bottom: 30px;
        }

        .completion-time span {
            font-weight: bold;
        }

        .answer-table {
            width: 100%;
            margin-top: 20px;
            border-collapse: collapse;
        }

        .answer-table th {
            background: #f8f9fa;
            padding: 12px;
            text-align: left;
            border-bottom: 2px solid #ddd;
            color: #2c3e50;
        }

        .answer-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        .response-indicator {
            display: inline-block;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            margin-right: 8px;
        }

        .back-link {
            display: inline-block;
            margin-top: 30px;
            padding: 12px 24px;
            background: #3498db;
            color: #fff;
            text-decoration: none;
            border-radius: 6px;
            transition: background 0.3s;
            text-align: center;
        }

        .back-link:hover {
            background: #2980b9;
        }

        .score-explanation {
            font-size: 14px;
            color: #7f8c8d;
            text-align: center;
            margin-top: 15px;
            font-style: italic;
        }

        .total-score {
            margin-top: 15px;
            font-size: 16px;
            font-weight: bold;
            color: #34495e;
            text-align: center;
        }

        .score-note {
            font-size: 14px;
            color: #7f8c8d;
            text-align: center;
            margin-top: 5px;
            font-style: italic;
        }

        .score-info {
            margin-top: 20px;
            text-align: center;
        }
    </style>
</head>

<body>
    <div class="container">
        <?php include(__DIR__ . '/patientMenu.php'); ?>
        <div class="dash-body">
            <div class="result-container">
                <h1><?php echo htmlspecialchars($testName); ?> - Test Result</h1>

                <div class="result-summary">
                    <div class="severity-card" style="background-color: <?php echo $severityColor; ?>">
                        <p class="severity-label">Assessment Level</p>
                        <p class="severity-value"><?php echo $severityText; ?></p>
                    </div>
                </div>

                <div class="score-info">
                    <p class="total-score">Total Score: <?php echo $totalScore; ?> / <?php echo $answerCount * 5; ?></p>
                    <p class="score-note">Lower scores indicate better mental health</p>
                </div>

                <p class="score-explanation">Based on your responses, this assessment indicates a <?php echo strtolower($severityText); ?> level that may require attention.</p>

                <div class="assessment-section">
                    <h2>Your Assessment</h2>
                    <div class="assessment-content">
                        <p><?php echo htmlspecialchars($description); ?></p>
                    </div>
                </div>

                <?php if ($completedAt) {
                    // Format the datetime from '2025-04-27 02:48:21' to 'Apr 27 2:48AM'
                    $dateObj = new DateTime($completedAt);
                    $formattedDate = $dateObj->format('M d g:iA');
                ?>
                    <p class="completion-time"><span>Completed:</span> <?php echo $formattedDate; ?></p>
                <?php } ?>

                <h2>Your Responses</h2>
                <table class="answer-table">
                    <thead>
                        <tr>
                            <th>Question</th>
                            <th>Your Response</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($answers as $answer) {
                            $value = (int)$answer['answer_value'];
                            // Calculate color intensity based on answer value (1-5)
                            $colorIntensity = ($value - 1) / 4; // Normalized to 0-1 range
                            $dotColor = "";

                            if ($colorIntensity <= 0.25) {
                                $dotColor = "#4caf50"; // Green for low values
                            } elseif ($colorIntensity <= 0.5) {
                                $dotColor = "#8bc34a"; // Light green
                            } elseif ($colorIntensity <= 0.75) {
                                $dotColor = "#ff9800"; // Orange
                            } else {
                                $dotColor = "#f44336"; // Red for high values
                            }
                        ?>
                            <tr>
                                <td><?php echo htmlspecialchars($answer['question_text']); ?></td>
                                <td>
                                    <span class="response-indicator" style="background-color: <?php echo $dotColor; ?>"></span>
                                    <?php echo htmlspecialchars(getAnswerText($value)); ?>
                                </td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>

                <a href="patientTestHistory.php" class="back-link">Back to Test History</a>
            </div>
        </div>
    </div>
</body>

</html>