<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Product;

class ProductController extends Controller {
    public function index() {
        $this->checkAuth(['admin', 'cashier']);
        $products = Product::getAll();
        $this->view('products/index', ['title' => 'Products', 'products' => $products]);
    }

    public function create() {
        $this->checkAuth(['admin', 'cashier']);
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $image = $this->uploadImage($_FILES['image']);
            $data = [
                'name' => $_POST['name'],
                'description' => $_POST['description'],
                'price' => $_POST['price'],
                'stock' => $_POST['stock'],
                'category' => $_POST['category'],
                'image' => $image
            ];
            if (Product::create($data)) {
                $_SESSION['message'] = "Product added successfully!";
                $_SESSION['message_type'] = "success";
            } else {
                $_SESSION['message'] = "Error adding product.";
                $_SESSION['message_type'] = "danger";
            }
            header('Location: /product');
            exit;
        }
    }

    public function update() {
        $this->checkAuth(['admin']);
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $image = $_POST['current_image'];
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $image = $this->uploadImage($_FILES['image']);
                // Optionally delete old image
            }
            $data = [
                'id' => $_POST['product_id'],
                'name' => $_POST['name'],
                'description' => $_POST['description'],
                'price' => $_POST['price'],
                'stock' => $_POST['stock'],
                'category' => $_POST['category'],
                'image' => $image
            ];
            if (Product::update($data)) {
                $_SESSION['message'] = "Product updated successfully!";
                $_SESSION['message_type'] = "success";
            } else {
                $_SESSION['message'] = "Error updating product.";
                $_SESSION['message_type'] = "danger";
            }
            header('Location: /product');
            exit;
        }
    }

    public function delete() {
        $this->checkAuth(['admin']);
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = $_POST['product_id'];
            // TO-DO: check if product is in a sale
            if (Product::delete($id)) {
                $_SESSION['message'] = "Product deleted successfully!";
                $_SESSION['message_type'] = "success";
            } else {
                $_SESSION['message'] = "Error deleting product.";
                $_SESSION['message_type'] = "danger";
            }
            header('Location: /product');
            exit;
        }
    }

    private function checkAuth($roles = []) {
        if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], $roles)) {
            $_SESSION['message'] = "You are not authorized to view this page.";
            $_SESSION['message_type'] = "danger";
            header('Location: /');
            exit;
        }
    }

    private function uploadImage($file) {
        if ($file && $file['error'] === UPLOAD_ERR_OK) {
            $file_ext = pathinfo($file['name'], PATHINFO_EXTENSION);
            $file_name = uniqid('product_') . '.' . $file_ext;
            $target_file = 'uploads/' . $file_name;
            if (move_uploaded_file($file['tmp_name'], $target_file)) {
                return $file_name;
            }
        }
        return null;
    }
}
