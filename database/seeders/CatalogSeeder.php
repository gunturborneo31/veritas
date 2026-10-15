<?php

namespace Database\Seeders;

use App\Enums\TemplateTier;
use App\Models\Category;
use App\Models\Component;
use App\Models\MusicTrack;
use App\Models\Template;
use Illuminate\Database\Seeder;

/**
 * Isi katalog rilis pertama: kategori, komponen, varian (Paket 2), pustaka musik,
 * dan 4 template sesuai PRD.
 */
class CatalogSeeder extends Seeder
{
    public function run(): void
    {
        $categories = $this->seedCategories();
        $components = $this->seedComponents();
        $this->seedVariants($components);
        $tracks = $this->seedMusic();
        $this->seedTemplates($categories, $components, $tracks);
    }

    /**
     * @return array<string, Category>
     */
    private function seedCategories(): array
    {
        $rows = [
            ['ulang-tahun', 'Ulang Tahun', '🎂'],
            ['pasangan', 'Pasangan', '💌'],
            ['keluarga', 'Keluarga', '🏡'],
            ['hari-raya', 'Hari Raya', '🌙'],
            // Momen yang banyak dicari (riset pasar) — tampil sebagai "segera hadir" sampai ada templatenya.
            ['minta-maaf', 'Minta Maaf', '🙏'],
            ['kangen', 'Kangen', '💭'],
            ['nembak', 'Nembak', '💘'],
            ['wisuda', 'Wisuda', '🎓'],
            ['terima-kasih', 'Terima Kasih', '🌷'],
        ];

        return collect($rows)->mapWithKeys(fn (array $row, int $index): array => [
            $row[0] => Category::updateOrCreate(['slug' => $row[0]], ['name' => $row[1], 'emoji' => $row[2], 'sort_order' => $index]),
        ])->all();
    }

    /**
     * @return array<string, Component>
     */
    private function seedComponents(): array
    {
        $definitions = [
            'sampul' => ['Sampul', 'Halaman pembuka dengan judul dan foto utama.', 'auto_awesome', [
                ['name' => 'title', 'type' => 'text', 'label' => 'Judul sampul', 'placeholder' => 'Selamat Ulang Tahun!', 'required' => true, 'max' => 60],
                ['name' => 'subtitle', 'type' => 'text', 'label' => 'Subjudul', 'max' => 90],
                ['name' => 'photo', 'type' => 'image', 'label' => 'Foto sampul'],
            ]],
            'gembok_pin' => ['Gembok PIN', 'Kunci ucapan dengan PIN 4–6 digit dan pertanyaan rahasia.', 'lock', [
                ['name' => 'enabled', 'type' => 'toggle', 'label' => 'Aktifkan gembok'],
                ['name' => 'question', 'type' => 'text', 'label' => 'Pertanyaan rahasia', 'placeholder' => 'Tanggal pertama kita ketemu? (DDMM)', 'max' => 120],
                ['name' => 'hint', 'type' => 'text', 'label' => 'Petunjuk tambahan', 'max' => 160],
                ['name' => 'pin', 'type' => 'pin', 'label' => 'Kode PIN (4–6 angka)', 'min' => 4, 'max' => 6, 'required' => true],
            ]],
            'kunci_waktu' => ['Kunci Waktu', 'Ucapan baru bisa dibuka pada tanggal & jam tertentu.', 'schedule', [
                ['name' => 'unlock_at', 'type' => 'datetime', 'label' => 'Terbuka pada', 'required' => true],
                ['name' => 'waiting_message', 'type' => 'text', 'label' => 'Pesan saat menunggu', 'max' => 120],
            ]],
            'foto_bingkai' => ['Foto Berbingkai', 'Satu foto kenangan dalam bingkai polaroid.', 'photo_frame', [
                ['name' => 'photo', 'type' => 'image', 'label' => 'Foto', 'required' => true],
                ['name' => 'caption', 'type' => 'text', 'label' => 'Keterangan foto', 'max' => 80],
            ]],
            'galeri_geser' => ['Galeri Geser', 'Album 3–12 foto yang bisa digeser.', 'photo_library', [
                ['name' => 'photos', 'type' => 'images', 'label' => 'Foto kenangan', 'min' => 3, 'max' => 12, 'required' => true],
            ]],
            'surat' => ['Surat', 'Kata pembuka dan surat dari hati.', 'mail', [
                ['name' => 'greeting_line', 'type' => 'text', 'label' => 'Sapaan pembuka', 'placeholder' => 'Untuk kamu yang paling istimewa,', 'max' => 80],
                ['name' => 'body', 'type' => 'textarea', 'label' => 'Isi surat', 'required' => true, 'max' => 1500],
                ['name' => 'signature', 'type' => 'text', 'label' => 'Penutup / tanda tangan', 'max' => 60],
            ]],
            'pesan_berlapis' => ['Pesan Berlapis', 'Pesan yang terbuka satu per satu setiap diketuk.', 'layers', [
                ['name' => 'layers', 'type' => 'list', 'label' => 'Lapisan pesan', 'min' => 2, 'max' => 6, 'item_max' => 300, 'required' => true],
            ]],
            'musik_latar' => ['Musik Latar', 'Lagu dari pustaka berlisensi, diputar setelah ucapan dibuka.', 'music_note', [
                ['name' => 'track', 'type' => 'music', 'label' => 'Pilih lagu', 'required' => true],
            ]],
            'pesan_suara' => ['Pesan Suara', 'Rekam ucapan langsung dengan suaramu.', 'mic', [
                ['name' => 'audio', 'type' => 'audio', 'label' => 'Rekaman suara', 'required' => true],
                ['name' => 'note', 'type' => 'text', 'label' => 'Judul rekaman', 'max' => 60],
            ]],
            'tiup_lilin' => ['Tiup Lilin', 'Penerima meniup mikrofon untuk memadamkan lilin.', 'cake', [
                ['name' => 'candles', 'type' => 'text', 'label' => 'Angka di atas kue', 'placeholder' => '24', 'max' => 3],
                ['name' => 'wish', 'type' => 'text', 'label' => 'Pesan setelah lilin padam', 'max' => 140],
            ], ['microphone']],
            'kuis' => ['Kuis Seberapa Kenal Aku', 'Pertanyaan pilihan ganda sebelum lanjut.', 'quiz', [
                ['name' => 'questions', 'type' => 'quiz', 'label' => 'Pertanyaan kuis', 'min' => 1, 'max' => 5, 'required' => true],
                ['name' => 'success_message', 'type' => 'text', 'label' => 'Pesan jika benar semua', 'max' => 120],
            ]],
            'gosok_kartu' => ['Gosok Kartu', 'Lapisan emas yang digosok untuk membuka pesan rahasia.', 'ink_eraser', [
                ['name' => 'hidden_message', 'type' => 'text', 'label' => 'Pesan tersembunyi', 'required' => true, 'max' => 160],
                ['name' => 'cover_label', 'type' => 'text', 'label' => 'Tulisan di lapisan gosok', 'placeholder' => 'Gosok aku!', 'max' => 40],
            ]],
            'kupon_janji' => ['Kupon Janji', 'Kupon yang bisa ditukar penerima kapan saja.', 'confirmation_number', [
                ['name' => 'coupons', 'type' => 'list', 'label' => 'Isi kupon', 'min' => 1, 'max' => 6, 'item_max' => 120, 'required' => true],
            ]],
            'hitung_mundur' => ['Hitung Mundur', 'Hitung mundur menuju momen spesial.', 'timer', [
                ['name' => 'target_at', 'type' => 'datetime', 'label' => 'Menuju tanggal', 'required' => true],
                ['name' => 'label', 'type' => 'text', 'label' => 'Keterangan', 'max' => 80],
            ]],
            'linimasa' => ['Linimasa Foto', 'Perjalanan kenangan dari masa ke masa.', 'timeline', [
                ['name' => 'moments', 'type' => 'images', 'label' => 'Momen (isi keterangan dengan tahun/cerita)', 'min' => 2, 'max' => 10, 'required' => true],
            ]],
            'sertifikat' => ['Sertifikat Unduhan', 'Sertifikat lucu yang bisa diunduh penerima.', 'workspace_premium', [
                ['name' => 'title', 'type' => 'text', 'label' => 'Judul sertifikat', 'placeholder' => 'Sahabat Terbaik Sedunia', 'required' => true, 'max' => 60],
                ['name' => 'description', 'type' => 'text', 'label' => 'Keterangan', 'max' => 140],
            ]],
            'tanya_jawab' => ['Satu Pertanyaan', 'Pertanyaan ya/tidak — tombol "tidak" kabur saat mau ditekan. Jawabannya dikirim ke kamu.', 'how_to_vote', [
                ['name' => 'question', 'type' => 'text', 'label' => 'Pertanyaannya', 'placeholder' => 'Mau jadi pacarku?', 'required' => true, 'max' => 90],
                ['name' => 'yes_label', 'type' => 'text', 'label' => 'Tulisan tombol "ya"', 'placeholder' => 'Mau! 💘', 'max' => 24],
                ['name' => 'no_label', 'type' => 'text', 'label' => 'Tulisan tombol "tidak"', 'placeholder' => 'Nggak', 'max' => 24],
                ['name' => 'yes_message', 'type' => 'text', 'label' => 'Pesan setelah dijawab "ya"', 'placeholder' => 'Yeay! Aku tahu kamu pasti mau 🥰', 'max' => 140],
            ]],
            'penutup' => ['Penutup', 'Kalimat penutup dan tombol balas untuk penerima.', 'favorite', [
                ['name' => 'closing', 'type' => 'textarea', 'label' => 'Kalimat penutup', 'max' => 400],
                ['name' => 'allow_reply', 'type' => 'toggle', 'label' => 'Izinkan penerima membalas'],
            ]],
        ];

        return collect($definitions)->mapWithKeys(fn (array $definition, string $key): array => [
            $key => Component::updateOrCreate(['key' => $key], [
                'name' => $definition[0],
                'description' => $definition[1],
                'icon' => $definition[2],
                'schema' => $definition[3],
                'permissions' => $definition[4] ?? [],
                'is_active' => true,
            ]),
        ])->all();
    }

    /**
     * Varian gaya disiapkan sekarang, aktif di Paket 2. Varian default = gaya Paket 1.
     *
     * @param  array<string, Component>  $components
     */
    private function seedVariants(array $components): void
    {
        $variants = [
            'sampul' => [['pastel_peach', 'Pastel Peach Bloom', true], ['midnight', 'Midnight Starry Glow', false], ['retro_scrapbook', 'Retro Scrapbook', false], ['hologram', '3D Hologram Glitz', false]],
            'galeri_geser' => [['floating_polaroid', 'Floating Polaroid', true], ['film_strip', 'Film Strip Roll', false], ['memory_cube', '3D Memory Cube', false]],
            'gembok_pin' => [['pin_pad', 'PIN Angka', true], ['envelope_seal', 'Segel Amplop', false]],
            'tiup_lilin' => [['classic_cake', 'Kue Klasik', true], ['ultra_candle', 'Efek Lilin Ultra', false]],
        ];

        foreach ($variants as $componentKey => $rows) {
            foreach ($rows as [$key, $name, $isDefault]) {
                $components[$componentKey]->variants()->updateOrCreate(['key' => $key], [
                    'name' => $name,
                    'package' => $isDefault ? 1 : 2,
                    'is_default' => $isDefault,
                ]);
            }
        }
    }

    /**
     * File audio belum disertakan: letakkan di storage/app/public/music/ dengan nama yang sama.
     *
     * @return array<string, MusicTrack>
     */
    private function seedMusic(): array
    {
        $rows = [
            'akustik' => ['Selamat Ulang Tahun (Akustik)', 'Hangat', 195, 'music/selamat-ulang-tahun-akustik.mp3'],
            'kotak_musik' => ['Kotak Musik Ajaib', 'Melodis', 105, 'music/kotak-musik-ajaib.mp3'],
            'jazz' => ['Jazz Kafe Sore', 'Santai', 150, 'music/jazz-kafe-sore.mp3'],
            'takbir' => ['Gema Takbir Instrumental', 'Khidmat', 180, 'music/gema-takbir-instrumental.mp3'],
            'teduh' => ['Gerimis Teduh (Piano)', 'Teduh', 160, 'music/gerimis-teduh-piano.mp3'],
            'rindu' => ['Rindu Lo-fi', 'Rindu', 170, 'music/rindu-lofi.mp3'],
            'langkah_baru' => ['Langkah Baru (Orkestra Ringan)', 'Bangga', 185, 'music/langkah-baru-orkestra.mp3'],
            'terima_kasih' => ['Terima Kasih (Akustik Hangat)', 'Hangat', 160, 'music/terima-kasih-akustik.mp3'],
        ];

        return collect($rows)->mapWithKeys(fn (array $row, string $key): array => [
            $key => MusicTrack::updateOrCreate(['path' => $row[3]], [
                'title' => $row[0],
                'artist' => 'Pustaka KartuKita',
                'mood' => $row[1],
                'duration_seconds' => $row[2],
                'license_note' => 'Bebas royalti (ganti dengan lisensi resmi)',
                'is_active' => true,
            ]),
        ])->all();
    }

    /**
     * @param  array<string, Category>  $categories
     * @param  array<string, Component>  $components
     * @param  array<string, MusicTrack>  $tracks
     */
    private function seedTemplates(array $categories, array $components, array $tracks): void
    {
        $templates = [
            [
                'slug' => 'tiup-lilin',
                'category' => 'ulang-tahun',
                'name' => 'Tiup Lilin',
                'tagline' => 'Penerima meniup mikrofon untuk memadamkan lilin kue',
                'story' => 'Buka gembok, tiup lilinnya sungguhan lewat mikrofon, lalu temukan surat dan sertifikat kejutan.',
                'description' => 'Buat hari spesialnya tak terlupakan. Penerima membuka gembok, meniup lilin lewat mikrofon HP, lalu membaca suratmu dan mengunduh sertifikat lucu.',
                'tier' => TemplateTier::Interaktif,
                'price' => 35000,
                'duration_minutes' => 3,
                'theme' => ['accent' => '#c94a38', 'soft' => '#ffdad4', 'gradient' => ['#fff4e8', '#ffe0d1', '#ffc7b8'], 'emoji' => '🎂', 'motif' => 'confetti'],
                'highlight_icon' => 'mic',
                'highlight_label' => 'Tiup Mikrofon',
                'tags' => ['Playful', 'Audio'],
                'slots' => [
                    ['sampul', 'sampul'], ['gembok', 'gembok_pin'], ['lilin', 'tiup_lilin'], ['foto', 'foto_bingkai'],
                    ['surat', 'surat'], ['musik', 'musik_latar'], ['sertifikat', 'sertifikat'], ['penutup', 'penutup'],
                ],
                'sample' => [
                    'occasions' => ['Ulang Tahun', 'Sweet Seventeen', 'Ulang Tahun Pernikahan'],
                    'base' => ['recipient_name' => 'Sarah', 'sender_name' => 'Nadia', 'occasion' => 'Ulang Tahun'],
                    'contributions' => [
                        ['name' => 'Dinda', 'message' => 'Happy birthday Sarah! Semoga makin sukses dan traktirannya jadi ya 🎂', 'photo' => null],
                        ['name' => 'Bimo', 'message' => 'Panjang umur, sehat selalu. Kangen nongkrong bareng!', 'photo' => ['url' => '/images/samples/kafe.svg']],
                        ['name' => 'Tante Rina', 'message' => 'Selamat ulang tahun, Nak. Doa terbaik dari kami sekeluarga 🤍', 'photo' => null],
                    ],
                    'components' => [
                        'sampul' => ['title' => 'Selamat Ulang Tahun, Sarah!', 'subtitle' => 'Ada kejutan kecil untukmu ✨'],
                        'lilin' => ['candles' => '24', 'wish' => 'Semoga semua harapanmu terkabul!'],
                        'foto' => ['photo' => ['url' => '/images/samples/pantai.svg'], 'caption' => 'Liburan Jogja 2023'],
                        'surat' => ['greeting_line' => 'Sarah tersayang,', 'body' => 'Terima kasih sudah selalu ada dengan tawa paling tulus. Semoga tahun ini membawamu lebih dekat ke semua mimpimu.', 'signature' => 'Sahabatmu, Nadia'],
                        'musik' => ['track' => $tracks['akustik']->id],
                        'sertifikat' => ['title' => 'Sahabat Terbaik Sedunia', 'description' => 'Diberikan atas tawa, pelukan, dan kesabaran tanpa batas.'],
                        'penutup' => ['closing' => 'Selamat ulang tahun! Peluk jauh dari sini 🤍', 'allow_reply' => true],
                    ],
                ],
            ],
            [
                'slug' => 'kado-berlapis',
                'category' => 'pasangan',
                'name' => 'Kado Berlapis',
                'tagline' => 'Pesan yang terbuka lapis demi lapis',
                'story' => 'Setiap ketukan membuka lapisan baru: pesan rahasia, album kenangan, kuis, sampai kupon janji.',
                'description' => 'Seperti membuka kado berlapis: setiap ketukan membuka pesan baru, galeri kenangan, kuis seru, dan kupon janji yang bisa ditukar.',
                'tier' => TemplateTier::Interaktif,
                'price' => 29000,
                'duration_minutes' => 4,
                'theme' => ['accent' => '#9c2f5f', 'soft' => '#ffd9ea', 'gradient' => ['#fff0f6', '#ffd9ea', '#f6b8d3'], 'emoji' => '🎁', 'motif' => 'hearts'],
                'highlight_icon' => 'redeem',
                'highlight_label' => 'Buka Lapisan',
                'tags' => ['Romantis', 'Kejutan'],
                'slots' => [
                    ['sampul', 'sampul'], ['lapisan', 'pesan_berlapis'], ['galeri', 'galeri_geser'], ['kuis', 'kuis', false],
                    ['kupon', 'kupon_janji'], ['musik', 'musik_latar'], ['penutup', 'penutup'],
                ],
                'sample' => [
                    'occasions' => ['Anniversary', 'Hari Valentine', 'Tanpa Alasan'],
                    'base' => ['recipient_name' => 'Dimas', 'sender_name' => 'Rani', 'occasion' => 'Anniversary'],
                    'components' => [
                        'sampul' => ['title' => 'Untuk Dimas', 'subtitle' => 'Buka pelan-pelan ya'],
                        'lapisan' => ['layers' => ['Dua tahun lalu, kita cuma dua orang asing di halte.', 'Sekarang kamu orang pertama yang kuceritakan semuanya.', 'Terima kasih sudah memilih bertahan. Aku sayang kamu.']],
                        'galeri' => ['photos' => [
                            ['media' => ['url' => '/images/samples/pantai.svg'], 'caption' => 'Pantai pertama kita'],
                            ['media' => ['url' => '/images/samples/kafe.svg'], 'caption' => 'Kopi sore di Braga'],
                            ['media' => ['url' => '/images/samples/kota-malam.svg'], 'caption' => 'Pulang larut, tetap senyum'],
                        ]],
                        'kuis' => ['questions' => [['question' => 'Di mana kita pertama kali makan bareng?', 'options' => ['Warung bakso', 'Kafe Braga', 'Kantin kampus'], 'answer' => 2]], 'success_message' => 'Kamu memang paling kenal aku!'],
                        'kupon' => ['coupons' => ['1x pijat pundak tanpa protes', '1x traktir martabak', 'Bebas pilih film malam minggu']],
                        'musik' => ['track' => $tracks['jazz']->id],
                        'penutup' => ['closing' => 'Happy anniversary, sayang.', 'allow_reply' => true],
                    ],
                ],
            ],
            [
                'slug' => 'mesin-waktu',
                'category' => 'keluarga',
                'name' => 'Mesin Waktu',
                'tagline' => 'Linimasa kenangan dan pesan suara untuk keluarga',
                'story' => 'Putar balik waktu: dari foto lama sampai hari ini, ditutup suara dan surat dari hatimu.',
                'description' => 'Ajak Ibu atau keluarga tersayang menyusuri kenangan dari masa ke masa, lengkap dengan pesan suara dari hatimu.',
                'tier' => TemplateTier::Klasik,
                'price' => 29000,
                'duration_minutes' => 4,
                'theme' => ['accent' => '#8a5a12', 'soft' => '#ffe8c2', 'gradient' => ['#fdf7ec', '#f6e6c8', '#ecd2a6'], 'emoji' => '🕰️', 'motif' => 'film'],
                'highlight_icon' => 'history',
                'highlight_label' => 'Linimasa Foto',
                'tags' => ['Keluarga', 'Kenangan'],
                'slots' => [
                    ['sampul', 'sampul'], ['linimasa', 'linimasa'], ['suara', 'pesan_suara', false], ['surat', 'surat'],
                    ['musik', 'musik_latar'], ['penutup', 'penutup'],
                ],
                'sample' => [
                    'occasions' => ['Hari Ibu', 'Hari Ayah', 'Ulang Tahun Orang Tua'],
                    'base' => ['recipient_name' => 'Ibu', 'sender_name' => 'Rian', 'occasion' => 'Hari Ibu'],
                    'components' => [
                        'sampul' => ['title' => 'Untuk Ibu, Perempuan Terhebat', 'subtitle' => 'Selamat Hari Ibu', 'photo' => ['url' => '/images/samples/keluarga-kini.svg']],
                        'linimasa' => ['moments' => [
                            ['media' => ['url' => '/images/samples/keluarga-lama.svg'], 'caption' => '1998 — Pertama kali Ibu menggendongku'],
                            ['media' => ['url' => '/images/samples/pantai.svg'], 'caption' => '2010 — Liburan keluarga ke Pangandaran'],
                            ['media' => ['url' => '/images/samples/keluarga-kini.svg'], 'caption' => '2026 — Masih dan selalu bersama'],
                        ]],
                        'surat' => ['greeting_line' => 'Ibu,', 'body' => 'Terima kasih untuk setiap doa yang tidak pernah putus. Semua yang aku capai hari ini berawal dari pelukan Ibu.', 'signature' => 'Anakmu, Rian'],
                        'musik' => ['track' => $tracks['kotak_musik']->id],
                        'penutup' => ['closing' => 'Sehat selalu ya, Bu.', 'allow_reply' => true],
                    ],
                ],
            ],
            [
                'slug' => 'lebaran',
                'category' => 'hari-raya',
                'name' => 'Ketupat Lebaran',
                'tagline' => 'Ucapan Idulfitri dengan kartu gosok berisi doa',
                'story' => 'Maaf lahir batin yang terbuka pelan-pelan, lalu gosok kartu emas untuk menemukan doa tersembunyi.',
                'description' => 'Sampaikan maaf lahir batin dengan pesan berlapis dan kartu gosok emas yang menyimpan doa tersembunyi.',
                'tier' => TemplateTier::Klasik,
                'price' => 25000,
                'duration_minutes' => 2,
                'theme' => ['accent' => '#1f6b52', 'soft' => '#d6f0e3', 'gradient' => ['#f3fbf6', '#dff3e8', '#f7e7b6'], 'emoji' => '🌙', 'motif' => 'stars'],
                'highlight_icon' => 'auto_awesome',
                'highlight_label' => 'Gosok Kartu',
                'tags' => ['Hari Raya', 'Berkah'],
                'slots' => [
                    ['sampul', 'sampul'], ['lapisan', 'pesan_berlapis'], ['gosok', 'gosok_kartu'], ['musik', 'musik_latar', false], ['penutup', 'penutup'],
                ],
                'sample' => [
                    'occasions' => ['Idulfitri', 'Iduladha', 'Ramadan'],
                    'base' => ['recipient_name' => 'Keluarga Besar', 'sender_name' => 'Keluarga Rahman', 'occasion' => 'Idulfitri'],
                    'components' => [
                        'sampul' => ['title' => 'Selamat Idulfitri', 'subtitle' => 'Mohon maaf lahir dan batin'],
                        'lapisan' => ['layers' => ['Taqabbalallahu minna wa minkum.', 'Semoga kita dipertemukan lagi dengan Ramadan berikutnya.']],
                        'gosok' => ['hidden_message' => 'Semoga rezeki dan kebahagiaan melimpah untuk keluarga kalian 🤲', 'cover_label' => 'Gosok untuk doa'],
                        'musik' => ['track' => $tracks['takbir']->id],
                        'penutup' => ['closing' => 'Salam hangat dari kami sekeluarga.', 'allow_reply' => true],
                    ],
                ],
            ],
            [
                'slug' => 'setelah-hujan',
                'category' => 'minta-maaf',
                'name' => 'Setelah Hujan',
                'tagline' => 'Permintaan maaf yang tulus, lapis demi lapis',
                'story' => 'Pengakuan yang terbuka pelan-pelan, janji tersembunyi di balik kartu gosok, lalu biarkan dia menjawab.',
                'description' => 'Untuk maaf yang susah diucapkan langsung. Penerima membuka pengakuanmu satu per satu, membaca suratmu, menggosok kartu untuk menemukan janjimu, dan bisa membalas langsung di akhir.',
                'tier' => TemplateTier::Interaktif,
                'price' => 29000,
                'duration_minutes' => 3,
                'theme' => ['accent' => '#3d5a80', 'soft' => '#dbe6f2', 'gradient' => ['#f1f5fa', '#dde7f1', '#f8e9dc'], 'emoji' => '☔', 'motif' => 'rain'],
                'highlight_icon' => 'umbrella',
                'highlight_label' => 'Gosok Janji',
                'tags' => ['Tulus', 'Deep Talk'],
                'slots' => [
                    ['sampul', 'sampul'], ['gembok', 'gembok_pin'], ['pengakuan', 'pesan_berlapis', true, 'Pengakuan'], ['surat', 'surat'],
                    ['janji', 'gosok_kartu', true, 'Janji Tersembunyi'], ['kupon', 'kupon_janji', false, 'Janji Perbaikan'],
                    ['musik', 'musik_latar', false], ['penutup', 'penutup', true, 'Jawabanmu'],
                ],
                'sample' => [
                    'occasions' => ['Minta Maaf', 'Baikan', 'Salah Paham'],
                    'base' => ['recipient_name' => 'Laras', 'sender_name' => 'Bima', 'occasion' => 'Minta Maaf'],
                    'components' => [
                        'sampul' => ['title' => 'Laras, boleh aku bicara sebentar?', 'subtitle' => 'Ada yang ingin kusampaikan dengan jujur', 'photo' => ['url' => '/images/samples/hujan.svg']],
                        'pengakuan' => ['layers' => [
                            'Kemarin aku bicara dengan nada yang tidak seharusnya.',
                            'Aku sibuk membela diri, padahal kamu cuma ingin didengar.',
                            'Aku salah. Dan aku tidak mau itu jadi kebiasaan.',
                        ]],
                        'surat' => ['greeting_line' => 'Laras,', 'body' => 'Aku tidak minta kamu langsung lupa. Aku cuma ingin kamu tahu bahwa aku sadar, aku menyesal, dan aku mau memperbaikinya pelan-pelan, dengan caramu.', 'signature' => 'Bima'],
                        'janji' => ['hidden_message' => 'Mulai hari ini, aku dengar dulu sampai selesai sebelum menjawab.', 'cover_label' => 'Gosok untuk janjiku'],
                        'kupon' => ['coupons' => ['1x curhat tanpa disela', '1x jalan sore ke tempat favoritmu', 'Hak veto memilih makan malam']],
                        'musik' => ['track' => $tracks['teduh']->id],
                        'penutup' => ['closing' => 'Apa pun jawabanmu, aku akan menghargainya. Terima kasih sudah membaca sampai sini.', 'allow_reply' => true],
                    ],
                ],
            ],
            [
                'slug' => 'satu-pertanyaan',
                'category' => 'nembak',
                'name' => 'Satu Pertanyaan',
                'tagline' => 'Nembak dengan tombol "nggak" yang selalu kabur',
                'story' => 'Kenangan, surat pengakuan, lalu satu pertanyaan penting — tombol "nggak"-nya kabur tiap mau ditekan. Jawabannya langsung sampai ke kamu.',
                'description' => 'Untuk yang mau nyatain perasaan dengan cara manis dan lucu. Penerima melihat kenanganmu, membaca suratmu, lalu menjawab satu pertanyaan. Tombol "nggak" tidak bisa ditekan, dan jawabannya dikirim ke dashboard serta email kamu.',
                'tier' => TemplateTier::Interaktif,
                'price' => 29000,
                'duration_minutes' => 3,
                'theme' => ['accent' => '#d6336c', 'soft' => '#ffd6e5', 'gradient' => ['#fff4f8', '#ffe1ec', '#ffd0df'], 'emoji' => '💘', 'motif' => 'hearts'],
                'highlight_icon' => 'how_to_vote',
                'highlight_label' => 'Tombol Kabur',
                'tags' => ['Viral', 'Lucu'],
                'slots' => [
                    ['sampul', 'sampul'], ['gembok', 'gembok_pin'], ['galeri', 'galeri_geser', false, 'Momen yang Bikin Aku Jatuh Hati'],
                    ['surat', 'surat', true, 'Pengakuan'], ['tanya', 'tanya_jawab'], ['sertifikat', 'sertifikat', false, 'Sertifikat Resmi'],
                    ['musik', 'musik_latar', false], ['penutup', 'penutup'],
                ],
                'sample' => [
                    'occasions' => ['Nembak', 'Confess', 'Ajak Kencan'],
                    'base' => ['recipient_name' => 'Kirana', 'sender_name' => 'Raka', 'occasion' => 'Nembak'],
                    'components' => [
                        'sampul' => ['title' => 'Kirana, ada yang mau aku tanyain…', 'subtitle' => 'Tapi baca pelan-pelan dulu ya', 'photo' => ['url' => '/images/samples/senja.svg']],
                        'galeri' => ['photos' => [
                            ['media' => ['url' => '/images/samples/kafe.svg'], 'caption' => 'Pertama kali ngopi bareng'],
                            ['media' => ['url' => '/images/samples/kota-malam.svg'], 'caption' => 'Pulang bareng habis hujan'],
                            ['media' => ['url' => '/images/samples/pantai.svg'], 'caption' => 'Liburan kelas yang seru'],
                        ]],
                        'surat' => ['greeting_line' => 'Kirana,', 'body' => 'Dari semua obrolan random kita, aku sadar satu hal: hariku selalu lebih ringan kalau ada kamu. Jadi aku mau berani jujur sekali ini.', 'signature' => 'Raka'],
                        'tanya' => ['question' => 'Mau jadi pacarku?', 'yes_label' => 'Mau! 💘', 'no_label' => 'Nggak', 'yes_message' => 'YES! Aku tahu kamu pasti mau 🥰 Sampai ketemu besok ya!'],
                        'sertifikat' => ['title' => 'Resmi Jadian', 'description' => 'Berlaku mulai hari ini, tanpa masa kedaluwarsa.'],
                        'musik' => ['track' => $tracks['akustik']->id],
                        'penutup' => ['closing' => 'Apa pun jawabanmu, terima kasih sudah jadi bagian terbaik hariku.', 'allow_reply' => true],
                    ],
                ],
            ],
            [
                'slug' => 'lempar-toga',
                'category' => 'wisuda',
                'name' => 'Lempar Toga',
                'tagline' => 'Rayakan perjalanan panjang menuju toga',
                'story' => 'Linimasa perjuangan dari maba sampai sidang, surat bangga darimu, kartu gosok berisi hadiah kelulusan, lalu sertifikat “Wisudawan Paling Tangguh”.',
                'description' => 'Untuk sahabat, pasangan, adik, atau anak yang baru lulus. Ajak dia menengok perjalanannya lewat linimasa foto, baca suratmu, gosok kartu untuk membuka hadiah kelulusan, dan unduh sertifikat lucunya.',
                'tier' => TemplateTier::Interaktif,
                'price' => 29000,
                'duration_minutes' => 3,
                'theme' => ['accent' => '#24386b', 'soft' => '#dde4f6', 'gradient' => ['#f5f7fc', '#e3e9f7', '#f6e7c1'], 'emoji' => '🎓', 'motif' => 'confetti'],
                'highlight_icon' => 'school',
                'highlight_label' => 'Lempar Toga',
                'tags' => ['Bangga', 'Kelulusan'],
                'slots' => [
                    ['sampul', 'sampul'], ['perjalanan', 'linimasa', true, 'Perjalanan Menuju Toga'], ['surat', 'surat', true, 'Surat Bangga'],
                    ['hadiah', 'gosok_kartu', true, 'Hadiah Kelulusan'], ['suara', 'pesan_suara', false], ['sertifikat', 'sertifikat'],
                    ['musik', 'musik_latar', false], ['penutup', 'penutup'],
                ],
                'sample' => [
                    'occasions' => ['Wisuda S1', 'Wisuda SMA', 'Lulus Sidang', 'Wisuda S2'],
                    'base' => ['recipient_name' => 'Kevin', 'sender_name' => 'Tim Kosan Melati', 'occasion' => 'Wisuda S1'],
                    'contributions' => [
                        ['name' => 'Bang Dodi', 'message' => 'Akhirnya lulus juga, Vin! Revisi bab 4 yang bikin begadang terbayar lunas 🎓', 'photo' => null],
                        ['name' => 'Bu Kos', 'message' => 'Selamat ya Nak, sukses terus di tempat kerja baru.', 'photo' => null],
                    ],
                    'components' => [
                        'sampul' => ['title' => 'Selamat Wisuda, Sarjana Kevin!', 'subtitle' => 'Toganya sudah kamu lempar — sekarang giliran kami yang bangga', 'photo' => ['url' => '/images/samples/wisuda.svg']],
                        'perjalanan' => ['moments' => [
                            ['media' => ['url' => '/images/samples/kampus.svg'], 'caption' => '2022 — Maba polos yang nyasar cari ruang kuliah'],
                            ['media' => ['url' => '/images/samples/kota-malam.svg'], 'caption' => '2025 — Begadang revisi, ditemani kopi saset'],
                            ['media' => ['url' => '/images/samples/wisuda.svg'], 'caption' => '2026 — Resmi bergelar sarjana!'],
                        ]],
                        'surat' => ['greeting_line' => 'Kevin, Sang Sarjana,', 'body' => 'Kami lihat sendiri malam-malam panjangmu: revisi yang tak kunjung selesai, dosen yang susah ditemui, dan rasa ingin menyerah. Tapi kamu tetap jalan. Hari ini kamu membuktikan bahwa pelan-pelan pun tetap sampai.', 'signature' => 'Tim Kosan Melati'],
                        'hadiah' => ['hidden_message' => 'Hadiahmu: traktiran all-you-can-eat bareng anak kosan! 🍣🎉', 'cover_label' => 'Gosok untuk hadiah'],
                        'sertifikat' => ['title' => 'Wisudawan Paling Tangguh', 'description' => 'Atas keberhasilan bertahan dari revisi, deadline, dan godaan rebahan.'],
                        'musik' => ['track' => $tracks['langkah_baru']->id],
                        'penutup' => ['closing' => 'Selamat memulai babak baru. Dunia kerja, kamu siap! 🎓', 'allow_reply' => true],
                    ],
                ],
            ],
            [
                'slug' => 'seikat-terima-kasih',
                'category' => 'terima-kasih',
                'name' => 'Seikat Terima Kasih',
                'tagline' => 'Untuk orang yang terlalu sering kita lupa ucapkan terima kasih',
                'story' => 'Seikat bunga digital berisi hal-hal kecil yang kamu syukuri darinya, dibuka satu per satu, ditutup surat dan janji balas budi.',
                'description' => 'Untuk guru, mentor, orang tua, sahabat, atau rekan kerja yang sudah banyak membantu. Setiap “kelopak” membuka satu hal yang kamu syukuri darinya, lalu ditutup surat tulus dan kupon balas budi.',
                'tier' => TemplateTier::Klasik,
                'price' => 25000,
                'duration_minutes' => 3,
                'theme' => ['accent' => '#127475', 'soft' => '#d5efee', 'gradient' => ['#f3fbfa', '#dcf1ef', '#fde8d7'], 'emoji' => '🌷', 'motif' => 'petals'],
                'highlight_icon' => 'local_florist',
                'highlight_label' => 'Buka Kelopak',
                'tags' => ['Tulus', 'Apresiasi'],
                'slots' => [
                    ['sampul', 'sampul'], ['kelopak', 'pesan_berlapis', true, 'Hal-hal yang Kusyukuri Darimu'], ['galeri', 'galeri_geser', false, 'Momen Bersamamu'],
                    ['suara', 'pesan_suara', false], ['surat', 'surat'], ['balas_budi', 'kupon_janji', false, 'Janji Balas Budi'],
                    ['musik', 'musik_latar', false], ['penutup', 'penutup'],
                ],
                'sample' => [
                    'occasions' => ['Terima Kasih', 'Hari Guru', 'Perpisahan Kantor', 'Apresiasi Mentor'],
                    'base' => ['recipient_name' => 'Bu Ratna', 'sender_name' => 'Alya & teman-teman 12 IPA 2', 'occasion' => 'Hari Guru'],
                    'components' => [
                        'sampul' => ['title' => 'Untuk Bu Ratna, Terima Kasih', 'subtitle' => 'Seikat bunga kecil dari murid-murid yang dulu sering telat', 'photo' => ['url' => '/images/samples/buket.svg']],
                        'kelopak' => ['layers' => [
                            'Terima kasih sudah sabar menjelaskan rumus yang sama sampai lima kali.',
                            'Terima kasih sudah percaya kami bisa, waktu kami sendiri tidak yakin.',
                            'Terima kasih untuk “Ibu tunggu di kelas ya” yang selalu bikin kami lari dari kantin.',
                        ]],
                        'galeri' => ['photos' => [
                            ['media' => ['url' => '/images/samples/kampus.svg'], 'caption' => 'Sekolah tempat semuanya dimulai'],
                            ['media' => ['url' => '/images/samples/buket.svg'], 'caption' => 'Bunga kecil untuk guru besar'],
                            ['media' => ['url' => '/images/samples/kafe.svg'], 'caption' => 'Reuni kecil bareng Ibu'],
                        ]],
                        'surat' => ['greeting_line' => 'Bu Ratna yang kami sayangi,', 'body' => 'Mungkin Ibu tidak ingat semua nama kami, tapi kami ingat semua pelajaran dari Ibu — termasuk yang tidak ada di buku. Sekarang kami sudah tersebar ke mana-mana, dan selalu ada sedikit Ibu di cara kami bekerja.', 'signature' => 'Alya & teman-teman 12 IPA 2'],
                        'balas_budi' => ['coupons' => ['1x traktir kopi saat Ibu ke kota kami', 'Jadi pembicara tamu untuk adik kelas kapan pun diminta']],
                        'musik' => ['track' => $tracks['terima_kasih']->id],
                        'penutup' => ['closing' => 'Sehat selalu, Bu. Terima kasih sudah jadi bagian terbaik masa sekolah kami. 🌷', 'allow_reply' => true],
                    ],
                ],
            ],
            [
                'slug' => 'jarak-rindu',
                'category' => 'kangen',
                'name' => 'Jarak & Rindu',
                'tagline' => 'Untuk yang sedang berjauhan',
                'story' => 'Hitung mundur ke pertemuan berikutnya, kenangan yang bikin rindu, suaramu, dan janji saat kalian bertemu lagi.',
                'description' => 'Untuk pasangan LDR, sahabat di kota lain, atau keluarga di perantauan. Dibuka dengan hitung mundur ke hari bertemu, lalu album kenangan, pesan suara, surat, dan daftar hal yang akan kalian lakukan bersama nanti.',
                'tier' => TemplateTier::Interaktif,
                'price' => 29000,
                'duration_minutes' => 4,
                'theme' => ['accent' => '#5b4b8a', 'soft' => '#e6e0f6', 'gradient' => ['#f6f3fc', '#e6e0f6', '#ffe3ef'], 'emoji' => '✈️', 'motif' => 'stars'],
                'highlight_icon' => 'flight',
                'highlight_label' => 'Hitung Mundur',
                'tags' => ['LDR', 'Rindu'],
                'slots' => [
                    ['sampul', 'sampul'], ['mundur', 'hitung_mundur', true, 'Menuju Hari Bertemu'], ['galeri', 'galeri_geser', true, 'Kenangan yang Bikin Rindu'],
                    ['suara', 'pesan_suara', false], ['surat', 'surat'], ['nanti', 'kupon_janji', true, 'Saat Kita Bertemu Nanti'],
                    ['musik', 'musik_latar', false], ['penutup', 'penutup'],
                ],
                'sample' => [
                    'occasions' => ['Kangen', 'LDR', 'Perantauan'],
                    'base' => ['recipient_name' => 'Arga', 'sender_name' => 'Nisa', 'occasion' => 'Kangen'],
                    'components' => [
                        'sampul' => ['title' => 'Arga, 842 km itu jauh ya', 'subtitle' => 'Tapi rinduku lebih jauh lagi', 'photo' => ['url' => '/images/samples/kota-malam.svg']],
                        'mundur' => ['target_at' => '+23 days 10:00', 'label' => 'Sampai kita ketemu di stasiun'],
                        'galeri' => ['photos' => [
                            ['media' => ['url' => '/images/samples/kafe.svg'], 'caption' => 'Kopi terakhir sebelum kamu berangkat'],
                            ['media' => ['url' => '/images/samples/senja.svg'], 'caption' => 'Senja yang kita kejar bareng'],
                            ['media' => ['url' => '/images/samples/pantai.svg'], 'caption' => 'Janji balik ke pantai ini'],
                        ]],
                        'surat' => ['greeting_line' => 'Arga,', 'body' => 'Hari-hariku tetap jalan, tapi selalu ada yang kurang. Terima kasih sudah tetap mengabari walau lelah. Sebentar lagi, ya.', 'signature' => 'Yang menunggumu, Nisa'],
                        'nanti' => ['coupons' => ['Makan seblak di tempat biasa', 'Nonton film yang kita tunda', 'Peluk lama di peron stasiun']],
                        'musik' => ['track' => $tracks['rindu']->id],
                        'penutup' => ['closing' => 'Jaga diri baik-baik di sana. Sampai ketemu sebentar lagi 🤍', 'allow_reply' => true],
                    ],
                ],
            ],
        ];

        foreach ($templates as $index => $data) {
            $template = Template::updateOrCreate(['slug' => $data['slug']], [
                'category_id' => $categories[$data['category']]->id,
                'name' => $data['name'],
                'tagline' => $data['tagline'],
                'story' => $data['story'],
                'description' => $data['description'],
                'tier' => $data['tier'],
                'price' => $data['price'],
                'duration_minutes' => $data['duration_minutes'],
                'theme' => $data['theme'],
                'highlight_icon' => $data['highlight_icon'],
                'highlight_label' => $data['highlight_label'],
                'tags' => $data['tags'],
                'sample_content' => $data['sample'],
                'is_published' => true,
                'sort_order' => $index,
            ]);

            foreach ($data['slots'] as $position => $slot) {
                $template->components()->updateOrCreate(['key' => $slot[0]], [
                    'component_id' => $components[$slot[1]]->id,
                    'position' => $position + 1,
                    'is_required' => $slot[2] ?? true,
                    'title' => $slot[3] ?? null,
                ]);
            }
        }
    }
}
