-- phpMyAdmin SQL Dump
-- Cơ sở dữ liệu: `database` (ĐOÀN 6 - Đồ án Quản lý dự án phần mềm Nhóm 06)

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET FOREIGN_KEY_CHECKS = 0;
START TRANSACTION;
SET time_zone = "+07:00";

/*!40101 SET NAMES utf8mb4 */;

CREATE DATABASE IF NOT EXISTS `database` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `database`;

DROP TABLE IF EXISTS `warranties`;
DROP TABLE IF EXISTS `trade_in_requests`;
DROP TABLE IF EXISTS `order_items`;
DROP TABLE IF EXISTS `orders`;
DROP TABLE IF EXISTS `product_images`;
DROP TABLE IF EXISTS `products`;
DROP TABLE IF EXISTS `news`;
DROP TABLE IF EXISTS `users`;
DROP TABLE IF EXISTS `categories`;
DROP TABLE IF EXISTS `brands`;

-- --------------------------------------------------------
-- 1. Bảng `brands` (Thương hiệu / Nhà sản xuất)
-- --------------------------------------------------------
CREATE TABLE `brands` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `slug` varchar(120) NOT NULL,
  `country` varchar(100) DEFAULT NULL,
  `logo` varchar(255) DEFAULT NULL,
  `description` varchar(500) DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_brands_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `brands` (`id`, `name`, `slug`, `country`, `logo`, `description`) VALUES
(1, 'ASUS', 'asus', 'Đài Loan', NULL, 'Laptop, Bo mạch chủ, VGA, Màn hình gaming hàng đầu'),
(2, 'MSI', 'msi', 'Đài Loan', NULL, 'Laptop Gaming, Card đồ họa, Bo mạch chủ cao cấp'),
(3, 'Gigabyte', 'gigabyte', 'Đài Loan', NULL, 'Linh kiện PC, Mainboard, VGA AORUS/Gaming'),
(4, 'Intel', 'intel', 'Hoa Kỳ', NULL, 'Bộ vi xử lý Intel Core hiệu năng cao'),
(5, 'AMD', 'amd', 'Hoa Kỳ', NULL, 'Bộ vi xử lý AMD Ryzen và giải pháp đồ họa'),
(6, 'Kingston', 'kingston', 'Hoa Kỳ', NULL, 'RAM Fury và ổ cứng SSD NVMe tốc độ cao'),
(7, 'Corsair', 'corsair', 'Hoa Kỳ', NULL, 'RAM, Nguồn máy tính, Vỏ case và Tản nhiệt cao cấp'),
(8, 'Logitech', 'logitech', 'Thụy Sĩ', NULL, 'Chuột gaming không dây, bàn phím và thiết bị ngoại vi'),
(9, 'Samsung', 'samsung', 'Hàn Quốc', NULL, 'Ổ cứng SSD NVMe và màn hình chuyên nghiệp'),
(10, 'LG', 'lg', 'Hàn Quốc', NULL, 'Màn hình UltraGear, OLED và thiết bị hiển thị'),
(11, 'Acer', 'acer', 'Đài Loan', NULL, 'Laptop Gaming Nitro, Predator và màn hình gaming'),
(12, 'Lenovo', 'lenovo', 'Trung Quốc', NULL, 'Laptop LOQ, Legion và IdeaPad chính hãng'),
(13, 'AKKO', 'akko', 'Trung Quốc', NULL, 'Bàn phím cơ custom, switch và keycap PBT'),
(14, 'HyperX', 'hyperx', 'Hoa Kỳ', NULL, 'Tai nghe gaming, micro và phụ kiện eSports'),
(15, 'Cooler Master', 'cooler-master', 'Đài Loan', NULL, 'Nguồn máy tính, tản nhiệt CPU và vỏ case PC'),
(16, 'ĐOÀN 6', 'doan-6', 'Việt Nam', NULL, 'PC lắp ráp tối ưu hiệu năng độc quyền bởi ĐOÀN 6');

-- --------------------------------------------------------
-- 2. Bảng `categories` (Danh mục sản phẩm)
-- --------------------------------------------------------
CREATE TABLE `categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `icon` varchar(50) DEFAULT 'fa-tag',
  `description` varchar(500) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `categories` (`id`, `name`, `slug`, `icon`, `description`) VALUES
(1, 'Laptop', 'laptop', 'fa-laptop', 'Laptop văn phòng, học tập, thiết kế mỏng nhẹ chính hãng'),
(2, 'Laptop Gaming', 'laptop-gaming', 'fa-gamepad', 'Laptop cấu hình cao, màn hình tần số quét lớn cho game thủ'),
(3, 'PC ĐOÀN 6', 'pc-gvn', 'fa-desktop', 'Bộ PC lắp ráp sẵn chuẩn hiệu năng tại ĐOÀN 6'),
(4, 'Màn hình máy tính', 'man-hinh', 'fa-tv', 'Màn hình Gaming 144Hz-240Hz, đồ họa 2K/4K sắc nét'),
(5, 'Bàn phím & Chuột', 'ban-phim-chuot', 'fa-keyboard', 'Bàn phím cơ custom, chuột gaming không dây siêu nhẹ'),
(6, 'Tai nghe & Loa', 'tai-nghe-loa', 'fa-headphones', 'Tai nghe gaming 7.1, micro và loa máy tính'),
(7, 'Linh kiện máy tính', 'linh-kien', 'fa-microchip', 'CPU, Mainboard, RAM, VGA, SSD, Nguồn, Case, Tản nhiệt lắp PC'),
(8, 'Ghế - Bàn Gaming', 'ghe-ban-gaming', 'fa-chair', 'Ghế công thái học và bàn gaming chuyên dụng'),
(9, 'Thiết bị văn phòng', 'thiet-bi-van-phong', 'fa-print', 'Máy in, máy scan và thiết bị hỗ trợ văn phòng'),
(10, 'Thiết bị mạng', 'thiet-bi-mang', 'fa-wifi', 'Router Wi-Fi 6/7, bộ phát mesh và card mạng'),
(11, 'Phụ kiện & Cáp sạc', 'phu-kien', 'fa-plug', 'Cáp chuyển đổi, hub USB-C, giá đỡ và phụ kiện'),
(12, 'Tay cầm chơi game', 'tay-cam', 'fa-gamepad', 'Tay cầm không dây tương thích PC và Console'),
(13, 'Console & Handheld', 'console', 'fa-vr-cardboard', 'Máy chơi game cầm tay và hệ thống console'),
(14, 'Studio & Livestream', 'studio', 'fa-microphone', 'Webcam, capture card, đèn livestream chuyên nghiệp'),
(15, 'Hàng Cũ - Đổi Trả', 'hang-cu', 'fa-tags', 'Sản phẩm thu cũ đổi mới được kiểm định kỹ thuật'),
(16, 'Dịch vụ IT & Sửa chữa', 'dich-vu', 'fa-tools', 'Vệ sinh, bảo trì, lắp ráp PC và nâng cấp phần cứng');

-- --------------------------------------------------------
-- 3. Bảng `users` (3 vai trò: admin, staff, customer)
-- --------------------------------------------------------
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `address` varchar(500) DEFAULT NULL,
  `role` enum('admin','staff','customer') NOT NULL DEFAULT 'customer',
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tài khoản mẫu (mật khẩu đã hash bcrypt):
-- admin@doan6.local     / admin123  (role: admin)
-- nhanvien@doan6.local  / staff123  (role: staff)
-- khachhang@doan6.local / 123456    (role: customer)
INSERT INTO `users` (`id`, `name`, `email`, `password`, `phone`, `address`, `role`, `status`, `created_at`) VALUES
(1, 'Quản trị viên Đoàn 6', 'admin@doan6.local', '$2y$10$ZLL5iu2.2Z2wk5cM9LUMHeX9zpAZN1D659Ipy8BynEtxuOUG6vjsy', '0901000001', 'Showroom ĐOÀN 6 - Quận 10, TP. Hồ Chí Minh', 'admin', 'active', NOW()),
(2, 'Nhân viên Bán Hàng', 'nhanvien@doan6.local', '$2y$10$eI1zJDqV1bHAGw8Q1UsX8OW8ouqNxbxGx8yBI7LkKCqRrXSLMCkR2', '0901000002', 'Showroom ĐOÀN 6 - Quận 10, TP. Hồ Chí Minh', 'staff', 'active', NOW()),
(3, 'Nguyễn Văn Khách', 'khachhang@doan6.local', '$2y$10$q/PeqSqSPwjEokn2fYYSOuoOucRWmqpMveIKBoFIoxd/WJ3gHKCnK', '0909123456', '273 An Dương Vương, Quận 5, TP. Hồ Chí Minh', 'customer', 'active', NOW());

-- --------------------------------------------------------
-- 4. Bảng `products` (Sản phẩm + Linh kiện PC Builder)
-- --------------------------------------------------------
CREATE TABLE `products` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `category_id` int(11) NOT NULL,
  `brand_id` int(11) DEFAULT NULL,
  `category_slug` varchar(255) NOT NULL,
  `brand` varchar(100) DEFAULT NULL,
  `name` varchar(500) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `price` decimal(12,2) NOT NULL DEFAULT 0.00,
  `old_price` decimal(12,2) NOT NULL DEFAULT 0.00,
  `discount` int(11) NOT NULL DEFAULT 0,
  `stock` int(11) NOT NULL DEFAULT 25,
  `image` varchar(255) DEFAULT NULL,
  `specs` text DEFAULT NULL COMMENT 'Chuỗi phân cách bằng dấu phẩy',
  `description` text DEFAULT NULL,
  `promo` varchar(500) DEFAULT NULL,
  `fast_delivery` tinyint(1) NOT NULL DEFAULT 1,
  `sold_percent` int(11) NOT NULL DEFAULT 0,
  `sold_text` varchar(100) DEFAULT NULL,
  `component_type` enum('none','cpu','mainboard','ram','vga','ssd','psu','case','cooling') NOT NULL DEFAULT 'none',
  `socket` varchar(40) DEFAULT NULL,
  `ram_type` varchar(20) DEFAULT NULL,
  `tdp_watt` int(11) NOT NULL DEFAULT 0,
  `psu_watt` int(11) NOT NULL DEFAULT 0,
  `warranty_months` int(11) NOT NULL DEFAULT 24,
  `is_featured` tinyint(1) NOT NULL DEFAULT 0,
  `is_flash_sale` tinyint(1) NOT NULL DEFAULT 0,
  `flash_sale` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`),
  KEY `idx_products_category_id` (`category_id`),
  KEY `idx_products_brand_id` (`brand_id`),
  KEY `idx_products_component_type` (`component_type`),
  CONSTRAINT `fk_products_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_products_brand` FOREIGN KEY (`brand_id`) REFERENCES `brands` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `products` (
  `id`, `category_id`, `brand_id`, `category_slug`, `brand`, `name`, `slug`,
  `price`, `old_price`, `discount`, `stock`, `image`, `specs`, `description`, `promo`,
  `fast_delivery`, `sold_percent`, `sold_text`,
  `component_type`, `socket`, `ram_type`, `tdp_watt`, `psu_watt`, `warranty_months`,
  `is_featured`, `is_flash_sale`, `flash_sale`
) VALUES
-- 15 sản phẩm chính (đã nâng cấp brand_id, category_id, warranty_months)
(1, 2, 11, 'laptop-gaming', 'Acer', 'Laptop Gaming Acer Nitro V 15 ANV15-51-58AN (i5-13420H / RTX 2050 / 16GB / 512GB)', 'laptop-gaming-acer-nitro-v-15',
 16990000.00, 24990000.00, 32, 9, 'assets/images/products/laptop1.jpg',
 'i5-13420H,RTX 2050,16GB RAM,512GB SSD,15.6" 144Hz',
 'Laptop Gaming Acer Nitro V 15 sở hữu CPU Intel Gen 13 mạnh mẽ, card đồ họa RTX 2050 cùng màn hình 144Hz mượt mà.',
 'Tặng Balo Acer Predator SUV + Chuột Gaming', 1, 82, 'Đã bán 41/50 suất',
 'none', NULL, NULL, 0, 0, 24, 1, 1, 1),

(2, 3, 16, 'pc-gvn', 'ĐOÀN 6', 'PC ĐOÀN 6 Intel i5-12400F / VGA RTX 4060 8GB / RAM 16GB / SSD 500GB', 'pc-gvn-intel-i5-rtx-4060',
 17990000.00, 22500000.00, 20, 7, 'assets/images/products/pc1.jpg',
 'i5-12400F,RTX 4060,16GB DDR4,500GB NVMe',
 'Cấu hình PC Gaming tối ưu tầm trung chiến mượt mọi tựa game eSports và AAA ở Full HD.',
 'Tặng kèm lót chuột size XL + Miễn phí lắp đặt', 1, 65, 'Đã bán 13/20 suất',
 'none', NULL, NULL, 0, 0, 36, 1, 1, 1),

(3, 4, 11, 'man-hinh', 'Acer', 'Màn hình Acer Nitro VG240Y M3 24" IPS 180Hz 0.5ms Chuyên Game', 'man-hinh-acer-nitro-vg240y-180hz',
 2890000.00, 4590000.00, 37, 5, 'assets/images/products/monitor1.jpg',
 '24 inch,IPS,180Hz,0.5ms,FHD',
 'Màn hình gaming tần số quét 180Hz tấm nền IPS cho màu sắc trung thực và phản hồi siêu nhanh.',
 'Giảm thêm 200K khi mua kèm PC/Laptop', 1, 90, 'Sắp hết suất',
 'none', NULL, NULL, 0, 0, 36, 1, 1, 1),

(4, 5, 13, 'ban-phim-chuot', 'AKKO', 'Bàn phím cơ Akko 3098B Multi-modes Prunus Lannesiana Hotswap RGB', 'ban-phim-co-akko-3098b-multi-modes',
 1390000.00, 2190000.00, 37, 18, 'assets/images/products/keyboard1.jpg',
 '3 Modes,Hotswap,RGB,PBT Keycap',
 'Bàn phím cơ layout 98 phím nhỏ gọn, hỗ trợ kết nối 3 chế độ và keycap PBT double-shot bền bỉ.',
 'Tặng kèm kê tay gỗ cao cấp', 1, 55, 'Đã bán 22/40 suất',
 'none', NULL, NULL, 0, 0, 12, 1, 1, 1),

(5, 5, 8, 'ban-phim-chuot', 'Logitech', 'Chuột không dây Logitech G Pro X Superlight 2 Lightspeed Wireless', 'chuot-logitech-g-pro-x-superlight-2',
 2990000.00, 3890000.00, 23, 6, 'assets/images/products/mouse1.jpg',
 '60g,HERO 2 32K,Lightspeed,95h Pin',
 'Chuột gaming siêu nhẹ 60g chuẩn thi đấu eSports với cảm biến HERO 2 32.000 DPI.',
 'Tặng lót chuột Gaming size XL', 1, 70, 'Đã bán 14/20 suất',
 'none', NULL, NULL, 0, 0, 24, 1, 1, 1),

(6, 3, 16, 'pc-gvn', 'ĐOÀN 6', 'PC ĐOÀN 6 Intel i3-12100F / VGA GTX 1650 4GB / RAM 16GB / SSD 256GB', 'pc-gvn-intel-i3-12100f-gtx-1650',
 9990000.00, 12990000.00, 23, 15, 'assets/images/products/pc2.jpg',
 'i3-12100F,GTX 1650,16GB RAM,256GB SSD',
 'Bộ PC giá tốt cho học sinh, sinh viên làm đồ họa nhẹ và chơi mượt game online phổ biến.',
 'Tặng bộ Phím Chuột Gaming trị giá 500K', 1, 0, NULL,
 'none', NULL, NULL, 0, 0, 24, 0, 0, 0),

(7, 3, 16, 'pc-gvn', 'ĐOÀN 6', 'PC ĐOÀN 6 Intel i7-14700K / VGA RTX 4070 Super 12GB / RAM 32GB DDR5', 'pc-gvn-intel-i7-14700k-rtx-4070-super',
 39990000.00, 45990000.00, 13, 8, 'assets/images/products/pc3.jpg',
 'i7-14700K,RTX 4070S,32GB DDR5,1TB NVMe',
 'Cấu hình PC High-End dành cho render 3D, AI và chơi game 2K/4K Max Settings.',
 'Tặng Tản nhiệt nước AIO 360mm', 1, 0, NULL,
 'none', NULL, NULL, 0, 0, 36, 1, 0, 0),

(8, 3, 16, 'pc-gvn', 'ĐOÀN 6', 'PC ĐOÀN 6 AMD Ryzen 5 7600 / VGA RTX 4060 Ti 8GB / RAM 32GB DDR5', 'pc-gvn-ryzen-5-7600-rtx-4060-ti',
 25490000.00, 29990000.00, 15, 12, 'assets/images/products/pc4.jpg',
 'Ryzen 5 7600,RTX 4060Ti,32GB DDR5,500GB SSD',
 'Sức mạnh nền tảng AM5 kết hợp RTX 4060 Ti và 32GB RAM DDR5 cho hiệu năng ổn định dài lâu.',
 'Miễn phí lắp đặt & giao hàng hỏa tốc', 1, 0, NULL,
 'none', NULL, NULL, 0, 0, 36, 1, 0, 0),

(9, 2, 1, 'laptop-gaming', 'ASUS', 'Laptop Gaming ASUS TUF Gaming F15 FX507ZC4-HN095W (i5-12500H / RTX 3050)', 'laptop-gaming-asus-tuf-f15',
 19490000.00, 25990000.00, 25, 20, 'assets/images/products/laptop2.jpg',
 'i5-12500H,RTX 3050,16GB RAM,512GB SSD,144Hz',
 'ASUS TUF F15 đạt chuẩn độ bền quân đội MIL-STD-810H, tản nhiệt Arc Flow mát mẻ.',
 'Tặng Combo Gear ASUS trị giá 1.5 triệu', 1, 0, NULL,
 'none', NULL, NULL, 0, 0, 24, 1, 0, 0),

(10, 2, 2, 'laptop-gaming', 'MSI', 'Laptop Gaming MSI Katana 15 B13VEK-252VN (i7-13620H / RTX 4050)', 'laptop-gaming-msi-katana-15',
 25990000.00, 31990000.00, 19, 14, 'assets/images/products/laptop3.jpg',
 'i7-13620H,RTX 4050,16GB DDR5,512GB SSD',
 'MSI Katana 15 trang bị RTX 4050 hỗ trợ DLSS 3 cùng hệ thống tản nhiệt Cooler Boost 5.',
 'Tặng Balo MSI Gaming + Chuột', 1, 0, NULL,
 'none', NULL, NULL, 0, 0, 24, 0, 0, 0),

(11, 2, 12, 'laptop-gaming', 'Lenovo', 'Laptop Gaming Lenovo LOQ 15IAX9 83GS001RVN (i5-12450HX / RTX 3050 6GB)', 'laptop-gaming-lenovo-loq-15',
 18990000.00, 23990000.00, 21, 16, 'assets/images/products/laptop4.jpg',
 'i5-12450HX,RTX 3050 6GB,16GB RAM,100% sRGB',
 'Lenovo LOQ 15 thiết kế tối giản, màn hình 100% sRGB phù hợp cả gaming lẫn sáng tạo nội dung.',
 'Tặng Balo Lenovo Gaming', 1, 0, NULL,
 'none', NULL, NULL, 0, 0, 24, 0, 0, 0),

(12, 1, 1, 'laptop', 'ASUS', 'Laptop ASUS Vivobook 15 OLED A1505VA-L1114W (i5-13500H / 16GB / 512GB)', 'laptop-asus-vivobook-15-oled',
 16490000.00, 19990000.00, 18, 22, 'assets/images/products/laptop3.jpg',
 'i5-13500H,16GB RAM,512GB SSD,15.6" OLED',
 'Màn hình Lumina OLED rực rỡ chuẩn màu điện ảnh cùng hiệu năng chip H mạnh mẽ.',
 'Giảm thêm 1 Triệu cho HSSV', 1, 0, NULL,
 'none', NULL, NULL, 0, 0, 24, 1, 0, 0),

(13, 1, 12, 'laptop', 'Lenovo', 'Laptop Lenovo IdeaPad Slim 5 14IMH9 (Core Ultra 5 125H / 16GB / 512GB)', 'laptop-lenovo-ideapad-slim-5',
 18990000.00, 22990000.00, 17, 11, 'assets/images/products/laptop4.jpg',
 'Core Ultra 5,AI Boost,16GB RAM,14" OLED',
 'Laptop mỏng nhẹ tích hợp nhân xử lý AI chuyên dụng Intel Core Ultra thế hệ mới.',
 'Tặng chuột không dây Lenovo', 1, 0, NULL,
 'none', NULL, NULL, 0, 0, 24, 0, 0, 0),

(14, 4, 1, 'man-hinh', 'ASUS', 'Màn hình ASUS TUF Gaming VG27AQ3A 27" Fast IPS 2K 180Hz G-Sync', 'man-hinh-asus-tuf-vg27aq3a-180hz',
 5990000.00, 7990000.00, 25, 19, 'assets/images/products/monitor2.jpg',
 '27 inch,2K QHD,Fast IPS,180Hz,1ms',
 'Màn hình 2K 27 inch Fast IPS hỗ trợ ELMB Sync và G-Sync tương thích mượt mà.',
 'Bảo hành 3 năm chính hãng', 1, 0, NULL,
 'none', NULL, NULL, 0, 0, 36, 1, 0, 0),

(15, 6, 14, 'tai-nghe-loa', 'HyperX', 'Tai nghe Gaming HyperX Cloud III Đỏ Đen (DTS Spatial Audio)', 'tai-nghe-hyperx-cloud-iii',
 1990000.00, 2690000.00, 26, 30, 'assets/images/products/headset1.jpg',
 '53mm Driver,DTS:X,Mic chống ồn,USB-C/3.5mm',
 'Đệm tai memory foam êm ái đặc trưng HyperX cùng âm thanh vòm DTS Headphone:X.',
 'Tặng giá treo tai nghe Gaming', 1, 0, NULL,
 'none', NULL, NULL, 0, 0, 24, 0, 0, 0),

-- 16 linh kiện PC Builder độc lập (đầy đủ 8 component_type + thông số socket/ram_type/tdp_watt/psu_watt)
(16, 7, 4, 'linh-kien', 'Intel', 'CPU Intel Core i5-12400F (6 Nhân 12 Luồng / LGA1700 / Up to 4.4GHz)', 'cpu-intel-core-i5-12400f',
 2990000.00, 3690000.00, 19, 25, 'assets/images/products/pc1.jpg',
 'LGA1700,6C/12T,4.4GHz,TDP 65W',
 'Bộ vi xử lý quốc dân cho PC Gaming tầm trung, tương thích tốt bo mạch chủ LGA1700.',
 'Miễn phí lắp đặt khi Build PC tại ĐOÀN 6', 1, 0, NULL,
 'cpu', 'LGA1700', 'DDR4', 65, 0, 36, 1, 0, 0),

(17, 7, 4, 'linh-kien', 'Intel', 'CPU Intel Core i7-14700K (20 Nhân 28 Luồng / LGA1700 / Up to 5.6GHz)', 'cpu-intel-core-i7-14700k',
 9890000.00, 11490000.00, 14, 12, 'assets/images/products/pc3.jpg',
 'LGA1700,20C/28T,5.6GHz,TDP 125W',
 'Vi xử lý hiệu năng cao cho máy trạm đồ họa, dựng phim và gaming đỉnh cao.',
 'Tặng keo tản nhiệt cao cấp', 1, 0, NULL,
 'cpu', 'LGA1700', 'DDR5', 125, 0, 36, 0, 0, 0),

(18, 7, 5, 'linh-kien', 'AMD', 'CPU AMD Ryzen 5 7600 (6 Nhân 12 Luồng / Socket AM5 / Up to 5.1GHz)', 'cpu-amd-ryzen-5-7600',
 4990000.00, 5890000.00, 15, 18, 'assets/images/products/pc4.jpg',
 'AM5,6C/12T,5.1GHz,TDP 65W',
 'CPU AMD kiến trúc Zen 4 tiết kiệm điện, hỗ trợ DDR5 và PCIe 5.0 lâu dài.',
 'Kèm tản nhiệt Wraith Stealth chính hãng', 1, 0, NULL,
 'cpu', 'AM5', 'DDR5', 65, 0, 36, 0, 0, 0),

(19, 7, 1, 'linh-kien', 'ASUS', 'Mainboard ASUS PRIME B760M-A D4 (LGA1700 / mATX / DDR4)', 'mainboard-asus-prime-b760m-a-d4',
 2790000.00, 3290000.00, 15, 20, 'assets/images/products/pc2.jpg',
 'LGA1700,mATX,4 khe DDR4,2x M.2 PCIe 4.0',
 'Bo mạch chủ ASUS B760 bền bỉ với tản nhiệt VRM lớn và 2 khe SSD NVMe Gen4.',
 'Hỗ trợ cập nhật BIOS miễn phí', 1, 0, NULL,
 'mainboard', 'LGA1700', 'DDR4', 30, 0, 36, 0, 0, 0),

(20, 7, 2, 'linh-kien', 'MSI', 'Mainboard MSI MAG Z790 TOMAHAWK WIFI (LGA1700 / ATX / DDR5)', 'mainboard-msi-mag-z790-tomahawk-wifi',
 6490000.00, 7490000.00, 13, 10, 'assets/images/products/pc3.jpg',
 'LGA1700,ATX,DDR5,Wi-Fi 6E,4x M.2',
 'Bo mạch chủ phân khúc cao cấp chuẩn ATX tối ưu ép xung Intel Gen 13/14.',
 'Bảo hành chính hãng 36 tháng', 1, 0, NULL,
 'mainboard', 'LGA1700', 'DDR5', 40, 0, 36, 0, 0, 0),

(21, 7, 3, 'linh-kien', 'Gigabyte', 'Mainboard GIGABYTE B650M GAMING X AX (AM5 / mATX / DDR5 / Wi-Fi 6E)', 'mainboard-gigabyte-b650m-gaming-x-ax',
 4290000.00, 4990000.00, 14, 14, 'assets/images/products/pc4.jpg',
 'AM5,mATX,DDR5,Wi-Fi 6E,2.5GbE LAN',
 'Mainboard nền tảng AMD AM5 hỗ trợ DDR5 EXPO và kết nối không dây tốc độ cao.',
 'Tặng kèm dây SATA bọc dù', 1, 0, NULL,
 'mainboard', 'AM5', 'DDR5', 35, 0, 36, 0, 0, 0),

(22, 7, 6, 'linh-kien', 'Kingston', 'RAM Kingston Fury Beast 16GB (2x8GB) DDR4 3200MHz CL16', 'ram-kingston-fury-beast-16gb-ddr4',
 1090000.00, 1390000.00, 22, 35, 'assets/images/products/pc1.jpg',
 '16GB (2x8GB),DDR4,3200MHz,XMP 2.0',
 'Kit RAM Dual Channel ổn định cao, tản nhiệt nhôm đen thanh lịch.',
 'Bảo hành chính hãng 36 tháng', 1, 0, NULL,
 'ram', NULL, 'DDR4', 10, 0, 36, 0, 0, 0),

(23, 7, 7, 'linh-kien', 'Corsair', 'RAM Corsair Vengeance 32GB (2x16GB) DDR5 5600MHz', 'ram-corsair-vengeance-32gb-ddr5',
 2890000.00, 3490000.00, 17, 22, 'assets/images/products/pc3.jpg',
 '32GB (2x16GB),DDR5,5600MHz,XMP 3.0',
 'Kit RAM DDR5 dung lượng 32GB tối ưu cho đa nhiệm nặng, render và game AAA.',
 'Bảo hành chính hãng 36 tháng', 1, 0, NULL,
 'ram', NULL, 'DDR5', 15, 0, 36, 0, 0, 0),

(24, 7, 1, 'linh-kien', 'ASUS', 'Card màn hình ASUS Dual GeForce RTX 4060 OC Edition 8GB GDDR6', 'vga-asus-dual-rtx-4060-oc-8gb',
 7990000.00, 9290000.00, 14, 16, 'assets/images/products/pc1.jpg',
 'RTX 4060,8GB GDDR6,DLSS 3,TDP 115W',
 'VGA kiến trúc Ada Lovelace mát mẻ với 2 quạt Axial-tech, hỗ trợ DLSS 3 Frame Generation.',
 'Hỗ trợ vệ sinh VGA định kỳ miễn phí', 1, 0, NULL,
 'vga', NULL, NULL, 115, 0, 36, 1, 0, 0),

(25, 7, 2, 'linh-kien', 'MSI', 'Card màn hình MSI GeForce RTX 4070 SUPER 12GB VENTUS 2X OC', 'vga-msi-rtx-4070-super-12gb',
 16990000.00, 18990000.00, 11, 9, 'assets/images/products/pc3.jpg',
 'RTX 4070 SUPER,12GB GDDR6X,DLSS 3,TDP 220W',
 'Hiệu năng đồ họa vượt trội ở độ phân giải 2K/4K và xử lý AI chuyên sâu.',
 'Tặng giá đỡ VGA nhôm CNC', 1, 0, NULL,
 'vga', NULL, NULL, 220, 0, 36, 0, 0, 0),

(26, 7, 9, 'linh-kien', 'Samsung', 'Ổ cứng SSD Samsung 980 PRO 1TB PCIe 4.0 NVMe M.2 (7000MB/s)', 'ssd-samsung-980-pro-1tb-nvme',
 2490000.00, 2990000.00, 17, 28, 'assets/images/products/pc2.jpg',
 '1TB,M.2 NVMe,PCIe 4.0,7000MB/s',
 'SSD NVMe PCIe 4.0 cao cấp tốc độ đọc lên tới 7000MB/s, độ bền TBW ấn tượng.',
 'Hỗ trợ cài Windows bản quyền dùng thử', 1, 0, NULL,
 'ssd', NULL, NULL, 8, 0, 60, 0, 0, 0),

(27, 7, 6, 'linh-kien', 'Kingston', 'Ổ cứng SSD Kingston NV2 500GB M.2 NVMe PCIe 4.0', 'ssd-kingston-nv2-500gb-nvme',
 990000.00, 1290000.00, 23, 40, 'assets/images/products/pc4.jpg',
 '500GB,M.2 NVMe,PCIe 4.0,3500MB/s',
 'Ổ cứng SSD NVMe quốc dân gọn nhẹ, nâng cao tốc độ khởi động máy và tải game.',
 'Bảo hành chính hãng 36 tháng', 1, 0, NULL,
 'ssd', NULL, NULL, 5, 0, 36, 0, 0, 0),

(28, 7, 15, 'linh-kien', 'Cooler Master', 'Nguồn máy tính Cooler Master MWE 550 Bronze V2 550W 80 Plus Bronze', 'psu-cooler-master-mwe-550w-bronze',
 1190000.00, 1490000.00, 20, 25, 'assets/images/products/pc2.jpg',
 '550W,80 Plus Bronze,DC-to-DC,Quạt HDB 120mm',
 'Bộ nguồn công suất thực 550W ổn định điện áp cho cấu hình CPU tầm trung và RTX 4060.',
 'Bảo hành chính hãng 60 tháng', 1, 0, NULL,
 'psu', NULL, NULL, 0, 550, 60, 0, 0, 0),

(29, 7, 7, 'linh-kien', 'Corsair', 'Nguồn máy tính Corsair RM750e 750W 80 Plus Gold Full Modular ATX 3.0', 'psu-corsair-rm750e-750w-gold',
 2790000.00, 3290000.00, 15, 15, 'assets/images/products/pc3.jpg',
 '750W,80 Plus Gold,Full Modular,ATX 3.0',
 'Nguồn chuẩn ATX 3.0 PCIe 5.0 dây rời hoàn toàn, gánh tốt RTX 4070 Super và Core i7.',
 'Bảo hành chính hãng 84 tháng', 1, 0, NULL,
 'psu', NULL, NULL, 0, 750, 84, 0, 0, 0),

(30, 7, 15, 'linh-kien', 'Cooler Master', 'Vỏ Case Cooler Master MasterBox TD500 Mesh V2 (3 Fan ARGB / Kính cường lực)', 'case-cooler-master-td500-mesh-v2',
 2190000.00, 2590000.00, 15, 18, 'assets/images/products/pc1.jpg',
 'Mid Tower,ATX/mATX,3 Fan ARGB,Mặt lưới Mesh',
 'Case mặt lưới FineMesh tối ưu luồng gió kèm sẵn 3 quạt ARGB và hỗ trợ rad 360mm.',
 'Tặng kèm bộ ốc và dây rút đi dây gọn gàng', 1, 0, NULL,
 'case', NULL, NULL, 10, 0, 24, 0, 0, 0),

(31, 7, 15, 'linh-kien', 'Cooler Master', 'Tản nhiệt nước AIO Cooler Master MasterLiquid 240L Core ARGB', 'cooling-cooler-master-240l-core-argb',
 1690000.00, 2090000.00, 19, 20, 'assets/images/products/pc4.jpg',
 'AIO 240mm,LGA1700/AM5,ARGB,Bơm thế hệ S',
 'Tản nhiệt nước AIO 240mm bơm buồng kép êm ái, tương thích cả Intel LGA1700 và AMD AM5.',
 'Kèm sẵn keo tản nhiệt CryoFuze', 1, 0, NULL,
 'cooling', 'LGA1700,AM5', NULL, 15, 0, 24, 0, 0, 0);

-- --------------------------------------------------------
-- 5. Bảng `product_images` (Đa ảnh mỗi sản phẩm, chỉ dùng file thật)
-- --------------------------------------------------------
CREATE TABLE `product_images` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `image_url` varchar(255) NOT NULL,
  `is_primary` tinyint(1) NOT NULL DEFAULT 0,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_product_images_product_id` (`product_id`),
  CONSTRAINT `fk_product_images_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `product_images` (`product_id`, `image_url`, `is_primary`, `sort_order`) VALUES
(1, 'assets/images/products/laptop1.jpg', 1, 0),
(1, 'assets/images/products/laptop2.jpg', 0, 1),
(1, 'assets/images/products/laptop3.jpg', 0, 2),
(2, 'assets/images/products/pc1.jpg', 1, 0),
(2, 'assets/images/products/pc2.jpg', 0, 1),
(2, 'assets/images/products/pc3.jpg', 0, 2),
(3, 'assets/images/products/monitor1.jpg', 1, 0),
(3, 'assets/images/products/monitor2.jpg', 0, 1),
(4, 'assets/images/products/keyboard1.jpg', 1, 0),
(4, 'assets/images/products/mouse1.jpg', 0, 1),
(5, 'assets/images/products/mouse1.jpg', 1, 0),
(5, 'assets/images/products/keyboard1.jpg', 0, 1),
(6, 'assets/images/products/pc2.jpg', 1, 0),
(6, 'assets/images/products/pc1.jpg', 0, 1),
(7, 'assets/images/products/pc3.jpg', 1, 0),
(7, 'assets/images/products/pc4.jpg', 0, 1),
(7, 'assets/images/products/pc1.jpg', 0, 2),
(8, 'assets/images/products/pc4.jpg', 1, 0),
(8, 'assets/images/products/pc2.jpg', 0, 1),
(9, 'assets/images/products/laptop2.jpg', 1, 0),
(9, 'assets/images/products/laptop1.jpg', 0, 1),
(10, 'assets/images/products/laptop3.jpg', 1, 0),
(10, 'assets/images/products/laptop4.jpg', 0, 1),
(11, 'assets/images/products/laptop4.jpg', 1, 0),
(11, 'assets/images/products/laptop2.jpg', 0, 1),
(12, 'assets/images/products/laptop3.jpg', 1, 0),
(12, 'assets/images/products/laptop1.jpg', 0, 1),
(13, 'assets/images/products/laptop4.jpg', 1, 0),
(13, 'assets/images/products/laptop3.jpg', 0, 1),
(14, 'assets/images/products/monitor2.jpg', 1, 0),
(14, 'assets/images/products/monitor1.jpg', 0, 1),
(15, 'assets/images/products/headset1.jpg', 1, 0),
(15, 'assets/images/products/mouse1.jpg', 0, 1),
(16, 'assets/images/products/pc1.jpg', 1, 0),
(16, 'assets/images/products/pc2.jpg', 0, 1),
(17, 'assets/images/products/pc3.jpg', 1, 0),
(17, 'assets/images/products/pc1.jpg', 0, 1),
(18, 'assets/images/products/pc4.jpg', 1, 0),
(18, 'assets/images/products/pc2.jpg', 0, 1),
(19, 'assets/images/products/pc2.jpg', 1, 0),
(19, 'assets/images/products/pc1.jpg', 0, 1),
(20, 'assets/images/products/pc3.jpg', 1, 0),
(20, 'assets/images/products/pc4.jpg', 0, 1),
(21, 'assets/images/products/pc4.jpg', 1, 0),
(21, 'assets/images/products/pc2.jpg', 0, 1),
(22, 'assets/images/products/pc1.jpg', 1, 0),
(22, 'assets/images/products/pc2.jpg', 0, 1),
(23, 'assets/images/products/pc3.jpg', 1, 0),
(23, 'assets/images/products/pc4.jpg', 0, 1),
(24, 'assets/images/products/pc1.jpg', 1, 0),
(24, 'assets/images/products/pc3.jpg', 0, 1),
(25, 'assets/images/products/pc3.jpg', 1, 0),
(25, 'assets/images/products/pc4.jpg', 0, 1),
(26, 'assets/images/products/pc2.jpg', 1, 0),
(26, 'assets/images/products/pc4.jpg', 0, 1),
(27, 'assets/images/products/pc4.jpg', 1, 0),
(27, 'assets/images/products/pc2.jpg', 0, 1),
(28, 'assets/images/products/pc2.jpg', 1, 0),
(28, 'assets/images/products/pc1.jpg', 0, 1),
(29, 'assets/images/products/pc3.jpg', 1, 0),
(29, 'assets/images/products/pc2.jpg', 0, 1),
(30, 'assets/images/products/pc1.jpg', 1, 0),
(30, 'assets/images/products/pc4.jpg', 0, 1),
(31, 'assets/images/products/pc4.jpg', 1, 0),
(31, 'assets/images/products/pc3.jpg', 0, 1);

-- --------------------------------------------------------
-- 6. Bảng `news` (Tin tức & Bài đánh giá sản phẩm)
-- --------------------------------------------------------
CREATE TABLE `news` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(500) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `summary` text DEFAULT NULL,
  `content` mediumtext DEFAULT NULL,
  `category` varchar(100) DEFAULT 'Tin công nghệ',
  `author_id` int(11) DEFAULT NULL,
  `published_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_news_slug` (`slug`),
  KEY `idx_news_author_id` (`author_id`),
  CONSTRAINT `fk_news_author` FOREIGN KEY (`author_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `news` (`id`, `title`, `slug`, `image`, `summary`, `content`, `category`, `author_id`, `published_at`) VALUES
(1, 'Đánh giá RTX 4060 vs RTX 4060 Ti: Card đồ họa nào đáng mua nhất 2026?', 'danh-gia-rtx-4060-vs-rtx-4060-ti', 'assets/images/news/news1.jpg',
 'So sánh hiệu năng thực tế trên các tựa game AAA ở độ phân giải Full HD và 2K, mức tiêu thụ điện năng và giá thành.',
 'RTX 4060 và RTX 4060 Ti cùng sử dụng kiến trúc Ada Lovelace với công nghệ DLSS 3 và Frame Generation. Ở độ phân giải Full HD 1080p, RTX 4060 đạt trung bình trên 90 FPS ở thiết lập High trong Cyberpunk 2077 và Hogwarts Legacy với mức TDP chỉ 115W. Trong khi đó, RTX 4060 Ti mạnh hơn khoảng 20-25%, phù hợp với màn hình 2K 144Hz-180Hz. Nếu bạn build PC trong tầm giá 18 triệu, RTX 4060 là lựa chọn tối ưu P/P.',
 'Đánh giá VGA', 1, '2026-11-15 09:00:00'),
(2, 'Top 5 Laptop Gaming dưới 20 triệu đáng mua nhất mùa tựu trường', 'top-5-laptop-gaming-duoi-20-trieu', 'assets/images/news/news2.jpg',
 'Tổng hợp các mẫu laptop trang bị RTX 3050, màn hình 144Hz chuẩn gaming từ Acer, ASUS, Lenovo.',
 'Ở phân khúc dưới 20 triệu đồng, game thủ và sinh viên kỹ thuật có rất nhiều lựa chọn sáng giá như Acer Nitro V 15, ASUS TUF Gaming F15 và Lenovo LOQ 15. Điểm chung của các mẫu máy này là CPU Intel Core i5 dòng H/HX kết hợp GPU RTX 2050 hoặc RTX 3050, RAM 16GB có thể nâng cấp và màn hình 144Hz giúp vừa chiến game mượt mà vừa học lập trình, thiết kế đồ họa.',
 'Tư vấn Laptop', 1, '2026-11-14 10:30:00'),
(3, 'Hướng dẫn chọn bàn phím cơ cho người mới: Linear, Tactile hay Clicky?', 'huong-dan-chon-ban-phim-co', 'assets/images/news/news3.jpg',
 'Tìm hiểu sự khác biệt giữa các loại switch phổ biến và cách chọn layout phù hợp nhu cầu.',
 'Switch Linear mang lại cảm giác bấm mượt mà từ đầu đến cuối hành trình, ít ồn nên rất được game thủ FPS ưa chuộng. Tactile tạo một khấc phản hồi nhẹ giúp gõ văn bản chính xác hơn, còn Clicky phát ra tiếng tách đặc trưng vui tai nhưng không phù hợp môi trường văn phòng yên tĩnh. Với người mới, layout 75% hoặc 98% hỗ trợ Hotswap và kết nối 3 chế độ là điểm khởi đầu lý tưởng.',
 'Đánh giá Gear', 1, '2026-11-12 14:15:00'),
(4, 'Đoàn 6 khai trương Showroom mới tại TP.HCM với hàng ngàn deal sốc', 'doan-6-khai-truong-showroom-moi', 'assets/images/news/news4.jpg',
 'Cơ hội săn gear giá 1K, giảm giá lên đến 50% cho các sản phẩm màn hình và linh kiện PC.',
 'Showroom mới của ĐOÀN 6 chính thức đi vào hoạt động với khu trải nghiệm PC Custom tản nhiệt nước, góc thử nghiệm bàn phím cơ và màn hình tần số quét cao 180Hz-240Hz. Khách hàng đến tham quan và mua sắm được miễn phí vệ sinh laptop, lắp ráp PC theo yêu cầu và nhận bảo hành điện tử tra cứu trực tuyến.',
 'Tin cửa hàng', 1, '2026-11-10 08:00:00');

-- --------------------------------------------------------
-- 7. Bảng `orders` (chỉ hỗ trợ COD & VietQR; online / walk_in / pc_build)
-- --------------------------------------------------------
CREATE TABLE `orders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_code` varchar(30) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `created_by_staff_id` int(11) DEFAULT NULL,
  `order_type` enum('online','walk_in','pc_build') NOT NULL DEFAULT 'online',
  `customer_name` varchar(255) NOT NULL,
  `customer_phone` varchar(30) NOT NULL,
  `customer_email` varchar(255) DEFAULT NULL,
  `customer_address` varchar(500) NOT NULL,
  `note` text DEFAULT NULL,
  `payment_method` enum('COD','VietQR') NOT NULL DEFAULT 'COD',
  `payment_status` enum('unpaid','pending_confirm','paid','expired') NOT NULL DEFAULT 'unpaid',
  `payment_expires_at` datetime DEFAULT NULL,
  `total_amount` decimal(14,2) NOT NULL DEFAULT 0.00,
  `status` enum('Chờ xác nhận','Đang lắp ráp','Đang giao hàng','Đang giao','Hoàn thành','Đã hủy') NOT NULL DEFAULT 'Chờ xác nhận',
  `pc_build_specs` text DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_orders_order_code` (`order_code`),
  KEY `idx_orders_user_id` (`user_id`),
  KEY `idx_orders_staff_id` (`created_by_staff_id`),
  CONSTRAINT `fk_orders_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_orders_staff` FOREIGN KEY (`created_by_staff_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `orders` (
  `id`, `order_code`, `user_id`, `created_by_staff_id`, `order_type`,
  `customer_name`, `customer_phone`, `customer_email`, `customer_address`, `note`,
  `payment_method`, `payment_status`, `payment_expires_at`, `total_amount`, `status`, `pc_build_specs`, `created_at`
) VALUES
(1, 'DH00001', 3, NULL, 'online',
 'Nguyễn Văn Khách', '0909123456', 'khachhang@doan6.local', '273 An Dương Vương, Quận 5, TP. Hồ Chí Minh', 'Giao giờ hành chính',
 'COD', 'unpaid', NULL, 18380000.00, 'Chờ xác nhận', NULL, DATE_SUB(NOW(), INTERVAL 2 DAY)),

(2, 'DH00002', 3, NULL, 'online',
 'Nguyễn Văn Khách', '0909123456', 'khachhang@doan6.local', '273 An Dương Vương, Quận 5, TP. Hồ Chí Minh', 'Đã quét mã VietQR đặt cọc',
 'VietQR', 'paid', DATE_ADD(NOW(), INTERVAL 15 MINUTE), 17990000.00, 'Hoàn thành', NULL, DATE_SUB(NOW(), INTERVAL 1 DAY)),

(3, 'DH00003', 3, 2, 'pc_build',
 'Nguyễn Văn Khách', '0909123456', 'khachhang@doan6.local', '273 An Dương Vương, Quận 5, TP. Hồ Chí Minh', 'Khách build bộ PC LGA1700 lắp ráp sẵn',
 'VietQR', 'paid', NULL, 19230000.00, 'Đang lắp ráp',
 '{"cpu":"Intel Core i5-12400F","mainboard":"ASUS PRIME B760M-A D4","ram":"Kingston Fury Beast 16GB DDR4","vga":"ASUS Dual RTX 4060 OC 8GB","ssd":"Kingston NV2 500GB","psu":"Cooler Master MWE 550W","case":"Cooler Master TD500 Mesh V2","estimated_watt":258}',
 NOW());

-- --------------------------------------------------------
-- 8. Bảng `order_items`
-- --------------------------------------------------------
CREATE TABLE `order_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `product_name` varchar(500) NOT NULL,
  `product_price` decimal(12,2) NOT NULL DEFAULT 0.00,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `component_slot` varchar(30) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_order_items_order_id` (`order_id`),
  KEY `idx_order_items_product_id` (`product_id`),
  CONSTRAINT `fk_order_items_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_order_items_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `product_name`, `product_price`, `quantity`, `component_slot`) VALUES
(1, 1, 1, 'Laptop Gaming Acer Nitro V 15 ANV15-51-58AN (i5-13420H / RTX 2050 / 16GB / 512GB)', 16990000.00, 1, NULL),
(2, 1, 4, 'Bàn phím cơ Akko 3098B Multi-modes Prunus Lannesiana Hotswap RGB', 1390000.00, 1, NULL),
(3, 2, 2, 'PC ĐOÀN 6 Intel i5-12400F / VGA RTX 4060 8GB / RAM 16GB / SSD 500GB', 17990000.00, 1, NULL),
(4, 3, 16, 'CPU Intel Core i5-12400F (6 Nhân 12 Luồng / LGA1700 / Up to 4.4GHz)', 2990000.00, 1, 'cpu'),
(5, 3, 19, 'Mainboard ASUS PRIME B760M-A D4 (LGA1700 / mATX / DDR4)', 2790000.00, 1, 'mainboard'),
(6, 3, 22, 'RAM Kingston Fury Beast 16GB (2x8GB) DDR4 3200MHz CL16', 1090000.00, 1, 'ram'),
(7, 3, 24, 'Card màn hình ASUS Dual GeForce RTX 4060 OC Edition 8GB GDDR6', 7990000.00, 1, 'vga'),
(8, 3, 27, 'Ổ cứng SSD Kingston NV2 500GB M.2 NVMe PCIe 4.0', 990000.00, 1, 'ssd'),
(9, 3, 28, 'Nguồn máy tính Cooler Master MWE 550 Bronze V2 550W 80 Plus Bronze', 1190000.00, 1, 'psu'),
(10, 3, 30, 'Vỏ Case Cooler Master MasterBox TD500 Mesh V2 (3 Fan ARGB / Kính cường lực)', 2190000.00, 1, 'case');

-- --------------------------------------------------------
-- 9. Bảng `warranties` (Bảo hành điện tử sinh tự động theo đơn hàng)
-- --------------------------------------------------------
CREATE TABLE `warranties` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `warranty_code` varchar(40) NOT NULL,
  `order_id` int(11) NOT NULL,
  `order_item_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `customer_phone` varchar(30) NOT NULL,
  `product_name` varchar(500) NOT NULL,
  `serial_number` varchar(80) DEFAULT NULL,
  `warranty_months` int(11) NOT NULL DEFAULT 24,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `status` enum('Còn hạn','Đang bảo hành','Hết hạn','Đã hủy') NOT NULL DEFAULT 'Còn hạn',
  `notes` text DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_warranties_code` (`warranty_code`),
  KEY `idx_warranties_order_id` (`order_id`),
  KEY `idx_warranties_phone` (`customer_phone`),
  KEY `idx_warranties_user_id` (`user_id`),
  CONSTRAINT `fk_warranties_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_warranties_order_item` FOREIGN KEY (`order_item_id`) REFERENCES `order_items` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_warranties_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_warranties_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `warranties` (`warranty_code`, `order_id`, `order_item_id`, `product_id`, `user_id`, `customer_phone`, `product_name`, `serial_number`, `warranty_months`, `start_date`, `end_date`, `status`, `notes`) VALUES
('BH00001-1-1', 1, 1, 1, 3, '0909123456', 'Laptop Gaming Acer Nitro V 15 ANV15-51-58AN (i5-13420H / RTX 2050 / 16GB / 512GB)', 'SN-D6-0001-01', 24, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 24 MONTH), 'Còn hạn', 'Kích hoạt bảo hành chính hãng 24 tháng'),
('BH00001-2-1', 1, 2, 4, 3, '0909123456', 'Bàn phím cơ Akko 3098B Multi-modes Prunus Lannesiana Hotswap RGB', 'SN-D6-0001-02', 12, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 12 MONTH), 'Còn hạn', 'Kích hoạt bảo hành 12 tháng'),
('BH00002-3-1', 2, 3, 2, 3, '0909123456', 'PC ĐOÀN 6 Intel i5-12400F / VGA RTX 4060 8GB / RAM 16GB / SSD 500GB', 'SN-D6-0002-01', 36, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 36 MONTH), 'Còn hạn', 'Bảo hành PC lắp ráp 36 tháng'),
('BH00003-4-1', 3, 4, 16, 3, '0909123456', 'CPU Intel Core i5-12400F (6 Nhân 12 Luồng / LGA1700 / Up to 4.4GHz)', 'SN-D6-0003-01', 36, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 36 MONTH), 'Còn hạn', 'Bảo hành linh kiện Build PC'),
('BH00003-7-1', 3, 7, 24, 3, '0909123456', 'Card màn hình ASUS Dual GeForce RTX 4060 OC Edition 8GB GDDR6', 'SN-D6-0003-04', 36, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 36 MONTH), 'Còn hạn', 'Bảo hành VGA 36 tháng');

-- --------------------------------------------------------
-- 10. Bảng `trade_in_requests` (Thu cũ đổi mới)
-- --------------------------------------------------------
CREATE TABLE `trade_in_requests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `request_code` varchar(30) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `customer_name` varchar(255) NOT NULL,
  `customer_phone` varchar(30) NOT NULL,
  `device_name` varchar(255) NOT NULL,
  `device_condition` varchar(255) NOT NULL,
  `estimated_value` decimal(12,2) NOT NULL DEFAULT 0.00,
  `target_product_id` int(11) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `status` enum('Chờ định giá','Đã báo giá','Hoàn tất','Từ chối') NOT NULL DEFAULT 'Chờ định giá',
  `admin_note` text DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_trade_in_code` (`request_code`),
  KEY `idx_trade_in_user_id` (`user_id`),
  KEY `idx_trade_in_target_product` (`target_product_id`),
  CONSTRAINT `fk_trade_in_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_trade_in_target_product` FOREIGN KEY (`target_product_id`) REFERENCES `products` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `trade_in_requests` (`request_code`, `user_id`, `customer_name`, `customer_phone`, `device_name`, `device_condition`, `estimated_value`, `target_product_id`, `notes`, `status`, `admin_note`) VALUES
('TC00001', 3, 'Nguyễn Văn Khách', '0909123456', 'Card đồ họa GTX 1660 Super 6GB', 'Ngoại hình 95%, còn hộp, hoạt động ổn định', 2600000.00, 24, 'Muốn lên đời ASUS Dual RTX 4060 OC', 'Đã báo giá', 'Kỹ thuật viên đã kiểm tra sơ bộ, hỗ trợ thu 2.600.000đ'),
('TC00002', NULL, 'Lê Minh Tuấn', '0912345678', 'Laptop ASUS VivoBook X515 i5 Gen 11', 'Pin còn 82%, màn hình không điểm chết', 5500000.00, 1, 'Đổi sang Acer Nitro V 15 chơi game', 'Chờ định giá', NULL);

COMMIT;
SET FOREIGN_KEY_CHECKS = 1;
