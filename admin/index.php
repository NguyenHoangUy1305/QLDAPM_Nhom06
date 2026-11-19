<?php
require_once __DIR__ . '/_auth.php';
require_once __DIR__ . '/_layout.php';

$adminUser = requireAdmin();
initAdminData();

$products = getAllProducts();
$categories = getCategories();
$orders = getAdminOrders();

$totalRevenue = 0;
foreach ($orders as $o) {
    if (($o['status'] ?? '') !== 'Đã hủy') {
        $totalRevenue += (int)($o['total_price'] ?? 0);
    }
}

renderAdminHeader('Tổng quan hệ thống', 'dashboard', $adminUser);
?>

<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <div class="bg-white rounded-xl border border-gray-200 p-4 shadow-sm">
        <div class="text-xs text-gray-500 font-semibold mb-1">Tổng doanh thu</div>
        <div class="text-xl font-extrabold text-gvn-red"><?= formatPrice($totalRevenue) ?></div>
        <div class="text-[11px] text-emerald-600 mt-1"><i class="fa-solid fa-arrow-trend-up"></i> Không tính đơn đã hủy</div>
    </div>
    <div class="bg-white rounded-xl border border-gray-200 p-4 shadow-sm">
        <div class="text-xs text-gray-500 font-semibold mb-1">Tổng đơn hàng</div>
        <div class="text-xl font-extrabold text-gray-900"><?= count($orders) ?> đơn</div>
        <div class="text-[11px] text-blue-600 mt-1"><a href="orders.php" class="hover:underline">Xem chi tiết →</a></div>
    </div>
    <div class="bg-white rounded-xl border border-gray-200 p-4 shadow-sm">
        <div class="text-xs text-gray-500 font-semibold mb-1">Sản phẩm đang bán</div>
        <div class="text-xl font-extrabold text-gray-900"><?= count($products) ?> SP</div>
        <div class="text-[11px] text-blue-600 mt-1"><a href="products.php" class="hover:underline">Quản lý sản phẩm →</a></div>
    </div>
    <div class="bg-white rounded-xl border border-gray-200 p-4 shadow-sm">
        <div class="text-xs text-gray-500 font-semibold mb-1">Danh mục ngành hàng</div>
        <div class="text-xl font-extrabold text-gray-900"><?= count($categories) ?> nhóm</div>
        <div class="text-[11px] text-blue-600 mt-1"><a href="categories.php" class="hover:underline">Quản lý danh mục →</a></div>
    </div>
</div>

<div class="bg-white rounded-xl border border-gray-200 p-5 shadow-sm">
    <div class="flex items-center justify-between mb-4">
        <h2 class="text-sm font-extrabold uppercase text-gray-900">Đơn hàng gần đây</h2>
        <a href="orders.php" class="text-xs font-bold text-gvn-red hover:underline">Xem tất cả</a>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-xs text-left">
            <thead class="bg-gray-50 text-gray-600 uppercase">
                <tr>
                    <th class="p-3">Mã đơn</th>
                    <th class="p-3">Khách hàng</th>
                    <th class="p-3">Tổng tiền</th>
                    <th class="p-3">Trạng thái</th>
                    <th class="p-3">Ngày đặt</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <?php foreach (array_slice($orders, 0, 5) as $o): ?>
                    <tr>
                        <td class="p-3 font-bold text-gvn-red"><?= e($o['code'] ?? ('DH' . $o['id'])) ?></td>
                        <td class="p-3"><?= e($o['customer_name']) ?> (<?= e($o['phone']) ?>)</td>
                        <td class="p-3 font-bold"><?= formatPrice((int)$o['total_price']) ?></td>
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
