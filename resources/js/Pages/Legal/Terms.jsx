import LegalDocument from '@/Components/LegalDocument';
import { Link, usePage } from '@inertiajs/react';

/**
 * Syarat & Ketentuan. Mencerminkan aturan bisnis yang benar-benar dijalankan aplikasi (PRD Bagian 5).
 */
export default function Terms({ supportEmail, supportWhatsapp, orderExpiresHours, updatedAt }) {
    const { brand } = usePage().props;

    const sections = [
        {
            id: 'layanan',
            title: 'Tentang layanan',
            body: (
                <p>
                    {brand} adalah layanan untuk membuat ucapan digital interaktif dari template yang tersedia, lalu membagikannya ke penerima melalui tautan dan QR Code.
                    Dengan memakai layanan ini, kamu menyetujui syarat berikut.
                </p>
            ),
        },
        {
            id: 'akun',
            title: 'Akun & draf',
            body: (
                <ul>
                    <li>Kamu bisa membuat dan mencoba ucapan tanpa akun. Akun diperlukan saat akan membayar.</li>
                    <li>Kamu bertanggung jawab menjaga kerahasiaan akun dan kata sandimu.</li>
                    <li>Pratinjau hanya untuk memeriksa ucapan, berwatermark, dan berlaku terbatas — bukan tautan final untuk penerima.</li>
                </ul>
            ),
        },
        {
            id: 'pembayaran',
            title: 'Harga & pembayaran',
            body: (
                <ul>
                    <li>Harga ditampilkan sebelum membayar dan dihitung oleh sistem kami. Kode promo dan referral mengikuti ketentuan masing-masing kode.</li>
                    <li>Pembayaran dilakukan lewat transfer bank atau QRIS lalu diverifikasi oleh tim kami. Ucapan terbit setelah pembayaran dikonfirmasi.</li>
                    <li>Pesanan yang tidak dibayar dalam {orderExpiresHours} jam dibatalkan otomatis; ucapanmu tetap tersimpan sebagai draf.</li>
                    <li>Satu pembayaran berlaku untuk satu ucapan dan satu penerima. Untuk penerima lain, gunakan fitur duplikat dan lakukan pembayaran terpisah.</li>
                </ul>
            ),
        },
        {
            id: 'terkunci',
            title: 'Isi dikunci setelah dibayar',
            body: (
                <>
                    <p>
                        Begitu bukti pembayaran dikirim, <strong>seluruh isi ucapan dikunci dan tidak dapat diubah</strong> — termasuk nama, tanggal, foto, pesan, musik, dan
                        gembok. Ini menjaga keaslian ucapan yang diterima penerima.
                    </p>
                    <p>Karena itu, mohon periksa ejaan nama, tanggal, dan seluruh isi di halaman pratinjau sebelum membayar.</p>
                </>
            ),
        },
        {
            id: 'konten',
            title: 'Konten yang kamu unggah',
            body: (
                <>
                    <p>Kamu tetap pemilik konten yang kamu unggah. Kamu memberi kami izin terbatas untuk menyimpan dan menampilkannya hanya kepada penerima dan pihak yang kamu undang.</p>
                    <p>Kamu dilarang mengunggah konten yang:</p>
                    <ul>
                        <li>melanggar hukum, mengandung pornografi, kekerasan, atau ujaran kebencian (SARA);</li>
                        <li>melecehkan, mengancam, menipu, atau menyebarkan data pribadi orang lain tanpa izin;</li>
                        <li>melanggar hak cipta atau hak orang lain, termasuk foto orang tanpa persetujuannya.</li>
                    </ul>
                    <p>Kami dapat menonaktifkan ucapan yang melanggar ketentuan ini.</p>
                </>
            ),
        },
        {
            id: 'patungan',
            title: 'Patungan, balasan & video reaksi',
            body: (
                <ul>
                    <li>Pengirim bertanggung jawab atas siapa yang diundang menulis lewat link patungan, dan dapat menyembunyikan tulisan sebelum ucapan dibayar.</li>
                    <li>Balasan, reaksi, dan video dari penerima hanya dikirim ke pengirim ucapan. Video reaksi hanya direkam dan dikirim atas persetujuan penerima.</li>
                </ul>
            ),
        },
        {
            id: 'musik',
            title: 'Musik & aset template',
            body: <p>Musik dan aset visual template disediakan untuk dipakai di dalam ucapan saja dan tidak boleh diunduh ulang atau digunakan di luar layanan.</p>,
        },
        {
            id: 'referral',
            title: 'Program referral & promo',
            body: (
                <ul>
                    <li>Kode referral pribadi tidak bisa dipakai untuk pesananmu sendiri dan hanya berlaku untuk pesanan pertama teman yang diajak.</li>
                    <li>Voucher hadiah bersifat sekali pakai, tidak dapat diuangkan, dan memiliki masa berlaku.</li>
                    <li>Kami dapat membatalkan promo atau hadiah yang diperoleh dengan cara curang, misalnya membuat banyak akun.</li>
                </ul>
            ),
        },
        {
            id: 'pengembalian',
            title: 'Kendala & pengembalian dana',
            body: (
                <p>
                    Bila ucapanmu tidak dapat dibuka karena kesalahan sistem kami, hubungi kami dan kami akan membantu memperbaikinya. Permintaan pengembalian dana ditinjau
                    kasus per kasus. Kesalahan isi setelah pembayaran (misalnya salah ketik) tidak termasuk alasan pengembalian dana karena isi sudah kamu periksa saat pratinjau.
                </p>
            ),
        },
        {
            id: 'tanggung-jawab',
            title: 'Batasan tanggung jawab',
            body: (
                <p>
                    Kami berupaya agar layanan selalu tersedia, tetapi tidak menjamin bebas gangguan. Fitur yang bergantung pada perangkat penerima — seperti mikrofon untuk
                    tiup lilin atau kamera untuk video reaksi — dapat tidak berjalan di sebagian perangkat atau peramban.
                </p>
            ),
        },
        {
            id: 'lainnya',
            title: 'Perubahan & hukum yang berlaku',
            body: (
                <p>
                    Syarat ini dapat diperbarui sewaktu-waktu; tanggal pembaruan tercantum di atas. Syarat ini tunduk pada hukum Republik Indonesia. Cara kami mengelola data
                    pribadi dijelaskan di{' '}
                    <Link href={route('legal.privacy')} className="text-primary underline">
                        Kebijakan Privasi
                    </Link>
                    .
                </p>
            ),
        },
    ];

    return (
        <LegalDocument
            title="Syarat & Ketentuan"
            intro={`Syarat ini mengatur penggunaan ${brand}. Kami menulisnya sesederhana mungkin — terutama bagian tentang pembayaran dan isi yang dikunci setelah dibayar.`}
            updatedAt={updatedAt}
            sections={sections}
            supportEmail={supportEmail}
            supportWhatsapp={supportWhatsapp}
        />
    );
}
