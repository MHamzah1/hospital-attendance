@echo off
echo =============================================
echo  SETUP FIREWALL - Multi Segment Access
echo  HARUS dijalankan sebagai Administrator!
echo =============================================
echo.

:: Cek apakah running as admin
net session >nul 2>&1
if %errorlevel% neq 0 (
    echo [ERROR] Harus dijalankan sebagai Administrator!
    echo.
    echo Klik kanan file ini ^> "Run as administrator"
    echo.
    pause
    exit /b 1
)

echo Menghapus rules lama...
netsh advfirewall firewall delete rule name="HospitalAttendance-Port80-IN" >nul 2>&1
netsh advfirewall firewall delete rule name="HospitalAttendance-Port443-IN" >nul 2>&1
netsh advfirewall firewall delete rule name="HospitalAttendance-Port8000-IN" >nul 2>&1
netsh advfirewall firewall delete rule name="HospitalAttendance-Port5173-IN" >nul 2>&1
netsh advfirewall firewall delete rule name="HospitalAttendance-Port8080-IN" >nul 2>&1
netsh advfirewall firewall delete rule name="HospitalAttendance-Ping-IN" >nul 2>&1
echo [OK] Rules lama dihapus
echo.

echo Membuat rules baru untuk semua segmen...
echo.

:: Port 80 - Apache HTTP
netsh advfirewall firewall add rule name="HospitalAttendance-Port80-IN" dir=in action=allow protocol=TCP localport=80 remoteip=192.168.0.0/16,10.0.0.0/8,172.16.0.0/12 profile=any
echo [OK] Port 80  (Apache HTTP)

:: Port 443 - Apache HTTPS
netsh advfirewall firewall add rule name="HospitalAttendance-Port443-IN" dir=in action=allow protocol=TCP localport=443 remoteip=192.168.0.0/16,10.0.0.0/8,172.16.0.0/12 profile=any
echo [OK] Port 443 (Apache HTTPS)

:: Port 8000 - PHP Artisan
netsh advfirewall firewall add rule name="HospitalAttendance-Port8000-IN" dir=in action=allow protocol=TCP localport=8000 remoteip=192.168.0.0/16,10.0.0.0/8,172.16.0.0/12 profile=any
echo [OK] Port 8000 (PHP Artisan)

:: Port 5173 - Vite Dev Server
netsh advfirewall firewall add rule name="HospitalAttendance-Port5173-IN" dir=in action=allow protocol=TCP localport=5173 remoteip=192.168.0.0/16,10.0.0.0/8,172.16.0.0/12 profile=any
echo [OK] Port 5173 (Vite Dev Server)

:: Port 8080 - Alt
netsh advfirewall firewall add rule name="HospitalAttendance-Port8080-IN" dir=in action=allow protocol=TCP localport=8080 remoteip=192.168.0.0/16,10.0.0.0/8,172.16.0.0/12 profile=any
echo [OK] Port 8080 (Alternative)

:: ICMP Ping
netsh advfirewall firewall add rule name="HospitalAttendance-Ping-IN" dir=in action=allow protocol=ICMPv4 remoteip=192.168.0.0/16,10.0.0.0/8,172.16.0.0/12 profile=any
echo [OK] Ping (ICMP)

echo.
echo =============================================
echo  Menambahkan route ke segmen lain...
echo =============================================
echo.

:: Route ke 192.168.10.x via gateway
route add 192.168.10.0 mask 255.255.255.0 192.168.100.1 -p >nul 2>&1
echo [OK] Route 192.168.10.0/24 via 192.168.100.1

:: Route ke 192.168.20.x via gateway  
route add 192.168.20.0 mask 255.255.255.0 192.168.100.1 -p >nul 2>&1
echo [OK] Route 192.168.20.0/24 via 192.168.100.1

echo.
echo =============================================
echo  VERIFIKASI
echo =============================================
echo.
netsh advfirewall firewall show rule name=all dir=in | findstr /i "HospitalAttendance"
echo.
echo =============================================
echo  SELESAI!
echo =============================================
echo.
echo Server: 192.168.100.50
echo.
echo Dari device di segmen lain, test:
echo   1. ping 192.168.100.50
echo   2. Buka browser: http://192.168.100.50
echo      atau https://192.168.100.50
echo.
pause
