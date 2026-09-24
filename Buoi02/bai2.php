<?php

class Movie
{
    public $id;
    public $title;
    public $price;
    public $totalSeats;
    public $availableSeats;

    public function __construct($id, $title, $price, $totalSeats)
    {
        $this->id = $id;
        $this->title = $title;
        $this->price = $price;
        $this->totalSeats = $totalSeats;

        $this->availableSeats = $totalSeats;
    }

    public function bookTicket($quantity)
    {
        if (!is_numeric($quantity) || $quantity <= 0) {
            echo "<p>Không thể đặt vé cho <strong>{$this->title}</strong>: "
                . "số lượng vé phải lớn hơn 0.</p>";

            return false;
        }

        if ($quantity > $this->availableSeats) {
            echo "<p>Không thể đặt {$quantity} vé cho "
                . "<strong>{$this->title}</strong>: "
                . "chỉ còn {$this->availableSeats} ghế.</p>";

            return false;
        }

        $this->availableSeats -= $quantity;

        echo "<p>Đặt thành công {$quantity} vé cho "
            . "<strong>{$this->title}</strong>.</p>";

        return true;
    }

    public function cancelTicket($quantity)
    {
        if (!is_numeric($quantity) || $quantity <= 0) {
            echo "<p>Không thể hủy vé của <strong>{$this->title}</strong>: "
                . "số lượng vé phải lớn hơn 0.</p>";

            return false;
        }

        $soldSeats = $this->getSoldSeats();

        if ($quantity > $soldSeats) {
            echo "<p>Không thể hủy {$quantity} vé của "
                . "<strong>{$this->title}</strong>: "
                . "mới chỉ bán {$soldSeats} vé.</p>";

            return false;
        }

        $this->availableSeats += $quantity;

        echo "<p>Hủy thành công {$quantity} vé của "
            . "<strong>{$this->title}</strong>.</p>";

        return true;
    }

    public function getSoldSeats()
    {
        return $this->totalSeats - $this->availableSeats;
    }

    public function getRevenue()
    {
        return $this->getSoldSeats() * $this->price;
    }

    public function displayInfo()
    {
        echo "<tr>";

        echo "<td>{$this->id}</td>";

        echo "<td>"
            . htmlspecialchars($this->title)
            . "</td>";

        echo "<td>"
            . number_format($this->price, 0, ',', '.')
            . " VNĐ</td>";

        echo "<td>{$this->totalSeats}</td>";

        echo "<td>{$this->availableSeats}</td>";

        echo "<td>"
            . $this->getSoldSeats()
            . "</td>";

        echo "<td>"
            . number_format($this->getRevenue(), 0, ',', '.')
            . " VNĐ</td>";

        echo "</tr>";
    }
}


function findMovieById($movies, $id)
{
    if (empty($movies)) {
        return null;
    }

    foreach ($movies as $movie) {
        if ($movie->id == $id) {
            return $movie;
        }
    }

    return null;
}


function getTotalRevenue($movies)
{
    if (empty($movies)) {
        return 0;
    }

    $totalRevenue = 0;

    foreach ($movies as $movie) {
        $totalRevenue += $movie->getRevenue();
    }

    return $totalRevenue;
}


function getBestSellingMovie($movies)
{
    if (empty($movies)) {
        return null;
    }

    $bestSellingMovie = $movies[0];

    foreach ($movies as $movie) {
        if ($movie->getSoldSeats() > $bestSellingMovie->getSoldSeats()) {
            $bestSellingMovie = $movie;
        }
    }

    return $bestSellingMovie;
}


function displayMovies($movies)
{
    echo "<h2>Danh sách phim</h2>";

    if (empty($movies)) {
        echo "<p>Danh sách phim đang rỗng.</p>";
        return;
    }

    echo "<table border='1' cellpadding='10' cellspacing='0'>";

    echo "
        <tr>
            <th>Mã phim</th>
            <th>Tên phim</th>
            <th>Giá vé</th>
            <th>Tổng số ghế</th>
            <th>Ghế còn lại</th>
            <th>Vé đã bán</th>
            <th>Doanh thu</th>
        </tr>
    ";

    foreach ($movies as $movie) {
        $movie->displayInfo();
    }

    echo "</table>";
}


$movie1 = new Movie(1, "Avengers", 100000, 100);
$movie2 = new Movie(2, "Avatar", 120000, 80);
$movie3 = new Movie(3, "Batman", 90000, 120);


$movies = [
    $movie1,
    $movie2,
    $movie3
];


$avengers = findMovieById($movies, 1);

if ($avengers !== null) {
    $avengers->bookTicket(30);
} else {
    echo "<p>Không tìm thấy phim Avengers.</p>";
}


$avatar = findMovieById($movies, 2);

if ($avatar !== null) {
    $avatar->bookTicket(25);
} else {
    echo "<p>Không tìm thấy phim Avatar.</p>";
}


if ($avengers !== null) {
    $avengers->cancelTicket(5);
}


displayMovies($movies);


echo "<h2>Tổng doanh thu</h2>";

echo "<p><strong>"
    . number_format(getTotalRevenue($movies), 0, ',', '.')
    . " VNĐ</strong></p>";


echo "<h2>Phim bán được nhiều vé nhất</h2>";

$bestSellingMovie = getBestSellingMovie($movies);

if ($bestSellingMovie !== null) {
    echo "<p>"
        . htmlspecialchars($bestSellingMovie->title)
        . " - "
        . $bestSellingMovie->getSoldSeats()
        . " vé đã bán"
        . "</p>";
} else {
    echo "<p>Danh sách phim đang rỗng.</p>";
}

?>