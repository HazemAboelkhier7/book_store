<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';

// معالجة الإجراءات
if (isset($_GET['action'])) {
    $action = $_GET['action'];
    $book_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    switch ($action) {
        case 'remove':
            if (isset($_SESSION['cart'][$book_id])) {
                unset($_SESSION['cart'][$book_id]);
                $_SESSION['message'] = 'تم حذف الكتاب من السلة';
                $_SESSION['message_type'] = 'success';
            }
            break;
        case 'clear':
            unset($_SESSION['cart']);
            $_SESSION['message'] = 'تم تفريغ السلة';
            $_SESSION['message_type'] = 'success';
            break;
    }
    header('Location: cart.php');
    exit;
}

// تحديث الكميات
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['quantities'])) {
    foreach ($_POST['quantities'] as $book_id => $quantity) {
        $book_id = (int)$book_id;
        $quantity = (int)$quantity;
        if (isset($_SESSION['cart'][$book_id])) {
            if ($quantity <= 0) {
                unset($_SESSION['cart'][$book_id]);
            } else if ($quantity <= $_SESSION['cart'][$book_id]['stock']) {
                $_SESSION['cart'][$book_id]['quantity'] = $quantity;
            }
        }
    }
    $_SESSION['message'] = 'تم تحديث السلة';
    $_SESSION['message_type'] = 'success';
    header('Location: cart.php');
    exit;
}

require_once 'includes/header.php';

$total = 0;
?>

<div class="container my-5">
    <h1 class="text-center text-primary mb-4">سلة المشتريات</h1>
    
    <?php if (!empty($_SESSION['cart'])): ?>
        <form method="post">
            <div class="card bg-dark border-secondary">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-dark table-borderless mb-0">
                            <thead>
                                <tr>
                                    <th width="80">الصورة</th>
                                    <th style="min-width:220px;">الكتاب</th>
                                    <th>السعر</th>
                                    <th width="150">الكمية</th>
                                    <th>المجموع</th>
                                    <th width="100">الإجراءات</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($_SESSION['cart'] as $book): ?>
                                    <?php $subtotal = $book['price'] * $book['quantity']; ?>
                                    <?php $total += $subtotal; ?>
                                    <tr>
                                        <td>
                                            <?php
                                            $cover_path = 'uploads/' . $book['cover_image'];
                                            if (!empty($book['cover_image']) && file_exists($cover_path)) {
                                                $img_src = $cover_path;
                                            } else {
                                                $img_src = 'assets/img/book-placeholder.jpg';
                                            }
                                            ?>
                                            <img src="<?php echo $img_src; ?>" 
                                                 alt="<?php echo $book['title']; ?>"
                                                 class="img-thumbnail"
                                                 style="width: 60px; height: 80px; object-fit: cover;">
                                        </td>
                                        <td style="white-space:normal; overflow:visible; text-overflow:unset; min-width:220px;">
                                            <strong><?php echo $book['title']; ?></strong>
                                            <?php if (!empty($book['author'])): ?>
                                                <div class="text-muted" style="font-size: 0.95em;">المؤلف: <?php echo $book['author']; ?></div>
                                            <?php endif; ?>
                                            <?php if (!empty($book['category'])): ?>
                                                <div class="text-info" style="font-size: 0.9em;">التصنيف: <?php echo $book['category']; ?></div>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-warning"><?php echo format_price($book['price']); ?></td>
                                        <td>
                                            <input type="number" 
                                                   name="quantities[<?php echo $book['id']; ?>]" 
                                                   value="<?php echo $book['quantity']; ?>"
                                                   min="1"
                                                   max="<?php echo $book['stock']; ?>"
                                                   class="form-control bg-dark text-light border-secondary"
                                                   style="width: 80px">
                                        </td>
                                        <td class="text-warning"><?php echo format_price($subtotal); ?></td>
                                        <td>
                                            <a href="cart.php?action=remove&id=<?php echo $book['id']; ?>" 
                                               class="btn btn-danger btn-sm"
                                               onclick="return confirm('هل أنت متأكد من حذف هذا الكتاب؟')">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <tr class="table-active">
                                    <td colspan="4" class="text-start"><strong>المجموع الكلي</strong></td>
                                    <td class="text-warning"><strong><?php echo format_price($total); ?></strong></td>
                                    <td></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="d-flex justify-content-between align-items-center mt-4">
                        <a href="cart.php?action=clear" 
                           class="btn btn-danger"
                           onclick="return confirm('هل أنت متأكد من تفريغ السلة؟')">
                            <i class="fas fa-trash me-2"></i>
                            تفريغ السلة
                        </a>
                        <div>
                            <button type="submit" name="update" class="btn btn-secondary me-2">
                                <i class="fas fa-sync-alt me-2"></i>
                                تحديث السلة
                            </button>
                            <a href="index.php" class="btn btn-outline-primary me-2">
                                <i class="fas fa-book-open me-2"></i>
                                مواصلة التسوق
                            </a>
                            <a href="checkout.php" class="btn btn-primary">
                                <i class="fas fa-credit-card me-2"></i>
                                إتمام الشراء
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    <?php else: ?>
        <div class="alert alert-info text-center">
            السلة فارغة. <a href="index.php" class="alert-link">تصفح الكتب المتاحة</a>
        </div>
    <?php endif; ?>
</div>

<style>
    .form-control:focus {
        background-color: var(--dark-color);
        border-color: var(--primary-color);
        color: var(--light-color);
        box-shadow: 0 0 0 0.25rem rgba(255, 215, 0, 0.25);
    }
</style>

<?php require_once 'includes/footer.php'; ?> 