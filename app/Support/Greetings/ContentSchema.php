<?php

namespace App\Support\Greetings;

use App\Models\Gate;
use App\Models\Greeting;
use Illuminate\Validation\Rule;

/**
 * Menerjemahkan skema komponen (manifest template) menjadi aturan validasi
 * dan pemeriksaan kelengkapan. Inilah yang membuat template baru cukup
 * berupa manifest tanpa kode aplikasi baru.
 *
 * Isi ucapan disimpan sebagai: ['components' => [<slot key> => [<field> => <value>]]].
 */
class ContentSchema
{
    public const TYPE_TEXT = 'text';

    public const TYPE_TEXTAREA = 'textarea';

    public const TYPE_DATE = 'date';

    public const TYPE_DATETIME = 'datetime';

    public const TYPE_IMAGE = 'image';

    public const TYPE_IMAGES = 'images';

    public const TYPE_AUDIO = 'audio';

    public const TYPE_MUSIC = 'music';

    public const TYPE_LIST = 'list';

    public const TYPE_QUIZ = 'quiz';

    public const TYPE_PIN = 'pin';

    public const TYPE_TOGGLE = 'toggle';

    /**
     * Titik lokasi di peta: [{name, lat, lng, caption?, media?}]. Tiap titik butuh teks, foto, atau keduanya.
     */
    public const TYPE_LOCATIONS = 'locations';

    /**
     * Lagu dari Spotify/YouTube yang diputar lewat pemutar resmi: {provider, id, title, artist?, image?}.
     */
    public const TYPE_SONG = 'song';

    /**
     * Pola ID per penyedia: ID lagu Spotify (22 karakter base62) dan ID video YouTube (11 karakter).
     *
     * @var array<string, string>
     */
    public const SONG_ID_PATTERNS = [
        'spotify' => '/^[A-Za-z0-9]{22}$/',
        'youtube' => '/^[A-Za-z0-9_-]{11}$/',
    ];

    /**
     * Aturan validasi untuk autosave. Semua field boleh kosong; kelengkapan
     * baru diperiksa saat pratinjau/checkout lewat {@see missingFields()}.
     *
     * @param  list<array<string, mixed>>  $manifest
     * @return array<string, mixed>
     */
    public function draftRules(array $manifest, Greeting $greeting): array
    {
        $rules = ['content' => ['nullable', 'array'], 'content.components' => ['nullable', 'array']];

        foreach ($manifest as $slot) {
            $base = "content.components.{$slot['key']}";
            $rules[$base] = ['nullable', 'array'];

            foreach ($slot['schema'] as $field) {
                foreach ($this->fieldRules("{$base}.{$field['name']}", $field, $greeting) as $path => $fieldRules) {
                    $rules[$path] = $fieldRules;
                }
            }
        }

        return $rules;
    }

    /**
     * Buang key yang tidak dikenal skema dan pisahkan field PIN (write-only).
     *
     * @param  list<array<string, mixed>>  $manifest
     * @param  array<string, mixed>  $content
     * @return array{content: array{components: array<string, array<string, mixed>>}, pins: array<string, ?string>}
     */
    public function sanitize(array $manifest, array $content): array
    {
        $clean = ['components' => []];
        $pins = [];

        foreach ($manifest as $slot) {
            $values = $content['components'][$slot['key']] ?? [];

            foreach ($slot['schema'] as $field) {
                if (! array_key_exists($field['name'], $values)) {
                    continue;
                }

                if ($field['type'] === self::TYPE_PIN) {
                    $pins[$slot['key']] = $values[$field['name']];

                    continue;
                }

                $clean['components'][$slot['key']][$field['name']] = $values[$field['name']];
            }
        }

        return ['content' => $clean, 'pins' => $pins];
    }

    /**
     * Daftar field wajib yang belum diisi, lengkap dengan nama bagian (untuk daftar periksa di
     * Studio, pratinjau, dan checkout) dan keterangan singkat bila ada jumlah minimal.
     *
     * @param  list<array<string, mixed>>  $manifest
     * @return list<array{slot: string, field: string, label: string, section: string, hint: ?string}>
     */
    public function missingFields(array $manifest, Greeting $greeting): array
    {
        $missing = [];

        // Undangan ditujukan ke banyak tamu, jadi yang wajib adalah nama tuan rumah dan minimal satu acara.
        $baseFields = $greeting->isInvitation()
            ? ['sender_name' => 'Nama mempelai / tuan rumah', 'occasion' => 'Jenis acara']
            : ['recipient_name' => 'Nama penerima', 'occasion' => 'Momen'];
        $baseSection = $greeting->isInvitation() ? 'Tuan Rumah & Acara' : 'Nama & Momen Spesial';

        foreach ($baseFields as $attribute => $label) {
            if (blank($greeting->{$attribute})) {
                $missing[] = ['slot' => 'dasar', 'field' => $attribute, 'label' => $label, 'section' => $baseSection, 'hint' => null];
            }
        }

        if ($greeting->isInvitation() && ! $greeting->invitationEvents()->exists()) {
            $missing[] = ['slot' => 'acara', 'field' => 'events', 'label' => 'Detail acara', 'section' => 'Acara, Amplop & Daftar Tamu', 'hint' => 'minimal 1 acara (akad, resepsi, …)'];
        }

        $content = $greeting->content['components'] ?? [];

        foreach ($manifest as $slot) {
            if (! $slot['required'] || ! $this->isSlotEnabled($slot, $content)) {
                continue;
            }

            foreach ($slot['schema'] as $field) {
                if (! ($field['required'] ?? false)) {
                    continue;
                }

                $value = $content[$slot['key']][$field['name']] ?? null;

                $isFilled = match ($field['type']) {
                    self::TYPE_PIN => $greeting->gate instanceof Gate,
                    self::TYPE_IMAGES, self::TYPE_LIST, self::TYPE_QUIZ => is_array($value) && count($value) >= ($field['min'] ?? 1),
                    self::TYPE_TOGGLE => $value !== null,
                    self::TYPE_LOCATIONS => $this->completeLocationCount($value) >= ($field['min'] ?? 1),
                    self::TYPE_SONG => is_array($value) && filled($value['provider'] ?? null) && filled($value['id'] ?? null),
                    default => filled($value),
                };

                if (! $isFilled) {
                    $missing[] = [
                        'slot' => $slot['key'],
                        'field' => $field['name'],
                        'label' => $field['label'] ?? $field['name'],
                        'section' => $slot['title'] ?? $slot['name'],
                        'hint' => $this->minimumHint($field),
                    ];
                }
            }
        }

        return $missing;
    }

    /**
     * Keterangan jumlah minimal untuk field berisi banyak item (foto, daftar, kuis, titik peta).
     *
     * @param  array<string, mixed>  $field
     */
    private function minimumHint(array $field): ?string
    {
        $unit = match ($field['type']) {
            self::TYPE_IMAGES => 'foto',
            self::TYPE_LIST => 'isian',
            self::TYPE_QUIZ => 'pertanyaan',
            self::TYPE_LOCATIONS => 'titik lengkap (nama, lokasi, dan teks/foto)',
            default => null,
        };

        return $unit === null ? null : 'minimal '.($field['min'] ?? 1).' '.$unit;
    }

    /**
     * Slot opsional (mis. gembok) memakai field toggle bernama `enabled`.
     *
     * @param  array<string, mixed>  $slot
     * @param  array<string, array<string, mixed>>  $content
     */
    public function isSlotEnabled(array $slot, array $content): bool
    {
        $hasToggle = collect($slot['schema'])->contains(
            fn (array $field): bool => $field['name'] === 'enabled' && $field['type'] === self::TYPE_TOGGLE,
        );

        return ! $hasToggle || (bool) ($content[$slot['key']]['enabled'] ?? false);
    }

    /**
     * @param  array<string, mixed>  $field
     * @return array<string, mixed>
     */
    private function fieldRules(string $path, array $field, Greeting $greeting): array
    {
        $mediaExists = fn (string $kind) => Rule::exists('media', 'public_id')
            ->where('greeting_id', $greeting->id)
            ->where('kind', $kind);

        return match ($field['type']) {
            self::TYPE_TEXT => [$path => ['nullable', 'string', 'max:'.($field['max'] ?? 120)]],
            self::TYPE_TEXTAREA => [$path => ['nullable', 'string', 'max:'.($field['max'] ?? 1500)]],
            self::TYPE_DATE => [$path => ['nullable', 'date_format:Y-m-d']],
            self::TYPE_DATETIME => [$path => ['nullable', 'date']],
            self::TYPE_TOGGLE => [$path => ['nullable', 'boolean']],
            self::TYPE_IMAGE => [$path => ['nullable', 'string', $mediaExists('image')]],
            self::TYPE_AUDIO => [$path => ['nullable', 'string', $mediaExists('audio')]],
            self::TYPE_IMAGES => [
                $path => ['nullable', 'array', 'max:'.($field['max'] ?? 12)],
                "{$path}.*.media" => ['required', 'string', $mediaExists('image')],
                "{$path}.*.caption" => ['nullable', 'string', 'max:80'],
            ],
            self::TYPE_MUSIC => [$path => ['nullable', 'integer', Rule::exists('music_tracks', 'id')->where('is_active', true)]],
            self::TYPE_LIST => [
                $path => ['nullable', 'array', 'max:'.($field['max'] ?? 10)],
                "{$path}.*" => ['nullable', 'string', 'max:'.($field['item_max'] ?? 300)],
            ],
            self::TYPE_QUIZ => [
                $path => ['nullable', 'array', 'max:'.($field['max'] ?? 5)],
                "{$path}.*.question" => ['required', 'string', 'max:150'],
                "{$path}.*.options" => ['required', 'array', 'min:2', 'max:4'],
                "{$path}.*.options.*" => ['required', 'string', 'max:60'],
                "{$path}.*.answer" => ['required', 'integer', 'min:0', 'max:3'],
            ],
            self::TYPE_PIN => [$path => ['nullable', 'string', 'regex:/^\d{'.($field['min'] ?? 4).','.($field['max'] ?? 6).'}$/']],
            self::TYPE_LOCATIONS => [
                $path => ['nullable', 'array', 'max:'.($field['max'] ?? 3)],
                "{$path}.*" => ['array'],
                "{$path}.*.name" => ['nullable', 'string', 'max:80'],
                "{$path}.*.lat" => ['nullable', 'numeric', 'between:-90,90'],
                "{$path}.*.lng" => ['nullable', 'numeric', 'between:-180,180'],
                "{$path}.*.caption" => ['nullable', 'string', 'max:200'],
                "{$path}.*.media" => ['nullable', 'string', $mediaExists('image')],
            ],
            self::TYPE_SONG => [
                $path => ['nullable', 'array'],
                "{$path}.provider" => ["required_with:{$path}", Rule::in(array_keys(self::SONG_ID_PATTERNS))],
                "{$path}.id" => ["required_with:{$path}", 'string', 'regex:/^([A-Za-z0-9]{22}|[A-Za-z0-9_-]{11})$/'],
                "{$path}.title" => ['nullable', 'string', 'max:200'],
                "{$path}.artist" => ['nullable', 'string', 'max:200'],
                "{$path}.image" => ['nullable', 'url', 'max:500', 'regex:#^https://(i\.scdn\.co|image-cdn-[a-z0-9-]+\.spotifycdn\.com|i\.ytimg\.com|img\.youtube\.com)/#'],
            ],
            default => [$path => ['prohibited']],
        };
    }

    /**
     * Titik dianggap lengkap bila punya nama, koordinat, dan setidaknya teks atau foto.
     */
    public function completeLocationCount(mixed $value): int
    {
        return collect(is_array($value) ? $value : [])
            ->filter(fn (mixed $place): bool => is_array($place)
                && filled($place['name'] ?? null)
                && is_numeric($place['lat'] ?? null)
                && is_numeric($place['lng'] ?? null)
                && (filled($place['caption'] ?? null) || filled($place['media'] ?? null)))
            ->count();
    }
}
