# Setup Ngrok untuk HTTPS access ke Hospital Attendance System

Write-Host "================================" -ForegroundColor Cyan
Write-Host "Setup NGROK untuk Mobile Access" -ForegroundColor Cyan
Write-Host "================================" -ForegroundColor Cyan
Write-Host ""

# Download ngrok
$ngrokUrl = "https://bin.equinox.io/c/bNyj1mQVY4c/ngrok-v3-stable-windows-amd64.zip"
$downloadPath = "$env:TEMP\ngrok.zip"
$extractPath = "C:\ngrok"

Write-Host "1. Checking if ngrok exists..." -ForegroundColor Yellow
if (Test-Path "$extractPath\ngrok.exe") {
    Write-Host "OK - Ngrok sudah ada di: $extractPath" -ForegroundColor Green
} else {
    Write-Host "Downloading ngrok..." -ForegroundColor Yellow
    try {
        $ProgressPreference = 'SilentlyContinue'
        Invoke-WebRequest -Uri $ngrokUrl -OutFile $downloadPath
        Write-Host "OK - Download selesai" -ForegroundColor Green
        
        Write-Host "Extracting ngrok..." -ForegroundColor Yellow
        if (Test-Path $extractPath) { Remove-Item $extractPath -Recurse -Force }
        New-Item -ItemType Directory -Path $extractPath -Force | Out-Null
        Expand-Archive -Path $downloadPath -DestinationPath $extractPath -Force
        Write-Host "OK - Extract selesai" -ForegroundColor Green
        
        Remove-Item $downloadPath -Force
    } catch {
        Write-Host "ERROR downloading ngrok: $_" -ForegroundColor Red
        exit 1
    }
}

Write-Host ""
Write-Host "2. Starting ngrok tunnel to localhost:8000..." -ForegroundColor Yellow

# Create ngrok config
$configDir = "$env:USERPROFILE\.ngrok2"
if (!(Test-Path $configDir)) { New-Item -ItemType Directory -Path $configDir -Force | Out-Null }

Write-Host "OK - Config folder ready" -ForegroundColor Green

Write-Host ""
Write-Host "======================================" -ForegroundColor Cyan
Write-Host "Ngrok sedang berjalan - lihat URL di bawah" -ForegroundColor Green
Write-Host "======================================" -ForegroundColor Cyan
Write-Host ""

# Run ngrok
& "$extractPath\ngrok.exe" http 8000
