<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

// Force UTF-8 encoding for Arabic text
header('Content-Type: text/html; charset=utf-8');
ini_set('default_charset', 'UTF-8');
mb_internal_encoding('UTF-8');
mb_http_output('UTF-8');

// Require authentication
get_auth()->requireLogin();

// Get current page for navigation highlighting
$current_page = basename($_SERVER['PHP_SELF']);

// Get site settings
$settings = get_settings();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($settings['site_name']); ?> - لوحة التحكم</title>
    
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
            font-family: 'Cairo', sans-serif;
            background-color: var(--dark-color);
            color: var(--light-color);
            min-height: 100vh;
        }
        
        .sidebar {
            background-color: var(--darker-color);
            border-left: 1px solid var(--secondary-color);
        }
        
        .nav-link {
            color: var(--light-color);
            border-radius: 0;
            padding: 0.75rem 1rem;
            transition: all 0.3s ease;
        }
        
        .nav-link:hover,
        .nav-link.active {
            background-color: var(--primary-color);
            color: var(--dark-color);
        }
        
        .nav-link i {
            width: 1.5rem;
            text-align: center;
            margin-left: 0.5rem;
        }
        
        .content {
            flex: 1;
            padding: 2rem;
        }
        
        .navbar {
            background-color: var(--darker-color);
            border-bottom: 1px solid var(--secondary-color);
        }
        
        .navbar-brand img {
            max-height: 40px;
        }
        
        .dropdown-menu {
            background-color: var(--darker-color);
            border-color: var(--secondary-color);
        }
        
        .dropdown-item {
            color: var(--light-color);
        }
        
        .dropdown-item:hover {
            background-color: var(--primary-color);
            color: var(--dark-color);
        }
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            .sidebar {
                width: 100%;
                position: static;
                height: auto;
            }
            
            .content {
                margin-right: 0;
                padding: 1rem;
            }
        }
    </style>
</head>
<body>
    <div class="d-flex">
        <!-- Sidebar -->
        <nav class="sidebar d-none d-md-block" style="width: 250px;">
            <div class="p-3">
                <div class="text-center mb-4">
                    <?php if (!empty($settings['site_logo'])): ?>
                        <img src="<?php echo UPLOAD_URL . $settings['site_logo']; ?>" alt="<?php echo htmlspecialchars($settings['site_name']); ?>" class="img-fluid" style="max-height: 60px;">
                    <?php else: ?>
                        <h4 class="mb-0"><?php echo htmlspecialchars($settings['site_name']); ?></h4>
                    <?php endif; ?>
                </div>
                
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page === 'dashboard.php' ? 'active' : ''; ?>" href="dashboard.php">
                            <i class="fas fa-tachometer-alt"></i> لوحة التحكم
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page === 'books.php' ? 'active' : ''; ?>" href="books.php">
                            <i class="fas fa-book"></i> إدارة الكتب
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page === 'orders.php' ? 'active' : ''; ?>" href="orders.php">
                            <i class="fas fa-shopping-cart"></i> إدارة الطلبات
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page === 'settings.php' ? 'active' : ''; ?>" href="settings.php">
                            <i class="fas fa-cog"></i> الإعدادات
                        </a>
                    </li>
                </ul>
            </div>
        </nav>
        
        <div class="flex-grow-1">
            <!-- Top Navbar -->
            <nav class="navbar navbar-expand-lg">
                <div class="container-fluid">
                    <button class="navbar-toggler d-md-none" type="button" data-bs-toggle="collapse" data-bs-target="#sidebarMenu">
                        <i class="fas fa-bars text-light"></i>
                    </button>
                    
                    <div class="ms-auto d-flex align-items-center">
                        <div class="dropdown">
                            <button class="btn btn-link text-light dropdown-toggle text-decoration-none" type="button" data-bs-toggle="dropdown">
                                <i class="fas fa-user-circle me-1"></i>
                                <?php echo htmlspecialchars($_SESSION['admin_name']); ?>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="settings.php"><i class="fas fa-cog me-2"></i> الإعدادات</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item text-danger" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i> تسجيل الخروج</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </nav>
            
            <!-- Mobile Sidebar -->
            <div class="collapse d-md-none" id="sidebarMenu">
                <nav class="bg-dark p-3">
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link <?php echo $current_page === 'dashboard.php' ? 'active' : ''; ?>" href="dashboard.php">
                                <i class="fas fa-tachometer-alt"></i> لوحة التحكم
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo $current_page === 'books.php' ? 'active' : ''; ?>" href="books.php">
                                <i class="fas fa-book"></i> إدارة الكتب
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo $current_page === 'orders.php' ? 'active' : ''; ?>" href="orders.php">
                                <i class="fas fa-shopping-cart"></i> إدارة الطلبات
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo $current_page === 'settings.php' ? 'active' : ''; ?>" href="settings.php">
                                <i class="fas fa-cog"></i> الإعدادات
                            </a>
                        </li>
                    </ul>
                </nav>
            </div>
            
            <!-- Main Content -->
            <main class="content"><?php
if (isset($_SESSION['admin_message'])): ?>
                <div class="alert alert-<?php echo $_SESSION['admin_message_type'] ?? 'info'; ?> alert-dismissible fade show">
                    <?php echo $_SESSION['admin_message']; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php
                unset($_SESSION['admin_message']);
                unset($_SESSION['admin_message_type']);
endif; ?> 