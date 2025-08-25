<?php
session_start();
require_once __DIR__ . '/../connection.php';
date_default_timezone_set('Asia/Kuala_Lumpur');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['journal_id'], $_POST['user_id'], $_POST['report_date'])) {
        echo json_encode(['status' => 'error', 'message' => 'Missing required data']);
        exit;
    }

    $journal_id = $_POST['journal_id'];
    $user_id = $_POST['user_id'];
    $report_date = $_POST['report_date'];

    // Start a transaction
    $database->begin_transaction();

    try {
        // 1. Delete from journal_feedback
        $stmt1 = $database->prepare("DELETE FROM journal_feedback WHERE journal_id = ?");
        $stmt1->bind_param("s", $journal_id);
        $stmt1->execute();

        // 2. Delete from mental_health_status_score
        $stmt2 = $database->prepare("DELETE FROM mental_health_status_score WHERE user_id = ? AND report_date = ?");
        $stmt2->bind_param("ss", $user_id, $report_date);
        $stmt2->execute();

        // 3. Delete from journal
        $stmt3 = $database->prepare("DELETE FROM journal WHERE journal_id = ?");
        $stmt3->bind_param("s", $journal_id);
        $stmt3->execute();

        // Commit transaction
        $database->commit();
        echo json_encode(['status' => 'success']);
    } catch (Exception $e) {
        $database->rollback();
        echo json_encode([
            'status' => 'error',
            'message' => 'Failed to delete journal and related data',
            'error_detail' => $e->getMessage()
        ]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
}
?>
