<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/data/store.php';

if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

$pdo = getPDO();
$user = currentUser();
$error = '';
$successNotice = '';
$placedOrder = null;

function loadAuthorizedOrder(PDO $pdo, string $orderCode, ?array $user): ?array
{
    if ($orderCode === '') {
        return null;
    }

    $stmt = $pdo->prepare('SELECT * FROM orders WHERE order_code = ? LIMIT 1');
    $stmt->execute([$orderCode]);
    $order = $stmt->fetch();
    if (!$order) {
        return null;
    }

    $sessionCodes = $_SESSION['last_order_codes'] ?? [];
    $isOwner = $user !== null && !empty($order['user_id']) && (int)$order['user_id'] === (int)$user['id'];
    $isStaffOrAdmin = hasRole('admin', 'staff');
    $isSessionOrder = is_array($sessionCodes) && in_array($order['order_code'], $sessionCodes, true);

    if (!$isOwner && !$isStaffOrAdmin && !$isSessionOrder) {
        return null;
    }

    if (
        ($order['payment_method'] ?? '') === 'VietQR'
        && ($order['payment_status'] ?? '') === 'unpaid'
        && !empty($order['payment_expires_at'])
        && strtotime((string)$order['payment_expires_at']) <= time()
    ) {
        $pdo->prepare("UPDATE orders SET payment_status = 'expired', updated_at = NOW() WHERE id = ? AND payment_status = 'unpaid'")
            ->execute([(int)$order['id']]);
        $order['payment_status'] = 'expired';
    }

    $itemStmt = $pdo->prepare('
        SELECT oi.*, oi.product_name AS name, oi.product_price AS price, oi.quantity AS qty
        FROM order_items oi
        WHERE oi.order_id = ?
        ORDER BY oi.id ASC
    ');
    $itemStmt->execute([(int)$order['id']]);
    $order['items'] = $itemStmt->fetchAll();

    $wStmt = $pdo->prepare('
        SELECT warranty_code, serial_number, product_name, warranty_months, start_date, end_date, status
        FROM warranties
        WHERE order_id = ?
        ORDER BY id ASC
    ');
    $wStmt->execute([(int)$order['id']]);
    $order['warranties'] = $wStmt->fetchAll();

    $order['id'] = (int)$order['id'];
    $order['code'] = $order['order_code'];
    $order['total_amount'] = (int)round((float)$order['total_amount']);
    return $order;
}

// Hydrate cart items with live stock validation
$cartItems = [];
$total = 0;
foreach ($_SESSION['cart'] as $pid => $qty) {
    $pid = (int)$pid;
    $qty = (int)$qty;
    $p = getProductById($pid);
    $stock = (int)($p['stock'] ?? 0);
    if ($p && $stock > 0 && $qty > 0) {
        $clampedQty = min($qty, $stock);
        $_SESSION['cart'][$pid] = $clampedQty;
        $sub = (int)$p['price'] * $clampedQty;
        $total += $sub;
        $cartItems[] = ['product' => $p, 'qty' => $clampedQty, 'subtotal' => $sub];
    } else {
        unset($_SESSION['cart'][$pid]);
    }
}

$customerName = $user['name'] ?? '';
$phone = $user['phone'] ?? '';
$customerEmail = $user['email'] ?? '';
$address = $user['address'] ?? '';
$paymentMethod = 'COD';
$note = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();
    $action = trim((string)($_POST['action'] ?? 'place_order'));

    if ($action === 'confirm_vietqr') {
        $orderCode = strtoupper(trim((string)($_POST['order_code'] ?? $_GET['order_code'] ?? '')));
        if (!$pdo) {
            $error = 'Không thể kết nối cơ sở dữ liệu để xác nhận thanh toán.';
        } else {
            $authorizedOrder = loadAuthorizedOrder($pdo, $orderCode, $user);
            if (!$authorizedOrder) {
                $error = 'Không tìm thấy đơn hàng hợp lệ hoặc bạn không có quyền thao tác đơn này.';
            } elseif (($authorizedOrder['payment_method'] ?? '') !== 'VietQR') {
                $error = 'Đơn hàng này không sử dụng phương thức VietQR.';
            } elseif (($authorizedOrder['status'] ?? '') === 'Đã hủy') {
                $error = 'Đơn hàng đã bị hủy.';
            } else {
                try {
                    $pdo->beginTransaction();
                    $lock = $pdo->prepare('SELECT id, payment_status, payment_expires_at, status FROM orders WHERE id = ? FOR UPDATE');
                    $lock->execute([(int)$authorizedOrder['id']]);
                    $row = $lock->fetch();
                    if (!$row) {
                        throw new RuntimeException('Không tìm thấy đơn hàng.');
                    }
                    if ($row['status'] === 'Đã hủy') {
                        throw new RuntimeException('Đơn hàng đã bị hủy.');
                    }
                    if (!empty($row['payment_expires_at']) && strtotime((string)$row['payment_expires_at']) <= time()) {
                        if ($row['payment_status'] === 'unpaid') {
                            $pdo->prepare("UPDATE orders SET payment_status = 'expired', updated_at = NOW() WHERE id = ?")->execute([(int)$row['id']]);
                        }
                        $pdo->commit();
                        $error = 'Mã VietQR đã hết hạn thanh toán (15 phút). Vui lòng đặt lại đơn hàng hoặc liên hệ hỗ trợ.';
                    } elseif ($row['payment_status'] === 'unpaid') {
                        $pdo->prepare("UPDATE orders SET payment_status = 'pending_confirm', updated_at = NOW() WHERE id = ?")->execute([(int)$row['id']]);
                        $pdo->commit();
                        $_SESSION['checkout_flash'] = 'Đã ghi nhận xác nhận chuyển khoản VietQR! Nhân viên sẽ đối soát và duyệt đơn trong ít phút.';
                        header('Location: checkout.php?order_code=' . urlencode($orderCode));
                        exit;
                    } else {
                        $pdo->commit();
                        $_SESSION['checkout_flash'] = 'Trạng thái thanh toán hiện tại đã được ghi nhận.';
                        header('Location: checkout.php?order_code=' . urlencode($orderCode));
                        exit;
                    }
                } catch (Throwable $e) {
                    if ($pdo->inTransaction()) {
                        $pdo->rollBack();
                    }
                    $error = $e->getMessage();
                }
            }
            $placedOrder = loadAuthorizedOrder($pdo, $orderCode, $user);
        }
    } elseif (!empty($cartItems)) {
        $customerName = trim((string)($_POST['customer_name'] ?? ''));
        $phone = trim((string)($_POST['phone'] ?? $_POST['customer_phone'] ?? ''));
        $customerEmail = trim((string)($_POST['customer_email'] ?? ''));
        $address = trim((string)($_POST['address'] ?? $_POST['customer_address'] ?? ''));
        $paymentMethod = trim((string)($_POST['payment_method'] ?? 'COD'));
        $note = trim((string)($_POST['note'] ?? ''));

        if (!in_array($paymentMethod, ['COD', 'VietQR'], true)) {
            $error = 'Phương thức thanh toán không hợp lệ. Chỉ hỗ trợ COD hoặc VietQR.';
        } elseif (!$pdo) {
            $error = 'Hệ thống cơ sở dữ liệu tạm thời gián đoạn, vui lòng thử lại.';
        } else {
            $pcBuildMeta = $_SESSION['pc_build_meta'] ?? null;
            $orderType = !empty($pcBuildMeta) ? 'pc_build' : 'online';
            $slotMap = is_array($pcBuildMeta) ? ($pcBuildMeta['slots'] ?? []) : [];

            $itemsPayload = [];
            foreach ($cartItems as $item) {
                $pid = (int)$item['product']['id'];
                $itemsPayload[] = [
                    'product_id' => $pid,
                    'quantity' => (int)$item['qty'],
                    'component_slot' => $slotMap[$pid] ?? null,
                ];
            }

            try {
                $created = createOrderWithItems($pdo, [
                    'user_id' => $user['id'] ?? null,
                    'order_type' => $orderType,
                    'customer_name' => $customerName,
                    'customer_phone' => $phone,
                    'customer_email' => $customerEmail,
                    'customer_address' => $address,
                    'note' => $note,
                    'payment_method' => $paymentMethod,
                    'pc_build_specs' => is_array($pcBuildMeta) ? ($pcBuildMeta['specs'] ?? null) : null,
                ], $itemsPayload);

                $_SESSION['cart'] = [];
                unset($_SESSION['pc_build_meta']);
                if (!isset($_SESSION['last_order_codes']) || !is_array($_SESSION['last_order_codes'])) {
                    $_SESSION['last_order_codes'] = [];
                }
                if (!in_array($created['order_code'], $_SESSION['last_order_codes'], true)) {
                    $_SESSION['last_order_codes'][] = $created['order_code'];
                }
                $_SESSION['checkout_flash'] = 'Đặt hàng thành công! Bảo hành điện tử đã được kích hoạt tự động.';

                header('Location: checkout.php?order_code=' . urlencode($created['order_code']));
                exit;
            } catch (Throwable $e) {
                $error = $e->getMessage();
            }
        }
    }
}

if ($placedOrder === null && !empty($_GET['order_code']) && $pdo) {
    $requestedCode = strtoupper(trim((string)$_GET['order_code']));
    $placedOrder = loadAuthorizedOrder($pdo, $requestedCode, $user);
    if ($placedOrder === null && $error === '') {
        $error = 'Không tìm thấy đơn hàng hoặc bạn không có quyền xem đơn hàng này.';
    }
}

if (!empty($_SESSION['checkout_flash'])) {
    $successNotice = (string)$_SESSION['checkout_flash'];
    unset($_SESSION['checkout_flash']);
}

$paymentStatusMap = [
    'unpaid' => ['label' => 'Chưa thanh toán', 'class' => 'bg-amber-100 text-amber-800'],
    'pending_confirm' => ['label' => 'Chờ nhân viên xác nhận', 'class' => 'bg-blue-100 text-blue-800'],
    'paid' => ['label' => 'Đã thanh toán', 'class' => 'bg-emerald-100 text-emerald-800'],
    'expired' => ['label' => 'Hết hạn thanh toán', 'class' => 'bg-red-100 text-red-700'],
];

$pageTitle = 'Thanh toán đơn hàng - ĐOÀN 6';
require_once __DIR__ . '/includes/header.php';
?>

<div class="max-w-[1040px] mx-auto px-3 py-6">
    <nav class="text-xs text-gray-500 mb-4 flex items-center gap-2">
        <a href="index.php" class="hover:text-gvn-red"><i class="fa-solid fa-house"></i> Trang chủ</a>
        <span>/</span>
        <a href="cart.php" class="hover:text-gvn-red">Giỏ hàng</a>
        <span>/</span>
        <span class="text-gray-800 font-semibold">Thanh toán</span>
    </nav>

    <?php if ($placedOrder): ?>
        <?php
        $ps = (string)($placedOrder['payment_status'] ?? 'unpaid');
        $psBadge = $paymentStatusMap[$ps] ?? $paymentStatusMap['unpaid'];
        $remainSeconds = 0;
        if (($placedOrder['payment_method'] ?? '') === 'VietQR' && !empty($placedOrder['payment_expires_at'])) {
            $remainSeconds = max(0, strtotime((string)$placedOrder['payment_expires_at']) - time());
        }
        $isExpired = ($ps === 'expired') || ($remainSeconds <= 0 && $ps === 'unpaid');
        ?>
        <div class="bg-white rounded-2xl border border-emerald-200 p-6 sm:p-8 shadow-sm max-w-2xl mx-auto space-y-5">
            <div class="text-center">
                <i class="fa-solid fa-circle-check text-5xl text-emerald-500 mb-3"></i>
                <h1 class="text-xl font-extrabold text-gray-900 mb-1">Thông tin đơn hàng <?= e($placedOrder['order_code']) ?></h1>
                <p class="text-xs text-gray-500">
                    Trạng thái đơn: <strong class="text-gray-900"><?= e($placedOrder['status']) ?></strong>
                    · Thanh toán:
                    <span class="inline-block px-2 py-0.5 rounded-full font-bold <?= e($psBadge['class']) ?>">
                        <?= e($psBadge['label']) ?>
                    </span>
                </p>
            </div>

            <?php if ($successNotice !== ''): ?>
                <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-semibold rounded-xl p-3 text-center">
                    <i class="fa-solid fa-check mr-1"></i> <?= e($successNotice) ?>
                </div>
            <?php endif; ?>

            <?php if ($error !== ''): ?>
                <div class="bg-red-50 border border-red-200 text-red-700 text-xs font-semibold rounded-xl p-3 text-center">
                    <i class="fa-solid fa-triangle-exclamation mr-1"></i> <?= e($error) ?>
                </div>
            <?php endif; ?>

            <div class="bg-gray-50 rounded-xl p-4 text-xs space-y-1.5 border border-gray-100">
                <div><span class="text-gray-500">Người nhận:</span> <strong><?= e($placedOrder['customer_name']) ?></strong></div>
                <div><span class="text-gray-500">Số điện thoại:</span> <strong><?= e($placedOrder['customer_phone']) ?></strong></div>
                <div><span class="text-gray-500">Địa chỉ:</span> <?= e($placedOrder['customer_address']) ?></div>
                <div><span class="text-gray-500">Phương thức thanh toán:</span> <strong><?= e($placedOrder['payment_method']) ?></strong></div>
                <div><span class="text-gray-500">Tổng thanh toán:</span> <strong class="text-gvn-red text-sm"><?= formatPrice((int)$placedOrder['total_amount']) ?></strong></div>
            </div>

            <?php if (($placedOrder['payment_method'] ?? '') === 'VietQR' && ($placedOrder['status'] ?? '') !== 'Đã hủy'): ?>
                <?php
                $qrAmount = (int)$placedOrder['total_amount'];
                $qrInfo = urlencode($placedOrder['order_code']);
                $qrUrl = "https://img.vietqr.io/image/MB-0349182931-compact2.png?amount={$qrAmount}&addInfo={$qrInfo}&accountName=DOAN%206%20TECH";
                ?>
                <div class="bg-blue-50 border border-blue-200 rounded-xl p-5 text-center space-y-3">
                    <div class="text-xs font-extrabold text-blue-900 uppercase">
                        <i class="fa-solid fa-qrcode mr-1"></i> Quét mã VietQR Napas 247 để thanh toán
                    </div>

                    <?php if ($ps === 'unpaid'): ?>
                        <div class="text-xs text-blue-800">
                            Thời gian giữ mã QR còn lại:
                            <span id="vietqr-timer" class="font-extrabold text-gvn-red text-sm">
                                <?= sprintf('%02d:%02d', intdiv($remainSeconds, 60), $remainSeconds % 60) ?>
                            </span>
                        </div>
                    <?php endif; ?>

                    <img src="<?= e($qrUrl) ?>"
                         alt="VietQR Thanh toán <?= e($placedOrder['order_code']) ?>"
                         onerror="this.classList.add('hidden'); document.getElementById('vietqr-svg-fallback').classList.remove('hidden');"
                         class="w-56 h-auto mx-auto rounded-lg border border-blue-200 bg-white p-2 shadow-sm">

                    <!-- Offline SVG Fallback -->
                    <div id="vietqr-svg-fallback" class="hidden max-w-xs mx-auto bg-white border border-blue-200 rounded-xl p-4 space-y-2">
                        <svg viewBox="0 0 120 120" class="w-32 h-32 mx-auto text-gray-900" fill="currentColor" aria-label="Mã QR dự phòng">
                            <rect x="8" y="8" width="32" height="32" fill="none" stroke="currentColor" stroke-width="6"/>
                            <rect x="17" y="17" width="14" height="14"/>
                            <rect x="80" y="8" width="32" height="32" fill="none" stroke="currentColor" stroke-width="6"/>
                            <rect x="89" y="17" width="14" height="14"/>
                            <rect x="8" y="80" width="32" height="32" fill="none" stroke="currentColor" stroke-width="6"/>
                            <rect x="17" y="89" width="14" height="14"/>
                            <rect x="50" y="14" width="8" height="8"/><rect x="62" y="24" width="8" height="8"/>
                            <rect x="48" y="48" width="12" height="12"/><rect x="68" y="52" width="10" height="10"/>
                            <rect x="50" y="72" width="8" height="18"/><rect x="66" y="78" width="16" height="8"/>
                            <rect x="88" y="64" width="18" height="10"/><rect x="88" y="86" width="22" height="22"/>
                        </svg>
                        <div class="text-[11px] text-gray-600 font-semibold">VietQR Offline Payload: MB-0349182931</div>
                    </div>

                    <div class="bg-white/80 rounded-lg p-3 text-xs text-gray-700 space-y-1 border border-blue-100 text-left max-w-sm mx-auto">
                        <div>Ngân hàng: <strong>MB Bank (Quân Đội)</strong></div>
                        <div>Số tài khoản: <strong>0349182931</strong> — Chủ TK: <strong>DOAN 6 TECH</strong></div>
                        <div>Số tiền chính xác: <strong class="text-gvn-red"><?= formatPrice($qrAmount) ?></strong></div>
                        <div>Nội dung chuyển khoản: <strong class="text-blue-700"><?= e($placedOrder['order_code']) ?></strong></div>
                    </div>

                    <?php if ($ps === 'unpaid' && !$isExpired): ?>
                        <form method="POST" action="checkout.php?order_code=<?= e($placedOrder['order_code']) ?>" class="pt-1">
                            <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                            <input type="hidden" name="action" value="confirm_vietqr">
                            <input type="hidden" name="order_code" value="<?= e($placedOrder['order_code']) ?>">
                            <button type="submit" id="btn-confirm-vietqr"
                                    class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold px-5 py-2.5 rounded-xl text-xs transition shadow-sm">
                                <i class="fa-solid fa-check-double mr-1"></i> Tôi đã chuyển khoản VietQR
                            </button>
                        </form>
                    <?php elseif ($ps === 'pending_confirm'): ?>
                        <div class="text-xs font-bold text-blue-700">
                            <i class="fa-solid fa-hourglass-half mr-1"></i> Đã gửi xác nhận chuyển khoản — đang chờ nhân viên duyệt đơn.
                        </div>
                    <?php elseif ($ps === 'paid'): ?>
                        <div class="text-xs font-bold text-emerald-700">
                            <i class="fa-solid fa-circle-check mr-1"></i> Thanh toán VietQR đã được xác nhận thành công.
                        </div>
                    <?php else: ?>
                        <div class="text-xs font-bold text-red-600">
                            <i class="fa-solid fa-clock mr-1"></i> Mã VietQR đã hết hạn thanh toán (15 phút).
                        </div>
                    <?php endif; ?>
                </div>

                <?php if ($ps === 'unpaid' && $remainSeconds > 0): ?>
                    <script>
                        (function () {
                            let remain = <?= (int)$remainSeconds ?>;
                            const el = document.getElementById('vietqr-timer');
                            const btn = document.getElementById('btn-confirm-vietqr');
                            if (!el) return;
                            const iv = setInterval(function () {
                                remain--;
                                if (remain <= 0) {
                                    clearInterval(iv);
                                    el.textContent = '00:00 (Đã hết hạn)';
                                    if (btn) {
                                        btn.disabled = true;
                                        btn.classList.add('opacity-50', 'cursor-not-allowed');
                                    }
                                    return;
                                }
                                const m = String(Math.floor(remain / 60)).padStart(2, '0');
                                const s = String(remain % 60).padStart(2, '0');
                                el.textContent = m + ':' + s;
                            }, 1000);
                        })();
                    </script>
                <?php endif; ?>
            <?php endif; ?>

            <?php if (!empty($placedOrder['warranties'])): ?>
                <div class="bg-emerald-50/70 border border-emerald-200 rounded-xl p-4 text-xs space-y-2">
                    <div class="flex items-center justify-between">
                        <span class="font-extrabold text-emerald-900 uppercase">
                            <i class="fa-solid fa-shield-halved mr-1"></i> Bảo hành điện tử đã kích hoạt (<?= count($placedOrder['warranties']) ?>)
                        </span>
                        <a href="warranty.php?q=<?= e($placedOrder['order_code']) ?>" class="font-bold text-gvn-red hover:underline">
                            Tra cứu bảo hành →
                        </a>
                    </div>
                    <div class="space-y-1">
                        <?php foreach ($placedOrder['warranties'] as $w): ?>
                            <div class="flex flex-wrap items-center justify-between gap-2 bg-white rounded-lg px-3 py-1.5 border border-emerald-100">
                                <span class="font-semibold text-gray-800 truncate max-w-[280px]"><?= e($w['product_name']) ?></span>
                                <span class="font-mono text-[11px] text-emerald-700 font-bold">
                                    <?= e($w['warranty_code']) ?> · <?= e($w['serial_number']) ?> (<?= (int)$w['warranty_months'] ?>T)
                                </span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <div class="flex flex-wrap justify-center gap-3 pt-2">
                <a href="orders.php?code=<?= e($placedOrder['order_code']) ?>" class="bg-black hover:bg-neutral-800 text-white font-bold px-5 py-2.5 rounded-xl text-xs transition">
                    Xem chi tiết đơn hàng
                </a>
                <a href="warranty.php?q=<?= e($placedOrder['order_code']) ?>" class="bg-blue-600 hover:bg-blue-700 text-white font-bold px-5 py-2.5 rounded-xl text-xs transition">
                    Xem phiếu bảo hành
                </a>
                <a href="index.php" class="bg-gvn-red hover:bg-gvn-redHover text-white font-bold px-5 py-2.5 rounded-xl text-xs transition">
                    Tiếp tục mua sắm
                </a>
            </div>
        </div>
    <?php elseif (empty($cartItems)): ?>
        <div class="bg-white rounded-2xl border border-gray-200 p-8 text-center shadow-sm space-y-3">
            <?php if ($error !== ''): ?>
                <div class="bg-red-50 border border-red-200 text-red-700 text-xs rounded-xl p-3 max-w-md mx-auto">
                    <?= e($error) ?>
                </div>
            <?php endif; ?>
            <p class="text-sm text-gray-500">Giỏ hàng đang trống, không thể thanh toán.</p>
            <a href="index.php" class="inline-block bg-gvn-red text-white font-bold px-6 py-2.5 rounded-xl text-sm">Quay về trang chủ</a>
        </div>
    <?php else: ?>
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-5">
            <!-- Form thông tin giao hàng -->
            <div class="lg:col-span-7 bg-white rounded-2xl border border-gray-200 p-5 shadow-sm">
                <h1 class="text-lg font-extrabold text-gray-900 uppercase mb-4 pb-2 border-b border-gray-100">Thông tin giao hàng</h1>

                <?php if ($error !== ''): ?>
                    <div class="bg-red-50 border border-red-200 text-red-700 text-xs rounded-xl p-3 mb-4">
                        <?= e($error) ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="checkout.php" class="space-y-4">
                    <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                    <input type="hidden" name="action" value="place_order">

                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">Họ và tên người nhận *</label>
                        <input type="text" name="customer_name" value="<?= e($customerName) ?>" required placeholder="Nguyễn Văn A"
                            class="w-full h-10 px-3.5 rounded-lg border border-gray-300 text-sm focus:outline-none focus:border-gvn-red">
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">Số điện thoại *</label>
                            <input type="text" name="phone" value="<?= e($phone) ?>" required placeholder="0909123456"
                                class="w-full h-10 px-3.5 rounded-lg border border-gray-300 text-sm focus:outline-none focus:border-gvn-red">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">Email (nhận thông báo bảo hành)</label>
                            <input type="email" name="customer_email" value="<?= e($customerEmail) ?>" placeholder="email@example.com"
                                class="w-full h-10 px-3.5 rounded-lg border border-gray-300 text-sm focus:outline-none focus:border-gvn-red">
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">Địa chỉ nhận hàng *</label>
                        <input type="text" name="address" value="<?= e($address) ?>" required placeholder="Số nhà, đường, phường/xã, quận/huyện, tỉnh/thành"
                            class="w-full h-10 px-3.5 rounded-lg border border-gray-300 text-sm focus:outline-none focus:border-gvn-red">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1.5">Phương thức thanh toán (COD / VietQR)</label>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5 text-xs">
                            <label class="flex items-center gap-2.5 border border-gray-200 rounded-xl p-3 cursor-pointer hover:border-gvn-red bg-gray-50/50">
                                <input type="radio" name="payment_method" value="COD" <?= $paymentMethod !== 'VietQR' ? 'checked' : '' ?>>
                                <div>
                                    <div class="font-bold text-gray-900">Thanh toán khi nhận hàng (COD)</div>
                                    <div class="text-[11px] text-gray-500">Kiểm tra hàng trước khi thanh toán</div>
                                </div>
                            </label>
                            <label class="flex items-center gap-2.5 border border-gray-200 rounded-xl p-3 cursor-pointer hover:border-gvn-red bg-gray-50/50">
                                <input type="radio" name="payment_method" value="VietQR" <?= $paymentMethod === 'VietQR' ? 'checked' : '' ?>>
                                <div>
                                    <div class="font-bold text-gray-900">Chuyển khoản VietQR</div>
                                    <div class="text-[11px] text-gray-500">Quét mã QR chuẩn Napas 247 (hạn 15 phút)</div>
                                </div>
                            </label>
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">Ghi chú đơn hàng</label>
                        <textarea name="note" rows="2" placeholder="Ví dụ: Giao giờ hành chính..."
                            class="w-full p-3 rounded-lg border border-gray-300 text-sm focus:outline-none focus:border-gvn-red"><?= e($note) ?></textarea>
                    </div>
                    <button type="submit" class="w-full bg-gvn-red hover:bg-gvn-redHover text-white font-extrabold py-3.5 rounded-xl uppercase text-sm transition shadow-md">
                        Xác nhận đặt hàng (<?= formatPrice($total) ?>)
                    </button>
                </form>
            </div>

            <!-- Tóm tắt đơn hàng -->
            <div class="lg:col-span-5 bg-white rounded-2xl border border-gray-200 p-5 shadow-sm h-fit">
                <h2 class="text-base font-extrabold text-gray-900 uppercase mb-4 pb-2 border-b border-gray-100">Đơn hàng của bạn (<?= count($cartItems) ?> sản phẩm)</h2>
                <div class="space-y-3 mb-4 max-h-80 overflow-y-auto pr-1">
                    <?php foreach ($cartItems as $item): ?>
                        <div class="flex items-center gap-3 pb-3 border-b border-gray-100">
                            <img src="<?= e($item['product']['image']) ?>" alt="" class="w-14 h-14 object-cover rounded-lg border border-gray-200 shrink-0">
                            <div class="flex-1 min-w-0">
                                <div class="text-xs font-bold text-gray-900 line-clamp-2"><?= e($item['product']['name']) ?></div>
                                <div class="text-[11px] text-gray-500 mt-0.5">SL: <?= (int)$item['qty'] ?> × <?= formatPrice((int)$item['product']['price']) ?></div>
                            </div>
                            <div class="text-xs font-extrabold text-gvn-red shrink-0"><?= formatPrice((int)$item['subtotal']) ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="space-y-2 text-xs pt-2">
                    <div class="flex justify-between text-gray-600">
                        <span>Tạm tính:</span>
                        <span><?= formatPrice($total) ?></span>
                    </div>
                    <div class="flex justify-between text-gray-600">
                        <span>Phí vận chuyển:</span>
                        <span class="text-emerald-600 font-semibold">Miễn phí</span>
                    </div>
                    <div class="flex justify-between text-base font-extrabold text-gray-900 pt-2 border-t border-gray-100">
                        <span>Tổng cộng:</span>
                        <span class="text-gvn-red"><?= formatPrice($total) ?></span>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
