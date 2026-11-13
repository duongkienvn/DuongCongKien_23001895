-- Câu 1: Cơ sở dữ liệu quản lý sản phẩm (MySQL/MariaDB).

CREATE DATABASE IF NOT EXISTS thuc_hanh_8
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE thuc_hanh_8;

CREATE TABLE IF NOT EXISTS products (
    id INT NOT NULL AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    price INT NOT NULL,
    stock INT NOT NULL,
    PRIMARY KEY (id),
    CONSTRAINT chk_products_price CHECK (price > 0),
    CONSTRAINT chk_products_stock CHECK (stock >= 0)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- Ba sản phẩm mẫu đúng theo yêu cầu của đề.
INSERT INTO products (name, price, stock) VALUES
    ('Bút bi', 5000, 20),
    ('Vở ô ly', 12000, 15),
    ('Thước kẻ', 8000, 0);
