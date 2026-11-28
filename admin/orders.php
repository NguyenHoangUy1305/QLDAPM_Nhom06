<?php
require_once __DIR__ . '/_auth.php';
$adminUser = requireStaffOrAdmin();
require_once __DIR__ . '/_layout.php';

$pdo = getPDO();
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();

    $action = (string)($_POST['action'] ?? '');
    $id = (int)($_POST['id'] ?? 0);

    if (!$pdo) {
        $error = 'Không thể kết nối cơ sở dữ liệu.';
    } elseif ($id <= 0) {
        $error = 'Mã đơn hàng không hợp lệ.';
    } else {
        try {
            if ($action === 'approve_vietqr') {
                $stmt = $pdo->prepare('SELECT * FROM orders WHERE id = ? LIMIT 1');
                $stmt->execute([$id]);
                $order = $stmt->fetch();
                if (!$order) {
                    throw new RuntimeException('Không tìm thấy đơn hàng #' . $id);
                }
                if (($order['payment_method'] ?? '') !== 'VietQR') {
                    throw new RuntimeException('Đơn hàng này không sử dụng phương thức VietQR.');
                }
                $updated = transitionOrderStatus($pdo, $id, (string)$order['status'], 'paid');
                $message = 'Đã duyệt thanh toán VietQR cho đơn ' . $updated['order_code'] . '!';
            } elseif ($action === 'mark_expired_vietqr') {
                $stmt = $pdo->prepare('SELECT * FROM orders WHERE id = ? LIMIT 1');
                $stmt->execute([$id]);
                $order = $stmt->fetch();
                if (!$order) {
                    throw new RuntimeException('Không tìm thấy đơn hàng #' . $id);
                }
                if (($order['payment_method'] ?? '') !== 'VietQR') {
                    throw new RuntimeException('Đơn hàng này không sử dụng phương thức VietQR.');
                }
                $updated = transitionOrderStatus($pdo, $id, (string)$order['status'], 'expired');
                $message = 'Đã đánh dấu hết hạn VietQR cho đơn ' . $updated['order_code'] . '.';
            } elseif ($action === 'update_status') {
                $newStatus = (string)($_POST['status'] ?? '');
                $updated = transitionOrderStatus($pdo, $id, $newStatus, null);
                $message = 'Đã cập nhật trạng thái đơn ' . $updated['order_code'] . ' thành "' . $updated['status'] . '"'
                    . ($updated['status'] === 'Đã hủy' ? ' (đã hoàn tồn kho & hủy bảo hành).' : '.');
            }
        } catch (Throwable $e) {
            $error = $e->getMessage();
        }
    }
}

$filterType = (string)($_GET['order_type'] ?? 'all');
if (!in_array($filterType, ['all', 'online', 'walk_in', 'pc_build'], true)) {
    $filterType = 'all';
}
$filterStatus = (string)($_GET['status'] ?? 'all');
$filterPaymentStatus = (string)($_GET['payment_status'] ?? 'all');
$q = trim((string)($_GET['q'] ?? ''));

$staffNames = [];
if ($pdo) {
    try {
        foreach ($pdo->query("SELECT id, name FROM users WHERE role IN ('admin', 'staff')")->fetchAll() as $u) {
            $staffNames[(int)$u['id']] = (string)$u['name'];
        }
    } catch (PDOException $e) {
    }
}

$allOrders = getAdminOrders();
$orders = array_values(array_filter($allOrders, static function (array $o) use ($filterType, $filterStatus, $filterPaymentStatus, $q): bool {
    if ($filterType !== 'all' && ($o['order_type'] ?? 'online') !== $filterType) {
        return false;
    }
    if ($filterStatus !== 'all') {
        $st = (string)($o['status'] ?? '');
        if ($filterStatus === 'Đang giao hàng') {
            if (!in_array($st, ['Đang giao hàng', 'Đang giao'], true)) {
                return false;
            }
        } elseif ($st !== $filterStatus) {
            return false;
        }
    }
    if ($filterPaymentStatus !== 'all' && ($o['payment_status'] ?? 'unpaid') !== $filterPaymentStatus) {
        return false;
    }
    if ($q !== '') {
        $hay = mb_strtolower(($o['order_code'] ?? '') . ' ' . ($o['customer_name'] ?? '') . ' ' . ($o['customer_phone'] ?? ''), 'UTF-8');
        if (!str_contains($hay, mb_strtolower($q, 'UTF-8'))) {
            return false;
        }
    }
    return true;
}));

function getValidStatusOptionsForOrder(string $orderType, string $currentStatus): array
{
    if (in_array($currentStatus, ['Hoàn thành', 'Đã hủy'], true)) {
        return [$currentStatus];
    }
    if ($orderType === 'pc_build') {
        return match ($currentStatus) {
            'Chờ xác nhận' => ['Chờ xác nhận', 'Đang lắp ráp', 'Đã hủy'],
            'Đang lắp ráp' => ['Đang lắp ráp', 'Đang giao hàng', 'Đã hủy'],
            'Đang giao hàng', 'Đang giao' => ['Đang giao hàng', 'Hoàn thành', 'Đã hủy'],
            default => [$currentStatus],
        };
    }
    return match ($currentStatus) {
        'Chờ xác nhận' => ['Chờ xác nhận', 'Đang giao hàng', 'Hoàn thành', 'Đã hủy'],
        'Đang giao hàng', 'Đang giao' => ['Đang giao hàng', 'Hoàn thành', 'Đã hủy'],
        default => [$currentStatus],
    };
}

renderAdminHeader('Quản lý Đơn hàng & Pipeline Build PC', 'orders', $adminUser);
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

<!-- Bộ lọc -->
<div class="bg-white rounded-xl border border-gray-200 p-4 shadow-sm mb-5">
    <form method="GET" action="orders.php" class="grid grid-cols-1 sm:grid-cols-12 gap-2.5 text-xs items-end">
        <div class="sm:col-span-3">
            <label class="block font-bold mb-1">Tìm kiếm</label>
            <input type="text" name="q" value="<?= e($q) ?>" placeholder="Mã đơn (DH00001), tên, SĐT..."
                class="w-full h-9 px-3 rounded-lg border border-gray-300">
        </div>
        <div class="sm:col-span-2">
            <label class="block font-bold mb-1">Loại đơn</label>
            <select name="order_type" class="w-full h-9 px-2 rounded-lg border border-gray-300">
                <option value="all" <?= $filterType === 'all' ? 'selected' : '' ?>>Tất cả loại</option>
                <option value="online" <?= $filterType === 'online' ? 'selected' : '' ?>>Online</option>
                <option value="pc_build" <?= $filterType === 'pc_build' ? 'selected' : '' ?>>Build PC</option>
                <option value="walk_in" <?= $filterType === 'walk_in' ? 'selected' : '' ?>>Tại quầy</option>
            </select>
        </div>
        <div class="sm:col-span-3">
            <label class="block font-bold mb-1">Trạng thái đơn</label>
            <select name="status" class="w-full h-9 px-2 rounded-lg border border-gray-300">
                <option value="all" <?= $filterStatus === 'all' ? 'selected' : '' ?>>Tất cả trạng thái</option>
                <?php foreach (['Chờ xác nhận', 'Đang lắp ráp', 'Đang giao hàng', 'Hoàn thành', 'Đã hủy'] as $stOpt): ?>
                    <option value="<?= e($stOpt) ?>" <?= $filterStatus === $stOpt ? 'selected' : '' ?>><?= e($stOpt) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="sm:col-span-2">
            <label class="block font-bold mb-1">Thanh toán</label>
            <select name="payment_status" class="w-full h-9 px-2 rounded-lg border border-gray-300">
                <option value="all" <?= $filterPaymentStatus === 'all' ? 'selected' : '' ?>>Tất cả TT</option>
                <option value="pending_confirm" <?= $filterPaymentStatus === 'pending_confirm' ? 'selected' : '' ?>>Chờ duyệt VietQR</option>
                <option value="unpaid" <?= $filterPaymentStatus === 'unpaid' ? 'selected' : '' ?>>Chưa thanh toán</option>
                <option value="paid" <?= $filterPaymentStatus === 'paid' ? 'selected' : '' ?>>Đã thanh toán</option>
                <option value="expired" <?= $filterPaymentStatus === 'expired' ? 'selected' : '' ?>>Hết hạn QR</option>
            </select>
        </div>
        <div class="sm:col-span-2 flex gap-1.5">
            <button type="submit" class="flex-1 h-9 bg-black hover:bg-neutral-800 text-white font-bold rounded-lg">Lọc</button>
            <a href="orders.php" class="h-9 px-3 bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold rounded-lg flex items-center">Reset</a>
        </div>
    </form>
</div>

<table class="w-full block">
    <tbody class="block space-y-4">
    <?php if (empty($orders)): ?>
        <tr class="block bg-white rounded-xl border border-gray-200 p-8 text-center text-xs text-gray-500">
            <td class="block">Không có đơn hàng nào khớp bộ lọc hiện tại.</td>
        </tr>
    <?php endif; ?>

    <?php foreach ($orders as $o): ?>
        <?php
        $oType = (string)($o['order_type'] ?? 'online');
        $oStatus = (string)($o['status'] ?? 'Chờ xác nhận');
        $payMethod = (string)($o['payment_method'] ?? 'COD');
        $payStatus = (string)($o['payment_status'] ?? 'unpaid');
        $isTerminal = in_array($oStatus, ['Hoàn thành', 'Đã hủy'], true);
        $validNext = getValidStatusOptionsForOrder($oType, $oStatus);

        $typeBadge = match ($oType) {
            'pc_build' => ['Build PC', 'bg-purple-100 text-purple-800'],
            'walk_in' => ['Tại quầy', 'bg-sky-100 text-sky-800'],
            default => ['Online', 'bg-gray-100 text-gray-700'],
        };
        $payBadge = match ($payStatus) {
            'paid' => ['Đã thanh toán', 'bg-emerald-100 text-emerald-800'],
            'pending_confirm' => ['Chờ duyệt VietQR', 'bg-amber-100 text-amber-800'],
            'expired' => ['Hết hạn QR', 'bg-red-100 text-red-700'],
            default => ['Chưa thanh toán', 'bg-gray-100 text-gray-700'],
        };
        $statusBadgeCls = match ($oStatus) {
            'Hoàn thành' => 'bg-emerald-100 text-emerald-800',
            'Đã hủy' => 'bg-red-100 text-red-700',
            'Đang lắp ráp' => 'bg-purple-100 text-purple-800',
            'Đang giao hàng', 'Đang giao' => 'bg-blue-100 text-blue-800',
            default => 'bg-amber-100 text-amber-800',
        };
        $canApproveVietQr = ($payMethod === 'VietQR') && in_array($payStatus, ['unpaid', 'pending_confirm'], true) && !$isTerminal;
        $pcSpecs = null;
        if ($oType === 'pc_build' && !empty($o['pc_build_specs'])) {
            $decoded = json_decode((string)$o['pc_build_specs'], true);
            if (is_array($decoded)) {
                $pcSpecs = $decoded;
            }
        }
        ?>
        <tr class="order-card block bg-white rounded-xl border border-gray-200 p-4 shadow-sm" data-order-code="<?= e($o['order_code'] ?? '') ?>">
            <td class="block">
            <div class="flex flex-wrap items-center justify-between gap-3 pb-3 mb-3 border-b border-gray-100">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="text-sm font-extrabold text-gvn-red"><?= e($o['order_code'] ?? ('DH' . $o['id'])) ?></span>
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold <?= $typeBadge[1] ?>"><?= e($typeBadge[0]) ?></span>
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold <?= $statusBadgeCls ?>"><?= e($oStatus) ?></span>
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-zinc-100 text-zinc-800"><?= e($payMethod) ?></span>
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold <?= $payBadge[1] ?>"><?= e($payBadge[0]) ?></span>
                    <span class="text-[11px] text-gray-400"><?= e($o['created_at']) ?></span>
                </div>

                <div class="flex flex-wrap items-center gap-2 text-xs">
                    <?php if ($canApproveVietQr): ?>
                        <form method="POST" action="orders.php" class="inline">
                            <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                            <input type="hidden" name="action" value="approve_vietqr">
                            <input type="hidden" name="id" value="<?= (int)$o['id'] ?>">
                            <button type="submit" class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold px-3 h-8 rounded-lg transition">
                                <i class="fa-solid fa-check mr-1"></i> Duyệt VietQR
                            </button>
                        </form>
                        <form method="POST" action="orders.php" class="inline">
                            <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                            <input type="hidden" name="action" value="mark_expired_vietqr">
                            <input type="hidden" name="id" value="<?= (int)$o['id'] ?>">
                            <button type="submit" class="bg-amber-100 hover:bg-amber-200 text-amber-900 font-bold px-2.5 h-8 rounded-lg transition">
                                Hết hạn QR
                            </button>
                        </form>
                    <?php endif; ?>

                    <?php if ($isTerminal): ?>
                        <span class="text-[11px] font-bold text-gray-500 px-2.5 py-1 bg-gray-100 rounded-lg">
                            Trạng thái cuối (Khóa)
                        </span>
                    <?php else: ?>
                        <form method="POST" action="orders.php" class="flex items-center gap-1.5">
                            <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                            <input type="hidden" name="action" value="update_status">
                            <input type="hidden" name="id" value="<?= (int)$o['id'] ?>">
                            <select name="status" class="h-8 px-2.5 rounded-lg border border-gray-300 font-semibold">
                                <?php foreach ($validNext as $st): ?>
                                    <?php $selected = ($oStatus === $st) || ($oStatus === 'Đang giao' && $st === 'Đang giao hàng'); ?>
                                    <option value="<?= e($st) ?>" <?= $selected ? 'selected' : '' ?>><?= e($st) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <button type="submit" class="bg-black hover:bg-neutral-800 text-white font-bold px-3 h-8 rounded-lg transition">
                                Cập nhật
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-2 text-xs text-gray-600 mb-3">
                <div>
                    <strong>Khách hàng:</strong> <?= e($o['customer_name']) ?> — <?= e($o['customer_phone'] ?? $o['phone']) ?>
                    <?php if (!empty($o['created_by_staff_id']) && isset($staffNames[(int)$o['created_by_staff_id']])): ?>
                        <span class="ml-2 px-2 py-0.5 rounded bg-sky-50 text-sky-800 font-semibold">
                            NV tạo: <?= e($staffNames[(int)$o['created_by_staff_id']]) ?>
                        </span>
                    <?php endif; ?>
                </div>
                <div><strong>Địa chỉ:</strong> <?= e($o['customer_address'] ?? $o['address']) ?></div>
                <?php if (!empty($o['note'])): ?>
                    <div class="md:col-span-2 text-gray-500"><strong>Ghi chú:</strong> <?= e($o['note']) ?></div>
                <?php endif; ?>
            </div>

            <?php if ($oType === 'pc_build'): ?>
                <div class="bg-purple-50 border border-purple-200 rounded-lg p-2.5 text-[11px] text-purple-900 mb-3">
                    <div class="font-extrabold uppercase mb-1">
                        <i class="fa-solid fa-computer mr-1"></i> Pipeline Build PC: Chờ xác nhận → Đang lắp ráp → Đang giao hàng → Hoàn thành
                    </div>
                    <?php if ($pcSpecs): ?>
                        <div class="flex flex-wrap gap-x-4 gap-y-1">
                            <?php foreach ($pcSpecs as $k => $v): ?>
                                <?php if (is_scalar($v) && (string)$v !== ''): ?>
                                    <span><strong><?= e((string)$k) ?>:</strong> <?= e((string)$v) ?></span>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($o['items'])): ?>
                <div class="bg-gray-50 rounded-lg p-3 space-y-1.5 text-xs mb-2">
                    <?php foreach ($o['items'] as $it): ?>
                        <div class="flex justify-between gap-2">
                            <span>
                                <?php if (!empty($it['component_slot'])): ?>
                                    <span class="inline-block px-1.5 py-0.5 rounded bg-purple-100 text-purple-800 font-bold text-[10px] uppercase mr-1">
                                        <?= e($it['component_slot']) ?>
                                    </span>
                                <?php endif; ?>
                                <?= e($it['product_name'] ?? $it['name']) ?> <strong>x<?= (int)($it['quantity'] ?? $it['qty']) ?></strong>
                            </span>
                            <span class="font-bold shrink-0"><?= formatPrice((int)($it['product_price'] ?? $it['price']) * (int)($it['quantity'] ?? $it['qty'])) ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <div class="flex items-center justify-between text-xs pt-1">
                <a href="warranties.php?q=<?= e($o['order_code'] ?? $o['code']) ?>" class="text-blue-600 hover:underline font-semibold">
                    <i class="fa-solid fa-shield-halved mr-1"></i> Xem phiếu bảo hành của đơn
                </a>
                <div class="text-sm font-extrabold text-gvn-red">
                    Tổng tiền: <?= formatPrice((int)($o['total_amount'] ?? $o['total_price'])) ?>
                </div>
            </div>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>

<?php renderAdminFooter(); ?>
