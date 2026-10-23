<?php

namespace App\Enums;

/**
 * Jawaban konfirmasi kehadiran tamu undangan.
 */
enum RsvpAttendance: string
{
    case Hadir = 'hadir';
    case TidakHadir = 'tidak_hadir';
    case Ragu = 'ragu';

    public function label(): string
    {
        return match ($this) {
            self::Hadir => 'Hadir',
            self::TidakHadir => 'Tidak Hadir',
            self::Ragu => 'Masih Ragu',
        };
    }

    /**
     * Hanya tamu yang hadir yang dihitung membawa rombongan.
     */
    public function countsAttendees(): bool
    {
        return $this === self::Hadir;
    }
}
