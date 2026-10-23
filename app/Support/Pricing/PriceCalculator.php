<?php

namespace App\Support\Pricing;

use App\Models\Greeting;
use App\Models\PromoCode;

/**
 * Satu-satunya tempat harga dihitung. Input harga dari browser tidak pernah dipakai.
 *
 * Rilis pertama hanya mengenal Paket 1: harga = harga template (+ biaya layanan bila diatur),
 * dikurangi kode promo bila ada, dengan batas minimum pembayaran.
 */
class PriceCalculator
{
    public function quote(Greeting $greeting, ?PromoCode $promo = null): PriceQuote
    {
        $greeting->loadMissing('template');

        $originalPrice = $greeting->template->price;
        $lines = [
            ['label' => "Paket 1 • {$greeting->template->name}", 'amount' => $originalPrice],
        ];

        $promoPrice = PromoPrice::apply($originalPrice);

        if ($promoPrice < $originalPrice) {
            $lines[] = ['label' => PromoPrice::label(), 'amount' => $promoPrice - $originalPrice];
        }

        $serviceFee = (int) config('kartukita.payment.service_fee');

        if ($serviceFee > 0) {
            $lines[] = ['label' => 'Biaya layanan', 'amount' => $serviceFee];
        }

        $subtotal = array_sum(array_column($lines, 'amount'));

        if ($promo === null) {
            return new PriceQuote($lines, $subtotal);
        }

        $minimum = min((int) config('kartukita.payment.minimum_payable'), $subtotal);
        $discount = min($promo->discountFor($subtotal), $subtotal - $minimum);
        $lines[] = ['label' => "Kode {$promo->code}", 'amount' => -$discount];

        return new PriceQuote($lines, $subtotal - $discount, $discount, $promo->code);
    }
}
