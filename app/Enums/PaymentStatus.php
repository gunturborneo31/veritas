<?php

namespace App\Enums;

enum PaymentStatus: string
{
    case Menunggu = 'menunggu';
    case Diterima = 'diterima';
    case Ditolak = 'ditolak';
}
