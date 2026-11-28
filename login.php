<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/data/store.php';

$rawRedirect = $_GET['redirect'] ?? $_POST['redirect'] ?? '';
$safeRedirect = sanitizeRedirectPath($rawRedirect, '');

$existingUser = currentUser();
if ($existingUser !== null) {
    $dest = $safeRedirect !== ''
        ? $safeRedirect
        : (hasRole('admin', 'staff') ? 'admin/index.php' : 'index.php');
    header('Location: ' . $dest);
    exit;
}

$error = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();

    $email = trim((string)($_POST['email'] ?? ''));
    $password = (string)($_POST['password'] ?? '');

    if ($email === '' || $password === '') {
        $error = 'Vui lòng nhập đầy đủ Email và Mật khẩu.';
    } else {
        $pdo = getPDO();
        if (!$pdo) {
            $error = 'Hệ thống cơ sở dữ liệu tạm thời gián đoạn. Vui lòng thử lại sau.';
        } else {
            try {
                $stmt = $pdo->prepare('SELECT id, name, email, phone, address, password, role, status FROM users WHERE email = ? LIMIT 1');
                $stmt->execute([$email]);
                $row = $stmt->fetch();

                if ($row && password_verify($password, (string)$row['password'])) {
                    if (($row['status'] ?? 'active') !== 'active') {
                        $error = 'Tài khoản của bạn đã bị khóa. Vui lòng liên hệ quản trị viên.';
                    } else {
                        session_regenerate_id(true);
                        $cleanUser = syncUserSession($row);
                        $dest = $safeRedirect !== ''
                            ? $safeRedirect
                            : (in_array($cleanUser['role'], ['admin', 'staff'], true) ? 'admin/index.php' : 'index.php');
                        header('Location: ' . $dest);
                        exit;
                    }
                } else {
                    $error = 'Email hoặc mật khẩu không chính xác.';
                }
            } catch (PDOException $e) {
                $error = 'Không thể xác thực tài khoản lúc này. Vui lòng thử lại.';
            }
        }
    }
}

$pageTitle = 'Đăng nhập - ĐOÀN 6';
require_once __DIR__ . '/includes/header.php';
?>

<div class="max-w-[460px] mx-auto px-3 py-8">
    <div class="bg-white rounded-2xl border border-gray-200 p-6 shadow-sm">
        <h1 class="text-xl font-extrabold text-gray-900 uppercase text-center mb-1">Đăng nhập</h1>
        <p class="text-xs text-gray-500 text-center mb-5">Chào mừng bạn quay trở lại với ĐOÀN 6</p>

        <?php if ($error !== ''): ?>
            <div class="bg-red-50 border border-red-200 text-red-700 text-xs rounded-xl p-3 mb-4 flex items-center gap-2">
                <i class="fa-solid fa-circle-exclamation"></i>
                <span><?= e($error) ?></span>
            </div>
        <?php endif; ?>

        <form method="POST" action="login.php" class="space-y-4">
            <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
            <?php if ($safeRedirect !== ''): ?>
                <input type="hidden" name="redirect" value="<?= e($safeRedirect) ?>">
            <?php endif; ?>

            <div>
                <label class="block text-xs font-bold text-gray-700 mb-1">Email</label>
                <input type="email" name="email" value="<?= e($email) ?>" required placeholder="nhapemail@example.com"
                    class="w-full h-10 px-3.5 rounded-lg border border-gray-300 text-sm focus:outline-none focus:border-gvn-red">
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-700 mb-1">Mật khẩu</label>
                <input type="password" name="password" required placeholder="Nhập mật khẩu"
                    class="w-full h-10 px-3.5 rounded-lg border border-gray-300 text-sm focus:outline-none focus:border-gvn-red">
            </div>
            <button type="submit" class="w-full bg-gvn-red hover:bg-gvn-redHover text-white font-extrabold py-3 rounded-xl uppercase text-sm transition shadow-sm">
                Đăng nhập
            </button>
        </form>

        <div class="mt-5 text-center text-xs text-gray-600">
            Chưa có tài khoản?
            <a href="register.php" class="text-gvn-red font-bold hover:underline">Đăng ký ngay</a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
