' ============================================================
' LeadGER - Silent Auto-Start (runs at Windows logon)
' Launched via: HKCU\Software\Microsoft\Windows\CurrentVersion\Run
' No visible window - runs PHP artisan processes silently in background
' ============================================================

Dim WshShell, fso
Set WshShell = CreateObject("WScript.Shell")
Set fso = CreateObject("Scripting.FileSystemObject")

Dim ScriptDir, PHP, CoreDir, LogDir
ScriptDir = fso.GetParentFolderName(WScript.ScriptFullName)
PHP       = "C:\Program Files\Php\php-8.3.21\php.exe"
CoreDir   = ScriptDir & "\core"
LogDir    = ScriptDir & "\logs"

' If PHP not found at primary path, check system PATH
If Not fso.FileExists(PHP) Then
    PHP = "php.exe"
End If

' Create logs folder if it doesn't exist
If Not fso.FolderExists(LogDir) Then
    fso.CreateFolder(LogDir)
End If

' Wait 25 seconds for Windows boot and MySQL service to be ready
WScript.Sleep 25000

' Set working directory
WshShell.CurrentDirectory = CoreDir

' Start web server (host 0.0.0.0 to allow localhost and LAN access) with log output
WshShell.Run "%comspec% /c """"" & PHP & """ artisan serve --host=0.0.0.0 --port=8000 >> """ & LogDir & "\server.log"" 2>&1""", 0, False

' Give web server 3 seconds to bind port
WScript.Sleep 3000

' Start queue worker with log output
WshShell.Run "%comspec% /c """"" & PHP & """ artisan queue:work --sleep=3 --tries=3 >> """ & LogDir & "\queue.log"" 2>&1""", 0, False

' Start Laravel scheduler with log output
WshShell.Run "%comspec% /c """"" & PHP & """ artisan schedule:work >> """ & LogDir & "\scheduler.log"" 2>&1""", 0, False

Set WshShell = Nothing
Set fso = Nothing
