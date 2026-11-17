<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'ĐOÀN 6 - Máy tính cao cấp, Laptop Gaming, Gear chính hãng')</title>
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
                <a href="{{ route('home') }}" class="flex items-center gap-1.5 shrink-0">
                    <div class="bg-white text-gvn-red font-extrabold text-xl px-2.5 py-1 rounded-md tracking-tighter shadow-sm">
                        ĐOÀN<span class="text-black"> 6</span>
                    </div>
                </a>

                <!-- Search Bar -->
                <form action="{{ route('collections.show', 'all') }}" method="GET" class="flex-1 max-w-xl hidden sm:flex">
                    <div class="relative w-full">
                        <input type="text" name="q" placeholder="Bạn cần tìm gì?"
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
                    <a href="{{ route('cart.index') }}" class="relative bg-black hover:bg-neutral-800 text-white px-3 py-2 rounded-lg flex items-center gap-2 text-xs font-semibold transition">
                        <i class="fa-solid fa-cart-shopping text-sm"></i>
                        <span class="hidden md:inline">Giỏ hàng</span>
                        <span id="cart-count" class="bg-gvn-red text-white text-[10px] font-bold px-1.5 py-0.5 rounded-full">2</span>
                    </a>
                    <button onclick="alert('Chức năng Đăng nhập đang được phát triển!')" class="bg-black hover:bg-neutral-800 text-white px-3 py-2 rounded-lg flex items-center gap-2 text-xs font-semibold transition">
                        <i class="fa-regular fa-user text-sm"></i>
                        <span class="hidden md:inline">Đăng nhập</span>
                    </button>
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
                        @foreach(($categories ?? []) as $cat)
                            <a href="{{ route('collections.show', $cat['slug']) }}" class="flex items-center justify-between px-4 py-2 text-xs text-gray-700 hover:bg-red-50 hover:text-gvn-red transition">
                                <span class="flex items-center gap-2.5">
                                    <i class="fa-solid {{ $cat['icon'] }} w-4 text-center text-gray-400"></i>
                                    {{ $cat['name'] }}
                                </span>
                                <i class="fa-solid fa-chevron-right text-[10px] text-gray-300"></i>
                            </a>
                        @endforeach
                    </div>
                </div>

                <!-- Quick Links -->
                <nav class="flex items-center gap-5 overflow-x-auto no-scrollbar whitespace-nowrap text-gray-800">
                    <a href="{{ route('collections.show', 'all') }}" class="hover:text-gvn-red flex items-center gap-1.5 transition">
                        <i class="fa-solid fa-tags text-gvn-red"></i> Tất cả khuyến mãi
                    </a>
                    <a href="#news-section" class="hover:text-gvn-red flex items-center gap-1.5 transition">
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
                    <a href="{{ route('collections.show', 'pc-gvn') }}" class="hover:text-gvn-red flex items-center gap-1.5 font-semibold text-gvn-red transition">
                        <i class="fa-solid fa-desktop"></i> Build PC
                    </a>
                </nav>
            </div>
        </div>
    </header>

    <!-- MAIN CONTENT -->
    <main class="flex-1">
        @yield('content')
    </main>

    <!-- FOOTER -->
    <footer class="bg-gvn-dark text-zinc-400 mt-10 pt-10 pb-6 text-xs">
        <div class="max-w-[1280px] mx-auto px-3">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-6 pb-8 border-b border-zinc-800">
                <!-- Col 1 -->
                <div>
                    <div class="inline-block bg-white text-gvn-red font-extrabold text-xl px-2.5 py-1 rounded-md tracking-tighter mb-4">
                        ĐOÀN<span class="text-black"> 6</span>
                    </div>
                    <h4 class="text-white font-bold text-xs uppercase tracking-wider mb-3">KẾT NỐI VỚI CHÚNG TÔI</h4>
                    <div class="flex items-center gap-2.5">
                        <a href="#" class="w-8 h-8 rounded-full bg-zinc-800 hover:bg-blue-600 text-white flex items-center justify-center transition"><i class="fa-brands fa-facebook-f"></i></a>
                        <a href="#" class="w-8 h-8 rounded-full bg-zinc-800 hover:bg-black text-white flex items-center justify-center transition"><i class="fa-brands fa-tiktok"></i></a>
                        <a href="#" class="w-8 h-8 rounded-full bg-zinc-800 hover:bg-red-600 text-white flex items-center justify-center transition"><i class="fa-brands fa-youtube"></i></a>
                        <a href="#" class="w-8 h-8 rounded-full bg-zinc-800 hover:bg-pink-600 text-white flex items-center justify-center transition"><i class="fa-brands fa-instagram"></i></a>
                    </div>
                </div>

                <!-- Col 2 -->
                <div>
                    <h4 class="text-white font-bold text-xs uppercase tracking-wider mb-3">TỔNG ĐÀI HỖ TRỢ (8:00 - 21:00)</h4>
                    <ul class="space-y-2">
                        <li>Tư vấn mua hàng: <a href="tel:00000000" class="text-blue-400 font-bold hover:underline">0000.0000</a></li>
                        <li>Hỗ trợ kỹ thuật, bảo hành: <a href="tel:00000000" class="text-blue-400 font-bold hover:underline">0000.0000</a></li>
                        <li>Góp ý, khiếu nại: <a href="tel:00000000" class="text-blue-400 font-bold hover:underline">0000.0000</a></li>
                        <li>Email: <a href="mailto:cskh@ĐOÀN 6.com" class="text-white hover:underline">cskh@ĐOÀN 6.com</a></li>
                    </ul>
                </div>

                <!-- Col 3 -->
                <div>
                    <h4 class="text-white font-bold text-xs uppercase tracking-wider mb-3">VỀ ĐOÀN 6 & CHÍNH SÁCH</h4>
                    <ul class="space-y-2">
                        <li><a href="#" class="hover:text-white transition">Giới thiệu về ĐOÀN 6</a></li>
                        <li><a href="#" class="hover:text-white transition">Tuyển dụng</a></li>
                        <li><a href="#" class="hover:text-white transition">Chính sách bảo hành</a></li>
                        <li><a href="#" class="hover:text-white transition">Chính sách vận chuyển</a></li>
                        <li><a href="#" class="hover:text-white transition">Chính sách bảo mật</a></li>
                    </ul>
                </div>

                <!-- Col 4 -->
                <div>
                    <h4 class="text-white font-bold text-xs uppercase tracking-wider mb-3">THÔNG TIN TIỆN ÍCH</h4>
                    <ul class="space-y-2">
                        <li><a href="#" class="hover:text-white transition">Hệ thống cửa hàng</a></li>
                        <li><a href="#" class="hover:text-white transition">Hướng dẫn mua hàng</a></li>
                        <li><a href="#" class="hover:text-white transition">Hướng dẫn thanh toán</a></li>
                        <li><a href="#" class="hover:text-white transition">Hướng dẫn trả góp 0%</a></li>
                        <li><a href="#" class="hover:text-white transition">Tra cứu địa chỉ bảo hành</a></li>
                    </ul>
                </div>

                <!-- Col 5 -->
                <div>
                    <h4 class="text-white font-bold text-xs uppercase tracking-wider mb-3">HỖ TRỢ THANH TOÁN</h4>
                    <div class="grid grid-cols-4 gap-1.5 mb-4">
                        <span class="bg-zinc-800 text-zinc-200 text-[10px] font-bold py-1.5 text-center rounded">VISA</span>
                        <span class="bg-zinc-800 text-zinc-200 text-[10px] font-bold py-1.5 text-center rounded">Master</span>
                        <span class="bg-zinc-800 text-zinc-200 text-[10px] font-bold py-1.5 text-center rounded">JCB</span>
                        <span class="bg-zinc-800 text-zinc-200 text-[10px] font-bold py-1.5 text-center rounded">ATM</span>
                        <span class="bg-zinc-800 text-zinc-200 text-[10px] font-bold py-1.5 text-center rounded">MoMo</span>
                        <span class="bg-zinc-800 text-zinc-200 text-[10px] font-bold py-1.5 text-center rounded">ZaloPay</span>
                        <span class="bg-zinc-800 text-zinc-200 text-[10px] font-bold py-1.5 text-center rounded">Tiền mặt</span>
                        <span class="bg-zinc-800 text-zinc-200 text-[10px] font-bold py-1.5 text-center rounded">Trả góp</span>
                    </div>
                    <h4 class="text-white font-bold text-xs uppercase tracking-wider mb-2">ĐƠN VỊ VẬN CHUYỂN</h4>
                    <div class="flex flex-wrap gap-1.5">
                        <span class="bg-zinc-800 text-zinc-300 text-[10px] px-2 py-1 rounded">GHN</span>
                        <span class="bg-zinc-800 text-zinc-300 text-[10px] px-2 py-1 rounded">Đoàn 6 Express</span>
                        <span class="bg-zinc-800 text-zinc-300 text-[10px] px-2 py-1 rounded">EMS</span>
                    </div>
                </div>
            </div>

            <!-- Bottom Copyright -->
            <div class="pt-6 flex flex-col md:flex-row items-center justify-between gap-4 text-[11px] text-zinc-500">
                <div>
                    <p>© 2026 Công ty TNHH Thương Mại ĐOÀN 6. MST: 0000000000.</p>
                    <p>Địa chỉ: Khu vực Demo, TP. Hồ Chí Minh.</p>
                </div>
                <div class="flex items-center gap-2 bg-blue-600/20 border border-blue-500/40 text-blue-400 px-3 py-1.5 rounded-md font-semibold">
                    <i class="fa-solid fa-circle-check"></i>
                    <span>ĐÃ THÔNG BÁO BỘ CÔNG THƯƠNG</span>
                </div>
            </div>
        </div>
    </footer>

    <!-- FLOATING WIDGETS -->
    <div class="fixed bottom-5 right-5 z-40 flex flex-col gap-2.5">
        <a href="tel:00000000" title="Hotline 0000.0000" class="w-11 h-11 rounded-full bg-gvn-red hover:bg-gvn-redHover text-white shadow-lg flex items-center justify-center transition">
            <i class="fa-solid fa-phone-volume"></i>
        </a>
        <a href="#" title="Chat Zalo" class="w-11 h-11 rounded-full bg-blue-600 hover:bg-blue-700 text-white shadow-lg flex items-center justify-center font-bold text-xs transition">
            Zalo
        </a>
        <button onclick="window.scrollTo({top: 0, behavior: 'smooth'})" title="Lên đầu trang" class="w-11 h-11 rounded-full bg-zinc-800 hover:bg-black text-white shadow-lg flex items-center justify-center transition">
            <i class="fa-solid fa-arrow-up"></i>
        </button>
    </div>

    @stack('scripts')
</body>
</html>
