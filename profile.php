<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/data/store.php';

$sessionUser = requireLogin('login.php');
$pdo = getPDO();

$successMsg = '';
$errorMsg = '';
$dbUser = $sessionUser;

if ($pdo) {
    $stmt = $pdo->prepare('SELECT id, name, email, phone, address, password, role, status, created_at FROM users WHERE id = ? LIMIT 1');
    $stmt->execute([(int)$sessionUser['id']]);
    $row = $stmt->fetch();
    if (!$row || ($row['status'] ?? 'active') !== 'active') {
        clearAuthSession();
        header('Location: login.php');
        exit;
    }
    $dbUser = $row;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();
    $action = (string)($_POST['action'] ?? '');

    if (!$pdo) {
        $errorMsg = 'Hệ thống cơ sở dữ liệu tạm thời gián đoạn.';
    } elseif ($action === 'update_profile') {
        $name = trim((string)($_POST['name'] ?? ''));
        $phoneRaw = trim((string)($_POST['phone'] ?? ''));
        $phoneDigits = preg_replace('/\D+/', '', $phoneRaw);
        $address = trim((string)($_POST['address'] ?? ''));

        if ($name === '') {
            $errorMsg = 'Họ và tên là bắt buộc và không được để trống.';
        } elseif ($phoneRaw !== '' && !preg_match('/^0\d{8,10}$/', $phoneDigits)) {
            $errorMsg = 'Số điện thoại không hợp lệ (cần bắt đầu bằng 0 và dài 9-11 chữ số).';
        } else {
            $storedPhone = $phoneRaw !== '' ? $phoneDigits : null;
            $upd = $pdo->prepare('UPDATE users SET name = ?, phone = ?, address = ?, updated_at = NOW() WHERE id = ?');
            $upd->execute([$name, $storedPhone, $address !== '' ? $address : null, (int)$dbUser['id']]);

            $stmt = $pdo->prepare('SELECT id, name, email, phone, address, password, role, status, created_at FROM users WHERE id = ? LIMIT 1');
            $stmt->execute([(int)$dbUser['id']]);
            $fresh = $stmt->fetch();
            if ($fresh) {
                $dbUser = $fresh;
                syncUserSession($fresh);
            }
            $successMsg = 'Cập nhật thông tin hồ sơ thành công.';
        }
    } elseif ($action === 'change_password') {
        $currentPassword = (string)($_POST['current_password'] ?? '');
        $newPassword = (string)($_POST['new_password'] ?? '');
        $confirmPassword = (string)($_POST['confirm_password'] ?? '');

        if ($currentPassword === '' || $newPassword === '' || $confirmPassword === '') {
            $errorMsg = 'Vui lòng nhập đầy đủ mật khẩu hiện tại và mật khẩu mới.';
        } elseif (empty($dbUser['password']) || !password_verify($currentPassword, (string)$dbUser['password'])) {
            $errorMsg = 'Mật khẩu hiện tại không chính xác.';
        } elseif (strlen($newPassword) < 6) {
            $errorMsg = 'Mật khẩu mới phải có ít nhất 6 ký tự.';
        } elseif ($newPassword !== $confirmPassword) {
            $errorMsg = 'Xác nhận mật khẩu mới không khớp.';
        } else {
            $hashed = password_hash($newPassword, PASSWORD_DEFAULT);
            $upd = $pdo->prepare('UPDATE users SET password = ?, updated_at = NOW() WHERE id = ?');
            $upd->execute([$hashed, (int)$dbUser['id']]);
            $dbUser['password'] = $hashed;
            session_regenerate_id(true);
            $successMsg = 'Đổi mật khẩu thành công.';
        }
    }
}

$roleBadge = match (normalizeUserRole($dbUser['role'] ?? 'customer')) {
    'admin' => ['label' => 'Quản trị viên (Admin)', 'class' => 'bg-red-100 text-red-700 border-red-200'],
    'staff' => ['label' => 'Nhân viên (Staff)', 'class' => 'bg-amber-100 text-amber-800 border-amber-200'],
    default => ['label' => 'Khách hàng', 'class' => 'bg-emerald-100 text-emerald-700 border-emerald-200'],
};

$pageTitle = 'Hồ sơ tài khoản - ĐOÀN 6';
require_once __DIR__ . '/includes/header.php';
?>

<div class="max-w-[1000px] mx-auto px-3 py-6">
    <div class="flex flex-wrap items-center justify-between gap-3 mb-5">
        <div>
            <h1 class="text-xl font-extrabold text-gray-900 uppercase">Hồ sơ tài khoản</h1>
            <p class="text-xs text-gray-500 mt-0.5">Quản lý thông tin cá nhân và bảo mật tài khoản ĐOÀN 6</p>
        </div>
        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold border <?= e($roleBadge['class']) ?>">
            <i class="fa-solid fa-id-badge"></i>
            <?= e($roleBadge['label']) ?>
        </span>
    </div>

    <?php if ($successMsg !== ''): ?>
        <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-semibold rounded-xl p-3 mb-4 flex items-center gap-2">
            <i class="fa-solid fa-circle-check"></i>
            <span><?= e($successMsg) ?></span>
        </div>
    <?php endif; ?>

    <?php if ($errorMsg !== ''): ?>
        <div class="bg-red-50 border border-red-200 text-red-700 text-xs font-semibold rounded-xl p-3 mb-4 flex items-center gap-2">
            <i class="fa-solid fa-circle-exclamation"></i>
            <span><?= e($errorMsg) ?></span>
        </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
        <!-- Sidebar điều hướng nhanh -->
        <div class="bg-white rounded-2xl border border-gray-200 p-5 shadow-sm h-fit space-y-3">
            <div class="pb-3 border-b border-gray-100">
                <div class="text-sm font-extrabold text-gray-900"><?= e($dbUser['name'] ?? '') ?></div>
                <div class="text-xs text-gray-500 break-all"><?= e($dbUser['email'] ?? '') ?></div>
            </div>
            <nav class="space-y-1.5 text-xs font-semibold">
                <a href="orders.php" class="flex items-center gap-2 px-3 py-2 rounded-lg text-gray-700 hover:bg-gray-100 transition">
                    <i class="fa-solid fa-file-invoice text-gvn-red w-4"></i>
                    <span>Lịch sử &amp; Tra cứu đơn hàng</span>
                </a>
                <a href="warranty.php" class="flex items-center gap-2 px-3 py-2 rounded-lg text-gray-700 hover:bg-gray-100 transition">
                    <i class="fa-solid fa-shield-halved text-gvn-red w-4"></i>
                    <span>Tra cứu bảo hành</span>
                </a>
                <?php if (hasRole('admin', 'staff')): ?>
                    <a href="admin/index.php" class="flex items-center gap-2 px-3 py-2 rounded-lg text-red-700 bg-red-50 hover:bg-red-100 transition">
                        <i class="fa-solid fa-user-shield w-4"></i>
                        <span>Vào trang Quản trị / Bán hàng</span>
                    </a>
                <?php endif; ?>
                <a href="logout.php" class="flex items-center gap-2 px-3 py-2 rounded-lg text-gray-600 hover:bg-gray-100 transition">
                    <i class="fa-solid fa-right-from-bracket w-4"></i>
                    <span>Đăng xuất</span>
                </a>
            </nav>
        </div>

        <!-- Form thông tin & mật khẩu -->
        <div class="md:col-span-2 space-y-5">
            <div class="bg-white rounded-2xl border border-gray-200 p-6 shadow-sm">
                <h2 class="text-sm font-extrabold text-gray-900 uppercase mb-4">Thông tin cá nhân</h2>
                <form method="POST" action="profile.php" class="space-y-4">
                    <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                    <input type="hidden" name="action" value="update_profile">

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">Email (không thể thay đổi)</label>
                            <input type="email" value="<?= e($dbUser['email'] ?? '') ?>" disabled
                                class="w-full h-10 px-3.5 rounded-lg border border-gray-200 bg-gray-100 text-sm text-gray-500">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">Họ và tên *</label>
                            <input type="text" name="name" value="<?= e($dbUser['name'] ?? '') ?>" required
                                class="w-full h-10 px-3.5 rounded-lg border border-gray-300 text-sm focus:outline-none focus:border-gvn-red">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">Số điện thoại</label>
                            <input type="text" name="phone" value="<?= e($dbUser['phone'] ?? '') ?>" placeholder="0909123456"
                                class="w-full h-10 px-3.5 rounded-lg border border-gray-300 text-sm focus:outline-none focus:border-gvn-red">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">Vai trò tài khoản</label>
                            <input type="text" value="<?= e($roleBadge['label']) ?>" disabled
                                class="w-full h-10 px-3.5 rounded-lg border border-gray-200 bg-gray-100 text-sm text-gray-500">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">Địa chỉ giao hàng mặc định</label>
                        <input type="text" name="address" value="<?= e($dbUser['address'] ?? '') ?>" placeholder="Số nhà, đường, quận/huyện, tỉnh/thành"
                            class="w-full h-10 px-3.5 rounded-lg border border-gray-300 text-sm focus:outline-none focus:border-gvn-red">
                    </div>

                    <button type="submit" class="bg-gvn-red hover:bg-gvn-redHover text-white font-extrabold px-5 py-2.5 rounded-xl text-xs uppercase transition shadow-sm">
                        Lưu thay đổi
                    </button>
                </form>
            </div>

            <div class="bg-white rounded-2xl border border-gray-200 p-6 shadow-sm">
                <h2 class="text-sm font-extrabold text-gray-900 uppercase mb-4">Đổi mật khẩu</h2>
                <form method="POST" action="profile.php" class="space-y-4">
                    <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                    <input type="hidden" name="action" value="change_password">

                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">Mật khẩu hiện tại *</label>
                        <input type="password" name="current_password" required
                            class="w-full h-10 px-3.5 rounded-lg border border-gray-300 text-sm focus:outline-none focus:border-gvn-red">
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">Mật khẩu mới (tối thiểu 6 ký tự) *</label>
                            <input type="password" name="new_password" required
                                class="w-full h-10 px-3.5 rounded-lg border border-gray-300 text-sm focus:outline-none focus:border-gvn-red">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">Xác nhận mật khẩu mới *</label>
                            <input type="password" name="confirm_password" required
                                class="w-full h-10 px-3.5 rounded-lg border border-gray-300 text-sm focus:outline-none focus:border-gvn-red">
                        </div>
                    </div>

                    <button type="submit" class="bg-black hover:bg-neutral-800 text-white font-extrabold px-5 py-2.5 rounded-xl text-xs uppercase transition shadow-sm">
                        Cập nhật mật khẩu
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
