<?php

namespace App\Models;

use Database\Factories\MusicTrackFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

/**
 * Musik latar dari pustaka internal berlisensi. Pengguna tidak bisa mengunggah lagu sendiri.
 */
#[Fillable(['title', 'artist', 'mood', 'duration_seconds', 'path', 'license_note', 'is_active'])]
class MusicTrack extends Model
{
    /** @use HasFactory<MusicTrackFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    #[Scope]
    protected function active(Builder $query): void
    {
        $query->where('is_active', true);
    }

    public function url(): string
    {
        return Storage::disk('public')->url($this->path);
    }

    /**
     * @return array{id: int, title: string, artist: ?string, mood: ?string, duration: int, url: string}
     */
    public function toPlayerArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'artist' => $this->artist,
            'mood' => $this->mood,
            'duration' => $this->duration_seconds,
            'url' => $this->url(),
        ];
    }
}
