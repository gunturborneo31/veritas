<?php

namespace App\Enums;

/**
 * Jenis kode potongan: promo umum dari admin, kode referral milik pengguna,
 * dan voucher hadiah untuk pemilik referral.
 */
enum PromoKind: string
{
    case Promo = 'promo';
    case Referral = 'referral';
    case Reward = 'reward';
}
