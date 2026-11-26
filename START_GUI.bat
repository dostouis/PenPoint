@echo off
:: Launches the PenPoint Control Center GUI with NO command prompt popups
cd /d "%~dp0"
start "" powershell -WindowStyle Hidden -ExecutionPolicy Bypass -File "%~dp0penpoint_control_center.ps1"
