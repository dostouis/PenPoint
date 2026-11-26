Set WshShell = CreateObject("WScript.Shell")
Set fso = CreateObject("Scripting.FileSystemObject")
currentDir = fso.GetParentFolderName(WScript.ScriptFullName)
WshShell.Run "powershell.exe -STA -WindowStyle Hidden -ExecutionPolicy Bypass -File """ & currentDir & "\penpoint_control_center.ps1""", 0, False
