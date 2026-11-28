<?php
$pageTitle = 'ĐOÀN 6 - Máy tính cao cấp, Laptop Gaming, Gear chính hãng';
require_once __DIR__ . '/includes/header.php';

$allProducts = getAllProducts();
$brands = getBrands();
$brandCounts = [];
foreach ($allProducts as $p) {
    $bid = (int)($p['brand_id'] ?? 0);
    if ($bid > 0) {
        $brandCounts[$bid] = ($brandCounts[$bid] ?? 0) + 1;
    }
}

$flashSales = array_values(array_filter($allProducts, fn($p) => !empty($p['flash_sale'])));
$pcs = array_values(array_filter($allProducts, fn($p) => $p['category'] === 'pc-gvn'));
$laptopGamings = array_values(array_filter($allProducts, fn($p) => $p['category'] === 'laptop-gaming'));
$monitors = array_values(array_filter($allProducts, fn($p) => $p['category'] === 'man-hinh'));
$gears = array_values(array_filter($allProducts, fn($p) => in_array($p['category'], ['ban-phim-chuot', 'tai-nghe-loa'], true)));
$news = getNews();
?>

<div class="max-w-[1280px] mx-auto px-3 py-4 space-y-6">

    <!-- 1. HERO SECTION: SIDEBAR + CAROUSEL + SIDE BANNERS -->
    <section class="grid grid-cols-1 lg:grid-cols-12 gap-3">
        <!-- Left Category Sidebar (Desktop) -->
        <aside class="hidden lg:block lg:col-span-2 bg-white rounded-xl border border-gray-200 py-1.5 shadow-sm">
            <?php foreach (array_slice($categories, 0, 12) as $cat): ?>
                <a href="collection.php?cat=<?= e($cat['slug']) ?>"
                   class="flex items-center justify-between px-3 py-1.5 text-[12px] font-medium text-gray-700 hover:bg-red-50 hover:text-gvn-red transition">
                    <span class="flex items-center gap-2 truncate">
                        <i class="fa-solid <?= e($cat['icon']) ?> w-4 text-center text-gray-400"></i>
                        <span class="truncate"><?= e($cat['name']) ?></span>
                    </span>
                    <i class="fa-solid fa-chevron-right text-[9px] text-gray-300"></i>
                </a>
            <?php endforeach; ?>
        </aside>

        <!-- Center Hero Carousel -->
        <div class="lg:col-span-7 relative rounded-xl overflow-hidden shadow-sm bg-black group">
            <div id="hero-slides" class="flex transition-transform duration-500 ease-out h-[220px] sm:h-[320px] lg:h-[360px]">
                <img src="assets/images/banners/hero1.jpg" alt="Đoàn 6 Banner 1" class="w-full h-full object-cover shrink-0">
                <img src="assets/images/banners/hero2.jpg" alt="Đoàn 6 Banner 2" class="w-full h-full object-cover shrink-0">
                <img src="assets/images/banners/hero3.jpg" alt="Đoàn 6 Banner 3" class="w-full h-full object-cover shrink-0">
            </div>

            <button onclick="moveSlide(-1)" aria-label="Slide trước" class="absolute left-2 top-1/2 -translate-y-1/2 w-9 h-9 rounded-full bg-black/50 hover:bg-gvn-red text-white flex items-center justify-center opacity-0 group-hover:opacity-100 transition">
                <i class="fa-solid fa-chevron-left"></i>
            </button>
            <button onclick="moveSlide(1)" aria-label="Slide tiếp theo" class="absolute right-2 top-1/2 -translate-y-1/2 w-9 h-9 rounded-full bg-black/50 hover:bg-gvn-red text-white flex items-center justify-center opacity-0 group-hover:opacity-100 transition">
                <i class="fa-solid fa-chevron-right"></i>
            </button>

            <div class="absolute bottom-3 left-1/2 -translate-x-1/2 flex items-center gap-2">
                <button onclick="goToSlide(0)" aria-label="Slide 1" class="hero-dot w-2.5 h-2.5 rounded-full bg-gvn-red transition"></button>
                <button onclick="goToSlide(1)" aria-label="Slide 2" class="hero-dot w-2.5 h-2.5 rounded-full bg-white/60 transition"></button>
                <button onclick="goToSlide(2)" aria-label="Slide 3" class="hero-dot w-2.5 h-2.5 rounded-full bg-white/60 transition"></button>
            </div>
        </div>

        <!-- Right Side Promo Banners -->
        <div class="lg:col-span-3 grid grid-cols-2 lg:grid-cols-1 gap-3">
            <a href="collection.php?cat=man-hinh" class="block rounded-xl overflow-hidden shadow-sm hover:opacity-95 transition h-[120px] lg:h-[174px]">
                <img src="assets/images/banners/side1.jpg" alt="Khuyến mãi Màn hình" class="w-full h-full object-cover">
            </a>
            <a href="collection.php?cat=laptop-gaming" class="block rounded-xl overflow-hidden shadow-sm hover:opacity-95 transition h-[120px] lg:h-[174px]">
                <img src="assets/images/banners/side2.jpg" alt="Laptop Gaming Acer" class="w-full h-full object-cover">
            </a>
        </div>
    </section>

    <!-- 2. QUICK CATEGORY SHORTCUTS BAR -->
    <section class="bg-white rounded-xl border border-gray-200 p-3 shadow-sm">
        <div class="flex items-center gap-3 overflow-x-auto no-scrollbar">
            <?php
            $shortcuts = [
                ['name' => 'Sale Sốc 11.11', 'icon' => 'fa-fire', 'color' => 'text-gvn-red bg-red-50', 'slug' => 'all'],
                ['name' => 'Laptop', 'icon' => 'fa-laptop', 'color' => 'text-blue-600 bg-blue-50', 'slug' => 'laptop'],
                ['name' => 'PC ĐOÀN 6', 'icon' => 'fa-desktop', 'color' => 'text-purple-600 bg-purple-50', 'slug' => 'pc-gvn'],
                ['name' => 'Màn hình', 'icon' => 'fa-tv', 'color' => 'text-emerald-600 bg-emerald-50', 'slug' => 'man-hinh'],
                ['name' => 'Linh kiện PC', 'icon' => 'fa-microchip', 'color' => 'text-orange-600 bg-orange-50', 'slug' => 'linh-kien'],
                ['name' => 'Bàn phím', 'icon' => 'fa-keyboard', 'color' => 'text-pink-600 bg-pink-50', 'slug' => 'ban-phim-chuot'],
                ['name' => 'Chuột', 'icon' => 'fa-computer-mouse', 'color' => 'text-cyan-600 bg-cyan-50', 'slug' => 'ban-phim-chuot'],
                ['name' => 'Tai nghe', 'icon' => 'fa-headphones', 'color' => 'text-indigo-600 bg-indigo-50', 'slug' => 'tai-nghe-loa'],
                ['name' => 'Ghế Gaming', 'icon' => 'fa-chair', 'color' => 'text-amber-600 bg-amber-50', 'slug' => 'ghe-ban-gaming'],
            ];
            foreach ($shortcuts as $sc):
            ?>
                <a href="collection.php?cat=<?= e($sc['slug']) ?>" class="flex flex-col items-center gap-1.5 min-w-[84px] p-2 rounded-lg hover:bg-gray-50 transition group">
                    <div class="w-11 h-11 rounded-xl <?= e($sc['color']) ?> flex items-center justify-center text-lg group-hover:scale-110 transition">
                        <i class="fa-solid <?= e($sc['icon']) ?>"></i>
                    </div>
                    <span class="text-[11px] font-semibold text-gray-700 text-center whitespace-nowrap"><?= e($sc['name']) ?></span>
                </a>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- 2.5 BRAND / MANUFACTURER FILTER STRIP -->
    <?php if (!empty($brands)): ?>
        <section class="bg-white rounded-xl border border-gray-200 p-4 shadow-sm">
            <div class="flex items-center justify-between mb-3">
                <h2 class="text-sm font-extrabold text-gray-900 uppercase flex items-center gap-2">
                    <i class="fa-solid fa-award text-gvn-red"></i>
                    Thương hiệu / Hãng sản xuất chính hãng
                </h2>
                <a href="collection.php?cat=all" class="text-xs font-bold text-gvn-red hover:underline">Xem tất cả ›</a>
            </div>
            <div class="flex items-center gap-2 overflow-x-auto no-scrollbar pb-1">
                <?php foreach ($brands as $b): ?>
                    <?php $cnt = (int)($brandCounts[(int)$b['id']] ?? 0); ?>
                    <a href="collection.php?brand_id=<?= (int)$b['id'] ?>"
                       class="shrink-0 inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-gray-200 bg-gray-50 hover:bg-gvn-red hover:text-white hover:border-gvn-red text-xs font-bold text-gray-800 transition">
                        <span><?= e($b['name']) ?></span>
                        <?php if ($cnt > 0): ?>
                            <span class="text-[10px] font-semibold px-1.5 py-0.5 rounded bg-white/80 text-gray-600"><?= $cnt ?></span>
                        <?php endif; ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>

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
            <a href="collection.php?cat=all" class="text-xs font-bold bg-white text-gvn-red px-3.5 py-1.5 rounded-full hover:bg-yellow-300 hover:text-black transition">
                Săn Deal Ngay <i class="fa-solid fa-chevron-right ml-1"></i>
            </a>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-3">
            <?php
            $showSoldBar = true;
            foreach ($flashSales as $product) {
                include __DIR__ . '/includes/product-card.php';
            }
            $showSoldBar = false;
            ?>
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
                <?php
                $pcTabs = [
                    ['label' => 'Tất cả', 'kw' => 'all'],
                    ['label' => 'PC I3', 'kw' => 'i3'],
                    ['label' => 'PC I5', 'kw' => 'i5'],
                    ['label' => 'PC I7', 'kw' => 'i7'],
                    ['label' => 'PC R5', 'kw' => 'ryzen 5'],
                ];
                foreach ($pcTabs as $idx => $tab):
                ?>
                    <button type="button" onclick="filterByText('pc-grid', '<?= e($tab['kw']) ?>', this)"
                        class="tab-btn px-3 py-1 rounded-full text-xs font-semibold border transition <?= $idx === 0 ? 'bg-gvn-red text-white border-gvn-red' : 'bg-gray-50 text-gray-700 border-gray-200 hover:border-gvn-red' ?>">
                        <?= e($tab['label']) ?>
                    </button>
                <?php endforeach; ?>
                <a href="collection.php?cat=pc-gvn" class="text-xs font-bold text-gvn-red hover:underline ml-2 whitespace-nowrap">
                    Xem tất cả <i class="fa-solid fa-chevron-right"></i>
                </a>
            </div>
        </div>

        <div id="pc-grid" class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3">
            <?php foreach ($pcs as $product): ?>
                <?php $pcSearchText = mb_strtolower($product['name'] . ' ' . implode(' ', (array)($product['specs'] ?? [])), 'UTF-8'); ?>
                <div class="filter-item" data-filter-text="<?= e($pcSearchText) ?>">
                    <?php include __DIR__ . '/includes/product-card.php'; ?>
                </div>
            <?php endforeach; ?>
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
                <?php foreach (['Tất cả', 'ACER', 'ASUS', 'MSI', 'LENOVO'] as $idx => $tab): ?>
                    <button type="button" onclick="filterSection('laptop-gaming-grid', '<?= e($tab) ?>', this)"
                        class="tab-btn px-3 py-1 rounded-full text-xs font-semibold border transition <?= $idx === 0 ? 'bg-gvn-red text-white border-gvn-red' : 'bg-gray-50 text-gray-700 border-gray-200 hover:border-gvn-red' ?>">
                        <?= e($tab) ?>
                    </button>
                <?php endforeach; ?>
                <a href="collection.php?cat=laptop-gaming" class="text-xs font-bold text-gvn-red hover:underline ml-2 whitespace-nowrap">
                    Xem tất cả <i class="fa-solid fa-chevron-right"></i>
                </a>
            </div>
        </div>

        <div id="laptop-gaming-grid" class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3">
            <?php foreach ($laptopGamings as $product): ?>
                <div class="filter-item" data-brand="<?= e(mb_strtolower((string)$product['brand'], 'UTF-8')) ?>">
                    <?php include __DIR__ . '/includes/product-card.php'; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- 6. MÀN HÌNH & GAMING GEAR -->
    <section class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        <div class="bg-white rounded-2xl border border-gray-200 p-4 shadow-sm">
            <div class="flex items-center justify-between mb-3 pb-2 border-b border-gray-100">
                <h2 class="text-lg font-extrabold text-gray-900 uppercase">Màn hình bán chạy</h2>
                <a href="collection.php?cat=man-hinh" class="text-xs font-bold text-gvn-red hover:underline">Xem tất cả ›</a>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <?php foreach ($monitors as $product) { include __DIR__ . '/includes/product-card.php'; } ?>
            </div>
        </div>

        <div class="bg-white rounded-2xl border border-gray-200 p-4 shadow-sm">
            <div class="flex items-center justify-between mb-3 pb-2 border-b border-gray-100">
                <h2 class="text-lg font-extrabold text-gray-900 uppercase">Bàn phím, Chuột & Gear</h2>
                <a href="collection.php?cat=ban-phim-chuot" class="text-xs font-bold text-gvn-red hover:underline">Xem tất cả ›</a>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <?php foreach (array_slice($gears, 0, 2) as $product) { include __DIR__ . '/includes/product-card.php'; } ?>
            </div>
        </div>
    </section>

    <!-- 7. TIN TỨC & REVIEW CÔNG NGHỆ -->
    <section id="news-section" class="bg-white rounded-2xl border border-gray-200 p-4 shadow-sm">
        <div class="flex items-center justify-between mb-4 pb-2 border-b border-gray-100">
            <h2 class="text-lg sm:text-xl font-extrabold text-gray-900 uppercase">Cập nhật Tin Tức Công Nghệ</h2>
            <a href="news.php" class="text-xs font-bold text-gvn-red hover:underline">Xem tất cả <i class="fa-solid fa-chevron-right"></i></a>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-4">
            <?php if (!empty($news[0])): ?>
                <?php $leadUrl = !empty($news[0]['slug']) ? 'news.php?slug=' . urlencode($news[0]['slug']) : 'news.php?id=' . (int)$news[0]['id']; ?>
                <a href="<?= e($leadUrl) ?>" class="lg:col-span-6 group block">
                    <div class="rounded-xl overflow-hidden aspect-video mb-3">
                        <img src="<?= e($news[0]['image']) ?>" alt="<?= e($news[0]['title']) ?>" class="w-full h-full object-cover group-hover:scale-105 transition duration-300">
                    </div>
                    <h3 class="text-base font-bold text-gray-900 group-hover:text-gvn-red transition mb-1.5"><?= e($news[0]['title']) ?></h3>
                    <p class="text-xs text-gray-500 line-clamp-2"><?= e($news[0]['summary']) ?></p>
                </a>
            <?php endif; ?>

            <div class="lg:col-span-6 flex flex-col gap-3">
                <?php foreach (array_slice($news, 1) as $item): ?>
                    <?php $itemUrl = !empty($item['slug']) ? 'news.php?slug=' . urlencode($item['slug']) : 'news.php?id=' . (int)$item['id']; ?>
                    <a href="<?= e($itemUrl) ?>" class="flex gap-3 group items-start">
                        <div class="w-32 sm:w-40 aspect-video rounded-lg overflow-hidden shrink-0">
                            <img src="<?= e($item['image']) ?>" alt="<?= e($item['title']) ?>" class="w-full h-full object-cover group-hover:scale-105 transition duration-300">
                        </div>
                        <div>
                            <h4 class="text-sm font-bold text-gray-900 group-hover:text-gvn-red line-clamp-2 transition mb-1"><?= e($item['title']) ?></h4>
                            <span class="text-[11px] text-gray-400"><i class="fa-regular fa-clock mr-1"></i><?= e($item['date']) ?></span>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

</div>

<script>
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

    let totalSeconds = 2 * 3600 + 15 * 60 + 40;
    setInterval(() => {
        if (totalSeconds <= 0) return;
        totalSeconds--;
        document.getElementById('countdown-h').textContent = String(Math.floor(totalSeconds / 3600)).padStart(2, '0');
        document.getElementById('countdown-m').textContent = String(Math.floor((totalSeconds % 3600) / 60)).padStart(2, '0');
        document.getElementById('countdown-s').textContent = String(totalSeconds % 60).padStart(2, '0');
    }, 1000);

    function setActiveTab(btn) {
        const container = btn.parentElement;
        container.querySelectorAll('.tab-btn').forEach(b => {
            b.classList.remove('bg-gvn-red', 'text-white', 'border-gvn-red');
            b.classList.add('bg-gray-50', 'text-gray-700', 'border-gray-200');
        });
        btn.classList.remove('bg-gray-50', 'text-gray-700', 'border-gray-200');
        btn.classList.add('bg-gvn-red', 'text-white', 'border-gvn-red');
    }

    function filterSection(gridId, brand, btn) {
        setActiveTab(btn);
        const brandLower = String(brand || '').toLowerCase();
        document.querySelectorAll(`#${gridId} .filter-item`).forEach(item => {
            const itemBrand = String(item.dataset.brand || '').toLowerCase();
            item.style.display = (brand === 'Tất cả' || itemBrand === brandLower) ? 'block' : 'none';
        });
    }

    function filterByText(gridId, keyword, btn) {
        setActiveTab(btn);
        const kwLower = String(keyword || '').toLowerCase();
        document.querySelectorAll(`#${gridId} .filter-item`).forEach(item => {
            const haystack = String(item.dataset.filterText || '').toLowerCase();
            const matched = (kwLower === 'all') || haystack.includes(kwLower) || (kwLower === 'ryzen 5' && haystack.includes('r5'));
            item.style.display = matched ? 'block' : 'none';
        });
    }
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
