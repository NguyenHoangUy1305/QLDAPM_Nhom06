<?php
// Đảm bảo session đã được bật nếu cần cho getCartCount()
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


/**
 * Lấy danh sách danh mục sản phẩm từ CSDL
 */
function getCategories(): array
{
    $pdo = getPDO();
    $stmt = $pdo->query("SELECT name, slug, icon FROM categories");
    return $stmt->fetchAll();
}

/**
 * Lấy toàn bộ danh sách sản phẩm từ CSDL
 */
function getAllProducts(): array
{
    $pdo = getPDO();
    $stmt = $pdo->query("SELECT id, slug, name, category_slug AS category, brand, image, old_price, price, discount, fast_delivery, specs, promo, sold_percent, sold_text, flash_sale FROM products");
    $rows = $stmt->fetchAll();

    foreach ($rows as &$p) {
        // Chuyển đổi specs từ chuỗi trong DB (VD: 'i5,RTX 2050') thành mảng
        $p['specs'] = !empty($p['specs']) ? explode(',', $p['specs']) : [];
        // Ép kiểu dữ liệu số và boolean
        $p['id'] = (int)$p['id'];
        $p['old_price'] = (float)$p['old_price'];
        $p['price'] = (float)$p['price'];
        $p['discount'] = (int)$p['discount'];
        $p['fast_delivery'] = (bool)$p['fast_delivery'];
        $p['sold_percent'] = (int)$p['sold_percent'];
        $p['flash_sale'] = (bool)$p['flash_sale'];
    }

    return $rows;
}

/**
 * Lấy danh sách tin tức từ CSDL
 */
function getNews(): array
{
    $pdo = getPDO();
    $stmt = $pdo->query("SELECT title, summary, image, date FROM news");
    return $stmt->fetchAll();
}

/**
 * Lấy thông tin sản phẩm dựa vào Slug
 */
function getProductBySlug(string $slug): array
{
    $pdo = getPDO();
    $stmt = $pdo->prepare("SELECT id, slug, name, category_slug AS category, brand, image, old_price, price, discount, fast_delivery, specs, promo, sold_percent, sold_text, flash_sale FROM products WHERE slug = :slug LIMIT 1");
    $stmt->execute(['slug' => $slug]);
    $p = $stmt->fetch();

    if ($p) {
        $p['specs'] = !empty($p['specs']) ? explode(',', $p['specs']) : [];
        $p['id'] = (int)$p['id'];
        $p['old_price'] = (float)$p['old_price'];
        $p['price'] = (float)$p['price'];
        $p['discount'] = (int)$p['discount'];
        $p['fast_delivery'] = (bool)$p['fast_delivery'];
        $p['sold_percent'] = (int)$p['sold_percent'];
        $p['flash_sale'] = (bool)$p['flash_sale'];
        return $p;
    }

    // Fallback trả về sản phẩm đầu tiên nếu không tìm thấy slug phù hợp
    $all = getAllProducts();
    return $all[0] ?? [];
}

/**
 * Lấy thông tin sản phẩm dựa vào ID
 */
function getProductById(int $id): ?array
{
    $pdo = getPDO();
    $stmt = $pdo->prepare("SELECT id, slug, name, category_slug AS category, brand, image, old_price, price, discount, fast_delivery, specs, promo, sold_percent, sold_text, flash_sale FROM products WHERE id = :id LIMIT 1");
    $stmt->execute(['id' => $id]);
    $p = $stmt->fetch();

    if ($p) {
        $p['specs'] = !empty($p['specs']) ? explode(',', $p['specs']) : [];
        $p['id'] = (int)$p['id'];
        $p['old_price'] = (float)$p['old_price'];
        $p['price'] = (float)$p['price'];
        $p['discount'] = (int)$p['discount'];
        $p['fast_delivery'] = (bool)$p['fast_delivery'];
        $p['sold_percent'] = (int)$p['sold_percent'];
        $p['flash_sale'] = (bool)$p['flash_sale'];
        return $p;
    }

    return null;
}

/**
 * Lấy tổng số lượng sản phẩm có trong giỏ hàng
 */
function getCartCount(): int
{
    $cart = $_SESSION['cart'] ?? [1 => 1, 4 => 1];
    return array_sum($cart);
}