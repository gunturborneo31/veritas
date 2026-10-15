# PRD — Platform Ucapan Digital Interaktif

Versi dokumen: 1.0
Status: Draf untuk rilis pertama (MVP)

---

## 1. Ringkasan Produk

[NamaBrand] adalah platform web untuk membuat **ucapan digital interaktif** (ulang tahun, anniversary, Hari Ibu, Lebaran, dan momen lain), berbentuk satu halaman yang dibuka penerima lewat link. Ucapan disusun dari **template siap pakai** berisi komponen interaktif (gembok, puzzle, galeri foto, musik, surat, dsb.), diisi sendiri oleh pembeli lewat editor (disebut **Studio**), lalu dibagikan lewat link dan QR Code setelah dibayar.

Ini adalah produk pertama dari rencana platform yang lebih besar (undangan digital, buku tamu digital, fitur AI menyusul di fase berikutnya), dibangun di atas fondasi teknis yang sama.

### Masalah yang diselesaikan
Mengirim ucapan teks atau kartu generik terasa datar. Pengguna ingin memberi kesan personal dan berkesan tanpa perlu kemampuan desain atau coding.

### Posisi produk
Pembanding langsung: Lovio.id (kado digital interaktif). Pembeda yang dituju: ragam kategori momen lebih luas (bukan hanya pasangan), mode patungan (banyak pengirim, fase lanjut), dan pilihan gaya visual per komponen (Paket 2).

---

## 2. Tujuan Rilis Pertama

1. Membuktikan orang mau membuat dan membagikan ucapan lewat platform ini.
2. Mengukur tingkat konversi dari pratinjau ke pembayaran.
3. Memvalidasi 3–4 template dan ragam komponen sebelum memperluas katalog.

### Metrik keberhasilan (indikatif, sesuaikan target)
- Jumlah draf dibuat per minggu
- Rasio draf → pratinjau → bayar
- Rasio penerima yang benar-benar membuka ucapan
- Waktu rata-rata menyelesaikan satu ucapan di Studio

---

## 3. Target Pengguna

- **Pembeli**: individu di Indonesia, mayoritas mengakses lewat HP, ingin memberi ucapan personal untuk momen tertentu (ulang tahun, anniversary, hari besar, dsb.), bersedia membayar harga kecil (di bawah Rp100.000 untuk kelas dasar).
- **Penerima**: tidak pernah perlu akun. Membuka ucapan lewat link yang dikirim lewat WhatsApp atau media lain.

---

## 4. Lingkup Rilis Pertama (MVP)

### Termasuk
- Katalog publik dengan 3–4 template, masing-masing dari kategori momen berbeda
- Studio: isi ucapan tanpa login (draf anonim), pratinjau berwatermark
- **Paket 1 saja** (template tetap, tanpa ganti gaya visual)
- Login/daftar hanya saat akan menyimpan permanen / membayar (Google atau WhatsApp OTP)
- Checkout dan pembayaran **manual** (transfer/QRIS statis, dikonfirmasi admin) — gateway otomatis menyusul
- Setelah bayar: ucapan **terkunci total**, tidak bisa diedit
- Hasil: link unik + QR Code, bisa dibagikan lewat WhatsApp
- Layar penerima: gerbang (gembok/PIN), pemutaran ucapan, tombol balas
- Dashboard akun sederhana: daftar ucapan, status, link/QR

### Tidak termasuk di rilis pertama (lihat Bagian 11 — Fase Lanjut)
- Paket 2 (ganti gaya visual per komponen)
- Payment gateway otomatis (QRIS dinamis via provider)
- Karakter buatan AI
- Mode patungan (banyak pengirim)
- Blog, SEO, program reseller
- Aplikasi mobile native

---

## 5. Aturan Bisnis Inti

Aturan ini adalah keputusan tetap, bukan sekadar fitur — harus tercermin di logika aplikasi, bukan hanya UI.

| Aturan | Ketentuan |
|---|---|
| **Tanpa versi gratis** | Tidak ada paket Rp0. Harga terendah adalah Paket 1 |
| **Pratinjau sebagai pengganti versi gratis** | Bisa dibuat dan dilihat tanpa bayar, dengan watermark, token berbatas waktu, tidak bisa dibagikan sebagai link final |
| **Tanpa edit setelah bayar** | Begitu status pembayaran lunas, seluruh isi (nama, foto, pesan, musik, gembok) dibekukan menjadi *snapshot* permanen. Tidak ada endpoint yang mengizinkan perubahan setelah status `terbit` |
| **Login ditunda semaksimal mungkin** | Draf anonim disimpan via token di cookie/local storage + server. Akun baru diwajibkan saat checkout |
| **Harga dihitung & dikunci di server** | Harga akhir dihitung dari snapshot di backend saat transaksi dibuat, bukan dari input browser |
| **Satu pembayaran = satu halaman ucapan untuk satu penerima** | Mencegah satu pembayaran dipakai berulang untuk penerima berbeda. Kebutuhan kirim ke banyak orang diarahkan ke fitur bundel/duplikat (fase lanjut), bukan dengan mengedit ulang ucapan yang sama |
| **Unduhan media berlisensi saja** | Musik latar hanya dari pustaka internal yang berlisensi/bebas royalti, tidak ada unggah lagu bebas oleh pengguna di rilis pertama |

---

## 6. Alur Pengguna Utama

```
Katalog → Pilih Template → Studio (isi konten) → Pratinjau (watermark)
   → Checkout (ringkasan + konfirmasi wajib + metode bayar)
   → Pembayaran (manual, dikonfirmasi admin)
   → Terbit (snapshot dikunci) → Hasil (link + QR)
   → Dibagikan ke penerima → Penerima buka gerbang → Pemutaran ucapan
```

### Detail langkah Studio
1. **Dasar** — nama penerima, momen, pesan utama
2. **Isi per komponen** — form dibuat otomatis dari skema tiap komponen template (foto, musik, pesan berlapis, dll.)
3. **Gaya** — ditampilkan terkunci dengan label "Tersedia di Paket 2" (UI sudah disiapkan meski Paket 2 belum aktif)
4. **Pratinjau** — tampilan penuh berwatermark, bisa dicoba interaksinya
5. **Ringkasan & Bayar** — centang wajib "saya sudah memeriksa nama dan tanggal", baru lanjut ke metode bayar

---

## 7. Pemetaan Layar (Desain Stitch → Fungsi)

Desain sudah dibuat di Stitch untuk 9 layar berikut. PRD ini memetakan tiap layar ke fungsi dan datanya.

| # | Layar | Fungsi utama | Butuh login? |
|---|---|---|---|
| 1 | Beranda | Pintu masuk, menuju katalog | Tidak |
| 2 | Katalog Template | Filter kategori, lihat daftar template | Tidak |
| 3 | Detail Template | Info template, tombol "Coba demo" / "Buat sekarang" | Tidak |
| 4 | Studio (Editor) | Isi semua konten ucapan, autosave ke draf anonim | Tidak |
| 5 | Pilih Gaya (Paket 2) | UI disiapkan, terkunci di rilis pertama | Tidak |
| 6 | Pratinjau + Watermark | Lihat hasil penuh sebelum bayar | Tidak |
| 7 | Ringkasan & Bayar | Checkout, pilih metode bayar | **Ya**, dibuat saat masuk tahap ini |
| 8 | Hasil: Link & QR | Ambil link, unduh QR, tombol bagikan WhatsApp | Ya |
| 9a | Penerima: Gembok | PIN/pertanyaan rahasia sebelum ucapan terbuka | Tidak, selamanya |
| 9b | Penerima: Pemutaran | Menampilkan ucapan sesuai snapshot | Tidak, selamanya |
| 10 | Dashboard Akun | Daftar ucapan milik pengguna, status, link/QR | Ya |

---

## 8. Sistem Template & Komponen

### Prinsip
- **Template = rakitan komponen.** Template tidak ditulis sebagai halaman statis, melainkan daftar komponen berurutan + data contoh + aset.
- **Komponen = satu sumber kebenaran**, dipakai ulang di banyak template. Tiap komponen punya skema isi (field apa yang diisi pembeli) dan (nanti) daftar varian gaya untuk Paket 2.
- **Isi ucapan disimpan sebagai data terstruktur (JSON)**, divalidasi oleh skema, dirender oleh satu komponen "pemutar" yang sama baik di Studio, Pratinjau, maupun halaman penerima.

### Komponen untuk rilis pertama (contoh cakupan minimum)
- Sampul
- Gembok PIN (4–6 digit) — hash disimpan di server, tidak dikirim ke client
- Kunci waktu (baru terbuka di tanggal/jam tertentu)
- Foto tunggal berbingkai
- Galeri geser (3–12 foto)
- Kata pembuka / surat
- Pesan berlapis
- Musik latar (dari pustaka internal)
- Pesan suara (rekaman)
- Minimal 2–3 mini-game/puzzle sederhana (mis. puzzle geser foto, kuis "seberapa kenal aku", gosok kartu)
- Kupon janji / sertifikat unduhan
- Hitung mundur
- Penutup + tombol balas

### Template rilis pertama (contoh susunan, 3–4 template)
1. **Tiup Lilin** — sampul, gembok PIN, tiup lilin (mikrofon), foto, surat, sertifikat unduhan
2. **Kado Berlapis** — sampul, pesan berlapis, galeri, kupon janji
3. **Mesin Waktu (Hari Ibu/keluarga)** — sampul, linimasa foto, pesan suara, surat
4. (opsional) **Lebaran** — sampul, pesan berlapis, gosok kartu, penutup

---

## 9. Model Data (Ringkas)

| Entitas | Keterangan |
|---|---|
| `users` | Akun pembeli. Metode masuk: Google / WhatsApp OTP / email |
| `categories` | Kategori momen (Ulang Tahun, Keluarga, dll.) |
| `templates` | Metadata template: nama, kategori, kelas (Klasik/Interaktif/Premium), daftar komponen & urutan default |
| `components` | Definisi komponen: skema isi, kebutuhan izin sensor (mikrofon dll.) |
| `variants` | Varian gaya per komponen (disiapkan skemanya, aktif di Paket 2) |
| `greetings` | Satu ucapan: pemilik/token draf, template, slug, status, konten JSON, jenis (untuk reuse ke produk lain nanti) |
| `greeting_snapshots` | Versi terkunci setelah bayar: konten final, tidak berubah lagi |
| `media` | File (foto, audio), status proses kompresi, kepemilikan |
| `orders` | Pesanan: ucapan terkait, harga, status (menunggu/lunas/batal) |
| `payments` | Catatan pembayaran manual: bukti transfer, dikonfirmasi admin |
| `gates` | Pengaturan gembok: jenis, hash jawaban, batas percobaan |
| `open_events` | Catatan kapan ucapan dibuka penerima (abaikan bot & sesi pemilik) |
| `replies` | Balasan dari penerima |

Status `greetings`: `draf` → `pratinjau` → `menunggu_bayar` → `terkunci` → `terbit` → (opsional) `arsip`

---

## 10. Kebutuhan Fungsional per Modul

### Katalog (publik)
- Tampilkan daftar template, filter kategori
- Halaman detail template dengan demo (data contoh)

### Studio
- Buat draf tanpa login, simpan via token
- Form dibangkitkan otomatis dari skema komponen template
- Autosave berkala
- Upload foto/audio dengan kompresi otomatis & batas ukuran
- Validasi input sesuai skema (Zod atau setara di sisi Laravel/Inertia)

### Pratinjau
- Render dengan watermark diagonal berulang
- Token kedaluwarsa (mis. 24 jam)
- Tidak tercatat sebagai "dibuka penerima"
- Tidak bisa diakses tanpa token valid

### Checkout & Pembayaran
- Hitung harga di server dari snapshot draf
- Checkbox wajib konfirmasi data sebelum lanjut bayar
- Metode manual: tampilkan rekening/QRIS statis + form unggah bukti transfer
- Status pesanan: menunggu konfirmasi → admin verifikasi manual → lunas
- Saat lunas: buat `greeting_snapshots`, ubah status jadi `terbit`, generate slug unik + QR Code

### Halaman Penerima
- Baca dari snapshot (bukan draf aktif)
- Gerbang (gembok) jika diaktifkan, verifikasi di server, hash tidak dikirim ke client
- Catat event "dibuka" hanya setelah aksi eksplisit (tombol "Buka"), abaikan bot preview WhatsApp
- Balasan penerima (teks sederhana) tersimpan ke `replies`

### Dashboard Akun
- Daftar ucapan + status
- Salin link, unduh QR
- Lihat balasan penerima (jika ada)

### Admin (minimum untuk rilis pertama)
- Kelola template, kategori, komponen (CRUD dasar)
- Verifikasi pembayaran manual
- Lihat daftar pesanan & status

---

## 11. Fase Lanjut (di luar lingkup rilis pertama)

| Fase | Fitur |
|---|---|
| Fase 2 | Paket 2 (varian gaya per komponen aktif), payment gateway otomatis (QRIS dinamis, VA, e-wallet) |
| Fase 3 | Perluasan katalog template & komponen (termasuk mini-game lanjutan) |
| Fase 4 | Mode patungan (banyak pengirim, dua fase: kumpulkan → kunci) |
| Fase 5 | Karakter buatan AI dari foto |
| Fase 6 | Produk berikutnya: undangan digital, buku tamu digital, di atas fondasi yang sama |

---

## 12. Kebutuhan Non-Fungsional

- **Performa**: halaman penerima harus tetap cepat dibuka di HP kelas menengah dan jaringan lambat. Gambar dikompresi otomatis ke WebP, audio dikompresi, musik baru dimuat setelah tombol "Buka" ditekan.
- **Keamanan**: hash untuk jawaban gembok (bukan plaintext), rate limit pada percobaan gembok & endpoint upload, validasi tipe/ukuran file di server, URL ucapan berupa slug acak.
- **Privasi**: kebijakan privasi mencakup foto dan data pribadi penerima (termasuk kemungkinan foto anak). Draf anonim dibersihkan otomatis setelah periode tidak aktif (mis. 30 hari).
- **Skalabilitas desain**: komponen & skema ditulis agar menambah template baru tidak memerlukan kode aplikasi baru, cukup manifest + aset.

---

## 13. Pertanyaan Terbuka (perlu diputuskan sebelum/selama build)

1. Gembok: efek visual saja, atau perlindungan isi yang sesungguhnya (muatan baru dikirim server setelah jawaban benar)?
2. Masa aktif ucapan: selamanya, atau ada batas waktu/arsip?
3. Berapa lama draf anonim disimpan sebelum dihapus?
4. Jendela koreksi pascabayar: benar-benar nol, atau ada satu pengecualian manual lewat admin untuk kasus salah ketik fatal?
5. Harga final tiap template/kelas (belum ditetapkan dalam dokumen ini)

---

## 14. Teknologi (Rencana)

- **Backend**: Laravel 13 (PHP 8.3+)
- **Frontend**: Inertia.js + React + Tailwind CSS
- **Database**: MySQL atau PostgreSQL
- **Penyimpanan file**: lokal untuk pengembangan awal, siap pindah ke S3-compatible storage (mis. Cloudflare R2) saat produksi
- **Antrean**: Laravel Queue untuk proses kompresi media
- **Autentikasi**: Laravel Breeze (Inertia + React) sebagai basis, tambah metode Google/WhatsApp OTP menyusul

---

## 15. Definisi Selesai (Rilis Pertama)

Rilis pertama dianggap selesai bila:
- Pengguna anonim bisa memilih template, mengisi ucapan di Studio, dan melihat pratinjau berwatermark
- Pengguna bisa membuat akun, checkout, mengunggah bukti bayar
- Admin bisa memverifikasi pembayaran secara manual
- Setelah diverifikasi, ucapan terkunci otomatis dan link + QR tersedia
- Penerima bisa membuka link, melewati gembok (jika ada), dan melihat ucapan sesuai snapshot — tanpa login
- Tidak ada jalur API/UI yang memungkinkan isi ucapan diubah setelah status `terbit`