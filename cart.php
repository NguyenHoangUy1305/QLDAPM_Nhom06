<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/data/store.php';

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [1 => 1, 4 => 1];
}

$orderSuccess = false;
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $action = $_POST['action'] ?? '';
    $pid = (int)($_POST['product_id'] ?? 0);

    if ($action === 'add' && $pid > 0) {
        $_SESSION['cart'][$pid] = ($_SESSION['cart'][$pid] ?? 0) + 1;
        header('Location: cart.php');
        exit;
    } elseif ($action === 'inc' && $pid > 0) {
        $_SESSION['cart'][$pid] = ($_SESSION['cart'][$pid] ?? 0) + 1;
        header('Location: cart.php');
        exit;
    } elseif ($action === 'dec' && $pid > 0) {
        if (isset($_SESSION['cart'][$pid])) {
            $_SESSION['cart'][$pid]--;
            if ($_SESSION['cart'][$pid] <= 0) {
                unset($_SESSION['cart'][$pid]);
            }
        }
        header('Location: cart.php');
        exit;
    } elseif ($action === 'remove' && $pid > 0) {
        unset($_SESSION['cart'][$pid]);
        header('Location: cart.php');
        exit;
    } elseif ($action === 'checkout') {
        $_SESSION['cart'] = [];
        $orderSuccess = true;
    }
}

$cartItems = [];
foreach ($_SESSION['cart'] as $pid => $qty) {
    $p = getProductById((int)$pid);
    if ($p) {
        $cartItems[] = ['product' => $p, 'qty' => (int)$qty];
    }
}

$pageTitle = 'Giỏ hàng - ĐOÀN 6';
require_once __DIR__ . '/includes/header.php';
?>

<div class="max-w-[960px] mx-auto px-3 py-6">
    <h1 class="text-xl font-extrabold text-gray-900 uppercase mb-4">Giỏ hàng của bạn</h1>

    <?php if ($orderSuccess): ?>
        <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-2xl p-6 text-center shadow-sm">
            <i class="fa-solid fa-circle-check text-4xl text-emerald-500 mb-3"></i>
            <h2 class="text-lg font-extrabold mb-1">Đặt hàng thành công!</h2>
            <p class="text-sm mb-4">Cảm ơn bạn đã mua sắm tại ĐOÀN 6. Nhân viên sẽ liên hệ qua số 0000.0000 để xác nhận đơn hàng.</p>
            <a href="index.php" class="inline-block bg-gvn-red text-white font-bold px-6 py-2.5 rounded-xl text-sm hover:bg-gvn-redHover transition">Tiếp tục mua sắm</a>
        </div>
    <?php elseif (empty($cartItems)): ?>
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
                $total += $p['price'] * $item['qty'];
            ?>
                <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 pb-4 border-b border-gray-100">
                    <div class="flex items-center gap-3">
                        <img src="<?= e($p['image']) ?>" alt="<?= e($p['name']) ?>" class="w-20 h-20 object-cover rounded-lg border border-gray-200">
                        <div>
                            <a href="product.php?slug=<?= e($p['slug']) ?>" class="text-sm font-bold text-gray-900 hover:text-gvn-red line-clamp-2"><?= e($p['name']) ?></a>
                            <div class="text-xs text-gray-400 line-through mt-1"><?= formatPrice((int)$p['old_price']) ?></div>
                            <div class="text-sm font-extrabold text-gvn-red"><?= formatPrice((int)$p['price']) ?></div>
                        </div>
                    </div>
                    <div class="flex items-center gap-4 self-end sm:self-center">
                        <div class="flex items-center border border-gray-300 rounded-lg overflow-hidden text-xs font-bold">
                            <form method="POST" action="cart.php" class="inline">
                                <input type="hidden" name="action" value="dec">
                                <input type="hidden" name="product_id" value="<?= (int)$p['id'] ?>">
                                <button type="submit" class="px-2.5 py-1.5 bg-gray-50 hover:bg-gray-200">-</button>
                            </form>
                            <span class="px-3 py-1.5"><?= (int)$item['qty'] ?></span>
                            <form method="POST" action="cart.php" class="inline">
                                <input type="hidden" name="action" value="inc">
                                <input type="hidden" name="product_id" value="<?= (int)$p['id'] ?>">
                                <button type="submit" class="px-2.5 py-1.5 bg-gray-50 hover:bg-gray-200">+</button>
                            </form>
                        </div>
                        <form method="POST" action="cart.php" class="inline">
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
                <form method="POST" action="cart.php">
                    <input type="hidden" name="action" value="checkout">
                    <button type="submit" class="w-full sm:w-auto bg-gvn-red hover:bg-gvn-redHover text-white font-extrabold px-8 py-3.5 rounded-xl uppercase tracking-wide transition shadow-md">
                        ĐẶT HÀNG NGAY
                    </button>
                </form>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
