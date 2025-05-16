<?php
require_once 'includes/db.php';
require_once 'includes/config.php';

try {
    $conn = get_db();
    echo "Database connection successful!\n";
    
    // Check tables
    $tables = ['settings', 'categories', 'authors', 'books', 'orders', 'order_items', 'admins'];
    foreach ($tables as $table) {
        $result = $conn->query("SHOW TABLES LIKE '$table'");
        if ($result && $result->num_rows > 0) {
            echo "Table '$table' exists.\n";
        } else {
            echo "Table '$table' does not exist!\n";
        }
    }
    
    // Check admin user
    $result = $conn->query("SELECT * FROM admins WHERE username = 'admin'");
    if ($result && $result->num_rows > 0) {
        echo "Admin user exists.\n";
    } else {
        echo "Admin user does not exist!\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
} 