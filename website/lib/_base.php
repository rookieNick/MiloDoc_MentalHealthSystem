<?php
// Company Information Constants
define("COMPANY_EMAIL", "mdocclinic@gmail.com");
define("CONTACT_NUMBER", "+6011-56646773");
define("MAP_ADDRESS_1", "88, Health Avenue");
define("MAP_ADDRESS_2", "Medical City, Block B");
define("MAP_ADDRESS_3", "Kuala Lumpur, Malaysia");
define("COMPANYNAME", "MDoc Clinic");
define("SLOGAN", "Your Health, Our Priority");
define("COPYRIGHT_TEXT", "&copy; " . date("Y") . " MDoc Clinic. All rights reserved.");

/**
 * Returns a configured PHPMailer instance ready for sending emails.
 *
 * @return PHPMailer\PHPMailer\PHPMailer
 */
function get_mail()
{
    // Load PHPMailer classes
    require_once 'PHPMailer.php';
    require_once 'SMTP.php';
    require_once 'Exception.php';

    // Create a new PHPMailer instance
    $mail = new PHPMailer\PHPMailer\PHPMailer(true); // true = enable exceptions

    // Server settings
    $mail->isSMTP();                                      // Set mailer to use SMTP
    $mail->Host       = 'smtp.gmail.com';                 // Specify SMTP server
    $mail->SMTPAuth   = true;                              // Enable SMTP authentication
    $mail->Username   = 'mdocclinic@gmail.com';            // SMTP username
    $mail->Password   = 'qfyb errt epew gewn';         // SMTP password (Use Gmail App Password)
    $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS; // TLS encryption
    $mail->Port       = 587;                               // TCP port to connect to

    // Sender Info
    $mail->setFrom(COMPANY_EMAIL, COMPANYNAME . ' 🏥');
    $mail->CharSet = 'UTF-8';                              // Character encoding


    return $mail;
}
?>
