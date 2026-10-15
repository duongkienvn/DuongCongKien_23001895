CREATE DATABASE IF NOT EXISTS shopping_cart
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE shopping_cart;
SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS products
(
    id       INT PRIMARY KEY AUTO_INCREMENT,
    name     VARCHAR(100) NOT NULL,
    price    DECIMAL(10, 2) NOT NULL,
    quantity INT NOT NULL
);

CREATE TABLE IF NOT EXISTS users
(
    id       INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL
);

INSERT INTO products (name, price, quantity)
SELECT 'Laptop Dell', 15000000, 10
WHERE NOT EXISTS (SELECT 1 FROM products WHERE name = 'Laptop Dell');

INSERT INTO products (name, price, quantity)
SELECT 'Chuột Logitech', 350000, 20
WHERE NOT EXISTS (SELECT 1 FROM products WHERE name = 'Chuột Logitech');

INSERT INTO products (name, price, quantity)
SELECT 'Bàn phím cơ', 1200000, 15
WHERE NOT EXISTS (SELECT 1 FROM products WHERE name = 'Bàn phím cơ');

INSERT INTO products (name, price, quantity)
SELECT 'Tai nghe Bluetooth', 850000, 12
WHERE NOT EXISTS (SELECT 1 FROM products WHERE name = 'Tai nghe Bluetooth');

INSERT INTO products (name, price, quantity)
SELECT 'Màn hình Samsung', 4500000, 8
WHERE NOT EXISTS (SELECT 1 FROM products WHERE name = 'Màn hình Samsung');

-- Tài khoản mẫu: demo / Demo@123.
-- Hash được tạo bằng PHP password_hash('Demo@123', PASSWORD_DEFAULT).
-- Nếu username demo đã tồn tại thì giữ nguyên tài khoản đó.
INSERT INTO users (username, password)
SELECT 'demo', '$2y$10$X9I8PNDpkes36hN4eZnreuVmy5Nqhnbh4FnDo2atnJnKNWt6ZZdu6'
WHERE NOT EXISTS (SELECT 1 FROM users WHERE username = 'demo');
