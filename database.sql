-- phpMyAdmin SQL Dump
-- Cơ sở dữ liệu: `database` (ĐOÀN 6 - Đồ án Quản lý dự án phần mềm Nhóm 06)

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

/*!40101 SET NAMES utf8mb4 */;

CREATE DATABASE IF NOT EXISTS `database` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `database`;

-- --------------------------------------------------------
-- 1. Bảng `categories`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `order_items`;
DROP TABLE IF EXISTS `orders`;
DROP TABLE IF EXISTS `products`;
DROP TABLE IF EXISTS `categories`;
DROP TABLE IF EXISTS `news`;
DROP TABLE IF EXISTS `users`;

CREATE TABLE `categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `icon` varchar(50) DEFAULT 'fa-tag',
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `categories` (`id`, `name`, `slug`, `icon`) VALUES
(1, 'Laptop', 'laptop', 'fa-laptop'),
(2, 'Laptop Gaming', 'laptop-gaming', 'fa-gamepad'),
(3, 'PC ĐOÀN 6', 'pc-gvn', 'fa-desktop'),
(4, 'Màn hình máy tính', 'man-hinh', 'fa-tv'),
(5, 'Bàn phím & Chuột', 'ban-phim-chuot', 'fa-keyboard'),
(6, 'Tai nghe & Loa', 'tai-nghe-loa', 'fa-headphones'),
(7, 'Linh kiện máy tính', 'linh-kien', 'fa-microchip'),
(8, 'Ghế - Bàn Gaming', 'ghe-ban-gaming', 'fa-chair'),
(9, 'Thiết bị văn phòng', 'thiet-bi-van-phong', 'fa-print'),
(10, 'Thiết bị mạng', 'thiet-bi-mang', 'fa-wifi'),
(11, 'Phụ kiện & Cáp sạc', 'phu-kien', 'fa-plug'),
(12, 'Tay cầm chơi game', 'tay-cam', 'fa-gamepad'),
(13, 'Console & Handheld', 'console', 'fa-vr-cardboard'),
(14, 'Studio & Livestream', 'studio', 'fa-microphone'),
(15, 'Hàng Cũ - Đổi Trả', 'hang-cu', 'fa-tags'),
(16, 'Dịch vụ IT & Sửa chữa', 'dich-vu', 'fa-tools');

-- --------------------------------------------------------
-- 2. Bảng `products` (có thêm cột `stock` quản lý tồn kho)
-- --------------------------------------------------------
CREATE TABLE `products` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `slug` varchar(255) NOT NULL,
  `name` varchar(500) NOT NULL,
  `category_slug` varchar(255) NOT NULL,
  `brand` varchar(100) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `old_price` decimal(12,2) DEFAULT 0.00,
  `price` decimal(12,2) DEFAULT 0.00,
  `discount` int(11) DEFAULT 0,
  `fast_delivery` tinyint(1) DEFAULT 1,
  `specs` text DEFAULT NULL COMMENT 'Chuỗi phân cách bằng dấu phẩy',
  `promo` varchar(500) DEFAULT NULL,
  `sold_percent` int(11) DEFAULT 0,
  `sold_text` varchar(100) DEFAULT NULL,
  `flash_sale` tinyint(1) DEFAULT 0,
  `stock` int(11) NOT NULL DEFAULT 25,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `products` (`id`, `slug`, `name`, `category_slug`, `brand`, `image`, `old_price`, `price`, `discount`, `fast_delivery`, `specs`, `promo`, `sold_percent`, `sold_text`, `flash_sale`, `stock`) VALUES
(1, 'laptop-gaming-acer-nitro-v-15', 'Laptop Gaming Acer Nitro V 15 ANV15-51-58AN (i5-13420H / RTX 2050 / 16GB / 512GB)', 'laptop-gaming', 'ACER', 'assets/images/products/laptop1.jpg', 24990000.00, 16990000.00, 32, 1, 'i5-13420H,RTX 2050,16GB RAM,512GB SSD,15.6\" 144Hz', 'Tặng Balo Acer Predator SUV + Chuột Gaming', 82, 'Đã bán 41/50 suất', 1, 9),
(2, 'pc-gvn-intel-i5-rtx-4060', 'PC ĐOÀN 6 Intel i5-12400F / VGA RTX 4060 8GB / RAM 16GB / SSD 500GB', 'pc-gvn', 'PC I5', 'assets/images/products/pc1.jpg', 22500000.00, 17990000.00, 20, 1, 'i5-12400F,RTX 4060,16GB DDR4,500GB NVMe', 'Tặng Màn hình 240Hz khi nâng cấp VGA', 65, 'Đã bán 13/20 suất', 1, 7),
(3, 'man-hinh-acer-nitro-vg240y-180hz', 'Màn hình Acer Nitro VG240Y M3 24\" IPS 180Hz 0.5ms Chuyên Game', 'man-hinh', 'ACER', 'assets/images/products/monitor1.jpg', 4590000.00, 2890000.00, 37, 1, '24 inch,IPS,180Hz,0.5ms,FHD', 'Giảm thêm 200K khi mua kèm PC/Laptop', 90, 'Sắp hết suất', 1, 5),
(4, 'ban-phim-co-akko-3098b-multi-modes', 'Bàn phím cơ Akko 3098B Multi-modes Prunus Lannesiana Hotswap RGB', 'ban-phim-chuot', 'Bàn phím cơ', 'assets/images/products/keyboard1.jpg', 2190000.00, 1390000.00, 37, 1, '3 Modes,Hotswap,RGB,PBT Keycap', 'Tặng kèm kê tay gỗ cao cấp', 55, 'Đã bán 22/40 suất', 1, 18),
(5, 'chuot-logitech-g-pro-x-superlight-2', 'Chuột không dây Logitech G Pro X Superlight 2 Lightspeed Wireless', 'ban-phim-chuot', 'Chuột không dây', 'assets/images/products/mouse1.jpg', 3890000.00, 2990000.00, 23, 1, '60g,HERO 2 32K,Lightspeed,95h Pin', 'Tặng lót chuột Gaming size XL', 70, 'Đã bán 14/20 suất', 1, 6),
(6, 'pc-gvn-intel-i3-12100f-gtx-1650', 'PC ĐOÀN 6 Intel i3-12100F / VGA GTX 1650 4GB / RAM 16GB / SSD 256GB', 'pc-gvn', 'PC I3', 'assets/images/products/pc2.jpg', 12990000.00, 9990000.00, 23, 1, 'i3-12100F,GTX 1650,16GB RAM,256GB SSD', 'Tặng bộ Phím Chuột Gaming trị giá 500K', 0, NULL, 0, 15),
(7, 'pc-gvn-intel-i7-14700k-rtx-4070-super', 'PC ĐOÀN 6 Intel i7-14700K / VGA RTX 4070 Super 12GB / RAM 32GB DDR5', 'pc-gvn', 'PC I7', 'assets/images/products/pc3.jpg', 45990000.00, 39990000.00, 13, 1, 'i7-14700K,RTX 4070S,32GB DDR5,1TB NVMe', 'Tặng Tản nhiệt nước AIO 360mm', 0, NULL, 0, 8),
(8, 'pc-gvn-ryzen-5-7600-rtx-4060-ti', 'PC ĐOÀN 6 AMD Ryzen 5 7600 / VGA RTX 4060 Ti 8GB / RAM 32GB DDR5', 'pc-gvn', 'PC R5', 'assets/images/products/pc4.jpg', 29990000.00, 25490000.00, 15, 1, 'Ryzen 5 7600,RTX 4060Ti,32GB DDR5,500GB SSD', 'Hỗ trợ trả góp 0% lãi suất', 0, NULL, 0, 12),
(9, 'laptop-gaming-asus-tuf-f15', 'Laptop Gaming ASUS TUF Gaming F15 FX507ZC4-HN095W (i5-12500H / RTX 3050)', 'laptop-gaming', 'ASUS', 'assets/images/products/laptop2.jpg', 25990000.00, 19490000.00, 25, 1, 'i5-12500H,RTX 3050,16GB RAM,512GB SSD,144Hz', 'Tặng Combo Gear ASUS trị giá 1.5 triệu', 0, NULL, 0, 20),
(10, 'laptop-gaming-msi-katana-15', 'Laptop Gaming MSI Katana 15 B13VEK-252VN (i7-13620H / RTX 4050)', 'laptop-gaming', 'MSI', 'assets/images/products/laptop3.jpg', 31990000.00, 25990000.00, 19, 1, 'i7-13620H,RTX 4050,16GB DDR5,512GB SSD', 'Tặng Balo MSI Gaming + Chuột', 0, NULL, 0, 14),
(11, 'laptop-gaming-lenovo-loq-15', 'Laptop Gaming Lenovo LOQ 15IAX9 83GS001RVN (i5-12450HX / RTX 3050 6GB)', 'laptop-gaming', 'LENOVO', 'assets/images/products/laptop4.jpg', 23990000.00, 18990000.00, 21, 1, 'i5-12450HX,RTX 3050 6GB,16GB RAM,100% sRGB', 'Tặng Balo Lenovo Gaming', 0, NULL, 0, 16),
(12, 'laptop-asus-vivobook-15-oled', 'Laptop ASUS Vivobook 15 OLED A1505VA-L1114W (i5-13500H / 16GB / 512GB)', 'laptop', 'ASUS', 'assets/images/products/laptop3.jpg', 19990000.00, 16490000.00, 18, 1, 'i5-13500H,16GB RAM,512GB SSD,15.6\" OLED', 'Giảm thêm 1 Triệu cho HSSV', 0, NULL, 0, 22),
(13, 'laptop-lenovo-ideapad-slim-5', 'Laptop Lenovo IdeaPad Slim 5 14IMH9 (Core Ultra 5 125H / 16GB / 512GB)', 'laptop', 'Laptop AI', 'assets/images/products/laptop4.jpg', 22990000.00, 18990000.00, 17, 1, 'Core Ultra 5,AI Boost,16GB RAM,14\" OLED', 'Tặng chuột không dây Lenovo', 0, NULL, 0, 11),
(14, 'man-hinh-asus-tuf-vg27aq3a-180hz', 'Màn hình ASUS TUF Gaming VG27AQ3A 27\" Fast IPS 2K 180Hz G-Sync', 'man-hinh', 'ASUS', 'assets/images/products/monitor2.jpg', 7990000.00, 5990000.00, 25, 1, '27 inch,2K QHD,Fast IPS,180Hz,1ms', 'Bảo hành 3 năm chính hãng', 0, NULL, 0, 19),
(15, 'tai-nghe-hyperx-cloud-iii', 'Tai nghe Gaming HyperX Cloud III Đỏ Đen (DTS Spatial Audio)', 'tai-nghe-loa', 'Tai nghe 7.1', 'assets/images/products/headset1.jpg', 2690000.00, 1990000.00, 26, 1, '53mm Driver,DTS:X,Mic chống ồn,USB-C/3.5mm', 'Tặng giá treo tai nghe Gaming', 0, NULL, 0, 30);

-- --------------------------------------------------------
-- 3. Bảng `news`
-- --------------------------------------------------------
CREATE TABLE `news` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(500) NOT NULL,
  `summary` text DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `date` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `news` (`id`, `title`, `summary`, `image`, `date`) VALUES
(1, 'Đánh giá RTX 4060 vs RTX 4060 Ti: Card đồ họa nào đáng mua nhất 2026?', 'So sánh hiệu năng thực tế trên các tựa game AAA ở độ phân giải Full HD và 2K, mức tiêu thụ điện năng và giá thành.', 'assets/images/news/news1.jpg', '15/11/2026'),
(2, 'Top 5 Laptop Gaming dưới 20 triệu đáng mua nhất mùa tựu trường', 'Tổng hợp các mẫu laptop trang bị RTX 3050, màn hình 144Hz chuẩn gaming từ Acer, ASUS, Lenovo.', 'assets/images/news/news2.jpg', '14/11/2026'),
(3, 'Hướng dẫn chọn bàn phím cơ cho người mới: Linear, Tactile hay Clicky?', 'Tìm hiểu sự khác biệt giữa các loại switch phổ biến và cách chọn layout phù hợp nhu cầu.', 'assets/images/news/news3.jpg', '12/11/2026'),
(4, 'Đoàn 6 khai trương Showroom mới tại TP.HCM với hàng ngàn deal sốc', 'Cơ hội săn gear giá 1K, giảm giá lên đến 50% cho các sản phẩm màn hình và linh kiện PC.', 'assets/images/news/news4.jpg', '10/11/2026');

-- --------------------------------------------------------
-- 4. Bảng `users` (quản lý tài khoản Khách hàng & Admin)
-- --------------------------------------------------------
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `address` varchar(500) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('user','admin') NOT NULL DEFAULT 'user',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Mật khẩu tài khoản mẫu sẽ được đồng bộ hoặc dùng login.php hỗ trợ:
-- admin@doan6.local (mật khẩu: admin123)
-- demo@doan6.local  (mật khẩu: 123456)
INSERT INTO `users` (`id`, `name`, `email`, `phone`, `address`, `password`, `role`, `created_at`) VALUES
(1, 'Quản trị viên Đoàn 6', 'admin@doan6.local', '0000.0000', 'Khu vực Demo, TP. Hồ Chí Minh', '$2y$10$hcFyM26Yvc9Akbk3tRt0DeJ0IrfBCnMQSydLR8PAyNXw07zu2YKGu', 'admin', NOW()),
(2, 'Thành viên Đoàn 6', 'demo@doan6.local', '0000.0000', 'Khu vực Demo, TP. Hồ Chí Minh', '$2y$10$WFevaHZ6yYUEaFI0H7cNxOImHiaYk.oAtBlaKQ4lHCtUx7cv1ORz.', 'user', NOW());

-- --------------------------------------------------------
-- 5. Bảng `orders` (quản lý đơn đặt hàng)
-- --------------------------------------------------------
CREATE TABLE `orders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `customer_name` varchar(255) NOT NULL,
  `phone` varchar(30) NOT NULL,
  `address` varchar(500) NOT NULL,
  `payment_method` varchar(100) DEFAULT 'COD',
  `note` text DEFAULT NULL,
  `total_price` decimal(14,2) NOT NULL DEFAULT 0.00,
  `status` varchar(50) NOT NULL DEFAULT 'Chờ xác nhận',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `orders` (`id`, `user_id`, `customer_name`, `phone`, `address`, `payment_method`, `note`, `total_price`, `status`, `created_at`) VALUES
(1, 2, 'Nguyễn Văn An', '0000.0000', 'Khu vực Demo, Quận 10, TP. Hồ Chí Minh', 'COD (Thanh toán khi nhận hàng)', 'Giao giờ hành chính', 18380000.00, 'Chờ xác nhận', NOW()),
(2, 2, 'Trần Thị Bình', '0000.0000', 'Khu vực Demo, Quận Tân Bình, TP. Hồ Chí Minh', 'Chuyển khoản ngân hàng', '', 17990000.00, 'Đang giao', NOW());

-- --------------------------------------------------------
-- 6. Bảng `order_items` (chi tiết sản phẩm trong từng đơn hàng)
-- --------------------------------------------------------
CREATE TABLE `order_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `product_name` varchar(500) DEFAULT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `price` decimal(12,2) NOT NULL DEFAULT 0.00,
  PRIMARY KEY (`id`),
  KEY `order_id` (`order_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `product_name`, `quantity`, `price`) VALUES
(1, 1, 1, 'Laptop Gaming Acer Nitro V 15 ANV15-51-58AN (i5-13420H / RTX 2050 / 16GB / 512GB)', 1, 16990000.00),
(2, 1, 4, 'Bàn phím cơ Akko 3098B Multi-modes Prunus Lannesiana Hotswap RGB', 1, 1390000.00),
(3, 2, 2, 'PC ĐOÀN 6 Intel i5-12400F / VGA RTX 4060 8GB / RAM 16GB / SSD 500GB', 1, 17990000.00);

COMMIT;
