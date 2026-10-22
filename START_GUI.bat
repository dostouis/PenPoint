@echo off
:: Launches the PenPoint Control Center GUI with NO command prompt popups
cd /d "c:\Coding\EcoPoints\penpoint"
start "" powershell -WindowStyle Hidden -ExecutionPolicy Bypass -File "c:\Coding\EcoPoints\penpoint\penpoint_control_center.ps1"
