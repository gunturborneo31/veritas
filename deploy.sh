#!/usr/bin/env bash
#
# Deploy KartuKita di server: jalankan dari folder proyek dengan `bash deploy.sh`.
# Urutan: ambil kode → dependensi produksi → build aset (JS/CSS) → migrasi → sampul → cache → restart antrean.
# Langkah build WAJIB: tanpa itu browser tetap memakai JS lama walau kode PHP sudah baru.
#
set -euo pipefail
cd "$(dirname "$0")"

step() { printf '\n\033[1;32m▶ %s\033[0m\n' "$1"; }

step 'Ambil kode terbaru'
git pull --ff-only

step 'Dependensi PHP (tanpa paket development)'
composer install --no-dev --optimize-autoloader --no-interaction

step 'Build aset frontend'
if command -v npm >/dev/null 2>&1; then
    npm ci --no-audit --no-fund
    npm run build
else
    echo '⚠️  npm tidak tersedia di server: jalankan `npm run build` di laptop, lalu unggah folder public/build/ (hapus yang lama dulu).'
fi

step 'Migrasi database'
php artisan migrate --force

step 'Tautan storage (musik, sampul unggahan)'
[ -e public/storage ] || php artisan storage:link

step 'Sampul katalog bawaan'
php artisan kartukita:install-covers

step 'Bersihkan & bangun ulang cache'
php artisan optimize:clear
php artisan optimize

step 'Muat ulang pemroses antrean'
php artisan queue:restart

printf '\n\033[1;32m✓ Deploy selesai.\033[0m Jangan lupa purge cache CDN di hPanel, lalu buka situs di jendela Incognito.\n'
