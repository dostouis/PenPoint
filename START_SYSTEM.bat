@echo off
title PenPoint System Launcher - UMak SkiDB
color 0A

echo =====================================================================
echo           PENPOINT SMART VENDING MACHINE SYSTEM LAUNCHER
echo                 Group SkiDB (IV-CINS) - University of Makati
echo =====================================================================
echo.

:: 1. Start XAMPP MySQL
echo [1/3] Starting MySQL Database Service...
if exist "C:\xampp\mysql_start.bat" (
    start "" /B "C:\xampp\mysql_start.bat"
    echo      - MySQL service initiated.
) else (
    echo      [!] Warning: C:\xampp\mysql_start.bat not found. Please ensure MySQL is running in XAMPP.
)
timeout /t 2 /nobreak >nul

:: 2. Start PHP Built-in Server on Port 8000
echo.
echo [2/3] Starting PHP Web Server on http://127.0.0.1:8000...
start "PenPoint PHP Server" cmd /k "color 0B && title PenPoint PHP Server (Port 8000) && C:\xampp\php\php.exe -S 127.0.0.1:8000 -t c:\Coding\EcoPoints\penpoint"
timeout /t 2 /nobreak >nul

:: 3. Start Public Cloudflare Tunnel for Remote Mobile Access
echo.
echo [3/3] Starting Cloudflare Public Tunnel (No blank screens on phones)...
start "PenPoint Cloudflare Tunnel" cmd /k "color 0E && title PenPoint Cloudflare Public Tunnel && echo Starting public tunnel... Check URL below: && C:\xampp\cloudflared.exe tunnel --url http://127.0.0.1:8000"
timeout /t 3 /nobreak >nul

echo.
echo =====================================================================
echo                      ALL SERVICES STARTED!
echo =====================================================================
echo.
echo  Local Student Kiosk:     http://127.0.0.1:8000/public/index.php
echo  Local Admin Dashboard:   http://127.0.0.1:8000/admin/index.php (admin / admin123)
echo.
echo  Remote / Phone Access:   Look at the "PenPoint Cloudflare Tunnel" window
echo                           for your live https://...trycloudflare.com link.
echo.
echo  Opening Local Student Kiosk in default browser...
start http://127.0.0.1:8000/public/index.php
echo.
pause
