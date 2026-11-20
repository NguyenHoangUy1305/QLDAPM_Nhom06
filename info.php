<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/data/store.php';

$pageKey = $_GET['page'] ?? 'showroom';
$pages = [
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
            'Tổng đài tiếp nhận bảo hành: 0000.0000 (8:00 - 21:00 hàng ngày).',
        ],
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
            'Định giá nhanh trong 15 phút tại tất cả Showroom ĐOÀN 6.',
            'Hỗ trợ trả góp 0% cho phần chênh lệch.',
        ],
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

    <div class="bg-white rounded-2xl border border-gray-200 p-6 shadow-sm">
        <h1 class="text-xl font-extrabold text-gray-900 uppercase mb-4 flex items-center gap-2 pb-3 border-b border-gray-100">
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
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
