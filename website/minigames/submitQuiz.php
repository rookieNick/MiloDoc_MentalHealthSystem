<?php
session_start();
date_default_timezone_set('Asia/Kuala_Lumpur');

include(__DIR__ . '../../connection.php');
include(__DIR__ . '/gamedb.php');

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quiz Completed</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            text-align: center;
            margin: 0;
            padding: 0;
            background-size: cover;
            background-attachment: fixed;
            background-Repeat: "no-repeat";  
            background-Position: "center";  
        }
        .container {
            max-width: 600px;
            margin: auto;
            transform: scale(1.25);
            transform-origin: top center;
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) scale(1.25);
            background: rgba(255, 255, 255, 0.8);
            padding: 20px;
            border-radius: 10px;
        }
        h2, p {
            margin: 10px 0;
        }
        .back-button {
            display: inline-block;
            margin-top: 20px;
            padding: 10px 15px;
            font-size: 18px;
            background: green;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
        }
        .back-button:hover {
            background: darkgreen;
        }
    </style>
</head>
<?php include(__DIR__ . '/ratingBox.php'); ?>
<body>
    
<div class="container" id="quiz-result-box">
    <?php 
    $playId = $_SESSION['playId'];
    $quizData = getQuizGameData($playId);
    $timeUsed = $quizData['timeUsed'];
    $percentageScore = $quizData['percentageScore'];
    $finalScore = $quizData['finalScore'];
    $totalQuestions = 10;
    ?>
    <h2>Quiz Completed!</h2>
    <p>You got <b><?php echo $percentageScore; ?></b>% correct!</p>
    <p>Time Used: <b><?php echo $timeUsed; ?> seconds</b></p>
    <p>Final Score: <b><?php echo $finalScore; ?></b></p>
    <a href="/minigames/gameLists.php" class="back-button" onclick="clearQuizSession()">Complete</a>
    <br><br>
    <button onclick="rateGame(3)">Rate The Game</button>  <!-- gameId 3 -->
</div>

</body>
</html>

<script>
    // Apply the background image
    let backgroundImage = "<?php echo $_SESSION['quizBackground']; ?>";
    document.body.style.backgroundImage = `url(${backgroundImage})`;

    function clearQuizSession() {
        fetch("clearQuizSession.php"); // Calls a PHP script to clear session data
    }
    
</script>