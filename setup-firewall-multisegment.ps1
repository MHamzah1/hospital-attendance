# =============================================================================
# setup-firewall-multisegment.ps1
# Membuka port aplikasi untuk akses dari SELURUH segmen jaringan lokal
# Jalankan sebagai Administrator!
# =============================================================================

#Requires -RunAsAdministrator

$ports = @(
    @{ Port = 80;   Name = "Apache HTTP (XAMPP)" },
    @{ Port = 443;  Name = "Apache HTTPS (XAMPP)" },
    @{ Port = 8080; Name = "Apache Alt (XAMPP)" },
    @{ Port = 8000; Name = "PHP Artisan Serve" },
    @{ Port = 5173; Name = "Vite Dev Server" }
)

# Semua segmen jaringan yang ada di rumah sakit
$allowedRanges = @(
    "192.168.100.0/24",   # Segmen utama (server ada di sini)
    "192.168.10.0/24",    # Segmen LAN 2
    "192.168.20.0/24",    # Segmen LAN 3
    "10.0.0.0/8",         # Cadangan untuk segmen 10.x.x.x
    "172.16.0.0/12",      # Cadangan untuk segmen 172.x.x.x
    "30.30.30.0/24"       # Segmen tambahan
)

$remoteAddresses = $allowedRanges -join ","

Write-Host "==========================================" -ForegroundColor Cyan
Write-Host " Hospital Attendance - Firewall Setup" -ForegroundColor Cyan
Write-Host " Multi-Segment Network Access" -ForegroundColor Cyan
Write-Host "==========================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "Segmen yang akan diizinkan:" -ForegroundColor Yellow
foreach ($r in $allowedRanges) {
    Write-Host "  - $r" -ForegroundColor White
}
Write-Host ""

foreach ($entry in $ports) {
    $ruleName = "HospitalAttendance-Port$($entry.Port)-IN"

    # Hapus rule lama jika ada
    $existing = Get-NetFirewallRule -DisplayName $ruleName -ErrorAction SilentlyContinue
    if ($existing) {
        Remove-NetFirewallRule -DisplayName $ruleName
        Write-Host "[UPDATE] Rule lama dihapus: $ruleName" -ForegroundColor Yellow
    }

    # Buat rule baru
    New-NetFirewallRule `
        -DisplayName  $ruleName `
        -Direction    Inbound `
        -Protocol     TCP `
        -LocalPort    $entry.Port `
        -RemoteAddress $remoteAddresses `
        -Action       Allow `
        -Profile      Any `
        -Description  "Hospital Attendance System - $($entry.Name)" | Out-Null

    Write-Host "[OK] Port $($entry.Port) ($($entry.Name)) dibuka untuk semua segmen" -ForegroundColor Green
}

Write-Host ""
Write-Host "==========================================" -ForegroundColor Cyan
Write-Host " Verifikasi Rules:" -ForegroundColor Cyan
Write-Host "==========================================" -ForegroundColor Cyan

Get-NetFirewallRule -DisplayName "HospitalAttendance-*" |
    Select-Object DisplayName, Enabled, Action |
    Format-Table -AutoSize

# ============================================================
# Tambahkan route statis agar server bisa balas ke segmen lain
# (hanya perlu jika gateway belum routing otomatis)
# ============================================================
Write-Host ""
Write-Host "==========================================" -ForegroundColor Cyan
Write-Host " Menambahkan Route ke Segmen Lain" -ForegroundColor Cyan
Write-Host "==========================================" -ForegroundColor Cyan

$gateway = "192.168.100.1"  # Gateway utama (router/L3 switch)

$routes = @(
    @{ Network = "192.168.10.0"; Mask = "255.255.255.0"; Desc = "Segmen 192.168.10.x" },
    @{ Network = "192.168.20.0"; Mask = "255.255.255.0"; Desc = "Segmen 192.168.20.x" }
)

foreach ($r in $routes) {
    # Cek apakah route sudah ada
    $exists = route print | Select-String $r.Network
    if ($exists) {
        Write-Host "[SKIP] Route ke $($r.Desc) sudah ada" -ForegroundColor Yellow
    } else {
        try {
            route add $r.Network mask $r.Mask $gateway -p | Out-Null
            Write-Host "[OK] Route ditambahkan: $($r.Network) -> $gateway ($($r.Desc))" -ForegroundColor Green
        } catch {
            Write-Host "[WARN] Gagal menambah route ke $($r.Desc): $_" -ForegroundColor Red
        }
    }
}

Write-Host ""
Write-Host "==========================================" -ForegroundColor Cyan
Write-Host " SELESAI!" -ForegroundColor Green
Write-Host "==========================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "Server IP: 192.168.100.50" -ForegroundColor Green
Write-Host ""
Write-Host "Device dari segmen berikut seharusnya bisa akses:" -ForegroundColor White
Write-Host "  192.168.100.x  ->  http://192.168.100.50" -ForegroundColor White
Write-Host "  192.168.10.x   ->  http://192.168.100.50" -ForegroundColor White
Write-Host "  192.168.20.x   ->  http://192.168.100.50" -ForegroundColor White
Write-Host ""
Write-Host "PENTING: Pastikan router/switch L3 di 192.168.100.1" -ForegroundColor Yellow
Write-Host "sudah mengaktifkan inter-VLAN routing antara semua segmen!" -ForegroundColor Yellow
Write-Host ""
Write-Host "Untuk test dari device di segmen lain:" -ForegroundColor Yellow
Write-Host "  1. Buka CMD di device tersebut" -ForegroundColor White
Write-Host "  2. Ketik: ping 192.168.100.50" -ForegroundColor White
Write-Host "  3. Jika reply, buka browser: http://192.168.100.50" -ForegroundColor White
Write-Host "  4. Jika timeout, hubungi admin jaringan" -ForegroundColor White
Write-Host "     untuk aktifkan routing antar segmen" -ForegroundColor White
Write-Host ""
