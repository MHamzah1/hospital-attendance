@echo off
echo =====================================
echo LAN Access - Hospital Attendance
echo =====================================
echo.
echo IP Komputer ini: 100.114.24.44
echo.
echo Buka dari device lain: http://100.114.24.44:8080
echo Dari HP, sistem otomatis tampil versi mobile.
echo.
echo Menjalankan Vite Dev Server untuk aset...
echo.
start "Vite Dev Server" npm run dev
echo.
echo Pastikan XAMPP Apache sudah berjalan (port 8080).
echo Lalu buka http://100.114.24.44:8080 dari device lain.
pause