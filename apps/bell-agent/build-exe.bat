@echo off
title Build JICOS Bell Agent Standalone EXE
cd /d "%~dp0"

echo ========================================================
echo   BUILDING JICOS BELL AGENT STANDALONE EXECUTABLE (.EXE)
echo ========================================================
echo Memastikan PyInstaller terpasang...

python -m pip install pyinstaller

echo Membersihkan build lama...
rmdir /s /q build dist 2>nul

echo Mengemas aplikasi menjadi file tunggal EXE (Standalone)...
python -m PyInstaller --noconfirm --onedir --windowed --name "JICOS-Bell-Agent" ^
    --collect-all "customtkinter" ^
    --collect-all "sounddevice" ^
    --collect-all "soundfile" ^
    --collect-all "pystray" ^
    --collect-all "PIL" ^
    main.py

copy /y config.json dist\JICOS-Bell-Agent\config.json >nul 2>nul
mkdir dist\JICOS-Bell-Agent\cache\sounds 2>nul

echo.
echo ========================================================
echo BUILD SELESAI!
echo Folder aplikasi siap pakai ada di: dist\JICOS-Bell-Agent\
echo Anda dapat menyalin folder dist\JICOS-Bell-Agent ke PC Client manapun!
echo ========================================================
pause
