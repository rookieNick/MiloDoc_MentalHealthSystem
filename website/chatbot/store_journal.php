<?php
session_start();
require_once __DIR__ . '/../connection.php';

date_default_timezone_set('Asia/Kuala_Lumpur');


// Ensure user_id and summary are available from POST
if (!isset($_POST["user_id"]) || !isset($_POST["summary"])) {
    echo json_encode(["error" => "Missing parameters"]);
    exit;
}

$user_id = $_POST["user_id"];
$summary = $_POST["summary"];
$today = date("Y-m-d");
$now = date("Y-m-d H:i:s");

// First, check if a journal entry already exists for this user for today's date.
$sql_check = "SELECT journal_id FROM journal WHERE user_id = ? AND date_happened = ?";
$stmt_check = $database->prepare($sql_check);
$stmt_check->bind_param("is", $user_id, $today);
$stmt_check->execute();
$result_check = $stmt_check->get_result();

if ($result_check->num_rows > 0) {
    // Journal exists; update the existing entry.
    $row = $result_check->fetch_assoc();
    $journal_id = $row["journal_id"];
    
    $sql_update = "UPDATE journal SET content = ?, updated_date = ? WHERE journal_id = ?";
    $stmt_update = $database->prepare($sql_update);
    $stmt_update->bind_param("sss", $summary, $now, $journal_id);
    $stmt_update->execute();
    
    if ($stmt_update->affected_rows > 0) {
        echo json_encode([
            "status" => "success", 
            "journal_id" => $journal_id, 
            "summary" => $summary,
            "action" => "updated"
        ]);
    } else {
        echo json_encode(["status" => "error", "message" => "Failed to update summary."]);
    }
} else {
    // No journal exists; insert a new entry.
    $journal_id = uniqid("J"); // Generate a new unique journal ID
    
    $sql_insert = "INSERT INTO journal (journal_id, user_id, content, date_happened, created_date, updated_date)
                   VALUES (?, ?, ?, ?, ?, ?)";
    $stmt_insert = $database->prepare($sql_insert);
    $stmt_insert->bind_param("sissss", $journal_id, $user_id, $summary, $today, $now, $now);
    $stmt_insert->execute();
    
    if ($stmt_insert->affected_rows > 0) {
        echo json_encode([
            "status" => "success", 
            "journal_id" => $journal_id, 
            "summary" => $summary,
            "action" => "inserted"
        ]);
    } else {
        echo json_encode(["status" => "error", "message" => "Failed to insert summary."]);
    }
}
?>
