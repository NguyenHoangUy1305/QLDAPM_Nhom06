<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/data/store.php';

$slug = $_GET['slug'] ?? '';
$product = getProductBySlug($slug);
$related = array_slice(getAllProducts(), 0, 4);

$pageTitle = $product['name'] . ' - ĐOÀN 6';
require_once __DIR__ . '/includes/header.php';
?>

<div class="max-w-[1280px] mx-auto px-3 py-5 space-y-6">
    <nav class="text-xs text-gray-500 flex items-center gap-2">
        <a href="index.php" class="hover:text-gvn-red"><i class="fa-solid fa-house"></i> Trang chủ</a>
        <span>/</span>
        <a href="collection.php?cat=<?= e($product['category']) ?>" class="hover:text-gvn-red uppercase"><?= e($product['category']) ?></a>
        <span>/</span>
        <span class="text-gray-800 font-semibold truncate"><?= e($product['name']) ?></span>
    </nav>

    <div class="bg-white rounded-2xl border border-gray-200 p-5 shadow-sm grid grid-cols-1 lg:grid-cols-12 gap-6">
        <div class="lg:col-span-5">
            <div class="aspect-square rounded-xl overflow-hidden border border-gray-100 bg-gray-50">
                <img src="<?= e($product['image']) ?>" alt="<?= e($product['name']) ?>" class="w-full h-full object-cover">
            </div>
        </div>

        <div class="lg:col-span-7 flex flex-col">
            <h1 class="text-xl sm:text-2xl font-extrabold text-gray-900 mb-2"><?= e($product['name']) ?></h1>
            <div class="flex items-center gap-3 text-xs text-gray-500 mb-4">
                <span>Thương hiệu: <strong class="text-gvn-red"><?= e($product['brand']) ?></strong></span>
                <span>|</span>
                <span class="text-emerald-600 font-semibold"><i class="fa-solid fa-circle-check"></i> Còn hàng</span>
            </div>

            <div class="bg-gray-50 rounded-xl p-4 mb-4 flex items-baseline gap-3">
                <span class="text-2xl sm:text-3xl font-extrabold text-gvn-red"><?= formatPrice((int)$product['price']) ?></span>
                <?php if (!empty($product['old_price'])): ?>
                    <span class="text-sm text-gray-400 line-through"><?= formatPrice((int)$product['old_price']) ?></span>
                    <span class="bg-gvn-red text-white text-xs font-bold px-2 py-0.5 rounded">-<?= (int)$product['discount'] ?>%</span>
                <?php endif; ?>
            </div>

            <?php if (!empty($product['specs'])): ?>
                <div class="mb-4">
                    <h3 class="text-xs font-bold uppercase text-gray-700 mb-2">Thông số nổi bật:</h3>
                    <div class="flex flex-wrap gap-2">
                        <?php foreach ($product['specs'] as $spec): ?>
                            <span class="bg-gray-100 text-gray-800 text-xs font-medium px-2.5 py-1 rounded-md"><?= e($spec) ?></span>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (!empty($product['promo'])): ?>
                <div class="bg-blue-50 border border-blue-200 rounded-xl p-3.5 mb-5 text-xs text-blue-800">
                    <div class="font-bold mb-1 flex items-center gap-1.5">
                        <i class="fa-solid fa-gift text-blue-600"></i> Quà tặng & Khuyến mãi:
                    </div>
                    <p><?= e($product['promo']) ?></p>
                </div>
            <?php endif; ?>

            <form action="cart.php" method="POST" class="mt-auto flex flex-col sm:flex-row gap-3">
                <input type="hidden" name="action" value="add">
                <input type="hidden" name="product_id" value="<?= (int)$product['id'] ?>">
                <button type="submit" class="flex-1 bg-gvn-red hover:bg-gvn-redHover text-white font-extrabold py-3.5 px-6 rounded-xl text-center uppercase tracking-wide transition shadow-md">
                    MUA NGAY
                    <div class="text-[11px] font-normal opacity-90">Giao tận nơi hoặc nhận tại Showroom</div>
                </button>
                <button type="submit" class="bg-white border-2 border-gvn-red text-gvn-red hover:bg-red-50 font-bold py-3.5 px-6 rounded-xl text-center flex items-center justify-center gap-2 transition">
                    <i class="fa-solid fa-cart-plus"></i> Thêm vào giỏ
                </button>
            </form>
        </div>
    </div>

    <div class="bg-white rounded-2xl border border-gray-200 p-4 shadow-sm">
        <h2 class="text-lg font-extrabold text-gray-900 uppercase mb-4">Sản phẩm tương tự</h2>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
            <?php foreach ($related as $product) { include __DIR__ . '/includes/product-card.php'; } ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
