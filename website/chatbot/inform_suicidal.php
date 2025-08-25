<?php
//inform_suicidal.php
session_start();
require_once __DIR__ . '/../connection.php';
require_once(__DIR__ . '/../utils/emailSender.php');
date_default_timezone_set('Asia/Kuala_Lumpur');

header('Content-Type: application/json');
ob_clean(); // Clears unwanted output


// Validate input
if (!isset($_POST['user_id']) || !isset($_POST['parent_email'])) {
    echo json_encode(["success" => false, "message" => "Missing required fields."]);
    exit;
}

$user_id = $_POST['user_id'];
$parent_email = $_POST['parent_email'];


// Fetch today's conversation from the database
$query = "SELECT message, ResponseByUser, is_suicidal, confidence 
          FROM chatbot_conversation 
          WHERE user_id = ? AND DATE(datetime) = CURDATE()
          ORDER BY datetime ASC";
$stmt = $database->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$conversation = [];
while ($row = $result->fetch_assoc()) {
    $conversation[] = $row;
}

// Check if there's a conversation to send
if (empty($conversation)) {
    echo json_encode(["success" => false, "message" => "No conversation found."]);
    exit;
}



// Send OTP to email
$emailSender = new EmailSender();


// Send conversation via email
if ($emailSender->sendEmail($parent_email, $conversation)) {
    echo json_encode(["success" => true, "message" => "Informed guardian."]);
} else {
    echo json_encode(["success" => false, "message" => "Failed to inform guardian."]);
}
