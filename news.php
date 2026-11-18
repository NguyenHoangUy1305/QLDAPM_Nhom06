<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/data/store.php';

$newsList = getNews();
$id = isset($_GET['id']) ? (int)$_GET['id'] : -1;
$article = ($id >= 0 && isset($newsList[$id])) ? $newsList[$id] : null;

$pageTitle = ($article ? $article['title'] : 'Tin tức & Review Công Nghệ') . ' - ĐOÀN 6';
require_once __DIR__ . '/includes/header.php';
?>

<div class="max-w-[1040px] mx-auto px-3 py-6 space-y-5">
    <nav class="text-xs text-gray-500 flex items-center gap-2">
        <a href="index.php" class="hover:text-gvn-red"><i class="fa-solid fa-house"></i> Trang chủ</a>
        <span>/</span>
        <a href="news.php" class="hover:text-gvn-red">Tin tức - Review</a>
        <?php if ($article): ?>
            <span>/</span>
            <span class="text-gray-800 font-semibold truncate"><?= e($article['title']) ?></span>
        <?php endif; ?>
    </nav>

    <?php if ($article): ?>
        <article class="bg-white rounded-2xl border border-gray-200 p-5 sm:p-8 shadow-sm space-y-4">
            <h1 class="text-xl sm:text-2xl font-extrabold text-gray-900 leading-snug"><?= e($article['title']) ?></h1>
            <div class="text-xs text-gray-400 flex items-center gap-4 pb-3 border-b border-gray-100">
                <span><i class="fa-regular fa-clock mr-1"></i> <?= e($article['date']) ?></span>
                <span><i class="fa-regular fa-user mr-1"></i> Ban biên tập ĐOÀN 6</span>
            </div>
            <div class="rounded-xl overflow-hidden max-h-[420px]">
                <img src="<?= e($article['image']) ?>" alt="<?= e($article['title']) ?>" class="w-full h-full object-cover">
            </div>
            <div class="text-sm text-gray-700 leading-relaxed space-y-3">
                <p class="font-semibold text-gray-900"><?= e($article['summary']) ?></p>
                <p>Trong năm 2026, thị trường phần cứng máy tính và laptop gaming tiếp tục chứng kiến nhiều bước tiến mạnh mẽ về hiệu năng trên giá thành. Đội ngũ kỹ thuật tại ĐOÀN 6 đã trực tiếp đo đạc, kiểm thử thực tế để mang đến cho bạn góc nhìn khách quan nhất trước khi quyết định nâng cấp cấu hình.</p>
                <p>Nếu cần tư vấn chi tiết hơn về cấu hình phù hợp với ngân sách, hãy liên hệ hotline <strong>0000.0000</strong> hoặc ghé trực tiếp hệ thống Showroom ĐOÀN 6 để trải nghiệm sản phẩm thực tế.</p>
            </div>
            <div class="pt-4 border-t border-gray-100">
                <a href="news.php" class="text-xs font-bold text-gvn-red hover:underline"><i class="fa-solid fa-arrow-left mr-1"></i> Xem các bài viết khác</a>
            </div>
        </article>
    <?php else: ?>
        <div class="bg-white rounded-2xl border border-gray-200 p-5 shadow-sm">
            <h1 class="text-xl font-extrabold text-gray-900 uppercase mb-4 pb-2 border-b border-gray-100">Tin tức & Review Công Nghệ</h1>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <?php foreach ($newsList as $idx => $item): ?>
                    <a href="news.php?id=<?= $idx ?>" class="group block border border-gray-100 rounded-xl overflow-hidden hover:shadow-md transition">
                        <div class="aspect-video overflow-hidden">
                            <img src="<?= e($item['image']) ?>" alt="<?= e($item['title']) ?>" class="w-full h-full object-cover group-hover:scale-105 transition duration-300">
                        </div>
                        <div class="p-4">
                            <h2 class="text-sm font-bold text-gray-900 group-hover:text-gvn-red mb-1.5 line-clamp-2"><?= e($item['title']) ?></h2>
                            <p class="text-xs text-gray-500 line-clamp-2 mb-2"><?= e($item['summary']) ?></p>
                            <span class="text-[11px] text-gray-400"><i class="fa-regular fa-clock mr-1"></i> <?= e($item['date']) ?></span>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
