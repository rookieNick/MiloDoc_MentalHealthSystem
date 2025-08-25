<?php
require_once(__DIR__ . '/gamedb.php');
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['user'])) {
    echo json_encode(["success" => false, "message" => "User not logged in"]);
    exit;
}

$userEmail = $_SESSION['user'];
$gameId = isset($_POST['gameId']) ? intval($_POST['gameId']) : 0;
$action = $_POST['action'] ?? '';

if (!$gameId || !in_array($action, ['add', 'remove'])) {
    echo json_encode(["success" => false, "message" => "Invalid data"]);
    exit;
}

// Get patient ID
$stmt = $database->prepare("SELECT pid FROM patient WHERE pemail = ?");
$stmt->bind_param("s", $userEmail);
$stmt->execute();
$result = $stmt->get_result();
$pid = $result->fetch_assoc()['pid'];

if ($action === 'add') {
    $stmt = $database->prepare("INSERT IGNORE INTO favoritegames (pid, gameId) VALUES (?, ?)");
    $stmt->bind_param("ii", $pid, $gameId);
    $stmt->execute();
    echo json_encode(["success" => true, "message" => "Game added to favorites"]);
} elseif ($action === 'remove') {
    $stmt = $database->prepare("DELETE FROM favoritegames WHERE pid = ? AND gameId = ?");
    $stmt->bind_param("ii", $pid, $gameId);
    $stmt->execute();
    echo json_encode(["success" => true, "message" => "Game removed from favorites"]);
}
?>