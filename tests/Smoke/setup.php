<?php

/**
 * Siapkan database smoke test yang terpisah (DB_DATABASE dari env), lalu isi data minimal:
 * akun admin, satu template ucapan, dan satu template undangan.
 */
$root = getenv('VERITAS_ROOT');
require $root.'/vendor/autoload.php';
$app = require $root.'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

use App\Enums\GreetingType;
use App\Models\Component;
use App\Models\Template;
use App\Models\User;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;

if (! str_contains((string) config('database.connections.sqlite.database'), 'smoke')) {
    fwrite(STDERR, "Menolak jalan: DB_DATABASE bukan database smoke.\n");
    exit(1);
}

Artisan::call('migrate:fresh', ['--force' => true]);

User::factory()->admin()->create(['name' => 'Admin Smoke', 'email' => 'admin@smoke.test', 'password' => Hash::make('password')]);

$components = [
    'sampul' => Component::factory()->create([
        'key' => 'sampul',
        'schema' => [['name' => 'title', 'type' => 'text', 'label' => 'Judul', 'required' => true]],
    ]),
    'gembok' => Component::factory()->pinGate()->create(),
    'surat' => Component::factory()->create([
        'key' => 'surat',
        'schema' => [['name' => 'body', 'type' => 'textarea', 'label' => 'Isi surat', 'required' => true]],
    ]),
];

$greetingTemplate = Template::factory()->withComponents($components)->create(['name' => 'Smoke Ucapan', 'slug' => 'smoke-ucapan', 'price' => 29000]);
$invitationTemplate = Template::factory()->withComponents($components)->create(['name' => 'Smoke Undangan', 'slug' => 'smoke-undangan', 'price' => 49000, 'type' => GreetingType::Undangan]);

echo json_encode(['greeting_template' => $greetingTemplate->slug, 'invitation_template' => $invitationTemplate->slug]).PHP_EOL;
