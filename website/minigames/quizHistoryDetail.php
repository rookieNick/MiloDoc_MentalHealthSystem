<?php
session_start();
include(__DIR__ . '../../connection.php');

$playId = $_GET['playId'];

$query = "SELECT qq.questionText, qq.optionA, qq.optionB, qq.optionC, qq.optionD, qq.correctAnswer, qg.userAnswer 
          FROM quizgamequestions qg 
          JOIN quizquestion qq ON qg.questionId = qq.questionId 
          WHERE qg.playId = ?";
$stmt = $database->prepare($query);
$stmt->bind_param("i", $playId);
$stmt->execute();
$result = $stmt->get_result();

$questions = [];
while ($row = $result->fetch_assoc()) {
    $questions[] = $row;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Quiz Details</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #fdfdfd;
            margin: 0;
            padding: 0;
        }
        .top-left-btns, .top-right-btns {
            position: fixed;
            top: 10px;
            z-index: 1000;
        }
        .top-left-btns { left: 10px; }
        .top-right-btns { right: 10px; }
        .top-left-btns button, .top-right-btns button {
            padding: 10px 15px;
            background: #f4f4f4;
            color: black;
            border: none;
            border-radius: 6px;
            border: 1px solid black;
            cursor: pointer;
        }
        .container {
            max-width: 800px;
            margin: 100px auto 30px;
        }
        h1 {
            text-align: center;
            color: #333;
        }
        .question-box {
            background: #f4f4f4;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
        }
        .question-box h2 {
            margin-bottom: 15px;
        }
        .option {
            padding: 12px;
            margin-bottom: 10px;
            border-radius: 8px;
            background: #e0e0e0;
            cursor: default;
        }
        .correct {
            background: #28a745;
            color: white;
        }
        .wrong {
            background: #dc3545;
            color: white;
        }
        body {
            background-image: url('/img/quizHistoryBackground.jpeg');
            background-size: cover; /* Ensure the image covers the entire page */
            background-position: center center; /* Center the image */
            background-repeat: no-repeat; /* Prevent the image from repeating */
            background-attachment: fixed; /* Optional: Keep the background fixed when scrolling */
        }
    </style>
</head>
<body>

<div class="top-left-btns">
    <button onclick="history.back()">Back</button>
</div>

<div class="container">
    <h1>Quiz Details</h1>

    <?php foreach ($questions as $index => $q): ?>
        <div class="question-box">
            <h2>Q<?= $index + 1 ?>: <?= htmlspecialchars($q['questionText']) ?></h2>
            <?php
            $options = ['A', 'B', 'C', 'D'];
            foreach ($options as $opt):
                $answerText = htmlspecialchars($q["option$opt"]);
                $classes = '';
                if ($q['correctAnswer'] === $opt) {
                    $classes = 'correct';
                }
                if ($q['userAnswer'] === $opt && $q['correctAnswer'] !== $opt) {
                    $classes = 'wrong';
                }
                ?>
                <div class="option <?= $classes ?>"><?= $opt ?>: <?= $answerText ?></div>
            <?php endforeach; ?>
        </div>
    <?php endforeach; ?>
</div>
</body>
</html>
