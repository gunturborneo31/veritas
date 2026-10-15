<?php

namespace App\Support\Pricing;

use App\Enums\OrderStatus;
use App\Enums\PromoKind;
use App\Models\Order;
use App\Models\PromoCode;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Validasi pemakaian kode promo/referral/voucher dan pembuatan kode referral pengguna.
 */
class PromoCodes
{
    /**
     * Kembalikan kode yang sah untuk pengguna ini, atau lempar pesan yang ramah.
     */
    public function resolve(string $code, User $user): PromoCode
    {
        $promo = PromoCode::query()->where('code', strtoupper(trim($code)))->first();

        $error = match (true) {
            $promo === null, ! $promo->is_active => 'Kode tidak ditemukan atau sudah tidak berlaku.',
            $promo->starts_at?->isFuture() => 'Kode ini belum bisa dipakai.',
            $promo->ends_at?->isPast() => 'Kode ini sudah kedaluwarsa.',
            $promo->max_uses !== null && $promo->uses_count >= $promo->max_uses => 'Kuota kode ini sudah habis.',
            $promo->owner_user_id === $user->id => 'Kode referral milikmu sendiri tidak bisa dipakai. Bagikan ke teman, ya!',
            $promo->restricted_to_user_id !== null && $promo->restricted_to_user_id !== $user->id => 'Kode ini khusus untuk akun lain.',
            $promo->first_order_only && $this->hasPaidOrder($user) => 'Kode ini hanya untuk pesanan pertama.',
            $this->hasUsed($promo, $user) => 'Kamu sudah memakai kode ini.',
            default => null,
        };

        if ($error !== null) {
            throw ValidationException::withMessages(['promo_code' => $error]);
        }

        return $promo;
    }

    /**
     * Kode referral pribadi (dibuat sekali, dipakai seterusnya).
     */
    public function referralFor(User $user): PromoCode
    {
        return PromoCode::query()->firstOrCreate(
            ['owner_user_id' => $user->id, 'kind' => PromoKind::Referral],
            [
                'code' => $this->uniqueCode(Str::upper(Str::limit(Str::slug(Str::before($user->name, ' '), ''), 6, ''))),
                'label' => "Referral {$user->name}",
                'discount_type' => PromoCode::TYPE_FIXED,
                'discount_value' => config('kartukita.referral.referee_discount'),
                'first_order_only' => true,
                'is_active' => true,
            ],
        );
    }

    /**
     * Voucher hadiah sekali pakai untuk pemilik referral.
     */
    public function issueReward(User $owner): PromoCode
    {
        return PromoCode::create([
            'code' => $this->uniqueCode('HADIAH'),
            'kind' => PromoKind::Reward,
            'label' => 'Hadiah referral',
            'discount_type' => PromoCode::TYPE_FIXED,
            'discount_value' => config('kartukita.referral.referrer_reward'),
            'max_uses' => 1,
            'restricted_to_user_id' => $owner->id,
            'ends_at' => now()->addDays(config('kartukita.referral.reward_valid_days')),
            'is_active' => true,
        ]);
    }

    private function hasPaidOrder(User $user): bool
    {
        return Order::query()->whereBelongsTo($user)->where('status', OrderStatus::Lunas)->exists();
    }

    private function hasUsed(PromoCode $promo, User $user): bool
    {
        return Order::query()
            ->whereBelongsTo($user)
            ->whereBelongsTo($promo)
            ->whereIn('status', [OrderStatus::MenungguKonfirmasi, OrderStatus::Lunas])
            ->exists();
    }

    private function uniqueCode(string $prefix): string
    {
        $prefix = $prefix === '' ? 'KK' : $prefix;

        do {
            $code = $prefix.Str::upper(Str::random(4));
        } while (PromoCode::query()->where('code', $code)->exists());

        return $code;
    }
}
