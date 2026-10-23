<?php

namespace App\Enums;

/**
 * Tujuan amplop digital/kado: rekening bank, dompet digital, atau alamat kirim kado fisik.
 */
enum GiftKind: string
{
    case Bank = 'bank';
    case EWallet = 'ewallet';
    case Alamat = 'alamat';

    public function label(): string
    {
        return match ($this) {
            self::Bank => 'Rekening Bank',
            self::EWallet => 'Dompet Digital',
            self::Alamat => 'Alamat Kirim Kado',
        };
    }
}
