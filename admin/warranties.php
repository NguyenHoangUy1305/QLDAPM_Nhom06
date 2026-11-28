<?php
require_once __DIR__ . '/_auth.php';
$adminUser = requireStaffOrAdmin();
require_once __DIR__ . '/_layout.php';

$pdo = getPDO();
$message = '';
$error = '';
$allowedStatuses = ['Còn hạn', 'Đang bảo hành', 'Hết hạn', 'Đã hủy'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();

    if (!$pdo) {
        $error = 'Không thể kết nối cơ sở dữ liệu.';
    } else {
        $action = (string)($_POST['action'] ?? '');
        if ($action === 'update_warranty') {
            $id = (int)($_POST['id'] ?? 0);
            $status = (string)($_POST['status'] ?? '');
            $serialNumber = trim((string)($_POST['serial_number'] ?? ''));
            $notes = trim((string)($_POST['notes'] ?? ''));

            if ($id <= 0 || !in_array($status, $allowedStatuses, true)) {
                $error = 'Thông tin cập nhật bảo hành không hợp lệ.';
            } else {
                try {
                    if ($serialNumber !== '') {
                        $stmt = $pdo->prepare('UPDATE warranties SET status = ?, serial_number = ?, notes = ? WHERE id = ?');
                        $stmt->execute([$status, $serialNumber, $notes !== '' ? $notes : null, $id]);
                    } else {
                        $stmt = $pdo->prepare('UPDATE warranties SET status = ?, notes = ? WHERE id = ?');
                        $stmt->execute([$status, $notes !== '' ? $notes : null, $id]);
                    }
                    $message = 'Đã cập nhật phiếu bảo hành #' . $id . ' thành "' . $status . '"!';
                } catch (PDOException $e) {
                    $error = 'Không thể cập nhật bảo hành (kiểm tra trùng Serial nếu có): ' . $e->getMessage();
                }
            }
        }
    }
}

$filterStatus = (string)($_GET['status'] ?? 'all');
if ($filterStatus !== 'all' && !in_array($filterStatus, $allowedStatuses, true)) {
    $filterStatus = 'all';
}
$q = trim((string)($_GET['q'] ?? ''));

$warranties = [];
if ($pdo) {
    try {
        $sql = '
            SELECT w.*, o.order_code, o.customer_name AS order_customer_name, o.status AS order_status
            FROM warranties w
            LEFT JOIN orders o ON o.id = w.order_id
            WHERE 1=1
        ';
        $params = [];
        if ($filterStatus !== 'all') {
            $sql .= ' AND w.status = ?';
            $params[] = $filterStatus;
        }
        if ($q !== '') {
            $like = '%' . $q . '%';
            $sql .= ' AND (w.warranty_code LIKE ? OR w.serial_number LIKE ? OR w.customer_phone LIKE ? OR w.product_name LIKE ? OR o.order_code LIKE ?)';
            $params = array_merge($params, [$like, $like, $like, $like, $like]);
        }
        $sql .= ' ORDER BY w.id DESC LIMIT 200';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $warranties = $stmt->fetchAll();
    } catch (PDOException $e) {
    }
}

renderAdminHeader('Quản lý Bảo hành điện tử', 'warranties', $adminUser);
?>

<?php if ($message !== ''): ?>
    <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-semibold rounded-xl p-3 mb-4">
        <i class="fa-solid fa-circle-check mr-1"></i> <?= e($message) ?>
    </div>
<?php endif; ?>

<?php if ($error !== ''): ?>
    <div class="bg-red-50 border border-red-200 text-red-700 text-xs font-semibold rounded-xl p-3 mb-4">
        <i class="fa-solid fa-triangle-exclamation mr-1"></i> <?= e($error) ?>
    </div>
<?php endif; ?>

<div class="bg-white rounded-xl border border-gray-200 p-4 shadow-sm mb-5">
    <form method="GET" action="warranties.php" class="flex flex-wrap items-end gap-3 text-xs">
        <div class="flex-1 min-w-[220px]">
            <label class="block font-bold mb-1">Tìm kiếm bảo hành</label>
            <input type="text" name="q" value="<?= e($q) ?>" placeholder="Mã BH (BH...), Serial, SĐT, mã đơn (DH...), tên sản phẩm..."
                class="w-full h-9 px-3 rounded-lg border border-gray-300">
        </div>
        <div class="w-48">
            <label class="block font-bold mb-1">Trạng thái</label>
            <select name="status" class="w-full h-9 px-2.5 rounded-lg border border-gray-300">
                <option value="all" <?= $filterStatus === 'all' ? 'selected' : '' ?>>Tất cả trạng thái</option>
                <?php foreach ($allowedStatuses as $st): ?>
                    <option value="<?= e($st) ?>" <?= $filterStatus === $st ? 'selected' : '' ?>><?= e($st) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit" class="h-9 px-4 bg-black text-white font-bold rounded-lg">Tra cứu</button>
        <a href="warranties.php" class="h-9 px-3 bg-gray-100 text-gray-700 font-bold rounded-lg flex items-center">Reset</a>
    </form>
</div>

<div class="bg-white rounded-xl border border-gray-200 p-4 shadow-sm">
    <h2 class="text-sm font-extrabold uppercase mb-3">Danh sách phiếu bảo hành (<?= count($warranties) ?>)</h2>
    <div class="overflow-x-auto">
        <table class="w-full text-xs text-left">
            <thead class="bg-gray-50 text-gray-600 uppercase">
                <tr>
                    <th class="p-2.5">Mã BH / Serial</th>
                    <th class="p-2.5">Sản phẩm & Đơn hàng</th>
                    <th class="p-2.5">Khách hàng</th>
                    <th class="p-2.5">Thời hạn</th>
                    <th class="p-2.5">Cập nhật xử lý</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <?php if (empty($warranties)): ?>
                    <tr><td colspan="5" class="p-4 text-center text-gray-500">Không tìm thấy phiếu bảo hành nào.</td></tr>
                <?php endif; ?>
                <?php foreach ($warranties as $w): ?>
                    <tr>
                        <td class="p-2.5 align-top">
                            <div class="font-extrabold text-gvn-red"><?= e($w['warranty_code']) ?></div>
                            <div class="text-[11px] text-gray-500 font-mono"><?= e($w['serial_number'] ?: '-') ?></div>
                        </td>
                        <td class="p-2.5 align-top max-w-[240px]">
                            <div class="font-semibold line-clamp-2"><?= e($w['product_name']) ?></div>
                            <div class="text-[11px] text-gray-500">
                                Đơn: <a href="orders.php?q=<?= e($w['order_code']) ?>" class="text-blue-600 font-bold hover:underline"><?= e($w['order_code']) ?></a>
                                (<?= e($w['order_status'] ?? '') ?>)
                            </div>
                        </td>
                        <td class="p-2.5 align-top">
                            <div class="font-semibold"><?= e($w['order_customer_name'] ?: '-') ?></div>
                            <div class="text-[11px] text-gray-500"><?= e($w['customer_phone']) ?></div>
                        </td>
                        <td class="p-2.5 align-top whitespace-nowrap">
                            <div><strong><?= (int)$w['warranty_months'] ?> tháng</strong></div>
                            <div class="text-[11px] text-gray-500"><?= e($w['start_date']) ?> → <?= e($w['end_date']) ?></div>
                        </td>
                        <td class="p-2.5 align-top">
                            <form method="POST" action="warranties.php?status=<?= urlencode($filterStatus) ?>&q=<?= urlencode($q) ?>" class="flex flex-wrap gap-1.5 items-center">
                                <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                                <input type="hidden" name="action" value="update_warranty">
                                <input type="hidden" name="id" value="<?= (int)$w['id'] ?>">
                                <select name="status" class="h-8 px-2 rounded border border-gray-300 font-semibold">
                                    <?php foreach ($allowedStatuses as $st): ?>
                                        <option value="<?= e($st) ?>" <?= ($w['status'] ?? '') === $st ? 'selected' : '' ?>><?= e($st) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <input type="text" name="serial_number" value="<?= e($w['serial_number'] ?? '') ?>" placeholder="Serial"
                                    class="h-8 w-32 px-2 rounded border border-gray-300">
                                <input type="text" name="notes" value="<?= e($w['notes'] ?? '') ?>" placeholder="Ghi chú bảo hành..."
                                    class="h-8 w-44 px-2 rounded border border-gray-300">
                                <button type="submit" class="h-8 px-3 bg-black hover:bg-neutral-800 text-white font-bold rounded">Lưu</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php renderAdminFooter(); ?>
