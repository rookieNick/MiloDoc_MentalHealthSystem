<?php
require_once __DIR__ . '/../connection.php';

if (!isset($_POST['journal_id'], $_POST['journal_feedback_id'])) {
    http_response_code(400);
    echo "Invalid input.";
    exit;
}

$journal_id = $_POST['journal_id'];
$feedback_id = $_POST['journal_feedback_id'];

$stmt = $database->prepare("SELECT * FROM journal_feedback WHERE journal_feedback_id = ?");
$stmt->bind_param("s", $feedback_id);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    $original = $row['original_journal_content'];

    $update = $database->prepare("UPDATE journal SET content = ?, updated_date = NOW() WHERE journal_id = ?");
    $update->bind_param("ss", $original, $journal_id);
    $update->execute();

    echo nl2br(htmlspecialchars($original)); // This will be injected back into #journal-content
} else {
    http_response_code(404);
    echo "Feedback not found.";
}
?>
