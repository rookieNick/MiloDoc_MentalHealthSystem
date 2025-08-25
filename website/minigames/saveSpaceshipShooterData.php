<?php
session_start();
include(__DIR__ . '../../connection.php');
include(__DIR__ . '/gamedb.php');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $userEmail = $_SESSION["user"];
    $gameId = $_POST["gameId"];
    $enemiesDestroyed = (int) $_POST["enemiesDestroyed"];
    $bulletsFired = (int) $_POST["bulletsFired"];
    $accuracy = (float) $_POST["accuracy"];
    $survivalTime = (float) $_POST["survivalTime"];
    $finalScore = (int) $_POST["finalScore"];

    // Generate a new play ID
    $newPlayId = generateNewPlayId($userEmail, $gameId);

    // Save game data using the function
    $result = saveShooterGameData($newPlayId, $enemiesDestroyed, $bulletsFired, $accuracy, $survivalTime, $finalScore);

    echo json_encode($result);
}
?>