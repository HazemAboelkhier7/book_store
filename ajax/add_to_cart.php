<?php
ob_end_clean();
ob_start();
header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once '../includes/db.php';
require_once '../includes/functions.php';

session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['book_id'])) {
    $book_id = (int)$_POST['book_id'];
    $success = add_to_cart($book_id, 1);
    if ($success) {
        $cart_count = get_cart_count();
        ob_end_clean();
        echo json_encode([
            'success' => true,
            'cart_count' => $cart_count,
            'message' => 'تم إضافة الكتاب إلى السلة بنجاح'
        ]);
        exit;
    } else {
        ob_end_clean();
        echo json_encode([
            'success' => false,
            'message' => 'عذراً، حدث خطأ أو أن الكتاب غير متوفر في المخزون'
        ]);
        exit;
    }
}

ob_end_clean();
echo json_encode([
    'success' => false,
    'message' => 'طلب غير صالح'
]);
exit; 