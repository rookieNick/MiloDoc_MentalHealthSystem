<?php
@ini_set('zlib.output_compression', 0);
@ini_set('implicit_flush', 1);
@ob_end_clean();
set_time_limit(0);

header('Content-Type: application/json');

if (isset($_POST['run_script'])) {
    // Path configurations
    $output_file = realpath(__DIR__ . '/../../chatbot_model_api') . '\\script_output.txt';
    $python_path = realpath(__DIR__ . '/../../chatbot_model_api/venv/Scripts') . '\\python.exe';
    $script_path = realpath(__DIR__ . '/../../chatbot_model_api') . '\\ingest_pdf.py';
    $script_dir = realpath(__DIR__ . '/../../chatbot_model_api');

    // Clear previous output
    file_put_contents($output_file, '');
    file_put_contents(__DIR__ . '/../../chatbot_model_api/progress.json', json_encode([
        'message' => 'Starting process...',
        'percent' => 0
    ]));

    // Build the command to run in background (Windows)
    $command = "start /B \"\" \"$python_path\" -u \"$script_path\" > \"$output_file\" 2>&1";

    // Execute the command without waiting
    pclose(popen($command, 'r'));

    // === ADDED BELOW ===
    usleep(500000); // wait for process to start
    $tasklist = shell_exec('wmic process where "CommandLine like \'%ingest_pdf.py%\' and name=\'python.exe\'" get ProcessId,CommandLine /FORMAT:CSV');
    preg_match('/(\d+)\s*$/m', $tasklist, $matches);
    $actual_pid = isset($matches[1]) ? trim($matches[1]) : null;
    if ($actual_pid) {
        $pid_file = $script_dir . '\\ingest_pid.txt';
        file_put_contents($pid_file, $actual_pid);
    }
    // === END ADDED ===



    echo json_encode([
        'status' => 'started',
        'message' => 'Script started in background',
        'pid' => getmypid()
    ]);
    exit;
}
