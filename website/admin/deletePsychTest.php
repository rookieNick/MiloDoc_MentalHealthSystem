<?php
session_start();

// 1) Admin check
if (empty($_SESSION["user"]) || $_SESSION['usertype'] !== 'a') {
    header("Location: ../login.php");
    exit;
}

if (!isset($_GET["test_id"])) {
    header("Location: tests.php");
    exit;
}

$test_id = $_GET["test_id"];
include("../connection.php");

// Use a transaction so that either all succeed or none
$database->begin_transaction();

try {
    // 2) Delete all patient_answer entries for this test's questions
    $sql = "
        DELETE FROM patient_answer
        WHERE question_id IN (
            SELECT question_id
            FROM test_question
            WHERE test_id = ?
        )
    ";
    $stmt = $database->prepare($sql);
    $stmt->bind_param("s", $test_id);
    $stmt->execute();
    $stmt->close();

    // 3) Delete all patient_test entries for this test
    $sql = "DELETE FROM patient_test WHERE test_id = ?";
    $stmt = $database->prepare($sql);
    $stmt->bind_param("s", $test_id);
    $stmt->execute();
    $stmt->close();

    // 4) Delete the test questions
    $sql = "DELETE FROM test_question WHERE test_id = ?";
    $stmt = $database->prepare($sql);
    $stmt->bind_param("s", $test_id);
    $stmt->execute();
    $stmt->close();

    // 5) Finally, delete the test itself
    $sql = "DELETE FROM test WHERE test_id = ?";
    $stmt = $database->prepare($sql);
    $stmt->bind_param("s", $test_id);
    $stmt->execute();
    $stmt->close();

    // Commit all deletions
    $database->commit();

    header("Location: tests.php");
    exit;
} catch (Exception $e) {
    // Something went wrong: roll back
    $database->rollback();
    error_log("Failed to delete test {$test_id}: " . $e->getMessage());
    header("Location: tests.php?error=delete_failed");
    exit;
}
?>
