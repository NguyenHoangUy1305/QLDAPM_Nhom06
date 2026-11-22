<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/data/store.php';

if (!empty($_SESSION['user'])) {
    header('Location: index.php');
    exit;
}

$error = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $error = 'Vui lòng nhập đầy đủ Email và Mật khẩu.';
    } else {
        $userFound = null;

        // 1. Kiểm tra trong MySQL nếu đã kết nối DB
        $pdo = getPDO();
        if ($pdo) {
            try {
                $stmt = $pdo->prepare('SELECT id, fullname, email, phone, password, role FROM users WHERE email = ? LIMIT 1');
                $stmt->execute([$email]);
                $row = $stmt->fetch();
                if ($row && password_verify($password, $row['password'])) {
                    $row['name'] = $row['fullname'];
                    $userFound = $row;
                }
            } catch (PDOException $e) {
                // Bỏ qua nếu bảng users chưa tạo
            }
        }

        // 2. Fallback kiểm tra trong Session hoặc tài khoản demo
        if (!$userFound) {
            $sessionUsers = $_SESSION['registered_users'] ?? [];
            if (isset($sessionUsers[$email]) && password_verify($password, $sessionUsers[$email]['password'])) {
                $userFound = $sessionUsers[$email];
            } elseif ($email === 'demo@doan6.local' && $password === '123456') {
                $userFound = [
                    'id' => 999,
                    'name' => 'Tài khoản Demo Admin',
                    'email' => 'demo@doan6.local',
                    'phone' => '00000000',
                    'address' => 'Khu vực Demo, TP. Hồ Chí Minh',
                    'role' => 'admin',
                ];
            }
        }

        if ($userFound) {
            unset($userFound['password']);
            $_SESSION['user'] = $userFound;
            header('Location: index.php');
            exit;
        } else {
            $error = 'Email hoặc mật khẩu không chính xác.';
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

        <div class="mt-4 bg-blue-50 border border-blue-200 rounded-xl p-3 text-[11px] text-blue-800">
            <strong>Tài khoản test nhanh:</strong><br>
            Email: <code>demo@doan6.local</code> | Mật khẩu: <code>123456</code>
        </div>

        <div class="mt-5 text-center text-xs text-gray-600">
            Chưa có tài khoản?
            <a href="register.php" class="text-gvn-red font-bold hover:underline">Đăng ký ngay</a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
