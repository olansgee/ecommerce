<?php

namespace App\Models;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class Email {
    protected $mail;

    public function __construct() {
        $this->mail = new PHPMailer(true);
        // Server settings
        $this->mail->isSMTP();
        $this->mail->Host       = SMTP_HOST;
        $this->mail->SMTPAuth   = true;
        $this->mail->Username   = SMTP_USER;
        $this->mail->Password   = SMTP_PASS;
        $this->mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $this->mail->Port       = SMTP_PORT;
        $this->mail->setFrom(SMTP_FROM, SMTP_FROM_NAME);
    }

    public function sendVerificationEmail($email, $token) {
        try {
            $this->mail->addAddress($email);
            $this->mail->isHTML(true);
            $this->mail->Subject = "Verify Your Email Address";
            $verification_link = BASE_URL . "auth/verify?token=$token";

            $message = "
            <html><body>
            <p>Thank you for creating an account! Please verify your email address to complete your registration.</p>
            <p><a href='".htmlspecialchars($verification_link)."'>Verify Email Address</a></p>
            </body></html>";

            $this->mail->Body = $message;
            $this->mail->send();
            return true;
        } catch (Exception $e) {
            error_log("Mailer Error: " . $this->mail->ErrorInfo);
            return false;
        }
    }

    public function sendReceiptEmail($customer_email, $receipt_html) {
        try {
            $this->mail->addAddress($customer_email);
            $this->mail->isHTML(true);
            $this->mail->Subject = "Your Purchase Receipt from " . ORG_NAME;
            $this->mail->Body = $receipt_html;
            $this->mail->send();
            return true;
        } catch (Exception $e) {
            error_log("Mailer Error: " . $this->mail->ErrorInfo);
            return false;
        }
    }

    public function sendFeedbackEmail($data) {
        try {
            // Send to admin
            $this->mail->addAddress(ORG_EMAIL);
            $this->mail->addReplyTo($data['email'], $data['name']);
            $this->mail->isHTML(true);
            $this->mail->Subject = "New Feedback: " . $data['subject'];
            $this->mail->Body = "
                <html><body>
                <p><strong>From:</strong> {$data['name']} &lt;{$data['email']}&gt;</p>
                <p><strong>Rating:</strong> {$data['rating']}/5</p>
                <p><strong>Subject:</strong> {$data['subject']}</p>
                <h3>Message:</h3>
                <p>" . nl2br(htmlspecialchars($data['message'])) . "</p>
                </body></html>";
            $this->mail->send();

            // Send confirmation to user
            $this->mail->clearAddresses();
            $this->mail->clearReplyTos();
            $this->mail->addAddress($data['email'], $data['name']);
            $this->mail->Subject = "Thank you for your feedback";
            $this->mail->Body = "
                <html><body>
                <p>Dear {$data['name']},</p>
                <p>Thank you for your feedback. We appreciate you taking the time to share your thoughts with us.</p>
                </body></html>";
            $this->mail->send();

            return true;
        } catch (Exception $e) {
            error_log("Mailer Error: " . $this->mail->ErrorInfo);
            return false;
        }
    }
}
