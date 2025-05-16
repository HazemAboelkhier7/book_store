<?php
// يجب ضبط إعدادات الجلسة قبل أي مخرجات أو استدعاء للجلسة
// ini_set('session.cookie_httponly', 1);
// ini_set('session.use_only_cookies', 1);
// ini_set('session.cookie_path', '/dashboard/book_store');

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', dirname(__DIR__) . DIRECTORY_SEPARATOR . 'logs' . DIRECTORY_SEPARATOR . 'error.log');

// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'book_store');

// Path configuration for XAMPP
define('BASE_URL', '/dashboard/book_store');
define('BASE_PATH', dirname(__DIR__) . DIRECTORY_SEPARATOR);
define('INCLUDES_PATH', BASE_PATH . 'includes' . DIRECTORY_SEPARATOR);
define('UPLOAD_PATH', BASE_PATH . 'uploads' . DIRECTORY_SEPARATOR);
define('UPLOAD_URL', BASE_URL . '/uploads/');

// Site configuration
define('SITE_URL', 'http://localhost/dashboard/book_store');
define('SITE_NAME', 'متجر الكتب');

// Theme configuration
define('PRIMARY_COLOR', '#ffd700'); // Gold
define('SECONDARY_COLOR', '#c4a300'); // Darker Gold
define('DARK_COLOR', '#1a1a1a'); // Dark Gray
define('DARKER_COLOR', '#0f0f0f'); // Darker Gray
define('LIGHT_COLOR', '#ffffff'); // White

// Security configuration
define('SESSION_LIFETIME', 1800); // 30 minutes
define('MAX_LOGIN_ATTEMPTS', 3);
define('LOGIN_TIMEOUT', 300); // 5 minutes
define('PASSWORD_MIN_LENGTH', 8);
define('CSRF_TOKEN_NAME', 'csrf_token');

// File upload configuration
define('MAX_FILE_SIZE', 2 * 1024 * 1024); // 2MB
define('ALLOWED_FILE_TYPES', ['image/jpeg', 'image/png']);
define('MAX_IMAGE_WIDTH', 1920);
define('MAX_IMAGE_HEIGHT', 1080);

// Pagination configuration
define('ITEMS_PER_PAGE', 12);
define('PAGINATION_RANGE', 2);

// Time zone
date_default_timezone_set('Asia/Riyadh');

// Character encoding
mb_internal_encoding('UTF-8');

// Shopping cart settings
define('SHIPPING_COST', 20);
define('TAX_RATE', 0.15); // 15% VAT

// Order status
define('ORDER_STATUS', [
    'pending' => 'قيد المراجعة',
    'processing' => 'جاري التجهيز',
    'shipped' => 'تم الشحن',
    'delivered' => 'تم التوصيل',
    'cancelled' => 'ملغي'
]);

// Required functions
require_once INCLUDES_PATH . 'functions.php';

// Initialize database connection
$conn = get_db();

// Get site settings
$query = "SELECT * FROM settings LIMIT 1";
$result = $conn->query($query);
$settings = $result->fetch_assoc();

// Set site name from database
if ($settings && !empty($settings['site_name'])) {
    define('SITE_NAME_DB', $settings['site_name']);
} else {
    define('SITE_NAME_DB', SITE_NAME);
}

// Set site logo from database
if ($settings && !empty($settings['site_logo'])) {
    define('SITE_LOGO', UPLOAD_URL . $settings['site_logo']);
} else {
    define('SITE_LOGO', '');
}

// Create required directories if they don't exist
$required_dirs = [
    BASE_PATH . 'logs',
    UPLOAD_PATH
];

foreach ($required_dirs as $dir) {
    if (!file_exists($dir)) {
        mkdir($dir, 0777, true);
    }
}

// Generate CSRF token if not exists
if (!isset($_SESSION[CSRF_TOKEN_NAME])) {
    $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
}

// Set headers for security
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: same-origin');
if (isset($_SERVER['HTTPS'])) {
    header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload');
}

// Function to generate CSRF token
function generate_csrf_token() {
    if (empty($_SESSION[CSRF_TOKEN_NAME])) {
        $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
    }
    return $_SESSION[CSRF_TOKEN_NAME];
}

// Function to verify CSRF token
function verify_csrf_token($token) {
    return !empty($_SESSION[CSRF_TOKEN_NAME]) && hash_equals($_SESSION[CSRF_TOKEN_NAME], $token);
} 