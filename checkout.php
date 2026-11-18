<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/data/store.php';

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [1 => 1, 4 => 1];
}

$cartItems = [];
$total = 0;
foreach ($_SESSION['cart'] as $pid => $qty) {
    $p = getProductById((int)$pid);
    if ($p) {
        $sub = $p['price'] * (int)$qty;
        $total += $sub;
        $cartItems[] = ['product' => $p, 'qty' => (int)$qty, 'subtotal' => $sub];
    }
}

$user = $_SESSION['user'] ?? null;
$error = '';
$placedOrder = null;

$customerName = $user['name'] ?? '';
$phone = $user['phone'] ?? '';
$address = $user['address'] ?? '';
$paymentMethod = 'COD';
$note = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($cartItems)) {
    $customerName = trim($_POST['customer_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $paymentMethod = $_POST['payment_method'] ?? 'COD';
    $note = trim($_POST['note'] ?? '');

    if ($customerName === '' || $phone === '' || $address === '') {
        $error = 'Vui lòng điền đầy đủ Họ tên, Số điện thoại và Địa chỉ nhận hàng.';
    } else {
        $orderCode = 'DH' . date('ymdHis');
        $orderData = [
            'id' => time(),
            'code' => $orderCode,
            'user_id' => $user['id'] ?? null,
            'customer_name' => $customerName,
            'phone' => $phone,
            'address' => $address,
            'payment_method' => $paymentMethod,
            'note' => $note,
            'total_price' => $total,
            'status' => 'Chờ xác nhận',
            'created_at' => date('d/m/Y H:i'),
            'items' => array_map(fn($item) => [
                'product_id' => $item['product']['id'],
                'name' => $item['product']['name'],
                'image' => $item['product']['image'],
                'price' => $item['product']['price'],
                'qty' => $item['qty'],
            ], $cartItems),
        ];

        // Lưu vào MySQL localhost nếu DB đã sẵn sàng
        $pdo = getPDO();
        if ($pdo) {
            try {
                $pdo->beginTransaction();
                $stmt = $pdo->prepare('INSERT INTO orders (user_id, customer_name, phone, address, total_price, status, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())');
                $stmt->execute([$user['id'] ?? null, $customerName, $phone, $address, $total, 'Chờ xác nhận']);
                $dbOrderId = (int)$pdo->lastInsertId();
                $orderData['id'] = $dbOrderId;
                $orderData['code'] = 'DH' . str_pad((string)$dbOrderId, 5, '0', STR_PAD_LEFT);

                $itemStmt = $pdo->prepare('INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)');
                foreach ($cartItems as $item) {
                    $itemStmt->execute([$dbOrderId, $item['product']['id'], $item['qty'], $item['product']['price']]);
                }
                $pdo->commit();
            } catch (PDOException $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
            }
        }

        if (!isset($_SESSION['orders'])) {
            $_SESSION['orders'] = [];
        }
        array_unshift($_SESSION['orders'], $orderData);
        $_SESSION['cart'] = [];
        $placedOrder = $orderData;
    }
}

$pageTitle = 'Thanh toán đơn hàng - ĐOÀN 6';
require_once __DIR__ . '/includes/header.php';
?>

<div class="max-w-[1040px] mx-auto px-3 py-6">
    <nav class="text-xs text-gray-500 mb-4 flex items-center gap-2">
        <a href="index.php" class="hover:text-gvn-red"><i class="fa-solid fa-house"></i> Trang chủ</a>
        <span>/</span>
        <a href="cart.php" class="hover:text-gvn-red">Giỏ hàng</a>
        <span>/</span>
        <span class="text-gray-800 font-semibold">Thanh toán</span>
    </nav>

    <?php if ($placedOrder): ?>
        <div class="bg-white rounded-2xl border border-emerald-200 p-8 text-center shadow-sm max-w-xl mx-auto">
            <i class="fa-solid fa-circle-check text-5xl text-emerald-500 mb-3"></i>
            <h1 class="text-xl font-extrabold text-gray-900 mb-1">Đặt hàng thành công!</h1>
            <p class="text-xs text-gray-500 mb-4">Mã đơn hàng của bạn là <strong class="text-gvn-red text-sm"><?= e($placedOrder['code']) ?></strong></p>

            <div class="bg-gray-50 rounded-xl p-4 text-left text-xs space-y-1.5 mb-5 border border-gray-100">
                <div><span class="text-gray-500">Người nhận:</span> <strong><?= e($placedOrder['customer_name']) ?></strong></div>
                <div><span class="text-gray-500">Số điện thoại:</span> <strong><?= e($placedOrder['phone']) ?></strong></div>
                <div><span class="text-gray-500">Địa chỉ:</span> <?= e($placedOrder['address']) ?></div>
                <div><span class="text-gray-500">Thanh toán:</span> <?= e($placedOrder['payment_method']) ?></div>
                <div><span class="text-gray-500">Tổng tiền:</span> <strong class="text-gvn-red"><?= formatPrice((int)$placedOrder['total_price']) ?></strong></div>
            </div>

            <div class="flex flex-wrap justify-center gap-3">
                <a href="orders.php?code=<?= e($placedOrder['code']) ?>" class="bg-black hover:bg-neutral-800 text-white font-bold px-5 py-2.5 rounded-xl text-xs transition">
                    Xem chi tiết đơn hàng
                </a>
                <a href="index.php" class="bg-gvn-red hover:bg-gvn-redHover text-white font-bold px-5 py-2.5 rounded-xl text-xs transition">
                    Tiếp tục mua sắm
                </a>
            </div>
        </div>
    <?php elseif (empty($cartItems)): ?>
        <div class="bg-white rounded-2xl border border-gray-200 p-8 text-center shadow-sm">
            <p class="text-sm text-gray-500 mb-4">Giỏ hàng đang trống, không thể thanh toán.</p>
            <a href="index.php" class="inline-block bg-gvn-red text-white font-bold px-6 py-2.5 rounded-xl text-sm">Quay về trang chủ</a>
        </div>
    <?php else: ?>
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-5">
            <!-- Form thông tin giao hàng -->
            <div class="lg:col-span-7 bg-white rounded-2xl border border-gray-200 p-5 shadow-sm">
                <h1 class="text-lg font-extrabold text-gray-900 uppercase mb-4 pb-2 border-b border-gray-100">Thông tin giao hàng</h1>

                <?php if ($error !== ''): ?>
                    <div class="bg-red-50 border border-red-200 text-red-700 text-xs rounded-xl p-3 mb-4">
                        <?= e($error) ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="checkout.php" class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">Họ và tên người nhận *</label>
                        <input type="text" name="customer_name" value="<?= e($customerName) ?>" required placeholder="Nguyễn Văn A"
                            class="w-full h-10 px-3.5 rounded-lg border border-gray-300 text-sm focus:outline-none focus:border-gvn-red">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">Số điện thoại *</label>
                        <input type="text" name="phone" value="<?= e($phone) ?>" required placeholder="0000.0000"
                            class="w-full h-10 px-3.5 rounded-lg border border-gray-300 text-sm focus:outline-none focus:border-gvn-red">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">Địa chỉ nhận hàng *</label>
                        <input type="text" name="address" value="<?= e($address) ?>" required placeholder="Số nhà, đường, phường/xã, quận/huyện, tỉnh/thành"
                            class="w-full h-10 px-3.5 rounded-lg border border-gray-300 text-sm focus:outline-none focus:border-gvn-red">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">Phương thức thanh toán</label>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-2 text-xs">
                            <label class="flex items-center gap-2 border border-gray-200 rounded-lg p-2.5 cursor-pointer hover:border-gvn-red">
                                <input type="radio" name="payment_method" value="COD (Thanh toán khi nhận hàng)" checked>
                                <span>Thanh toán COD</span>
                            </label>
                            <label class="flex items-center gap-2 border border-gray-200 rounded-lg p-2.5 cursor-pointer hover:border-gvn-red">
                                <input type="radio" name="payment_method" value="Chuyển khoản ngân hàng">
                                <span>Chuyển khoản</span>
                            </label>
                            <label class="flex items-center gap-2 border border-gray-200 rounded-lg p-2.5 cursor-pointer hover:border-gvn-red">
                                <input type="radio" name="payment_method" value="Trả góp 0%">
                                <span>Trả góp 0%</span>
                            </label>
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">Ghi chú đơn hàng</label>
                        <textarea name="note" rows="2" placeholder="Ví dụ: Giao giờ hành chính..."
                            class="w-full p-3 rounded-lg border border-gray-300 text-sm focus:outline-none focus:border-gvn-red"><?= e($note) ?></textarea>
                    </div>
                    <button type="submit" class="w-full bg-gvn-red hover:bg-gvn-redHover text-white font-extrabold py-3.5 rounded-xl uppercase text-sm transition shadow-md">
                        Xác nhận đặt hàng (<?= formatPrice($total) ?>)
                    </button>
                </form>
            </div>

            <!-- Tóm tắt đơn hàng -->
            <div class="lg:col-span-5 bg-white rounded-2xl border border-gray-200 p-5 shadow-sm h-fit">
                <h2 class="text-base font-extrabold text-gray-900 uppercase mb-4 pb-2 border-b border-gray-100">Đơn hàng của bạn (<?= count($cartItems) ?> sản phẩm)</h2>
                <div class="space-y-3 mb-4 max-h-80 overflow-y-auto pr-1">
                    <?php foreach ($cartItems as $item): ?>
                        <div class="flex items-center gap-3 pb-3 border-b border-gray-100">
                            <img src="<?= e($item['product']['image']) ?>" alt="" class="w-14 h-14 object-cover rounded-lg border border-gray-200 shrink-0">
                            <div class="flex-1 min-w-0">
                                <div class="text-xs font-bold text-gray-900 line-clamp-2"><?= e($item['product']['name']) ?></div>
                                <div class="text-[11px] text-gray-500 mt-0.5">SL: <?= (int)$item['qty'] ?> × <?= formatPrice((int)$item['product']['price']) ?></div>
                            </div>
                            <div class="text-xs font-extrabold text-gvn-red shrink-0"><?= formatPrice((int)$item['subtotal']) ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="space-y-2 text-xs pt-2">
                    <div class="flex justify-between text-gray-600">
                        <span>Tạm tính:</span>
                        <span><?= formatPrice($total) ?></span>
                    </div>
                    <div class="flex justify-between text-gray-600">
                        <span>Phí vận chuyển:</span>
                        <span class="text-emerald-600 font-semibold">Miễn phí</span>
                    </div>
                    <div class="flex justify-between text-base font-extrabold text-gray-900 pt-2 border-t border-gray-100">
                        <span>Tổng cộng:</span>
                        <span class="text-gvn-red"><?= formatPrice($total) ?></span>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
