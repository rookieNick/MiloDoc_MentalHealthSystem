<?php
session_start();
require_once __DIR__ . '/../connection.php';

date_default_timezone_set('Asia/Kuala_Lumpur');

if (!isset($_POST['journal_id'])) {
    echo '<p>Missing journal ID.</p>';
    exit;
}

$journal_id = $_POST['journal_id'];

$sql = "SELECT * FROM journal_feedback WHERE journal_id = ? ORDER BY created_date DESC";
$stmt = $database->prepare($sql);
$stmt->bind_param("s", $journal_id);
$stmt->execute();
$result = $stmt->get_result();

$html = "";

while ($row = $result->fetch_assoc()) {
    $user_feedback = htmlspecialchars($row['feedback']);
    $original = htmlspecialchars($row['original_journal_content']);
    $refined = htmlspecialchars($row['refined_journal_content']);
    $created = htmlspecialchars($row['created_date']);

    $html .= "
    <div class='history-entry' id='{$row['journal_feedback_id']}'>
        <div class='entry-section'>
            <h4>User Feedback</h4>
            <p>" . nl2br($user_feedback) . "</p>
        </div>
        <div class='entry-section original'>
            <h4>Original Journal</h4>
            <p>" . nl2br($original) . "</p>
        </div>
        <div class='entry-section refined'>
            <h4>Refined Journal</h4>
            <p>" . nl2br($refined) . "</p>
        </div>
        <div class='entry-meta'>
            <span>🕒 " . $created . "</span>
            <button class='restore-btn' data-journal-id='" . $row['journal_id'] . "' data-feedback-id='" . $row['journal_feedback_id'] . "'>Restore</button>
        </div>
    </div>
    ";
}

if ($html === "") {
    $html = "<p>No history found for this journal.</p>";
}

echo $html;
?>