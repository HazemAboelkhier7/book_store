                </main>
                
                <!-- Footer -->
                <footer class="mt-5 pb-4">
                    <div class="text-center text-muted">
                        <small>جميع الحقوق محفوظة &copy; <?php echo date('Y'); ?> <?php echo SITE_NAME_DB; ?></small>
                    </div>
                </footer>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- jQuery (needed for some plugins) -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <!-- Set page title based on current page -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            let pageTitle = '';
            const currentPage = '<?php echo $current_page; ?>';
            
            switch (currentPage) {
                case 'dashboard.php':
                    pageTitle = 'لوحة التحكم';
                    break;
                case 'books.php':
                    pageTitle = 'إدارة الكتب';
                    break;
                case 'orders.php':
                    pageTitle = 'إدارة الطلبات';
                    break;
                case 'settings.php':
                    pageTitle = 'إعدادات الموقع';
                    break;
                default:
                    pageTitle = 'لوحة التحكم';
            }
            
            document.getElementById('page-title').textContent = pageTitle;
        });

        // Auto-hide alerts after 5 seconds
        window.setTimeout(function() {
            $(".alert").fadeTo(500, 0).slideUp(500, function() {
                $(this).remove();
            });
        }, 5000);

        // Enable tooltips everywhere
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });

        // Confirm delete actions
        document.querySelectorAll('.confirm-delete').forEach(function(element) {
            element.addEventListener('click', function(e) {
                if (!confirm('هل أنت متأكد من حذف هذا العنصر؟')) {
                    e.preventDefault();
                }
            });
        });
    </script>
</body>
</html> 