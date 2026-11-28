<?php
require_once __DIR__ . '/_auth.php';
$adminUser = requireStaffOrAdmin();
require_once __DIR__ . '/_layout.php';

$products = getAllProducts();
$categories = getCategories();
$brands = getBrands();
$orders = getAdminOrders();

$totalRevenue = 0;
$pendingRevenue = 0;
$pcBuildCount = 0;
$walkInCount = 0;
$pendingVietQrCount = 0;

foreach ($orders as $o) {
    $st = (string)($o['status'] ?? '');
    $amt = (int)($o['total_amount'] ?? $o['total_price'] ?? 0);
    if ($st === 'Hoàn thành') {
        $totalRevenue += $amt;
    } elseif ($st !== 'Đã hủy') {
        $pendingRevenue += $amt;
    }
    if (($o['order_type'] ?? '') === 'pc_build') {
        $pcBuildCount++;
    }
    if (($o['order_type'] ?? '') === 'walk_in') {
        $walkInCount++;
    }
    if (($o['payment_method'] ?? '') === 'VietQR' && ($o['payment_status'] ?? '') === 'pending_confirm' && !in_array($st, ['Hoàn thành', 'Đã hủy'], true)) {
        $pendingVietQrCount++;
    }
}

$lowStockCount = 0;
foreach ($products as $p) {
    if ((int)($p['stock'] ?? 0) <= 5) {
        $lowStockCount++;
    }
}

$pendingTradeIns = 0;
$staffMap = [];
$pdo = getPDO();
if ($pdo) {
    try {
        $pendingTradeIns = (int)$pdo->query("SELECT COUNT(*) FROM trade_in_requests WHERE status = 'Chờ định giá'")->fetchColumn();
        foreach ($pdo->query("SELECT id, name FROM users WHERE role IN ('admin', 'staff')")->fetchAll() as $u) {
            $staffMap[(int)$u['id']] = (string)$u['name'];
        }
    } catch (PDOException $e) {
    }
}

renderAdminHeader('Tổng quan hệ thống', 'dashboard', $adminUser);
?>

<?php if (isStaff()): ?>
    <div class="bg-amber-50 border border-amber-200 text-amber-900 text-xs rounded-xl p-3.5 mb-5 flex items-center justify-between gap-3">
        <div>
            <i class="fa-solid fa-user-tie mr-1 text-amber-600"></i>
            Bạn đang đăng nhập với vai trò <strong>Nhân viên</strong>. Bạn có thể tạo đơn tại quầy, duyệt/cập nhật tiến độ đơn hàng (kể cả đơn Build PC), tra cứu bảo hành & thu cũ đổi mới. Thao tác thêm/sửa/xóa sản phẩm, danh mục, thương hiệu cần xác thực mật khẩu Admin.
        </div>
        <a href="walkin-order.php" class="shrink-0 bg-gvn-red hover:bg-gvn-redHover text-white font-bold px-3.5 py-2 rounded-lg transition">
            + Tạo đơn tại quầy
        </a>
    </div>
<?php endif; ?>

<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-5">
    <div class="bg-white rounded-xl border border-gray-200 p-4 shadow-sm">
        <div class="text-xs text-gray-500 font-semibold mb-1">Doanh thu thực thu</div>
        <div class="text-xl font-extrabold text-gvn-red"><?= formatPrice($totalRevenue) ?></div>
        <div class="text-[11px] text-emerald-600 mt-1"><i class="fa-solid fa-circle-check"></i> Hoàn thành · Đang xử lý: <?= formatPrice($pendingRevenue) ?></div>
    </div>
    <div class="bg-white rounded-xl border border-gray-200 p-4 shadow-sm">
        <div class="text-xs text-gray-500 font-semibold mb-1">Đơn hàng (Build PC / Tại quầy)</div>
        <div class="text-xl font-extrabold text-gray-900"><?= count($orders) ?> đơn</div>
        <div class="text-[11px] text-gray-600 mt-1">
            Build PC: <strong><?= $pcBuildCount ?></strong> · Tại quầy: <strong><?= $walkInCount ?></strong>
            · <a href="orders.php" class="text-blue-600 hover:underline">Xem →</a>
        </div>
    </div>
    <div class="bg-white rounded-xl border border-gray-200 p-4 shadow-sm">
        <div class="text-xs text-gray-500 font-semibold mb-1">VietQR chờ duyệt & Thu cũ</div>
        <div class="text-xl font-extrabold <?= $pendingVietQrCount > 0 ? 'text-amber-600' : 'text-gray-900' ?>"><?= $pendingVietQrCount ?> VietQR</div>
        <div class="text-[11px] text-gray-600 mt-1">
            Thu cũ chờ định giá: <strong><?= $pendingTradeIns ?></strong>
            · <a href="trade-ins.php" class="text-blue-600 hover:underline">Xử lý →</a>
        </div>
    </div>
    <div class="bg-white rounded-xl border border-gray-200 p-4 shadow-sm">
        <div class="text-xs text-gray-500 font-semibold mb-1">Kho hàng & Danh mục</div>
        <div class="text-xl font-extrabold text-gray-900"><?= count($products) ?> SP</div>
        <div class="text-[11px] mt-1">
            <span class="<?= $lowStockCount > 0 ? 'text-red-600 font-bold' : 'text-gray-500' ?>">Sắp hết (≤5): <?= $lowStockCount ?></span>
            · <?= count($categories) ?> danh mục · <?= count($brands) ?> hãng
        </div>
    </div>
</div>

<div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-6 gap-3 mb-6 text-xs font-bold">
    <a href="walkin-order.php" class="bg-white hover:border-gvn-red border border-gray-200 rounded-xl p-3 flex items-center gap-2.5 shadow-sm transition">
        <i class="fa-solid fa-cash-register text-gvn-red text-base"></i><span>Đơn tại quầy</span>
    </a>
    <a href="orders.php?order_type=pc_build" class="bg-white hover:border-gvn-red border border-gray-200 rounded-xl p-3 flex items-center gap-2.5 shadow-sm transition">
        <i class="fa-solid fa-computer text-blue-600 text-base"></i><span>Đơn Build PC</span>
    </a>
    <a href="products.php" class="bg-white hover:border-gvn-red border border-gray-200 rounded-xl p-3 flex items-center gap-2.5 shadow-sm transition">
        <i class="fa-solid fa-boxes-stacked text-emerald-600 text-base"></i><span>Sản phẩm & Kho</span>
    </a>
    <a href="warranties.php" class="bg-white hover:border-gvn-red border border-gray-200 rounded-xl p-3 flex items-center gap-2.5 shadow-sm transition">
        <i class="fa-solid fa-shield-halved text-indigo-600 text-base"></i><span>Bảo hành</span>
    </a>
    <?php if (isAdmin()): ?>
        <a href="staff.php" class="bg-white hover:border-gvn-red border border-gray-200 rounded-xl p-3 flex items-center gap-2.5 shadow-sm transition">
            <i class="fa-solid fa-user-tie text-purple-600 text-base"></i><span>Nhân viên</span>
        </a>
        <a href="news.php" class="bg-white hover:border-gvn-red border border-gray-200 rounded-xl p-3 flex items-center gap-2.5 shadow-sm transition">
            <i class="fa-solid fa-newspaper text-amber-600 text-base"></i><span>Bài viết Review</span>
        </a>
    <?php else: ?>
        <a href="trade-ins.php" class="bg-white hover:border-gvn-red border border-gray-200 rounded-xl p-3 flex items-center gap-2.5 shadow-sm transition">
            <i class="fa-solid fa-arrows-rotate text-amber-600 text-base"></i><span>Thu cũ đổi mới</span>
        </a>
        <a href="profile.php" class="bg-white hover:border-gvn-red border border-gray-200 rounded-xl p-3 flex items-center gap-2.5 shadow-sm transition">
            <i class="fa-solid fa-id-card text-gray-700 text-base"></i><span>Hồ sơ cá nhân</span>
        </a>
    <?php endif; ?>
</div>

<div class="bg-white rounded-xl border border-gray-200 p-5 shadow-sm">
    <div class="flex items-center justify-between mb-4">
        <h2 class="text-sm font-extrabold uppercase text-gray-900">Đơn hàng gần đây</h2>
        <a href="orders.php" class="text-xs font-bold text-gvn-red hover:underline">Xem tất cả →</a>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-xs text-left">
            <thead class="bg-gray-50 text-gray-600 uppercase">
                <tr>
                    <th class="p-3">Mã đơn</th>
                    <th class="p-3">Loại</th>
                    <th class="p-3">Khách hàng</th>
                    <th class="p-3">Thanh toán</th>
                    <th class="p-3">Tổng tiền</th>
                    <th class="p-3">Trạng thái</th>
                    <th class="p-3">Ngày tạo</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <?php foreach (array_slice($orders, 0, 8) as $o): ?>
                    <?php
                    $otype = (string)($o['order_type'] ?? 'online');
                    $typeLabel = match ($otype) {
                        'pc_build' => 'Build PC',
                        'walk_in' => 'Tại quầy',
                        default => 'Online',
                    };
                    $typeCls = match ($otype) {
                        'pc_build' => 'bg-purple-100 text-purple-800',
                        'walk_in' => 'bg-sky-100 text-sky-800',
                        default => 'bg-gray-100 text-gray-700',
                    };
                    ?>
                    <tr>
                        <td class="p-3 font-bold text-gvn-red">
                            <a href="orders.php?q=<?= e($o['order_code'] ?? $o['code']) ?>" class="hover:underline">
                                <?= e($o['order_code'] ?? ('DH' . $o['id'])) ?>
                            </a>
                        </td>
                        <td class="p-3">
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold <?= $typeCls ?>"><?= e($typeLabel) ?></span>
                        </td>
                        <td class="p-3">
                            <div class="font-semibold"><?= e($o['customer_name']) ?></div>
                            <div class="text-[11px] text-gray-500"><?= e($o['customer_phone'] ?? $o['phone']) ?></div>
                            <?php if (!empty($o['created_by_staff_id']) && isset($staffMap[(int)$o['created_by_staff_id']])): ?>
                                <div class="text-[10px] text-sky-700">NV: <?= e($staffMap[(int)$o['created_by_staff_id']]) ?></div>
                            <?php endif; ?>
                        </td>
                        <td class="p-3">
                            <span class="font-bold"><?= e($o['payment_method'] ?? 'COD') ?></span>
                            <div class="text-[10px] text-gray-500"><?= e($o['payment_status'] ?? 'unpaid') ?></div>
                        </td>
                        <td class="p-3 font-bold"><?= formatPrice((int)($o['total_amount'] ?? $o['total_price'])) ?></td>
                        <td class="p-3">
                            <span class="px-2.5 py-1 rounded-full text-[11px] font-bold bg-amber-100 text-amber-800"><?= e($o['status']) ?></span>
                        </td>
                        <td class="p-3 text-gray-500"><?= e($o['created_at']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php renderAdminFooter(); ?>
