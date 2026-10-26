# Uji katalog dari hulu ke hilir untuk SEMUA template. Database & server terpisah; tidak menyentuh data lokal/produksi.
# Pakai: powershell -File run-catalog.ps1 [-SkipBuild]
param(
    [string]$ProjectRoot = (Resolve-Path (Join-Path $PSScriptRoot '..\..')).Path,
    [int]$Port = 8765,
    [switch]$SkipBuild
)

$ErrorActionPreference = 'Stop'
$here = Split-Path -Parent $MyInvocation.MyCommand.Path
$database = Join-Path $here 'catalog-smoke.sqlite'
$cardsFile = Join-Path $here 'catalog-cards.json'
$audioFile = Join-Path $here 'suara-uji.mp3'

if (-not $SkipBuild) {
    Push-Location $ProjectRoot; npm run build | Out-Null; Pop-Location
}

$env:VERITAS_ROOT = $ProjectRoot
$env:APP_ENV = 'local'
$env:APP_DEBUG = 'true'
$env:APP_URL = "http://127.0.0.1:$Port"
$env:DB_CONNECTION = 'sqlite'
$env:DB_DATABASE = $database
$env:SESSION_DRIVER = 'file'
$env:CACHE_STORE = 'array'
$env:QUEUE_CONNECTION = 'sync'
$env:MAIL_MAILER = 'log'
$env:KARTUKITA_PAYMENT_MODE = 'manual'
$env:KARTUKITA_MANUAL_PROOF_CHANNEL = 'upload'
# Di PowerShell `$env:X = ''` MENGHAPUS variabel; `(empty)` dibaca Laravel sebagai string kosong.
$env:TELEGRAM_BOT_TOKEN = '(empty)'
$env:TELEGRAM_ADMIN_CHAT_ID = '(empty)'
$env:GOOGLE_ANALYTICS_ID = '(empty)'

php (Join-Path $here 'check-external.php')
if ($LASTEXITCODE -ne 0) { throw 'Dibatalkan: Telegram/Analytics masih aktif.' }

function Stop-SmokeServer {
    Get-CimInstance Win32_Process -Filter "Name='php.exe'" |
        Where-Object { $_.CommandLine -like "*-S 127.0.0.1:$Port*" } |
        ForEach-Object { Stop-Process -Id $_.ProcessId -Force -ErrorAction SilentlyContinue }
}

if (-not (Test-Path $audioFile)) {
    ffmpeg -loglevel error -f lavfi -i "sine=frequency=440:duration=3" -c:a libmp3lame -b:a 64k $audioFile
}

if (Test-Path $database) { Remove-Item $database -Force }
New-Item -ItemType File $database | Out-Null

Write-Host 'Menyiapkan kartu untuk setiap template (unggah foto & suara)...'
php (Join-Path $here 'catalog-setup.php') $audioFile | Out-File -Encoding utf8 $cardsFile
if ($LASTEXITCODE -ne 0) { throw 'Setup katalog gagal.' }

Stop-SmokeServer
Start-Process php -ArgumentList '-S', "127.0.0.1:$Port", '-t', (Join-Path $ProjectRoot 'public'), (Join-Path $here 'router.php') -WorkingDirectory (Join-Path $ProjectRoot 'public') -WindowStyle Hidden | Out-Null

try {
    Start-Sleep -Milliseconds 800
    $env:SMOKE_URL = "http://127.0.0.1:$Port"
    node (Join-Path $here 'catalog.mjs') $cardsFile
    $code = $LASTEXITCODE
} finally {
    Stop-SmokeServer
    php (Join-Path $here 'catalog-cleanup.php')
}

exit $code
