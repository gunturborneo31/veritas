<?php

namespace App\Actions\Greetings;

use App\Enums\GreetingStatus;
use App\Models\Greeting;
use App\Models\Media;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DuplicateGreeting
{
    /**
     * Salin ucapan menjadi draf baru untuk penerima lain (mis. Lebaran ke banyak keluarga).
     * Satu pembayaran tetap untuk satu penerima: draf hasil salinan harus dibayar sendiri.
     * Media ikut disalin; PIN gembok dan jadwal buka tidak ikut disalin.
     */
    public function handle(Greeting $source): Greeting
    {
        $source->loadMissing('media');

        return DB::transaction(function () use ($source): Greeting {
            $copy = Greeting::create([
                'user_id' => $source->user_id,
                'template_id' => $source->template_id,
                'type' => $source->type,
                'status' => GreetingStatus::Draf,
                'recipient_name' => null,
                'sender_name' => $source->sender_name,
                'occasion' => $source->occasion,
                'content' => ['components' => []],
                'last_edited_at' => now(),
            ]);

            $mediaMap = $source->media->mapWithKeys(fn (Media $media): array => [
                $media->public_id => $this->copyMedia($media, $copy)->public_id,
            ])->all();

            $copy->update(['content' => $this->remapMedia($source->content ?? ['components' => []], $mediaMap)]);

            return $copy;
        });
    }

    private function copyMedia(Media $media, Greeting $copy): Media
    {
        $storage = Storage::disk($media->disk);
        $newPath = "greetings/{$copy->public_id}/".Str::ulid()->toBase32().'.'.pathinfo($media->path, PATHINFO_EXTENSION);

        if ($storage->exists($media->path)) {
            $storage->copy($media->path, $newPath);
        }

        return $copy->media()->create([
            ...$media->only(['kind', 'status', 'disk', 'original_name', 'mime_type', 'size', 'meta']),
            'path' => $newPath,
        ]);
    }

    /**
     * Ganti semua referensi ULID media lama di konten dengan media salinan.
     *
     * @param  array<string, mixed>  $content
     * @param  array<string, string>  $mediaMap
     * @return array<string, mixed>
     */
    private function remapMedia(array $content, array $mediaMap): array
    {
        array_walk_recursive($content, function (mixed &$value) use ($mediaMap): void {
            if (is_string($value) && isset($mediaMap[$value])) {
                $value = $mediaMap[$value];
            }
        });

        return $content;
    }
}
