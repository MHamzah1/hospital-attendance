@echo off
echo =============================================
echo  Hospital Attendance - LAN Multi-Segment
echo =============================================
echo.
echo Server IP: 192.168.100.50
echo.
echo Akses dari semua segmen via:
echo   http://192.168.100.50   (dari segmen mana pun)
echo.
echo Segmen yang didukung:
echo   192.168.100.x  (Segmen utama - server)
echo   192.168.10.x   (Segmen LAN 2)
echo   192.168.20.x   (Segmen LAN 3)
echo.
echo =============================================
echo  CHECKLIST SEBELUM MULAI:
echo =============================================
echo.
echo [1] XAMPP Apache sudah START? (port 80/443)
echo [2] Firewall sudah dikonfigurasi?
echo     Jika belum: buka PowerShell sebagai Admin, lalu:
echo     cd %~dp0
echo     .\setup-firewall-multisegment.ps1
echo.
echo [3] Dari device segmen lain, coba:
echo     ping 192.168.100.50
echo     Jika tidak reply, minta admin jaringan aktifkan
echo     inter-VLAN routing di router/switch L3
echo.
echo =============================================
echo.
echo Menjalankan Vite Dev Server...
echo.
start "Vite Dev Server" npm run dev
echo.
echo Vite berjalan di background.
echo Buka http://192.168.100.50 dari device manapun.
echo.
pause