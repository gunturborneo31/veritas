<?php

namespace App\Enums;

enum MediaStatus: string
{
    case Menunggu = 'menunggu';
    case Siap = 'siap';
    case Gagal = 'gagal';
}
