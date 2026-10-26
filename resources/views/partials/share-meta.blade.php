{{-- Pratinjau saat link dibagikan (WhatsApp, Telegram, dll.). Harus ada di HTML awal karena aplikasi ini dirender di browser. --}}
@php
    $share = array_merge([
        'title' => config('kartukita.brand').' — Ucapan & Undangan Digital Interaktif',
        'description' => 'Kartu ucapan dan undangan digital yang bisa ditiup lilinnya, digosok, dan dibuka langkah demi langkah. Kirim lewat link & QR.',
        'image' => null,
    ], $share ?? []);
@endphp
<meta name="description" content="{{ $share['description'] }}">
<meta property="og:type" content="website">
<meta property="og:site_name" content="{{ config('kartukita.brand') }}">
<meta property="og:title" content="{{ $share['title'] }}">
<meta property="og:description" content="{{ $share['description'] }}">
<meta property="og:url" content="{{ url()->current() }}">
@if ($share['image'])
    <meta property="og:image" content="{{ $share['image'] }}">
    <meta name="twitter:card" content="summary_large_image">
@endif
