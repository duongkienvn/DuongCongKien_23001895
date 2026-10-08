CREATE DATABASE shopping_cart
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE shopping_cart;

CREATE TABLE products
(
    id       INT PRIMARY KEY AUTO_INCREMENT,
    name     VARCHAR(100)   NOT NULL,
    price    DECIMAL(10, 2) NOT NULL,
    quantity INT            NOT NULL
);

INSERT INTO products (name, price, quantity)
VALUES ('Laptop Dell', 15000000, 10),
       ('Chuột Logitech', 350000, 20),
       ('Bàn phím cơ', 1200000, 15),
       ('Tai nghe Bluetooth', 850000, 12),
       ('Màn hình Samsung', 4500000, 8);