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

    'legal_updated_at' => env('KARTUKITA_LEGAL_UPDATED_AT', '2026-10-19'),

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
    | Undangan
    |--------------------------------------------------------------------------
    |
    | Berbeda dari ucapan, isi undangan masih bisa direvisi setelah terbit
    | sampai beberapa hari setelah acara terakhir selesai. Halaman tamu tetap
    | bisa dibuka (sebagai kenangan) sampai masa aktif berakhir, lalu 410.
    | Jumlah tamu tidak dibatasi; batas di bawah hanya pengaman impor massal.
    |
    */

    'invitation' => [
        'revision_grace_days' => (int) env('KARTUKITA_INVITATION_REVISION_GRACE_DAYS', 3),
        'active_days_after_event' => (int) env('KARTUKITA_INVITATION_ACTIVE_DAYS', 365),
        'max_guests' => (int) env('KARTUKITA_INVITATION_MAX_GUESTS', 2000),
        'max_import_rows' => 500,
        'max_guest_quota' => 10,
        'timezones' => [
            'Asia/Jakarta' => 'WIB',
            'Asia/Makassar' => 'WITA',
            'Asia/Jayapura' => 'WIT',
        ],
        'default_broadcast_message' => "Kepada Yth.\nBapak/Ibu/Saudara/i *{nama}*\n\nTanpa mengurangi rasa hormat, kami mengundang Anda untuk hadir di acara kami. Detail acara dan konfirmasi kehadiran ada di tautan berikut:\n\n{link}\n\nMerupakan kebahagiaan bagi kami apabila Anda berkenan hadir dan memberikan doa restu.\n\nTerima kasih.",
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

        /*
        | `qris` = QRIS dinamis lewat API (lunas otomatis). `manual` = QRIS statis milik merchant:
        | pembeli membayar nominal unik (harga + 3 digit), menekan "Saya sudah bayar", admin
        | mendapat notifikasi lalu cukup menekan Konfirmasi. Dipakai selama API QRIS belum aktif.
        */
        'mode' => env('KARTUKITA_PAYMENT_MODE', 'qris'),

        'manual' => [
            // Gambar QRIS statis dari dashboard merchant, relatif terhadap folder public/.
            'qris_image' => env('KARTUKITA_MANUAL_QRIS_IMAGE', 'images/qris-statis.jpg'),
            // Harus sama dengan nama yang tertera di QRIS agar pembeli yakin tujuan pembayarannya benar.
            'merchant_name' => env('KARTUKITA_MANUAL_MERCHANT_NAME', 'VERITAS WEB DEVELOPER'),
            'proof_max_kb' => 5120,
            // `upload` = pembeli mengunggah bukti di web. `whatsapp` = pembeli mengirim bukti ke
            // KARTUKITA_SUPPORT_WHATSAPP (bila nomor belum diisi, otomatis kembali ke `upload`).
            'proof_channel' => env('KARTUKITA_MANUAL_PROOF_CHANNEL', 'upload'),
            // Kode unik 1–499 ditambahkan ke harga agar setiap pembayaran mudah dicocokkan di mutasi.
            'unique_code_max' => 499,
        ],

        /*
        | QRIS dinamis InterActive (https://qris.interactive.co.id/api-doc/). Setiap pesanan mendapat QR
        | dengan nominal terkunci yang berlaku 30 menit. Lunas dideteksi otomatis lewat webhook (yang selalu
        | dikonfirmasi ulang ke API cek status, karena webhook mereka tidak bertanda tangan) dan cron tiap menit.
        |
        | Driver `fake` hanya untuk pengembangan lokal: QR contoh + tombol "Simulasikan bayar".
        */
        'qris' => [
            'driver' => env('QRIS_DRIVER', 'interactive'),
            'base_url' => env('QRIS_INTERACTIVE_BASE_URL', 'https://qris.interactive.co.id/restapi/qris'),
            'api_key' => env('QRIS_INTERACTIVE_API_KEY'),
            'merchant_id' => env('QRIS_INTERACTIVE_MID'),
            'webhook_token' => env('QRIS_INTERACTIVE_WEBHOOK_TOKEN'),
            'expires_minutes' => 30,
            'max_amount' => 2000000,
            // Panduan InterActive: cek status maksimal 30 kali dengan jeda ±60 detik agar API key tidak diblokir.
            'max_status_checks' => 30,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Peta (komponen Jejak Kenangan)
    |--------------------------------------------------------------------------
    |
    | Ubin peta bawaan memakai OpenStreetMap, yang hanya cocok untuk lalu lintas
    | ringan. Saat ramai, arahkan ke penyedia ubin berbayar (mis. MapTiler/Stadia)
    | lewat env tanpa mengubah kode. Pencarian tempat diproksikan lewat server
    | (Nominatim) dengan cache dan pembatasan sesuai kebijakan pemakaiannya.
    |
    */

    'maps' => [
        'tile_url' => env('KARTUKITA_MAP_TILE_URL', 'https://tile.openstreetmap.org/{z}/{x}/{y}.png'),
        'attribution' => env('KARTUKITA_MAP_ATTRIBUTION', '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'),
        'geocoder_url' => env('KARTUKITA_GEOCODER_URL', 'https://nominatim.openstreetmap.org'),
        /*
        | Nominatim menolak (HTTP 403) identitas generik atau email contoh seperti hello@example.com.
        | Untuk produksi, isi dengan nama aplikasi + email kontak asli, mis. "KartuKita/1.0 (halo@kartukita.id)".
        */
        'geocoder_user_agent' => env('KARTUKITA_GEOCODER_USER_AGENT', env('APP_NAME', 'KartuKita').'/1.0 (+'.env('APP_URL', 'http://localhost').')'),
        'search_cache_days' => 30,
    ],

];
