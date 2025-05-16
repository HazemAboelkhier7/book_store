<?php
require_once 'includes/header.php';
require_once 'includes/db.php';

$conn = get_db();

// Get all categories for the filter
$categories_result = $conn->query("SELECT * FROM categories ORDER BY name");
$categories = [];
while ($category = $categories_result->fetch_assoc()) {
    $categories[] = $category;
}

// Get all authors for the filter
$authors_result = $conn->query("SELECT * FROM authors ORDER BY name");
$authors = [];
while ($author = $authors_result->fetch_assoc()) {
    $authors[] = $author;
}

// Process search
$search_results = [];
$search_query = '';
$selected_category = '';
$selected_author = '';

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['search'])) {
    $search_query = clean($_GET['search'] ?? '');
    $selected_category = (int)($_GET['category'] ?? 0);
    $selected_author = (int)($_GET['author'] ?? 0);
    
    $query = "SELECT b.*, a.name as author_name, c.name as category_name 
              FROM books b 
              LEFT JOIN authors a ON b.author_id = a.id 
              LEFT JOIN categories c ON b.category_id = c.id 
              WHERE 1=1";
    
    $params = [];
    $types = '';
    
    if (!empty($search_query)) {
        $query .= " AND (b.title LIKE ? OR b.description LIKE ?)";
        $params[] = "%$search_query%";
        $params[] = "%$search_query%";
        $types .= "ss";
    }
    
    if ($selected_category > 0) {
        $query .= " AND b.category_id = ?";
        $params[] = $selected_category;
        $types .= "i";
    }
    
    if ($selected_author > 0) {
        $query .= " AND b.author_id = ?";
        $params[] = $selected_author;
        $types .= "i";
    }
    
    $query .= " ORDER BY b.created_at DESC";
    
    $stmt = $conn->prepare($query);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $search_results = $stmt->get_result();
}

// Get search parameters
$filters = [
    'search' => isset($_GET['q']) ? sanitize_input($_GET['q']) : '',
    'category_id' => isset($_GET['category']) ? (int)$_GET['category'] : null,
    'author_id' => isset($_GET['author']) ? (int)$_GET['author'] : null,
    'min_price' => isset($_GET['min_price']) ? (float)$_GET['min_price'] : null,
    'max_price' => isset($_GET['max_price']) ? (float)$_GET['max_price'] : null,
    'sort' => isset($_GET['sort']) ? sanitize_input($_GET['sort']) : 'newest'
];

// Pagination
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$per_page = 12;
$offset = ($page - 1) * $per_page;

// Get books and total count
$books = get_books($filters, $per_page, $offset);
$total_books = get_books_count($filters);
$total_pages = ceil($total_books / $per_page);

// Get price range
$query = "SELECT MIN(price) as min_price, MAX(price) as max_price FROM books";
$result = $conn->query($query);
$price_range = $result->fetch_assoc();

// --- الذكاء في البحث عن اسم التصنيف ---
// إذا لم يتم اختيار تصنيف من القائمة، لكن المستخدم كتب اسم تصنيف في مربع البحث، يتم تحويله إلى category_id تلقائيًا
if (empty($_GET['category']) && !empty($_GET['q'])) {
    $search_text = trim($_GET['q']);
    if ($search_text !== '') {
        $stmt = $conn->prepare("SELECT id FROM categories WHERE name = ? LIMIT 1");
        $stmt->bind_param('s', $search_text);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $_GET['category'] = $row['id'];
            $_GET['q'] = '';
        }
    }
}
?>

<div class="container my-5">
    <!-- Page Title -->
    <div class="row mb-4">
        <div class="col-12">
            <h1 class="text-center text-primary mb-4">
                <?php if (!empty($filters['search'])): ?>
                    نتائج البحث عن: <?php echo htmlspecialchars($filters['search']); ?>
                <?php else: ?>
                    بحث متقدم
                <?php endif; ?>
            </h1>
        </div>
    </div>
    
    <!-- Search Form -->
    <div class="card bg-dark border-secondary mb-4">
        <div class="card-body">
            <form action="" method="GET" class="row g-3">
                <!-- Search Query -->
                <div class="col-md-12">
                    <div class="input-group">
                        <input type="text" name="q" class="form-control form-control-lg bg-dark text-light border-secondary" 
                               placeholder="ابحث عن كتاب، مؤلف، أو دار نشر..."
                               value="<?php echo htmlspecialchars($filters['search']); ?>">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search me-2"></i>
                            بحث
                        </button>
                    </div>
                </div>
                
                <!-- Advanced Filters -->
                <div class="col-md-12">
                    <div class="collapse" id="advancedFilters">
                        <div class="row g-3 mt-2">
                            <!-- Categories -->
                            <div class="col-md-4">
                                <label class="form-label text-light">التصنيف</label>
                                <select name="category" class="form-select bg-dark text-light border-secondary">
                                    <option value="">جميع التصنيفات</option>
                                    <?php while ($category = $categories_result->fetch_assoc()): ?>
                                        <option value="<?php echo $category['id']; ?>" 
                                                <?php echo $filters['category_id'] == $category['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($category['name']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            
                            <!-- Authors -->
                            <div class="col-md-4">
                                <label class="form-label text-light">المؤلف</label>
                                <select name="author" class="form-select bg-dark text-light border-secondary">
                                    <option value="">جميع المؤلفين</option>
                                    <?php while ($author = $authors_result->fetch_assoc()): ?>
                                        <option value="<?php echo $author['id']; ?>"
                                                <?php echo $filters['author_id'] == $author['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($author['name']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            
                            <!-- Price Range -->
                            <div class="col-md-4">
                                <label class="form-label text-light">السعر</label>
                                <div class="input-group">
                                    <input type="number" class="form-control bg-dark text-light border-secondary" 
                                           name="min_price" placeholder="من" 
                                           value="<?php echo $filters['min_price']; ?>"
                                           min="<?php echo floor($price_range['min_price']); ?>"
                                           max="<?php echo ceil($price_range['max_price']); ?>">
                                    <span class="input-group-text bg-dark text-light border-secondary">-</span>
                                    <input type="number" class="form-control bg-dark text-light border-secondary" 
                                           name="max_price" placeholder="إلى"
                                           value="<?php echo $filters['max_price']; ?>"
                                           min="<?php echo floor($price_range['min_price']); ?>"
                                           max="<?php echo ceil($price_range['max_price']); ?>">
                                </div>
                            </div>
                            
                            <!-- Sort -->
                            <div class="col-md-4">
                                <label class="form-label text-light">الترتيب</label>
                                <select name="sort" class="form-select bg-dark text-light border-secondary">
                                    <option value="newest" <?php echo $filters['sort'] === 'newest' ? 'selected' : ''; ?>>الأحدث</option>
                                    <option value="price_asc" <?php echo $filters['sort'] === 'price_asc' ? 'selected' : ''; ?>>السعر: من الأقل</option>
                                    <option value="price_desc" <?php echo $filters['sort'] === 'price_desc' ? 'selected' : ''; ?>>السعر: من الأعلى</option>
                                    <option value="title" <?php echo $filters['sort'] === 'title' ? 'selected' : ''; ?>>عنوان الكتاب</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Toggle Advanced Filters -->
                <div class="col-md-12">
                    <button class="btn btn-link text-light p-0" type="button" 
                            data-bs-toggle="collapse" data-bs-target="#advancedFilters">
                        <i class="fas fa-sliders-h me-2"></i>
                        خيارات البحث المتقدم
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Search Results -->
    <?php if (!empty($books)): ?>
        <!-- Results Count -->
        <div class="alert bg-dark border-secondary text-light">
            <i class="fas fa-search me-2"></i>
            تم العثور على <?php echo $total_books; ?> نتيجة
        </div>
        
        <!-- Books Grid -->
        <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 row-cols-xl-4 g-4">
            <?php foreach ($books as $book): ?>
                <div class="col">
                    <div class="card h-100 bg-dark border-secondary hover-card">
                        <div class="position-relative">
                            <img src="<?php echo !empty($book['cover_image']) ? 'assets/uploads/' . $book['cover_image'] : 'assets/img/book-placeholder.jpg'; ?>"
                                 class="card-img-top"
                                 alt="<?php echo htmlspecialchars($book['title']); ?>"
                                 style="height: 300px; object-fit: cover;">
                            
                            <?php if ($book['stock'] <= 5 && $book['stock'] > 0): ?>
                                <div class="position-absolute top-0 start-0 m-2">
                                    <span class="badge bg-warning text-dark">
                                        باقي <?php echo $book['stock']; ?> نسخ فقط
                                    </span>
                                </div>
                            <?php elseif ($book['stock'] === 0): ?>
                                <div class="position-absolute top-0 start-0 m-2">
                                    <span class="badge bg-danger">نفذت الكمية</span>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="card-body">
                            <h5 class="card-title text-primary mb-2">
                                <a href="book.php?id=<?php echo $book['id']; ?>" class="text-primary text-decoration-none">
                                    <?php echo htmlspecialchars($book['title']); ?>
                                </a>
                            </h5>
                            
                            <p class="card-text text-light mb-1">
                                <i class="fas fa-user-edit me-2 text-secondary"></i>
                                <a href="index.php?author=<?php echo $book['author_id']; ?>" class="text-light text-decoration-none hover-primary">
                                    <?php echo htmlspecialchars($book['author_name']); ?>
                                </a>
                            </p>
                            
                            <p class="card-text text-light mb-2">
                                <i class="fas fa-bookmark me-2 text-secondary"></i>
                                <a href="index.php?category=<?php echo $book['category_id']; ?>" class="text-light text-decoration-none hover-primary">
                                    <?php echo htmlspecialchars($book['category_name']); ?>
                                </a>
                            </p>
                            
                            <p class="card-text">
                                <small class="text-muted">
                                    <?php echo mb_substr($book['description'], 0, 100) . '...'; ?>
                                </small>
                            </p>
                        </div>
                        
                        <div class="card-footer bg-dark border-secondary">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="text-warning fw-bold">
                                    <?php echo format_price($book['price']); ?>
                                </span>
                                
                                <?php if ($book['stock'] > 0): ?>
                                    <form action="cart.php" method="POST" class="d-inline">
                                        <input type="hidden" name="book_id" value="<?php echo $book['id']; ?>">
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
        
        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
            <div class="row mt-4">
                <div class="col-12">
                    <nav aria-label="Page navigation">
                        <ul class="pagination justify-content-center">
                            <?php
                            $url_pattern = '?' . http_build_query(array_merge($_GET, ['page' => '{page}']));
                            $pagination = generate_pagination($page, $total_pages, $url_pattern);
                            
                            foreach ($pagination as $link):
                                if (isset($link['url'])):
                            ?>
                                <li class="page-item <?php echo $link['active'] ? 'active' : ''; ?>">
                                    <a class="page-link bg-dark text-light border-secondary <?php echo $link['active'] ? 'bg-primary border-primary' : ''; ?>" 
                                       href="<?php echo $link['url']; ?>">
                                        <?php echo $link['text']; ?>
                                    </a>
                                </li>
                            <?php else: ?>
                                <li class="page-item disabled">
                                    <span class="page-link bg-dark text-light border-secondary"><?php echo $link['text']; ?></span>
                                </li>
                            <?php
                                endif;
                            endforeach;
                            ?>
                        </ul>
                    </nav>
                </div>
            </div>
        <?php endif; ?>
        
    <?php else: ?>
        <div class="alert alert-info text-center">
            <?php if (!empty($filters['search'])): ?>
                لم يتم العثور على نتائج تطابق بحثك. جرب تعديل معايير البحث.
            <?php else: ?>
                ابدأ البحث عن الكتب باستخدام خيارات البحث أعلاه.
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<style>
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
    
    .hover-primary {
        transition: color 0.3s ease;
    }
    
    .hover-primary:hover {
        color: var(--primary-color) !important;
    }
</style>

<?php require_once 'includes/footer.php'; ?> 