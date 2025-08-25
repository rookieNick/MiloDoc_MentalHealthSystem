<?php
session_start();
require_once __DIR__ . '/../connection.php';

date_default_timezone_set('Asia/Kuala_Lumpur');

// Ensure required POST data is present
if (!isset($_POST["user_id"]) || !isset($_POST["overall_score"])) {
    echo json_encode(["error" => "Missing parameters"]);
    exit;
}

$user_id = $_POST["user_id"];
$overall_score = $_POST["overall_score"];
$sentiment_score = $_POST["sentiment_score"] ?? null;
$stress_level = $_POST["stress_level"] ?? null;
$anxiety_level = $_POST["anxiety_level"] ?? null;
$depression_risk = $_POST["depression_risk"] ?? null;
$overall_score_reason = $_POST["overall_score_reason"] ?? null;
$sentiment_score_reason = $_POST["sentiment_score_reason"] ?? null;
$stress_level_reason = $_POST["stress_level_reason"] ?? null;
$anxiety_level_reason = $_POST["anxiety_level_reason"] ?? null;
$depression_risk_reason = $_POST["depression_risk_reason"] ?? null;
$report_date = date("Y-m-d");
$now = date("Y-m-d H:i:s");

// Check if a report already exists for this user today
$sql_check = "SELECT mhss FROM mental_health_status_score WHERE user_id = ? AND report_date = ?";
$stmt_check = $database->prepare($sql_check);
$stmt_check->bind_param("is", $user_id, $report_date);
$stmt_check->execute();
$result_check = $stmt_check->get_result();

if ($result_check->num_rows > 0) {
    // Report exists, update it
    $row = $result_check->fetch_assoc();
    $mhss = $row["mhss"];

    $sql_update = "UPDATE mental_health_status_score 
                   SET overall_score = ?, sentiment_score = ?, stress_level = ?, anxiety_level = ?, depression_risk = ?, 
                       overall_score_reason = ?, sentiment_score_reason = ?, stress_level_reason = ?, anxiety_level_reason = ?, depression_risk_reason = ?, 
                       updated_date = ?
                   WHERE mhss = ?";
    
    $stmt_update = $database->prepare($sql_update);
    $stmt_update->bind_param("iiiiisssssss", 
        $overall_score, $sentiment_score, $stress_level, $anxiety_level, $depression_risk, 
        $overall_score_reason, $sentiment_score_reason, $stress_level_reason, $anxiety_level_reason, $depression_risk_reason, 
        $now, $mhss
    );
    $stmt_update->execute();

    if ($stmt_update->affected_rows > 0) {
        echo json_encode([
            "status" => "success", 
            "mhss" => $mhss, 
            "action" => "updated"
        ]);
    } else {
        echo json_encode(["status" => "error", "message" => "Failed to update report."]);
    }
} else {
    // No report exists, insert a new one
    $mhss = uniqid("MHSS_"); // Generate a unique ID for mhss

    $sql_insert = "INSERT INTO mental_health_status_score 
                   (mhss, user_id, overall_score, sentiment_score, stress_level, anxiety_level, depression_risk, 
                    overall_score_reason, sentiment_score_reason, stress_level_reason, anxiety_level_reason, depression_risk_reason, 
                    report_date, created_date, updated_date) 
                   VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt_insert = $database->prepare($sql_insert);
    $stmt_insert->bind_param("siiiiisssssssss", 
        $mhss, $user_id, $overall_score, $sentiment_score, $stress_level, $anxiety_level, $depression_risk, 
        $overall_score_reason, $sentiment_score_reason, $stress_level_reason, $anxiety_level_reason, $depression_risk_reason, 
        $report_date, $now, $now
    );
    $stmt_insert->execute();

    if ($stmt_insert->affected_rows > 0) {
        echo json_encode([
            "status" => "success", 
            "mhss" => $mhss, 
            "action" => "inserted"
        ]);
    } else {
        echo json_encode(["status" => "error", "message" => "Failed to insert report."]);
    }
}
?>
