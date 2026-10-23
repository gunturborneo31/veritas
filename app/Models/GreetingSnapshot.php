<?php

namespace App\Models;

use App\Exceptions\GreetingLockedException;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Versi permanen ucapan setelah lunas. Hanya bisa dibuat, tidak bisa diubah atau dihapus.
 * Revisi undangan menambah baris baru dengan nomor `revision` berikutnya.
 */
#[Fillable(['greeting_id', 'template_id', 'revision', 'recipient_name', 'sender_name', 'occasion', 'components', 'content', 'content_hash'])]
class GreetingSnapshot extends Model
{
    public const UPDATED_AT = null;

    protected static function booted(): void
    {
        static::updating(function (GreetingSnapshot $snapshot): never {
            throw GreetingLockedException::forSnapshot($snapshot);
        });

        static::deleting(function (GreetingSnapshot $snapshot): never {
            throw GreetingLockedException::forSnapshot($snapshot);
        });
    }

    protected function casts(): array
    {
        return [
            'components' => 'array',
            'content' => 'array',
            'revision' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Greeting, $this>
     */
    public function greeting(): BelongsTo
    {
        return $this->belongsTo(Greeting::class);
    }

    /**
     * @return BelongsTo<Template, $this>
     */
    public function template(): BelongsTo
    {
        return $this->belongsTo(Template::class);
    }
}
