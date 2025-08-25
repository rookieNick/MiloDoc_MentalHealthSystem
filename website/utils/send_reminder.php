<?php
// send_reminder.php

// Set timezone
date_default_timezone_set('Asia/Kuala_Lumpur');

// Autoload PHPMailer and DB connection
require_once(__DIR__ . '/../vendor/autoload.php');
include(__DIR__ . '/../../website/connection.php');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Start a loop to check the time
while (true) {
    // Get current time in HH:MM format
    $currentTime = date('H:i');
    echo "Checking time: $currentTime\n"; // Debugging line to track time checks

    // If the time is 20:37, send the email reminders
    if ($currentTime == '20:41') {
        echo "Triggering email send...\n"; // Debugging line to confirm triggering
        sendEmails();
    }

    // Sleep for 30 seconds before checking again
    sleep(30); // Check every 30 seconds
}

// Function to send emails to patients
function sendEmails() {
    global $database;

    // Log the time when the reminder is sent
    echo "Sending mood reminders at " . date('H:i:s') . "\n";

    // Query to get patients who should receive the mood reminder
    $sql = "SELECT pid, pname, pemail 
            FROM patient 
            WHERE moodReminder = 1 
              AND pemail IS NOT NULL";
    $res = $database->query($sql);
    
    // If there was an error with the database query, log the error
    if (!$res) {
        echo "Database error: " . $database->error . "\n";
        return;
    }

    // If no patients are found, log a message and stop
    if ($res->num_rows == 0) {
        echo "No patients found for reminder\n"; // Debugging line
        return;
    }

    // Loop through each patient and send the reminder email
    while ($user = $res->fetch_assoc()) {
        $mail = new PHPMailer(true);
        try {
            // Set up PHPMailer to send an email via SMTP
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'faceducation2024@gmail.com';  // Your Gmail username
            $mail->Password = 'dwlp ntlb daem iuao';  // Your Gmail app password
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;

            // Set the sender and recipient
            $mail->setFrom('faceducation2024@gmail.com', 'MiloDoc');
            $mail->addAddress($user['pemail']);

            // Set email format to HTML
            $mail->isHTML(true);
            $mail->Subject = '🕗 How are you feeling today?';
            $mail->Body = "
                <p>Hi " . htmlspecialchars($user['pname']) . ",</p>
                <p>This is your friendly nightly reminder to <a href='https://yourdomain.com/patient/mood.php'>log your mood</a> for today.</p>
                <p>Tracking daily helps you see patterns and get the support you need!</p>
                <p>— The MiloDoc Team</p>
            ";

            // Send the email
            $mail->send();
            echo "Reminder sent to {$user['pemail']}\n";  // Log the email sending success
        } catch (Exception $e) {
            // If PHPMailer fails to send, log the error
            echo "Mailer Error ({$user['pemail']}): {$mail->ErrorInfo}\n";
        }
    }
}
?>
