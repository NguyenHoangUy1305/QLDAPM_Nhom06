"""MySQL 3306 preflight, isolated DB probing/snapshot-restore, and deterministic fixture seeding."""
from __future__ import annotations

import os
import re
import shutil
import socket
import subprocess
import tempfile
from pathlib import Path

import requests

from config import (
    ADMIN_EMAIL,
    ADMIN_PASS,
    CUSTOMER_EMAIL,
    CUSTOMER_PASS,
    DB_HOST,
    DB_PASS,
    DB_PORT,
    DB_USER,
    DEFAULT_DB_NAME,
    ISOLATED_DB_NAME,
    MYSQL_BIN,
    MYSQL_CONNECT_TIMEOUT,
    MYSQLDUMP_BIN,
    PHP_BIN,
    REPO_ROOT,
    STAFF_EMAIL,
    STAFF_PASS,
)
from lib.php_server import PhpServerManager


def check_mysql_port(
    host: str = DB_HOST,
    port: int = DB_PORT,
    timeout: float = MYSQL_CONNECT_TIMEOUT,
) -> None:
    """Fail fast if TCP 127.0.0.1:3306 is not accepting connections."""
    try:
        with socket.create_connection((host, port), timeout=timeout):
            return
    except OSError as exc:
        raise RuntimeError(
            f"MySQL server is NOT listening on {host}:{port} ({exc}). "
            "Start XAMPP MySQL before running E2E tests."
        ) from exc


def _sql_str(val: str) -> str:
    escaped = (
        val.replace("\\", "\\\\")
        .replace("'", "\\'")
        .replace("\0", "\\0")
        .replace("\n", "\\n")
        .replace("\r", "\\r")
    )
    return f"'{escaped}'"


def _php_bcrypt(password: str, _cache: dict[str, str] = {}) -> str:
    if password in _cache:
        return _cache[password]
    proc = subprocess.run(
        [PHP_BIN, "-r", "echo password_hash($argv[1], PASSWORD_BCRYPT);", password],
        capture_output=True,
        text=True,
        encoding="utf-8",
        check=True,
    )
    hashed = proc.stdout.strip()
    if not hashed.startswith("$2"):
        raise RuntimeError(f"Unexpected PHP password_hash output: {hashed!r}")
    _cache[password] = hashed
    return hashed


class DbManager:
    """Opaque-box MySQL fixture manager using mysql.exe / mysqldump.exe."""

    def __init__(
        self,
        host: str = DB_HOST,
        port: int = DB_PORT,
        user: str = DB_USER,
        password: str = DB_PASS,
        db_name: str = DEFAULT_DB_NAME,
        repo_root: Path = REPO_ROOT,
    ) -> None:
        self.host = host
        self.port = port
        self.user = user
        self.password = password
        self.db_name = db_name
        self.repo_root = Path(repo_root)
        self._snapshot_dir: Path | None = None
        self._snapshot_file: Path | None = None
        self._db_existed_before: bool = False
        self.env_override_supported: bool = False

    def _mysql_base_cmd(self, db_name: str | None = None) -> list[str]:
        cmd = [
            MYSQL_BIN,
            "-h",
            self.host,
            "-P",
            str(self.port),
            "-u",
            self.user,
            "--default-character-set=utf8mb4",
        ]
        if self.password:
            cmd.append(f"-p{self.password}")
        if db_name:
            cmd.append(db_name)
        return cmd

    def preflight(self) -> None:
        check_mysql_port(self.host, self.port)
        res = self.run_sql("SELECT 1 AS ok;", db_name=None)
        if "1" not in res:
            raise RuntimeError(f"Unexpected mysql.exe preflight output: {res!r}")

    def run_sql(self, sql: str, db_name: str | None = "__default__") -> str:
        target_db = self.db_name if db_name == "__default__" else db_name
        cmd = self._mysql_base_cmd(target_db)
        proc = subprocess.run(
            cmd,
            input=sql,
            capture_output=True,
            text=True,
            encoding="utf-8",
            errors="replace",
        )
        if proc.returncode != 0:
            raise RuntimeError(
                f"mysql.exe failed (rc={proc.returncode}) on DB={target_db!r}:\n"
                f"STDERR: {proc.stderr.strip()}\nSQL snippet: {sql[:300]}"
            )
        return proc.stdout

    def query_tsv(self, sql: str, db_name: str | None = "__default__") -> list[dict[str, str]]:
        target_db = self.db_name if db_name == "__default__" else db_name
        cmd = self._mysql_base_cmd(target_db) + ["--batch", "--raw"]
        proc = subprocess.run(
            cmd,
            input=sql,
            capture_output=True,
            text=True,
            encoding="utf-8",
            errors="replace",
        )
        if proc.returncode != 0:
            raise RuntimeError(
                f"mysql.exe query failed (rc={proc.returncode}): {proc.stderr.strip()}"
            )
        lines = [ln for ln in proc.stdout.splitlines() if ln.strip() != ""]
        if len(lines) < 2:
            return []
        headers = lines[0].split("\t")
        rows: list[dict[str, str]] = []
        for line in lines[1:]:
            cols = line.split("\t")
            rows.append({headers[i]: (cols[i] if i < len(cols) else "") for i in range(len(headers))})
        return rows

    def database_exists(self, name: str) -> bool:
        rows = self.query_tsv(f"SHOW DATABASES LIKE {_sql_str(name)};", db_name=None)
        return len(rows) > 0

    def _rewritten_schema_sql(self, target_db: str) -> str:
        schema_path = self.repo_root / "database.sql"
        if not schema_path.exists():
            raise FileNotFoundError(f"Missing schema file: {schema_path}")
        raw = schema_path.read_text(encoding="utf-8", errors="replace")
        # Rewrite hardcoded CREATE DATABASE / USE statements so schema loads into target_db
        rewritten = re.sub(
            r"CREATE\s+DATABASE\s+IF\s+NOT\s+EXISTS\s+`?[\w-]+`?[^;]*;",
            f"CREATE DATABASE IF NOT EXISTS `{target_db}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;",
            raw,
            flags=re.IGNORECASE,
        )
        rewritten = re.sub(
            r"\bUSE\s+`?[\w-]+`?\s*;",
            f"USE `{target_db}`;",
            rewritten,
            flags=re.IGNORECASE,
        )
        prefix = (
            f"CREATE DATABASE IF NOT EXISTS `{target_db}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;\n"
            f"USE `{target_db}`;\n"
        )
        return prefix + rewritten

    def load_schema(self, target_db: str | None = None) -> None:
        db = target_db or self.db_name
        sql = self._rewritten_schema_sql(db)
        self.run_sql(sql, db_name=None)

    def probe_env_db_support(self) -> bool:
        """Opaque-box probe: verify if `DB_NAME` env var switches the PHP app DB."""
        probe_db = ISOLATED_DB_NAME
        self.run_sql(f"DROP DATABASE IF EXISTS `{probe_db}`;", db_name=None)
        self.load_schema(probe_db)

        marker = "E2E_ENV_PROBE_RTX_MARKER"
        probe_slug = "e2e-env-probe-rtx-marker"
        try:
            self.run_sql(
                f"""
                INSERT INTO categories (name, slug, icon, description)
                VALUES ('VGA', 'vga', 'fa-tv', 'Probe')
                ON DUPLICATE KEY UPDATE name=VALUES(name);
                INSERT INTO products (category_id, category_slug, brand, name, slug, price, stock, image, specs, description)
                SELECT c.id, 'vga', 'ASUS', {_sql_str(marker)}, {_sql_str(probe_slug)}, 1000000, 5,
                       'assets/images/products/laptop1.jpg', 'Probe', 'Probe'
                FROM categories c WHERE c.slug='vga' LIMIT 1
                ON DUPLICATE KEY UPDATE name=VALUES(name), stock=VALUES(stock), category_slug=VALUES(category_slug);
                """,
                db_name=probe_db,
            )
        except Exception:
            self.run_sql(f"DROP DATABASE IF EXISTS `{probe_db}`;", db_name=None)
            self.env_override_supported = False
            return False

        with PhpServerManager(extra_env={"DB_NAME": probe_db}) as srv:
            found = False
            for path in (f"/api/search.php?q={marker}", f"/collection.php?q={marker}"):
                try:
                    resp = requests.get(f"{srv.base_url}{path}", timeout=3.0)
                    if resp.status_code == 200 and marker in resp.text:
                        found = True
                        break
                except Exception:
                    pass

        if found:
            self.env_override_supported = True
            self.db_name = probe_db
            return True

        self.run_sql(f"DROP DATABASE IF EXISTS `{probe_db}`;", db_name=None)
        self.env_override_supported = False
        return False

    def take_snapshot(self) -> None:
        """Snapshot an existing `database` via mysqldump before running E2E against it."""
        self._db_existed_before = self.database_exists(self.db_name)
        if not self._db_existed_before:
            return
        self._snapshot_dir = Path(tempfile.mkdtemp(prefix="qldapm_e2e_db_"))
        self._snapshot_file = self._snapshot_dir / f"{self.db_name}_backup.sql"
        cmd = [
            MYSQLDUMP_BIN,
            "-h",
            self.host,
            "-P",
            str(self.port),
            "-u",
            self.user,
            "--default-character-set=utf8mb4",
            "--single-transaction",
            "--routines",
            "--triggers",
            self.db_name,
        ]
        if self.password:
            cmd.append(f"-p{self.password}")
        with open(self._snapshot_file, "w", encoding="utf-8") as fh:
            proc = subprocess.run(cmd, stdout=fh, stderr=subprocess.PIPE, text=True, encoding="utf-8")
        if proc.returncode != 0:
            raise RuntimeError(f"mysqldump failed (rc={proc.returncode}): {proc.stderr.strip()}")

    def restore_snapshot(self) -> None:
        """Restore `database` from mysqldump snapshot and clean temp files."""
        try:
            if self._db_existed_before and self._snapshot_file and self._snapshot_file.exists():
                self.run_sql(
                    f"DROP DATABASE IF EXISTS `{self.db_name}`; "
                    f"CREATE DATABASE `{self.db_name}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;",
                    db_name=None,
                )
                sql = self._snapshot_file.read_text(encoding="utf-8", errors="replace")
                self.run_sql(sql, db_name=self.db_name)
            elif self.env_override_supported and self.db_name == ISOLATED_DB_NAME:
                self.run_sql(f"DROP DATABASE IF EXISTS `{ISOLATED_DB_NAME}`;", db_name=None)
        finally:
            if self._snapshot_dir and self._snapshot_dir.exists():
                shutil.rmtree(self._snapshot_dir, ignore_errors=True)
            self._snapshot_dir = None
            self._snapshot_file = None

    def reset_and_seed_baseline(self) -> None:
        """Drop & recreate target DB from database.sql + seed deterministic E2E fixtures."""
        self.run_sql(f"DROP DATABASE IF EXISTS `{self.db_name}`;", db_name=None)
        self.load_schema(self.db_name)

        admin_hash = _php_bcrypt(ADMIN_PASS)
        staff_hash = _php_bcrypt(STAFF_PASS)
        cust_hash = _php_bcrypt(CUSTOMER_PASS)

        seed_sql = f"""
        -- 1. Seeded 3-Role Users
        INSERT INTO users (name, email, password, phone, address, role, status)
        VALUES
            ('Quản Trị Viên', {_sql_str(ADMIN_EMAIL)}, {_sql_str(admin_hash)}, '0900000001', 'Long Xuyên, An Giang', 'admin', 'active'),
            ('Nhân Viên Bán Hàng', {_sql_str(STAFF_EMAIL)}, {_sql_str(staff_hash)}, '0900000002', 'Long Xuyên, An Giang', 'staff', 'active'),
            ('Khách Hàng Mẫu', {_sql_str(CUSTOMER_EMAIL)}, {_sql_str(cust_hash)}, '0912345678', 'Châu Đốc, An Giang', 'customer', 'active')
        ON DUPLICATE KEY UPDATE
            password=VALUES(password), role=VALUES(role), status='active';

        -- 2. Seeded Brands
        INSERT INTO brands (name, slug, country, description)
        VALUES
            ('ASUS', 'asus', 'Taiwan', 'Linh kiện & Laptop ASUS'),
            ('MSI', 'msi', 'Taiwan', 'Card đồ họa & Bo mạch chủ MSI'),
            ('Gigabyte', 'gigabyte', 'Taiwan', 'Phần cứng Gigabyte'),
            ('Corsair', 'corsair', 'USA', 'RAM, Nguồn & Tản nhiệt Corsair'),
            ('Intel', 'intel', 'USA', 'Vi xử lý Intel Core'),
            ('AMD', 'amd', 'USA', 'Vi xử lý AMD Ryzen'),
            ('Samsung', 'samsung', 'Korea', 'Ổ cứng SSD NVMe Samsung'),
            ('Kingston', 'kingston', 'USA', 'RAM & SSD Kingston')
        ON DUPLICATE KEY UPDATE name=VALUES(name);

        -- 3. Seeded Categories
        INSERT INTO categories (name, slug, icon, description)
        VALUES
            ('Card màn hình', 'vga', 'fa-display', 'Card đồ họa NVIDIA & AMD'),
            ('CPU - Vi xử lý', 'cpu', 'fa-microchip', 'CPU Intel & AMD'),
            ('Bo mạch chủ', 'mainboard', 'fa-server', 'Mainboard Intel & AMD'),
            ('RAM - Bộ nhớ trong', 'ram', 'fa-memory', 'RAM DDR4 & DDR5'),
            ('Ổ cứng SSD', 'ssd', 'fa-hard-drive', 'SSD NVMe Gen4'),
            ('Nguồn máy tính', 'psu', 'fa-bolt', 'PSU công suất thực'),
            ('Laptop Gaming', 'laptop', 'fa-laptop', 'Laptop hiệu năng cao'),
            ('Gaming Gear', 'gaming-gear', 'fa-gamepad', 'Chuột, bàn phím, tai nghe')
        ON DUPLICATE KEY UPDATE name=VALUES(name);

        -- 4. Deterministic E2E Products (Catalog, Live Search, PC Builder)
        INSERT INTO products (
            category_id, category_slug, brand_id, brand, name, slug, price, old_price, stock, image,
            specs, description, component_type, socket, ram_type, tdp_watt, psu_watt,
            warranty_months, is_featured, is_flash_sale
        ) VALUES
        (
            (SELECT id FROM categories WHERE slug='vga' LIMIT 1), 'vga',
            (SELECT id FROM brands WHERE slug='asus' LIMIT 1),
            'ASUS', 'VGA ASUS Dual RTX 4060 8GB E2E', 'e2e-asus-rtx-4060',
            7890000, 8990000, 20, 'assets/images/products/pc1.jpg',
            '8GB GDDR6 | Boost 2535MHz | PCIe 4.0', 'Card đồ họa RTX 4060 hiệu năng cao',
            'vga', NULL, NULL, 115, 0, 24, 1, 1
        ),
        (
            (SELECT id FROM categories WHERE slug='vga' LIMIT 1), 'vga',
            (SELECT id FROM brands WHERE slug='msi' LIMIT 1),
            'MSI', 'VGA MSI RTX 4070 SUPER Ventus 12GB E2E', 'e2e-msi-rtx-4070-super',
            17990000, 19500000, 8, 'assets/images/products/pc2.jpg',
            '12GB GDDR6X | Boost 2505MHz', 'Card đồ họa RTX 4070 SUPER',
            'vga', NULL, NULL, 220, 0, 36, 1, 0
        ),
        (
            (SELECT id FROM categories WHERE slug='vga' LIMIT 1), 'vga',
            (SELECT id FROM brands WHERE slug='asus' LIMIT 1),
            'ASUS', 'VGA ASUS ROG RTX 4090 24GB OutOfStock E2E', 'e2e-asus-rtx-4090-oos',
            49990000, 52990000, 0, 'assets/images/products/pc3.jpg',
            '24GB GDDR6X | Flagship', 'Sản phẩm hết hàng để kiểm thử tồn kho',
            'vga', NULL, NULL, 450, 0, 36, 0, 0
        ),
        (
            (SELECT id FROM categories WHERE slug='cpu' LIMIT 1), 'cpu',
            (SELECT id FROM brands WHERE slug='intel' LIMIT 1),
            'Intel', 'CPU Intel Core i5-13400F LGA1700 E2E', 'e2e-intel-i5-13400f',
            4890000, 5490000, 10, 'assets/images/products/pc4.jpg',
            '10 Nhân 16 Luồng | Socket LGA1700 | Turbo 4.6GHz', 'CPU tầm trung LGA1700',
            'cpu', 'LGA1700', NULL, 148, 0, 36, 1, 0
        ),
        (
            (SELECT id FROM categories WHERE slug='cpu' LIMIT 1), 'cpu',
            (SELECT id FROM brands WHERE slug='intel' LIMIT 1),
            'Intel', 'CPU Intel Core i7-14700K LGA1700 E2E', 'e2e-intel-i7-14700k',
            10490000, 11290000, 10, 'assets/images/products/pc1.jpg',
            '20 Nhân 28 Luồng | Socket LGA1700 | Max 253W', 'CPU cao cấp TDP cao',
            'cpu', 'LGA1700', NULL, 253, 0, 36, 1, 0
        ),
        (
            (SELECT id FROM categories WHERE slug='mainboard' LIMIT 1), 'mainboard',
            (SELECT id FROM brands WHERE slug='asus' LIMIT 1),
            'ASUS', 'Mainboard ASUS PRIME B760M-K D4 LGA1700 E2E', 'e2e-asus-b760m-d4',
            2690000, 2990000, 10, 'assets/images/products/pc2.jpg',
            'Socket LGA1700 | 2x DDR4 | M.2 NVMe', 'Mainboard LGA1700 hỗ trợ DDR4',
            'mainboard', 'LGA1700', 'DDR4', 35, 0, 36, 0, 0
        ),
        (
            (SELECT id FROM categories WHERE slug='mainboard' LIMIT 1), 'mainboard',
            (SELECT id FROM brands WHERE slug='msi' LIMIT 1),
            'MSI', 'Mainboard MSI PRO B650M-P AM5 DDR5 E2E', 'e2e-msi-b650m-am5',
            3490000, 3890000, 10, 'assets/images/products/pc3.jpg',
            'Socket AM5 | 4x DDR5 | PCIe 4.0', 'Mainboard AM5 hỗ trợ DDR5',
            'mainboard', 'AM5', 'DDR5', 35, 0, 36, 0, 0
        ),
        (
            (SELECT id FROM categories WHERE slug='ram' LIMIT 1), 'ram',
            (SELECT id FROM brands WHERE slug='corsair' LIMIT 1),
            'Corsair', 'RAM Corsair Vengeance LPX 16GB DDR4 E2E', 'e2e-corsair-16gb-ddr4',
            1090000, 1290000, 10, 'assets/images/products/pc4.jpg',
            '16GB (2x8GB) DDR4 3200MHz', 'RAM DDR4 tương thích main D4',
            'ram', NULL, 'DDR4', 10, 0, 36, 0, 0
        ),
        (
            (SELECT id FROM categories WHERE slug='ram' LIMIT 1), 'ram',
            (SELECT id FROM brands WHERE slug='kingston' LIMIT 1),
            'Kingston', 'RAM Kingston Fury Beast 32GB DDR5 E2E', 'e2e-kingston-32gb-ddr5',
            2890000, 3190000, 10, 'assets/images/products/monitor1.jpg',
            '32GB (2x16GB) DDR5 5600MHz', 'RAM DDR5 tương thích main D5',
            'ram', NULL, 'DDR5', 12, 0, 36, 0, 0
        ),
        (
            (SELECT id FROM categories WHERE slug='ssd' LIMIT 1), 'ssd',
            (SELECT id FROM brands WHERE slug='samsung' LIMIT 1),
            'Samsung', 'SSD Samsung 980 PRO 1TB NVMe E2E', 'e2e-samsung-980-pro-1tb',
            2490000, 2790000, 15, 'assets/images/products/monitor2.jpg',
            '1TB M.2 NVMe PCIe 4.0 | 7000MB/s', 'Ổ cứng SSD tốc độ cao',
            'ssd', NULL, NULL, 5, 0, 36, 0, 0
        ),
        (
            (SELECT id FROM categories WHERE slug='psu' LIMIT 1), 'psu',
            (SELECT id FROM brands WHERE slug='corsair' LIMIT 1),
            'Corsair', 'Nguồn Corsair CV550 550W 80 Plus Bronze E2E', 'e2e-corsair-cv550',
            1290000, 1490000, 10, 'assets/images/products/keyboard1.jpg',
            '550W | 80 Plus Bronze', 'Nguồn 550W cho cấu hình tầm trung',
            'psu', NULL, NULL, 0, 550, 36, 0, 0
        ),
        (
            (SELECT id FROM categories WHERE slug='psu' LIMIT 1), 'psu',
            (SELECT id FROM brands WHERE slug='corsair' LIMIT 1),
            'Corsair', 'Nguồn Corsair RM750e 750W 80 Plus Gold E2E', 'e2e-corsair-rm750e',
            2890000, 3190000, 10, 'assets/images/products/mouse1.jpg',
            '750W | 80 Plus Gold | Full Modular', 'Nguồn 750W công suất lớn',
            'psu', NULL, NULL, 0, 750, 60, 0, 0
        )
        ON DUPLICATE KEY UPDATE
            category_slug=VALUES(category_slug), image=VALUES(image),
            stock=VALUES(stock), price=VALUES(price), component_type=VALUES(component_type),
            socket=VALUES(socket), ram_type=VALUES(ram_type), tdp_watt=VALUES(tdp_watt),
            psu_watt=VALUES(psu_watt), warranty_months=VALUES(warranty_months);

        -- 5. Multi-image Gallery Records for Primary E2E RTX 4060 Product
        DELETE FROM product_images WHERE product_id = (SELECT id FROM products WHERE slug='e2e-asus-rtx-4060' LIMIT 1);
        INSERT INTO product_images (product_id, image_url, is_primary, sort_order)
        SELECT id, 'assets/images/products/pc1.jpg', 1, 0 FROM products WHERE slug='e2e-asus-rtx-4060' LIMIT 1;
        INSERT INTO product_images (product_id, image_url, is_primary, sort_order)
        SELECT id, 'assets/images/products/pc2.jpg', 0, 1 FROM products WHERE slug='e2e-asus-rtx-4060' LIMIT 1;
        INSERT INTO product_images (product_id, image_url, is_primary, sort_order)
        SELECT id, 'assets/images/products/laptop1.jpg', 0, 2 FROM products WHERE slug='e2e-asus-rtx-4060' LIMIT 1;
        """
        self.run_sql(seed_sql, db_name=self.db_name)

    # --- Controlled Fixture Setup Helpers (only where no public endpoint exists) ---

    def set_product_stock(self, slug: str, stock: int) -> None:
        self.run_sql(
            f"UPDATE products SET stock = {int(stock)} WHERE slug = {_sql_str(slug)};",
            db_name=self.db_name,
        )

    def get_product_stock(self, slug: str) -> int:
        rows = self.query_tsv(
            f"SELECT stock FROM products WHERE slug = {_sql_str(slug)} LIMIT 1;",
            db_name=self.db_name,
        )
        if not rows:
            raise AssertionError(f"Seeded product slug not found: {slug}")
        return int(rows[0]["stock"])

    def get_product_id(self, slug: str) -> int:
        rows = self.query_tsv(
            f"SELECT id FROM products WHERE slug = {_sql_str(slug)} LIMIT 1;",
            db_name=self.db_name,
        )
        if not rows:
            raise AssertionError(f"Seeded product slug not found: {slug}")
        return int(rows[0]["id"])

    def expire_vietqr_order(self, order_code: str, minutes_ago: int = 30) -> None:
        """Backdate payment_expires_at for T2-F03-04 (no public clock-advance endpoint exists)."""
        self.run_sql(
            f"UPDATE orders SET payment_expires_at = DATE_SUB(NOW(), INTERVAL {int(minutes_ago)} MINUTE) "
            f"WHERE order_code = {_sql_str(order_code)};",
            db_name=self.db_name,
        )
