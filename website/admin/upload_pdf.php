<?php
header("Access-Control-Allow-Origin: *");

$uploadDir = realpath(__DIR__ . "/../../chatbot_model_api/data/");

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['pdf_file'])) {
    $file = $_FILES['pdf_file'];
    $filename = basename($file['name']);
    $targetPath = $uploadDir . DIRECTORY_SEPARATOR . $filename;

    // Validate MIME type
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    // Optionally: Validate PDF file signature (magic number)
    $fileStart = file_get_contents($file['tmp_name'], false, null, 0, 4);

    if ($mimeType === 'application/pdf' && $fileStart === '%PDF') {
        if (move_uploaded_file($file['tmp_name'], $targetPath)) {
            echo json_encode(["message" => "✅ PDF uploaded successfully!"]);
        } else {
            echo json_encode(["message" => "❌ Failed to move uploaded file."]);
        }
    } else {
        echo json_encode(["message" => "❌ Only PDF files are allowed."]);
    }
} else {
    echo json_encode(["message" => "No PDF file uploaded."]);
}