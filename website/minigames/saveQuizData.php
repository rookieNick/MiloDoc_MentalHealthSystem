<?php 
require_once(__DIR__ . '/gamedb.php');

session_start();
$questions = $_SESSION['quiz_questions'];
$userAnswers = $_SESSION['user_answers'];
$questionRecords = $_SESSION['question_records'];
$totalQuestions = count($questions);
$correctCount = 0;

foreach ($questions as $index => $question) {
    if ($userAnswers[$index] === $question['correct']) {
        $correctCount++;
    }
}

$percentageScore = ($correctCount / $totalQuestions) * 100;
$userEmail = $_SESSION['user']; // Ensure user is logged in
$timeUsed = round(time() - $_SESSION['quiz_start_time'], 2); // Calculate time taken

$bonus = max(0, 200 - $timeUsed); // Faster completion gets more bonus points
$finalScore = $percentageScore * 10 * $bonus / 10; // Final score formula   max 20000points

$newPlayId = generateNewPlayId($userEmail, 3); // Generate new play ID

$stmt = $database->prepare("INSERT INTO quizgame (playId, timeUsed, percentageScore, finalScore) VALUES (?, ?, ?, ?)");
$stmt->bind_param("iddi", $newPlayId, $timeUsed, $percentageScore, $finalScore);
$stmt->execute();

// Insert question-answer records
$questionStmt = $database->prepare("INSERT INTO quizgamequestions (playId, questionId, userAnswer, isCorrect) VALUES (?, ?, ?, ?)");
foreach ($questionRecords as $record) {
    $questionStmt->bind_param("issi", $newPlayId, $record['questionId'], $record['userAnswer'], $record['isCorrect']);
    $questionStmt->execute();
}

$_SESSION['playId'] = $newPlayId;

header("Location: submitQuiz.php");
?>