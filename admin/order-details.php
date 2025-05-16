<?php
// Force UTF-8 encoding for Arabic text
ini_set('default_charset', 'UTF-8');
mb_internal_encoding('UTF-8');
mb_http_output('UTF-8');
require_once 'header.php';
require_once '../includes/db.php';

$conn = get_db();

// Check if ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['admin_message'] = 'معرف الطلب غير صالح';
    $_SESSION['admin_message_type'] = 'danger';
    header('Location: orders.php');
    exit;
}

$order_id = (int)$_GET['id'];

// Get order details
$stmt = $conn->prepare("SELECT * FROM orders WHERE id = ?");
$stmt->bind_param("i", $order_id);
$stmt->execute();
$result = $stmt->get_result();

if (!$result || $result->num_rows === 0) {
    $_SESSION['admin_message'] = 'الطلب غير موجود';
    $_SESSION['admin_message_type'] = 'danger';
    header('Location: orders.php');
    exit;
}

$order = $result->fetch_assoc();

// Get order items
$stmt = $conn->prepare("SELECT oi.*, b.title, b.cover_image 
                        FROM order_items oi 
                        JOIN books b ON oi.book_id = b.id 
                        WHERE oi.order_id = ?");
$stmt->bind_param("i", $order_id);
$stmt->execute();
$items_result = $stmt->get_result();

// Status text mapping
$status_text = [
    'pending' => 'قيد الانتظار',
    'processing' => 'قيد المعالجة',
    'completed' => 'مكتمل',
    'cancelled' => 'ملغي'
];

// Status class mapping
$status_class = [
    'pending' => 'bg-warning',
    'processing' => 'bg-info',
    'completed' => 'bg-success',
    'cancelled' => 'bg-danger'
];
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1>تفاصيل الطلب #<?php echo $order['id']; ?></h1>
    <a href="orders.php" class="btn btn-secondary">
        <i class="fas fa-arrow-left me-1"></i> العودة إلى الطلبات
    </a>
</div>

<div class="row">
    <div class="col-lg-8">
        <!-- Order Items -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">منتجات الطلب</h5>
            </div>
            <div class="card-body">
                <?php if ($items_result && $items_result->num_rows > 0): ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th style="width: 80px;">الصورة</th>
                                    <th>المنتج</th>
                                    <th>السعر</th>
                                    <th>الكمية</th>
                                    <th>الإجمالي</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $total = 0;
                                while ($item = $items_result->fetch_assoc()): 
                                    $item_total = $item['price'] * $item['quantity'];
                                    $total += $item_total;
                                ?>
                                    <tr>
                                        <td>
                                            <img 
                                                src="<?php echo !empty($item['cover_image']) ? '../assets/uploads/' . $item['cover_image'] : '../assets/img/book-placeholder.jpg'; ?>" 
                                                class="img-thumbnail" 
                                                alt="<?php echo $item['title']; ?>"
                                                style="max-width: 60px; max-height: 80px;"
                                            >
                                        </td>
                                        <td><?php echo $item['title']; ?></td>
                                        <td><?php echo format_price($item['price']); ?></td>
                                        <td><?php echo $item['quantity']; ?></td>
                                        <td><?php echo format_price($item_total); ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="4" class="text-end fw-bold">المجموع</td>
                                    <td class="fw-bold"><?php echo format_price($total); ?></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info">لا توجد منتجات في هذا الطلب</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4">
        <!-- Order Status -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">حالة الطلب</h5>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <span class="badge <?php echo $status_class[$order['status']] ?? 'bg-secondary'; ?> p-2 fs-6 mb-2 d-block">
                        <?php echo $status_text[$order['status']] ?? 'غير معروف'; ?>
                    </span>
                    
                    <div class="small text-muted">
                        تاريخ الطلب: <?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?>
                    </div>
                    
                    <div class="small text-muted">
                        آخر تحديث: <?php echo date('d/m/Y H:i', strtotime($order['updated_at'])); ?>
                    </div>
                </div>
                
                <form method="post" action="orders.php">
                    <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                    <div class="mb-3">
                        <label for="status" class="form-label">تحديث الحالة</label>
                        <select class="form-select" id="status" name="status">
                            <option value="pending" <?php echo $order['status'] === 'pending' ? 'selected' : ''; ?>>قيد الانتظار</option>
                            <option value="processing" <?php echo $order['status'] === 'processing' ? 'selected' : ''; ?>>قيد المعالجة</option>
                            <option value="completed" <?php echo $order['status'] === 'completed' ? 'selected' : ''; ?>>مكتمل</option>
                            <option value="cancelled" <?php echo $order['status'] === 'cancelled' ? 'selected' : ''; ?>>ملغي</option>
                        </select>
                    </div>
                    <button type="submit" name="update_status" class="btn btn-primary w-100">تحديث الحالة</button>
                </form>
            </div>
        </div>
        
        <!-- Customer Information -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">معلومات العميل</h5>
            </div>
            <div class="card-body">
                <p><strong>الاسم:</strong> <?php echo $order['customer_name']; ?></p>
                <p><strong>البريد الإلكتروني:</strong> <?php echo $order['customer_email']; ?></p>
                <p><strong>رقم الهاتف:</strong> <?php echo $order['customer_phone']; ?></p>
                <p><strong>العنوان:</strong> <?php echo $order['customer_address']; ?></p>
            </div>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?> 