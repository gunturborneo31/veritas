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
#[Fillable(['title', 'artist', 'mood', 'category', 'duration_seconds', 'path', 'license_note', 'is_active'])]
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

    /**
     * Pustaka bawaan berupa file statis di public/ (mis. /audio/latar/carefree.mp3) agar ikut ter-deploy lewat git;
     * path tanpa garis miring di depan berada di disk public (storage).
     */
    public function url(): string
    {
        if (str_starts_with($this->path, '/')) {
            return asset(ltrim($this->path, '/'));
        }

        return Storage::disk('public')->url($this->path);
    }

    /**
     * `credit` = keterangan lisensi yang wajib ditampilkan (mis. atribusi CC BY untuk musik Kevin MacLeod).
     *
     * @return array{id: int, title: string, artist: ?string, mood: ?string, category: ?string, duration: int, url: string, credit: ?string}
     */
    public function toPlayerArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'artist' => $this->artist,
            'mood' => $this->mood,
            'category' => $this->category,
            'duration' => $this->duration_seconds,
            'url' => $this->url(),
            'credit' => $this->license_note,
        ];
    }
}
