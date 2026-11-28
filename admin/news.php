<?php
require_once __DIR__ . '/_auth.php';
$adminUser = requireAdmin();
require_once __DIR__ . '/_layout.php';

function adminNewsSlugify(string $text): string
{
    $trans = [
        'à'=>'a','á'=>'a','ạ'=>'a','ả'=>'a','ã'=>'a','â'=>'a','ầ'=>'a','ấ'=>'a','ậ'=>'a','ẩ'=>'a','ẫ'=>'a','ă'=>'a','ằ'=>'a','ắ'=>'a','ặ'=>'a','ẳ'=>'a','ẵ'=>'a',
        'è'=>'e','é'=>'e','ẹ'=>'e','ẻ'=>'e','ẽ'=>'e','ê'=>'e','ề'=>'e','ế'=>'e','ệ'=>'e','ể'=>'e','ễ'=>'e',
        'ì'=>'i','í'=>'i','ị'=>'i','ỉ'=>'i','ĩ'=>'i',
        'ò'=>'o','ó'=>'o','ọ'=>'o','ỏ'=>'o','õ'=>'o','ô'=>'o','ồ'=>'o','ố'=>'o','ộ'=>'o','ổ'=>'o','ỗ'=>'o','ơ'=>'o','ờ'=>'o','ớ'=>'o','ợ'=>'o','ở'=>'o','ỡ'=>'o',
        'ù'=>'u','ú'=>'u','ụ'=>'u','ủ'=>'u','ũ'=>'u','ư'=>'u','ừ'=>'u','ứ'=>'u','ự'=>'u','ử'=>'u','ữ'=>'u',
        'ỳ'=>'y','ý'=>'y','ỵ'=>'y','ỷ'=>'y','ỹ'=>'y','đ'=>'d',
        'À'=>'a','Á'=>'a','Ạ'=>'a','Ả'=>'a','Ã'=>'a','Â'=>'a','Ầ'=>'a','Ấ'=>'a','Ậ'=>'a','Ẩ'=>'a','Ẫ'=>'a','Ă'=>'a','Ằ'=>'a','Ắ'=>'a','Ặ'=>'a','Ẳ'=>'a','Ẵ'=>'a',
        'È'=>'e','É'=>'e','Ẹ'=>'e','Ẻ'=>'e','Ẽ'=>'e','Ê'=>'e','Ề'=>'e','Ế'=>'e','Ệ'=>'e','Ể'=>'e','Ễ'=>'e',
        'Ì'=>'i','Í'=>'i','Ị'=>'i','Ỉ'=>'i','Ĩ'=>'i',
        'Ò'=>'o','Ó'=>'o','Ọ'=>'o','Ỏ'=>'o','Õ'=>'o','Ô'=>'o','Ồ'=>'o','Ố'=>'o','Ộ'=>'o','Ổ'=>'o','Ỗ'=>'o','Ơ'=>'o','Ờ'=>'o','Ớ'=>'o','Ợ'=>'o','Ở'=>'o','Ỡ'=>'o',
        'Ù'=>'u','Ú'=>'u','Ụ'=>'u','Ủ'=>'u','Ũ'=>'u','Ư'=>'u','Ừ'=>'u','Ứ'=>'u','Ự'=>'u','Ử'=>'u','Ữ'=>'u',
        'Ỳ'=>'y','Ý'=>'y','Ỵ'=>'y','Ỷ'=>'y','Ỹ'=>'y','Đ'=>'d',
    ];
    $slug = strtolower(strtr(trim($text), $trans));
    $slug = trim((string)preg_replace('/[^a-z0-9]+/', '-', $slug), '-');
    return $slug !== '' ? $slug : 'bai-viet-' . bin2hex(random_bytes(3));
}

$pdo = getPDO();
$message = '';
$error = '';
$newsCategories = ['Đánh giá VGA', 'Tư vấn Laptop', 'Đánh giá Gear', 'Đánh giá CPU/PC', 'Tin công nghệ', 'Tin cửa hàng'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();

    if (!$pdo) {
        $error = 'Không thể kết nối cơ sở dữ liệu.';
    } else {
        $action = (string)($_POST['action'] ?? '');

        if ($action === 'save') {
            $id = (int)($_POST['id'] ?? 0);
            $title = trim((string)($_POST['title'] ?? ''));
            $rawSlug = trim((string)($_POST['slug'] ?? ''));
            $category = trim((string)($_POST['category'] ?? 'Tin công nghệ')) ?: 'Tin công nghệ';
            $summary = trim((string)($_POST['summary'] ?? ''));
            $content = trim((string)($_POST['content'] ?? ''));
            $publishedAt = trim((string)($_POST['published_at'] ?? ''));
            $imagePath = trim((string)($_POST['existing_image'] ?? 'assets/images/news/news1.jpg'));

            $imageUrlInput = trim((string)($_POST['image_url'] ?? ''));
            if ($imageUrlInput !== '' && !str_contains($imageUrlInput, '..') && !str_contains($imageUrlInput, '\\')) {
                $imagePath = $imageUrlInput;
            }

            if (!empty($_FILES['image_file']['name']) && ($_FILES['image_file']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
                $tmp = (string)$_FILES['image_file']['tmp_name'];
                $ext = strtolower(pathinfo((string)$_FILES['image_file']['name'], PATHINFO_EXTENSION));
                if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true) && is_uploaded_file($tmp) && @getimagesize($tmp) !== false) {
                    $dir = __DIR__ . '/../assets/images/news';
                    if (!is_dir($dir)) {
                        @mkdir($dir, 0775, true);
                    }
                    $fileName = 'news_' . bin2hex(random_bytes(5)) . '.' . $ext;
                    if (move_uploaded_file($tmp, $dir . '/' . $fileName)) {
                        $imagePath = 'assets/images/news/' . $fileName;
                    }
                }
            }

            if ($imagePath === '') {
                $imagePath = 'assets/images/news/news1.jpg';
            }

            if ($title === '' || $content === '') {
                $error = 'Vui lòng nhập tiêu đề và nội dung bài viết.';
            } else {
                if ($summary === '') {
                    $summary = mb_substr(strip_tags($content), 0, 180, 'UTF-8');
                }
                if ($publishedAt === '') {
                    $publishedAt = date('Y-m-d H:i:s');
                } else {
                    $ts = strtotime($publishedAt);
                    $publishedAt = $ts ? date('Y-m-d H:i:s', $ts) : date('Y-m-d H:i:s');
                }

                $baseSlug = $rawSlug !== '' ? adminNewsSlugify($rawSlug) : adminNewsSlugify($title);
                $slug = $baseSlug;
                $suffix = 1;
                while (true) {
                    $chk = $pdo->prepare('SELECT id FROM news WHERE slug = ? AND id != ? LIMIT 1');
                    $chk->execute([$slug, $id]);
                    if (!$chk->fetch()) {
                        break;
                    }
                    $slug = $baseSlug . '-' . $suffix++;
                }

                try {
                    if ($id > 0) {
                        $stmt = $pdo->prepare('
                            UPDATE news
                            SET title = ?, slug = ?, image = ?, summary = ?, content = ?, category = ?, published_at = ?
                            WHERE id = ?
                        ');
                        $stmt->execute([$title, $slug, $imagePath, $summary, $content, $category, $publishedAt, $id]);
                        $message = 'Đã cập nhật bài viết #' . $id . '!';
                    } else {
                        $stmt = $pdo->prepare('
                            INSERT INTO news (title, slug, image, summary, content, category, author_id, published_at)
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                        ');
                        $stmt->execute([$title, $slug, $imagePath, $summary, $content, $category, (int)$adminUser['id'], $publishedAt]);
                        $message = 'Đã đăng bài viết review mới thành công!';
                    }
                } catch (PDOException $e) {
                    $error = 'Không thể lưu bài viết: ' . $e->getMessage();
                }
            }
        } elseif ($action === 'delete') {
            $id = (int)($_POST['id'] ?? 0);
            if ($id > 0) {
                try {
                    $stmt = $pdo->prepare('DELETE FROM news WHERE id = ?');
                    $stmt->execute([$id]);
                    if ($stmt->rowCount() > 0) {
                        $message = 'Đã xóa bài viết #' . $id . '!';
                    } else {
                        $error = 'Không tìm thấy bài viết cần xóa.';
                    }
                } catch (PDOException $e) {
                    $error = 'Không thể xóa bài viết.';
                }
            }
        }
    }
}

$newsList = getNews();
$editNews = null;
if (isset($_GET['edit'])) {
    $editId = (int)$_GET['edit'];
    foreach ($newsList as $n) {
        if ((int)$n['id'] === $editId) {
            $editNews = $n;
            break;
        }
    }
}

renderAdminHeader('Tin tức & Bài viết Review (Chỉ Admin)', 'news', $adminUser);
?>

<?php if ($message !== ''): ?>
    <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-semibold rounded-xl p-3 mb-4">
        <i class="fa-solid fa-circle-check mr-1"></i> <?= e($message) ?>
    </div>
<?php endif; ?>

<?php if ($error !== ''): ?>
    <div class="bg-red-50 border border-red-200 text-red-700 text-xs font-semibold rounded-xl p-3 mb-4">
        <i class="fa-solid fa-triangle-exclamation mr-1"></i> <?= e($error) ?>
    </div>
<?php endif; ?>

<div class="grid grid-cols-1 lg:grid-cols-12 gap-5">
    <div class="lg:col-span-5 bg-white rounded-xl border border-gray-200 p-4 shadow-sm h-fit">
        <div class="flex items-center justify-between pb-2 mb-3 border-b border-gray-100">
            <h2 class="text-sm font-extrabold uppercase">
                <?= $editNews ? 'Sửa bài viết #' . (int)$editNews['id'] : 'Thêm bài viết Review sản phẩm' ?>
            </h2>
            <?php if ($editNews): ?>
                <a href="news.php" class="text-xs font-bold text-blue-600 hover:underline">+ Bài mới</a>
            <?php endif; ?>
        </div>

        <form method="POST" action="news.php" enctype="multipart/form-data" class="space-y-3 text-xs">
            <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
            <input type="hidden" name="action" value="save">
            <input type="hidden" name="id" value="<?= (int)($editNews['id'] ?? 0) ?>">
            <input type="hidden" name="existing_image" value="<?= e($editNews['image'] ?? 'assets/images/news/news1.jpg') ?>">

            <div>
                <label class="block font-bold mb-1">Tiêu đề bài viết *</label>
                <input type="text" name="title" required value="<?= e($editNews['title'] ?? '') ?>" placeholder="Đánh giá hiệu năng RTX 4070 Super..."
                    class="w-full h-9 px-3 rounded-lg border border-gray-300">
            </div>
            <div class="grid grid-cols-2 gap-2">
                <div>
                    <label class="block font-bold mb-1">Chuyên mục</label>
                    <select name="category" class="w-full h-9 px-2 rounded-lg border border-gray-300">
                        <?php foreach ($newsCategories as $nc): ?>
                            <option value="<?= e($nc) ?>" <?= ($editNews['category'] ?? '') === $nc ? 'selected' : '' ?>><?= e($nc) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block font-bold mb-1">Slug (tự tạo nếu trống)</label>
                    <input type="text" name="slug" value="<?= e($editNews['slug'] ?? '') ?>" class="w-full h-9 px-3 rounded-lg border border-gray-300">
                </div>
            </div>
            <div>
                <label class="block font-bold mb-1">Tóm tắt ngắn</label>
                <textarea name="summary" rows="2" class="w-full p-2.5 rounded-lg border border-gray-300"><?= e($editNews['summary'] ?? '') ?></textarea>
            </div>
            <div>
                <label class="block font-bold mb-1">Nội dung chi tiết *</label>
                <textarea name="content" rows="6" required class="w-full p-2.5 rounded-lg border border-gray-300"><?= e($editNews['content'] ?? '') ?></textarea>
            </div>
            <div>
                <label class="block font-bold mb-1">Ảnh bìa (tải lên file)</label>
                <input type="file" name="image_file" accept="image/*" class="w-full text-[11px]">
            </div>
            <div class="grid grid-cols-2 gap-2">
                <div>
                    <label class="block font-bold mb-1">Hoặc đường dẫn ảnh</label>
                    <input type="text" name="image_url" value="<?= e($editNews['image'] ?? 'assets/images/news/news1.jpg') ?>" class="w-full h-9 px-3 rounded-lg border border-gray-300">
                </div>
                <div>
                    <label class="block font-bold mb-1">Ngày đăng</label>
                    <?php
                    $dtVal = !empty($editNews['published_at'])
                        ? date('Y-m-d\TH:i', strtotime((string)$editNews['published_at']))
                        : date('Y-m-d\TH:i');
                    ?>
                    <input type="datetime-local" name="published_at" value="<?= e($dtVal) ?>" class="w-full h-9 px-2.5 rounded-lg border border-gray-300">
                </div>
            </div>

            <div class="flex gap-2 pt-1">
                <button type="submit" class="flex-1 bg-gvn-red hover:bg-gvn-redHover text-white font-bold py-2.5 rounded-lg uppercase transition">
                    <?= $editNews ? 'Cập nhật bài viết' : 'Đăng bài viết' ?>
                </button>
                <?php if ($editNews): ?>
                    <a href="news.php" class="px-4 py-2.5 bg-gray-200 rounded-lg font-bold text-gray-700">Hủy</a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <div class="lg:col-span-7 bg-white rounded-xl border border-gray-200 p-4 shadow-sm">
        <h2 class="text-sm font-extrabold uppercase mb-3">Danh sách bài viết (<?= count($newsList) ?>)</h2>
        <div class="space-y-3">
            <?php foreach ($newsList as $n): ?>
                <?php $nImg = preg_match('#^https?://#i', (string)$n['image']) ? $n['image'] : '../' . ltrim((string)$n['image'], '/'); ?>
                <div class="border border-gray-200 rounded-xl p-3 flex gap-3 items-start">
                    <img src="<?= e($nImg) ?>" alt="" class="w-20 h-16 object-cover rounded-lg border border-gray-200 shrink-0">
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 text-[10px] mb-1">
                            <span class="px-2 py-0.5 rounded bg-red-50 text-gvn-red font-bold"><?= e($n['category']) ?></span>
                            <span class="text-gray-400"><?= e($n['date'] ?? $n['published_at']) ?></span>
                            <?php if (!empty($n['author_name'])): ?>
                                <span class="text-gray-500">· <?= e($n['author_name']) ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="font-bold text-xs text-gray-900 line-clamp-2"><?= e($n['title']) ?></div>
                        <div class="text-[11px] text-gray-500 line-clamp-1 mt-0.5"><?= e($n['summary']) ?></div>
                        <div class="mt-2 flex items-center gap-3 text-xs">
                            <a href="news.php?edit=<?= (int)$n['id'] ?>" class="text-blue-600 hover:underline font-bold">Sửa</a>
                            <form method="POST" action="news.php" class="inline" onsubmit="return confirm('Xóa bài viết này?')">
                                <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= (int)$n['id'] ?>">
                                <button type="submit" class="text-red-600 hover:underline font-bold">Xóa</button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<?php renderAdminFooter(); ?>
