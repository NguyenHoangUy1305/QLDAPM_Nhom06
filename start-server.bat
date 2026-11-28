@echo off
chcp 65001 >nul
echo ======================================================
echo   KHOI DONG WEBSITE DOAN 6 - QLDAPM NHOM 06 (PHP 8)
echo   Truy cap: http://127.0.0.1:8000
echo ======================================================
php -S 127.0.0.1:8000 -t "%~dp0"
pause
