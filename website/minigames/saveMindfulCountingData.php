<?php
session_start();
include(__DIR__ . '../../connection.php');
include(__DIR__ . '/gamedb.php');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $userEmail = $_SESSION["user"];
    $gameId = $_POST["gameId"];
    $difficultyLevel = $_POST["difficultyLevel"];
    $gameTimeOut = intval($_POST["gameTimeOut"]);
    $birdCount = intval($_POST["birdCount"]);
    $finalScore = intval($_POST["finalScore"]);

    // Generate a new play ID
    $newPlayId = generateNewPlayId($userEmail, $gameId);

    // Save game data using the function
    $result = saveMindfulCountingData($newPlayId, $difficultyLevel, $gameTimeOut, $birdCount, $finalScore);

    echo json_encode($result);
}
?>