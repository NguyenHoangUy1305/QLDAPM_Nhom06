<?php
require_once __DIR__ . '/_auth.php';
require_once __DIR__ . '/_layout.php';

$adminUser = requireAdmin();
initAdminData();

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $pdo = getPDO();

    if ($action === 'add') {
        $name = trim($_POST['name'] ?? '');
        $slug = trim($_POST['slug'] ?? '');
        $icon = trim($_POST['icon'] ?? 'fa-tag');
        if ($name !== '' && $slug !== '') {
            if ($pdo) {
                try {
                    $stmt = $pdo->prepare('INSERT INTO categories (name, slug, icon) VALUES (?, ?, ?)');
                    $stmt->execute([$name, $slug, $icon]);
                } catch (PDOException $e) {
                }
            }
            $_SESSION['admin_categories'][] = ['name' => $name, 'slug' => $slug, 'icon' => $icon];
            $message = 'Đã thêm danh mục mới!';
        }
    } elseif ($action === 'delete') {
        $slug = $_POST['slug'] ?? '';
        if ($pdo && $slug !== '') {
            try {
                $stmt = $pdo->prepare('DELETE FROM categories WHERE slug = ?');
                $stmt->execute([$slug]);
            } catch (PDOException $e) {
            }
        }
        $_SESSION['admin_categories'] = array_values(array_filter(
            $_SESSION['admin_categories'] ?? [],
            fn($c) => $c['slug'] !== $slug
        ));
        $message = 'Đã xóa danh mục!';
    }
}

$categories = getCategories();
$_SESSION['admin_categories'] = $categories;

renderAdminHeader('Quản lý Danh mục', 'categories', $adminUser);
?>

<?php if ($message !== ''): ?>
    <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-semibold rounded-xl p-3 mb-4">
        <i class="fa-solid fa-circle-check mr-1"></i> <?= e($message) ?>
    </div>
<?php endif; ?>

<div class="grid grid-cols-1 lg:grid-cols-12 gap-5">
    <div class="lg:col-span-4 bg-white rounded-xl border border-gray-200 p-4 shadow-sm h-fit">
        <h2 class="text-sm font-extrabold uppercase mb-3 pb-2 border-b border-gray-100">Thêm danh mục</h2>
        <form method="POST" action="categories.php" class="space-y-3 text-xs">
            <input type="hidden" name="action" value="add">
            <div>
                <label class="block font-bold mb-1">Tên danh mục *</label>
                <input type="text" name="name" required placeholder="Ví dụ: Laptop AI"
                    class="w-full h-9 px-3 rounded-lg border border-gray-300">
            </div>
            <div>
                <label class="block font-bold mb-1">Slug (đường dẫn) *</label>
                <input type="text" name="slug" required placeholder="laptop-ai"
                    class="w-full h-9 px-3 rounded-lg border border-gray-300">
            </div>
            <div>
                <label class="block font-bold mb-1">Icon FontAwesome</label>
                <input type="text" name="icon" value="fa-laptop" class="w-full h-9 px-3 rounded-lg border border-gray-300">
            </div>
            <button type="submit" class="w-full bg-gvn-red hover:bg-gvn-redHover text-white font-bold py-2.5 rounded-lg uppercase transition">
                Thêm danh mục
            </button>
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
                        <th class="p-2.5 text-right">Thao tác</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php foreach ($categories as $c): ?>
                        <tr>
                            <td class="p-2.5"><i class="fa-solid <?= e($c['icon']) ?> text-gray-500"></i></td>
                            <td class="p-2.5 font-bold"><?= e($c['name']) ?></td>
                            <td class="p-2.5 text-gray-500"><code><?= e($c['slug']) ?></code></td>
                            <td class="p-2.5 text-right">
                                <form method="POST" action="categories.php" class="inline" onsubmit="return confirm('Xóa danh mục này?')">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="slug" value="<?= e($c['slug']) ?>">
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
