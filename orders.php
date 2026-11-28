<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/data/store.php';

$searchCode = trim((string)($_GET['code'] ?? $_GET['q'] ?? ''));
$user = currentUser();
$pdo = getPDO();
$flashSuccess = '';
$flashError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $pdo) {
    requireCsrf();
    $action = trim((string)($_POST['action'] ?? ''));
    $orderId = (int)($_POST['order_id'] ?? 0);
    $lookupReturn = trim((string)($_POST['lookup_code'] ?? $searchCode));

    if ($orderId > 0) {
        try {
            $stmt = $pdo->prepare('SELECT * FROM orders WHERE id = ? LIMIT 1');
            $stmt->execute([$orderId]);
            $targetOrder = $stmt->fetch();

            if (!$targetOrder) {
                throw new RuntimeException('Không tìm thấy đơn hàng.');
            }

            $sessionCodes = $_SESSION['last_order_codes'] ?? [];
            $isOwner = $user !== null && !empty($targetOrder['user_id']) && (int)$targetOrder['user_id'] === (int)$user['id'];
            $isStaffOrAdmin = hasRole('admin', 'staff');
            $isSessionOrder = is_array($sessionCodes) && in_array($targetOrder['order_code'], $sessionCodes, true);
            $matchesLookup = $lookupReturn !== '' && (
                strcasecmp($lookupReturn, (string)$targetOrder['order_code']) === 0
                || preg_replace('/\D+/', '', $lookupReturn) === preg_replace('/\D+/', '', (string)$targetOrder['customer_phone'])
            );

            if (!$isOwner && !$isStaffOrAdmin && !$isSessionOrder && !$matchesLookup) {
                throw new RuntimeException('Bạn không có quyền thực hiện thao tác trên đơn hàng này.');
            }

            if ($action === 'cancel_order') {
                if ((string)$targetOrder['status'] !== 'Chờ xác nhận') {
                    throw new RuntimeException('Chỉ có thể hủy đơn hàng khi đang ở trạng thái "Chờ xác nhận".');
                }
                transitionOrderStatus($pdo, $orderId, 'Đã hủy');
                $_SESSION['orders_flash_ok'] = 'Đã hủy đơn hàng ' . $targetOrder['order_code'] . ', hoàn trả tồn kho và hủy hiệu lực phiếu bảo hành liên quan.';
            } elseif ($action === 'confirm_vietqr') {
                if ((string)$targetOrder['payment_method'] !== 'VietQR') {
                    throw new RuntimeException('Đơn hàng không sử dụng thanh toán VietQR.');
                }
                if ((string)$targetOrder['status'] === 'Đã hủy') {
                    throw new RuntimeException('Đơn hàng đã bị hủy.');
                }
                if (!empty($targetOrder['payment_expires_at']) && strtotime((string)$targetOrder['payment_expires_at']) <= time()) {
                    if ($targetOrder['payment_status'] === 'unpaid') {
                        $pdo->prepare("UPDATE orders SET payment_status = 'expired', updated_at = NOW() WHERE id = ?")->execute([$orderId]);
                    }
                    throw new RuntimeException('Mã VietQR của đơn ' . $targetOrder['order_code'] . ' đã hết hạn (15 phút).');
                }
                if ((string)$targetOrder['payment_status'] === 'unpaid') {
                    $pdo->prepare("UPDATE orders SET payment_status = 'pending_confirm', updated_at = NOW() WHERE id = ?")->execute([$orderId]);
                    $_SESSION['orders_flash_ok'] = 'Đã ghi nhận xác nhận chuyển khoản VietQR cho đơn ' . $targetOrder['order_code'] . '.';
                }
            }
        } catch (Throwable $e) {
            $_SESSION['orders_flash_err'] = $e->getMessage();
        }
    }

    $redir = 'orders.php' . ($lookupReturn !== '' ? '?code=' . urlencode($lookupReturn) : '');
    header('Location: ' . $redir);
    exit;
}

if (!empty($_SESSION['orders_flash_ok'])) {
    $flashSuccess = (string)$_SESSION['orders_flash_ok'];
    unset($_SESSION['orders_flash_ok']);
}
if (!empty($_SESSION['orders_flash_err'])) {
    $flashError = (string)$_SESSION['orders_flash_err'];
    unset($_SESSION['orders_flash_err']);
}

$orders = [];
if ($pdo) {
    try {
        if ($searchCode !== '') {
            $codeUpper = strtoupper($searchCode);
            $digits = preg_replace('/\D+/', '', $searchCode);
            if ($digits !== '' && $digits !== $searchCode) {
                $stmt = $pdo->prepare('SELECT * FROM orders WHERE order_code = ? OR customer_phone = ? OR customer_phone = ? ORDER BY id DESC');
                $stmt->execute([$codeUpper, $searchCode, $digits]);
            } else {
                $stmt = $pdo->prepare('SELECT * FROM orders WHERE order_code = ? OR customer_phone = ? ORDER BY id DESC');
                $stmt->execute([$codeUpper, $searchCode]);
            }
            $dbOrders = $stmt->fetchAll();
        } elseif ($user !== null) {
            $stmt = $pdo->prepare('SELECT * FROM orders WHERE user_id = ? ORDER BY id DESC');
            $stmt->execute([(int)$user['id']]);
            $dbOrders = $stmt->fetchAll();
        } else {
            $dbOrders = [];
        }

        if (!empty($dbOrders)) {
            $itemStmt = $pdo->prepare('
                SELECT oi.id, oi.product_id, oi.quantity AS qty, oi.product_price AS price,
                       oi.component_slot, COALESCE(oi.product_name, p.name, "Sản phẩm") AS name
                FROM order_items oi
                LEFT JOIN products p ON p.id = oi.product_id
                WHERE oi.order_id = ?
                ORDER BY oi.id ASC
            ');
            $warrantyStmt = $pdo->prepare('SELECT warranty_code, serial_number, status FROM warranties WHERE order_id = ? ORDER BY id ASC');

            foreach ($dbOrders as $o) {
                if (
                    ($o['payment_method'] ?? '') === 'VietQR'
                    && ($o['payment_status'] ?? '') === 'unpaid'
                    && !empty($o['payment_expires_at'])
                    && strtotime((string)$o['payment_expires_at']) <= time()
                ) {
                    $pdo->prepare("UPDATE orders SET payment_status = 'expired', updated_at = NOW() WHERE id = ? AND payment_status = 'unpaid'")
                        ->execute([(int)$o['id']]);
                    $o['payment_status'] = 'expired';
                }

                $itemStmt->execute([(int)$o['id']]);
                $dbItems = $itemStmt->fetchAll();

                $warrantyStmt->execute([(int)$o['id']]);
                $dbWarranties = $warrantyStmt->fetchAll();

                $orders[] = [
                    'id' => (int)$o['id'],
                    'order_code' => (string)$o['order_code'],
                    'order_type' => (string)($o['order_type'] ?? 'online'),
                    'customer_name' => (string)$o['customer_name'],
                    'customer_phone' => (string)$o['customer_phone'],
                    'customer_address' => (string)$o['customer_address'],
                    'payment_method' => (string)($o['payment_method'] ?? 'COD'),
                    'payment_status' => (string)($o['payment_status'] ?? 'unpaid'),
                    'payment_expires_at' => $o['payment_expires_at'],
                    'total_amount' => (int)round((float)$o['total_amount']),
                    'status' => (string)$o['status'],
                    'pc_build_specs' => $o['pc_build_specs'] ?? null,
                    'created_at' => !empty($o['created_at']) ? date('d/m/Y H:i', strtotime((string)$o['created_at'])) : '',
                    'items' => $dbItems ?: [],
                    'warranties' => $dbWarranties ?: [],
                ];
            }
        }
    } catch (PDOException $e) {
        $orders = [];
    }
}

$orderTypeLabels = [
    'online' => ['label' => 'Đặt Online', 'class' => 'bg-blue-50 text-blue-700'],
    'walk_in' => ['label' => 'Tại quầy', 'class' => 'bg-purple-50 text-purple-700'],
    'pc_build' => ['label' => 'Build PC', 'class' => 'bg-red-50 text-gvn-red'],
];

$paymentStatusLabels = [
    'unpaid' => ['label' => 'Chưa thanh toán', 'class' => 'bg-amber-50 text-amber-700'],
    'pending_confirm' => ['label' => 'Chờ xác nhận CK', 'class' => 'bg-blue-50 text-blue-700'],
    'paid' => ['label' => 'Đã thanh toán', 'class' => 'bg-emerald-50 text-emerald-700'],
    'expired' => ['label' => 'QR hết hạn', 'class' => 'bg-red-50 text-red-700'],
];

$statusBadgeColors = [
    'Chờ xác nhận' => 'bg-amber-100 text-amber-800',
    'Đang lắp ráp' => 'bg-indigo-100 text-indigo-800',
    'Đang giao hàng' => 'bg-blue-100 text-blue-800',
    'Đang giao' => 'bg-blue-100 text-blue-800',
    'Hoàn thành' => 'bg-emerald-100 text-emerald-800',
    'Đã hủy' => 'bg-red-100 text-red-700',
];

$pageTitle = 'Tra cứu & Lịch sử đơn hàng - ĐOÀN 6';
require_once __DIR__ . '/includes/header.php';
?>

<div class="max-w-[960px] mx-auto px-3 py-6 space-y-5">
    <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
    <div class="bg-white rounded-2xl border border-gray-200 p-5 shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-2 mb-2">
            <h1 class="text-xl font-extrabold text-gray-900 uppercase">Tra cứu & Lịch sử đơn hàng</h1>
            <?php if ($user !== null): ?>
                <span class="text-xs text-gray-500">Đang đăng nhập: <strong class="text-gray-800"><?= e($user['name']) ?></strong></span>
            <?php endif; ?>
        </div>
        <p class="text-xs text-gray-500 mb-4">Nhập mã đơn hàng hoặc số điện thoại đặt hàng để kiểm tra trạng thái xử lý và bảo hành.</p>

        <form method="GET" action="orders.php" class="flex flex-col sm:flex-row gap-2.5">
            <input type="text" name="code" value="<?= e($searchCode) ?>" placeholder="Nhập mã đơn hàng (DH...) hoặc SĐT..."
                class="flex-1 h-10 px-4 rounded-lg border border-gray-300 text-sm focus:outline-none focus:border-gvn-red">
            <button type="submit" class="bg-gvn-red hover:bg-gvn-redHover text-white font-bold px-6 h-10 rounded-lg text-xs uppercase transition">
                <i class="fa-solid fa-magnifying-glass mr-1"></i> Tra cứu
            </button>
            <?php if ($searchCode !== '' && $user !== null): ?>
                <a href="orders.php" class="h-10 px-4 rounded-lg border border-gray-300 text-xs font-bold text-gray-700 hover:bg-gray-50 flex items-center justify-center">
                    Đơn của tôi
                </a>
            <?php endif; ?>
        </form>
    </div>

    <?php if ($flashSuccess !== ''): ?>
        <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-semibold rounded-xl p-3">
            <i class="fa-solid fa-circle-check mr-1"></i> <?= e($flashSuccess) ?>
        </div>
    <?php endif; ?>
    <?php if ($flashError !== ''): ?>
        <div class="bg-red-50 border border-red-200 text-red-700 text-xs font-semibold rounded-xl p-3">
            <i class="fa-solid fa-triangle-exclamation mr-1"></i> <?= e($flashError) ?>
        </div>
    <?php endif; ?>

    <?php if (empty($orders)): ?>
        <div class="bg-white rounded-2xl border border-gray-200 p-8 text-center shadow-sm">
            <i class="fa-solid fa-box-open text-4xl text-gray-300 mb-3"></i>
            <p class="text-sm text-gray-500 mb-3">
                <?= $searchCode !== '' ? 'Không tìm thấy đơn hàng nào khớp với "' . e($searchCode) . '".' : ($user ? 'Bạn chưa có đơn hàng nào.' : 'Vui lòng nhập mã đơn hàng hoặc số điện thoại để tra cứu, hoặc đăng nhập để xem lịch sử mua hàng.') ?>
            </p>
            <div class="flex justify-center gap-2">
                <?php if ($user === null): ?>
                    <a href="login.php?redirect=orders.php" class="inline-block bg-black text-white font-bold px-5 py-2 rounded-xl text-xs">Đăng nhập</a>
                <?php endif; ?>
                <a href="index.php" class="inline-block bg-gvn-red text-white font-bold px-5 py-2 rounded-xl text-xs">Mua sắm ngay</a>
            </div>
        </div>
    <?php else: ?>
        <div class="space-y-4">
            <?php foreach ($orders as $order): ?>
                <?php
                $typeBadge = $orderTypeLabels[$order['order_type']] ?? $orderTypeLabels['online'];
                $payBadge = $paymentStatusLabels[$order['payment_status']] ?? $paymentStatusLabels['unpaid'];
                $statusClass = $statusBadgeColors[$order['status']] ?? 'bg-gray-100 text-gray-800';
                $steps = $order['order_type'] === 'pc_build'
                    ? ['Chờ xác nhận', 'Đang lắp ráp', 'Đang giao hàng', 'Hoàn thành']
                    : ['Chờ xác nhận', 'Đang giao hàng', 'Hoàn thành'];
                $normStatus = $order['status'] === 'Đang giao' ? 'Đang giao hàng' : $order['status'];
                $activeIdx = array_search($normStatus, $steps, true);
                $isVietQrUnpaid = $order['payment_method'] === 'VietQR'
                    && $order['payment_status'] === 'unpaid'
                    && $order['status'] !== 'Đã hủy'
                    && (empty($order['payment_expires_at']) || strtotime((string)$order['payment_expires_at']) > time());
                $canViewQrLink = $user !== null || hasRole('admin', 'staff') || in_array($order['order_code'], $_SESSION['last_order_codes'] ?? [], true);
                ?>
                <div class="bg-white rounded-2xl border border-gray-200 p-4 sm:p-5 shadow-sm space-y-3">
                    <div class="flex flex-wrap items-center justify-between gap-2 pb-3 border-b border-gray-100">
                        <div class="flex flex-wrap items-center gap-2 text-sm font-extrabold text-gray-900">
                            <span>Mã đơn: <span class="text-gvn-red"><?= e($order['order_code']) ?></span></span>
                            <span class="text-[11px] font-bold px-2 py-0.5 rounded <?= e($typeBadge['class']) ?>"><?= e($typeBadge['label']) ?></span>
                            <span class="text-xs font-normal text-gray-400"><?= e($order['created_at']) ?></span>
                        </div>
                        <div class="flex flex-wrap items-center gap-1.5">
                            <span class="text-[11px] font-bold px-2.5 py-1 rounded-full <?= e($payBadge['class']) ?>">
                                <?= e($order['payment_method']) ?> · <?= e($payBadge['label']) ?>
                            </span>
                            <span class="text-xs font-bold px-3 py-1 rounded-full <?= e($statusClass) ?>">
                                <?= e($order['status']) ?>
                            </span>
                        </div>
                    </div>

                    <!-- Pipeline Steps -->
                    <?php if ($order['status'] !== 'Đã hủy'): ?>
                        <div class="flex flex-wrap items-center gap-1.5 text-[11px] bg-gray-50 rounded-xl px-3 py-2 border border-gray-100">
                            <?php foreach ($steps as $sIdx => $stepName): ?>
                                <?php $reached = ($activeIdx !== false && $sIdx <= $activeIdx); ?>
                                <span class="px-2 py-0.5 rounded font-semibold <?= $reached ? 'bg-emerald-600 text-white' : 'bg-white text-gray-400 border border-gray-200' ?>">
                                    <?= $sIdx + 1 ?>. <?= e($stepName) ?>
                                </span>
                                <?php if ($sIdx < count($steps) - 1): ?>
                                    <i class="fa-solid fa-chevron-right text-[9px] text-gray-300"></i>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <div class="text-xs text-gray-600 space-y-1">
                        <div><strong>Người nhận:</strong> <?= e($order['customer_name']) ?> — <?= e($order['customer_phone']) ?></div>
                        <div><strong>Địa chỉ:</strong> <?= e($order['customer_address']) ?></div>
                    </div>

                    <?php if (!empty($order['items'])): ?>
                        <div class="space-y-2 border-t border-gray-100 pt-3">
                            <?php foreach ($order['items'] as $it): ?>
                                <div class="flex items-center justify-between gap-2 text-xs">
                                    <span class="text-gray-800">
                                        <?php if (!empty($it['component_slot'])): ?>
                                            <span class="uppercase text-[10px] font-bold bg-gray-100 text-gray-600 px-1.5 py-0.5 rounded mr-1"><?= e($it['component_slot']) ?></span>
                                        <?php endif; ?>
                                        <?= e($it['name']) ?> <strong class="text-gray-500">×<?= (int)$it['qty'] ?></strong>
                                    </span>
                                    <span class="font-bold text-gray-900 shrink-0"><?= formatPrice((int)$it['price'] * (int)$it['qty']) ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <div class="flex flex-wrap items-center justify-between gap-3 border-t border-gray-100 pt-3">
                        <div class="flex flex-wrap items-center gap-2">
                            <?php if (!empty($order['warranties'])): ?>
                                <a href="warranty.php?q=<?= e($order['order_code']) ?>"
                                   class="inline-flex items-center gap-1.5 bg-blue-50 hover:bg-blue-100 text-blue-700 font-bold px-3 py-1.5 rounded-lg text-xs transition">
                                    <i class="fa-solid fa-shield-halved"></i>
                                    Xem bảo hành (<?= count($order['warranties']) ?>)
                                </a>
                            <?php endif; ?>

                            <?php if ($isVietQrUnpaid): ?>
                                <?php if ($canViewQrLink): ?>
                                    <a href="checkout.php?order_code=<?= e($order['order_code']) ?>"
                                       class="inline-flex items-center gap-1 bg-blue-600 hover:bg-blue-700 text-white font-bold px-3 py-1.5 rounded-lg text-xs transition">
                                        <i class="fa-solid fa-qrcode"></i> Quét VietQR
                                    </a>
                                <?php endif; ?>
                                <form method="POST" action="orders.php" class="inline">
                                    <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                                    <input type="hidden" name="action" value="confirm_vietqr">
                                    <input type="hidden" name="order_id" value="<?= (int)$order['id'] ?>">
                                    <input type="hidden" name="lookup_code" value="<?= e($searchCode ?: $order['order_code']) ?>">
                                    <button type="submit" class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold px-3 py-1.5 rounded-lg text-xs transition">
                                        <i class="fa-solid fa-check mr-1"></i> Xác nhận đã CK
                                    </button>
                                </form>
                            <?php endif; ?>

                            <?php if ($order['status'] === 'Chờ xác nhận'): ?>
                                <form method="POST" action="orders.php" class="inline" onsubmit="return confirm('Bạn chắc chắn muốn hủy đơn hàng <?= e($order['order_code']) ?>?')">
                                    <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                                    <input type="hidden" name="action" value="cancel_order">
                                    <input type="hidden" name="order_id" value="<?= (int)$order['id'] ?>">
                                    <input type="hidden" name="lookup_code" value="<?= e($searchCode ?: $order['order_code']) ?>">
                                    <button type="submit" class="bg-red-50 hover:bg-red-100 text-red-700 border border-red-200 font-bold px-3 py-1.5 rounded-lg text-xs transition">
                                        <i class="fa-solid fa-ban mr-1"></i> Hủy đơn hàng
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>

                        <div class="text-sm font-extrabold text-gvn-red ml-auto">
                            Tổng tiền: <?= formatPrice((int)$order['total_amount']) ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
