<?php
// Force UTF-8 encoding for Arabic text
header('Content-Type: text/html; charset=utf-8');
ini_set('default_charset', 'UTF-8');
mb_internal_encoding('UTF-8');
mb_http_output('UTF-8');

require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

// Redirect to dashboard if already logged in
if (get_auth()->isLoggedIn()) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
$username = '';

// Process login form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = clean($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error = 'يرجى إدخال اسم المستخدم وكلمة المرور';
    } else {
        if (get_auth()->login($username, $password)) {
            // Redirect to dashboard on successful login
            header('Location: dashboard.php');
            exit;
        } else {
            $error = 'بيانات تسجيل الدخول غير صحيحة';
            // Add delay to prevent brute force
            sleep(1);
        }
    }
}

// Get site settings
$settings = get_settings();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تسجيل الدخول | <?php echo htmlspecialchars($settings['site_name']); ?></title>
    
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
            background-color: var(--dark-color);
            color: var(--light-color);
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .login-card {
            max-width: 400px;
            width: 100%;
            background-color: var(--darker-color);
            border: 1px solid var(--secondary-color);
        }
        
        .card-header {
            background-color: var(--primary-color);
            color: var(--dark-color);
            text-align: center;
            padding: 1.5rem;
            border-bottom: 2px solid var(--secondary-color);
        }
        
        .form-control {
            background-color: var(--dark-color);
            border-color: var(--secondary-color);
            color: var(--light-color);
        }
        
        .form-control:focus {
            background-color: var(--dark-color);
            border-color: var(--primary-color);
            color: var(--light-color);
            box-shadow: 0 0 0 0.25rem rgba(255, 215, 0, 0.25);
        }
        
        .input-group-text {
            background-color: var(--dark-color);
            border-color: var(--secondary-color);
            color: var(--primary-color);
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            color: var(--dark-color);
        }
        
        .btn-primary:hover {
            background-color: var(--secondary-color);
            border-color: var(--secondary-color);
        }
        
        .card-footer {
            background-color: var(--darker-color);
            border-top: 1px solid var(--secondary-color);
        }
        
        .card-footer a {
            color: var(--primary-color);
            text-decoration: none;
        }
        
        .card-footer a:hover {
            color: var(--secondary-color);
        }
        
        .alert-danger {
            background-color: rgba(220, 53, 69, 0.2);
            border-color: #dc3545;
            color: #dc3545;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card login-card">
                    <div class="card-header">
                        <h4 class="mb-0">
                            <?php if (!empty($settings['site_logo'])): ?>
                                <img src="<?php echo UPLOAD_URL . $settings['site_logo']; ?>" alt="<?php echo htmlspecialchars($settings['site_name']); ?>" style="max-height: 40px;">
                            <?php else: ?>
                                <?php echo htmlspecialchars($settings['site_name']); ?>
                            <?php endif; ?>
                        </h4>
                        <p class="mb-0">لوحة التحكم</p>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($error)): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>
                        
                        <form method="post" autocomplete="off">
                            <div class="mb-3">
                                <label for="username" class="form-label">اسم المستخدم</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-user"></i></span>
                                    <input type="text" class="form-control" id="username" name="username" required 
                                           value="<?php echo htmlspecialchars($username); ?>">
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="password" class="form-label">كلمة المرور</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                    <input type="password" class="form-control" id="password" name="password" required>
                                </div>
                            </div>
                            
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-sign-in-alt me-2"></i> تسجيل الدخول
                            </button>
                        </form>
                    </div>
                    <div class="card-footer text-center">
                        <a href="../index.php">
                            <i class="fas fa-home me-1"></i> العودة إلى الموقع
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 