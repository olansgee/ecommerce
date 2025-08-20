<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\User;
use App\Models\Email;

class AuthController extends Controller {
    public function login() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $user = User::findByEmailOrUsername($_POST['username']);

            if ($user && password_verify($_POST['password'], $user['password'])) {
                if (!$user['email_verified']) {
                    $_SESSION['message'] = "Email not verified! Please check your email for verification link.";
                    $_SESSION['message_type'] = "danger";
                } else {
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['email'] = $user['email'];
                    $_SESSION['role'] = $user['role'];
                    $_SESSION['message'] = "Login successful!";
                    $_SESSION['message_type'] = "success";
                }
            } else {
                $_SESSION['message'] = "Invalid username or password!";
                $_SESSION['message_type'] = "danger";
            }
            header('Location: ' . BASE_URL);
            exit;
        }
    }

    public function logout() {
        session_destroy();
        header('Location: ' . BASE_URL);
        exit;
    }

    public function signup() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if ($_POST['password'] !== $_POST['confirm_password']) {
                $_SESSION['message'] = "Passwords do not match!";
                $_SESSION['message_type'] = "danger";
                header('Location: ' . BASE_URL);
                exit;
            }

            $user = User::findByEmailOrUsername($_POST['username']);
            if ($user) {
                $_SESSION['message'] = "Username or email already exists!";
                $_SESSION['message_type'] = "danger";
                header('Location: ' . BASE_URL);
                exit;
            }

            $password_hash = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $verification_token = bin2hex(random_bytes(32));

            $data = [
                'username' => $_POST['username'],
                'email' => $_POST['email'],
                'password' => $password_hash,
                'role' => 'customer',
                'gender' => $_POST['gender'],
                'age' => $_POST['age'],
                'address' => $_POST['address'],
                'phone' => $_POST['phone'],
                'verification_token' => $verification_token,
                'token_expiry' => date('Y-m-d H:i:s', strtotime('+24 hours'))
            ];

            $userId = User::create($data);

            if ($userId) {
                $email_model = new Email();
                if ($email_model->sendVerificationEmail($_POST['email'], $verification_token)) {
                    $_SESSION['message'] = "Account created! Please check your email to verify your account.";
                    $_SESSION['message_type'] = "success";
                } else {
                    $_SESSION['message'] = "Account created but failed to send verification email.";
                    $_SESSION['message_type'] = "warning";
                }
            } else {
                $_SESSION['message'] = "Error creating account.";
                $_SESSION['message_type'] = "danger";
            }
            header('Location: ' . BASE_URL);
            exit;
        }
    }

    public function verify() {
        $token = $_GET['token'] ?? '';
        if (User::verifyEmail($token)) {
            $_SESSION['message'] = "Email verified successfully! You can now login.";
            $_SESSION['message_type'] = "success";
        } else {
            $_SESSION['message'] = "Invalid or expired token.";
            $_SESSION['message_type'] = "danger";
        }
        header('Location: ' . BASE_URL);
        exit;
    }
}
