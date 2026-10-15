<?php

namespace App\Enums;

/**
 * Kelas template, menentukan harga dasar Paket 1.
 */
enum TemplateTier: string
{
    case Klasik = 'klasik';
    case Interaktif = 'interaktif';
    case Premium = 'premium';

    public function label(): string
    {
        return match ($this) {
            self::Klasik => 'Klasik',
            self::Interaktif => 'Interaktif',
            self::Premium => 'Premium',
        };
    }
}
