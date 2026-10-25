@echo off
title JICOS CCTV - Register Auto-Start on Windows Boot
cd /d "%~dp0"

echo Menambahkan JICOS CCTV Gateway ke Windows Startup...

set "STARTUP_FOLDER=%APPDATA%\Microsoft\Windows\Start Menu\Programs\Startup"
set "VBS_SCRIPT=%STARTUP_FOLDER%\Start-JICOS-CCTV-Gateway.vbs"

:: Create VBS script to run MediaMTX and Web Server silently in background on boot
(
echo Set WshShell = CreateObject("WScript.Shell"^)
echo WshShell.Run """%~dp0mediamtx.exe"" ""%~dp0mediamtx.yml""", 0, False
echo WshShell.Run "powershell.exe -ExecutionPolicy Bypass -NoProfile -WindowStyle Hidden -File """"%~dp0server.ps1"""" 8880", 0, False
) > "%VBS_SCRIPT%"

if exist "%VBS_SCRIPT%" (
    echo [SUKSES] JICOS CCTV Gateway berhasil didaftarkan ke Windows Startup!
    echo Service akan otomatis berjalan di latar belakang setiap kali PC JIDOKA dinyalakan.
    echo Smart TV dapat langsung mengakses: http://jidoka.local:8880
) else (
    echo [GAGAL] Tidak dapat membuat file startup.
)

echo.
pause
