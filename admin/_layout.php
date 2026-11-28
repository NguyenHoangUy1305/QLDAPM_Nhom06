<?php
require_once __DIR__ . '/_auth.php';

$adminPageTitle = $adminPageTitle ?? 'Quản trị - ĐOÀN 6';
$activeMenu = $activeMenu ?? 'dashboard';
$adminUser = $adminUser ?? requireStaffOrAdmin();

function renderAdminHeader(string $title, string $activeMenu, array $adminUser): void
{
    $isAdminUser = (($adminUser['role'] ?? '') === 'admin');
    $roleBadgeText = $isAdminUser ? 'Admin' : 'Nhân viên';
    $roleBadgeClass = $isAdminUser
        ? 'bg-gvn-red text-white'
        : 'bg-amber-400 text-zinc-950';

    $navItems = [
        ['href' => 'index.php', 'key' => 'dashboard', 'icon' => 'fa-chart-line', 'label' => 'Tổng quan', 'short' => 'Tổng quan'],
        ['href' => 'walkin-order.php', 'key' => 'walkin', 'icon' => 'fa-cash-register', 'label' => 'Tạo đơn tại quầy', 'short' => 'Tại quầy'],
        ['href' => 'orders.php', 'key' => 'orders', 'icon' => 'fa-file-invoice-dollar', 'label' => 'Quản lý Đơn hàng', 'short' => 'Đơn hàng'],
        ['href' => 'products.php', 'key' => 'products', 'icon' => 'fa-box', 'label' => 'Sản phẩm & Kho', 'short' => 'Sản phẩm'],
        ['href' => 'categories.php', 'key' => 'categories', 'icon' => 'fa-layer-group', 'label' => 'Danh mục', 'short' => 'Danh mục'],
        ['href' => 'brands.php', 'key' => 'brands', 'icon' => 'fa-award', 'label' => 'Thương hiệu', 'short' => 'Thương hiệu'],
        ['href' => 'warranties.php', 'key' => 'warranties', 'icon' => 'fa-shield-halved', 'label' => 'Bảo hành', 'short' => 'Bảo hành'],
        ['href' => 'trade-ins.php', 'key' => 'tradeins', 'icon' => 'fa-arrows-rotate', 'label' => 'Thu cũ đổi mới', 'short' => 'Thu cũ'],
        ['href' => 'users.php', 'key' => 'users', 'icon' => 'fa-users', 'label' => 'Khách hàng & Hồ sơ', 'short' => 'Khách hàng'],
    ];

    if ($isAdminUser) {
        $navItems[] = ['href' => 'staff.php', 'key' => 'staff', 'icon' => 'fa-user-tie', 'label' => 'Quản lý Nhân viên', 'short' => 'Nhân viên'];
        $navItems[] = ['href' => 'news.php', 'key' => 'news', 'icon' => 'fa-newspaper', 'label' => 'Tin tức & Review', 'short' => 'Tin tức'];
    }

    $navItems[] = ['href' => 'profile.php', 'key' => 'profile', 'icon' => 'fa-id-card', 'label' => 'Hồ sơ cá nhân', 'short' => 'Hồ sơ'];
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($title) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: { extend: { colors: { gvn: { red: '#E30019', redHover: '#B80014' } } } }
        }
    </script>
    <style>body { font-family: 'Be Vietnam Pro', system-ui, sans-serif; }</style>
</head>
<body class="bg-gray-100 text-gray-800 min-h-screen flex">

    <!-- Sidebar -->
    <aside class="w-64 bg-[#111111] text-zinc-300 flex flex-col shrink-0 hidden md:flex">
        <div class="h-16 px-5 flex items-center justify-between gap-2 border-b border-zinc-800">
            <div class="flex items-center gap-2">
                <div class="bg-white text-gvn-red font-extrabold text-base px-2 py-0.5 rounded">
                    ĐOÀN<span class="text-black"> 6</span>
                </div>
                <span class="text-[10px] font-extrabold uppercase px-2 py-0.5 rounded <?= $roleBadgeClass ?>">
                    <?= e($roleBadgeText) ?>
                </span>
            </div>
        </div>

        <nav class="flex-1 p-3 space-y-1 text-xs font-semibold overflow-y-auto">
            <?php foreach ($navItems as $item): ?>
                <a href="<?= e($item['href']) ?>"
                   class="flex items-center gap-3 px-3.5 py-2.5 rounded-lg transition <?= $activeMenu === $item['key'] ? 'bg-gvn-red text-white' : 'hover:bg-zinc-800 hover:text-white' ?>">
                    <i class="fa-solid <?= e($item['icon']) ?> w-4"></i>
                    <span><?= e($item['label']) ?></span>
                </a>
            <?php endforeach; ?>

            <div class="pt-4 mt-4 border-t border-zinc-800 space-y-1">
                <a href="../index.php" class="flex items-center gap-3 px-3.5 py-2.5 rounded-lg hover:bg-zinc-800 hover:text-white transition">
                    <i class="fa-solid fa-store w-4"></i> Xem trang bán hàng
                </a>
                <a href="logout.php" class="flex items-center gap-3 px-3.5 py-2.5 rounded-lg text-red-400 hover:bg-zinc-800 hover:text-red-300 transition">
                    <i class="fa-solid fa-right-from-bracket w-4"></i> Đăng xuất
                </a>
            </div>
        </nav>
    </aside>

    <!-- Main Area -->
    <section class="flex-1 flex flex-col min-w-0">
        <header class="h-16 bg-white border-b border-gray-200 px-5 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <span class="font-extrabold text-base text-gray-900"><?= e($title) ?></span>
            </div>
            <div class="flex items-center gap-3 text-xs">
                <a href="profile.php" class="flex items-center gap-2 bg-gray-100 hover:bg-gray-200 px-3 py-1.5 rounded-full font-semibold transition">
                    <i class="fa-solid <?= $isAdminUser ? 'fa-user-shield text-gvn-red' : 'fa-user-tie text-amber-600' ?>"></i>
                    <span><?= e($adminUser['name'] ?? $roleBadgeText) ?></span>
                    <span class="px-2 py-0.5 rounded text-[10px] font-extrabold <?= $roleBadgeClass ?>">
                        <?= e($roleBadgeText) ?>
                    </span>
                </a>
                <a href="logout.php" class="md:hidden text-gvn-red font-bold">Thoát</a>
            </div>
        </header>

        <!-- Mobile Nav -->
        <div class="md:hidden bg-zinc-900 text-white px-3 py-2 flex gap-2 overflow-x-auto text-xs">
            <?php foreach ($navItems as $item): ?>
                <a href="<?= e($item['href']) ?>"
                   class="px-3 py-1 rounded whitespace-nowrap <?= $activeMenu === $item['key'] ? 'bg-gvn-red' : 'bg-zinc-800' ?>">
                    <?= e($item['short']) ?>
                </a>
            <?php endforeach; ?>
        </div>

        <main class="flex-1 p-4 sm:p-6">
<?php
}

function renderAdminFooter(): void
{
?>
        </main>
    </section>
</body>
</html>
<?php
}
