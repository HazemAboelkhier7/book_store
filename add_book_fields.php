<?php
// ملف لإضافة حقول جديدة لجدول الكتب

// تضمين اتصال قاعدة البيانات
require_once 'includes/db.php';

// الاتصال بقاعدة البيانات
$conn = get_db();

// إضافة حقل سنة النشر وعدد الصفحات للجدول
$alter_query = "ALTER TABLE books 
                ADD COLUMN publication_year INT NULL,
                ADD COLUMN pages INT NULL";

// تنفيذ الاستعلام
if ($conn->query($alter_query) === TRUE) {
    echo "تم إضافة الحقول بنجاح";
} else {
    echo "خطأ في إضافة الحقول: " . $conn->error;
}

// إغلاق الاتصال
$conn->close();
?> 