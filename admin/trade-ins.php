<?php
require_once __DIR__ . '/_auth.php';
$adminUser = requireStaffOrAdmin();
require_once __DIR__ . '/_layout.php';

$pdo = getPDO();
$message = '';
$error = '';
$allowedStatuses = ['Chờ định giá', 'Đã báo giá', 'Hoàn tất', 'Từ chối'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();

    if (!$pdo) {
        $error = 'Không thể kết nối cơ sở dữ liệu.';
    } else {
        $action = (string)($_POST['action'] ?? '');
        if ($action === 'update_trade_in') {
            $id = (int)($_POST['id'] ?? 0);
            $status = (string)($_POST['status'] ?? '');
            $rawEstVal = (float)($_POST['estimated_value'] ?? 0);
            $adminNote = trim((string)($_POST['admin_note'] ?? ''));

            if ($rawEstVal < 0) {
                $error = 'Giá trị định giá không hợp lệ (không được âm).';
            } elseif ($id <= 0 || !in_array($status, $allowedStatuses, true)) {
                $error = 'Thông tin cập nhật thu cũ đổi mới không hợp lệ.';
            } else {
                $estimatedValue = (int)round($rawEstVal);
                try {
                    $stmt = $pdo->prepare('
                        UPDATE trade_in_requests
                        SET estimated_value = ?, status = ?, admin_note = ?, updated_at = NOW()
                        WHERE id = ?
                    ');
                    $stmt->execute([$estimatedValue, $status, $adminNote !== '' ? $adminNote : null, $id]);
                    if ($stmt->rowCount() > 0) {
                        $message = 'Đã cập nhật yêu cầu Thu cũ #' . $id . ' (' . $status . ' — ' . formatPrice($estimatedValue) . ')!';
                    } else {
                        $error = 'Không tìm thấy yêu cầu thu cũ #' . $id;
                    }
                } catch (PDOException $e) {
                    $error = 'Không thể cập nhật yêu cầu thu cũ đổi mới.';
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

$tradeIns = [];
if ($pdo) {
    try {
        $sql = '
            SELECT t.*, p.name AS target_product_name, p.price AS target_product_price
            FROM trade_in_requests t
            LEFT JOIN products p ON p.id = t.target_product_id
            WHERE 1=1
        ';
        $params = [];
        if ($filterStatus !== 'all') {
            $sql .= ' AND t.status = ?';
            $params[] = $filterStatus;
        }
        if ($q !== '') {
            $like = '%' . $q . '%';
            $sql .= ' AND (t.request_code LIKE ? OR t.customer_name LIKE ? OR t.customer_phone LIKE ? OR t.device_name LIKE ?)';
            $params = array_merge($params, [$like, $like, $like, $like]);
        }
        $sql .= ' ORDER BY t.id DESC';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $tradeIns = $stmt->fetchAll();
    } catch (PDOException $e) {
    }
}

renderAdminHeader('Quản lý Thu cũ đổi mới', 'tradeins', $adminUser);
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
    <form method="GET" action="trade-ins.php" class="flex flex-wrap items-end gap-3 text-xs">
        <div class="flex-1 min-w-[220px]">
            <label class="block font-bold mb-1">Tìm kiếm yêu cầu</label>
            <input type="text" name="q" value="<?= e($q) ?>" placeholder="Mã YC (TC...), tên khách, SĐT, tên thiết bị..."
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
        <button type="submit" class="h-9 px-4 bg-black text-white font-bold rounded-lg">Lọc</button>
        <a href="trade-ins.php" class="h-9 px-3 bg-gray-100 text-gray-700 font-bold rounded-lg flex items-center">Reset</a>
    </form>
</div>

<table class="w-full block">
    <tbody class="block space-y-4">
    <?php if (empty($tradeIns)): ?>
        <tr class="block bg-white rounded-xl border border-gray-200 p-8 text-center text-xs text-gray-500">
            <td class="block">Chưa có yêu cầu Thu cũ đổi mới nào khớp điều kiện.</td>
        </tr>
    <?php endif; ?>

    <?php foreach ($tradeIns as $t): ?>
        <?php
        $badgeCls = match ($t['status']) {
            'Hoàn tất' => 'bg-emerald-100 text-emerald-800',
            'Đã báo giá' => 'bg-blue-100 text-blue-800',
            'Từ chối' => 'bg-red-100 text-red-700',
            default => 'bg-amber-100 text-amber-800',
        };
        ?>
        <tr class="block bg-white rounded-xl border border-gray-200 p-4 shadow-sm">
            <td class="block">
            <div class="flex flex-wrap items-center justify-between gap-2 pb-2.5 mb-3 border-b border-gray-100">
                <div class="flex items-center gap-2">
                    <span class="text-sm font-extrabold text-gvn-red"><?= e($t['request_code']) ?></span>
                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold <?= $badgeCls ?>"><?= e($t['status']) ?></span>
                    <span class="text-[11px] text-gray-400"><?= e($t['created_at']) ?></span>
                </div>
                <div class="text-xs font-extrabold text-gray-900">
                    Định giá thu vào: <span class="text-gvn-red"><?= formatPrice((int)round((float)$t['estimated_value'])) ?></span>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-xs text-gray-700 mb-3">
                <div class="space-y-1">
                    <div><strong>Khách hàng:</strong> <?= e($t['customer_name']) ?> — <?= e($t['customer_phone']) ?></div>
                    <div><strong>Thiết bị khách bán/đổi:</strong> <?= e($t['device_name']) ?></div>
                    <div><strong>Tình trạng mô tả:</strong> <?= e($t['device_condition']) ?></div>
                </div>
                <div class="space-y-1">
                    <div><strong>Sản phẩm muốn lên đời:</strong> <?= e($t['target_product_name'] ?: 'Chưa chọn cụ thể') ?></div>
                    <div><strong>Ghi chú khách hàng:</strong> <?= e($t['notes'] ?: '-') ?></div>
                    <div><strong>Ghi chú nhân viên:</strong> <?= e($t['admin_note'] ?: '-') ?></div>
                </div>
            </div>

            <form method="POST" action="trade-ins.php?status=<?= urlencode($filterStatus) ?>&q=<?= urlencode($q) ?>" class="bg-gray-50 rounded-lg p-3 grid grid-cols-1 sm:grid-cols-12 gap-2 text-xs items-end">
                <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                <input type="hidden" name="action" value="update_trade_in">
                <input type="hidden" name="id" value="<?= (int)$t['id'] ?>">

                <div class="sm:col-span-3">
                    <label class="block font-bold mb-1">Giá thu vào (VNĐ)</label>
                    <input type="number" name="estimated_value" min="0" step="50000" value="<?= (int)round((float)$t['estimated_value']) ?>" required
                        class="w-full h-9 px-2.5 rounded-lg border border-gray-300 bg-white font-bold">
                </div>
                <div class="sm:col-span-3">
                    <label class="block font-bold mb-1">Trạng thái xử lý</label>
                    <select name="status" class="w-full h-9 px-2.5 rounded-lg border border-gray-300 bg-white font-semibold">
                        <?php foreach ($allowedStatuses as $st): ?>
                            <option value="<?= e($st) ?>" <?= ($t['status'] ?? '') === $st ? 'selected' : '' ?>><?= e($st) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="sm:col-span-4">
                    <label class="block font-bold mb-1">Ghi chú định giá / phản hồi</label>
                    <input type="text" name="admin_note" value="<?= e($t['admin_note'] ?? '') ?>" placeholder="VD: Máy đẹp 98%, hỗ trợ bù chênh lệch..."
                        class="w-full h-9 px-2.5 rounded-lg border border-gray-300 bg-white">
                </div>
                <div class="sm:col-span-2">
                    <button type="submit" class="w-full h-9 bg-gvn-red hover:bg-gvn-redHover text-white font-bold rounded-lg uppercase transition">
                        Lưu
                    </button>
                </div>
            </form>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>

<?php renderAdminFooter(); ?>
