<?php
require_once __DIR__ . '/_auth.php';
$panelUser = requireStaffOrAdmin();

$accept = $_SERVER['HTTP_ACCEPT'] ?? '';
$xhr = strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '');
$wantsJson = str_contains($accept, 'application/json') || $xhr === 'xmlhttprequest' || (($_POST['format'] ?? '') === 'json');

$result = null;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();
    $adminPassword = (string)($_POST['admin_password'] ?? '');
    $approver = verifyAdminPassword($adminPassword);

    if ($wantsJson) {
        header('Content-Type: application/json; charset=utf-8');
        if ($approver !== null) {
            echo json_encode([
                'ok' => true,
                'admin_id' => (int)$approver['id'],
                'admin_name' => (string)$approver['name'],
                'admin_email' => (string)$approver['email'],
            ], JSON_UNESCAPED_UNICODE);
        } else {
            http_response_code(403);
            echo json_encode([
                'ok' => false,
                'error' => 'Mật khẩu Admin không đúng hoặc tài khoản Admin không hoạt động.',
            ], JSON_UNESCAPED_UNICODE);
        }
        exit;
    }

    if ($approver !== null) {
        $result = $approver;
    } else {
        http_response_code(403);
        $error = 'Mật khẩu Admin không đúng hoặc tài khoản Admin không hoạt động.';
    }
}

require_once __DIR__ . '/_layout.php';
renderAdminHeader('Xác thực mật khẩu Admin', 'dashboard', $panelUser);
?>

<div class="max-w-md mx-auto bg-white rounded-xl border border-gray-200 p-6 shadow-sm">
    <h1 class="text-sm font-extrabold uppercase text-gray-900 mb-2">Kiểm tra mật khẩu Quản trị viên</h1>
    <p class="text-xs text-gray-500 mb-4">
        Xác thực mật khẩu của tài khoản Admin đang hoạt động trước khi thực hiện thao tác thay đổi danh mục, thương hiệu hoặc kho hàng.
    </p>

    <?php if ($result !== null): ?>
        <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-semibold rounded-xl p-3 mb-4">
            <i class="fa-solid fa-circle-check mr-1"></i>
            Xác thực thành công bởi Admin: <strong><?= e($result['name']) ?></strong> (<?= e($result['email']) ?>).
        </div>
    <?php endif; ?>

    <?php if ($error !== ''): ?>
        <div class="bg-red-50 border border-red-200 text-red-700 text-xs font-semibold rounded-xl p-3 mb-4">
            <i class="fa-solid fa-triangle-exclamation mr-1"></i> <?= e($error) ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="verify-admin-password.php" class="space-y-3 text-xs">
        <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
        <div>
            <label class="block font-bold mb-1">Mật khẩu Admin *</label>
            <input type="password" name="admin_password" required placeholder="Nhập mật khẩu Admin"
                class="w-full h-10 px-3 rounded-lg border border-gray-300 focus:outline-none focus:border-gvn-red">
        </div>
        <button type="submit" class="w-full bg-gvn-red hover:bg-gvn-redHover text-white font-bold py-2.5 rounded-lg uppercase transition">
            Xác thực mật khẩu Admin
        </button>
    </form>
</div>

<?php renderAdminFooter(); ?>
