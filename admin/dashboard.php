<?php
// Force UTF-8 encoding for Arabic text
ini_set('default_charset', 'UTF-8');
mb_internal_encoding('UTF-8');
mb_http_output('UTF-8');
require_once 'header.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Get counts for dashboard
$conn = get_db();

// Count books
$books_result = $conn->query("SELECT COUNT(*) as count FROM books");
$books_count = $books_result ? $books_result->fetch_assoc()['count'] : 0;

// Count orders
$orders_result = $conn->query("SELECT COUNT(*) as count FROM orders");
$orders_count = $orders_result ? $orders_result->fetch_assoc()['count'] : 0;

// Sum of all completed orders
$total_result = $conn->query("SELECT SUM(total) as total FROM orders WHERE status = 'completed'");
$total_sales = $total_result ? $total_result->fetch_assoc()['total'] : 0;
$total_sales = $total_sales ?: 0; // Handle NULL value

// Get recent orders with item count
$recent_orders_result = $conn->query("
    SELECT o.*, COUNT(oi.id) as items_count 
    FROM orders o 
    LEFT JOIN order_items oi ON o.id = oi.order_id 
    GROUP BY o.id 
    ORDER BY o.created_at DESC 
    LIMIT 5
");

// All books with their stock
$low_stock_query = "SELECT * FROM books ORDER BY stock ASC";
$low_stock_result = $conn->query($low_stock_query);
$low_stock_books = $low_stock_result->fetch_all(MYSQLI_ASSOC);
?>

<div class="container-fluid py-4">
    <div class="row">
        <!-- Statistics Cards -->
        <div class="col-md-4">
            <div class="card bg-dark border-primary mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-2">إجمالي الكتب</h6>
                            <h2 class="mb-0 text-light"><?php echo $books_count; ?></h2>
                        </div>
                        <div class="bg-primary bg-opacity-25 p-3 rounded">
                            <i class="fas fa-book fa-2x text-primary"></i>
                        </div>
                    </div>
                </div>
                <div class="card-footer bg-transparent border-0 text-center">
                    <a href="books.php" class="text-primary text-decoration-none">عرض التفاصيل <i class="fas fa-arrow-left ms-1"></i></a>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card bg-dark border-success mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-2">إجمالي الطلبات</h6>
                            <h2 class="mb-0 text-light"><?php echo $orders_count; ?></h2>
                        </div>
                        <div class="bg-success bg-opacity-25 p-3 rounded">
                            <i class="fas fa-shopping-cart fa-2x text-success"></i>
                        </div>
                    </div>
                </div>
                <div class="card-footer bg-transparent border-0 text-center">
                    <a href="orders.php" class="text-success text-decoration-none">عرض التفاصيل <i class="fas fa-arrow-left ms-1"></i></a>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card bg-dark border-warning mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-2">إجمالي المبيعات</h6>
                            <h2 class="mb-0 text-light"><?php echo format_price($total_sales); ?></h2>
                        </div>
                        <div class="bg-warning bg-opacity-25 p-3 rounded">
                            <i class="fas fa-money-bill-wave fa-2x text-warning"></i>
                        </div>
                    </div>
                </div>
                <div class="card-footer bg-transparent border-0 text-center">
                    <a href="orders.php" class="text-warning text-decoration-none">عرض التفاصيل <i class="fas fa-arrow-left ms-1"></i></a>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Orders -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card bg-dark border-secondary">
                <div class="card-header bg-dark border-secondary d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 text-light">أحدث الطلبات</h5>
                    <a href="orders.php" class="btn btn-sm btn-primary">عرض الكل</a>
                </div>
                <div class="card-body">
                    <?php if ($recent_orders_result && $recent_orders_result->num_rows > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-dark table-hover">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>العميل</th>
                                        <th>عدد الكتب</th>
                                        <th>المبلغ</th>
                                        <th>تاريخ الطلب</th>
                                        <th>الحالة</th>
                                        <th>إجراءات</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($order = $recent_orders_result->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo str_pad($order['id'], 6, '0', STR_PAD_LEFT); ?></td>
                                            <td><?php echo htmlspecialchars($order['customer_name']); ?></td>
                                            <td><?php echo $order['items_count']; ?></td>
                                            <td><?php echo format_price($order['total']); ?></td>
                                            <td><?php echo date('d/m/Y h:i A', strtotime($order['created_at'])); ?></td>
                                            <td>
                                                <?php
                                                $status_class = '';
                                                switch ($order['status']) {
                                                    case 'pending':
                                                        $status_class = 'bg-warning text-dark';
                                                        $status_text = 'قيد المراجعة';
                                                        break;
                                                    case 'processing':
                                                        $status_class = 'bg-info';
                                                        $status_text = 'قيد التجهيز';
                                                        break;
                                                    case 'shipped':
                                                        $status_class = 'bg-primary';
                                                        $status_text = 'تم الشحن';
                                                        break;
                                                    case 'delivered':
                                                        $status_class = 'bg-success';
                                                        $status_text = 'تم التوصيل';
                                                        break;
                                                    case 'cancelled':
                                                        $status_class = 'bg-danger';
                                                        $status_text = 'ملغي';
                                                        break;
                                                    case 'completed':
                                                        $status_class = 'bg-success';
                                                        $status_text = 'مكتمل';
                                                        break;
                                                    case 'مكتمل':
                                                        $status_class = 'bg-success';
                                                        $status_text = 'مكتمل';
                                                        break;
                                                    default:
                                                        $status_class = 'bg-secondary';
                                                        $status_text = htmlspecialchars($order['status']);
                                                }
                                                ?>
                                                <span class="badge <?php echo $status_class; ?>"><?php echo $status_text; ?></span>
                                            </td>
                                            <td>
                                                <a href="order-details.php?id=<?php echo $order['id']; ?>" 
                                                   class="btn btn-sm btn-info" 
                                                   title="عرض التفاصيل">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info bg-dark text-info border-info">
                            لا توجد طلبات حتى الآن
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Low Stock Alert -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card bg-dark border-secondary">
                <div class="card-header bg-dark border-secondary d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 text-light">تنبيه المخزون</h5>
                    <a href="books.php" class="btn btn-sm btn-primary">عرض الكل</a>
                </div>
                <div class="card-body">
                    <?php if (!empty($low_stock_books)): ?>
                        <div class="table-responsive">
                            <table class="table table-dark table-hover">
                                <thead>
                                    <tr>
                                        <th>الكتاب</th>
                                        <th>المخزون المتبقي</th>
                                        <th>السعر</th>
                                        <th>الإجراءات</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($low_stock_books as $book): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($book['title']); ?></td>
                                            <td>
                                                <span class="badge bg-danger"><?php echo $book['stock']; ?></span>
                                            </td>
                                            <td><?php echo format_price($book['price']); ?></td>
                                            <td>
                                                <a href="book-edit.php?id=<?php echo $book['id']; ?>" class="btn btn-sm btn-primary">
                                                    <i class="fas fa-edit"></i> تعديل
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info bg-dark text-info border-info">
                            لا توجد كتب مخزن صغير حتى الآن
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?> 