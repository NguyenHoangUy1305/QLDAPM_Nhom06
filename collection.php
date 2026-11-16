<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/data/store.php';

$catSlug = $_GET['cat'] ?? 'all';
$query = trim($_GET['q'] ?? '');

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

$pageTitle = $currentCat['name'] . ' - ĐOÀN 6';
require_once __DIR__ . '/includes/header.php';
?>

<div class="max-w-[1280px] mx-auto px-3 py-5">
    <nav class="text-xs text-gray-500 mb-4 flex items-center gap-2">
        <a href="index.php" class="hover:text-gvn-red"><i class="fa-solid fa-house"></i> Trang chủ</a>
        <span>/</span>
        <span class="text-gray-800 font-semibold"><?= e($currentCat['name']) ?></span>
    </nav>

    <div class="bg-white rounded-2xl border border-gray-200 p-4 shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-3 mb-4 pb-3 border-b border-gray-100">
            <h1 class="text-xl font-extrabold text-gray-900 uppercase"><?= e($currentCat['name']) ?></h1>
            <span class="text-xs text-gray-500">Hiển thị <?= count($products) ?> sản phẩm</span>
        </div>

        <?php if (empty($products)): ?>
            <div class="py-12 text-center text-gray-500 text-sm">
                Không tìm thấy sản phẩm nào phù hợp. <a href="index.php" class="text-gvn-red font-bold hover:underline">Quay về trang chủ</a>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-3">
                <?php foreach ($products as $product) { include __DIR__ . '/includes/product-card.php'; } ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
