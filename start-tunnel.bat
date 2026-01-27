@echo off
TITLE Cloudflare Tunnel - erp.nsp.my.id
echo ========================================================
echo   Menjalankan Cloudflare Tunnel
echo   Target Lokal: http://erp.test
echo   Domain Publik: https://erp.nsp.my.id
echo ========================================================
echo.

:: Cek apakah cloudflared terinstall/terdeteksi
where cloudflared >nul 2>nul
if %errorlevel% neq 0 (
    echo [ERROR] 'cloudflared' tidak ditemukan di System PATH.
    echo Mohon edit file ini dan set path lengkap ke cloudflared.exe
    echo Contoh: set TOOL_PATH="C:\Tools\cloudflared.exe"
    echo.
    echo.
    set TOOL_PATH="C:\cloudflared\cloudflared.exe"
)

:: Token Tunnel (Diberikan User)
set TUNNEL_TOKEN=eyJ6b25lSUQiOiJiNDEwN2IwMzBhNGJlZjJhNTJhOWFiNGQxMWVkYTgyYiIsImFjY291bnRJRCI6IjNlZjJjZWQ5ZmEzZDQ1NWRjM2U4OTBmYjgwMzliNDE1Iiwic2VydmljZUtleSI6InYxLjAtMjk0MWRjZjA0NjJhYzQ1ZTMwMTBiYmIzLWRlZjlkNzZkZWQ0MmFhMmNmYmQwZWUzZGIzOTE0NjU3NTE1YzVhMmU3NmVmZWRhZjE4ZWEyYmI0NzY5MjMyMGE1N2I5NjAwNWJiOTc4YTBhYzQ4ZWUyODQyN2Y2ZTQzZDZmMzYxMWJjMGIyMjM3MzVkODFlZTVmNzY1ZDlhOWQ3NDE2NjEwNTg4ZTIwNWYzMWQzMDk2OGFiNDA1ODVlYzciLCJhcGlUb2tlbiI6Ikx3YVYwTjFhZ1RvTnJyYlVJOElGNzFlYVlJSzVwRVFhTGpLTllZbHoifQ==

echo Menjalankan tunnel untuk erp.nsp.my.id...
:: Menambahkan --protocol http2 untuk mengatasi error "failed to run datagram handler" (masalah koneksi UDP/QUIC)
%TOOL_PATH% tunnel run --protocol http2 --token %TUNNEL_TOKEN%

pause
