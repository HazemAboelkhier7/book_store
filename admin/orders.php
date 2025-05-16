<?php
// Force UTF-8 encoding for Arabic text
ini_set('default_charset', 'UTF-8');
mb_internal_encoding('UTF-8');
mb_http_output('UTF-8');

// Initialize session if needed
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database connection
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Process form submissions first, before any output
if (isset($_POST['update_status']) && isset($_POST['order_id']) && isset($_POST['status'])) {
    $conn = get_db();
    $order_id = (int)$_POST['order_id'];
    $status = clean($_POST['status']);
    $prev_status = ''; // تخزين الحالة السابقة للطلب
    
    // الحصول على الحالة الحالية للطلب قبل التعديل
    $check_stmt = $conn->prepare("SELECT status FROM orders WHERE id = ?");
    $check_stmt->bind_param("i", $order_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    if ($check_result && $check_result->num_rows > 0) {
        $prev_status = $check_result->fetch_assoc()['status'];
    }
    $check_stmt->close();
    
    $allowed_statuses = ['pending', 'processing', 'completed', 'cancelled'];
    
    if (in_array($status, $allowed_statuses)) {
        $conn->begin_transaction(); // بدء معاملة قاعدة البيانات
        
        try {
            // تحديث حالة الطلب
            $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
            $stmt->bind_param("si", $status, $order_id);
            $stmt->execute();
            
            // إذا تم تغيير الحالة إلى "مكتمل" وكانت الحالة السابقة ليست "مكتمل"
            if ($status === 'completed' && $prev_status !== 'completed') {
                // الحصول على عناصر الطلب
                $items_stmt = $conn->prepare("SELECT book_id, quantity FROM order_items WHERE order_id = ?");
                $items_stmt->bind_param("i", $order_id);
                $items_stmt->execute();
                $items_result = $items_stmt->get_result();
                
                while ($item = $items_result->fetch_assoc()) {
                    $book_id = $item['book_id'];
                    $quantity = $item['quantity'];
                    
                    // تحديث مخزون الكتاب
                    $update_stock_stmt = $conn->prepare("UPDATE books SET stock = stock - ? WHERE id = ? AND stock >= ?");
                    $update_stock_stmt->bind_param("iii", $quantity, $book_id, $quantity);
                    $update_stock_stmt->execute();
                    
                    // التحقق إذا تم تحديث المخزون بنجاح
                    if ($update_stock_stmt->affected_rows === 0) {
                        // قد يكون السبب أن المخزون أقل من الكمية المطلوبة
                        $stock_check = $conn->prepare("SELECT stock FROM books WHERE id = ?");
                        $stock_check->bind_param("i", $book_id);
                        $stock_check->execute();
                        $stock_result = $stock_check->get_result();
                        $current_stock = $stock_result->fetch_assoc()['stock'];
                        
                        if ($current_stock < $quantity) {
                            throw new Exception("الكتاب رقم {$book_id} المخزون غير كافٍ. المتوفر: {$current_stock}, المطلوب: {$quantity}");
                        }
                    }
                    $update_stock_stmt->close();
                }
                $items_stmt->close();
            }
            
            // إذا تم إلغاء الطلب، نعيد الكمية إلى المخزون بغض النظر عن الحالة السابقة
            if ($status === 'cancelled' && $prev_status !== 'cancelled') {
                // الحصول على عناصر الطلب
                $items_stmt = $conn->prepare("SELECT book_id, quantity FROM order_items WHERE order_id = ?");
                $items_stmt->bind_param("i", $order_id);
                $items_stmt->execute();
                $items_result = $items_stmt->get_result();
                
                while ($item = $items_result->fetch_assoc()) {
                    $book_id = $item['book_id'];
                    $quantity = $item['quantity'];
                    
                    // إعادة الكمية إلى المخزون
                    $update_stock_stmt = $conn->prepare("UPDATE books SET stock = stock + ? WHERE id = ?");
                    $update_stock_stmt->bind_param("ii", $quantity, $book_id);
                    $update_stock_stmt->execute();
                    $update_stock_stmt->close();
                }
                $items_stmt->close();
            }
            
            $conn->commit(); // تأكيد المعاملة
            
            $_SESSION['admin_message'] = 'تم تحديث حالة الطلب بنجاح';
            $_SESSION['admin_message_type'] = 'success';
        } catch (Exception $e) {
            $conn->rollback(); // التراجع عن المعاملة في حالة حدوث خطأ
            $_SESSION['admin_message'] = 'فشل في تحديث حالة الطلب: ' . $e->getMessage();
            $_SESSION['admin_message_type'] = 'danger';
        }
    } else {
        $_SESSION['admin_message'] = 'حالة الطلب غير صالحة';
        $_SESSION['admin_message_type'] = 'danger';
    }
    
    // Redirect to avoid resubmission - this must come before any HTML output
    header('Location: orders.php');
    exit;
}

// معالجة حذف الطلب
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $order_id = (int)$_GET['delete'];
    $conn = get_db();
    // حذف عناصر الطلب أولاً
    $conn->query("DELETE FROM order_items WHERE order_id = $order_id");
    // حذف الطلب نفسه
    $conn->query("DELETE FROM orders WHERE id = $order_id");
    $_SESSION['admin_message'] = 'تم حذف الطلب بنجاح';
    $_SESSION['admin_message_type'] = 'success';
    header('Location: orders.php');
    exit;
}

// Include header after processing form and potential redirects
require_once 'header.php';

$conn = get_db();

// Get all orders
$result = $conn->query("SELECT o.*, COUNT(oi.id) as items_count 
                        FROM orders o 
                        LEFT JOIN order_items oi ON o.id = oi.order_id 
                        GROUP BY o.id 
                        ORDER BY o.created_at DESC");
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1>إدارة الطلبات</h1>
</div>

<?php if ($result && $result->num_rows > 0): ?>
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>العميل</th>
                            <th>البريد الإلكتروني</th>
                            <th>رقم الهاتف</th>
                            <th>المبلغ</th>
                            <th>عدد العناصر</th>
                            <th>الحالة</th>
                            <th>تاريخ الطلب</th>
                            <th>الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($order = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $order['id']; ?></td>
                                <td><?php echo $order['customer_name']; ?></td>
                                <td><?php echo $order['customer_email']; ?></td>
                                <td><?php echo $order['customer_phone']; ?></td>
                                <td><?php echo format_price(isset($order['total_amount']) ? $order['total_amount'] : 0.0); ?></td>
                                <td><?php echo $order['items_count']; ?></td>
                                <td>
                                    <?php
                                    $status_class = '';
                                    switch ($order['status']) {
                                        case 'pending':
                                            $status_class = 'bg-warning';
                                            $status_text = 'قيد الانتظار';
                                            break;
                                        case 'processing':
                                            $status_class = 'bg-info';
                                            $status_text = 'قيد المعالجة';
                                            break;
                                        case 'completed':
                                            $status_class = 'bg-success';
                                            $status_text = 'مكتمل';
                                            break;
                                        case 'مكتمل':
                                            $status_class = 'bg-success';
                                            $status_text = 'مكتمل';
                                            break;
                                        case 'cancelled':
                                            $status_class = 'bg-danger';
                                            $status_text = 'ملغي';
                                            break;
                                        default:
                                            $status_class = 'bg-secondary';
                                            $status_text = htmlspecialchars($order['status']);
                                    }
                                    ?>
                                    <span class="badge <?php echo $status_class; ?>"><?php echo $status_text; ?></span>
                                </td>
                                <td><?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?></td>
                                <td>
                                    <div class="btn-group">
                                        <a href="order-details.php?id=<?php echo $order['id']; ?>" class="btn btn-sm btn-info">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#statusModal<?php echo $order['id']; ?>">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <a href="orders.php?delete=<?php echo $order['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('هل أنت متأكد من حذف هذا الطلب نهائياً؟');">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </div>
                                    
                                    <!-- Status Update Modal -->
                                    <div class="modal fade" id="statusModal<?php echo $order['id']; ?>" tabindex="-1" aria-hidden="true">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">تحديث حالة الطلب #<?php echo $order['id']; ?></h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <form method="post">
                                                    <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                                    <div class="modal-body">
                                                        <div class="mb-3">
                                                            <label for="status<?php echo $order['id']; ?>" class="form-label">حالة الطلب</label>
                                                            <select class="form-select" id="status<?php echo $order['id']; ?>" name="status">
                                                                <option value="pending" <?php echo $order['status'] === 'pending' ? 'selected' : ''; ?>>قيد الانتظار</option>
                                                                <option value="processing" <?php echo $order['status'] === 'processing' ? 'selected' : ''; ?>>قيد المعالجة</option>
                                                                <option value="completed" <?php echo $order['status'] === 'completed' ? 'selected' : ''; ?>>مكتمل</option>
                                                                <option value="cancelled" <?php echo $order['status'] === 'cancelled' ? 'selected' : ''; ?>>ملغي</option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                                                        <button type="submit" name="update_status" class="btn btn-primary">تحديث الحالة</button>
                                                    </div>
                                                </form>
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
    <div class="alert alert-info">لا توجد طلبات حتى الآن</div>
<?php endif; ?>

<?php require_once 'footer.php'; ?> 