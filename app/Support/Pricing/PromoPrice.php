<?php

namespace App\Support\Pricing;

use App\Enums\GreetingType;

/**
 * Harga promo per jenis template: undangan memakai harga promo undangan bila diatur, selain itu
 * harga promo umum. Template yang harga aslinya sudah lebih murah dari harga promo tetap memakai harga aslinya.
 */
class PromoPrice
{
    public static function price(GreetingType $type = GreetingType::Ucapan): ?int
    {
        if ($type === GreetingType::Undangan && config('kartukita.promo.invitation_price') !== null) {
            return (int) config('kartukita.promo.invitation_price');
        }

        return config('kartukita.promo.price') === null ? null : (int) config('kartukita.promo.price');
    }

    public static function isActive(GreetingType $type = GreetingType::Ucapan): bool
    {
        return self::price($type) !== null;
    }

    public static function label(): string
    {
        return (string) config('kartukita.promo.label');
    }

    /**
     * Harga yang dibayar pembeli untuk template dengan harga asli tertentu.
     */
    public static function apply(int $originalPrice, GreetingType $type = GreetingType::Ucapan): int
    {
        $promoPrice = self::price($type);

        return $promoPrice === null ? $originalPrice : min($originalPrice, $promoPrice);
    }
}
