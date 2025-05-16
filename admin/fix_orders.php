<?php
require_once '../includes/db.php';
$conn = get_db();

// تحديث الحالات الفارغة
$status_sql = "UPDATE orders SET status = 'pending' WHERE status IS NULL OR status = ''";
$conn->query($status_sql);
$status_affected = $conn->affected_rows;

// تحديث المبالغ الفارغة
$amount_sql = "UPDATE orders SET total = 0 WHERE total IS NULL OR total = ''";
$conn->query($amount_sql);
$amount_affected = $conn->affected_rows;

// ملخص
?>
<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <title>تحديث الطلبات</title>
    <style>body{font-family:Cairo,sans-serif;background:#222;color:#fff;padding:40px;text-align:center}</style>
</head>
<body>
    <h1>تم تحديث الطلبات بنجاح</h1>
    <p>عدد الطلبات التي تم تحديث حالتها: <b><?php echo $status_affected; ?></b></p>
    <p>عدد الطلبات التي تم تحديث مبلغها: <b><?php echo $amount_affected; ?></b></p>
    <a href="orders.php" style="color:#ffd700;">العودة لإدارة الطلبات</a>
</body>
</html> 