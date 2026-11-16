<?php
/** @var array $product */
/** @var bool $showSoldBar */
$showSoldBar = $showSoldBar ?? false;
?>
<div class="bg-white rounded-xl border border-gray-200 p-3 flex flex-col justify-between hover:shadow-lg hover:-translate-y-1 transition duration-200 group relative">
    <!-- Top Badges -->
    <div class="flex items-center justify-between gap-1 mb-2">
        <?php if (!empty($product['discount'])): ?>
            <span class="bg-gvn-red text-white text-[11px] font-bold px-2 py-0.5 rounded-md">
                -<?= (int)$product['discount'] ?>%
            </span>
        <?php else: ?>
            <span></span>
        <?php endif; ?>

        <?php if (!empty($product['fast_delivery'])): ?>
            <span class="bg-amber-100 text-red-600 border border-amber-300 text-[10px] font-extrabold px-1.5 py-0.5 rounded flex items-center gap-1">
                <i class="fa-solid fa-bolt text-amber-500"></i> HỎA TỐC
            </span>
        <?php endif; ?>
    </div>

    <!-- Image -->
    <a href="product.php?slug=<?= e($product['slug']) ?>" class="block aspect-square overflow-hidden rounded-lg bg-gray-50 mb-3">
        <img src="<?= e($product['image']) ?>" alt="<?= e($product['name']) ?>"
             class="w-full h-full object-cover group-hover:scale-105 transition duration-300">
    </a>

    <!-- Title -->
    <a href="product.php?slug=<?= e($product['slug']) ?>" class="text-[13px] font-semibold text-gray-900 group-hover:text-gvn-red line-clamp-2 leading-snug mb-2 min-h-[36px] transition">
        <?= e($product['name']) ?>
    </a>

    <!-- Spec Badges -->
    <?php if (!empty($product['specs'])): ?>
        <div class="flex flex-wrap gap-1 mb-2.5">
            <?php foreach (array_slice($product['specs'], 0, 4) as $spec): ?>
                <span class="bg-gray-100 text-gray-600 text-[10px] font-medium px-1.5 py-0.5 rounded">
                    <?= e($spec) ?>
                </span>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- Price -->
    <div class="mt-auto">
        <?php if (!empty($product['old_price'])): ?>
            <div class="text-xs text-gray-400 line-through">
                <?= formatPrice((int)$product['old_price']) ?>
            </div>
        <?php endif; ?>
        <div class="text-base font-extrabold text-gvn-red">
            <?= formatPrice((int)$product['price']) ?>
        </div>

        <!-- Flash Sale Sold Bar -->
        <?php if ($showSoldBar && !empty($product['sold_percent'])): ?>
            <div class="mt-2">
                <div class="w-full bg-red-100 rounded-full h-4 relative overflow-hidden">
                    <div class="bg-gradient-to-r from-orange-500 to-gvn-red h-4 rounded-full" style="width: <?= (int)$product['sold_percent'] ?>%"></div>
                    <span class="absolute inset-0 flex items-center justify-center text-[10px] font-bold text-white drop-shadow">
                        <?= e($product['sold_text'] ?? 'Đang bán chạy') ?>
                    </span>
                </div>
            </div>
        <?php endif; ?>

        <!-- Promo Box -->
        <?php if (!empty($product['promo'])): ?>
            <div class="mt-2.5 bg-blue-50 border border-blue-200 text-blue-700 text-[11px] rounded-lg px-2 py-1.5 flex items-start gap-1.5 leading-tight">
                <i class="fa-solid fa-gift text-blue-500 mt-0.5 shrink-0"></i>
                <span class="line-clamp-2"><?= e($product['promo']) ?></span>
            </div>
        <?php endif; ?>
    </div>
</div>
