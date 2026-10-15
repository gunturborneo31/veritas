<?php

namespace Database\Factories;

use App\Enums\PromoKind;
use App\Models\PromoCode;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<PromoCode>
 */
class PromoCodeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => 'PROMO'.Str::upper(Str::random(5)),
            'kind' => PromoKind::Promo,
            'label' => 'Promo',
            'discount_type' => PromoCode::TYPE_FIXED,
            'discount_value' => 5000,
            'is_active' => true,
        ];
    }

    public function percent(int $value): static
    {
        return $this->state(fn (array $attributes) => ['discount_type' => PromoCode::TYPE_PERCENT, 'discount_value' => $value]);
    }

    public function referralOf(User $owner): static
    {
        return $this->state(fn (array $attributes) => [
            'kind' => PromoKind::Referral,
            'owner_user_id' => $owner->id,
            'first_order_only' => true,
        ]);
    }
}
