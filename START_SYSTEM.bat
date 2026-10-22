@echo off
title PenPoint System Launcher - UMak SkiDB
color 0A

echo =====================================================================
echo           PENPOINT SMART VENDING MACHINE SYSTEM LAUNCHER
echo                 Group SkiDB (IV-CINS) - University of Makati
echo =====================================================================
echo.

:: Ensure working directory is penpoint folder
cd /d "c:\Coding\EcoPoints\penpoint"

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

:: 3. Start Public Cloudflare Tunnel & Extract Live URL
echo.
echo [3/3] Launching Cloudflare Public Tunnel for Phone & ESP32 Access...
echo      - Initializing tunnel and grabbing your live public URL...

del /f /q "%TEMP%\cf_penpoint.log" >nul 2>&1
start "PenPoint Cloudflare Tunnel" cmd /k "color 0E && title PenPoint Cloudflare Public Tunnel && C:\xampp\cloudflared.exe tunnel --logfile "%TEMP%\cf_penpoint.log" --url http://127.0.0.1:8000"

:: Wait up to 10 seconds for the trycloudflare.com URL to appear in the log
set "TUNNEL_URL="
for /L %%i in (1,1,10) do (
    timeout /t 1 /nobreak >nul
    if exist "%TEMP%\cf_penpoint.log" (
        for /f "usebackq tokens=*" %%a in (`powershell -NoProfile -Command "(Get-Content -Path $env:TEMP\cf_penpoint.log -ErrorAction SilentlyContinue | Select-String -Pattern 'https://[a-zA-Z0-9-]+\.trycloudflare\.com' | Select-Object -First 1).Matches.Value"`) do (
            set "TUNNEL_URL=%%a"
        )
    )
    if defined TUNNEL_URL goto :url_found
)

:url_found
echo.
echo =====================================================================
echo                      ALL SERVICES ACTIVE!
echo =====================================================================
echo.
echo  Local Student Kiosk:     http://127.0.0.1:8000/public/index.php
echo  Local Admin Dashboard:   http://127.0.0.1:8000/admin/index.php (admin / admin123)
echo.
if defined TUNNEL_URL (
    echo  =================================================================
    echo  YOUR LIVE CLOUDFLARE PUBLIC URL (Scan / Open on any Phone):
    echo  %TUNNEL_URL%/public/index.php
    echo  =================================================================
    echo.
    echo  Copying public link to your Windows clipboard...
    echo %TUNNEL_URL%/public/index.php| clip
    echo  [Copied to clipboard! You can paste it directly into Messenger or your phone browser]
) else (
    echo  Cloudflare is connecting. Look at the yellow "PenPoint Cloudflare Tunnel"
    echo  window to see your live https://...trycloudflare.com link.
)
echo.
echo  Opening Local Student Kiosk in default browser...
start http://127.0.0.1:8000/public/index.php
echo.
pause
