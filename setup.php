<?php
/**
 * Database Setup Script
 * 
 * This script initializes the database schema and inserts sample data.
 * 
 * IMPORTANT: 
 * - Update database credentials in includes/config.php before running
 * - Change the default admin password immediately after setup
 * - Delete or restrict access to this file after initial setup
 */

// Force UTF-8 encoding
header('Content-Type: text/html; charset=utf-8');
ini_set('default_charset', 'UTF-8');
mb_internal_encoding('UTF-8');
mb_http_output('UTF-8');

// Load configuration
require_once 'includes/config.php';

// Database connection using config constants
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Create database
$conn->query("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
$conn->select_db(DB_NAME);

// Create settings table
$conn->query("
CREATE TABLE IF NOT EXISTS settings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    site_name VARCHAR(255) NOT NULL,
    site_description TEXT,
    site_keywords TEXT,
    site_email VARCHAR(255),
    site_phone VARCHAR(20),
    site_address TEXT,
    site_logo VARCHAR(255),
    facebook_url VARCHAR(255),
    twitter_url VARCHAR(255),
    instagram_url VARCHAR(255),
    shipping_cost DECIMAL(10,2) DEFAULT 30.00,
    tax_rate DECIMAL(5,2) DEFAULT 15.00,
    currency_symbol VARCHAR(10) DEFAULT 'ر.س',
    items_per_page INT DEFAULT 12,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)");

// Create categories table
$conn->query("
CREATE TABLE IF NOT EXISTS categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)");

// Create authors table
$conn->query("
CREATE TABLE IF NOT EXISTS authors (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    bio TEXT,
    image VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)");

// Create books table
$conn->query("
CREATE TABLE IF NOT EXISTS books (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    isbn VARCHAR(13),
    publisher VARCHAR(255),
    publish_date DATE,
    price DECIMAL(10,2) NOT NULL,
    stock INT NOT NULL DEFAULT 0,
    cover_image VARCHAR(255),
    category_id INT,
    author_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL,
    FOREIGN KEY (author_id) REFERENCES authors(id) ON DELETE SET NULL
)");

// Create orders table
$conn->query("
CREATE TABLE IF NOT EXISTS orders (
    id INT PRIMARY KEY AUTO_INCREMENT,
    customer_name VARCHAR(255) NOT NULL,
    customer_email VARCHAR(255) NOT NULL,
    customer_phone VARCHAR(20) NOT NULL,
    customer_address TEXT NOT NULL,
    subtotal DECIMAL(10,2) NOT NULL,
    tax DECIMAL(10,2) NOT NULL,
    shipping DECIMAL(10,2) NOT NULL,
    total DECIMAL(10,2) NOT NULL,
    status ENUM('pending', 'processing', 'shipped', 'delivered', 'cancelled') NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)");

// Create order items table
$conn->query("
CREATE TABLE IF NOT EXISTS order_items (
    id INT PRIMARY KEY AUTO_INCREMENT,
    order_id INT NOT NULL,
    book_id INT NOT NULL,
    quantity INT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (book_id) REFERENCES books(id) ON DELETE RESTRICT
)");

// Create admins table
$conn->query("
CREATE TABLE IF NOT EXISTS admins (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    last_login TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)");

// Insert default settings
$conn->query("
INSERT INTO settings (
    site_name, site_description, site_keywords, site_email, 
    site_phone, site_address, shipping_cost, tax_rate, currency_symbol, items_per_page
) VALUES (
    'متجر الكتب', 'متجر الكتب العربية الأول',
    'كتب، روايات، أدب عربي، كتب إلكترونية',
    'info@example.com', '+1234567890', 'عنوان المتجر',
    30.00, 15.00, 'ر.س', 12
)");

// Insert sample categories
$conn->query("
INSERT INTO categories (name) VALUES 
('روايات'), ('كتب دينية'), ('كتب تاريخية'), ('كتب علمية'),
('كتب أطفال'), ('كتب تنمية بشرية'), ('شعر'), ('سير ذاتية')
");

// Insert sample authors
$conn->query("
INSERT INTO authors (name) VALUES 
('نجيب محفوظ'), ('أحمد خالد توفيق'), ('غسان كنفاني'), ('جبران خليل جبران'),
('طه حسين'), ('يوسف زيدان'), ('أحلام مستغانمي'), ('واسيني الأعرج')
");

// Insert default admin - CHANGE THIS PASSWORD IMMEDIATELY AFTER SETUP
$admin_password = password_hash(bin2hex(random_bytes(8)), PASSWORD_DEFAULT);
$stmt = $conn->prepare("INSERT INTO admins (username, password, name, email) VALUES (?, ?, 'مدير النظام', 'admin@example.com')");
$default_user = 'admin';
$stmt->bind_param("ss", $default_user, $admin_password);
$stmt->execute();

// Create required directories
$dirs = ['uploads', 'logs'];
foreach ($dirs as $dir) {
    if (!file_exists($dir)) {
        mkdir($dir, 0755, true);
    }
}

echo "تم إعداد قاعدة البيانات بنجاح!<br>";
echo "⚠️ يرجى تعيين كلمة مرور جديدة للأدمن من خلال قاعدة البيانات<br>";
echo '<a href="index.php">العودة إلى الصفحة الرئيسية</a>';
echo "<br><br><strong>⚠️ تحذير: احذف هذا الملف بعد الإعداد الأولي لأسباب أمنية!</strong>";
