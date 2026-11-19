<?php
require_once __DIR__ . '/_auth.php';
require_once __DIR__ . '/_layout.php';

$adminUser = requireAdmin();
initAdminData();

$categories = getCategories();
$message = '';

// Xử lý Thêm / Sửa / Xóa sản phẩm (Đồng bộ cả MySQL và Session)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $pdo = getPDO();

    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($pdo && $id > 0) {
            try {
                $stmt = $pdo->prepare('DELETE FROM products WHERE id = ?');
                $stmt->execute([$id]);
            } catch (PDOException $e) {
            }
        }
        $_SESSION['admin_products'] = array_values(array_filter(
            $_SESSION['admin_products'] ?? [],
            fn($p) => (int)$p['id'] !== $id
        ));
        $message = 'Đã xóa sản phẩm thành công!';
    } elseif ($action === 'save') {
        $id = (int)($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $category = $_POST['category'] ?? 'laptop-gaming';
        $brand = trim($_POST['brand'] ?? 'ACER');
        $price = (int)($_POST['price'] ?? 0);
        $oldPrice = (int)($_POST['old_price'] ?? 0);
        $stock = max(0, (int)($_POST['stock'] ?? 20));
        $promo = trim($_POST['promo'] ?? '');
        $specsStr = trim($_POST['specs'] ?? ($brand . ',Chính hãng'));
        $discount = ($oldPrice > $price && $oldPrice > 0) ? (int)round((($oldPrice - $price) / $oldPrice) * 100) : 0;

        $imagePath = trim($_POST['existing_image'] ?? 'assets/images/products/laptop1.jpg');
        if (!empty($_FILES['image_file']['name']) && $_FILES['image_file']['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['image_file']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
                $fileName = 'sp_' . time() . '.' . $ext;
                $dest = __DIR__ . '/../assets/images/products/' . $fileName;
                if (move_uploaded_file($_FILES['image_file']['tmp_name'], $dest)) {
                    $imagePath = 'assets/images/products/' . $fileName;
                }
            }
        }

        if ($id > 0) {
            if ($pdo) {
                try {
                    $stmt = $pdo->prepare('
                        UPDATE products
                        SET name = ?, category_slug = ?, brand = ?, image = ?, old_price = ?, price = ?, discount = ?, specs = ?, promo = ?, stock = ?
                        WHERE id = ?
                    ');
                    $stmt->execute([$name, $category, $brand, $imagePath, $oldPrice, $price, $discount, $specsStr, $promo, $stock, $id]);
                } catch (PDOException $e) {
                }
            }
            if (!empty($_SESSION['admin_products'])) {
                foreach ($_SESSION['admin_products'] as &$p) {
                    if ((int)$p['id'] === $id) {
                        $p['name'] = $name;
                        $p['category'] = $category;
                        $p['brand'] = $brand;
                        $p['price'] = $price;
                        $p['old_price'] = $oldPrice;
                        $p['discount'] = $discount;
                        $p['promo'] = $promo;
                        $p['image'] = $imagePath;
                        $p['stock'] = $stock;
                        $p['specs'] = array_map('trim', explode(',', $specsStr));
                        break;
                    }
                }
                unset($p);
            }
            $message = 'Đã cập nhật sản phẩm vào hệ thống!';
        } else {
            $slug = 'san-pham-' . time();
            $newId = time();
            if ($pdo) {
                try {
                    $stmt = $pdo->prepare('
                        INSERT INTO products (slug, name, category_slug, brand, image, old_price, price, discount, fast_delivery, specs, promo, flash_sale, stock)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1, ?, ?, 0, ?)
                    ');
                    $stmt->execute([$slug, $name, $category, $brand, $imagePath, $oldPrice, $price, $discount, $specsStr, $promo, $stock]);
                    $newId = (int)$pdo->lastInsertId();
                } catch (PDOException $e) {
                }
            }
            if (!isset($_SESSION['admin_products'])) {
                $_SESSION['admin_products'] = [];
            }
            array_unshift($_SESSION['admin_products'], [
                'id' => $newId,
                'slug' => $slug,
                'name' => $name,
                'category' => $category,
                'brand' => $brand,
                'image' => $imagePath,
                'old_price' => $oldPrice,
                'price' => $price,
                'discount' => $discount,
                'fast_delivery' => true,
                'specs' => array_map('trim', explode(',', $specsStr)),
                'promo' => $promo,
                'flash_sale' => false,
                'stock' => $stock,
            ]);
            $message = 'Đã thêm sản phẩm mới vào hệ thống!';
        }
    }
}

$products = getAllProducts();
$_SESSION['admin_products'] = $products;

$editProduct = null;
if (isset($_GET['edit'])) {
    $editId = (int)$_GET['edit'];
    foreach ($products as $p) {
        if ((int)$p['id'] === $editId) {
            $editProduct = $p;
            break;
        }
    }
}

renderAdminHeader('Quản lý Sản phẩm', 'products', $adminUser);
?>

<?php if ($message !== ''): ?>
    <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-semibold rounded-xl p-3 mb-4">
        <i class="fa-solid fa-circle-check mr-1"></i> <?= e($message) ?>
    </div>
<?php endif; ?>

<div class="grid grid-cols-1 lg:grid-cols-12 gap-5">
    <!-- Form Thêm / Sửa -->
    <div class="lg:col-span-4 bg-white rounded-xl border border-gray-200 p-4 shadow-sm h-fit">
        <h2 class="text-sm font-extrabold uppercase mb-3 pb-2 border-b border-gray-100">
            <?= $editProduct ? 'Sửa sản phẩm #' . (int)$editProduct['id'] : 'Thêm sản phẩm mới' ?>
        </h2>
        <form method="POST" action="products.php" enctype="multipart/form-data" class="space-y-3 text-xs">
            <input type="hidden" name="action" value="save">
            <input type="hidden" name="id" value="<?= (int)($editProduct['id'] ?? 0) ?>">
            <input type="hidden" name="existing_image" value="<?= e($editProduct['image'] ?? 'assets/images/products/laptop1.jpg') ?>">

            <div>
                <label class="block font-bold mb-1">Tên sản phẩm *</label>
                <input type="text" name="name" required value="<?= e($editProduct['name'] ?? '') ?>"
                    class="w-full h-9 px-3 rounded-lg border border-gray-300 focus:outline-none focus:border-gvn-red">
            </div>
            <div class="grid grid-cols-2 gap-2">
                <div>
                    <label class="block font-bold mb-1">Danh mục</label>
                    <select name="category" class="w-full h-9 px-2 rounded-lg border border-gray-300">
                        <?php foreach ($categories as $c): ?>
                            <option value="<?= e($c['slug']) ?>" <?= ($editProduct['category'] ?? '') === $c['slug'] ? 'selected' : '' ?>>
                                <?= e($c['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block font-bold mb-1">Hãng / Dòng</label>
                    <input type="text" name="brand" required value="<?= e($editProduct['brand'] ?? 'ACER') ?>"
                        class="w-full h-9 px-3 rounded-lg border border-gray-300">
                </div>
            </div>
            <div class="grid grid-cols-2 gap-2">
                <div>
                    <label class="block font-bold mb-1">Giá bán (đ) *</label>
                    <input type="number" name="price" required value="<?= (int)($editProduct['price'] ?? 15000000) ?>"
                        class="w-full h-9 px-3 rounded-lg border border-gray-300">
                </div>
                <div>
                    <label class="block font-bold mb-1">Giá niêm yết (đ)</label>
                    <input type="number" name="old_price" value="<?= (int)($editProduct['old_price'] ?? 18000000) ?>"
                        class="w-full h-9 px-3 rounded-lg border border-gray-300">
                </div>
            </div>
            <div class="grid grid-cols-2 gap-2">
                <div>
                    <label class="block font-bold mb-1">Tồn kho *</label>
                    <input type="number" name="stock" required value="<?= (int)($editProduct['stock'] ?? 20) ?>"
                        class="w-full h-9 px-3 rounded-lg border border-gray-300">
                </div>
                <div>
                    <label class="block font-bold mb-1">Thông số (cách dấu phẩy)</label>
                    <input type="text" name="specs" value="<?= e(isset($editProduct['specs']) ? implode(',', (array)$editProduct['specs']) : 'i5,16GB RAM,512GB SSD') ?>"
                        class="w-full h-9 px-3 rounded-lg border border-gray-300">
                </div>
            </div>
            <div>
                <label class="block font-bold mb-1">Khuyến mãi kèm theo</label>
                <input type="text" name="promo" value="<?= e($editProduct['promo'] ?? '') ?>" placeholder="Ví dụ: Tặng Balo Gaming"
                    class="w-full h-9 px-3 rounded-lg border border-gray-300">
            </div>
            <div>
                <label class="block font-bold mb-1">Hình ảnh sản phẩm</label>
                <input type="file" name="image_file" accept="image/*" class="w-full text-[11px]">
            </div>
            <div class="flex gap-2 pt-1">
                <button type="submit" class="flex-1 bg-gvn-red hover:bg-gvn-redHover text-white font-bold py-2.5 rounded-lg uppercase transition">
                    <?= $editProduct ? 'Cập nhật' : 'Thêm sản phẩm' ?>
                </button>
                <?php if ($editProduct): ?>
                    <a href="products.php" class="px-4 py-2.5 bg-gray-200 rounded-lg font-bold text-gray-700">Hủy</a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- Danh sách sản phẩm -->
    <div class="lg:col-span-8 bg-white rounded-xl border border-gray-200 p-4 shadow-sm">
        <h2 class="text-sm font-extrabold uppercase mb-3">Danh sách sản phẩm (<?= count($products) ?>)</h2>
        <div class="overflow-x-auto">
            <table class="w-full text-xs text-left">
                <thead class="bg-gray-50 text-gray-600 uppercase">
                    <tr>
                        <th class="p-2.5">Ảnh</th>
                        <th class="p-2.5">Tên sản phẩm</th>
                        <th class="p-2.5">Hãng</th>
                        <th class="p-2.5">Tồn kho</th>
                        <th class="p-2.5">Giá bán</th>
                        <th class="p-2.5 text-right">Thao tác</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php foreach ($products as $p): ?>
                        <tr>
                            <td class="p-2.5">
                                <img src="../<?= e($p['image']) ?>" alt="" class="w-11 h-11 object-cover rounded border border-gray-200">
                            </td>
                            <td class="p-2.5 font-semibold max-w-[240px]">
                                <div class="line-clamp-2"><?= e($p['name']) ?></div>
                            </td>
                            <td class="p-2.5 text-gray-500"><?= e($p['brand']) ?></td>
                            <td class="p-2.5 font-bold <?= ((int)($p['stock'] ?? 20)) > 0 ? 'text-emerald-600' : 'text-red-600' ?>">
                                <?= (int)($p['stock'] ?? 20) ?>
                            </td>
                            <td class="p-2.5 font-bold text-gvn-red"><?= formatPrice((int)$p['price']) ?></td>
                            <td class="p-2.5 text-right whitespace-nowrap">
                                <a href="products.php?edit=<?= (int)$p['id'] ?>" class="text-blue-600 hover:underline font-bold mr-2">Sửa</a>
                                <form method="POST" action="products.php" class="inline" onsubmit="return confirm('Xác nhận xóa sản phẩm này?')">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
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

<?php renderAdminFooter(); ?>
