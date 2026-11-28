<?php
if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
    session_set_cookie_params([
        'httponly' => true,
        'samesite' => 'Lax',
        'path' => '/',
    ]);
    session_start();
}

// Cấu hình kết nối MySQL (hỗ trợ override qua biến môi trường)
if (!defined('DB_HOST')) {
    define('DB_HOST', getenv('DB_HOST') ?: '127.0.0.1');
}
if (!defined('DB_NAME')) {
    define('DB_NAME', getenv('DB_NAME') ?: 'database');
}
if (!defined('DB_USER')) {
    define('DB_USER', getenv('DB_USER') ?: 'root');
}
if (!defined('DB_PASS')) {
    define('DB_PASS', getenv('DB_PASS') !== false ? (string)getenv('DB_PASS') : '');
}

function getPDO(): ?PDO
{
    static $pdos = [];
    $host = getenv('DB_HOST') ?: DB_HOST;
    $name = getenv('DB_NAME') ?: DB_NAME;
    $user = getenv('DB_USER') ?: DB_USER;
    $passEnv = getenv('DB_PASS');
    $pass = $passEnv !== false ? (string)$passEnv : DB_PASS;

    $key = $host . '|' . $name . '|' . $user;
    if (isset($pdos[$key])) {
        return $pdos[$key];
    }

    try {
        $dsn = 'mysql:host=' . $host . ';dbname=' . $name . ';charset=utf8mb4';
        $pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        $pdos[$key] = $pdo;
        return $pdo;
    } catch (PDOException $e) {
        return null;
    }
}

// Hàm escape dữ liệu đầu ra để tránh lỗi XSS (hỗ trợ null)
function e(?string $str): string
{
    return htmlspecialchars((string)$str, ENT_QUOTES, 'UTF-8');
}

function formatPrice(int|float $price): string
{
    return number_format((int)round($price), 0, ',', '.') . 'đ';
}

function csrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrf(?string $token): bool
{
    return is_string($token)
        && $token !== ''
        && !empty($_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $token);
}

function requireCsrf(): void
{
    $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
    if (verifyCsrf(is_string($token) ? $token : null)) {
        return;
    }

    http_response_code(403);
    $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
    $xhr = strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '');
    if (str_contains($accept, 'application/json') || $xhr === 'xmlhttprequest') {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => false, 'error' => 'Phiên làm việc hết hạn hoặc CSRF token không hợp lệ.'], JSON_UNESCAPED_UNICODE);
    } else {
        header('Content-Type: text/html; charset=utf-8');
        echo '<!DOCTYPE html><html lang="vi"><head><meta charset="UTF-8"><title>403 Forbidden</title></head>'
            . '<body style="font-family:sans-serif;padding:2rem;"><h1>403 - Yêu cầu không hợp lệ</h1>'
            . '<p>CSRF token không hợp lệ hoặc đã hết hạn. Vui lòng quay lại và tải lại trang.</p>'
            . '<p><a href="javascript:history.back()">Quay lại</a></p></body></html>';
    }
    exit;
}

function normalizeUserRole(?string $role): string
{
    $r = strtolower(trim((string)$role));
    if ($r === 'user') {
        return 'customer';
    }
    return in_array($r, ['admin', 'staff', 'customer'], true) ? $r : 'customer';
}

function syncUserSession(array $userRow): array
{
    $clean = [
        'id' => (int)($userRow['id'] ?? 0),
        'name' => (string)($userRow['name'] ?? ''),
        'email' => (string)($userRow['email'] ?? ''),
        'phone' => (string)($userRow['phone'] ?? ''),
        'address' => (string)($userRow['address'] ?? ''),
        'role' => normalizeUserRole($userRow['role'] ?? 'customer'),
        'status' => (string)($userRow['status'] ?? 'active'),
    ];

    $_SESSION['user'] = $clean;
    if (in_array($clean['role'], ['admin', 'staff'], true)) {
        $_SESSION['admin_user'] = $clean;
    } else {
        unset($_SESSION['admin_user']);
    }

    return $clean;
}

function clearAuthSession(): void
{
    unset($_SESSION['user'], $_SESSION['admin_user']);
}

function currentUser(): ?array
{
    $raw = $_SESSION['user'] ?? $_SESSION['admin_user'] ?? null;
    if (!is_array($raw) || empty($raw['id'])) {
        return null;
    }

    $role = normalizeUserRole($raw['role'] ?? '');
    $status = (string)($raw['status'] ?? 'active');
    if ($status !== 'active' || !in_array($role, ['admin', 'staff', 'customer'], true)) {
        clearAuthSession();
        return null;
    }

    $raw['id'] = (int)$raw['id'];
    $raw['role'] = $role;
    $raw['status'] = $status;
    unset($raw['password']);

    return $raw;
}

function hasRole(string ...$roles): bool
{
    $user = currentUser();
    if ($user === null) {
        return false;
    }
    return in_array($user['role'], $roles, true);
}

function sanitizeRedirectPath(?string $target, string $default = 'index.php'): string
{
    $target = trim((string)$target);
    if ($target === '') {
        return $default;
    }
    if (preg_match('/[\r\n\0]/', $target)) {
        return $default;
    }
    if (preg_match('#^(?:[a-z][a-z0-9+.-]*:|//)#i', $target) || str_contains($target, '\\')) {
        return $default;
    }
    if (str_contains($target, '..')) {
        return $default;
    }
    return $target;
}

function requireLogin(string $redirect = 'login.php'): array
{
    $user = currentUser();
    if ($user !== null) {
        return $user;
    }

    $requestUri = (string)($_SERVER['REQUEST_URI'] ?? '');
    $relative = ltrim($requestUri, '/');
    if ($relative === '' || str_contains($relative, 'login.php')) {
        header('Location: ' . $redirect);
        exit;
    }

    $sep = str_contains($redirect, '?') ? '&' : '?';
    header('Location: ' . $redirect . $sep . 'redirect=' . rawurlencode(basename($relative)));
    exit;
}

function verifyAdminPassword(string $password): ?array
{
    if ($password === '') {
        return null;
    }

    $pdo = getPDO();
    if (!$pdo) {
        return null;
    }

    try {
        $stmt = $pdo->query("SELECT id, name, email, phone, address, password, role, status FROM users WHERE role = 'admin' AND status = 'active'");
        foreach ($stmt->fetchAll() as $row) {
            if (!empty($row['password']) && password_verify($password, (string)$row['password'])) {
                unset($row['password']);
                $row['id'] = (int)$row['id'];
                return $row;
            }
        }
    } catch (PDOException $e) {
        return null;
    }

    return null;
}
