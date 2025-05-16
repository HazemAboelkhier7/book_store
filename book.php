<?php
// Force UTF-8 encoding for Arabic text
ini_set('default_charset', 'UTF-8');
mb_internal_encoding('UTF-8');
mb_http_output('UTF-8');
header('Content-Type: text/html; charset=UTF-8');

require_once 'includes/header.php';
require_once 'includes/db.php';

// تحقق من وجود معرف الكتاب في الرابط
if (!isset($_GET['id']) || empty($_GET['id'])) {
    // إذا لم يتم تحديد كتاب، إعادة التوجيه إلى الصفحة الرئيسية
    header('Location: index.php');
    exit;
}

$book_id = (int)$_GET['id'];

// جلب معلومات الكتاب
$conn = get_db();
$stmt = $conn->prepare("SELECT * FROM books WHERE id = ?");
$stmt->bind_param("i", $book_id);
$stmt->execute();
$result = $stmt->get_result();

// التحقق من وجود الكتاب
if (!$result || $result->num_rows === 0) {
    // إذا لم يتم العثور على الكتاب، إعادة التوجيه إلى الصفحة الرئيسية
    $_SESSION['message'] = 'الكتاب غير موجود';
    $_SESSION['message_type'] = 'danger';
    header('Location: index.php');
    exit;
}

$book = $result->fetch_assoc();

// Process add to cart action
if (isset($_POST['add_to_cart']) && isset($_POST['book_id'])) {
    $posted_book_id = (int)$_POST['book_id'];
    
    // التحقق من توفر المخزون
    if ($book['stock'] <= 0) {
        // الكتاب غير متوفر
        $_SESSION['message'] = 'عذراً، الكتاب غير متوفر حالياً في المخزون';
        $_SESSION['message_type'] = 'danger';
        header('Location: book.php?id=' . $book_id);
        exit;
    }
    
    // Initialize cart if not exists
    init_cart();
    
    // Check if book already in cart
    if (isset($_SESSION['cart'][$posted_book_id])) {
        // تحقق ما إذا كانت الكمية الجديدة تزيد عن المخزون المتاح
        $new_quantity = $_SESSION['cart'][$posted_book_id]['quantity'] + 1;
        
        if ($new_quantity > $book['stock']) {
            $_SESSION['message'] = 'عذراً، لا يمكن إضافة المزيد. الكمية المتاحة: ' . $book['stock'];
            $_SESSION['message_type'] = 'warning';
            header('Location: book.php?id=' . $book_id);
            exit;
        }
        
        // Increase quantity
        $_SESSION['cart'][$posted_book_id]['quantity']++;
    } else {
        // Add book to cart
        $_SESSION['cart'][$posted_book_id] = [
            'id' => $book['id'],
            'title' => $book['title'],
            'price' => $book['price'],
            'cover_image' => $book['cover_image'],
            'quantity' => 1,
            'stock' => $book['stock'] // تخزين المخزون المتاح للتحقق لاحقاً
        ];
    }
    
    // Set success message
    $_SESSION['message'] = 'تمت إضافة الكتاب إلى سلة المشتريات';
    $_SESSION['message_type'] = 'success';
    
    // Redirect to avoid form resubmission
    header('Location: book.php?id=' . $book_id);
    exit;
}

// Get related books (same category or author)
$related_books = get_books([
    'category_id' => $book['category_id'],
    'author_id' => $book['author_id']
], 4);

// Remove current book from related books
foreach ($related_books as $key => $related_book) {
    if ($related_book['id'] === $book_id) {
        unset($related_books[$key]);
        break;
    }
}
?>

<div class="container my-5">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index.php">الرئيسية</a></li>
            <li class="breadcrumb-item active" aria-current="page"><?php echo $book['title']; ?></li>
        </ol>
    </nav>

    <div class="row">
        <!-- صورة الكتاب -->
        <div class="col-md-4 mb-4">
            <img 
                src="<?php echo !empty($book['cover_image']) ? 'assets/uploads/' . $book['cover_image'] : 'assets/img/book-placeholder.jpg'; ?>" 
                class="img-fluid rounded shadow book-detail-cover" 
                alt="<?php echo $book['title']; ?>"
            >
        </div>
        
        <!-- تفاصيل الكتاب -->
        <div class="col-md-8">
            <h1 class="mb-3"><?php echo $book['title']; ?></h1>
            <h4 class="text-muted mb-3"><?php echo $book['author']; ?></h4>
            
            <!-- حالة المخزون -->
            <?php if ($book['stock'] > 10): ?>
                <div class="badge bg-success p-2 mb-3 fs-6">متوفر</div>
            <?php elseif ($book['stock'] > 0): ?>
                <div class="badge bg-warning text-dark p-2 mb-3 fs-6">متوفر (<?php echo $book['stock']; ?> قطع متبقية)</div>
            <?php else: ?>
                <div class="badge bg-danger p-2 mb-3 fs-6">غير متوفر حالياً</div>
            <?php endif; ?>
            
            <div class="fs-3 fw-bold text-primary mb-4"><?php echo format_price($book['price']); ?></div>
            
            <div class="mb-4">
                <h5>الوصف:</h5>
                <p class="lead">
                    <?php echo nl2br($book['description']); ?>
                </p>
            </div>
            
            <!-- معلومات إضافية -->
            <div class="row mb-4">
                <div class="col-6 col-md-4">
                    <div class="card h-100">
                        <div class="card-body text-center">
                            <i class="fas fa-calendar-alt fs-3 mb-2 text-secondary"></i>
                            <h6>سنة النشر</h6>
                            <p class="fw-bold"><?php echo $book['publication_year'] ?? 'غير محدد'; ?></p>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-4">
                    <div class="card h-100">
                        <div class="card-body text-center">
                            <i class="fas fa-bookmark fs-3 mb-2 text-secondary"></i>
                            <h6>عدد الصفحات</h6>
                            <p class="fw-bold"><?php echo $book['pages'] ?? 'غير محدد'; ?></p>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-4">
                    <div class="card h-100">
                        <div class="card-body text-center">
                            <i class="fas fa-language fs-3 mb-2 text-secondary"></i>
                            <h6>اللغة</h6>
                            <p class="fw-bold"><?php echo $book['language'] ?? 'العربية'; ?></p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- زر إضافة إلى السلة -->
            <form method="post" class="mt-4">
                <input type="hidden" name="book_id" value="<?php echo $book['id']; ?>">
                <button type="submit" name="add_to_cart" class="btn btn-primary btn-lg w-100 <?php echo $book['stock'] <= 0 ? 'disabled' : ''; ?>">
                    <i class="fas fa-shopping-cart me-2"></i> أضف إلى السلة
                </button>
            </form>
        </div>
    </div>

    <!-- Related Books -->
    <?php if (!empty($related_books)): ?>
        <div class="mt-5">
            <h3 class="text-primary mb-4">كتب ذات صلة</h3>
            <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 row-cols-xl-4 g-4">
                <?php foreach ($related_books as $related_book): ?>
                    <div class="col">
                        <div class="card h-100 bg-dark border-secondary hover-card">
                            <div class="position-relative">
                                <img src="<?php echo !empty($related_book['cover_image']) ? 'assets/uploads/' . $related_book['cover_image'] : 'assets/img/book-placeholder.jpg'; ?>"
                                     class="card-img-top"
                                     alt="<?php echo htmlspecialchars($related_book['title']); ?>"
                                     style="height: 300px; object-fit: cover;">
                                
                                <?php if ($related_book['stock'] <= 5 && $related_book['stock'] > 0): ?>
                                    <div class="position-absolute top-0 start-0 m-2">
                                        <span class="badge bg-warning text-dark">
                                            باقي <?php echo $related_book['stock']; ?> نسخ فقط
                                        </span>
                                    </div>
                                <?php elseif ($related_book['stock'] === 0): ?>
                                    <div class="position-absolute top-0 start-0 m-2">
                                        <span class="badge bg-danger">نفذت الكمية</span>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="card-body">
                                <h5 class="card-title text-primary mb-2">
                                    <a href="book.php?id=<?php echo $related_book['id']; ?>" class="text-primary text-decoration-none">
                                        <?php echo htmlspecialchars($related_book['title']); ?>
                                    </a>
                                </h5>
                                
                                <p class="card-text text-light mb-1">
                                    <i class="fas fa-user-edit me-2 text-secondary"></i>
                                    <?php echo htmlspecialchars($related_book['author_name']); ?>
                                </p>
                                
                                <p class="card-text">
                                    <small class="text-muted">
                                        <?php echo mb_substr($related_book['description'], 0, 100) . '...'; ?>
                                    </small>
                                </p>
                            </div>
                            
                            <div class="card-footer bg-dark border-secondary">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="text-warning fw-bold">
                                        <?php echo format_price($related_book['price']); ?>
                                    </span>
                                    
                                    <?php if ($related_book['stock'] > 0): ?>
                                        <form action="cart.php" method="POST" class="d-inline">
                                            <input type="hidden" name="book_id" value="<?php echo $related_book['id']; ?>">
                                            <input type="hidden" name="action" value="add">
                                            <button type="submit" class="btn btn-primary btn-sm">
                                                <i class="fas fa-cart-plus me-2"></i>
                                                أضف للسلة
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <button class="btn btn-secondary btn-sm" disabled>
                                            <i class="fas fa-times me-2"></i>
                                            نفذت الكمية
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<style>
    .book-cover {
        transition: transform 0.3s ease;
    }
    
    .book-cover:hover {
        transform: scale(1.02);
    }
    
    .breadcrumb-item + .breadcrumb-item::before {
        color: var(--light-color);
    }
    
    .hover-card {
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }
    
    .hover-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 5px 15px rgba(255, 215, 0, 0.2);
    }
    
    .card-img-top {
        transition: transform 0.3s ease;
    }
    
    .hover-card:hover .card-img-top {
        transform: scale(1.05);
    }
</style>

<?php require_once 'includes/footer.php'; ?> 