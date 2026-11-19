<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/data/store.php';

$searchCode = trim($_GET['code'] ?? '');
$user = $_SESSION['user'] ?? null;

$orders = $_SESSION['orders'] ?? [];

// Đọc thêm từ MySQL nếu đã tạo bảng orders
$pdo = getPDO();
if ($pdo) {
    try {
        if ($searchCode !== '') {
            $stmt = $pdo->prepare('SELECT * FROM orders WHERE phone = ? OR id = ? ORDER BY id DESC');
            $idNum = (int)preg_replace('/\D/', '', $searchCode);
            $stmt->execute([$searchCode, $idNum]);
            $dbOrders = $stmt->fetchAll();
        } elseif ($user) {
            $stmt = $pdo->prepare('SELECT * FROM orders WHERE user_id = ? ORDER BY id DESC');
            $stmt->execute([$user['id']]);
            $dbOrders = $stmt->fetchAll();
        } else {
            $dbOrders = [];
        }

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
                $dbItems = $itemStmt->fetchAll();
                $orders[] = [
                    'id' => $o['id'],
                    'code' => 'DH' . str_pad((string)$o['id'], 5, '0', STR_PAD_LEFT),
                    'customer_name' => $o['customer_name'],
                    'phone' => $o['phone'],
                    'address' => $o['address'],
                    'total_price' => $o['total_price'],
                    'status' => $o['status'],
                    'created_at' => $o['created_at'],
                    'items' => $dbItems ?: [],
                ];
            }
        }
    } catch (PDOException $e) {
        // Fallback dùng $_SESSION['orders']
    }
}

if ($searchCode !== '' && !empty($orders)) {
    $q = mb_strtolower($searchCode, 'UTF-8');
    $orders = array_values(array_filter($orders, fn($o) =>
        str_contains(mb_strtolower($o['code'] ?? '', 'UTF-8'), $q) ||
        str_contains(mb_strtolower($o['phone'] ?? '', 'UTF-8'), $q)
    ));
}

$pageTitle = 'Tra cứu & Lịch sử đơn hàng - ĐOÀN 6';
require_once __DIR__ . '/includes/header.php';
?>

<div class="max-w-[960px] mx-auto px-3 py-6 space-y-5">
    <div class="bg-white rounded-2xl border border-gray-200 p-5 shadow-sm">
        <h1 class="text-xl font-extrabold text-gray-900 uppercase mb-2">Tra cứu đơn hàng</h1>
        <p class="text-xs text-gray-500 mb-4">Nhập mã đơn hàng (ví dụ: DH00001) hoặc số điện thoại đặt hàng để kiểm tra trạng thái.</p>

        <form method="GET" action="orders.php" class="flex flex-col sm:flex-row gap-2.5">
            <input type="text" name="code" value="<?= e($searchCode) ?>" placeholder="Nhập mã đơn hàng hoặc SĐT..."
                class="flex-1 h-10 px-4 rounded-lg border border-gray-300 text-sm focus:outline-none focus:border-gvn-red">
            <button type="submit" class="bg-gvn-red hover:bg-gvn-redHover text-white font-bold px-6 h-10 rounded-lg text-xs uppercase transition">
                <i class="fa-solid fa-magnifying-glass mr-1"></i> Tra cứu
            </button>
        </form>
    </div>

    <?php if (empty($orders)): ?>
        <div class="bg-white rounded-2xl border border-gray-200 p-8 text-center shadow-sm">
            <i class="fa-solid fa-box-open text-4xl text-gray-300 mb-3"></i>
            <p class="text-sm text-gray-500 mb-3">Chưa tìm thấy đơn hàng nào.</p>
            <a href="index.php" class="inline-block bg-gvn-red text-white font-bold px-5 py-2 rounded-xl text-xs">Mua sắm ngay</a>
        </div>
    <?php else: ?>
        <div class="space-y-4">
            <?php foreach ($orders as $order): ?>
                <div class="bg-white rounded-2xl border border-gray-200 p-4 sm:p-5 shadow-sm">
                    <div class="flex flex-wrap items-center justify-between gap-2 pb-3 mb-3 border-b border-gray-100">
                        <div class="text-sm font-extrabold text-gray-900">
                            Mã đơn: <span class="text-gvn-red"><?= e($order['code']) ?></span>
                            <span class="text-xs font-normal text-gray-400 ml-2"><?= e($order['created_at']) ?></span>
                        </div>
                        <span class="bg-amber-100 text-amber-800 text-xs font-bold px-3 py-1 rounded-full">
                            <?= e($order['status']) ?>
                        </span>
                    </div>
                    <div class="text-xs text-gray-600 space-y-1 mb-3">
                        <div><strong>Người nhận:</strong> <?= e($order['customer_name']) ?> - <?= e($order['phone']) ?></div>
                        <div><strong>Địa chỉ:</strong> <?= e($order['address']) ?></div>
                    </div>
                    <?php if (!empty($order['items'])): ?>
                        <div class="space-y-2 border-t border-gray-100 pt-3 mb-3">
                            <?php foreach ($order['items'] as $it): ?>
                                <div class="flex items-center justify-between text-xs">
                                    <span class="text-gray-800"><?= e($it['name']) ?> <strong class="text-gray-500">x<?= (int)$it['qty'] ?></strong></span>
                                    <span class="font-bold text-gray-900"><?= formatPrice((int)$it['price'] * (int)$it['qty']) ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    <div class="text-right text-sm font-extrabold text-gvn-red border-t border-gray-100 pt-2">
                        Tổng tiền: <?= formatPrice((int)$order['total_price']) ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
