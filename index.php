<?php
// Force UTF-8 encoding for Arabic text
header('Content-Type: text/html; charset=utf-8');
ini_set('default_charset', 'UTF-8');
mb_internal_encoding('UTF-8');
mb_http_output('UTF-8');

require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

// Get settings
$settings = get_settings();

// Get books with pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = $settings['items_per_page'];
$offset = ($page - 1) * $limit;

// Get filters
$filters = [
    'category_id' => isset($_GET['category']) ? (int)$_GET['category'] : null,
    'author_id' => isset($_GET['author']) ? (int)$_GET['author'] : null,
    'search' => isset($_GET['search']) ? clean($_GET['search']) : null,
    'min_price' => isset($_GET['min_price']) ? (float)$_GET['min_price'] : null,
    'max_price' => isset($_GET['max_price']) ? (float)$_GET['max_price'] : null,
    'sort' => isset($_GET['sort']) ? clean($_GET['sort']) : 'newest'
];

// Get books
$books = get_books($filters, $limit, $offset);
$total_books = get_books_count($filters);
$total_pages = ceil($total_books / $limit);

// Get categories for filter
$categories_query = "SELECT * FROM categories ORDER BY name";
$categories = Database::getInstance()->query($categories_query)->fetch_all(MYSQLI_ASSOC);

// Get authors for filter
$authors_query = "SELECT * FROM authors ORDER BY name";
$authors = Database::getInstance()->query($authors_query)->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($settings['site_name']); ?></title>
    
    <!-- Meta tags -->
    <meta name="description" content="<?php echo htmlspecialchars($settings['site_description']); ?>">
    <meta name="keywords" content="<?php echo htmlspecialchars($settings['site_keywords']); ?>">
    
    <!-- Preload critical assets -->
    <link rel="preload" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" as="style">
    <link rel="preload" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" as="style">
    
    <!-- Bootstrap RTL CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts - Cairo -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary-color: <?php echo PRIMARY_COLOR; ?>;
            --secondary-color: <?php echo SECONDARY_COLOR; ?>;
            --dark-color: <?php echo DARK_COLOR; ?>;
            --darker-color: <?php echo DARKER_COLOR; ?>;
            --light-color: <?php echo LIGHT_COLOR; ?>;
        }
        
        body {
            font-family: 'Cairo', sans-serif !important;
            background-color: #181a1b !important;
            color: var(--light-color);
            min-height: 100vh;
        }
        
        .navbar {
            background-color: var(--darker-color);
            border-bottom: 1px solid var(--secondary-color);
        }
        
        .navbar-brand img {
            max-height: 40px;
        }
        
        .search-form .form-control {
            background-color: var(--dark-color);
            border-color: var(--secondary-color);
            color: var(--light-color);
        }
        
        .search-form .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.25rem rgba(255, 215, 0, 0.25);
        }
        
        .search-form .btn {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            color: var(--dark-color);
        }
        
        .book-card {
            background-color: var(--darker-color);
            border: 1px solid var(--secondary-color);
            transition: transform 0.3s ease;
        }
        
        .book-card:hover {
            transform: translateY(-5px);
        }
        
        .book-card img {
            height: 300px;
            object-fit: cover;
        }
        
        .book-card .card-body {
            padding: 1rem;
        }
        
        .book-card .card-title {
            color: var(--light-color);
            font-weight: 600;
            margin-bottom: 0.5rem;
            height: 48px;
            overflow: hidden;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
        }
        
        .book-card .author {
            color: var(--primary-color);
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
        }
        
        .book-card .price {
            color: var(--primary-color);
            font-size: 1.25rem;
            font-weight: 700;
        }
        
        .book-card .btn-add-to-cart {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            color: var(--dark-color);
            width: 100%;
            margin-top: 1rem;
        }
        
        .book-card .btn-add-to-cart:hover {
            background-color: var(--secondary-color);
            border-color: var(--secondary-color);
        }
        
        .filter-section {
            background-color: var(--darker-color);
            border: 1px solid var(--secondary-color);
            padding: 1rem;
            margin-bottom: 1rem;
        }
        
        .filter-section .form-select {
            background-color: var(--dark-color);
            border-color: var(--secondary-color);
            color: var(--light-color);
        }
        
        .filter-section .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.25rem rgba(255, 215, 0, 0.25);
        }
        
        .pagination .page-link {
            background-color: var(--darker-color);
            border-color: var(--secondary-color);
            color: var(--light-color);
        }
        
        .pagination .page-link:hover {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            color: var(--dark-color);
        }
        
        .pagination .active .page-link {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            color: var(--dark-color);
        }
        
        .card {
            background-color: #23272b !important;
            border-color: #444 !important;
            transition: transform 0.3s ease;
        }
        .card:hover {
            transform: translateY(-5px);
        }
        .card-img-top {
            border-bottom: 1px solid #444;
        }
        .btn-primary {
            background-color: #ffd700;
            border-color: #ffd700;
            color: #23272b;
        }
        .btn-primary:hover {
            background-color: #444;
            border-color: #444;
            color: #ffd700;
        }
        .text-warning {
            color: #ffd700 !important;
        }
        .text-white {
            color: #e0e0e0 !important;
        }
        .text-white-50 {
            color: #a0a0a0 !important;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg sticky-top">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <?php if (!empty($settings['site_logo'])): ?>
                    <img src="<?php echo UPLOAD_URL . $settings['site_logo']; ?>" alt="<?php echo htmlspecialchars($settings['site_name']); ?>">
                <?php else: ?>
                    <?php echo htmlspecialchars($settings['site_name']); ?>
                <?php endif; ?>
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <i class="fas fa-bars text-light"></i>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link text-light active" href="index.php">
                            <i class="fas fa-home"></i> الرئيسية
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-light" href="cart.php">
                            <i class="fas fa-shopping-cart"></i> السلة
                            <?php if (get_cart_count() > 0): ?>
                                <span class="badge bg-primary"><?php echo get_cart_count(); ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                </ul>
                
                <form class="d-flex search-form" method="get">
                    <div class="input-group">
                        <input type="search" class="form-control" name="search" placeholder="ابحث عن كتاب..."
                               value="<?php echo htmlspecialchars($filters['search'] ?? ''); ?>">
                        <button class="btn" type="submit">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </nav>
    
    <!-- Main Content -->
    <div class="container py-4">
        <div class="row">
            <!-- Filters -->
            <div class="col-lg-3 mb-4">
                <div class="filter-section">
                    <h5 class="mb-3">تطبيق الفلتر</h5>
                    <form method="get">
                        <?php if (!empty($filters['search'])): ?>
                            <input type="hidden" name="search" value="<?php echo htmlspecialchars($filters['search']); ?>">
                        <?php endif; ?>
                        
                        <div class="mb-3">
                            <label class="form-label">التصنيف</label>
                            <select class="form-select" name="category">
                                <option value="">الكل</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo $category['id']; ?>" 
                                            <?php echo ($filters['category_id'] == $category['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($category['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">المؤلف</label>
                            <select class="form-select" name="author">
                                <option value="">الكل</option>
                                <?php foreach ($authors as $author): ?>
                                    <option value="<?php echo $author['id']; ?>"
                                            <?php echo ($filters['author_id'] == $author['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($author['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">السعر</label>
                            <div class="row g-2">
                                <div class="col">
                                    <input type="number" class="form-control" name="min_price" placeholder="من"
                                           value="<?php echo $filters['min_price'] ?? ''; ?>">
                                </div>
                                <div class="col">
                                    <input type="number" class="form-control" name="max_price" placeholder="إلى"
                                           value="<?php echo $filters['max_price'] ?? ''; ?>">
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">الترتيب</label>
                            <select class="form-select" name="sort">
                                <option value="newest" <?php echo ($filters['sort'] == 'newest') ? 'selected' : ''; ?>>الأحدث</option>
                                <option value="price_asc" <?php echo ($filters['sort'] == 'price_asc') ? 'selected' : ''; ?>>السعر: من الأقل للأعلى</option>
                                <option value="price_desc" <?php echo ($filters['sort'] == 'price_desc') ? 'selected' : ''; ?>>السعر: من الأعلى للأقل</option>
                                <option value="title" <?php echo ($filters['sort'] == 'title') ? 'selected' : ''; ?>>عنوان الكتاب</option>
                            </select>
                        </div>
                        
                        <button type="submit" class="btn btn-primary w-100">تطبيق الفلتر</button>
                    </form>
                </div>
            </div>
            
            <!-- Books Grid -->
            <div class="col-lg-9">
                <?php if (!empty($books)): ?>
                    <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
                        <?php foreach ($books as $book): ?>
                            <div class="col">
                                <div class="card book-card h-100">
                                    <img src="<?php echo !empty($book['cover_image']) ? UPLOAD_URL . $book['cover_image'] : 'assets/images/no-cover.jpg'; ?>"
                                         class="card-img-top" alt="<?php echo htmlspecialchars($book['title']); ?>">
                                    <div class="card-body">
                                        <h5 class="card-title"><?php echo htmlspecialchars($book['title']); ?></h5>
                                        <p class="author">
                                            <i class="fas fa-user-edit"></i>
                                            <?php echo htmlspecialchars($book['author_name']); ?>
                                        </p>
                                        <div class="d-flex justify-content-between align-items-center">
                                            <span class="price"><?php echo format_price($book['price']); ?></span>
                                            <button class="btn btn-add-to-cart" onclick="addToCart(<?php echo $book['id']; ?>)">
                                                <i class="fas fa-cart-plus"></i> أضف للسلة
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                        <div class="mt-4">
                            <?php
                            $url_pattern = '?page=%d';
                            if (!empty($filters['category_id'])) $url_pattern .= '&category=' . $filters['category_id'];
                            if (!empty($filters['author_id'])) $url_pattern .= '&author=' . $filters['author_id'];
                            if (!empty($filters['search'])) $url_pattern .= '&search=' . urlencode($filters['search']);
                            if (!empty($filters['min_price'])) $url_pattern .= '&min_price=' . $filters['min_price'];
                            if (!empty($filters['max_price'])) $url_pattern .= '&max_price=' . $filters['max_price'];
                            if (!empty($filters['sort'])) $url_pattern .= '&sort=' . $filters['sort'];
                            
                            echo generate_pagination($page, $total_pages, $url_pattern);
                            ?>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> لا توجد كتب متوفرة حالياً.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Footer -->
    <footer class="bg-darker text-light py-4 mt-5">
        <div class="container">
            <div class="row">
                <div class="col-md-4 mb-4">
                    <h5>عن المتجر</h5>
                    <p><?php echo htmlspecialchars($settings['site_description']); ?></p>
                </div>
                <div class="col-md-4 mb-4">
                    <h5>روابط سريعة</h5>
                    <ul class="list-unstyled">
                        <li><a href="index.php" class="text-light">الرئيسية</a></li>
                        <li><a href="cart.php" class="text-light">سلة المشتريات</a></li>
                        <li><a href="contact.php" class="text-light">اتصل بنا</a></li>
                    </ul>
                </div>
                <div class="col-md-4 mb-4">
                    <h5>تواصل معنا</h5>
                    <ul class="list-unstyled">
                        <li><i class="fas fa-phone"></i> <?php echo htmlspecialchars($settings['site_phone']); ?></li>
                        <li><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($settings['site_email']); ?></li>
                        <li><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($settings['site_address']); ?></li>
                    </ul>
                    <div class="social-links mt-3">
                        <?php if (!empty($settings['facebook_url'])): ?>
                            <a href="<?php echo htmlspecialchars($settings['facebook_url']); ?>" class="text-light me-2" target="_blank">
                                <i class="fab fa-facebook-f"></i>
                            </a>
                        <?php endif; ?>
                        <?php if (!empty($settings['twitter_url'])): ?>
                            <a href="<?php echo htmlspecialchars($settings['twitter_url']); ?>" class="text-light me-2" target="_blank">
                                <i class="fab fa-twitter"></i>
                            </a>
                        <?php endif; ?>
                        <?php if (!empty($settings['instagram_url'])): ?>
                            <a href="<?php echo htmlspecialchars($settings['instagram_url']); ?>" class="text-light me-2" target="_blank">
                                <i class="fab fa-instagram"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <hr class="border-secondary">
            <div class="text-center">
                <p class="mb-0">جميع الحقوق محفوظة &copy; <?php echo date('Y') . ' ' . htmlspecialchars($settings['site_name']); ?></p>
            </div>
        </div>
    </footer>
    
    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JavaScript -->
    <script>
        function addToCart(bookId) {
            fetch('ajax/add_to_cart.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'book_id=' + bookId
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update cart count
                    const cartBadge = document.querySelector('.nav-link .badge');
                    if (cartBadge) {
                        cartBadge.textContent = data.cart_count;
                    } else {
                        const cartLink = document.querySelector('.nav-link i.fa-shopping-cart').parentNode;
                        cartLink.innerHTML += `<span class="badge bg-primary">${data.cart_count}</span>`;
                    }
                    
                    // Show success message
                    alert('تم إضافة الكتاب إلى السلة بنجاح');
                } else {
                    alert(data.message || 'حدث خطأ أثناء إضافة الكتاب إلى السلة');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('حدث خطأ أثناء إضافة الكتاب إلى السلة');
            });
        }
    </script>
</body>
</html> 