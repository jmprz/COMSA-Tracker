<?php
// functions/send_task_email.php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Assuming PHPMailer is installed via composer or included manually
require '../vendor/autoload.php'; // Adjust path if needed
require_once 'email_config.php'; 

function sendTaskAssignmentEmail($recipientEmail, $recipientName, $taskName, $dueDate, $description) {
    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = MAIL_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = MAIL_USERNAME;
        $mail->Password   = MAIL_PASSWORD;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // or ENCRYPTION_SMTPS for port 465
        $mail->Port       = MAIL_PORT;

        // Recipients
        $mail->setFrom(MAIL_SENDER_EMAIL, MAIL_SENDER_NAME);
        $mail->addAddress($recipientEmail, $recipientName);

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'New Task Assigned: ' . $taskName;
        
        $body = "
            <html>
            <body style='font-family: Arial, sans-serif; line-height: 1.6;'>
                <p>Hello <strong>" . htmlspecialchars($recipientName) . "</strong>,</p>
                <p>You have been assigned a new task</p>
                <table style='border: 1px solid #ccc; border-collapse: collapse; width: 100%;'>
                    <tr><td style='padding: 8px; border: 1px solid #ccc; background-color: #f2f2f2;'><strong>Task:</strong></td><td style='padding: 8px; border: 1px solid #ccc;'>" . htmlspecialchars($taskName) . "</td></tr>
                    <tr><td style='padding: 8px; border: 1px solid #ccc; background-color: #f2f2f2;'><strong>Description:</strong></td><td style='padding: 8px; border: 1px solid #ccc;'>" . nl2br(htmlspecialchars($description)) . "</td></tr>
                    <tr><td style='padding: 8px; border: 1px solid #ccc; background-color: #f2f2f2;'><strong>Due Date:</strong></td><td style='padding: 8px; border: 1px solid #ccc;'>" . htmlspecialchars($dueDate) . "</td></tr>
                </table>
                <p>Please login to your account using the COMSA-Tracker Website / App for more details.</p>
                <p>Thank you!</p>
            </body>
            </html>
        ";
        
        $mail->Body    = $body;
        $mail->AltBody = "Hello {$recipientName},\nYou have been assigned a new task: {$taskName}. Due Date: {$dueDate}. Description: {$description}.";

        $mail->send();
        return true;
    } catch (Exception $e) {
        // Log the error for debugging, but don't show to user
        error_log("Email could not be sent. Mailer Error: {$mail->ErrorInfo}");
        return false;
    }
}
?>