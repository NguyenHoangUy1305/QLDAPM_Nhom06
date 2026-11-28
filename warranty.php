<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/data/store.php';

$user = currentUser();
$q = trim((string)($_GET['q'] ?? ''));

if ($q !== '') {
    $warranties = getWarranties($q, null);
} elseif ($user !== null) {
    $warranties = getWarranties(null, (int)$user['id']);
} else {
    $warranties = [];
}

$statusBadgeMap = [
    'Còn hạn' => 'bg-emerald-100 text-emerald-800',
    'Đang bảo hành' => 'bg-blue-100 text-blue-800',
    'Hết hạn' => 'bg-gray-200 text-gray-700',
    'Đã hủy' => 'bg-red-100 text-red-700',
];

$pageTitle = 'Tra cứu bảo hành điện tử - ĐOÀN 6';
require_once __DIR__ . '/includes/header.php';
?>

<div class="max-w-[1040px] mx-auto px-3 py-6 space-y-5">
    <nav class="text-xs text-gray-500 flex items-center gap-2">
        <a href="index.php" class="hover:text-gvn-red"><i class="fa-solid fa-house"></i> Trang chủ</a>
        <span>/</span>
        <span class="text-gray-800 font-semibold">Tra cứu bảo hành điện tử</span>
    </nav>

    <div class="bg-white rounded-2xl border border-gray-200 p-5 shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-2 mb-2">
            <h1 class="text-xl font-extrabold text-gray-900 uppercase flex items-center gap-2">
                <i class="fa-solid fa-shield-halved text-gvn-red"></i>
                Tra cứu bảo hành điện tử
            </h1>
            <?php if ($user !== null): ?>
                <span class="text-xs text-gray-500">Tài khoản: <strong class="text-gray-800"><?= e($user['name']) ?></strong></span>
            <?php endif; ?>
        </div>
        <p class="text-xs text-gray-500 mb-4">
            Nhập <strong>Mã bảo hành</strong>, <strong>Số Serial</strong>, <strong>Mã đơn hàng</strong> hoặc <strong>Số điện thoại</strong> mua hàng để kiểm tra thời hạn bảo hành điện tử.
        </p>

        <form method="GET" action="warranty.php" class="flex flex-col sm:flex-row gap-2.5">
            <input type="text" name="q" value="<?= e($q) ?>"
                   placeholder="Nhập mã bảo hành, số Serial, mã đơn hàng hoặc SĐT..."
                   class="flex-1 h-10 px-4 rounded-lg border border-gray-300 text-sm focus:outline-none focus:border-gvn-red">
            <button type="submit" class="bg-gvn-red hover:bg-gvn-redHover text-white font-bold px-6 h-10 rounded-lg text-xs uppercase transition">
                <i class="fa-solid fa-magnifying-glass mr-1"></i> Tra cứu
            </button>
            <?php if ($q !== '' && $user !== null): ?>
                <a href="warranty.php" class="h-10 px-4 rounded-lg border border-gray-300 text-xs font-bold text-gray-700 hover:bg-gray-50 flex items-center justify-center">
                    Bảo hành của tôi
                </a>
            <?php endif; ?>
        </form>
    </div>

    <?php if (empty($warranties)): ?>
        <div class="bg-white rounded-2xl border border-gray-200 p-8 text-center shadow-sm space-y-3">
            <i class="fa-solid fa-shield text-4xl text-gray-300"></i>
            <p class="text-sm text-gray-600 font-semibold">
                <?= $q !== ''
                    ? 'Không tìm thấy thông tin bảo hành nào khớp với "' . e($q) . '".'
                    : ($user !== null
                        ? 'Bạn chưa có phiếu bảo hành điện tử nào.'
                        : 'Nhập mã bảo hành, số Serial, mã đơn hàng hoặc SĐT để kiểm tra thời hạn bảo hành.') ?>
            </p>
            <p class="text-xs text-gray-400">
                Mọi đơn hàng đặt thành công tại ĐOÀN 6 đều được tự động kích hoạt bảo hành điện tử theo từng sản phẩm.
            </p>
            <?php if ($user === null): ?>
                <div>
                    <a href="login.php?redirect=warranty.php" class="inline-block bg-black text-white font-bold px-5 py-2 rounded-xl text-xs">
                        Đăng nhập để xem bảo hành của bạn
                    </a>
                </div>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="space-y-3">
            <div class="text-xs text-gray-500 px-1">
                Tìm thấy <strong class="text-gray-900"><?= count($warranties) ?></strong> phiếu bảo hành điện tử
            </div>
            <?php foreach ($warranties as $w): ?>
                <?php
                $status = (string)($w['status'] ?? 'Còn hạn');
                $badgeClass = $statusBadgeMap[$status] ?? 'bg-gray-100 text-gray-700';
                $startFormatted = !empty($w['start_date']) ? date('d/m/Y', strtotime((string)$w['start_date'])) : '—';
                $endTs = !empty($w['end_date']) ? strtotime((string)$w['end_date'] . ' 23:59:59') : 0;
                $endFormatted = $endTs > 0 ? date('d/m/Y', $endTs) : '—';
                $remainDays = $endTs > 0 ? (int)floor(($endTs - time()) / 86400) : 0;
                ?>
                <div class="bg-white rounded-2xl border border-gray-200 p-4 sm:p-5 shadow-sm space-y-3">
                    <div class="flex flex-wrap items-center justify-between gap-2 pb-3 border-b border-gray-100">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="font-mono text-sm font-extrabold text-gvn-red"><?= e($w['warranty_code']) ?></span>
                            <?php if (!empty($w['serial_number'])): ?>
                                <span class="font-mono text-[11px] bg-gray-100 text-gray-700 px-2 py-0.5 rounded font-semibold">
                                    SN: <?= e($w['serial_number']) ?>
                                </span>
                            <?php endif; ?>
                        </div>
                        <span class="text-xs font-bold px-3 py-1 rounded-full <?= e($badgeClass) ?>">
                            <?= e($status) ?>
                        </span>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-12 gap-3 text-xs">
                        <div class="md:col-span-6 space-y-1">
                            <div class="text-sm font-bold text-gray-900"><?= e($w['product_name']) ?></div>
                            <div class="text-gray-500">
                                Đơn hàng:
                                <?php if (!empty($w['order_code'])): ?>
                                    <a href="orders.php?code=<?= e($w['order_code']) ?>" class="font-bold text-blue-600 hover:underline">
                                        <?= e($w['order_code']) ?>
                                    </a>
                                <?php else: ?>
                                    <span>—</span>
                                <?php endif; ?>
                                · SĐT: <strong><?= e($w['customer_phone']) ?></strong>
                            </div>
                        </div>

                        <div class="md:col-span-6 grid grid-cols-3 gap-2 bg-gray-50 rounded-xl p-3 border border-gray-100 text-center">
                            <div>
                                <div class="text-[10px] text-gray-400 uppercase font-bold">Thời hạn</div>
                                <div class="font-extrabold text-gray-900 mt-0.5"><?= (int)($w['warranty_months'] ?? 24) ?> tháng</div>
                            </div>
                            <div>
                                <div class="text-[10px] text-gray-400 uppercase font-bold">Từ — Đến</div>
                                <div class="font-semibold text-gray-800 mt-0.5"><?= e($startFormatted) ?> – <?= e($endFormatted) ?></div>
                            </div>
                            <div>
                                <div class="text-[10px] text-gray-400 uppercase font-bold">Còn lại</div>
                                <?php if ($status === 'Đã hủy'): ?>
                                    <div class="font-bold text-red-600 mt-0.5">Đã hủy</div>
                                <?php elseif ($remainDays > 0 && $status !== 'Hết hạn'): ?>
                                    <div class="font-extrabold text-emerald-700 mt-0.5">Còn <?= $remainDays ?> ngày</div>
                                <?php else: ?>
                                    <div class="font-bold text-gray-500 mt-0.5">Hết hạn</div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <?php if (!empty($w['notes'])): ?>
                        <div class="text-[11px] text-gray-500 bg-blue-50/60 border border-blue-100 rounded-lg px-3 py-2">
                            <i class="fa-solid fa-circle-info text-blue-500 mr-1"></i> <?= e($w['notes']) ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
