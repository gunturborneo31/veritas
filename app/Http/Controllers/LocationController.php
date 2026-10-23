<?php

namespace App\Http\Controllers;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

/**
 * Proksi Nominatim untuk komponen Jejak Kenangan: cari tempat dengan nama, dan cari nama tempat dari titik
 * yang diketuk di peta. Browser pengirim tidak memanggil Nominatim langsung; server memakai identitas aplikasi,
 * cache, dan batas 1 permintaan per detik sesuai kebijakan pemakaiannya.
 */
class LocationController extends Controller
{
    private const UNAVAILABLE = 'Pencarian tempat sedang tidak tersedia. Ketuk peta atau tempel link Google Maps untuk menandai titik.';

    public function search(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'q' => ['required', 'string', 'min:3', 'max:120'],
        ]);

        $query = Str::of($validated['q'])->squish()->lower()->toString();

        return $this->cached('location-search:'.md5($query), function () use ($query): ?array {
            $response = $this->nominatim('search', ['q' => $query, 'limit' => 5]);

            if ($response === null) {
                return null;
            }

            return collect($response->json())
                ->filter(fn (mixed $place): bool => is_array($place) && isset($place['lat'], $place['lon'], $place['display_name']))
                ->map(fn (array $place): array => $this->toPlace($place))
                ->values()
                ->all();
        });
    }

    public function reverse(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'lat' => ['required', 'numeric', 'between:-90,90'],
            'lng' => ['required', 'numeric', 'between:-180,180'],
        ]);

        // Pembulatan ±11 m: ketukan yang berdekatan memakai hasil cache yang sama.
        $lat = round((float) $validated['lat'], 4);
        $lng = round((float) $validated['lng'], 4);

        return $this->cached("location-reverse:{$lat},{$lng}", function () use ($lat, $lng): ?array {
            $response = $this->nominatim('reverse', ['lat' => $lat, 'lon' => $lng, 'zoom' => 18]);

            if ($response === null) {
                return null;
            }

            $place = $response->json();

            return is_array($place) && isset($place['display_name']) ? [[...$this->toPlace($place), 'lat' => $lat, 'lng' => $lng]] : [];
        });
    }

    /**
     * Link Google Maps (termasuk link pendek maps.app.goo.gl dari aplikasi) → koordinat.
     * Hanya mengikuti pengalihan ke domain Google agar endpoint ini tidak bisa dipakai mengakses alamat lain.
     */
    public function link(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'url' => ['required', 'url', 'max:2000'],
        ]);

        $url = $validated['url'];

        for ($hop = 0; $hop < 5; $hop++) {
            if (! $this->isGoogleMapsHost(parse_url($url, PHP_URL_HOST))) {
                return response()->json(['message' => 'Hanya link Google Maps yang bisa dipakai.'], 422);
            }

            if (($place = self::placeFromMapsUrl($url)) !== null) {
                return response()->json(['results' => [$place]]);
            }

            try {
                $response = Http::withoutRedirecting()->timeout(6)->withHeaders(['User-Agent' => config('kartukita.maps.geocoder_user_agent')])->get($url);
            } catch (ConnectionException) {
                break;
            }

            $next = $response->header('Location');

            if (! $response->redirect() || $next === '') {
                break;
            }

            $url = str_starts_with($next, 'http') ? $next : 'https://'.parse_url($url, PHP_URL_HOST).$next;
        }

        return response()->json(['message' => 'Koordinat tidak ditemukan di link itu. Buka link-nya, lalu salin alamat dari browser.'], 422);
    }

    /**
     * Ambil koordinat (dan nama tempat bila ada) dari URL Google Maps versi panjang.
     *
     * @return array{name: string, label: string, lat: float, lng: float}|null
     */
    public static function placeFromMapsUrl(string $url): ?array
    {
        $decoded = urldecode($url);
        $patterns = [
            '/!3d(-?\d+(?:\.\d+)?)!4d(-?\d+(?:\.\d+)?)/',
            '/[?&](?:q|query|ll|destination|center)=(-?\d+(?:\.\d+)?),\s*(-?\d+(?:\.\d+)?)/',
            '/@(-?\d+(?:\.\d+)?),(-?\d+(?:\.\d+)?)/',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $decoded, $matches) && abs((float) $matches[1]) <= 90 && abs((float) $matches[2]) <= 180) {
                $name = preg_match('#/place/([^/@]+)#', $decoded, $placeMatch) ? trim(str_replace('+', ' ', $placeMatch[1])) : '';

                return [
                    'name' => $name,
                    'label' => $name !== '' ? $name : 'Titik dari Google Maps',
                    'lat' => round((float) $matches[1], 6),
                    'lng' => round((float) $matches[2], 6),
                ];
            }
        }

        return null;
    }

    private function isGoogleMapsHost(?string $host): bool
    {
        return $host !== null && (bool) preg_match('/^(maps\.app\.goo\.gl|goo\.gl|maps\.google\.[a-z.]+|(www\.)?google\.[a-z.]+)$/i', $host);
    }

    /**
     * Hasil kosong hanya di-cache sebentar agar tempat yang baru ditambahkan ke OpenStreetMap tetap bisa ditemukan.
     *
     * @param  callable(): ?list<array<string, mixed>>  $fetch
     */
    private function cached(string $key, callable $fetch): JsonResponse
    {
        if (($cached = Cache::get($key)) !== null) {
            return response()->json(['results' => $cached]);
        }

        if (RateLimiter::tooManyAttempts('nominatim', 1)) {
            return response()->json(['message' => 'Sebentar ya, coba lagi dalam 1 detik.'], 429);
        }

        RateLimiter::hit('nominatim', 1);
        $results = $fetch();

        if ($results === null) {
            return response()->json(['message' => self::UNAVAILABLE], 503);
        }

        Cache::put($key, $results, $results === [] ? now()->addHour() : now()->addDays(config('kartukita.maps.search_cache_days')));

        return response()->json(['results' => $results]);
    }

    /**
     * @param  array<string, mixed>  $query
     */
    private function nominatim(string $endpoint, array $query): ?Response
    {
        try {
            $response = Http::withHeaders([
                'User-Agent' => config('kartukita.maps.geocoder_user_agent'),
                'Referer' => config('app.url'),
                'Accept-Language' => 'id,en',
            ])
                ->timeout(10)
                ->retry(2, 400, fn (Throwable $exception): bool => $exception instanceof ConnectionException, throw: false)
                ->get(rtrim(config('kartukita.maps.geocoder_url'), '/')."/{$endpoint}", [...$query, 'format' => 'jsonv2']);
        } catch (ConnectionException $exception) {
            report($exception);

            return null;
        }

        if ($response->failed()) {
            report(new RuntimeException("Nominatim {$endpoint} gagal: HTTP {$response->status()} ".Str::limit($response->body(), 120)));

            return null;
        }

        return $response;
    }

    /**
     * @param  array<string, mixed>  $place
     * @return array{name: string, label: string, lat: float, lng: float}
     */
    private function toPlace(array $place): array
    {
        return [
            'name' => filled($place['name'] ?? null) ? $place['name'] : Str::before($place['display_name'], ','),
            'label' => $place['display_name'],
            'lat' => round((float) $place['lat'], 6),
            'lng' => round((float) $place['lon'], 6),
        ];
    }
}
