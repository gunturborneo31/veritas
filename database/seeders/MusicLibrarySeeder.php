<?php

namespace Database\Seeders;

use App\Models\MusicTrack;
use Illuminate\Database\Seeder;

/**
 * Pustaka musik latar instrumental. File ada di public/audio/latar/ (ikut ter-deploy lewat git),
 * sumbernya Kevin MacLeod (incompetech.com), lisensi CC BY 4.0: boleh komersial dengan mencantumkan kredit.
 * Aman dijalankan berulang (updateOrCreate per path).
 */
class MusicLibrarySeeder extends Seeder
{
    private const CREDIT = '"%s" Kevin MacLeod (incompetech.com) — Licensed under Creative Commons: By Attribution 4.0 (creativecommons.org/licenses/by/4.0)';

    /**
     * Kategori untuk musik bawaan lama yang disimpan di storage.
     *
     * @var array<string, string>
     */
    private const LEGACY_CATEGORIES = [
        'music/selamat-ulang-tahun-akustik.mp3' => 'Ceria',
        'music/kotak-musik-ajaib.mp3' => 'Ceria',
        'music/jazz-kafe-sore.mp3' => 'Santai & Jazz',
        'music/gema-takbir-instrumental.mp3' => 'Khidmat & Religi',
        'music/gerimis-teduh-piano.mp3' => 'Teduh & Piano',
        'music/rindu-lofi.mp3' => 'Haru & Rindu',
        'music/langkah-baru-orkestra.mp3' => 'Semangat & Bangga',
        'music/terima-kasih-akustik.mp3' => 'Romantis',
    ];

    /**
     * [kategori, judul asli, nama file, durasi detik]
     *
     * @var list<array{0: string, 1: string, 2: string, 3: int}>
     */
    private const TRACKS = [
        ['Romantis', 'Smooth Lovin', 'smooth-lovin', 259],
        ['Romantis', 'At Rest', 'at-rest', 206],
        ['Romantis', 'Bittersweet', 'bittersweet', 202],
        ['Romantis', 'Heartwarming', 'heartwarming', 72],
        ['Romantis', 'Night in Venice', 'night-in-venice', 218],
        ['Romantis', 'Touching Moments Two - Higher', 'touching-moments-two-higher', 174],
        ['Ceria', 'Carefree', 'carefree', 205],
        ['Ceria', 'Fluffing a Duck', 'fluffing-a-duck', 67],
        ['Ceria', 'Monkeys Spinning Monkeys', 'monkeys-spinning-monkeys', 125],
        ['Ceria', 'Happy Alley', 'happy-alley', 80],
        ['Ceria', 'Cheery Monday', 'cheery-monday', 80],
        ['Ceria', 'Somewhere Sunny', 'somewhere-sunny', 59],
        ['Ceria', 'Pinball Spring', 'pinball-spring', 235],
        ['Ceria', 'Funin and Sunin', 'funin-and-sunin', 218],
        ['Ceria', 'Merry Go', 'merry-go', 120],
        ['Ceria', 'Life of Riley', 'life-of-riley', 235],
        ['Ceria', 'Amazing Plan', 'amazing-plan', 87],
        ['Santai & Jazz', 'Bossa Antigua', 'bossa-antigua', 283],
        ['Santai & Jazz', 'Airport Lounge', 'airport-lounge', 307],
        ['Santai & Jazz', 'Backbay Lounge', 'backbay-lounge', 266],
        ['Santai & Jazz', 'Cool Vibes', 'cool-vibes', 218],
        ['Santai & Jazz', 'Hep Cats', 'hep-cats', 250],
        ['Santai & Jazz', 'Local Forecast - Elevator', 'local-forecast-elevator', 189],
        ['Santai & Jazz', 'Lobby Time', 'lobby-time', 193],
        ['Santai & Jazz', 'Easy Lemon', 'easy-lemon', 126],
        ['Santai & Jazz', 'Laid Back Guitars', 'laid-back-guitars', 244],
        ['Teduh & Piano', 'Gymnopedie No 1', 'gymnopedie-no-1', 187],
        ['Teduh & Piano', 'Prelude in C - BWV 846', 'prelude-in-c-bwv-846', 178],
        ['Teduh & Piano', 'Meditation Impromptu 01', 'meditation-impromptu-01', 212],
        ['Teduh & Piano', 'Meditation Impromptu 02', 'meditation-impromptu-02', 249],
        ['Teduh & Piano', 'Clear Air', 'clear-air', 183],
        ['Teduh & Piano', 'Dreamer', 'dreamer', 204],
        ['Teduh & Piano', 'Kalimba Relaxation Music', 'kalimba-relaxation-music', 428],
        ['Teduh & Piano', 'Wallpaper', 'wallpaper', 220],
        ['Klasik & Pernikahan', 'Canon in D Major', 'canon-in-d-major', 355],
        ['Klasik & Pernikahan', 'Village Consort', 'village-consort', 214],
        ['Klasik & Pernikahan', 'Celebration', 'celebration', 277],
        ['Klasik & Pernikahan', 'Maple Leaf Rag', 'maple-leaf-rag', 179],
        ['Khidmat & Religi', 'Desert City', 'desert-city', 89],
        ['Khidmat & Religi', 'Tabuk', 'tabuk', 158],
        ['Khidmat & Religi', 'Eastern Thought', 'eastern-thought', 261],
        ['Khidmat & Religi', 'Lasting Hope', 'lasting-hope', 143],
        ['Khidmat & Religi', 'Floating Cities', 'floating-cities', 184],
        ['Haru & Rindu', 'Sad Trio', 'sad-trio', 251],
        ['Haru & Rindu', 'Reaching Out', 'reaching-out', 61],
        ['Haru & Rindu', 'Long Road Ahead', 'long-road-ahead', 146],
        ['Haru & Rindu', 'Rains Will Fall', 'rains-will-fall', 222],
        ['Haru & Rindu', 'Overcast', 'overcast', 228],
        ['Semangat & Bangga', 'Inspired', 'inspired', 286],
        ['Semangat & Bangga', 'Rising Game', 'rising-game', 112],
        ['Semangat & Bangga', 'Heroic Age', 'heroic-age', 97],
        ['Semangat & Bangga', 'Dreams Become Real', 'dreams-become-real', 410],
        ['Semangat & Bangga', 'Fresh Air', 'fresh-air', 294],
        ['Semangat & Bangga', 'Wholesome', 'wholesome', 363],
    ];

    public function run(): void
    {
        foreach (self::TRACKS as [$category, $title, $file, $seconds]) {
            MusicTrack::updateOrCreate(['path' => "/audio/latar/{$file}.m4a"], [
                'title' => $title,
                'artist' => 'Kevin MacLeod',
                'mood' => null,
                'category' => $category,
                'duration_seconds' => $seconds,
                'license_note' => sprintf(self::CREDIT, $title),
                'is_active' => true,
            ]);
        }

        foreach (self::LEGACY_CATEGORIES as $path => $category) {
            MusicTrack::query()->where('path', $path)->whereNull('category')->update(['category' => $category]);
        }

        // Catatan internal lama ("ganti dengan lisensi resmi") bukan kredit, jangan tampil ke pengguna.
        MusicTrack::query()->where('license_note', 'like', 'Bebas royalti%')->update(['license_note' => null]);
    }
}
