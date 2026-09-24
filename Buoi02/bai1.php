<?php

class CartItem
{
    public $name;
    public $price;
    public $quantity;

    public function __construct($name, $price, $quantity)
    {
        $this->name = $name;
        $this->price = $price;
        $this->quantity = $quantity;
    }

    public function getTotal()
    {
        return $this->price * $this->quantity;
    }
}


class ShoppingCart
{
    private $items = [];

    public function addItem($item)
    {
        if (!($item instanceof CartItem)) {
            echo "<p>Không thể thêm sản phẩm: dữ liệu không hợp lệ.</p>";
            return false;
        }

        if (trim($item->name) === "") {
            echo "<p>Không thể thêm sản phẩm: tên sản phẩm không được để trống.</p>";
            return false;
        }

        if (!is_numeric($item->price) || $item->price <= 0) {
            echo "<p>Không thể thêm '{$item->name}': giá sản phẩm phải lớn hơn 0.</p>";
            return false;
        }

        if (!is_numeric($item->quantity) || $item->quantity <= 0) {
            echo "<p>Không thể thêm '{$item->name}': số lượng sản phẩm phải lớn hơn 0.</p>";
            return false;
        }

        $this->items[] = $item;

        return true;
    }


    public function removeItem($name)
    {
        if (trim($name) === "") {
            echo "<p>Tên sản phẩm cần xóa không hợp lệ.</p>";
            return false;
        }

        foreach ($this->items as $index => $item) {
            if ($item->name === $name) {
                array_splice($this->items, $index, 1);

                echo "<p>Đã xóa sản phẩm <strong>{$name}</strong> khỏi giỏ hàng.</p>";

                return true;
            }
        }

        echo "<p>Không tìm thấy sản phẩm <strong>{$name}</strong> trong giỏ hàng.</p>";

        return false;
    }


    public function calculateTotal()
    {
        $total = 0;

        foreach ($this->items as $item) {
            $total += $item->getTotal();
        }

        return $total;
    }


    public function displayCart()
    {
        echo "<h2>Giỏ hàng</h2>";

        if (empty($this->items)) {
            echo "<p>Giỏ hàng không có sản phẩm.</p>";
            echo "<p><strong>Tổng tiền: 0 VNĐ</strong></p>";
            return;
        }

        echo "<table border='1' cellpadding='10' cellspacing='0'>";

        echo "
            <tr>
                <th>Tên sản phẩm</th>
                <th>Đơn giá</th>
                <th>Số lượng</th>
                <th>Thành tiền</th>
            </tr>
        ";

        foreach ($this->items as $item) {
            echo "<tr>";

            echo "<td>" . htmlspecialchars($item->name) . "</td>";

            echo "<td>"
                . number_format($item->price, 0, ',', '.')
                . " VNĐ</td>";

            echo "<td>"
                . $item->quantity
                . "</td>";

            echo "<td>"
                . number_format($item->getTotal(), 0, ',', '.')
                . " VNĐ</td>";

            echo "</tr>";
        }

        echo "
            <tr>
                <td colspan='3'><strong>Tổng tiền</strong></td>
                <td>
                    <strong>"
                    . number_format($this->calculateTotal(), 0, ',', '.')
                    . " VNĐ</strong>
                </td>
            </tr>
        ";

        echo "</table>";
    }
}


$cart = new ShoppingCart();


$item1 = new CartItem("Laptop", 15000000, 1);
$item2 = new CartItem("Chuột", 300000, 2);
$item3 = new CartItem("Bàn phím", 800000, 1);
$item4 = new CartItem("Tai nghe", 1200000, 1);


$cart->addItem($item1);
$cart->addItem($item2);
$cart->addItem($item3);
$cart->addItem($item4);


$cart->displayCart();


echo "<h3>Tổng tiền của giỏ hàng</h3>";

echo "<p>"
    . number_format($cart->calculateTotal(), 0, ',', '.')
    . " VNĐ</p>";


echo "<h3>Xóa sản phẩm</h3>";

$cart->removeItem("Chuột");


echo "<h3>Giỏ hàng sau khi xóa</h3>";

$cart->displayCart();

?>