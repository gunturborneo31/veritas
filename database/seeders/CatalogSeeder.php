<?php

namespace Database\Seeders;

use App\Enums\TemplateTier;
use App\Models\Category;
use App\Models\Component;
use App\Models\MusicTrack;
use App\Models\Series;
use App\Models\Template;
use Illuminate\Database\Seeder;

/**
 * Isi katalog: kategori, komponen, varian, pustaka musik, lalu seri template.
 *
 * Satu seri (mis. "Tiup Lilin") punya beberapa desain. Setiap desain adalah template
 * mandiri dengan jumlah halaman, alur, isi contoh, warna, hiasan, dan huruf sendiri.
 */
class CatalogSeeder extends Seeder
{
    /**
     * @var array<string, MusicTrack>
     */
    private array $tracks = [];

    public function run(): void
    {
        $categories = $this->seedCategories();
        $components = $this->seedComponents();
        $this->seedVariants($components);
        $this->tracks = $this->seedMusic();
        $this->seedSeries($categories, $components);
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
            'kutipan' => ['Kalimat Besar', 'Satu kalimat penting yang tampil sendirian memenuhi layar.', 'format_quote', [
                ['name' => 'quote', 'type' => 'textarea', 'label' => 'Kalimatnya', 'placeholder' => 'Kamu alasan aku percaya hal baik itu nyata.', 'required' => true, 'max' => 220],
                ['name' => 'caption', 'type' => 'text', 'label' => 'Keterangan kecil di bawahnya', 'max' => 100],
            ]],
            'daftar_alasan' => ['Daftar Alasan', 'Alasan-alasan yang dibuka satu per satu oleh penerima.', 'format_list_numbered', [
                ['name' => 'title', 'type' => 'text', 'label' => 'Judul daftar', 'placeholder' => 'Alasan aku sayang kamu', 'max' => 60],
                ['name' => 'reasons', 'type' => 'list', 'label' => 'Alasan-alasannya', 'min' => 3, 'max' => 10, 'item_max' => 160, 'required' => true],
            ]],
            'angka_kita' => ['Angka-Angka Kita', 'Statistik kecil tentang kalian yang menghitung naik saat dibuka.', 'monitoring', [
                ['name' => 'title', 'type' => 'text', 'label' => 'Judul', 'placeholder' => 'Kita dalam angka', 'max' => 60],
                ['name' => 'stats', 'type' => 'list', 'label' => 'Angka & keterangan (awali dengan angka, mis. "730 hari kita bareng")', 'min' => 2, 'max' => 6, 'item_max' => 60, 'required' => true],
                ['name' => 'footnote', 'type' => 'text', 'label' => 'Catatan kaki', 'max' => 100],
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
     * Varian gaya per komponen (disiapkan untuk Paket 2).
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
     */
    private function seedSeries(array $categories, array $components): void
    {
        $catalog = [
            ['tiup-lilin', 'Tiup Lilin', 'Penerima meniup mikrofon untuk memadamkan lilin kue', 'Tiup lilinnya sungguhan lewat mikrofon HP, lalu temukan kejutan di setiap halaman.', $this->tiupLilin()],
            ['kado-berlapis', 'Kado Berlapis', 'Pesan yang terbuka lapis demi lapis', 'Setiap ketukan membuka kejutan baru: pesan rahasia, album kenangan, kuis, sampai kupon janji.', $this->kadoBerlapis()],
            ['mesin-waktu', 'Mesin Waktu', 'Linimasa kenangan untuk keluarga', 'Putar balik waktu lewat foto lama sampai hari ini, ditutup suara dan surat dari hatimu.', $this->mesinWaktu()],
            ['ketupat-lebaran', 'Ketupat Lebaran', 'Ucapan hari raya dengan doa tersembunyi', 'Maaf lahir batin yang terbuka pelan-pelan, lalu gosok kartu emas untuk menemukan doa.', $this->ketupatLebaran()],
            ['setelah-hujan', 'Setelah Hujan', 'Permintaan maaf yang tulus', 'Pengakuan yang terbuka pelan-pelan, janji di balik kartu gosok, lalu biarkan dia menjawab.', $this->setelahHujan()],
            ['satu-pertanyaan', 'Satu Pertanyaan', 'Nembak dengan tombol "nggak" yang selalu kabur', 'Kenangan, pengakuan, lalu satu pertanyaan penting — tombol "nggak"-nya kabur tiap mau ditekan.', $this->satuPertanyaan()],
            ['lempar-toga', 'Lempar Toga', 'Rayakan perjalanan panjang menuju toga', 'Linimasa perjuangan, surat bangga, hadiah kelulusan di balik kartu gosok, dan sertifikat lucu.', $this->lemparToga()],
            ['seikat-terima-kasih', 'Seikat Terima Kasih', 'Untuk orang yang terlalu sering lupa kita terima kasihi', 'Hal-hal kecil yang kamu syukuri darinya, dibuka satu per satu, ditutup surat dan janji balas budi.', $this->seikatTerimaKasih()],
            ['jarak-rindu', 'Jarak & Rindu', 'Untuk yang sedang berjauhan', 'Hitung mundur ke pertemuan berikutnya, kenangan yang bikin rindu, dan janji saat bertemu lagi.', $this->jarakRindu()],
        ];

        $sortOrder = 0;

        foreach ($catalog as $seriesIndex => [$slug, $name, $tagline, $story, $designs]) {
            $series = Series::updateOrCreate(['slug' => $slug], [
                'name' => $name,
                'tagline' => $tagline,
                'story' => $story,
                'sort_order' => $seriesIndex,
            ]);

            foreach ($designs as $data) {
                $template = Template::updateOrCreate(['slug' => $data['slug']], [
                    'category_id' => $categories[$data['category']]->id,
                    'series_id' => $series->id,
                    'name' => "{$name} · {$data['design']}",
                    'design_name' => $data['design'],
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
                    'sort_order' => $sortOrder++,
                ]);

                $template->components()->whereNotIn('key', array_column($data['slots'], 0))->delete();

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

    /**
     * @return array{url: string}
     */
    private function image(string $name): array
    {
        return ['url' => "/images/samples/{$name}.svg"];
    }

    /**
     * @param  list<array{0: string, 1: string}>  $items  [nama gambar, keterangan]
     * @return list<array{media: array{url: string}, caption: string}>
     */
    private function photos(array $items): array
    {
        return array_map(fn (array $item): array => ['media' => $this->image($item[0]), 'caption' => $item[1]], $items);
    }

    private function track(string $key): int
    {
        return $this->tracks[$key]->id;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function tiupLilin(): array
    {
        return [
            [
                'slug' => 'tiup-lilin',
                'design' => 'Pesta Klasik',
                'category' => 'ulang-tahun',
                'tagline' => 'Kue, konfeti, dan lilin yang ditiup sungguhan',
                'story' => 'Buka gembok, tiup lilinnya lewat mikrofon, baca alasan dunia bersyukur dia lahir, lalu unduh sertifikat kejutan.',
                'description' => 'Desain pesta paling lengkap. Penerima meniup lilin lewat mikrofon HP, membuka alasan-alasan kenapa dia istimewa, melihat album kenangan, membaca suratmu, dan mengunduh sertifikat lucu.',
                'tier' => TemplateTier::Interaktif,
                'price' => 35000,
                'duration_minutes' => 4,
                'theme' => ['accent' => '#c94a38', 'soft' => '#ffdad4', 'gradient' => ['#fff4e8', '#ffe0d1', '#ffc7b8'], 'emoji' => '🎂', 'motif' => 'confetti', 'font' => 'modern'],
                'highlight_icon' => 'mic',
                'highlight_label' => 'Tiup Mikrofon',
                'tags' => ['Playful', 'Audio'],
                'slots' => [
                    ['sampul', 'sampul'], ['gembok', 'gembok_pin'], ['lilin', 'tiup_lilin'], ['kutipan', 'kutipan', true, 'Hari Ini Milikmu'],
                    ['alasan', 'daftar_alasan', true, 'Alasan Dunia Bersyukur Kamu Lahir'], ['foto', 'foto_bingkai'], ['galeri', 'galeri_geser', false, 'Setahun Penuh Cerita'],
                    ['surat', 'surat'], ['suara', 'pesan_suara', false], ['sertifikat', 'sertifikat'], ['musik', 'musik_latar'], ['penutup', 'penutup'],
                ],
                'sample' => [
                    'occasions' => ['Ulang Tahun', 'Sweet Seventeen', 'Ulang Tahun Pernikahan'],
                    'base' => ['recipient_name' => 'Sarah', 'sender_name' => 'Nadia', 'occasion' => 'Ulang Tahun'],
                    'contributions' => [
                        ['name' => 'Dinda', 'message' => 'Happy birthday Sarah! Semoga makin sukses dan traktirannya jadi ya 🎂', 'photo' => null],
                        ['name' => 'Bimo', 'message' => 'Panjang umur, sehat selalu. Kangen nongkrong bareng!', 'photo' => $this->image('kafe')],
                        ['name' => 'Tante Rina', 'message' => 'Selamat ulang tahun, Nak. Doa terbaik dari kami sekeluarga 🤍', 'photo' => null],
                    ],
                    'components' => [
                        'sampul' => ['title' => 'Selamat Ulang Tahun, Sarah!', 'subtitle' => 'Ada kejutan kecil untukmu ✨', 'photo' => $this->image('kue')],
                        'lilin' => ['candles' => '24', 'wish' => 'Semoga semua harapanmu terkabul!'],
                        'kutipan' => ['quote' => 'Hari ini dunia merayakan satu hal: kamu pernah lahir, dan semuanya jadi lebih baik.', 'caption' => '— untuk Sarah, 24 tahun'],
                        'alasan' => ['title' => 'Alasan dunia bersyukur kamu lahir', 'reasons' => [
                            'Tawamu bisa bikin satu ruangan ikut ketawa, padahal leluconnya garing.',
                            'Kamu selalu ingat ulang tahun semua orang (kecuali punyamu sendiri).',
                            'Kamu orang pertama yang datang waktu aku butuh.',
                            'Playlist-mu selalu pas untuk segala suasana.',
                            'Kamu bikin kata "sahabat" terasa punya arti.',
                        ]],
                        'foto' => ['photo' => $this->image('pantai'), 'caption' => 'Liburan Jogja 2023'],
                        'galeri' => ['photos' => $this->photos([['kafe', 'Kopi sore langganan'], ['senja', 'Senja di rooftop'], ['balon', 'Pesta kejutan tahun lalu']])],
                        'surat' => ['greeting_line' => 'Sarah tersayang,', 'body' => 'Terima kasih sudah selalu ada dengan tawa paling tulus. Semoga tahun ini membawamu lebih dekat ke semua mimpimu, dan semoga aku masih boleh ada di barisan depan untuk menyorakimu.', 'signature' => 'Sahabatmu, Nadia'],
                        'musik' => ['track' => $this->track('akustik')],
                        'sertifikat' => ['title' => 'Sahabat Terbaik Sedunia', 'description' => 'Diberikan atas tawa, pelukan, dan kesabaran tanpa batas.'],
                        'penutup' => ['closing' => 'Selamat ulang tahun! Peluk jauh dari sini 🤍', 'allow_reply' => true],
                    ],
                ],
            ],
            [
                'slug' => 'tiup-lilin-pastel-manis',
                'design' => 'Pastel Manis',
                'category' => 'ulang-tahun',
                'tagline' => 'Cupcake pastel, kuis manis, dan kupon kakak',
                'story' => 'Cupcake pastel dengan lilin yang bisa ditiup, angka-angka lucu tentang dia, kuis tebak-tebakan, dan kupon spesial.',
                'description' => 'Manis untuk adik, sahabat, atau pacar. Huruf tulisan tangan dan warna pastel, berisi tiup lilin, statistik lucu tentang si dia, kuis, foto, kupon, dan surat.',
                'tier' => TemplateTier::Interaktif,
                'price' => 35000,
                'duration_minutes' => 4,
                'theme' => ['accent' => '#b4537a', 'soft' => '#fbdbe8', 'gradient' => ['#fff6fa', '#fde4ef', '#e9defb'], 'emoji' => '🧁', 'motif' => 'hearts', 'font' => 'tulisan'],
                'highlight_icon' => 'cake',
                'highlight_label' => 'Cupcake Pastel',
                'tags' => ['Manis', 'Lucu'],
                'slots' => [
                    ['sampul', 'sampul'], ['lilin', 'tiup_lilin'], ['angka', 'angka_kita', true, 'Kamu dalam Angka'], ['kuis', 'kuis', false, 'Kuis Ulang Tahun'],
                    ['foto', 'foto_bingkai'], ['kupon', 'kupon_janji', true, 'Kupon dari Kakak'], ['surat', 'surat'], ['sertifikat', 'sertifikat', false],
                    ['musik', 'musik_latar'], ['penutup', 'penutup'],
                ],
                'sample' => [
                    'occasions' => ['Ulang Tahun', 'Ulang Tahun Adik', 'Ulang Tahun Sahabat'],
                    'base' => ['recipient_name' => 'Aurel', 'sender_name' => 'Kak Tasya', 'occasion' => 'Ulang Tahun Adik'],
                    'components' => [
                        'sampul' => ['title' => 'Happy Birthday, Aurel!', 'subtitle' => 'Adik paling bawel sedunia resmi 20 tahun 🧁', 'photo' => $this->image('balon')],
                        'lilin' => ['candles' => '20', 'wish' => 'Make a wish dulu, baru buka halaman berikutnya!'],
                        'angka' => ['title' => 'Aurel dalam angka', 'stats' => ['20 tahun jadi adik paling bawel', '7300 hari pinjam baju kakak', '1000+ voice note kepanjangan', '∞ sayang kakak buat kamu'], 'footnote' => 'Data diolah dengan penuh kasih sayang.'],
                        'kuis' => ['questions' => [
                            ['question' => 'Makanan yang selalu kamu rebut dari piring kakak?', 'options' => ['Kerupuk', 'Telur dadar', 'Sambal'], 'answer' => 1],
                            ['question' => 'Film yang sudah kita tonton lebih dari 10 kali?', 'options' => ['Frozen', 'Toy Story', 'Up'], 'answer' => 0],
                        ], 'success_message' => 'Pinter! Hadiahnya ada di halaman berikutnya 🎁'],
                        'foto' => ['photo' => $this->image('keluarga-lama'), 'caption' => 'Waktu kamu masih mau digendong'],
                        'kupon' => ['coupons' => ['1x pinjam baju kakak tanpa izin', '1x dijemput kapan pun', 'Traktir boba ukuran besar']],
                        'surat' => ['greeting_line' => 'Adikku Aurel,', 'body' => 'Dua puluh tahun lalu kamu datang dan mengambil setengah kamarku, setengah jajananku, dan seluruh hatiku. Tumbuh terus jadi perempuan yang berani, ya. Kakak selalu di belakangmu.', 'signature' => 'Kak Tasya'],
                        'sertifikat' => ['title' => 'Adik Paling Disayang', 'description' => 'Berlaku seumur hidup, tidak bisa dipindahtangankan.'],
                        'musik' => ['track' => $this->track('kotak_musik')],
                        'penutup' => ['closing' => 'Selamat ulang tahun, Dek. Jangan lupa traktir 😋', 'allow_reply' => true],
                    ],
                ],
            ],
            [
                'slug' => 'tiup-lilin-pesta-malam',
                'design' => 'Pesta Malam',
                'category' => 'ulang-tahun',
                'tagline' => 'Ucapan rame-rame dari geng, lengkap dengan hadiah rahasia',
                'story' => 'Pesan berlapis dari geng, tiup lilin, gosok kartu untuk hadiah rahasia, album foto, dan voucher ulang tahun.',
                'description' => 'Untuk teman tongkrongan atau geng kantor. Pesan yang dibuka bergantian, tiup lilin bersama, kartu gosok berisi hadiah, galeri foto, dan voucher yang bisa ditagih kapan saja. Cocok dipakai patungan.',
                'tier' => TemplateTier::Interaktif,
                'price' => 39000,
                'duration_minutes' => 4,
                'theme' => ['accent' => '#7c3aed', 'soft' => '#ede4ff', 'gradient' => ['#f6f2ff', '#e9e0ff', '#d9f3ff'], 'emoji' => '🎉', 'motif' => 'stars', 'font' => 'modern'],
                'highlight_icon' => 'celebration',
                'highlight_label' => 'Mode Geng',
                'tags' => ['Rame-rame', 'Patungan'],
                'slots' => [
                    ['sampul', 'sampul'], ['lapisan', 'pesan_berlapis', true, 'Pesan dari Geng'], ['lilin', 'tiup_lilin'], ['hadiah', 'gosok_kartu', true, 'Hadiah Rahasia'],
                    ['galeri', 'galeri_geser', true, 'Bukti-Bukti Kekacauan Kita'], ['kutipan', 'kutipan'], ['suara', 'pesan_suara', false], ['kupon', 'kupon_janji', true, 'Voucher Ulang Tahun'],
                    ['musik', 'musik_latar'], ['penutup', 'penutup'],
                ],
                'sample' => [
                    'occasions' => ['Ulang Tahun', 'Ulang Tahun Teman Kantor', 'Pesta Kejutan'],
                    'base' => ['recipient_name' => 'Dito', 'sender_name' => 'Geng Futsal Kamis', 'occasion' => 'Ulang Tahun'],
                    'contributions' => [
                        ['name' => 'Yoga', 'message' => 'HBD kapten! Tahun ini harus cetak gol, jangan cuma cetak alasan 😂', 'photo' => null],
                        ['name' => 'Rendy', 'message' => 'Panjang umur bro, semoga lutut aman sampai turnamen.', 'photo' => null],
                    ],
                    'components' => [
                        'sampul' => ['title' => 'HBD, Kapten Dito! 🎉', 'subtitle' => 'Dari kami yang selalu telat datang futsal', 'photo' => $this->image('balon')],
                        'lapisan' => ['layers' => ['Pesan dari Yoga: makasih sudah selalu jadi kiper darurat.', 'Pesan dari Rendy: kamu satu-satunya yang ingat bawa air minum.', 'Pesan dari semua: tim ini nggak akan sama tanpa kamu.']],
                        'lilin' => ['candles' => '27', 'wish' => 'Tiup yang kencang, kayak tendangan pinaltimu!'],
                        'hadiah' => ['hidden_message' => 'Sepatu futsal baru sudah nunggu di loker! 👟', 'cover_label' => 'Gosok hadiahnya'],
                        'galeri' => ['photos' => $this->photos([['kota-malam', 'Habis futsal lanjut nasi goreng'], ['kafe', 'Rapat strategi (isinya ngemil)'], ['pantai', 'Outing tim ke pantai']])],
                        'kutipan' => ['quote' => 'Tim boleh kalah, tapi ulang tahun kapten wajib menang meriah.', 'caption' => '— Geng Futsal Kamis'],
                        'kupon' => ['coupons' => ['Bebas jadi kapten 1 bulan', '1x sewa lapangan kami yang bayar', 'Kebal ejekan selama seminggu']],
                        'musik' => ['track' => $this->track('akustik')],
                        'penutup' => ['closing' => 'Sampai ketemu Kamis depan. Kamu yang traktir es teh ya 😎', 'allow_reply' => true],
                    ],
                ],
            ],
            [
                'slug' => 'tiup-lilin-sweet-seventeen',
                'design' => 'Sweet Seventeen',
                'category' => 'ulang-tahun',
                'tagline' => 'Perjalanan dari bayi mungil sampai 17 tahun',
                'story' => 'Linimasa dari 0 sampai 17, tiup lilin mahkota, angka-angka perjalanannya, kuis, surat orang tua, dan sertifikat resmi dewasa.',
                'description' => 'Hadiah paling lengkap untuk momen 17 tahun. Ada linimasa foto dari kecil, tiup lilin, angka-angka perjalanan hidupnya, daftar hal yang membuatnya istimewa, kuis, surat panjang, kupon, dan sertifikat yang bisa diunduh.',
                'tier' => TemplateTier::Interaktif,
                'price' => 45000,
                'duration_minutes' => 6,
                'theme' => ['accent' => '#db2777', 'soft' => '#fce1ee', 'gradient' => ['#fff5fa', '#ffe4f1', '#fff1d6'], 'emoji' => '👑', 'motif' => 'confetti', 'font' => 'klasik'],
                'highlight_icon' => 'crown',
                'highlight_label' => 'Paling Lengkap',
                'tags' => ['17 Tahun', 'Lengkap'],
                'slots' => [
                    ['sampul', 'sampul'], ['gembok', 'gembok_pin'], ['perjalanan', 'linimasa', true, 'Dari 0 sampai 17'], ['lilin', 'tiup_lilin'],
                    ['angka', 'angka_kita', true, '17 Tahun dalam Angka'], ['alasan', 'daftar_alasan', true, 'Hal-hal yang Bikin Kamu Istimewa'], ['kuis', 'kuis', false],
                    ['surat', 'surat'], ['kupon', 'kupon_janji'], ['sertifikat', 'sertifikat'], ['musik', 'musik_latar'], ['penutup', 'penutup'],
                ],
                'sample' => [
                    'occasions' => ['Sweet Seventeen', 'Ulang Tahun ke-17'],
                    'base' => ['recipient_name' => 'Nayla', 'sender_name' => 'Mama & Papa', 'occasion' => 'Sweet Seventeen'],
                    'components' => [
                        'sampul' => ['title' => 'Selamat 17 Tahun, Nayla 👑', 'subtitle' => 'Putri kecil kami resmi beranjak dewasa', 'photo' => $this->image('kue')],
                        'perjalanan' => ['moments' => $this->photos([
                            ['keluarga-lama', '2009 — Pertama kali kamu menggenggam jari Papa'],
                            ['pantai', '2015 — Berani berenang tanpa pelampung'],
                            ['kampus', '2021 — Hari pertama masuk SMP'],
                            ['keluarga-kini', '2026 — Tujuh belas, dan tetap anak kami'],
                        ])],
                        'lilin' => ['candles' => '17', 'wish' => 'Semoga langkah dewasamu selalu dijaga.'],
                        'angka' => ['title' => '17 tahun dalam angka', 'stats' => ['17 tahun jadi kebanggaan kami', '6209 hari kamu bangun kesiangan', '43 piala & piagam di lemari', '1 putri kesayangan'], 'footnote' => 'Angka-angka ini akan terus bertambah 🤍'],
                        'alasan' => ['title' => 'Hal-hal yang bikin kamu istimewa', 'reasons' => [
                            'Kamu selalu membela temanmu yang diganggu.',
                            'Kamu bisa menggambar apa saja dari ingatan.',
                            'Kamu masih mau peluk Mama di depan teman-temanmu.',
                            'Kamu tidak pernah menyerah, bahkan pada soal matematika.',
                            'Kamu punya hati paling lembut di rumah ini.',
                        ]],
                        'kuis' => ['questions' => [
                            ['question' => 'Kata pertama yang kamu ucapkan?', 'options' => ['Mama', 'Papa', 'Mamam'], 'answer' => 2],
                            ['question' => 'Cita-cita pertamamu waktu TK?', 'options' => ['Dokter', 'Penjual es krim', 'Astronot'], 'answer' => 1],
                        ], 'success_message' => 'Ingatanmu tajam, sama seperti Mama!'],
                        'surat' => ['greeting_line' => 'Nayla, anak kami,', 'body' => 'Tujuh belas tahun lalu kami belajar menjadi orang tua karena kamu. Hari ini kamu mulai belajar menjadi dewasa. Tidak apa-apa salah, tidak apa-apa ragu — rumah ini selalu terbuka, dan kami selalu bangga padamu.', 'signature' => 'Peluk erat, Mama & Papa'],
                        'kupon' => ['coupons' => ['1x pulang malam (dengan kabar!)', 'Belajar nyetir bareng Papa', 'Pilih tempat liburan keluarga tahun ini']],
                        'sertifikat' => ['title' => 'Resmi Dewasa (Versi Beta)', 'description' => 'Masih boleh manja ke Mama dan Papa kapan saja.'],
                        'musik' => ['track' => $this->track('kotak_musik')],
                        'penutup' => ['closing' => 'Selamat 17 tahun, sayang. Dunia menunggumu.', 'allow_reply' => true],
                    ],
                ],
            ],
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function kadoBerlapis(): array
    {
        return [
            [
                'slug' => 'kado-berlapis',
                'design' => 'Merah Muda',
                'category' => 'pasangan',
                'tagline' => 'Pesan, alasan, dan kupon untuk pasangan',
                'story' => 'Setiap ketukan membuka lapisan baru: pesan rahasia, angka-angka kalian, album kenangan, alasan aku sayang kamu, sampai kupon janji.',
                'description' => 'Seperti membuka kado berlapis: pesan yang terbuka bertahap, statistik hubungan kalian, galeri kenangan, daftar alasan, kuis seru, dan kupon janji yang bisa ditukar.',
                'tier' => TemplateTier::Interaktif,
                'price' => 29000,
                'duration_minutes' => 4,
                'theme' => ['accent' => '#9c2f5f', 'soft' => '#ffd9ea', 'gradient' => ['#fff0f6', '#ffd9ea', '#f6b8d3'], 'emoji' => '🎁', 'motif' => 'hearts', 'font' => 'modern'],
                'highlight_icon' => 'redeem',
                'highlight_label' => 'Buka Lapisan',
                'tags' => ['Romantis', 'Kejutan'],
                'slots' => [
                    ['sampul', 'sampul'], ['lapisan', 'pesan_berlapis'], ['angka', 'angka_kita', true, 'Kita dalam Angka'], ['galeri', 'galeri_geser'],
                    ['alasan', 'daftar_alasan', true, 'Alasan Aku Sayang Kamu'], ['kuis', 'kuis', false], ['kutipan', 'kutipan'], ['kupon', 'kupon_janji'],
                    ['suara', 'pesan_suara', false], ['musik', 'musik_latar'], ['penutup', 'penutup'],
                ],
                'sample' => [
                    'occasions' => ['Anniversary', 'Hari Valentine', 'Tanpa Alasan'],
                    'base' => ['recipient_name' => 'Dimas', 'sender_name' => 'Rani', 'occasion' => 'Anniversary'],
                    'components' => [
                        'sampul' => ['title' => 'Untuk Dimas', 'subtitle' => 'Buka pelan-pelan ya', 'photo' => $this->image('senja')],
                        'lapisan' => ['layers' => ['Dua tahun lalu, kita cuma dua orang asing di halte.', 'Sekarang kamu orang pertama yang kuceritakan semuanya.', 'Terima kasih sudah memilih bertahan. Aku sayang kamu.']],
                        'angka' => ['title' => 'Kita dalam angka', 'stats' => ['730 hari kita bareng', '12 kota yang kita datangi', '348 kali debat mau makan apa', '1 orang yang aku pilih tiap hari']],
                        'galeri' => ['photos' => $this->photos([['pantai', 'Pantai pertama kita'], ['kafe', 'Kopi sore di Braga'], ['kota-malam', 'Pulang larut, tetap senyum']])],
                        'alasan' => ['title' => 'Alasan aku sayang kamu', 'reasons' => [
                            'Kamu selalu mengalah soal suhu AC.',
                            'Kamu dengar ceritaku sampai habis, walaupun ngalor-ngidul.',
                            'Kamu tetap tenang waktu aku panik.',
                            'Kamu bikin hari Senin terasa nggak terlalu buruk.',
                        ]],
                        'kuis' => ['questions' => [['question' => 'Di mana kita pertama kali makan bareng?', 'options' => ['Warung bakso', 'Kafe Braga', 'Kantin kampus'], 'answer' => 2]], 'success_message' => 'Kamu memang paling kenal aku!'],
                        'kutipan' => ['quote' => 'Dari semua hal yang terjadi tahun ini, kamu tetap bagian favoritku.', 'caption' => '— Rani'],
                        'kupon' => ['coupons' => ['1x pijat pundak tanpa protes', '1x traktir martabak', 'Bebas pilih film malam minggu']],
                        'musik' => ['track' => $this->track('jazz')],
                        'penutup' => ['closing' => 'Happy anniversary, sayang.', 'allow_reply' => true],
                    ],
                ],
            ],
            [
                'slug' => 'kado-berlapis-surat-vintage',
                'design' => 'Surat Vintage',
                'category' => 'pasangan',
                'tagline' => 'Tiga surat cinta: tentang awal, hari ini, dan nanti',
                'story' => 'Tiga surat bergaya klasik — tentang awal kita, hari ini, dan masa depan — diselingi foto dan album kenangan.',
                'description' => 'Untuk yang lebih suka menulis daripada bicara. Huruf klasik dan warna kertas tua, berisi tiga surat bertema (awal, kini, nanti), foto polaroid, galeri, dan kupon janji.',
                'tier' => TemplateTier::Klasik,
                'price' => 29000,
                'duration_minutes' => 5,
                'theme' => ['accent' => '#8c4a2f', 'soft' => '#f6e1d3', 'gradient' => ['#fbf5ee', '#f3e4d4', '#ead2c0'], 'emoji' => '💌', 'motif' => 'petals', 'font' => 'klasik'],
                'highlight_icon' => 'mail',
                'highlight_label' => '3 Surat Cinta',
                'tags' => ['Klasik', 'Puitis'],
                'slots' => [
                    ['sampul', 'sampul'], ['kutipan', 'kutipan'], ['surat_awal', 'surat', true, 'Surat Pertama: Tentang Awal Kita'], ['foto', 'foto_bingkai'],
                    ['surat_kini', 'surat', true, 'Surat Kedua: Tentang Hari Ini'], ['galeri', 'galeri_geser', false], ['surat_nanti', 'surat', true, 'Surat Ketiga: Tentang Nanti'],
                    ['kupon', 'kupon_janji', false], ['musik', 'musik_latar'], ['penutup', 'penutup'],
                ],
                'sample' => [
                    'occasions' => ['Anniversary', 'Hari Valentine', 'Ulang Tahun Pasangan'],
                    'base' => ['recipient_name' => 'Adit', 'sender_name' => 'Sekar', 'occasion' => 'Anniversary'],
                    'components' => [
                        'sampul' => ['title' => 'Tiga Surat untuk Adit', 'subtitle' => 'Bacalah berurutan, seperti kita menjalaninya', 'photo' => $this->image('buket')],
                        'kutipan' => ['quote' => 'Ada hal-hal yang terlalu besar untuk diucapkan, jadi aku menuliskannya.', 'caption' => '— Sekar'],
                        'surat_awal' => ['greeting_line' => 'Adit,', 'body' => 'Kamu ingat hujan di perpustakaan kampus itu? Kamu meminjamkan payung, lalu kita sama-sama basah karena payungnya terlalu kecil untuk berdua. Sejak hari itu aku tahu: aku tidak keberatan basah asal bersamamu.', 'signature' => 'Sekar, di awal'],
                        'foto' => ['photo' => $this->image('hujan'), 'caption' => 'Payung kecil itu'],
                        'surat_kini' => ['greeting_line' => 'Adit hari ini,', 'body' => 'Sekarang kita sudah melewati banyak hal: pindah kos, kerja pertama, dan pertengkaran soal hal-hal sepele. Tapi setiap malam aku tetap bersyukur, karena kamu yang ada di ujung telepon.', 'signature' => 'Sekar, hari ini'],
                        'galeri' => ['photos' => $this->photos([['kafe', 'Kencan murah meriah'], ['senja', 'Sore di dermaga'], ['kota-malam', 'Jalan kaki pulang']])],
                        'surat_nanti' => ['greeting_line' => 'Adit nanti,', 'body' => 'Kalau suatu hari kita sudah tua dan lupa banyak hal, semoga kamu tetap ingat satu: aku memilihmu, hari ini dan semua hari setelahnya.', 'signature' => 'Sekar, selamanya'],
                        'kupon' => ['coupons' => ['Satu surat tulisan tangan tiap bulan', 'Piknik sore di taman kota']],
                        'musik' => ['track' => $this->track('teduh')],
                        'penutup' => ['closing' => 'Simpan surat-surat ini. Aku akan terus menulis yang baru.', 'allow_reply' => true],
                    ],
                ],
            ],
            [
                'slug' => 'kado-berlapis-kencan-malam',
                'design' => 'Kencan Malam',
                'category' => 'pasangan',
                'tagline' => 'Ajak kencan dengan hitung mundur dan tujuan rahasia',
                'story' => 'Hitung mundur ke kencan berikutnya, kuis seberapa kenal, tebak tujuan kencan di balik kartu gosok, lalu daftar rencana malamnya.',
                'description' => 'Cara manis mengajak pasangan kencan. Hitung mundur menuju hari H, statistik hubungan, kuis, kartu gosok berisi tujuan rahasia, galeri, surat, dan kupon rencana kencan.',
                'tier' => TemplateTier::Interaktif,
                'price' => 32000,
                'duration_minutes' => 4,
                'theme' => ['accent' => '#be123c', 'soft' => '#ffe0e6', 'gradient' => ['#fff4f5', '#ffe2e7', '#ede3ff'], 'emoji' => '🌹', 'motif' => 'stars', 'font' => 'modern'],
                'highlight_icon' => 'timer',
                'highlight_label' => 'Kencan Rahasia',
                'tags' => ['Romantis', 'Ajak Kencan'],
                'slots' => [
                    ['sampul', 'sampul'], ['gembok', 'gembok_pin'], ['mundur', 'hitung_mundur', true, 'Menuju Kencan Kita'], ['angka', 'angka_kita'],
                    ['kuis', 'kuis'], ['tujuan', 'gosok_kartu', true, 'Tebak Tujuan Kencan'], ['galeri', 'galeri_geser', false], ['surat', 'surat'],
                    ['rencana', 'kupon_janji', true, 'Rencana Malam Itu'], ['musik', 'musik_latar'], ['penutup', 'penutup'],
                ],
                'sample' => [
                    'occasions' => ['Ajak Kencan', 'Anniversary', 'Tanpa Alasan'],
                    'base' => ['recipient_name' => 'Salsa', 'sender_name' => 'Fajar', 'occasion' => 'Ajak Kencan'],
                    'components' => [
                        'sampul' => ['title' => 'Salsa, kosongkan Sabtu malam ya', 'subtitle' => 'Ada yang sudah kurencanakan 🌹', 'photo' => $this->image('kota-malam')],
                        'mundur' => ['target_at' => '+5 days 19:00', 'label' => 'Aku jemput jam 7 malam'],
                        'angka' => ['title' => 'Kita sejauh ini', 'stats' => ['412 hari pacaran', '58 kencan yang tercatat', '3 kali nyasar karena aku sok tahu jalan'], 'footnote' => 'Kencan ke-59 segera dimulai.'],
                        'kuis' => ['questions' => [
                            ['question' => 'Makanan yang paling sering kita pesan?', 'options' => ['Ramen', 'Sate padang', 'Dimsum'], 'answer' => 0],
                            ['question' => 'Lagu yang selalu kamu putar di mobil?', 'options' => ['Lagu galau', 'Lagu dangdut', 'Lagu Disney'], 'answer' => 2],
                        ], 'success_message' => 'Benar semua! Sekarang gosok kartunya…'],
                        'tujuan' => ['hidden_message' => 'Makan malam di rooftop, lalu nonton film di bioskop favoritmu 🍝🎬', 'cover_label' => 'Gosok untuk tujuan'],
                        'galeri' => ['photos' => $this->photos([['kafe', 'Kencan pertama'], ['senja', 'Sore paling tenang'], ['pantai', 'Liburan dadakan']])],
                        'surat' => ['greeting_line' => 'Salsa,', 'body' => 'Belakangan kita sama-sama sibuk, dan aku kangen kita yang dulu sering iseng jalan tanpa rencana. Jadi kali ini aku yang merencanakan semuanya. Kamu cukup datang dan jadi dirimu sendiri.', 'signature' => 'Fajar'],
                        'rencana' => ['coupons' => ['19.00 — Aku jemput, kamu tinggal cantik', '19.45 — Makan malam di rooftop', '21.30 — Film pilihanmu, popcorn aku yang bayar']],
                        'musik' => ['track' => $this->track('jazz')],
                        'penutup' => ['closing' => 'Sampai ketemu Sabtu. Dandan yang biasa saja, kamu sudah cukup 🌹', 'allow_reply' => true],
                    ],
                ],
            ],
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function mesinWaktu(): array
    {
        return [
            [
                'slug' => 'mesin-waktu',
                'design' => 'Film Sepia',
                'category' => 'keluarga',
                'tagline' => 'Linimasa kenangan dan pesan suara untuk Ibu',
                'story' => 'Putar balik waktu: dari foto lama sampai hari ini, angka-angka perjuangan Ibu, alasan kami berterima kasih, ditutup suara dan surat.',
                'description' => 'Ajak Ibu atau keluarga tersayang menyusuri kenangan dari masa ke masa, lengkap dengan angka perjuangannya, foto berbingkai, alasan-alasan terima kasih, pesan suara, dan surat dari hatimu.',
                'tier' => TemplateTier::Klasik,
                'price' => 29000,
                'duration_minutes' => 4,
                'theme' => ['accent' => '#8a5a12', 'soft' => '#ffe8c2', 'gradient' => ['#fdf7ec', '#f6e6c8', '#ecd2a6'], 'emoji' => '🕰️', 'motif' => 'film', 'font' => 'klasik'],
                'highlight_icon' => 'history',
                'highlight_label' => 'Linimasa Foto',
                'tags' => ['Keluarga', 'Kenangan'],
                'slots' => [
                    ['sampul', 'sampul'], ['linimasa', 'linimasa'], ['angka', 'angka_kita', false, 'Ibu dalam Angka'], ['foto', 'foto_bingkai'],
                    ['alasan', 'daftar_alasan', true, 'Terima Kasih, Bu, untuk…'], ['suara', 'pesan_suara', false], ['surat', 'surat'],
                    ['kupon', 'kupon_janji', false, 'Janji Anakmu'], ['musik', 'musik_latar'], ['penutup', 'penutup'],
                ],
                'sample' => [
                    'occasions' => ['Hari Ibu', 'Hari Ayah', 'Ulang Tahun Orang Tua'],
                    'base' => ['recipient_name' => 'Ibu', 'sender_name' => 'Rian', 'occasion' => 'Hari Ibu'],
                    'components' => [
                        'sampul' => ['title' => 'Untuk Ibu, Perempuan Terhebat', 'subtitle' => 'Selamat Hari Ibu', 'photo' => $this->image('keluarga-kini')],
                        'linimasa' => ['moments' => $this->photos([
                            ['keluarga-lama', '1998 — Pertama kali Ibu menggendongku'],
                            ['pantai', '2010 — Liburan keluarga ke Pangandaran'],
                            ['keluarga-kini', '2026 — Masih dan selalu bersama'],
                        ])],
                        'angka' => ['title' => 'Ibu dalam angka', 'stats' => ['28 tahun jadi ibuku', '10000+ bekal yang Ibu siapkan', '1 doa yang tak pernah putus']],
                        'foto' => ['photo' => $this->image('keluarga-lama'), 'caption' => 'Foto favoritku dari album lama'],
                        'alasan' => ['title' => 'Terima kasih, Bu, untuk…', 'reasons' => [
                            'Bangun paling pagi dan tidur paling malam.',
                            'Pura-pura sudah kenyang supaya aku makan lebih banyak.',
                            'Tetap percaya padaku waktu nilaiku jelek.',
                            'Telepon setiap Jumat, walau cuma tanya "sudah makan?".',
                        ]],
                        'surat' => ['greeting_line' => 'Ibu,', 'body' => 'Terima kasih untuk setiap doa yang tidak pernah putus. Semua yang aku capai hari ini berawal dari pelukan Ibu.', 'signature' => 'Anakmu, Rian'],
                        'kupon' => ['coupons' => ['Pulang tiap bulan, tanpa alasan sibuk', 'Antar Ibu ke pasar setiap Minggu']],
                        'musik' => ['track' => $this->track('kotak_musik')],
                        'penutup' => ['closing' => 'Sehat selalu ya, Bu.', 'allow_reply' => true],
                    ],
                ],
            ],
            [
                'slug' => 'mesin-waktu-jagoan-ayah',
                'design' => 'Jagoan Ayah',
                'category' => 'keluarga',
                'tagline' => 'Untuk ayah yang jarang bilang sayang tapi selalu ada',
                'story' => 'Kalimat pembuka untuk Ayah, linimasa bersama, kuis "Ayah masih ingat?", surat, kupon, dan sertifikat Ayah Terhebat.',
                'description' => 'Untuk Hari Ayah atau ulang tahun Ayah. Dimulai dengan satu kalimat besar, linimasa foto, kuis kenangan, surat yang jarang terucap, kupon janji, dan sertifikat Ayah Terhebat Sedunia.',
                'tier' => TemplateTier::Klasik,
                'price' => 29000,
                'duration_minutes' => 4,
                'theme' => ['accent' => '#2f5d50', 'soft' => '#d9ece5', 'gradient' => ['#f4f8f6', '#e0eee8', '#f3e6cf'], 'emoji' => '🧢', 'motif' => 'stars', 'font' => 'modern'],
                'highlight_icon' => 'quiz',
                'highlight_label' => 'Kuis Ayah',
                'tags' => ['Hari Ayah', 'Hangat'],
                'slots' => [
                    ['sampul', 'sampul'], ['kutipan', 'kutipan'], ['linimasa', 'linimasa', true, 'Aku dan Ayah'], ['kuis', 'kuis', true, 'Ayah Masih Ingat?'],
                    ['surat', 'surat'], ['kupon', 'kupon_janji', true, 'Kupon untuk Ayah'], ['sertifikat', 'sertifikat'], ['musik', 'musik_latar'], ['penutup', 'penutup'],
                ],
                'sample' => [
                    'occasions' => ['Hari Ayah', 'Ulang Tahun Ayah', 'Pensiun Ayah'],
                    'base' => ['recipient_name' => 'Ayah', 'sender_name' => 'Dimas & Laila', 'occasion' => 'Hari Ayah'],
                    'components' => [
                        'sampul' => ['title' => 'Selamat Hari Ayah, Jagoan Kami', 'subtitle' => 'Dari dua anak yang dulu sering minta digendong', 'photo' => $this->image('keluarga-kini')],
                        'kutipan' => ['quote' => 'Ayah tidak pernah banyak bicara, tapi semua yang Ayah lakukan adalah kalimat sayang yang paling panjang.', 'caption' => '— Dimas & Laila'],
                        'linimasa' => ['moments' => $this->photos([
                            ['keluarga-lama', '2002 — Ayah mengajariku naik sepeda'],
                            ['pantai', '2011 — Memancing yang tak pernah dapat ikan'],
                            ['wisuda', '2024 — Ayah menangis di wisudaku (katanya kelilipan)'],
                        ])],
                        'kuis' => ['questions' => [
                            ['question' => 'Ke mana Ayah mengajak kami setiap Minggu pagi?', 'options' => ['Pasar burung', 'Car free day', 'Toko bangunan'], 'answer' => 2],
                            ['question' => 'Lagu yang Ayah nyanyikan di mobil?', 'options' => ['Koes Plus', 'Iwan Fals', 'Chrisye'], 'answer' => 0],
                        ], 'success_message' => 'Ingatan Ayah masih juara!'],
                        'surat' => ['greeting_line' => 'Ayah,', 'body' => 'Kami tahu Ayah lelah, walau Ayah tidak pernah bilang. Terima kasih sudah bekerja keras tanpa mengeluh, dan selalu pulang membawa martabak setiap gajian. Sekarang giliran kami yang menjaga Ayah.', 'signature' => 'Dimas & Laila'],
                        'kupon' => ['coupons' => ['1x cuci motor Ayah sampai kinclong', 'Temani Ayah memancing (kali ini harus dapat)', 'Bebas pegang remote TV seharian']],
                        'sertifikat' => ['title' => 'Ayah Terhebat Sedunia', 'description' => 'Atas kesabaran, lelucon garing, dan martabak setiap gajian.'],
                        'musik' => ['track' => $this->track('kotak_musik')],
                        'penutup' => ['closing' => 'Sehat terus, Yah. Kami sayang Ayah (iya, kami bilang juga akhirnya).', 'allow_reply' => true],
                    ],
                ],
            ],
            [
                'slug' => 'mesin-waktu-rumah-kita',
                'design' => 'Rumah Kita',
                'category' => 'keluarga',
                'tagline' => 'Anniversary pernikahan orang tua dari anak & cucu',
                'story' => 'Puluhan tahun dalam angka, linimasa pernikahan, album keluarga, pesan bergantian dari anak dan cucu, surat, dan sertifikat cinta abadi.',
                'description' => 'Hadiah untuk ulang tahun pernikahan Bapak & Ibu dari seluruh keluarga. Berisi angka-angka perjalanan pernikahan, linimasa, album, satu kalimat besar, pesan dari setiap anak dan cucu, pesan suara, surat, dan sertifikat. Cocok untuk patungan keluarga.',
                'tier' => TemplateTier::Interaktif,
                'price' => 35000,
                'duration_minutes' => 5,
                'theme' => ['accent' => '#9a3412', 'soft' => '#fde3d2', 'gradient' => ['#fff8f1', '#fde8d8', '#f3dccb'], 'emoji' => '🏡', 'motif' => 'petals', 'font' => 'klasik'],
                'highlight_icon' => 'family_restroom',
                'highlight_label' => 'Patungan Keluarga',
                'tags' => ['Anniversary', 'Keluarga Besar'],
                'slots' => [
                    ['sampul', 'sampul'], ['angka', 'angka_kita', true, 'Pernikahan dalam Angka'], ['linimasa', 'linimasa'], ['galeri', 'galeri_geser', false, 'Album Keluarga'],
                    ['kutipan', 'kutipan'], ['lapisan', 'pesan_berlapis', true, 'Pesan dari Anak & Cucu'], ['suara', 'pesan_suara', false], ['surat', 'surat'],
                    ['sertifikat', 'sertifikat'], ['musik', 'musik_latar'], ['penutup', 'penutup'],
                ],
                'sample' => [
                    'occasions' => ['Anniversary Pernikahan', 'Ulang Tahun Pernikahan Perak', 'Ulang Tahun Pernikahan Emas'],
                    'base' => ['recipient_name' => 'Bapak & Ibu', 'sender_name' => 'Anak, Mantu & Cucu', 'occasion' => 'Anniversary Pernikahan ke-35'],
                    'contributions' => [
                        ['name' => 'Cucu Kirana (7 th)', 'message' => 'Selamat ulang tahun nikah Eyang! Nanti main ke rumah lagi ya, aku mau dibuatin bakwan.', 'photo' => null],
                    ],
                    'components' => [
                        'sampul' => ['title' => '35 Tahun Rumah Kita', 'subtitle' => 'Selamat ulang tahun pernikahan, Bapak & Ibu', 'photo' => $this->image('keluarga-kini')],
                        'angka' => ['title' => 'Pernikahan dalam angka', 'stats' => ['35 tahun bersama', '4 anak yang dibesarkan', '6 cucu yang berisik', '12775 sarapan bersama']],
                        'linimasa' => ['moments' => $this->photos([
                            ['keluarga-lama', '1991 — Hari pernikahan di rumah Mbah'],
                            ['pantai', '2005 — Liburan pertama dengan empat anak'],
                            ['keluarga-kini', '2026 — Rumah yang makin ramai'],
                        ])],
                        'galeri' => ['photos' => $this->photos([['keluarga-lama', 'Lebaran 1999'], ['kafe', 'Makan malam ulang tahun Ibu'], ['keluarga-kini', 'Foto keluarga terbaru']])],
                        'kutipan' => ['quote' => 'Kalian mengajari kami bahwa cinta bukan soal hari-hari besar, tapi tentang tetap pulang ke rumah yang sama.', 'caption' => '— anak-anakmu'],
                        'lapisan' => ['layers' => [
                            'Dari Mas Anton: terima kasih sudah jadi contoh suami dan ayah yang baik.',
                            'Dari Mbak Rini: semoga Bapak dan Ibu sehat terus, kita liburan bareng lagi ya.',
                            'Dari para cucu: Eyang Kakung dan Eyang Putri paling seru sedunia!',
                        ]],
                        'surat' => ['greeting_line' => 'Bapak dan Ibu,', 'body' => 'Tiga puluh lima tahun bukan waktu yang sebentar. Ada masa sulit, ada masa senang, dan Bapak Ibu tetap saling menggenggam. Terima kasih sudah membangun rumah yang selalu kami rindukan untuk pulang.', 'signature' => 'Anak, mantu, dan cucu-cucumu'],
                        'sertifikat' => ['title' => 'Pasangan Paling Setia', 'description' => '35 tahun dan masih saling menyisihkan lauk terenak.'],
                        'musik' => ['track' => $this->track('kotak_musik')],
                        'penutup' => ['closing' => 'Semoga diberi umur panjang untuk merayakan yang ke-50 🤍', 'allow_reply' => true],
                    ],
                ],
            ],
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function ketupatLebaran(): array
    {
        return [
            [
                'slug' => 'lebaran',
                'design' => 'Hijau Emas',
                'category' => 'hari-raya',
                'tagline' => 'Ucapan Idulfitri dengan kartu gosok berisi doa',
                'story' => 'Kalimat salam, maaf lahir batin yang terbuka pelan-pelan, album lebaran, lalu gosok kartu emas untuk menemukan doa tersembunyi.',
                'description' => 'Sampaikan maaf lahir batin dengan kalimat pembuka, pesan berlapis, album lebaran tahun lalu, kartu gosok emas berisi doa, dan kupon silaturahmi.',
                'tier' => TemplateTier::Klasik,
                'price' => 25000,
                'duration_minutes' => 3,
                'theme' => ['accent' => '#1f6b52', 'soft' => '#d6f0e3', 'gradient' => ['#f3fbf6', '#dff3e8', '#f7e7b6'], 'emoji' => '🌙', 'motif' => 'stars', 'font' => 'klasik'],
                'highlight_icon' => 'auto_awesome',
                'highlight_label' => 'Gosok Kartu',
                'tags' => ['Hari Raya', 'Berkah'],
                'slots' => [
                    ['sampul', 'sampul'], ['kutipan', 'kutipan'], ['lapisan', 'pesan_berlapis'], ['galeri', 'galeri_geser', false, 'Lebaran Tahun Lalu'],
                    ['gosok', 'gosok_kartu'], ['kupon', 'kupon_janji', false, 'Janji Silaturahmi'], ['suara', 'pesan_suara', false], ['musik', 'musik_latar', false], ['penutup', 'penutup'],
                ],
                'sample' => [
                    'occasions' => ['Idulfitri', 'Iduladha', 'Ramadan'],
                    'base' => ['recipient_name' => 'Keluarga Besar', 'sender_name' => 'Keluarga Rahman', 'occasion' => 'Idulfitri'],
                    'components' => [
                        'sampul' => ['title' => 'Selamat Idulfitri', 'subtitle' => 'Mohon maaf lahir dan batin', 'photo' => $this->image('masjid')],
                        'kutipan' => ['quote' => 'Taqabbalallahu minna wa minkum, taqabbal ya karim.', 'caption' => 'Semoga Allah menerima amal kita semua'],
                        'lapisan' => ['layers' => ['Taqabbalallahu minna wa minkum.', 'Atas segala salah kata dan laku, kami mohon maaf.', 'Semoga kita dipertemukan lagi dengan Ramadan berikutnya.']],
                        'galeri' => ['photos' => $this->photos([['keluarga-kini', 'Sungkeman tahun lalu'], ['masjid', 'Salat Id bersama'], ['kafe', 'Opor dan ketupat buatan Nenek']])],
                        'gosok' => ['hidden_message' => 'Semoga rezeki dan kebahagiaan melimpah untuk keluarga kalian 🤲', 'cover_label' => 'Gosok untuk doa'],
                        'kupon' => ['coupons' => ['Silaturahmi ke rumah di hari kedua lebaran', 'Kue nastar buatan Ibu satu toples']],
                        'musik' => ['track' => $this->track('takbir')],
                        'penutup' => ['closing' => 'Salam hangat dari kami sekeluarga.', 'allow_reply' => true],
                    ],
                ],
            ],
            [
                'slug' => 'lebaran-malam-takbiran',
                'design' => 'Malam Takbiran',
                'category' => 'hari-raya',
                'tagline' => 'Langit malam, hitung mundur hari kemenangan',
                'story' => 'Hitung mundur menuju hari raya, kalimat takbir, maaf yang terbuka pelan, foto kenangan, dan doa tersembunyi.',
                'description' => 'Bernuansa langit malam takbiran. Penerima melihat hitung mundur ke hari raya, satu kalimat besar, permohonan maaf berlapis, foto berbingkai, kartu gosok doa, dan surat.',
                'tier' => TemplateTier::Interaktif,
                'price' => 29000,
                'duration_minutes' => 4,
                'theme' => ['accent' => '#3b3486', 'soft' => '#e0ddf7', 'gradient' => ['#eeedfb', '#dcd8f5', '#f7e7b6'], 'emoji' => '🕌', 'motif' => 'stars', 'font' => 'klasik'],
                'highlight_icon' => 'nights_stay',
                'highlight_label' => 'Hitung Mundur',
                'tags' => ['Khidmat', 'Malam Takbiran'],
                'slots' => [
                    ['sampul', 'sampul'], ['mundur', 'hitung_mundur', true, 'Menuju Hari Kemenangan'], ['kutipan', 'kutipan'], ['lapisan', 'pesan_berlapis', true, 'Maaf yang Ingin Kusampaikan'],
                    ['foto', 'foto_bingkai'], ['gosok', 'gosok_kartu', true, 'Doa untukmu'], ['surat', 'surat'], ['musik', 'musik_latar'], ['penutup', 'penutup'],
                ],
                'sample' => [
                    'occasions' => ['Idulfitri', 'Malam Takbiran', 'Iduladha'],
                    'base' => ['recipient_name' => 'Om Hasan & Tante Mira', 'sender_name' => 'Fikri sekeluarga', 'occasion' => 'Idulfitri'],
                    'components' => [
                        'sampul' => ['title' => 'Malam Kemenangan Sudah Dekat', 'subtitle' => 'Untuk Om Hasan & Tante Mira', 'photo' => $this->image('bintang')],
                        'mundur' => ['target_at' => '+2 days 06:30', 'label' => 'Menuju salat Id pagi nanti'],
                        'kutipan' => ['quote' => 'Allahu akbar, Allahu akbar, walillahil hamd.', 'caption' => 'Gema takbir dari rumah kami untuk rumah kalian'],
                        'lapisan' => ['layers' => ['Maaf kalau tahun ini jarang berkunjung.', 'Maaf atas kata-kata yang mungkin menyinggung.', 'Semoga hati kita kembali bersih seperti fitrah.']],
                        'foto' => ['photo' => $this->image('masjid'), 'caption' => 'Salat Id bersama tahun lalu'],
                        'gosok' => ['hidden_message' => 'Semoga Om & Tante selalu sehat, rezeki lancar, dan rumahnya penuh berkah 🤲', 'cover_label' => 'Gosok untuk doa'],
                        'surat' => ['greeting_line' => 'Om Hasan dan Tante Mira,', 'body' => 'Terima kasih sudah selalu menyambut kami seperti anak sendiri. Lebaran ini kami belum bisa pulang, tapi doa kami sampai lebih dulu. Mohon maaf lahir dan batin.', 'signature' => 'Fikri, Ayu & si kecil Rafa'],
                        'musik' => ['track' => $this->track('takbir')],
                        'penutup' => ['closing' => 'Selamat hari raya. Sampai jumpa di lebaran berikutnya, insyaallah.', 'allow_reply' => true],
                    ],
                ],
            ],
            [
                'slug' => 'lebaran-mudik-ceria',
                'design' => 'Mudik Ceria',
                'category' => 'hari-raya',
                'tagline' => 'Lebaran rame-rame dengan kuis dan kupon THR',
                'story' => 'Mudik dalam angka, album perjalanan, kuis keluarga, maaf berlapis, kupon THR, dan doa di balik kartu gosok.',
                'description' => 'Ceria untuk sepupu, keponakan, atau grup keluarga. Ada statistik mudik, album perjalanan, kuis lebaran keluarga, maaf berlapis, kupon THR, dan kartu gosok berisi doa.',
                'tier' => TemplateTier::Interaktif,
                'price' => 25000,
                'duration_minutes' => 3,
                'theme' => ['accent' => '#c2410c', 'soft' => '#ffe1cc', 'gradient' => ['#fff7ed', '#ffe4cc', '#e7f5d8'], 'emoji' => '🚗', 'motif' => 'confetti', 'font' => 'modern'],
                'highlight_icon' => 'directions_car',
                'highlight_label' => 'Kuis Keluarga',
                'tags' => ['Ceria', 'Keluarga'],
                'slots' => [
                    ['sampul', 'sampul'], ['angka', 'angka_kita', true, 'Mudik dalam Angka'], ['galeri', 'galeri_geser', true, 'Album Perjalanan'], ['kuis', 'kuis', true, 'Kuis Lebaran Keluarga'],
                    ['lapisan', 'pesan_berlapis'], ['thr', 'kupon_janji', true, 'Kupon THR'], ['gosok', 'gosok_kartu'], ['musik', 'musik_latar'], ['penutup', 'penutup'],
                ],
                'sample' => [
                    'occasions' => ['Idulfitri', 'Lebaran', 'Mudik'],
                    'base' => ['recipient_name' => 'Para Sepupu', 'sender_name' => 'Mbak Dewi', 'occasion' => 'Idulfitri'],
                    'components' => [
                        'sampul' => ['title' => 'Lebaran Tiba, Sepupu-Sepupuku!', 'subtitle' => 'Mudik tahun ini makin seru 🚗', 'photo' => $this->image('pantai')],
                        'angka' => ['title' => 'Mudik dalam angka', 'stats' => ['14 jam perjalanan ke rumah Nenek', '23 sepupu berkumpul', '7 toples nastar ludes', '1 nenek yang paling bahagia']],
                        'galeri' => ['photos' => $this->photos([['kota-malam', 'Rest area tengah malam'], ['keluarga-kini', 'Foto bareng di depan rumah Nenek'], ['masjid', 'Salat Id di lapangan']])],
                        'kuis' => ['questions' => [
                            ['question' => 'Siapa yang paling cepat habisin opor?', 'options' => ['Om Budi', 'Sepupu Rafi', 'Nenek'], 'answer' => 0],
                            ['question' => 'Pertanyaan paling sering ditanya tante?', 'options' => ['Kapan nikah?', 'Kerja di mana?', 'Kok gendutan?'], 'answer' => 0],
                        ], 'success_message' => 'Kamu memang anggota keluarga sejati 😆'],
                        'lapisan' => ['layers' => ['Maaf ya kalau sering ngabisin kue kalian.', 'Maaf juga kalau suka nyontek PR waktu kecil.', 'Mohon maaf lahir dan batin, sepupu-sepupuku!']],
                        'thr' => ['coupons' => ['THR Rp20.000 untuk yang sudah hafal surat pendek', '1x traktir es kelapa di alun-alun', 'Bebas main HP Mbak seharian']],
                        'gosok' => ['hidden_message' => 'Semoga tahun depan kita kumpul lengkap lagi di rumah Nenek 🤲', 'cover_label' => 'Gosok untuk doa'],
                        'musik' => ['track' => $this->track('takbir')],
                        'penutup' => ['closing' => 'Selamat Idulfitri! Jangan lupa salim ke Nenek dulu sebelum minta THR 😄', 'allow_reply' => true],
                    ],
                ],
            ],
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function setelahHujan(): array
    {
        return [
            [
                'slug' => 'setelah-hujan',
                'design' => 'Gerimis Biru',
                'category' => 'minta-maaf',
                'tagline' => 'Permintaan maaf yang tulus, lapis demi lapis',
                'story' => 'Satu kalimat jujur, pengakuan yang terbuka pelan, surat, hal-hal yang akan kuperbaiki, janji di balik kartu gosok, lalu biarkan dia menjawab.',
                'description' => 'Untuk maaf yang susah diucapkan langsung. Penerima membuka pengakuanmu satu per satu, membaca suratmu, melihat daftar hal yang akan kamu perbaiki, menggosok kartu untuk menemukan janjimu, dan bisa membalas di akhir.',
                'tier' => TemplateTier::Interaktif,
                'price' => 29000,
                'duration_minutes' => 4,
                'theme' => ['accent' => '#3d5a80', 'soft' => '#dbe6f2', 'gradient' => ['#f1f5fa', '#dde7f1', '#f8e9dc'], 'emoji' => '☔', 'motif' => 'rain', 'font' => 'modern'],
                'highlight_icon' => 'umbrella',
                'highlight_label' => 'Gosok Janji',
                'tags' => ['Tulus', 'Deep Talk'],
                'slots' => [
                    ['sampul', 'sampul'], ['gembok', 'gembok_pin'], ['kutipan', 'kutipan'], ['pengakuan', 'pesan_berlapis', true, 'Pengakuan'], ['surat', 'surat'],
                    ['perbaikan', 'daftar_alasan', true, 'Yang Akan Kuperbaiki'], ['janji', 'gosok_kartu', true, 'Janji Tersembunyi'], ['kupon', 'kupon_janji', false, 'Janji Perbaikan'],
                    ['suara', 'pesan_suara', false], ['musik', 'musik_latar', false], ['penutup', 'penutup', true, 'Jawabanmu'],
                ],
                'sample' => [
                    'occasions' => ['Minta Maaf', 'Baikan', 'Salah Paham'],
                    'base' => ['recipient_name' => 'Laras', 'sender_name' => 'Bima', 'occasion' => 'Minta Maaf'],
                    'components' => [
                        'sampul' => ['title' => 'Laras, boleh aku bicara sebentar?', 'subtitle' => 'Ada yang ingin kusampaikan dengan jujur', 'photo' => $this->image('hujan')],
                        'kutipan' => ['quote' => 'Setelah hujan, langit biasanya lebih jernih. Semoga setelah ini, kita juga.', 'caption' => '— Bima'],
                        'pengakuan' => ['layers' => [
                            'Kemarin aku bicara dengan nada yang tidak seharusnya.',
                            'Aku sibuk membela diri, padahal kamu cuma ingin didengar.',
                            'Aku salah. Dan aku tidak mau itu jadi kebiasaan.',
                        ]],
                        'surat' => ['greeting_line' => 'Laras,', 'body' => 'Aku tidak minta kamu langsung lupa. Aku cuma ingin kamu tahu bahwa aku sadar, aku menyesal, dan aku mau memperbaikinya pelan-pelan, dengan caramu.', 'signature' => 'Bima'],
                        'perbaikan' => ['title' => 'Yang akan kuperbaiki', 'reasons' => ['Mendengar sampai selesai sebelum menjawab.', 'Tidak membalas pesan dengan "terserah".', 'Bilang kalau aku capek, bukan malah marah.']],
                        'janji' => ['hidden_message' => 'Mulai hari ini, aku dengar dulu sampai selesai sebelum menjawab.', 'cover_label' => 'Gosok untuk janjiku'],
                        'kupon' => ['coupons' => ['1x curhat tanpa disela', '1x jalan sore ke tempat favoritmu', 'Hak veto memilih makan malam']],
                        'musik' => ['track' => $this->track('teduh')],
                        'penutup' => ['closing' => 'Apa pun jawabanmu, aku akan menghargainya. Terima kasih sudah membaca sampai sini.', 'allow_reply' => true],
                    ],
                ],
            ],
            [
                'slug' => 'setelah-hujan-bendera-putih',
                'design' => 'Bendera Putih',
                'category' => 'minta-maaf',
                'tagline' => 'Minta maaf versi lucu, dengan tombol "dimaafin?"',
                'story' => 'Aku menyerah! Daftar dosa yang dibuka satu per satu, kuis tebak kesalahanku, lalu pertanyaan "dimaafin nggak?" dengan tombol yang kabur.',
                'description' => 'Untuk salah yang kecil-kecil tapi bikin bete. Huruf tulisan tangan dan suasana ceria, berisi daftar dosa berlapis, kuis tebak kesalahan, pertanyaan "dimaafin?" dengan tombol "nggak" yang kabur, kupon tebusan, dan sertifikat resmi dimaafkan.',
                'tier' => TemplateTier::Interaktif,
                'price' => 25000,
                'duration_minutes' => 3,
                'theme' => ['accent' => '#ea580c', 'soft' => '#ffe4d1', 'gradient' => ['#fffaf3', '#ffeedd', '#e0f2fe'], 'emoji' => '🏳️', 'motif' => 'confetti', 'font' => 'tulisan'],
                'highlight_icon' => 'how_to_vote',
                'highlight_label' => 'Dimaafin Nggak?',
                'tags' => ['Lucu', 'Ringan'],
                'slots' => [
                    ['sampul', 'sampul'], ['kutipan', 'kutipan'], ['dosa', 'pesan_berlapis', true, 'Daftar Dosaku'], ['kuis', 'kuis', true, 'Tebak Kesalahanku'],
                    ['tanya', 'tanya_jawab'], ['tebusan', 'kupon_janji', true, 'Kupon Tebusan'], ['sertifikat', 'sertifikat'], ['musik', 'musik_latar'], ['penutup', 'penutup'],
                ],
                'sample' => [
                    'occasions' => ['Minta Maaf', 'Baikan', 'Lupa Janji'],
                    'base' => ['recipient_name' => 'Mira', 'sender_name' => 'Kevin', 'occasion' => 'Minta Maaf'],
                    'components' => [
                        'sampul' => ['title' => 'Aku Kibarkan Bendera Putih 🏳️', 'subtitle' => 'Mira, tolong baca dulu sebelum marah lagi', 'photo' => $this->image('balon')],
                        'kutipan' => ['quote' => 'Aku menyerah. Kamu benar, aku yang salah, dan aku kangen kamu yang nggak cemberut.', 'caption' => '— Kevin, dengan penuh penyesalan'],
                        'dosa' => ['layers' => ['Dosa #1: lupa kalau kemarin janji jemput jam 5.', 'Dosa #2: alasannya "ketiduran" padahal main game.', 'Dosa #3: bilang "kok gitu aja marah". Itu dosa terbesar.']],
                        'kuis' => ['questions' => [
                            ['question' => 'Kesalahan paling fatal Kevin minggu ini?', 'options' => ['Lupa jemput', 'Bohong ketiduran', 'Semua di atas'], 'answer' => 2],
                        ], 'success_message' => 'Iya… semua di atas. Maaf ya 🙇'],
                        'tanya' => ['question' => 'Jadi… aku dimaafin nggak?', 'yes_label' => 'Iya deh 🙄', 'no_label' => 'Nggak!', 'yes_message' => 'YES! Makasih Mira, aku nggak akan ulangi (minimal sebulan) 😆'],
                        'tebusan' => ['coupons' => ['Jemput-antar seminggu penuh, tepat waktu', '1x seblak level berapa pun yang kamu mau', 'Game dihapus sementara dari HP-ku']],
                        'sertifikat' => ['title' => 'Resmi Dimaafkan', 'description' => 'Berlaku selama Kevin tidak mengulangi kesalahan yang sama.'],
                        'musik' => ['track' => $this->track('akustik')],
                        'penutup' => ['closing' => 'Terima kasih sudah sabar menghadapi aku. Besok aku jemput jam 4.55, janji!', 'allow_reply' => true],
                    ],
                ],
            ],
            [
                'slug' => 'setelah-hujan-pelangi',
                'design' => 'Pelangi',
                'category' => 'minta-maaf',
                'tagline' => 'Maaf yang dalam untuk hubungan yang ingin dijaga',
                'story' => 'Foto kenangan, pengakuan berlapis, surat, alasan kenapa aku tak mau kehilanganmu, suaraku, dan janji di balik kartu gosok.',
                'description' => 'Untuk salah paham yang lebih besar dengan sahabat, pasangan, atau keluarga. Dimulai dengan foto kenangan, lalu pengakuan berlapis, surat panjang, daftar alasan, pesan suara, dan janji yang digosok.',
                'tier' => TemplateTier::Klasik,
                'price' => 29000,
                'duration_minutes' => 4,
                'theme' => ['accent' => '#3d7a9e', 'soft' => '#d9eef7', 'gradient' => ['#f3fafd', '#e1f1f8', '#fdf0d9'], 'emoji' => '🌈', 'motif' => 'rain', 'font' => 'klasik'],
                'highlight_icon' => 'favorite',
                'highlight_label' => 'Alasan Bertahan',
                'tags' => ['Tulus', 'Sahabat'],
                'slots' => [
                    ['sampul', 'sampul'], ['foto', 'foto_bingkai'], ['pengakuan', 'pesan_berlapis', true, 'Yang Ingin Kuakui'], ['surat', 'surat'],
                    ['alasan', 'daftar_alasan', true, 'Kenapa Aku Tak Mau Kehilanganmu'], ['suara', 'pesan_suara', false], ['janji', 'gosok_kartu', true, 'Janjiku'],
                    ['musik', 'musik_latar'], ['penutup', 'penutup'],
                ],
                'sample' => [
                    'occasions' => ['Minta Maaf', 'Baikan dengan Sahabat', 'Salah Paham'],
                    'base' => ['recipient_name' => 'Tari', 'sender_name' => 'Indah', 'occasion' => 'Baikan dengan Sahabat'],
                    'components' => [
                        'sampul' => ['title' => 'Tari, aku rindu kita yang dulu', 'subtitle' => 'Setelah badai, semoga ada pelangi 🌈', 'photo' => $this->image('senja')],
                        'foto' => ['photo' => $this->image('kafe'), 'caption' => 'Kafe tempat kita curhat sampai tutup'],
                        'pengakuan' => ['layers' => ['Aku cerita masalahmu ke orang lain. Itu salah besar.', 'Aku tidak bermaksud mengkhianati, tapi akibatnya tetap menyakitimu.', 'Aku paham kalau kamu butuh waktu.']],
                        'surat' => ['greeting_line' => 'Tari,', 'body' => 'Sepuluh tahun bersahabat dan aku merusak kepercayaanmu dalam satu sore. Aku tidak mencari pembenaran. Aku hanya ingin kamu tahu aku menyesal, dan aku siap membuktikan bahwa rahasiamu aman bersamaku lagi.', 'signature' => 'Indah'],
                        'alasan' => ['title' => 'Kenapa aku tak mau kehilanganmu', 'reasons' => ['Kamu yang menemaniku di hari terburukku.', 'Kamu tahu aku lebih dari aku tahu diriku sendiri.', 'Tidak ada orang lain yang tertawa pada lelucon kita.', 'Sepuluh tahun terlalu berharga untuk berhenti di sini.']],
                        'janji' => ['hidden_message' => 'Mulai sekarang, ceritamu berhenti di aku. Selamanya.', 'cover_label' => 'Gosok untuk janjiku'],
                        'musik' => ['track' => $this->track('teduh')],
                        'penutup' => ['closing' => 'Aku tunggu, selama apa pun kamu butuh waktu.', 'allow_reply' => true],
                    ],
                ],
            ],
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function satuPertanyaan(): array
    {
        return [
            [
                'slug' => 'satu-pertanyaan',
                'design' => 'Merah Jambu',
                'category' => 'nembak',
                'tagline' => 'Nembak dengan tombol "nggak" yang selalu kabur',
                'story' => 'Angka-angka sejak kenal, kenangan, alasan kenapa dia, surat pengakuan, lalu satu pertanyaan penting yang jawabannya langsung sampai ke kamu.',
                'description' => 'Untuk yang mau nyatain perasaan dengan cara manis dan lucu. Penerima melihat angka-angka sejak kalian kenal, kenanganmu, alasanmu, membaca suratmu, lalu menjawab satu pertanyaan. Tombol "nggak" tidak bisa ditekan, dan jawabannya dikirim ke dashboard serta email kamu.',
                'tier' => TemplateTier::Interaktif,
                'price' => 29000,
                'duration_minutes' => 4,
                'theme' => ['accent' => '#d6336c', 'soft' => '#ffd6e5', 'gradient' => ['#fff4f8', '#ffe1ec', '#ffd0df'], 'emoji' => '💘', 'motif' => 'hearts', 'font' => 'modern'],
                'highlight_icon' => 'how_to_vote',
                'highlight_label' => 'Tombol Kabur',
                'tags' => ['Viral', 'Lucu'],
                'slots' => [
                    ['sampul', 'sampul'], ['gembok', 'gembok_pin'], ['angka', 'angka_kita', true, 'Sejak Kenal Kamu'], ['galeri', 'galeri_geser', false, 'Momen yang Bikin Aku Jatuh Hati'],
                    ['alasan', 'daftar_alasan', true, 'Kenapa Harus Kamu'], ['surat', 'surat', true, 'Pengakuan'], ['tanya', 'tanya_jawab'], ['sertifikat', 'sertifikat', false, 'Sertifikat Resmi'],
                    ['musik', 'musik_latar', false], ['penutup', 'penutup'],
                ],
                'sample' => [
                    'occasions' => ['Nembak', 'Confess', 'Ajak Kencan'],
                    'base' => ['recipient_name' => 'Kirana', 'sender_name' => 'Raka', 'occasion' => 'Nembak'],
                    'components' => [
                        'sampul' => ['title' => 'Kirana, ada yang mau aku tanyain…', 'subtitle' => 'Tapi baca pelan-pelan dulu ya', 'photo' => $this->image('senja')],
                        'angka' => ['title' => 'Sejak kenal kamu', 'stats' => ['186 hari sejak "hai" pertama', '2400+ chat yang kusimpan', '37 kali pura-pura kebetulan lewat'], 'footnote' => 'Iya, yang terakhir itu disengaja.'],
                        'galeri' => ['photos' => $this->photos([['kafe', 'Pertama kali ngopi bareng'], ['kota-malam', 'Pulang bareng habis hujan'], ['pantai', 'Liburan kelas yang seru']])],
                        'alasan' => ['title' => 'Kenapa harus kamu', 'reasons' => ['Kamu satu-satunya yang ketawa sama jokes recehku.', 'Kamu ingat hal kecil yang aku sendiri lupa.', 'Hariku selalu lebih ringan kalau ada kamu.']],
                        'surat' => ['greeting_line' => 'Kirana,', 'body' => 'Dari semua obrolan random kita, aku sadar satu hal: hariku selalu lebih ringan kalau ada kamu. Jadi aku mau berani jujur sekali ini.', 'signature' => 'Raka'],
                        'tanya' => ['question' => 'Mau jadi pacarku?', 'yes_label' => 'Mau! 💘', 'no_label' => 'Nggak', 'yes_message' => 'YES! Aku tahu kamu pasti mau 🥰 Sampai ketemu besok ya!'],
                        'sertifikat' => ['title' => 'Resmi Jadian', 'description' => 'Berlaku mulai hari ini, tanpa masa kedaluwarsa.'],
                        'musik' => ['track' => $this->track('akustik')],
                        'penutup' => ['closing' => 'Apa pun jawabanmu, terima kasih sudah jadi bagian terbaik hariku.', 'allow_reply' => true],
                    ],
                ],
            ],
            [
                'slug' => 'satu-pertanyaan-senja',
                'design' => 'Senja Malu-Malu',
                'category' => 'nembak',
                'tagline' => 'Pengakuan pelan-pelan di bawah langit senja',
                'story' => 'Satu kalimat besar, hal-hal yang belum sempat kubilang, foto senja kita, surat, lalu pertanyaan dan kupon "kalau jawabannya iya".',
                'description' => 'Untuk yang pemalu tapi serius. Huruf tulisan tangan dan warna senja, berisi satu kalimat pembuka, pengakuan berlapis, foto polaroid, surat, satu pertanyaan dengan tombol kabur, dan kupon kencan pertama.',
                'tier' => TemplateTier::Interaktif,
                'price' => 29000,
                'duration_minutes' => 3,
                'theme' => ['accent' => '#b45309', 'soft' => '#fde6cf', 'gradient' => ['#fff8ef', '#fde3c8', '#f9d0d6'], 'emoji' => '🌇', 'motif' => 'petals', 'font' => 'tulisan'],
                'highlight_icon' => 'wb_twilight',
                'highlight_label' => 'Pengakuan Pelan',
                'tags' => ['Malu-malu', 'Puitis'],
                'slots' => [
                    ['sampul', 'sampul'], ['kutipan', 'kutipan'], ['lapisan', 'pesan_berlapis', true, 'Hal-hal yang Belum Sempat Kubilang'], ['foto', 'foto_bingkai'],
                    ['surat', 'surat'], ['tanya', 'tanya_jawab'], ['kupon', 'kupon_janji', true, 'Kalau Jawabannya Iya…'], ['musik', 'musik_latar'], ['penutup', 'penutup'],
                ],
                'sample' => [
                    'occasions' => ['Nembak', 'Confess'],
                    'base' => ['recipient_name' => 'Dinda', 'sender_name' => 'Arya', 'occasion' => 'Confess'],
                    'components' => [
                        'sampul' => ['title' => 'Untuk Dinda, di jam senja', 'subtitle' => 'Aku butuh keberanian lama untuk mengirim ini', 'photo' => $this->image('senja')],
                        'kutipan' => ['quote' => 'Setiap senja aku memikirkan hal yang sama: kamu.', 'caption' => '— dari seseorang yang selama ini diam'],
                        'lapisan' => ['layers' => ['Aku selalu pura-pura sibuk waktu kamu lewat.', 'Aku hafal jadwal kamu ke perpustakaan.', 'Dan aku suka kamu. Sudah lama.']],
                        'foto' => ['photo' => $this->image('pantai'), 'caption' => 'Senja waktu study tour, kamu di sebelahku'],
                        'surat' => ['greeting_line' => 'Dinda,', 'body' => 'Aku bukan orang yang pandai bicara, jadi biar halaman-halaman ini yang bicara. Kamu tidak harus menjawab hari ini. Aku hanya tidak mau menyesal karena tidak pernah bilang.', 'signature' => 'Arya'],
                        'tanya' => ['question' => 'Mau nonton senja bareng aku… lebih sering?', 'yes_label' => 'Mau 🌇', 'no_label' => 'Nggak', 'yes_message' => 'Aku senyum-senyum sendiri sekarang. Makasih, Dinda 🧡'],
                        'kupon' => ['coupons' => ['Kencan pertama: es krim di taman kota', 'Foto senja bareng, kamu yang pilih tempatnya']],
                        'musik' => ['track' => $this->track('teduh')],
                        'penutup' => ['closing' => 'Apa pun jawabannya, terima kasih sudah membaca sampai habis.', 'allow_reply' => true],
                    ],
                ],
            ],
            [
                'slug' => 'satu-pertanyaan-level-up',
                'design' => 'Level Up Hati',
                'category' => 'nembak',
                'tagline' => 'Nembak ala game: selesaikan level, kalahkan bos terakhir',
                'story' => 'Level 1 kuis, level 2 item rahasia di kartu gosok, statistik pemain, bonus alasan, lalu bos terakhir: "Mau jadi player 2-ku?"',
                'description' => 'Untuk gamer dan pecinta hal lucu. Setiap halaman adalah level: kuis, kartu gosok, statistik pemain, bonus alasan, dan pertanyaan pamungkas dengan tombol yang kabur. Ditutup sertifikat "Achievement Unlocked".',
                'tier' => TemplateTier::Interaktif,
                'price' => 29000,
                'duration_minutes' => 4,
                'theme' => ['accent' => '#2563eb', 'soft' => '#dbe7ff', 'gradient' => ['#f5f8ff', '#e0e9ff', '#fde2f0'], 'emoji' => '🎮', 'motif' => 'stars', 'font' => 'modern'],
                'highlight_icon' => 'sports_esports',
                'highlight_label' => 'Mode Game',
                'tags' => ['Gamer', 'Lucu'],
                'slots' => [
                    ['sampul', 'sampul'], ['kuis', 'kuis', true, 'Level 1: Seberapa Kenal Kita'], ['item', 'gosok_kartu', true, 'Level 2: Item Rahasia'], ['angka', 'angka_kita', true, 'Statistik Pemain'],
                    ['bonus', 'daftar_alasan', true, 'Level 3: Bonus Alasan'], ['tanya', 'tanya_jawab', true, 'Bos Terakhir'], ['sertifikat', 'sertifikat', true, 'Achievement Unlocked'],
                    ['musik', 'musik_latar'], ['penutup', 'penutup'],
                ],
                'sample' => [
                    'occasions' => ['Nembak', 'Confess', 'Anniversary'],
                    'base' => ['recipient_name' => 'Nadia', 'sender_name' => 'Bagas', 'occasion' => 'Nembak'],
                    'components' => [
                        'sampul' => ['title' => 'Player 1 Siap. Mulai Permainan?', 'subtitle' => 'Selesaikan semua level untuk hadiah utama 🎮', 'photo' => $this->image('kota-malam')],
                        'kuis' => ['questions' => [
                            ['question' => 'Game yang pertama kali kita mabar?', 'options' => ['Mobile Legends', 'Minecraft', 'Uno'], 'answer' => 1],
                            ['question' => 'Snack wajib waktu kita main?', 'options' => ['Keripik pedas', 'Cokelat', 'Seblak'], 'answer' => 0],
                        ], 'success_message' => 'Level 1 selesai! +100 XP ✨'],
                        'item' => ['hidden_message' => 'Item langka didapat: hati Bagas (cuma ada satu) 💙', 'cover_label' => 'Gosok untuk loot'],
                        'angka' => ['title' => 'Statistik pemain', 'stats' => ['142 jam mabar bareng', '89 kali kamu carry aku', '99+ level suka ke kamu']],
                        'bonus' => ['title' => 'Bonus alasan', 'reasons' => ['Kamu nggak pernah ninggalin aku waktu kalah.', 'Kamu sabar ngajarin aku main.', 'Kamu bikin kalah pun terasa seru.']],
                        'tanya' => ['question' => 'Mau jadi player 2-ku selamanya?', 'yes_label' => 'Accept ✅', 'no_label' => 'Decline', 'yes_message' => 'Achievement unlocked: Punya pacar! 🏆 Gas mabar nanti malam!'],
                        'sertifikat' => ['title' => 'Achievement Unlocked: Duo Terbaik', 'description' => 'Diberikan kepada pemain yang berhasil menaklukkan hati Bagas.'],
                        'musik' => ['track' => $this->track('rindu')],
                        'penutup' => ['closing' => 'GG! Terima kasih sudah main sampai akhir 💙', 'allow_reply' => true],
                    ],
                ],
            ],
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function lemparToga(): array
    {
        return [
            [
                'slug' => 'lempar-toga',
                'design' => 'Biru Akademik',
                'category' => 'wisuda',
                'tagline' => 'Rayakan perjalanan panjang menuju toga',
                'story' => 'Linimasa perjuangan dari maba sampai sidang, angka-angka perjuangannya, surat bangga, hadiah di kartu gosok, lalu sertifikat "Wisudawan Paling Tangguh".',
                'description' => 'Untuk sahabat, pasangan, adik, atau anak yang baru lulus. Ajak dia menengok perjalanannya lewat linimasa foto, angka-angka perjuangan, kalimat penyemangat, suratmu, kartu gosok berisi hadiah kelulusan, dan sertifikat lucu.',
                'tier' => TemplateTier::Interaktif,
                'price' => 29000,
                'duration_minutes' => 4,
                'theme' => ['accent' => '#24386b', 'soft' => '#dde4f6', 'gradient' => ['#f5f7fc', '#e3e9f7', '#f6e7c1'], 'emoji' => '🎓', 'motif' => 'confetti', 'font' => 'modern'],
                'highlight_icon' => 'school',
                'highlight_label' => 'Lempar Toga',
                'tags' => ['Bangga', 'Kelulusan'],
                'slots' => [
                    ['sampul', 'sampul'], ['perjalanan', 'linimasa', true, 'Perjalanan Menuju Toga'], ['angka', 'angka_kita', true, 'Perjuangan dalam Angka'], ['kutipan', 'kutipan'],
                    ['surat', 'surat', true, 'Surat Bangga'], ['hadiah', 'gosok_kartu', true, 'Hadiah Kelulusan'], ['suara', 'pesan_suara', false], ['sertifikat', 'sertifikat'],
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
                        'sampul' => ['title' => 'Selamat Wisuda, Sarjana Kevin!', 'subtitle' => 'Toganya sudah kamu lempar — sekarang giliran kami yang bangga', 'photo' => $this->image('wisuda')],
                        'perjalanan' => ['moments' => $this->photos([
                            ['kampus', '2022 — Maba polos yang nyasar cari ruang kuliah'],
                            ['kota-malam', '2025 — Begadang revisi, ditemani kopi saset'],
                            ['wisuda', '2026 — Resmi bergelar sarjana!'],
                        ])],
                        'angka' => ['title' => 'Perjuangan dalam angka', 'stats' => ['1460 hari kuliah', '214 kali revisi', '87 gelas kopi saset', '1 gelar sarjana']],
                        'kutipan' => ['quote' => 'Pelan-pelan pun tetap sampai. Hari ini buktinya.', 'caption' => '— Tim Kosan Melati'],
                        'surat' => ['greeting_line' => 'Kevin, Sang Sarjana,', 'body' => 'Kami lihat sendiri malam-malam panjangmu: revisi yang tak kunjung selesai, dosen yang susah ditemui, dan rasa ingin menyerah. Tapi kamu tetap jalan. Hari ini kamu membuktikan bahwa pelan-pelan pun tetap sampai.', 'signature' => 'Tim Kosan Melati'],
                        'hadiah' => ['hidden_message' => 'Hadiahmu: traktiran all-you-can-eat bareng anak kosan! 🍣🎉', 'cover_label' => 'Gosok untuk hadiah'],
                        'sertifikat' => ['title' => 'Wisudawan Paling Tangguh', 'description' => 'Atas keberhasilan bertahan dari revisi, deadline, dan godaan rebahan.'],
                        'musik' => ['track' => $this->track('langkah_baru')],
                        'penutup' => ['closing' => 'Selamat memulai babak baru. Dunia kerja, kamu siap! 🎓', 'allow_reply' => true],
                    ],
                ],
            ],
            [
                'slug' => 'lempar-toga-emas',
                'design' => 'Toga Emas',
                'category' => 'wisuda',
                'tagline' => 'Ucapan wisuda elegan dari orang tua untuk anak',
                'story' => 'Satu kalimat bangga, linimasa dari TK sampai toga, foto wisuda, surat orang tua, alasan kami bangga, hadiah, dan sertifikat.',
                'description' => 'Elegan dengan sentuhan emas dan huruf klasik. Cocok dari orang tua atau keluarga besar. Ada kalimat besar, linimasa sekolah, foto berbingkai, surat, daftar hal yang membuat bangga, kupon hadiah, dan sertifikat.',
                'tier' => TemplateTier::Klasik,
                'price' => 35000,
                'duration_minutes' => 5,
                'theme' => ['accent' => '#8a6d1d', 'soft' => '#f6ecc9', 'gradient' => ['#fffcf2', '#f8efd2', '#efe2bd'], 'emoji' => '🏅', 'motif' => 'stars', 'font' => 'klasik'],
                'highlight_icon' => 'military_tech',
                'highlight_label' => 'Elegan',
                'tags' => ['Orang Tua', 'Elegan'],
                'slots' => [
                    ['sampul', 'sampul'], ['kutipan', 'kutipan'], ['perjalanan', 'linimasa', true, 'Dari TK sampai Toga'], ['foto', 'foto_bingkai'],
                    ['surat', 'surat'], ['bangga', 'daftar_alasan', true, 'Yang Membuat Kami Bangga'], ['hadiah', 'kupon_janji', true, 'Hadiah dari Ayah & Ibu'],
                    ['sertifikat', 'sertifikat'], ['musik', 'musik_latar'], ['penutup', 'penutup'],
                ],
                'sample' => [
                    'occasions' => ['Wisuda S1', 'Wisuda S2', 'Wisuda SMA'],
                    'base' => ['recipient_name' => 'Putri', 'sender_name' => 'Ayah & Ibu', 'occasion' => 'Wisuda S1'],
                    'components' => [
                        'sampul' => ['title' => 'Selamat Wisuda, Putri Kebanggaan Kami', 'subtitle' => 'Sarjana Hukum, Cum Laude', 'photo' => $this->image('wisuda')],
                        'kutipan' => ['quote' => 'Toga itu milikmu. Tapi rasa bangga hari ini, izinkan kami ikut memilikinya.', 'caption' => '— Ayah & Ibu'],
                        'perjalanan' => ['moments' => $this->photos([
                            ['keluarga-lama', '2008 — Hari pertama TK, menangis tidak mau ditinggal'],
                            ['kampus', '2020 — Lolos kampus impian'],
                            ['wisuda', '2026 — Sarjana Hukum, cum laude'],
                        ])],
                        'foto' => ['photo' => $this->image('keluarga-kini'), 'caption' => 'Foto keluarga di hari wisuda'],
                        'surat' => ['greeting_line' => 'Putri kami tersayang,', 'body' => 'Dulu kami mengantarmu ke gerbang sekolah dan menunggumu pulang. Hari ini kami duduk di barisan orang tua, melihatmu dipanggil ke panggung. Teruslah melangkah dengan jujur dan rendah hati. Doa kami selalu menyertai.', 'signature' => 'Ayah & Ibu'],
                        'bangga' => ['title' => 'Yang membuat kami bangga', 'reasons' => ['Kamu tidak pernah menyerah walau sempat sakit di semester lima.', 'Kamu tetap menelepon Ibu setiap malam.', 'Kamu membiayai sebagian kuliahmu sendiri.', 'Kamu tetap rendah hati.']],
                        'hadiah' => ['coupons' => ['Laptop baru untuk kerja pertamamu', 'Liburan keluarga ke tempat pilihanmu']],
                        'sertifikat' => ['title' => 'Anak Paling Membanggakan', 'description' => 'Diberikan dengan penuh cinta oleh Ayah & Ibu.'],
                        'musik' => ['track' => $this->track('langkah_baru')],
                        'penutup' => ['closing' => 'Selamat, Nak. Ini baru awal dari semua hal baik.', 'allow_reply' => true],
                    ],
                ],
            ],
            [
                'slug' => 'lempar-toga-lulus-sidang',
                'design' => 'Lulus Sidang',
                'category' => 'wisuda',
                'tagline' => 'Ucapan kocak untuk teman yang akhirnya lulus sidang',
                'story' => 'Skripsi dalam angka, kuis mahasiswa akhir, pesan dari teman seperjuangan, gelar baru di kartu gosok, kupon, dan sertifikat "Lulus dengan Pujian (Revisi)".',
                'description' => 'Untuk teman seperjuangan yang baru lulus sidang. Huruf tulisan tangan dan nuansa santai, berisi statistik skripsi, kuis, pesan berlapis dari teman, kartu gosok gelar baru, kupon, dan sertifikat lucu.',
                'tier' => TemplateTier::Interaktif,
                'price' => 25000,
                'duration_minutes' => 3,
                'theme' => ['accent' => '#0e7490', 'soft' => '#d3eff5', 'gradient' => ['#f2fbfd', '#dbf1f6', '#fff1cc'], 'emoji' => '📚', 'motif' => 'confetti', 'font' => 'tulisan'],
                'highlight_icon' => 'menu_book',
                'highlight_label' => 'Lucu',
                'tags' => ['Teman', 'Kocak'],
                'slots' => [
                    ['sampul', 'sampul'], ['angka', 'angka_kita', true, 'Skripsi dalam Angka'], ['kuis', 'kuis', true, 'Kuis Mahasiswa Akhir'], ['lapisan', 'pesan_berlapis', true, 'Pesan dari Teman Seperjuangan'],
                    ['gelar', 'gosok_kartu', true, 'Gelar Barumu'], ['kupon', 'kupon_janji'], ['sertifikat', 'sertifikat'], ['musik', 'musik_latar'], ['penutup', 'penutup'],
                ],
                'sample' => [
                    'occasions' => ['Lulus Sidang', 'Wisuda S1', 'Selesai Skripsi'],
                    'base' => ['recipient_name' => 'Bayu', 'sender_name' => 'Anak-anak Lab Lantai 3', 'occasion' => 'Lulus Sidang'],
                    'components' => [
                        'sampul' => ['title' => 'BAYU LULUS SIDANG!!! 📚', 'subtitle' => 'Akhirnya bebas dari kata "revisi"… untuk sementara', 'photo' => $this->image('kampus')],
                        'angka' => ['title' => 'Skripsi dalam angka', 'stats' => ['18 bulan mengerjakan skripsi', '9 kali ganti judul', '320 halaman (setengahnya lampiran)', '1 dosen pembimbing yang akhirnya ACC']],
                        'kuis' => ['questions' => [
                            ['question' => 'Kalimat dosen pembimbing paling menakutkan?', 'options' => ['"Coba dibaca lagi"', '"Saya lagi di luar kota"', '"Ganti metode saja"'], 'answer' => 2],
                        ], 'success_message' => 'Trauma yang sama. Selamat sudah melewatinya!'],
                        'lapisan' => ['layers' => ['Dari Sinta: makasih sudah bagi template daftar pustaka.', 'Dari Galih: kamu bukti kalau begadang ada hasilnya.', 'Dari semua: lab ini bakal sepi tanpa suara keyboard-mu.']],
                        'gelar' => ['hidden_message' => 'Bayu, S.T. — Sarjana Tahan banting 💪', 'cover_label' => 'Gosok untuk gelar'],
                        'kupon' => ['coupons' => ['Kami bantu angkat barang pindahan kos', '1x makan-makan syukuran (kamu yang bayar ya)']],
                        'sertifikat' => ['title' => 'Lulus dengan Pujian (dan Revisi)', 'description' => 'Atas keberhasilan menaklukkan bab 1 sampai 5 dan dosen penguji.'],
                        'musik' => ['track' => $this->track('langkah_baru')],
                        'penutup' => ['closing' => 'Selamat, Bay! Kami nyusul (semoga semester ini) 😭', 'allow_reply' => true],
                    ],
                ],
            ],
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function seikatTerimaKasih(): array
    {
        return [
            [
                'slug' => 'seikat-terima-kasih',
                'design' => 'Untuk Guru',
                'category' => 'terima-kasih',
                'tagline' => 'Untuk guru yang mengajarkan lebih dari pelajaran',
                'story' => 'Seikat bunga digital berisi hal-hal kecil yang kamu syukuri, kalimat terima kasih, album, surat, janji balas budi, dan sertifikat.',
                'description' => 'Untuk guru, dosen, atau mentor. Setiap "kelopak" membuka satu hal yang kamu syukuri darinya, lalu kalimat besar, album kenangan, pesan suara, surat tulus, kupon balas budi, dan sertifikat penghargaan.',
                'tier' => TemplateTier::Klasik,
                'price' => 25000,
                'duration_minutes' => 4,
                'theme' => ['accent' => '#127475', 'soft' => '#d5efee', 'gradient' => ['#f3fbfa', '#dcf1ef', '#fde8d7'], 'emoji' => '🌷', 'motif' => 'petals', 'font' => 'klasik'],
                'highlight_icon' => 'local_florist',
                'highlight_label' => 'Buka Kelopak',
                'tags' => ['Tulus', 'Apresiasi'],
                'slots' => [
                    ['sampul', 'sampul'], ['kelopak', 'pesan_berlapis', true, 'Hal-hal yang Kusyukuri Darimu'], ['kutipan', 'kutipan'], ['galeri', 'galeri_geser', false, 'Momen Bersamamu'],
                    ['suara', 'pesan_suara', false], ['surat', 'surat'], ['balas_budi', 'kupon_janji', false, 'Janji Balas Budi'], ['sertifikat', 'sertifikat'],
                    ['musik', 'musik_latar', false], ['penutup', 'penutup'],
                ],
                'sample' => [
                    'occasions' => ['Hari Guru', 'Terima Kasih', 'Perpisahan Sekolah', 'Apresiasi Mentor'],
                    'base' => ['recipient_name' => 'Bu Ratna', 'sender_name' => 'Alya & teman-teman 12 IPA 2', 'occasion' => 'Hari Guru'],
                    'components' => [
                        'sampul' => ['title' => 'Untuk Bu Ratna, Terima Kasih', 'subtitle' => 'Seikat bunga kecil dari murid-murid yang dulu sering telat', 'photo' => $this->image('buket')],
                        'kelopak' => ['layers' => [
                            'Terima kasih sudah sabar menjelaskan rumus yang sama sampai lima kali.',
                            'Terima kasih sudah percaya kami bisa, waktu kami sendiri tidak yakin.',
                            'Terima kasih untuk “Ibu tunggu di kelas ya” yang selalu bikin kami lari dari kantin.',
                        ]],
                        'kutipan' => ['quote' => 'Guru yang baik mengajar pelajaran. Ibu mengajari kami cara percaya pada diri sendiri.', 'caption' => '— 12 IPA 2'],
                        'galeri' => ['photos' => $this->photos([['kampus', 'Sekolah tempat semuanya dimulai'], ['buket', 'Bunga kecil untuk guru besar'], ['kafe', 'Reuni kecil bareng Ibu']])],
                        'surat' => ['greeting_line' => 'Bu Ratna yang kami sayangi,', 'body' => 'Mungkin Ibu tidak ingat semua nama kami, tapi kami ingat semua pelajaran dari Ibu — termasuk yang tidak ada di buku. Sekarang kami sudah tersebar ke mana-mana, dan selalu ada sedikit Ibu di cara kami bekerja.', 'signature' => 'Alya & teman-teman 12 IPA 2'],
                        'balas_budi' => ['coupons' => ['1x traktir kopi saat Ibu ke kota kami', 'Jadi pembicara tamu untuk adik kelas kapan pun diminta']],
                        'sertifikat' => ['title' => 'Guru Paling Sabar Sedunia', 'description' => 'Atas kesabaran menghadapi kelas paling ribut di angkatan.'],
                        'musik' => ['track' => $this->track('terima_kasih')],
                        'penutup' => ['closing' => 'Sehat selalu, Bu. Terima kasih sudah jadi bagian terbaik masa sekolah kami. 🌷', 'allow_reply' => true],
                    ],
                ],
            ],
            [
                'slug' => 'seikat-terima-kasih-perpisahan-kantor',
                'design' => 'Perpisahan Kantor',
                'category' => 'terima-kasih',
                'tagline' => 'Salam perpisahan dari tim untuk rekan kerja',
                'story' => 'Tahun-tahun bersama dalam angka, album kantor, pesan dari tim, satu kalimat besar, surat, sertifikat, dan "utang" yang belum lunas.',
                'description' => 'Untuk rekan kerja atau atasan yang resign, pensiun, atau pindah. Cocok dipakai patungan satu tim: angka-angka kebersamaan, album, pesan berlapis, kalimat besar, surat, sertifikat, dan kupon.',
                'tier' => TemplateTier::Interaktif,
                'price' => 29000,
                'duration_minutes' => 4,
                'theme' => ['accent' => '#1d4ed8', 'soft' => '#dbe6fb', 'gradient' => ['#f6f9ff', '#e3ebfb', '#e8f6ef'], 'emoji' => '💼', 'motif' => 'confetti', 'font' => 'modern'],
                'highlight_icon' => 'groups',
                'highlight_label' => 'Patungan Tim',
                'tags' => ['Kantor', 'Perpisahan'],
                'slots' => [
                    ['sampul', 'sampul'], ['angka', 'angka_kita', true, 'Bersama Tim dalam Angka'], ['galeri', 'galeri_geser', true, 'Album Kantor'], ['lapisan', 'pesan_berlapis', true, 'Pesan dari Tim'],
                    ['kutipan', 'kutipan'], ['surat', 'surat'], ['sertifikat', 'sertifikat'], ['utang', 'kupon_janji', false, 'Utang yang Belum Lunas'], ['musik', 'musik_latar'], ['penutup', 'penutup'],
                ],
                'sample' => [
                    'occasions' => ['Perpisahan Kantor', 'Pensiun', 'Resign', 'Terima Kasih'],
                    'base' => ['recipient_name' => 'Mas Adi', 'sender_name' => 'Tim Produk', 'occasion' => 'Perpisahan Kantor'],
                    'contributions' => [
                        ['name' => 'Wulan', 'message' => 'Makasih sudah jadi mentor pertamaku di sini, Mas. Sukses di tempat baru!', 'photo' => null],
                        ['name' => 'Reza', 'message' => 'Siapa lagi yang mau beliin gorengan jam 4 sore? 😭', 'photo' => null],
                    ],
                    'components' => [
                        'sampul' => ['title' => 'Sampai Jumpa, Mas Adi!', 'subtitle' => 'Terima kasih untuk 5 tahun yang luar biasa', 'photo' => $this->image('kota-malam')],
                        'angka' => ['title' => 'Bersama tim dalam angka', 'stats' => ['5 tahun bersama', '38 fitur yang dirilis', '1200+ gorengan jam 4 sore', '1 rekan kerja terbaik']],
                        'galeri' => ['photos' => $this->photos([['kafe', 'Rapat sambil ngopi'], ['pantai', 'Outing kantor 2024'], ['kota-malam', 'Lembur rilis versi 2.0']])],
                        'lapisan' => ['layers' => ['Terima kasih sudah selalu bilang "tenang, kita beresin bareng".', 'Terima kasih sudah membela tim di depan klien.', 'Terima kasih sudah membuat kantor terasa seperti rumah kedua.']],
                        'kutipan' => ['quote' => 'Pemimpin yang baik pergi meninggalkan tim yang lebih kuat. Itu yang Mas Adi lakukan.', 'caption' => '— Tim Produk'],
                        'surat' => ['greeting_line' => 'Mas Adi,', 'body' => 'Kami masih belum terbiasa membayangkan stand-up pagi tanpa lelucon garingmu. Terima kasih sudah sabar mengajari, berani mengambil tanggung jawab, dan selalu memastikan semua orang pulang tepat waktu. Sukses di petualangan barumu.', 'signature' => 'Tim Produk'],
                        'sertifikat' => ['title' => 'Rekan Kerja Terbaik', 'description' => 'Diberikan atas kepemimpinan, kesabaran, dan gorengan tanpa henti.'],
                        'utang' => ['coupons' => ['1x makan siang bareng tiap kali Mas mampir', 'Bantu review CV tim kapan pun dibutuhkan']],
                        'musik' => ['track' => $this->track('terima_kasih')],
                        'penutup' => ['closing' => 'Pintu tim ini selalu terbuka untukmu. Jangan lupa main ke sini!', 'allow_reply' => true],
                    ],
                ],
            ],
            [
                'slug' => 'seikat-terima-kasih-sahabat',
                'design' => 'Bunga Matahari',
                'category' => 'terima-kasih',
                'tagline' => 'Terima kasih untuk sahabat yang selalu ada',
                'story' => 'Foto kenangan, daftar "terima kasih karena…", angka persahabatan, surat, rahasia kecil di kartu gosok, dan kupon.',
                'description' => 'Untuk sahabat, saudara, atau siapa saja yang sudah banyak membantu. Hangat dengan huruf tulisan tangan: foto, daftar terima kasih, statistik persahabatan, surat, kartu gosok, dan kupon.',
                'tier' => TemplateTier::Klasik,
                'price' => 25000,
                'duration_minutes' => 3,
                'theme' => ['accent' => '#be185d', 'soft' => '#fcdcea', 'gradient' => ['#fff5f9', '#fde3ee', '#fef3c7'], 'emoji' => '🌻', 'motif' => 'petals', 'font' => 'tulisan'],
                'highlight_icon' => 'volunteer_activism',
                'highlight_label' => 'Untuk Sahabat',
                'tags' => ['Sahabat', 'Hangat'],
                'slots' => [
                    ['sampul', 'sampul'], ['foto', 'foto_bingkai'], ['alasan', 'daftar_alasan', true, 'Terima Kasih Karena…'], ['angka', 'angka_kita'],
                    ['surat', 'surat'], ['rahasia', 'gosok_kartu', true, 'Rahasia Kecil'], ['kupon', 'kupon_janji'], ['musik', 'musik_latar'], ['penutup', 'penutup'],
                ],
                'sample' => [
                    'occasions' => ['Terima Kasih', 'Hari Persahabatan', 'Tanpa Alasan'],
                    'base' => ['recipient_name' => 'Rara', 'sender_name' => 'Citra', 'occasion' => 'Terima Kasih'],
                    'components' => [
                        'sampul' => ['title' => 'Rara, terima kasih ya 🌻', 'subtitle' => 'Untuk semua hal yang jarang kuucapkan', 'photo' => $this->image('buket')],
                        'foto' => ['photo' => $this->image('pantai'), 'caption' => 'Liburan nekat berdua'],
                        'alasan' => ['title' => 'Terima kasih karena…', 'reasons' => ['Kamu datang jam 2 pagi waktu aku putus.', 'Kamu bantu pindahan kos tanpa mengeluh.', 'Kamu selalu jujur, walau kadang pedas.', 'Kamu merayakan hal kecil yang kucapai.']],
                        'angka' => ['title' => 'Persahabatan kita', 'stats' => ['12 tahun bersahabat', '3 kota yang kita tinggali', '∞ voice note curhat']],
                        'surat' => ['greeting_line' => 'Rara,', 'body' => 'Aku jarang bilang ini, tapi hidupku jauh lebih ringan karena ada kamu. Terima kasih sudah memilih tetap jadi sahabatku, bahkan di masa-masa aku sulit disayang.', 'signature' => 'Citra'],
                        'rahasia' => ['hidden_message' => 'Tiket konser band favoritmu sudah kubeli. Kita nonton bareng! 🎫', 'cover_label' => 'Gosok rahasianya'],
                        'kupon' => ['coupons' => ['1x curhat semalam suntuk, aku yang siapkan camilan', 'Jadi fotografer pribadimu seharian']],
                        'musik' => ['track' => $this->track('terima_kasih')],
                        'penutup' => ['closing' => 'Terima kasih sudah jadi rumah di luar rumah 🌻', 'allow_reply' => true],
                    ],
                ],
            ],
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function jarakRindu(): array
    {
        return [
            [
                'slug' => 'jarak-rindu',
                'design' => 'Ungu Senja',
                'category' => 'kangen',
                'tagline' => 'Untuk pasangan LDR yang sedang berjauhan',
                'story' => 'Hitung mundur ke pertemuan berikutnya, jarak dalam angka, kenangan yang bikin rindu, suaramu, surat, dan janji saat bertemu lagi.',
                'description' => 'Untuk pasangan LDR, sahabat di kota lain, atau keluarga di perantauan. Dibuka dengan hitung mundur ke hari bertemu, lalu statistik jarak, album kenangan, kalimat besar, pesan suara, surat, dan daftar hal yang akan kalian lakukan bersama nanti.',
                'tier' => TemplateTier::Interaktif,
                'price' => 29000,
                'duration_minutes' => 4,
                'theme' => ['accent' => '#5b4b8a', 'soft' => '#e6e0f6', 'gradient' => ['#f6f3fc', '#e6e0f6', '#ffe3ef'], 'emoji' => '✈️', 'motif' => 'stars', 'font' => 'modern'],
                'highlight_icon' => 'flight',
                'highlight_label' => 'Hitung Mundur',
                'tags' => ['LDR', 'Rindu'],
                'slots' => [
                    ['sampul', 'sampul'], ['mundur', 'hitung_mundur', true, 'Menuju Hari Bertemu'], ['angka', 'angka_kita', true, 'Jarak Kita dalam Angka'], ['galeri', 'galeri_geser', true, 'Kenangan yang Bikin Rindu'],
                    ['suara', 'pesan_suara', false], ['kutipan', 'kutipan'], ['surat', 'surat'], ['nanti', 'kupon_janji', true, 'Saat Kita Bertemu Nanti'],
                    ['musik', 'musik_latar', false], ['penutup', 'penutup'],
                ],
                'sample' => [
                    'occasions' => ['Kangen', 'LDR', 'Perantauan'],
                    'base' => ['recipient_name' => 'Arga', 'sender_name' => 'Nisa', 'occasion' => 'Kangen'],
                    'components' => [
                        'sampul' => ['title' => 'Arga, 842 km itu jauh ya', 'subtitle' => 'Tapi rinduku lebih jauh lagi', 'photo' => $this->image('kota-malam')],
                        'mundur' => ['target_at' => '+23 days 10:00', 'label' => 'Sampai kita ketemu di stasiun'],
                        'angka' => ['title' => 'Jarak kita dalam angka', 'stats' => ['842 km antara kita', '96 hari sejak terakhir ketemu', '310 video call', '1 tujuan: pulang ke kamu']],
                        'galeri' => ['photos' => $this->photos([['kafe', 'Kopi terakhir sebelum kamu berangkat'], ['senja', 'Senja yang kita kejar bareng'], ['pantai', 'Janji balik ke pantai ini']])],
                        'kutipan' => ['quote' => 'Jarak cuma angka. Yang kurasakan tiap malam jauh lebih besar dari itu.', 'caption' => '— Nisa'],
                        'surat' => ['greeting_line' => 'Arga,', 'body' => 'Hari-hariku tetap jalan, tapi selalu ada yang kurang. Terima kasih sudah tetap mengabari walau lelah. Sebentar lagi, ya.', 'signature' => 'Yang menunggumu, Nisa'],
                        'nanti' => ['coupons' => ['Makan seblak di tempat biasa', 'Nonton film yang kita tunda', 'Peluk lama di peron stasiun']],
                        'musik' => ['track' => $this->track('rindu')],
                        'penutup' => ['closing' => 'Jaga diri baik-baik di sana. Sampai ketemu sebentar lagi 🤍', 'allow_reply' => true],
                    ],
                ],
            ],
            [
                'slug' => 'jarak-rindu-surat-rantau',
                'design' => 'Surat dari Rantau',
                'category' => 'kangen',
                'tagline' => 'Rindu rumah dari anak yang merantau',
                'story' => 'Satu kalimat untuk rumah, linimasa sebelum merantau, hal-hal yang kurindukan, suara, surat, hitung mundur mudik, dan janji oleh-oleh.',
                'description' => 'Untuk orang tua atau keluarga di kampung halaman. Bernuansa surat lama: kalimat besar, linimasa kenangan, pesan berlapis tentang hal yang dirindukan, pesan suara, surat, hitung mundur pulang, dan kupon oleh-oleh.',
                'tier' => TemplateTier::Klasik,
                'price' => 29000,
                'duration_minutes' => 4,
                'theme' => ['accent' => '#6d4c1f', 'soft' => '#f2e3cb', 'gradient' => ['#fbf7f0', '#f2e6d2', '#e3eddc'], 'emoji' => '🏠', 'motif' => 'film', 'font' => 'klasik'],
                'highlight_icon' => 'home',
                'highlight_label' => 'Rindu Rumah',
                'tags' => ['Perantauan', 'Keluarga'],
                'slots' => [
                    ['sampul', 'sampul'], ['kutipan', 'kutipan'], ['linimasa', 'linimasa', true, 'Sebelum Aku Merantau'], ['rindu', 'pesan_berlapis', true, 'Hal yang Kurindukan dari Rumah'],
                    ['suara', 'pesan_suara', false], ['surat', 'surat'], ['mundur', 'hitung_mundur', true, 'Menuju Pulang Kampung'], ['oleh_oleh', 'kupon_janji', true, 'Oleh-oleh yang Kujanjikan'],
                    ['musik', 'musik_latar'], ['penutup', 'penutup'],
                ],
                'sample' => [
                    'occasions' => ['Kangen Rumah', 'Perantauan', 'Hari Ibu'],
                    'base' => ['recipient_name' => 'Bapak & Mamak', 'sender_name' => 'Ucok', 'occasion' => 'Kangen Rumah'],
                    'components' => [
                        'sampul' => ['title' => 'Surat dari Rantau untuk Bapak & Mamak', 'subtitle' => 'Dari anakmu di Jakarta', 'photo' => $this->image('keluarga-kini')],
                        'kutipan' => ['quote' => 'Sejauh apa pun aku pergi, jalan pulang selalu kuingat.', 'caption' => '— Ucok'],
                        'linimasa' => ['moments' => $this->photos([
                            ['keluarga-lama', '2005 — Bapak mengajariku mancing di sungai'],
                            ['kampus', '2019 — Diantar ke terminal untuk kuliah'],
                            ['kota-malam', '2026 — Sekarang kerja di kota besar'],
                        ])],
                        'rindu' => ['layers' => ['Aku rindu masakan arsik Mamak.', 'Aku rindu suara Bapak membangunkanku subuh.', 'Aku rindu rumah yang selalu ramai waktu makan malam.']],
                        'surat' => ['greeting_line' => 'Bapak dan Mamak,', 'body' => 'Di sini aku baik-baik saja, makan cukup, kerja lancar. Kadang sepi, tapi setiap kali ingat doa Mamak, aku kuat lagi. Tunggu aku pulang, ya.', 'signature' => 'Anakmu, Ucok'],
                        'mundur' => ['target_at' => '+40 days 07:00', 'label' => 'Kereta pagi menuju kampung'],
                        'oleh_oleh' => ['coupons' => ['Kain songket untuk Mamak', 'Topi baru untuk Bapak', 'Seminggu penuh di rumah tanpa buka laptop']],
                        'musik' => ['track' => $this->track('rindu')],
                        'penutup' => ['closing' => 'Sehat-sehat di rumah. Sebentar lagi aku pulang.', 'allow_reply' => true],
                    ],
                ],
            ],
            [
                'slug' => 'jarak-rindu-bintang',
                'design' => 'Bintang yang Sama',
                'category' => 'kangen',
                'tagline' => 'Di bawah langit yang sama, walau berjauhan',
                'story' => 'Kalimat tentang langit yang sama, angka rindu, alasan paling kurindukan, foto, rahasia di kartu gosok, surat, dan rencana nanti.',
                'description' => 'Romantis bernuansa langit malam dengan huruf tulisan tangan. Berisi kalimat besar, statistik rindu, daftar hal yang paling dirindukan, foto, kartu gosok rahasia, surat, dan kupon rencana bertemu.',
                'tier' => TemplateTier::Interaktif,
                'price' => 32000,
                'duration_minutes' => 4,
                'theme' => ['accent' => '#3730a3', 'soft' => '#e0e0fb', 'gradient' => ['#f1f0ff', '#dedcfb', '#d6e4fb'], 'emoji' => '🌙', 'motif' => 'stars', 'font' => 'tulisan'],
                'highlight_icon' => 'nights_stay',
                'highlight_label' => 'Langit Malam',
                'tags' => ['Romantis', 'LDR'],
                'slots' => [
                    ['sampul', 'sampul'], ['kutipan', 'kutipan'], ['angka', 'angka_kita', true, 'Rindu dalam Angka'], ['alasan', 'daftar_alasan', true, 'Yang Paling Kurindukan Darimu'],
                    ['foto', 'foto_bingkai'], ['rahasia', 'gosok_kartu', true, 'Rahasia Kecil'], ['surat', 'surat'], ['nanti', 'kupon_janji', true, 'Rencana Kita Nanti'],
                    ['musik', 'musik_latar'], ['penutup', 'penutup'],
                ],
                'sample' => [
                    'occasions' => ['Kangen', 'LDR', 'Tanpa Alasan'],
                    'base' => ['recipient_name' => 'Lintang', 'sender_name' => 'Bara', 'occasion' => 'LDR'],
                    'components' => [
                        'sampul' => ['title' => 'Lintang, lihat ke langit malam ini', 'subtitle' => 'Aku juga sedang melihatnya 🌙', 'photo' => $this->image('bintang')],
                        'kutipan' => ['quote' => 'Kita memang beda kota, tapi bulan yang kita lihat masih sama.', 'caption' => '— Bara, dari Balikpapan'],
                        'angka' => ['title' => 'Rindu dalam angka', 'stats' => ['1200 km jarak kita', '2 zona waktu berbeda', '58 malam ngobrol sampai ketiduran']],
                        'alasan' => ['title' => 'Yang paling kurindukan darimu', 'reasons' => ['Cara kamu menggenggam tanganku waktu nyebrang jalan.', 'Tawamu yang keras di bioskop.', 'Wangi rambutmu waktu kamu bersandar.']],
                        'foto' => ['photo' => $this->image('senja'), 'caption' => 'Malam terakhir sebelum aku berangkat'],
                        'rahasia' => ['hidden_message' => 'Tiket pesawat ke kotamu sudah kubeli. Bulan depan aku datang ✈️', 'cover_label' => 'Gosok rahasianya'],
                        'surat' => ['greeting_line' => 'Lintang,', 'body' => 'Setiap malam sebelum tidur aku melihat ke luar jendela dan membayangkan kamu di bawah langit yang sama. Jarak ini sementara. Kita tidak.', 'signature' => 'Bara'],
                        'nanti' => ['coupons' => ['Lihat bintang dari bukit dekat rumahmu', 'Masak bareng resep nenekmu', 'Satu hari tanpa HP, hanya kita']],
                        'musik' => ['track' => $this->track('rindu')],
                        'penutup' => ['closing' => 'Selamat malam, Lintang. Sampai ketemu sebentar lagi 🌙', 'allow_reply' => true],
                    ],
                ],
            ],
        ];
    }
}
