<?php
// Force UTF-8 encoding for Arabic text
ini_set('default_charset', 'UTF-8');
mb_internal_encoding('UTF-8');
mb_http_output('UTF-8');
session_start();

require_once '../includes/config.php';
require_once '../includes/auth.php';

// Unset all session variables
$_SESSION = [];

// Destroy the session
session_destroy();

// Logout user
get_auth()->logout();

// Redirect to login page
header('Location: index.php');
exit; 