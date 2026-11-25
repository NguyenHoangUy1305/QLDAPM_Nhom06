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

function getAdminOrders(): array
{
    $pdo = getPDO();
    if ($pdo) {
        try {
            $dbOrders = $pdo->query('SELECT * FROM orders ORDER BY id DESC')->fetchAll();
            if (!empty($dbOrders)) {
                $orders = [];
                $itemStmt = $pdo->prepare('
                    SELECT oi.quantity AS qty, oi.price, COALESCE(oi.product_name, p.name, "Sản phẩm") AS name
                    FROM order_items oi
                    LEFT JOIN products p ON p.id = oi.product_id
                    WHERE oi.order_id = ?
                ');
                foreach ($dbOrders as $o) {
                    $itemStmt->execute([$o['id']]);
                    $orders[] = [
                        'id' => (int)$o['id'],
                        'code' => 'DH' . str_pad((string)$o['id'], 5, '0', STR_PAD_LEFT),
                        'customer_name' => $o['customer_name'],
                        'phone' => $o['phone'],
                        'address' => $o['address'],
                        'payment_method' => $o['payment_method'] ?? 'COD',
                        'total_price' => (int)round((float)$o['total_price']),
                        'status' => $o['status'],
                        'created_at' => $o['created_at'],
                        'items' => $itemStmt->fetchAll() ?: [],
                    ];
                }
                return $orders;
            }
        } catch (PDOException $e) {
            // Fallback sang Session bên dưới
        }
    }

    if (!isset($_SESSION['orders'])) {
        $_SESSION['orders'] = [
            [
                'id' => 1,
                'code' => 'DH00001',
                'customer_name' => 'Nguyễn Văn An',
                'phone' => '0000.0000',
                'address' => 'Khu vực Demo, Quận 10, TP. Hồ Chí Minh',
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
                'id' => 2,
                'code' => 'DH00002',
                'customer_name' => 'Trần Thị Bình',
                'phone' => '0000.0000',
                'address' => 'Khu vực Demo, Quận Tân Bình, TP. Hồ Chí Minh',
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
    return $_SESSION['orders'];
}

function initAdminData(): void
{
    if (!isset($_SESSION['admin_products'])) {
        $_SESSION['admin_products'] = getAllProducts();
    }
    if (!isset($_SESSION['admin_categories'])) {
        $_SESSION['admin_categories'] = getCategories();
    }
}
