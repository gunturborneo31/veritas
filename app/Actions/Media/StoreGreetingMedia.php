<?php

namespace App\Actions\Media;

use App\Enums\MediaKind;
use App\Enums\MediaStatus;
use App\Exceptions\GreetingLockedException;
use App\Jobs\ProcessGreetingMedia;
use App\Models\Greeting;
use App\Models\Media;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

class StoreGreetingMedia
{
    /**
     * Simpan file mentah secara privat lalu antrekan kompresi.
     * Undangan dalam masa revisi boleh menambah foto (media lama tetap dipakai snapshot sebelumnya).
     */
    public function handle(Greeting $greeting, UploadedFile $file, MediaKind $kind): Media
    {
        if (! $greeting->isEditable() && ! $greeting->isRevisable()) {
            throw GreetingLockedException::forGreeting($greeting);
        }

        $disk = config('kartukita.media.disk');
        $name = Str::ulid()->toBase32().'.'.$file->extension();
        $path = $file->storeAs("greetings/{$greeting->public_id}", $name, $disk);

        $media = $greeting->media()->create([
            'kind' => $kind,
            'status' => MediaStatus::Menunggu,
            'disk' => $disk,
            'path' => $path,
            'original_name' => Str::limit($file->getClientOriginalName(), 120, ''),
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
        ]);

        ProcessGreetingMedia::dispatch($media);

        return $media;
    }
}
