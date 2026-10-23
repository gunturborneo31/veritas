<?php

namespace App\Enums;

enum PaymentStatus: string
{
    case Menunggu = 'menunggu';
    case Diterima = 'diterima';
    case Ditolak = 'ditolak';

    /**
     * QR tidak dibayar sampai masa berlakunya habis; pembeli bisa membuat QR baru.
     */
    case Kedaluwarsa = 'kedaluwarsa';
}
