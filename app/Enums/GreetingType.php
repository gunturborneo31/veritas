<?php

namespace App\Enums;

/**
 * Jenis produk. Ucapan untuk satu penerima dan dibekukan selamanya; undangan untuk banyak tamu,
 * punya acara/RSVP/amplop digital, dan isinya masih boleh direvisi sampai acara selesai.
 */
enum GreetingType: string
{
    case Ucapan = 'ucapan';
    case Undangan = 'undangan';

    public function label(): string
    {
        return match ($this) {
            self::Ucapan => 'Ucapan',
            self::Undangan => 'Undangan',
        };
    }
}
