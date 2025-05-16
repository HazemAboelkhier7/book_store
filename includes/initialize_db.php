<?php
// Script to properly initialize the database with correct character encoding
// This will fix the issue with question marks (????????) appearing instead of Arabic text

// Create connection
$conn = mysqli_connect('localhost', 'root', '');

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Set the character set for the connection
mysqli_set_charset($conn, 'utf8mb4');

// Check if database exists, if not create it
$sql = "CREATE DATABASE IF NOT EXISTS `book_store` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
if (mysqli_query($conn, $sql)) {
    echo "Database created or already exists with proper encoding<br>";
} else {
    echo "Error creating database: " . mysqli_error($conn) . "<br>";
}

// Select the database
mysqli_select_db($conn, 'book_store');

// Import the SQL schema if needed
$sql_file = file_get_contents(__DIR__ . '/../book_store.sql');
if (!empty($sql_file)) {
    // Split SQL file into individual queries
    $queries = explode(';', $sql_file);
    foreach ($queries as $query) {
        $query = trim($query);
        if (!empty($query)) {
            if (mysqli_query($conn, $query)) {
                // Query executed successfully
            } else {
                echo "Error executing query: " . mysqli_error($conn) . "<br>";
            }
        }
    }
}

// Close connection
mysqli_close($conn);
?> 