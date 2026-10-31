@echo off
title JICOS WhatsApp Engine - Multi-Session (Port 5050)
cd /d "%~dp0"
echo ======================================================================
echo           🚀 JICOS WHATSAPP ENGINE (MULTI-SESSION)
echo ======================================================================
echo  Port: 5050
echo  Web QR Dashboard: http://localhost:5050/qr
echo  Active Sessions : [SALES & CS] + [PURCHASING] + [INVENTORY & HR]
echo ======================================================================
echo.

echo [INFO] Memeriksa dan membersihkan proses lama di port 5050...
for /f "tokens=5" %%a in ('netstat -aon ^| findstr ":5050" ^| findstr "LISTENING"') do (
    echo [INFO] Menutup proses lama (PID: %%a)...
    taskkill /f /pid %%a >nul 2>&1
)

if not exist node_modules (
    echo [INFO] Installing required dependencies...
    call npm install
)
echo [INFO] Memulai server WhatsApp Engine di Port 5050...
node server.js
pause
