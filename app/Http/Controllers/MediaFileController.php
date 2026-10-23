<?php

namespace App\Http\Controllers;

use App\Models\Media;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class MediaFileController extends Controller
{
    /**
     * Sajikan file media lewat URL bertanda tangan yang memakai ID media, bukan nama file.
     * Dengan begitu URL yang sudah dipegang browser tetap berlaku setelah foto dikompres
     * (file .jpg diganti .webp oleh ProcessGreetingMedia).
     */
    public function __invoke(Media $media): Response
    {
        $storage = Storage::disk($media->disk);

        abort_unless($storage->exists($media->path), 404);

        // Disk awan (mis. S3) menyajikan file sendiri lewat URL sementaranya.
        if (config("filesystems.disks.{$media->disk}.driver") !== 'local') {
            return redirect()->away($storage->temporaryUrl($media->path, now()->addMinutes(10)));
        }

        return $storage->response($media->path, headers: [
            'Content-Type' => $media->mime_type ?? 'application/octet-stream',
            'Cache-Control' => 'private, max-age=3600',
        ]);
    }
}
