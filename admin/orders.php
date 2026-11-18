<?php
require_once __DIR__ . '/_auth.php';
require_once __DIR__ . '/_layout.php';

$adminUser = requireAdmin();
initAdminData();

$orders = &$_SESSION['orders'];
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $id = (int)($_POST['id'] ?? 0);
    $newStatus = $_POST['status'] ?? '';

    if ($action === 'update_status' && $id > 0 && $newStatus !== '') {
        foreach ($orders as &$o) {
            if ((int)$o['id'] === $id) {
                $o['status'] = $newStatus;
                $message = 'Đã cập nhật trạng thái đơn ' . ($o['code'] ?? ('DH' . $id)) . ' thành "' . $newStatus . '"';
                break;
            }
        }
        unset($o);

        $pdo = getPDO();
        if ($pdo) {
            try {
                $stmt = $pdo->prepare('UPDATE orders SET status = ? WHERE id = ?');
                $stmt->execute([$newStatus, $id]);
            } catch (PDOException $e) {}
        }
    }
}

$statuses = ['Chờ xác nhận', 'Đang giao', 'Hoàn thành', 'Đã hủy'];
renderAdminHeader('Quản lý Đơn hàng', 'orders', $adminUser);
?>

<?php if ($message !== ''): ?>
    <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-semibold rounded-xl p-3 mb-4">
        <i class="fa-solid fa-circle-check mr-1"></i> <?= e($message) ?>
    </div>
<?php endif; ?>

<div class="space-y-4">
    <?php foreach ($orders as $o): ?>
        <div class="bg-white rounded-xl border border-gray-200 p-4 shadow-sm">
            <div class="flex flex-wrap items-center justify-between gap-3 pb-3 mb-3 border-b border-gray-100">
                <div>
                    <span class="text-sm font-extrabold text-gvn-red"><?= e($o['code'] ?? ('DH' . $o['id'])) ?></span>
                    <span class="text-xs text-gray-400 ml-2"><?= e($o['created_at']) ?></span>
                </div>
                <form method="POST" action="orders.php" class="flex items-center gap-2 text-xs">
                    <input type="hidden" name="action" value="update_status">
                    <input type="hidden" name="id" value="<?= (int)$o['id'] ?>">
                    <select name="status" class="h-8 px-2.5 rounded-lg border border-gray-300 font-semibold">
                        <?php foreach ($statuses as $st): ?>
                            <option value="<?= e($st) ?>" <?= ($o['status'] ?? '') === $st ? 'selected' : '' ?>><?= e($st) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" class="bg-black hover:bg-neutral-800 text-white font-bold px-3 h-8 rounded-lg transition">
                        Lưu
                    </button>
                </form>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-xs text-gray-600 mb-3">
                <div><strong>Khách hàng:</strong> <?= e($o['customer_name']) ?> - <?= e($o['phone']) ?></div>
                <div><strong>Địa chỉ:</strong> <?= e($o['address']) ?></div>
            </div>

            <?php if (!empty($o['items'])): ?>
                <div class="bg-gray-50 rounded-lg p-3 space-y-1.5 text-xs mb-2">
                    <?php foreach ($o['items'] as $it): ?>
                        <div class="flex justify-between">
                            <span><?= e($it['name']) ?> <strong>x<?= (int)$it['qty'] ?></strong></span>
                            <span class="font-bold"><?= formatPrice((int)$it['price'] * (int)$it['qty']) ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <div class="text-right text-sm font-extrabold text-gvn-red">
                Tổng tiền: <?= formatPrice((int)$o['total_price']) ?>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<?php renderAdminFooter(); ?>
