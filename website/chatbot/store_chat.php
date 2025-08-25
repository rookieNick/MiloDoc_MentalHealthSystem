<?php
// store_chat.php
session_start();
require_once __DIR__ . '/../connection.php';

date_default_timezone_set('Asia/Kuala_Lumpur');


$user_id = $_POST['user_id'];
$user_message = $_POST['user_message'];
$bot_message = $_POST['bot_message'];
$is_suicidal = isset($_POST['is_suicidal']) ? (int)$_POST['is_suicidal'] : 0; // Default to 0 (false)
$confidence = $_POST['confidence'] ?? 0.0; // Default to 0.0

// Generate unique IDs for each message
$user_conversation_id = uniqid("C");
$bot_conversation_id  = uniqid("C");

if ($user_message) {
    // Insert user message
    $sql1 = "INSERT INTO chatbot_conversation 
         (conversation_id, user_id, message, ResponseByUser, datetime, is_suicidal, confidence) 
         VALUES (?, ?, ?, 1, NOW(), ?, ?)";
    $stmt1 = $database->prepare($sql1);
    $stmt1->bind_param(
        "sssid",
        $user_conversation_id,
        $user_id,
        $user_message,
        $is_suicidal,
        $confidence
    );

    $stmt1->execute();
}


if ($bot_message) {
    // Insert bot message
    $sql2 = "INSERT INTO chatbot_conversation 
         (conversation_id, user_id, message, ResponseByUser, datetime) 
         VALUES (?, ?, ?, 0, NOW())";
    $stmt2 = $database->prepare($sql2);
    $stmt2->bind_param(
        "sss",
        $bot_conversation_id,
        $user_id,
        $bot_message
    );
    $stmt2->execute();
}


echo "Success";
