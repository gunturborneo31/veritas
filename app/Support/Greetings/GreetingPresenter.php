<?php

namespace App\Support\Greetings;

use App\Models\Contribution;
use App\Models\Gate;
use App\Models\Greeting;
use App\Models\GreetingSnapshot;
use App\Models\Media;
use App\Models\MusicTrack;
use App\Models\Template;
use Illuminate\Support\Collection;

/**
 * Menyusun payload yang dirender oleh satu komponen "pemutar" yang sama di
 * Studio, Pratinjau, demo katalog, maupun halaman penerima.
 *
 * Referensi media (ULID) diubah menjadi URL bertanda tangan dan id musik
 * menjadi data trek; field PIN tidak pernah ikut dikirim.
 */
class GreetingPresenter
{
    /**
     * @return array<string, mixed>
     */
    public function forDraft(Greeting $greeting): array
    {
        $greeting->loadMissing('template', 'gate', 'contributions.media');
        $manifest = $greeting->template->manifest();

        $contributions = $greeting->contributions
            ->where('is_hidden', false)
            ->sortBy('id')
            ->map(fn (Contribution $contribution): array => [
                'name' => $contribution->contributor_name,
                'message' => $contribution->message,
                'photo' => $contribution->media?->toClientArray(),
            ])
            ->values()
            ->all();

        return [...$this->build(
            manifest: $manifest,
            content: $greeting->content ?? [],
            greetingId: $greeting->id,
            base: [
                'recipient_name' => $greeting->recipient_name,
                'sender_name' => $greeting->sender_name,
                'occasion' => $greeting->occasion,
            ],
            gate: $greeting->gate,
            theme: $greeting->template->resolvedTheme(),
        ), 'contributions' => $contributions];
    }

    /**
     * @return array<string, mixed>
     */
    public function forSnapshot(GreetingSnapshot $snapshot): array
    {
        return [...$this->build(
            manifest: $snapshot->components,
            content: $snapshot->content,
            greetingId: $snapshot->greeting_id,
            base: [
                'recipient_name' => $snapshot->recipient_name,
                'sender_name' => $snapshot->sender_name,
                'occasion' => $snapshot->occasion,
            ],
            gate: null,
            theme: $snapshot->template->resolvedTheme(),
        ), 'contributions' => $this->presentFrozenContributions($snapshot->content['contributions'] ?? [], $snapshot->greeting_id)];
    }

    /**
     * Tulisan patungan yang sudah dibekukan di snapshot (foto disimpan sebagai ULID media).
     *
     * @param  list<array{name: string, message: string, photo: ?string}>  $items
     * @return list<array{name: string, message: string, photo: ?array<string, mixed>}>
     */
    private function presentFrozenContributions(array $items, int $greetingId): array
    {
        $photoIds = array_filter(array_column($items, 'photo'));

        $media = $photoIds === []
            ? collect()
            : Media::query()->where('greeting_id', $greetingId)->whereIn('public_id', $photoIds)->get()->keyBy('public_id');

        return array_map(fn (array $item): array => [
            'name' => $item['name'],
            'message' => $item['message'],
            'photo' => $item['photo'] ? $media->get($item['photo'])?->toClientArray() : null,
        ], $items);
    }

    /**
     * Data contoh template untuk demo katalog (media berupa URL publik).
     *
     * @return array<string, mixed>
     */
    public function forTemplateSample(Template $template): array
    {
        $sample = $template->sample_content ?? [];

        return [
            'components' => $template->manifest(),
            'base' => $sample['base'] ?? [],
            'values' => $this->resolveMusic($template->manifest(), $this->resolveRelativeDates($template->manifest(), $sample['components'] ?? [])),
            'gate' => null,
            'theme' => $template->resolvedTheme(),
            'contributions' => $sample['contributions'] ?? [],
        ];
    }

    /**
     * Tanggal di data contoh boleh relatif (mis. "+23 days 10:00") agar demo hitung mundur tidak pernah kedaluwarsa.
     *
     * @param  list<array<string, mixed>>  $manifest
     * @param  array<string, array<string, mixed>>  $values
     * @return array<string, array<string, mixed>>
     */
    private function resolveRelativeDates(array $manifest, array $values): array
    {
        foreach ($manifest as $slot) {
            foreach ($slot['schema'] as $field) {
                $value = $values[$slot['key']][$field['name']] ?? null;

                if (! in_array($field['type'], [ContentSchema::TYPE_DATE, ContentSchema::TYPE_DATETIME], true) || ! is_string($value) || ! str_starts_with($value, '+')) {
                    continue;
                }

                $values[$slot['key']][$field['name']] = now()
                    ->modify($value)
                    ->format($field['type'] === ContentSchema::TYPE_DATE ? 'Y-m-d' : 'Y-m-d\TH:i');
            }
        }

        return $values;
    }

    /**
     * @param  list<array<string, mixed>>  $manifest
     * @param  array<string, mixed>  $content
     * @param  array<string, ?string>  $base
     * @param  array<string, mixed>  $theme
     * @return array<string, mixed>
     */
    private function build(array $manifest, array $content, int $greetingId, array $base, ?Gate $gate, array $theme): array
    {
        $values = $content['components'] ?? [];
        $media = $this->loadMedia($manifest, $values, $greetingId);

        foreach ($manifest as $slot) {
            foreach ($slot['schema'] as $field) {
                $value = $values[$slot['key']][$field['name']] ?? null;

                $values[$slot['key']][$field['name']] = match ($field['type']) {
                    ContentSchema::TYPE_IMAGE, ContentSchema::TYPE_AUDIO => $value ? $media->get($value) : null,
                    ContentSchema::TYPE_IMAGES => collect($value ?? [])
                        ->map(fn (array $item): array => [...$item, 'media' => $media->get($item['media'])])
                        ->filter(fn (array $item): bool => $item['media'] !== null)
                        ->values()
                        ->all(),
                    ContentSchema::TYPE_PIN => ['is_set' => $gate !== null, 'length' => $gate?->answer_length],
                    default => $value,
                };
            }
        }

        return [
            'components' => $manifest,
            'base' => $base,
            'values' => $this->resolveMusic($manifest, $values),
            'gate' => $gate?->toPublicArray(),
            'theme' => $theme,
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $manifest
     * @param  array<string, array<string, mixed>>  $values
     * @return Collection<string, array<string, mixed>>
     */
    private function loadMedia(array $manifest, array $values, int $greetingId): Collection
    {
        $ids = [];

        foreach ($manifest as $slot) {
            foreach ($slot['schema'] as $field) {
                $value = $values[$slot['key']][$field['name']] ?? null;

                if (in_array($field['type'], [ContentSchema::TYPE_IMAGE, ContentSchema::TYPE_AUDIO], true) && is_string($value)) {
                    $ids[] = $value;
                }

                if ($field['type'] === ContentSchema::TYPE_IMAGES && is_array($value)) {
                    $ids = [...$ids, ...array_column($value, 'media')];
                }
            }
        }

        if ($ids === []) {
            return collect();
        }

        return Media::query()
            ->where('greeting_id', $greetingId)
            ->whereIn('public_id', array_unique($ids))
            ->get()
            ->mapWithKeys(fn (Media $media): array => [$media->public_id => $media->toClientArray()]);
    }

    /**
     * @param  list<array<string, mixed>>  $manifest
     * @param  array<string, array<string, mixed>>  $values
     * @return array<string, array<string, mixed>>
     */
    private function resolveMusic(array $manifest, array $values): array
    {
        $musicFields = [];

        foreach ($manifest as $slot) {
            foreach ($slot['schema'] as $field) {
                if ($field['type'] === ContentSchema::TYPE_MUSIC && isset($values[$slot['key']][$field['name']])) {
                    $musicFields[] = [$slot['key'], $field['name']];
                }
            }
        }

        if ($musicFields === []) {
            return $values;
        }

        $tracks = MusicTrack::query()
            ->whereIn('id', array_map(fn (array $path): mixed => $values[$path[0]][$path[1]], $musicFields))
            ->get()
            ->keyBy('id');

        foreach ($musicFields as [$slotKey, $fieldName]) {
            $values[$slotKey][$fieldName] = $tracks->get($values[$slotKey][$fieldName])?->toPlayerArray();
        }

        return $values;
    }
}
