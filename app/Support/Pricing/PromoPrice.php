<?php

namespace App\Support\Pricing;

/**
 * Harga promo yang berlaku untuk semua template. Template yang harga aslinya sudah lebih murah
 * dari harga promo tetap memakai harga aslinya.
 */
class PromoPrice
{
    public static function isActive(): bool
    {
        return config('kartukita.promo.price') !== null;
    }

    public static function label(): string
    {
        return (string) config('kartukita.promo.label');
    }

    /**
     * Harga yang dibayar pembeli untuk template dengan harga asli tertentu.
     */
    public static function apply(int $originalPrice): int
    {
        return self::isActive() ? min($originalPrice, (int) config('kartukita.promo.price')) : $originalPrice;
    }
}
