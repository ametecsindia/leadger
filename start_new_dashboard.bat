@echo off
title LeadGER Modern Dashboard Server
echo ===================================================================
echo Starting LeadGER Modern Dashboard Server
echo Dedicated URL: http://localhost:8001
echo Admin Dashboard: http://localhost:8001/admin
echo ===================================================================
cd /d "%~dp0core"
php artisan serve --host=127.0.0.1 --port=8001
pause
