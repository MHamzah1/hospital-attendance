# =============================================================================
# setup-firewall-multisegment.ps1
# Membuka port aplikasi untuk akses dari SELURUH segmen jaringan lokal
# Jalankan sebagai Administrator!
# =============================================================================

#Requires -RunAsAdministrator

$ports = @(
    @{ Port = 80;   Name = "Apache HTTP (XAMPP)" },
    @{ Port = 8080; Name = "Apache Alt (XAMPP)" },
    @{ Port = 8000; Name = "PHP Artisan Serve" },
    @{ Port = 5173; Name = "Vite Dev Server" }
)

# Semua segmen jaringan privat (RFC 1918) + jaringan kantor ini
$allowedRanges = @(
    "10.0.0.0/8",
    "172.16.0.0/12",
    "192.168.0.0/16",
    "30.30.30.0/24"
)

$remoteAddresses = $allowedRanges -join ","

Write-Host "==========================================" -ForegroundColor Cyan
Write-Host " Hospital Attendance - Firewall Setup" -ForegroundColor Cyan
Write-Host " Multi-Segment Network Access" -ForegroundColor Cyan
Write-Host "==========================================" -ForegroundColor Cyan
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

Write-Host ""
Write-Host "Server ini dapat diakses dari:" -ForegroundColor Green
Write-Host "  http://30.30.30.123      (Ethernet - primary)" -ForegroundColor White
Write-Host "  http://30.30.30.63       (Wi-Fi)" -ForegroundColor White
Write-Host ""
Write-Host "Segmen yang diizinkan mengakses:" -ForegroundColor Yellow
foreach ($r in $allowedRanges) {
    Write-Host "  - $r" -ForegroundColor White
}
Write-Host ""
Write-Host "Jika ada segmen lain (misal 172.20.x.x), tambahkan ke variabel" -ForegroundColor Yellow
Write-Host "\$allowedRanges di dalam script ini, lalu jalankan ulang." -ForegroundColor Yellow
Write-Host ""
