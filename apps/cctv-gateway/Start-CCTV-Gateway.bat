@echo off
title JICOS CCTV Gateway - Local Streaming Service
cd /d "%~dp0"

echo ===================================================================
echo [JICOS CCTV] Menjalankan Gateway Streaming CCTV Lokal (MediaMTX)
echo Target NVR : 192.168.1.4:554
echo Port WebRTC: 8889 ^| Port HLS: 8888 ^| Port Web UI: 8880
echo Akses Smart TV: http://jidoka.local:8880 (atau http://localhost:8880)
echo ===================================================================

:: Start local web server via PowerShell (built-in on every Windows)
start "JICOS CCTV Web UI" /min powershell.exe -ExecutionPolicy Bypass -NoProfile -File "%~dp0server.ps1" 8880

:: Run MediaMTX
if exist "%~dp0mediamtx.exe" (
    "%~dp0mediamtx.exe" "%~dp0mediamtx.yml"
) else if exist "C:\laragon\bin\mediamtx\mediamtx.exe" (
    "C:\laragon\bin\mediamtx\mediamtx.exe" "%~dp0mediamtx.yml"
) else (
    echo [ERROR] File executable mediamtx.exe tidak ditemukan!
    pause
)
