<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/data/store.php';

$newsList = getNews();
$slugParam = trim((string)($_GET['slug'] ?? ''));
$hasIdParam = isset($_GET['id']) && $_GET['id'] !== '';
$idParam = $hasIdParam ? (int)$_GET['id'] : -1;
$catFilter = trim((string)($_GET['cat'] ?? ''));

$article = null;
if ($slugParam !== '') {
    foreach ($newsList as $item) {
        if (($item['slug'] ?? '') === $slugParam) {
            $article = $item;
            break;
        }
    }
} elseif ($hasIdParam) {
    foreach ($newsList as $item) {
        if ((int)($item['id'] ?? 0) === $idParam) {
            $article = $item;
            break;
        }
    }
    if ($article === null && $idParam === 0 && !empty($newsList[0])) {
        $article = $newsList[0];
    }
}

$newsCategories = array_values(array_unique(array_filter(array_map(
    fn($n) => trim((string)($n['category'] ?? '')),
    $newsList
))));

$filteredNews = $newsList;
if ($catFilter !== '') {
    $filteredNews = array_values(array_filter(
        $newsList,
        fn($n) => mb_strtolower((string)($n['category'] ?? ''), 'UTF-8') === mb_strtolower($catFilter, 'UTF-8')
    ));
}

$relatedNews = [];
if ($article !== null) {
    $relatedNews = array_values(array_filter(
        $newsList,
        fn($n) => (int)($n['id'] ?? 0) !== (int)($article['id'] ?? 0)
    ));
    $relatedNews = array_slice($relatedNews, 0, 3);
}

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
            <?php if (!empty($article['category'])): ?>
                <span class="inline-block bg-red-50 text-gvn-red text-[11px] font-bold px-2.5 py-1 rounded-full uppercase">
                    <?= e($article['category']) ?>
                </span>
            <?php endif; ?>
            <h1 class="text-xl sm:text-2xl font-extrabold text-gray-900 leading-snug"><?= e($article['title']) ?></h1>
            <div class="text-xs text-gray-500 flex flex-wrap items-center gap-4 pb-3 border-b border-gray-100">
                <span><i class="fa-regular fa-clock mr-1"></i> <?= e($article['date']) ?></span>
                <span><i class="fa-regular fa-user mr-1"></i> <?= e(!empty($article['author_name']) ? $article['author_name'] : 'Ban biên tập ĐOÀN 6') ?></span>
            </div>
            <?php if (!empty($article['image'])): ?>
                <div class="rounded-xl overflow-hidden max-h-[420px] bg-gray-50">
                    <img src="<?= e($article['image']) ?>" alt="<?= e($article['title']) ?>" class="w-full h-full object-cover">
                </div>
            <?php endif; ?>
            <div class="text-sm text-gray-700 leading-relaxed space-y-3">
                <?php if (!empty($article['summary'])): ?>
                    <p class="font-semibold text-gray-900 text-base"><?= e($article['summary']) ?></p>
                <?php endif; ?>
                <?php if (!empty($article['content'])): ?>
                    <div class="text-gray-700 leading-relaxed space-y-3">
                        <?= nl2br(e($article['content'])) ?>
                    </div>
                <?php endif; ?>
            </div>
            <div class="pt-4 border-t border-gray-100 flex items-center justify-between">
                <a href="news.php" class="text-xs font-bold text-gvn-red hover:underline"><i class="fa-solid fa-arrow-left mr-1"></i> Xem tất cả bài viết</a>
                <a href="collection.php?cat=all" class="text-xs font-bold text-gray-600 hover:text-gvn-red">Khám phá sản phẩm →</a>
            </div>
        </article>

        <?php if (!empty($relatedNews)): ?>
            <div class="bg-white rounded-2xl border border-gray-200 p-5 shadow-sm">
                <h2 class="text-sm font-extrabold text-gray-900 uppercase mb-3">Bài viết liên quan</h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <?php foreach ($relatedNews as $rel): ?>
                        <?php $relUrl = !empty($rel['slug']) ? 'news.php?slug=' . urlencode($rel['slug']) : 'news.php?id=' . (int)$rel['id']; ?>
                        <a href="<?= e($relUrl) ?>" class="group block border border-gray-100 rounded-xl overflow-hidden hover:shadow-md transition">
                            <div class="aspect-video overflow-hidden bg-gray-50">
                                <img src="<?= e($rel['image']) ?>" alt="<?= e($rel['title']) ?>" class="w-full h-full object-cover group-hover:scale-105 transition duration-300">
                            </div>
                            <div class="p-3">
                                <h3 class="text-xs font-bold text-gray-900 group-hover:text-gvn-red line-clamp-2 mb-1"><?= e($rel['title']) ?></h3>
                                <span class="text-[11px] text-gray-400"><?= e($rel['date']) ?></span>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    <?php else: ?>
        <div class="bg-white rounded-2xl border border-gray-200 p-5 shadow-sm space-y-4">
            <div class="flex flex-wrap items-center justify-between gap-3 pb-3 border-b border-gray-100">
                <h1 class="text-xl font-extrabold text-gray-900 uppercase">Tin tức & Review Công Nghệ</h1>
                <?php if (!empty($newsCategories)): ?>
                    <div class="flex flex-wrap items-center gap-1.5 text-xs">
                        <a href="news.php" class="px-3 py-1 rounded-full font-semibold border transition <?= $catFilter === '' ? 'bg-gvn-red text-white border-gvn-red' : 'bg-gray-50 text-gray-700 border-gray-200 hover:border-gvn-red' ?>">
                            Tất cả
                        </a>
                        <?php foreach ($newsCategories as $nc): ?>
                            <a href="news.php?cat=<?= urlencode($nc) ?>" class="px-3 py-1 rounded-full font-semibold border transition <?= mb_strtolower($catFilter, 'UTF-8') === mb_strtolower($nc, 'UTF-8') ? 'bg-gvn-red text-white border-gvn-red' : 'bg-gray-50 text-gray-700 border-gray-200 hover:border-gvn-red' ?>">
                                <?= e($nc) ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <?php foreach ($filteredNews as $item): ?>
                    <?php $itemUrl = !empty($item['slug']) ? 'news.php?slug=' . urlencode($item['slug']) : 'news.php?id=' . (int)$item['id']; ?>
                    <a href="<?= e($itemUrl) ?>" class="group block border border-gray-100 rounded-xl overflow-hidden hover:shadow-md transition">
                        <div class="aspect-video overflow-hidden bg-gray-50">
                            <img src="<?= e($item['image']) ?>" alt="<?= e($item['title']) ?>" class="w-full h-full object-cover group-hover:scale-105 transition duration-300">
                        </div>
                        <div class="p-4">
                            <?php if (!empty($item['category'])): ?>
                                <span class="inline-block text-[10px] font-bold uppercase text-gvn-red bg-red-50 px-2 py-0.5 rounded mb-1.5">
                                    <?= e($item['category']) ?>
                                </span>
                            <?php endif; ?>
                            <h2 class="text-sm font-bold text-gray-900 group-hover:text-gvn-red mb-1.5 line-clamp-2"><?= e($item['title']) ?></h2>
                            <p class="text-xs text-gray-500 line-clamp-2 mb-2"><?= e($item['summary']) ?></p>
                            <div class="flex items-center justify-between text-[11px] text-gray-400">
                                <span><i class="fa-regular fa-clock mr-1"></i> <?= e($item['date']) ?></span>
                                <span><?= e(!empty($item['author_name']) ? $item['author_name'] : 'Ban biên tập ĐOÀN 6') ?></span>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
