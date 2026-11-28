<?php
require_once __DIR__ . '/_auth.php';
$adminUser = requireStaffOrAdmin();
require_once __DIR__ . '/_layout.php';

$pdo = getPDO();
$allProducts = getAllProducts();
$inStockProducts = array_values(array_filter($allProducts, static fn(array $p): bool => (int)($p['stock'] ?? 0) > 0));

$message = '';
$error = '';
$createdOrder = null;

$formCustomerName = '';
$formCustomerPhone = '';
$formCustomerEmail = '';
$formCustomerAddress = '';
$formNote = '';
$formPaymentMethod = 'COD';
$formPaymentStatus = 'paid';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();

    $formCustomerName = trim((string)($_POST['customer_name'] ?? ''));
    $formCustomerPhone = trim((string)($_POST['customer_phone'] ?? $_POST['phone'] ?? ''));
    $formCustomerEmail = trim((string)($_POST['customer_email'] ?? $_POST['email'] ?? ''));
    $formCustomerAddress = trim((string)($_POST['customer_address'] ?? $_POST['address'] ?? ''));
    $formNote = trim((string)($_POST['note'] ?? ''));
    $formPaymentMethod = (string)($_POST['payment_method'] ?? 'COD');
    $formPaymentStatus = (string)($_POST['payment_status'] ?? 'paid');
    if (!in_array($formPaymentStatus, ['paid', 'unpaid'], true)) {
        $formPaymentStatus = 'unpaid';
    }

    $productIds = $_POST['product_ids'] ?? [];
    $quantities = $_POST['quantities'] ?? [];
    $items = [];
    if (!empty($_POST['product_id'])) {
        $pid = (int)$_POST['product_id'];
        $qty = (int)($_POST['quantity'] ?? $_POST['qty'] ?? 1);
        if ($pid > 0 && $qty > 0) {
            $items[] = [
                'product_id' => $pid,
                'quantity' => $qty,
            ];
        }
    }
    if (is_array($productIds) && is_array($quantities)) {
        foreach ($productIds as $idx => $pidRaw) {
            $pid = (int)$pidRaw;
            $qty = (int)($quantities[$idx] ?? 0);
            if ($pid > 0 && $qty > 0) {
                $items[] = [
                    'product_id' => $pid,
                    'quantity' => $qty,
                ];
            }
        }
    }

    if (!$pdo) {
        $error = 'Không thể kết nối cơ sở dữ liệu.';
    } else {
        try {
            $createdOrder = createOrderWithItems($pdo, [
                'order_type' => 'walk_in',
                'user_id' => null,
                'created_by_staff_id' => (int)$adminUser['id'],
                'customer_name' => $formCustomerName,
                'customer_phone' => $formCustomerPhone,
                'customer_email' => $formCustomerEmail,
                'customer_address' => $formCustomerAddress,
                'note' => $formNote,
                'payment_method' => $formPaymentMethod,
                'payment_status' => $formPaymentStatus,
            ], $items);

            $message = 'Đã tạo đơn hàng tại quầy ' . $createdOrder['order_code'] . ' thành công và tự động kích hoạt bảo hành điện tử!';
            $formCustomerName = '';
            $formCustomerPhone = '';
            $formCustomerEmail = '';
            $formCustomerAddress = '';
            $formNote = '';
            $allProducts = getAllProducts();
            $inStockProducts = array_values(array_filter($allProducts, static fn(array $p): bool => (int)($p['stock'] ?? 0) > 0));
        } catch (Throwable $e) {
            $error = $e->getMessage();
        }
    }
}

renderAdminHeader('Tạo đơn hàng tại quầy (Walk-In)', 'walkin', $adminUser);
?>

<div class="bg-sky-50 border border-sky-200 text-sky-900 text-xs rounded-xl p-3.5 mb-5">
    <i class="fa-solid fa-cash-register mr-1 text-sky-600"></i>
    <strong>Đơn hàng trực tiếp tại Showroom:</strong> Dành cho khách đến mua hàng trực tiếp tại quầy. Hệ thống sẽ ghi nhận nhân viên lập đơn (<strong><?= e($adminUser['name']) ?></strong>), tự động trừ tồn kho và sinh mã bảo hành điện tử mà <strong>không</strong> tạo tài khoản khách hàng mới.
</div>

<?php if ($message !== ''): ?>
    <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-semibold rounded-xl p-3.5 mb-4">
        <i class="fa-solid fa-circle-check mr-1"></i> <?= e($message) ?>
    </div>
<?php endif; ?>

<?php if ($error !== ''): ?>
    <div class="bg-red-50 border border-red-200 text-red-700 text-xs font-semibold rounded-xl p-3.5 mb-4">
        <i class="fa-solid fa-triangle-exclamation mr-1"></i> <?= e($error) ?>
    </div>
<?php endif; ?>

<?php if ($createdOrder): ?>
    <div class="bg-white rounded-xl border-2 border-emerald-300 p-5 shadow-sm mb-6 space-y-4">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-100 pb-3">
            <div>
                <div class="text-xs text-gray-500 font-semibold">Đơn hàng tại quầy vừa tạo</div>
                <div class="text-lg font-extrabold text-gvn-red"><?= e($createdOrder['order_code']) ?></div>
            </div>
            <div class="text-right">
                <div class="text-xs text-gray-500">Tổng thanh toán</div>
                <div class="text-lg font-extrabold text-gray-900"><?= formatPrice((int)$createdOrder['total_amount']) ?></div>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs">
            <div class="space-y-1.5 bg-gray-50 rounded-lg p-3">
                <div><strong>Khách hàng:</strong> <?= e($createdOrder['customer_name']) ?> — <?= e($createdOrder['customer_phone']) ?></div>
                <div><strong>Địa chỉ:</strong> <?= e($createdOrder['customer_address']) ?></div>
                <div><strong>Thanh toán:</strong> <?= e($createdOrder['payment_method']) ?> (<?= e($createdOrder['payment_status']) ?>)</div>
                <div><strong>Trạng thái:</strong> <?= e($createdOrder['status']) ?></div>
            </div>

            <?php if (($createdOrder['payment_method'] ?? '') === 'VietQR' && ($createdOrder['payment_status'] ?? '') !== 'paid'): ?>
                <?php
                $qrAmount = (int)$createdOrder['total_amount'];
                $qrInfo = rawurlencode($createdOrder['order_code']);
                $qrUrl = "https://img.vietqr.io/image/MB-0987654321-compact2.png?amount={$qrAmount}&addInfo={$qrInfo}&accountName=DOAN%206%20TECH";
                ?>
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-3 flex items-center gap-4">
                    <img src="<?= e($qrUrl) ?>" alt="VietQR" class="w-28 h-28 object-contain bg-white rounded border border-blue-200 p-1">
                    <div class="space-y-1">
                        <div class="font-extrabold text-blue-900">Mã VietQR thanh toán tại quầy</div>
                        <div>Nội dung CK: <strong><?= e($createdOrder['order_code']) ?></strong></div>
                        <div>Số tiền: <strong class="text-gvn-red"><?= formatPrice($qrAmount) ?></strong></div>
                        <div>Hết hạn sau: <strong id="walkin-qr-countdown" class="text-blue-700">15:00</strong></div>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <?php if (!empty($createdOrder['warranties'])): ?>
            <div>
                <div class="text-xs font-extrabold uppercase mb-2">Mã bảo hành điện tử tự động (<?= count($createdOrder['warranties']) ?>)</div>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2 text-xs">
                    <?php foreach ($createdOrder['warranties'] as $w): ?>
                        <div class="border border-gray-200 rounded-lg p-2.5 bg-gray-50">
                            <div class="font-bold text-gvn-red"><?= e($w['warranty_code']) ?></div>
                            <div class="text-[11px] text-gray-600">SN: <?= e($w['serial_number']) ?></div>
                            <div class="text-[11px] font-semibold line-clamp-1"><?= e($w['product_name']) ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <div class="flex gap-3 text-xs">
            <a href="orders.php?q=<?= e($createdOrder['order_code']) ?>" class="bg-black text-white font-bold px-4 py-2 rounded-lg">
                Quản lý đơn hàng này →
            </a>
            <a href="warranties.php?q=<?= e($createdOrder['order_code']) ?>" class="bg-gray-100 text-gray-800 font-bold px-4 py-2 rounded-lg">
                Xem bảo hành
            </a>
        </div>
    </div>
<?php endif; ?>

<form method="POST" action="walkin-order.php" class="grid grid-cols-1 lg:grid-cols-12 gap-5" id="walkin-form">
    <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">

    <!-- Chọn sản phẩm -->
    <div class="lg:col-span-7 bg-white rounded-xl border border-gray-200 p-4 shadow-sm space-y-3">
        <div class="flex items-center justify-between border-b border-gray-100 pb-2">
            <h2 class="text-sm font-extrabold uppercase">Chọn sản phẩm mua tại quầy</h2>
            <button type="button" onclick="addWalkinRow()" class="bg-black hover:bg-neutral-800 text-white text-xs font-bold px-3 py-1.5 rounded-lg">
                + Thêm dòng sản phẩm
            </button>
        </div>

        <div id="walkin-rows" class="space-y-2.5 text-xs">
            <div class="walkin-row grid grid-cols-12 gap-2 items-center bg-gray-50 p-2.5 rounded-lg border border-gray-200">
                <div class="col-span-7">
                    <select name="product_id" required onchange="recalcWalkin()" class="w-full h-9 px-2.5 rounded-lg border border-gray-300 bg-white">
                        <option value="">-- Chọn sản phẩm còn hàng --</option>
                        <?php foreach ($inStockProducts as $p): ?>
                            <option value="<?= (int)$p['id'] ?>" data-price="<?= (int)$p['price'] ?>" data-stock="<?= (int)$p['stock'] ?>">
                                <?= e($p['name']) ?> — <?= formatPrice((int)$p['price']) ?> (Tồn: <?= (int)$p['stock'] ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-span-2">
                    <input type="number" name="quantity" min="1" value="1" required oninput="recalcWalkin()"
                        class="w-full h-9 px-2 rounded-lg border border-gray-300 text-center font-bold">
                </div>
                <div class="col-span-2 text-right font-bold text-gvn-red row-subtotal">0đ</div>
                <div class="col-span-1 text-right">
                    <button type="button" onclick="removeWalkinRow(this)" class="text-red-500 hover:text-red-700 font-bold" title="Xóa dòng">
                        <i class="fa-solid fa-trash"></i>
                    </button>
                </div>
            </div>
        </div>

        <div class="pt-3 border-t border-gray-100 flex items-center justify-between text-sm font-extrabold">
            <span>Tổng tạm tính:</span>
            <span id="walkin-total" class="text-lg text-gvn-red">0đ</span>
        </div>
    </div>

    <!-- Thông tin khách tại quầy -->
    <div class="lg:col-span-5 bg-white rounded-xl border border-gray-200 p-4 shadow-sm space-y-3 text-xs h-fit">
        <h2 class="text-sm font-extrabold uppercase border-b border-gray-100 pb-2">Thông tin khách & Thanh toán</h2>

        <div>
            <label class="block font-bold mb-1">Họ tên khách hàng *</label>
            <input type="text" name="customer_name" required value="<?= e($formCustomerName) ?>" placeholder="Khách mua tại quầy"
                class="w-full h-9 px-3 rounded-lg border border-gray-300">
        </div>
        <div class="grid grid-cols-2 gap-2">
            <div>
                <label class="block font-bold mb-1">Số điện thoại *</label>
                <input type="text" name="customer_phone" required value="<?= e($formCustomerPhone) ?>" placeholder="0909123456"
                    class="w-full h-9 px-3 rounded-lg border border-gray-300">
            </div>
            <div>
                <label class="block font-bold mb-1">Email (tùy chọn)</label>
                <input type="email" name="customer_email" value="<?= e($formCustomerEmail) ?>" placeholder="khach@example.com"
                    class="w-full h-9 px-3 rounded-lg border border-gray-300">
            </div>
        </div>
        <div>
            <label class="block font-bold mb-1">Địa chỉ (để trống nếu nhận tại quầy)</label>
            <input type="text" name="customer_address" value="<?= e($formCustomerAddress) ?>" placeholder="Mua trực tiếp tại Showroom ĐOÀN 6"
                class="w-full h-9 px-3 rounded-lg border border-gray-300">
        </div>
        <div>
            <label class="block font-bold mb-1">Ghi chú đơn hàng</label>
            <textarea name="note" rows="2" placeholder="Yêu cầu kiểm tra, cài đặt..." class="w-full p-2.5 rounded-lg border border-gray-300"><?= e($formNote) ?></textarea>
        </div>

        <div class="grid grid-cols-2 gap-2">
            <div>
                <label class="block font-bold mb-1">Phương thức thanh toán *</label>
                <select name="payment_method" class="w-full h-9 px-2.5 rounded-lg border border-gray-300 font-semibold">
                    <option value="COD" <?= $formPaymentMethod === 'COD' ? 'selected' : '' ?>>Tiền mặt / Tại quầy (COD)</option>
                    <option value="VietQR" <?= $formPaymentMethod === 'VietQR' ? 'selected' : '' ?>>Chuyển khoản VietQR</option>
                </select>
            </div>
            <div>
                <label class="block font-bold mb-1">Trạng thái thu tiền</label>
                <select name="payment_status" class="w-full h-9 px-2.5 rounded-lg border border-gray-300 font-semibold">
                    <option value="paid" <?= $formPaymentStatus === 'paid' ? 'selected' : '' ?>>Đã thu tiền tại quầy</option>
                    <option value="unpaid" <?= $formPaymentStatus === 'unpaid' ? 'selected' : '' ?>>Chờ thanh toán / quét QR</option>
                </select>
            </div>
        </div>

        <button type="submit" class="w-full bg-gvn-red hover:bg-gvn-redHover text-white font-extrabold py-3 rounded-xl uppercase text-xs transition shadow">
            <i class="fa-solid fa-check-double mr-1"></i> Xác nhận tạo đơn tại quầy
        </button>
    </div>
</form>

<script>
const WALKIN_OPTIONS_HTML = `
<option value="">-- Chọn sản phẩm còn hàng --</option>
<?php foreach ($inStockProducts as $p): ?>
<option value="<?= (int)$p['id'] ?>" data-price="<?= (int)$p['price'] ?>" data-stock="<?= (int)$p['stock'] ?>"><?= e($p['name']) ?> — <?= formatPrice((int)$p['price']) ?> (Tồn: <?= (int)$p['stock'] ?>)</option>
<?php endforeach; ?>
`;

function fmtVnd(n) {
    return new Intl.NumberFormat('vi-VN').format(n) + 'đ';
}

function addWalkinRow() {
    const container = document.getElementById('walkin-rows');
    const div = document.createElement('div');
    div.className = 'walkin-row grid grid-cols-12 gap-2 items-center bg-gray-50 p-2.5 rounded-lg border border-gray-200';
    div.innerHTML = `
        <div class="col-span-7">
            <select name="product_ids[]" required onchange="recalcWalkin()" class="w-full h-9 px-2.5 rounded-lg border border-gray-300 bg-white">${WALKIN_OPTIONS_HTML}</select>
        </div>
        <div class="col-span-2">
            <input type="number" name="quantities[]" min="1" value="1" required oninput="recalcWalkin()" class="w-full h-9 px-2 rounded-lg border border-gray-300 text-center font-bold">
        </div>
        <div class="col-span-2 text-right font-bold text-gvn-red row-subtotal">0đ</div>
        <div class="col-span-1 text-right">
            <button type="button" onclick="removeWalkinRow(this)" class="text-red-500 hover:text-red-700 font-bold" title="Xóa dòng"><i class="fa-solid fa-trash"></i></button>
        </div>
    `;
    container.appendChild(div);
    recalcWalkin();
}

function removeWalkinRow(btn) {
    const rows = document.querySelectorAll('.walkin-row');
    if (rows.length <= 1) {
        return;
    }
    btn.closest('.walkin-row').remove();
    recalcWalkin();
}

function recalcWalkin() {
    let total = 0;
    document.querySelectorAll('.walkin-row').forEach(row => {
        const sel = row.querySelector('select');
        const qtyInput = row.querySelector('input[type="number"]');
        const subEl = row.querySelector('.row-subtotal');
        const opt = sel.options[sel.selectedIndex];
        const price = opt && opt.dataset.price ? parseInt(opt.dataset.price, 10) : 0;
        const maxStock = opt && opt.dataset.stock ? parseInt(opt.dataset.stock, 10) : 999;
        if (maxStock > 0) {
            qtyInput.max = maxStock;
        }
        const qty = Math.max(0, parseInt(qtyInput.value || '0', 10));
        const sub = price * qty;
        total += sub;
        subEl.textContent = fmtVnd(sub);
    });
    document.getElementById('walkin-total').textContent = fmtVnd(total);
}

const qrCd = document.getElementById('walkin-qr-countdown');
if (qrCd) {
    let sec = 15 * 60;
    setInterval(() => {
        if (sec <= 0) {
            qrCd.textContent = '00:00 (Hết hạn)';
            return;
        }
        sec--;
        const m = String(Math.floor(sec / 60)).padStart(2, '0');
        const s = String(sec % 60).padStart(2, '0');
        qrCd.textContent = m + ':' + s;
    }, 1000);
}
</script>

<?php renderAdminFooter(); ?>
