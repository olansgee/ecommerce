<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Email;

class FeedbackController extends Controller {
    public function send() {
        header('Content-Type: application/json');

        $errors = [];
        $data = [
            'name' => trim($_POST['name'] ?? ''),
            'email' => trim($_POST['email'] ?? ''),
            'subject' => trim($_POST['subject'] ?? ''),
            'rating' => $_POST['rating'] ?? '5',
            'message' => trim($_POST['message'] ?? '')
        ];

        if (empty($data['name'])) $errors['name'] = 'Name is required';
        if (empty($data['email'])) $errors['email'] = 'Email is required';
        if (empty($data['subject'])) $errors['subject'] = 'Subject is required';
        if (empty($data['message'])) $errors['message'] = 'Message is required';
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) $errors['email'] = 'Invalid email format';

        if (!empty($errors)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'errors' => $errors]);
            exit;
        }

        $email_model = new Email();
        if ($email_model->sendFeedbackEmail($data)) {
            echo json_encode(['success' => true]);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Message could not be sent.']);
        }
        exit;
    }
}
