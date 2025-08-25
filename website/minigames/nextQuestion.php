<?php
session_start();
date_default_timezone_set('Asia/Kuala_Lumpur');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $index = (int) $_POST['questionIndex'];

    // Set quiz start time only when first answer is submitted
    if (!isset($_SESSION['quiz_start_time']) && $index == 0) {
        $_SESSION['quiz_start_time'] = time();
    }

    // Store the answer
    if (isset($_POST['answer'])) {
        $userAnswer = $_POST['answer'];
        $_SESSION['user_answers'][$index] = $userAnswer;

        // Save detailed record for later DB insert
        $question = $_SESSION['quiz_questions'][$index];
        $isCorrect = ($userAnswer === $question['correct']) ? 1 : 0;

        $_SESSION['question_records'][$index] = [
            'questionId' => $question['id'],
            'userAnswer' => $userAnswer,
            'isCorrect' => $isCorrect
        ];
    }

    // Move to next question
    $_SESSION['quiz_index']++;

    // Redirect to next question or submit page
    if ($_SESSION['quiz_index'] < count($_SESSION['quiz_questions'])) {
        header("Location: positivityQuiz.php");
        exit;
    } else {
        header("Location: saveQuizData.php");
        exit;
    }
}
?>
