<?php

require_once __DIR__ . '/../common/dbConnect.php';

function getUserByUsername($username)
{
    global $conn;

    $stmt = $conn->prepare('SELECT id, username, password FROM users WHERE username = ?');
    $stmt->bind_param('s', $username);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    return $user;
}

function addUser($username, $password)
{
    global $conn;

    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $conn->prepare('INSERT INTO users (username, password) VALUES (?, ?)');
    $stmt->bind_param('ss', $username, $hash);
    $result = $stmt->execute();
    $stmt->close();

    return $result;
}
