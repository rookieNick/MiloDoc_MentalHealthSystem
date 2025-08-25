<?php
session_start();

// Include DB config
require_once '../connection.php';
include_once '../config.php'; 
require_once '../lib/_base.php';

$statusMsg = '';
$status = 'danger';

// Check if form submitted
if (isset($_POST['appoid']) && isset($_POST['new_scheduleid'])) {
    // Get old appoid (not needed to update, just keeping for tracking)
    $old_appoid = intval($_POST['appoid']);
    $new_scheduleid = intval($_POST['new_scheduleid']);

    // Check if user session valid
    if (isset($_SESSION["user"]) && $_SESSION["usertype"] === 'p') {
        $useremail = $_SESSION["user"];

        // Get patient ID
        $sql = "SELECT pid FROM patient WHERE pemail = ?";
        $stmt = $database->prepare($sql);
        $stmt->bind_param("s", $useremail);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $userid = $user['pid'];
    } else {
        $statusMsg = 'Invalid user session.';
        $_SESSION['status_response'] = array('status' => $status, 'status_msg' => $statusMsg);
        header("Location: appointment.php");
        exit();
    }

    // Set current date
    date_default_timezone_set('Asia/Singapore');
    $appodate = date('Y-m-d H:i:s');

    // Get current non-cancelled appointments for this new schedule
    $apponumSQL = "SELECT COUNT(*) AS current_count FROM appointment WHERE scheduleid = ? AND status != 'Cancelled'";
    $stmt = $database->prepare($apponumSQL);
    $stmt->bind_param("i", $new_scheduleid);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res->fetch_assoc();
    $next_apponum = $row['current_count'] + 1;

    // Insert new appointment (like booking)
    $sqlInsert = "INSERT INTO appointment (pid, apponum, scheduleid, appodate, status) VALUES (?, ?, ?, ?, 'Pending')";
    $stmtInsert = $database->prepare($sqlInsert);
    $stmtInsert->bind_param("iiis", $userid, $next_apponum, $new_scheduleid, $appodate);
    $insert = $stmtInsert->execute();

    if ($insert) {
        $new_appoid = $stmtInsert->insert_id;

        // Optional: store last rescheduled appointment ID if needed
        $_SESSION['last_appoid'] = $new_appoid;

        $mail = get_mail();

        $mail->addAddress($useremail, $username); // Send to patient
        $mail->Subject = "Appointment Confirmation - MDoc Clinic";
        $mail->isHTML(true);
        $mail->Body = "
            <h2 style='color: #2C3E50;'>Thank you for booking with MDoc Clinic!</h2>
            <p>Hi <b>{$username}</b>,</p>
            <p>Your appointment has been successfully booked.</p>
            <p><b>Appointment Details:</b></p>
            <ul>
                <li><b>Appointment Number:</b> OC-000{$new_appoid}</li>
                <li><b>Date:</b> {$date}</li>
            </ul>
            <br>
            <p>If you have any questions, feel free to contact us at <a href='mailto:".COMPANY_EMAIL."'>".COMPANY_EMAIL."</a> or call ".CONTACT_NUMBER.".</p>
            <br>
            <p style='color:gray; font-size:12px;'>".SLOGAN."<br>".MAP_ADDRESS_1.", ".MAP_ADDRESS_2.", ".MAP_ADDRESS_3."</p>
            <br><br>
            <p style='font-size:11px;color:gray;'>".COPYRIGHT_TEXT."</p>
        ";
        $mail->send();

        header("Location: $googleOauthURL");
        echo "<script>
            alert('Appointment successfully rescheduled and Added into the google calendar');
            window.location.href='appointment.php';
        </script>";
        exit();
    } else {
        $statusMsg = 'Something went wrong while rescheduling.';
        $_SESSION['status_response'] = array('status' => $status, 'status_msg' => $statusMsg);
        header("Location: appointment.php");
        exit();
    }
} else {
    $statusMsg = 'Invalid reschedule request.';
    $_SESSION['status_response'] = array('status' => $status, 'status_msg' => $statusMsg);
    header("Location: appointment.php");
    exit();
}
?>
