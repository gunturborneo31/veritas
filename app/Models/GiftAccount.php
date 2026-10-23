<?php

namespace App\Models;

use App\Enums\GiftKind;
use Database\Factories\GiftAccountFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Tujuan amplop digital. KartuKita hanya menampilkan nomor rekening/e-wallet (tamu menyalin
 * lalu transfer sendiri); uang tidak pernah melewati KartuKita, jadi tidak ada dana titipan.
 */
#[Fillable(['greeting_id', 'kind', 'provider', 'account_number', 'account_holder', 'address', 'position'])]
class GiftAccount extends Model
{
    /** @use HasFactory<GiftAccountFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'kind' => GiftKind::class,
            'position' => 'integer',
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
     * @return array<string, mixed>
     */
    public function toPublicArray(): array
    {
        return [
            'id' => $this->id,
            'kind' => $this->kind->value,
            'kind_label' => $this->kind->label(),
            'provider' => $this->provider,
            'account_number' => $this->account_number,
            'account_holder' => $this->account_holder,
            'address' => $this->address,
        ];
    }
}
