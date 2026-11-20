<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../data/store.php';

$error = '';
$success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullname = trim($_POST['fullname'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($fullname && $email && $phone && $password) {
        $pdo = getPDO();
        // Kiểm tra email đã tồn tại chưa
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $error = 'Email này đã được sử dụng!';
        } else {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            // Thêm phone vào câu lệnh INSERT
            $stmt = $pdo->prepare("INSERT INTO users (fullname, email, phone, password, role) VALUES (?, ?, ?, ?, 'user')");
            if ($stmt->execute([$fullname, $email, $phone, $hashedPassword])) {
                $success = 'Đăng ký thành công! Bạn có thể <a href="login.php">đăng nhập</a> ngay.';
            } else {
                $error = 'Có lỗi xảy ra, vui lòng thử lại.';
            }
        }
    } else {
        $error = 'Vui lòng điền đầy đủ thông tin!';
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Đăng ký - ĐOÀN 6</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f5f5f5; }
        .card { border: none; border-radius: 10px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
        .btn-danger { background-color: #d70018; border-color: #d70018; }
    </style>
</head>
<body class="d-flex align-items-center py-4 bg-body-tertiary" style="height: 100vh;">
    <main class="form-signin w-100 m-auto" style="max-width: 400px;">
        <div class="card p-4">
            <div class="text-center mb-4">
                <h3 class="fw-bold" style="color: #d70018;">ĐOÀN 6</h3>
                <p class="text-muted">Tạo tài khoản mới</p>
            </div>
            <?php if ($error): ?>
                <div class="alert alert-danger py-2"><?= $error ?></div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="alert alert-success py-2"><?= $success ?></div>
            <?php endif; ?>
            <form method="POST">
                <div class="mb-3">
                    <label class="form-label">Họ và tên</label>
                    <input type="text" name="fullname" class="form-control" required placeholder="Nguyễn Văn A">
                </div>
                <div class="mb-3">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control" required placeholder="name@example.com">
                </div>
                <div class="mb-3">
                    <label class="form-label">Số điện thoại</label>
                    <input type="text" name="phone" class="form-control" required placeholder="0912345678">
                </div>
                <div class="mb-3">
                    <label class="form-label">Mật khẩu</label>
                    <input type="password" name="password" class="form-control" required placeholder="Mật khẩu">
                </div>
                <button type="submit" class="btn btn-danger w-100 py-2 mb-3">Đăng Ký</button>
                <div class="text-center">
                    <small class="text-muted">Đã có tài khoản? <a href="login.php" class="text-decoration-none text-danger">Đăng nhập</a></small>
                </div>
            </form>
        </div>
    </main>
</body>
</html>