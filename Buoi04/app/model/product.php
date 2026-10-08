<?php

require_once __DIR__ . '/../common/dbConnect.php';

function getAllProducts()
{
    global $conn;

    $sql = "SELECT * FROM products ORDER BY id ASC";
    $result = $conn->query($sql);

    $products = [];

    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $products[] = $row;
        }
    }

    return $products;
}

function getProductById($id)
{
    global $conn;

    $sql = "SELECT * FROM products WHERE id = ?";
    $stmt = $conn->prepare($sql);

    $stmt->bind_param("i", $id);
    $stmt->execute();

    $result = $stmt->get_result();
    $product = $result->fetch_assoc();

    $stmt->close();

    return $product;
}

function addProduct($name, $price, $quantity)
{
    global $conn;

    $sql = "INSERT INTO products (name, price, quantity)
            VALUES (?, ?, ?)";

    $stmt = $conn->prepare($sql);

    $stmt->bind_param(
        "sdi",
        $name,
        $price,
        $quantity
    );

    $result = $stmt->execute();

    $stmt->close();

    return $result;
}

function updateProduct($id, $name, $price, $quantity)
{
    global $conn;

    $sql = "UPDATE products
            SET name = ?, price = ?, quantity = ?
            WHERE id = ?";

    $stmt = $conn->prepare($sql);

    $stmt->bind_param(
        "sdii",
        $name,
        $price,
        $quantity,
        $id
    );

    $result = $stmt->execute();

    $stmt->close();

    return $result;
}

function deleteProduct($id)
{
    global $conn;

    $sql = "DELETE FROM products WHERE id = ?";
    $stmt = $conn->prepare($sql);

    $stmt->bind_param("i", $id);

    $result = $stmt->execute();

    $stmt->close();

    return $result;
}