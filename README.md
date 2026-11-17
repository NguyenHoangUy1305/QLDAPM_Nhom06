# ĐỒ ÁN QUẢN LÝ DỰ ÁN PHẦN MỀM - NHÓM 06
## WEBSITE BÁN HÀNG CÔNG NGHỆ, MÁY TÍNH & GEAR (PHP THUẦN)

Dự án website thương mại điện tử chuyên cung cấp máy tính cao cấp, laptop gaming, linh kiện và phụ kiện chính hãng, được xây dựng hoàn toàn bằng **PHP thuần** (không dùng framework).

---

### 1. Cấu trúc thư mục dự án

```text
QLDAPM_Nhom06/
├── assets/                  # Tài nguyên tĩnh (hình ảnh sản phẩm, banner, tin tức)
│   └── images/
├── config/                  # Cấu hình hệ thống & cơ sở dữ liệu
│   └── db.php               # Kết nối MySQL (PDO)
├── data/                    # Nguồn dữ liệu & logic xử lý
│   └── store.php            # Mock data, hàm lấy sản phẩm, quản lý giỏ hàng
├── docs/                    # Tài liệu quản lý dự án của nhóm (Word/PDF)
├── includes/                # Các thành phần giao diện dùng chung
│   ├── header.php           # Đầu trang, thanh tìm kiếm, menu danh mục
│   ├── footer.php           # Chân trang, thông tin liên hệ, chính sách
│   └── product-card.php     # Component hiển thị thẻ sản phẩm
├── cart.php                 # Trang giỏ hàng và đặt hàng
├── collection.php           # Trang danh mục sản phẩm & tìm kiếm
├── index.php                # Trang chủ website
├── product.php              # Trang chi tiết sản phẩm
├── start-server.bat         # File chạy nhanh web server cục bộ
└── README.md                # Hướng dẫn dự án
```

---

### 2. Công nghệ sử dụng
- **Backend**: PHP 8.x thuần (Native PHP)
- **Database**: MySQL / MariaDB (kết nối qua PDO trong `config/db.php`)
- **Frontend**: HTML5, Tailwind CSS (qua CDN), Font Awesome 6, JavaScript ES6

---

### 3. Hướng dẫn chạy dự án

#### Cách 1: Chạy bằng file `start-server.bat` (Khuyên dùng)
- Nhấp đúp chuột vào file `start-server.bat` trong thư mục dự án.
- Mở trình duyệt web và truy cập địa chỉ: **[http://127.0.0.1:8000](http://127.0.0.1:8000)**

#### Cách 2: Chạy bằng lệnh dòng lệnh (Terminal / PowerShell)
Mở terminal tại thư mục dự án và chạy:
```bash
php -S 127.0.0.1:8000
```
Truy cập: **[http://127.0.0.1:8000](http://127.0.0.1:8000)**

#### Cách 3: Chạy với XAMPP / WampServer
- Đặt thư mục dự án vào `C:/xampp/htdocs/QLDAPM_Nhom06/`
- Khởi động Apache trong XAMPP Control Panel
- Truy cập: **[http://localhost/QLDAPM_Nhom06/](http://localhost/QLDAPM_Nhom06/)**
