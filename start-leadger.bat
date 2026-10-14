@echo off
setlocal EnableDelayedExpansion
cd /d "%~dp0"
title LeadGER - Starting...

:: ============================================================
:: LeadGER One-Click Start  (double-click; Windows names are not case-sensitive,
:: so this file is also "Start-LeadGER.bat")
:: Stack:  Laravel 13 / PHP 8.3.21 / MySQL 8.0 (service MYSQL80)
:: Starts: web server (port 8000), queue worker, scheduler - each in its own
::         minimized window, independent of any code editor.
:: Never runs migrations or any other database-changing command.
:: ============================================================

set "PROJECT_ROOT=%~dp0"
set "CORE=%PROJECT_ROOT%core"
set "LOGS_DIR=%PROJECT_ROOT%logs"
set "URL=http://127.0.0.1:8000"

set "PHP=C:\Program Files\Php\php-8.3.21\php.exe"
if not exist "%PHP%" if exist "%PROJECT_ROOT%php\php.exe" set "PHP=%PROJECT_ROOT%php\php.exe"
if not exist "%PHP%" (
    for /f "delims=" %%I in ('where php 2^>nul') do if not defined PHP_FROM_PATH set "PHP_FROM_PATH=%%I"
    if defined PHP_FROM_PATH set "PHP=!PHP_FROM_PATH!"
)
if not exist "%LOGS_DIR%" mkdir "%LOGS_DIR%"

echo.
echo ============================================================
echo   LeadGER - Startup Manager      %DATE% %TIME%
echo ============================================================
echo.

:: --- 1. Project checks ---------------------------------------------------
if not exist "%PHP%" (
    echo [ERROR] PHP not found at "C:\Program Files\Php\php-8.3.21\php.exe" and not on PATH.
    echo         Install PHP 8.3 there, or add php.exe to the system PATH.
    goto :ERROR_EXIT
)
echo [OK] PHP: %PHP%
if not exist "%CORE%\artisan" (
    echo [ERROR] Laravel project not found: %CORE%\artisan is missing.
    goto :ERROR_EXIT
)
if not exist "%CORE%\vendor\autoload.php" (
    echo [ERROR] Composer dependencies are missing ^(core\vendor^).
    echo         Open a terminal in "%CORE%" and run:  composer install
    goto :ERROR_EXIT
)
if not exist "%CORE%\.env" (
    echo [ERROR] core\.env is missing. Restore it from your backup - it holds the database and APP_KEY settings.
    goto :ERROR_EXIT
)
findstr /R /C:"^APP_KEY=base64:." "%CORE%\.env" >nul || (
    echo [ERROR] APP_KEY is not set in core\.env. Do NOT generate a new one on a live install
    echo         ^(it would invalidate stored encrypted data^); restore the original .env instead.
    goto :ERROR_EXIT
)
echo [OK] Laravel project, dependencies and .env found.

:: --- 2. Database -----------------------------------------------------------
echo.
echo [1/4] Database (MySQL service MYSQL80)...
sc query MYSQL80 2>nul | findstr "RUNNING" >nul
if errorlevel 1 (
    echo       MySQL is stopped - starting it...
    net start MYSQL80 >> "%LOGS_DIR%\startup.log" 2>&1
    if errorlevel 1 (
        echo [ERROR] Could not start MYSQL80. Right-click this file and choose "Run as administrator",
        echo         or start "MySQL80" in services.msc, then run this launcher again.
        goto :ERROR_EXIT
    )
    ping -n 4 127.0.0.1 >nul
)
:: Read-only connection test (lists migrations, changes nothing)
pushd "%CORE%"
"%PHP%" artisan migrate:status > "%LOGS_DIR%\db-check.log" 2>&1
set "DB_RC=!ERRORLEVEL!"
popd
if not "!DB_RC!"=="0" (
    echo [ERROR] Laravel cannot connect to the database. Details: logs\db-check.log
    echo         Check DB_HOST / DB_DATABASE / DB_USERNAME / DB_PASSWORD in core\.env.
    goto :ERROR_EXIT
)
findstr /C:"Pending" "%LOGS_DIR%\db-check.log" >nul && (
    echo [WARN] There are pending migrations. They are NOT run automatically.
    echo        Back up the database first, then run in core:  php artisan migrate
)
echo       [OK] Database connected.

:: --- 3. Already running? (never start duplicates) ---------------------------
echo.
echo [2/4] Checking for a running LeadGER...
call :COUNT_PHP "artisan serve|resources.server\.php" WEB
call :COUNT_PHP "queue:work" QUEUE
call :COUNT_PHP "schedule:work" SCHED
netstat -ano -p tcp | findstr /R /C:":8000 .*LISTENING" >nul
if not errorlevel 1 if "!WEB!"=="0" (
    echo [ERROR] Port 8000 is used by another program, not LeadGER.
    echo         Close that program ^(netstat -ano ^| findstr :8000 shows its PID^) and try again.
    goto :ERROR_EXIT
)

:: --- 4. Start what is missing ---------------------------------------------
echo.
echo [3/4] Starting services...
cd /d "%CORE%"
if "!WEB!"=="0" (
    start "LeadGER - Web Server" /MIN cmd /c ""%PHP%" artisan serve --host=0.0.0.0 --port=8000 >> "%LOGS_DIR%\server.log" 2>&1"
    echo       - Web server started        ^(logs\server.log^)
) else ( echo       - Web server already running )
if "!QUEUE!"=="0" (
    start "LeadGER - Queue Worker" /MIN cmd /c ""%PHP%" artisan queue:work --sleep=3 --tries=3 >> "%LOGS_DIR%\queue.log" 2>&1"
    echo       - Queue worker started      ^(logs\queue.log^)
) else ( echo       - Queue worker already running )
if "!SCHED!"=="0" (
    start "LeadGER - Scheduler" /MIN cmd /c ""%PHP%" artisan schedule:work >> "%LOGS_DIR%\scheduler.log" 2>&1"
    echo       - Scheduler started         ^(logs\scheduler.log^)
) else ( echo       - Scheduler already running )

:: --- 5. Wait until the site really answers, then open the browser --------
echo.
echo [4/4] Waiting for %URL% to respond...
set /a TRIES=0
:WAIT_READY
set /a TRIES+=1
:: any HTTP answer means the server is up (/admin and /login redirect to the login page at /)
curl.exe -s -o nul --max-time 5 "%URL%/" >nul 2>&1 && goto :SERVER_READY
if !TRIES! GEQ 40 (
    echo [ERROR] The web server did not respond within about 40 seconds. See logs\server.log
    goto :ERROR_EXIT
)
ping -n 2 127.0.0.1 >nul
goto :WAIT_READY
:SERVER_READY

echo.
echo ============================================================
echo   LeadGER is running.
echo     This PC:        %URL%/admin
echo     Other PCs:      http://192.168.0.128:8000/admin
echo.
echo   It keeps running after you close this window or the code editor.
echo   Do NOT close the minimized "LeadGER - ..." windows - they ARE the server.
echo   To stop LeadGER: double-click stop-leadger.bat
echo ============================================================
start "" "%URL%/admin"
echo.
echo [Press any key to close this window. LeadGER keeps running.]
pause >nul
exit /b 0

:: Counts running php.exe processes whose command line matches a regex -> %2
:COUNT_PHP
set "%~2=0"
for /f %%c in ('powershell -NoProfile -Command "@(Get-CimInstance Win32_Process | Where-Object { $_.Name -eq 'php.exe' -and $_.CommandLine -match '%~1' -and $_.CommandLine -notmatch 'schedule:run' }).Count"') do set "%~2=%%c"
exit /b 0

:ERROR_EXIT
echo.
echo ============================================================
echo   [ERROR] LeadGER was not started. See the message above.
echo ============================================================
echo.
pause
exit /b 1
