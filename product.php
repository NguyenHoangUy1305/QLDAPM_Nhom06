<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/data/store.php';

$slug = trim((string)($_GET['slug'] ?? ''));
$product = $slug !== '' ? getProductBySlug($slug) : [];
$notFound = ($slug === '' || empty($product) || ($product['slug'] ?? '') !== $slug);

if (!$notFound) {
    $images = getProductImages((int)$product['id']);
    if (empty($images)) {
        $images = [['image_url' => $product['image'] ?: 'assets/images/products/laptop1.jpg']];
    }

    $allProducts = getAllProducts();
    $sameCategory = array_values(array_filter(
        $allProducts,
        fn($p) => (int)$p['id'] !== (int)$product['id'] && ($p['category_slug'] ?? '') === ($product['category_slug'] ?? '')
    ));
    $otherProducts = array_values(array_filter(
        $allProducts,
        fn($p) => (int)$p['id'] !== (int)$product['id'] && ($p['category_slug'] ?? '') !== ($product['category_slug'] ?? '')
    ));
    $related = array_slice(array_merge($sameCategory, $otherProducts), 0, 4);
    $pageTitle = $product['name'] . ' - ĐOÀN 6';
} else {
    http_response_code(404);
    $images = [];
    $related = array_slice(getAllProducts(), 0, 4);
    $pageTitle = 'Không tìm thấy sản phẩm - ĐOÀN 6';
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="max-w-[1280px] mx-auto px-3 py-5 space-y-6">
    <?php if ($notFound): ?>
        <div class="bg-white rounded-2xl border border-gray-200 p-8 text-center shadow-sm space-y-4">
            <i class="fa-solid fa-box-open text-5xl text-gray-300"></i>
            <h1 class="text-xl font-extrabold text-gray-900">Không tìm thấy sản phẩm</h1>
            <p class="text-xs text-gray-500">Sản phẩm bạn tìm kiếm không tồn tại hoặc đường dẫn đã thay đổi.</p>
            <div class="flex justify-center gap-3">
                <a href="collection.php?cat=all" class="bg-gvn-red hover:bg-gvn-redHover text-white font-bold px-5 py-2.5 rounded-xl text-xs transition">
                    Xem tất cả sản phẩm
                </a>
                <a href="index.php" class="bg-gray-100 hover:bg-gray-200 text-gray-800 font-bold px-5 py-2.5 rounded-xl text-xs transition">
                    Về trang chủ
                </a>
            </div>
        </div>
    <?php else: ?>
        <?php
        $stock = (int)($product['stock'] ?? 0);
        $warrantyMonths = max(1, (int)($product['warranty_months'] ?? 24));
        $hasDiscountPrice = !empty($product['old_price']) && (int)$product['old_price'] > (int)$product['price'];
        $brandUrl = !empty($product['brand_id'])
            ? 'collection.php?brand_id=' . (int)$product['brand_id']
            : 'collection.php?brand=' . urlencode((string)$product['brand']);
        $categoryLabel = $product['category_name'] ?: $product['category'];
        ?>
        <nav class="text-xs text-gray-500 flex items-center gap-2">
            <a href="index.php" class="hover:text-gvn-red"><i class="fa-solid fa-house"></i> Trang chủ</a>
            <span>/</span>
            <a href="collection.php?cat=<?= e($product['category']) ?>" class="hover:text-gvn-red"><?= e($categoryLabel) ?></a>
            <span>/</span>
            <span class="text-gray-800 font-semibold truncate"><?= e($product['name']) ?></span>
        </nav>

        <div class="bg-white rounded-2xl border border-gray-200 p-5 shadow-sm grid grid-cols-1 lg:grid-cols-12 gap-6">
            <!-- Multi-Image Gallery -->
            <div class="lg:col-span-5 space-y-3" id="product-gallery">
                <div class="aspect-square rounded-xl overflow-hidden border border-gray-100 bg-gray-50">
                    <img id="main-product-image" src="<?= e($images[0]['image_url']) ?>" alt="<?= e($product['name']) ?>" class="main-product-image w-full h-full object-cover">
                </div>
                <?php if (count($images) > 1): ?>
                    <div class="grid grid-cols-5 gap-2" id="product-thumbnails">
                        <?php foreach ($images as $idx => $img): ?>
                            <button type="button"
                                data-gallery-thumb="1"
                                data-src="<?= e($img['image_url']) ?>"
                                onclick="switchProductImage(this, '<?= e($img['image_url']) ?>')"
                                class="thumb-btn gallery-thumb product-thumb aspect-square rounded-lg overflow-hidden border-2 bg-gray-50 transition <?= $idx === 0 ? 'border-gvn-red ring-2 ring-gvn-red/20' : 'border-gray-200 hover:border-gray-400' ?>">
                                <img src="<?= e($img['image_url']) ?>" data-src="<?= e($img['image_url']) ?>" alt="Ảnh <?= $idx + 1 ?> - <?= e($product['name']) ?>" class="w-full h-full object-cover">
                            </button>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Product Info & Actions -->
            <div class="lg:col-span-7 flex flex-col">
                <h1 class="text-xl sm:text-2xl font-extrabold text-gray-900 mb-2"><?= e($product['name']) ?></h1>
                <div class="flex flex-wrap items-center gap-3 text-xs text-gray-500 mb-4">
                    <span>
                        Thương hiệu:
                        <a href="<?= e($brandUrl) ?>" class="font-bold text-gvn-red hover:underline"><?= e($product['brand'] ?: 'ĐOÀN 6') ?></a>
                    </span>
                    <span>|</span>
                    <?php if ($stock > 0): ?>
                        <span class="text-emerald-600 font-semibold"><i class="fa-solid fa-circle-check"></i> Còn hàng (<?= $stock ?> sản phẩm)</span>
                    <?php else: ?>
                        <span class="text-red-600 font-semibold"><i class="fa-solid fa-circle-xmark"></i> Tạm hết hàng</span>
                    <?php endif; ?>
                    <span>|</span>
                    <span class="text-blue-700 font-semibold"><i class="fa-solid fa-shield-halved"></i> Bảo hành <?= $warrantyMonths ?> tháng</span>
                </div>

                <div class="bg-gray-50 rounded-xl p-4 mb-4 flex flex-wrap items-baseline gap-3">
                    <span class="text-2xl sm:text-3xl font-extrabold text-gvn-red"><?= formatPrice((int)$product['price']) ?></span>
                    <?php if ($hasDiscountPrice): ?>
                        <span class="text-sm text-gray-400 line-through"><?= formatPrice((int)$product['old_price']) ?></span>
                        <?php if (!empty($product['discount'])): ?>
                            <span class="bg-gvn-red text-white text-xs font-bold px-2 py-0.5 rounded">-<?= (int)$product['discount'] ?>%</span>
                        <?php endif; ?>
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

                <?php if (!empty($product['description'])): ?>
                    <div class="mb-4 text-xs text-gray-600 leading-relaxed bg-gray-50/70 rounded-xl p-3.5 border border-gray-100">
                        <h3 class="font-bold uppercase text-gray-800 mb-1">Mô tả sản phẩm:</h3>
                        <p><?= nl2br(e($product['description'])) ?></p>
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

                <?php if ($stock > 0): ?>
                    <form action="cart.php" method="POST" class="mt-auto flex flex-col sm:flex-row gap-3 items-stretch">
                        <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                        <input type="hidden" name="action" value="add">
                        <input type="hidden" name="product_id" value="<?= (int)$product['id'] ?>">
                        <input type="number" name="quantity" value="1" min="1" max="<?= $stock ?>" aria-label="Số lượng"
                            class="w-20 px-3 py-2 rounded-xl border border-gray-300 text-center text-sm font-bold">
                        <button type="submit" name="add_to_cart" value="1" class="sm:order-2 bg-white border-2 border-gvn-red text-gvn-red hover:bg-red-50 font-bold py-3.5 px-6 rounded-xl text-center flex items-center justify-center gap-2 transition">
                            <i class="fa-solid fa-cart-plus"></i> Thêm vào giỏ
                        </button>
                        <button type="submit" name="buy_now" value="1" class="sm:order-1 flex-1 bg-gvn-red hover:bg-gvn-redHover text-white font-extrabold py-3.5 px-6 rounded-xl text-center uppercase tracking-wide transition shadow-md">
                            MUA NGAY
                            <div class="text-[11px] font-normal opacity-90">Thanh toán COD hoặc VietQR</div>
                        </button>
                    </form>
                <?php else: ?>
                    <div class="mt-auto bg-red-50 border border-red-200 text-red-700 font-bold py-3.5 px-6 rounded-xl text-center text-sm">
                        <i class="fa-solid fa-ban mr-1"></i> Sản phẩm tạm hết hàng — Vui lòng quay lại sau
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>

    <?php if (!empty($related)): ?>
        <div class="bg-white rounded-2xl border border-gray-200 p-4 shadow-sm">
            <h2 class="text-lg font-extrabold text-gray-900 uppercase mb-4">Sản phẩm tương tự</h2>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                <?php foreach ($related as $product) { include __DIR__ . '/includes/product-card.php'; } ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
    function switchProductImage(btn, url) {
        const mainImg = document.getElementById('main-product-image');
        if (mainImg && url) {
            mainImg.src = url;
        }
        document.querySelectorAll('#product-thumbnails .thumb-btn').forEach(function (el) {
            el.classList.remove('border-gvn-red', 'ring-2', 'ring-gvn-red/20');
            el.classList.add('border-gray-200');
        });
        if (btn) {
            btn.classList.remove('border-gray-200');
            btn.classList.add('border-gvn-red', 'ring-2', 'ring-gvn-red/20');
        }
    }
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
