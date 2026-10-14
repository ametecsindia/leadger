@echo off
setlocal
cd /d "%~dp0"
title LeadGER - Remove Auto-Start

echo.
echo ============================================================
echo   LeadGER - Removing Auto-Start
echo ============================================================
echo.

echo [1/2] Removing User Registry startup entry...
reg delete "HKCU\Software\Microsoft\Windows\CurrentVersion\Run" /v "LeadGER_AutoStart" /f >nul 2>&1
if %ERRORLEVEL% EQU 0 (
    echo       [OK] Registry entry removed.
) else (
    echo       [INFO] Registry entry was not found or already removed.
)

echo [2/2] Removing Task Scheduler task (if present)...
schtasks /delete /tn "LeadGER_AutoStart" /f >nul 2>&1
if %ERRORLEVEL% EQU 0 (
    echo       [OK] Task Scheduler task removed.
) else (
    echo       [INFO] Task Scheduler task was not found or already removed.
)

echo.
echo ============================================================
echo   [SUCCESS] Auto-start disabled.
echo   LeadGER will no longer start automatically at Windows logon.
echo   You can still launch it manually anytime via start-leadger.bat
echo ============================================================
echo.
pause
exit /b 0
