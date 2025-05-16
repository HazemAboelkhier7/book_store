<?php
require_once 'includes/header.php';
require_once 'includes/db.php';
$conn = get_db();

// Get order ID from URL
$order_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Get order details
$query = "SELECT o.*, 
                 oi.book_id, oi.quantity, oi.price,
                 b.title, b.cover_image,
                 a.name as author_name
          FROM orders o
          LEFT JOIN order_items oi ON o.id = oi.order_id
          LEFT JOIN books b ON oi.book_id = b.id
          LEFT JOIN authors a ON b.author_id = a.id
          WHERE o.id = ?";

$stmt = $conn->prepare($query);
$stmt->bind_param('i', $order_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    set_flash_message('الطلب غير موجود', 'danger');
    header('Location: index.php');
    exit;
}

// Group order items
$order = null;
$items = [];
$subtotal = 0;

while ($row = $result->fetch_assoc()) {
    if (!$order) {
        $order = [
            'id' => $row['id'],
            'customer_name' => $row['customer_name'],
            'customer_email' => $row['customer_email'],
            'customer_phone' => $row['customer_phone'],
            'customer_address' => $row['customer_address'],
            'status' => $row['status'],
            'created_at' => $row['created_at']
        ];
    }
    
    $items[] = [
        'book_id' => $row['book_id'],
        'title' => $row['title'],
        'author_name' => $row['author_name'],
        'cover_image' => $row['cover_image'],
        'quantity' => $row['quantity'],
        'price' => $row['price']
    ];
    
    $subtotal += $row['quantity'] * $row['price'];
}

// Calculate totals
$tax_rate = 0.15; // 15% VAT
$shipping = 20; // Fixed shipping cost
$tax = $subtotal * $tax_rate;
$total = $subtotal + $tax + $shipping;

// Get status class and text
function get_status_info($status) {
    switch ($status) {
        case 'pending':
            return [
                'class' => 'warning',
                'icon' => 'clock',
                'text' => 'قيد المراجعة'
            ];
        case 'processing':
            return [
                'class' => 'info',
                'icon' => 'sync',
                'text' => 'جاري التجهيز'
            ];
        case 'shipped':
            return [
                'class' => 'primary',
                'icon' => 'truck',
                'text' => 'تم الشحن'
            ];
        case 'delivered':
            return [
                'class' => 'success',
                'icon' => 'check-circle',
                'text' => 'تم التوصيل'
            ];
        case 'cancelled':
            return [
                'class' => 'danger',
                'icon' => 'times-circle',
                'text' => 'ملغي'
            ];
        default:
            return [
                'class' => 'secondary',
                'icon' => 'question-circle',
                'text' => 'غير معروف'
            ];
    }
}

$status_info = get_status_info($order['status']);
?>

<div class="container my-5">
    <!-- Order Status -->
    <div class="text-center mb-5">
        <div class="display-1 text-<?php echo $status_info['class']; ?> mb-3">
            <i class="fas fa-<?php echo $status_info['icon']; ?>"></i>
        </div>
        <h1 class="text-primary mb-2">شكراً لك على طلبك!</h1>
        <p class="text-light mb-0">رقم الطلب: #<?php echo str_pad($order['id'], 6, '0', STR_PAD_LEFT); ?></p>
        <p class="text-light mb-0">
            حالة الطلب: 
            <span class="text-<?php echo $status_info['class']; ?>">
                <?php echo $status_info['text']; ?>
            </span>
        </p>
    </div>
    
    <div class="row">
        <!-- Order Details -->
        <div class="col-lg-8 mb-4">
            <!-- Order Items -->
            <div class="card bg-dark border-secondary mb-4">
                <div class="card-header bg-dark border-secondary">
                    <h5 class="card-title text-primary mb-0">تفاصيل الطلب</h5>
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush">
                        <?php foreach ($items as $item): ?>
                            <div class="list-group-item bg-dark border-secondary">
                                <div class="row align-items-center">
                                    <div class="col-auto">
                                        <img src="<?php echo !empty($item['cover_image']) ? 'assets/uploads/' . $item['cover_image'] : 'assets/img/book-placeholder.jpg'; ?>"
                                             alt="<?php echo htmlspecialchars($item['title']); ?>"
                                             class="me-3"
                                             style="width: 60px; height: 80px; object-fit: cover;">
                                    </div>
                                    <div class="col">
                                        <h6 class="text-warning mb-1">
                                            <a href="book.php?id=<?php echo $item['book_id']; ?>" class="text-warning text-decoration-none hover-primary">
                                                <?php echo htmlspecialchars($item['title']); ?>
                                            </a>
                                        </h6>
                                        <p class="text-warning small mb-1">
                                            <i class="fas fa-user-edit me-1"></i>
                                            <?php echo htmlspecialchars($item['author_name']); ?>
                                        </p>
                                        <p class="text-warning small mb-0">
                                            الكمية: <?php echo $item['quantity']; ?> × <?php echo format_price($item['price']); ?>
                                        </p>
                                    </div>
                                    <div class="col-auto">
                                        <span class="text-warning">
                                            <?php echo format_price($item['quantity'] * $item['price']); ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            
            <!-- Customer Info -->
            <div class="card bg-dark border-secondary">
                <div class="card-header bg-dark border-secondary">
                    <h5 class="card-title text-primary mb-0">معلومات العميل</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-sm-6 mb-3">
                            <h6 class="text-light mb-2">الاسم</h6>
                            <p class="text-warning mb-0">
                                <i class="fas fa-user me-2"></i>
                                <?php echo htmlspecialchars($order['customer_name']); ?>
                            </p>
                        </div>
                        <div class="col-sm-6 mb-3">
                            <h6 class="text-light mb-2">البريد الإلكتروني</h6>
                            <p class="text-warning mb-0">
                                <i class="fas fa-envelope me-2"></i>
                                <?php echo htmlspecialchars($order['customer_email']); ?>
                            </p>
                        </div>
                        <div class="col-sm-6 mb-3">
                            <h6 class="text-light mb-2">رقم الهاتف</h6>
                            <p class="text-warning mb-0">
                                <i class="fas fa-phone me-2"></i>
                                <?php echo htmlspecialchars($order['customer_phone']); ?>
                            </p>
                        </div>
                        <div class="col-sm-6 mb-3">
                            <h6 class="text-light mb-2">تاريخ الطلب</h6>
                            <p class="text-warning mb-0">
                                <i class="fas fa-calendar-alt me-2"></i>
                                <?php echo date('d/m/Y h:i A', strtotime($order['created_at'])); ?>
                            </p>
                        </div>
                        <div class="col-12">
                            <h6 class="text-light mb-2">عنوان التوصيل</h6>
                            <p class="text-warning mb-0">
                                <i class="fas fa-map-marker-alt me-2"></i>
                                <?php echo nl2br(htmlspecialchars($order['customer_address'])); ?>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Order Summary -->
        <div class="col-lg-4">
            <div class="card bg-dark border-secondary sticky-lg-top" style="top: 2rem;">
                <div class="card-header bg-dark border-secondary">
                    <h5 class="card-title text-primary mb-0">ملخص الطلب</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-dark table-borderless mb-0">
                            <tbody>
                                <tr>
                                    <td class="text-light">المجموع الفرعي:</td>
                                    <td class="text-end text-warning"><?php echo format_price($subtotal); ?></td>
                                </tr>
                                <tr>
                                    <td class="text-light">ضريبة القيمة المضافة (15%):</td>
                                    <td class="text-end text-warning"><?php echo format_price($tax); ?></td>
                                </tr>
                                <tr>
                                    <td class="text-light">رسوم الشحن:</td>
                                    <td class="text-end text-warning"><?php echo format_price($shipping); ?></td>
                                </tr>
                                <tr class="border-top border-secondary">
                                    <td class="text-light"><strong>الإجمالي:</strong></td>
                                    <td class="text-end text-warning"><strong><?php echo format_price($total); ?></strong></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer bg-dark border-secondary">
                    <a href="index.php" class="btn btn-primary w-100">
                        <i class="fas fa-home me-2"></i>
                        العودة للرئيسية
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    @media (min-width: 992px) {
        .sticky-lg-top {
            position: sticky;
            top: 2rem;
        }
    }
    
    .hover-primary {
        transition: color 0.3s ease;
    }
    
    .hover-primary:hover {
        color: var(--primary-color) !important;
    }
</style>

<?php require_once 'includes/footer.php'; ?> 