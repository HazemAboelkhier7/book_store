<?php
// Force UTF-8 encoding for Arabic text
ini_set('default_charset', 'UTF-8');
mb_internal_encoding('UTF-8');
mb_http_output('UTF-8');

require_once 'header.php';
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Check admin session
if (!isset($_SESSION['admin_id'])) {
    header('Location: index.php');
    exit;
}

$conn = get_db();
$errors = [];
$success = false;

// Get current settings
$stmt = $conn->prepare("SELECT * FROM settings LIMIT 1");
$stmt->execute();
$result = $stmt->get_result();

if ($result && $result->num_rows > 0) {
    $settings = $result->fetch_assoc();
} else {
    // Create default settings if not exist
    $default_settings = [
        'site_name' => 'متجر الكتب',
        'site_description' => 'متجر الكتب العربية الأول',
        'site_keywords' => 'كتب، روايات، أدب عربي، كتب إلكترونية',
        'site_email' => 'info@example.com',
        'site_phone' => '+1234567890',
        'site_address' => 'عنوان المتجر',
        'facebook_url' => '',
        'twitter_url' => '',
        'instagram_url' => '',
        'shipping_cost' => '30.00',
        'tax_rate' => '15.00',
        'currency_symbol' => 'ر.س',
        'items_per_page' => '12'
    ];
    
    $sql = "INSERT INTO settings (" . implode(',', array_keys($default_settings)) . ") VALUES ('" . implode("','", array_values($default_settings)) . "')";
    $conn->query($sql);
    $settings = $default_settings;
    $settings['id'] = $conn->insert_id;
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate and sanitize input
    $site_name = clean($_POST['site_name'] ?? '');
    $site_description = clean($_POST['site_description'] ?? '');
    $site_keywords = clean($_POST['site_keywords'] ?? '');
    $site_phone = clean($_POST['site_phone'] ?? '');
    $site_address = clean($_POST['site_address'] ?? '');
    $facebook_url = filter_var($_POST['facebook_url'] ?? '', FILTER_SANITIZE_URL);
    $twitter_url = filter_var($_POST['twitter_url'] ?? '', FILTER_SANITIZE_URL);
    $instagram_url = filter_var($_POST['instagram_url'] ?? '', FILTER_SANITIZE_URL);
    $shipping_cost = filter_var($_POST['shipping_cost'] ?? '0', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    $tax_rate = filter_var($_POST['tax_rate'] ?? '0', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    $currency_symbol = clean($_POST['currency_symbol'] ?? 'ر.س');
    $items_per_page = filter_var($_POST['items_per_page'] ?? '12', FILTER_SANITIZE_NUMBER_INT);
    
    // Validate required fields
    if (empty($site_name)) {
        $errors[] = 'يرجى إدخال اسم الموقع';
    }
    
    // Handle logo upload
    $logo_image = $_FILES['site_logo'] ?? null;
    $current_logo = $settings['site_logo'] ?? '';
    
    if ($logo_image && $logo_image['size'] > 0) {
        if (!file_exists(UPLOAD_PATH)) {
            mkdir(UPLOAD_PATH, 0777, true);
        }
        
        $allowed_types = ['image/jpeg', 'image/png', 'image/jpg'];
        $max_size = 2 * 1024 * 1024; // 2MB
        
        if (!in_array($logo_image['type'], $allowed_types)) {
            $errors[] = 'يجب أن يكون شعار الموقع بصيغة JPG أو PNG';
        } elseif ($logo_image['size'] > $max_size) {
            $errors[] = 'يجب أن يكون حجم شعار الموقع أقل من 2 ميجابايت';
        } else {
            $extension = pathinfo($logo_image['name'], PATHINFO_EXTENSION);
            $filename = 'logo_' . time() . '.' . $extension;
            
            if (move_uploaded_file($logo_image['tmp_name'], UPLOAD_PATH . $filename)) {
                if (!empty($current_logo) && file_exists(UPLOAD_PATH . $current_logo)) {
                    unlink(UPLOAD_PATH . $current_logo);
                }
                $current_logo = $filename;
            } else {
                $errors[] = 'فشل في تحميل شعار الموقع';
            }
        }
    }
    
    // Update settings if no errors
    if (empty($errors)) {
        $sql = "UPDATE settings SET 
                site_name = ?, 
                site_description = ?,
                site_keywords = ?,
                site_phone = ?,
                site_address = ?,
                facebook_url = ?,
                twitter_url = ?,
                instagram_url = ?,
                shipping_cost = ?,
                tax_rate = ?,
                currency_symbol = ?,
                items_per_page = ?,
                site_logo = ?
                WHERE id = ?";
                
        $stmt = $conn->prepare($sql);
        $stmt->bind_param(
            "sssssssssddssi",
            $site_name,
            $site_description,
            $site_keywords,
            $site_phone,
            $site_address,
            $facebook_url,
            $twitter_url,
            $instagram_url,
            $shipping_cost,
            $tax_rate,
            $currency_symbol,
            $items_per_page,
            $current_logo,
            $settings['id']
        );
        
        if ($stmt->execute()) {
            $_SESSION['admin_message'] = 'تم تحديث إعدادات الموقع بنجاح';
            $_SESSION['admin_message_type'] = 'success';
            $success = true;
            
            // Refresh settings
            $settings = array_merge($settings, [
                'site_name' => $site_name,
                'site_description' => $site_description,
                'site_keywords' => $site_keywords,
                'site_phone' => $site_phone,
                'site_address' => $site_address,
                'facebook_url' => $facebook_url,
                'twitter_url' => $twitter_url,
                'instagram_url' => $instagram_url,
                'shipping_cost' => $shipping_cost,
                'tax_rate' => $tax_rate,
                'currency_symbol' => $currency_symbol,
                'items_per_page' => $items_per_page,
                'site_logo' => $current_logo
            ]);
        } else {
            $errors[] = 'فشل في تحديث إعدادات الموقع: ' . $conn->error;
        }
    }
}
?>

<style>
    .form-control.bg-darker,
    .form-control[type="file"],
    textarea.form-control.bg-darker {
        color: #ffd700 !important;
        border-color: #ffd700 !important;
        background-color: #232323 !important;
    }
    .form-control.bg-darker:focus,
    textarea.form-control.bg-darker:focus {
        box-shadow: 0 0 0 0.2rem rgba(255, 215, 0, 0.25) !important;
        border-color: #ffd700 !important;
    }
    label.form-label.text-light {
        color: #ffd700 !important;
    }
</style>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <h2 class="mb-0 text-light">إعدادات الموقع</h2>
            </div>
        </div>
    </div>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger bg-danger bg-opacity-10 border-danger">
            <ul class="mb-0">
                <?php foreach ($errors as $error): ?>
                    <li><?php echo $error; ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-lg-8">
            <form method="post" enctype="multipart/form-data">
                <div class="card bg-dark border-secondary mb-4">
                    <div class="card-header bg-dark border-secondary">
                        <h5 class="mb-0 text-light">الإعدادات الأساسية</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="site_name" class="form-label text-light">اسم الموقع <span class="text-danger">*</span></label>
                                <input type="text" class="form-control bg-darker border-secondary text-light" id="site_name" name="site_name" 
                                       value="<?php echo htmlspecialchars($settings['site_name']); ?>" required>
                            </div>
                            
                            <div class="col-12 mb-3">
                                <label for="site_description" class="form-label text-light">وصف الموقع</label>
                                <textarea class="form-control bg-darker border-secondary text-light" id="site_description" name="site_description" 
                                          rows="3"><?php echo htmlspecialchars($settings['site_description']); ?></textarea>
                            </div>
                            
                            <div class="col-12 mb-3">
                                <label for="site_keywords" class="form-label text-light">الكلمات المفتاحية</label>
                                <input type="text" class="form-control bg-darker border-secondary text-light" id="site_keywords" name="site_keywords" 
                                       value="<?php echo htmlspecialchars($settings['site_keywords']); ?>">
                                <small class="text-muted">افصل بين الكلمات بفاصلة</small>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card bg-dark border-secondary mb-4">
                    <div class="card-header bg-dark border-secondary">
                        <h5 class="mb-0 text-light">شعار الموقع</h5>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($settings['site_logo'])): ?>
                            <div class="mb-3">
                                <img src="<?php echo UPLOAD_URL . $settings['site_logo']; ?>" alt="شعار الموقع" class="img-thumbnail bg-darker border-secondary" style="max-height: 100px;">
                            </div>
                        <?php endif; ?>
                        
                        <div class="mb-3">
                            <label for="site_logo" class="form-label text-light">تحميل شعار جديد</label>
                            <input type="file" class="form-control bg-darker border-secondary text-light" id="site_logo" name="site_logo" accept="image/jpeg,image/png">
                            <small class="text-muted">يفضل شعار بخلفية شفافة بأبعاد 200×60 بكسل. الحد الأقصى للحجم: 2 ميجابايت.</small>
                        </div>
                    </div>
                </div>

                <div class="text-end">
                    <button type="submit" class="btn btn-primary btn-lg px-5">
                        <i class="fas fa-save me-2"></i> حفظ الإعدادات
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?> 