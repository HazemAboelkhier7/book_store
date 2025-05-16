<?php
require_once 'includes/config.php';
$settings = get_settings();

// Set proper HTTP response code
http_response_code(404);
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - الصفحة غير موجودة | <?php echo htmlspecialchars($settings['site_name']); ?></title>
    
    <!-- Bootstrap RTL CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts - Cairo -->
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
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .error-container {
            text-align: center;
            padding: 2rem;
        }
        
        .error-code {
            font-size: 8rem;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 1rem;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }
        
        .error-message {
            font-size: 2rem;
            margin-bottom: 2rem;
        }
        
        .btn-home {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            color: var(--dark-color);
            padding: 0.75rem 2rem;
            font-size: 1.2rem;
            transition: all 0.3s ease;
        }
        
        .btn-home:hover {
            background-color: var(--secondary-color);
            border-color: var(--secondary-color);
            transform: translateY(-2px);
        }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="error-code">404</div>
        <h1 class="error-message">عذراً، الصفحة غير موجودة</h1>
        <p class="mb-4">الصفحة التي تبحث عنها قد تكون محذوفة أو تم نقلها أو غير متوفرة مؤقتاً</p>
        <a href="index.php" class="btn btn-home">
            <i class="fas fa-home me-2"></i>
            العودة إلى الصفحة الرئيسية
        </a>
    </div>
    
    <!-- Bootstrap Bundle JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 