@echo off
cd /d "D:\xampp\htdocs\qldapm"
echo Dang khoi dong server Laravel tai http://127.0.0.1:8080 ...
D:\php83\php.exe artisan serve --host=127.0.0.1 --port=8080
pause
