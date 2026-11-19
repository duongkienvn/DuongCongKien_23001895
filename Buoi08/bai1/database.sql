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
    image VARCHAR(255) NULL,
    PRIMARY KEY (id),
    CONSTRAINT chk_products_price CHECK (price > 0),
    CONSTRAINT chk_products_stock CHECK (stock >= 0)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- Nâng cấp bảng Lab 8 hiện có, không xóa hoặc ghi đè dữ liệu.
SET @image_migration = IF(
    EXISTS(SELECT 1 FROM information_schema.columns
        WHERE table_schema = DATABASE() AND table_name = 'products' AND column_name = 'image'),
    'SELECT 1',
    'ALTER TABLE products ADD COLUMN image VARCHAR(255) NULL'
);
PREPARE image_migration FROM @image_migration;
EXECUTE image_migration;
DEALLOCATE PREPARE image_migration;

-- Chỉ bổ sung số mẫu còn thiếu để có ít nhất 12 sản phẩm.
SET @missing_products = (SELECT GREATEST(0, 12 - COUNT(*)) FROM products);
PREPARE seed_products FROM '
    INSERT INTO products (name, price, stock)
    SELECT samples.name, samples.price, samples.stock FROM (
        SELECT 1 AS position, ''Bút bi'' AS name, 5000 AS price, 20 AS stock
        UNION ALL SELECT 2, ''Vở ô ly'', 12000, 15
        UNION ALL SELECT 3, ''Thước kẻ'', 8000, 0
        UNION ALL SELECT 4, ''Bút chì'', 4000, 30
        UNION ALL SELECT 5, ''Tẩy'', 3000, 25
        UNION ALL SELECT 6, ''Hộp bút'', 35000, 10
        UNION ALL SELECT 7, ''Bút dạ quang'', 15000, 18
        UNION ALL SELECT 8, ''Giấy A4'', 65000, 12
        UNION ALL SELECT 9, ''Kéo học sinh'', 20000, 14
        UNION ALL SELECT 10, ''Keo dán'', 9000, 0
        UNION ALL SELECT 11, ''Sổ tay'', 25000, 16
        UNION ALL SELECT 12, ''Compa'', 30000, 8
    ) AS samples
    WHERE NOT EXISTS (SELECT 1 FROM products WHERE products.name = samples.name)
    ORDER BY samples.position LIMIT ?
';
EXECUTE seed_products USING @missing_products;
DEALLOCATE PREPARE seed_products;
