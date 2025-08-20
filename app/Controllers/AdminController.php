<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\User;

class AdminController extends Controller {
    public function __construct($route_params) {
        parent::__construct($route_params);
        $this->checkAuth(['admin']);
    }

    public function settings() {
        $this->view('Admin/settings', ['title' => 'Settings']);
    }

    public function createCashier() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if ($_POST['password'] !== $_POST['confirm_password']) {
                $_SESSION['message'] = "Passwords do not match!";
                $_SESSION['message_type'] = "danger";
                header('Location: ' . url('admin/settings'));
                exit;
            }

            $user = User::findByEmailOrUsername($_POST['username']);
            if ($user) {
                $_SESSION['message'] = "Username or email already exists!";
                $_SESSION['message_type'] = "danger";
                header('Location: ' . url('admin/settings'));
                exit;
            }

            $password_hash = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $data = [
                'username' => $_POST['username'],
                'email' => $_POST['email'],
                'password' => $password_hash,
            ];

            if (User::createCashier($data)) {
                $_SESSION['message'] = "Cashier account created successfully!";
                $_SESSION['message_type'] = "success";
            } else {
                $_SESSION['message'] = "Error creating cashier account.";
                $_SESSION['message_type'] = "danger";
            }
            header('Location: ' . url('admin/settings'));
            exit;
        }
    }

    public function reports() {
        $this->view('Admin/reports', ['title' => 'Sales Reports']);
    }

    private function checkAuth($roles = []) {
        if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], $roles)) {
            $_SESSION['message'] = "You are not authorized to view this page.";
            $_SESSION['message_type'] = "danger";
            header('Location: ' . BASE_URL);
            exit;
        }
    }
}
