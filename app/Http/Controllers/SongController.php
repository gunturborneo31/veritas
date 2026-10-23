<?php

namespace App\Http\Controllers;

use App\Support\Greetings\ContentSchema;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Komponen "Lagu Kita": ubah link Spotify/YouTube menjadi data lagu, dan cari lagu di Spotify.
 * Lagu tidak pernah diunduh; penerima memutarnya lewat pemutar resmi (embed) masing-masing layanan.
 */
class SongController extends Controller
{
    private const SPOTIFY_HOSTS = ['open.spotify.com', 'spotify.link', 'spoti.fi'];

    private const YOUTUBE_HOSTS = ['youtube.com', 'www.youtube.com', 'm.youtube.com', 'music.youtube.com', 'youtu.be'];

    public static function isSearchEnabled(): bool
    {
        return filled(config('services.spotify.client_id')) && filled(config('services.spotify.client_secret'));
    }

    public function search(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'q' => ['required', 'string', 'min:2', 'max:100'],
        ]);

        if (! self::isSearchEnabled()) {
            return response()->json(['message' => 'Pencarian Spotify belum diaktifkan. Tempel link lagunya saja, ya.'], 503);
        }

        $query = Str::of($validated['q'])->squish()->lower()->toString();

        $results = Cache::remember('song-search:'.md5($query), now()->addDay(), function () use ($query): ?array {
            $response = $this->spotify('search', ['q' => $query, 'type' => 'track', 'limit' => 8, 'market' => config('services.spotify.market')]);

            return $response === null ? null : collect($response['tracks']['items'] ?? [])->map(fn (array $track): array => $this->fromSpotifyTrack($track))->all();
        });

        if ($results === null) {
            Cache::forget('song-search:'.md5($query));

            return response()->json(['message' => 'Spotify sedang tidak bisa dihubungi. Coba lagi, atau tempel link lagunya.'], 503);
        }

        return response()->json(['results' => $results]);
    }

    public function link(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'url' => ['required', 'url', 'max:500'],
        ]);

        $url = $this->followSpotifyShortLink($validated['url']);
        $host = Str::lower((string) parse_url($url, PHP_URL_HOST));

        if (in_array($host, self::SPOTIFY_HOSTS, true) && preg_match('#/track/([A-Za-z0-9]{22})#', $url, $match)) {
            $song = $this->spotifyTrack($match[1]);
        } elseif (in_array($host, self::YOUTUBE_HOSTS, true) && ($videoId = self::youtubeId($url)) !== null) {
            $song = $this->youtubeVideo($videoId);

            if ($song === false) {
                return response()->json(['message' => 'Pemilik video ini tidak mengizinkannya diputar di situs lain. Coba video lain (mis. versi lirik/akustik) atau cari lagunya di Spotify.'], 422);
            }
        } else {
            return response()->json(['message' => 'Tempel link lagu dari Spotify (open.spotify.com/track/…) atau video YouTube.'], 422);
        }

        return $song === null
            ? response()->json(['message' => 'Lagu tidak ditemukan. Pastikan link-nya benar dan lagunya tidak dibatasi.'], 422)
            : response()->json(['song' => $song]);
    }

    /**
     * ID video dari berbagai bentuk link YouTube (watch, youtu.be, shorts, embed, music).
     */
    public static function youtubeId(string $url): ?string
    {
        $patterns = ['#youtu\.be/([A-Za-z0-9_-]{11})#', '#[?&]v=([A-Za-z0-9_-]{11})#', '#/(?:shorts|embed|live)/([A-Za-z0-9_-]{11})#'];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $url, $match) && preg_match(ContentSchema::SONG_ID_PATTERNS['youtube'], $match[1])) {
                return $match[1];
            }
        }

        return null;
    }

    /**
     * Link pendek (spotify.link / spoti.fi) diikuti hanya ke domain Spotify.
     */
    private function followSpotifyShortLink(string $url): string
    {
        for ($hop = 0; $hop < 4; $hop++) {
            $host = Str::lower((string) parse_url($url, PHP_URL_HOST));

            if (! in_array($host, ['spotify.link', 'spoti.fi'], true)) {
                return $url;
            }

            try {
                $response = Http::withoutRedirecting()->timeout(6)->get($url);
            } catch (ConnectionException) {
                return $url;
            }

            $next = (string) $response->header('Location');

            if (! $response->redirect() || $next === '' || ! in_array(Str::lower((string) parse_url($next, PHP_URL_HOST)), self::SPOTIFY_HOSTS, true)) {
                return $url;
            }

            $url = $next;
        }

        return $url;
    }

    /**
     * @return array{provider: string, id: string, title: string, artist: ?string, image: ?string}|null
     */
    private function spotifyTrack(string $id): ?array
    {
        return Cache::remember("song-spotify:{$id}", now()->addWeek(), function () use ($id): ?array {
            if (self::isSearchEnabled() && ($track = $this->spotify("tracks/{$id}", ['market' => config('services.spotify.market')])) !== null) {
                return $this->fromSpotifyTrack($track);
            }

            $oembed = $this->oembed('https://open.spotify.com/oembed', "https://open.spotify.com/track/{$id}");

            return $oembed === null ? null : [
                'provider' => 'spotify',
                'id' => $id,
                'title' => Str::limit($oembed['title'] ?? 'Lagu Spotify', 200, ''),
                'artist' => null,
                'image' => $oembed['thumbnail_url'] ?? null,
            ];
        });
    }

    /**
     * `false` berarti video ada tetapi pemiliknya mematikan pemutaran di situs lain (oEmbed YouTube membalas 401/403).
     *
     * @return array{provider: string, id: string, title: string, artist: ?string, image: ?string}|false|null
     */
    private function youtubeVideo(string $id): array|false|null
    {
        return Cache::remember("song-youtube:{$id}", now()->addWeek(), function () use ($id): array|false|null {
            try {
                $response = Http::timeout(6)->get('https://www.youtube.com/oembed', ['url' => "https://www.youtube.com/watch?v={$id}", 'format' => 'json']);
            } catch (ConnectionException) {
                return null;
            }

            if (in_array($response->status(), [401, 403], true)) {
                return false;
            }

            $oembed = $response->successful() && is_array($response->json()) ? $response->json() : null;

            return $oembed === null ? null : [
                'provider' => 'youtube',
                'id' => $id,
                'title' => Str::limit($oembed['title'] ?? 'Video YouTube', 200, ''),
                'artist' => isset($oembed['author_name']) ? Str::limit($oembed['author_name'], 200, '') : null,
                'image' => "https://i.ytimg.com/vi/{$id}/hqdefault.jpg",
            ];
        });
    }

    /**
     * @param  array<string, mixed>  $track
     * @return array{provider: string, id: string, title: string, artist: ?string, image: ?string}
     */
    private function fromSpotifyTrack(array $track): array
    {
        $images = collect($track['album']['images'] ?? [])->sortBy('width')->values();

        return [
            'provider' => 'spotify',
            'id' => $track['id'],
            'title' => Str::limit($track['name'] ?? 'Lagu Spotify', 200, ''),
            'artist' => Str::limit(collect($track['artists'] ?? [])->pluck('name')->implode(', '), 200, '') ?: null,
            'image' => ($images->first(fn (array $image): bool => ($image['width'] ?? 0) >= 160) ?? $images->last())['url'] ?? null,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function oembed(string $endpoint, string $url): ?array
    {
        try {
            $response = Http::timeout(6)->get($endpoint, ['url' => $url, 'format' => 'json']);
        } catch (ConnectionException) {
            return null;
        }

        return $response->successful() && is_array($response->json()) ? $response->json() : null;
    }

    /**
     * Panggil Spotify Web API dengan token Client Credentials yang di-cache sampai hampir kedaluwarsa.
     *
     * @param  array<string, mixed>  $query
     * @return array<string, mixed>|null
     */
    private function spotify(string $path, array $query): ?array
    {
        $token = Cache::get('spotify-token');

        try {
            if ($token === null) {
                $response = Http::asForm()
                    ->withBasicAuth(config('services.spotify.client_id'), config('services.spotify.client_secret'))
                    ->timeout(6)
                    ->post('https://accounts.spotify.com/api/token', ['grant_type' => 'client_credentials']);

                if ($response->failed()) {
                    report(new RuntimeException('Token Spotify gagal: HTTP '.$response->status()));

                    return null;
                }

                $token = $response->json('access_token');
                Cache::put('spotify-token', $token, now()->addSeconds(max(60, (int) $response->json('expires_in', 3600) - 120)));
            }

            $response = Http::withToken($token)->timeout(6)->get("https://api.spotify.com/v1/{$path}", $query);
        } catch (ConnectionException) {
            return null;
        }

        if ($response->status() === 401) {
            Cache::forget('spotify-token');
        }

        return $response->successful() ? $response->json() : null;
    }
}
