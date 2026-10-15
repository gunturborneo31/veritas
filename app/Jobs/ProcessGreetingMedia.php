<?php

namespace App\Jobs;

use App\Enums\MediaKind;
use App\Enums\MediaStatus;
use App\Models\Media;
use GdImage;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

/**
 * Kompresi media agar halaman penerima tetap ringan di jaringan lambat.
 * Foto diubah ke WebP dengan sisi terpanjang dibatasi.
 */
class ProcessGreetingMedia implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(public Media $media) {}

    public function handle(): void
    {
        if ($this->media->kind === MediaKind::Image) {
            $this->compressImage();
        }

        // TODO: kompres audio (mis. ke Opus/AAC via ffmpeg) setelah tool tersedia di server.

        $this->media->update(['status' => MediaStatus::Siap]);
    }

    public function failed(?Throwable $exception): void
    {
        $this->media->update(['status' => MediaStatus::Gagal]);
    }

    private function compressImage(): void
    {
        if (! function_exists('imagewebp')) {
            return;
        }

        $storage = Storage::disk($this->media->disk);
        $source = @imagecreatefromstring($storage->get($this->media->path));

        if (! $source instanceof GdImage) {
            return;
        }

        $image = $this->scaleDown($source, config('kartukita.media.image_max_dimension'));

        ob_start();
        imagewebp($image, null, config('kartukita.media.webp_quality'));
        $webp = ob_get_clean();

        $newPath = Str::beforeLast($this->media->path, '.').'.webp';
        $storage->put($newPath, $webp);

        if ($newPath !== $this->media->path) {
            $storage->delete($this->media->path);
        }

        $this->media->update([
            'path' => $newPath,
            'mime_type' => 'image/webp',
            'size' => strlen($webp),
            'meta' => ['width' => imagesx($image), 'height' => imagesy($image)],
        ]);
    }

    private function scaleDown(GdImage $image, int $maxDimension): GdImage
    {
        $width = imagesx($image);
        $height = imagesy($image);
        $ratio = $maxDimension / max($width, $height);

        if ($ratio >= 1) {
            return $image;
        }

        return imagescale($image, (int) round($width * $ratio), (int) round($height * $ratio));
    }
}
