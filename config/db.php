<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Cấu hình kết nối MySQL (khi nhóm tạo database trên phpMyAdmin thì sửa tên DB ở đây)
define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'database');
define('DB_USER', 'root');
define('DB_PASS', '');

function getPDO(): ?PDO
{
    static $pdo = null;
    if ($pdo !== null) {
        return $pdo;
    }
    try {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        return $pdo;
    } catch (PDOException $e) {
        // Trả về null nếu chưa tạo DB để trang vẫn chạy được bằng dữ liệu mẫu
        return null;
    }
}

function e(string $str): string
{
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

function formatPrice(int $price): string
{
    return number_format($price, 0, ',', '.') . 'đ';
}
