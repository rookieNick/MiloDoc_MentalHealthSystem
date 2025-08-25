<?php
session_start();
include(__DIR__ . '../../connection.php');
include(__DIR__ . '/gamedb.php');

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $userEmail = $_SESSION["user"]; // Get the user email from session
    $gameId = $_POST["gameId"]; // Get the game ID from JavaScript

    $newPlayId = generateNewPlayId($userEmail, $gameId);
    echo json_encode(["playId" => $newPlayId]); // Return the new play ID
}
?>