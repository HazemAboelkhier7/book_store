<?php
require_once 'db.php';

// Clean and sanitize input
function clean($string) {
    if (is_array($string)) {
        return array_map('clean', $string);
    }
    $string = trim($string);
    // Remove any invalid UTF-8 characters
    $string = mb_convert_encoding($string, 'UTF-8', 'UTF-8');
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

// Get settings from database
function get_settings() {
    static $settings = null;
    
    if ($settings === null) {
        $db = Database::getInstance();
        $result = $db->query("SELECT * FROM settings LIMIT 1");
        
        if ($result && $result->num_rows > 0) {
            $settings = $result->fetch_assoc();
        } else {
            // Return default settings if none found
            $settings = [
                'site_name' => 'متجر الكتب',
                'site_description' => 'متجر الكتب العربية الأول',
                'site_keywords' => 'كتب، روايات، أدب عربي، كتب إلكترونية',
                'site_email' => 'info@example.com',
                'site_phone' => '+1234567890',
                'site_address' => 'عنوان المتجر',
                'shipping_cost' => '30.00',
                'tax_rate' => '15.00',
                'currency_symbol' => 'ر.س',
                'items_per_page' => '12'
            ];
        }
    }
    
    return $settings;
}

// Check if admin is logged in
function is_admin() {
    return isset($_SESSION['admin_id']) && !empty($_SESSION['admin_id']);
}

// Redirect to URL
function redirect($url) {
    header("Location: $url");
    exit;
}

// Display error message
function show_error($message) {
    return '<div class="alert alert-danger">' . $message . '</div>';
}

// Display success message
function show_success($message) {
    return '<div class="alert alert-success">' . $message . '</div>';
}

// Generate random string
function random_string($length = 10) {
    return substr(str_shuffle("0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, $length);
}

// Format price with currency symbol
function format_price($price) {
    $settings = get_settings();
    return $settings['currency_symbol'] . ' ' . number_format($price, 2);
}

// Initialize shopping cart
function init_cart() {
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }
}

// Get cart items count
function get_cart_count() {
    init_cart();
    $count = 0;
    foreach ($_SESSION['cart'] as $item) {
        $count += $item['quantity'];
    }
    return $count;
}

// Get cart total
function get_cart_total() {
    init_cart();
    $total = 0;
    foreach ($_SESSION['cart'] as $item) {
        $total += $item['price'] * $item['quantity'];
    }
    return $total;
}

// Get book details by ID
function get_book($id) {
    $conn = get_db();
    $id = (int)$id;
    
    $query = "SELECT b.*, a.name as author_name, c.name as category_name 
              FROM books b 
              LEFT JOIN authors a ON b.author_id = a.id 
              LEFT JOIN categories c ON b.category_id = c.id 
              WHERE b.id = ?";
              
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    return $result->fetch_assoc();
}

// Get books with filters
function get_books($filters = [], $limit = 12, $offset = 0) {
    $conn = get_db();
    
    $query = "SELECT b.*, a.name as author_name, c.name as category_name 
              FROM books b 
              LEFT JOIN authors a ON b.author_id = a.id 
              LEFT JOIN categories c ON b.category_id = c.id 
              WHERE 1=1";
    $params = [];
    $types = '';
    
    if (!empty($filters['category_id'])) {
        $query .= " AND b.category_id = ?";
        $params[] = (int)$filters['category_id'];
        $types .= 'i';
    }
    
    if (!empty($filters['author_id'])) {
        $query .= " AND b.author_id = ?";
        $params[] = (int)$filters['author_id'];
        $types .= 'i';
    }
    
    if (!empty($filters['search'])) {
        $search = '%' . $filters['search'] . '%';
        $query .= " AND (b.title LIKE ? OR b.description LIKE ? OR a.name LIKE ?)";
        $params[] = $search;
        $params[] = $search;
        $params[] = $search;
        $types .= 'sss';
    }
    
    if (!empty($filters['min_price'])) {
        $query .= " AND b.price >= ?";
        $params[] = (float)$filters['min_price'];
        $types .= 'd';
    }
    
    if (!empty($filters['max_price'])) {
        $query .= " AND b.price <= ?";
        $params[] = (float)$filters['max_price'];
        $types .= 'd';
    }
    
    if (!empty($filters['sort'])) {
        switch ($filters['sort']) {
            case 'price_asc':
                $query .= " ORDER BY b.price ASC";
                break;
            case 'price_desc':
                $query .= " ORDER BY b.price DESC";
                break;
            case 'newest':
                $query .= " ORDER BY b.created_at DESC";
                break;
            case 'title':
                $query .= " ORDER BY b.title ASC";
                break;
            default:
                $query .= " ORDER BY b.created_at DESC";
        }
    } else {
        $query .= " ORDER BY b.created_at DESC";
    }
    
    $query .= " LIMIT ? OFFSET ?";
    $params[] = $limit;
    $params[] = $offset;
    $types .= 'ii';
    
    $stmt = $conn->prepare($query);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    
    return $result->fetch_all(MYSQLI_ASSOC);
}

// Get total books count with filters
function get_books_count($filters = []) {
    $conn = get_db();
    
    $query = "SELECT COUNT(*) as total FROM books b 
              LEFT JOIN authors a ON b.author_id = a.id 
              WHERE 1=1";
    $params = [];
    $types = '';
    
    if (!empty($filters['category_id'])) {
        $query .= " AND b.category_id = ?";
        $params[] = (int)$filters['category_id'];
        $types .= 'i';
    }
    
    if (!empty($filters['author_id'])) {
        $query .= " AND b.author_id = ?";
        $params[] = (int)$filters['author_id'];
        $types .= 'i';
    }
    
    if (!empty($filters['search'])) {
        $search = '%' . $filters['search'] . '%';
        $query .= " AND (b.title LIKE ? OR b.description LIKE ? OR a.name LIKE ?)";
        $params[] = $search;
        $params[] = $search;
        $params[] = $search;
        $types .= 'sss';
    }
    
    if (!empty($filters['min_price'])) {
        $query .= " AND b.price >= ?";
        $params[] = (float)$filters['min_price'];
        $types .= 'd';
    }
    
    if (!empty($filters['max_price'])) {
        $query .= " AND b.price <= ?";
        $params[] = (float)$filters['max_price'];
        $types .= 'd';
    }
    
    $stmt = $conn->prepare($query);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    return $row['total'];
}

// Add item to cart
function add_to_cart($book_id, $quantity = 1) {
    $conn = get_db();
    $book_id = (int)$book_id;
    $quantity = max(1, (int)$quantity);
    
    // Get book details
    $query = "SELECT b.*, a.name as author_name 
              FROM books b 
              LEFT JOIN authors a ON b.author_id = a.id 
              WHERE b.id = ? AND b.stock > 0";
              
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $book_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($book = $result->fetch_assoc()) {
        init_cart();
        
        // Check if book already in cart
        if (isset($_SESSION['cart'][$book_id])) {
            $new_quantity = $_SESSION['cart'][$book_id]['quantity'] + $quantity;
            
            // Check stock
            if ($new_quantity > $book['stock']) {
                $new_quantity = $book['stock'];
            }
            
            $_SESSION['cart'][$book_id]['quantity'] = $new_quantity;
        } else {
            // Add new book to cart
            $_SESSION['cart'][$book_id] = [
                'id' => $book_id,
                'title' => $book['title'],
                'author_name' => $book['author_name'],
                'cover_image' => $book['cover_image'],
                'price' => $book['price'],
                'quantity' => min($quantity, $book['stock']),
                'stock' => $book['stock']
            ];
        }
        
        return true;
    }
    
    return false;
}

// Update cart item quantity
function update_cart_quantity($book_id, $quantity) {
    $conn = get_db();
    $book_id = (int)$book_id;
    $quantity = max(0, (int)$quantity);
    
    if (isset($_SESSION['cart'][$book_id])) {
        // Get current stock
        $query = "SELECT stock FROM books WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param('i', $book_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($book = $result->fetch_assoc()) {
            if ($quantity > $book['stock']) {
                $quantity = $book['stock'];
            }
            
            if ($quantity > 0) {
                $_SESSION['cart'][$book_id]['quantity'] = $quantity;
                $_SESSION['cart'][$book_id]['stock'] = $book['stock'];
            } else {
                unset($_SESSION['cart'][$book_id]);
            }
            
            return true;
        }
    }
    
    return false;
}

// Clear cart
function clear_cart() {
    unset($_SESSION['cart']);
}

// Create order
function create_order($customer_data) {
    if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
        return false;
    }
    
    $conn = get_db();
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Calculate totals
        $subtotal = 0;
        foreach ($_SESSION['cart'] as $item) {
            $subtotal += $item['price'] * $item['quantity'];
        }
        
        $tax_rate = 0.15; // 15% VAT
        $shipping = 20; // Fixed shipping cost
        $tax = $subtotal * $tax_rate;
        $total = $subtotal + $tax + $shipping;
        
        // Insert order
        $query = "INSERT INTO orders (customer_name, customer_email, customer_phone, customer_address, 
                                    subtotal, tax, shipping, total, status) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending')";
                  
        $stmt = $conn->prepare($query);
        $stmt->bind_param('ssssdddd', 
            $customer_data['name'],
            $customer_data['email'],
            $customer_data['phone'],
            $customer_data['address'],
            $subtotal,
            $tax,
            $shipping,
            $total
        );
        $stmt->execute();
        
        $order_id = $conn->insert_id;
        
        // Insert order items and update stock
        $query = "INSERT INTO order_items (order_id, book_id, quantity, price) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($query);
        
        $update_stock = "UPDATE books SET stock = stock - ? WHERE id = ? AND stock >= ?";
        $stmt_stock = $conn->prepare($update_stock);
        
        foreach ($_SESSION['cart'] as $item) {
            // Add order item
            $stmt->bind_param('iiid', $order_id, $item['id'], $item['quantity'], $item['price']);
            $stmt->execute();
            
            // Update stock
            $stmt_stock->bind_param('iii', $item['quantity'], $item['id'], $item['quantity']);
            $stmt_stock->execute();
            
            if ($stmt_stock->affected_rows === 0) {
                throw new Exception('الكمية المطلوبة غير متوفرة في المخزون');
            }
        }
        
        // Clear cart
        $_SESSION['cart'] = [];
        
        // Commit transaction
        $conn->commit();
        
        return $order_id;
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        return false;
    }
}

// Get order details
function get_order($order_id) {
    $conn = get_db();
    $order_id = (int)$order_id;
    
    // Get order info
    $query = "SELECT * FROM orders WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $order_id);
    $stmt->execute();
    $order = $stmt->get_result()->fetch_assoc();
    
    if (!$order) {
        return false;
    }
    
    // Get order items
    $query = "SELECT oi.*, b.title, b.cover_image 
              FROM order_items oi 
              JOIN books b ON oi.book_id = b.id 
              WHERE oi.order_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $order_id);
    $stmt->execute();
    $order['items'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    return $order;
}

// Validate email
function is_valid_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

// Validate phone number (Saudi format)
function is_valid_phone($phone) {
    // إزالة أي رموز أو فراغات
    $phone = preg_replace('/[^0-9]/', '', $phone);
    // دعم صيغ: 05xxxxxxxx, 5xxxxxxxx, 9665xxxxxxxx, +9665xxxxxxxx
    if (preg_match('/^(05|5)[0-9]{8}$/', $phone)) {
        return true;
    }
    if (preg_match('/^(9665)[0-9]{8}$/', $phone)) {
        return true;
    }
    return false;
}

// Generate pagination links
function generate_pagination($current_page, $total_pages, $url_pattern) {
    $pagination = '';
    
    if ($total_pages > 1) {
        $pagination .= '<nav aria-label="Page navigation"><ul class="pagination justify-content-center">';
        
        // Previous button
        if ($current_page > 1) {
            $pagination .= sprintf(
                '<li class="page-item"><a class="page-link" href="%s">السابق</a></li>',
                sprintf($url_pattern, $current_page - 1)
            );
        } else {
            $pagination .= '<li class="page-item disabled"><span class="page-link">السابق</span></li>';
        }
        
        // Page numbers
        for ($i = max(1, $current_page - 2); $i <= min($total_pages, $current_page + 2); $i++) {
            if ($i == $current_page) {
                $pagination .= sprintf(
                    '<li class="page-item active"><span class="page-link">%d</span></li>',
                    $i
                );
            } else {
                $pagination .= sprintf(
                    '<li class="page-item"><a class="page-link" href="%s">%d</a></li>',
                    sprintf($url_pattern, $i),
                    $i
                );
            }
        }
        
        // Next button
        if ($current_page < $total_pages) {
            $pagination .= sprintf(
                '<li class="page-item"><a class="page-link" href="%s">التالي</a></li>',
                sprintf($url_pattern, $current_page + 1)
            );
        } else {
            $pagination .= '<li class="page-item disabled"><span class="page-link">التالي</span></li>';
        }
        
        $pagination .= '</ul></nav>';
    }
    
    return $pagination;
}

// Sanitize and validate input
function sanitize_input($input) {
    return htmlspecialchars(trim($input));
}

// Set flash message
function set_flash_message($message, $type = 'success') {
    $_SESSION['flash_message'] = $message;
    $_SESSION['flash_type'] = $type;
}

// Get flash message
function get_flash_message() {
    if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message'];
        $type = $_SESSION['flash_type'];
        
        unset($_SESSION['flash_message']);
        unset($_SESSION['flash_type']);
        
        return [
            'message' => $message,
            'type' => $type
        ];
    }
    
    return null;
}

// Remove item from cart
function remove_from_cart($book_id) {
    $book_id = (int)$book_id;
    
    if (isset($_SESSION['cart'][$book_id])) {
        unset($_SESSION['cart'][$book_id]);
        return true;
    }
    
    return false;
}

// Handle file upload
function handle_upload($file, $allowed_types = ['image/jpeg', 'image/png'], $max_size = 2097152) {
    try {
        if (!isset($file['error']) || is_array($file['error'])) {
            throw new Exception('Invalid file parameters');
        }
        
        switch ($file['error']) {
            case UPLOAD_ERR_OK:
                break;
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                throw new Exception('File size exceeds limit');
            case UPLOAD_ERR_NO_FILE:
                throw new Exception('No file uploaded');
            default:
                throw new Exception('Unknown upload error');
        }
        
        if ($file['size'] > $max_size) {
            throw new Exception('File size exceeds limit');
        }
        
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime_type = $finfo->file($file['tmp_name']);
        
        if (!in_array($mime_type, $allowed_types)) {
            throw new Exception('Invalid file type');
        }
        
        $extension = array_search($mime_type, [
            'jpg' => 'image/jpeg',
            'png' => 'image/png'
        ], true);
        
        if ($extension === false) {
            throw new Exception('Invalid file extension');
        }
        
        $filename = sprintf(
            '%s_%s.%s',
            uniqid('file_', true),
            bin2hex(random_bytes(8)),
            $extension
        );
        
        $upload_path = UPLOAD_PATH . $filename;
        
        if (!move_uploaded_file($file['tmp_name'], $upload_path)) {
            throw new Exception('Failed to move uploaded file');
        }
        
        return $filename;
        
    } catch (Exception $e) {
        error_log('File Upload Error: ' . $e->getMessage());
        return false;
    }
}

// Delete file
function delete_file($filename) {
    $file_path = UPLOAD_PATH . $filename;
    
    if (file_exists($file_path)) {
        return unlink($file_path);
    }
    
    return false;
}

// Generate order number
function generate_order_number($order_id) {
    return str_pad($order_id, 6, '0', STR_PAD_LEFT);
}

// Get order status in Arabic
function get_order_status_ar($status) {
    $statuses = [
        'pending' => 'قيد المراجعة',
        'processing' => 'قيد التجهيز',
        'shipped' => 'تم الشحن',
        'delivered' => 'تم التوصيل',
        'cancelled' => 'ملغي'
    ];
    
    return $statuses[$status] ?? 'غير معروف';
}

// Get order status class
function get_order_status_class($status) {
    $classes = [
        'pending' => 'bg-warning text-dark',
        'processing' => 'bg-info',
        'shipped' => 'bg-primary',
        'delivered' => 'bg-success',
        'cancelled' => 'bg-danger'
    ];
    
    return $classes[$status] ?? 'bg-secondary';
}

// Format date in Arabic
function format_date($date) {
    return date('d/m/Y h:i A', strtotime($date));
}

// Calculate order totals
function calculate_order_totals($subtotal) {
    $settings = get_settings();
    
    $shipping = floatval($settings['shipping_cost']);
    $tax_rate = floatval($settings['tax_rate']) / 100;
    $tax = $subtotal * $tax_rate;
    $total = $subtotal + $shipping + $tax;
    
    return [
        'subtotal' => $subtotal,
        'shipping' => $shipping,
        'tax' => $tax,
        'total' => $total
    ];
}

// Validate required fields
function validate_required($data, $fields) {
    $errors = [];
    
    foreach ($fields as $field => $label) {
        if (empty($data[$field])) {
            $errors[] = "حقل {$label} مطلوب";
        }
    }
    
    return $errors;
}

// Send email
function send_email($to, $subject, $message) {
    $settings = get_settings();
    $headers = [
        'MIME-Version: 1.0',
        'Content-type: text/html; charset=utf-8',
        'From: ' . $settings['site_name'] . ' <' . $settings['site_email'] . '>',
        'X-Mailer: PHP/' . phpversion()
    ];
    
    return mail($to, $subject, $message, implode("\r\n", $headers));
} 