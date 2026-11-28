<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../data/store.php';

$categories = getCategories();
$brands = getBrands();
$pageTitle = $pageTitle ?? 'ĐOÀN 6 - Máy tính cao cấp, Laptop Gaming, Gear chính hãng';
$cartCount = getCartCount();
$authUser = currentUser();
$canAccessAdmin = hasRole('admin', 'staff');
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        gvn: {
                            red: '#E30019',
                            redHover: '#B80014',
                            dark: '#111111',
                            bg: '#F4F4F4',
                        }
                    },
                    fontFamily: {
                        sans: ['"Be Vietnam Pro"', 'system-ui', 'sans-serif'],
                    }
                }
            }
        }
    </script>
    <style>
        body { font-family: 'Be Vietnam Pro', system-ui, sans-serif; background-color: #F4F4F4; color: #1E1E1E; }
        .no-scrollbar::-webkit-scrollbar { display: none; }
        .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
        .mega-menu-trigger:hover .mega-menu-dropdown { display: block; }
    </style>
</head>
<body class="min-h-screen flex flex-col">

    <!-- STICKY HEADER -->
    <header class="sticky top-0 z-50 shadow-sm">
        <!-- Tier 1: Main Red Header -->
        <div class="bg-gvn-red text-white">
            <div class="max-w-[1280px] mx-auto px-3 h-16 flex items-center justify-between gap-2 sm:gap-3">
                <!-- Logo -->
                <a href="index.php" class="flex items-center gap-1.5 shrink-0">
                    <div class="bg-white text-gvn-red font-extrabold text-base sm:text-xl px-2 sm:px-2.5 py-1 rounded-md tracking-tighter shadow-sm">
                        ĐOÀN<span class="text-black"> 6</span>
                    </div>
                </a>

                <!-- Search Bar + Live Dropdown -->
                <form action="collection.php" method="GET" id="header-search-form" class="flex-1 min-w-0 max-w-xl flex relative">
                    <div class="relative w-full">
                        <input type="text"
                            id="header-search-input"
                            name="q"
                            autocomplete="off"
                            value="<?= e($_GET['q'] ?? '') ?>"
                            placeholder="Tìm sản phẩm, linh kiện, VGA (vd: rtx)..."
                            aria-label="Tìm kiếm sản phẩm"
                            class="w-full h-10 pl-3.5 sm:pl-4 pr-10 rounded-lg text-xs sm:text-sm text-gray-800 bg-white focus:outline-none focus:ring-2 focus:ring-black/20">
                        <button type="button" onclick="this.form.submit()" aria-label="Tìm kiếm" class="absolute right-0 top-0 h-10 w-10 flex items-center justify-center text-gray-600 hover:text-gvn-red">
                            <i class="fa-solid fa-magnifying-glass"></i>
                        </button>
                        <div id="live-search-dropdown"
                             class="hidden absolute left-0 right-0 top-full mt-1.5 bg-white text-gray-900 rounded-xl shadow-2xl border border-gray-200 overflow-hidden z-[60] max-h-[420px] overflow-y-auto"></div>
                    </div>
                </form>

                <!-- Utility Links -->
                <div class="hidden lg:flex items-center gap-4 text-xs shrink-0">
                    <a href="tel:00000000" class="flex items-center gap-2 hover:opacity-90">
                        <i class="fa-solid fa-headset text-lg"></i>
                        <div class="leading-tight">
                            <div class="opacity-85">Hotline</div>
                            <div class="font-bold">0000.0000</div>
                        </div>
                    </a>
                    <a href="info.php?page=showroom" class="flex items-center gap-2 hover:opacity-90">
                        <i class="fa-solid fa-location-dot text-lg"></i>
                        <div class="leading-tight">
                            <div class="opacity-85">Hệ thống</div>
                            <div class="font-bold">Showroom</div>
                        </div>
                    </a>
                    <a href="orders.php" class="flex items-center gap-2 hover:opacity-90">
                        <i class="fa-solid fa-file-invoice text-lg"></i>
                        <div class="leading-tight">
                            <div class="opacity-85">Tra cứu</div>
                            <div class="font-bold">đơn hàng</div>
                        </div>
                    </a>
                </div>

                <!-- Action Buttons -->
                <div class="flex items-center gap-1.5 sm:gap-2 shrink-0">
                    <a href="cart.php" class="relative bg-black hover:bg-neutral-800 text-white px-2.5 sm:px-3 py-2 rounded-lg flex items-center gap-1.5 sm:gap-2 text-xs font-semibold transition">
                        <i class="fa-solid fa-cart-shopping text-sm"></i>
                        <span class="hidden md:inline">Giỏ hàng</span>
                        <span id="cart-count" class="bg-gvn-red text-white text-[10px] font-bold px-1.5 py-0.5 rounded-full"><?= (int)$cartCount ?></span>
                    </a>
                    <?php if ($authUser !== null): ?>
                        <div class="flex items-center gap-1.5">
                            <a href="profile.php" title="Hồ sơ cá nhân" class="bg-black hover:bg-neutral-800 text-white px-2.5 sm:px-3 py-2 rounded-lg flex items-center gap-2 text-xs font-semibold transition">
                                <i class="fa-regular fa-user text-sm"></i>
                                <span class="hidden md:inline max-w-[110px] truncate"><?= e($authUser['name']) ?></span>
                            </a>
                            <?php if ($canAccessAdmin): ?>
                                <a href="admin/index.php" title="Vào bảng điều khiển" class="bg-red-800 hover:bg-red-900 text-white px-2.5 py-2 rounded-lg text-xs font-semibold transition flex items-center gap-1">
                                    <i class="fa-solid fa-user-shield"></i>
                                    <span class="hidden md:inline"><?= $authUser['role'] === 'admin' ? 'Quản trị' : 'Nhân viên' ?></span>
                                </a>
                            <?php endif; ?>
                            <a href="logout.php" title="Đăng xuất" class="bg-black/70 hover:bg-black text-white px-2.5 py-2 rounded-lg text-xs transition">
                                <i class="fa-solid fa-right-from-bracket"></i>
                            </a>
                        </div>
                    <?php else: ?>
                        <a href="login.php" class="bg-black hover:bg-neutral-800 text-white px-2.5 sm:px-3 py-2 rounded-lg flex items-center gap-2 text-xs font-semibold transition">
                            <i class="fa-regular fa-user text-sm"></i>
                            <span class="hidden md:inline">Đăng nhập</span>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Tier 2: Sub-Navigation Bar -->
        <div class="bg-white border-b border-gray-200">
            <div class="max-w-[1280px] mx-auto px-3 h-[42px] flex items-center gap-4 sm:gap-5 text-[13px] font-medium">
                <!-- DANH MỤC Mega Menu Trigger -->
                <div class="relative mega-menu-trigger h-full flex items-center shrink-0">
                    <button type="button" class="bg-gvn-red hover:bg-gvn-redHover text-white px-3.5 py-1.5 rounded-md flex items-center gap-2 font-bold text-xs uppercase tracking-wide transition">
                        <i class="fa-solid fa-bars"></i>
                        <span>DANH MỤC</span>
                    </button>

                    <!-- Dropdown Mega Menu -->
                    <div class="mega-menu-dropdown hidden absolute left-0 top-full w-72 bg-white shadow-xl border border-gray-200 rounded-b-xl py-2 z-50 max-h-[80vh] overflow-y-auto">
                        <?php foreach ($categories as $cat): ?>
                            <a href="collection.php?cat=<?= e($cat['slug']) ?>" class="flex items-center justify-between px-4 py-2 text-xs text-gray-700 hover:bg-red-50 hover:text-gvn-red transition">
                                <span class="flex items-center gap-2.5">
                                    <i class="fa-solid <?= e($cat['icon']) ?> w-4 text-center text-gray-400"></i>
                                    <?= e($cat['name']) ?>
                                </span>
                                <i class="fa-solid fa-chevron-right text-[10px] text-gray-300"></i>
                            </a>
                        <?php endforeach; ?>
                        <?php if (!empty($brands)): ?>
                            <div class="border-t border-gray-100 mt-1.5 pt-2 px-4 pb-1">
                                <div class="text-[10px] font-extrabold uppercase text-gray-400 mb-1.5">Thương hiệu nổi bật</div>
                                <div class="flex flex-wrap gap-1.5">
                                    <?php foreach (array_slice($brands, 0, 10) as $b): ?>
                                        <a href="collection.php?brand_id=<?= (int)$b['id'] ?>"
                                           class="px-2 py-0.5 rounded bg-gray-100 hover:bg-gvn-red hover:text-white text-[11px] font-semibold text-gray-700 transition">
                                            <?= e($b['name']) ?>
                                        </a>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Quick Links -->
                <nav class="flex items-center gap-5 overflow-x-auto no-scrollbar whitespace-nowrap text-gray-800">
                    <a href="collection.php?cat=all" class="hover:text-gvn-red flex items-center gap-1.5 transition">
                        <i class="fa-solid fa-tags text-gvn-red"></i> Tất cả sản phẩm
                    </a>
                    <a href="news.php" class="hover:text-gvn-red flex items-center gap-1.5 transition">
                        <i class="fa-regular fa-newspaper text-gray-500"></i> Tin tức - Review
                    </a>
                    <a href="warranty.php" class="hover:text-gvn-red flex items-center gap-1.5 transition">
                        <i class="fa-solid fa-shield-halved text-gray-500"></i> Tra cứu bảo hành
                    </a>
                    <a href="trade-in.php" class="hover:text-gvn-red flex items-center gap-1.5 transition">
                        <i class="fa-solid fa-rotate text-gray-500"></i> Thu cũ đổi mới
                    </a>
                    <a href="orders.php" class="hover:text-gvn-red flex items-center gap-1.5 transition">
                        <i class="fa-solid fa-file-invoice text-gray-500"></i> Đơn hàng
                    </a>
                    <a href="pc-builder.php" class="hover:text-gvn-red flex items-center gap-1.5 font-semibold text-gvn-red transition">
                        <i class="fa-solid fa-desktop"></i> Tự Build PC
                    </a>
                </nav>
            </div>
        </div>
    </header>

    <script>
        (function () {
            const input = document.getElementById('header-search-input');
            const dropdown = document.getElementById('live-search-dropdown');
            const form = document.getElementById('header-search-form');
            if (!input || !dropdown || !form) return;

            let debounceTimer = null;
            let activeController = null;

            function escapeHtml(str) {
                return String(str ?? '')
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;')
                    .replace(/'/g, '&#039;');
            }

            function formatVnd(amount) {
                return new Intl.NumberFormat('vi-VN').format(Math.round(Number(amount || 0))) + 'đ';
            }

            function hideDropdown() {
                dropdown.classList.add('hidden');
            }

            function renderResults(items, query) {
                if (!items || items.length === 0) {
                    dropdown.innerHTML =
                        '<div class="p-4 text-center text-xs text-gray-500">' +
                        'Không tìm thấy sản phẩm nào khớp với "<strong>' + escapeHtml(query) + '</strong>".' +
                        '<div class="mt-2"><a href="collection.php?q=' + encodeURIComponent(query) + '" class="text-gvn-red font-bold hover:underline">Tìm trong toàn bộ cửa hàng →</a></div>' +
                        '</div>';
                    dropdown.classList.remove('hidden');
                    return;
                }

                const rowsHtml = items.map(function (item) {
                    const stock = Number(item.stock || 0);
                    const stockHtml = stock > 0
                        ? '<span class="text-emerald-700 font-semibold">Còn ' + stock + ' sp</span>'
                        : '<span class="text-red-600 font-semibold">Hết hàng</span>';
                    const oldPriceHtml = (Number(item.old_price || 0) > Number(item.price || 0))
                        ? '<span class="text-[11px] text-gray-400 line-through ml-1.5">' + formatVnd(item.old_price) + '</span>'
                        : '';

                    return '<a href="product.php?slug=' + encodeURIComponent(item.slug) + '" class="flex items-center gap-3 px-3.5 py-2.5 hover:bg-red-50/60 border-b border-gray-100 last:border-b-0 transition">' +
                        '<img src="' + escapeHtml(item.image) + '" alt="' + escapeHtml(item.name) + '" class="w-12 h-12 object-cover rounded-lg border border-gray-200 bg-gray-50 shrink-0">' +
                        '<div class="flex-1 min-w-0">' +
                            '<div class="text-xs font-bold text-gray-900 line-clamp-1">' + escapeHtml(item.name) + '</div>' +
                            '<div class="flex items-center gap-2 mt-0.5 text-[11px]">' +
                                '<span class="bg-gray-100 text-gray-600 font-semibold px-1.5 py-0.5 rounded">' + escapeHtml(item.brand) + '</span>' +
                                stockHtml +
                            '</div>' +
                        '</div>' +
                        '<div class="text-right shrink-0">' +
                            '<div class="text-xs font-extrabold text-gvn-red">' + formatVnd(item.price) + '</div>' +
                            oldPriceHtml +
                        '</div>' +
                    '</a>';
                }).join('');

                const footerHtml = '<a href="collection.php?q=' + encodeURIComponent(query) + '" class="block text-center py-2.5 bg-gray-50 hover:bg-gray-100 text-xs font-bold text-gvn-red border-t border-gray-100 transition">' +
                    'Xem tất cả kết quả cho "' + escapeHtml(query) + '" <i class="fa-solid fa-arrow-right ml-1"></i>' +
                    '</a>';

                dropdown.innerHTML = rowsHtml + footerHtml;
                dropdown.classList.remove('hidden');
            }

            async function performSearch(query) {
                if (activeController) {
                    activeController.abort();
                }
                activeController = new AbortController();

                try {
                    const res = await fetch('api/search.php?q=' + encodeURIComponent(query), {
                        signal: activeController.signal,
                        headers: { 'Accept': 'application/json' }
                    });
                    if (!res.ok) return;
                    const data = await res.json();
                    if (data && data.ok && input.value.trim() === query) {
                        renderResults(data.items || [], query);
                    }
                } catch (err) {
                    if (err && err.name === 'AbortError') return;
                }
            }

            input.addEventListener('input', function () {
                const q = input.value.trim();
                clearTimeout(debounceTimer);
                if (q.length < 1) {
                    hideDropdown();
                    return;
                }
                debounceTimer = setTimeout(function () {
                    performSearch(q);
                }, 180);
            });

            input.addEventListener('focus', function () {
                const q = input.value.trim();
                if (q.length >= 1) {
                    performSearch(q);
                }
            });

            input.addEventListener('keydown', function (e) {
                if (e.key === 'Escape') {
                    hideDropdown();
                }
            });

            document.addEventListener('click', function (e) {
                if (!form.contains(e.target)) {
                    hideDropdown();
                }
            });
        })();
    </script>

    <!-- MAIN CONTENT -->
    <main class="flex-1">
