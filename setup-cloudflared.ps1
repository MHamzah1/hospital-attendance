Write-Host "================================" -ForegroundColor Cyan
Write-Host "Setup CLOUDFLARED (Gratis, Tanpa Akun)" -ForegroundColor Cyan
Write-Host "================================" -ForegroundColor Cyan
Write-Host ""

# Download cloudflared
$cloudflareUrl = "https://github.com/cloudflare/cloudflared/releases/download/2024.1.4/cloudflared-windows-amd64.msi"
$downloadPath = "$env:TEMP\cloudflared.msi"
$installPath = "C:\Program Files\Cloudflare\Cloudflared"

Write-Host "1. Checking cloudflared..." -ForegroundColor Yellow
if (Get-Command cloudflared -ErrorAction SilentlyContinue) {
    Write-Host "OK - Cloudflared sudah terinstall" -ForegroundColor Green
} else {
    Write-Host "Download cloudflared..." -ForegroundColor Yellow
    try {
        $ProgressPreference = 'SilentlyContinue'
        Invoke-WebRequest -Uri $cloudflareUrl -OutFile $downloadPath -ErrorVariable $null
        Write-Host "OK - Download selesai" -ForegroundColor Green
        
        Write-Host "Installing cloudflared..." -ForegroundColor Yellow
        Start-Process msiexec.exe -ArgumentList "/i `"$downloadPath`" /quiet" -Wait
        Write-Host "OK - Install selesai" -ForegroundColor Green
        
        Remove-Item $downloadPath -Force -ErrorAction SilentlyContinue
    }
    catch {
        Write-Host "Note: Manual install mungkin diperlukan" -ForegroundColor Yellow
    }
}

Write-Host ""
Write-Host "2. Starting Cloudflared tunnel..." -ForegroundColor Yellow
Write-Host ""
Write-Host "======================================" -ForegroundColor Cyan
Write-Host "COPY URL di bawah ini ke HP Anda!" -ForegroundColor Green
Write-Host "======================================" -ForegroundColor Cyan
Write-Host ""

# Run cloudflared
cloudflared tunnel --url http://localhost:8000
