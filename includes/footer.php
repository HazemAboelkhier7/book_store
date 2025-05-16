    </main>
    <footer class="bg-darker mt-5 py-5">
        <div class="container">
            <div class="row g-4">
                <!-- About Section -->
                <div class="col-md-4">
                    <h5 class="text-dark mb-3">عن المتجر</h5>
                    <p class="text-dark mb-0">
                        متجر متخصص في بيع الكتب والروايات العربية والعالمية المترجمة.
                        نوفر لكم أفضل وأحدث الإصدارات من دور النشر المختلفة.
                    </p>
                </div>
                
                <!-- Quick Links -->
                <div class="col-md-4">
                    <h5 class="text-dark mb-3">روابط سريعة</h5>
                    <ul class="list-unstyled">
                        <li class="mb-2">
                            <a href="index.php" class="text-dark text-decoration-none hover-primary">
                                <i class="fas fa-home me-2"></i>
                                الرئيسية
                            </a>
                        </li>
                        <li class="mb-2">
                            <a href="cart.php" class="text-dark text-decoration-none hover-primary">
                                <i class="fas fa-shopping-cart me-2"></i>
                                سلة المشتريات
                            </a>
                        </li>
                        <?php if (isset($_SESSION['admin_id'])): ?>
                            <li class="mb-2">
                                <a href="admin/index.php" class="text-dark text-decoration-none hover-primary">
                                    <i class="fas fa-cog me-2"></i>
                                    لوحة التحكم
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </div>
                
                <!-- Contact Info -->
                <div class="col-md-4">
                    <h5 class="text-dark mb-3">معلومات التواصل</h5>
                    <ul class="list-unstyled">
                        <li class="mb-2 text-dark">
                            <i class="fas fa-phone me-2 text-secondary"></i>
                            +966 50 000 0000
                        </li>
                        <li class="mb-2 text-dark">
                            <i class="fas fa-envelope me-2 text-secondary"></i>
                            info@bookstore.com
                        </li>
                        <li class="mb-2 text-dark">
                            <i class="fas fa-map-marker-alt me-2 text-secondary"></i>
                            الرياض، المملكة العربية السعودية
                        </li>
                    </ul>
                    
                    <!-- Social Media -->
                    <div class="mt-4">
                        <a href="#" class="text-dark me-3 fs-5 hover-primary">
                            <i class="fab fa-facebook"></i>
                        </a>
                        <a href="#" class="text-dark me-3 fs-5 hover-primary">
                            <i class="fab fa-twitter"></i>
                        </a>
                        <a href="#" class="text-dark me-3 fs-5 hover-primary">
                            <i class="fab fa-instagram"></i>
                        </a>
                        <a href="#" class="text-dark fs-5 hover-primary">
                            <i class="fab fa-whatsapp"></i>
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Copyright -->
            <div class="row mt-4">
                <div class="col-12">
                    <hr class="border-secondary">
                    <p class="text-center text-dark mb-0">
                        جميع الحقوق محفوظة &copy; <?php echo date('Y'); ?> متجر الكتب
                    </p>
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- AOS Animation Library -->
    <script src="https://unpkg.com/aos@next/dist/aos.js"></script>
    
    <!-- Custom JavaScript -->
    <script>
        AOS.init();
    </script>

    <style>
        .bg-darker {
            background-color: var(--darker-color);
        }
        
        .hover-primary {
            transition: color 0.3s ease;
        }
        
        .hover-primary:hover {
            color: var(--primary-color) !important;
        }
        
        footer {
            border-top: 1px solid var(--secondary-color);
        }
    </style>
</body>
</html> 