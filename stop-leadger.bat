@echo off
setlocal EnableDelayedExpansion
cd /d "%~dp0"
title LeadGER - Stopping...

echo.
echo ============================================================
echo   LeadGER - Stopping All Running Services
echo   Time: %DATE% %TIME%
echo ============================================================
echo.

:: 1. Terminate process on port 8000
echo [1/3] Stopping web server (port 8000)...
:: only the process LISTENING on 8000 (the server) - not browsers that are connected to it
for /f "tokens=5" %%a in ('netstat -ano -p tcp 2^>nul ^| findstr /R /C:":8000 .*LISTENING"') do (
    taskkill /PID %%a /F >nul 2>&1
)
echo       [OK] Port 8000 released.

:: 2. Terminate only LeadGER's PHP processes (web server, queue worker, scheduler) - other PHP apps are left alone
echo [2/3] Stopping background PHP processes (queue, scheduler)...
powershell -NoProfile -Command "$p = Get-CimInstance Win32_Process | Where-Object { $_.Name -eq 'php.exe' -and $_.CommandLine -match 'artisan (serve|queue:work|schedule:work|schedule:run)|resources.server\.php' }; $p | ForEach-Object { Stop-Process -Id $_.ProcessId -Force -ErrorAction SilentlyContinue }; if ($p) { '      [OK] Stopped ' + @($p).Count + ' LeadGER PHP process(es).' } else { '      [INFO] No LeadGER PHP processes were running.' }"

:: 3. Terminate any orphaned LeadGER cmd windows
echo [3/3] Closing LeadGER console sessions...
taskkill /FI "WINDOWTITLE eq LeadGER - Web Server*" /F >nul 2>&1
taskkill /FI "WINDOWTITLE eq LeadGER - Queue Worker*" /F >nul 2>&1
taskkill /FI "WINDOWTITLE eq LeadGER - Scheduler*" /F >nul 2>&1
taskkill /FI "WINDOWTITLE eq LeadGER - Starting*" /F >nul 2>&1
echo       [OK] Console sessions closed.

echo.
echo ============================================================
echo   [SUCCESS] LeadGER services stopped cleanly.
echo   Note: MySQL service (MYSQL80) remains active in background.
echo   To restart, double-click start-leadger.bat.
echo ============================================================
echo.
ping -n 4 127.0.0.1 >nul
exit /b 0
