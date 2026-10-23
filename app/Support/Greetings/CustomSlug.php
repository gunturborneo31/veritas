<?php

namespace App\Support\Greetings;

use App\Models\Greeting;
use Illuminate\Support\Str;

/**
 * Nama link pilihan pembeli (`/u/<slug>`). Dipilih sebelum bayar dan dikunci saat terbit,
 * karena link yang sudah disebar tidak boleh berubah. Tanpa pilihan, slug acak dibuat saat terbit.
 */
class CustomSlug
{
    public const MIN_LENGTH = 3;

    public const MAX_LENGTH = 40;

    public const PATTERN = '/^[a-z0-9]+(?:-[a-z0-9]+)*$/';

    /**
     * Nama yang bisa disalahgunakan untuk menyamar sebagai layanan atau admin.
     *
     * @var list<string>
     */
    public const RESERVED = ['admin', 'administrator', 'kartukita', 'official', 'resmi', 'support', 'bantuan', 'cs', 'promo', 'gratis', 'login', 'masuk', 'daftar'];

    /**
     * Rapikan masukan bebas menjadi kandidat slug: huruf kecil, spasi jadi tanda hubung.
     */
    public static function normalize(?string $value): string
    {
        return Str::limit(Str::slug((string) $value), self::MAX_LENGTH, '');
    }

    /**
     * Alasan slug tidak bisa dipakai, atau null bila tersedia.
     */
    public function problem(string $slug, ?Greeting $owner = null): ?string
    {
        return match (true) {
            strlen($slug) < self::MIN_LENGTH => 'Minimal '.self::MIN_LENGTH.' karakter.',
            strlen($slug) > self::MAX_LENGTH => 'Maksimal '.self::MAX_LENGTH.' karakter.',
            preg_match(self::PATTERN, $slug) !== 1 => 'Hanya huruf kecil, angka, dan tanda hubung (-).',
            in_array($slug, self::RESERVED, true) => 'Nama link ini tidak bisa dipakai.',
            $this->isTaken($slug, $owner) => 'Link ini sudah dipakai. Coba variasi lain.',
            default => null,
        };
    }

    /**
     * Beberapa alternatif yang masih kosong, mis. raka-dinda-2026, raka-dinda-2.
     *
     * @return list<string>
     */
    public function suggestions(string $slug, ?Greeting $owner = null): array
    {
        $base = Str::limit($slug, self::MAX_LENGTH - 5, '');
        $candidates = [$base.'-'.now()->year, $base.'-2', $base.'-3', $base.'-'.Str::lower(Str::random(3))];

        return array_values(array_slice(array_filter($candidates, fn (string $candidate): bool => $this->problem($candidate, $owner) === null), 0, 3));
    }

    private function isTaken(string $slug, ?Greeting $owner): bool
    {
        return Greeting::query()
            ->where('slug', $slug)
            ->when($owner !== null, fn ($query) => $query->whereKeyNot($owner->getKey()))
            ->exists();
    }
}
