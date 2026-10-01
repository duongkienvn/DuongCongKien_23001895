# BÀI 1 – QUẢN LÝ GIỎ HÀNG
CREATE DATABASE shopping_cart;

USE shopping_cart;

SELECT DATABASE();

CREATE TABLE cart_items
(
    id       INT PRIMARY KEY AUTO_INCREMENT,
    name     VARCHAR(100)   NOT NULL,
    price    DECIMAL(10, 2) NOT NULL,
    quantity INT            NOT NULL
);

# 1. Thêm ít nhất 5 sản phẩm vào bảng.
INSERT INTO cart_items (name, price, quantity)
VALUES ('Ban phim co', 850000, 2),
       ('Chuot khong day', 250000, 6),
       ('Tai nghe Bluetooth', 1200000, 1),
       ('Lot chuot', 80000, 10),
       ('Gia do laptop', 180000, 3);

# 2. Hiển thị toàn bộ sản phẩm.
SELECT *
FROM cart_items;

# 3. Hiển thị sản phẩm có giá lớn hơn 100000.
SELECT *
FROM cart_items
WHERE price > 100000;

# 4. Hiển thị sản phẩm có số lượng lớn hơn 5.
SELECT *
FROM cart_items
WHERE quantity > 5;


# 5. Sắp xếp sản phẩm theo giá giảm dần.
SELECT *
FROM cart_items
ORDER BY price DESC;

# 6. Cập nhật giá của một sản phẩm.
UPDATE cart_items
SET price = 280000
WHERE id = 2;

SELECT *
FROM cart_items
WHERE id = 2;

# 7. Cập nhật số lượng của một sản phẩm.
UPDATE cart_items
SET quantity = 5
WHERE id = 5;

SELECT *
FROM cart_items
WHERE id = 5;

# 8. Xóa một sản phẩm.
DELETE
FROM cart_items
WHERE id = 4;

SELECT *
FROM cart_items;

# 9. Hiển thị tên sản phẩm, giá, số lượng và thành tiền (price × quantity).
SELECT name,
       price,
       quantity,
       price * quantity AS total_price
FROM cart_items;

# 10. Tính tổng tiền của toàn bộ giỏ hàng.
SELECT SUM(price * quantity) AS cart_total
FROM cart_items;


# BÀI 2 – QUẢN LÝ VÉ XEM PHIM
CREATE DATABASE movie_management;

USE movie_management;

CREATE TABLE movies
(
    id              INT PRIMARY KEY AUTO_INCREMENT,
    title           VARCHAR(100)   NOT NULL,
    price           DECIMAL(10, 2) NOT NULL,
    total_seats     INT            NOT NULL,
    available_seats INT            NOT NULL
);

# 1. Thêm ít nhất 5 bộ phim.
INSERT INTO movies (title, price, total_seats, available_seats)
VALUES ('Dune Part Two', 120000, 120, 40),
       ('Oppenheimer', 90000, 100, 25),
       ('Interstellar', 110000, 150, 60),
       ('Avatar 2', 130000, 200, 80),
       ('Inside Out 2', 80000, 90, 55);

# 2. Hiển thị toàn bộ danh sách phim.
SELECT *
FROM movies;

# 3. Hiển thị phim có giá vé lớn hơn 100000.
SELECT *
FROM movies
WHERE price > 100000;

# 4. Hiển thị phim còn nhiều hơn 50 ghế.
SELECT *
FROM movies
WHERE available_seats > 50;

# 5. Sắp xếp phim theo giá vé giảm dần.
SELECT *
FROM movies
ORDER BY price DESC;

# 6. Cập nhật số ghế còn lại của một phim.
UPDATE movies
SET available_seats = 30
WHERE id = 1;

SELECT *
FROM movies
WHERE id = 1;

# 7. Xóa một phim.
DELETE
FROM movies
WHERE id = 5;

SELECT *
FROM movies;

# 8. Hiển thị số vé đã bán của từng phim: total_seats - available_seats.
SELECT title,
       total_seats,
       available_seats,
       total_seats - available_seats AS sold_tickets
FROM movies;

# 9. Tính doanh thu của từng phim: (total_seats - available_seats) × price.
SELECT title,
       total_seats - available_seats           AS sold_tickets,
       price,
       (total_seats - available_seats) * price AS revenue
FROM movies;

# 10. Tính tổng doanh thu của tất cả các phim.
SELECT SUM((total_seats - available_seats) * price) AS total_revenue
FROM movies;

# 11. Tìm phim có số vé bán ra nhiều nhất.
SELECT title,
       total_seats - available_seats AS sold_tickets
FROM movies
WHERE total_seats - available_seats = (SELECT MAX(total_seats - available_seats)
                                       FROM movies);






