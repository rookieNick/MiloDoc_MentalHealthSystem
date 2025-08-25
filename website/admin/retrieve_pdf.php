<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");


$dir = realpath(__DIR__ . "/../../chatbot_model_api/data/");
$pdfs = [];

if ($dir && is_dir($dir)) {
    $files = scandir($dir);
    foreach ($files as $file) {
        if (pathinfo($file, PATHINFO_EXTENSION) === 'pdf') {
            $pdfs[] = $file;
        }
    }
}

header('Content-Type: application/json');
echo json_encode($pdfs);
