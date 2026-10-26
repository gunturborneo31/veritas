<?php

// Hapus file foto/suara uji yang diunggah uji katalog (hanya milik kartu di database smoke).
$root = getenv('VERITAS_ROOT');
require $root.'/vendor/autoload.php';
$app = require $root.'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

use App\Models\Media;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Storage;

if (! str_contains((string) config('database.connections.sqlite.database'), 'smoke')) {
    fwrite(STDERR, "Menolak jalan: DB_DATABASE bukan database smoke.\n");
    exit(1);
}

$deleted = 0;

foreach (Media::query()->with('greeting')->get()->groupBy('greeting_id') as $items) {
    $first = $items->first();
    Storage::disk($first->disk)->deleteDirectory("greetings/{$first->greeting->public_id}");
    $deleted += $items->count();
}

echo "Berkas uji dihapus: {$deleted} media.".PHP_EOL;
