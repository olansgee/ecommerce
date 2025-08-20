
<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// require 'PHPMailer/src/PHPMailer.php';
// require 'PHPMailer/src/Exception.php';
// require 'PHPMailer/src/SMTP.php';

require 'vendor/autoload.php';

header('Content-Type: application/json');

// Validate input
$errors = [];
$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$subject = trim($_POST['subject'] ?? '');
$rating = $_POST['rating'] ?? '5';
$message = trim($_POST['message'] ?? '');

if (empty($name)) {
    $errors['name'] = 'Name is required';
}

if (empty($email)) {
    $errors['email'] = 'Email is required';
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors['email'] = 'Invalid email format';
}

if (empty($subject)) {
    $errors['subject'] = 'Subject is required';
}

if (empty($message)) {
    $errors['message'] = 'Feedback message is required';
}

if (!empty($errors)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'errors' => $errors]);
    exit;
}

// Create PHPMailer instance
$mail = new PHPMailer(true);

try {
    // Server settings
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com'; // Your SMTP server
    $mail->SMTPAuth = true;
    $mail->Username = 'feedback.olansgee@gmail.com'; // SMTP username
    $mail->Password = 'nzsfspeagsoszwyp'; // SMTP password
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = 587;

    // Recipients
    $mail->setFrom('noreply@gmail.com', 'Feedback System');
    $mail->addAddress('feedback.olansgee@gmail.com'); // Where feedback should be sent
    $mail->addReplyTo($email, $name);

    // Content
    $mail->isHTML(true);
    $mail->Subject = "New Feedback: $subject";
    
    $emailBody = "
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background-color: #4361ee; color: white; padding: 15px; text-align: center; }
            .content { padding: 20px; background-color: #f9f9f9; }
            .rating { color: #ff9800; font-weight: bold; }
            .footer { margin-top: 20px; padding-top: 10px; border-top: 1px solid #eee; font-size: 0.9em; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h2>New Customer Feedback</h2>
            </div>
            <div class='content'>
                <p><strong>From:</strong> $name &lt;$email&gt;</p>
                <p><strong>Rating:</strong> <span class='rating'>$rating/5</span></p>
                <p><strong>Subject:</strong> $subject</p>
                <h3>Message:</h3>
                <p>" . nl2br(htmlspecialchars($message)) . "</p>
            </div>
            <div class='footer'>
                <p>This feedback was submitted through the customer feedback form.</p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    $mail->Body = $emailBody;
    $mail->AltBody = "New Feedback\nFrom: $name <$email>\nRating: $rating/5\nSubject: $subject\n\nMessage:\n$message";

    $mail->send();
    
    // Send confirmation to client
    $confirmationMail = new PHPMailer(true);
    $confirmationMail->isSMTP();
    $confirmationMail->Host = 'smtp.gmail.com';
    $confirmationMail->SMTPAuth = true;
    $confirmationMail->Username = 'feedback.olansgee@gmail.com';
    $confirmationMail->Password = 'nzsfspeagsoszwyp';
    $confirmationMail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $confirmationMail->Port = 587;
    
    $confirmationMail->setFrom('noreply@gmail.com', 'Olansgee Technology');
    $confirmationMail->addAddress($email, $name);
    
    $confirmationMail->isHTML(true);
    $confirmationMail->Subject = "Thank you for your feedback";
    
    $confirmationBody = "
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background-color: #4361ee; color: white; padding: 15px; text-align: center; }
            .content { padding: 20px; background-color: #f9f9f9; }
            .footer { margin-top: 20px; padding-top: 10px; border-top: 1px solid #eee; font-size: 0.9em; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h2>Thank You for Your Feedback</h2>
            </div>
            <div class='content'>
                <p>Dear $name,</p>
                <p>We sincerely appreciate you taking the time to share your feedback with us. Your input is valuable in helping us improve our services.</p>
                <p>We've received your message regarding <strong>$subject</strong> and our team will review it shortly.</p>
                <p>If you have any additional comments or questions, please don't hesitate to contact us.</p>
                <p>Best regards,</p>
                <p>The Customer Support Team</p>
            </div>
            <div class='footer'>
                <p>This is an automated message. Please do not reply directly to this email.</p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    $confirmationMail->Body = $confirmationBody;
    $confirmationMail->AltBody = "Thank You for Your Feedback\n\nDear $name,\n\nWe sincerely appreciate you taking the time to share your feedback with us. Your input is valuable in helping us improve our services.\n\nWe've received your message regarding $subject and our team will review it shortly.\n\nIf you have any additional comments or questions, please don't hesitate to contact us.\n\nBest regards,\nThe Customer Support Team";
    
    $confirmationMail->send();
    
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Message could not be sent. Mailer Error: ' . $mail->ErrorInfo]);
}