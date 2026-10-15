<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Pesan Validasi
    |--------------------------------------------------------------------------
    |
    | Aturan yang belum diterjemahkan di sini otomatis memakai bahasa Inggris
    | (APP_FALLBACK_LOCALE).
    |
    */

    'accepted' => ':Attribute wajib dicentang.',
    'after' => ':Attribute harus setelah :date.',
    'alpha_dash' => ':Attribute hanya boleh berisi huruf, angka, tanda hubung, dan garis bawah.',
    'array' => ':Attribute harus berupa daftar.',
    'between' => [
        'numeric' => ':Attribute harus antara :min dan :max.',
        'string' => ':Attribute harus antara :min dan :max karakter.',
    ],
    'boolean' => ':Attribute harus bernilai ya atau tidak.',
    'confirmed' => 'Konfirmasi :attribute tidak cocok.',
    'current_password' => 'Kata sandi salah.',
    'date' => ':Attribute bukan tanggal yang valid.',
    'date_format' => ':Attribute tidak sesuai format :format.',
    'distinct' => ':Attribute memiliki nilai yang sama.',
    'email' => ':Attribute harus berupa alamat email yang valid.',
    'exists' => ':Attribute yang dipilih tidak valid.',
    'file' => ':Attribute harus berupa file.',
    'image' => ':Attribute harus berupa gambar.',
    'in' => ':Attribute yang dipilih tidak valid.',
    'integer' => ':Attribute harus berupa angka bulat.',
    'lowercase' => ':Attribute harus huruf kecil.',
    'max' => [
        'array' => ':Attribute maksimal berisi :max item.',
        'file' => ':Attribute maksimal :max kilobyte.',
        'numeric' => ':Attribute maksimal :max.',
        'string' => ':Attribute maksimal :max karakter.',
    ],
    'mimes' => ':Attribute harus berformat: :values.',
    'mimetypes' => ':Attribute harus berformat: :values.',
    'min' => [
        'array' => ':Attribute minimal berisi :min item.',
        'file' => ':Attribute minimal :min kilobyte.',
        'numeric' => ':Attribute minimal :min.',
        'string' => ':Attribute minimal :min karakter.',
    ],
    'numeric' => ':Attribute harus berupa angka.',
    'password' => [
        'letters' => ':Attribute harus mengandung huruf.',
        'mixed' => ':Attribute harus mengandung huruf besar dan kecil.',
        'numbers' => ':Attribute harus mengandung angka.',
        'symbols' => ':Attribute harus mengandung simbol.',
        'uncompromised' => ':Attribute ini pernah bocor di internet. Pilih kata sandi lain.',
    ],
    'present' => ':Attribute wajib ada.',
    'prohibited' => ':Attribute tidak diizinkan.',
    'regex' => 'Format :attribute tidak valid.',
    'required' => ':Attribute wajib diisi.',
    'required_if' => ':Attribute wajib diisi.',
    'string' => ':Attribute harus berupa teks.',
    'unique' => ':Attribute sudah dipakai.',
    'uploaded' => ':Attribute gagal diunggah.',

    'attributes' => [
        'name' => 'nama',
        'email' => 'email',
        'password' => 'kata sandi',
        'current_password' => 'kata sandi saat ini',
        'recipient_name' => 'nama penerima',
        'sender_name' => 'nama pengirim',
        'occasion' => 'momen',
        'opens_at' => 'waktu buka',
        'proof' => 'bukti transfer',
        'method' => 'metode pembayaran',
        'message' => 'pesan',
        'note' => 'catatan',
        'promo_code' => 'kode promo',
        'person_name' => 'nama',
        'day' => 'tanggal',
        'month' => 'bulan',
        'photo' => 'foto',
        'video' => 'video',
        'file' => 'file',
    ],

];
