<?php

namespace App\Models;

use App\Enums\MediaKind;
use App\Enums\MediaStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\URL;

/**
 * File foto/audio milik satu ucapan. Disimpan privat dan diakses lewat URL bertanda tangan.
 */
#[Fillable(['greeting_id', 'kind', 'status', 'disk', 'path', 'original_name', 'mime_type', 'size', 'meta'])]
class Media extends Model
{
    use HasUlids;

    protected function casts(): array
    {
        return [
            'kind' => MediaKind::class,
            'status' => MediaStatus::class,
            'meta' => 'array',
        ];
    }

    /**
     * @return list<string>
     */
    public function uniqueIds(): array
    {
        return ['public_id'];
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }

    /**
     * @return BelongsTo<Greeting, $this>
     */
    public function greeting(): BelongsTo
    {
        return $this->belongsTo(Greeting::class);
    }

    /**
     * URL bertanda tangan berbasis ID media (bukan path), agar tetap berlaku setelah file
     * dikompres dan berganti nama di latar belakang.
     */
    public function temporaryUrl(int $minutes = 120): string
    {
        return URL::temporarySignedRoute('media.file', now()->addMinutes($minutes), ['media' => $this->public_id]);
    }

    /**
     * @return array{id: string, kind: string, status: string, url: string, name: ?string}
     */
    public function toClientArray(): array
    {
        return [
            'id' => $this->public_id,
            'kind' => $this->kind->value,
            'status' => $this->status->value,
            'url' => $this->temporaryUrl(),
            'name' => $this->original_name,
        ];
    }
}
