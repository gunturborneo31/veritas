<?php

namespace App\Support\Greetings;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Str;

/**
 * Identitas pengunjung anonim untuk draf ucapan. Token mentah hanya ada di
 * cookie (terenkripsi); server menyimpan hash-nya saja.
 */
class DraftToken
{
    public function fromRequest(Request $request): ?string
    {
        $token = $request->cookie(config('kartukita.draft_cookie'));

        return is_string($token) && $token !== '' ? $token : null;
    }

    /**
     * Ambil token yang ada atau buat baru dan antrekan cookie-nya.
     */
    public function ensure(Request $request): string
    {
        $token = $this->fromRequest($request) ?? Str::random(48);

        Cookie::queue(
            config('kartukita.draft_cookie'),
            $token,
            config('kartukita.draft_cookie_days') * 24 * 60,
        );

        return $token;
    }

    public function hashFromRequest(Request $request): ?string
    {
        $token = $this->fromRequest($request);

        return $token === null ? null : self::hash($token);
    }

    public static function hash(string $token): string
    {
        return hash('sha256', $token);
    }
}
