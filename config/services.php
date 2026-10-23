<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Resend, Postmark, AWS, and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    /*
    | Komponen "Lagu Kita": pencarian lagu memakai Spotify Web API (Client Credentials).
    | Buat aplikasi gratis di https://developer.spotify.com/dashboard. Tanpa kredensial,
    | pengirim tetap bisa menempel link Spotify/YouTube.
    */
    'spotify' => [
        'client_id' => env('SPOTIFY_CLIENT_ID'),
        'client_secret' => env('SPOTIFY_CLIENT_SECRET'),
        'market' => env('SPOTIFY_MARKET', 'ID'),
    ],

    /*
    | Notifikasi admin lewat Telegram (mis. pembayaran manual menunggu konfirmasi).
    | Buat bot di @BotFather, kirim pesan apa saja ke bot, lalu ambil chat_id dari
    | https://api.telegram.org/bot<token>/getUpdates. Kosongkan untuk mematikan.
    */
    'telegram' => [
        'bot_token' => env('TELEGRAM_BOT_TOKEN'),
        // Hanya chat ini yang boleh menekan tombol verifikasi pembayaran.
        'chat_id' => env('TELEGRAM_ADMIN_CHAT_ID'),
        // Rahasia webhook (header X-Telegram-Bot-Api-Secret-Token); wajib bila memakai webhook di produksi.
        'webhook_secret' => env('TELEGRAM_WEBHOOK_SECRET'),
    ],

    /*
    | Google Analytics 4 (gtag.js). Kosongkan untuk mematikan (mis. di lokal atau saat test).
    */
    'google_analytics' => [
        'measurement_id' => env('GOOGLE_ANALYTICS_ID'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

];
