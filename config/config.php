<?php

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'olansge1_olansgee');
define('DB_PASS', '#,Adewunmi16!');
define('DB_NAME', 'olansge1_ecommerce');

// Organization Details
define('ORG_NAME', 'Olansgee Technology');
define('ORG_ADDRESS', 'FIMI MONA OLUWA HOUSE Abiola Way Abeokuta, Ogun State');
define('ORG_PHONE', '+234 803 635 7536');
define('ORG_EMAIL', 'sales.olansgee@gmail.com');
define('REPORT_RECIPIENTS', 'sales.olansgee@gmail.com,olansgee@gmail.com');

// Application Settings
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https" : "http";
$host = $_SERVER['HTTP_HOST'];
$script_name = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
$base_path = rtrim(str_replace('/public', '', $script_name), '/');
define('BASE_URL', $protocol . '://' . $host . $base_path . '/public/');

define('UPLOAD_DIR', 'uploads/');

// API Keys & Credentials
define('GOOGLE_CLIENT_ID', 'YOUR_GOOGLE_CLIENT_ID');
define('PAYPAL_CLIENT_ID', 'YOUR_PAYPAL_CLIENT_ID');
define('PAYPAL_SECRET', 'YOUR_PAYPAL_SECRET');
define('PAYPAL_ENVIRONMENT', 'sandbox');

// SMTP Configuration
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'sales.olansgee@gmail.com');
define('SMTP_PASS', 'pdygnvlnuazjohwf');
define('SMTP_FROM', 'sales.olansgee@gmail.com');
define('SMTP_FROM_NAME', 'Olansgee Technology');

// Show errors for development
define('SHOW_ERRORS', true);
