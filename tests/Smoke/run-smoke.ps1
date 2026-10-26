# Smoke test end-to-end di Chrome sungguhan: ucapan & undangan dari buat → bayar → konfirmasi admin → tayang,
# termasuk tamu, RSVP, ucapan, check-in, revisi setelah dibagikan, dan edit oleh admin.
# Database & server terpisah (port 8765); tidak menyentuh database lokal/produksi, Telegram & Analytics dimatikan.
# Butuh: PHP, Node 22+, Google Chrome. Jalankan sebelum deploy:
#   powershell -ExecutionPolicy Bypass -File tests\Smoke\run-smoke.ps1 [-SkipBuild]
# Uji SEMUA template katalog (±15 menit): tests\Smoke\run-catalog.ps1
param(
    [string]$ProjectRoot = (Resolve-Path (Join-Path $PSScriptRoot '..\..')).Path,
    [int]$Port = 8765,
    [switch]$SkipBuild
)

$ErrorActionPreference = 'Stop'
$here = Split-Path -Parent $MyInvocation.MyCommand.Path
$database = Join-Path $here 'smoke.sqlite'

if (-not $SkipBuild) {
    Write-Host 'Build aset frontend (sama seperti di server)...'
    Push-Location $ProjectRoot
    npm run build | Out-Null
    Pop-Location
}

# Variabel env proses menang atas .env (Dotenv tidak menimpa env yang sudah ada).
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
# Layanan luar WAJIB mati agar smoke test tidak mengirim notifikasi Telegram / data Analytics sungguhan.
# Catatan: di PowerShell `$env:X = ''` justru MENGHAPUS variabel (lalu .env asli yang terpakai),
# jadi dipakai nilai khusus Laravel `(empty)` yang dibaca env() sebagai string kosong.
$env:TELEGRAM_BOT_TOKEN = '(empty)'
$env:TELEGRAM_ADMIN_CHAT_ID = '(empty)'
$env:GOOGLE_ANALYTICS_ID = '(empty)'

php (Join-Path $here 'check-external.php')
if ($LASTEXITCODE -ne 0) { throw 'Smoke test dibatalkan: Telegram/Analytics masih aktif.' }

if (Test-Path $database) { Remove-Item $database -Force }
New-Item -ItemType File $database | Out-Null

Write-Host 'Siapkan database smoke...'
php (Join-Path $here 'setup.php')
if ($LASTEXITCODE -ne 0) { throw 'Setup database smoke gagal.' }

# `php` bisa berupa shim (mis. Herd) yang menjalankan php.exe sebagai proses anak, jadi server dimatikan lewat command line-nya.
function Stop-SmokeServer {
    Get-CimInstance Win32_Process -Filter "Name='php.exe'" |
        Where-Object { $_.CommandLine -like "*-S 127.0.0.1:$Port*" } |
        ForEach-Object { Stop-Process -Id $_.ProcessId -Force -ErrorAction SilentlyContinue }
}

Stop-SmokeServer
$router = Join-Path $here 'router.php'
$server = Start-Process php -ArgumentList '-S', "127.0.0.1:$Port", '-t', (Join-Path $ProjectRoot 'public'), $router -WorkingDirectory (Join-Path $ProjectRoot 'public') -PassThru -WindowStyle Hidden

try {
    Start-Sleep -Milliseconds 800
    $env:SMOKE_URL = "http://127.0.0.1:$Port"
    node (Join-Path $here 'run.mjs')
    $code = $LASTEXITCODE
} finally {
    Stop-SmokeServer
}

exit $code
