@extends('layouts.app')

@section('title', 'ĐOÀN 6 - Máy tính cao cấp, Laptop Gaming, Gear chính hãng')

@section('content')
<div class="max-w-[1280px] mx-auto px-3 py-4 space-y-6">

    <!-- 1. HERO SECTION: SIDEBAR + CAROUSEL + SIDE BANNERS -->
    <section class="grid grid-cols-1 lg:grid-cols-12 gap-3">
        <!-- Left Category Sidebar (Desktop) -->
        <aside class="hidden lg:block lg:col-span-2 bg-white rounded-xl border border-gray-200 py-1.5 shadow-sm">
            @foreach(array_slice($categories, 0, 12) as $cat)
                <a href="{{ route('collections.show', $cat['slug']) }}"
                   class="flex items-center justify-between px-3 py-1.5 text-[12px] font-medium text-gray-700 hover:bg-red-50 hover:text-gvn-red transition">
                    <span class="flex items-center gap-2 truncate">
                        <i class="fa-solid {{ $cat['icon'] }} w-4 text-center text-gray-400"></i>
                        <span class="truncate">{{ $cat['name'] }}</span>
                    </span>
                    <i class="fa-solid fa-chevron-right text-[9px] text-gray-300"></i>
                </a>
            @endforeach
        </aside>

        <!-- Center Hero Carousel -->
        <div class="lg:col-span-7 relative rounded-xl overflow-hidden shadow-sm bg-black group">
            <div id="hero-slides" class="flex transition-transform duration-500 ease-out h-[220px] sm:h-[320px] lg:h-[360px]">
                <img src="{{ asset('images/banners/hero1.jpg') }}" alt="ĐOÀN 6 Banner 1" class="w-full h-full object-cover shrink-0">
                <img src="{{ asset('images/banners/hero2.jpg') }}" alt="ĐOÀN 6 Banner 2" class="w-full h-full object-cover shrink-0">
                <img src="{{ asset('images/banners/hero3.jpg') }}" alt="ĐOÀN 6 Banner 3" class="w-full h-full object-cover shrink-0">
            </div>

            <!-- Prev/Next Buttons -->
            <button onclick="moveSlide(-1)" class="absolute left-2 top-1/2 -translate-y-1/2 w-9 h-9 rounded-full bg-black/50 hover:bg-gvn-red text-white flex items-center justify-center opacity-0 group-hover:opacity-100 transition">
                <i class="fa-solid fa-chevron-left"></i>
            </button>
            <button onclick="moveSlide(1)" class="absolute right-2 top-1/2 -translate-y-1/2 w-9 h-9 rounded-full bg-black/50 hover:bg-gvn-red text-white flex items-center justify-center opacity-0 group-hover:opacity-100 transition">
                <i class="fa-solid fa-chevron-right"></i>
            </button>

            <!-- Dots -->
            <div class="absolute bottom-3 left-1/2 -translate-x-1/2 flex items-center gap-2">
                <button onclick="goToSlide(0)" class="hero-dot w-2.5 h-2.5 rounded-full bg-gvn-red transition"></button>
                <button onclick="goToSlide(1)" class="hero-dot w-2.5 h-2.5 rounded-full bg-white/60 transition"></button>
                <button onclick="goToSlide(2)" class="hero-dot w-2.5 h-2.5 rounded-full bg-white/60 transition"></button>
            </div>
        </div>

        <!-- Right Side Promo Banners -->
        <div class="lg:col-span-3 grid grid-cols-2 lg:grid-cols-1 gap-3">
            <a href="{{ route('collections.show', 'man-hinh') }}" class="block rounded-xl overflow-hidden shadow-sm hover:opacity-95 transition h-[120px] lg:h-[174px]">
                <img src="{{ asset('images/banners/side1.jpg') }}" alt="Khuyến mãi Màn hình" class="w-full h-full object-cover">
            </a>
            <a href="{{ route('collections.show', 'laptop-gaming') }}" class="block rounded-xl overflow-hidden shadow-sm hover:opacity-95 transition h-[120px] lg:h-[174px]">
                <img src="{{ asset('images/banners/side2.jpg') }}" alt="Laptop Gaming Acer" class="w-full h-full object-cover">
            </a>
        </div>
    </section>

    <!-- 2. QUICK CATEGORY SHORTCUTS BAR -->
    <section class="bg-white rounded-xl border border-gray-200 p-3 shadow-sm">
        <div class="flex items-center gap-3 overflow-x-auto no-scrollbar">
            @php
                $shortcuts = [
                    ['name' => 'Sale Sốc 11.11', 'icon' => 'fa-fire', 'color' => 'text-gvn-red bg-red-50', 'slug' => 'all'],
                    ['name' => 'Laptop', 'icon' => 'fa-laptop', 'color' => 'text-blue-600 bg-blue-50', 'slug' => 'laptop'],
                    ['name' => 'PC ĐOÀN 6', 'icon' => 'fa-desktop', 'color' => 'text-purple-600 bg-purple-50', 'slug' => 'pc-gvn'],
                    ['name' => 'Màn hình', 'icon' => 'fa-tv', 'color' => 'text-emerald-600 bg-emerald-50', 'slug' => 'man-hinh'],
                    ['name' => 'VGA - Card', 'icon' => 'fa-microchip', 'color' => 'text-orange-600 bg-orange-50', 'slug' => 'linh-kien'],
                    ['name' => 'Bàn phím', 'icon' => 'fa-keyboard', 'color' => 'text-pink-600 bg-pink-50', 'slug' => 'ban-phim-chuot'],
                    ['name' => 'Chuột', 'icon' => 'fa-computer-mouse', 'color' => 'text-cyan-600 bg-cyan-50', 'slug' => 'ban-phim-chuot'],
                    ['name' => 'Tai nghe', 'icon' => 'fa-headphones', 'color' => 'text-indigo-600 bg-indigo-50', 'slug' => 'tai-nghe-loa'],
                    ['name' => 'Ghế Gaming', 'icon' => 'fa-chair', 'color' => 'text-amber-600 bg-amber-50', 'slug' => 'ghe-ban-gaming'],
                ];
            @endphp
            @foreach($shortcuts as $sc)
                <a href="{{ route('collections.show', $sc['slug']) }}" class="flex flex-col items-center gap-1.5 min-w-[84px] p-2 rounded-lg hover:bg-gray-50 transition group">
                    <div class="w-11 h-11 rounded-xl {{ $sc['color'] }} flex items-center justify-center text-lg group-hover:scale-110 transition">
                        <i class="fa-solid {{ $sc['icon'] }}"></i>
                    </div>
                    <span class="text-[11px] font-semibold text-gray-700 text-center whitespace-nowrap">{{ $sc['name'] }}</span>
                </a>
            @endforeach
        </div>
    </section>

    <!-- 3. FLASH SALE / HOT DEAL -->
    <section class="bg-gradient-to-r from-[#E30019] to-[#FF4D4D] rounded-2xl p-4 shadow-md">
        <div class="flex flex-wrap items-center justify-between gap-3 mb-4 text-white">
            <div class="flex items-center gap-3">
                <h2 class="text-xl sm:text-2xl font-extrabold uppercase tracking-tight flex items-center gap-2">
                    <i class="fa-solid fa-bolt text-yellow-300 animate-pulse"></i>
                    FLASH SALE GIÁ SỐC
                </h2>
                <div class="flex items-center gap-1.5 text-xs font-bold bg-black/30 px-3 py-1.5 rounded-lg">
                    <span>Kết thúc sau:</span>
                    <span id="countdown-h" class="bg-black text-white px-1.5 py-0.5 rounded">02</span>:
                    <span id="countdown-m" class="bg-black text-white px-1.5 py-0.5 rounded">15</span>:
                    <span id="countdown-s" class="bg-black text-white px-1.5 py-0.5 rounded">40</span>
                </div>
            </div>
            <a href="{{ route('collections.show', 'all') }}" class="text-xs font-bold bg-white text-gvn-red px-3.5 py-1.5 rounded-full hover:bg-yellow-300 hover:text-black transition">
                Săn Deal Ngay <i class="fa-solid fa-chevron-right ml-1"></i>
            </a>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-3">
            @foreach($flashSales as $product)
                @include('components.product-card', ['product' => $product, 'showSoldBar' => true])
            @endforeach
        </div>
    </section>

    <!-- 4. PC BÁN CHẠY -->
    <section class="bg-white rounded-2xl border border-gray-200 p-4 shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-3 mb-4 pb-2 border-b border-gray-100">
            <div class="flex items-center gap-3">
                <h2 class="text-lg sm:text-xl font-extrabold text-gray-900 uppercase">PC bán chạy</h2>
                <span class="hidden sm:inline-block bg-red-50 text-gvn-red text-xs font-bold px-2.5 py-1 rounded-full">
                    <i class="fa-solid fa-gift mr-1"></i> Tặng Màn hình 240Hz
                </span>
            </div>
            <div class="flex items-center gap-2 overflow-x-auto no-scrollbar">
                @foreach(['Tất cả', 'PC I3', 'PC I5', 'PC I7', 'PC R5'] as $idx => $tab)
                    <button onclick="filterSection('pc-grid', '{{ $tab }}', this)"
                        class="tab-btn px-3 py-1 rounded-full text-xs font-semibold border transition {{ $idx === 0 ? 'bg-gvn-red text-white border-gvn-red' : 'bg-gray-50 text-gray-700 border-gray-200 hover:border-gvn-red' }}">
                        {{ $tab }}
                    </button>
                @endforeach
                <a href="{{ route('collections.show', 'pc-gvn') }}" class="text-xs font-bold text-gvn-red hover:underline ml-2 whitespace-nowrap">
                    Xem tất cả <i class="fa-solid fa-chevron-right"></i>
                </a>
            </div>
        </div>

        <div id="pc-grid" class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3">
            @foreach($pcs as $product)
                <div class="filter-item" data-brand="{{ $product['brand'] }}">
                    @include('components.product-card', ['product' => $product])
                </div>
            @endforeach
        </div>
    </section>

    <!-- 5. LAPTOP GAMING BÁN CHẠY -->
    <section class="bg-white rounded-2xl border border-gray-200 p-4 shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-3 mb-4 pb-2 border-b border-gray-100">
            <div class="flex items-center gap-3">
                <h2 class="text-lg sm:text-xl font-extrabold text-gray-900 uppercase">Laptop Gaming bán chạy</h2>
                <span class="hidden sm:inline-block bg-blue-50 text-blue-700 text-xs font-bold px-2.5 py-1 rounded-full">
                    Tặng Combo Gear đến 16 triệu
                </span>
            </div>
            <div class="flex items-center gap-2 overflow-x-auto no-scrollbar">
                @foreach(['Tất cả', 'ACER', 'ASUS', 'MSI', 'LENOVO'] as $idx => $tab)
                    <button onclick="filterSection('laptop-gaming-grid', '{{ $tab }}', this)"
                        class="tab-btn px-3 py-1 rounded-full text-xs font-semibold border transition {{ $idx === 0 ? 'bg-gvn-red text-white border-gvn-red' : 'bg-gray-50 text-gray-700 border-gray-200 hover:border-gvn-red' }}">
                        {{ $tab }}
                    </button>
                @endforeach
                <a href="{{ route('collections.show', 'laptop-gaming') }}" class="text-xs font-bold text-gvn-red hover:underline ml-2 whitespace-nowrap">
                    Xem tất cả <i class="fa-solid fa-chevron-right"></i>
                </a>
            </div>
        </div>

        <div id="laptop-gaming-grid" class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3">
            @foreach($laptopGamings as $product)
                <div class="filter-item" data-brand="{{ $product['brand'] }}">
                    @include('components.product-card', ['product' => $product])
                </div>
            @endforeach
        </div>
    </section>

    <!-- 6. MÀN HÌNH & GAMING GEAR -->
    <section class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        <div class="bg-white rounded-2xl border border-gray-200 p-4 shadow-sm">
            <div class="flex items-center justify-between mb-3 pb-2 border-b border-gray-100">
                <h2 class="text-lg font-extrabold text-gray-900 uppercase">Màn hình bán chạy</h2>
                <a href="{{ route('collections.show', 'man-hinh') }}" class="text-xs font-bold text-gvn-red hover:underline">Xem tất cả ›</a>
            </div>
            <div class="grid grid-cols-2 gap-3">
                @foreach($monitors as $product)
                    @include('components.product-card', ['product' => $product])
                @endforeach
            </div>
        </div>

        <div class="bg-white rounded-2xl border border-gray-200 p-4 shadow-sm">
            <div class="flex items-center justify-between mb-3 pb-2 border-b border-gray-100">
                <h2 class="text-lg font-extrabold text-gray-900 uppercase">Bàn phím, Chuột & Gear</h2>
                <a href="{{ route('collections.show', 'ban-phim-chuot') }}" class="text-xs font-bold text-gvn-red hover:underline">Xem tất cả ›</a>
            </div>
            <div class="grid grid-cols-2 gap-3">
                @foreach(array_slice($gears, 0, 2) as $product)
                    @include('components.product-card', ['product' => $product])
                @endforeach
            </div>
        </div>
    </section>

    <!-- 7. TIN TỨC & REVIEW CÔNG NGHỆ -->
    <section id="news-section" class="bg-white rounded-2xl border border-gray-200 p-4 shadow-sm">
        <div class="flex items-center justify-between mb-4 pb-2 border-b border-gray-100">
            <h2 class="text-lg sm:text-xl font-extrabold text-gray-900 uppercase">Cập nhật Tin Tức Công Nghệ</h2>
            <a href="#" class="text-xs font-bold text-gvn-red hover:underline">Xem tất cả <i class="fa-solid fa-chevron-right"></i></a>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-4">
            <!-- Featured News -->
            @if(!empty($news[0]))
                <a href="#" class="lg:col-span-6 group block">
                    <div class="rounded-xl overflow-hidden aspect-video mb-3">
                        <img src="{{ asset($news[0]['image']) }}" alt="{{ $news[0]['title'] }}" class="w-full h-full object-cover group-hover:scale-105 transition duration-300">
                    </div>
                    <h3 class="text-base font-bold text-gray-900 group-hover:text-gvn-red transition mb-1.5">{{ $news[0]['title'] }}</h3>
                    <p class="text-xs text-gray-500 line-clamp-2">{{ $news[0]['summary'] }}</p>
                </a>
            @endif

            <!-- Other News -->
            <div class="lg:col-span-6 flex flex-col gap-3">
                @foreach(array_slice($news, 1) as $item)
                    <a href="#" class="flex gap-3 group items-start">
                        <div class="w-32 sm:w-40 aspect-video rounded-lg overflow-hidden shrink-0">
                            <img src="{{ asset($item['image']) }}" alt="{{ $item['title'] }}" class="w-full h-full object-cover group-hover:scale-105 transition duration-300">
                        </div>
                        <div>
                            <h4 class="text-sm font-bold text-gray-900 group-hover:text-gvn-red line-clamp-2 transition mb-1">{{ $item['title'] }}</h4>
                            <span class="text-[11px] text-gray-400"><i class="fa-regular fa-clock mr-1"></i>{{ $item['date'] }}</span>
                        </div>
                    </a>
                @endforeach
            </div>
        </div>
    </section>

</div>
@endsection

@push('scripts')
<script>
    // Hero Carousel
    let currentSlide = 0;
    const totalSlides = 3;
    function updateSlide() {
        document.getElementById('hero-slides').style.transform = `translateX(-${currentSlide * 100}%)`;
        document.querySelectorAll('.hero-dot').forEach((dot, idx) => {
            dot.classList.toggle('bg-gvn-red', idx === currentSlide);
            dot.classList.toggle('bg-white/60', idx !== currentSlide);
        });
    }
    function moveSlide(dir) {
        currentSlide = (currentSlide + dir + totalSlides) % totalSlides;
        updateSlide();
    }
    function goToSlide(idx) {
        currentSlide = idx;
        updateSlide();
    }
    setInterval(() => moveSlide(1), 5000);

    // Flash Sale Countdown
    let totalSeconds = 2 * 3600 + 15 * 60 + 40;
    setInterval(() => {
        if (totalSeconds <= 0) return;
        totalSeconds--;
        const h = String(Math.floor(totalSeconds / 3600)).padStart(2, '0');
        const m = String(Math.floor((totalSeconds % 3600) / 60)).padStart(2, '0');
        const s = String(totalSeconds % 60).padStart(2, '0');
        document.getElementById('countdown-h').textContent = h;
        document.getElementById('countdown-m').textContent = m;
        document.getElementById('countdown-s').textContent = s;
    }, 1000);

    // Tab Filtering
    function filterSection(gridId, brand, btn) {
        const container = btn.parentElement;
        container.querySelectorAll('.tab-btn').forEach(b => {
            b.classList.remove('bg-gvn-red', 'text-white', 'border-gvn-red');
            b.classList.add('bg-gray-50', 'text-gray-700', 'border-gray-200');
        });
        btn.classList.remove('bg-gray-50', 'text-gray-700', 'border-gray-200');
        btn.classList.add('bg-gvn-red', 'text-white', 'border-gvn-red');

        document.querySelectorAll(`#${gridId} .filter-item`).forEach(item => {
            if (brand === 'Tất cả' || item.dataset.brand === brand) {
                item.style.display = 'block';
            } else {
                item.style.display = 'none';
            }
        });
    }
</script>
@endpush
