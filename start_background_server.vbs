Set WshShell = CreateObject("WScript.Shell")
Set fso = CreateObject("Scripting.FileSystemObject")
scriptDir = fso.GetParentFolderName(WScript.ScriptFullName)
WshShell.CurrentDirectory = scriptDir & "\core"
WshShell.Run Chr(34) & "C:\Program Files\Php\php-8.3.21\php.exe" & Chr(34) & " artisan serve --host=0.0.0.0 --port=8000", 0, False
