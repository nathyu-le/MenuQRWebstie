<?php
// Kết nối DB + constants cơ bản
session_start();

define('DB_HOST', 'localhost');
define('DB_NAME', 'order_mon_an');
define('DB_USER', 'root');
define('DB_PASS', '');

function getDatabaseConnection() {
    try {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
        return new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    } catch (PDOException $e) {
        die('Lỗi kết nối DB: ' . $e->getMessage());
    }
}
