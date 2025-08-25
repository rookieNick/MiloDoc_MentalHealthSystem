<?php
header("Access-Control-Allow-Origin: *");

$uploadDir = realpath(__DIR__ . "/../../chatbot_model_api/data/");

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['filename'])) {
    $filename = basename($_POST['filename']); // Sanitize filename to prevent directory traversal
    $targetPath = $uploadDir . DIRECTORY_SEPARATOR . $filename;

    if (file_exists($targetPath)) {
        if (unlink($targetPath)) {
            echo json_encode(["success" => true, "message" => "✅ PDF deleted successfully!"]);
        } else {
            echo json_encode(["success" => false, "message" => "❌ Failed to delete file."]);
        }
    } else {
        echo json_encode(["success" => false, "message" => "❌ File does not exist."]);
    }
} else {
    echo json_encode(["success" => false, "message" => "Invalid request or no filename provided."]);
}
?>