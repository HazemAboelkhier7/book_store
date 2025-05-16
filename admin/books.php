<?php
// Force UTF-8 encoding for Arabic text
ini_set('default_charset', 'UTF-8');
mb_internal_encoding('UTF-8');
mb_http_output('UTF-8');
require_once 'header.php';
require_once '../includes/db.php';

$conn = get_db();
$message = '';
$error = '';

// Handle delete action
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $book_id = (int)$_GET['delete'];
    
    // Check if book has any order items
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM order_items WHERE book_id = ?");
    $stmt->bind_param("i", $book_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $order_count = $result->fetch_assoc()['count'];
    
    if ($order_count > 0) {
        $_SESSION['admin_message'] = 'لا يمكن حذف هذه الرواية لأنها مرتبطة بطلبات سابقة';
        $_SESSION['admin_message_type'] = 'danger';
        header('Location: books.php');
        exit;
    }
    
    // Get the book cover image before deletion
    $stmt = $conn->prepare("SELECT cover_image FROM books WHERE id = ?");
    $stmt->bind_param("i", $book_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result && $result->num_rows > 0) {
        $book = $result->fetch_assoc();
        $cover_image = $book['cover_image'];
        
        // Delete the book
        $stmt = $conn->prepare("DELETE FROM books WHERE id = ?");
        $stmt->bind_param("i", $book_id);
        
        if ($stmt->execute()) {
            // Delete the cover image if exists
            if (!empty($cover_image) && file_exists(UPLOAD_PATH . $cover_image)) {
                unlink(UPLOAD_PATH . $cover_image);
            }
            
            $_SESSION['admin_message'] = 'تم حذف الرواية بنجاح';
            $_SESSION['admin_message_type'] = 'success';
        } else {
            $_SESSION['admin_message'] = 'فشل في حذف الرواية';
            $_SESSION['admin_message_type'] = 'danger';
        }
        
        // Redirect to avoid re-deletion on refresh
        header('Location: books.php');
        exit;
    }
}

// Get all books
$result = $conn->query("SELECT b.*, a.name AS author_name FROM books b LEFT JOIN authors a ON b.author_id = a.id ORDER BY b.created_at DESC");
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1>إدارة الروايات</h1>
    <a href="book-edit.php" class="btn btn-primary">
        <i class="fas fa-plus-circle me-1"></i> إضافة رواية جديدة
    </a>
</div>

<?php if ($result && $result->num_rows > 0): ?>
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>الصورة</th>
                            <th>العنوان</th>
                            <th>المؤلف</th>
                            <th>سنة النشر</th>
                            <th>عدد الصفحات</th>
                            <th>السعر</th>
                            <th>المخزون</th>
                            <th>تاريخ الإضافة</th>
                            <th>الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($book = $result->fetch_assoc()): ?>
                            <tr>
                                <td style="width: 80px;">
                                    <?php
                                    $cover_path = '../uploads/' . $book['cover_image'];
                                    if (!empty($book['cover_image']) && file_exists($cover_path)) {
                                        $img_src = $cover_path;
                                    } else {
                                        $img_src = '../assets/img/book-placeholder.jpg';
                                    }
                                    ?>
                                    <img 
                                        src="<?php echo $img_src; ?>" 
                                        class="img-thumbnail" 
                                        alt="<?php echo $book['title']; ?>"
                                        style="max-width: 60px; max-height: 80px;"
                                    >
                                </td>
                                <td><?php echo $book['title']; ?></td>
                                <td><?php echo isset($book['author_name']) ? $book['author_name'] : 'غير محدد'; ?></td>
                                <td><?php echo $book['publication_year'] ? $book['publication_year'] : 'غير محدد'; ?></td>
                                <td><?php echo $book['pages'] ? $book['pages'] : 'غير محدد'; ?></td>
                                <td><?php echo format_price($book['price']); ?></td>
                                <td><?php echo $book['stock']; ?></td>
                                <td><?php echo date('d/m/Y', strtotime($book['created_at'])); ?></td>
                                <td>
                                    <div class="btn-group">
                                        <a href="book-edit.php?id=<?php echo $book['id']; ?>" class="btn btn-sm btn-info">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="#" class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#deleteModal<?php echo $book['id']; ?>">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </div>
                                    
                                    <!-- Delete Confirmation Modal -->
                                    <div class="modal fade" id="deleteModal<?php echo $book['id']; ?>" tabindex="-1" aria-hidden="true">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">تأكيد الحذف</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <p>هل أنت متأكد من حذف هذه الرواية؟</p>
                                                    <p class="fw-bold"><?php echo $book['title']; ?></p>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                                                    <a href="books.php?delete=<?php echo $book['id']; ?>" class="btn btn-danger">تأكيد الحذف</a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php else: ?>
    <div class="alert alert-info">لا توجد روايات متاحة حالياً</div>
<?php endif; ?>

<?php require_once 'footer.php'; ?> 