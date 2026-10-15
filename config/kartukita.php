<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Identitas Brand
    |--------------------------------------------------------------------------
    */

    'brand' => env('APP_NAME', 'KartuKita'),

    'support_whatsapp' => env('KARTUKITA_SUPPORT_WHATSAPP'),

    'support_email' => env('KARTUKITA_SUPPORT_EMAIL', env('MAIL_FROM_ADDRESS')),

    /*
    | Tanggal terakhir Kebijakan Privasi / Syarat & Ketentuan diperbarui (YYYY-MM-DD).
    */

    'legal_updated_at' => env('KARTUKITA_LEGAL_UPDATED_AT', '2026-10-15'),

    /*
    |--------------------------------------------------------------------------
    | Draf & Pratinjau
    |--------------------------------------------------------------------------
    |
    | Draf anonim diikat ke cookie. Draf yang tidak disentuh melewati masa
    | retensi dihapus otomatis oleh `model:prune`. Token pratinjau berlaku
    | terbatas dan tidak pernah bisa dipakai sebagai link final.
    |
    */

    'draft_cookie' => 'kk_draft',

    'draft_cookie_days' => 60,

    'draft_retention_days' => (int) env('KARTUKITA_DRAFT_RETENTION_DAYS', 30),

    'preview_ttl_hours' => (int) env('KARTUKITA_PREVIEW_TTL_HOURS', 24),

    /*
    |--------------------------------------------------------------------------
    | Gembok
    |--------------------------------------------------------------------------
    */

    'gate' => [
        'max_attempts_per_minute' => 5,
    ],

    /*
    |--------------------------------------------------------------------------
    | Media
    |--------------------------------------------------------------------------
    */

    'media' => [
        'disk' => env('KARTUKITA_MEDIA_DISK', 'local'),
        'image_max_kb' => 10240,
        'audio_max_kb' => 5120,
        'reaction_video_max_kb' => 30720,
        'reaction_video_max_seconds' => 90,
        'image_max_dimension' => 1600,
        'webp_quality' => 80,
    ],

    /*
    |--------------------------------------------------------------------------
    | Referral
    |--------------------------------------------------------------------------
    |
    | Teman yang memakai kode referral mendapat potongan di pesanan pertamanya;
    | pemilik kode mendapat voucher setelah pesanan teman itu lunas.
    |
    */

    'referral' => [
        'referee_discount' => (int) env('KARTUKITA_REFERRAL_DISCOUNT', 5000),
        'referrer_reward' => (int) env('KARTUKITA_REFERRAL_REWARD', 5000),
        'reward_valid_days' => 90,
    ],

    /*
    |--------------------------------------------------------------------------
    | Pembayaran Manual (Rilis Pertama)
    |--------------------------------------------------------------------------
    |
    | Pembayaran dikonfirmasi admin secara manual. Gateway otomatis menyusul
    | di Fase 2.
    |
    */

    'payment' => [
        'service_fee' => (int) env('KARTUKITA_SERVICE_FEE', 0),
        'minimum_payable' => 1000,
        'order_expires_hours' => (int) env('KARTUKITA_ORDER_EXPIRES_HOURS', 24),
        'proof_max_kb' => 4096,
        'methods' => [
            'transfer' => [
                'label' => 'Transfer Bank',
                'bank' => env('KARTUKITA_BANK_NAME', 'BCA'),
                'account_number' => env('KARTUKITA_BANK_ACCOUNT', '0000000000'),
                'account_holder' => env('KARTUKITA_BANK_HOLDER', 'KartuKita'),
            ],
            'qris' => [
                'label' => 'QRIS',
                'image_path' => env('KARTUKITA_QRIS_IMAGE', 'images/qris.png'),
            ],
        ],
    ],

];
