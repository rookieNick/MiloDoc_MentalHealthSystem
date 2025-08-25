<?php
session_start();
require_once __DIR__ . '/../connection.php';

date_default_timezone_set('Asia/Kuala_Lumpur');

// Check if a user_id is passed as a GET parameter; otherwise, use the session.
if (isset($_GET["user_id"])) {
    $user_id = $_GET["user_id"];
}

$database->set_charset("utf8mb4"); // Ensure Unicode support

// Retrieve last 7 journal entries (sorted by date_happened DESC)
$journal_sql = "SELECT content, date_happened FROM journal 
                WHERE user_id = ? 
                ORDER BY date_happened DESC 
                LIMIT 7";

$journal_stmt = $database->prepare($journal_sql);
$journal_stmt->bind_param("i", $user_id);
$journal_stmt->execute();
$journal_result = $journal_stmt->get_result();

$journals = [];
while ($row = $journal_result->fetch_assoc()) {
    $journals[] = "[" . $row["date_happened"] . "] Journal: " . $row["content"];
}

// Retrieve today's chatbot conversation
$chat_sql = "SELECT * FROM chatbot_conversation 
             WHERE user_id = ? 
               AND DATE(datetime) = CURDATE()
             ORDER BY datetime ASC";

$chat_stmt = $database->prepare($chat_sql);
$chat_stmt->bind_param("i", $user_id);
$chat_stmt->execute();
$chat_result = $chat_stmt->get_result();

$conversation = [];
while ($row = $chat_result->fetch_assoc()) {
    if ($row["ResponseByUser"] == 1) {
        $conversation[] = "[Today] User: " . $row["message"];
    } else {
        $conversation[] = "[Today] Bot: " . $row["message"];
    }
}

// Combine both journal and conversation history
$full_memory = array_merge($journals, $conversation);

echo json_encode(["memory" => implode("\n", $full_memory)]);
?>
