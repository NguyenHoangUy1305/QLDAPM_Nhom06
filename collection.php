<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/data/store.php';

$catSlug = $_GET['cat'] ?? 'all';
$query = trim($_GET['q'] ?? '');
$priceRange = $_GET['price'] ?? 'all';
$brandFilter = $_GET['brand'] ?? 'all';
$sort = $_GET['sort'] ?? 'default';
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 10;

$categories = getCategories();
$allProducts = getAllProducts();

$currentCat = ['name' => 'Tất cả sản phẩm', 'slug' => 'all'];
foreach ($categories as $c) {
    if ($c['slug'] === $catSlug) {
        $currentCat = $c;
        break;
    }
}

$products = $allProducts;
if ($catSlug !== 'all') {
    $filtered = array_values(array_filter($allProducts, fn($p) => $p['category'] === $catSlug));
    if (!empty($filtered)) {
        $products = $filtered;
    }
}

if ($query !== '') {
    $qLower = mb_strtolower($query, 'UTF-8');
    $products = array_values(array_filter($products, fn($p) => str_contains(mb_strtolower($p['name'], 'UTF-8'), $qLower)));
    $currentCat['name'] = 'Kết quả tìm kiếm: "' . $query . '"';
}

// Danh sách hãng có trong tập sản phẩm hiện tại
$availableBrands = array_values(array_unique(array_map(fn($p) => $p['brand'], $products)));
sort($availableBrands);

// Lọc theo hãng
if ($brandFilter !== 'all') {
    $products = array_values(array_filter($products, fn($p) => $p['brand'] === $brandFilter));
}

// Lọc theo khoảng giá
if ($priceRange !== 'all') {
    $products = array_values(array_filter($products, function ($p) use ($priceRange) {
        $price = (int)$p['price'];
        return match ($priceRange) {
            'under-5' => $price < 5000000,
            '5-15' => $price >= 5000000 && $price <= 15000000,
            '15-25' => $price > 15000000 && $price <= 25000000,
            'over-25' => $price > 25000000,
            default => true,
        };
    }));
}

// Sắp xếp
if ($sort === 'price-asc') {
    usort($products, fn($a, $b) => $a['price'] <=> $b['price']);
} elseif ($sort === 'price-desc') {
    usort($products, fn($a, $b) => $b['price'] <=> $a['price']);
} elseif ($sort === 'discount') {
    usort($products, fn($a, $b) => ($b['discount'] ?? 0) <=> ($a['discount'] ?? 0));
}

// Phân trang
$totalItems = count($products);
$totalPages = max(1, (int)ceil($totalItems / $perPage));
$page = min($page, $totalPages);
$pagedProducts = array_slice($products, ($page - 1) * $perPage, $perPage);

$pageTitle = $currentCat['name'] . ' - ĐOÀN 6';
require_once __DIR__ . '/includes/header.php';
?>

<div class="max-w-[1280px] mx-auto px-3 py-5 space-y-4">
    <nav class="text-xs text-gray-500 flex items-center gap-2">
        <a href="index.php" class="hover:text-gvn-red"><i class="fa-solid fa-house"></i> Trang chủ</a>
        <span>/</span>
        <span class="text-gray-800 font-semibold"><?= e($currentCat['name']) ?></span>
    </nav>

    <!-- Bộ lọc & Sắp xếp -->
    <form method="GET" action="collection.php" class="bg-white rounded-2xl border border-gray-200 p-4 shadow-sm flex flex-wrap items-center justify-between gap-3 text-xs">
        <input type="hidden" name="cat" value="<?= e($catSlug) ?>">
        <?php if ($query !== ''): ?>
            <input type="hidden" name="q" value="<?= e($query) ?>">
        <?php endif; ?>

        <div class="flex flex-wrap items-center gap-2.5">
            <span class="font-bold text-gray-700"><i class="fa-solid fa-filter text-gvn-red mr-1"></i> Bộ lọc:</span>
            <select name="price" onchange="this.form.submit()" class="h-9 px-3 rounded-lg border border-gray-300 bg-gray-50 focus:outline-none focus:border-gvn-red">
                <option value="all" <?= $priceRange === 'all' ? 'selected' : '' ?>>Tất cả mức giá</option>
                <option value="under-5" <?= $priceRange === 'under-5' ? 'selected' : '' ?>>Dưới 5 triệu</option>
                <option value="5-15" <?= $priceRange === '5-15' ? 'selected' : '' ?>>5 - 15 triệu</option>
                <option value="15-25" <?= $priceRange === '15-25' ? 'selected' : '' ?>>15 - 25 triệu</option>
                <option value="over-25" <?= $priceRange === 'over-25' ? 'selected' : '' ?>>Trên 25 triệu</option>
            </select>

            <select name="brand" onchange="this.form.submit()" class="h-9 px-3 rounded-lg border border-gray-300 bg-gray-50 focus:outline-none focus:border-gvn-red">
                <option value="all" <?= $brandFilter === 'all' ? 'selected' : '' ?>>Tất cả hãng / dòng</option>
                <?php foreach ($availableBrands as $b): ?>
                    <option value="<?= e($b) ?>" <?= $brandFilter === $b ? 'selected' : '' ?>><?= e($b) ?></option>
                <?php endforeach; ?>
            </select>

            <?php if ($priceRange !== 'all' || $brandFilter !== 'all' || $sort !== 'default'): ?>
                <a href="collection.php?cat=<?= e($catSlug) ?><?= $query !== '' ? '&q=' . urlencode($query) : '' ?>" class="text-gvn-red font-semibold hover:underline ml-1">
                    Xóa bộ lọc
                </a>
            <?php endif; ?>
        </div>

        <div class="flex items-center gap-2">
            <span class="font-bold text-gray-700">Sắp xếp:</span>
            <select name="sort" onchange="this.form.submit()" class="h-9 px-3 rounded-lg border border-gray-300 bg-gray-50 focus:outline-none focus:border-gvn-red">
                <option value="default" <?= $sort === 'default' ? 'selected' : '' ?>>Nổi bật</option>
                <option value="price-asc" <?= $sort === 'price-asc' ? 'selected' : '' ?>>Giá thấp đến cao</option>
                <option value="price-desc" <?= $sort === 'price-desc' ? 'selected' : '' ?>>Giá cao đến thấp</option>
                <option value="discount" <?= $sort === 'discount' ? 'selected' : '' ?>>Giảm giá nhiều nhất</option>
            </select>
        </div>
    </form>

    <div class="bg-white rounded-2xl border border-gray-200 p-4 shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-3 mb-4 pb-3 border-b border-gray-100">
            <h1 class="text-xl font-extrabold text-gray-900 uppercase"><?= e($currentCat['name']) ?></h1>
            <span class="text-xs text-gray-500">Tìm thấy <strong><?= $totalItems ?></strong> sản phẩm</span>
        </div>

        <?php if (empty($pagedProducts)): ?>
            <div class="py-12 text-center text-gray-500 text-sm">
                Không tìm thấy sản phẩm nào phù hợp với bộ lọc.
                <a href="collection.php?cat=<?= e($catSlug) ?>" class="text-gvn-red font-bold hover:underline ml-1">Xem lại tất cả</a>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-3">
                <?php foreach ($pagedProducts as $product) { include __DIR__ . '/includes/product-card.php'; } ?>
            </div>

            <!-- Phân trang -->
            <?php if ($totalPages > 1): ?>
                <div class="mt-6 flex justify-center gap-2">
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <?php
                        $params = $_GET;
                        $params['page'] = $i;
                        $url = 'collection.php?' . http_build_query($params);
                        ?>
                        <a href="<?= e($url) ?>" class="w-9 h-9 rounded-lg flex items-center justify-center text-xs font-bold border transition <?= $i === $page ? 'bg-gvn-red text-white border-gvn-red' : 'bg-white text-gray-700 border-gray-200 hover:border-gvn-red' ?>">
                            <?= $i ?>
                        </a>
                    <?php endfor; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
