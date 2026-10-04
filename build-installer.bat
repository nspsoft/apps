@echo off
title Build SPINDO ERP Inno Setup Installer
echo ========================================================
echo   SPINDO ERP - Build Inno Setup Production Installer
echo ========================================================
echo.

cd /d "%~dp0"

echo [1/4] Memeriksa apakah asset frontend sudah di-build...
if not exist "public\build\manifest.json" (
    echo [INFO] Asset build belum ditemukan. Menjalankan npm run build...
    call npm run build
    if %errorlevel% neq 0 (
        echo [ERROR] Gagal melakukan build asset frontend (npm run build)!
        pause
        exit /b 1
    )
) else (
    echo [OK] Asset frontend sudah tersedia di public\build.
)

echo.
echo [2/4] Memeriksa compiler Inno Setup (ISCC.exe)...
set ISCC="C:\Program Files (x86)\Inno Setup 6\ISCC.exe"
if not exist %ISCC% (
    set ISCC="C:\Program Files\Inno Setup 6\ISCC.exe"
)
if not exist %ISCC% (
    where ISCC.exe >nul 2>&1
    if %errorlevel% equ 0 (
        set ISCC=ISCC.exe
    ) else (
        echo [ERROR] Inno Setup 6 tidak ditemukan!
        echo Pastikan Inno Setup 6 terpasang di komputer ini.
        pause
        exit /b 1
    )
)
echo [OK] Compiler ditemukan: %ISCC%

echo.
echo [3/4] Memastikan folder output tersedia (C:\OutputInstaller)...
if not exist "C:\OutputInstaller" (
    mkdir "C:\OutputInstaller"
)

echo.
echo [4/4] Mengompilasi installer-erp.iss menjadi file .EXE...
%ISCC% "%~dp0installer-erp.iss"

if %errorlevel% equ 0 (
    echo.
    echo ========================================================
    echo   [SUKSES] Installer SPINDO ERP berhasil dibuat!
    echo   Lokasi File: C:\OutputInstaller\SPINDO_ERP_Installer_v1.0.0.exe
    echo ========================================================
) else (
    echo.
    echo ========================================================
    echo   [GAGAL] Gagal mengompilasi installer! Periksa error di atas.
    echo ========================================================
)

echo.
pause
