<?php
require_once __DIR__ . '/../config/db.php';

if (!empty($_SESSION['admin_user'])) {
    header('Location: index.php');
    exit;
}

$error = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $adminFound = null;

    // 1. Kiểm tra MySQL bảng users với role = 'admin'
    $pdo = getPDO();
    if ($pdo) {
        try {
            $stmt = $pdo->prepare("SELECT id, name, email, password, role FROM users WHERE email = ? AND role = 'admin' LIMIT 1");
            $stmt->execute([$email]);
            $row = $stmt->fetch();
            if ($row && password_verify($password, $row['password'])) {
                $adminFound = $row;
            }
        } catch (PDOException $e) {
            // Bỏ qua nếu chưa có DB
        }
    }

    // 2. Tài khoản Admin demo mặc định
    if (!$adminFound && $email === 'admin@doan6.local' && $password === 'admin123') {
        $adminFound = [
            'id' => 1,
            'name' => 'Quản trị viên Đoàn 6',
            'email' => 'admin@doan6.local',
            'role' => 'admin',
        ];
    }

    if ($adminFound) {
        unset($adminFound['password']);
        $_SESSION['admin_user'] = $adminFound;
        header('Location: index.php');
        exit;
    } else {
        $error = 'Email hoặc mật khẩu quản trị không đúng.';
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng nhập Admin - ĐOÀN 6</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-zinc-900 min-h-screen flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-md p-7">
        <div class="text-center mb-6">
            <div class="inline-block bg-[#E30019] text-white font-extrabold text-xl px-3 py-1 rounded-md mb-2">
                ĐOÀN 6 ADMIN
            </div>
            <h1 class="text-lg font-bold text-gray-900">Đăng nhập hệ thống quản trị</h1>
        </div>

        <?php if ($error !== ''): ?>
            <div class="bg-red-50 border border-red-200 text-red-700 text-xs rounded-xl p-3 mb-4">
                <?= e($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="login.php" class="space-y-4">
            <div>
                <label class="block text-xs font-bold text-gray-700 mb-1">Email quản trị</label>
                <input type="email" name="email" value="<?= e($email) ?>" required placeholder="admin@doan6.local"
                    class="w-full h-10 px-3.5 rounded-lg border border-gray-300 text-sm focus:outline-none focus:border-[#E30019]">
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-700 mb-1">Mật khẩu</label>
                <input type="password" name="password" required placeholder="Nhập mật khẩu"
                    class="w-full h-10 px-3.5 rounded-lg border border-gray-300 text-sm focus:outline-none focus:border-[#E30019]">
            </div>
            <button type="submit" class="w-full bg-[#E30019] hover:bg-[#B80014] text-white font-extrabold py-3 rounded-xl text-sm uppercase transition">
                Đăng nhập Admin
            </button>
        </form>

        <div class="mt-4 bg-blue-50 border border-blue-200 rounded-xl p-3 text-xs text-blue-800">
            <strong>Tài khoản Admin demo:</strong><br>
            Email: <code>admin@doan6.local</code> | Mật khẩu: <code>admin123</code>
        </div>

        <div class="mt-4 text-center text-xs">
            <a href="../index.php" class="text-gray-500 hover:text-[#E30019]">← Quay về trang bán hàng</a>
        </div>
    </div>
</body>
</html>
