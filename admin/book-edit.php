<?php
// Force UTF-8 encoding for Arabic text
ini_set('default_charset', 'UTF-8');
mb_internal_encoding('UTF-8');
mb_http_output('UTF-8');
ob_start(); // بدء التخزين المؤقت للمخرجات
require_once 'header.php';
require_once '../includes/db.php';

$conn = get_db();
$errors = [];
$book = [
    'id' => '',
    'title' => '',
    'author' => '',
    'description' => '',
    'price' => '',
    'stock' => '1',
    'cover_image' => '',
    'publication_year' => '',
    'pages' => ''
];

$is_edit = false;

// Check if we're editing an existing book
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $book_id = (int)$_GET['id'];
    $is_edit = true;
    
    // Get book details
    $stmt = $conn->prepare("SELECT * FROM books WHERE id = ?");
    $stmt->bind_param("i", $book_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result && $result->num_rows > 0) {
        $book = $result->fetch_assoc();
    } else {
        // Book not found
        $_SESSION['admin_message'] = 'الرواية غير موجودة';
        $_SESSION['admin_message_type'] = 'danger';
        header('Location: books.php');
        exit;
    }
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate title
    $book['title'] = clean($_POST['title'] ?? '');
    if (empty($book['title'])) {
        $errors[] = 'يرجى إدخال عنوان الرواية';
    }
    
    // Validate author
    $book['author'] = clean($_POST['author'] ?? '');
    if (empty($book['author'])) {
        $errors[] = 'يرجى إدخال اسم المؤلف';
    }
    
    // Validate description
    $book['description'] = clean($_POST['description'] ?? '');
    if (empty($book['description'])) {
        $errors[] = 'يرجى إدخال وصف الرواية';
    }
    
    // Validate price
    $book['price'] = floatval($_POST['price'] ?? 0);
    if ($book['price'] <= 0) {
        $errors[] = 'يرجى إدخال سعر صحيح للرواية';
    }
    
    // Validate stock
    $book['stock'] = intval($_POST['stock'] ?? 1);
    if ($book['stock'] < 0) {
        $errors[] = 'يرجى إدخال كمية صحيحة للمخزون';
    }
    
    // حقول جديدة: سنة النشر وعدد الصفحات
    $book['publication_year'] = !empty($_POST['publication_year']) ? intval($_POST['publication_year']) : null;
    if (!empty($book['publication_year']) && ($book['publication_year'] < 1800 || $book['publication_year'] > date('Y'))) {
        $errors[] = 'يرجى إدخال سنة نشر صحيحة';
    }
    
    $book['pages'] = !empty($_POST['pages']) ? intval($_POST['pages']) : null;
    if (!empty($book['pages']) && $book['pages'] <= 0) {
        $errors[] = 'يرجى إدخال عدد صفحات صحيح';
    }
    
    // Handle cover image upload
    $cover_image = $_FILES['cover_image'] ?? null;
    
    if ($cover_image && $cover_image['size'] > 0) {
        // Check if upload directory exists, create if not
        if (!file_exists(UPLOAD_PATH)) {
            mkdir(UPLOAD_PATH, 0777, true);
        }
        
        // Validate image
        $allowed_types = ['image/jpeg', 'image/png', 'image/jpg'];
        $max_size = 2 * 1024 * 1024; // 2MB
        
        if (!in_array($cover_image['type'], $allowed_types)) {
            $errors[] = 'يجب أن تكون صورة الغلاف بصيغة JPG أو PNG';
        } elseif ($cover_image['size'] > $max_size) {
            $errors[] = 'يجب أن يكون حجم صورة الغلاف أقل من 2 ميجابايت';
        } else {
            // Generate unique filename
            $extension = pathinfo($cover_image['name'], PATHINFO_EXTENSION);
            $filename = 'book_' . time() . '_' . random_string(6) . '.' . $extension;
            
            // Move uploaded file
            if (move_uploaded_file($cover_image['tmp_name'], UPLOAD_PATH . $filename)) {
                // Delete old image if exists
                if ($is_edit && !empty($book['cover_image']) && file_exists(UPLOAD_PATH . $book['cover_image'])) {
                    unlink(UPLOAD_PATH . $book['cover_image']);
                }
                
                $book['cover_image'] = $filename;
            } else {
                $errors[] = 'فشل في تحميل صورة الغلاف';
            }
        }
    } elseif (!$is_edit) {
        // اشتراط الصورة للكتب الجديدة فقط
        if (empty($cover_image) || $cover_image['size'] == 0) {
            $errors[] = 'يرجى تحميل صورة الغلاف';
        }
    }
    
    // Process if no errors
    if (empty($errors)) {
        if ($is_edit) {
            // Update existing book
            $stmt = $conn->prepare("UPDATE books SET 
                                    title = ?, 
                                    author = ?, 
                                    description = ?, 
                                    price = ?, 
                                    stock = ?, 
                                    cover_image = ?,
                                    publication_year = ?,
                                    pages = ?
                                    WHERE id = ?");
            $stmt->bind_param("sssdssiis", 
                            $book['title'], 
                            $book['author'], 
                            $book['description'], 
                            $book['price'], 
                            $book['stock'], 
                            $book['cover_image'],
                            $book['publication_year'],
                            $book['pages'],
                            $book['id']);
            
            if ($stmt->execute()) {
                $_SESSION['admin_message'] = 'تم تحديث الرواية بنجاح';
                $_SESSION['admin_message_type'] = 'success';
                header('Location: books.php');
                exit;
            } else {
                $errors[] = 'فشل في تحديث الرواية: ' . $conn->error;
            }
        } else {
            // Insert new book
            $stmt = $conn->prepare("INSERT INTO books (title, author, description, price, stock, cover_image, publication_year, pages) 
                                   VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssdssis", 
                            $book['title'], 
                            $book['author'], 
                            $book['description'], 
                            $book['price'], 
                            $book['stock'], 
                            $book['cover_image'],
                            $book['publication_year'],
                            $book['pages']);
            
            if ($stmt->execute()) {
                $_SESSION['admin_message'] = 'تمت إضافة الرواية بنجاح';
                $_SESSION['admin_message_type'] = 'success';
                header('Location: books.php');
                exit;
            } else {
                $errors[] = 'فشل في إضافة الرواية: ' . $conn->error;
            }
        }
    }
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1><?php echo $is_edit ? 'تعديل الرواية' : 'إضافة رواية جديدة'; ?></h1>
    <a href="books.php" class="btn btn-secondary">
        <i class="fas fa-arrow-left me-1"></i> العودة إلى القائمة
    </a>
</div>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <ul class="mb-0">
            <?php foreach ($errors as $error): ?>
                <li><?php echo $error; ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-body">
        <form method="post" enctype="multipart/form-data">
            <div class="row">
                <div class="col-md-8">
                    <div class="mb-3">
                        <label for="title" class="form-label">عنوان الرواية <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="title" name="title" value="<?php echo htmlspecialchars(isset($book['title']) ? $book['title'] : ''); ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="author" class="form-label">المؤلف <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="author" name="author" value="<?php echo htmlspecialchars(isset($book['author']) ? $book['author'] : ''); ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="description" class="form-label">وصف الرواية <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="description" name="description" rows="5" required><?php echo htmlspecialchars(isset($book['description']) ? $book['description'] : ''); ?></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="price" class="form-label">السعر <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="number" class="form-control" id="price" name="price" step="0.01" min="0" value="<?php echo $book['price']; ?>" required>
                                    <span class="input-group-text">ر.س</span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="stock" class="form-label">الكمية المتوفرة</label>
                                <input type="number" class="form-control" id="stock" name="stock" min="0" value="<?php echo $book['stock']; ?>">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="publication_year" class="form-label">سنة النشر</label>
                                <input type="number" class="form-control" id="publication_year" name="publication_year" min="1800" max="<?php echo date('Y'); ?>" value="<?php echo $book['publication_year']; ?>">
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="pages" class="form-label">عدد الصفحات</label>
                                <input type="number" class="form-control" id="pages" name="pages" min="1" value="<?php echo $book['pages']; ?>">
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="mb-3">
                        <label for="cover_image" class="form-label">صورة الغلاف <?php echo $is_edit ? '' : '<span class="text-danger">*</span>'; ?></label>
                        <?php if (!empty($book['cover_image'])): ?>
                            <div class="mb-2">
                                <img src="<?php echo '../assets/uploads/' . $book['cover_image']; ?>" alt="Cover Image" class="img-thumbnail" style="max-height: 200px;">
                            </div>
                        <?php endif; ?>
                        <input type="file" class="form-control" id="cover_image" name="cover_image" accept="image/jpeg,image/png" <?php echo $is_edit ? '' : 'required'; ?>>
                        <small class="form-text text-muted">يفضل صورة بأبعاد 400×600 بكسل. الحد الأقصى للحجم: 2 ميجابايت.</small>
                    </div>
                </div>
            </div>
            
            <div class="d-flex justify-content-end mt-4">
                <a href="books.php" class="btn btn-secondary me-2">إلغاء</a>
                <button type="submit" class="btn btn-primary">
                    <?php echo $is_edit ? 'تحديث الرواية' : 'إضافة الرواية'; ?>
                </button>
            </div>
        </form>
    </div>
</div>

<?php 
require_once 'footer.php'; 
ob_end_flush(); // إنهاء وعرض التخزين المؤقت
?> 