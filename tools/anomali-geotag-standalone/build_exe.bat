@echo off
title Build Executable - Anomali Geotag SE2026
echo ========================================================
echo  MEMBANGUN APLIKASI STANDALONE (.EXE) ANOMALI GEOTAG SE2026
echo ========================================================
echo.

cd /d "%~dp0"

echo [1/3] Memeriksa dependensi PyInstaller...
python -m pip install pyinstaller flask shapely

echo.
echo [2/3] Mengompilasi aplikasi menjadi single executable (.exe)...
pyinstaller --noconfirm --clean ^
    --name "AnomaliGeotagSE2026" ^
    --onefile ^
    --add-data "templates;templates" ^
    --add-data "static;static" ^
    app.py

if %ERRORLEVEL% NEQ 0 (
    echo.
    echo [ERROR] Kompilasi gagal! Silakan periksa pesan error di atas.
    pause
    exit /b %ERRORLEVEL%
)

echo.
echo [3/3] Menyiapkan folder distribusi mandiri...
if not exist "dist\Aplikasi_Anomali_Geotag_SE2026" mkdir "dist\Aplikasi_Anomali_Geotag_SE2026"
if not exist "dist\Aplikasi_Anomali_Geotag_SE2026\data" mkdir "dist\Aplikasi_Anomali_Geotag_SE2026\data"

move /y "dist\AnomaliGeotagSE2026.exe" "dist\Aplikasi_Anomali_Geotag_SE2026\"
copy /y "PANDUAN_PENGGUNA.txt" "dist\Aplikasi_Anomali_Geotag_SE2026\"
copy /y "data\*.*" "dist\Aplikasi_Anomali_Geotag_SE2026\data\"

echo.
echo ========================================================
echo  BERHASIL!
echo  Folder aplikasi siap dibagikan ada di:
echo  dist\Aplikasi_Anomali_Geotag_SE2026\
echo ========================================================
pause
