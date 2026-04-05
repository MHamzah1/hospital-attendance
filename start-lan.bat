@echo off
echo =====================================
echo LAN Access - Hospital Attendance
echo Multi-Segment Network Support
echo =====================================
echo.
echo Server dapat diakses dari segmen mana pun via:
echo   http://192.168.100.50  (Ethernet - KANTOR, aktif sekarang)
echo   http://30.30.30.123    (Ethernet - LAN LAIN)
echo   http://30.30.30.63     (Wi-Fi)
echo.
echo Pastikan firewall sudah dikonfigurasi:
echo   Jalankan setup-firewall-multisegment.ps1 sebagai Administrator
echo.
echo Menjalankan Vite Dev Server untuk aset...
echo.
start "Vite Dev Server" npm run dev
echo.
echo Pastikan XAMPP Apache sudah berjalan (port 80).
echo Lalu buka http://30.30.30.123 dari device di segmen lain.
pause