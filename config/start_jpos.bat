@echo off
echo Starting MySQL...
start "" "C:\xampp\mysql\bin\mysqld.exe" --defaults-file="C:\xampp\mysql\bin\my.ini"
timeout /t 4 /nobreak >nul
echo MySQL started.

echo Starting PHP server...
cd /d C:\jaan\rathnapura-pharmacy
start "" cmd /k "php artisan serve"

echo Done. Open http://127.0.0.1:8000 in your browser.
