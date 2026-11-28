<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/data/store.php';

if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();

    $action = trim((string)($_POST['action'] ?? ''));
    $pid = (int)($_POST['product_id'] ?? 0);
    $qtyToAdd = max(1, (int)($_POST['quantity'] ?? 1));
    $buyNow = !empty($_POST['buy_now']);
    $redirectTarget = 'cart.php';

    if ($action === 'add_bulk' && !empty($_POST['product_ids']) && is_array($_POST['product_ids'])) {
        $addedCount = 0;
        $skippedNames = [];
        $slotMap = [];

        foreach ($_POST['product_ids'] as $slotKey => $rawId) {
            $id = (int)$rawId;
            if ($id <= 0) {
                continue;
            }
            $p = getProductById($id);
            $stock = (int)($p['stock'] ?? 0);
            if ($p && $stock > 0) {
                $cur = (int)($_SESSION['cart'][$id] ?? 0);
                $_SESSION['cart'][$id] = min($stock, $cur + 1);
                $addedCount++;
                if (is_string($slotKey) && $slotKey !== '') {
                    $slotMap[$id] = $slotKey;
                }
            } else {
                $skippedNames[] = $p['name'] ?? ('#' . $id);
            }
        }

        if (!empty($_POST['pc_build_specs']) || !empty($slotMap)) {
            $_SESSION['pc_build_meta'] = [
                'slots' => $slotMap,
                'specs' => $_POST['pc_build_specs'] ?? null,
            ];
        }

        if ($addedCount > 0 && empty($skippedNames)) {
            $_SESSION['cart_notice'] = 'Đã thêm toàn bộ cấu hình (' . $addedCount . ' linh kiện) vào giỏ hàng!';
        } elseif ($addedCount > 0) {
            $_SESSION['cart_notice'] = 'Đã thêm ' . $addedCount . ' sản phẩm. Một số linh kiện hết hàng đã được bỏ qua: ' . implode(', ', $skippedNames);
        } else {
            $_SESSION['cart_notice'] = 'Không có sản phẩm còn hàng để thêm vào giỏ.';
        }

        if ($buyNow && $addedCount > 0) {
            $redirectTarget = 'checkout.php';
        }
    } elseif (($action === 'add' || $action === 'inc') && $pid > 0) {
        $p = getProductById($pid);
        $stock = (int)($p['stock'] ?? 0);
        if (!$p || $stock <= 0) {
            $_SESSION['cart_notice'] = 'Sản phẩm này hiện đã hết hàng.';
        } else {
            $cur = (int)($_SESSION['cart'][$pid] ?? 0);
            $delta = ($action === 'add') ? $qtyToAdd : 1;
            if ($cur >= $stock) {
                $_SESSION['cart_notice'] = 'Số lượng tối đa trong kho chỉ còn ' . $stock . ' sản phẩm.';
            } else {
                $newQty = min($stock, $cur + $delta);
                $_SESSION['cart'][$pid] = $newQty;
                if ($cur + $delta > $stock) {
                    $_SESSION['cart_notice'] = 'Đã điều chỉnh số lượng theo tồn kho tối đa (' . $stock . ' sản phẩm).';
                }
            }
            if ($action === 'add' && $buyNow && !empty($_SESSION['cart'][$pid])) {
                $redirectTarget = 'checkout.php';
            }
        }
    } elseif ($action === 'dec' && $pid > 0) {
        if (isset($_SESSION['cart'][$pid])) {
            $_SESSION['cart'][$pid]--;
            if ($_SESSION['cart'][$pid] <= 0) {
                unset($_SESSION['cart'][$pid]);
            }
        }
        if (empty($_SESSION['cart'])) {
            unset($_SESSION['pc_build_meta']);
        }
    } elseif ($action === 'remove' && $pid > 0) {
        unset($_SESSION['cart'][$pid]);
        if (empty($_SESSION['cart'])) {
            unset($_SESSION['pc_build_meta']);
        }
    } elseif ($action === 'clear') {
        $_SESSION['cart'] = [];
        unset($_SESSION['pc_build_meta']);
        $_SESSION['cart_notice'] = 'Đã xóa toàn bộ sản phẩm khỏi giỏ hàng.';
    }

    header('Location: ' . $redirectTarget);
    exit;
}

$notices = [];
if (!empty($_SESSION['cart_notice'])) {
    $notices[] = (string)$_SESSION['cart_notice'];
    unset($_SESSION['cart_notice']);
}

$cartItems = [];
foreach ($_SESSION['cart'] as $pid => $qty) {
    $pid = (int)$pid;
    $qty = (int)$qty;
    $p = getProductById($pid);
    $stock = (int)($p['stock'] ?? 0);

    if (!$p || $stock <= 0 || $qty <= 0) {
        unset($_SESSION['cart'][$pid]);
        if ($p) {
            $notices[] = 'Sản phẩm "' . $p['name'] . '" đã hết hàng và được gỡ khỏi giỏ.';
        }
        continue;
    }

    if ($qty > $stock) {
        $qty = $stock;
        $_SESSION['cart'][$pid] = $stock;
        $notices[] = 'Sản phẩm "' . $p['name'] . '" đã được điều chỉnh về tối đa ' . $stock . ' trong kho.';
    }

    $cartItems[] = ['product' => $p, 'qty' => $qty];
}

if (empty($cartItems)) {
    unset($_SESSION['pc_build_meta']);
}

$pageTitle = 'Giỏ hàng - ĐOÀN 6';
require_once __DIR__ . '/includes/header.php';
?>

<div class="max-w-[960px] mx-auto px-3 py-6">
    <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
        <h1 class="text-xl font-extrabold text-gray-900 uppercase">Giỏ hàng của bạn</h1>
        <?php if (!empty($cartItems)): ?>
            <form method="POST" action="cart.php" onsubmit="return confirm('Xóa toàn bộ sản phẩm trong giỏ hàng?')">
                <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                <input type="hidden" name="action" value="clear">
                <button type="submit" class="text-xs font-bold text-gray-500 hover:text-gvn-red transition">
                    <i class="fa-solid fa-trash-can mr-1"></i> Xóa tất cả
                </button>
            </form>
        <?php endif; ?>
    </div>

    <?php foreach ($notices as $notice): ?>
        <div class="bg-amber-50 border border-amber-200 text-amber-800 text-xs font-semibold rounded-xl p-3 mb-4">
            <i class="fa-solid fa-circle-info mr-1"></i> <?= e($notice) ?>
        </div>
    <?php endforeach; ?>

    <?php if (empty($cartItems)): ?>
        <div class="bg-white rounded-2xl border border-gray-200 p-8 text-center shadow-sm">
            <i class="fa-solid fa-cart-shopping text-4xl text-gray-300 mb-3"></i>
            <p class="text-sm text-gray-500 mb-4">Giỏ hàng của bạn đang trống.</p>
            <a href="index.php" class="inline-block bg-gvn-red text-white font-bold px-6 py-2.5 rounded-xl text-sm hover:bg-gvn-redHover transition">Khám phá sản phẩm</a>
        </div>
    <?php else: ?>
        <div class="bg-white rounded-2xl border border-gray-200 p-4 sm:p-6 shadow-sm space-y-4">
            <?php
            $total = 0;
            foreach ($cartItems as $item):
                $p = $item['product'];
                $stock = (int)($p['stock'] ?? 0);
                $hasOldPrice = !empty($p['old_price']) && (int)$p['old_price'] > (int)$p['price'];
                $total += (int)$p['price'] * (int)$item['qty'];
            ?>
                <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 pb-4 border-b border-gray-100">
                    <div class="flex items-center gap-3">
                        <img src="<?= e($p['image']) ?>" alt="<?= e($p['name']) ?>" class="w-20 h-20 object-cover rounded-lg border border-gray-200 shrink-0">
                        <div>
                            <a href="product.php?slug=<?= e($p['slug']) ?>" class="text-sm font-bold text-gray-900 hover:text-gvn-red line-clamp-2"><?= e($p['name']) ?></a>
                            <div class="flex items-center gap-2 mt-1 text-[11px] text-gray-500">
                                <span class="bg-gray-100 px-1.5 py-0.5 rounded font-semibold"><?= e($p['brand'] ?: 'ĐOÀN 6') ?></span>
                                <span class="text-emerald-700 font-semibold">Còn <?= $stock ?> sp</span>
                            </div>
                            <?php if ($hasOldPrice): ?>
                                <div class="text-xs text-gray-400 line-through mt-1"><?= formatPrice((int)$p['old_price']) ?></div>
                            <?php endif; ?>
                            <div class="text-sm font-extrabold text-gvn-red mt-0.5"><?= formatPrice((int)$p['price']) ?></div>
                        </div>
                    </div>
                    <div class="flex items-center gap-4 self-end sm:self-center">
                        <div class="flex items-center border border-gray-300 rounded-lg overflow-hidden text-xs font-bold">
                            <form method="POST" action="cart.php" class="inline">
                                <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                                <input type="hidden" name="action" value="dec">
                                <input type="hidden" name="product_id" value="<?= (int)$p['id'] ?>">
                                <button type="submit" aria-label="Giảm số lượng" class="px-2.5 py-1.5 bg-gray-50 hover:bg-gray-200">-</button>
                            </form>
                            <span class="px-3 py-1.5"><?= (int)$item['qty'] ?></span>
                            <form method="POST" action="cart.php" class="inline">
                                <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                                <input type="hidden" name="action" value="inc">
                                <input type="hidden" name="product_id" value="<?= (int)$p['id'] ?>">
                                <button type="submit" aria-label="Tăng số lượng" class="px-2.5 py-1.5 bg-gray-50 hover:bg-gray-200">+</button>
                            </form>
                        </div>
                        <form method="POST" action="cart.php" class="inline">
                            <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                            <input type="hidden" name="action" value="remove">
                            <input type="hidden" name="product_id" value="<?= (int)$p['id'] ?>">
                            <button type="submit" class="text-gray-400 hover:text-gvn-red text-sm" title="Xóa"><i class="fa-solid fa-trash"></i></button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>

            <div class="flex flex-col sm:flex-row items-center justify-between gap-4 pt-2">
                <div>
                    <span class="text-sm text-gray-600">Tổng tiền tạm tính:</span>
                    <span class="text-2xl font-extrabold text-gvn-red ml-2"><?= formatPrice($total) ?></span>
                </div>
                <a href="checkout.php" class="w-full sm:w-auto text-center bg-gvn-red hover:bg-gvn-redHover text-white font-extrabold px-8 py-3.5 rounded-xl uppercase tracking-wide transition shadow-md">
                    TIẾN HÀNH ĐẶT HÀNG
                </a>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
