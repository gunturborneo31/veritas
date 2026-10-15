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
     * Daftar field wajib yang belum diisi.
     *
     * @param  list<array<string, mixed>>  $manifest
     * @return list<array{slot: string, field: string, label: string}>
     */
    public function missingFields(array $manifest, Greeting $greeting): array
    {
        $missing = [];

        foreach (['recipient_name' => 'Nama penerima', 'occasion' => 'Momen'] as $attribute => $label) {
            if (blank($greeting->{$attribute})) {
                $missing[] = ['slot' => 'dasar', 'field' => $attribute, 'label' => $label];
            }
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
                    default => filled($value),
                };

                if (! $isFilled) {
                    $missing[] = ['slot' => $slot['key'], 'field' => $field['name'], 'label' => $field['label'] ?? $field['name']];
                }
            }
        }

        return $missing;
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
            default => [$path => ['prohibited']],
        };
    }
}
