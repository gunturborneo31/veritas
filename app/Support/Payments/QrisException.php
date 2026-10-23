<?php

namespace App\Support\Payments;

use RuntimeException;

/**
 * Kegagalan berkomunikasi dengan penyedia QRIS. Pesannya aman ditampilkan ke pembeli.
 */
class QrisException extends RuntimeException
{
    public static function unavailable(): self
    {
        return new self('Layanan QRIS sedang tidak bisa dihubungi. Coba lagi sebentar lagi.');
    }

    public static function notConfigured(): self
    {
        return new self('Pembayaran QRIS belum diaktifkan. Hubungi admin.');
    }
}
