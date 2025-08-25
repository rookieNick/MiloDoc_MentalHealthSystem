<?php
session_start();

// Include DB config
require_once '../connection.php';
include_once '../config.php'; 
require_once '../lib/_base.php';

$postData = $statusMsg = $valErr = '';
$status = 'danger';

// Check if form is submitted
if (isset($_POST['booknow'])) {
    // Store input in session
    $_SESSION['postData'] = $_POST;

    // Extract appointment data
    $scheduleid = !empty($_POST['scheduleid']) ? trim($_POST['scheduleid']) : '';
    $apponum = !empty($_POST['apponum']) ? trim($_POST['apponum']) : '';
    $date = !empty($_POST['date']) ? trim($_POST['date']) : '';

    // Get patient ID from session
    if (isset($_SESSION["user"]) && $_SESSION["usertype"] === 'p') {
        $useremail = $_SESSION["user"];

        // Get patient ID from DB
        $sql = "SELECT pid,pname FROM patient WHERE pemail = ?";
        $stmt = $database->prepare($sql);
        $stmt->bind_param("s", $useremail);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $userid = $user['pid'];
        $username = $user['pname'];

    } else {
        $valErr .= 'User session invalid.<br/>';
    }

    // Basic validation
    if (empty($scheduleid)) {
        $valErr .= 'Missing schedule ID.<br/>';
    }
    if (empty($apponum)) {
        $valErr .= 'Missing appointment number.<br/>';
    }
    if (empty($date)) {
        $valErr .= 'Missing appointment date.<br/>';
    }

    // If no validation errors
    if (empty($valErr)) {
        // Prepare INSERT
        $sqlInsert = "INSERT INTO appointment (pid, apponum, scheduleid, appodate) VALUES (?, ?, ?, ?)";
        $stmt = $database->prepare($sqlInsert);
        $stmt->bind_param("iiis", $db_pid, $db_apponum, $db_scheduleid, $db_date);

        // Bind values
        $db_pid = $userid;
        $db_apponum = $apponum;
        $db_scheduleid = $scheduleid;
        $db_date = $date;

        $insert = $stmt->execute();

        if ($insert) {
            $appoid = $stmt->insert_id;

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
                    <li><b>Appointment Number:</b> OC-000{$appoid}</li>
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
            //unset($_SESSION['postData']);
            $_SESSION['last_appoid'] = $appoid;

            header("Location: $googleOauthURL");          
            
            exit();
        } else {
            $statusMsg = 'Something went wrong, please try again later.';
        }
    } else {
        $statusMsg = '<p>Please fix the following:</p>' . trim($valErr, '<br/>');
    }
} else {
    $statusMsg = 'Form submission failed!';
}

$_SESSION['status_response'] = array(
    'status' => $status,
    'status_msg' => $statusMsg
);

header("Location: index.php");
exit();
?>
