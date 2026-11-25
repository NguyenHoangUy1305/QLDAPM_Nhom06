<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/data/store.php';

if (!empty($_SESSION['user'])) {
    header('Location: index.php');
    exit;
}

$error = '';
$name = '';
$email = '';
$phone = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if ($name === '' || $email === '' || $password === '') {
        $error = 'Vui lòng điền đầy đủ Họ tên, Email và Mật khẩu.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Địa chỉ Email không hợp lệ.';
    } elseif (strlen($password) < 6) {
        $error = 'Mật khẩu phải có ít nhất 6 ký tự.';
    } elseif ($password !== $confirm) {
        $error = 'Mật khẩu xác nhận không khớp.';
    } else {
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $savedToDb = false;

        $pdo = getPDO();
        if ($pdo) {
            try {
                $check = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
                $check->execute([$email]);
                if ($check->fetch()) {
                    $error = 'Email này đã được đăng ký.';
                } else {
                    $stmt = $pdo->prepare('INSERT INTO users (name, email, phone, password, role, created_at) VALUES (?, ?, ?, ?, ?, NOW())');
                    $stmt->execute([$name, $email, $phone, $hashed, 'user']);
                    $newId = (int)$pdo->lastInsertId();
                    $_SESSION['user'] = [
                        'id' => $newId,
                        'name' => $name,
                        'email' => $email,
                        'phone' => $phone,
                        'address' => '',
                        'role' => 'user',
                    ];
                    $savedToDb = true;
                }
            } catch (PDOException $e) {
                // Bảng users chưa tồn tại -> dùng session fallback bên dưới
            }
        }

        if ($error === '' && !$savedToDb) {
            if (isset($_SESSION['registered_users'][$email])) {
                $error = 'Email này đã được đăng ký.';
            } else {
                $userData = [
                    'id' => time(),
                    'name' => $name,
                    'email' => $email,
                    'phone' => $phone,
                    'address' => '',
                    'password' => $hashed,
                    'role' => 'user',
                ];
                $_SESSION['registered_users'][$email] = $userData;
                unset($userData['password']);
                $_SESSION['user'] = $userData;
            }
        }

        if ($error === '') {
            header('Location: index.php');
            exit;
        }
    }
}

$pageTitle = 'Đăng ký tài khoản - ĐOÀN 6';
require_once __DIR__ . '/includes/header.php';
?>

<div class="max-w-[460px] mx-auto px-3 py-8">
    <div class="bg-white rounded-2xl border border-gray-200 p-6 shadow-sm">
        <h1 class="text-xl font-extrabold text-gray-900 uppercase text-center mb-1">Đăng ký tài khoản</h1>
        <p class="text-xs text-gray-500 text-center mb-5">Tạo tài khoản để mua sắm & theo dõi đơn hàng dễ dàng</p>

        <?php if ($error !== ''): ?>
            <div class="bg-red-50 border border-red-200 text-red-700 text-xs rounded-xl p-3 mb-4 flex items-center gap-2">
                <i class="fa-solid fa-circle-exclamation"></i>
                <span><?= e($error) ?></span>
            </div>
        <?php endif; ?>

        <form method="POST" action="register.php" class="space-y-3.5">
            <div>
                <label class="block text-xs font-bold text-gray-700 mb-1">Họ và tên *</label>
                <input type="text" name="name" value="<?= e($name) ?>" required placeholder="Nguyễn Văn A"
                    class="w-full h-10 px-3.5 rounded-lg border border-gray-300 text-sm focus:outline-none focus:border-gvn-red">
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-700 mb-1">Email *</label>
                <input type="email" name="email" value="<?= e($email) ?>" required placeholder="email@example.com"
                    class="w-full h-10 px-3.5 rounded-lg border border-gray-300 text-sm focus:outline-none focus:border-gvn-red">
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-700 mb-1">Số điện thoại</label>
                <input type="text" name="phone" value="<?= e($phone) ?>" placeholder="0000.0000"
                    class="w-full h-10 px-3.5 rounded-lg border border-gray-300 text-sm focus:outline-none focus:border-gvn-red">
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-700 mb-1">Mật khẩu (tối thiểu 6 ký tự) *</label>
                <input type="password" name="password" required placeholder="Nhập mật khẩu"
                    class="w-full h-10 px-3.5 rounded-lg border border-gray-300 text-sm focus:outline-none focus:border-gvn-red">
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-700 mb-1">Xác nhận mật khẩu *</label>
                <input type="password" name="confirm_password" required placeholder="Nhập lại mật khẩu"
                    class="w-full h-10 px-3.5 rounded-lg border border-gray-300 text-sm focus:outline-none focus:border-gvn-red">
            </div>
            <button type="submit" class="w-full bg-gvn-red hover:bg-gvn-redHover text-white font-extrabold py-3 rounded-xl uppercase text-sm transition shadow-sm mt-2">
                Đăng ký
            </button>
        </form>

        <div class="mt-5 text-center text-xs text-gray-600">
            Đã có tài khoản?
            <a href="login.php" class="text-gvn-red font-bold hover:underline">Đăng nhập ngay</a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
