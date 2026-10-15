<?php

namespace App\Models;

use App\Enums\PromoKind;
use Database\Factories\PromoCodeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Kode potongan harga: promo admin, kode referral pengguna, atau voucher hadiah referral.
 */
#[Fillable([
    'code', 'kind', 'label', 'discount_type', 'discount_value', 'max_uses', 'uses_count', 'first_order_only',
    'owner_user_id', 'restricted_to_user_id', 'starts_at', 'ends_at', 'is_active',
])]
class PromoCode extends Model
{
    /** @use HasFactory<PromoCodeFactory> */
    use HasFactory;

    public const TYPE_PERCENT = 'percent';

    public const TYPE_FIXED = 'fixed';

    protected function casts(): array
    {
        return [
            'kind' => PromoKind::class,
            'first_order_only' => 'boolean',
            'is_active' => 'boolean',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    /**
     * Kode selalu disimpan huruf besar agar pencocokan tidak peka huruf.
     */
    protected function setCodeAttribute(string $value): void
    {
        $this->attributes['code'] = strtoupper(trim($value));
    }

    /**
     * Besar potongan untuk subtotal tertentu (belum dibatasi harga minimum).
     */
    public function discountFor(int $subtotal): int
    {
        $discount = $this->discount_type === self::TYPE_PERCENT
            ? intdiv($subtotal * $this->discount_value, 100)
            : $this->discount_value;

        return min($discount, $subtotal);
    }

    public function describe(): string
    {
        return $this->discount_type === self::TYPE_PERCENT ? "{$this->discount_value}%" : 'Rp'.number_format($this->discount_value, 0, ',', '.');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    /**
     * @return HasMany<Order, $this>
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }
}
