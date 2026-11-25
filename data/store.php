<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Chuẩn hóa 1 dòng sản phẩm từ MySQL sang cấu trúc mảng dùng trên giao diện
 */
function normalizeProductRow(array $p): array
{
    $specs = $p['specs'] ?? [];
    if (is_string($specs)) {
        $specs = $specs !== '' ? array_map('trim', explode(',', $specs)) : [];
    }
    return [
        'id' => (int)$p['id'],
        'slug' => (string)$p['slug'],
        'name' => (string)$p['name'],
        'category' => (string)($p['category'] ?? $p['category_slug'] ?? 'laptop'),
        'brand' => (string)($p['brand'] ?? 'ACER'),
        'image' => (string)($p['image'] ?? 'assets/images/products/laptop1.jpg'),
        'old_price' => (int)round((float)($p['old_price'] ?? 0)),
        'price' => (int)round((float)($p['price'] ?? 0)),
        'discount' => (int)($p['discount'] ?? 0),
        'fast_delivery' => (bool)($p['fast_delivery'] ?? true),
        'specs' => $specs,
        'promo' => (string)($p['promo'] ?? ''),
        'sold_percent' => (int)($p['sold_percent'] ?? 0),
        'sold_text' => (string)($p['sold_text'] ?? ''),
        'flash_sale' => (bool)($p['flash_sale'] ?? false),
        'stock' => isset($p['stock']) ? (int)$p['stock'] : 20,
    ];
}

function getDefaultCategories(): array
{
    return [
        ['name' => 'Laptop', 'slug' => 'laptop', 'icon' => 'fa-laptop'],
        ['name' => 'Laptop Gaming', 'slug' => 'laptop-gaming', 'icon' => 'fa-gamepad'],
        ['name' => 'PC ĐOÀN 6', 'slug' => 'pc-gvn', 'icon' => 'fa-desktop'],
        ['name' => 'Màn hình máy tính', 'slug' => 'man-hinh', 'icon' => 'fa-tv'],
        ['name' => 'Bàn phím & Chuột', 'slug' => 'ban-phim-chuot', 'icon' => 'fa-keyboard'],
        ['name' => 'Tai nghe & Loa', 'slug' => 'tai-nghe-loa', 'icon' => 'fa-headphones'],
        ['name' => 'Linh kiện máy tính', 'slug' => 'linh-kien', 'icon' => 'fa-microchip'],
        ['name' => 'Ghế - Bàn Gaming', 'slug' => 'ghe-ban-gaming', 'icon' => 'fa-chair'],
        ['name' => 'Thiết bị văn phòng', 'slug' => 'thiet-bi-van-phong', 'icon' => 'fa-print'],
        ['name' => 'Thiết bị mạng', 'slug' => 'thiet-bi-mang', 'icon' => 'fa-wifi'],
        ['name' => 'Phụ kiện & Cáp sạc', 'slug' => 'phu-kien', 'icon' => 'fa-plug'],
        ['name' => 'Tay cầm chơi game', 'slug' => 'tay-cam', 'icon' => 'fa-gamepad'],
        ['name' => 'Console & Handheld', 'slug' => 'console', 'icon' => 'fa-vr-cardboard'],
        ['name' => 'Studio & Livestream', 'slug' => 'studio', 'icon' => 'fa-microphone'],
        ['name' => 'Hàng Cũ - Đổi Trả', 'slug' => 'hang-cu', 'icon' => 'fa-tags'],
        ['name' => 'Dịch vụ IT & Sửa chữa', 'slug' => 'dich-vu', 'icon' => 'fa-tools'],
    ];
}

function getDefaultProducts(): array
{
    return [
        [
            'id' => 1,
            'slug' => 'laptop-gaming-acer-nitro-v-15',
            'name' => 'Laptop Gaming Acer Nitro V 15 ANV15-51-58AN (i5-13420H / RTX 2050 / 16GB / 512GB)',
            'category' => 'laptop-gaming',
            'brand' => 'ACER',
            'image' => 'assets/images/products/laptop1.jpg',
            'old_price' => 24990000,
            'price' => 16990000,
            'discount' => 32,
            'fast_delivery' => true,
            'specs' => ['i5-13420H', 'RTX 2050', '16GB RAM', '512GB SSD', '15.6" 144Hz'],
            'promo' => 'Tặng Balo Acer Predator SUV + Chuột Gaming',
            'sold_percent' => 82,
            'sold_text' => 'Đã bán 41/50 suất',
            'flash_sale' => true,
            'stock' => 9,
        ],
        [
            'id' => 2,
            'slug' => 'pc-gvn-intel-i5-rtx-4060',
            'name' => 'PC ĐOÀN 6 Intel i5-12400F / VGA RTX 4060 8GB / RAM 16GB / SSD 500GB',
            'category' => 'pc-gvn',
            'brand' => 'PC I5',
            'image' => 'assets/images/products/pc1.jpg',
            'old_price' => 22500000,
            'price' => 17990000,
            'discount' => 20,
            'fast_delivery' => true,
            'specs' => ['i5-12400F', 'RTX 4060', '16GB DDR4', '500GB NVMe'],
            'promo' => 'Tặng Màn hình 240Hz khi nâng cấp VGA',
            'sold_percent' => 65,
            'sold_text' => 'Đã bán 13/20 suất',
            'flash_sale' => true,
            'stock' => 7,
        ],
        [
            'id' => 3,
            'slug' => 'man-hinh-acer-nitro-vg240y-180hz',
            'name' => 'Màn hình Acer Nitro VG240Y M3 24" IPS 180Hz 0.5ms Chuyên Game',
            'category' => 'man-hinh',
            'brand' => 'ACER',
            'image' => 'assets/images/products/monitor1.jpg',
            'old_price' => 4590000,
            'price' => 2890000,
            'discount' => 37,
            'fast_delivery' => true,
            'specs' => ['24 inch', 'IPS', '180Hz', '0.5ms', 'FHD'],
            'promo' => 'Giảm thêm 200K khi mua kèm PC/Laptop',
            'sold_percent' => 90,
            'sold_text' => 'Sắp hết suất',
            'flash_sale' => true,
            'stock' => 5,
        ],
        [
            'id' => 4,
            'slug' => 'ban-phim-co-akko-3098b-multi-modes',
            'name' => 'Bàn phím cơ Akko 3098B Multi-modes Prunus Lannesiana Hotswap RGB',
            'category' => 'ban-phim-chuot',
            'brand' => 'Bàn phím cơ',
            'image' => 'assets/images/products/keyboard1.jpg',
            'old_price' => 2190000,
            'price' => 1390000,
            'discount' => 37,
            'fast_delivery' => true,
            'specs' => ['3 Modes', 'Hotswap', 'RGB', 'PBT Keycap'],
            'promo' => 'Tặng kèm kê tay gỗ cao cấp',
            'sold_percent' => 55,
            'sold_text' => 'Đã bán 22/40 suất',
            'flash_sale' => true,
            'stock' => 18,
        ],
        [
            'id' => 5,
            'slug' => 'chuot-logitech-g-pro-x-superlight-2',
            'name' => 'Chuột không dây Logitech G Pro X Superlight 2 Lightspeed Wireless',
            'category' => 'ban-phim-chuot',
            'brand' => 'Chuột không dây',
            'image' => 'assets/images/products/mouse1.jpg',
            'old_price' => 3890000,
            'price' => 2990000,
            'discount' => 23,
            'fast_delivery' => true,
            'specs' => ['60g', 'HERO 2 32K', 'Lightspeed', '95h Pin'],
            'promo' => 'Tặng lót chuột Gaming size XL',
            'sold_percent' => 70,
            'sold_text' => 'Đã bán 14/20 suất',
            'flash_sale' => true,
            'stock' => 6,
        ],
        [
            'id' => 6,
            'slug' => 'pc-gvn-intel-i3-12100f-gtx-1650',
            'name' => 'PC ĐOÀN 6 Intel i3-12100F / VGA GTX 1650 4GB / RAM 16GB / SSD 256GB',
            'category' => 'pc-gvn',
            'brand' => 'PC I3',
            'image' => 'assets/images/products/pc2.jpg',
            'old_price' => 12990000,
            'price' => 9990000,
            'discount' => 23,
            'fast_delivery' => true,
            'specs' => ['i3-12100F', 'GTX 1650', '16GB RAM', '256GB SSD'],
            'promo' => 'Tặng bộ Phím Chuột Gaming trị giá 500K',
            'flash_sale' => false,
            'stock' => 15,
        ],
        [
            'id' => 7,
            'slug' => 'pc-gvn-intel-i7-14700k-rtx-4070-super',
            'name' => 'PC ĐOÀN 6 Intel i7-14700K / VGA RTX 4070 Super 12GB / RAM 32GB DDR5',
            'category' => 'pc-gvn',
            'brand' => 'PC I7',
            'image' => 'assets/images/products/pc3.jpg',
            'old_price' => 45990000,
            'price' => 39990000,
            'discount' => 13,
            'fast_delivery' => true,
            'specs' => ['i7-14700K', 'RTX 4070S', '32GB DDR5', '1TB NVMe'],
            'promo' => 'Tặng Tản nhiệt nước AIO 360mm',
            'flash_sale' => false,
            'stock' => 8,
        ],
        [
            'id' => 8,
            'slug' => 'pc-gvn-ryzen-5-7600-rtx-4060-ti',
            'name' => 'PC ĐOÀN 6 AMD Ryzen 5 7600 / VGA RTX 4060 Ti 8GB / RAM 32GB DDR5',
            'category' => 'pc-gvn',
            'brand' => 'PC R5',
            'image' => 'assets/images/products/pc4.jpg',
            'old_price' => 29990000,
            'price' => 25490000,
            'discount' => 15,
            'fast_delivery' => true,
            'specs' => ['Ryzen 5 7600', 'RTX 4060Ti', '32GB DDR5', '500GB SSD'],
            'promo' => 'Hỗ trợ trả góp 0% lãi suất',
            'flash_sale' => false,
            'stock' => 12,
        ],
        [
            'id' => 9,
            'slug' => 'laptop-gaming-asus-tuf-f15',
            'name' => 'Laptop Gaming ASUS TUF Gaming F15 FX507ZC4-HN095W (i5-12500H / RTX 3050)',
            'category' => 'laptop-gaming',
            'brand' => 'ASUS',
            'image' => 'assets/images/products/laptop2.jpg',
            'old_price' => 25990000,
            'price' => 19490000,
            'discount' => 25,
            'fast_delivery' => true,
            'specs' => ['i5-12500H', 'RTX 3050', '16GB RAM', '512GB SSD', '144Hz'],
            'promo' => 'Tặng Combo Gear ASUS trị giá 1.5 triệu',
            'flash_sale' => false,
            'stock' => 20,
        ],
        [
            'id' => 10,
            'slug' => 'laptop-gaming-msi-katana-15',
            'name' => 'Laptop Gaming MSI Katana 15 B13VEK-252VN (i7-13620H / RTX 4050)',
            'category' => 'laptop-gaming',
            'brand' => 'MSI',
            'image' => 'assets/images/products/laptop3.jpg',
            'old_price' => 31990000,
            'price' => 25990000,
            'discount' => 19,
            'fast_delivery' => true,
            'specs' => ['i7-13620H', 'RTX 4050', '16GB DDR5', '512GB SSD'],
            'promo' => 'Tặng Balo MSI Gaming + Chuột',
            'flash_sale' => false,
            'stock' => 14,
        ],
        [
            'id' => 11,
            'slug' => 'laptop-gaming-lenovo-loq-15',
            'name' => 'Laptop Gaming Lenovo LOQ 15IAX9 83GS001RVN (i5-12450HX / RTX 3050 6GB)',
            'category' => 'laptop-gaming',
            'brand' => 'LENOVO',
            'image' => 'assets/images/products/laptop4.jpg',
            'old_price' => 23990000,
            'price' => 18990000,
            'discount' => 21,
            'fast_delivery' => true,
            'specs' => ['i5-12450HX', 'RTX 3050 6GB', '16GB RAM', '100% sRGB'],
            'promo' => 'Tặng Balo Lenovo Gaming',
            'flash_sale' => false,
            'stock' => 16,
        ],
        [
            'id' => 12,
            'slug' => 'laptop-asus-vivobook-15-oled',
            'name' => 'Laptop ASUS Vivobook 15 OLED A1505VA-L1114W (i5-13500H / 16GB / 512GB)',
            'category' => 'laptop',
            'brand' => 'ASUS',
            'image' => 'assets/images/products/laptop3.jpg',
            'old_price' => 19990000,
            'price' => 16490000,
            'discount' => 18,
            'fast_delivery' => true,
            'specs' => ['i5-13500H', '16GB RAM', '512GB SSD', '15.6" OLED'],
            'promo' => 'Giảm thêm 1 Triệu cho HSSV',
            'flash_sale' => false,
            'stock' => 22,
        ],
        [
            'id' => 13,
            'slug' => 'laptop-lenovo-ideapad-slim-5',
            'name' => 'Laptop Lenovo IdeaPad Slim 5 14IMH9 (Core Ultra 5 125H / 16GB / 512GB)',
            'category' => 'laptop',
            'brand' => 'Laptop AI',
            'image' => 'assets/images/products/laptop4.jpg',
            'old_price' => 22990000,
            'price' => 18990000,
            'discount' => 17,
            'fast_delivery' => true,
            'specs' => ['Core Ultra 5', 'AI Boost', '16GB RAM', '14" OLED'],
            'promo' => 'Tặng chuột không dây Lenovo',
            'flash_sale' => false,
            'stock' => 11,
        ],
        [
            'id' => 14,
            'slug' => 'man-hinh-asus-tuf-vg27aq3a-180hz',
            'name' => 'Màn hình ASUS TUF Gaming VG27AQ3A 27" Fast IPS 2K 180Hz G-Sync',
            'category' => 'man-hinh',
            'brand' => 'ASUS',
            'image' => 'assets/images/products/monitor2.jpg',
            'old_price' => 7990000,
            'price' => 5990000,
            'discount' => 25,
            'fast_delivery' => true,
            'specs' => ['27 inch', '2K QHD', 'Fast IPS', '180Hz', '1ms'],
            'promo' => 'Bảo hành 3 năm chính hãng',
            'flash_sale' => false,
            'stock' => 19,
        ],
        [
            'id' => 15,
            'slug' => 'tai-nghe-hyperx-cloud-iii',
            'name' => 'Tai nghe Gaming HyperX Cloud III Đỏ Đen (DTS Spatial Audio)',
            'category' => 'tai-nghe-loa',
            'brand' => 'Tai nghe 7.1',
            'image' => 'assets/images/products/headset1.jpg',
            'old_price' => 2690000,
            'price' => 1990000,
            'discount' => 26,
            'fast_delivery' => true,
            'specs' => ['53mm Driver', 'DTS:X', 'Mic chống ồn', 'USB-C/3.5mm'],
            'promo' => 'Tặng giá treo tai nghe Gaming',
            'flash_sale' => false,
            'stock' => 30,
        ],
    ];
}

/**
 * Lấy danh sách danh mục: ưu tiên đọc từ MySQL, fallback sang Session Admin hoặc mặc định
 */
function getCategories(): array
{
    $pdo = getPDO();
    if ($pdo) {
        try {
            $stmt = $pdo->query("SELECT id, name, slug, icon FROM categories ORDER BY id ASC");
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

/**
 * Lấy toàn bộ sản phẩm: ưu tiên đọc từ MySQL, fallback sang Session Admin hoặc mặc định
 */
function getAllProducts(): array
{
    $pdo = getPDO();
    if ($pdo) {
        try {
            $stmt = $pdo->query("SELECT * FROM products ORDER BY id DESC");
            $rows = $stmt->fetchAll();
            if (!empty($rows)) {
                return array_map('normalizeProductRow', $rows);
            }
        } catch (PDOException $e) {
            // Fallback bên dưới
        }
    }
    return $_SESSION['admin_products'] ?? getDefaultProducts();
}

/**
 * Lấy tin tức: ưu tiên đọc từ MySQL
 */
function getNews(): array
{
    $pdo = getPDO();
    if ($pdo) {
        try {
            $stmt = $pdo->query("SELECT id, title, summary, image, date FROM news ORDER BY id ASC");
            $rows = $stmt->fetchAll();
            if (!empty($rows)) {
                return $rows;
            }
        } catch (PDOException $e) {
        }
    }
    return [
        [
            'title' => 'Đánh giá RTX 4060 vs RTX 4060 Ti: Card đồ họa nào đáng mua nhất 2026?',
            'summary' => 'So sánh hiệu năng thực tế trên các tựa game AAA ở độ phân giải Full HD và 2K, mức tiêu thụ điện năng và giá thành.',
            'image' => 'assets/images/news/news1.jpg',
            'date' => '15/11/2026',
        ],
        [
            'title' => 'Top 5 Laptop Gaming dưới 20 triệu đáng mua nhất mùa tựu trường',
            'summary' => 'Tổng hợp các mẫu laptop trang bị RTX 3050, màn hình 144Hz chuẩn gaming từ Acer, ASUS, Lenovo.',
            'image' => 'assets/images/news/news2.jpg',
            'date' => '14/11/2026',
        ],
        [
            'title' => 'Hướng dẫn chọn bàn phím cơ cho người mới: Linear, Tactile hay Clicky?',
            'summary' => 'Tìm hiểu sự khác biệt giữa các loại switch phổ biến và cách chọn layout phù hợp nhu cầu.',
            'image' => 'assets/images/news/news3.jpg',
            'date' => '12/11/2026',
        ],
        [
            'title' => 'Đoàn 6 khai trương Showroom mới tại TP.HCM với hàng ngàn deal sốc',
            'summary' => 'Cơ hội săn gear giá 1K, giảm giá lên đến 50% cho các sản phẩm màn hình và linh kiện PC.',
            'image' => 'assets/images/news/news4.jpg',
            'date' => '10/11/2026',
        ],
    ];
}

function getProductBySlug(string $slug): array
{
    $pdo = getPDO();
    if ($pdo && $slug !== '') {
        try {
            $stmt = $pdo->prepare("SELECT * FROM products WHERE slug = :slug LIMIT 1");
            $stmt->execute(['slug' => $slug]);
            $p = $stmt->fetch();
            if ($p) {
                return normalizeProductRow($p);
            }
        } catch (PDOException $e) {
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
            $stmt = $pdo->prepare("SELECT * FROM products WHERE id = :id LIMIT 1");
            $stmt->execute(['id' => $id]);
            $p = $stmt->fetch();
            if ($p) {
                return normalizeProductRow($p);
            }
        } catch (PDOException $e) {
        }
    }

    foreach (getAllProducts() as $p) {
        if ((int)$p['id'] === $id) {
            return $p;
        }
    }
    return null;
}

/**
 * Lấy tổng số lượng sản phẩm trong giỏ hàng (mặc định giỏ rỗng [])
 */
function getCartCount(): int
{
    $cart = $_SESSION['cart'] ?? [];
    return array_sum($cart);
}
