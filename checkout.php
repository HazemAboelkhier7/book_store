<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/functions.php';

// Redirect if cart is empty
if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
    set_flash_message('السلة فارغة', 'warning');
    header('Location: cart.php');
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $errors = [];
    
    // Validate name
    $name = sanitize_input($_POST['name']);
    if (empty($name)) {
        $errors['name'] = 'الرجاء إدخال الاسم';
    }
    
    // Validate email
    $email = sanitize_input($_POST['email']);
    if (empty($email) || !is_valid_email($email)) {
        $errors['email'] = 'الرجاء إدخال بريد إلكتروني صحيح';
    }
    
    // Validate phone
    $phone = sanitize_input($_POST['phone']);
    if (empty($phone) || !is_valid_phone($phone)) {
        $errors['phone'] = 'الرجاء إدخال رقم هاتف صحيح (مثال: 0500000000)';
    }
    
    // Validate address
    $address = sanitize_input($_POST['address']);
    if (empty($address)) {
        $errors['address'] = 'الرجاء إدخال العنوان';
    }
    
    // If no errors, create order
    if (empty($errors)) {
        $customer_data = [
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'address' => $address
        ];
        
        $order_id = create_order($customer_data);
        
        if ($order_id) {
            set_flash_message('تم إنشاء الطلب بنجاح');
            header('Location: order.php?id=' . $order_id);
            exit;
        } else {
            set_flash_message('حدث خطأ أثناء إنشاء الطلب', 'danger');
        }
    }
}

require_once 'includes/header.php';

// Calculate totals
$subtotal = 0;
$shipping = 20; // Fixed shipping cost
$tax_rate = 0.15; // 15% VAT

foreach ($_SESSION['cart'] as $item) {
    $subtotal += $item['price'] * $item['quantity'];
}

$tax = $subtotal * $tax_rate;
$total = $subtotal + $tax + $shipping;
?>

<div class="container my-5">
    <h1 class="text-center text-primary mb-4">إتمام الطلب</h1>
    
    <div class="row">
        <!-- Order Summary -->
        <div class="col-lg-4 order-lg-2 mb-4">
            <div class="card bg-dark border-secondary sticky-lg-top" style="top: 2rem;">
                <div class="card-header bg-dark border-secondary">
                    <h5 class="card-title text-primary mb-0">ملخص الطلب</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-dark table-borderless mb-0">
                            <tbody>
                                <tr>
                                    <td class="text-light">المجموع الفرعي:</td>
                                    <td class="text-end text-warning"><?php echo format_price($subtotal); ?></td>
                                </tr>
                                <tr>
                                    <td class="text-light">ضريبة القيمة المضافة (15%):</td>
                                    <td class="text-end text-warning"><?php echo format_price($tax); ?></td>
                                </tr>
                                <tr>
                                    <td class="text-light">رسوم الشحن:</td>
                                    <td class="text-end text-warning"><?php echo format_price($shipping); ?></td>
                                </tr>
                                <tr class="border-top border-secondary">
                                    <td class="text-light"><strong>الإجمالي:</strong></td>
                                    <td class="text-end text-warning"><strong><?php echo format_price($total); ?></strong></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <!-- Order Items -->
            <div class="card bg-dark border-secondary mt-4">
                <div class="card-header bg-dark border-secondary">
                    <h5 class="card-title text-primary mb-0">الكتب المطلوبة</h5>
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush">
                        <?php foreach ($_SESSION['cart'] as $item): ?>
                            <div class="list-group-item bg-dark border-secondary">
                                <div class="d-flex">
                                    <img src="<?php echo !empty($item['cover_image']) ? 'assets/uploads/' . $item['cover_image'] : 'assets/img/book-placeholder.jpg'; ?>"
                                         alt="<?php echo htmlspecialchars($item['title']); ?>"
                                         class="me-3"
                                         style="width: 60px; height: 80px; object-fit: cover;">
                                    
                                    <div class="flex-grow-1">
                                        <h6 class="text-light mb-1"><?php echo htmlspecialchars($item['title']); ?></h6>
                                        <p class="text-muted small mb-1">الكمية: <?php echo $item['quantity']; ?></p>
                                        <p class="text-warning mb-0"><?php echo format_price($item['price'] * $item['quantity']); ?></p>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Checkout Form -->
        <div class="col-lg-8 order-lg-1">
            <div class="card bg-dark border-secondary">
                <div class="card-header bg-dark border-secondary">
                    <h5 class="card-title text-primary mb-0">معلومات الطلب</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="" class="row g-3" novalidate>
                        <!-- Name -->
                        <div class="col-md-6">
                            <label for="name" class="form-label text-light">الاسم الكامل</label>
                            <input type="text" class="form-control bg-dark text-light border-secondary <?php echo isset($errors['name']) ? 'is-invalid' : ''; ?>"
                                   id="name" name="name" value="<?php echo isset($name) ? htmlspecialchars($name) : ''; ?>"
                                   required>
                            <?php if (isset($errors['name'])): ?>
                                <div class="invalid-feedback"><?php echo $errors['name']; ?></div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Email -->
                        <div class="col-md-6">
                            <label for="email" class="form-label text-light">البريد الإلكتروني</label>
                            <input type="email" class="form-control bg-dark text-light border-secondary <?php echo isset($errors['email']) ? 'is-invalid' : ''; ?>"
                                   id="email" name="email" value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>"
                                   required>
                            <?php if (isset($errors['email'])): ?>
                                <div class="invalid-feedback"><?php echo $errors['email']; ?></div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Phone -->
                        <div class="col-md-6">
                            <label for="phone" class="form-label text-light">رقم الهاتف</label>
                            <div class="input-group">
                                <span class="input-group-text bg-dark text-light border-secondary">+966</span>
                                <input type="tel" class="form-control bg-dark text-light border-secondary <?php echo isset($errors['phone']) ? 'is-invalid' : ''; ?>"
                                       id="phone" name="phone" value="<?php echo isset($phone) ? htmlspecialchars($phone) : ''; ?>"
                                       placeholder="5xxxxxxxx" pattern="[0-9]{9}" maxlength="9"
                                       required>
                                <?php if (isset($errors['phone'])): ?>
                                    <div class="invalid-feedback"><?php echo $errors['phone']; ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <!-- Address -->
                        <div class="col-12">
                            <label for="address" class="form-label text-light">العنوان</label>
                            <textarea class="form-control bg-dark text-light border-secondary <?php echo isset($errors['address']) ? 'is-invalid' : ''; ?>"
                                      id="address" name="address" rows="3" required><?php echo isset($address) ? htmlspecialchars($address) : ''; ?></textarea>
                            <?php if (isset($errors['address'])): ?>
                                <div class="invalid-feedback"><?php echo $errors['address']; ?></div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Submit -->
                        <div class="col-12 mt-4">
                            <button type="submit" class="btn btn-primary btn-lg w-100">
                                <i class="fas fa-check me-2"></i>
                                تأكيد الطلب
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    @media (min-width: 992px) {
        .sticky-lg-top {
            position: sticky;
            top: 2rem;
        }
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
        color: var(--light-color);
    }
</style>

<script>
// Phone number formatting
document.getElementById('phone').addEventListener('input', function(e) {
    let value = e.target.value.replace(/\D/g, '');
    if (value.startsWith('05')) {
        value = value.substring(1);
    }
    e.target.value = value;
});

// Form validation
(function() {
    'use strict';
    
    var forms = document.querySelectorAll('.needs-validation');
    
    Array.prototype.slice.call(forms).forEach(function(form) {
        form.addEventListener('submit', function(event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            
            form.classList.add('was-validated');
        }, false);
    });
})();
</script>

<?php require_once 'includes/footer.php'; ?> 