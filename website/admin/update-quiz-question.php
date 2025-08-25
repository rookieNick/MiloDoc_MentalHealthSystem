<?php
include("../connection.php");
include(__DIR__ . '/../minigames/gamedb.php');

session_start();

if ($_POST) {
    $questionId = $_POST['questionId'];
    $questionText = $_POST['questionText'];
    $optionA = $_POST['optionA'];
    $optionB = $_POST['optionB'];
    $optionC = $_POST['optionC'];
    $optionD = $_POST['optionD'];
    $correctAnswer = $_POST['correctAnswer'];
    $status = $_POST['status'];

    // Error code guide:
    // error=1 → missing field
    // error=2 → invalid correctAnswer
    // error=3 → update failed
    // error=4 → success

    // Validate empty fields
    if (
        empty($questionText) || empty($optionA) || empty($optionB) ||
        empty($optionC) || empty($optionD)
    ) {
        $error = '1';
    } elseif (!in_array($correctAnswer, ['A', 'B', 'C', 'D'])) {
        $error = '2';
    } else {
        // Try to update
        $updateSuccess = updateQuizQuestion($questionId, $questionText, $optionA, $optionB, $optionC, $optionD, $correctAnswer, $status);

        if ($updateSuccess) {
            $error = '4'; // Success
        } else {
            $error = '3'; // Failed to update
        }
    }

    // Redirect with error or success
    header("Location: manageQuiz.php?action=edit&error=$error&id=$questionId");
    exit();

} else {
    // If form not submitted properly
    header("Location: manageQuiz.php?action=edit&error=1&id=0");
    exit();
}
?>