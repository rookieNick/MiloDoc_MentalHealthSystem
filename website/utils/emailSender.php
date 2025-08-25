<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;
require_once(__DIR__ . '/../vendor/autoload.php');

class EmailSender
{
    public function sendEmail($toEmail, $conversation)
    {
        $mail = new PHPMailer(true);

        try {
            // Load email template
            $emailTemplate = file_get_contents(__DIR__ . '/email_template.html');

            // Generate chat HTML with inline styles
            $chatHtml = "";
            foreach ($conversation as $msg) {
                // Format message differently if suicidal
                if ($msg['ResponseByUser'] == 1) { // User message
                    if ($msg['is_suicidal'] == 1) {
                        // 🚩 Red flag + probability
                        $chatHtml .= "<p style='background: #ffebee; padding: 10px; border-radius: 8px; margin: 5px 0; font-size: 14px; color: #d32f2f;'>
                                        <strong style='color: #d32f2f;'>🚩 Child:</strong> {$msg['message']} 
                                        <br><small>⚠️ Suicidal Risk: " . $msg['confidence'] . "%</small>
                                      </p>";
                    } else {
                        // Normal message
                        $chatHtml .= "<p style='background: #e9ffe7; padding: 10px; border-radius: 8px; margin: 5px 0; font-size: 14px;'>
                                        <strong style='color: #28a745;'>Child:</strong> {$msg['message']}
                                      </p>";
                    }
                } else { // Chatbot (MiloDoc) message
                    $chatHtml .= "<p style='background: #e7f3ff; padding: 10px; border-radius: 8px; margin: 5px 0; font-size: 14px;'>
                                    <strong style='color: #007bff;'>MiloDoc:</strong> {$msg['message']}
                                  </p>";
                }
            }

            // Replace placeholder in template
            $emailContent = str_replace("{{CHAT_MESSAGES}}", $chatHtml, $emailTemplate);

            // Email setup
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'faceducation2024@gmail.com';
            $mail->Password = 'dwlp ntlb daem iuao';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;

            // Recipients
            $mail->setFrom('faceducation2024@gmail.com', 'MiloDoc');
            $mail->addAddress($toEmail);

            // Email content
            $mail->isHTML(true);
            $mail->Subject = 'Urgent: Suicidal Intention Detected';
            $mail->Body = $emailContent;

            $mail->send();
            return true;
        } catch (Exception $e) {
            echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
            return false;
        }
    }

    public function sendPostEmail($toEmail, $postContent, $confidence)
    {
        $mail = new PHPMailer(true);
        try {
            $mail->SMTPDebug = SMTP::DEBUG_SERVER;
            $mail->Debugoutput = function($str, $level) {
                error_log("SMTP Debug level $level: $str");
            };
    
            $templatePath = __DIR__ . '/post_email_template.html';
            if (!file_exists($templatePath)) {
                throw new Exception("Email template file not found: $templatePath");
            }
            $emailTemplate = file_get_contents($templatePath);
            if ($emailTemplate === false) {
                throw new Exception("Failed to read email template: $templatePath");
            }
    
            $postHtml = "<p style='background: #ffebee; padding: 10px; border-radius: 8px; margin: 5px 0; font-size: 14px; color: #d32f2f;'>
                            <strong style='color: #d32f2f;'>🚩 Child's Post:</strong> " . htmlspecialchars($postContent) . " 
                            <br><small>⚠️ Suicidal Risk: " . $confidence . "%</small>
                         </p>";
            $emailContent = str_replace("{{POST_MESSAGE}}", $postHtml, $emailTemplate);
    
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'faceducation2024@gmail.com';
            $mail->Password = 'dwlp ntlb daem iuao';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;
    
            $mail->setFrom('faceducation2024@gmail.com', 'MiloDoc');
            $mail->addReplyTo('faceducation2024@gmail.com', 'MiloDoc Support');
            $mail->addAddress($toEmail);
    
            $mail->isHTML(true);
            $mail->Subject = 'Urgent: Concerning Forum Post Detected';
            $mail->Body = $emailContent;
    
            $mail->send();
            error_log("Email successfully sent to: $toEmail with subject: {$mail->Subject}");
            return true;
        } catch (Exception $e) {
            error_log("Failed to send email to $toEmail. Mailer Error: {$mail->ErrorInfo}");
            return false;
        }
    }
}
