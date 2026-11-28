<?php
require_once __DIR__ . '/_auth.php';
$adminUser = requireStaffOrAdmin();
require_once __DIR__ . '/_layout.php';

function adminBrandSlugify(string $text): string
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
    return $slug !== '' ? $slug : 'thuong-hieu-' . bin2hex(random_bytes(3));
}

$pdo = getPDO();
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();

    $approver = requireAdminPasswordForStaff();
    if ($approver === null) {
        http_response_code(403);
        $error = 'Nhân viên cần nhập đúng mật khẩu Admin để thêm, sửa hoặc xóa thương hiệu.';
    } elseif (!$pdo) {
        $error = 'Không thể kết nối cơ sở dữ liệu.';
    } else {
        $action = (string)($_POST['action'] ?? '');

        if ($action === 'save') {
            $id = (int)($_POST['id'] ?? 0);
            $name = trim((string)($_POST['name'] ?? ''));
            $rawSlug = trim((string)($_POST['slug'] ?? ''));
            $country = trim((string)($_POST['country'] ?? ''));
            $description = trim((string)($_POST['description'] ?? ''));
            $logoPath = trim((string)($_POST['existing_logo'] ?? ''));

            $logoUrlInput = trim((string)($_POST['logo_url'] ?? ''));
            if ($logoUrlInput !== '' && !str_contains($logoUrlInput, '..') && !str_contains($logoUrlInput, '\\')) {
                $logoPath = $logoUrlInput;
            }

            if (!empty($_FILES['logo_file']['name']) && ($_FILES['logo_file']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
                $tmp = (string)$_FILES['logo_file']['tmp_name'];
                $ext = strtolower(pathinfo((string)$_FILES['logo_file']['name'], PATHINFO_EXTENSION));
                if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true) && is_uploaded_file($tmp) && @getimagesize($tmp) !== false) {
                    $dir = __DIR__ . '/../assets/images/brands';
                    if (!is_dir($dir)) {
                        @mkdir($dir, 0775, true);
                    }
                    $fileName = 'brand_' . bin2hex(random_bytes(5)) . '.' . $ext;
                    if (move_uploaded_file($tmp, $dir . '/' . $fileName)) {
                        $logoPath = 'assets/images/brands/' . $fileName;
                    }
                }
            }

            if ($name === '') {
                $error = 'Tên thương hiệu không được để trống.';
            } else {
                $baseSlug = $rawSlug !== '' ? adminBrandSlugify($rawSlug) : adminBrandSlugify($name);
                $slug = $baseSlug;
                $dupChk = $pdo->prepare('SELECT id FROM brands WHERE slug = ? AND id != ? LIMIT 1');
                $dupChk->execute([$slug, $id]);
                if ($dupChk->fetch()) {
                    if ($rawSlug !== '') {
                        $error = 'Slug thương hiệu đã tồn tại, vui lòng chọn slug khác.';
                    } else {
                        $suffix = 1;
                        while (true) {
                            $slug = $baseSlug . '-' . $suffix++;
                            $dupChk->execute([$slug, $id]);
                            if (!$dupChk->fetch()) {
                                break;
                            }
                        }
                    }
                }

                if ($error === '') {
                try {
                    if ($id > 0) {
                        $stmt = $pdo->prepare('UPDATE brands SET name = ?, slug = ?, country = ?, logo = ?, description = ? WHERE id = ?');
                        $stmt->execute([
                            $name,
                            $slug,
                            $country !== '' ? $country : null,
                            $logoPath !== '' ? $logoPath : null,
                            $description !== '' ? $description : null,
                            $id,
                        ]);
                        $pdo->prepare('UPDATE products SET brand = ? WHERE brand_id = ?')->execute([$name, $id]);
                        $message = 'Đã cập nhật thương hiệu #' . $id . '!';
                    } else {
                        $stmt = $pdo->prepare('INSERT INTO brands (name, slug, country, logo, description) VALUES (?, ?, ?, ?, ?)');
                        $stmt->execute([
                            $name,
                            $slug,
                            $country !== '' ? $country : null,
                            $logoPath !== '' ? $logoPath : null,
                            $description !== '' ? $description : null,
                        ]);
                        $message = 'Đã thêm thương hiệu mới!';
                    }
                } catch (PDOException $e) {
                    $error = 'Không thể lưu thương hiệu: ' . $e->getMessage();
                }
                }
            }
        } elseif ($action === 'delete') {
            $id = (int)($_POST['id'] ?? 0);
            if ($id > 0) {
                try {
                    $chk = $pdo->prepare('SELECT COUNT(*) FROM products WHERE brand_id = ?');
                    $chk->execute([$id]);
                    if ((int)$chk->fetchColumn() > 0) {
                        $error = 'Không thể xóa thương hiệu vì vẫn còn sản phẩm liên kết với thương hiệu này.';
                    } else {
                        $stmt = $pdo->prepare('DELETE FROM brands WHERE id = ?');
                        $stmt->execute([$id]);
                        if ($stmt->rowCount() > 0) {
                            $message = 'Đã xóa thương hiệu thành công!';
                        } else {
                            $error = 'Không tìm thấy thương hiệu cần xóa.';
                        }
                    }
                } catch (PDOException $e) {
                    $error = 'Không thể xóa thương hiệu vì đang được sử dụng bởi sản phẩm.';
                }
            }
        }
    }
}

$brands = getBrands();
$prodCountByBrand = [];
if ($pdo) {
    try {
        foreach ($pdo->query('SELECT brand_id, COUNT(*) AS cnt FROM products WHERE brand_id IS NOT NULL GROUP BY brand_id')->fetchAll() as $r) {
            $prodCountByBrand[(int)$r['brand_id']] = (int)$r['cnt'];
        }
    } catch (PDOException $e) {
    }
}

$editBrand = null;
if (isset($_GET['edit'])) {
    $editId = (int)$_GET['edit'];
    foreach ($brands as $b) {
        if ((int)$b['id'] === $editId) {
            $editBrand = $b;
            break;
        }
    }
}

renderAdminHeader('Quản lý Thương hiệu (Hãng)', 'brands', $adminUser);
?>

<?php if (isStaff()): ?>
    <div class="bg-amber-50 border border-amber-300 text-amber-900 text-xs rounded-xl p-3.5 mb-4">
        <i class="fa-solid fa-lock text-amber-600 mr-1"></i>
        <strong>Quyền Nhân viên:</strong> Mọi thao tác thêm, sửa, xóa thương hiệu đều yêu cầu nhập đúng <strong>Mật khẩu Admin</strong>.
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
                <?= $editBrand ? 'Sửa thương hiệu #' . (int)$editBrand['id'] : 'Thêm thương hiệu mới' ?>
            </h2>
            <?php if ($editBrand): ?>
                <a href="brands.php" class="text-xs font-bold text-blue-600 hover:underline">+ Thêm mới</a>
            <?php endif; ?>
        </div>

        <form method="POST" action="brands.php" enctype="multipart/form-data" class="space-y-3 text-xs">
            <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
            <input type="hidden" name="action" value="save">
            <input type="hidden" name="id" value="<?= (int)($editBrand['id'] ?? 0) ?>">
            <input type="hidden" name="existing_logo" value="<?= e($editBrand['logo'] ?? '') ?>">

            <div>
                <label class="block font-bold mb-1">Tên thương hiệu *</label>
                <input type="text" name="name" required value="<?= e($editBrand['name'] ?? '') ?>" placeholder="ASUS / MSI / Logitech"
                    class="w-full h-9 px-3 rounded-lg border border-gray-300">
            </div>
            <div>
                <label class="block font-bold mb-1">Slug (tự tạo nếu để trống)</label>
                <input type="text" name="slug" value="<?= e($editBrand['slug'] ?? '') ?>" placeholder="asus"
                    class="w-full h-9 px-3 rounded-lg border border-gray-300">
            </div>
            <div>
                <label class="block font-bold mb-1">Quốc gia</label>
                <input type="text" name="country" value="<?= e($editBrand['country'] ?? '') ?>" placeholder="Đài Loan / Mỹ / Việt Nam"
                    class="w-full h-9 px-3 rounded-lg border border-gray-300">
            </div>
            <div>
                <label class="block font-bold mb-1">Tải lên Logo thương hiệu</label>
                <input type="file" name="logo_file" accept="image/*" class="w-full text-[11px]">
            </div>
            <div>
                <label class="block font-bold mb-1">Hoặc URL / đường dẫn Logo</label>
                <input type="text" name="logo_url" value="<?= e($editBrand['logo'] ?? '') ?>" placeholder="assets/images/brands/..."
                    class="w-full h-9 px-3 rounded-lg border border-gray-300">
            </div>
            <div>
                <label class="block font-bold mb-1">Mô tả ngắn</label>
                <textarea name="description" rows="2" class="w-full p-2.5 rounded-lg border border-gray-300"><?= e($editBrand['description'] ?? '') ?></textarea>
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
                    <?= $editBrand ? 'Cập nhật' : 'Thêm thương hiệu' ?>
                </button>
                <?php if ($editBrand): ?>
                    <a href="brands.php" class="px-4 py-2.5 bg-gray-200 rounded-lg font-bold text-gray-700">Hủy</a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <div class="lg:col-span-8 bg-white rounded-xl border border-gray-200 p-4 shadow-sm">
        <h2 class="text-sm font-extrabold uppercase mb-3">Danh sách thương hiệu (<?= count($brands) ?>)</h2>
        <div class="overflow-x-auto">
            <table class="w-full text-xs text-left">
                <thead class="bg-gray-50 text-gray-600 uppercase">
                    <tr>
                        <th class="p-2.5">Thương hiệu</th>
                        <th class="p-2.5">Slug</th>
                        <th class="p-2.5">Quốc gia</th>
                        <th class="p-2.5">Số SP</th>
                        <th class="p-2.5 text-right">Thao tác</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php foreach ($brands as $b): ?>
                        <tr>
                            <td class="p-2.5">
                                <div class="font-bold text-gray-900"><?= e($b['name']) ?></div>
                                <?php if (!empty($b['description'])): ?>
                                    <div class="text-[10px] text-gray-400 line-clamp-1"><?= e($b['description']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td class="p-2.5 text-gray-500"><code><?= e($b['slug']) ?></code></td>
                            <td class="p-2.5 text-gray-600"><?= e($b['country'] ?? '-') ?></td>
                            <td class="p-2.5 font-semibold"><?= (int)($prodCountByBrand[(int)$b['id']] ?? 0) ?></td>
                            <td class="p-2.5 text-right whitespace-nowrap">
                                <a href="brands.php?edit=<?= (int)$b['id'] ?>" class="text-blue-600 hover:underline font-bold mr-2">Sửa</a>
                                <form method="POST" action="brands.php" class="inline" onsubmit="return confirmBrandDelete(this)">
                                    <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= (int)$b['id'] ?>">
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
const IS_STAFF_BRAND = <?= isStaff() ? 'true' : 'false' ?>;
function confirmBrandDelete(form) {
    if (!confirm('Xác nhận xóa thương hiệu này?')) {
        return false;
    }
    if (!IS_STAFF_BRAND) {
        return true;
    }
    const pwInput = form.querySelector('input[name="admin_password"]');
    if (pwInput && !pwInput.value) {
        const pw = prompt('Nhập mật khẩu Admin để xác nhận xóa thương hiệu:');
        if (!pw) {
            return false;
        }
        pwInput.value = pw;
    }
    return true;
}
</script>

<?php renderAdminFooter(); ?>
