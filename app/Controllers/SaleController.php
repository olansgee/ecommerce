<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Sale;
use App\Models\Product;

class SaleController extends Controller {
    public function index() {
        $this->checkAuth(['admin', 'cashier', 'customer']);
        $products = Product::getAll();

        $cart_items = [];
        if (isset($_SESSION['cart'])) {
            foreach ($_SESSION['cart'] as $product_id => $quantity) {
                $product = Product::getById($product_id);
                if ($product) {
                    $cart_items[] = [
                        'id' => $product_id,
                        'name' => $product['name'],
                        'price' => $product['price'],
                        'quantity' => $quantity
                    ];
                }
            }
        }

        $this->view('sales/index', [
            'title' => 'Point of Sale',
            'products' => $products,
            'cart_items' => $cart_items
        ]);
    }

    public function addToCart() {
        $this->checkAuth(['admin', 'cashier', 'customer']);
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $product_id = $_POST['product_id'];
            $quantity = $_POST['quantity'];
            $product = Product::getById($product_id);

            if ($product && $product['stock'] >= $quantity) {
                if (!isset($_SESSION['cart'])) {
                    $_SESSION['cart'] = [];
                }
                if (isset($_SESSION['cart'][$product_id])) {
                    $_SESSION['cart'][$product_id] += $quantity;
                } else {
                    $_SESSION['cart'][$product_id] = $quantity;
                }
                $_SESSION['message'] = "Product added to cart!";
                $_SESSION['message_type'] = "success";
            } else {
                $_SESSION['message'] = "Insufficient stock or product not found!";
                $_SESSION['message_type'] = "danger";
            }
        }
        header('Location: ' . url('sale'));
        exit;
    }

    public function removeFromCart() {
        $this->checkAuth(['admin', 'cashier', 'customer']);
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $product_id = $_POST['product_id'];
            if (isset($_SESSION['cart'][$product_id])) {
                unset($_SESSION['cart'][$product_id]);
                $_SESSION['message'] = "Item removed from cart!";
                $_SESSION['message_type'] = "success";
            }
        }
        header('Location: ' . url('sale'));
        exit;
    }

    public function checkout() {
        $this->checkAuth(['admin', 'cashier', 'customer']);
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $total = 0;
            $items = [];
            foreach ($_SESSION['cart'] as $product_id => $quantity) {
                $product = Product::getById($product_id);
                if ($product) {
                    $total += $product['price'] * $quantity;
                    $items[] = [
                        'product_id' => $product_id,
                        'product_name' => $product['name'],
                        'quantity' => $quantity,
                        'price' => $product['price']
                    ];
                }
            }

            $data = [
                'customer_id' => $_SESSION['user_id'],
                'customer_name' => $_POST['customer_name'],
                'customer_email' => $_POST['customer_email'],
                'total_amount' => $total,
                'payment_status' => 'incomplete',
                'delivery_status' => 'pending'
            ];

            $sale_id = Sale::create($data);

            if ($sale_id) {
                foreach($items as $item) {
                    $item['sale_id'] = $sale_id;
                    Sale::addSaleItem($item);
                }
                unset($_SESSION['cart']);
                $_SESSION['last_sale_id'] = $sale_id;
                $_SESSION['message'] = "Sale recorded! Admin must confirm payment.";
                $_SESSION['message_type'] = "warning";
                header('Location: ' . url('sale/receipt'));
            } else {
                $_SESSION['message'] = "Error processing sale.";
                $_SESSION['message_type'] = "danger";
                header('Location: ' . url('sale'));
            }
            exit;
        }
    }

    public function history() {
        $this->checkAuth(['admin', 'cashier']);
        $sales = Sale::getAll();
        $this->view('sales/history', ['title' => 'Sales History', 'sales' => $sales]);
    }

    public function receipt() {
        $this->checkAuth(['admin', 'cashier', 'customer']);
        $sale_id = $_SESSION['last_sale_id'] ?? null;
        $sale = $sale_id ? Sale::getById($sale_id) : null;
        $this->view('sales/receipt', ['title' => 'Receipt', 'sale' => $sale]);
    }

    public function confirmPayment() {
        $this->checkAuth(['admin', 'cashier']);
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $sale_id = $_POST['sale_id'];
            if (Sale::confirmPayment($sale_id)) {
                $_SESSION['message'] = "Payment confirmed successfully!";
                $_SESSION['message_type'] = "success";
            } else {
                $_SESSION['message'] = "Error confirming payment.";
                $_SESSION['message_type'] = "danger";
            }
        }
        header('Location: ' . url('sale/history'));
        exit;
    }

    public function updateDelivery() {
        $this->checkAuth(['admin']);
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $sale_id = $_POST['sale_id'];
            $status = $_POST['delivery_status'];
            if (Sale::updateDeliveryStatus($sale_id, $status)) {
                $_SESSION['message'] = "Delivery status updated!";
                $_SESSION['message_type'] = "success";
            } else {
                $_SESSION['message'] = "Error updating delivery status.";
                $_SESSION['message_type'] = "danger";
            }
        }
        header('Location: ' . url('sale/history'));
        exit;
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
