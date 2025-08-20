<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Sale;
use App\Models\Product;

class HomeController extends Controller {
    public function index() {
        if (isset($_SESSION['user_id'])) {
            $stats = [
                'today_completed' => 0,
                'today_completed_amount' => 0,
                'today_pending' => 0,
                'today_pending_amount' => 0,
                'total_products' => 0,
                'low_stock' => 0,
                'pending_payments' => 0
            ];

            $recent_sales = Sale::getRecent(5);
            $products = Product::getAll();
            $stats['total_products'] = count($products);

            $this->view('home/index', [
                'title' => 'Dashboard',
                'stats' => $stats,
                'recent_sales' => $recent_sales
            ]);
        } else {
            $this->view('auth/login', ['title' => 'Login']);
        }
    }
}
