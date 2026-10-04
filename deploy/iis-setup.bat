@echo off
title Setup SPINDO ERP IIS Permissions & Configuration
echo ========================================================
echo   SPINDO ERP - IIS Permissions & AppPool Setup Script
echo ========================================================
echo.

:: Pastikan dijalankan sebagai Administrator
net session >nul 2>&1
if %errorlevel% neq 0 (
    echo [ERROR] Harap jalankan script ini sebagai Administrator (Run as Administrator)!
    pause
    exit /b 1
)

set TARGET_DIR=%~dp0..
cd /d "%TARGET_DIR%"
set APP_ROOT=%CD%

echo [1/3] Menyetel izin akses folder (icacls) untuk IIS...
echo Memberikan hak Modify / Read-Write ke IIS_IUSRS dan IUSR pada folder:
echo - %APP_ROOT%\storage
echo - %APP_ROOT%\bootstrap\cache
echo.

icacls "%APP_ROOT%\storage" /grant "IIS_IUSRS":(OI)(CI)M /grant "IUSR":(OI)(CI)M /grant "Network Service":(OI)(CI)M /T /Q
icacls "%APP_ROOT%\bootstrap\cache" /grant "IIS_IUSRS":(OI)(CI)M /grant "IUSR":(OI)(CI)M /grant "Network Service":(OI)(CI)M /T /Q

echo [2/3] Memastikan Symbolic Link Storage publik terpasang...
if exist "%APP_ROOT%\artisan" (
    where php >nul 2>&1
    if %errorlevel% equ 0 (
        php artisan storage:link
    ) else (
        if exist "C:\php\php.exe" (
            C:\php\php.exe artisan storage:link
        ) else (
            echo [INFO] Perintah php artisan storage:link akan dijalankan oleh Inno Setup installer.
        )
    )
)

echo.
echo [3/3] Informasi Konfigurasi IIS Site:
echo --------------------------------------------------------
echo Site Name       : SPINDO-ERP
echo Application Pool: ERP_Prod
echo Physical Path   : %APP_ROOT%\public
echo --------------------------------------------------------
echo.
echo Setup izin direktori IIS selesai!
pause
