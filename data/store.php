<?php
require_once __DIR__ . '/../config/db.php';

/**
 * Chuẩn hóa 1 dòng sản phẩm từ MySQL sang cấu trúc mảng dùng trên giao diện
 */
function normalizeProductRow(array $p): array
{
    $specs = $p['specs'] ?? [];
    if (is_string($specs)) {
        $specs = $specs !== '' ? array_map('trim', explode(',', $specs)) : [];
    }

    $categorySlug = (string)($p['category'] ?? $p['category_slug'] ?? 'laptop');
    $brandName = (string)($p['brand_name'] ?? $p['brand'] ?? 'ĐOÀN 6');
    $isFlashSale = (bool)($p['is_flash_sale'] ?? $p['flash_sale'] ?? false);

    return [
        'id' => (int)($p['id'] ?? 0),
        'category_id' => isset($p['category_id']) ? (int)$p['category_id'] : 0,
        'category_slug' => $categorySlug,
        'category_name' => (string)($p['category_name'] ?? ''),
        'category' => $categorySlug,
        'brand_id' => isset($p['brand_id']) && $p['brand_id'] !== null ? (int)$p['brand_id'] : null,
        'brand_slug' => (string)($p['brand_slug'] ?? ''),
        'brand' => $brandName,
        'slug' => (string)($p['slug'] ?? ''),
        'name' => (string)($p['name'] ?? ''),
        'image' => (string)($p['image'] ?? 'assets/images/products/laptop1.jpg'),
        'old_price' => (int)round((float)($p['old_price'] ?? 0)),
        'price' => (int)round((float)($p['price'] ?? 0)),
        'discount' => (int)($p['discount'] ?? 0),
        'fast_delivery' => (bool)($p['fast_delivery'] ?? true),
        'specs' => $specs,
        'description' => (string)($p['description'] ?? ''),
        'promo' => (string)($p['promo'] ?? ''),
        'sold_percent' => (int)($p['sold_percent'] ?? 0),
        'sold_text' => (string)($p['sold_text'] ?? ''),
        'flash_sale' => $isFlashSale,
        'is_flash_sale' => $isFlashSale,
        'is_featured' => (bool)($p['is_featured'] ?? false),
        'stock' => isset($p['stock']) ? (int)$p['stock'] : 20,
        'component_type' => (string)($p['component_type'] ?? 'none'),
        'socket' => isset($p['socket']) ? (string)$p['socket'] : null,
        'ram_type' => isset($p['ram_type']) ? (string)$p['ram_type'] : null,
        'tdp_watt' => (int)($p['tdp_watt'] ?? 0),
        'psu_watt' => (int)($p['psu_watt'] ?? 0),
        'warranty_months' => isset($p['warranty_months']) ? (int)$p['warranty_months'] : 24,
        'created_at' => (string)($p['created_at'] ?? ''),
    ];
}

function getDefaultCategories(): array
{
    return [
        ['id' => 1, 'name' => 'Laptop', 'slug' => 'laptop', 'icon' => 'fa-laptop', 'description' => 'Laptop văn phòng, học tập, thiết kế mỏng nhẹ chính hãng'],
        ['id' => 2, 'name' => 'Laptop Gaming', 'slug' => 'laptop-gaming', 'icon' => 'fa-gamepad', 'description' => 'Laptop cấu hình cao, màn hình tần số quét lớn cho game thủ'],
        ['id' => 3, 'name' => 'PC ĐOÀN 6', 'slug' => 'pc-gvn', 'icon' => 'fa-desktop', 'description' => 'Bộ PC lắp ráp sẵn chuẩn hiệu năng tại ĐOÀN 6'],
        ['id' => 4, 'name' => 'Màn hình máy tính', 'slug' => 'man-hinh', 'icon' => 'fa-tv', 'description' => 'Màn hình Gaming 144Hz-240Hz, đồ họa 2K/4K sắc nét'],
        ['id' => 5, 'name' => 'Bàn phím & Chuột', 'slug' => 'ban-phim-chuot', 'icon' => 'fa-keyboard', 'description' => 'Bàn phím cơ custom, chuột gaming không dây siêu nhẹ'],
        ['id' => 6, 'name' => 'Tai nghe & Loa', 'slug' => 'tai-nghe-loa', 'icon' => 'fa-headphones', 'description' => 'Tai nghe gaming 7.1, micro và loa máy tính'],
        ['id' => 7, 'name' => 'Linh kiện máy tính', 'slug' => 'linh-kien', 'icon' => 'fa-microchip', 'description' => 'CPU, Mainboard, RAM, VGA, SSD, Nguồn, Case, Tản nhiệt lắp PC'],
        ['id' => 8, 'name' => 'Ghế - Bàn Gaming', 'slug' => 'ghe-ban-gaming', 'icon' => 'fa-chair', 'description' => 'Ghế công thái học và bàn gaming chuyên dụng'],
        ['id' => 9, 'name' => 'Thiết bị văn phòng', 'slug' => 'thiet-bi-van-phong', 'icon' => 'fa-print', 'description' => 'Máy in, máy scan và thiết bị hỗ trợ văn phòng'],
        ['id' => 10, 'name' => 'Thiết bị mạng', 'slug' => 'thiet-bi-mang', 'icon' => 'fa-wifi', 'description' => 'Router Wi-Fi 6/7, bộ phát mesh và card mạng'],
        ['id' => 11, 'name' => 'Phụ kiện & Cáp sạc', 'slug' => 'phu-kien', 'icon' => 'fa-plug', 'description' => 'Cáp chuyển đổi, hub USB-C, giá đỡ và phụ kiện'],
        ['id' => 12, 'name' => 'Tay cầm chơi game', 'slug' => 'tay-cam', 'icon' => 'fa-gamepad', 'description' => 'Tay cầm không dây tương thích PC và Console'],
        ['id' => 13, 'name' => 'Console & Handheld', 'slug' => 'console', 'icon' => 'fa-vr-cardboard', 'description' => 'Máy chơi game cầm tay và hệ thống console'],
        ['id' => 14, 'name' => 'Studio & Livestream', 'slug' => 'studio', 'icon' => 'fa-microphone', 'description' => 'Webcam, capture card, đèn livestream chuyên nghiệp'],
        ['id' => 15, 'name' => 'Hàng Cũ - Đổi Trả', 'slug' => 'hang-cu', 'icon' => 'fa-tags', 'description' => 'Sản phẩm thu cũ đổi mới được kiểm định kỹ thuật'],
        ['id' => 16, 'name' => 'Dịch vụ IT & Sửa chữa', 'slug' => 'dich-vu', 'icon' => 'fa-tools', 'description' => 'Vệ sinh, bảo trì, lắp ráp PC và nâng cấp phần cứng'],
    ];
}

function getDefaultProducts(): array
{
    $defaults = [
        [
            'id' => 1, 'category_id' => 2, 'brand_id' => 11,
            'slug' => 'laptop-gaming-acer-nitro-v-15',
            'name' => 'Laptop Gaming Acer Nitro V 15 ANV15-51-58AN (i5-13420H / RTX 2050 / 16GB / 512GB)',
            'category' => 'laptop-gaming', 'brand' => 'Acer',
            'image' => 'assets/images/products/laptop1.jpg',
            'old_price' => 24990000, 'price' => 16990000, 'discount' => 32, 'fast_delivery' => true,
            'specs' => ['i5-13420H', 'RTX 2050', '16GB RAM', '512GB SSD', '15.6" 144Hz'],
            'promo' => 'Tặng Balo Acer Predator SUV + Chuột Gaming',
            'sold_percent' => 82, 'sold_text' => 'Đã bán 41/50 suất', 'flash_sale' => true, 'stock' => 9, 'warranty_months' => 24,
        ],
        [
            'id' => 2, 'category_id' => 3, 'brand_id' => 16,
            'slug' => 'pc-gvn-intel-i5-rtx-4060',
            'name' => 'PC ĐOÀN 6 Intel i5-12400F / VGA RTX 4060 8GB / RAM 16GB / SSD 500GB',
            'category' => 'pc-gvn', 'brand' => 'ĐOÀN 6',
            'image' => 'assets/images/products/pc1.jpg',
            'old_price' => 22500000, 'price' => 17990000, 'discount' => 20, 'fast_delivery' => true,
            'specs' => ['i5-12400F', 'RTX 4060', '16GB DDR4', '500GB NVMe'],
            'promo' => 'Tặng kèm lót chuột size XL + Miễn phí lắp đặt',
            'sold_percent' => 65, 'sold_text' => 'Đã bán 13/20 suất', 'flash_sale' => true, 'stock' => 7, 'warranty_months' => 36,
        ],
        [
            'id' => 3, 'category_id' => 4, 'brand_id' => 11,
            'slug' => 'man-hinh-acer-nitro-vg240y-180hz',
            'name' => 'Màn hình Acer Nitro VG240Y M3 24" IPS 180Hz 0.5ms Chuyên Game',
            'category' => 'man-hinh', 'brand' => 'Acer',
            'image' => 'assets/images/products/monitor1.jpg',
            'old_price' => 4590000, 'price' => 2890000, 'discount' => 37, 'fast_delivery' => true,
            'specs' => ['24 inch', 'IPS', '180Hz', '0.5ms', 'FHD'],
            'promo' => 'Giảm thêm 200K khi mua kèm PC/Laptop',
            'sold_percent' => 90, 'sold_text' => 'Sắp hết suất', 'flash_sale' => true, 'stock' => 5, 'warranty_months' => 36,
        ],
        [
            'id' => 4, 'category_id' => 5, 'brand_id' => 13,
            'slug' => 'ban-phim-co-akko-3098b-multi-modes',
            'name' => 'Bàn phím cơ Akko 3098B Multi-modes Prunus Lannesiana Hotswap RGB',
            'category' => 'ban-phim-chuot', 'brand' => 'AKKO',
            'image' => 'assets/images/products/keyboard1.jpg',
            'old_price' => 2190000, 'price' => 1390000, 'discount' => 37, 'fast_delivery' => true,
            'specs' => ['3 Modes', 'Hotswap', 'RGB', 'PBT Keycap'],
            'promo' => 'Tặng kèm kê tay gỗ cao cấp',
            'sold_percent' => 55, 'sold_text' => 'Đã bán 22/40 suất', 'flash_sale' => true, 'stock' => 18, 'warranty_months' => 12,
        ],
        [
            'id' => 5, 'category_id' => 5, 'brand_id' => 8,
            'slug' => 'chuot-logitech-g-pro-x-superlight-2',
            'name' => 'Chuột không dây Logitech G Pro X Superlight 2 Lightspeed Wireless',
            'category' => 'ban-phim-chuot', 'brand' => 'Logitech',
            'image' => 'assets/images/products/mouse1.jpg',
            'old_price' => 3890000, 'price' => 2990000, 'discount' => 23, 'fast_delivery' => true,
            'specs' => ['60g', 'HERO 2 32K', 'Lightspeed', '95h Pin'],
            'promo' => 'Tặng lót chuột Gaming size XL',
            'sold_percent' => 70, 'sold_text' => 'Đã bán 14/20 suất', 'flash_sale' => true, 'stock' => 6, 'warranty_months' => 24,
        ],
        [
            'id' => 6, 'category_id' => 3, 'brand_id' => 16,
            'slug' => 'pc-gvn-intel-i3-12100f-gtx-1650',
            'name' => 'PC ĐOÀN 6 Intel i3-12100F / VGA GTX 1650 4GB / RAM 16GB / SSD 256GB',
            'category' => 'pc-gvn', 'brand' => 'ĐOÀN 6',
            'image' => 'assets/images/products/pc2.jpg',
            'old_price' => 12990000, 'price' => 9990000, 'discount' => 23, 'fast_delivery' => true,
            'specs' => ['i3-12100F', 'GTX 1650', '16GB RAM', '256GB SSD'],
            'promo' => 'Tặng bộ Phím Chuột Gaming trị giá 500K',
            'flash_sale' => false, 'stock' => 15, 'warranty_months' => 24,
        ],
        [
            'id' => 7, 'category_id' => 3, 'brand_id' => 16,
            'slug' => 'pc-gvn-intel-i7-14700k-rtx-4070-super',
            'name' => 'PC ĐOÀN 6 Intel i7-14700K / VGA RTX 4070 Super 12GB / RAM 32GB DDR5',
            'category' => 'pc-gvn', 'brand' => 'ĐOÀN 6',
            'image' => 'assets/images/products/pc3.jpg',
            'old_price' => 45990000, 'price' => 39990000, 'discount' => 13, 'fast_delivery' => true,
            'specs' => ['i7-14700K', 'RTX 4070S', '32GB DDR5', '1TB NVMe'],
            'promo' => 'Tặng Tản nhiệt nước AIO 360mm',
            'flash_sale' => false, 'stock' => 8, 'warranty_months' => 36,
        ],
        [
            'id' => 8, 'category_id' => 3, 'brand_id' => 16,
            'slug' => 'pc-gvn-ryzen-5-7600-rtx-4060-ti',
            'name' => 'PC ĐOÀN 6 AMD Ryzen 5 7600 / VGA RTX 4060 Ti 8GB / RAM 32GB DDR5',
            'category' => 'pc-gvn', 'brand' => 'ĐOÀN 6',
            'image' => 'assets/images/products/pc4.jpg',
            'old_price' => 29990000, 'price' => 25490000, 'discount' => 15, 'fast_delivery' => true,
            'specs' => ['Ryzen 5 7600', 'RTX 4060Ti', '32GB DDR5', '500GB SSD'],
            'promo' => 'Miễn phí lắp đặt & giao hàng hỏa tốc',
            'flash_sale' => false, 'stock' => 12, 'warranty_months' => 36,
        ],
        [
            'id' => 9, 'category_id' => 2, 'brand_id' => 1,
            'slug' => 'laptop-gaming-asus-tuf-f15',
            'name' => 'Laptop Gaming ASUS TUF Gaming F15 FX507ZC4-HN095W (i5-12500H / RTX 3050)',
            'category' => 'laptop-gaming', 'brand' => 'ASUS',
            'image' => 'assets/images/products/laptop2.jpg',
            'old_price' => 25990000, 'price' => 19490000, 'discount' => 25, 'fast_delivery' => true,
            'specs' => ['i5-12500H', 'RTX 3050', '16GB RAM', '512GB SSD', '144Hz'],
            'promo' => 'Tặng Combo Gear ASUS trị giá 1.5 triệu',
            'flash_sale' => false, 'stock' => 20, 'warranty_months' => 24,
        ],
        [
            'id' => 10, 'category_id' => 2, 'brand_id' => 2,
            'slug' => 'laptop-gaming-msi-katana-15',
            'name' => 'Laptop Gaming MSI Katana 15 B13VEK-252VN (i7-13620H / RTX 4050)',
            'category' => 'laptop-gaming', 'brand' => 'MSI',
            'image' => 'assets/images/products/laptop3.jpg',
            'old_price' => 31990000, 'price' => 25990000, 'discount' => 19, 'fast_delivery' => true,
            'specs' => ['i7-13620H', 'RTX 4050', '16GB DDR5', '512GB SSD'],
            'promo' => 'Tặng Balo MSI Gaming + Chuột',
            'flash_sale' => false, 'stock' => 14, 'warranty_months' => 24,
        ],
        [
            'id' => 11, 'category_id' => 2, 'brand_id' => 12,
            'slug' => 'laptop-gaming-lenovo-loq-15',
            'name' => 'Laptop Gaming Lenovo LOQ 15IAX9 83GS001RVN (i5-12450HX / RTX 3050 6GB)',
            'category' => 'laptop-gaming', 'brand' => 'Lenovo',
            'image' => 'assets/images/products/laptop4.jpg',
            'old_price' => 23990000, 'price' => 18990000, 'discount' => 21, 'fast_delivery' => true,
            'specs' => ['i5-12450HX', 'RTX 3050 6GB', '16GB RAM', '100% sRGB'],
            'promo' => 'Tặng Balo Lenovo Gaming',
            'flash_sale' => false, 'stock' => 16, 'warranty_months' => 24,
        ],
        [
            'id' => 12, 'category_id' => 1, 'brand_id' => 1,
            'slug' => 'laptop-asus-vivobook-15-oled',
            'name' => 'Laptop ASUS Vivobook 15 OLED A1505VA-L1114W (i5-13500H / 16GB / 512GB)',
            'category' => 'laptop', 'brand' => 'ASUS',
            'image' => 'assets/images/products/laptop3.jpg',
            'old_price' => 19990000, 'price' => 16490000, 'discount' => 18, 'fast_delivery' => true,
            'specs' => ['i5-13500H', '16GB RAM', '512GB SSD', '15.6" OLED'],
            'promo' => 'Giảm thêm 1 Triệu cho HSSV',
            'flash_sale' => false, 'stock' => 22, 'warranty_months' => 24,
        ],
        [
            'id' => 13, 'category_id' => 1, 'brand_id' => 12,
            'slug' => 'laptop-lenovo-ideapad-slim-5',
            'name' => 'Laptop Lenovo IdeaPad Slim 5 14IMH9 (Core Ultra 5 125H / 16GB / 512GB)',
            'category' => 'laptop', 'brand' => 'Lenovo',
            'image' => 'assets/images/products/laptop4.jpg',
            'old_price' => 22990000, 'price' => 18990000, 'discount' => 17, 'fast_delivery' => true,
            'specs' => ['Core Ultra 5', 'AI Boost', '16GB RAM', '14" OLED'],
            'promo' => 'Tặng chuột không dây Lenovo',
            'flash_sale' => false, 'stock' => 11, 'warranty_months' => 24,
        ],
        [
            'id' => 14, 'category_id' => 4, 'brand_id' => 1,
            'slug' => 'man-hinh-asus-tuf-vg27aq3a-180hz',
            'name' => 'Màn hình ASUS TUF Gaming VG27AQ3A 27" Fast IPS 2K 180Hz G-Sync',
            'category' => 'man-hinh', 'brand' => 'ASUS',
            'image' => 'assets/images/products/monitor2.jpg',
            'old_price' => 7990000, 'price' => 5990000, 'discount' => 25, 'fast_delivery' => true,
            'specs' => ['27 inch', '2K QHD', 'Fast IPS', '180Hz', '1ms'],
            'promo' => 'Bảo hành 3 năm chính hãng',
            'flash_sale' => false, 'stock' => 19, 'warranty_months' => 36,
        ],
        [
            'id' => 15, 'category_id' => 6, 'brand_id' => 14,
            'slug' => 'tai-nghe-hyperx-cloud-iii',
            'name' => 'Tai nghe Gaming HyperX Cloud III Đỏ Đen (DTS Spatial Audio)',
            'category' => 'tai-nghe-loa', 'brand' => 'HyperX',
            'image' => 'assets/images/products/headset1.jpg',
            'old_price' => 2690000, 'price' => 1990000, 'discount' => 26, 'fast_delivery' => true,
            'specs' => ['53mm Driver', 'DTS:X', 'Mic chống ồn', 'USB-C/3.5mm'],
            'promo' => 'Tặng giá treo tai nghe Gaming',
            'flash_sale' => false, 'stock' => 30, 'warranty_months' => 24,
        ],
    ];

    return array_map('normalizeProductRow', $defaults);
}

function getBrands(): array
{
    $pdo = getPDO();
    if ($pdo) {
        try {
            $rows = $pdo->query('SELECT id, name, slug, country, logo, description FROM brands ORDER BY name ASC')->fetchAll();
            if (!empty($rows)) {
                return $rows;
            }
        } catch (PDOException $e) {
            // Fallback bên dưới
        }
    }

    return [
        ['id' => 1, 'name' => 'ASUS', 'slug' => 'asus', 'country' => 'Đài Loan', 'logo' => null, 'description' => 'Laptop, Mainboard, VGA, Màn hình'],
        ['id' => 2, 'name' => 'MSI', 'slug' => 'msi', 'country' => 'Đài Loan', 'logo' => null, 'description' => 'Laptop Gaming, VGA, Mainboard'],
        ['id' => 11, 'name' => 'Acer', 'slug' => 'acer', 'country' => 'Đài Loan', 'logo' => null, 'description' => 'Laptop Gaming Nitro, Màn hình'],
        ['id' => 12, 'name' => 'Lenovo', 'slug' => 'lenovo', 'country' => 'Trung Quốc', 'logo' => null, 'description' => 'Laptop LOQ, IdeaPad'],
        ['id' => 16, 'name' => 'ĐOÀN 6', 'slug' => 'doan-6', 'country' => 'Việt Nam', 'logo' => null, 'description' => 'PC lắp ráp ĐOÀN 6'],
    ];
}

function getCategories(): array
{
    $pdo = getPDO();
    if ($pdo) {
        try {
            $stmt = $pdo->query('SELECT id, name, slug, icon, description FROM categories ORDER BY id ASC');
            $rows = $stmt->fetchAll();
            if (!empty($rows)) {
                return $rows;
            }
        } catch (PDOException $e) {
            // Fallback bên dưới
        }
    }
    return $_SESSION['admin_categories'] ?? getDefaultCategories();
}

function getAllProducts(): array
{
    $pdo = getPDO();
    if ($pdo) {
        try {
            $sql = '
                SELECT p.*,
                       c.slug AS category_slug,
                       c.name AS category_name,
                       COALESCE(b.name, p.brand) AS brand_name,
                       b.slug AS brand_slug
                FROM products p
                LEFT JOIN categories c ON c.id = p.category_id
                LEFT JOIN brands b ON b.id = p.brand_id
                ORDER BY p.id DESC
            ';
            $rows = $pdo->query($sql)->fetchAll();
            if (!empty($rows)) {
                return array_map('normalizeProductRow', $rows);
            }
        } catch (PDOException $e) {
            // Fallback bên dưới
        }
    }
    return $_SESSION['admin_products'] ?? getDefaultProducts();
}

function getProductImages(int $productId): array
{
    $pdo = getPDO();
    if ($pdo && $productId > 0) {
        try {
            $stmt = $pdo->prepare('
                SELECT id, product_id, image_url, is_primary, sort_order
                FROM product_images
                WHERE product_id = ?
                ORDER BY is_primary DESC, sort_order ASC, id ASC
            ');
            $stmt->execute([$productId]);
            $rows = $stmt->fetchAll();
            if (!empty($rows)) {
                return $rows;
            }
        } catch (PDOException $e) {
            // Fallback bên dưới
        }
    }

    $product = getProductById($productId);
    if ($product && !empty($product['image'])) {
        return [[
            'id' => 0,
            'product_id' => $productId,
            'image_url' => $product['image'],
            'is_primary' => 1,
            'sort_order' => 0,
        ]];
    }

    return [];
}

function getPcBuilderParts(?string $componentType = null): array
{
    $allowedTypes = ['cpu', 'mainboard', 'ram', 'vga', 'ssd', 'psu', 'case', 'cooling'];
    $pdo = getPDO();
    if ($pdo) {
        try {
            $sql = "
                SELECT p.*,
                       c.slug AS category_slug,
                       c.name AS category_name,
                       COALESCE(b.name, p.brand) AS brand_name,
                       b.slug AS brand_slug
                FROM products p
                LEFT JOIN categories c ON c.id = p.category_id
                LEFT JOIN brands b ON b.id = p.brand_id
                WHERE p.component_type != 'none'
            ";
            $params = [];
            if ($componentType !== null && in_array($componentType, $allowedTypes, true)) {
                $sql .= ' AND p.component_type = ?';
                $params[] = $componentType;
            }
            $sql .= ' ORDER BY p.price ASC, p.id ASC';
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            return array_map('normalizeProductRow', $stmt->fetchAll());
        } catch (PDOException $e) {
            return [];
        }
    }
    return [];
}

function getNews(): array
{
    $pdo = getPDO();
    if ($pdo) {
        try {
            $sql = '
                SELECT n.id, n.title, n.slug, n.image, n.summary, n.content,
                       n.category, n.author_id, n.published_at, u.name AS author_name
                FROM news n
                LEFT JOIN users u ON u.id = n.author_id
                ORDER BY n.published_at DESC, n.id DESC
            ';
            $rows = $pdo->query($sql)->fetchAll();
            if (!empty($rows)) {
                foreach ($rows as &$r) {
                    $r['date'] = !empty($r['published_at'])
                        ? date('d/m/Y', strtotime((string)$r['published_at']))
                        : date('d/m/Y');
                }
                unset($r);
                return $rows;
            }
        } catch (PDOException $e) {
            // Fallback bên dưới
        }
    }

    return [
        [
            'id' => 1,
            'title' => 'Đánh giá RTX 4060 vs RTX 4060 Ti: Card đồ họa nào đáng mua nhất 2026?',
            'slug' => 'danh-gia-rtx-4060-vs-rtx-4060-ti',
            'summary' => 'So sánh hiệu năng thực tế trên các tựa game AAA ở độ phân giải Full HD và 2K, mức tiêu thụ điện năng và giá thành.',
            'content' => 'RTX 4060 và RTX 4060 Ti cùng sử dụng kiến trúc Ada Lovelace với công nghệ DLSS 3 và Frame Generation.',
            'category' => 'Đánh giá VGA',
            'image' => 'assets/images/news/news1.jpg',
            'date' => '15/11/2026',
            'published_at' => '2026-11-15 09:00:00',
        ],
        [
            'id' => 2,
            'title' => 'Top 5 Laptop Gaming dưới 20 triệu đáng mua nhất mùa tựu trường',
            'slug' => 'top-5-laptop-gaming-duoi-20-trieu',
            'summary' => 'Tổng hợp các mẫu laptop trang bị RTX 3050, màn hình 144Hz chuẩn gaming từ Acer, ASUS, Lenovo.',
            'content' => 'Phân khúc laptop gaming dưới 20 triệu đồng đáp ứng tốt nhu cầu học tập, lập trình và chơi game eSports.',
            'category' => 'Tư vấn Laptop',
            'image' => 'assets/images/news/news2.jpg',
            'date' => '14/11/2026',
            'published_at' => '2026-11-14 10:30:00',
        ],
        [
            'id' => 3,
            'title' => 'Hướng dẫn chọn bàn phím cơ cho người mới: Linear, Tactile hay Clicky?',
            'slug' => 'huong-dan-chon-ban-phim-co',
            'summary' => 'Tìm hiểu sự khác biệt giữa các loại switch phổ biến và cách chọn layout phù hợp nhu cầu.',
            'content' => 'Chọn switch Linear, Tactile hoặc Clicky tùy thuộc môi trường làm việc và sở thích gõ phím.',
            'category' => 'Đánh giá Gear',
            'image' => 'assets/images/news/news3.jpg',
            'date' => '12/11/2026',
            'published_at' => '2026-11-12 14:15:00',
        ],
        [
            'id' => 4,
            'title' => 'Đoàn 6 khai trương Showroom mới tại TP.HCM với hàng ngàn deal sốc',
            'slug' => 'doan-6-khai-truong-showroom-moi',
            'summary' => 'Cơ hội săn gear giá 1K, giảm giá lên đến 50% cho các sản phẩm màn hình và linh kiện PC.',
            'content' => 'Showroom mới của ĐOÀN 6 mang đến trải nghiệm Build PC thực tế và bảo hành điện tử chính hãng.',
            'category' => 'Tin cửa hàng',
            'image' => 'assets/images/news/news4.jpg',
            'date' => '10/11/2026',
            'published_at' => '2026-11-10 08:00:00',
        ],
    ];
}

function getProductBySlug(string $slug): array
{
    $pdo = getPDO();
    if ($pdo && $slug !== '') {
        try {
            $stmt = $pdo->prepare('
                SELECT p.*,
                       c.slug AS category_slug,
                       c.name AS category_name,
                       COALESCE(b.name, p.brand) AS brand_name,
                       b.slug AS brand_slug
                FROM products p
                LEFT JOIN categories c ON c.id = p.category_id
                LEFT JOIN brands b ON b.id = p.brand_id
                WHERE p.slug = :slug
                LIMIT 1
            ');
            $stmt->execute(['slug' => $slug]);
            $p = $stmt->fetch();
            if ($p) {
                return normalizeProductRow($p);
            }
        } catch (PDOException $e) {
            // Fallback bên dưới
        }
    }

    $all = getAllProducts();
    foreach ($all as $p) {
        if ($p['slug'] === $slug) {
            return $p;
        }
    }
    return $all[0] ?? [];
}

function getProductById(int $id): ?array
{
    $pdo = getPDO();
    if ($pdo && $id > 0) {
        try {
            $stmt = $pdo->prepare('
                SELECT p.*,
                       c.slug AS category_slug,
                       c.name AS category_name,
                       COALESCE(b.name, p.brand) AS brand_name,
                       b.slug AS brand_slug
                FROM products p
                LEFT JOIN categories c ON c.id = p.category_id
                LEFT JOIN brands b ON b.id = p.brand_id
                WHERE p.id = :id
                LIMIT 1
            ');
            $stmt->execute(['id' => $id]);
            $p = $stmt->fetch();
            if ($p) {
                return normalizeProductRow($p);
            }
        } catch (PDOException $e) {
            // Fallback bên dưới
        }
    }

    foreach (getAllProducts() as $p) {
        if ((int)$p['id'] === $id) {
            return $p;
        }
    }
    return null;
}

function getWarranties(?string $query = null, ?int $userId = null): array
{
    $pdo = getPDO();
    if (!$pdo) {
        return [];
    }

    try {
        $sql = '
            SELECT w.*, o.order_code, o.status AS order_status
            FROM warranties w
            LEFT JOIN orders o ON o.id = w.order_id
            WHERE 1=1
        ';
        $params = [];

        if ($userId !== null && $userId > 0) {
            $sql .= ' AND w.user_id = ?';
            $params[] = $userId;
        }

        $q = trim((string)$query);
        if ($q !== '') {
            $digits = preg_replace('/\D+/', '', $q);
            $qUpper = strtoupper($q);
            $sql .= ' AND (w.warranty_code = ? OR w.warranty_code LIKE ? OR w.serial_number = ? OR w.customer_phone = ? OR o.order_code = ?';
            $params[] = $qUpper;
            $params[] = $qUpper . '-%';
            $params[] = $qUpper;
            $params[] = $q;
            $params[] = $qUpper;
            if ($digits !== '' && $digits !== $q) {
                $sql .= ' OR w.customer_phone = ?';
                $params[] = $digits;
            }
            $sql .= ')';
        }

        $sql .= ' ORDER BY w.id DESC';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        return [];
    }
}

function getTradeIns(?int $userId = null): array
{
    $pdo = getPDO();
    if (!$pdo) {
        return [];
    }

    try {
        $sql = '
            SELECT t.*, p.name AS target_product_name, p.slug AS target_product_slug
            FROM trade_in_requests t
            LEFT JOIN products p ON p.id = t.target_product_id
        ';
        $params = [];
        if ($userId !== null && $userId > 0) {
            $sql .= ' WHERE t.user_id = ?';
            $params[] = $userId;
        }
        $sql .= ' ORDER BY t.id DESC';

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        return [];
    }
}

function createTradeInRequest(PDO $pdo, array $data): array
{
    $customerName = trim((string)($data['customer_name'] ?? ''));
    $customerPhone = preg_replace('/\D+/', '', (string)($data['customer_phone'] ?? ''));
    $deviceName = trim((string)($data['device_name'] ?? ''));
    $deviceCondition = trim((string)($data['device_condition'] ?? ''));
    $estimatedValue = max(0, (float)($data['estimated_value'] ?? 0));
    $targetProductId = !empty($data['target_product_id']) ? (int)$data['target_product_id'] : null;
    $userId = !empty($data['user_id']) ? (int)$data['user_id'] : null;
    $notes = trim((string)($data['notes'] ?? ''));

    if ($customerName === '' || $deviceName === '' || $deviceCondition === '') {
        throw new RuntimeException('Vui lòng nhập đầy đủ thông tin thiết bị và người gửi.');
    }
    if (!preg_match('/^0\d{8,10}$/', $customerPhone)) {
        throw new RuntimeException('Số điện thoại không hợp lệ (cần bắt đầu bằng 0 và dài 9-11 số).');
    }

    $tempCode = 'TC-TMP-' . bin2hex(random_bytes(6));
    $stmt = $pdo->prepare("
        INSERT INTO trade_in_requests (
            request_code, user_id, customer_name, customer_phone, device_name,
            device_condition, estimated_value, target_product_id, notes, status, created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'Chờ định giá', NOW())
    ");
    $stmt->execute([
        $tempCode,
        $userId,
        $customerName,
        $customerPhone,
        $deviceName,
        $deviceCondition,
        $estimatedValue,
        $targetProductId,
        $notes !== '' ? $notes : null,
    ]);

    $id = (int)$pdo->lastInsertId();
    $requestCode = 'TC' . str_pad((string)$id, 5, '0', STR_PAD_LEFT);
    $pdo->prepare('UPDATE trade_in_requests SET request_code = ? WHERE id = ?')->execute([$requestCode, $id]);

    $fetch = $pdo->prepare('SELECT * FROM trade_in_requests WHERE id = ?');
    $fetch->execute([$id]);
    return $fetch->fetch() ?: ['id' => $id, 'request_code' => $requestCode];
}

/**
 * Tạo đơn hàng nguyên tử: khóa tồn kho FOR UPDATE, trừ kho, sinh bảo hành điện tử theo từng đơn vị sản phẩm.
 * Chỉ hỗ trợ COD và VietQR.
 */
function createOrderWithItems(PDO $pdo, array $orderData, array $items): array
{
    if (empty($items)) {
        throw new RuntimeException('Đơn hàng phải có ít nhất 1 sản phẩm.');
    }

    $rawPayment = preg_replace('/\s+/u', ' ', trim((string)($orderData['payment_method'] ?? 'COD')));
    if (preg_match('/trả\s*góp|tra\s*gop/iu', $rawPayment)) {
        throw new RuntimeException('Không hỗ trợ thanh toán trả góp. Chỉ hỗ trợ COD hoặc VietQR.');
    }
    $normalizedPayment = mb_strtolower($rawPayment, 'UTF-8');
    $codLabels = ['cod', 'thanh toán khi nhận hàng (cod)', 'thanh toán khi nhận hàng'];
    $vietQrLabels = ['vietqr', 'chuyển khoản vietqr', 'quét mã vietqr'];
    if (in_array($normalizedPayment, $codLabels, true)) {
        $paymentMethod = 'COD';
    } elseif (in_array($normalizedPayment, $vietQrLabels, true)) {
        $paymentMethod = 'VietQR';
    } else {
        throw new RuntimeException('Phương thức thanh toán không hợp lệ. Chỉ hỗ trợ COD hoặc VietQR.');
    }

    $orderType = (string)($orderData['order_type'] ?? 'online');
    if (!in_array($orderType, ['online', 'walk_in', 'pc_build'], true)) {
        throw new RuntimeException('Loại đơn hàng không hợp lệ.');
    }

    $customerName = trim((string)($orderData['customer_name'] ?? ''));
    $customerPhone = preg_replace('/\D+/', '', (string)($orderData['customer_phone'] ?? ''));
    $customerEmail = trim((string)($orderData['customer_email'] ?? ''));
    $customerAddress = trim((string)($orderData['customer_address'] ?? ''));
    if ($customerAddress === '' && $orderType === 'walk_in') {
        $customerAddress = 'Mua trực tiếp tại Showroom ĐOÀN 6';
    }
    $note = trim((string)($orderData['note'] ?? ''));
    $userId = !empty($orderData['user_id']) ? (int)$orderData['user_id'] : null;
    $staffId = !empty($orderData['created_by_staff_id']) ? (int)$orderData['created_by_staff_id'] : null;

    $pcBuildSpecs = $orderData['pc_build_specs'] ?? null;
    if (is_array($pcBuildSpecs)) {
        $pcBuildSpecs = json_encode($pcBuildSpecs, JSON_UNESCAPED_UNICODE);
    } elseif ($pcBuildSpecs !== null) {
        $pcBuildSpecs = (string)$pcBuildSpecs;
    }

    if ($customerName === '' || $customerAddress === '') {
        throw new RuntimeException('Vui lòng nhập đầy đủ họ tên và địa chỉ nhận hàng.');
    }
    if (!preg_match('/^0\d{8,10}$/', $customerPhone)) {
        throw new RuntimeException('Số điện thoại không hợp lệ (cần bắt đầu bằng 0 và dài 9-11 số).');
    }

    $requestedTotals = [];
    $normalizedItems = [];
    foreach ($items as $it) {
        $pid = (int)($it['product_id'] ?? $it['id'] ?? 0);
        $qty = (int)($it['quantity'] ?? $it['qty'] ?? 0);
        $slot = isset($it['component_slot']) && $it['component_slot'] !== ''
            ? substr(trim((string)$it['component_slot']), 0, 30)
            : null;
        if ($pid <= 0 || $qty <= 0) {
            throw new RuntimeException('Thông tin sản phẩm hoặc số lượng không hợp lệ.');
        }
        $requestedTotals[$pid] = ($requestedTotals[$pid] ?? 0) + $qty;
        $normalizedItems[] = [
            'product_id' => $pid,
            'quantity' => $qty,
            'component_slot' => $slot,
        ];
    }

    $productIds = array_keys($requestedTotals);
    sort($productIds, SORT_NUMERIC);

    $paymentStatus = (string)($orderData['payment_status'] ?? 'unpaid');
    if (!in_array($paymentStatus, ['unpaid', 'pending_confirm', 'paid', 'expired'], true)) {
        $paymentStatus = 'unpaid';
    }

    $startedTx = !$pdo->inTransaction();
    if ($startedTx) {
        $pdo->beginTransaction();
    }

    try {
        $lockedProducts = [];
        $lockStmt = $pdo->prepare('SELECT id, name, price, stock, warranty_months FROM products WHERE id = ? FOR UPDATE');
        foreach ($productIds as $pid) {
            $lockStmt->execute([$pid]);
            $prod = $lockStmt->fetch();
            if (!$prod) {
                throw new RuntimeException('Sản phẩm #' . $pid . ' không tồn tại.');
            }
            $needQty = $requestedTotals[$pid];
            if ((int)$prod['stock'] < $needQty) {
                throw new RuntimeException('Sản phẩm "' . $prod['name'] . '" chỉ còn ' . (int)$prod['stock'] . ' trong kho.');
            }
            $lockedProducts[$pid] = $prod;
        }

        $totalAmount = 0.0;
        foreach ($normalizedItems as $it) {
            $prod = $lockedProducts[$it['product_id']];
            $totalAmount += ((float)$prod['price']) * $it['quantity'];
        }

        $tempCode = 'DH-TMP-' . bin2hex(random_bytes(6));
        $expiresSql = $paymentMethod === 'VietQR' ? 'DATE_ADD(NOW(), INTERVAL 15 MINUTE)' : 'NULL';
        $insertOrderSql = "
            INSERT INTO orders (
                order_code, user_id, created_by_staff_id, order_type,
                customer_name, customer_phone, customer_email, customer_address, note,
                payment_method, payment_status, payment_expires_at,
                total_amount, status, pc_build_specs, created_at
            ) VALUES (
                ?, ?, ?, ?,
                ?, ?, ?, ?, ?,
                ?, ?, {$expiresSql},
                ?, 'Chờ xác nhận', ?, NOW()
            )
        ";
        $orderStmt = $pdo->prepare($insertOrderSql);
        $orderStmt->execute([
            $tempCode,
            $userId,
            $staffId,
            $orderType,
            $customerName,
            $customerPhone,
            $customerEmail !== '' ? $customerEmail : null,
            $customerAddress,
            $note !== '' ? $note : null,
            $paymentMethod,
            $paymentStatus,
            $totalAmount,
            $pcBuildSpecs,
        ]);

        $orderId = (int)$pdo->lastInsertId();
        $orderCode = 'DH' . str_pad((string)$orderId, 5, '0', STR_PAD_LEFT);
        $pdo->prepare('UPDATE orders SET order_code = ? WHERE id = ?')->execute([$orderCode, $orderId]);

        $itemInsert = $pdo->prepare('
            INSERT INTO order_items (order_id, product_id, product_name, product_price, quantity, component_slot)
            VALUES (?, ?, ?, ?, ?, ?)
        ');
        $stockUpdate = $pdo->prepare('UPDATE products SET stock = stock - ? WHERE id = ? AND stock >= ?');
        $warrantyInsert = $pdo->prepare("
            INSERT INTO warranties (
                warranty_code, order_id, order_item_id, product_id, user_id,
                customer_phone, product_name, serial_number, warranty_months,
                start_date, end_date, status, notes, created_at
            ) VALUES (
                ?, ?, ?, ?, ?,
                ?, ?, ?, ?,
                CURDATE(), DATE_ADD(CURDATE(), INTERVAL ? MONTH), 'Còn hạn', ?, NOW()
            )
        ");

        $createdItems = [];
        $createdWarranties = [];
        $serialSeq = 1;

        foreach ($normalizedItems as $it) {
            $prod = $lockedProducts[$it['product_id']];
            $unitPrice = (float)$prod['price'];
            $warrantyMonths = max(1, (int)($prod['warranty_months'] ?? 24));

            $itemInsert->execute([
                $orderId,
                $it['product_id'],
                $prod['name'],
                $unitPrice,
                $it['quantity'],
                $it['component_slot'],
            ]);
            $orderItemId = (int)$pdo->lastInsertId();

            $stockUpdate->execute([$it['quantity'], $it['product_id'], $it['quantity']]);
            if ($stockUpdate->rowCount() !== 1) {
                throw new RuntimeException('Không thể cập nhật tồn kho cho sản phẩm #' . $it['product_id']);
            }

            $createdItems[] = [
                'id' => $orderItemId,
                'order_id' => $orderId,
                'product_id' => $it['product_id'],
                'product_name' => $prod['name'],
                'name' => $prod['name'],
                'product_price' => (int)round($unitPrice),
                'price' => (int)round($unitPrice),
                'quantity' => $it['quantity'],
                'qty' => $it['quantity'],
                'component_slot' => $it['component_slot'],
            ];

            for ($u = 1; $u <= $it['quantity']; $u++) {
                $warrantyCode = sprintf('BH%04d%02d%02d', $orderId, $orderItemId % 100, $u % 100);
                $serialNumber = sprintf('SN-D6-%05d-%04d', $orderId, $serialSeq++);
                $wNote = 'Bảo hành điện tử ' . $warrantyMonths . ' tháng cho đơn ' . $orderCode;
                $warrantyInsert->execute([
                    $warrantyCode,
                    $orderId,
                    $orderItemId,
                    $it['product_id'],
                    $userId,
                    $customerPhone,
                    $prod['name'],
                    $serialNumber,
                    $warrantyMonths,
                    $warrantyMonths,
                    $wNote,
                ]);
                $createdWarranties[] = [
                    'id' => (int)$pdo->lastInsertId(),
                    'warranty_code' => $warrantyCode,
                    'serial_number' => $serialNumber,
                    'product_name' => $prod['name'],
                    'warranty_months' => $warrantyMonths,
                    'status' => 'Còn hạn',
                ];
            }
        }

        if ($startedTx) {
            $pdo->commit();
        }

        $fetch = $pdo->prepare('SELECT * FROM orders WHERE id = ?');
        $fetch->execute([$orderId]);
        $orderRow = $fetch->fetch() ?: ['id' => $orderId, 'order_code' => $orderCode];

        $orderRow['id'] = (int)$orderRow['id'];
        $orderRow['code'] = $orderRow['order_code'];
        $orderRow['phone'] = $orderRow['customer_phone'];
        $orderRow['address'] = $orderRow['customer_address'];
        $orderRow['total_amount'] = (int)round((float)$orderRow['total_amount']);
        $orderRow['total_price'] = $orderRow['total_amount'];
        $orderRow['items'] = $createdItems;
        $orderRow['warranties'] = $createdWarranties;

        return $orderRow;
    } catch (Throwable $e) {
        if ($startedTx && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}

/**
 * Chuyển trạng thái đơn hàng có kiểm soát pipeline (đặc biệt pc_build) và hoàn kho/hủy bảo hành khi Đã hủy.
 */
function transitionOrderStatus(PDO $pdo, int $orderId, string $newStatus, ?string $newPaymentStatus = null): array
{
    $allowedStatuses = ['Chờ xác nhận', 'Đang lắp ráp', 'Đang giao hàng', 'Đang giao', 'Hoàn thành', 'Đã hủy'];
    if (!in_array($newStatus, $allowedStatuses, true)) {
        throw new RuntimeException('Trạng thái đơn hàng không hợp lệ.');
    }
    if ($newPaymentStatus !== null && !in_array($newPaymentStatus, ['unpaid', 'pending_confirm', 'paid', 'expired'], true)) {
        throw new RuntimeException('Trạng thái thanh toán không hợp lệ.');
    }

    $startedTx = !$pdo->inTransaction();
    if ($startedTx) {
        $pdo->beginTransaction();
    }

    try {
        $stmt = $pdo->prepare('SELECT * FROM orders WHERE id = ? FOR UPDATE');
        $stmt->execute([$orderId]);
        $order = $stmt->fetch();
        if (!$order) {
            throw new RuntimeException('Không tìm thấy đơn hàng #' . $orderId);
        }

        $oldStatus = (string)$order['status'];
        $oldPaymentStatus = (string)$order['payment_status'];
        $orderType = (string)($order['order_type'] ?? 'online');

        if (in_array($oldStatus, ['Hoàn thành', 'Đã hủy'], true)) {
            if ($oldStatus !== $newStatus) {
                throw new RuntimeException('Không thể thay đổi trạng thái đơn đã "' . $oldStatus . '".');
            }
            if ($newPaymentStatus !== null && $newPaymentStatus !== $oldPaymentStatus) {
                throw new RuntimeException('Không thể thay đổi trạng thái thanh toán của đơn đã "' . $oldStatus . '".');
            }
        } elseif ($oldStatus !== $newStatus) {
            if ($orderType === 'pc_build') {
                $allowedNext = match ($oldStatus) {
                    'Chờ xác nhận' => ['Đang lắp ráp', 'Đã hủy'],
                    'Đang lắp ráp' => ['Đang giao hàng', 'Đã hủy'],
                    'Đang giao hàng', 'Đang giao' => ['Hoàn thành', 'Đã hủy'],
                    default => [],
                };
            } else {
                $allowedNext = match ($oldStatus) {
                    'Chờ xác nhận' => ['Đang giao hàng', 'Đang giao', 'Hoàn thành', 'Đã hủy'],
                    'Đang giao hàng', 'Đang giao' => ['Hoàn thành', 'Đã hủy'],
                    default => [],
                };
            }

            if (!in_array($newStatus, $allowedNext, true)) {
                throw new RuntimeException('Không thể chuyển trạng thái từ "' . $oldStatus . '" sang "' . $newStatus . '" cho đơn ' . $orderType . '.');
            }
        }

        $finalPaymentStatus = $newPaymentStatus ?? $oldPaymentStatus;
        if ($oldStatus !== 'Hoàn thành' && $newStatus === 'Hoàn thành' && ($order['payment_method'] ?? '') === 'COD' && $newPaymentStatus === null) {
            $finalPaymentStatus = 'paid';
        }

        $upd = $pdo->prepare('UPDATE orders SET status = ?, payment_status = ?, updated_at = NOW() WHERE id = ?');
        $upd->execute([$newStatus, $finalPaymentStatus, $orderId]);

        if ($oldStatus !== 'Đã hủy' && $newStatus === 'Đã hủy') {
            $itemsStmt = $pdo->prepare('SELECT product_id, quantity FROM order_items WHERE order_id = ?');
            $itemsStmt->execute([$orderId]);
            $restock = $pdo->prepare('UPDATE products SET stock = stock + ? WHERE id = ?');
            foreach ($itemsStmt->fetchAll() as $it) {
                $restock->execute([(int)$it['quantity'], (int)$it['product_id']]);
            }

            $pdo->prepare("UPDATE warranties SET status = 'Đã hủy' WHERE order_id = ?")->execute([$orderId]);
        }

        if ($startedTx) {
            $pdo->commit();
        }

        $fetch = $pdo->prepare('SELECT * FROM orders WHERE id = ?');
        $fetch->execute([$orderId]);
        $updated = $fetch->fetch() ?: $order;
        $updated['id'] = (int)$updated['id'];
        $updated['code'] = $updated['order_code'];
        $updated['total_amount'] = (int)round((float)$updated['total_amount']);
        $updated['total_price'] = $updated['total_amount'];

        return $updated;
    } catch (Throwable $e) {
        if ($startedTx && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}

/**
 * Lấy tổng số lượng sản phẩm trong giỏ hàng (mặc định giỏ rỗng [])
 */
function getCartCount(): int
{
    $cart = $_SESSION['cart'] ?? [];
    return array_sum($cart);
}
