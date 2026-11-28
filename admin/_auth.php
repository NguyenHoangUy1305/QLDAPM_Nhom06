<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../data/store.php';

function requireRole(array $roles, string $loginUrl = 'login.php'): array
{
    $user = currentUser();
    if ($user === null) {
        header('Location: ' . $loginUrl);
        exit;
    }

    if (!in_array($user['role'], $roles, true)) {
        http_response_code(403);
        header('Content-Type: text/html; charset=utf-8');
        $panelLink = $user['role'] === 'staff'
            ? '<a href="index.php" style="margin-right:12px;color:#E30019;font-weight:bold;">Về bảng điều khiển Nhân viên</a>'
            : '';
        echo '<!DOCTYPE html><html lang="vi"><head><meta charset="UTF-8"><title>403 - Không có quyền truy cập</title></head>'
            . '<body style="font-family:system-ui,sans-serif;background:#f4f4f4;padding:3rem;">'
            . '<div style="max-width:520px;margin:0 auto;background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:24px;">'
            . '<h1 style="margin:0 0 8px;font-size:20px;color:#b91c1c;">403 - Không có quyền truy cập</h1>'
            . '<p style="margin:0 0 16px;color:#4b5563;font-size:14px;">Bạn không có quyền truy cập trang này với vai trò hiện tại (<strong>' . e($user['role']) . '</strong>).</p>'
            . '<div style="font-size:14px;">' . $panelLink . '<a href="../index.php" style="color:#111;font-weight:bold;">Về trang chủ</a></div>'
            . '</div></body></html>';
        exit;
    }

    return $user;
}

function requireAdmin(string $loginUrl = 'login.php'): array
{
    return requireRole(['admin'], $loginUrl);
}

function requireAdminOnly(string $loginUrl = 'login.php'): array
{
    return requireAdmin($loginUrl);
}

function requireStaffOrAdmin(string $loginUrl = 'login.php'): array
{
    return requireRole(['admin', 'staff'], $loginUrl);
}

function requirePanelAccess(string $loginUrl = 'login.php'): array
{
    return requireStaffOrAdmin($loginUrl);
}

function isAdmin(): bool
{
    return hasRole('admin');
}

function isStaff(): bool
{
    return hasRole('staff');
}

function requireAdminPasswordForStaff(?string $adminPassword = null): ?array
{
    if (isAdmin()) {
        return currentUser();
    }
    if (isStaff()) {
        $pw = $adminPassword ?? (string)($_POST['admin_password'] ?? '');
        return verifyAdminPassword($pw);
    }
    return null;
}

function getAdminOrders(): array
{
    $pdo = getPDO();
    if ($pdo) {
        try {
            $dbOrders = $pdo->query('SELECT * FROM orders ORDER BY id DESC')->fetchAll();
            $orders = [];
            $itemStmt = $pdo->prepare('
                SELECT oi.id,
                       oi.product_id,
                       oi.product_name,
                       oi.product_price,
                       oi.product_price AS price,
                       oi.quantity,
                       oi.quantity AS qty,
                       oi.component_slot,
                       COALESCE(oi.product_name, p.name, "Sản phẩm") AS name
                FROM order_items oi
                LEFT JOIN products p ON p.id = oi.product_id
                WHERE oi.order_id = ?
                ORDER BY oi.id ASC
            ');

            foreach ($dbOrders as $o) {
                $itemStmt->execute([$o['id']]);
                $orderCode = (string)($o['order_code'] ?? ('DH' . str_pad((string)$o['id'], 5, '0', STR_PAD_LEFT)));
                $totalAmount = (int)round((float)($o['total_amount'] ?? $o['total_price'] ?? 0));
                $phone = (string)($o['customer_phone'] ?? $o['phone'] ?? '');
                $address = (string)($o['customer_address'] ?? $o['address'] ?? '');

                $orders[] = [
                    'id' => (int)$o['id'],
                    'order_code' => $orderCode,
                    'code' => $orderCode,
                    'user_id' => $o['user_id'] !== null ? (int)$o['user_id'] : null,
                    'created_by_staff_id' => isset($o['created_by_staff_id']) && $o['created_by_staff_id'] !== null ? (int)$o['created_by_staff_id'] : null,
                    'order_type' => (string)($o['order_type'] ?? 'online'),
                    'customer_name' => (string)$o['customer_name'],
                    'customer_phone' => $phone,
                    'phone' => $phone,
                    'customer_email' => (string)($o['customer_email'] ?? ''),
                    'customer_address' => $address,
                    'address' => $address,
                    'note' => (string)($o['note'] ?? ''),
                    'payment_method' => (string)($o['payment_method'] ?? 'COD'),
                    'payment_status' => (string)($o['payment_status'] ?? 'unpaid'),
                    'payment_expires_at' => $o['payment_expires_at'] ?? null,
                    'total_amount' => $totalAmount,
                    'total_price' => $totalAmount,
                    'status' => (string)$o['status'],
                    'pc_build_specs' => $o['pc_build_specs'] ?? null,
                    'created_at' => (string)$o['created_at'],
                    'updated_at' => (string)($o['updated_at'] ?? $o['created_at']),
                    'items' => $itemStmt->fetchAll() ?: [],
                ];
            }
            return $orders;
        } catch (PDOException $e) {
            // Fallback sang Session bên dưới nếu DB chưa sẵn sàng
        }
    }

    if (!isset($_SESSION['orders'])) {
        $_SESSION['orders'] = [
            [
                'id' => 1,
                'order_code' => 'DH00001',
                'code' => 'DH00001',
                'order_type' => 'online',
                'customer_name' => 'Nguyễn Văn Khách',
                'customer_phone' => '0909123456',
                'phone' => '0909123456',
                'customer_address' => '273 An Dương Vương, Quận 5, TP. Hồ Chí Minh',
                'address' => '273 An Dương Vương, Quận 5, TP. Hồ Chí Minh',
                'payment_method' => 'COD',
                'payment_status' => 'unpaid',
                'total_amount' => 18380000,
                'total_price' => 18380000,
                'status' => 'Chờ xác nhận',
                'created_at' => date('d/m/Y H:i'),
                'items' => [
                    ['name' => 'Laptop Gaming Acer Nitro V 15 ANV15-51-58AN', 'qty' => 1, 'quantity' => 1, 'price' => 16990000, 'product_price' => 16990000],
                    ['name' => 'Bàn phím cơ Akko 3098B Multi-modes', 'qty' => 1, 'quantity' => 1, 'price' => 1390000, 'product_price' => 1390000],
                ],
            ],
            [
                'id' => 2,
                'order_code' => 'DH00002',
                'code' => 'DH00002',
                'order_type' => 'online',
                'customer_name' => 'Nguyễn Văn Khách',
                'customer_phone' => '0909123456',
                'phone' => '0909123456',
                'customer_address' => '273 An Dương Vương, Quận 5, TP. Hồ Chí Minh',
                'address' => '273 An Dương Vương, Quận 5, TP. Hồ Chí Minh',
                'payment_method' => 'VietQR',
                'payment_status' => 'paid',
                'total_amount' => 17990000,
                'total_price' => 17990000,
                'status' => 'Hoàn thành',
                'created_at' => date('d/m/Y H:i'),
                'items' => [
                    ['name' => 'PC ĐOÀN 6 Intel i5-12400F / VGA RTX 4060 8GB', 'qty' => 1, 'quantity' => 1, 'price' => 17990000, 'product_price' => 17990000],
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
