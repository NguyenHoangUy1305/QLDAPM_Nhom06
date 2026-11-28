<?php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../data/store.php';

$q = trim((string)($_GET['q'] ?? ''));
if (mb_strlen($q, 'UTF-8') < 1) {
    echo json_encode(['ok' => true, 'items' => []], JSON_UNESCAPED_UNICODE);
    exit;
}

$items = [];
$pdo = getPDO();

if ($pdo) {
    try {
        $escaped = str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $q);
        $like = '%' . $escaped . '%';

        $sql = "
            SELECT p.id,
                   p.name,
                   p.slug,
                   p.price,
                   p.old_price,
                   p.stock,
                   COALESCE(b.name, p.brand, 'ĐOÀN 6') AS brand,
                   COALESCE(
                       (SELECT pi.image_url
                        FROM product_images pi
                        WHERE pi.product_id = p.id
                        ORDER BY pi.is_primary DESC, pi.sort_order ASC, pi.id ASC
                        LIMIT 1),
                       p.image,
                       'assets/images/products/laptop1.jpg'
                   ) AS image
            FROM products p
            LEFT JOIN brands b ON b.id = p.brand_id
            LEFT JOIN categories c ON c.id = p.category_id
            WHERE (
                p.name LIKE ? ESCAPE '!'
                OR p.specs LIKE ? ESCAPE '!'
                OR p.description LIKE ? ESCAPE '!'
                OR COALESCE(b.name, p.brand, '') LIKE ? ESCAPE '!'
                OR COALESCE(c.name, '') LIKE ? ESCAPE '!'
            )
            ORDER BY
                CASE WHEN p.name LIKE ? ESCAPE '!' THEN 0 ELSE 1 END,
                p.id DESC
            LIMIT 8
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([$like, $like, $like, $like, $like, $like]);
        $rows = $stmt->fetchAll();

        foreach ($rows as $row) {
            $items[] = [
                'id' => (int)$row['id'],
                'name' => (string)$row['name'],
                'slug' => (string)$row['slug'],
                'brand' => (string)($row['brand'] ?: 'ĐOÀN 6'),
                'price' => (int)round((float)$row['price']),
                'old_price' => (int)round((float)($row['old_price'] ?? 0)),
                'stock' => (int)($row['stock'] ?? 0),
                'image' => (string)($row['image'] ?: 'assets/images/products/laptop1.jpg'),
            ];
        }
    } catch (PDOException $e) {
        $items = [];
    }
}

if (!$pdo) {
    $all = getAllProducts();
    $matched = [];
    foreach ($all as $p) {
        $haystack = ($p['name'] ?? '') . ' '
            . ($p['brand'] ?? '') . ' '
            . ($p['category_name'] ?? '') . ' '
            . ($p['description'] ?? '') . ' '
            . implode(' ', (array)($p['specs'] ?? []));
        if (mb_stripos($haystack, $q, 0, 'UTF-8') !== false) {
            $matched[] = $p;
        }
    }

    usort($matched, static function (array $a, array $b): int {
        $inStockA = ((int)($a['stock'] ?? 0) > 0) ? 1 : 0;
        $inStockB = ((int)($b['stock'] ?? 0) > 0) ? 1 : 0;
        if ($inStockA !== $inStockB) {
            return $inStockB <=> $inStockA;
        }
        return ((int)$b['id']) <=> ((int)$a['id']);
    });

    foreach (array_slice($matched, 0, 8) as $row) {
        $items[] = [
            'id' => (int)$row['id'],
            'name' => (string)$row['name'],
            'slug' => (string)$row['slug'],
            'brand' => (string)($row['brand'] ?: 'ĐOÀN 6'),
            'price' => (int)round((float)$row['price']),
            'old_price' => (int)round((float)($row['old_price'] ?? 0)),
            'stock' => (int)($row['stock'] ?? 0),
            'image' => (string)($row['image'] ?: 'assets/images/products/laptop1.jpg'),
        ];
    }
}

echo json_encode(['ok' => true, 'items' => $items], JSON_UNESCAPED_UNICODE);
