@echo off
setlocal EnableDelayedExpansion
cd /d "%~dp0"
title LeadGER - Install Auto-Start

echo.
echo ============================================================
echo   LeadGER - Installing Auto-Start on Windows Boot/Logon
echo ============================================================
echo.

set "VBS=%~dp0leadger-autostart.vbs"
set "REG_VALUE=wscript.exe ""%VBS%"""

:: 1. Register in User Startup Registry (works without administrator privileges)
echo [1/3] Adding to Windows User Logon Registry...
reg add "HKCU\Software\Microsoft\Windows\CurrentVersion\Run" /v "LeadGER_AutoStart" /t REG_SZ /d "%REG_VALUE%" /f >nul 2>&1
if !ERRORLEVEL! EQU 0 (
    echo       [OK] Registry auto-start registered successfully.
) else (
    echo       [WARN] Could not write to registry key.
)

:: 2. Attempt Task Scheduler registration (if running with elevated permissions)
echo [2/3] Checking Task Scheduler registration...
schtasks /create /tn "LeadGER_AutoStart" /tr "wscript.exe \"%VBS%\"" /sc onlogon /f >nul 2>&1
if !ERRORLEVEL! EQU 0 (
    echo       [OK] Task Scheduler task created.
) else (
    echo       [INFO] Task Scheduler requires Administrator rights; registry startup will be used.
)

:: 3. Verify MySQL service startup config
echo [3/3] Checking MySQL service startup configuration...
sc qc MYSQL80 2>nul | findstr "AUTO_START" >nul 2>&1
if !ERRORLEVEL! EQU 0 (
    echo       [OK] MySQL service (MYSQL80) is configured to start automatically with Windows.
) else (
    echo       [INFO] Please ensure MySQL service is running or set to Automatic in services.msc.
)

echo.
echo ============================================================
echo   [SUCCESS] Auto-start setup complete!
echo.
echo   LeadGER will now start silently in the background when
echo   Windows starts or you log into your PC.
echo.
echo   After boot/logon, wait ~30 seconds and visit:
echo     http://localhost:8000/admin
echo.
echo   To test right now:   double-click  start-leadger.bat
echo   To disable:          double-click  uninstall-autostart.bat
echo ============================================================
echo.
pause
exit /b 0
