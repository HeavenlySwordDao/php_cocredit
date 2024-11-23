<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . "/vendor/autoload.php";

$mail = new PHPMailer(true);

// Enable SMTP debugging
// $mail->SMTPDebug = SMTP::DEBUG_SERVER; // Uncomment this line for debugging output

try {
    // Server settings
    $mail->isSMTP();                                            // Send using SMTP
    $mail->SMTPAuth = true;                                   // Enable SMTP authentication
    $mail->Host = "smtp.gmail.com";                           // Set the SMTP server to send through
    $mail->Username = "isucoop543@gmail.com";                 // SMTP username
    $mail->Password = "tmeyugsnuvnnmykb";                    // SMTP password (use app password)
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;      // Enable TLS encryption
    $mail->Port = 587;                                       // TCP port to connect to

    // Recipients
    $mail->setFrom('from@example.com', 'Mailer'); // Set sender email and name
    $mail->addAddress('recipient@example.com', 'Recipient Name'); // Add a recipient

    // Content
    $mail->isHTML(true);                                  // Set email format to HTML
    $mail->Subject = 'Here is the subject';
    $mail->Body    = 'This is the HTML message body <b>in bold!</b>';
    $mail->AltBody = 'This is the body in plain text for non-HTML mail clients';

    // Send the email
    $mail->send();
    echo 'Message has been sent';
} catch (Exception $e) {
    echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
}
