<?php
session_start();
include(__DIR__ . '../../connection.php');
date_default_timezone_set('Asia/Kuala_Lumpur');
include(__DIR__ . '/gamedb.php');

$_SESSION["currentGame"] = 3;

$gameId = 3;
$gameStatus = getGameStatusById($gameId);
if ($gameStatus == 0) {
    $gameName = getGameNameById($gameId);
    $gameNameUrl = urlencode($gameName);
    header("Location: /minigames/underMaintenance.php?game=" . $gameNameUrl);
    exit;
}

// Initialize only if first time, prevent user frequently refresh the question and background
// Check if quiz needs to be initialized or reset
$index = isset($_SESSION['quiz_index']) ? $_SESSION['quiz_index'] : 0;
$questionExists = isset($_SESSION['quiz_questions'][$index]);

if (!isset($_SESSION['quiz_questions']) || !$questionExists) {
    $query = "SELECT * FROM quizQuestion WHERE status='enabled' ORDER BY RAND() LIMIT 10";
    $result = $database->query($query);

    $_SESSION['quiz_questions'] = [];
    while ($row = $result->fetch_assoc()) {
        $_SESSION['quiz_questions'][] = [
            'id' => $row['questionId'],
            'question' => $row['questionText'],
            'options' => [
                'A' => $row['optionA'],
                'B' => $row['optionB'],
                'C' => $row['optionC'],
                'D' => $row['optionD']
            ],
            'correct' => $row['correctAnswer']
        ];
    }

    $_SESSION['user_answers'] = array_fill(0, count($_SESSION['quiz_questions']), null);
    $_SESSION['quiz_index'] = 0;
    // $_SESSION['quiz_start_time'] = time();
}

// Get current question
$index = $_SESSION['quiz_index'];
$question = $_SESSION['quiz_questions'][$index];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/positiveQuiz.css">
    <title>Positivity Quiz</title>
</head>
<body>
<div class="top-left-btns">
    <button id="backBtn" onclick="goBack()">Back</button>
</div>
<div class="top-right-btns">
    <button id="historyBtn" onclick="window.location.href='quizHistory.php'">History</button>
</div>
<div class="container">
    <h1>Positivity Quiz</h1>
    <div id="quiz-container">
        <form method="POST" action="nextQuestion.php">
            <input type="hidden" name="questionIndex" value="<?php echo $index; ?>">
            <div class='question-box'>
                <?php if (!empty($question['question'])) {
                    echo "<h2>Q" . ($index + 1) . ": " . htmlspecialchars($question['question']) . "</h2>";

                     foreach ($question['options'] as $key => $value): ?>
                        <div class="option" onclick="selectAnswer('<?php echo $key; ?>')" id="opt<?php echo $key; ?>">
                            <?php echo "$key: $value"; ?>
                        </div>
                    <?php endforeach;

                } else {
                    echo "<h2>Reload Page to Start New Quiz.</h2>";
                } ?>
                
            </div>
            <input type="hidden" name="answer" id="selectedAnswer">
            <button type="submit" id="next-btn" disabled>Next</button>
        </form>
    </div>
</div>

<script>
    function goBack() {
        window.location.href = "/minigames/gameLists.php";
    }

    function selectAnswer(answer) {
        document.getElementById("selectedAnswer").value = answer;

        let options = document.querySelectorAll(".option");
        options.forEach(option => option.classList.remove("selected"));

        document.getElementById("opt" + answer).classList.add("selected");

        document.getElementById("next-btn").disabled = false;
    }

    <?php
    // Randomize background image
    // Check if a background image is already stored in sessionStorage
    if (!isset($_SESSION['quizBackground'])) {
        $rand = mt_rand(1, 100); // Generate a random number between 1 and 100

        if ($rand <= 30) {
            $_SESSION['quizBackground'] = "/img/quizBackground1.jpg";
        } elseif ($rand <= 65) {
            $_SESSION['quizBackground'] = "/img/quizBackground2.jpeg";
        } else {
            $_SESSION['quizBackground'] = "/img/quizBackground3.jpeg";
        }
    }
    ?>
    
    // Apply the background image
    let backgroundImage = "<?php echo $_SESSION['quizBackground']; ?>";
    document.body.style.backgroundImage = `url(${backgroundImage})`;
</script>

</body>
</html>
