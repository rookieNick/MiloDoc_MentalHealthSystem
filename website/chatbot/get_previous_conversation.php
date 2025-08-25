<?php
session_start();
require_once __DIR__ . '/../connection.php';

date_default_timezone_set('Asia/Kuala_Lumpur');


// Check if a user_id is passed as a GET parameter; otherwise, use the session.
if (isset($_GET["user_id"])) {
    $user_id = $_GET["user_id"];
}

$sql = "SELECT * FROM chatbot_conversation 
        WHERE user_id = ? 
        ORDER BY datetime ASC";
$stmt = $database->prepare($sql);
// $stmt->bind_param("s", $user_id);
$stmt->bind_param("i", $user_id);

$stmt->execute();
$result = $stmt->get_result();

$conversation = "";
while ($row = $result->fetch_assoc()) {
    $date = date("d M Y", strtotime($row["datetime"])); // Format date (DD Month YYYY)
    $time = date("H:i", strtotime($row["datetime"])); // Format time (HH:MM)
    
    if ($row["ResponseByUser"] == 1) {
        $conversation .= "[$date $time] User: " . $row["message"] . "\n";
    } else {
        $conversation .= "[$date $time] Bot: " . $row["message"] . "\n";
    }
}

echo json_encode(["conversation" => $conversation]);
?>
