<?php
require_once __DIR__ . '/_auth.php';
$adminUser = requireStaffOrAdmin();
require_once __DIR__ . '/_layout.php';

function adminCategorySlugify(string $text): string
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
    return $slug !== '' ? $slug : 'danh-muc-' . bin2hex(random_bytes(3));
}

$pdo = getPDO();
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();

    $approver = requireAdminPasswordForStaff();
    if ($approver === null) {
        http_response_code(403);
        $error = 'Mật khẩu Admin không chính xác hoặc không được để trống để thêm, sửa hoặc xóa danh mục.';
    } elseif (!$pdo) {
        $error = 'Không thể kết nối cơ sở dữ liệu.';
    } else {
        $action = (string)($_POST['action'] ?? '');

        if ($action === 'save' || $action === 'add') {
            $id = (int)($_POST['id'] ?? 0);
            $name = trim((string)($_POST['name'] ?? ''));
            $rawSlug = trim((string)($_POST['slug'] ?? ''));
            $icon = trim((string)($_POST['icon'] ?? 'fa-tag')) ?: 'fa-tag';
            $description = trim((string)($_POST['description'] ?? ''));

            if ($name === '') {
                $error = 'Vui lòng nhập tên danh mục.';
            } else {
                $baseSlug = $rawSlug !== '' ? adminCategorySlugify($rawSlug) : adminCategorySlugify($name);
                $slug = $baseSlug;
                $suffix = 1;
                while (true) {
                    $chk = $pdo->prepare('SELECT id FROM categories WHERE slug = ? AND id != ? LIMIT 1');
                    $chk->execute([$slug, $id]);
                    if (!$chk->fetch()) {
                        break;
                    }
                    $slug = $baseSlug . '-' . $suffix++;
                }

                try {
                    if ($id > 0) {
                        $stmt = $pdo->prepare('UPDATE categories SET name = ?, slug = ?, icon = ?, description = ? WHERE id = ?');
                        $stmt->execute([$name, $slug, $icon, $description !== '' ? $description : null, $id]);
                        $message = 'Đã cập nhật danh mục #' . $id . '!';
                    } else {
                        $stmt = $pdo->prepare('INSERT INTO categories (name, slug, icon, description) VALUES (?, ?, ?, ?)');
                        $stmt->execute([$name, $slug, $icon, $description !== '' ? $description : null]);
                        $message = 'Đã thêm danh mục mới!';
                    }
                } catch (PDOException $e) {
                    $error = 'Không thể lưu danh mục: ' . $e->getMessage();
                }
            }
        } elseif ($action === 'delete') {
            $id = (int)($_POST['id'] ?? 0);
            $slug = trim((string)($_POST['slug'] ?? ''));
            try {
                if ($id > 0) {
                    $stmt = $pdo->prepare('DELETE FROM categories WHERE id = ?');
                    $stmt->execute([$id]);
                } elseif ($slug !== '') {
                    $stmt = $pdo->prepare('DELETE FROM categories WHERE slug = ?');
                    $stmt->execute([$slug]);
                }
                if (isset($stmt) && $stmt->rowCount() > 0) {
                    $message = 'Đã xóa danh mục thành công!';
                } else {
                    $error = 'Không tìm thấy danh mục cần xóa.';
                }
            } catch (PDOException $e) {
                $error = 'Không thể xóa danh mục vì đang có sản phẩm thuộc danh mục này.';
            }
        }
    }
}

$categories = getCategories();
$prodCountByCat = [];
if ($pdo) {
    try {
        foreach ($pdo->query('SELECT category_id, COUNT(*) AS cnt FROM products GROUP BY category_id')->fetchAll() as $r) {
            $prodCountByCat[(int)$r['category_id']] = (int)$r['cnt'];
        }
    } catch (PDOException $e) {
    }
}

$editCategory = null;
if (isset($_GET['edit'])) {
    $editId = (int)$_GET['edit'];
    foreach ($categories as $c) {
        if ((int)$c['id'] === $editId) {
            $editCategory = $c;
            break;
        }
    }
}

renderAdminHeader('Quản lý Danh mục', 'categories', $adminUser);
?>

<?php if (isStaff()): ?>
    <div class="bg-amber-50 border border-amber-300 text-amber-900 text-xs rounded-xl p-3.5 mb-4">
        <i class="fa-solid fa-lock text-amber-600 mr-1"></i>
        <strong>Quyền Nhân viên:</strong> Mọi thao tác thêm, sửa, xóa danh mục đều yêu cầu nhập đúng <strong>Mật khẩu Admin</strong>.
    </div>
<?php endif; ?>

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
    <div class="lg:col-span-4 bg-white rounded-xl border border-gray-200 p-4 shadow-sm h-fit">
        <div class="flex items-center justify-between pb-2 mb-3 border-b border-gray-100">
            <h2 class="text-sm font-extrabold uppercase">
                <?= $editCategory ? 'Sửa danh mục #' . (int)$editCategory['id'] : 'Thêm danh mục mới' ?>
            </h2>
            <?php if ($editCategory): ?>
                <a href="categories.php" class="text-xs font-bold text-blue-600 hover:underline">+ Thêm mới</a>
            <?php endif; ?>
        </div>

        <form method="POST" action="categories.php" class="space-y-3 text-xs">
            <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
            <input type="hidden" name="action" value="save">
            <input type="hidden" name="id" value="<?= (int)($editCategory['id'] ?? 0) ?>">

            <div>
                <label class="block font-bold mb-1">Tên danh mục *</label>
                <input type="text" name="name" required value="<?= e($editCategory['name'] ?? '') ?>" placeholder="Ví dụ: Laptop AI"
                    class="w-full h-9 px-3 rounded-lg border border-gray-300">
            </div>
            <div>
                <label class="block font-bold mb-1">Slug (tự tạo nếu để trống)</label>
                <input type="text" name="slug" value="<?= e($editCategory['slug'] ?? '') ?>" placeholder="laptop-ai"
                    class="w-full h-9 px-3 rounded-lg border border-gray-300">
            </div>
            <div>
                <label class="block font-bold mb-1">Icon FontAwesome</label>
                <input type="text" name="icon" value="<?= e($editCategory['icon'] ?? 'fa-laptop') ?>" class="w-full h-9 px-3 rounded-lg border border-gray-300">
            </div>
            <div>
                <label class="block font-bold mb-1">Mô tả ngắn</label>
                <textarea name="description" rows="2" class="w-full p-2.5 rounded-lg border border-gray-300"><?= e($editCategory['description'] ?? '') ?></textarea>
            </div>

            <?php if (isStaff()): ?>
                <div class="bg-amber-50 border border-amber-300 rounded-lg p-2.5">
                    <label class="block font-bold text-amber-900 mb-1">Mật khẩu Admin *</label>
                    <input type="password" name="admin_password" required placeholder="Nhập mật khẩu Admin"
                        class="w-full h-9 px-3 rounded-lg border border-amber-400 bg-white">
                </div>
            <?php endif; ?>

            <div class="flex gap-2">
                <button type="submit" class="flex-1 bg-gvn-red hover:bg-gvn-redHover text-white font-bold py-2.5 rounded-lg uppercase transition">
                    <?= $editCategory ? 'Cập nhật' : 'Thêm danh mục' ?>
                </button>
                <?php if ($editCategory): ?>
                    <a href="categories.php" class="px-4 py-2.5 bg-gray-200 rounded-lg font-bold text-gray-700">Hủy</a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <div class="lg:col-span-8 bg-white rounded-xl border border-gray-200 p-4 shadow-sm">
        <h2 class="text-sm font-extrabold uppercase mb-3">Danh sách danh mục (<?= count($categories) ?>)</h2>
        <div class="overflow-x-auto">
            <table class="w-full text-xs text-left">
                <thead class="bg-gray-50 text-gray-600 uppercase">
                    <tr>
                        <th class="p-2.5">Icon</th>
                        <th class="p-2.5">Tên danh mục</th>
                        <th class="p-2.5">Slug</th>
                        <th class="p-2.5">Số SP</th>
                        <th class="p-2.5 text-right">Thao tác</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php foreach ($categories as $c): ?>
                        <tr>
                            <td class="p-2.5"><i class="fa-solid <?= e($c['icon']) ?> text-gray-500"></i></td>
                            <td class="p-2.5">
                                <div class="font-bold"><?= e($c['name']) ?></div>
                                <?php if (!empty($c['description'])): ?>
                                    <div class="text-[10px] text-gray-400 line-clamp-1"><?= e($c['description']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td class="p-2.5 text-gray-500"><code><?= e($c['slug']) ?></code></td>
                            <td class="p-2.5 font-semibold"><?= (int)($prodCountByCat[(int)$c['id']] ?? 0) ?></td>
                            <td class="p-2.5 text-right whitespace-nowrap">
                                <a href="categories.php?edit=<?= (int)$c['id'] ?>" class="text-blue-600 hover:underline font-bold mr-2">Sửa</a>
                                <form method="POST" action="categories.php" class="inline" onsubmit="return confirmCatDelete(this)">
                                    <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
                                    <input type="hidden" name="slug" value="<?= e($c['slug']) ?>">
                                    <input type="hidden" name="admin_password" value="">
                                    <button type="submit" class="text-red-600 hover:underline font-bold">Xóa</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
const IS_STAFF_CAT = <?= isStaff() ? 'true' : 'false' ?>;
function confirmCatDelete(form) {
    if (!confirm('Xác nhận xóa danh mục này?')) {
        return false;
    }
    if (!IS_STAFF_CAT) {
        return true;
    }
    const pwInput = form.querySelector('input[name="admin_password"]');
    if (pwInput && !pwInput.value) {
        const pw = prompt('Nhập mật khẩu Admin để xác nhận xóa danh mục:');
        if (!pw) {
            return false;
        }
        pwInput.value = pw;
    }
    return true;
}
</script>

<?php renderAdminFooter(); ?>
