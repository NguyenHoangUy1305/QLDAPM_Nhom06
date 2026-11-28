<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/data/store.php';

$user = currentUser();
$pdo = getPDO();
$products = getAllProducts();

$q = trim((string)($_GET['q'] ?? ''));
$createdSuccess = !empty($_GET['created']);
$error = '';
$successNotice = '';

$customerName = $user['name'] ?? '';
$customerPhone = $user['phone'] ?? '';
$deviceName = '';
$deviceCondition = 'Ngoại hình 95%, hoạt động bình thường';
$estimatedValue = '';
$targetProductId = 0;
$notes = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();

    $customerName = trim((string)($_POST['customer_name'] ?? ''));
    $customerPhone = trim((string)($_POST['phone'] ?? '')) ?: trim((string)($_POST['customer_phone'] ?? ''));
    $deviceName = trim((string)($_POST['device_name'] ?? ''));
    $deviceCondition = trim((string)($_POST['device_condition'] ?? ''));
    $estimatedValue = trim((string)($_POST['estimated_value'] ?? '0'));
    $targetProductId = (int)($_POST['target_product_id'] ?? 0);
    $notes = trim((string)($_POST['notes'] ?? ''));

    if (!$pdo) {
        $error = 'Không thể kết nối cơ sở dữ liệu để gửi yêu cầu thu cũ đổi mới.';
    } else {
        try {
            $created = createTradeInRequest($pdo, [
                'user_id' => $user['id'] ?? null,
                'customer_name' => $customerName,
                'customer_phone' => $customerPhone,
                'device_name' => $deviceName,
                'device_condition' => $deviceCondition,
                'estimated_value' => (float)preg_replace('/\D+/', '', $estimatedValue),
                'target_product_id' => $targetProductId > 0 ? $targetProductId : null,
                'notes' => $notes,
            ]);

            $_SESSION['tradein_flash'] = 'Đã gửi yêu cầu Thu cũ đổi mới thành công! Mã yêu cầu của bạn là ' . $created['request_code'] . '.';
            header('Location: trade-in.php?q=' . urlencode((string)$created['request_code']) . '&created=1');
            exit;
        } catch (Throwable $e) {
            $error = $e->getMessage();
        }
    }
}

if (!empty($_SESSION['tradein_flash'])) {
    $successNotice = (string)$_SESSION['tradein_flash'];
    unset($_SESSION['tradein_flash']);
} elseif ($createdSuccess && $q !== '') {
    $successNotice = 'Yêu cầu ' . $q . ' đã được ghi nhận thành công!';
}

$requests = [];
if ($q !== '' && $pdo) {
    try {
        $codeUpper = strtoupper($q);
        $digits = preg_replace('/\D+/', '', $q);
        if ($digits !== '' && $digits !== $q) {
            $stmt = $pdo->prepare('
                SELECT t.*, p.name AS target_product_name, p.slug AS target_product_slug
                FROM trade_in_requests t
                LEFT JOIN products p ON p.id = t.target_product_id
                WHERE t.request_code = ? OR t.customer_phone = ? OR t.customer_phone = ?
                ORDER BY t.id DESC
            ');
            $stmt->execute([$codeUpper, $q, $digits]);
        } else {
            $stmt = $pdo->prepare('
                SELECT t.*, p.name AS target_product_name, p.slug AS target_product_slug
                FROM trade_in_requests t
                LEFT JOIN products p ON p.id = t.target_product_id
                WHERE t.request_code = ? OR t.customer_phone = ?
                ORDER BY t.id DESC
            ');
            $stmt->execute([$codeUpper, $q]);
        }
        $requests = $stmt->fetchAll();
    } catch (PDOException $e) {
        $requests = [];
    }
} elseif ($user !== null) {
    $requests = getTradeIns((int)$user['id']);
}

$statusBadgeMap = [
    'Chờ định giá' => 'bg-amber-100 text-amber-800',
    'Đã báo giá' => 'bg-blue-100 text-blue-800',
    'Hoàn tất' => 'bg-emerald-100 text-emerald-800',
    'Từ chối' => 'bg-red-100 text-red-700',
];

$pageTitle = 'Thu cũ đổi mới - ĐOÀN 6';
require_once __DIR__ . '/includes/header.php';
?>

<div class="max-w-[1200px] mx-auto px-3 py-6 space-y-5">
    <nav class="text-xs text-gray-500 flex items-center gap-2">
        <a href="index.php" class="hover:text-gvn-red"><i class="fa-solid fa-house"></i> Trang chủ</a>
        <span>/</span>
        <span class="text-gray-800 font-semibold">Thu cũ đổi mới</span>
    </nav>

    <div class="bg-gradient-to-r from-gvn-dark to-zinc-800 text-white rounded-2xl p-5 sm:p-6 shadow-sm">
        <h1 class="text-xl sm:text-2xl font-extrabold uppercase flex items-center gap-2">
            <i class="fa-solid fa-rotate text-gvn-red"></i>
            Chương trình Thu cũ đổi mới tại ĐOÀN 6
        </h1>
        <p class="text-xs text-zinc-300 mt-1.5 max-w-2xl">
            Hỗ trợ thu lại Laptop, VGA, Màn hình, Linh kiện cũ với mức giá minh bạch. Định giá nhanh tại Showroom, thanh toán phần chênh lệch linh hoạt bằng COD hoặc chuyển khoản VietQR.
        </p>
    </div>

    <?php if ($successNotice !== ''): ?>
        <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-semibold rounded-xl p-3.5">
            <i class="fa-solid fa-circle-check mr-1"></i> <?= e($successNotice) ?>
        </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-5">
        <!-- Left: Submission Form -->
        <div class="lg:col-span-6 bg-white rounded-2xl border border-gray-200 p-5 shadow-sm">
            <h2 class="text-base font-extrabold text-gray-900 uppercase mb-4 pb-2 border-b border-gray-100">
                Gửi yêu cầu định giá thiết bị cũ
            </h2>

            <?php if ($error !== ''): ?>
                <div class="bg-red-50 border border-red-200 text-red-700 text-xs font-semibold rounded-xl p-3 mb-4">
                    <i class="fa-solid fa-triangle-exclamation mr-1"></i> <?= e($error) ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="trade-in.php" class="space-y-3.5 text-xs">
                <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block font-bold text-gray-700 mb-1">Họ và tên *</label>
                        <input type="text" name="customer_name" value="<?= e($customerName) ?>" required placeholder="Nguyễn Văn A"
                               class="w-full h-10 px-3.5 rounded-lg border border-gray-300 text-sm focus:outline-none focus:border-gvn-red">
                    </div>
                    <div>
                        <label class="block font-bold text-gray-700 mb-1">Số điện thoại *</label>
                        <input type="text" name="customer_phone" value="<?= e($customerPhone) ?>" required placeholder="0909123456"
                               class="w-full h-10 px-3.5 rounded-lg border border-gray-300 text-sm focus:outline-none focus:border-gvn-red">
                    </div>
                </div>

                <div>
                    <label class="block font-bold text-gray-700 mb-1">Tên thiết bị cũ muốn thu *</label>
                    <input type="text" name="device_name" value="<?= e($deviceName) ?>" required
                           placeholder="Ví dụ: VGA GTX 1660 Super 6GB / Laptop ASUS VivoBook..."
                           class="w-full h-10 px-3.5 rounded-lg border border-gray-300 text-sm focus:outline-none focus:border-gvn-red">
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block font-bold text-gray-700 mb-1">Tình trạng thiết bị *</label>
                        <input type="text" name="device_condition" value="<?= e($deviceCondition) ?>" required
                               placeholder="Ngoại hình 95%, còn hộp, zin..."
                               class="w-full h-10 px-3.5 rounded-lg border border-gray-300 text-sm focus:outline-none focus:border-gvn-red">
                    </div>
                    <div>
                        <label class="block font-bold text-gray-700 mb-1">Giá mong muốn (VNĐ)</label>
                        <input type="number" min="0" step="50000" name="estimated_value" value="<?= e($estimatedValue) ?>"
                               placeholder="Ví dụ: 3500000"
                               class="w-full h-10 px-3.5 rounded-lg border border-gray-300 text-sm focus:outline-none focus:border-gvn-red">
                    </div>
                </div>

                <div>
                    <label class="block font-bold text-gray-700 mb-1">Sản phẩm muốn lên đời (tùy chọn)</label>
                    <select name="target_product_id" class="w-full h-10 px-3 rounded-lg border border-gray-300 text-xs bg-white focus:outline-none focus:border-gvn-red">
                        <option value="0">-- Chỉ bán lại / Chưa chọn sản phẩm đổi --</option>
                        <?php foreach ($products as $prod): ?>
                            <option value="<?= (int)$prod['id'] ?>" <?= $targetProductId === (int)$prod['id'] ? 'selected' : '' ?>>
                                <?= e($prod['name']) ?> (<?= formatPrice((int)$prod['price']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block font-bold text-gray-700 mb-1">Ghi chú thêm</label>
                    <textarea name="notes" rows="2" placeholder="Mô tả phụ kiện đi kèm, thời gian mua, bảo hành hãng..."
                              class="w-full p-3 rounded-lg border border-gray-300 text-sm focus:outline-none focus:border-gvn-red"><?= e($notes) ?></textarea>
                </div>

                <button type="submit" class="w-full bg-gvn-red hover:bg-gvn-redHover text-white font-extrabold py-3.5 rounded-xl uppercase text-xs transition shadow-md">
                    <i class="fa-solid fa-paper-plane mr-1"></i> Gửi yêu cầu định giá
                </button>
            </form>
        </div>

        <!-- Right: Lookup & User Requests -->
        <div class="lg:col-span-6 space-y-4">
            <div class="bg-white rounded-2xl border border-gray-200 p-5 shadow-sm">
                <h2 class="text-base font-extrabold text-gray-900 uppercase mb-2">Tra cứu yêu cầu Thu cũ đổi mới</h2>
                <p class="text-xs text-gray-500 mb-3">Nhập mã yêu cầu thu cũ hoặc số điện thoại để theo dõi báo giá từ kỹ thuật viên.</p>
                <form method="GET" action="trade-in.php" class="flex gap-2">
                    <input type="text" name="q" value="<?= e($q) ?>" placeholder="Mã yêu cầu (TC...) hoặc SĐT..."
                           class="flex-1 h-10 px-3.5 rounded-lg border border-gray-300 text-sm focus:outline-none focus:border-gvn-red">
                    <button type="submit" class="bg-black hover:bg-neutral-800 text-white font-bold px-5 h-10 rounded-lg text-xs uppercase transition">
                        Tra cứu
                    </button>
                </form>
            </div>

            <?php if (empty($requests)): ?>
                <div class="bg-white rounded-2xl border border-gray-200 p-6 text-center shadow-sm text-xs text-gray-500">
                    <?= $q !== ''
                        ? 'Không tìm thấy yêu cầu thu cũ đổi mới nào khớp với "' . e($q) . '".'
                        : ($user !== null
                            ? 'Bạn chưa có yêu cầu thu cũ đổi mới nào.'
                            : 'Hãy gửi yêu cầu bên trái hoặc nhập mã yêu cầu / SĐT để kiểm tra trạng thái.') ?>
                </div>
            <?php else: ?>
                <div class="space-y-3">
                    <?php foreach ($requests as $req): ?>
                        <?php
                        $st = (string)($req['status'] ?? 'Chờ định giá');
                        $stClass = $statusBadgeMap[$st] ?? 'bg-gray-100 text-gray-700';
                        ?>
                        <div class="bg-white rounded-2xl border border-gray-200 p-4 shadow-sm space-y-2 text-xs">
                            <div class="flex flex-wrap items-center justify-between gap-2 pb-2 border-b border-gray-100">
                                <div>
                                    <span class="font-mono font-extrabold text-gvn-red text-sm"><?= e($req['request_code']) ?></span>
                                    <span class="text-gray-400 ml-2"><?= !empty($req['created_at']) ? e(date('d/m/Y H:i', strtotime((string)$req['created_at']))) : '' ?></span>
                                </div>
                                <span class="font-bold px-2.5 py-0.5 rounded-full <?= e($stClass) ?>"><?= e($st) ?></span>
                            </div>
                            <div class="font-bold text-gray-900 text-sm"><?= e($req['device_name']) ?></div>
                            <div class="text-gray-600">Tình trạng: <?= e($req['device_condition']) ?></div>
                            <div class="flex flex-wrap items-center justify-between gap-2 text-gray-600">
                                <span>Khách: <strong><?= e($req['customer_name']) ?></strong> — <?= e($req['customer_phone']) ?></span>
                                <span class="font-extrabold text-gvn-red">
                                    Giá ước tính/báo giá: <?= formatPrice((int)round((float)($req['estimated_value'] ?? 0))) ?>
                                </span>
                            </div>
                            <?php if (!empty($req['target_product_name'])): ?>
                                <div class="text-blue-700 font-semibold">
                                    Lên đời sản phẩm:
                                    <?php if (!empty($req['target_product_slug'])): ?>
                                        <a href="product.php?slug=<?= e($req['target_product_slug']) ?>" class="hover:underline">
                                            <?= e($req['target_product_name']) ?>
                                        </a>
                                    <?php else: ?>
                                        <?= e($req['target_product_name']) ?>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                            <?php if (!empty($req['admin_note'])): ?>
                                <div class="bg-blue-50 border border-blue-100 text-blue-800 rounded-lg p-2.5">
                                    <strong>Phản hồi kỹ thuật viên:</strong> <?= e($req['admin_note']) ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
