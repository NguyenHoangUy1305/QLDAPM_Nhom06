<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../data/store.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email && $password) {
        $pdo = getPDO();
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            // Lưu thêm phone vào Session
            $_SESSION['user'] = [
                'id' => $user['id'],
                'fullname' => $user['fullname'],
                'email' => $user['email'],
                'phone' => $user['phone'], 
                'role' => $user['role']
            ];

            // Phân hướng dựa vào quyền
            if ($user['role'] === 'admin') {
                header('Location: ../admin/index.php');
            } else {
                header('Location: ../index.php');
            }
            exit;
        } else {
            $error = 'Email hoặc mật khẩu không chính xác!';
        }
    } else {
        $error = 'Vui lòng điền đầy đủ thông tin!';
    }
}
?>