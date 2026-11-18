<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../data/store.php';

$categories = getCategories();
$pageTitle = $pageTitle ?? 'ĐOÀN 6 - Máy tính cao cấp, Laptop Gaming, Gear chính hãng';
$cartCount = getCartCount();
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
            <div class="max-w-[1280px] mx-auto px-3 h-16 flex items-center justify-between gap-3">
                <!-- Logo -->
                <a href="index.php" class="flex items-center gap-1.5 shrink-0">
                    <div class="bg-white text-gvn-red font-extrabold text-xl px-2.5 py-1 rounded-md tracking-tighter shadow-sm">
                        ĐOÀN<span class="text-black"> 6</span>
                    </div>
                </a>

                <!-- Search Bar -->
                <form action="collection.php" method="GET" class="flex-1 max-w-xl hidden sm:flex">
                    <div class="relative w-full">
                        <input type="text" name="q" value="<?= e($_GET['q'] ?? '') ?>" placeholder="Bạn cần tìm gì?"
                            class="w-full h-10 pl-4 pr-11 rounded-lg text-sm text-gray-800 bg-white focus:outline-none focus:ring-2 focus:ring-black/20">
                        <button type="submit" class="absolute right-0 top-0 h-10 w-10 flex items-center justify-center text-gray-600 hover:text-gvn-red">
                            <i class="fa-solid fa-magnifying-glass"></i>
                        </button>
                    </div>
                </form>

                <!-- Utility Links -->
                <div class="hidden lg:flex items-center gap-4 text-xs">
                    <a href="tel:00000000" class="flex items-center gap-2 hover:opacity-90">
                        <i class="fa-solid fa-headset text-lg"></i>
                        <div class="leading-tight">
                            <div class="opacity-85">Hotline</div>
                            <div class="font-bold">0000.0000</div>
                        </div>
                    </a>
                    <a href="#showroom" class="flex items-center gap-2 hover:opacity-90">
                        <i class="fa-solid fa-location-dot text-lg"></i>
                        <div class="leading-tight">
                            <div class="opacity-85">Hệ thống</div>
                            <div class="font-bold">Showroom</div>
                        </div>
                    </a>
                    <a href="#tra-cuu" class="flex items-center gap-2 hover:opacity-90">
                        <i class="fa-solid fa-file-invoice text-lg"></i>
                        <div class="leading-tight">
                            <div class="opacity-85">Tra cứu</div>
                            <div class="font-bold">đơn hàng</div>
                        </div>
                    </a>
                </div>

                <!-- Action Buttons -->
                <div class="flex items-center gap-2 shrink-0">
                    <a href="cart.php" class="relative bg-black hover:bg-neutral-800 text-white px-3 py-2 rounded-lg flex items-center gap-2 text-xs font-semibold transition">
                        <i class="fa-solid fa-cart-shopping text-sm"></i>
                        <span class="hidden md:inline">Giỏ hàng</span>
                        <span id="cart-count" class="bg-gvn-red text-white text-[10px] font-bold px-1.5 py-0.5 rounded-full"><?= (int)$cartCount ?></span>
                    </a>
                    <!-- Phần hiển thị nút Đăng nhập / Tài khoản trên Header -->
<div class="user-auth-section">
    <?php if (isset($_SESSION['user'])): ?>
        <!-- Nếu đã đăng nhập, hiển thị tên và nút Đăng xuất -->
        <div class="dropdown d-inline-block">
            <button class="btn text-white dropdown-toggle border-0 bg-transparent" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="fa fa-user me-1"></i> Chào, <?= htmlspecialchars($_SESSION['user']['fullname']) ?>
            </button>
            <ul class="dropdown-menu">
                <li><a class="dropdown-item text-danger" href="auths/logout.php"><i class="fa fa-sign-out-alt me-2"></i>Đăng xuất</a></li>
            </ul>
        </div>
    <?php else: ?>
        <!-- Nếu chưa đăng nhập, bấm vào sẽ trỏ thẳng sang trang login.php trong thư mục auths -->
        <a href="auths/login.php" class="btn text-white text-decoration-none">
            <i class="fa fa-user me-1"></i> Đăng nhập
        </a>
    <?php endif; ?>
</div>
                </div>
            </div>
        </div>

        <!-- Tier 2: Sub-Navigation Bar -->
        <div class="bg-white border-b border-gray-200">
            <div class="max-w-[1280px] mx-auto px-3 h-[42px] flex items-center gap-5 text-[13px] font-medium">
                <!-- DANH MỤC Mega Menu Trigger -->
                <div class="relative mega-menu-trigger h-full flex items-center">
                    <button class="bg-gvn-red hover:bg-gvn-redHover text-white px-3.5 py-1.5 rounded-md flex items-center gap-2 font-bold text-xs uppercase tracking-wide transition">
                        <i class="fa-solid fa-bars"></i>
                        <span>DANH MỤC</span>
                    </button>

                    <!-- Dropdown Mega Menu -->
                    <div class="mega-menu-dropdown hidden absolute left-0 top-full w-64 bg-white shadow-xl border border-gray-200 rounded-b-xl py-2 z-50">
                        <?php foreach ($categories as $cat): ?>
                            <a href="collection.php?cat=<?= e($cat['slug']) ?>" class="flex items-center justify-between px-4 py-2 text-xs text-gray-700 hover:bg-red-50 hover:text-gvn-red transition">
                                <span class="flex items-center gap-2.5">
                                    <i class="fa-solid <?= e($cat['icon']) ?> w-4 text-center text-gray-400"></i>
                                    <?= e($cat['name']) ?>
                                </span>
                                <i class="fa-solid fa-chevron-right text-[10px] text-gray-300"></i>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Quick Links -->
                <nav class="flex items-center gap-5 overflow-x-auto no-scrollbar whitespace-nowrap text-gray-800">
                    <a href="collection.php?cat=all" class="hover:text-gvn-red flex items-center gap-1.5 transition">
                        <i class="fa-solid fa-tags text-gvn-red"></i> Tất cả khuyến mãi
                    </a>
                    <a href="index.php#news-section" class="hover:text-gvn-red flex items-center gap-1.5 transition">
                        <i class="fa-regular fa-newspaper text-gray-500"></i> Tin tức - Review
                    </a>
                    <a href="#bao-hanh" class="hover:text-gvn-red flex items-center gap-1.5 transition">
                        <i class="fa-solid fa-shield-halved text-gray-500"></i> Tra Cứu Bảo Hành
                    </a>
                    <a href="#dich-vu" class="hover:text-gvn-red flex items-center gap-1.5 transition">
                        <i class="fa-solid fa-screwdriver-wrench text-gray-500"></i> Dịch vụ tận nhà
                    </a>
                    <a href="#thu-cu" class="hover:text-gvn-red flex items-center gap-1.5 transition">
                        <i class="fa-solid fa-rotate text-gray-500"></i> Thu cũ đổi mới
                    </a>
                    <a href="collection.php?cat=pc-gvn" class="hover:text-gvn-red flex items-center gap-1.5 font-semibold text-gvn-red transition">
                        <i class="fa-solid fa-desktop"></i> Build PC
                    </a>
                </nav>
            </div>
        </div>
    </header>

    <!-- MAIN CONTENT -->
    <main class="flex-1">
