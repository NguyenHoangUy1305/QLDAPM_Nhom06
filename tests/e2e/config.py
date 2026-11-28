"""Configuration and shared runtime state for the QLDAPM_Nhom06 E2E suite."""
from __future__ import annotations

import os
import shutil
from dataclasses import dataclass
from pathlib import Path

TESTS_E2E_DIR = Path(__file__).resolve().parent
REPO_ROOT = TESTS_E2E_DIR.parents[1]


def _resolve_bin(env_key: str, candidates: list[str], fallback: str) -> str:
    env_val = os.environ.get(env_key)
    if env_val and os.path.exists(env_val):
        return env_val
    for cand in candidates:
        if os.path.exists(cand):
            return cand
    found = shutil.which(fallback)
    return found or fallback


PHP_BIN = _resolve_bin("PHP_BIN", [r"D:\php83\php.EXE", r"D:\xampp\php\php.exe"], "php")
MYSQL_BIN = _resolve_bin("MYSQL_BIN", [r"D:\xampp\mysql\bin\mysql.exe"], "mysql")
MYSQLDUMP_BIN = _resolve_bin("MYSQLDUMP_BIN", [r"D:\xampp\mysql\bin\mysqldump.exe"], "mysqldump")
CHROME_BIN = _resolve_bin(
    "CHROME_BIN",
    [
        r"C:\Program Files\Google\Chrome\Application\chrome.exe",
        r"C:\Program Files (x86)\Google\Chrome\Application\chrome.exe",
        r"C:\Program Files (x86)\Microsoft\Edge\Application\msedge.exe",
        r"C:\Program Files\Microsoft\Edge\Application\msedge.exe",
    ],
    "chrome",
)

DB_HOST = os.environ.get("E2E_DB_HOST", "127.0.0.1")
DB_PORT = int(os.environ.get("E2E_DB_PORT", "3306"))
DB_USER = os.environ.get("E2E_DB_USER", "root")
DB_PASS = os.environ.get("E2E_DB_PASS", "")
DEFAULT_DB_NAME = "database"
ISOLATED_DB_NAME = "qldapm_e2e_test"

# Seeded accounts defined in PROJECT.md § Interface Contracts
ADMIN_EMAIL = "admin@doan6.local"
ADMIN_PASS = "admin123"
STAFF_EMAIL = "nhanvien@doan6.local"
STAFF_PASS = "staff123"
CUSTOMER_EMAIL = "khachhang@doan6.local"
CUSTOMER_PASS = "123456"

# Timeouts (seconds)
SERVER_START_TIMEOUT = 12.0
HTTP_TIMEOUT = 15.0
CDP_TIMEOUT = 15.0
MYSQL_CONNECT_TIMEOUT = 2.0


@dataclass
class RuntimeState:
    base_url: str | None = None
    db_name: str = DEFAULT_DB_NAME
    skip_db_reset: bool = False
    verbose: bool = False
    managed_by_runner: bool = False
    php_server: object | None = None
    db_manager: object | None = None


RUNTIME = RuntimeState()
