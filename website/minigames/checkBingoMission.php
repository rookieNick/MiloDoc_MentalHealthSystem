<?php
session_start();
include(__DIR__ . '../../connection.php');

$userEmail = $_SESSION['user'] ?? ''; // Ensure user session exists

if (!$userEmail) {
    header("Location: bingo.php"); // Redirect if no user
    exit;
}

// Ensure session array exists
if (!isset($_SESSION['completed_missions'])) {
    $_SESSION['completed_missions'] = [];
}

// Mission 1: Played 3 different games after last Monday
$lastMonday = date('Y-m-d', strtotime('last Monday'));

$stmt = $database->prepare("
    SELECT COUNT(DISTINCT gameId) AS gameCount 
    FROM gameplay 
    WHERE userEmail = ? AND playDate >= ?
");
$stmt->bind_param("ss", $userEmail, $lastMonday);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();

$_SESSION['completed_missions'][1] = ($row['gameCount'] >= 3);

// Add more missions here as needed...

// Redirect back to the bingo page
header("Location: bingo.php");
exit;