@echo off
echo =====================================
echo LAN Access - Hospital Attendance
echo =====================================
echo.
echo Pastikan file .env sudah berisi APP_URL dengan IP komputer ini,
echo misalnya http://192.168.0.102:8000
echo.
echo Menjalankan server untuk akses device lain...
echo.
start "Laravel Server" php artisan serve --host=0.0.0.0 --port=8000
start "Vite Dev Server" npm run dev
echo.
echo Buka dari device lain menggunakan IP komputer ini.
echo Contoh: http://192.168.0.102:8000