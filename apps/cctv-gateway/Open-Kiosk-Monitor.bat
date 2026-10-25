@echo off
title JICOS CCTV - Open Fullscreen Kiosk Monitor
cd /d "%~dp0"

echo ===================================================================
echo Membuka Layar Pantau CCTV Surveillance (Fullscreen Kiosk Mode)
echo ===================================================================

:: Prioritaskan Google Chrome jika tersedia
if exist "C:\Program Files\Google\Chrome\Application\chrome.exe" (
    start "" "C:\Program Files\Google\Chrome\Application\chrome.exe" --kiosk "http://localhost/facilities/cctv" --start-fullscreen
    exit
)

if exist "C:\Program Files (x86)\Google\Chrome\Application\chrome.exe" (
    start "" "C:\Program Files (x86)\Google\Chrome\Application\chrome.exe" --kiosk "http://localhost/facilities/cctv" --start-fullscreen
    exit
)

:: Alternatif Microsoft Edge
start "" msedge --kiosk "http://localhost/facilities/cctv" --edge-kiosk-type=fullscreen
exit
