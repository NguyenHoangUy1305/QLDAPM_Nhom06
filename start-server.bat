@echo off
chcp 65001 >nul
cd /d "%~dp0"
echo =========================================================
echo   KHOI DONG SERVER PHP THUAN - DO AN NHOM 06
echo   Dia chi website: http://127.0.0.1:8000
echo   (Nhan Ctrl + C trong cua so nay de dung server)
echo =========================================================
php -S 127.0.0.1:8000
pause
