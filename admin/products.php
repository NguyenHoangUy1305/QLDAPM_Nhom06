<?php
require_once __DIR__ . '/_auth.php';
$adminUser = requireStaffOrAdmin();
require_once __DIR__ . '/_layout.php';

function adminProductSlugify(string $text): string
{
    $text = trim($text);
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
    $slug = strtolower(strtr($text, $trans));
    $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
    $slug = trim((string)$slug, '-');
    return $slug !== '' ? $slug : 'san-pham-' . bin2hex(random_bytes(3));
}

function handleProductUploadedImages(array $filesInput, array &$uploadErrors = []): array
{
    $saved = [];
    if (empty($filesInput['name'])) {
        return $saved;
    }

    $names = is_array($filesInput['name']) ? $filesInput['name'] : [$filesInput['name']];
    $tmps = is_array($filesInput['tmp_name']) ? $filesInput['tmp_name'] : [$filesInput['tmp_name']];
    $errors = is_array($filesInput['error']) ? $filesInput['error'] : [$filesInput['error']];

    $uploadDir = __DIR__ . '/../assets/images/products';
    if (!is_dir($uploadDir)) {
        @mkdir($uploadDir, 0775, true);
    }

    $allowedExts = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
    foreach ($names as $idx => $origName) {
        $origName = (string)$origName;
        if ($origName === '' || ($errors[$idx] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            continue;
        }
        if (($errors[$idx] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            $uploadErrors[] = 'Tệp ảnh "' . $origName . '" tải lên bị lỗi.';
            continue;
        }
        $tmpName = (string)($tmps[$idx] ?? '');
        if ($tmpName === '' || !is_uploaded_file($tmpName)) {
            $uploadErrors[] = 'Tệp ảnh "' . $origName . '" không hợp lệ.';
            continue;
        }
        $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
        if (!in_array($ext, $allowedExts, true)) {
            $uploadErrors[] = 'Định dạng tệp ảnh "' . $origName . '" không hợp lệ (chỉ chấp nhận JPG, PNG, WEBP, GIF).';
            continue;
        }
        if (@getimagesize($tmpName) === false) {
            $uploadErrors[] = 'Nội dung tệp ảnh "' . $origName . '" không hợp lệ hoặc bị hỏng.';
            continue;
        }
        $fileName = 'sp_' . bin2hex(random_bytes(5)) . '.' . $ext;
        $dest = $uploadDir . '/' . $fileName;
        if (move_uploaded_file($tmpName, $dest)) {
            $saved[] = 'assets/images/products/' . $fileName;
        }
    }
    return $saved;
}

function syncProductPrimaryImage(PDO $pdo, int $productId): void
{
    $stmt = $pdo->prepare('
        SELECT id, image_url, is_primary
        FROM product_images
        WHERE product_id = ?
        ORDER BY is_primary DESC, sort_order ASC, id ASC
    ');
    $stmt->execute([$productId]);
    $imgs = $stmt->fetchAll();
    if (empty($imgs)) {
        return;
    }

    $primary = null;
    foreach ($imgs as $im) {
        if ((int)$im['is_primary'] === 1) {
            $primary = $im;
            break;
        }
    }
    if ($primary === null) {
        $primary = $imgs[0];
        $pdo->prepare('UPDATE product_images SET is_primary = 1 WHERE id = ?')->execute([(int)$primary['id']]);
    }
    $pdo->prepare('UPDATE products SET image = ? WHERE id = ?')->execute([(string)$primary['image_url'], $productId]);
}

$pdo = getPDO();
$categories = getCategories();
$brands = getBrands();
$message = '';
$error = '';

$allowedComponentTypes = ['none', 'cpu', 'mainboard', 'ram', 'vga', 'ssd', 'psu', 'case', 'cooling'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();

    $approver = requireAdminPasswordForStaff();
    if ($approver === null) {
        http_response_code(403);
        $error = 'Nhân viên cần nhập mật khẩu Admin (không được để trống và phải chính xác) để thêm, sửa, xóa hoặc nhập kho sản phẩm.';
    } elseif (!$pdo) {
        $error = 'Không thể kết nối cơ sở dữ liệu.';
    } else {
        $action = (string)($_POST['action'] ?? '');

        if ($action === 'import_stock') {
            $productId = (int)($_POST['product_id'] ?? 0);
            $importQty = (int)($_POST['import_qty'] ?? 0);
            if ($productId <= 0 || $importQty <= 0) {
                $error = 'Vui lòng chọn sản phẩm và nhập số lượng nhập kho lớn hơn 0.';
            } else {
                try {
                    $stmt = $pdo->prepare('UPDATE products SET stock = stock + ? WHERE id = ?');
                    $stmt->execute([$importQty, $productId]);
                    if ($stmt->rowCount() > 0) {
                        $message = 'Đã nhập thêm ' . $importQty . ' sản phẩm vào kho!';
                    } else {
                        $error = 'Không tìm thấy sản phẩm cần nhập kho.';
                    }
                } catch (PDOException $e) {
                    $error = 'Lỗi khi cập nhật tồn kho.';
                }
            }
        } elseif ($action === 'set_primary_image') {
            $productId = (int)($_POST['product_id'] ?? 0);
            $imageId = (int)($_POST['image_id'] ?? 0);
            if ($productId > 0 && $imageId > 0) {
                try {
                    $pdo->beginTransaction();
                    $chk = $pdo->prepare('SELECT image_url FROM product_images WHERE id = ? AND product_id = ?');
                    $chk->execute([$imageId, $productId]);
                    $url = $chk->fetchColumn();
                    if ($url !== false) {
                        $pdo->prepare('UPDATE product_images SET is_primary = 0 WHERE product_id = ?')->execute([$productId]);
                        $pdo->prepare('UPDATE product_images SET is_primary = 1 WHERE id = ?')->execute([$imageId]);
                        $pdo->prepare('UPDATE products SET image = ? WHERE id = ?')->execute([(string)$url, $productId]);
                        $pdo->commit();
                        $message = 'Đã đặt ảnh đại diện chính cho sản phẩm!';
                    } else {
                        $pdo->rollBack();
                        $error = 'Không tìm thấy ảnh thuộc sản phẩm này.';
                    }
                } catch (PDOException $e) {
                    if ($pdo->inTransaction()) {
                        $pdo->rollBack();
                    }
                    $error = 'Không thể cập nhật ảnh đại diện.';
                }
            }
        } elseif ($action === 'delete_image') {
            $productId = (int)($_POST['product_id'] ?? 0);
            $imageId = (int)($_POST['image_id'] ?? 0);
            if ($productId > 0 && $imageId > 0) {
                try {
                    $pdo->beginTransaction();
                    $del = $pdo->prepare('DELETE FROM product_images WHERE id = ? AND product_id = ?');
                    $del->execute([$imageId, $productId]);
                    syncProductPrimaryImage($pdo, $productId);
                    $pdo->commit();
                    $message = 'Đã xóa ảnh khỏi sản phẩm!';
                } catch (PDOException $e) {
                    if ($pdo->inTransaction()) {
                        $pdo->rollBack();
                    }
                    $error = 'Không thể xóa ảnh sản phẩm.';
                }
            }
        } elseif ($action === 'delete') {
            $id = (int)($_POST['id'] ?? 0);
            if ($id > 0) {
                try {
                    $stmt = $pdo->prepare('DELETE FROM products WHERE id = ?');
                    $stmt->execute([$id]);
                    if ($stmt->rowCount() > 0) {
                        $message = 'Đã xóa sản phẩm #' . $id . ' thành công!';
                    } else {
                        $error = 'Sản phẩm không tồn tại hoặc đã bị xóa.';
                    }
                } catch (PDOException $e) {
                    $error = 'Không thể xóa sản phẩm vì đã phát sinh dữ liệu trong đơn hàng hoặc bảo hành.';
                }
            }
        } elseif ($action === 'save') {
            $id = (int)($_POST['id'] ?? 0);
            $name = trim((string)($_POST['name'] ?? ''));
            $rawSlug = trim((string)($_POST['slug'] ?? ''));
            $categoryId = (int)($_POST['category_id'] ?? 0);
            $brandId = (int)($_POST['brand_id'] ?? 0);
            $rawPrice = (int)($_POST['price'] ?? 0);
            $rawOldPrice = (int)($_POST['old_price'] ?? 0);
            $rawStock = (int)($_POST['stock'] ?? 0);
            $price = max(0, $rawPrice);
            $oldPrice = max(0, $rawOldPrice);
            $stock = max(0, $rawStock);
            $warrantyMonths = max(1, (int)($_POST['warranty_months'] ?? 24));
            $specsStr = trim((string)($_POST['specs'] ?? ''));
            $description = trim((string)($_POST['description'] ?? ''));
            $promo = trim((string)($_POST['promo'] ?? ''));
            $isFeatured = isset($_POST['is_featured']) ? 1 : 0;
            $isFlashSale = isset($_POST['is_flash_sale']) ? 1 : 0;
            $componentType = (string)($_POST['component_type'] ?? 'none');
            if (!in_array($componentType, $allowedComponentTypes, true)) {
                $componentType = 'none';
            }
            $socket = trim((string)($_POST['socket'] ?? ''));
            $ramType = trim((string)($_POST['ram_type'] ?? ''));
            $tdpWatt = max(0, (int)($_POST['tdp_watt'] ?? 0));
            $psuWatt = max(0, (int)($_POST['psu_watt'] ?? 0));

            $categorySlug = 'laptop';
            foreach ($categories as $c) {
                if ((int)$c['id'] === $categoryId) {
                    $categorySlug = (string)$c['slug'];
                    break;
                }
            }
            if ($categoryId <= 0 && !empty($categories)) {
                $categoryId = (int)$categories[0]['id'];
                $categorySlug = (string)$categories[0]['slug'];
            }

            $brandName = trim((string)($_POST['brand_custom'] ?? ''));
            $brandIdDb = null;
            foreach ($brands as $b) {
                if ((int)$b['id'] === $brandId) {
                    $brandIdDb = (int)$b['id'];
                    $brandName = (string)$b['name'];
                    break;
                }
            }
            if ($brandName === '') {
                $brandName = 'ĐOÀN 6';
            }

            if ($name === '') {
                $error = 'Tên sản phẩm không được để trống.';
            } elseif ($rawPrice < 0 || $rawStock < 0 || $rawOldPrice < 0) {
                $error = 'Giá bán và số lượng tồn kho không hợp lệ (không được âm).';
            } elseif ($categoryId <= 0) {
                $error = 'Vui lòng chọn danh mục hợp lệ.';
            } else {
                $discount = ($oldPrice > $price && $oldPrice > 0)
                    ? (int)round((($oldPrice - $price) / $oldPrice) * 100)
                    : 0;

                $baseSlug = $rawSlug !== '' ? adminProductSlugify($rawSlug) : adminProductSlugify($name);
                $slug = $baseSlug;
                $suffix = 1;
                while (true) {
                    $chk = $pdo->prepare('SELECT id FROM products WHERE slug = ? AND id != ? LIMIT 1');
                    $chk->execute([$slug, $id]);
                    if (!$chk->fetch()) {
                        break;
                    }
                    $slug = $baseSlug . '-' . $suffix++;
                }

                $uploadErrors = [];
                $newImages = [];
                if (!empty($_FILES['image_file']['name'])) {
                    $newImages = array_merge($newImages, handleProductUploadedImages($_FILES['image_file'], $uploadErrors));
                }
                if (!empty($_FILES['gallery_images'])) {
                    $newImages = array_merge($newImages, handleProductUploadedImages($_FILES['gallery_images'], $uploadErrors));
                }
                if (!empty($_FILES['images'])) {
                    $newImages = array_merge($newImages, handleProductUploadedImages($_FILES['images'], $uploadErrors));
                }
                if (!empty($uploadErrors)) {
                    $error = implode(' ', $uploadErrors);
                } else {

                $imageUrlsRaw = trim((string)($_POST['image_urls'] ?? ''));
                if ($imageUrlsRaw !== '') {
                    $lines = preg_split('/\r\n|\r|\n/', $imageUrlsRaw);
                    foreach ($lines as $line) {
                        $u = trim((string)$line);
                        if ($u === '' || str_contains($u, '..') || str_contains($u, '\\')) {
                            continue;
                        }
                        if (preg_match('#^(https?://|assets/)#i', $u)) {
                            $newImages[] = $u;
                        }
                    }
                }
                $newImages = array_values(array_unique($newImages));

                $existingImage = trim((string)($_POST['existing_image'] ?? 'assets/images/products/laptop1.jpg'));
                if ($existingImage === '') {
                    $existingImage = 'assets/images/products/laptop1.jpg';
                }
                $primaryImagePath = $newImages[0] ?? $existingImage;

                try {
                    $pdo->beginTransaction();

                    if ($id > 0) {
                        $stmt = $pdo->prepare('
                            UPDATE products
                            SET category_id = ?, brand_id = ?, category_slug = ?, brand = ?, name = ?, slug = ?,
                                price = ?, old_price = ?, discount = ?, stock = ?, specs = ?, description = ?, promo = ?,
                                component_type = ?, socket = ?, ram_type = ?, tdp_watt = ?, psu_watt = ?,
                                warranty_months = ?, is_featured = ?, is_flash_sale = ?, flash_sale = ?
                            WHERE id = ?
                        ');
                        $stmt->execute([
                            $categoryId,
                            $brandIdDb,
                            $categorySlug,
                            $brandName,
                            $name,
                            $slug,
                            $price,
                            $oldPrice,
                            $discount,
                            $stock,
                            $specsStr,
                            $description !== '' ? $description : null,
                            $promo,
                            $componentType,
                            $socket !== '' ? $socket : null,
                            $ramType !== '' ? $ramType : null,
                            $tdpWatt,
                            $psuWatt,
                            $warrantyMonths,
                            $isFeatured,
                            $isFlashSale,
                            $isFlashSale,
                            $id,
                        ]);
                        $targetProductId = $id;
                        $message = 'Đã cập nhật sản phẩm #' . $id . ' thành công!';
                    } else {
                        $stmt = $pdo->prepare('
                            INSERT INTO products (
                                category_id, brand_id, category_slug, brand, name, slug,
                                price, old_price, discount, stock, image, specs, description, promo,
                                fast_delivery, component_type, socket, ram_type, tdp_watt, psu_watt,
                                warranty_months, is_featured, is_flash_sale, flash_sale
                            ) VALUES (
                                ?, ?, ?, ?, ?, ?,
                                ?, ?, ?, ?, ?, ?, ?, ?,
                                1, ?, ?, ?, ?, ?,
                                ?, ?, ?, ?
                            )
                        ');
                        $stmt->execute([
                            $categoryId,
                            $brandIdDb,
                            $categorySlug,
                            $brandName,
                            $name,
                            $slug,
                            $price,
                            $oldPrice,
                            $discount,
                            $stock,
                            $primaryImagePath,
                            $specsStr,
                            $description !== '' ? $description : null,
                            $promo,
                            $componentType,
                            $socket !== '' ? $socket : null,
                            $ramType !== '' ? $ramType : null,
                            $tdpWatt,
                            $psuWatt,
                            $warrantyMonths,
                            $isFeatured,
                            $isFlashSale,
                            $isFlashSale,
                        ]);
                        $targetProductId = (int)$pdo->lastInsertId();
                        $message = 'Đã thêm sản phẩm mới #' . $targetProductId . ' thành công!';
                    }

                    if (!empty($newImages)) {
                        $maxSortStmt = $pdo->prepare('SELECT COALESCE(MAX(sort_order), -1) FROM product_images WHERE product_id = ?');
                        $maxSortStmt->execute([$targetProductId]);
                        $sortOrder = ((int)$maxSortStmt->fetchColumn()) + 1;

                        $hasPrimaryStmt = $pdo->prepare('SELECT COUNT(*) FROM product_images WHERE product_id = ? AND is_primary = 1');
                        $hasPrimaryStmt->execute([$targetProductId]);
                        $hasPrimary = ((int)$hasPrimaryStmt->fetchColumn()) > 0;

                        $imgIns = $pdo->prepare('INSERT INTO product_images (product_id, image_url, is_primary, sort_order) VALUES (?, ?, ?, ?)');
                        foreach ($newImages as $imgUrl) {
                            $makePrimary = !$hasPrimary ? 1 : 0;
                            $imgIns->execute([$targetProductId, $imgUrl, $makePrimary, $sortOrder++]);
                            if ($makePrimary === 1) {
                                $hasPrimary = true;
                            }
                        }
                        syncProductPrimaryImage($pdo, $targetProductId);
                    } elseif ($id === 0) {
                        $pdo->prepare('INSERT INTO product_images (product_id, image_url, is_primary, sort_order) VALUES (?, ?, 1, 0)')
                            ->execute([$targetProductId, $primaryImagePath]);
                    }

                    $pdo->commit();
                } catch (PDOException $e) {
                    if ($pdo->inTransaction()) {
                        $pdo->rollBack();
                    }
                    $error = 'Không thể lưu sản phẩm: ' . $e->getMessage();
                }
                }
            }
        }
    }
}

$filterQ = trim((string)($_GET['q'] ?? ''));
$filterCat = (int)($_GET['category_id'] ?? 0);
$filterBrand = (int)($_GET['brand_id'] ?? 0);

$allProducts = getAllProducts();
$products = array_values(array_filter($allProducts, static function (array $p) use ($filterQ, $filterCat, $filterBrand): bool {
    if ($filterCat > 0 && (int)($p['category_id'] ?? 0) !== $filterCat) {
        return false;
    }
    if ($filterBrand > 0 && (int)($p['brand_id'] ?? 0) !== $filterBrand) {
        return false;
    }
    if ($filterQ !== '') {
        $hay = mb_strtolower($p['name'] . ' ' . $p['brand'] . ' ' . $p['slug'], 'UTF-8');
        if (!str_contains($hay, mb_strtolower($filterQ, 'UTF-8'))) {
            return false;
        }
    }
    return true;
}));

$editProduct = null;
$editImages = [];
if (isset($_GET['edit'])) {
    $editId = (int)$_GET['edit'];
    foreach ($allProducts as $p) {
        if ((int)$p['id'] === $editId) {
            $editProduct = $p;
            $editImages = getProductImages($editId);
            break;
        }
    }
}

renderAdminHeader('Quản lý Sản phẩm & Nhập kho', 'products', $adminUser);
?>

<?php if (isStaff()): ?>
    <div class="bg-amber-50 border border-amber-300 text-amber-900 text-xs rounded-xl p-3.5 mb-4 flex items-start gap-2.5">
        <i class="fa-solid fa-lock text-amber-600 mt-0.5"></i>
        <div>
            <strong>Quyền Nhân viên (Xác thực Admin bắt buộc):</strong>
            Bạn không thể tự thêm, sửa, xóa sản phẩm hoặc nhập kho nếu chưa nhập đúng <strong>Mật khẩu Admin</strong> cho từng thao tác.
        </div>
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

<!-- Nhập kho nhanh -->
<?php if (!$editProduct): ?>
<div class="bg-white rounded-xl border border-gray-200 p-4 shadow-sm mb-5">
    <h2 class="text-xs font-extrabold uppercase text-gray-900 mb-3 flex items-center gap-2">
        <i class="fa-solid fa-boxes-packing text-emerald-600"></i> Nhập kho nhanh sản phẩm
    </h2>
    <form method="POST" action="products.php" class="grid grid-cols-1 sm:grid-cols-12 gap-3 text-xs items-end">
        <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
        <input type="hidden" name="action" value="import_stock">
        <div class="<?= isStaff() ? 'sm:col-span-5' : 'sm:col-span-7' ?>">
            <label class="block font-bold mb-1">Chọn sản phẩm cần nhập</label>
            <select name="product_id" required class="w-full h-9 px-3 rounded-lg border border-gray-300">
                <option value="">-- Chọn sản phẩm --</option>
                <?php foreach ($allProducts as $p): ?>
                    <option value="<?= (int)$p['id'] ?>">[#<?= (int)$p['id'] ?>] <?= e($p['name']) ?> (Tồn: <?= (int)$p['stock'] ?>)</option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="sm:col-span-2">
            <label class="block font-bold mb-1">Số lượng nhập +</label>
            <input type="number" name="import_qty" min="1" value="10" required class="w-full h-9 px-3 rounded-lg border border-gray-300">
        </div>
        <?php if (isStaff()): ?>
            <div class="sm:col-span-3">
                <label class="block font-bold mb-1 text-amber-800">Mật khẩu Admin *</label>
                <input type="password" name="admin_password" required placeholder="Nhập mật khẩu Admin"
                    class="w-full h-9 px-3 rounded-lg border border-amber-400 bg-amber-50">
            </div>
        <?php endif; ?>
        <div class="<?= isStaff() ? 'sm:col-span-2' : 'sm:col-span-3' ?>">
            <button type="submit" class="w-full h-9 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-lg uppercase transition">
                + Nhập kho
            </button>
        </div>
    </form>
</div>
<?php endif; ?>

<div class="grid grid-cols-1 lg:grid-cols-12 gap-5">
    <!-- Form Thêm / Sửa -->
    <div class="lg:col-span-5 bg-white rounded-xl border border-gray-200 p-4 shadow-sm h-fit space-y-4">
        <div class="flex items-center justify-between pb-2 border-b border-gray-100">
            <h2 class="text-sm font-extrabold uppercase">
                <?= $editProduct ? 'Sửa sản phẩm #' . (int)$editProduct['id'] : 'Thêm sản phẩm mới' ?>
            </h2>
            <?php if ($editProduct): ?>
                <a href="products.php" class="text-xs font-bold text-blue-600 hover:underline">+ Thêm mới</a>
            <?php endif; ?>
        </div>

        <form method="POST" action="products.php<?= $editProduct ? '?edit=' . (int)$editProduct['id'] : '' ?>" enctype="multipart/form-data" class="space-y-3 text-xs">
            <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
            <input type="hidden" name="action" value="save">
            <input type="hidden" name="id" value="<?= (int)($editProduct['id'] ?? 0) ?>">
            <input type="hidden" name="existing_image" value="<?= e($editProduct['image'] ?? 'assets/images/products/laptop1.jpg') ?>">

            <div>
                <label class="block font-bold mb-1">Tên sản phẩm *</label>
                <input type="text" name="name" required value="<?= e($editProduct['name'] ?? '') ?>"
                    class="w-full h-9 px-3 rounded-lg border border-gray-300 focus:outline-none focus:border-gvn-red">
            </div>

            <div>
                <label class="block font-bold mb-1">Slug (tự tạo nếu để trống)</label>
                <input type="text" name="slug" value="<?= e($editProduct['slug'] ?? '') ?>" placeholder="vi-du-laptop-gaming"
                    class="w-full h-9 px-3 rounded-lg border border-gray-300">
            </div>

            <div class="grid grid-cols-2 gap-2">
                <div>
                    <label class="block font-bold mb-1">Danh mục *</label>
                    <select name="category_id" required class="w-full h-9 px-2 rounded-lg border border-gray-300">
                        <?php foreach ($categories as $c): ?>
                            <option value="<?= (int)$c['id'] ?>" <?= (int)($editProduct['category_id'] ?? 0) === (int)$c['id'] ? 'selected' : '' ?>>
                                <?= e($c['name']) ?> (<?= e(strtoupper((string)$c['slug'])) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block font-bold mb-1">Thương hiệu *</label>
                    <select name="brand_id" class="w-full h-9 px-2 rounded-lg border border-gray-300">
                        <option value="0">-- Chọn hãng --</option>
                        <?php foreach ($brands as $b): ?>
                            <option value="<?= (int)$b['id'] ?>" <?= (int)($editProduct['brand_id'] ?? 0) === (int)$b['id'] ? 'selected' : '' ?>>
                                <?= e($b['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-2">
                <div>
                    <label class="block font-bold mb-1">Giá bán (đ) *</label>
                    <input type="number" name="price" min="0" required value="<?= (int)($editProduct['price'] ?? 15000000) ?>"
                        class="w-full h-9 px-3 rounded-lg border border-gray-300">
                </div>
                <div>
                    <label class="block font-bold mb-1">Giá niêm yết (đ)</label>
                    <input type="number" name="old_price" min="0" value="<?= (int)($editProduct['old_price'] ?? 18000000) ?>"
                        class="w-full h-9 px-3 rounded-lg border border-gray-300">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-2">
                <div>
                    <label class="block font-bold mb-1">Tồn kho *</label>
                    <input type="number" name="stock" min="0" required value="<?= (int)($editProduct['stock'] ?? 20) ?>"
                        class="w-full h-9 px-3 rounded-lg border border-gray-300">
                </div>
                <div>
                    <label class="block font-bold mb-1">Bảo hành (tháng)</label>
                    <input type="number" name="warranty_months" min="1" value="<?= (int)($editProduct['warranty_months'] ?? 24) ?>"
                        class="w-full h-9 px-3 rounded-lg border border-gray-300">
                </div>
            </div>

            <div>
                <label class="block font-bold mb-1">Thông số nổi bật (cách nhau dấu phẩy)</label>
                <input type="text" name="specs" value="<?= e(isset($editProduct['specs']) ? implode(',', (array)$editProduct['specs']) : '') ?>"
                    placeholder="i5-13420H,RTX 3050,16GB RAM,512GB SSD"
                    class="w-full h-9 px-3 rounded-lg border border-gray-300">
            </div>

            <div>
                <label class="block font-bold mb-1">Khuyến mãi kèm theo</label>
                <input type="text" name="promo" value="<?= e($editProduct['promo'] ?? '') ?>" placeholder="Ví dụ: Tặng Balo Gaming"
                    class="w-full h-9 px-3 rounded-lg border border-gray-300">
            </div>

            <div>
                <label class="block font-bold mb-1">Mô tả chi tiết</label>
                <textarea name="description" rows="2" class="w-full p-2.5 rounded-lg border border-gray-300"><?= e($editProduct['description'] ?? '') ?></textarea>
            </div>

            <!-- Thông số PC Builder -->
            <div class="bg-gray-50 border border-gray-200 rounded-lg p-3 space-y-2">
                <div class="font-extrabold text-[11px] uppercase text-gray-700">Thông số PC Builder (nếu là linh kiện)</div>
                <div class="grid grid-cols-3 gap-2">
                    <div>
                        <label class="block text-[11px] font-semibold mb-1">Loại linh kiện</label>
                        <select name="component_type" class="w-full h-8 px-2 rounded border border-gray-300">
                            <?php foreach ($allowedComponentTypes as $ct): ?>
                                <option value="<?= e($ct) ?>" <?= ($editProduct['component_type'] ?? 'none') === $ct ? 'selected' : '' ?>><?= e($ct) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-[11px] font-semibold mb-1">Socket</label>
                        <input type="text" name="socket" value="<?= e($editProduct['socket'] ?? '') ?>" placeholder="LGA1700 / AM5"
                            class="w-full h-8 px-2 rounded border border-gray-300">
                    </div>
                    <div>
                        <label class="block text-[11px] font-semibold mb-1">Chuẩn RAM</label>
                        <input type="text" name="ram_type" value="<?= e($editProduct['ram_type'] ?? '') ?>" placeholder="DDR4 / DDR5"
                            class="w-full h-8 px-2 rounded border border-gray-300">
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="block text-[11px] font-semibold mb-1">TDP (Watt)</label>
                        <input type="number" name="tdp_watt" min="0" value="<?= (int)($editProduct['tdp_watt'] ?? 0) ?>"
                            class="w-full h-8 px-2 rounded border border-gray-300">
                    </div>
                    <div>
                        <label class="block text-[11px] font-semibold mb-1">Công suất nguồn PSU (W)</label>
                        <input type="number" name="psu_watt" min="0" value="<?= (int)($editProduct['psu_watt'] ?? 0) ?>"
                            class="w-full h-8 px-2 rounded border border-gray-300">
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-4">
                <label class="inline-flex items-center gap-1.5 font-semibold">
                    <input type="checkbox" name="is_featured" value="1" <?= !empty($editProduct['is_featured']) ? 'checked' : '' ?>> Nổi bật
                </label>
                <label class="inline-flex items-center gap-1.5 font-semibold">
                    <input type="checkbox" name="is_flash_sale" value="1" <?= !empty($editProduct['is_flash_sale']) ? 'checked' : '' ?>> Flash Sale
                </label>
            </div>

            <!-- Đa ảnh -->
            <div class="border-t border-gray-100 pt-2 space-y-2">
                <div>
                    <label class="block font-bold mb-1">Ảnh chính sản phẩm (JPG/PNG/WEBP)</label>
                    <input type="file" name="image_file" accept="image/*" class="w-full text-[11px]">
                </div>
                <div>
                    <label class="block font-bold mb-1">Thư viện nhiều ảnh phụ (chọn nhiều ảnh)</label>
                    <input type="file" name="gallery_images[]" multiple accept="image/*" class="w-full text-[11px]">
                </div>
                <div>
                    <label class="block font-bold mb-1">Hoặc nhập đường dẫn/URL ảnh (mỗi dòng 1 ảnh)</label>
                    <textarea name="image_urls" rows="2" placeholder="assets/images/products/pc1.jpg"
                        class="w-full p-2 rounded-lg border border-gray-300 text-[11px]"></textarea>
                </div>
            </div>

            <?php if (isStaff()): ?>
                <div class="bg-amber-50 border border-amber-300 rounded-lg p-2.5">
                    <label class="block font-bold text-amber-900 mb-1">Mật khẩu Admin xác thực *</label>
                    <input type="password" name="admin_password" required placeholder="Nhập mật khẩu Admin để lưu"
                        class="w-full h-9 px-3 rounded-lg border border-amber-400 bg-white">
                </div>
            <?php endif; ?>

            <div class="flex gap-2 pt-1">
                <button type="submit" class="flex-1 bg-gvn-red hover:bg-gvn-redHover text-white font-bold py-2.5 rounded-lg uppercase transition">
                    <?= $editProduct ? 'Cập nhật sản phẩm' : 'Thêm sản phẩm' ?>
                </button>
                <?php if ($editProduct): ?>
                    <a href="products.php" class="px-4 py-2.5 bg-gray-200 rounded-lg font-bold text-gray-700">Hủy</a>
                <?php endif; ?>
            </div>
        </form>

        <?php if ($editProduct && !empty($editImages)): ?>
            <div class="border-t border-gray-200 pt-3">
                <div class="text-xs font-extrabold uppercase mb-2">Thư viện ảnh sản phẩm (<?= count($editImages) ?> ảnh)</div>
                <div class="grid grid-cols-3 gap-2">
                    <?php foreach ($editImages as $img): ?>
                        <?php $imgSrc = preg_match('#^https?://#i', $img['image_url']) ? $img['image_url'] : '../' . ltrim($img['image_url'], '/'); ?>
                        <div class="border rounded-lg p-1.5 bg-gray-50 flex flex-col gap-1.5 text-[10px]">
                            <img src="<?= e($imgSrc) ?>" alt="" class="w-full h-16 object-cover rounded">
                            <?php if ((int)$img['id'] > 0): ?>
                                <?php if ((int)$img['is_primary'] === 1): ?>
                                    <span class="text-center bg-emerald-600 text-white font-bold py-0.5 rounded">Ảnh chính</span>
                                <?php else: ?>
                                    <form method="POST" action="products.php?edit=<?= (int)$editProduct['id'] ?>" class="staff-pw-form">
                                        <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                                        <input type="hidden" name="action" value="set_primary_image">
                                        <input type="hidden" name="product_id" value="<?= (int)$editProduct['id'] ?>">
                                        <input type="hidden" name="image_id" value="<?= (int)$img['id'] ?>">
                                        <input type="hidden" name="admin_password" value="">
                                        <button type="submit" class="w-full bg-blue-600 text-white font-bold py-0.5 rounded">Đặt làm chính</button>
                                    </form>
                                <?php endif; ?>
                                <form method="POST" action="products.php?edit=<?= (int)$editProduct['id'] ?>" class="staff-pw-form" onsubmit="return confirmStaffForm(this, 'Xóa ảnh này?')">
                                    <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                                    <input type="hidden" name="action" value="delete_image">
                                    <input type="hidden" name="product_id" value="<?= (int)$editProduct['id'] ?>">
                                    <input type="hidden" name="image_id" value="<?= (int)$img['id'] ?>">
                                    <input type="hidden" name="admin_password" value="">
                                    <button type="submit" class="w-full bg-red-100 text-red-700 font-bold py-0.5 rounded">Xóa ảnh</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Danh sách sản phẩm -->
    <div class="lg:col-span-7 bg-white rounded-xl border border-gray-200 p-4 shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-2 mb-3">
            <h2 class="text-sm font-extrabold uppercase">Danh sách sản phẩm (<?= count($products) ?>)</h2>
            <form method="GET" action="products.php" class="flex flex-wrap gap-2 text-xs">
                <input type="text" name="q" value="<?= e($filterQ) ?>" placeholder="Tìm tên / hãng..."
                    class="h-8 px-2.5 rounded-lg border border-gray-300">
                <select name="category_id" class="h-8 px-2 rounded-lg border border-gray-300">
                    <option value="0">Tất cả danh mục</option>
                    <?php foreach ($categories as $c): ?>
                        <option value="<?= (int)$c['id'] ?>" <?= $filterCat === (int)$c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <select name="brand_id" class="h-8 px-2 rounded-lg border border-gray-300">
                    <option value="0">Tất cả hãng</option>
                    <?php foreach ($brands as $b): ?>
                        <option value="<?= (int)$b['id'] ?>" <?= $filterBrand === (int)$b['id'] ? 'selected' : '' ?>><?= e($b['name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="h-8 px-3 bg-black text-white font-bold rounded-lg">Lọc</button>
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-xs text-left">
                <thead class="bg-gray-50 text-gray-600 uppercase">
                    <tr>
                        <th class="p-2.5">Ảnh</th>
                        <th class="p-2.5">Tên sản phẩm</th>
                        <th class="p-2.5">Hãng / DM</th>
                        <th class="p-2.5">Kho</th>
                        <th class="p-2.5">Giá bán</th>
                        <th class="p-2.5 text-right">Thao tác</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php foreach ($products as $p): ?>
                        <?php $thumb = preg_match('#^https?://#i', $p['image']) ? $p['image'] : '../' . ltrim($p['image'], '/'); ?>
                        <tr>
                            <td class="p-2.5">
                                <img src="<?= e($thumb) ?>" alt="" class="w-11 h-11 object-cover rounded border border-gray-200">
                            </td>
                            <td class="p-2.5 font-semibold max-w-[230px]">
                                <div class="line-clamp-2"><?= e($p['name']) ?></div>
                                <div class="text-[10px] text-gray-400">
                                    BH <?= (int)$p['warranty_months'] ?>T
                                    <?php if (($p['component_type'] ?? 'none') !== 'none'): ?>
                                        · <span class="text-purple-700 font-bold"><?= e(strtoupper($p['component_type'])) ?></span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td class="p-2.5 text-gray-600">
                                <div class="font-bold"><?= e($p['brand']) ?></div>
                                <div class="text-[10px] text-gray-400"><?= e($p['category_name'] ?: $p['category_slug']) ?></div>
                            </td>
                            <td class="p-2.5 font-bold <?= (int)$p['stock'] > 5 ? 'text-emerald-600' : ((int)$p['stock'] > 0 ? 'text-amber-600' : 'text-red-600') ?>">
                                <?= (int)$p['stock'] ?>
                            </td>
                            <td class="p-2.5 font-bold text-gvn-red"><?= formatPrice((int)$p['price']) ?></td>
                            <td class="p-2.5 text-right whitespace-nowrap">
                                <a href="products.php?edit=<?= (int)$p['id'] ?>" class="text-blue-600 hover:underline font-bold mr-2">Sửa</a>
                                <form method="POST" action="products.php" class="inline staff-pw-form" onsubmit="return confirmStaffForm(this, 'Xác nhận xóa sản phẩm này?')">
                                    <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
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
const IS_STAFF = <?= isStaff() ? 'true' : 'false' ?>;
function confirmStaffForm(form, msg) {
    if (msg && !confirm(msg)) {
        return false;
    }
    if (!IS_STAFF) {
        return true;
    }
    const pwInput = form.querySelector('input[name="admin_password"]');
    if (pwInput && !pwInput.value) {
        const entered = prompt('Thao tác này yêu cầu mật khẩu Admin xác nhận:');
        if (!entered) {
            return false;
        }
        pwInput.value = entered;
    }
    return true;
}
document.querySelectorAll('form.staff-pw-form').forEach(f => {
    if (!f.getAttribute('onsubmit')) {
        f.addEventListener('submit', function(e) {
            if (!confirmStaffForm(this, '')) {
                e.preventDefault();
            }
        });
    }
});
</script>

<?php renderAdminFooter(); ?>
