<?php

$students = [
    [
        "name" => "Nguyen Van An",
        "age" => 20,
        "score" => 8.5
    ],
    [
        "name" => "Tran Thi Binh",
        "age" => 21,
        "score" => 6.5
    ],
    [
        "name" => "Le Van Cuong",
        "age" => 19,
        "score" => 4.5
    ],
    [
        "name" => "Pham Thi Dung",
        "age" => 20,
        "score" => 7.5
    ]
];

function displayStudent($student)
{
    echo "Họ tên: " . $student["name"] . "<br>";
    echo "Tuổi: " . $student["age"] . "<br>";
    echo "Điểm: " . $student["score"] . "<br>";
}

function findBestStudent($students)
{
    $bestStudent = $students[0];

    foreach ($students as $student) {
        if ($student["score"] > $bestStudent["score"]) {
            $bestStudent = $student;
        }
    }

    return $bestStudent;
}

function findWorstStudent($students)
{
    $worstStudent = $students[0];

    foreach ($students as $student) {
        if ($student["score"] < $worstStudent["score"]) {
            $worstStudent = $student;
        }
    }

    return $worstStudent;
}

function countPassedStudents($students)
{
    $count = 0;

    foreach ($students as $student) {
        if ($student["score"] >= 5) {
            $count++;
        }
    }

    return $count;
}

function findStudentByName($students, $name)
{
    foreach ($students as $student) {
        if ($student["name"] === $name) {
            return $student;
        }
    }

    return null;
}



echo "<h2>Sinh viên có điểm cao nhất</h2>";

$bestStudent = findBestStudent($students);
displayStudent($bestStudent);


echo "<h2>Sinh viên có điểm thấp nhất</h2>";

$worstStudent = findWorstStudent($students);
displayStudent($worstStudent);


echo "<h2>Số sinh viên đạt</h2>";

echo countPassedStudents($students);


echo "<h2>Tìm sinh viên</h2>";

$student = findStudentByName($students, "Tran Thi Binh");

if ($student !== null) {
    displayStudent($student);
} else {
    echo "Không tìm thấy sinh viên";
}

?>