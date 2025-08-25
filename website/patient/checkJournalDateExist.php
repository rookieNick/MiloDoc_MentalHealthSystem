<?php
session_start();
require_once("../connection.php");

if (!isset($_POST['user_id'], $_POST['date'])) {
    echo "NOT_FOUND";
    exit();
}

$user_id = intval($_POST['user_id']);
$date = $_POST['date'];

$sql = "SELECT 1 FROM journal WHERE user_id = ? AND date_happened = ?";
$stmt = $database->prepare($sql);
$stmt->bind_param("is", $user_id, $date);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    echo "FOUND";
} else {
    echo "NOT_FOUND";
}

?>