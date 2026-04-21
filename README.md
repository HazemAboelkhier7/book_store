# 📚 Book Store - متجر الكتب

An online Arabic book store web application built with **PHP** and **MySQL**. Customers can browse, search, and purchase books with a full shopping cart and checkout system.

## ✨ Features

### Customer Interface
- Browse books by category, author, or search
- Shopping cart with quantity management
- Secure checkout process
- Order tracking
- Responsive dark-themed UI with Arabic RTL support

### Admin Panel
- Manage books (CRUD operations)
- Manage orders and update status
- Site settings and branding
- Dashboard with statistics

## 🛠️ Tech Stack

| Technology | Purpose |
|-----------|---------|
| PHP 8.0+ | Backend logic |
| MySQL 5.7+ | Database |
| Bootstrap 5 | Frontend framework |
| Font Awesome | Icons |
| jQuery | DOM manipulation |

## 🚀 Installation

### Prerequisites
- PHP 8.0 or higher
- MySQL 5.7 or higher
- Apache/Nginx web server
- XAMPP/WAMP/MAMP (for local development)

### Setup Steps

1. **Clone the repository:**
   ```bash
   git clone https://github.com/HazemAboelkhier7/book_store.git
   cd book_store
   ```

2. **Configure database connection:**
   
   Edit `includes/config.php` and update the database credentials:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_USER', 'your_username');
   define('DB_PASS', 'your_password');
   define('DB_NAME', 'book_store');
   ```
   
   Or set environment variables:
   ```bash
   export DB_HOST=localhost
   export DB_USER=your_username
   export DB_PASS=your_password
   export DB_NAME=book_store
   ```

3. **Create the database:**
   - Import `book_store.sql` via phpMyAdmin or command line:
   ```bash
   mysql -u your_username -p < book_store.sql
   ```

4. **Run the setup script:**
   - Navigate to `http://localhost/book_store/setup.php`
   - This will create tables and insert sample data
   - **Delete `setup.php` after initial setup for security**

5. **Set directory permissions:**
   ```bash
   chmod 755 uploads/ logs/
   ```

## 📁 Project Structure

```
book_store/
├── admin/              # Admin panel pages
├── ajax/               # AJAX handlers
├── assets/             # Static files (CSS, JS, images)
├── includes/           # Core PHP files (config, functions, auth)
├── logs/               # Error logs (gitignored)
├── uploads/            # User uploads (gitignored)
├── book_store.sql      # Database schema
├── setup.php           # Initial setup script
├── index.php           # Homepage
├── book.php            # Book details page
├── cart.php            # Shopping cart
├── checkout.php        # Checkout page
├── search.php          # Search results
└── order.php           # Order details
```

## 🔒 Security Features

- Password hashing with `password_hash()` (bcrypt)
- CSRF token protection on all forms
- Prepared statements (SQL injection prevention)
- Input sanitization and validation
- Session timeout management
- Security headers (X-Frame-Options, XSS Protection, etc.)
- File upload validation (type, size, dimensions)

## 📄 License

This project is licensed under the **GNU General Public License v3.0** - see the [LICENSE](LICENSE) file for details.

## 👤 Author

**Hazem Aboelkhier**  
GitHub: [@HazemAboelkhier7](https://github.com/HazemAboelkhier7)
