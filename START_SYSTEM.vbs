Set WshShell = CreateObject("WScript.Shell")
WshShell.Run "powershell.exe -STA -WindowStyle Hidden -ExecutionPolicy Bypass -File ""c:\Coding\EcoPoints\penpoint\penpoint_control_center.ps1""", 0, False
