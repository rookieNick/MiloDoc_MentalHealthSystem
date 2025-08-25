<?php

header('Content-Type: application/json');

$output_file = __DIR__ . '/../../chatbot_model_api/script_output.txt';
error_log("output_file: $output_file");
if (file_exists($output_file)) {
    $output = file_get_contents($output_file);
    echo json_encode(['output' => $output]);
} else {
    echo json_encode(['output' => 'No output file found.']);
    
}
?>