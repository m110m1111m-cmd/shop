-- Create database
CREATE DATABASE IF NOT EXISTS shop CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE shop;

-- Users
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'cashier') DEFAULT 'cashier',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO users (username, password, role) VALUES ('admin', '$2y$10$V9AnjTmn7KphgU8uh43L4uaw56hkm1HgEV0O7iELvwQQwAg5FNOce', 'admin');

-- Categories
CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Products
CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    barcode VARCHAR(100) UNIQUE NOT NULL,
    name VARCHAR(255) NOT NULL,
    category_id INT,
    unit_name VARCHAR(50) NOT NULL COMMENT 'e.g., Bottle, Pack, Box',
    selling_price DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
);

-- Product Conversions (For Multi-Unit)
-- Example: 1 Pack (parent) contains 12 Bottles (child).
-- When parent is sold, child stock is deducted by quantity.
CREATE TABLE product_conversions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    parent_product_id INT NOT NULL,
    child_product_id INT NOT NULL,
    quantity DECIMAL(10,2) NOT NULL COMMENT 'How many child units in the parent unit',
    FOREIGN KEY (parent_product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (child_product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- Inventory
CREATE TABLE inventory (
    product_id INT PRIMARY KEY,
    stock_qty DECIMAL(10,2) NOT NULL DEFAULT 0,
    alert_threshold DECIMAL(10,2) NOT NULL DEFAULT 0,
    avg_cost_price DECIMAL(10,2) NOT NULL DEFAULT 0,
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- Shifts
CREATE TABLE shifts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cashier_name VARCHAR(100) NOT NULL,
    start_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    end_time TIMESTAMP NULL DEFAULT NULL,
    starting_cash DECIMAL(10,2) NOT NULL DEFAULT 0,
    ending_cash DECIMAL(10,2) NULL,
    status ENUM('open', 'closed') DEFAULT 'open'
);

-- Sales
CREATE TABLE sales (
    id INT AUTO_INCREMENT PRIMARY KEY,
    shift_id INT,
    total_amount DECIMAL(10,2) NOT NULL,
    payment_method ENUM('cash', 'transfer') NOT NULL,
    sale_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (shift_id) REFERENCES shifts(id) ON DELETE SET NULL
);

-- Sale Details
CREATE TABLE sale_details (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sale_id INT NOT NULL,
    product_id INT NOT NULL,
    qty DECIMAL(10,2) NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    subtotal DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (sale_id) REFERENCES sales(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE NO ACTION
);

-- Promotions (Simple setup for future rules)
CREATE TABLE promotions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    promo_type ENUM('discount_percent', 'discount_fixed', 'buy_x_get_y') NOT NULL,
    details JSON NOT NULL,
    start_date DATETIME NOT NULL,
    end_date DATETIME NOT NULL,
    is_active TINYINT(1) DEFAULT 1
);

-- Sample Data
INSERT INTO categories (name) VALUES ('Drinks'), ('Snacks'), ('Daily Use');

-- Product 1: Tiger Water (Bottle)
INSERT INTO products (barcode, name, category_id, unit_name, selling_price) 
VALUES ('8856001001001', 'ນ້ຳດື່ມ Tiger (ຕຸກ)', 1, 'ຕຸກ (Bottle)', 5000);

-- Product 2: Tiger Water (Pack - 12 bottles)
INSERT INTO products (barcode, name, category_id, unit_name, selling_price) 
VALUES ('8856001001002', 'ນ້ຳດື່ມ Tiger (ແພັກ)', 1, 'ແພັກ (Pack)', 55000);

-- Conversion: Selling 1 Pack deducts 12 Bottles
-- Assuming Product 1 gets ID 1, and Product 2 gets ID 2
INSERT INTO product_conversions (parent_product_id, child_product_id, quantity) 
VALUES (2, 1, 12);

-- Initial Inventory
INSERT INTO inventory (product_id, stock_qty, alert_threshold, avg_cost_price) 
VALUES (1, 100, 10, 3000), (2, 5, 2, 36000); -- Note: We usually only track stock for the base unit (Bottle), but let's initialize both for flexibility or we can just track child unit.

