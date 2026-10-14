@echo off
title Lt_Leadger Server
echo ====================================================
echo Starting Lt_Leadger Server
echo.
echo  Local Access:
echo    http://localhost:8000
echo.
echo  Access from Another Laptop (same Wi-Fi):
echo    http://192.168.0.128:8000
echo ====================================================
cd /d "%~dp0core"
php artisan serve --host=0.0.0.0 --port=8000
pause
