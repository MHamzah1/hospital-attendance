@echo off
echo =====================================
echo Quick Camera Fix - Desktop Testing
echo =====================================
echo.
echo Menjalankan server dengan localhost...
echo (Camera works di desktop dengan localhost)
echo.

REM Update .env to localhost
powershell -Command "(Get-Content .env) -replace 'APP_URL=http://.*', 'APP_URL=http://localhost:8000' | Set-Content .env"

echo ✅ Updated APP_URL to http://localhost:8000
echo.
echo Starting Laravel server...
echo.
echo 📱 MOBILE: Gunakan setup-xampp-https.bat untuk HTTPS
echo 💻 DESKTOP: Buka http://localhost:8000 (camera works!)
echo.
php artisan serve --host=localhost --port=8000