<?php
session_start();
date_default_timezone_set('Africa/Lagos');

// Include PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require 'vendor/autoload.php'; // Adjust path as needed

// ============================================
// Configuration and Database Setup
// ============================================
define('DB_HOST', 'localhost');
define('DB_USER', 'olansge1_olansgee');
define('DB_PASS', '#,Adewunmi16!');
define('DB_NAME', 'olansge1_ecommerce');
define('ORG_NAME', 'Olansgee Technology');
define('ORG_ADDRESS', 'FIMI MONA OLUWA HOUSE Abiola Way Abeokuta, Ogun State');
define('ORG_PHONE', '+234 803 635 7536');
define('ORG_EMAIL', 'sales.olansgee@gmail.com');
define('REPORT_RECIPIENTS', 'sales.olansgee@gmail.com,olansgee@gmail.com');
define('BASE_URL', 'http://' . $_SERVER['HTTP_HOST'] . str_replace(basename($_SERVER['SCRIPT_NAME']), '', $_SERVER['SCRIPT_NAME']));
define('UPLOAD_DIR', 'uploads/');
define('GOOGLE_CLIENT_ID', 'YOUR_GOOGLE_CLIENT_ID');
define('PAYPAL_CLIENT_ID', 'YOUR_PAYPAL_CLIENT_ID');
define('PAYPAL_SECRET', 'YOUR_PAYPAL_SECRET');
define('PAYPAL_ENVIRONMENT', 'sandbox');
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'sales.olansgee@gmail.com');
define('SMTP_PASS', 'pdygnvlnuazjohwf');
define('SMTP_FROM', 'sales.olansgee@gmail.com');
define('SMTP_FROM_NAME', 'Olansgee Technology');

// Create database connection
function db_connect() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    return $conn;
}

// Initialize database structure
function init_db() {
    $conn = db_connect();
    
    // Create products table
    $conn->query("CREATE TABLE IF NOT EXISTS products (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        description TEXT,
        price DECIMAL(10,2) NOT NULL,
        stock INT NOT NULL DEFAULT 0,
        category VARCHAR(100),
        image VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    
    // Create reports table
    $conn->query("CREATE TABLE IF NOT EXISTS reports (
        id INT AUTO_INCREMENT PRIMARY KEY,
        report_date DATE NOT NULL,
        total_sales DECIMAL(10,2) NOT NULL,
        total_transactions INT NOT NULL,
        top_product VARCHAR(255),
        generated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    
    // Create users table with email verification
    $conn->query("CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) NOT NULL UNIQUE,
        email VARCHAR(255) NOT NULL UNIQUE,
        password VARCHAR(255),
        google_id VARCHAR(255),
        role ENUM('admin','cashier','customer') DEFAULT 'customer',
        gender ENUM('male','female','other') DEFAULT NULL,
        age INT DEFAULT NULL,
        address VARCHAR(255) DEFAULT NULL,
        phone VARCHAR(20) DEFAULT NULL,
        email_verified BOOLEAN DEFAULT 0,
        verification_token VARCHAR(255) DEFAULT NULL,
        token_expiry DATETIME DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    
    // Create sales table
    $conn->query("CREATE TABLE IF NOT EXISTS sales (
        id INT AUTO_INCREMENT PRIMARY KEY,
        customer_id INT DEFAULT NULL,
        customer_name VARCHAR(255),
        customer_email VARCHAR(255),
        total_amount DECIMAL(10,2) NOT NULL,
        sale_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        payment_status ENUM('pending','completed','failed','incomplete') DEFAULT 'pending',
        paypal_order_id VARCHAR(255),
        delivery_status ENUM('pending','delivered','completed') DEFAULT 'pending',
        last_reminder_sent DATETIME DEFAULT NULL,
        FOREIGN KEY (customer_id) REFERENCES users(id) ON DELETE SET NULL
    )");
    
    // Create sale_items table
    $conn->query("CREATE TABLE IF NOT EXISTS sale_items (
        id INT AUTO_INCREMENT PRIMARY KEY,
        sale_id INT NOT NULL,
        product_id INT NOT NULL,
        product_name VARCHAR(255) NOT NULL,
        quantity INT NOT NULL,
        price DECIMAL(10,2) NOT NULL,
        FOREIGN KEY (sale_id) REFERENCES sales(id) ON DELETE CASCADE,
        FOREIGN KEY (product_id) REFERENCES products(id)
    )");

    // Create activity logs table
    $conn->query("CREATE TABLE IF NOT EXISTS activity_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT,
        activity VARCHAR(255) NOT NULL,
        details TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
    )");
    
    // Insert admin user if not exists
    $admin_check = $conn->query("SELECT * FROM users WHERE username = 'admin'");
    if ($admin_check->num_rows == 0) {
        $password = password_hash('admin123', PASSWORD_DEFAULT);
        $conn->query("INSERT INTO users (username, email, password, role, email_verified) VALUES ('admin', 'admin@olansgee.tech', '$password', 'admin', 1)");
    }
    
    // Insert sample products if table is empty
    $product_check = $conn->query("SELECT * FROM products");
    if ($product_check->num_rows == 0) {
        $sample_products = [
            ["Laptop Pro 15", "The Laptop Pro 15 is a high-performance laptop designed for professionals. Featuring a stunning 15.6-inch 4K display, Intel Core i9 processor, 32GB RAM, and 1TB SSD storage. Perfect for graphic design, video editing, and multitasking.", 120000.00, 15, "Computers", "laptop.jpg"],
            ["Smartphone X", "Experience the future with Smartphone X. This flagship device features a 6.7-inch AMOLED display, 5G connectivity, triple camera system with 108MP main sensor, and all-day battery life. With its sleek design and powerful performance, it's the ultimate smartphone.", 85000.00, 30, "Mobile", "smartphone.jpg"],
            ["Wireless Headphones", "Immerse yourself in sound with our premium wireless headphones. Featuring active noise cancellation, 30-hour battery life, and crystal-clear audio quality. The comfortable over-ear design makes them perfect for travel, work, or relaxing at home.", 25000.00, 50, "Accessories", "headphones.jpg"],
            ["4K Smart TV", "Transform your living room with our 55-inch 4K Smart TV. Enjoy stunning picture quality with HDR10+ support, Dolby Atmos sound, and smart features including streaming apps and voice control. The perfect centerpiece for your entertainment system.", 180000.00, 10, "TV & Audio", "tv.jpg"],
            ["Tablet Mini", "The Tablet Mini combines power and portability in a compact 8-inch design. Featuring a high-resolution display, powerful processor, and support for stylus input. Perfect for note-taking, reading, and entertainment on the go.", 45000.00, 25, "Mobile", "tablet.jpg"]
        ];
        
        $stmt = $conn->prepare("INSERT INTO products (name, description, price, stock, category, image) VALUES (?, ?, ?, ?, ?, ?)");
        foreach ($sample_products as $p) {
            $stmt->bind_param("ssdiss", $p[0], $p[1], $p[2], $p[3], $p[4], $p[5]);
            $stmt->execute();
        }
    }
    
    // Create upload directory if it doesn't exist
    if (!file_exists(UPLOAD_DIR)) {
        mkdir(UPLOAD_DIR, 0777, true);
    }
    
    $conn->close();
}

// Initialize database
init_db();

// ============================================
// Logging Functions
// ============================================
function log_activity($user_id, $activity, $details = null) {
    $conn = db_connect();
    $stmt = $conn->prepare("INSERT INTO activity_logs (user_id, activity, details) VALUES (?, ?, ?)");
    $stmt->bind_param("iss", $user_id, $activity, $details);
    $stmt->execute();
    $conn->close();
}

// ============================================
// Email Functions
// ============================================
function send_verification_email($email, $token) {
    $mail = new PHPMailer(true);
    
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USER;
        $mail->Password   = SMTP_PASS;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = SMTP_PORT;

        // Recipients
        $mail->setFrom(SMTP_FROM, SMTP_FROM_NAME);
        $mail->addAddress($email);

        // Content
        $mail->isHTML(true);
        $mail->Subject = "Verify Your Email Address";
        
        $verification_link = BASE_URL . "index.php?token=$token";        
        
        $message = "
        <html>
        <head>
            <title>Email Verification</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #ddd; border-radius: 10px; }
                .button { display: inline-block; padding: 12px 24px; background-color: #3498db; color: white; text-decoration: none; border-radius: 4px; font-weight: bold; margin: 20px 0; }
                .footer { margin-top: 30px; font-size: 12px; color: #777; text-align: center; }
            </style>
        </head>
        <body>
            <div class='container'>
                <h2 style='color: #3498db;'>Welcome to " . ORG_NAME . "</h2>
                <p>Thank you for creating an account! Please verify your email address to complete your registration.</p>
                <p>Click the link below to verify your email:</p>
                <p style='text-align: center;'>
                    <a href='".htmlspecialchars($verification_link)."' class='button'>
                        Verify Email Address
                    </a>
                </p>
                <p>If you did not create an account, no further action is required.</p>
                <p>Best regards,<br>" . ORG_NAME . " Team</p>
                <div class='footer'>
                    <p>This is an automated message, please do not reply.</p>
                </div>
            </div>
        </body>
        </html>
        ";
        
        $mail->Body = $message;
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Mailer Error: " . $mail->ErrorInfo);
        return false;
    }
}

// Send receipt email
function send_receipt_email($customer_email, $receipt_html) {
    $mail = new PHPMailer(true);
    
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USER;
        $mail->Password   = SMTP_PASS;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = SMTP_PORT;

        // Recipients
        $mail->setFrom(SMTP_FROM, SMTP_FROM_NAME);
        $mail->addAddress($customer_email);

        // Content
        $mail->isHTML(true);
        $mail->Subject = "Your Purchase Receipt from " . ORG_NAME;
        $mail->Body = $receipt_html;
        
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Mailer Error: " . $mail->ErrorInfo);
        return false;
    }
}

// Send payment reminder email
function send_payment_reminder($sale_id, $customer_email, $customer_name, $items_list, $total_amount) {
    $mail = new PHPMailer(true);
    
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USER;
        $mail->Password   = SMTP_PASS;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = SMTP_PORT;

        // Recipients
        $mail->setFrom(SMTP_FROM, SMTP_FROM_NAME);
        $mail->addAddress($customer_email);

        // Content
        $mail->isHTML(true);
        $mail->Subject = "Reminder: Complete Your Payment for Order #$sale_id";
        
        $message = "
        <html>
        <head>
            <title>Payment Reminder</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #ddd; border-radius: 10px; }
                .button { display: inline-block; padding: 12px 24px; background-color: #3498db; color: white; text-decoration: none; border-radius: 4px; font-weight: bold; margin: 20px 0; }
                .footer { margin-top: 30px; font-size: 12px; color: #777; text-align: center; }
            </style>
        </head>
        <body>
            <div class='container'>
                <h2 style='color: #3498db;'>Dear $customer_name,</h2>
                <p>We noticed that you haven't completed your payment for Order #$sale_id.</p>
                <p>Here are the items in your order:</p>
                <ul>
                    $items_list
                </ul>
                <p><strong>Total Amount:</strong> ₦" . number_format($total_amount, 2) . "</p>
                <p>Please complete your payment to avoid cancellation of your order.</p>
                <p>You can complete your payment by logging into your account or by visiting our store.</p>
                <p>Best regards,<br>" . ORG_NAME . " Team</p>
                <div class='footer'>
                    <p>This is an automated message, please do not reply.</p>
                </div>
            </div>
        </body>
        </html>
        ";
        
        $mail->Body = $message;
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Mailer Error: " . $mail->ErrorInfo);
        return false;
    }
}

// Send payment reminders for incomplete orders
function send_payment_reminders() {
    $conn = db_connect();
    
    // Get incomplete sales that need reminders
    $query = "SELECT s.id, s.customer_name, s.customer_email, s.total_amount,
                     GROUP_CONCAT(CONCAT('<li>', i.product_name, ' (Qty: ', i.quantity, ')</li>') SEPARATOR '') as items_list
              FROM sales s
              JOIN sale_items i ON s.id = i.sale_id
              WHERE s.payment_status = 'incomplete'
                AND (
                    (s.last_reminder_sent IS NULL AND s.sale_date <= NOW() - INTERVAL 2 HOUR) OR
                    (s.last_reminder_sent <= NOW() - INTERVAL 24 HOUR)
                )
              GROUP BY s.id";
    
    $result = $conn->query($query);
    $reminders_sent = 0;
    
    while ($sale = $result->fetch_assoc()) {
        $sale_id = $sale['id'];
        $customer_email = $sale['customer_email'];
        $customer_name = $sale['customer_name'];
        $items_list = $sale['items_list'];
        $total_amount = $sale['total_amount'];
        
        if (send_payment_reminder($sale_id, $customer_email, $customer_name, $items_list, $total_amount)) {
            // Update last reminder time
            $update_stmt = $conn->prepare("UPDATE sales SET last_reminder_sent = NOW() WHERE id = ?");
            $update_stmt->bind_param("i", $sale_id);
            $update_stmt->execute();
            $reminders_sent++;
        }
    }
    
    $conn->close();
    return $reminders_sent;
}

// ============================================
// PayPal Helper Functions
// ============================================

function getPayPalAccessToken() {
    $authUrl = PAYPAL_ENVIRONMENT === 'sandbox' 
        ? 'https://api.sandbox.paypal.com/v1/oauth2/token' 
        : 'https://api.paypal.com/v1/oauth2/token';
    
    $ch = curl_init($authUrl);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Accept: application/json',
        'Accept-Language: en_US'
    ]);
    curl_setopt($ch, CURLOPT_USERPWD, PAYPAL_CLIENT_ID . ':' . PAYPAL_SECRET);
    curl_setopt($ch, CURLOPT_POSTFIELDS, 'grant_type=client_credentials');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    $response = curl_exec($ch);
    $data = json_decode($response, true);
    curl_close($ch);
    
    return $data['access_token'];
}

function capturePayPalOrder($orderId, $accessToken) {
    $captureUrl = PAYPAL_ENVIRONMENT === 'sandbox'
        ? 'https://api.sandbox.paypal.com/v2/checkout/orders/' . $orderId . '/capture'
        : 'https://api.paypal.com/v2/checkout/orders/' . $orderId . '/capture';
    
    $ch = curl_init($captureUrl);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $accessToken
    ]);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    $response = curl_exec($ch);
    $data = json_decode($response, true);
    curl_close($ch);
    
    return $data;
}

// ============================================
// Google Sign-In Integration
// ============================================

if (isset($_GET['code'])) {
    // Handle Google OAuth callback
    $token = file_get_contents('https://accounts.google.com/o/oauth2/token', false, stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => 'Content-type: application/x-www-form-urlencoded',
            'content' => http_build_query([
                'code' => $_GET['code'],
                'client_id' => GOOGLE_CLIENT_ID,
                'client_secret' => 'YOUR_GOOGLE_CLIENT_SECRET',
                'redirect_uri' => BASE_URL,
                'grant_type' => 'authorization_code'
            ])
        ]
    ]));
    
    $token = json_decode($token, true);
    
    if (isset($token['access_token'])) {
        $info = file_get_contents('https://www.googleapis.com/oauth2/v1/userinfo?access_token='.$token['access_token']);
        $info = json_decode($info, true);
        
        if (isset($info['id'])) {
            $conn = db_connect();
            $google_id = $info['id'];
            $email = $info['email'];
            $name = $info['name'];
            
            // Check if user exists
            $stmt = $conn->prepare("SELECT * FROM users WHERE google_id = ? OR email = ?");
            $stmt->bind_param("ss", $google_id, $email);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $user = $result->fetch_assoc();
            } else {
                // Create new user
                $username = explode('@', $email)[0];
                $stmt = $conn->prepare("INSERT INTO users (username, email, google_id, role, email_verified) VALUES (?, ?, ?, 'customer', 1)");
                $stmt->bind_param("sss", $username, $email, $google_id);
                $stmt->execute();
                $user_id = $conn->insert_id;
                
                $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $result = $stmt->get_result();
                $user = $result->fetch_assoc();
            }
            
            // Login user
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['message'] = "Login successful!";
            $_SESSION['message_type'] = "success";
            
            // Log activity
            log_activity($user['id'], "User logged in via Google");
            
            header("Location: index.php");
            exit;
        }
    }
}

// ============================================
// Email Verification Handler
// ============================================
if (isset($_GET['token'])) {
    $token = $_GET['token'];
    $conn = db_connect();
    
    // Prepare statement to find user by token
    $stmt = $conn->prepare("SELECT id FROM users WHERE verification_token = ? AND token_expiry > NOW()");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        $update_stmt = $conn->prepare("UPDATE users SET email_verified = 1, verification_token = NULL, token_expiry = NULL WHERE id = ?");
        $update_stmt->bind_param("i", $user['id']);
        
        if ($update_stmt->execute()) {
            $_SESSION['message'] = "Email verified successfully! You can now login.";
            $_SESSION['message_type'] = "success";
            log_activity($user['id'], "Email verified");
        } else {
            $_SESSION['message'] = "Error verifying email.";
            $_SESSION['message_type'] = "danger";
        }
    } else {
        $_SESSION['message'] = "Invalid or expired token.";
        $_SESSION['message_type'] = "danger";
    }
    
    header("Location: index.php");
    exit;
}
// ============================================
// Application Logic
// ============================================

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $conn = db_connect();
    
    if (isset($_POST['login'])) {
        $username = $_POST['username'];
        $password = $_POST['password'];
        
        $stmt = $conn->prepare("SELECT id, username, password, role, email, email_verified FROM users WHERE username = ? OR email = ?");
        $stmt->bind_param("ss", $username, $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows == 1) {
            $user = $result->fetch_assoc();
            if (password_verify($password, $user['password'])) {
                // Check if email is verified
                if (!$user['email_verified']) {
                    $_SESSION['message'] = "Email not verified! Please check your email for verification link.";
                    $_SESSION['message_type'] = "danger";
                    header("Location: index.php");
                    exit;
                }
                
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['message'] = "Login successful!";
                $_SESSION['message_type'] = "success";
                
                // Log activity
                log_activity($user['id'], "User logged in");
            } else {
                $_SESSION['message'] = "Invalid password!";
                $_SESSION['message_type'] = "danger";
            }
        } else {
            $_SESSION['message'] = "User not found!";
            $_SESSION['message_type'] = "danger";
        }
        
        header("Location: index.php");
        exit;
    }
    
    if (isset($_POST['signup'])) {
        $username = $_POST['username'];
        $email = $_POST['email'];
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];
        $gender = $_POST['gender'];
        $age = (int)$_POST['age']; // Cast to integer
        $address = $_POST['address'];
        $phone = $_POST['phone'];
        
        if ($password !== $confirm_password) {
            $_SESSION['message'] = "Passwords do not match!";
            $_SESSION['message_type'] = "danger";
            header("Location: index.php");
            exit;
        }
        
        // Check if user exists
        $stmt = $conn->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
        $stmt->bind_param("ss", $username, $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $_SESSION['message'] = "Username or email already exists!";
            $_SESSION['message_type'] = "danger";
            header("Location: index.php");
            exit;
        }
        
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        $verification_token = bin2hex(random_bytes(32));
        $token_expiry = date('Y-m-d H:i:s', strtotime('+24 hours'));
        $role = 'customer'; // Fixed role value
        
        // FIX: Added role parameter and corrected bind_param types
        $stmt = $conn->prepare("INSERT INTO users (username, email, password, role, gender, age, address, phone, verification_token, token_expiry) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssissss", $username, $email, $password_hash, $role, $gender, $age, $address, $phone, $verification_token, $token_expiry);
        
        if ($stmt->execute()) {
            $user_id = $conn->insert_id;
            // Log activity
            log_activity($user_id, "User account created");
            
            // Send verification email
            if (send_verification_email($email, $verification_token)) {
                $_SESSION['message'] = "Account created successfully! Please check your email to verify your account.";
                $_SESSION['message_type'] = "success";
            } else {
                $_SESSION['message'] = "Account created but failed to send verification email. Please contact support.";
                $_SESSION['message_type'] = "warning";
            }
        } else {
            $_SESSION['message'] = "Error creating account: " . $conn->error;
            $_SESSION['message_type'] = "danger";
        }
        
        header("Location: index.php");
        exit;
    }
    
    if (isset($_POST['logout'])) {
        // Log activity
        if(isset($_SESSION['user_id'])) {
            log_activity($_SESSION['user_id'], "User logged out");
        }
        session_destroy();
        header("Location: index.php");
        exit;
    }
    
    if (isset($_POST['add_product'])) {
        $name = $_POST['name'];
        $description = $_POST['description'];
        $price = $_POST['price'];
        $stock = $_POST['stock'];
        $category = $_POST['category'];
        $image = null;
        
        // Handle image upload
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $file_ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $file_name = uniqid('product_') . '.' . $file_ext;
            $target_file = UPLOAD_DIR . $file_name;
            
            if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
                $image = $file_name;
            } else {
                $_SESSION['message'] = "Error uploading image!";
                $_SESSION['message_type'] = "danger";
                header("Location: index.php#products");
                exit;
            }
        }
        
        $stmt = $conn->prepare("INSERT INTO products (name, description, price, stock, category, image) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssdiss", $name, $description, $price, $stock, $category, $image);
        
        if ($stmt->execute()) {
            $_SESSION['message'] = "Product added successfully!";
            $_SESSION['message_type'] = "success";
            
            // Log activity
            if(isset($_SESSION['user_id'])) {
                log_activity($_SESSION['user_id'], "Product added", "Name: $name");
            }
        } else {
            $_SESSION['message'] = "Error adding product: " . $conn->error;
            $_SESSION['message_type'] = "danger";
        }
        
        header("Location: index.php#products");
        exit;
    }
    
    // UPDATE PRODUCT HANDLER
    if (isset($_POST['update_product'])) {
        $product_id = $_POST['product_id'];
        $name = $_POST['name'];
        $description = $_POST['description'];
        $price = $_POST['price'];
        $stock = $_POST['stock'];
        $category = $_POST['category'];
        $image = null;
        
        // Handle image upload if provided
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $file_ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $file_name = uniqid('product_') . '.' . $file_ext;
            $target_file = UPLOAD_DIR . $file_name;
            
            if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
                $image = $file_name;
                
                // Delete old image if it exists
                $old_image = $_POST['current_image'];
                if ($old_image && file_exists(UPLOAD_DIR . $old_image)) {
                    unlink(UPLOAD_DIR . $old_image);
                }
            } else {
                $_SESSION['message'] = "Error uploading image!";
                $_SESSION['message_type'] = "danger";
                header("Location: index.php#products");
                exit;
            }
        } else {
            // Keep the existing image
            $image = $_POST['current_image'];
        }
        
        $stmt = $conn->prepare("UPDATE products SET name=?, description=?, price=?, stock=?, category=?, image=? WHERE id=?");
        $stmt->bind_param("ssdissi", $name, $description, $price, $stock, $category, $image, $product_id);
        
        if ($stmt->execute()) {
            $_SESSION['message'] = "Product updated successfully!";
            $_SESSION['message_type'] = "success";
            
            // Log activity
            if(isset($_SESSION['user_id'])) {
                log_activity($_SESSION['user_id'], "Product updated", "ID: $product_id");
            }
        } else {
            $_SESSION['message'] = "Error updating product: " . $conn->error;
            $_SESSION['message_type'] = "danger";
        }
        
        $conn->close();
        header("Location: index.php#products");
        exit;
    }
    
    if (isset($_POST['update_stock'])) {
        $product_id = $_POST['product_id'];
        $quantity = $_POST['quantity'];
        
        $stmt = $conn->prepare("UPDATE products SET stock = stock + ? WHERE id = ?");
        $stmt->bind_param("ii", $quantity, $product_id);
        
        if ($stmt->execute()) {
            $_SESSION['message'] = "Stock updated successfully!";
            $_SESSION['message_type'] = "success";
            
            // Log activity
            if(isset($_SESSION['user_id'])) {
                $product = get_product_by_id($product_id);
                log_activity($_SESSION['user_id'], "Stock updated", "Product: {$product['name']}, Quantity: $quantity");
            }
        } else {
            $_SESSION['message'] = "Error updating stock: " . $conn->error;
            $_SESSION['message_type'] = "danger";
        }
        
        header("Location: index.php#inventory");
        exit;
    }
    
    if (isset($_POST['add_to_cart'])) {
        $product_id = $_POST['product_id'];
        $quantity = $_POST['quantity'];
        
        $product = $conn->query("SELECT * FROM products WHERE id = $product_id")->fetch_assoc();
        
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
            
            // Log activity
            if(isset($_SESSION['user_id'])) {
                log_activity($_SESSION['user_id'], "Added to cart", "Product: {$product['name']}, Quantity: $quantity");
            }
        } else {
            $_SESSION['message'] = "Insufficient stock or product not found!";
            $_SESSION['message_type'] = "danger";
        }
        
        header("Location: index.php#pos");
        exit;
    }
    
    if (isset($_POST['remove_from_cart'])) {
        $product_id = $_POST['product_id'];
        
        if (isset($_SESSION['cart'][$product_id])) {
            unset($_SESSION['cart'][$product_id]);
            $_SESSION['message'] = "Item removed from cart!";
            $_SESSION['message_type'] = "success";
            
            // Log activity
            if(isset($_SESSION['user_id'])) {
                $product = get_product_by_id($product_id);
                if($product) {
                    log_activity($_SESSION['user_id'], "Removed from cart", "Product: {$product['name']}");
                }
            }
        }
        
        header("Location: index.php#pos");
        exit;
    }
    
    if (isset($_POST['create_paypal_order'])) {
        // Calculate total
        $total = 0;
        $items = [];
        foreach ($_SESSION['cart'] as $product_id => $quantity) {
            $product = $conn->query("SELECT * FROM products WHERE id = $product_id")->fetch_assoc();
            if ($product) {
                $subtotal = $product['price'] * $quantity;
                $total += $subtotal;
                $items[] = [
                    'product_id' => $product_id,
                    'name' => $product['name'],
                    'quantity' => $quantity,
                    'price' => $product['price'],
                    'subtotal' => $subtotal
                ];
            }
        }
        
        // Get customer details
        $customer_name = $_POST['customer_name'];
        $customer_email = $_POST['customer_email'];
        
        // Create sale record with pending status
        $customer_id = isset($_SESSION['user_id']) && $_SESSION['role'] == 'customer' ? $_SESSION['user_id'] : null;
        
        $stmt = $conn->prepare("INSERT INTO sales (customer_id, customer_name, customer_email, total_amount, payment_status, delivery_status) VALUES (?, ?, ?, ?, 'pending', 'pending')");
        $stmt->bind_param("issd", $customer_id, $customer_name, $customer_email, $total);
        $stmt->execute();
        $sale_id = $conn->insert_id;
        
        // Store sale data for PayPal processing
        $_SESSION['paypal_sale'] = [
            'id' => $sale_id,
            'customer_id' => $customer_id,
            'customer_name' => $customer_name,
            'customer_email' => $customer_email,
            'total' => $total,
            'items' => $items,
        ];
        
        // Return sale ID for PayPal integration
        echo json_encode(['sale_id' => $sale_id, 'total' => $total]);
        exit;
    }
    
    if (isset($_POST['complete_paypal_payment'])) {
        $sale_id = $_POST['sale_id'];
        $order_id = $_POST['order_id'];
        
        if (!isset($_SESSION['paypal_sale']) || $_SESSION['paypal_sale']['id'] != $sale_id) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid sale data']);
            exit;
        }
        
        $sale = $_SESSION['paypal_sale'];
        $conn->begin_transaction();
        
        try {
            // Capture PayPal payment
            $accessToken = getPayPalAccessToken();
            $captureResponse = capturePayPalOrder($order_id, $accessToken);
            
            if ($captureResponse['status'] !== 'COMPLETED') {
                throw new Exception('PayPal payment capture failed');
            }
            
            // Update sale with PayPal order ID and mark as completed
            $stmt = $conn->prepare("UPDATE sales SET payment_status = 'completed', paypal_order_id = ?, delivery_status = 'pending' WHERE id = ?");
            $stmt->bind_param("si", $order_id, $sale_id);
            $stmt->execute();
            
            // Create sale items and update inventory
            foreach ($sale['items'] as $item) {
                // Add sale item
                $stmt = $conn->prepare("INSERT INTO sale_items (sale_id, product_id, product_name, quantity, price) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("iisid", $sale_id, $item['product_id'], $item['name'], $item['quantity'], $item['price']);
                $stmt->execute();
                
                // Update stock
                $updateStock = $conn->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");
                $updateStock->bind_param("ii", $item['quantity'], $item['product_id']);
                $updateStock->execute();
            }
            
            // Commit transaction
            $conn->commit();
            
            // Store sale data for receipt
            $_SESSION['last_sale_id'] = $sale_id;
            
            // Clear cart and PayPal sale data
            unset($_SESSION['cart']);
            unset($_SESSION['paypal_sale']);
            
            // Log activity
            if(isset($_SESSION['user_id'])) {
                log_activity($_SESSION['user_id'], "Payment completed via PayPal", "Sale ID: $sale_id, Amount: {$sale['total']}");
            }
            
            echo json_encode(['status' => 'success', 'sale_id' => $sale_id]);
            exit;
            
        } catch (Exception $e) {
            // Rollback transaction on error
            $conn->rollback();
            
            // Update payment status to failed
            $stmt = $conn->prepare("UPDATE sales SET payment_status = 'failed' WHERE id = ?");
            $stmt->bind_param("i", $sale_id);
            $stmt->execute();
            
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
            exit;
        }
    }
    
    // Handle cash payment
    if (isset($_POST['complete_cash_payment'])) {
        $customer_name = $_POST['customer_name'];
        $customer_email = $_POST['customer_email'];
        $customer_id = isset($_SESSION['user_id']) && $_SESSION['role'] == 'customer' ? $_SESSION['user_id'] : null;
        
        // Calculate total
        $total = 0;
        $items = [];
        foreach ($_SESSION['cart'] as $product_id => $quantity) {
            $product = $conn->query("SELECT * FROM products WHERE id = $product_id")->fetch_assoc();
            if ($product) {
                $subtotal = $product['price'] * $quantity;
                $total += $subtotal;
                $items[] = [
                    'product_id' => $product_id,
                    'name' => $product['name'],
                    'quantity' => $quantity,
                    'price' => $product['price'],
                    'subtotal' => $subtotal
                ];
            }
        }
        
        $conn->begin_transaction();
        
        try {
            // Create sale record with incomplete status
            $stmt = $conn->prepare("INSERT INTO sales (customer_id, customer_name, customer_email, total_amount, payment_status, delivery_status) VALUES (?, ?, ?, ?, 'incomplete', 'pending')");
            $stmt->bind_param("issd", $customer_id, $customer_name, $customer_email, $total);
            $stmt->execute();
            $sale_id = $conn->insert_id;
            
            // Store sale ID for later reference
            $_SESSION['last_sale_id'] = $sale_id;
            
            // Create sale items
            foreach ($items as $item) {
                $stmt = $conn->prepare("INSERT INTO sale_items (sale_id, product_id, product_name, quantity, price) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("iisid", $sale_id, $item['product_id'], $item['name'], $item['quantity'], $item['price']);
                $stmt->execute();
            }
            
            $conn->commit();
            
            // Clear cart
            unset($_SESSION['cart']);
            
            $_SESSION['message'] = "Sale recorded with incomplete payment! Admin must confirm payment.";
            $_SESSION['message_type'] = "warning";
            
            // Log activity
            if(isset($_SESSION['user_id'])) {
                log_activity($_SESSION['user_id'], "Cash payment initiated", "Sale ID: $sale_id, Amount: $total");
            }
            
            header("Location: index.php#receipt");
            exit;
            
        } catch (Exception $e) {
            $conn->rollback();
            $_SESSION['message'] = "Error processing payment: " . $e->getMessage();
            $_SESSION['message_type'] = "danger";
            header("Location: index.php#pos");
            exit;
        }
    }
    
    // Handle payment confirmation by admin/cashier
    if (isset($_POST['confirm_payment'])) {
        $sale_id = $_POST['sale_id'];
        
        if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'cashier')) {
            $_SESSION['message'] = "Unauthorized action!";
            $_SESSION['message_type'] = "danger";
            header("Location: index.php#sales");
            exit;
        }
        
        $conn = db_connect();
        $conn->begin_transaction();
        
        try {
            // Update payment status to completed
            $stmt = $conn->prepare("UPDATE sales SET payment_status = 'completed' WHERE id = ?");
            $stmt->bind_param("i", $sale_id);
            $stmt->execute();
            
            // Get sale items and update inventory
            $stmt = $conn->prepare("SELECT * FROM sale_items WHERE sale_id = ?");
            $stmt->bind_param("i", $sale_id);
            $stmt->execute();
            $sale_items = $stmt->get_result();
            
            while ($item = $sale_items->fetch_assoc()) {
                // Update stock
                $update = $conn->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");
                $update->bind_param("ii", $item['quantity'], $item['product_id']);
                $update->execute();
            }
            
            $conn->commit();
            
            $_SESSION['message'] = "Payment confirmed successfully!";
            $_SESSION['message_type'] = "success";
            
            // Log activity
            log_activity($_SESSION['user_id'], "Payment confirmed", "Sale ID: $sale_id");
            
        } catch (Exception $e) {
            $conn->rollback();
            $_SESSION['message'] = "Error confirming payment: " . $e->getMessage();
            $_SESSION['message_type'] = "danger";
        }
        
        $conn->close();
        header("Location: index.php#sales");
        exit;
    }

    // ============================================
    // Delivery Status Update Handler
    // ============================================
    if (isset($_POST['update_delivery_status'])) {
        $sale_id = $_POST['sale_id'];
        $delivery_status = $_POST['delivery_status'];
        
        if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
            $_SESSION['message'] = "Unauthorized action! Only admin can update delivery status.";
            $_SESSION['message_type'] = "danger";
            header("Location: index.php#sales");
            exit;
        }
        
        $conn = db_connect();
        $stmt = $conn->prepare("UPDATE sales SET delivery_status = ? WHERE id = ?");
        $stmt->bind_param("si", $delivery_status, $sale_id);
        
        if ($stmt->execute()) {
            $_SESSION['message'] = "Delivery status updated successfully!";
            $_SESSION['message_type'] = "success";
            log_activity($_SESSION['user_id'], "Delivery status updated", "Sale ID: $sale_id, Status: $delivery_status");
        } else {
            $_SESSION['message'] = "Error updating delivery status: " . $conn->error;
            $_SESSION['message_type'] = "danger";
        }
        
        $conn->close();
        header("Location: index.php#sales");
        exit;
    }
    
    // Handle product deletion by admin
    if (isset($_POST['delete_product'])) {
        $product_id = $_POST['product_id'];
        
        if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
            $_SESSION['message'] = "Unauthorized action! Only admin can delete products.";
            $_SESSION['message_type'] = "danger";
            header("Location: index.php#products");
            exit;
        }
        
        $conn = db_connect();
        
        // First, check if the product exists in any sale
        $sale_check = $conn->query("SELECT * FROM sale_items WHERE product_id = $product_id");
        
        if ($sale_check->num_rows > 0) {
            $_SESSION['message'] = "Cannot delete product. It has been sold in transactions.";
            $_SESSION['message_type'] = "danger";
        } else {
            // Get image path
            $product = $conn->query("SELECT image FROM products WHERE id = $product_id")->fetch_assoc();
            
            // Delete product
            $stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
            $stmt->bind_param("i", $product_id);
            
            if ($stmt->execute()) {
                // Delete image file
                if ($product['image'] && file_exists(UPLOAD_DIR . $product['image'])) {
                    unlink(UPLOAD_DIR . $product['image']);
                }
                
                $_SESSION['message'] = "Product deleted successfully!";
                $_SESSION['message_type'] = "success";
                
                // Log activity
                log_activity($_SESSION['user_id'], "Product deleted", "ID: $product_id");
            } else {
                $_SESSION['message'] = "Error deleting product: " . $conn->error;
                $_SESSION['message_type'] = "danger";
            }
        }
        
        $conn->close();
        header("Location: index.php#products");
        exit;
    }
    
    // Admin create cashier account
    if (isset($_POST['create_cashier'])) {
        if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
            $_SESSION['message'] = "Unauthorized action!";
            $_SESSION['message_type'] = "danger";
            header("Location: index.php#settings");
            exit;
        }
        
        $username = $_POST['username'];
        $email = $_POST['email'];
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];
        
        if ($password !== $confirm_password) {
            $_SESSION['message'] = "Passwords do not match!";
            $_SESSION['message_type'] = "danger";
            header("Location: index.php#settings");
            exit;
        }
        
        // Check if user exists
        $stmt = $conn->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
        $stmt->bind_param("ss", $username, $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $_SESSION['message'] = "Username or email already exists!";
            $_SESSION['message_type'] = "danger";
            header("Location: index.php#settings");
            exit;
        }
        
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO users (username, email, password, role, email_verified) VALUES (?, ?, ?, 'cashier', 1)");
        $stmt->bind_param("sss", $username, $email, $password_hash);
        
        if ($stmt->execute()) {
            $_SESSION['message'] = "Cashier account created successfully!";
            $_SESSION['message_type'] = "success";
            
            // Log activity
            log_activity($_SESSION['user_id'], "Cashier account created", "Username: $username");
        } else {
            $_SESSION['message'] = "Error creating cashier account: " . $conn->error;
            $_SESSION['message_type'] = "danger";
        }
        
        header("Location: index.php#settings");
        exit;
    }
    
    // Handle receipt email request
    if (isset($_POST['send_receipt_email'])) {
        if (!isset($_SESSION['last_sale_id'])) {
            echo json_encode(['status' => 'error', 'message' => 'No receipt available']);
            exit;
        }
        
        $sale_id = $_SESSION['last_sale_id'];
        $sale = get_sale_by_id($sale_id);
        $receipt_html = generate_receipt_html($sale);
        
        if (send_receipt_email($sale['customer_email'], $receipt_html)) {
            $_SESSION['last_sale']['email_sent'] = true;
            echo json_encode(['status' => 'success']);
            
            // Log activity
            if(isset($_SESSION['user_id'])) {
                log_activity($_SESSION['user_id'], "Receipt emailed", "Sale ID: $sale_id");
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to send email']);
        }
        exit;
    }
    
    $conn->close();
}

// ============================================
// Receipt Generation Function
// ============================================
function generate_receipt_html($sale) {
    $html = '
    <!DOCTYPE html>
    <html>
    <head>
        <title>Purchase Receipt - ' . ORG_NAME . '</title>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; }
            .container { max-width: 800px; margin: 0 auto; padding: 20px; }
            .header { text-align: center; border-bottom: 2px dashed #ccc; padding-bottom: 20px; margin-bottom: 20px; }
            .footer { text-align: center; border-top: 2px dashed #ccc; padding-top: 20px; margin-top: 20px; font-style: italic; color: #666; }
            table { width: 100%; border-collapse: collapse; margin: 20px 0; }
            th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
            th { background-color: #f2f2f2; }
            .text-right { text-align: right; }
            .text-center { text-align: center; }
            .total-row { font-weight: bold; }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h2>' . ORG_NAME . '</h2>
                <p>' . ORG_ADDRESS . '</p>
                <p>Phone: ' . ORG_PHONE . ' | Email: ' . ORG_EMAIL . '</p>
                <h3>SALES INVOICE</h3>
            </div>
            
            <div>
                <p><strong>Invoice #:</strong> ' . str_pad($sale['id'], 6, '0', STR_PAD_LEFT) . '</p>
                <p><strong>Date:</strong> ' . $sale['sale_date'] . '</p>
                <p><strong>Customer:</strong> ' . $sale['customer_name'] . '</p>
                <p><strong>Email:</strong> ' . $sale['customer_email'] . '</p>
                <p><strong>Payment Status:</strong> '; 
    
    // Add payment status badge
    $payment_status = $sale['payment_status'];
    if ($payment_status == 'completed') {
        $html .= '<span style="background-color: #27ae60; color: white; padding: 3px 8px; border-radius: 4px;">Completed</span>';
    } elseif ($payment_status == 'pending') {
        $html .= '<span style="background-color: #f39c12; color: white; padding: 3px 8px; border-radius: 4px;">Pending</span>';
    } elseif ($payment_status == 'incomplete') {
        $html .= '<span style="background-color: #e74c3c; color: white; padding: 3px 8px; border-radius: 4px;">Incomplete</span>';
    } else {
        $html .= ucfirst($payment_status);
    }
    
    $html .= '</p>
                <p><strong>Delivery Status:</strong> ';
    
    // Add delivery status badge
    $delivery_status = $sale['delivery_status'];
    if ($delivery_status == 'delivered') {
        $html .= '<span style="background-color: #27ae60; color: white; padding: 3px 8px; border-radius: 4px;">Delivered</span>';
    } elseif ($delivery_status == 'pending') {
        $html .= '<span style="background-color: #f39c12; color: white; padding: 3px 8px; border-radius: 4px;">Pending</span>';
    } else {
        $html .= ucfirst($delivery_status);
    }
    
    $html .= '</p>
            </div>
            
            <table>
                <thead>
                    <tr>
                        <th>Product</th>
                        <th class="text-center">Price</th>
                        <th class="text-center">Qty</th>
                        <th class="text-right">Total</th>
                    </tr>
                </thead>
                <tbody>';
    
    // Get sale items
    $items = [];
    if (isset($sale['items'])) {
        $items = $sale['items'];
    } else {
        $conn = db_connect();
        $stmt = $conn->prepare("SELECT * FROM sale_items WHERE sale_id = ?");
        $stmt->bind_param("i", $sale['id']);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($item = $result->fetch_assoc()) {
            $items[] = $item;
        }
        $conn->close();
    }
    
    $total = 0;
    foreach ($items as $item) {
        $subtotal = $item['price'] * $item['quantity'];
        $total += $subtotal;
        $html .= '
                    <tr>
                        <td>' . $item['product_name'] . '</td>
                        <td class="text-center">₦' . number_format($item['price'], 2) . '</td>
                        <td class="text-center">' . $item['quantity'] . '</td>
                        <td class="text-right">₦' . number_format($subtotal, 2) . '</td>
                    </tr>';
    }
    
    $html .= '
                    <tr class="total-row">
                        <td colspan="3" class="text-right">TOTAL:</td>
                        <td class="text-right">₦' . number_format($total, 2) . '</td>
                    </tr>
                </tbody>
            </table>
            
            <div class="footer">
                <p>Thank you for your purchase!</p>
                <p>' . ORG_NAME . '</p>
            </div>
        </div>
    </body>
    </html>';
    
    return $html;
}

// ============================================
// Data Access Functions
// ============================================

// Get products
function get_products() {
    $conn = db_connect();
    $result = $conn->query("SELECT * FROM products ORDER BY name");
    $products = [];
    while ($row = $result->fetch_assoc()) {
        $products[] = $row;
    }
    $conn->close();
    return $products;
}

// Get product by ID
function get_product_by_id($id) {
    $conn = db_connect();
    $stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $product = $result->fetch_assoc();
    $conn->close();
    return $product;
}

// Get sale by ID
function get_sale_by_id($id) {
    $conn = db_connect();
    $stmt = $conn->prepare("SELECT * FROM sales WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $sale = $result->fetch_assoc();
    
    if ($sale) {
        $stmt = $conn->prepare("SELECT * FROM sale_items WHERE sale_id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $sale['items'] = $items;
    }
    
    $conn->close();
    return $sale;
}

// Get recent sales
function get_recent_sales($limit = 5, $customer_id = null) {
    $conn = db_connect();
    
    if ($customer_id) {
        $query = "SELECT s.*, COUNT(i.id) as items_count 
                  FROM sales s 
                  JOIN sale_items i ON s.id = i.sale_id 
                  WHERE s.customer_id = ?
                  GROUP BY s.id 
                  ORDER BY s.sale_date DESC 
                  LIMIT ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ii", $customer_id, $limit);
    } else {
        $query = "SELECT s.*, COUNT(i.id) as items_count 
                  FROM sales s 
                  JOIN sale_items i ON s.id = i.sale_id 
                  GROUP BY s.id 
                  ORDER BY s.sale_date DESC 
                  LIMIT ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $limit);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    $sales = [];
    while ($row = $result->fetch_assoc()) {
        $sales[] = $row;
    }
    $conn->close();
    return $sales;
}

// Get all sales for admin/cashier
function get_all_sales() {
    $conn = db_connect();
    $result = $conn->query("
        SELECT s.*, u.username as customer_username 
        FROM sales s
        LEFT JOIN users u ON s.customer_id = u.id
        ORDER BY s.sale_date DESC
    ");
    $sales = [];
    while ($row = $result->fetch_assoc()) {
        $sales[] = $row;
    }
    $conn->close();
    return $sales;
}

// Get customer transactions
function get_customer_transactions($customer_id) {
    $conn = db_connect();
    $stmt = $conn->prepare("SELECT * FROM sales WHERE customer_id = ? ORDER BY sale_date DESC");
    $stmt->bind_param("i", $customer_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $transactions = [];
    while ($row = $result->fetch_assoc()) {
        $transactions[] = $row;
    }
    $conn->close();
    return $transactions;
}

// Get sales reports for different time periods
function get_sales_reports($period = 'today') {
    $conn = db_connect();
    $reports = [];
    
    $today = date('Y-m-d');
    $start_date = '';
    $end_date = $today;
    
    switch ($period) {
        case 'today':
            $start_date = $today;
            break;
        case 'week':
            $start_date = date('Y-m-d', strtotime('-1 week'));
            break;
        case 'month':
            $start_date = date('Y-m-d', strtotime('-1 month'));
            break;
        case 'year':
            $start_date = date('Y-m-d', strtotime('-1 year'));
            break;
        default:
            $start_date = $today;
    }
    
    $query = "SELECT 
                DATE(sale_date) as sale_day,
                COUNT(id) as transaction_count,
                SUM(total_amount) as total_sales,
                SUM(CASE WHEN payment_status = 'completed' THEN 1 ELSE 0 END) as completed_sales,
                SUM(CASE WHEN payment_status = 'pending' OR payment_status = 'incomplete' THEN 1 ELSE 0 END) as pending_sales
              FROM sales
              WHERE DATE(sale_date) BETWEEN ? AND ?
              GROUP BY sale_day
              ORDER BY sale_day DESC";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ss", $start_date, $end_date);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $reports[] = $row;
    }
    
    $conn->close();
    return $reports;
}

// Get dashboard stats
function get_dashboard_stats($customer_id = null, $role = null) {
    $conn = db_connect();
    
    $today = date('Y-m-d');
    $stats = [
        'today_completed' => 0,   // completed transactions/sales today
        'today_completed_amount' => 0,
        'today_pending' => 0,     // pending transactions/sales today
        'today_pending_amount' => 0,
        'total_products' => 0,
        'low_stock' => 0,
        'pending_payments' => 0
    ];
    
    // Today's completed transactions/sales
    $query_completed = "SELECT COUNT(id) as transactions, SUM(total_amount) as sales 
              FROM sales 
              WHERE DATE(sale_date) = ? AND payment_status = 'completed'";
              
    // Today's pending transactions/sales
    $query_pending = "SELECT COUNT(id) as transactions, SUM(total_amount) as sales 
              FROM sales 
              WHERE DATE(sale_date) = ? AND payment_status IN ('pending', 'incomplete')";
              
    if ($customer_id) {
        $query_completed .= " AND customer_id = ?";
        $query_pending .= " AND customer_id = ?";
        
        $stmt_completed = $conn->prepare($query_completed);
        $stmt_completed->bind_param("si", $today, $customer_id);
        $stmt_completed->execute();
        $result_completed = $stmt_completed->get_result();
        
        $stmt_pending = $conn->prepare($query_pending);
        $stmt_pending->bind_param("si", $today, $customer_id);
        $stmt_pending->execute();
        $result_pending = $stmt_pending->get_result();
    } else {
        $stmt_completed = $conn->prepare($query_completed);
        $stmt_completed->bind_param("s", $today);
        $stmt_completed->execute();
        $result_completed = $stmt_completed->get_result();
        
        $stmt_pending = $conn->prepare($query_pending);
        $stmt_pending->bind_param("s", $today);
        $stmt_pending->execute();
        $result_pending = $stmt_pending->get_result();
    }
    
    if ($row = $result_completed->fetch_assoc()) {
        $stats['today_completed'] = $row['transactions'];
        $stats['today_completed_amount'] = $row['sales'] ? $row['sales'] : 0;
    }
    
    if ($row = $result_pending->fetch_assoc()) {
        $stats['today_pending'] = $row['transactions'];
        $stats['today_pending_amount'] = $row['sales'] ? $row['sales'] : 0;
    }
    
    // Product counts (only for admin/cashier)
    if ($role == 'admin' || $role == 'cashier') {
        $result = $conn->query("SELECT COUNT(id) as total_products, 
                               SUM(CASE WHEN stock <= 5 THEN 1 ELSE 0 END) as low_stock 
                               FROM products");
        if ($row = $result->fetch_assoc()) {
            $stats['total_products'] = $row['total_products'];
            $stats['low_stock'] = $row['low_stock'];
        }
        
        // Pending payments count
        $result = $conn->query("SELECT COUNT(id) as count FROM sales WHERE payment_status = 'incomplete'");
        if ($row = $result->fetch_assoc()) {
            $stats['pending_payments'] = $row['count'];
        }
    }
    
    $conn->close();
    return $stats;
}

// Get activity logs
function get_activity_logs($limit = 20) {
    $conn = db_connect();
    $result = $conn->query("
        SELECT al.*, u.username 
        FROM activity_logs al
        LEFT JOIN users u ON al.user_id = u.id
        ORDER BY created_at DESC
        LIMIT $limit
    ");
    $logs = [];
    while ($row = $result->fetch_assoc()) {
        $logs[] = $row;
    }
    $conn->close();
    return $logs;
}

// Get all users
function get_all_users() {
    $conn = db_connect();
    $result = $conn->query("SELECT id, username, email, role, created_at, email_verified FROM users");
    $users = [];
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
    $conn->close();
    return $users;
}

// ============================================
// HTML Output
// ============================================
$products = get_products();

// Send payment reminders if admin/cashier is logged in
if (isset($_SESSION['user_id']) && ($_SESSION['role'] == 'admin' || $_SESSION['role'] == 'cashier')) {
    $reminders_sent = send_payment_reminders();
    if ($reminders_sent > 0) {
        log_activity($_SESSION['user_id'], "Sent payment reminders", "Count: $reminders_sent");
    }
}

// Check if we need to show a product for social sharing
if (isset($_GET['action']) && $_GET['action'] == 'product' && isset($_GET['id'])) {
    $product = get_product_by_id($_GET['id']);
    if ($product) {
        // Generate Open Graph meta tags for social sharing
        $image_url = $product['image'] ? BASE_URL . UPLOAD_DIR . $product['image'] : BASE_URL . 'placeholder.jpg';

        
        echo '<!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>'.htmlspecialchars($product['name']).' - '.ORG_NAME.'</title>
            
            <!-- Open Graph Meta Tags -->
            <meta property="og:title" content="'.htmlspecialchars($product['name']).'">
            <meta property="og:description" content="'.htmlspecialchars(substr($product['description'], 0, 200)).'">
            <meta property="og:image" content="'.$image_url.'">
            <meta property="og:url" content="'.BASE_URL.'?action=product&id='.$product['id'].'">
            <meta property="og:type" content="product">
            <meta property="og:image:width" content="600">
            <meta property="og:image:height" content="600">
            
            <!-- Twitter Card Tags -->
            <meta name="twitter:card" content="summary_large_image">
            <meta name="twitter:title" content="'.htmlspecialchars($product['name']).'">
            <meta name="twitter:description" content="'.htmlspecialchars(substr($product['description'], 0, 200)).'">
            <meta name="twitter:image" content="'.$image_url.'">
            
            <!-- WhatsApp Tags -->
            <meta property="og:image:type" content="image/jpeg">
            <meta property="og:image:alt" content="'.htmlspecialchars($product['name']).'">
    
            <style>
                body {
                    font-family: Arial, sans-serif;
                    text-align: center;
                    padding: 50px;
                    background-color: #f5f7fb;
                }
                .product-container {
                    max-width: 600px;
                    margin: 0 auto;
                    background: white;
                    padding: 30px;
                    border-radius: 10px;
                    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
                }
                .product-image {
                    max-height: 300px;
                    margin-bottom: 20px;
                }
                .btn {
                    display: inline-block;
                    padding: 12px 24px;
                    background-color: #3498db;
                    color: white;
                    text-decoration: none;
                    border-radius: 4px;
                    font-weight: bold;
                    margin: 10px;
                }
            </style>
        </head>
        <body>
            <div class="product-container">
                <h1>'.htmlspecialchars($product['name']).'</h1>
                <img src="'.$image_url.'" alt="'.htmlspecialchars($product['name']).'" class="product-image">
                <p>'.htmlspecialchars(substr($product['description'], 0, 200)).'...</p>
                <p class="price"><strong>Price: ₦'.number_format($product['price'], 2).'</strong></p>
                <p>Available at '.ORG_NAME.'</p>
                <div>
                    <a href="'.BASE_URL.'" class="btn">Visit Our Store</a>
                </div>
            </div>
        </body>
        </html>';
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Olansgee Technology - Ecommerce System</title>
    
    <!-- Open Graph Meta Tags for main page -->
    <meta property="og:title" content="<?= ORG_NAME ?> - Ecommerce Store">
    <meta property="og:description" content="Premium electronics and tech products at competitive prices">
    <meta property="og:image" content="<?= BASE_URL ?>og-image.jpg">
    <meta property="og:url" content="<?= BASE_URL ?>">
    <meta property="og:type" content="website">
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Sign-In -->
    <script src="https://accounts.google.com/gsi/client" async defer></script>
    
    <!-- PayPal SDK -->
    <script src="https://www.paypal.com/sdk/js?client-id=<?= PAYPAL_CLIENT_ID ?>&currency=USD"></script>
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <style>
        :root {
            --primary: #3498db;
            --secondary: #2c3e50;
            --success: #27ae60;
            --warning: #f39c12;
            --danger: #e74c3c;
            --light: #f8f9fa;
            --dark: #343a40;
        }
        
        body {
            background-color: #f5f7fb;
            color: #333;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            padding-top: 56px; /* For fixed navbar */
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }
        
        /* Navbar */
        .navbar {
            background: linear-gradient(180deg, var(--secondary), #1a2530);
            color: white;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1030;
        }
        
        .main-content {
            padding: 20px;
            transition: all 0.3s;
            flex: 1;
        }
        
        .card {
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            border: none;
            margin-bottom: 20px;
            transition: transform 0.3s;
        }
        
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 12px rgba(0,0,0,0.1);
        }
        
        .stat-card {
            text-align: center;
            padding: 20px;
        }
        
        .stat-card i {
            font-size: 2.5rem;
            margin-bottom: 15px;
        }
        
        .stat-card .number {
            font-size: 2rem;
            font-weight: bold;
        }
        
        .stat-card .label {
            color: #6c757d;
            font-size: 0.9rem;
        }
        
        .bg-primary-light {
            background: rgba(52, 152, 219, 0.1);
            color: var(--primary);
        }
        
        .bg-success-light {
            background: rgba(39, 174, 96, 0.1);
            color: var(--success);
        }
        
        .bg-warning-light {
            background: rgba(243, 156, 18, 0.1);
            color: var(--warning);
        }
        
        .bg-danger-light {
            background: rgba(231, 76, 60, 0.1);
            color: var(--danger);
        }
        
        .btn-primary {
            background-color: var(--primary);
            border-color: var(--primary);
        }
        
        .btn-primary:hover {
            background-color: #2980b9;
            border-color: #2980b9;
        }
        
        .btn-success {
            background-color: var(--success);
            border-color: var(--success);
        }
        
        .btn-outline-primary {
            color: var(--primary);
            border-color: var(--primary);
        }
        
        .btn-outline-primary:hover {
            background-color: var(--primary);
            color: white;
        }
        
        .table th {
            background-color: var(--primary);
            color: white;
        }
        
        .badge-success {
            background-color: var(--success);
        }
        
        .badge-warning {
            background-color: var(--warning);
        }
        
        .badge-danger {
            background-color: var(--danger);
        }
        
        .badge-incomplete {
            background-color: #e74c3c;
            color: white;
        }
        
        .cart-item {
            border-bottom: 1px solid #eee;
            padding: 10px 0;
        }
        
        .cart-item:last-child {
            border-bottom: none;
        }
        
        .receipt {
            background: white;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            max-width: 800px;
            margin: 0 auto;
            padding: 30px;
        }
        
        .receipt-header {
            text-align: center;
            border-bottom: 2px dashed #ccc;
            padding-bottom: 20px;
            margin-bottom: 20px;
        }
        
        .receipt-footer {
            text-align: center;
            border-top: 2px dashed #ccc;
            padding-top: 20px;
            margin-top: 20px;
            font-style: italic;
            color: #666;
        }
        
        .page-title {
            position: relative;
            padding-bottom: 15px;
            margin-bottom: 25px;
        }
        
        .page-title:after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 50px;
            height: 3px;
            background: var(--primary);
        }
        
        .product-card {
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .product-card:hover {
            transform: scale(1.03);
            box-shadow: 0 8px 15px rgba(0,0,0,0.1);
        }
        
        .stock-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            font-size: 0.8rem;
        }
        
        .modal-content {
            border-radius: 10px;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 0.25rem rgba(52, 152, 219, 0.25);
        }
        
        .alert {
            border-radius: 8px;
        }
        
        .footer {
            background: var(--secondary);
            color: white;
            padding: 20px;
            text-align: center;
            margin-top: 30px;
            flex-shrink: 0;
        }
        
        .product-image {
            height: 150px;
            object-fit: contain;
            padding: 10px;
            background: #fff;
            border-radius: 8px;
            margin-bottom: 15px;
        }
        
        .product-image-thumb {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 4px;
        }
        
        .upload-preview {
            max-height: 200px;
            display: block;
            margin: 10px auto;
            border-radius: 8px;
        }
        
        .product-detail-image {
            max-height: 400px;
            width: 100%;
            object-fit: contain;
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
        }
        
        .social-sharing {
            display: flex;
            gap: 10px;
            margin-top: 20px;
            justify-content: center;
        }
        
        .social-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            color: white;
            font-size: 18px;
            transition: transform 0.3s;
        }
        
        .social-btn:hover {
            transform: translateY(-3px);
        }
        
        .facebook { background-color: #3b5998; }
        .twitter { background-color: #1da1f2; }
        .whatsapp { background-color: #25d366; }
        .linkedin { background-color: #0077b5; }
        .instagram { background: linear-gradient(45deg, #f09433, #e6683c, #dc2743, #cc2366, #bc1888); }
        
        .product-description {
            line-height: 1.8;
            font-size: 16px;
            color: #444;
        }
        
        .feature-section {
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            padding: 60px 0;
            border-radius: 15px;
            margin: 40px 0;
        }
        
        .product-feature {
            text-align: center;
            padding: 20px;
        }
        
        .product-feature i {
            font-size: 2.5rem;
            color: var(--primary);
            margin-bottom: 15px;
        }
        
        .payment-info {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            margin-top: 20px;
        }
        
        .auth-tabs .nav-link {
            color: var(--dark);
            border-radius: 0;
            border: none;
            padding: 12px 20px;
        }
        
        .auth-tabs .nav-link.active {
            background-color: var(--primary);
            color: white;
            border: none;
        }
        
        .auth-container {
            max-width: 500px;
            margin: 0 auto;
        }
        
        #paypal-button-container {
            margin-top: 20px;
        }
        
        .payment-options {
            display: flex;
            gap: 15px;
            margin-top: 20px;
        }
        
        .payment-option {
            flex: 1;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 15px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .payment-option:hover, .payment-option.active {
            border-color: var(--primary);
            background-color: rgba(52, 152, 219, 0.05);
        }
        
        .payment-option i {
            font-size: 2rem;
            margin-bottom: 10px;
            color: #666;
        }
        
        .payment-option.active i {
            color: var(--primary);
        }
        
        .payment-status-incomplete {
            color: #e74c3c;
            font-weight: bold;
        }
        
        /* Tooltip styling */
        .btn-tooltip {
            position: relative;
            display: inline-block;
        }
        
        .btn-tooltip .tooltip-text {
            visibility: hidden;
            width: 80px;
            background-color: #555;
            color: #fff;
            text-align: center;
            border-radius: 6px;
            padding: 5px;
            position: absolute;
            z-index: 1;
            bottom: 125%;
            left: 50%;
            margin-left: -40px;
            opacity: 0;
            transition: opacity 0.3s;
            font-size: 12px;
        }
        
        .btn-tooltip:hover .tooltip-text {
            visibility: visible;
            opacity: 1;
        }
        
        .report-period-selector {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        
        .report-period-btn {
            flex: 1;
            min-width: 80px;
            text-align: center;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 5px;
            cursor: pointer;
            border: 1px solid #ddd;
        }
        
        .report-period-btn.active {
            background-color: var(--primary);
            color: white;
            border-color: var(--primary);
        }
        
        .chart-container {
            height: 300px;
            margin-bottom: 30px;
        }
        
        /* Mobile Sidebar */
        .offcanvas-sidebar {
            background: linear-gradient(180deg, var(--secondary), #1a2530);
            color: white;
            width: 250px;
        }
        
        .sidebar-desktop {
            height: calc(100vh - 56px);
            position: fixed;
            top: 56px;
            left: 0;
            overflow-y: auto;
            background: linear-gradient(180deg, var(--secondary), #1a2530);
            color: white;
            z-index: 1000;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
        }
        
        .offcanvas-body .nav-link, 
        .sidebar-desktop .nav-link {
            color: rgba(255,255,255,0.8);
            border-radius: 0;
            padding: 15px 20px;
            margin: 2px 0;
            transition: all 0.3s;
        }
        
        .offcanvas-body .nav-link:hover, 
        .offcanvas-body .nav-link.active,
        .sidebar-desktop .nav-link:hover,
        .sidebar-desktop .nav-link.active {
            background: rgba(255,255,255,0.1);
            color: white;
            padding-left: 25px;
        }
        
        .offcanvas-body .nav-link i,
        .sidebar-desktop .nav-link i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }
        
        /* Responsive layout adjustments */
        @media (max-width: 991.98px) {
            .sidebar-desktop {
                display: none;
            }
        }
        
        @media (min-width: 992px) {
            .main-content {
                margin-left: 250px;
                width: calc(100% - 250px);
            }
            .footer {
                margin-left: 250px;
                width: calc(100% - 250px);
            }
        }

        /* Add to existing CSS */
        @media (min-width: 992px) {
            .main-content, 
            .footer, 
            #settings,
            #receipt,
            .receipt{
                margin-left: 250px;
                width: calc(100% - 250px);
            }
        }

        /* Print styles */
        @media print {
            body * {
                visibility: hidden;
            }
            .receipt, .receipt * {
                visibility: visible;
            }
            .receipt {
                position: absolute;
                left: 0;
                top: 0;
                width: 100%;
            }
        }
        
        /* Activity Log Styling */
        .activity-log-item {
            border-left: 3px solid #3498db;
            padding: 10px 15px;
            margin-bottom: 10px;
            background: #f8f9fa;
            border-radius: 0 5px 5px 0;
        }
        .activity-log-item .time {
            font-size: 12px;
            color: #6c757d;
        }
        .activity-log-item .details {
            font-size: 14px;
            color: #495057;
        }

        // In the <style> section, add these rules:

/* Add to existing CSS */
#checkoutModal {
    z-index: 1100 !important;
}

.main-content {
    position: relative;
    z-index: 1001;
}

#receipt {
    position: relative;
    z-index: 1001;
    padding: 20px;
}

.receipt {
    max-width: 800px;
    margin: 0 auto;
    background: white;
    border-radius: 10px;
    box-shadow: 0 0 20px rgba(0,0,0,0.1);
    padding: 30px;
    position: relative;
    z-index: 1001;
}

@media print {
    .sidebar-desktop,
    .navbar,
    .offcanvas,
    .footer {
        display: none !important;
    }
    
    body > *:not(#receipt) {
        display: none !important;
    }
    
    #receipt {
        position: static;
        width: 100%;
        margin: 0;
        padding: 0;
    }
    
    .receipt {
        max-width: 100% !important;
        box-shadow: none !important;
        padding: 20px !important;
        margin: 0 !important;
        page-break-inside: avoid;
    }
}
    </style>
</head>
<body>
     <!-- Top Navigation -->
     <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container-fluid">
            <button class="navbar-toggler" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebar">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <a class="navbar-brand" href="../index.php?ufname=<?php echo isset($_SESSION['username']) ?  $_SESSION['username'] : ' ' ;?>">
                <img src='../assets/img/newlogo3.png' width='30' height='30' viewBox='0 0 24 24'%3e%3cpath fill='%23ffffff' d='M2 21V7l10-4l10 4v14h-6v-8H8v8H2Zm10 0q-.825 0-1.413-.588T10 19q0-.825.588-1.413T12 17q.825 0 1.413.588T14 19q0 .825-.588 1.413T12 22ZM6.15 6l2.4 5h7l2.75-5H6.15ZM5.2 4h14.75q.575 0 .875.513t.025 1.037l-3.55 6.4q-.275.5-.738.775T15.55 13H8.1L7 15h12v2H7q-1.125 0-1.7-.988t-.05-1.962L6.6 11.6L3 4H1V2h3.25l.95 2Zm3.35 7h7h-7Z'/%3e%3c/svg%3e" 
                     alt="Logo" width="30" height="30" class="d-inline-block align-top me-2">
                <?= ORG_NAME ?>
            </a>
            
            <div class="d-flex align-items-center">
                <div class="me-3 d-none d-md-block text-white">
                    <i class="fas fa-calendar-day me-1"></i> <?= date('M j, Y') ?>
                </div>
                <div class="me-3 d-none d-md-block text-white">
                    <i class="fas fa-clock me-1"></i> <span id="current-time"><?= date('h:i:s A') ?></span>
                </div>
                
                <?php if(isset($_SESSION['user_id'])): ?>
                <div class="dropdown">
                    <button class="btn btn-outline-light dropdown-toggle" type="button" id="userDropdown" data-bs-toggle="dropdown">
                        <i class="fas fa-user-circle me-1"></i> <?= $_SESSION['username'] ?>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li>
                            <form method="post">
                                <button type="submit" name="logout" class="dropdown-item">
                                    <i class="fas fa-sign-out-alt me-1"></i> Logout
                                </button>
                            </form>
                        </li>
                    </ul>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </nav>
    
    <!-- Mobile Sidebar (Offcanvas) -->
    <div class="offcanvas offcanvas-start offcanvas-sidebar" tabindex="-1" id="sidebar">
        <div class="offcanvas-header">
            <h5 class="offcanvas-title"><?= ORG_NAME ?></h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body p-0">
            <ul class="nav flex-column">
                <li class="nav-item">
                    <a class="nav-link" href="#dashboard">
                        <i class="fas fa-tachometer-alt"></i> Dashboard
                    </a>
                </li>
                <?php if(isset($_SESSION['user_id'])): ?>
                <li class="nav-item">
                    <a class="nav-link" href="#pos">
                        <i class="fas fa-cash-register"></i> Point of Sale
                    </a>
                </li>
                <?php endif; ?>
                <?php if(isset($_SESSION['role']) && ($_SESSION['role'] == 'admin' || $_SESSION['role'] == 'cashier')): ?>
                <li class="nav-item">
                    <a class="nav-link" href="#products">
                        <i class="fas fa-box"></i> Products
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#inventory">
                        <i class="fas fa-warehouse"></i> Inventory
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#sales">
                        <i class="fas fa-chart-line"></i> Sales
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#reports">
                        <i class="fas fa-file-alt"></i> Reports
                    </a>
                </li>
                <?php endif; ?>
                <li class="nav-item">
                    <a class="nav-link" href="#receipt">
                        <i class="fas fa-receipt"></i> Receipts
                    </a>
                </li>
                <?php if(isset($_SESSION['role']) && $_SESSION['role'] == 'customer'): ?>
                <li class="nav-item">
                    <a class="nav-link" href="#transactions">
                        <i class="fas fa-history"></i> My Transactions
                    </a>
                </li>
                <?php endif; ?>
                <?php if(isset($_SESSION['role']) && $_SESSION['role'] == 'admin'): ?>
                <li class="nav-item">
                    <a class="nav-link" href="#settings">
                        <i class="fas fa-cog"></i> Settings
                    </a>
                </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
    
    <!-- Desktop Sidebar -->
    <div class="d-none d-lg-block sidebar-desktop">
        <div class="pt-3">
            <div class="text-center mb-4">
                <a class="navbar-brand" href="../index.php?ufname=<?php echo isset($_SESSION['username']) ?  $_SESSION['username'] : ' ' ;?>">
                    <img src='../assets/img/newlogo3.png' width='80' height='80' viewBox='0 0 24 24'%3e%3cpath fill='%23ffffff' d='M2 21V7l10-4l10 4v14h-6v-8H8v8H2Zm10 0q-.825 0-1.413-.588T10 19q0-.825.588-1.413T12 17q.825 0 1.413.588T14 19q0 .825-.588 1.413T12 22ZM6.15 6l2.4 5h7l2.75-5H6.15ZM5.2 4h14.75q.575 0 .875.513t.025 1.037l-3.55 6.4q-.275.5-.738.775T15.55 13H8.1L7 15h12v2H7q-1.125 0-1.7-.988t-.05-1.962L6.6 11.6L3 4H1V2h3.25l.95 2Zm3.35 7h7h-7Z'/%3e%3c/svg%3e" 
                        alt="Logo" class="mb-3" width="80">
                    <h4 class="text-white"><?= ORG_NAME ?></h4>
                    <hr class="bg-light mx-3">
                </a>
            </div>
            
            <ul class="nav flex-column">
                <li class="nav-item">
                    <a class="nav-link" href="#dashboard">
                        <i class="fas fa-tachometer-alt"></i> Dashboard
                    </a>
                </li>
                <?php if(isset($_SESSION['user_id'])): ?>
                <li class="nav-item">
                    <a class="nav-link" href="#pos">
                        <i class="fas fa-cash-register"></i> Point of Sale
                    </a>
                </li>
                <?php endif; ?>
                <?php if(isset($_SESSION['role']) && ($_SESSION['role'] == 'admin' || $_SESSION['role'] == 'cashier')): ?>
                <li class="nav-item">
                    <a class="nav-link" href="#products">
                        <i class="fas fa-box"></i> Products
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#inventory">
                        <i class="fas fa-warehouse"></i> Inventory
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#sales">
                        <i class="fas fa-chart-line"></i> Sales
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#reports">
                        <i class="fas fa-file-alt"></i> Reports
                    </a>
                </li>
                <?php endif; ?>
                <li class="nav-item">
                    <a class="nav-link" href="#receipt">
                        <i class="fas fa-receipt"></i> Receipts
                    </a>
                </li>
                <?php if(isset($_SESSION['role']) && $_SESSION['role'] == 'customer'): ?>
                <li class="nav-item">
                    <a class="nav-link" href="#transactions">
                        <i class="fas fa-history"></i> My Transactions
                    </a>
                </li>
                <?php endif; ?>
                <?php if(isset($_SESSION['role']) && $_SESSION['role'] == 'admin'): ?>
                <li class="nav-item">
                    <a class="nav-link" href="#settings">
                        <i class="fas fa-cog"></i> Settings
                    </a>
                </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
    
    <!-- Main Content -->
    <div class="main-content">
        <!-- Messages -->
        <?php if(isset($_SESSION['message'])): ?>
            <div class="alert alert-<?= $_SESSION['message_type'] ?> alert-dismissible fade show" role="alert">
                <?= $_SESSION['message'] ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['message']); unset($_SESSION['message_type']); ?>
        <?php endif; ?>
        
        <!-- Login/Signup Screen -->
        <?php if(!isset($_SESSION['user_id'])): ?>
            <div class="row justify-content-center mt-5">
                <div class="col-md-6 auth-container">
                    <div class="card">
                        <div class="card-header p-0">
                            <ul class="nav nav-tabs auth-tabs">
                                <li class="nav-item w-50 text-center">
                                    <a class="nav-link active" data-bs-toggle="tab" href="#login">Login</a>
                                </li>
                                <li class="nav-item w-50 text-center">
                                    <a class="nav-link" data-bs-toggle="tab" href="#signup">Sign Up</a>
                                </li>
                            </ul>
                        </div>
                        <div class="card-body p-4">
                            <div class="tab-content">
                                <!-- Login Tab -->
                                <div class="tab-pane active" id="login">
                                    <div class="text-center mb-4">
                                        <img src="data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' width='80' height='80' viewBox='0 0 24 24'%3e%3cpath fill='%233498db' d='M7 22q-.825 0-1.413-.588T5 20q0-.825.588-1.413T7 18q.825 0 1.413.588T9 20q0 .825-.588 1.413T7 22Zm10 0q-.825 0-1.413-.588T15 20q0-.825.588-1.413T17 18q.825 0 1.413.588T19 20q0 .825-.588 1.413T17 22ZM6.15 6l2.4 5h7l2.75-5H6.15ZM5.2 4h14.75q.575 0 .875.513t.025 1.037l-3.55 6.4q-.275.5-.738.775T15.55 13H8.1L7 15h12v2H7q-1.125 0-1.7-.988t-.05-1.962L6.6 11.6L3 4H1V2h3.25l.95 2Zm3.35 7h7h-7Z'/%3e%3c/svg%3e" 
                                             alt="Login" width="80">
                                        <h3 class="mb-1">Olansgee Technology</h3>
                                        <p class="text-muted">E-commerce Management System</p>
                                    </div>
                                    
                                    <form method="post">
                                        <div class="mb-3">
                                            <label for="username" class="form-label">Username or Email</label>
                                            <input type="text" class="form-control form-control-lg" id="username" name="username" required>
                                        </div>
                                        <div class="mb-3">
                                            <label for="password" class="form-label">Password</label>
                                            <input type="password" class="form-control form-control-lg" id="password" name="password" required>
                                        </div>
                                        <div class="d-grid mb-3">
                                            <button type="submit" name="login" class="btn btn-primary btn-lg">Login</button>
                                        </div>
                                        
                                        <div class="text-center mb-3">- OR -</div>
                                        
                                        <!-- Google Sign-In Button -->
                                        <div id="g_id_onload"
                                            data-client_id="<?= GOOGLE_CLIENT_ID ?>"
                                            data-login_uri="<?= BASE_URL ?>"
                                            data-auto_prompt="false">
                                        </div>
                                        <div class="g_id_signin"
                                            data-type="standard"
                                            data-size="large"
                                            data-theme="outline"
                                            data-text="sign_in_with"
                                            data-shape="rectangular"
                                            data-logo_alignment="left"
                                            data-width="100%"
                                            style="margin-bottom: 15px;">
                                        </div>
                                    </form>
                                </div>
                                
                                <!-- Signup Tab -->
                                <div class="tab-pane" id="signup">
                                    <div class="text-center mb-4">
                                        <img src="data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' width='80' height='80' viewBox='0 0 24 24'%3e%3cpath fill='%2327ae60' d='M12 14v-2h6V8l4 4l-4 4v-4h-6Zm-2 0H4v-2h6V8l-4 4l4 4v-4Z'/%3e%3c/svg%3e" 
                                             alt="Signup" width="80">
                                        <h3 class="mb-1">Create Account</h3>
                                        <p class="text-muted">Join our e-commerce system</p>
                                    </div>
                                    
                                    <form method="post">
                                        <div class="mb-3">
                                            <label for="signup_username" class="form-label">Username</label>
                                            <input type="text" class="form-control form-control-lg" id="signup_username" name="username" required>
                                        </div>
                                        <div class="mb-3">
                                            <label for="signup_email" class="form-label">Email</label>
                                            <input type="email" class="form-control form-control-lg" id="signup_email" name="email" required>
                                        </div>
                                        <div class="mb-3">
                                            <label for="signup_password" class="form-label">Password</label>
                                            <input type="password" class="form-control form-control-lg" id="signup_password" name="password" required>
                                        </div>
                                        <div class="mb-3">
                                            <label for="confirm_password" class="form-label">Confirm Password</label>
                                            <input type="password" class="form-control form-control-lg" id="confirm_password" name="confirm_password" required>
                                        </div>
                                        
                                        <!-- New customer fields -->
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label for="gender" class="form-label">Gender</label>
                                                <select class="form-select form-select-lg" id="gender" name="gender" required>
                                                    <option value="">Select Gender</option>
                                                    <option value="male">Male</option>
                                                    <option value="female">Female</option>
                                                    <option value="other">Other</option>
                                                </select>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label for="age" class="form-label">Age</label>
                                                <input type="number" class="form-control form-control-lg" id="age" name="age" min="1" required>
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <label for="address" class="form-label">Residential Address</label>
                                            <textarea class="form-control form-control-lg" id="address" name="address" rows="2" required></textarea>
                                        </div>
                                        <div class="mb-3">
                                            <label for="phone" class="form-label">Phone Number</label>
                                            <input type="tel" class="form-control form-control-lg" id="phone" name="phone" required>
                                        </div>
                                        
                                        <div class="d-grid">
                                            <button type="submit" name="signup" class="btn btn-success btn-lg">Create Account</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
   
        <!-- Dashboard -->
        <?php else: ?>
            <!-- Dashboard Section -->
            <section id="dashboard">
                        <?php 
                        $customer_id = isset($_SESSION['user_id']) && $_SESSION['role'] == 'customer' ? $_SESSION['user_id'] : null;
                        $role = $_SESSION['role'] ?? null;
                        $stats = get_dashboard_stats($customer_id, $role); 
                        ?>
                    <h2 class="page-title">Dashboard</h2>
                    
                    <?php 
                    $customer_id = isset($_SESSION['user_id']) && $_SESSION['role'] == 'customer' ? $_SESSION['user_id'] : null;
                    $role = $_SESSION['role'] ?? null;
                    $stats = get_dashboard_stats($customer_id, $role); 
                    ?>
                <div class="row">
                    <div class="col-md-3">
                        <div class="card bg-success-light stat-card">
                            <i class="fas fa-check-circle"></i>
                            <div class="number"><?= $stats['today_completed'] ?></div>
                            <div class="label">
                                <?= $customer_id ? 'My Completed' : 'Completed' ?> 
                                <?= $role == 'customer' ? 'Transactions' : 'Sales' ?>
                            </div>
                            <div class="small">₦<?= number_format($stats['today_completed_amount'], 2) ?></div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-warning-light stat-card">
                            <i class="fas fa-clock"></i>
                            <div class="number"><?= $stats['today_pending'] ?></div>
                            <div class="label">
                                <?= $customer_id ? 'My Pending' : 'Pending' ?> 
                                <?= $role == 'customer' ? 'Transactions' : 'Sales' ?>
                            </div>
                            <div class="small">₦<?= number_format($stats['today_pending_amount'], 2) ?></div>
                        </div>
                    </div>
                    <?php if($role == 'admin' || $role == 'cashier'): ?>
                    <div class="col-md-3">
                        <div class="card bg-primary-light stat-card">
                            <i class="fas fa-boxes"></i>
                            <div class="number"><?= $stats['total_products'] ?></div>
                            <div class="label">Total Products</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-danger-light stat-card">
                            <i class="fas fa-exclamation-triangle"></i>
                            <div class="number"><?= $stats['pending_payments'] ?></div>
                            <div class="label">Pending Payments</div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>

                <div class="row mt-4">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <?= $customer_id ? 'My Recent Transaction(s)' : 'Recent Sales' ?>
                    </h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Date</th>
                                    <?php if($role == 'customer'): ?>
                                        <th>Amount</th>
                                        <th>Payment Status</th>
                                        <th>Delivery Status</th>
                                        <th>View</th>
                                    <?php else: ?>
                                        <th>Customer</th>
                                        <th>Amount</th>
                                        <th>Payment Status</th>
                                        <th>Delivery Status</th>
                                    <?php endif; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $recent_sales = get_recent_sales(5, $customer_id);
                                foreach($recent_sales as $sale): 
                                    $payment_class = $sale['payment_status'] == 'completed' ? 'success' : 
                                                  ($sale['payment_status'] == 'pending' ? 'warning' : 
                                                  ($sale['payment_status'] == 'incomplete' ? 'danger' : 'secondary'));
                                    $delivery_class = $sale['delivery_status'] == 'delivered' ? 'success' : 
                                                  ($sale['delivery_status'] == 'completed' ? 'primary' : 'warning');
                                ?>
                                <tr>
                                    <td>#<?= str_pad($sale['id'], 6, '0', STR_PAD_LEFT) ?></td>
                                    <td><?= date('M j, Y h:i A', strtotime($sale['sale_date'])) ?></td>
                                    <?php if($role == 'customer'): ?>
                                        <td>₦<?= number_format($sale['total_amount'], 2) ?></td>
                                        <td>
                                            <span class="badge bg-<?= $payment_class ?>">
                                                <?= ucfirst($sale['payment_status']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?= $delivery_class ?>">
                                                <?= ucfirst($sale['delivery_status']) ?>
                                            </span>
                                        </td>
                                        <!-- View button for customer -->
                                        <td>
                                            <a href="?sale_id=<?= $sale['id'] ?>#receipt" 
                                               class="btn btn-sm btn-info">
                                               <i class="fas fa-eye"></i>
                                            </a>
                                        </td>
                                    <?php else: ?>
                                        <td><?= $sale['customer_name'] ? $sale['customer_name'] : 'Walk-in Customer' ?></td>
                                        <td>₦<?= number_format($sale['total_amount'], 2) ?></td>
                                        <td>
                                            <span class="badge bg-<?= $payment_class ?>">
                                                <?= ucfirst($sale['payment_status']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?= $delivery_class ?>">
                                                <?= ucfirst($sale['delivery_status']) ?>
                                            </span>
                                        </td>
                                    <?php endif; ?>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
                <?php if($role == 'admin'): ?>
                <!-- Admin Sales Reports -->
                <div class="row mt-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="card-title mb-0">Sales Reports</h5>
                                
                                <!-- Activity Log Toggle -->
                                <button class="btn btn-sm btn-outline-primary" id="toggle-activity-log">
                                    <i class="fas fa-history me-1"></i> View Activity Log
                                </button>
                            </div>
                            <div class="card-body">
                                <?php 
                                $period = isset($_GET['period']) ? $_GET['period'] : 'today';
                                $reports = get_sales_reports($period);
                                ?>
                                <div class="report-period-selector">
                                    <a href="?period=today" class="report-period-btn <?= $period === 'today' ? 'active' : '' ?>">Today</a>
                                    <a href="?period=week" class="report-period-btn <?= $period === 'week' ? 'active' : '' ?>">This Week</a>
                                    <a href="?period=month" class="report-period-btn <?= $period === 'month' ? 'active' : '' ?>">This Month</a>
                                    <a href="?period=year" class="report-period-btn <?= $period === 'year' ? 'active' : '' ?>">This Year</a>
                                </div>
                                
                <!-- Donut Chart -->
                <div class="chart-container">
                    <canvas id="salesStatusChart"></canvas>
                </div>
                
                <div class="table-responsive">
                    <table class="table table-hover" id="sales-report-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Transactions</th>
                                <th>Completed</th>
                                <th>Pending</th>
                                <th>Total Sales</th>
                                <th>Average Sale</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $completed_total = 0;
                            $pending_total = 0;
                            
                            foreach($reports as $report): 
                                $completed_total += $report['completed_sales'];
                                $pending_total += $report['pending_sales'];
                            ?>
                            <tr>
                                <td><?= date('M j, Y', strtotime($report['sale_day'])) ?></td>
                                <td><?= $report['transaction_count'] ?></td>
                                <td><?= $report['completed_sales'] ?></td>
                                <td><?= $report['pending_sales'] ?></td>
                                <td>₦<?= number_format($report['total_sales'], 2) ?></td>
                                <td>₦<?= $report['transaction_count'] > 0 ? number_format($report['total_sales'] / $report['transaction_count'], 2) : '0.00' ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Activity Log Section -->
                <div id="activity-log-section" style="display: none; margin-top: 30px;">
                    <h5 class="mb-3">Recent Activity Log</h5>
                    <div class="activity-log-container">
                        <?php
                        $activity_logs = get_activity_logs(10);
                        foreach ($activity_logs as $log):
                        ?>
                        <div class="activity-log-item">
                            <div class="fw-bold">
                                <?= $log['username'] ? '@'.$log['username'] : 'System' ?>
                                <span class="text-primary"><?= $log['activity'] ?></span>
                            </div>
                            <?php if ($log['details']): ?>
                            <div class="details"><?= $log['details'] ?></div>
                            <?php endif; ?>
                            <div class="time">
                                <?= date('M j, Y h:i A', strtotime($log['created_at'])) ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        
                        <?php if (empty($activity_logs)): ?>
                        <div class="alert alert-info">
                            No activity found
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
                <?php endif; ?>
                
            </section>
            
            <!-- Point of Sale Section -->
            <section id="pos" class="mt-5 pt-5">
                <h2 class="page-title">Point of Sale</h2>
                
                <div class="row">
                    <!-- Products List -->
                    <div class="col-md-8">
                        <div class="card mb-4">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="card-title mb-0">Available Products</h5>
                                <div>
                                    <input type="text" class="form-control" placeholder="Search products..." id="product-search">
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="row" id="products-container">
                                    <?php 
                                    foreach($products as $p): 
                                        $image_url = $p['image'] ? UPLOAD_DIR . $p['image'] : 'data:image/svg+xml;charset=UTF-8,%3csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'100\' height=\'100\' viewBox=\'0 0 24 24\'%3e%3cpath fill=\'%23aaa\' d=\'M7 22q-.825 0-1.413-.588T5 20q0-.825.588-1.413T7 18q.825 0 1.413.588T9 20q0 .825-.588 1.413T7 22Zm10 0q-.825 0-1.413-.588T15 20q0-.825.588-1.413T17 18q.825 0 1.413.588T19 20q0 .825-.588 1.413T17 22ZM6.15 6l2.4 5h7l2.75-5H6.15ZM5.2 4h14.75q.575 0 .875.513t.025 1.037l-3.55 6.4q-.275.5-.738.775T15.55 13H8.1L7 15h12v2H7q-1.125 0-1.7-.988t-.05-1.962L6.6 11.6L3 4H1V2h3.25l.95 2Zm3.35 7h7h-7Z\'/%3e%3c/svg%3e';
                                    ?>
                                    <div class="col-md-3 mb-3 product-card" data-id="<?= $p['id'] ?>" data-name="<?= $p['name'] ?>" data-price="<?= $p['price'] ?>" data-stock="<?= $p['stock'] ?>" data-description="<?= htmlspecialchars($p['description']) ?>" data-image="<?= $image_url ?>">
                                        <div class="card h-100 position-relative">
                                            <?php if($p['stock'] < 5): ?>
                                            <span class="badge bg-<?= $p['stock'] == 0 ? 'danger' : 'warning' ?> stock-badge">
                                                <?= $p['stock'] == 0 ? 'Out of Stock' : 'Low Stock' ?>
                                            </span>
                                            <?php endif; ?>
                                            <div class="card-body text-center">
                                                <div class="mb-3">
                                                    <img src="<?= $image_url ?>" 
                                                         alt="<?= $p['name'] ?>" 
                                                         class="product-image">
                                                </div>
                                                <h6 class="card-title"><?= $p['name'] ?></h6>
                                                <p class="card-text text-success fw-bold">₦<?= number_format($p['price'], 2) ?></p>
                                                <p class="small text-muted">Stock: <?= $p['stock'] ?></p>
                                                <button class="btn btn-sm btn-primary w-100 add-to-cart" data-id="<?= $p['id'] ?>" <?= $p['stock'] == 0 ? 'disabled' : '' ?>>
                                                    <i class="fas fa-cart-plus me-1"></i> Add to Cart
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Shopping Cart -->
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Shopping Cart</h5>
                            </div>
                            <div class="card-body">
                                <div id="cart-container">
                                    <?php if(isset($_SESSION['cart']) && count($_SESSION['cart']) > 0): ?>
                                        <?php 
                                        $total = 0;
                                        foreach($_SESSION['cart'] as $product_id => $quantity):
                                            $product = null;
                                            foreach($products as $p) {
                                                if($p['id'] == $product_id) {
                                                    $product = $p;
                                                    break;
                                                }
                                            }
                                            if(!$product) continue;
                                            $subtotal = $product['price'] * $quantity;
                                            $total += $subtotal;
                                            $image_url = $product['image'] ? UPLOAD_DIR . $product['image'] : 'data:image/svg+xml;charset=UTF-8,%3csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'60\' height=\'60\' viewBox=\'0 0 24 24\'%3e%3cpath fill=\'%23aaa\' d=\'M7 22q-.825 0-1.413-.588T5 20q0-.825.588-1.413T7 18q.825 0 1.413.588T9 20q0 .825-.588 1.413T7 22Zm10 0q-.825 0-1.413-.588T15 20q0-.825.588-1.413T17 18q.825 0 1.413.588T19 20q0 .825-.588 1.413T17 22ZM6.15 6l2.4 5h7l2.75-5H6.15ZM5.2 4h14.75q.575 0 .875.513t.025 1.037l-3.55 6.4q-.275.5-.738.775T15.55 13H8.1L7 15h12v2H7q-1.125 0-1.7-.988t-.05-1.962L6.6 11.6L3 4H1V2h3.25l.95 2Zm3.35 7h7h-7Z\'/%3e%3c/svg%3e';
                                    ?>
                                    <div class="cart-item">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div class="d-flex align-items-center">
                                                <img src="<?= $image_url ?>" 
                                                     alt="<?= $product['name'] ?>" 
                                                     class="product-image-thumb me-3">
                                                <div>
                                                    <div class="fw-bold"><?= $product['name'] ?></div>
                                                    <div>₦<?= number_format($product['price'], 2) ?> x <?= $quantity ?></div>
                                                </div>
                                            </div>
                                            <div class="text-end">
                                                <div class="fw-bold">₦<?= number_format($subtotal, 2) ?></div>
                                                <form method="post" class="d-inline">
                                                    <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                                                    <button type="submit" name="remove_from_cart" class="btn btn-sm btn-danger">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                    <div class="cart-item">
                                        <div class="d-flex justify-content-between fw-bold">
                                            <div>Total:</div>
                                            <div>₦<?= number_format($total, 2) ?></div>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <div class="text-center py-4">
                                        <i class="fas fa-shopping-cart fa-3x text-muted mb-3"></i>
                                        <p class="text-muted">Your cart is empty</p>
                                    </div>
                                <?php endif; ?>
                                </div>
                                
                                <?php if(isset($_SESSION['cart']) && count($_SESSION['cart']) > 0): ?>
                                <button class="btn btn-primary w-100 mt-3" data-bs-toggle="modal" data-bs-target="#checkoutModal">
                                    <i class="fas fa-check-circle me-1"></i> Checkout
                                </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
            
            <!-- Products Management Section (only for admin/cashier) -->
            <?php if(isset($_SESSION['role']) && ($_SESSION['role'] == 'admin' || $_SESSION['role'] == 'cashier')): ?>
            <section id="products" class="mt-5 pt-5">
                <h2 class="page-title">Products Management</h2>
                
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">Product List</h5>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addProductModal">
                            <i class="fas fa-plus me-1"></i> Add Product
                        </button>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Image</th>
                                        <th>Name</th>
                                        <th>Description</th>
                                        <th>Category</th>
                                        <th>Price</th>
                                        <th>Stock</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    foreach($products as $p): 
                                        $image_url = $p['image'] ? UPLOAD_DIR . $p['image'] : 'data:image/svg+xml;charset=UTF-8,%3csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'60\' height=\'60\' viewBox=\'0 0 24 24\'%3e%3cpath fill=\'%23aaa\' d=\'M7 22q-.825 0-1.413-.588T5 20q0-.825.588-1.413T7 18q.825 0 1.413.588T9 20q0 .825-.588 1.413T7 22Zm10 0q-.825 0-1.413-.588T15 20q0-.825.588-1.413T17 18q.825 0 1.413.588T19 20q0 .825-.588 1.413T17 22ZM6.15 6l2.4 5h7l2.75-5H6.15ZM5.2 4h14.75q.575 0 .875.513t.025 1.037l-3.55 6.4q-.275.5-.738.775T15.55 13H8.1L7 15h12v2H7q-1.125 0-1.7-.988t-.05-1.962L6.6 11.6L3 4H1V2h3.25l.95 2Zm3.35 7h7h-7Z\'/%3e%3c/svg%3e';
                                    ?>
                                    <tr>
                                        <td><?= $p['id'] ?></td>
                                        <td><img src="<?= $image_url ?>" alt="<?= $p['name'] ?>" class="product-image-thumb"></td>
                                        <td><?= $p['name'] ?></td>
                                        <td><?= substr($p['description'], 0, 50) ?>...</td>
                                        <td><?= $p['category'] ?></td>
                                        <td>₦<?= number_format($p['price'], 2) ?></td>
                                        <td><?= $p['stock'] ?></td>
                                        <td>
                                            <?php if($p['stock'] == 0): ?>
                                                <span class="badge bg-danger">Out of Stock</span>
                                            <?php elseif($p['stock'] < 5): ?>
                                                <span class="badge bg-warning">Low Stock</span>
                                            <?php else: ?>
                                                <span class="badge bg-success">In Stock</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <!-- View Button with Tooltip -->
                                            <button class="btn btn-sm btn-info view-product-details btn-tooltip" 
                                                    data-id="<?= $p['id'] ?>" 
                                                    data-name="<?= $p['name'] ?>" 
                                                    data-price="<?= $p['price'] ?>" 
                                                    data-stock="<?= $p['stock'] ?>" 
                                                    data-description="<?= htmlspecialchars($p['description']) ?>" 
                                                    data-image="<?= $image_url ?>">
                                                <i class="fas fa-eye"></i>
                                                <span class="tooltip-text">View</span>
                                            </button>
                                            
                                            <?php if($_SESSION['role'] == 'admin'): ?>
                                                <!-- Update Button with Tooltip -->
                                                <button class="btn btn-sm btn-warning update-product btn-tooltip" 
                                                        data-id="<?= $p['id'] ?>" 
                                                        data-name="<?= $p['name'] ?>" 
                                                        data-description="<?= htmlspecialchars($p['description']) ?>" 
                                                        data-price="<?= $p['price'] ?>" 
                                                        data-stock="<?= $p['stock'] ?>" 
                                                        data-category="<?= $p['category'] ?>" 
                                                        data-image="<?= $image_url ?>">
                                                    <i class="fas fa-edit"></i>
                                                    <span class="tooltip-text">Update</span>
                                                </button>
                                                
                                                <!-- Delete Button with Tooltip -->
                                                <form method="post" class="d-inline">
                                                    <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
                                                    <button type="submit" name="delete_product" class="btn btn-sm btn-danger btn-tooltip" 
                                                            onclick="return confirm('Are you sure you want to delete this product?')">
                                                        <i class="fas fa-trash"></i>
                                                        <span class="tooltip-text">Delete</span>
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </section>
            <?php endif; ?>
            
            <!-- Sales Section -->
            <?php if(isset($_SESSION['role']) && ($_SESSION['role'] == 'admin' || $_SESSION['role'] == 'cashier')): ?>
            <section id="sales" class="mt-5 pt-5">
                <h2 class="page-title">Sales Management</h2>
                
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">All Sales</h5>
                    </div>
                    <div class="card-body">
                        <?php 
                        $all_sales = get_all_sales();
                        echo '<div class="table-responsive"><table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Date</th>
                                    <th>Customer</th>
                                    <th>Amount</th>
                                    <th>Payment Status</th>
                                    <th>Delivery Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>';
                                foreach($all_sales as $sale): 
                                    $payment_class = $sale['payment_status'] == 'completed' ? 'success' : 
                                                  ($sale['payment_status'] == 'pending' ? 'warning' : 
                                                  ($sale['payment_status'] == 'incomplete' ? 'danger' : 'secondary'));
                                                  
                                    $delivery_class = $sale['delivery_status'] == 'delivered' ? 'success' : 
                                                  ($sale['delivery_status'] == 'completed' ? 'primary' : 'warning');
                                    $delivery_display = $sale['delivery_status'];
                                    if ($_SESSION['role'] == 'cashier' && $delivery_display == 'delivered') {
                                        $delivery_display = 'Completed';
                                    } else {
                                        $delivery_display = ucfirst($delivery_display);
                                    }
                                ?>
                                <tr>
                                    <td>#<?= str_pad($sale['id'], 6, '0', STR_PAD_LEFT) ?></td>
                                    <td><?= date('M j, Y h:i A', strtotime($sale['sale_date'])) ?></td>
                                    <td>
                                        <?= $sale['customer_name'] ? $sale['customer_name'] : 'Walk-in Customer' ?>
                                        <?php if($sale['customer_username']): ?>
                                            <br><small>@<?= $sale['customer_username'] ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>₦<?= number_format($sale['total_amount'], 2) ?></td>
                                    <td>
                                        <span class="badge bg-<?= $payment_class ?>">
                                            <?= ucfirst($sale['payment_status']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?= $delivery_class ?>">
                                            <?= $delivery_display ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if(($sale['payment_status'] === 'incomplete' || $sale['payment_status'] === 'pending') && ($_SESSION['role'] == 'admin' || $_SESSION['role'] == 'cashier')): ?>
                                        <form method="post" class="d-inline">
                                            <input type="hidden" name="sale_id" value="<?= $sale['id'] ?>">
                                            <button type="submit" name="confirm_payment" class="btn btn-sm btn-success" onclick="return confirm('Are you sure you want to confirm payment for this sale?')">
                                                <i class="fas fa-check-circle me-1"></i> Confirm Payment
                                            </button>
                                        </form>
                                        <?php endif; ?>
                                        
                                        <?php if($_SESSION['role'] == 'admin'): ?>
                                        <form method="post" class="d-inline">
                                            <input type="hidden" name="sale_id" value="<?= $sale['id'] ?>">
                                            <select name="delivery_status" class="form-select form-select-sm d-inline w-auto">
                                                <option value="pending" <?= $sale['delivery_status'] == 'pending' ? 'selected' : '' ?>>Pending</option>
                                                <option value="delivered" <?= $sale['delivery_status'] == 'delivered' ? 'selected' : '' ?>>Delivered</option>
                                            </select>
                                            <button type="submit" name="update_delivery_status" class="btn btn-sm btn-primary">Update</button>
                                        </form>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table></div>
                        
                    </div>
                </div>
            </section>
            <?php endif; ?>
            
            <!-- Receipt Section -->
            <section id="receipt" class="mt-5 pt-5">
                <h2 class="page-title">Receipt</h2>
                
                <?php 
                // Get sale_id from URL or session
                $sale_id_to_display = isset($_GET['sale_id']) ? $_GET['sale_id'] : 
                                     (isset($_SESSION['last_sale_id']) ? $_SESSION['last_sale_id'] : null);
                
                if($sale_id_to_display): 
                    $sale = get_sale_by_id($sale_id_to_display);
                    if ($sale): ?>
                <div class="receipt">
                <div class="receipt-header">
                        <h3><?= ORG_NAME ?></h3>
                        <p><?= ORG_ADDRESS ?></p>
                        <p>Phone: <?= ORG_PHONE ?> | Email: <?= ORG_EMAIL ?></p>
                        <h4 class="mt-3">SALES INVOICE</h4>
                    </div>
                    
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <p><strong>Invoice #:</strong> <?= str_pad($sale['id'], 6, '0', STR_PAD_LEFT) ?></p>
                            <p><strong>Date:</strong> <?= $sale['sale_date'] ?></p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Customer:</strong> <?= $sale['customer_name'] ?></p>
                            <p><strong>Email:</strong> <?= $sale['customer_email'] ?></p>
                            <p><strong>Payment Status:</strong> 
                                <?php 
                                $status = $sale['payment_status'];
                                if ($status == 'completed') {
                                    echo '<span class="badge bg-success">Completed</span>';
                                } elseif ($status == 'pending') {
                                    echo '<span class="badge bg-warning">Pending</span>';
                                } elseif ($status == 'incomplete') {
                                    echo '<span class="badge bg-danger">Incomplete</span>';
                                } else {
                                    echo ucfirst($status);
                                }
                                ?>
                            </p>
                            <p><strong>Delivery Status:</strong> 
                                <?php 
                                $status = $sale['delivery_status'];
                                if ($status == 'delivered') {
                                    echo '<span class="badge bg-success">Delivered</span>';
                                } elseif ($status == 'pending') {
                                    echo '<span class="badge bg-warning">Pending</span>';
                                } else {
                                    echo ucfirst($status);
                                }
                                ?>
                            </p>
                        </div>
                    </div>
                    
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th class="text-center">Price</th>
                                <th class="text-center">Qty</th>
                                <th class="text-end">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $items = $sale['items'] ?? [];
                            $total = 0;
                            foreach($items as $item): 
                                $subtotal = $item['price'] * $item['quantity'];
                                $total += $subtotal;
                            ?>
                            <tr>
                                <td><?= $item['product_name'] ?></td>
                                <td class="text-center">₦<?= number_format($item['price'], 2) ?></td>
                                <td class="text-center"><?= $item['quantity'] ?></td>
                                <td class="text-end">₦<?= number_format($subtotal, 2) ?></td>
                            </tr>
                            <?php endforeach; ?>
                            <tr>
                                <td colspan="3" class="text-end fw-bold">TOTAL:</td>
                                <td class="text-end fw-bold">₦<?= number_format($total, 2) ?></td>
                            </tr>
                        </tbody>
                    </table>
                    
                    <div class="receipt-footer">
                        <?php if($sale['payment_status'] == 'incomplete'): ?>
                            <p>Make Payment by transferring cash to:</p>
                            <ul style="text-align: left; margin: 10px auto; display: inline-block;">
                                <li><strong>Bank:</strong> Zenith</li>
                                <li><strong>Account Name:</strong> Olansgee Technology</li>
                                <li><strong>Account Number:</strong> 1017570496</li>
                            </ul>
                            <p class="mt-2">Send proof of payment to:</p>
                            <ul style="text-align: left; margin: 10px auto; display: inline-block;">
                                <li><strong>Phone:</strong> +2348036357536 (WhatsApp)</li>
                                <li><strong>Email:</strong> sales.olansgee@gmail.com</li>
                            </ul>
                            <p class="payment-status-incomplete">PAYMENT STATUS: INCOMPLETE - ADMIN MUST CONFIRM PAYMENT</p>
                        <?php else: ?>
                            <p>Make Payment by transferring cash to:
                            <p><strong>Bank:</strong> Zenith</p>
                            <p><strong>Account Name:</strong> Olansgee Technology</p>
                            <p><strong>Account Number:</strong> 1017570496</p>
                            <br/>
                            <p><strong>Send proof of payment to:</strong></p>
                            <p><strong>Phone:</strong> +2348036357536 (WhatsApp)</p>
                            <p> <strong>Email:</strong> sales.olansgee@gmail.com </p>
                                Thank you. </p>
                        <?php endif; ?>
                    </div>
                    
                    <div class="d-flex justify-content-center mt-4">
                        <button class="btn btn-primary me-2" id="email-receipt-btn">
                            <i class="fas fa-envelope me-1"></i> Email Receipt
                        </button>
                        <button class="btn btn-success" id="print-receipt-btn">
                            <i class="fas fa-print me-1"></i> Print Invoice
                        </button>
                    </div>
                    <div id="email-status" class="mt-2 text-center"></div>
                </div>
                <div id="email-status" class="mt-2 text-center"></div>
            </div>
            <?php else: ?>
                <div class="alert alert-info">
                    No recent sales receipt available. Complete a sale to generate a receipt.
                </div>
            <?php endif; ?>
            <?php else: ?>
                <div class="alert alert-info">
                    No recent sales receipt available. Complete a sale to generate a receipt.
                </div>
            <?php endif; ?>
        </section>

            
            <!-- Settings Section (Admin Only) -->
            <?php if(isset($_SESSION['role']) && $_SESSION['role'] == 'admin'): ?>
            <section id="settings" class="mt-5 pt-5" style="position: relative; z-index: 1;">
                <h2 class="page-title">System Settings</h2>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Create Cashier Account</h5>
                            </div>
                            <div class="card-body">
                                <form method="post">
                                    <div class="mb-3">
                                        <label for="cashier_username" class="form-label">Username</label>
                                        <input type="text" class="form-control" id="cashier_username" name="username" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="cashier_email" class="form-label">Email</label>
                                        <input type="email" class="form-control" id="cashier_email" name="email" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="cashier_password" class="form-label">Password</label>
                                        <input type="password" class="form-control" id="cashier_password" name="password" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="cashier_confirm_password" class="form-label">Confirm Password</label>
                                        <input type="password" class="form-control" id="cashier_confirm_password" name="confirm_password" required>
                                    </div>
                                    <div class="d-grid">
                                        <button type="submit" name="create_cashier" class="btn btn-primary">
                                            <i class="fas fa-user-plus me-1"></i> Create Cashier Account
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">System Information</h5>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <strong>Organization:</strong> <?= ORG_NAME ?>
                                </div>
                                <div class="mb-3">
                                    <strong>Address:</strong> <?= ORG_ADDRESS ?>
                                </div>
                                <div class="mb-3">
                                    <strong>Contact:</strong> <?= ORG_PHONE ?> | <?= ORG_EMAIL ?>
                                </div>
                                <div class="mb-3">
                                    <strong>System Version:</strong> 1.5.0
                                </div>
                                <div class="mb-3">
                                    <strong>Last Updated:</strong> <?= date('F j, Y') ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- User Management Section -->
                <div class="row">
                    <div class="col-12">
                        <div class="card mt-4">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="card-title mb-0">User Management</h5>
                                <button class="btn btn-sm btn-outline-primary" id="toggle-users-table">
                                    <i class="fas fa-table me-1"></i> Show Users
                                </button>
                            </div>
                            <div class="card-body" id="users-table-section" style="display: none;">
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>ID</th>
                                                <th>Username</th>
                                                <th>Email</th>
                                                <th>Role</th>
                                                <th>Verified</th>
                                                <th>Created At</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php 
                                            $users = get_all_users();
                                            foreach($users as $user): 
                                            ?>
                                            <tr>
                                                <td><?= $user['id'] ?></td>
                                                <td><?= $user['username'] ?></td>
                                                <td><?= $user['email'] ?></td>
                                                <td><?= $user['role'] ?></td>
                                                <td>
                                                    <?php if($user['email_verified']): ?>
                                                        <span class="badge bg-success">Yes</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-warning">No</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?= date('M j, Y', strtotime($user['created_at'])) ?></td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
         <?php endif; ?>
        <?php endif; ?>
    </div>
    
    <!-- Footer -->
       

    <footer class="footer" style="position: relative; z-index: 1;">
        <div class="container">
            <div class="row">
                <div class="col-md-12 text-md-start">

                    <?php 
                    require_once '../contact.php';
                    require_once '../footer.php';
                    ?>
                   
                </div>
            </div>
        </div>
    </footer>
    
    <!-- Modals -->
    <!-- Add Product Modal -->
    <div class="modal fade" id="addProductModal" tabindex="-1" aria-labelledby="addProductModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addProductModalLabel">Add New Product</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="post" enctype="multipart/form-data">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="name" class="form-label">Product Name</label>
                            <input type="text" class="form-control" id="name" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="price" class="form-label">Price (₦)</label>
                                <input type="number" class="form-control" id="price" name="price" step="0.01" min="0" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="stock" class="form-label">Initial Stock</label>
                                <input type="number" class="form-control" id="stock" name="stock" min="0" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="category" class="form-label">Category</label>
                            <input type="text" class="form-control" id="category" name="category">
                        </div>
                        <div class="mb-3">
                            <label for="image" class="form-label">Product Image</label>
                            <input type="file" class="form-control" id="image" name="image" accept="image/*">
                            <div id="image-preview" class="mt-2 text-center"></div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="add_product" class="btn btn-primary">Add Product</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Update Product Modal -->
    <div class="modal fade" id="updateProductModal" tabindex="-1" aria-labelledby="updateProductModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="updateProductModalLabel">Update Product</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="post" enctype="multipart/form-data">
                    <input type="hidden" name="product_id" id="update-product-id">
                    <input type="hidden" name="current_image" id="current-image">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="update-name" class="form-label">Product Name</label>
                            <input type="text" class="form-control" id="update-name" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label for="update-description" class="form-label">Description</label>
                            <textarea class="form-control" id="update-description" name="description" rows="3"></textarea>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="update-price" class="form-label">Price (₦)</label>
                                <input type="number" class="form-control" id="update-price" name="price" step="0.01" min="0" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="update-stock" class="form-label">Stock</label>
                                <input type="number" class="form-control" id="update-stock" name="stock" min="0" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="update-category" class="form-label">Category</label>
                            <input type="text" class="form-control" id="update-category" name="category">
                        </div>
                        <div class="mb-3">
                            <label for="update-image" class="form-label">Product Image</label>
                            <input type="file" class="form-control" id="update-image" name="image" accept="image/*">
                            <div id="update-image-preview" class="mt-2 text-center"></div>
                            <small class="text-muted">Leave blank to keep current image</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="update_product" class="btn btn-primary">Update Product</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Update Stock Modal -->
    <div class="modal fade" id="updateStockModal" tabindex="-1" aria-labelledby="updateStockModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="updateStockModalLabel">Update Product Stock</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="post">
                    <input type="hidden" name="product_id" id="update_product_id">
                    <div class="modal-body">
                        <p>Product: <strong id="update_product_name"></strong></p>
                        <div class="mb-3">
                            <label for="quantity" class="form-label">Quantity to Add (use negative to remove)</label>
                            <input type="number" class="form-control" id="quantity" name="quantity" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="update_stock" class="btn btn-primary">Update Stock</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Checkout Modal -->
    <div class="modal fade" id="checkoutModal" tabindex="-1" aria-labelledby="checkoutModalLabel" aria-hidden="true" style="z-index: 1051;">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="checkoutModalLabel">Complete Purchase</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="checkoutForm" method="post">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="customer_name" class="form-label">Customer Name</label>
                            <input type="text" class="form-control" id="customer_name" name="customer_name" 
                                value="<?= isset($_SESSION['username']) ? $_SESSION['username'] : '' ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="customer_email" class="form-label">Customer Email</label>
                            <input type="email" class="form-control" id="customer_email" name="customer_email"
                                value="<?= isset($_SESSION['email']) ? $_SESSION['email'] : '' ?>" required>
                        </div>
                        
                        <?php 
                        $total = 0;
                        if(isset($_SESSION['cart'])) {
                            foreach($_SESSION['cart'] as $product_id => $quantity) {
                                foreach($products as $p) {
                                    if($p['id'] == $product_id) {
                                        $total += $p['price'] * $quantity;
                                        break;
                                    }
                                }
                            }
                        }
                        ?>
                        
                        <div class="alert alert-info">
                            <strong>Total Amount:</strong> ₦<?= number_format($total, 2) ?>
                        </div>
                        
                        <!-- Cash Payment Section -->
                        <div id="cashPaymentSection">
                        <div class="payment-info">
                            <p>Make Payment by transferring cash to:</p>
                            <ul>
                                <li><strong>Bank:</strong> Zenith</li>
                                <li><strong>Account Name:</strong> Olansgee Technology</li>
                                <li><strong>Account Number:</strong> 1017570496</li>
                            </ul>
                            <p class="mt-2">Send proof of payment to:</p>
                            <ul>
                                <li><strong>Phone:</strong> +2348036357536 (WhatsApp)</li>
                                <li><strong>Email:</strong> sales.olansgee@gmail.com</li>
                            </ul>
                        </div>
                            <button type="submit" name="complete_cash_payment" class="btn btn-primary w-100 mt-3">
                                <i class="fas fa-check-circle me-1"></i> Complete Order
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Product Details Modal -->
    <div class="modal fade" id="productDetailsModal" tabindex="-1" aria-labelledby="productDetailsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="productDetailsModalLabel">Product Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <img id="detail-product-image" src="" alt="Product Image" class="product-detail-image">
                        </div>
                        <div class="col-md-6">
                            <h2 id="detail-product-name"></h2>
                            <div class="d-flex align-items-center mb-3">
                                <span class="text-success fw-bold fs-4" id="detail-product-price"></span>
                                <span class="badge bg-primary ms-3" id="detail-product-category"></span>
                            </div>
                            
                            <div class="mb-4">
                                <span class="fw-bold">Availability:</span>
                                <span class="badge" id="detail-product-stock"></span>
                            </div>
                            
                            <div class="product-description" id="detail-product-description"></div>
                            
                            <div class="feature-section mt-4">
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="product-feature">
                                            <i class="fas fa-shipping-fast"></i>
                                            <h5>Free Shipping</h5>
                                            <p class="small">On orders over ₦50,000</p>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="product-feature">
                                            <i class="fas fa-sync-alt"></i>
                                            <h5>30-Day Returns</h5>
                                            <p class="small">No questions asked</p>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="product-feature">
                                            <i class="fas fa-shield-alt"></i>
                                            <h5>1-Year Warranty</h5>
                                            <p class="small">Manufacturer guarantee</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="social-sharing">
                                <a href="#" class="social-btn facebook" id="share-facebook">
                                    <i class="fab fa-facebook-f"></i>
                                </a>
                                <a href="#" class="social-btn twitter" id="share-twitter">
                                    <i class="fab fa-twitter"></i>
                                </a>
                                <a href="#" class="social-btn whatsapp" id="share-whatsapp">
                                    <i class="fab fa-whatsapp"></i>
                                </a>
                                <a href="#" class="social-btn linkedin" id="share-linkedin">
                                    <i class="fab fa-linkedin-in"></i>
                                </a>
                                <a href="#" class="social-btn instagram" id="share-instagram">
                                    <i class="fab fa-instagram"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
    
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    
        // Update time every second
        function updateTime() {
            const now = new Date();
            const timeString = now.toLocaleTimeString('en-US', {
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit'
            });
            document.getElementById('current-time').textContent = timeString;
        }
        setInterval(updateTime, 1000);
        updateTime();
        
        // Update Stock Modal Handler
        const updateStockModal = document.getElementById('updateStockModal');
        if (updateStockModal) {
            updateStockModal.addEventListener('show.bs.modal', function(event) {
                const button = event.relatedTarget;
                const productId = button.getAttribute('data-id');
                const productName = button.getAttribute('data-name');
                
                document.getElementById('update_product_id').value = productId;
                document.getElementById('update_product_name').textContent = productName;
            });
        }
        
        // Product Search
        document.getElementById('product-search').addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            const products = document.querySelectorAll('.product-card');
            
            products.forEach(product => {
                const name = product.getAttribute('data-name').toLowerCase();
                if (name.includes(searchTerm)) {
                    product.style.display = 'block';
                } else {
                    product.style.display = 'none';
                }
            });
        });
        
        // Add to Cart Buttons
        document.querySelectorAll('.add-to-cart').forEach(button => {
            button.addEventListener('click', function() {
                const productId = this.getAttribute('data-id');
                const quantity = prompt('Enter quantity:', '1');
                
                if (quantity && parseInt(quantity) > 0) {
                    const form = document.createElement('form');
                    form.method = 'post';
                    form.style.display = 'none';
                    
                    const productInput = document.createElement('input');
                    productInput.type = 'hidden';
                    productInput.name = 'product_id';
                    productInput.value = productId;
                    
                    const quantityInput = document.createElement('input');
                    quantityInput.type = 'hidden';
                    quantityInput.name = 'quantity';
                    quantityInput.value = quantity;
                    
                    const submitInput = document.createElement('input');
                    submitInput.type = 'hidden';
                    submitInput.name = 'add_to_cart';
                    submitInput.value = '1';
                    
                    form.appendChild(productInput);
                    form.appendChild(quantityInput);
                    form.appendChild(submitInput);
                    document.body.appendChild(form);
                    form.submit();
                }
            });
        });
        
        // Image preview for product upload
        document.getElementById('image').addEventListener('change', function(e) {
            const preview = document.getElementById('image-preview');
            preview.innerHTML = '';
            
            if (this.files && this.files[0]) {
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    const img = document.createElement('img');
                    img.src = e.target.result;
                    img.className = 'upload-preview';
                    img.style.maxWidth = '100%';
                    preview.appendChild(img);
                }
                
                reader.readAsDataURL(this.files[0]);
            }
        });
        
        // Product details modal
        const productDetailsModal = new bootstrap.Modal(document.getElementById('productDetailsModal'));
        
        // View product details buttons
        document.querySelectorAll('.view-product-details').forEach(button => {
            button.addEventListener('click', function() {
                const productId = this.getAttribute('data-id');
                const productName = this.getAttribute('data-name');
                const productPrice = this.getAttribute('data-price');
                const productStock = this.getAttribute('data-stock');
                const productDescription = this.getAttribute('data-description');
                const productImage = this.getAttribute('data-image');
                const productCategory = this.closest('.product-card') ? 
                    this.closest('.product-card').querySelector('.badge').textContent : 
                    'In Stock';
                
                // Populate modal with product data
                document.getElementById('detail-product-name').textContent = productName;
                document.getElementById('detail-product-price').textContent = '₦' + parseFloat(productPrice).toLocaleString('en', {minimumFractionDigits: 2});
                document.getElementById('detail-product-description').textContent = productDescription;
                document.getElementById('detail-product-image').src = productImage;
                document.getElementById('detail-product-category').textContent = productCategory;
                
                // Set stock status
                const stockBadge = document.getElementById('detail-product-stock');
                stockBadge.textContent = productStock;
                if (productStock == 0) {
                    stockBadge.className = 'badge bg-danger';
                    stockBadge.textContent = 'Out of Stock';
                } else if (productStock < 5) {
                    stockBadge.className = 'badge bg-warning';
                    stockBadge.textContent = productStock + ' left (Low Stock)';
                } else {
                    stockBadge.className = 'badge bg-success';
                    stockBadge.textContent = productStock + ' in Stock';
                }
                
                // Set up social sharing
                const shareUrl = '<?= BASE_URL ?>?action=product&id=' + productId;
                const shareText = encodeURIComponent('Check out this product: ' + productName);
                
                document.getElementById('share-facebook').href = 
                    'https://www.facebook.com/sharer/sharer.php?u=' + shareUrl;
                document.getElementById('share-twitter').href = 
                    'https://twitter.com/intent/tweet?text=' + shareText + '&url=' + shareUrl;
                document.getElementById('share-whatsapp').href = 
                    'https://api.whatsapp.com/send?text=' + shareText + '%20' + shareUrl;
                document.getElementById('share-linkedin').href = 
                    'https://www.linkedin.com/shareArticle?mini=true&url=' + shareUrl + '&title=' + encodeURIComponent(productName);
                document.getElementById('share-instagram').href = 
                    'https://www.instagram.com/?url=' + shareUrl;
                
                // Show the modal
                productDetailsModal.show();
            });
        });
        
        // Update product modal handler
        document.querySelectorAll('.update-product').forEach(button => {
            button.addEventListener('click', function() {
                const productId = this.getAttribute('data-id');
                const productName = this.getAttribute('data-name');
                const productDescription = this.getAttribute('data-description');
                const productPrice = this.getAttribute('data-price');
                const productStock = this.getAttribute('data-stock');
                const productCategory = this.getAttribute('data-category');
                const productImage = this.getAttribute('data-image');
                
                document.getElementById('update-product-id').value = productId;
                document.getElementById('update-name').value = productName;
                document.getElementById('update-description').value = productDescription;
                document.getElementById('update-price').value = productPrice;
                document.getElementById('update-stock').value = productStock;
                document.getElementById('update-category').value = productCategory;
                document.getElementById('current-image').value = productImage.split('/').pop();
                
                // Image preview
                const preview = document.getElementById('update-image-preview');
                preview.innerHTML = '';
                if (productImage) {
                    const img = document.createElement('img');
                    img.src = productImage;
                    img.className = 'upload-preview';
                    img.style.maxWidth = '100%';
                    preview.appendChild(img);
                }
                
                const updateModal = new bootstrap.Modal(document.getElementById('updateProductModal'));
                updateModal.show();
            });
        });
        
        // Toggle activity log
        document.getElementById('toggle-activity-log').addEventListener('click', function() {
            const section = document.getElementById('activity-log-section');
            section.style.display = section.style.display === 'none' ? 'block' : 'none';
        });
        
        // Toggle users table
        document.getElementById('toggle-users-table').addEventListener('click', function() {
            const section = document.getElementById('users-table-section');
            section.style.display = section.style.display === 'none' ? 'block' : 'none';
        });
        
        // Print receipt button
        document.getElementById('print-receipt-btn').addEventListener('click', function() {
            window.print();
        });
        
        // Email receipt button
        document.getElementById('email-receipt-btn').addEventListener('click', function() {
            const btn = this;
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending...';
            
            fetch('index.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'send_receipt_email=1'
            })
            .then(response => response.json())
            .then(data => {
                const statusDiv = document.getElementById('email-status');
                if (data.status === 'success') {
                    statusDiv.innerHTML = '<div class="alert alert-success">Receipt emailed successfully!</div>';
                } else {
                    statusDiv.innerHTML = '<div class="alert alert-danger">Failed to send email: ' + (data.message || 'Unknown error') + '</div>';
                }
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-envelope me-1"></i> Email Receipt';
            })
            .catch(error => {
                const statusDiv = document.getElementById('email-status');
                statusDiv.innerHTML = '<div class="alert alert-danger">Error: ' + error.message + '</div>';
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-envelope me-1"></i> Email Receipt';
            });
        });
        
        // Sales chart
        <?php if(isset($_SESSION['role']) && $_SESSION['role'] == 'admin'): ?>
        const ctx = document.getElementById('salesStatusChart').getContext('2d');
        const chart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Completed Sales', 'Pending Sales'],
                datasets: [{
                    data: [<?= $completed_total ?>, <?= $pending_total ?>],
                    backgroundColor: [
                        'rgba(39, 174, 96, 0.8)',
                        'rgba(243, 156, 18, 0.8)'
                    ],
                    borderColor: [
                        'rgba(39, 174, 96, 1)',
                        'rgba(243, 156, 18, 1)'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    title: {
                        display: true,
                        text: 'Sales Status Distribution'
                    }
                }
            }
        });
        <?php endif; ?>
    </script>
</body>
</html>