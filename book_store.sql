-- Drop database if exists
DROP DATABASE IF EXISTS book_store;

-- Create database if not exists
CREATE DATABASE IF NOT EXISTS book_store;
USE book_store;

-- Settings table
CREATE TABLE IF NOT EXISTS settings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    site_name VARCHAR(255) NOT NULL,
    site_description TEXT,
    site_keywords TEXT,
    site_email VARCHAR(255),
    site_phone VARCHAR(20),
    site_address TEXT,
    site_logo VARCHAR(255),
    facebook_url VARCHAR(255),
    twitter_url VARCHAR(255),
    instagram_url VARCHAR(255),
    shipping_cost DECIMAL(10,2) DEFAULT 30.00,
    tax_rate DECIMAL(5,2) DEFAULT 15.00,
    currency_symbol VARCHAR(10) DEFAULT 'ر.س',
    items_per_page INT DEFAULT 12,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Categories table
CREATE TABLE IF NOT EXISTS categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Authors table
CREATE TABLE IF NOT EXISTS authors (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    bio TEXT,
    image VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Books table
CREATE TABLE IF NOT EXISTS books (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    isbn VARCHAR(13),
    publisher VARCHAR(255),
    publish_date DATE,
    price DECIMAL(10,2) NOT NULL,
    stock INT NOT NULL DEFAULT 0,
    cover_image VARCHAR(255),
    category_id INT,
    author_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL,
    FOREIGN KEY (author_id) REFERENCES authors(id) ON DELETE SET NULL
);

-- Orders table
CREATE TABLE IF NOT EXISTS orders (
    id INT PRIMARY KEY AUTO_INCREMENT,
    customer_name VARCHAR(255) NOT NULL,
    customer_email VARCHAR(255) NOT NULL,
    customer_phone VARCHAR(20) NOT NULL,
    customer_address TEXT NOT NULL,
    subtotal DECIMAL(10,2) NOT NULL,
    tax DECIMAL(10,2) NOT NULL,
    shipping DECIMAL(10,2) NOT NULL,
    total DECIMAL(10,2) NOT NULL,
    status ENUM('pending', 'processing', 'shipped', 'delivered', 'cancelled') NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Order items table
CREATE TABLE IF NOT EXISTS order_items (
    id INT PRIMARY KEY AUTO_INCREMENT,
    order_id INT NOT NULL,
    book_id INT NOT NULL,
    quantity INT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (book_id) REFERENCES books(id) ON DELETE RESTRICT
);

-- Admins table
CREATE TABLE IF NOT EXISTS admins (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    last_login TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert default settings
INSERT INTO settings (
    site_name, 
    site_description, 
    site_keywords, 
    site_email, 
    site_phone, 
    site_address, 
    shipping_cost, 
    tax_rate, 
    currency_symbol, 
    items_per_page
) VALUES (
    'متجر الكتب',
    'متجر الكتب العربية الأول',
    'كتب، روايات، أدب عربي، كتب إلكترونية',
    'info@example.com',
    '+1234567890',
    'عنوان المتجر',
    30.00,
    15.00,
    'ر.س',
    12
) ON DUPLICATE KEY UPDATE 
    site_name = VALUES(site_name),
    site_description = VALUES(site_description),
    site_keywords = VALUES(site_keywords),
    site_email = VALUES(site_email),
    site_phone = VALUES(site_phone),
    site_address = VALUES(site_address),
    shipping_cost = VALUES(shipping_cost),
    tax_rate = VALUES(tax_rate),
    currency_symbol = VALUES(currency_symbol),
    items_per_page = VALUES(items_per_page);

-- Insert sample categories
INSERT INTO categories (name) VALUES 
('روايات'),
('كتب دينية'),
('كتب تاريخية'),
('كتب علمية'),
('كتب أطفال'),
('كتب تنمية بشرية'),
('شعر'),
('سير ذاتية');

-- Insert sample authors
INSERT INTO authors (name) VALUES 
('نجيب محفوظ'),
('أحمد خالد توفيق'),
('غسان كنفاني'),
('جبران خليل جبران'),
('طه حسين'),
('يوسف زيدان'),
('أحلام مستغانمي'),
('واسيني الأعرج');

-- Insert sample books
INSERT INTO books (title, description, isbn, publisher, publish_date, price, stock, category_id, author_id) VALUES 
('ثلاثية غرناطة', 'رواية تاريخية تحكي قصة سقوط غرناطة', '9789777651234', 'دار الشروق', '2020-01-01', 120.00, 50, 1, 6),
('عساكر قوس قزح', 'مجموعة قصصية', '9789777651235', 'دار الشروق', '2019-06-15', 80.00, 30, 1, 2),
('رجال في الشمس', 'رواية فلسطينية', '9789777651236', 'دار الآداب', '2018-12-01', 60.00, 20, 1, 3),
('النبي', 'كتاب فلسفي', '9789777651237', 'دار الساقي', '2017-08-20', 90.00, 40, 6, 4),
('الأيام', 'سيرة ذاتية', '9789777651238', 'دار المعارف', '2016-03-10', 70.00, 25, 8, 5),
('عزازيل', 'رواية تاريخية', '9789777651239', 'دار الشروق', '2015-11-05', 110.00, 35, 1, 6),
('ذاكرة الجسد', 'رواية عاطفية', '9789777651240', 'دار الآداب', '2014-07-15', 95.00, 45, 1, 7),
('طوق الياسمين', 'رواية', '9789777651241', 'دار الآداب', '2013-04-20', 85.00, 30, 1, 8);

-- Insert default admin
INSERT INTO admins (username, password, name, email) VALUES 
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'مدير النظام', 'admin@example.com'); 