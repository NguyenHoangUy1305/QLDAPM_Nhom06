<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../data/store.php';

function requireAdmin(): array
{
    if (!empty($_SESSION['admin_user'])) {
        return $_SESSION['admin_user'];
    }
    if (!empty($_SESSION['user']) && ($_SESSION['user']['role'] ?? '') === 'admin') {
        return $_SESSION['user'];
    }
    header('Location: login.php');
    exit;
}

function initAdminData(): void
{
    if (!isset($_SESSION['admin_products'])) {
        $_SESSION['admin_products'] = getAllProducts();
    }
    if (!isset($_SESSION['admin_categories'])) {
        $_SESSION['admin_categories'] = getCategories();
    }
    if (!isset($_SESSION['orders'])) {
        $_SESSION['orders'] = [
            [
                'id' => 1001,
                'code' => 'DH01001',
                'customer_name' => 'Nguyễn Văn An',
                'phone' => '0000.0000',
                'address' => 'TP. Hồ Chí Minh',
                'payment_method' => 'COD',
                'total_price' => 18380000,
                'status' => 'Chờ xác nhận',
                'created_at' => date('d/m/Y H:i'),
                'items' => [
                    ['name' => 'Laptop Gaming Acer Nitro V 15 ANV15-51-58AN', 'qty' => 1, 'price' => 16990000],
                    ['name' => 'Bàn phím cơ Akko 3098B Multi-modes', 'qty' => 1, 'price' => 1390000],
                ],
            ],
            [
                'id' => 1002,
                'code' => 'DH01002',
                'customer_name' => 'Trần Thị Bình',
                'phone' => '0000.0000',
                'address' => 'Hà Nội',
                'payment_method' => 'Chuyển khoản',
                'total_price' => 17990000,
                'status' => 'Đang giao',
                'created_at' => date('d/m/Y H:i'),
                'items' => [
                    ['name' => 'PC ĐOÀN 6 Intel i5-12400F / VGA RTX 4060 8GB', 'qty' => 1, 'price' => 17990000],
                ],
            ],
        ];
    }
}
