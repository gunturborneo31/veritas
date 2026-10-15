<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Dicatat hanya ketika penerima menekan tombol "Buka" (bukan saat bot preview memuat link).
 */
#[Fillable(['greeting_id', 'visitor_hash', 'user_agent', 'opened_at'])]
class OpenEvent extends Model
{
    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'opened_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Greeting, $this>
     */
    public function greeting(): BelongsTo
    {
        return $this->belongsTo(Greeting::class);
    }
}
