<?php
session_start();
require_once __DIR__ . '/../connection.php';

date_default_timezone_set('Asia/Kuala_Lumpur');

// Ensure required POST parameters exist
if (!isset($_POST["journal_id"]) || !isset($_POST["journal_content"]) || !isset($_POST["refined_journal"])) {
    echo json_encode(["error" => "Missing parameters"]);
    exit;
}

$journal_id = $_POST["journal_id"];
$original_journal_content = $_POST["journal_content"];
$user_feedback = $_POST["user_feedback"];
$refined_journal = $_POST["refined_journal"];
$created_date = date("Y-m-d H:i:s");

// Generate unique feedback ID
$journal_feedback_id = uniqid("JF");

// Insert into journal_feedback
$sql_feedback = "INSERT INTO journal_feedback (journal_feedback_id, journal_id, feedback, refined_journal_content,original_journal_content, created_date) 
                 VALUES (?, ?, ?, ?, ?,?)";
$stmt_feedback = $database->prepare($sql_feedback);
$stmt_feedback->bind_param("ssssss", $journal_feedback_id, $journal_id, $user_feedback, $refined_journal,$original_journal_content, $created_date);
$stmt_feedback->execute();

$feedback_inserted = $stmt_feedback->affected_rows > 0;

// Also update content and updated_date in journal table
$sql_update_journal = "UPDATE journal SET content = ?, updated_date = ? WHERE journal_id = ?";
$stmt_update = $database->prepare($sql_update_journal);
$stmt_update->bind_param("sss", $refined_journal, $created_date, $journal_id);
$stmt_update->execute();

$journal_updated = $stmt_update->affected_rows > 0;

if ($feedback_inserted || $journal_updated) {
    echo json_encode([
        "status" => "success"
    ]);
} else {
    echo json_encode(["status" => "error", "message" => "No changes made."]);
}
?>
