<?php

use App\Support\Telegram\TelegramBot;
use Illuminate\Contracts\Console\Kernel;

// Pastikan layanan luar mati di lingkungan smoke test sebelum test dijalankan.
$root = getenv('VERITAS_ROOT') ?: dirname(__DIR__, 2);
require $root.'/vendor/autoload.php';
$app = require $root.'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$active = array_filter([
    'Telegram' => app(TelegramBot::class)->isConfigured(),
    'Google Analytics' => filled(config('services.google_analytics.measurement_id')),
]);

if ($active !== []) {
    fwrite(STDERR, 'Menolak jalan: layanan luar masih aktif: '.implode(', ', array_keys($active)).PHP_EOL);
    exit(1);
}

echo 'Layanan luar mati (Telegram, Google Analytics).'.PHP_EOL;
