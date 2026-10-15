<?php

namespace App\Enums;

enum OrderStatus: string
{
    case Menunggu = 'menunggu';
    case MenungguKonfirmasi = 'menunggu_konfirmasi';
    case Lunas = 'lunas';
    case Batal = 'batal';

    public function label(): string
    {
        return match ($this) {
            self::Menunggu => 'Menunggu Pembayaran',
            self::MenungguKonfirmasi => 'Menunggu Konfirmasi Admin',
            self::Lunas => 'Lunas',
            self::Batal => 'Batal',
        };
    }
}
