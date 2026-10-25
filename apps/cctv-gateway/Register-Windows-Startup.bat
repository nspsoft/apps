@echo off
title JICOS CCTV - Register Auto-Start on Windows Boot
cd /d "%~dp0"

echo Menambahkan JICOS CCTV Gateway ke Windows Startup...

set "STARTUP_FOLDER=%APPDATA%\Microsoft\Windows\Start Menu\Programs\Startup"
set "VBS_SCRIPT=%STARTUP_FOLDER%\Start-JICOS-CCTV-Gateway.vbs"

:: Create VBS script to run MediaMTX silently in background on boot
(
echo Set WshShell = CreateObject("WScript.Shell"^)
echo WshShell.Run "C:\laragon\bin\mediamtx\mediamtx.exe ""%~dp0mediamtx.yml""", 0, False
) > "%VBS_SCRIPT%"

if exist "%VBS_SCRIPT%" (
    echo [SUKSES] JICOS CCTV Gateway berhasil didaftarkan ke Windows Startup!
    echo Gateway akan otomatis berjalan di latar belakang setiap kali komputer dinyalakan.
) else (
    echo [GAGAL] Tidak dapat membuat file startup.
)

echo.
pause
