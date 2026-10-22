<?php

$prices = [100, 250, 150, 450, 350];

function calculateTotal($prices)
{
    if (empty($prices)) {
        return 0;
    }

    $total = 0;

    foreach ($prices as $price) {
        $total += $price;
    }

    return $total;
}

function findMaxPrice($prices)
{
    if (empty($prices)) {
        return null;
    }

    $max = $prices[0];

    foreach ($prices as $price) {
        if ($price > $max) {
            $max = $price;
        }
    }

    return $max;
}

$total = calculateTotal($prices);
$maxPrice = findMaxPrice($prices);

echo "Tổng giá trị sản phẩm: " . $total . "<br>";

if ($maxPrice !== null) {
    echo "Giá cao nhất: " . $maxPrice;
} else {
    echo "Danh sách sản phẩm rỗng";
}