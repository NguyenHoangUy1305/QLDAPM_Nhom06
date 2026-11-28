<?php
require_once __DIR__ . '/_auth.php';
$adminUser = requireAdmin();
require_once __DIR__ . '/_layout.php';

$pdo = getPDO();
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();

    if (!$pdo) {
        $error = 'Không thể kết nối cơ sở dữ liệu.';
    } else {
        $action = (string)($_POST['action'] ?? '');

        if ($action === 'create') {
            $name = trim((string)($_POST['name'] ?? ''));
            $email = trim((string)($_POST['email'] ?? ''));
            $password = (string)($_POST['password'] ?? '');
            $phone = preg_replace('/\D+/', '', (string)($_POST['phone'] ?? ''));
            $address = trim((string)($_POST['address'] ?? ''));
            $status = (string)($_POST['status'] ?? 'active');
            if (!in_array($status, ['active', 'inactive'], true)) {
                $status = 'active';
            }

            if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $error = 'Vui lòng nhập họ tên và email hợp lệ.';
            } elseif (strlen($password) < 6) {
                $error = 'Mật khẩu nhân viên phải từ 6 ký tự trở lên.';
            } elseif ($phone !== '' && !preg_match('/^0\d{8,10}$/', $phone)) {
                $error = 'Số điện thoại không hợp lệ (bắt đầu bằng 0 và dài 9-11 số).';
            } else {
                try {
                    $chk = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
                    $chk->execute([$email]);
                    if ($chk->fetch()) {
                        $error = 'Email "' . $email . '" đã tồn tại trong hệ thống.';
                    } else {
                        $hash = password_hash($password, PASSWORD_DEFAULT);
                        $stmt = $pdo->prepare("
                            INSERT INTO users (name, email, password, phone, address, role, status, created_at)
                            VALUES (?, ?, ?, ?, ?, 'staff', ?, NOW())
                        ");
                        $stmt->execute([
                            $name,
                            $email,
                            $hash,
                            $phone !== '' ? $phone : null,
                            $address !== '' ? $address : null,
                            $status,
                        ]);
                        $message = 'Đã thêm tài khoản Nhân viên "' . $name . '" thành công!';
                    }
                } catch (PDOException $e) {
                    $error = 'Không thể tạo tài khoản nhân viên: ' . $e->getMessage();
                }
            }
        } elseif ($action === 'update') {
            $id = (int)($_POST['id'] ?? 0);
            $name = trim((string)($_POST['name'] ?? ''));
            $phone = preg_replace('/\D+/', '', (string)($_POST['phone'] ?? ''));
            $address = trim((string)($_POST['address'] ?? ''));
            $status = (string)($_POST['status'] ?? 'active');
            $newPassword = (string)($_POST['password'] ?? '');
            if (!in_array($status, ['active', 'inactive'], true)) {
                $status = 'active';
            }

            if ($id <= 0 || $id === (int)$adminUser['id']) {
                $error = 'Không thể chỉnh sửa tài khoản này tại màn hình quản lý Nhân viên.';
            } elseif ($name === '') {
                $error = 'Họ tên không được để trống.';
            } elseif ($phone !== '' && !preg_match('/^0\d{8,10}$/', $phone)) {
                $error = 'Số điện thoại không hợp lệ (bắt đầu bằng 0 và dài 9-11 số).';
            } elseif ($newPassword !== '' && strlen($newPassword) < 6) {
                $error = 'Mật khẩu mới phải từ 6 ký tự trở lên.';
            } else {
                try {
                    if ($newPassword !== '') {
                        $hash = password_hash($newPassword, PASSWORD_DEFAULT);
                        $stmt = $pdo->prepare("
                            UPDATE users
                            SET name = ?, phone = ?, address = ?, status = ?, password = ?, updated_at = NOW()
                            WHERE id = ? AND role = 'staff' AND id != ?
                        ");
                        $stmt->execute([
                            $name,
                            $phone !== '' ? $phone : null,
                            $address !== '' ? $address : null,
                            $status,
                            $hash,
                            $id,
                            (int)$adminUser['id'],
                        ]);
                    } else {
                        $stmt = $pdo->prepare("
                            UPDATE users
                            SET name = ?, phone = ?, address = ?, status = ?, updated_at = NOW()
                            WHERE id = ? AND role = 'staff' AND id != ?
                        ");
                        $stmt->execute([
                            $name,
                            $phone !== '' ? $phone : null,
                            $address !== '' ? $address : null,
                            $status,
                            $id,
                            (int)$adminUser['id'],
                        ]);
                    }
                    if ($stmt->rowCount() > 0) {
                        $message = 'Đã cập nhật thông tin nhân viên #' . $id . '!';
                    } else {
                        $error = 'Không tìm thấy nhân viên hoặc không có thay đổi nào.';
                    }
                } catch (PDOException $e) {
                    $error = 'Không thể cập nhật nhân viên.';
                }
            }
        } elseif ($action === 'toggle_status') {
            $id = (int)($_POST['id'] ?? 0);
            $newStatus = (string)($_POST['status'] ?? 'active');
            if (!in_array($newStatus, ['active', 'inactive'], true)) {
                $newStatus = 'active';
            }
            if ($id <= 0 || $id === (int)$adminUser['id']) {
                $error = 'Không thể khóa tài khoản quản trị đang đăng nhập.';
            } else {
                try {
                    $stmt = $pdo->prepare("UPDATE users SET status = ?, updated_at = NOW() WHERE id = ? AND role = 'staff' AND id != ?");
                    $stmt->execute([$newStatus, $id, (int)$adminUser['id']]);
                    $message = $newStatus === 'active' ? 'Đã kích hoạt lại tài khoản nhân viên.' : 'Đã tạm khóa tài khoản nhân viên.';
                } catch (PDOException $e) {
                    $error = 'Không thể đổi trạng thái nhân viên.';
                }
            }
        } elseif ($action === 'delete') {
            $id = (int)($_POST['id'] ?? 0);
            if ($id <= 0 || $id === (int)$adminUser['id']) {
                $error = 'Không thể tự xóa tài khoản quản trị đang đăng nhập.';
            } else {
                try {
                    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ? AND role = 'staff' AND id != ?");
                    $stmt->execute([$id, (int)$adminUser['id']]);
                    if ($stmt->rowCount() > 0) {
                        $message = 'Đã xóa tài khoản nhân viên #' . $id . '!';
                    } else {
                        $error = 'Không thể xóa tài khoản này (chỉ được xóa tài khoản có vai trò Nhân viên).';
                    }
                } catch (PDOException $e) {
                    $error = 'Không thể xóa tài khoản nhân viên do có dữ liệu liên kết.';
                }
            }
        }
    }
}

$staffList = [];
if ($pdo) {
    try {
        $staffList = $pdo->query("
            SELECT u.id, u.name, u.email, u.phone, u.address, u.role, u.status, u.created_at,
                   COUNT(o.id) AS walkin_orders
            FROM users u
            LEFT JOIN orders o ON o.created_by_staff_id = u.id
            WHERE u.role = 'staff'
            GROUP BY u.id
            ORDER BY u.id DESC
        ")->fetchAll();
    } catch (PDOException $e) {
    }
}

$editStaff = null;
if (isset($_GET['edit'])) {
    $editId = (int)$_GET['edit'];
    foreach ($staffList as $s) {
        if ((int)$s['id'] === $editId) {
            $editStaff = $s;
            break;
        }
    }
}

renderAdminHeader('Quản lý Nhân viên (Chỉ Admin)', 'staff', $adminUser);
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
    <div class="lg:col-span-4 bg-white rounded-xl border border-gray-200 p-4 shadow-sm h-fit">
        <div class="flex items-center justify-between pb-2 mb-3 border-b border-gray-100">
            <h2 class="text-sm font-extrabold uppercase">
                <?= $editStaff ? 'Sửa nhân viên #' . (int)$editStaff['id'] : 'Thêm tài khoản Nhân viên' ?>
            </h2>
            <?php if ($editStaff): ?>
                <a href="staff.php" class="text-xs font-bold text-blue-600 hover:underline">+ Thêm mới</a>
            <?php endif; ?>
        </div>

        <form method="POST" action="staff.php" class="space-y-3 text-xs">
            <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
            <input type="hidden" name="action" value="<?= $editStaff ? 'update' : 'create' ?>">
            <?php if ($editStaff): ?>
                <input type="hidden" name="id" value="<?= (int)$editStaff['id'] ?>">
            <?php endif; ?>

            <div>
                <label class="block font-bold mb-1">Họ và tên *</label>
                <input type="text" name="name" required value="<?= e($editStaff['name'] ?? '') ?>" placeholder="Nguyễn Văn Nhân Viên"
                    class="w-full h-9 px-3 rounded-lg border border-gray-300">
            </div>
            <div>
                <label class="block font-bold mb-1">Email đăng nhập *</label>
                <input type="email" name="email" <?= $editStaff ? 'disabled' : 'required' ?> value="<?= e($editStaff['email'] ?? '') ?>" placeholder="nhanvien2@doan6.local"
                    class="w-full h-9 px-3 rounded-lg border border-gray-300 <?= $editStaff ? 'bg-gray-100 text-gray-500' : '' ?>">
            </div>
            <div>
                <label class="block font-bold mb-1">
                    <?= $editStaff ? 'Mật khẩu mới (để trống nếu giữ nguyên)' : 'Mật khẩu * (tối thiểu 6 ký tự)' ?>
                </label>
                <input type="password" name="password" <?= $editStaff ? '' : 'required' ?> minlength="6" placeholder="••••••"
                    class="w-full h-9 px-3 rounded-lg border border-gray-300">
            </div>
            <div>
                <label class="block font-bold mb-1">Số điện thoại</label>
                <input type="text" name="phone" value="<?= e($editStaff['phone'] ?? '') ?>" placeholder="0909123456"
                    class="w-full h-9 px-3 rounded-lg border border-gray-300">
            </div>
            <div>
                <label class="block font-bold mb-1">Địa chỉ</label>
                <input type="text" name="address" value="<?= e($editStaff['address'] ?? '') ?>" placeholder="TP. Hồ Chí Minh"
                    class="w-full h-9 px-3 rounded-lg border border-gray-300">
            </div>
            <div>
                <label class="block font-bold mb-1">Trạng thái</label>
                <select name="status" class="w-full h-9 px-2.5 rounded-lg border border-gray-300">
                    <option value="active" <?= ($editStaff['status'] ?? 'active') === 'active' ? 'selected' : '' ?>>Hoạt động (active)</option>
                    <option value="inactive" <?= ($editStaff['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>Tạm khóa (inactive)</option>
                </select>
            </div>

            <div class="flex gap-2 pt-1">
                <button type="submit" class="flex-1 bg-gvn-red hover:bg-gvn-redHover text-white font-bold py-2.5 rounded-lg uppercase transition">
                    <?= $editStaff ? 'Cập nhật nhân viên' : 'Tạo nhân viên' ?>
                </button>
                <?php if ($editStaff): ?>
                    <a href="staff.php" class="px-4 py-2.5 bg-gray-200 rounded-lg font-bold text-gray-700">Hủy</a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <div class="lg:col-span-8 bg-white rounded-xl border border-gray-200 p-4 shadow-sm">
        <h2 class="text-sm font-extrabold uppercase mb-3">Danh sách Nhân viên (<?= count($staffList) ?>)</h2>
        <div class="overflow-x-auto">
            <table class="w-full text-xs text-left">
                <thead class="bg-gray-50 text-gray-600 uppercase">
                    <tr>
                        <th class="p-2.5">Nhân viên</th>
                        <th class="p-2.5">Liên hệ</th>
                        <th class="p-2.5">Đơn tại quầy</th>
                        <th class="p-2.5">Trạng thái</th>
                        <th class="p-2.5 text-right">Thao tác</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php if (empty($staffList)): ?>
                        <tr><td colspan="5" class="p-4 text-center text-gray-500">Chưa có tài khoản nhân viên nào.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($staffList as $s): ?>
                        <tr>
                            <td class="p-2.5">
                                <div class="font-bold text-gray-900"><?= e($s['name']) ?></div>
                                <div class="text-[11px] text-gray-500"><?= e($s['email']) ?></div>
                            </td>
                            <td class="p-2.5">
                                <div><?= e($s['phone'] ?: '-') ?></div>
                                <div class="text-[10px] text-gray-400 line-clamp-1"><?= e($s['address'] ?: '') ?></div>
                            </td>
                            <td class="p-2.5 font-bold text-sky-700"><?= (int)$s['walkin_orders'] ?> đơn</td>
                            <td class="p-2.5">
                                <?php if ($s['status'] === 'active'): ?>
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800">Hoạt động</span>
                                <?php else: ?>
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-red-100 text-red-700">Đã khóa</span>
                                <?php endif; ?>
                            </td>
                            <td class="p-2.5 text-right whitespace-nowrap space-x-2">
                                <a href="staff.php?edit=<?= (int)$s['id'] ?>" class="text-blue-600 hover:underline font-bold">Sửa</a>
                                <form method="POST" action="staff.php" class="inline">
                                    <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                                    <input type="hidden" name="action" value="toggle_status">
                                    <input type="hidden" name="id" value="<?= (int)$s['id'] ?>">
                                    <input type="hidden" name="status" value="<?= $s['status'] === 'active' ? 'inactive' : 'active' ?>">
                                    <button type="submit" class="text-amber-600 hover:underline font-bold">
                                        <?= $s['status'] === 'active' ? 'Khóa' : 'Mở' ?>
                                    </button>
                                </form>
                                <form method="POST" action="staff.php" class="inline" onsubmit="return confirm('Xác nhận xóa tài khoản nhân viên này?')">
                                    <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= (int)$s['id'] ?>">
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
