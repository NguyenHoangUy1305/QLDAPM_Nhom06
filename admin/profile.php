<?php
require_once __DIR__ . '/_auth.php';
$adminUser = requireStaffOrAdmin();
require_once __DIR__ . '/_layout.php';

$pdo = getPDO();
$message = '';
$error = '';

$freshUser = $adminUser;
if ($pdo) {
    try {
        $stmt = $pdo->prepare('SELECT id, name, email, phone, address, role, status, created_at FROM users WHERE id = ? LIMIT 1');
        $stmt->execute([(int)$adminUser['id']]);
        if ($row = $stmt->fetch()) {
            $freshUser = $row;
        }
    } catch (PDOException $e) {
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();

    if (!$pdo) {
        $error = 'Không thể kết nối cơ sở dữ liệu.';
    } else {
        $action = (string)($_POST['action'] ?? 'update_profile');

        if ($action === 'update_profile') {
            $name = trim((string)($_POST['name'] ?? ''));
            $phone = preg_replace('/\D+/', '', (string)($_POST['phone'] ?? ''));
            $address = trim((string)($_POST['address'] ?? ''));

            if ($name === '') {
                $error = 'Họ tên không được để trống.';
            } elseif ($phone !== '' && !preg_match('/^0\d{8,10}$/', $phone)) {
                $error = 'Số điện thoại không hợp lệ (bắt đầu bằng 0 và dài 9-11 số).';
            } else {
                try {
                    $upd = $pdo->prepare('UPDATE users SET name = ?, phone = ?, address = ?, updated_at = NOW() WHERE id = ?');
                    $upd->execute([
                        $name,
                        $phone !== '' ? $phone : null,
                        $address !== '' ? $address : null,
                        (int)$adminUser['id'],
                    ]);

                    $reStmt = $pdo->prepare('SELECT id, name, email, phone, address, role, status, created_at FROM users WHERE id = ? LIMIT 1');
                    $reStmt->execute([(int)$adminUser['id']]);
                    if ($reRow = $reStmt->fetch()) {
                        $freshUser = $reRow;
                        $adminUser = syncUserSession($reRow);
                    }
                    $message = 'Đã cập nhật thông tin hồ sơ cá nhân!';
                } catch (PDOException $e) {
                    $error = 'Không thể cập nhật hồ sơ.';
                }
            }
        } elseif ($action === 'change_password') {
            $currentPw = (string)($_POST['current_password'] ?? '');
            $newPw = (string)($_POST['new_password'] ?? '');
            $confirmPw = (string)($_POST['confirm_password'] ?? '');

            if ($currentPw === '' || $newPw === '' || $confirmPw === '') {
                $error = 'Vui lòng nhập đầy đủ mật khẩu hiện tại và mật khẩu mới.';
            } elseif (strlen($newPw) < 6) {
                $error = 'Mật khẩu mới phải từ 6 ký tự trở lên.';
            } elseif ($newPw !== $confirmPw) {
                $error = 'Xác nhận mật khẩu mới không khớp.';
            } else {
                try {
                    $pwStmt = $pdo->prepare('SELECT password FROM users WHERE id = ? LIMIT 1');
                    $pwStmt->execute([(int)$adminUser['id']]);
                    $storedHash = (string)($pwStmt->fetchColumn() ?: '');
                    if ($storedHash === '' || !password_verify($currentPw, $storedHash)) {
                        $error = 'Mật khẩu hiện tại không đúng.';
                    } else {
                        $newHash = password_hash($newPw, PASSWORD_DEFAULT);
                        $pdo->prepare('UPDATE users SET password = ?, updated_at = NOW() WHERE id = ?')
                            ->execute([$newHash, (int)$adminUser['id']]);
                        session_regenerate_id(true);
                        $message = 'Đã đổi mật khẩu thành công!';
                    }
                } catch (PDOException $e) {
                    $error = 'Không thể đổi mật khẩu.';
                }
            }
        }
    }
}

renderAdminHeader('Hồ sơ cá nhân', 'profile', $adminUser);
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

<div class="grid grid-cols-1 lg:grid-cols-2 gap-5 max-w-4xl">
    <div class="bg-white rounded-xl border border-gray-200 p-5 shadow-sm">
        <h2 class="text-sm font-extrabold uppercase mb-3 pb-2 border-b border-gray-100">Thông tin tài khoản</h2>
        <form method="POST" action="profile.php" class="space-y-3 text-xs">
            <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
            <input type="hidden" name="action" value="update_profile">

            <div>
                <label class="block font-bold mb-1">Email (không thể thay đổi)</label>
                <input type="email" disabled value="<?= e($freshUser['email']) ?>" class="w-full h-9 px-3 rounded-lg border border-gray-200 bg-gray-100 text-gray-500">
            </div>
            <div>
                <label class="block font-bold mb-1">Vai trò</label>
                <input type="text" disabled value="<?= ($freshUser['role'] ?? '') === 'admin' ? 'Quản trị viên (Admin)' : 'Nhân viên (Staff)' ?>"
                    class="w-full h-9 px-3 rounded-lg border border-gray-200 bg-gray-100 text-gray-500">
            </div>
            <div>
                <label class="block font-bold mb-1">Họ và tên *</label>
                <input type="text" name="name" required value="<?= e($freshUser['name']) ?>" class="w-full h-9 px-3 rounded-lg border border-gray-300">
            </div>
            <div>
                <label class="block font-bold mb-1">Số điện thoại</label>
                <input type="text" name="phone" value="<?= e($freshUser['phone'] ?? '') ?>" placeholder="0909123456" class="w-full h-9 px-3 rounded-lg border border-gray-300">
            </div>
            <div>
                <label class="block font-bold mb-1">Địa chỉ</label>
                <input type="text" name="address" value="<?= e($freshUser['address'] ?? '') ?>" placeholder="TP. Hồ Chí Minh" class="w-full h-9 px-3 rounded-lg border border-gray-300">
            </div>
            <button type="submit" class="w-full bg-gvn-red hover:bg-gvn-redHover text-white font-bold py-2.5 rounded-lg uppercase transition">
                Lưu thông tin hồ sơ
            </button>
        </form>
    </div>

    <div class="bg-white rounded-xl border border-gray-200 p-5 shadow-sm h-fit">
        <h2 class="text-sm font-extrabold uppercase mb-3 pb-2 border-b border-gray-100">Đổi mật khẩu</h2>
        <form method="POST" action="profile.php" class="space-y-3 text-xs">
            <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
            <input type="hidden" name="action" value="change_password">

            <div>
                <label class="block font-bold mb-1">Mật khẩu hiện tại *</label>
                <input type="password" name="current_password" required class="w-full h-9 px-3 rounded-lg border border-gray-300">
            </div>
            <div>
                <label class="block font-bold mb-1">Mật khẩu mới * (tối thiểu 6 ký tự)</label>
                <input type="password" name="new_password" required minlength="6" class="w-full h-9 px-3 rounded-lg border border-gray-300">
            </div>
            <div>
                <label class="block font-bold mb-1">Nhập lại mật khẩu mới *</label>
                <input type="password" name="confirm_password" required minlength="6" class="w-full h-9 px-3 rounded-lg border border-gray-300">
            </div>
            <button type="submit" class="w-full bg-black hover:bg-neutral-800 text-white font-bold py-2.5 rounded-lg uppercase transition">
                Cập nhật mật khẩu
            </button>
        </form>
    </div>
</div>

<?php renderAdminFooter(); ?>
