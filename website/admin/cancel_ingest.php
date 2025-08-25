<?php
header('Content-Type: application/json');

if (isset($_POST['cancel_script'])) {
    $pid_file = realpath(__DIR__ . '/../../chatbot_model_api') . '\\ingest_pid.txt';
    error_log("PID file: $pid_file");

    if (file_exists($pid_file)) {
        $pid = trim(file_get_contents($pid_file));
        if (!empty($pid) && is_numeric($pid)) {
            // Terminate the process and its children
            $command = "taskkill /PID $pid /F /T";
            error_log("Running command: $command");
            exec($command, $output, $return_var);
            
            if ($return_var === 0) {
                // Clear the PID file
                file_put_contents($pid_file, '');
                echo json_encode(['status' => 'cancelled', 'message' => 'Script cancelled successfully.']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Failed to cancel script.']);
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Invalid or missing PID in PID file.']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'PID file not found.']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request.']);
}
?>