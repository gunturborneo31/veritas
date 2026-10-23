<?php

namespace App\Models;

use Database\Factories\WishFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Ucapan & doa di buku tamu undangan. Berbeda dari Reply (balasan pribadi ke pengirim ucapan),
 * ucapan ini tampil untuk semua tamu sehingga pemilik bisa menyembunyikannya.
 */
#[Fillable(['greeting_id', 'guest_id', 'name', 'message', 'is_hidden'])]
class Wish extends Model
{
    /** @use HasFactory<WishFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'is_hidden' => 'boolean',
        ];
    }

    #[Scope]
    protected function visible(Builder $query): void
    {
        $query->where('is_hidden', false);
    }

    /**
     * @return BelongsTo<Greeting, $this>
     */
    public function greeting(): BelongsTo
    {
        return $this->belongsTo(Greeting::class);
    }

    /**
     * @return BelongsTo<Guest, $this>
     */
    public function guest(): BelongsTo
    {
        return $this->belongsTo(Guest::class);
    }
}
