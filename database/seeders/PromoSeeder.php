<?php

namespace Database\Seeders;

use App\Enums\PromoKind;
use App\Models\PromoCode;
use Illuminate\Database\Seeder;

/**
 * Kode promo awal (aman dijalankan berulang).
 */
class PromoSeeder extends Seeder
{
    public function run(): void
    {
        PromoCode::query()->firstOrCreate(['code' => 'KARTUHANGAT'], [
            'kind' => PromoKind::Promo,
            'label' => 'Promo pengguna pertama',
            'discount_type' => PromoCode::TYPE_PERCENT,
            'discount_value' => 10,
            'first_order_only' => true,
            'is_active' => true,
        ]);
    }
}
