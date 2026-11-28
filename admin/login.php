<?php
require_once __DIR__ . '/../config/db.php';

if (hasRole('admin', 'staff')) {
    header('Location: index.php');
    exit;
}

$existingCustomer = currentUser();
$error = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();

    $email = trim((string)($_POST['email'] ?? ''));
    $password = (string)($_POST['password'] ?? '');

    if ($email === '' || $password === '') {
        $error = 'Vui lòng nhập đầy đủ email và mật khẩu.';
    } else {
        $pdo = getPDO();
        if (!$pdo) {
            $error = 'Không thể kết nối cơ sở dữ liệu. Vui lòng thử lại sau.';
        } else {
            try {
                $stmt = $pdo->prepare("
                    SELECT id, name, email, phone, address, password, role, status
                    FROM users
                    WHERE email = ? AND role IN ('admin', 'staff')
                    LIMIT 1
                ");
                $stmt->execute([$email]);
                $row = $stmt->fetch();

                if (!$row || empty($row['password']) || !password_verify($password, (string)$row['password'])) {
                    $error = 'Email hoặc mật khẩu không chính xác, hoặc tài khoản không có quyền truy cập hệ thống quản trị.';
                } elseif (($row['status'] ?? 'active') !== 'active') {
                    $error = 'Tài khoản của bạn hiện đang bị tạm khóa. Vui lòng liên hệ Quản trị viên.';
                } else {
                    session_regenerate_id(true);
                    syncUserSession($row);
                    header('Location: index.php');
                    exit;
                }
            } catch (PDOException $e) {
                $error = 'Đã xảy ra lỗi hệ thống khi kiểm tra thông tin đăng nhập.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng nhập Quản trị & Nhân viên - ĐOÀN 6</title>
    <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>body { font-family: 'Be Vietnam Pro', system-ui, sans-serif; }</style>
</head>
<body class="bg-zinc-900 min-h-screen flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-md p-7">
        <div class="text-center mb-6">
            <div class="inline-block bg-[#E30019] text-white font-extrabold text-xl px-3.5 py-1 rounded-md mb-2">
                ĐOÀN 6 PANEL
            </div>
            <h1 class="text-lg font-bold text-gray-900">Đăng nhập Quản trị viên / Nhân viên</h1>
            <p class="text-xs text-gray-500 mt-1">Sử dụng tài khoản nội bộ được cấp quyền Admin hoặc Staff</p>
        </div>

        <?php if ($existingCustomer && ($existingCustomer['role'] ?? '') === 'customer'): ?>
            <div class="bg-amber-50 border border-amber-200 text-amber-800 text-xs rounded-xl p-3 mb-4">
                <i class="fa-solid fa-circle-info mr-1"></i>
                Bạn đang đăng nhập tài khoản Khách hàng (<strong><?= e($existingCustomer['email']) ?></strong>). Vui lòng đăng nhập bằng tài khoản Admin hoặc Nhân viên để vào bảng điều khiển.
            </div>
        <?php endif; ?>

        <?php if ($error !== ''): ?>
            <div class="bg-red-50 border border-red-200 text-red-700 text-xs font-semibold rounded-xl p-3 mb-4">
                <i class="fa-solid fa-triangle-exclamation mr-1"></i> <?= e($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="login.php" class="space-y-4">
            <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
            <div>
                <label class="block text-xs font-bold text-gray-700 mb-1">Email tài khoản nội bộ</label>
                <input type="email" name="email" value="<?= e($email) ?>" required autocomplete="username" placeholder="ten@doan6.local"
                    class="w-full h-10 px-3.5 rounded-lg border border-gray-300 text-sm focus:outline-none focus:border-[#E30019]">
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-700 mb-1">Mật khẩu</label>
                <input type="password" name="password" required autocomplete="current-password" placeholder="Nhập mật khẩu"
                    class="w-full h-10 px-3.5 rounded-lg border border-gray-300 text-sm focus:outline-none focus:border-[#E30019]">
            </div>
            <button type="submit" class="w-full bg-[#E30019] hover:bg-[#B80014] text-white font-extrabold py-3 rounded-xl uppercase text-sm transition">
                <i class="fa-solid fa-right-to-bracket mr-1"></i> Đăng nhập hệ thống
            </button>
        </form>

        <div class="mt-5 pt-4 border-t border-gray-100 text-center">
            <a href="../index.php" class="text-xs text-gray-500 hover:text-[#E30019] font-semibold">← Quay về trang chủ bán hàng</a>
        </div>
    </div>
</body>
</html>
