<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/data/store.php';

$pageKey = trim((string)($_GET['page'] ?? 'showroom'));
$pages = [
    'gioi-thieu' => [
        'title' => 'Giới thiệu về ĐOÀN 6',
        'icon' => 'fa-building',
        'content' => [
            'ĐOÀN 6 là hệ thống bán lẻ PC Gaming, Laptop chính hãng, màn hình và linh kiện máy tính hiệu năng cao.',
            'Cam kết 100% linh kiện chính hãng, xuất hóa đơn đầy đủ và kích hoạt bảo hành điện tử tự động.',
            'Đội ngũ kỹ thuật viên giàu kinh nghiệm hỗ trợ tư vấn Build PC tối ưu ngân sách và lắp ráp chuẩn thẩm mỹ.',
        ],
        'cta' => ['label' => 'Khám phá PC Builder thông minh', 'url' => 'pc-builder.php'],
    ],
    'showroom' => [
        'title' => 'Hệ thống Showroom ĐOÀN 6',
        'icon' => 'fa-location-dot',
        'content' => [
            'Showroom 1: Khu vực Demo, Quận Tân Bình, TP. Hồ Chí Minh (8:00 - 21:00)',
            'Showroom 2: Khu vực Demo, Quận 10, TP. Hồ Chí Minh (8:00 - 21:00)',
            'Showroom 3: Khu vực Demo, Quận Đống Đa, Hà Nội (8:00 - 21:00)',
            'Hotline hỗ trợ showroom: 0000.0000',
        ],
    ],
    'bao-hanh' => [
        'title' => 'Tra cứu & Chính sách Bảo hành',
        'icon' => 'fa-shield-halved',
        'content' => [
            'Bảo hành chính hãng từ 12 - 36 tháng theo tiêu chuẩn nhà sản xuất.',
            'Hỗ trợ 1 đổi 1 trong 7 ngày đầu nếu phát sinh lỗi phần cứng từ nhà sản xuất.',
            'Mọi đơn hàng đặt mua đều tự động sinh mã bảo hành điện tử và số Serial tra cứu trực tuyến.',
            'Tổng đài tiếp nhận bảo hành: 0000.0000 (8:00 - 21:00 hàng ngày).',
        ],
        'cta' => ['label' => 'Đi tới trang Tra cứu Bảo hành điện tử', 'url' => 'warranty.php'],
    ],
    'dich-vu' => [
        'title' => 'Dịch vụ kỹ thuật tận nhà',
        'icon' => 'fa-screwdriver-wrench',
        'content' => [
            'Vệ sinh PC / Laptop, tra keo tản nhiệt cao cấp tận nơi.',
            'Cài đặt phần mềm, nâng cấp RAM, SSD, VGA theo yêu cầu.',
            'Đặt lịch kỹ thuật viên qua hotline: 0000.0000.',
        ],
    ],
    'thu-cu' => [
        'title' => 'Chương trình Thu cũ đổi mới',
        'icon' => 'fa-rotate',
        'content' => [
            'Hỗ trợ thu lại Laptop, VGA, Màn hình cũ với mức giá ưu đãi để lên đời cấu hình mới.',
            'Định giá nhanh trong 15 phút tại tất cả Showroom ĐOÀN 6 hoặc gửi yêu cầu trực tuyến.',
            'Thanh toán phần chênh lệch linh hoạt bằng COD hoặc chuyển khoản VietQR trong 15 phút.',
        ],
        'cta' => ['label' => 'Gửi yêu cầu Thu cũ đổi mới ngay', 'url' => 'trade-in.php'],
    ],
    'van-chuyen' => [
        'title' => 'Chính sách vận chuyển',
        'icon' => 'fa-truck-fast',
        'content' => [
            'Miễn phí giao hàng tiêu chuẩn toàn quốc cho đơn hàng linh kiện, laptop và PC lắp ráp.',
            'Hỗ trợ giao hàng hỏa tốc nội thành TP.HCM và Hà Nội trong 2 - 4 giờ.',
            'Kiểm tra ngoại quan, niêm phong và phụ kiện đi kèm trước khi ký nhận.',
        ],
    ],
    'bao-mat' => [
        'title' => 'Chính sách bảo mật thông tin',
        'icon' => 'fa-lock',
        'content' => [
            'Thông tin cá nhân, số điện thoại và lịch sử đơn hàng của khách hàng được bảo mật tuyệt đối.',
            'Mật khẩu tài khoản được mã hóa an toàn theo chuẩn bcrypt, không chia sẻ dữ liệu cho bên thứ ba.',
            'Hệ thống áp dụng kiểm tra CSRF trên mọi thao tác đặt hàng và cập nhật tài khoản.',
        ],
    ],
    'thanh-toan' => [
        'title' => 'Hướng dẫn thanh toán COD & VietQR',
        'icon' => 'fa-qrcode',
        'content' => [
            'Thanh toán khi nhận hàng (COD): Kiểm tra sản phẩm và thanh toán trực tiếp cho đơn vị vận chuyển.',
            'Chuyển khoản VietQR Napas 247: Quét mã QR hiển thị sau khi đặt hàng với số tiền và mã đơn chính xác trong vòng 15 phút.',
            'Sau khi chuyển khoản VietQR, khách hàng nhấn "Tôi đã chuyển khoản VietQR" để nhân viên đối soát và xử lý đơn nhanh chóng.',
        ],
        'cta' => ['label' => 'Tra cứu đơn hàng & thanh toán', 'url' => 'orders.php'],
    ],
];

$info = $pages[$pageKey] ?? $pages['showroom'];
$pageTitle = $info['title'] . ' - ĐOÀN 6';
require_once __DIR__ . '/includes/header.php';
?>

<div class="max-w-[960px] mx-auto px-3 py-6">
    <nav class="text-xs text-gray-500 mb-4 flex items-center gap-2">
        <a href="index.php" class="hover:text-gvn-red"><i class="fa-solid fa-house"></i> Trang chủ</a>
        <span>/</span>
        <span class="text-gray-800 font-semibold"><?= e($info['title']) ?></span>
    </nav>

    <div class="bg-white rounded-2xl border border-gray-200 p-6 shadow-sm space-y-5">
        <h1 class="text-xl font-extrabold text-gray-900 uppercase flex items-center gap-2 pb-3 border-b border-gray-100">
            <i class="fa-solid <?= e($info['icon']) ?> text-gvn-red"></i>
            <?= e($info['title']) ?>
        </h1>
        <ul class="space-y-3 text-sm text-gray-700">
            <?php foreach ($info['content'] as $line): ?>
                <li class="flex items-start gap-2.5">
                    <i class="fa-solid fa-circle-check text-emerald-500 mt-1 shrink-0"></i>
                    <span><?= e($line) ?></span>
                </li>
            <?php endforeach; ?>
        </ul>

        <?php if (!empty($info['cta'])): ?>
            <div class="pt-3 border-t border-gray-100">
                <a href="<?= e($info['cta']['url']) ?>" class="inline-flex items-center gap-2 bg-gvn-red hover:bg-gvn-redHover text-white font-bold px-5 py-2.5 rounded-xl text-xs transition">
                    <?= e($info['cta']['label']) ?>
                    <i class="fa-solid fa-arrow-right"></i>
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
