<?php
$adminPageTitle = $adminPageTitle ?? 'Quản trị - ĐOÀN 6';
$activeMenu = $activeMenu ?? 'dashboard';
$adminUser = requireAdmin();

function renderAdminHeader(string $title, string $activeMenu, array $adminUser): void
{
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
    <aside class="w-60 bg-[#111111] text-zinc-300 flex flex-col shrink-0 hidden md:flex">
        <div class="h-16 px-5 flex items-center gap-2 border-b border-zinc-800">
            <div class="bg-white text-gvn-red font-extrabold text-base px-2 py-0.5 rounded">
                ĐOÀN<span class="text-black"> 6</span>
            </div>
            <span class="text-xs font-bold text-white uppercase tracking-wider">Admin</span>
        </div>

        <nav class="flex-1 p-3 space-y-1 text-xs font-semibold">
            <a href="index.php" class="flex items-center gap-3 px-3.5 py-2.5 rounded-lg transition <?= $activeMenu === 'dashboard' ? 'bg-gvn-red text-white' : 'hover:bg-zinc-800 hover:text-white' ?>">
                <i class="fa-solid fa-chart-line w-4"></i> Tổng quan
            </a>
            <a href="products.php" class="flex items-center gap-3 px-3.5 py-2.5 rounded-lg transition <?= $activeMenu === 'products' ? 'bg-gvn-red text-white' : 'hover:bg-zinc-800 hover:text-white' ?>">
                <i class="fa-solid fa-box w-4"></i> Quản lý Sản phẩm
            </a>
            <a href="categories.php" class="flex items-center gap-3 px-3.5 py-2.5 rounded-lg transition <?= $activeMenu === 'categories' ? 'bg-gvn-red text-white' : 'hover:bg-zinc-800 hover:text-white' ?>">
                <i class="fa-solid fa-layer-group w-4"></i> Quản lý Danh mục
            </a>
            <a href="orders.php" class="flex items-center gap-3 px-3.5 py-2.5 rounded-lg transition <?= $activeMenu === 'orders' ? 'bg-gvn-red text-white' : 'hover:bg-zinc-800 hover:text-white' ?>">
                <i class="fa-solid fa-file-invoice-dollar w-4"></i> Quản lý Đơn hàng
            </a>
            <div class="pt-4 mt-4 border-t border-zinc-800">
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
    <div class="flex-1 flex flex-col min-w-0">
        <header class="h-16 bg-white border-b border-gray-200 px-5 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <span class="font-extrabold text-base text-gray-900"><?= e($title) ?></span>
            </div>
            <div class="flex items-center gap-3 text-xs">
                <div class="flex items-center gap-2 bg-gray-100 px-3 py-1.5 rounded-full font-semibold">
                    <i class="fa-solid fa-user-shield text-gvn-red"></i>
                    <span><?= e($adminUser['name'] ?? 'Admin') ?></span>
                </div>
                <a href="logout.php" class="md:hidden text-gvn-red font-bold">Thoát</a>
            </div>
        </header>

        <!-- Mobile Nav -->
        <div class="md:hidden bg-zinc-900 text-white px-3 py-2 flex gap-2 overflow-x-auto text-xs">
            <a href="index.php" class="px-3 py-1 rounded <?= $activeMenu === 'dashboard' ? 'bg-gvn-red' : 'bg-zinc-800' ?>">Tổng quan</a>
            <a href="products.php" class="px-3 py-1 rounded <?= $activeMenu === 'products' ? 'bg-gvn-red' : 'bg-zinc-800' ?>">Sản phẩm</a>
            <a href="categories.php" class="px-3 py-1 rounded <?= $activeMenu === 'categories' ? 'bg-gvn-red' : 'bg-zinc-800' ?>">Danh mục</a>
            <a href="orders.php" class="px-3 py-1 rounded <?= $activeMenu === 'orders' ? 'bg-gvn-red' : 'bg-zinc-800' ?>">Đơn hàng</a>
        </div>

        <main class="flex-1 p-4 sm:p-6">
<?php
}

function renderAdminFooter(): void
{
?>
        </main>
    </div>
</body>
</html>
<?php
}
