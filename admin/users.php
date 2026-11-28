<?php
require_once __DIR__ . '/_auth.php';
$adminUser = requireStaffOrAdmin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    http_response_code(405);
    header('Content-Type: text/html; charset=utf-8');
    echo '<!DOCTYPE html><html lang="vi"><head><meta charset="UTF-8"><title>405 Method Not Allowed</title></head>'
        . '<body style="font-family:sans-serif;padding:2rem;"><h1>405 - Không hỗ trợ thêm/sửa khách hàng</h1>'
        . '<p>Trang Khách hàng & Hồ sơ chỉ hỗ trợ tra cứu và xem chi tiết, không cho phép thêm mới hoặc chỉnh sửa khách hàng từ bảng quản trị.</p>'
        . '<p><a href="users.php">Quay lại danh sách</a></p></body></html>';
    exit;
}

require_once __DIR__ . '/_layout.php';

$pdo = getPDO();
$roleFilter = (string)($_GET['role'] ?? 'all');
if (isStaff()) {
    $roleFilter = 'customer';
} elseif (!in_array($roleFilter, ['all', 'customer', 'staff', 'admin'], true)) {
    $roleFilter = 'all';
}

$q = trim((string)($_GET['q'] ?? ''));
$viewId = (int)($_GET['view'] ?? 0);

$users = [];
$viewUser = null;
$viewOrders = [];

if ($pdo) {
    try {
        $sql = "
            SELECT u.id, u.name, u.email, u.phone, u.address, u.role, u.status, u.created_at,
                   COUNT(DISTINCT o.id) AS order_count,
                   COALESCE(SUM(CASE WHEN o.status != 'Đã hủy' THEN o.total_amount ELSE 0 END), 0) AS total_spent,
                   MAX(o.created_at) AS last_order_at,
                   (SELECT COUNT(*) FROM orders os WHERE os.created_by_staff_id = u.id) AS staff_created_count
            FROM users u
            LEFT JOIN orders o ON o.user_id = u.id
            WHERE 1=1
        ";
        $params = [];
        if ($roleFilter !== 'all') {
            $sql .= ' AND u.role = ?';
            $params[] = $roleFilter;
        }
        if ($q !== '') {
            $like = '%' . $q . '%';
            $sql .= ' AND (u.name LIKE ? OR u.email LIKE ? OR u.phone LIKE ?)';
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }
        $sql .= ' GROUP BY u.id ORDER BY u.id DESC';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $users = $stmt->fetchAll();

        if ($viewId > 0) {
            $uStmt = $pdo->prepare('SELECT id, name, email, phone, address, role, status, created_at FROM users WHERE id = ? LIMIT 1');
            $uStmt->execute([$viewId]);
            $candidate = $uStmt->fetch();
            if ($candidate && (!isStaff() || $candidate['role'] === 'customer')) {
                $viewUser = $candidate;
                $oStmt = $pdo->prepare('
                    SELECT id, order_code, order_type, total_amount, payment_method, payment_status, status, created_at
                    FROM orders
                    WHERE user_id = ? OR created_by_staff_id = ?
                    ORDER BY id DESC
                    LIMIT 20
                ');
                $oStmt->execute([$viewId, $viewId]);
                $viewOrders = $oStmt->fetchAll();
            }
        }
    } catch (PDOException $e) {
    }
}

renderAdminHeader('Khách hàng & Hồ sơ tài khoản (Chỉ xem)', 'users', $adminUser);
?>

<div class="bg-blue-50 border border-blue-200 text-blue-900 text-xs rounded-xl p-3.5 mb-4 flex items-center justify-between gap-3">
    <div>
        <i class="fa-solid fa-eye mr-1 text-blue-600"></i>
        <strong>Chế độ chỉ xem:</strong> Danh sách dùng để tra cứu thông tin khách hàng<?= isAdmin() ? ' và nhân viên' : '' ?> cùng lịch sử đơn hàng. Khách hàng tự đăng ký tài khoản ngoài trang chủ hoặc mua tại quầy không cần tạo tài khoản.
    </div>
</div>

<div class="bg-white rounded-xl border border-gray-200 p-4 shadow-sm mb-5">
    <form method="GET" action="users.php" class="flex flex-wrap items-center justify-between gap-3 text-xs">
        <div class="flex flex-wrap gap-1.5">
            <?php if (isAdmin()): ?>
                <a href="users.php?role=all&q=<?= urlencode($q) ?>" class="px-3 py-1.5 rounded-lg font-bold <?= $roleFilter === 'all' ? 'bg-gvn-red text-white' : 'bg-gray-100 text-gray-700' ?>">Tất cả</a>
                <a href="users.php?role=customer&q=<?= urlencode($q) ?>" class="px-3 py-1.5 rounded-lg font-bold <?= $roleFilter === 'customer' ? 'bg-gvn-red text-white' : 'bg-gray-100 text-gray-700' ?>">Khách hàng</a>
                <a href="users.php?role=staff&q=<?= urlencode($q) ?>" class="px-3 py-1.5 rounded-lg font-bold <?= $roleFilter === 'staff' ? 'bg-gvn-red text-white' : 'bg-gray-100 text-gray-700' ?>">Nhân viên</a>
                <a href="users.php?role=admin&q=<?= urlencode($q) ?>" class="px-3 py-1.5 rounded-lg font-bold <?= $roleFilter === 'admin' ? 'bg-gvn-red text-white' : 'bg-gray-100 text-gray-700' ?>">Admin</a>
            <?php else: ?>
                <span class="px-3 py-1.5 rounded-lg font-bold bg-gvn-red text-white">Khách hàng</span>
            <?php endif; ?>
        </div>
        <div class="flex gap-2">
            <?php if (isAdmin()): ?>
                <input type="hidden" name="role" value="<?= e($roleFilter) ?>">
            <?php endif; ?>
            <input type="text" name="q" value="<?= e($q) ?>" placeholder="Tìm tên, email, SĐT..."
                class="h-9 px-3 rounded-lg border border-gray-300">
            <button type="submit" class="h-9 px-4 bg-black text-white font-bold rounded-lg">Tìm kiếm</button>
        </div>
    </form>
</div>

<div class="grid grid-cols-1 <?= $viewUser ? 'lg:grid-cols-12' : '' ?> gap-5">
    <div class="<?= $viewUser ? 'lg:col-span-7' : '' ?> bg-white rounded-xl border border-gray-200 p-4 shadow-sm">
        <h2 class="text-sm font-extrabold uppercase mb-3">Danh sách tài khoản (<?= count($users) ?>)</h2>
        <div class="overflow-x-auto">
            <table class="w-full text-xs text-left">
                <thead class="bg-gray-50 text-gray-600 uppercase">
                    <tr>
                        <th class="p-2.5">Họ tên / Email</th>
                        <th class="p-2.5">Vai trò</th>
                        <th class="p-2.5">SĐT</th>
                        <th class="p-2.5">Đơn hàng / Chi tiêu</th>
                        <th class="p-2.5 text-right">Chi tiết</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php if (empty($users)): ?>
                        <tr><td colspan="5" class="p-4 text-center text-gray-500">Không tìm thấy tài khoản phù hợp.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($users as $u): ?>
                        <?php
                        $rLabel = match ($u['role']) {
                            'admin' => 'Admin',
                            'staff' => 'Nhân viên',
                            default => 'Khách hàng',
                        };
                        $rCls = match ($u['role']) {
                            'admin' => 'bg-red-100 text-red-700',
                            'staff' => 'bg-amber-100 text-amber-800',
                            default => 'bg-blue-100 text-blue-800',
                        };
                        ?>
                        <tr>
                            <td class="p-2.5">
                                <div class="font-bold text-gray-900"><?= e($u['name']) ?></div>
                                <div class="text-[11px] text-gray-500"><?= e($u['email']) ?></div>
                            </td>
                            <td class="p-2.5">
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold <?= $rCls ?>"><?= e($rLabel) ?></span>
                            </td>
                            <td class="p-2.5">
                                <div><?= e($u['phone'] ?: '-') ?></div>
                                <?php if (!empty($u['address'])): ?>
                                    <div class="text-[11px] text-gray-500"><?= e($u['address']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td class="p-2.5">
                                <?php if ($u['role'] === 'staff'): ?>
                                    <span class="font-bold text-sky-700"><?= (int)$u['staff_created_count'] ?> đơn tại quầy</span>
                                <?php else: ?>
                                    <div class="font-bold"><?= (int)$u['order_count'] ?> đơn</div>
                                    <div class="text-[11px] text-gvn-red font-bold"><?= formatPrice((int)$u['total_spent']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td class="p-2.5 text-right">
                                <a href="users.php?role=<?= e($roleFilter) ?>&q=<?= urlencode($q) ?>&view=<?= (int)$u['id'] ?>"
                                   class="text-blue-600 hover:underline font-bold">Xem hồ sơ</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php if ($viewUser): ?>
        <div class="lg:col-span-5 bg-white rounded-xl border border-gray-200 p-4 shadow-sm h-fit space-y-4">
            <div class="flex items-center justify-between pb-2 border-b border-gray-100">
                <h2 class="text-sm font-extrabold uppercase">Hồ sơ #<?= (int)$viewUser['id'] ?></h2>
                <a href="users.php?role=<?= e($roleFilter) ?>&q=<?= urlencode($q) ?>" class="text-xs text-gray-500 hover:text-gray-800">Đóng</a>
            </div>
            <div class="text-xs space-y-2 bg-gray-50 rounded-lg p-3">
                <div><strong>Họ tên:</strong> <?= e($viewUser['name']) ?></div>
                <div><strong>Email:</strong> <?= e($viewUser['email']) ?></div>
                <div><strong>Vai trò:</strong> <?= e($viewUser['role']) ?> (<?= e($viewUser['status']) ?>)</div>
                <div><strong>Số điện thoại:</strong> <?= e($viewUser['phone'] ?: 'Chưa cập nhật') ?></div>
                <div><strong>Địa chỉ:</strong> <?= e($viewUser['address'] ?: 'Chưa cập nhật') ?></div>
                <div><strong>Ngày tạo:</strong> <?= e($viewUser['created_at']) ?></div>
            </div>

            <div>
                <h3 class="text-xs font-extrabold uppercase mb-2">Đơn hàng liên quan (<?= count($viewOrders) ?>)</h3>
                <?php if (empty($viewOrders)): ?>
                    <p class="text-xs text-gray-500">Chưa có đơn hàng nào.</p>
                <?php else: ?>
                    <div class="space-y-2 max-h-80 overflow-y-auto">
                        <?php foreach ($viewOrders as $vo): ?>
                            <div class="border border-gray-200 rounded-lg p-2.5 text-xs flex items-center justify-between">
                                <div>
                                    <a href="orders.php?q=<?= e($vo['order_code']) ?>" class="font-bold text-gvn-red hover:underline">
                                        <?= e($vo['order_code']) ?>
                                    </a>
                                    <span class="text-[10px] text-gray-500 ml-1">(<?= e($vo['order_type']) ?>)</span>
                                    <div class="text-[11px] text-gray-500"><?= e($vo['created_at']) ?></div>
                                </div>
                                <div class="text-right">
                                    <div class="font-bold"><?= formatPrice((int)$vo['total_amount']) ?></div>
                                    <div class="text-[10px] font-semibold text-amber-700"><?= e($vo['status']) ?></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php renderAdminFooter(); ?>
