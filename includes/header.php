<?php
// Force UTF-8 encoding for Arabic text
ini_set('default_charset', 'UTF-8');
mb_internal_encoding('UTF-8');
mb_http_output('UTF-8');
header('Content-Type: text/html; charset=UTF-8');

require_once 'config.php';
init_cart();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_NAME_DB; ?></title>
    
    <!-- Bootstrap RTL -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Google Fonts - Cairo -->
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap" rel="stylesheet">
    
    <style>
        /* Additional styles */
        body {
            font-family: 'Cairo', sans-serif;
        }
        
        .navbar {
            background-color: var(--darker-color);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        
        .navbar-brand {
            color: var(--primary-color);
            font-weight: 700;
            font-size: 1.5rem;
        }
        
        .navbar-brand:hover {
            color: var(--primary-color);
        }
        
        .nav-link {
            color: var(--light-color);
            transition: color 0.3s ease;
        }
        
        .nav-link:hover {
            color: var(--primary-color);
        }
        
        .nav-link.active {
            color: var(--primary-color) !important;
        }
        
        .cart-count {
            position: absolute;
            top: -8px;
            left: -8px;
            background-color: var(--primary-color);
            color: var(--dark-color);
            font-size: 0.75rem;
            font-weight: 600;
            padding: 0.25rem 0.5rem;
            border-radius: 1rem;
            min-width: 1.5rem;
            text-align: center;
        }
        
        .search-form {
            max-width: 400px;
        }
        
        .search-form .form-control {
            border-start-end-radius: 0;
            border-end-end-radius: 0;
        }
        
        .search-form .btn {
            border-start-start-radius: 0;
            border-end-start-radius: 0;
        }
        
        @media (max-width: 991.98px) {
            .search-form {
                max-width: 100%;
                margin: 1rem 0;
            }
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg sticky-top">
        <div class="container">
            <!-- Brand -->
            <a class="navbar-brand" href="index.php">
                <?php if (!empty(SITE_LOGO)): ?>
                    <img src="<?php echo SITE_LOGO; ?>" alt="<?php echo SITE_NAME_DB; ?>" height="40">
                <?php else: ?>
                    <?php echo SITE_NAME_DB; ?>
                <?php endif; ?>
            </a>
            
            <!-- Mobile Toggle -->
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent">
                <i class="fas fa-bars text-light"></i>
            </button>
            
            <!-- Navbar Content -->
            <div class="collapse navbar-collapse" id="navbarContent">
                <!-- Search Form -->
                <form action="search.php" method="GET" class="d-flex mx-auto search-form">
                    <input type="text" name="q" class="form-control" placeholder="ابحث عن كتاب..."
                           value="<?php echo isset($_GET['q']) ? htmlspecialchars($_GET['q']) : ''; ?>">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i>
                    </button>
                </form>
                
                <!-- Navigation -->
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link<?php echo basename($_SERVER['PHP_SELF']) === 'index.php' ? ' active' : ''; ?>" href="index.php">
                            <i class="fas fa-home me-1"></i>
                            الرئيسية
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link position-relative<?php echo basename($_SERVER['PHP_SELF']) === 'cart.php' ? ' active' : ''; ?>" href="cart.php">
                            <i class="fas fa-shopping-cart me-1"></i>
                            السلة
                            <?php if (isset($_SESSION['cart']) && count($_SESSION['cart']) > 0): ?>
                                <span class="cart-count"><?php echo get_cart_count(); ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    
    <!-- Flash Messages -->
    <?php if ($flash = get_flash_message()): ?>
        <div class="container mt-3">
            <div class="alert alert-<?php echo $flash['type']; ?> alert-dismissible fade show">
                <?php echo $flash['message']; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        </div>
    <?php endif; ?>
    
    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 