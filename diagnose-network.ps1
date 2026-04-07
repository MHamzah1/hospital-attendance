# =============================================================================
# diagnose-network.ps1
# Diagnosa koneksi jaringan multi-segmen untuk Hospital Attendance System
# Bisa dijalankan TANPA Administrator
# =============================================================================

Write-Host "==========================================" -ForegroundColor Cyan
Write-Host " Hospital Attendance - Network Diagnostic" -ForegroundColor Cyan
Write-Host "==========================================" -ForegroundColor Cyan
Write-Host ""

# 1. Tampilkan semua IP aktif
Write-Host "[1] IP Address Aktif:" -ForegroundColor Yellow
$adapters = Get-NetIPAddress -AddressFamily IPv4 | 
    Where-Object { $_.IPAddress -ne "127.0.0.1" -and $_.InterfaceAlias -notlike "*Loopback*" } |
    Select-Object InterfaceAlias, IPAddress, PrefixLength
    
foreach ($a in $adapters) {
    $status = Get-NetAdapter -InterfaceAlias $a.InterfaceAlias -ErrorAction SilentlyContinue
    if ($status.Status -eq "Up") {
        Write-Host "  [AKTIF] $($a.InterfaceAlias): $($a.IPAddress)/$($a.PrefixLength)" -ForegroundColor Green
    } else {
        Write-Host "  [MATI]  $($a.InterfaceAlias): $($a.IPAddress)/$($a.PrefixLength)" -ForegroundColor DarkGray
    }
}
Write-Host ""

# 2. Cek gateway
Write-Host "[2] Default Gateway:" -ForegroundColor Yellow
$gw = Get-NetRoute -DestinationPrefix "0.0.0.0/0" -ErrorAction SilentlyContinue | Select-Object -First 1
if ($gw) {
    Write-Host "  Gateway: $($gw.NextHop)" -ForegroundColor Green
    
    # Ping gateway
    $pingGw = Test-Connection $gw.NextHop -Count 1 -Quiet -ErrorAction SilentlyContinue
    if ($pingGw) {
        Write-Host "  Ping gateway: OK" -ForegroundColor Green
    } else {
        Write-Host "  Ping gateway: GAGAL" -ForegroundColor Red
    }
} else {
    Write-Host "  Tidak ada default gateway!" -ForegroundColor Red
}
Write-Host ""

# 3. Cek port yang sedang listen
Write-Host "[3] Port yang Listen:" -ForegroundColor Yellow
$targetPorts = @(80, 443, 8000, 5173)
foreach ($port in $targetPorts) {
    $listening = Get-NetTCPConnection -LocalPort $port -State Listen -ErrorAction SilentlyContinue
    if ($listening) {
        $localAddr = ($listening | Select-Object -First 1).LocalAddress
        Write-Host "  Port $port : LISTENING (bind: $localAddr)" -ForegroundColor Green
    } else {
        Write-Host "  Port $port : TIDAK AKTIF" -ForegroundColor Red
    }
}
Write-Host ""

# 4. Cek routing ke segmen lain
Write-Host "[4] Test Routing ke Segmen Lain:" -ForegroundColor Yellow
$segments = @(
    @{ Name = "192.168.100.x (Server)"; TestIP = "192.168.100.1" },
    @{ Name = "192.168.10.x  (LAN 2)"; TestIP = "192.168.10.1" },
    @{ Name = "192.168.20.x  (LAN 3)"; TestIP = "192.168.20.1" }
)

foreach ($seg in $segments) {
    $result = Test-Connection $seg.TestIP -Count 1 -Quiet -ErrorAction SilentlyContinue
    if ($result) {
        Write-Host "  $($seg.Name) -> Gateway $($seg.TestIP) REACHABLE" -ForegroundColor Green
    } else {
        Write-Host "  $($seg.Name) -> Gateway $($seg.TestIP) UNREACHABLE" -ForegroundColor Red
        Write-Host "         (Router mungkin belum routing ke segmen ini)" -ForegroundColor DarkYellow
    }
}
Write-Host ""

# 5. Cek Windows Firewall status
Write-Host "[5] Windows Firewall Status:" -ForegroundColor Yellow
try {
    $fwProfiles = Get-NetFirewallProfile -ErrorAction Stop
    foreach ($p in $fwProfiles) {
        $status = if ($p.Enabled) { "AKTIF" } else { "MATI" }
        $color = if ($p.Enabled) { "Yellow" } else { "Green" }
        Write-Host "  $($p.Name): $status" -ForegroundColor $color
    }
} catch {
    Write-Host "  (Perlu Administrator untuk cek detail firewall)" -ForegroundColor DarkYellow
}
Write-Host ""

# 6. Cek Apache config
Write-Host "[6] Apache XAMPP Config:" -ForegroundColor Yellow
$httpdConf = "C:\xampp\apache\conf\httpd.conf"
if (Test-Path $httpdConf) {
    $listenLines = Get-Content $httpdConf | Select-String "^Listen " 
    foreach ($line in $listenLines) {
        Write-Host "  $($line.Line)" -ForegroundColor White
        if ($line.Line -match "Listen\s+(\d+)$" -or $line.Line -match "Listen\s+0\.0\.0\.0") {
            Write-Host "    -> Bind ke SEMUA interface (OK)" -ForegroundColor Green
        } elseif ($line.Line -match "Listen\s+\d+\.\d+\.\d+\.\d+") {
            Write-Host "    -> Bind ke IP SPESIFIK (mungkin perlu diubah)" -ForegroundColor Yellow
        }
    }
} else {
    Write-Host "  httpd.conf tidak ditemukan di $httpdConf" -ForegroundColor Red
}
Write-Host ""

# 7. Summary & Rekomendasi
Write-Host "==========================================" -ForegroundColor Cyan
Write-Host " REKOMENDASI:" -ForegroundColor Cyan
Write-Host "==========================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "Agar semua segmen bisa akses http://192.168.100.50:" -ForegroundColor White
Write-Host ""
Write-Host "1. JALANKAN firewall script sebagai Administrator:" -ForegroundColor Yellow
Write-Host "   PowerShell (Admin) -> cd project -> .\setup-firewall-multisegment.ps1" -ForegroundColor White
Write-Host ""
Write-Host "2. PASTIKAN router/switch L3 sudah inter-VLAN routing:" -ForegroundColor Yellow
Write-Host "   Device di 192.168.10.x harus bisa ping 192.168.100.50" -ForegroundColor White
Write-Host "   Device di 192.168.20.x harus bisa ping 192.168.100.50" -ForegroundColor White
Write-Host "   Jika tidak bisa ping, hubungi admin jaringan" -ForegroundColor White
Write-Host ""
Write-Host "3. TEST dari device di segmen lain:" -ForegroundColor Yellow
Write-Host "   Buka browser -> http://192.168.100.50" -ForegroundColor White
Write-Host ""
