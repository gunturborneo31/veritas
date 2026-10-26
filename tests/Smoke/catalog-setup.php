<?php

/**
 * Siapkan uji katalog: seed katalog asli, lalu untuk setiap template buat satu draf dan satu kartu
 * terbit yang SEMUA isiannya terisi (foto & suara diunggah sungguhan, PIN, kuis, lokasi, lagu, dst).
 * Kartu diterbitkan lewat PlaceOrder + FulfillOrder seperti pesanan lunas. Keluaran: JSON per template.
 *
 * Pakai: php catalog-setup.php <file-audio-uji>
 */
$root = getenv('VERITAS_ROOT');
require $root.'/vendor/autoload.php';
$app = require $root.'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

use App\Actions\Greetings\CreateDraftGreeting;
use App\Actions\Greetings\SaveGreetingDraft;
use App\Actions\Media\StoreGreetingMedia;
use App\Actions\Orders\FulfillOrder;
use App\Actions\Orders\PlaceOrder;
use App\Enums\MediaKind;
use App\Models\Greeting;
use App\Models\MusicTrack;
use App\Models\Template;
use App\Models\User;
use App\Support\Greetings\ContentSchema;
use Database\Seeders\CatalogSeeder;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

if (! str_contains((string) config('database.connections.sqlite.database'), 'smoke')) {
    fwrite(STDERR, "Menolak jalan: DB_DATABASE bukan database smoke.\n");
    exit(1);
}

$audioFile = $argv[1];
Artisan::call('migrate:fresh', ['--force' => true]);
Artisan::call('db:seed', ['--class' => CatalogSeeder::class, '--force' => true]);

$buyer = User::factory()->create(['name' => 'Pembeli Katalog', 'email' => 'katalog@smoke.test', 'password' => Hash::make('password'), 'phone' => '081200000001']);
$track = MusicTrack::query()->where('is_active', true)->firstOrFail();
const PIN = '1402';

$upload = function (Greeting $greeting, MediaKind $kind) use ($buyer, $audioFile): string {
    $file = $kind === MediaKind::Image
        ? UploadedFile::fake()->image('foto-'.Str::random(4).'.jpg', 900, 700)
        : new UploadedFile($audioFile, 'suara.mp3', 'audio/mpeg', null, true);

    return app(StoreGreetingMedia::class)->handle($greeting, $file, $kind, $buyer)->public_id;
};

/** Nilai uji yang valid untuk satu isian, sesuai jenisnya. */
$valueFor = function (Greeting $greeting, array $slot, array $field) use ($upload, $track): mixed {
    $count = max($field['min'] ?? 1, $field['type'] === ContentSchema::TYPE_IMAGES ? 3 : 2);
    $count = min($count, $field['max'] ?? $count);
    $limit = fn (string $text, int $default) => Str::limit($text, ($field['max'] ?? $default) - 3, '');

    return match ($field['type']) {
        ContentSchema::TYPE_TEXT => match (true) {
            $field['name'] === 'question' => 'Tanggal jadian kita? (DDMM)',
            default => $limit("Uji {$field['label']}", 120),
        },
        ContentSchema::TYPE_TEXTAREA => $limit("Ini isi {$field['label']} untuk uji coba katalog. Semoga bahagia selalu.", 1500),
        ContentSchema::TYPE_DATE => now()->addDays(10)->format('Y-m-d'),
        // Kunci waktu dibuat sudah terbuka agar penerima bisa lanjut; hitung mundur menuju masa depan.
        ContentSchema::TYPE_DATETIME => str_contains($field['name'], 'unlock') ? now()->subHour()->toIso8601String() : now()->addDays(20)->toIso8601String(),
        ContentSchema::TYPE_TOGGLE => true,
        ContentSchema::TYPE_IMAGE => $upload($greeting, MediaKind::Image),
        ContentSchema::TYPE_IMAGES => collect(range(1, $count))->map(fn (int $i) => ['media' => $upload($greeting, MediaKind::Image), 'caption' => "Momen {$i} (202{$i})"])->all(),
        ContentSchema::TYPE_AUDIO => $upload($greeting, MediaKind::Audio),
        ContentSchema::TYPE_MUSIC => $track->id,
        ContentSchema::TYPE_LIST => collect(range(1, $count))->map(fn (int $i) => Str::limit("{$i}00 hari isi daftar ke-{$i}", ($field['item_max'] ?? 300) - 3, ''))->all(),
        ContentSchema::TYPE_QUIZ => collect(range(1, $count))->map(fn (int $i) => ['question' => "Pertanyaan {$i}?", 'options' => ['Merah', 'Biru', 'Hijau'], 'answer' => 1])->all(),
        ContentSchema::TYPE_PIN => PIN,
        ContentSchema::TYPE_LOCATIONS => collect(range(1, $count))->map(fn (int $i) => ['name' => "Tempat {$i}", 'lat' => -6.2 + $i / 100, 'lng' => 106.8 + $i / 100, 'caption' => "Kenangan di tempat {$i}"])->all(),
        ContentSchema::TYPE_SONG => ['provider' => 'youtube', 'id' => 'jNQXAC9IVRw', 'title' => 'Lagu Kita'],
        default => null,
    };
};

$makeCard = function (Template $template, bool $publish) use ($buyer, $valueFor): Greeting {
    $greeting = app(CreateDraftGreeting::class)->handle($template, $buyer, null);
    $manifest = $template->manifest();
    $components = [];

    foreach ($manifest as $slot) {
        foreach ($slot['schema'] as $field) {
            $value = $valueFor($greeting, $slot, $field);

            if ($value !== null) {
                $components[$slot['key']][$field['name']] = $value;
            }
        }
    }

    $base = $greeting->isInvitation()
        ? ['sender_name' => 'Raka & Dinda', 'occasion' => $template->sample_content['base']['occasion'] ?? 'Pernikahan']
        : ['recipient_name' => 'Sari', 'sender_name' => 'Budi', 'occasion' => $template->sample_content['base']['occasion'] ?? 'Ulang Tahun'];

    app(SaveGreetingDraft::class)->handle($greeting, [...$base, 'content' => ['components' => $components]]);

    if ($greeting->isInvitation()) {
        $greeting->invitationEvents()->create([
            'name' => 'Resepsi', 'starts_at' => now()->addDays(30)->setTime(10, 0), 'timezone' => 'Asia/Jakarta',
            'venue_name' => 'Gedung Serbaguna', 'address' => 'Jl. Merdeka No. 1, Jakarta', 'position' => 0,
        ]);
    }

    $greeting = $greeting->fresh();
    $missing = app(ContentSchema::class)->missingFields($manifest, $greeting);

    if ($missing !== []) {
        throw new RuntimeException('Isian wajib belum terpenuhi: '.collect($missing)->pluck('label')->join(', '));
    }

    if ($publish) {
        DB::transaction(function () use ($greeting, $buyer): void {
            app(FulfillOrder::class)->handle(app(PlaceOrder::class)->handle($greeting, $buyer));
        });
    }

    return $greeting->fresh();
};

$cards = [];

foreach (Template::query()->where('is_published', true)->orderBy('sort_order')->orderBy('id')->get() as $template) {
    try {
        $draft = $makeCard($template, false);
        $published = $makeCard($template, true);
        $cards[] = [
            'template' => $template->slug,
            'name' => $template->name,
            'type' => $template->type->value,
            'draft' => $draft->public_id,
            'slug' => $published->slug,
            'has_pin' => $published->gate !== null,
            'pin' => PIN,
        ];
    } catch (Throwable $exception) {
        $cards[] = ['template' => $template->slug, 'name' => $template->name, 'error' => $exception->getMessage()];
    }
}

echo json_encode($cards).PHP_EOL;
