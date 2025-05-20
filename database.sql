CREATE DATABASE IF NOT EXISTS printproject;
USE printproject;

-- Tabel admin
CREATE TABLE IF NOT EXISTS admin (
    id_admin INT PRIMARY KEY AUTO_INCREMENT,
    nama_admin VARCHAR(100) NOT NULL,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    ses_level ENUM('admin') default 'admin'
);

--Tabel Users
CREATE TABLE users (
    id_user INT AUTO_INCREMENT PRIMARY KEY,
    nama_user VARCHAR(100) NOT NULL,
    email VARCHAR(255) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    address TEXT NOT NULL,
    usn_user VARCHAR(50) NOT NULL UNIQUE,
    pass_user VARCHAR(255) NOT NULL,
    ses_level ENUM('user') DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabel customers
CREATE TABLE customers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(255) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    address TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    bank_account VARCHAR(50) NOT NULL,
    bank_name VARCHAR(100) NOT NULL,
    account_holder_name VARCHAR(255) NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id_user)
);


-- Tabel products
CREATE TABLE IF NOT EXISTS products (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    price_per_meter DECIMAL(10,2) NOT NULL,
    description TEXT,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabel orders
CREATE TABLE IF NOT EXISTS orders (
    id INT PRIMARY KEY AUTO_INCREMENT,
    midtrans_order_id VARCHAR(255) NULL,
    customer_id INT NOT NULL,
    product_id INT NOT NULL,
    width DECIMAL(10,2) NOT NULL,
    height DECIMAL(10,2) NOT NULL,
    total_price DECIMAL(10,2) NOT NULL,
    status ENUM('pending', 'paid', 'admin_confirmed', 'confirmed', 'processing', 
                'in_progress', 'completed', 'cancelled', 'refund_requested', 
                'refund_approved', 'refund_completed', 'refund_rejected') DEFAULT 'pending',
    payment_method VARCHAR(50) NULL,
    production_status ENUM('pending', 'confirmed', 'processing', 'completed') DEFAULT 'pending',
    transaction_id VARCHAR(100) NULL,
    admin_notes TEXT,
    delivery_option ENUM('pickup', 'delivery') DEFAULT 'pickup',
    delivery_fee DECIMAL(10,2) DEFAULT 0,
    design_type ENUM('upload', 'ai'),
    design_path VARCHAR(255),
    prompt_category VARCHAR(100),
    prompt_description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status_updated_at TIMESTAMP NULL,
    notification_sent TINYINT(1) DEFAULT 0,
    FOREIGN KEY (customer_id) REFERENCES customers(id),
    FOREIGN KEY (product_id) REFERENCES products(id)
);

-- Tabel payment_logs
CREATE TABLE IF NOT EXISTS payment_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NULL,
    midtrans_order_id VARCHAR(255) NULL,
    status VARCHAR(50) NOT NULL,
    payment_type VARCHAR(50) NULL,
    raw_response TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabel refunds
CREATE TABLE IF NOT EXISTS refunds (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    customer_id INT NOT NULL,
    refund_amount DECIMAL(10,2) NOT NULL,
    refund_reason TEXT,
    admin_notes TEXT,
    bank_account VARCHAR(50),
    bank_name VARCHAR(100),
    account_holder_name VARCHAR(255),
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    processed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id),
    FOREIGN KEY (customer_id) REFERENCES customers(id)
);

-- Menambahkan data admin default
INSERT INTO admin (username, password) VALUES ('admin', 'admin123');

-- Menambahkan beberapa produk contoh
INSERT INTO products (name, price_per_meter, description) VALUES
('Banner', 12000, 'Banner biasa untuk promosi'),
('X-Banner', 13000, 'X-Banner dengan bahan berkualitas tinggi'),
('Spanduk', 15000, 'Spanduk horizontal untuk outdoor');