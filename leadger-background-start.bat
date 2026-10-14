:: ============================================================
:: LeadGER - Background Launcher (no visible window)
:: Called by Windows Task Scheduler at logon
:: Uses VBScript trick to hide the cmd window completely
:: ============================================================
@echo off
set "PHP=C:\Program Files\Php\php-8.3.21\php.exe"
set "CORE=C:\Lt_Leadger_source - Copy 1\core"
set "LOGS=C:\Lt_Leadger_source - Copy 1\logs"
set "VBS=%TEMP%\leadger_start.vbs"

if not exist "%LOGS%" mkdir "%LOGS%"

:: Write a temporary VBScript that silently runs artisan serve
echo Set WshShell = CreateObject("WScript.Shell") > "%VBS%"
echo Set fso = CreateObject("Scripting.FileSystemObject") >> "%VBS%"
echo WshShell.CurrentDirectory = "%CORE%" >> "%VBS%"
echo WshShell.Run Chr(34) ^& "%PHP%" ^& Chr(34) ^& " artisan serve --host=0.0.0.0 --port=8000", 0, False >> "%VBS%"
echo WshShell.Run Chr(34) ^& "%PHP%" ^& Chr(34) ^& " artisan queue:work --sleep=3 --tries=3", 0, False >> "%VBS%"
echo WshShell.Run Chr(34) ^& "%PHP%" ^& Chr(34) ^& " artisan schedule:work", 0, False >> "%VBS%"

:: Run the VBScript (invisible, detached)
cscript //nologo "%VBS%"

exit /b 0
