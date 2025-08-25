<?php
session_start();
include("../connection.php");
include(__DIR__ . '/../minigames/gamedb.php');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $questionText = trim($_POST["questionText"] ?? '');
    $optionA = trim($_POST["optionA"] ?? '');
    $optionB = trim($_POST["optionB"] ?? '');
    $optionC = trim($_POST["optionC"] ?? '');
    $optionD = trim($_POST["optionD"] ?? '');
    $correctAnswer = trim($_POST["correctAnswer"] ?? '');

    // validation
    if (
        empty($questionText) || empty($optionA) || empty($optionB) ||
        empty($optionC) || empty($optionD) || empty($correctAnswer)
    ) {
        header("Location: manageQuiz.php?action=add&error=2");
        exit();
    }

    // Insert into database
    $result = insertQuizQuestion($questionText, $optionA, $optionB, $optionC, $optionD, $correctAnswer);

    if ($result["success"]) {
        header("Location: manageQuiz.php?action=add&error=4");
        exit();
    } else {
        if (str_contains($result["message"], "Invalid correct answer")) {
            header("Location: manageQuiz.php?action=add&error=1");
        } else {
            header("Location: manageQuiz.php?action=add&error=3");
        }
        exit();
    }
} else {
    // Redirect if accessed directly
    header("Location: manageQuiz.php");
    exit();
}
?>
