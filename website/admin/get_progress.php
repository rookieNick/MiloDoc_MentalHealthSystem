<?php
header('Content-Type: application/json');

$progress_file = __DIR__ . '/../../chatbot_model_api/progress.json';
error_log("Progress file path: $progress_file");
if (file_exists($progress_file)) {
    $progress = file_get_contents($progress_file);
    echo $progress;
} else {
    error_log("Progress file not found: $progress_file");
    echo json_encode(["message" => "No progress file found.", "percent" => 0]);
}
?>