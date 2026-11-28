<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/data/store.php';

$slotMeta = [
    'cpu'       => ['label' => '1. Bộ vi xử lý (CPU)',        'icon' => 'fa-microchip'],
    'mainboard' => ['label' => '2. Bo mạch chủ (Mainboard)',   'icon' => 'fa-server'],
    'ram'       => ['label' => '3. Bộ nhớ trong (RAM)',        'icon' => 'fa-memory'],
    'vga'       => ['label' => '4. Card đồ họa (VGA)',         'icon' => 'fa-tv'],
    'ssd'       => ['label' => '5. Ổ cứng lưu trữ (SSD)',      'icon' => 'fa-hard-drive'],
    'psu'       => ['label' => '6. Nguồn máy tính (PSU)',      'icon' => 'fa-bolt'],
    'case'      => ['label' => '7. Vỏ máy tính (Case)',        'icon' => 'fa-box'],
    'cooling'   => ['label' => '8. Tản nhiệt (Cooling)',       'icon' => 'fa-fan'],
];

$allParts = getPcBuilderParts();
$partsBySlot = array_fill_keys(array_keys($slotMeta), []);
$partsById = [];
foreach ($allParts as $p) {
    $ct = (string)($p['component_type'] ?? '');
    if (isset($partsBySlot[$ct])) {
        $partsBySlot[$ct][] = $p;
        $partsById[(int)$p['id']] = $p;
    }
}

$loggedUser = currentUser();
$pdo = getPDO();

$buildErrors = [];
$buildMessage = '';
$createdOrder = null;

$selectedPartIds = [];
$formCustomerName = (string)($loggedUser['name'] ?? '');
$formCustomerPhone = (string)($loggedUser['phone'] ?? '');
$formCustomerEmail = (string)($loggedUser['email'] ?? '');
$formCustomerAddress = (string)($loggedUser['address'] ?? '');
$formNote = '';
$formPaymentMethod = 'COD';

function splitCompatTokens(?string $raw): array
{
    if ($raw === null || trim($raw) === '') {
        return [];
    }
    $tokens = array_map(
        static fn(string $t): string => strtoupper(trim($t)),
        explode(',', $raw)
    );
    return array_values(array_filter($tokens, static fn(string $t): bool => $t !== ''));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();
    $action = (string)($_POST['action'] ?? '');

    if ($action === 'confirm_vietqr') {
        $orderCode = strtoupper(trim((string)($_POST['order_code'] ?? '')));
        if (!$pdo || $orderCode === '') {
            $buildErrors[] = 'Không tìm thấy đơn hàng VietQR cần xác nhận.';
        } else {
            try {
                $stmt = $pdo->prepare("SELECT * FROM orders WHERE order_code = ? AND order_type = 'pc_build' LIMIT 1");
                $stmt->execute([$orderCode]);
                $orderRow = $stmt->fetch();
                if (!$orderRow) {
                    $buildErrors[] = 'Không tìm thấy đơn hàng Build PC với mã ' . $orderCode . '.';
                } elseif (($orderRow['payment_method'] ?? '') !== 'VietQR') {
                    $buildErrors[] = 'Đơn hàng này không sử dụng phương thức VietQR.';
                } elseif (in_array((string)$orderRow['status'], ['Hoàn thành', 'Đã hủy'], true)) {
                    $buildErrors[] = 'Đơn hàng đã ở trạng thái kết thúc, không thể xác nhận thanh toán.';
                } else {
                    $expiresAt = !empty($orderRow['payment_expires_at']) ? strtotime((string)$orderRow['payment_expires_at']) : null;
                    if ($expiresAt !== null && $expiresAt < time() && ($orderRow['payment_status'] ?? '') !== 'paid') {
                        $pdo->prepare("UPDATE orders SET payment_status = 'expired', updated_at = NOW() WHERE id = ?")->execute([(int)$orderRow['id']]);
                        $orderRow['payment_status'] = 'expired';
                        $buildErrors[] = 'Mã VietQR đã hết hạn 15 phút. Vui lòng liên hệ cửa hàng hoặc đặt lại đơn mới.';
                    } else {
                        if (($orderRow['payment_status'] ?? '') !== 'paid') {
                            $pdo->prepare("UPDATE orders SET payment_status = 'pending_confirm', updated_at = NOW() WHERE id = ?")->execute([(int)$orderRow['id']]);
                            $orderRow['payment_status'] = 'pending_confirm';
                        }
                        $buildMessage = 'Đã ghi nhận thông báo chuyển khoản VietQR cho đơn ' . $orderCode . '. Nhân viên/Admin sẽ kiểm tra và chuyển sang Đang lắp ráp!';
                    }

                    $wStmt = $pdo->prepare('SELECT warranty_code, serial_number, product_name, warranty_months, status FROM warranties WHERE order_id = ? ORDER BY id ASC');
                    $wStmt->execute([(int)$orderRow['id']]);
                    $orderRow['warranties'] = $wStmt->fetchAll() ?: [];
                    $createdOrder = $orderRow;
                }
            } catch (PDOException $e) {
                $buildErrors[] = 'Không thể cập nhật trạng thái xác nhận VietQR.';
            }
        }
    } elseif ($action === 'place_build_order' || $action === '') {
        $rawParts = $_POST['parts'] ?? [];
        foreach ($slotMeta as $slotKey => $meta) {
            $pid = 0;
            if (is_array($rawParts) && isset($rawParts[$slotKey])) {
                $pid = (int)$rawParts[$slotKey];
            }
            if ($pid <= 0 && isset($_POST[$slotKey . '_id'])) {
                $pid = (int)$_POST[$slotKey . '_id'];
            }
            if ($pid <= 0 && isset($_POST[$slotKey])) {
                $pid = (int)$_POST[$slotKey];
            }
            if ($pid > 0) {
                $selectedPartIds[$slotKey] = $pid;
            }
        }

        $formCustomerName = trim((string)($_POST['customer_name'] ?? ''));
        $formCustomerPhone = trim((string)($_POST['phone'] ?? '')) ?: trim((string)($_POST['customer_phone'] ?? ''));
        $formCustomerEmail = trim((string)($_POST['email'] ?? '')) ?: trim((string)($_POST['customer_email'] ?? ''));
        $formCustomerAddress = trim((string)($_POST['address'] ?? '')) ?: trim((string)($_POST['customer_address'] ?? ''));
        $formNote = trim((string)($_POST['note'] ?? ''));
        $formPaymentMethod = (string)($_POST['payment_method'] ?? 'COD');

        if (empty($selectedPartIds)) {
            $buildErrors[] = 'Vui lòng chọn ít nhất 1 linh kiện để đặt hàng cấu hình PC.';
        }

        $selectedParts = [];
        $items = [];
        $totalTdp = 0;

        foreach ($selectedPartIds as $slotKey => $pid) {
            $part = getProductById($pid);
            if (!$part || ($part['component_type'] ?? 'none') !== $slotKey) {
                $buildErrors[] = 'Linh kiện ở mục ' . $slotMeta[$slotKey]['label'] . ' không hợp lệ.';
                continue;
            }
            if ((int)($part['stock'] ?? 0) <= 0) {
                $buildErrors[] = 'Linh kiện "' . $part['name'] . '" hiện đã hết hàng trong kho.';
                continue;
            }
            $selectedParts[$slotKey] = $part;
            $totalTdp += max(0, (int)($part['tdp_watt'] ?? 0));
            $items[] = [
                'product_id' => $pid,
                'quantity' => 1,
                'component_slot' => $slotKey,
            ];
        }

        if (isset($selectedParts['cpu'], $selectedParts['mainboard'])) {
            $cpuSock = strtoupper(trim((string)($selectedParts['cpu']['socket'] ?? '')));
            $mbSock = strtoupper(trim((string)($selectedParts['mainboard']['socket'] ?? '')));
            if ($cpuSock !== '' && $mbSock !== '' && $cpuSock !== $mbSock) {
                $buildErrors[] = "Xung đột Socket: CPU ({$selectedParts['cpu']['socket']}) không tương thích Mainboard ({$selectedParts['mainboard']['socket']}).";
            }
        }

        if (isset($selectedParts['mainboard'], $selectedParts['ram'])) {
            $mbRam = strtoupper(trim((string)($selectedParts['mainboard']['ram_type'] ?? '')));
            $ramType = strtoupper(trim((string)($selectedParts['ram']['ram_type'] ?? '')));
            if ($mbRam !== '' && $ramType !== '' && $mbRam !== $ramType) {
                $buildErrors[] = "Xung đột chuẩn RAM: Mainboard hỗ trợ {$selectedParts['mainboard']['ram_type']} nhưng RAM đang chọn là {$selectedParts['ram']['ram_type']}.";
            }
        }

        if (isset($selectedParts['cooling'])) {
            $coolingSockets = splitCompatTokens($selectedParts['cooling']['socket'] ?? null);
            if (!empty($coolingSockets)) {
                $targetSock = '';
                if (isset($selectedParts['cpu']) && trim((string)($selectedParts['cpu']['socket'] ?? '')) !== '') {
                    $targetSock = strtoupper(trim((string)$selectedParts['cpu']['socket']));
                } elseif (isset($selectedParts['mainboard']) && trim((string)($selectedParts['mainboard']['socket'] ?? '')) !== '') {
                    $targetSock = strtoupper(trim((string)$selectedParts['mainboard']['socket']));
                }
                if ($targetSock !== '' && !in_array($targetSock, $coolingSockets, true)) {
                    $buildErrors[] = "Xung đột tản nhiệt: Tản nhiệt ({$selectedParts['cooling']['socket']}) không hỗ trợ socket {$targetSock}.";
                }
            }
        }

        if (isset($selectedParts['psu'])) {
            $psuWatt = (int)($selectedParts['psu']['psu_watt'] ?? 0);
            if ($psuWatt > 0 && $totalTdp > $psuWatt) {
                $buildErrors[] = "Thiếu công suất nguồn: Tổng TDP ước tính ({$totalTdp}W) vượt quá công suất nguồn PSU ({$psuWatt}W).";
            }
        }

        if (empty($buildErrors)) {
            if (!$pdo) {
                $buildErrors[] = 'Không thể kết nối cơ sở dữ liệu để tạo đơn hàng.';
            } else {
                $specsSummary = [];
                foreach ($selectedParts as $slotKey => $part) {
                    $specsSummary[$slotKey] = $part['name'];
                }
                if (isset($selectedParts['cpu']['socket'])) {
                    $specsSummary['socket'] = $selectedParts['cpu']['socket'];
                } elseif (isset($selectedParts['mainboard']['socket'])) {
                    $specsSummary['socket'] = $selectedParts['mainboard']['socket'];
                }
                if (isset($selectedParts['ram']['ram_type'])) {
                    $specsSummary['ram_type'] = $selectedParts['ram']['ram_type'];
                } elseif (isset($selectedParts['mainboard']['ram_type'])) {
                    $specsSummary['ram_type'] = $selectedParts['mainboard']['ram_type'];
                }
                $specsSummary['estimated_watt'] = $totalTdp;
                if (isset($selectedParts['psu']['psu_watt'])) {
                    $specsSummary['psu_watt'] = (int)$selectedParts['psu']['psu_watt'];
                }

                try {
                    $createdOrder = createOrderWithItems($pdo, [
                        'order_type' => 'pc_build',
                        'user_id' => $loggedUser['id'] ?? null,
                        'customer_name' => $formCustomerName,
                        'customer_phone' => $formCustomerPhone,
                        'customer_email' => $formCustomerEmail,
                        'customer_address' => $formCustomerAddress,
                        'note' => $formNote,
                        'payment_method' => $formPaymentMethod,
                        'pc_build_specs' => $specsSummary,
                    ], $items);

                    $buildMessage = 'Đặt cấu hình Build PC thành công! Mã đơn hàng: ' . $createdOrder['order_code']
                        . '. Đơn hàng đang ở trạng thái "Chờ xác nhận" để kỹ thuật viên tiến hành lắp ráp.';
                    $allParts = getPcBuilderParts();
                    $partsBySlot = array_fill_keys(array_keys($slotMeta), []);
                    foreach ($allParts as $p) {
                        $ct = (string)($p['component_type'] ?? '');
                        if (isset($partsBySlot[$ct])) {
                            $partsBySlot[$ct][] = $p;
                        }
                    }
                } catch (Throwable $e) {
                    $buildErrors[] = $e->getMessage();
                }
            }
        }
    }
}

$pageTitle = 'Tự Build PC Thông Minh - Kiểm tra tương thích & Đặt lắp ráp | ĐOÀN 6';
require_once __DIR__ . '/includes/header.php';
?>

<div class="max-w-[1280px] mx-auto px-3 py-6 space-y-5">
    <div class="bg-white rounded-2xl border border-gray-200 p-5 shadow-sm flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-xl sm:text-2xl font-extrabold text-gray-900 uppercase flex items-center gap-2">
                <i class="fa-solid fa-sliders text-gvn-red"></i>
                Trình Cấu Hình PC Builder Thông Minh
            </h1>
            <p class="text-xs text-gray-500 mt-1">
                Kiểm tra tương thích trực tiếp theo dữ liệu kho: Socket CPU – Mainboard – Tản nhiệt, chuẩn RAM DDR4/DDR5, tồn kho và công suất nguồn PSU (+25% headroom). Quy trình xử lý: Chờ xác nhận → Đang lắp ráp → Đang giao hàng → Hoàn thành.
            </p>
        </div>
        <div class="flex items-center gap-2">
            <button type="button" onclick="window.print()" class="bg-gray-100 hover:bg-gray-200 text-gray-800 font-bold px-4 py-2.5 rounded-xl text-xs transition">
                <i class="fa-solid fa-print mr-1"></i> In báo giá
            </button>
            <button type="button" onclick="resetBuild()" class="bg-black hover:bg-neutral-800 text-white font-bold px-4 py-2.5 rounded-xl text-xs transition">
                <i class="fa-solid fa-rotate-left mr-1"></i> Làm mới
            </button>
        </div>
    </div>

    <?php if ($buildMessage !== ''): ?>
        <div class="bg-emerald-50 border border-emerald-200 text-emerald-900 text-xs font-semibold rounded-2xl p-4">
            <i class="fa-solid fa-circle-check mr-1 text-emerald-600"></i> <?= e($buildMessage) ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($buildErrors)): ?>
        <div class="bg-red-50 border border-red-200 text-red-800 text-xs rounded-2xl p-4 space-y-1">
            <div class="font-extrabold uppercase"><i class="fa-solid fa-triangle-exclamation mr-1"></i> Không thể đặt cấu hình PC:</div>
            <?php foreach ($buildErrors as $err): ?>
                <div>• <?= e($err) ?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if ($createdOrder): ?>
        <div class="bg-white rounded-2xl border-2 border-emerald-300 p-5 shadow-sm space-y-4">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-100 pb-3">
                <div>
                    <div class="text-xs text-gray-500">Đơn hàng Build PC đã được tạo</div>
                    <div class="text-xl font-extrabold text-gvn-red"><?= e($createdOrder['order_code']) ?></div>
                </div>
                <div class="text-right">
                    <div class="text-xs text-gray-500">Tổng thanh toán</div>
                    <div class="text-xl font-extrabold text-gray-900"><?= formatPrice((int)$createdOrder['total_amount']) ?></div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs">
                <div class="bg-gray-50 rounded-xl p-4 space-y-1.5">
                    <div><strong>Khách hàng:</strong> <?= e($createdOrder['customer_name']) ?> — <?= e($createdOrder['customer_phone']) ?></div>
                    <div><strong>Địa chỉ:</strong> <?= e($createdOrder['customer_address']) ?></div>
                    <div><strong>Trạng thái đơn:</strong> <span class="px-2 py-0.5 rounded bg-amber-100 text-amber-800 font-bold"><?= e($createdOrder['status']) ?></span></div>
                    <div><strong>Thanh toán:</strong> <?= e($createdOrder['payment_method']) ?> (<strong><?= e($createdOrder['payment_status']) ?></strong>)</div>
                    <div class="pt-2">
                        <a href="orders.php?code=<?= e($createdOrder['order_code']) ?>" class="inline-block bg-black text-white font-bold px-4 py-2 rounded-lg">
                            Theo dõi đơn hàng <?= e($createdOrder['order_code']) ?> →
                        </a>
                    </div>
                </div>

                <?php if (($createdOrder['payment_method'] ?? '') === 'VietQR'): ?>
                    <?php
                    $qrAmount = (int)$createdOrder['total_amount'];
                    $qrInfo = rawurlencode($createdOrder['order_code']);
                    $qrUrl = "https://img.vietqr.io/image/MB-0987654321-compact2.png?amount={$qrAmount}&addInfo={$qrInfo}&accountName=DOAN%206%20TECH";
                    $paySt = (string)($createdOrder['payment_status'] ?? 'unpaid');
                    $expiresTs = !empty($createdOrder['payment_expires_at']) ? strtotime((string)$createdOrder['payment_expires_at']) : (time() + 900);
                    $remainingSec = max(0, $expiresTs - time());
                    ?>
                    <div class="bg-blue-50 border border-blue-200 rounded-xl p-4 flex flex-col sm:flex-row gap-4 items-center">
                        <img src="<?= e($qrUrl) ?>" alt="VietQR Thanh toán" class="w-36 h-36 object-contain bg-white rounded-lg border border-blue-200 p-1.5 shrink-0">
                        <div class="space-y-1.5 flex-1">
                            <div class="font-extrabold text-blue-900 uppercase">Thanh toán VietQR (15 phút)</div>
                            <div>Mã đơn / Nội dung CK: <strong class="text-gvn-red"><?= e($createdOrder['order_code']) ?></strong></div>
                            <div>Số tiền chính xác: <strong class="text-gvn-red"><?= formatPrice($qrAmount) ?></strong></div>
                            <div>Thời gian còn lại: <strong id="pc-vietqr-timer" data-seconds="<?= (int)$remainingSec ?>" class="text-blue-700">15:00</strong></div>
                            <?php if ($paySt === 'unpaid'): ?>
                                <form method="POST" action="pc-builder.php" class="pt-1">
                                    <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                                    <input type="hidden" name="action" value="confirm_vietqr">
                                    <input type="hidden" name="order_code" value="<?= e($createdOrder['order_code']) ?>">
                                    <button type="submit" class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold px-4 py-2 rounded-lg transition">
                                        <i class="fa-solid fa-check mr-1"></i> Tôi đã chuyển khoản
                                    </button>
                                </form>
                            <?php elseif ($paySt === 'pending_confirm'): ?>
                                <div class="inline-block bg-amber-100 text-amber-900 font-bold px-3 py-1.5 rounded-lg">
                                    <i class="fa-solid fa-clock mr-1"></i> Đã gửi xác nhận — Chờ Admin/Nhân viên duyệt
                                </div>
                            <?php elseif ($paySt === 'paid'): ?>
                                <div class="inline-block bg-emerald-100 text-emerald-800 font-bold px-3 py-1.5 rounded-lg">
                                    <i class="fa-solid fa-circle-check mr-1"></i> Đã thanh toán thành công
                                </div>
                            <?php else: ?>
                                <div class="inline-block bg-red-100 text-red-700 font-bold px-3 py-1.5 rounded-lg">
                                    Mã VietQR đã hết hạn
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <?php if (!empty($createdOrder['warranties'])): ?>
                <div>
                    <div class="text-xs font-extrabold uppercase mb-2">Phiếu bảo hành điện tử đi kèm linh kiện (<?= count($createdOrder['warranties']) ?>)</div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-2 text-xs">
                        <?php foreach ($createdOrder['warranties'] as $w): ?>
                            <div class="border border-gray-200 rounded-lg p-2.5 bg-gray-50">
                                <div class="font-bold text-gvn-red"><?= e($w['warranty_code']) ?></div>
                                <div class="text-[11px] text-gray-500">SN: <?= e($w['serial_number']) ?></div>
                                <div class="text-[11px] font-semibold line-clamp-1"><?= e($w['product_name']) ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="pc-builder.php" id="pc-build-order-form" class="grid grid-cols-1 lg:grid-cols-12 gap-5">
        <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
        <input type="hidden" name="action" value="place_build_order">

        <!-- 8 Slot chọn linh kiện từ MySQL -->
        <div class="lg:col-span-8 space-y-3">
            <?php foreach ($slotMeta as $key => $meta): ?>
                <?php $slotItems = $partsBySlot[$key] ?? []; ?>
                <div class="bg-white rounded-xl border border-gray-200 p-4 shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div class="flex items-center gap-3 min-w-[230px]">
                        <img id="thumb-<?= e($key) ?>" src="assets/images/products/pc1.jpg" alt="" class="w-12 h-12 rounded-lg object-cover border border-gray-200 bg-gray-50 shrink-0">
                        <div>
                            <div class="text-xs font-extrabold text-gray-900 uppercase flex items-center gap-1.5">
                                <i class="fa-solid <?= e($meta['icon']) ?> text-gvn-red"></i>
                                <span><?= e($meta['label']) ?></span>
                            </div>
                            <div id="spec-<?= e($key) ?>" class="text-[11px] text-gray-500 mt-0.5">Chưa chọn linh kiện</div>
                        </div>
                    </div>

                    <select id="select-<?= e($key) ?>" name="parts[<?= e($key) ?>]" onchange="evaluateBuild()"
                            class="flex-1 h-10 px-3 rounded-lg border border-gray-300 text-xs font-semibold text-gray-800 focus:outline-none focus:border-gvn-red">
                        <option value="">-- Chọn <?= e($meta['label']) ?> --</option>
                        <?php foreach ($slotItems as $item): ?>
                            <?php
                            $pid = (int)$item['id'];
                            $stock = (int)($item['stock'] ?? 0);
                            $isSelected = (($selectedPartIds[$key] ?? 0) === $pid);
                            ?>
                            <option value="<?= $pid ?>"
                                    data-slug="<?= e($item['slug'] ?? '') ?>"
                                    data-price="<?= (int)($item['price'] ?? 0) ?>"
                                    data-stock="<?= $stock ?>"
                                    data-socket="<?= e($item['socket'] ?? '') ?>"
                                    data-ram-type="<?= e($item['ram_type'] ?? '') ?>"
                                    data-tdp="<?= (int)($item['tdp_watt'] ?? 0) ?>"
                                    data-psu-watt="<?= (int)($item['psu_watt'] ?? 0) ?>"
                                    <?= $stock <= 0 ? 'disabled' : '' ?> <?= $isSelected ? 'selected' : '' ?>>
                                <?= e($item['name']) ?> — <?= formatPrice((int)$item['price']) ?>
                                <?= $stock <= 0 ? ' [Hết hàng]' : ' (Còn ' . $stock . ')' ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Phân tích tương thích & Đặt mua Build PC -->
        <div class="lg:col-span-4 space-y-4">
            <div class="bg-white rounded-2xl border border-gray-200 p-5 shadow-sm sticky top-20 space-y-4">
                <h2 class="text-sm font-extrabold uppercase text-gray-900 border-b border-gray-100 pb-2">
                    Kiểm tra tương thích & Đặt cấu hình
                </h2>

                <div id="compat-box" class="rounded-xl p-3.5 text-xs bg-gray-50 border border-gray-200 text-gray-600">
                    Hãy chọn linh kiện bên trái để hệ thống tự động kiểm tra tương thích phần cứng.
                </div>

                <div class="bg-gray-50 rounded-xl p-3.5 text-xs space-y-2 border border-gray-100">
                    <div class="flex justify-between">
                        <span class="text-gray-500">Tổng công suất tiêu thụ (TDP):</span>
                        <strong id="tdp-val" class="text-gray-900">0 W</strong>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Nguồn PSU khuyến nghị (+25%):</span>
                        <strong id="psu-rec" class="text-blue-700">0 W</strong>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Nguồn PSU đang chọn:</span>
                        <strong id="psu-cur" class="text-gray-900">-</strong>
                    </div>
                    <div class="flex justify-between pt-2 border-t border-gray-200 text-base font-extrabold">
                        <span>Tổng chi phí dự kiến:</span>
                        <span id="build-total" class="text-gvn-red">0đ</span>
                    </div>
                </div>

                <div class="space-y-2.5 text-xs border-t border-gray-100 pt-3">
                    <div class="font-extrabold uppercase text-gray-800">Thông tin đặt lắp ráp PC</div>
                    <input type="text" name="customer_name" required value="<?= e($formCustomerName) ?>" placeholder="Họ và tên người nhận *"
                        class="w-full h-9 px-3 rounded-lg border border-gray-300">
                    <input type="text" name="phone" required value="<?= e($formCustomerPhone) ?>" placeholder="Số điện thoại (09xxxxxxxx) *"
                        class="w-full h-9 px-3 rounded-lg border border-gray-300">
                    <input type="email" name="customer_email" value="<?= e($formCustomerEmail) ?>" placeholder="Email nhận thông báo (tùy chọn)"
                        class="w-full h-9 px-3 rounded-lg border border-gray-300">
                    <input type="text" name="address" required value="<?= e($formCustomerAddress) ?>" placeholder="Địa chỉ giao hàng / lắp đặt *"
                        class="w-full h-9 px-3 rounded-lg border border-gray-300">
                    <textarea name="note" rows="2" placeholder="Ghi chú lắp ráp (cài Windows, đi dây...)" class="w-full p-2.5 rounded-lg border border-gray-300"><?= e($formNote) ?></textarea>

                    <div class="space-y-1.5 pt-1">
                        <div class="font-bold text-gray-700">Phương thức thanh toán:</div>
                        <label class="flex items-center gap-2 p-2 rounded-lg border border-gray-200 cursor-pointer hover:bg-gray-50">
                            <input type="radio" name="payment_method" value="COD" <?= $formPaymentMethod !== 'VietQR' ? 'checked' : '' ?>>
                            <span class="font-semibold">Thanh toán khi nhận hàng (COD)</span>
                        </label>
                        <label class="flex items-center gap-2 p-2 rounded-lg border border-gray-200 cursor-pointer hover:bg-gray-50">
                            <input type="radio" name="payment_method" value="VietQR" <?= $formPaymentMethod === 'VietQR' ? 'checked' : '' ?>>
                            <span class="font-semibold">Chuyển khoản VietQR (Quét mã 15 phút)</span>
                        </label>
                    </div>
                </div>

                <button type="submit" id="btn-order-build" class="w-full bg-gvn-red hover:bg-gvn-redHover text-white font-extrabold py-3.5 rounded-xl uppercase text-xs transition shadow-md">
                    <i class="fa-solid fa-screwdriver-wrench mr-1"></i> Đặt Build PC & Lắp ráp
                </button>
            </div>
        </div>
    </form>

    <!-- Tùy chọn thêm toàn bộ linh kiện vào giỏ hàng -->
    <div class="bg-white rounded-2xl border border-gray-200 p-4 shadow-sm flex flex-wrap items-center justify-between gap-3">
        <div class="text-xs text-gray-600">
            <strong>Muốn mua chung với phụ kiện khác?</strong> Bạn cũng có thể đưa toàn bộ linh kiện đã chọn vào giỏ hàng.
        </div>
        <form method="POST" action="cart.php" id="bulk-cart-form">
            <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
            <input type="hidden" name="action" value="add_bulk">
            <div id="bulk-product-inputs"></div>
            <button type="submit" id="btn-bulk-cart" class="bg-black hover:bg-neutral-800 text-white font-bold px-5 py-2.5 rounded-xl text-xs uppercase transition">
                <i class="fa-solid fa-cart-plus mr-1"></i> Thêm tất cả vào giỏ hàng
            </button>
        </form>
    </div>
</div>

<script>
const PARTS_BY_SLOT = <?= json_encode($partsBySlot, JSON_UNESCAPED_UNICODE) ?>;
const PARTS_BY_ID = {};
Object.keys(PARTS_BY_SLOT).forEach(slot => {
    PARTS_BY_SLOT[slot].forEach(item => {
        PARTS_BY_ID[String(item.id)] = item;
    });
});

function fmtVnd(n) {
    return new Intl.NumberFormat('vi-VN').format(n) + 'đ';
}

function splitTokens(raw) {
    if (!raw) return [];
    return String(raw).split(',').map(s => s.trim().toUpperCase()).filter(Boolean);
}

function resetBuild() {
    Object.keys(PARTS_BY_SLOT).forEach(k => {
        const sel = document.getElementById('select-' + k);
        if (sel) sel.value = '';
    });
    evaluateBuild();
}

function evaluateBuild() {
    const chosen = {};
    let total = 0;
    let totalTdp = 0;
    const bulkContainer = document.getElementById('bulk-product-inputs');
    if (bulkContainer) bulkContainer.innerHTML = '';

    Object.keys(PARTS_BY_SLOT).forEach(k => {
        const sel = document.getElementById('select-' + k);
        const specEl = document.getElementById('spec-' + k);
        const thumbEl = document.getElementById('thumb-' + k);
        const val = sel ? sel.value : '';

        if (val !== '' && PARTS_BY_ID[val]) {
            const item = PARTS_BY_ID[val];
            chosen[k] = item;
            total += Number(item.price || 0);
            totalTdp += Number(item.tdp_watt || 0);

            const specParts = [];
            if (item.socket) specParts.push('Socket ' + item.socket);
            if (item.ram_type) specParts.push(item.ram_type);
            if (item.tdp_watt > 0) specParts.push('TDP ' + item.tdp_watt + 'W');
            if (item.psu_watt > 0) specParts.push(item.psu_watt + 'W');
            specParts.push('Kho: ' + item.stock);
            specEl.textContent = specParts.join(' · ');

            if (thumbEl && item.image) {
                thumbEl.src = item.image;
            }
            if (bulkContainer) {
                const hid = document.createElement('input');
                hid.type = 'hidden';
                hid.name = 'product_ids[]';
                hid.value = String(item.id);
                bulkContainer.appendChild(hid);
            }
        } else {
            specEl.textContent = 'Chưa chọn linh kiện';
        }
    });

    const recPsu = totalTdp > 0 ? Math.ceil((totalTdp + 50) * 1.25 / 50) * 50 : 0;
    document.getElementById('tdp-val').textContent = totalTdp + ' W';
    document.getElementById('psu-rec').textContent = recPsu + ' W';
    document.getElementById('psu-cur').textContent = chosen.psu && chosen.psu.psu_watt > 0 ? (chosen.psu.psu_watt + ' W') : '-';
    document.getElementById('build-total').textContent = fmtVnd(total);

    const errors = [];
    const warns = [];

    Object.keys(chosen).forEach(k => {
        if (Number(chosen[k].stock || 0) <= 0) {
            errors.push(`Linh kiện "${chosen[k].name}" đã hết hàng.`);
        }
    });

    if (chosen.cpu && chosen.mainboard) {
        const cpuSock = String(chosen.cpu.socket || '').trim().toUpperCase();
        const mbSock = String(chosen.mainboard.socket || '').trim().toUpperCase();
        if (cpuSock && mbSock && cpuSock !== mbSock) {
            errors.push(`Xung đột Socket: CPU (${chosen.cpu.socket}) không tương thích Mainboard (${chosen.mainboard.socket}).`);
        }
    }

    if (chosen.mainboard && chosen.ram) {
        const mbRam = String(chosen.mainboard.ram_type || '').trim().toUpperCase();
        const ramT = String(chosen.ram.ram_type || '').trim().toUpperCase();
        if (mbRam && ramT && mbRam !== ramT) {
            errors.push(`Xung đột chuẩn RAM: Mainboard hỗ trợ ${chosen.mainboard.ram_type} nhưng đang chọn RAM ${chosen.ram.ram_type}.`);
        }
    }

    if (chosen.cooling) {
        const coolSocks = splitTokens(chosen.cooling.socket);
        const targetSock = String((chosen.cpu && chosen.cpu.socket) || (chosen.mainboard && chosen.mainboard.socket) || '').trim().toUpperCase();
        if (coolSocks.length > 0 && targetSock && !coolSocks.includes(targetSock)) {
            errors.push(`Xung đột tản nhiệt: Tản nhiệt (${chosen.cooling.socket}) không hỗ trợ socket ${targetSock}.`);
        }
    }

    if (chosen.psu && Number(chosen.psu.psu_watt || 0) > 0) {
        const psuWatt = Number(chosen.psu.psu_watt);
        if (totalTdp > psuWatt) {
            errors.push(`Thiếu công suất nguồn: Tổng TDP (${totalTdp}W) vượt quá PSU (${psuWatt}W).`);
        } else if (recPsu > 0 && psuWatt < recPsu) {
            warns.push(`Công suất nguồn ${psuWatt}W thấp hơn mức khuyến nghị +25% headroom (${recPsu}W).`);
        }
    }

    const box = document.getElementById('compat-box');
    const btnOrder = document.getElementById('btn-order-build');
    const btnBulk = document.getElementById('btn-bulk-cart');
    const hasAny = Object.keys(chosen).length > 0;

    if (!hasAny) {
        box.className = 'rounded-xl p-3.5 text-xs bg-gray-50 border border-gray-200 text-gray-600';
        box.innerHTML = 'Hãy chọn linh kiện bên trái để hệ thống tự động kiểm tra tương thích phần cứng.';
        btnOrder.disabled = true;
        btnOrder.classList.add('opacity-50', 'cursor-not-allowed');
        if (btnBulk) {
            btnBulk.disabled = true;
            btnBulk.classList.add('opacity-50', 'cursor-not-allowed');
        }
    } else if (errors.length > 0) {
        box.className = 'rounded-xl p-3.5 text-xs bg-red-50 border border-red-300 text-red-800 space-y-1.5';
        box.innerHTML = '<div class="font-extrabold"><i class="fa-solid fa-triangle-exclamation mr-1"></i> Phát hiện lỗi tương thích:</div>' +
            errors.map(err => '<div>• ' + err + '</div>').join('');
        btnOrder.disabled = true;
        btnOrder.classList.add('opacity-50', 'cursor-not-allowed');
        if (btnBulk) {
            btnBulk.disabled = true;
            btnBulk.classList.add('opacity-50', 'cursor-not-allowed');
        }
    } else if (warns.length > 0) {
        box.className = 'rounded-xl p-3.5 text-xs bg-amber-50 border border-amber-300 text-amber-900 space-y-1.5';
        box.innerHTML = '<div class="font-extrabold"><i class="fa-solid fa-bolt mr-1"></i> Cảnh báo công suất nguồn:</div>' +
            warns.map(w => '<div>• ' + w + '</div>').join('');
        btnOrder.disabled = false;
        btnOrder.classList.remove('opacity-50', 'cursor-not-allowed');
        if (btnBulk) {
            btnBulk.disabled = false;
            btnBulk.classList.remove('opacity-50', 'cursor-not-allowed');
        }
    } else {
        box.className = 'rounded-xl p-3.5 text-xs bg-emerald-50 border border-emerald-300 text-emerald-800';
        box.innerHTML = '<div class="font-extrabold"><i class="fa-solid fa-circle-check mr-1"></i> Cấu hình PC tương thích 100%!</div><div class="mt-1">Tất cả linh kiện đã chọn khớp Socket, chuẩn RAM, tồn kho và công suất nguồn.</div>';
        btnOrder.disabled = false;
        btnOrder.classList.remove('opacity-50', 'cursor-not-allowed');
        if (btnBulk) {
            btnBulk.disabled = false;
            btnBulk.classList.remove('opacity-50', 'cursor-not-allowed');
        }
    }
}

window.addEventListener('DOMContentLoaded', () => {
    evaluateBuild();

    const timerEl = document.getElementById('pc-vietqr-timer');
    if (timerEl) {
        let sec = parseInt(timerEl.dataset.seconds || '900', 10);
        const tick = () => {
            if (sec <= 0) {
                timerEl.textContent = '00:00 (Hết hạn)';
                return;
            }
            const m = String(Math.floor(sec / 60)).padStart(2, '0');
            const s = String(sec % 60).padStart(2, '0');
            timerEl.textContent = m + ':' + s;
            sec--;
        };
        tick();
        setInterval(tick, 1000);
    }
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
