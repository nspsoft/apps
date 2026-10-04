@echo off
title JICOS Factory Bell Agent
cd /d "%~dp0"

echo ===================================================
echo     JICOS FACTORY BELL AGENT - TOA CONTROLLER
echo ===================================================
echo Memeriksa dependensi Python...

where python >nul 2>nul
if %errorlevel% neq 0 (
    echo [ERROR] Python tidak ditemukan di PC ini!
    echo Harap instal Python 3.10+ atau gunakan paket Portable / EXE.
    pause
    exit /b 1
)

python -c "import sounddevice, customtkinter, pystray" >nul 2>nul
if %errorlevel% neq 0 (
    echo Menginstal dependensi library yang dibutuhkan...
    python -m pip install -r requirements.txt
)

echo Menjalankan JICOS Bell Agent...
start "" pythonw main.py
exit
