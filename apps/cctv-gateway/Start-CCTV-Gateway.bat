@echo off
title JICOS CCTV Gateway - Local Streaming Service
cd /d "%~dp0"

echo ===================================================================
echo [JICOS CCTV] Menjalankan Gateway Streaming CCTV Lokal (MediaMTX)
echo Port WebRTC: 8889 ^| Port HLS: 8888 ^| Port RTSP: 8554
echo Target NVR : 192.168.1.4:554
echo ===================================================================

:: Check if mediamtx exists in laragon bin
if exist "C:\laragon\bin\mediamtx\mediamtx.exe" (
    "C:\laragon\bin\mediamtx\mediamtx.exe" "%~dp0mediamtx.yml"
) else (
    echo [ERROR] File executable mediamtx.exe tidak ditemukan di C:\laragon\bin\mediamtx\
    pause
)
