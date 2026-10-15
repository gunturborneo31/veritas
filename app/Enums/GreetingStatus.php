<?php

namespace App\Enums;

/**
 * Siklus hidup satu ucapan: draf → pratinjau → menunggu_bayar → terkunci → terbit → (arsip).
 */
enum GreetingStatus: string
{
    case Draf = 'draf';
    case Pratinjau = 'pratinjau';
    case MenungguBayar = 'menunggu_bayar';
    case Terkunci = 'terkunci';
    case Terbit = 'terbit';
    case Arsip = 'arsip';

    /**
     * Isi ucapan hanya boleh diubah sebelum bukti bayar dikirim.
     */
    public function isEditable(): bool
    {
        return in_array($this, [self::Draf, self::Pratinjau, self::MenungguBayar], true);
    }

    public function label(): string
    {
        return match ($this) {
            self::Draf => 'Draf',
            self::Pratinjau => 'Pratinjau',
            self::MenungguBayar => 'Menunggu Bayar',
            self::Terkunci => 'Menunggu Konfirmasi',
            self::Terbit => 'Terbit',
            self::Arsip => 'Arsip',
        };
    }
}
