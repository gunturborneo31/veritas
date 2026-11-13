<?php

namespace App\Support\WhatsApp;

/**
 * Nomor HP Indonesia untuk WhatsApp. Menerima 08…, +62…, 62…, atau 8…, dengan spasi/tanda hubung.
 */
class PhoneNumber
{
    /**
     * Ubah ke format internasional tanpa tanda plus (mis. 6281234567890). Null bila tidak valid.
     */
    public static function normalize(?string $input): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $input);

        if ($digits === '') {
            return null;
        }

        $digits = match (true) {
            str_starts_with($digits, '62') => $digits,
            str_starts_with($digits, '0') => '62'.substr($digits, 1),
            str_starts_with($digits, '8') => '62'.$digits,
            default => $digits,
        };

        return preg_match('/^628\d{7,12}$/', $digits) === 1 ? $digits : null;
    }

    /**
     * Tampilan lokal untuk pembeli (6281234567890 → 081234567890).
     */
    public static function display(?string $normalized): ?string
    {
        return $normalized === null ? null : '0'.substr($normalized, 2);
    }
}
