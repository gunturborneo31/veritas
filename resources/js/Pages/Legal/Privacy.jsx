import LegalDocument from '@/Components/LegalDocument';
import { usePage } from '@inertiajs/react';

/**
 * Kebijakan Privasi. Disusun mengikuti data yang benar-benar dikumpulkan aplikasi dan
 * prinsip UU No. 27 Tahun 2022 tentang Pelindungan Data Pribadi (UU PDP).
 */
export default function Privacy({ supportEmail, supportWhatsapp, draftRetentionDays, updatedAt }) {
    const { brand } = usePage().props;

    const sections = [
        {
            id: 'data',
            title: 'Data yang kami kumpulkan',
            body: (
                <>
                    <p>Kami hanya mengumpulkan data yang diperlukan agar ucapanmu bisa dibuat, dibayar, dan dibuka penerima:</p>
                    <ul>
                        <li>
                            <strong>Data akun</strong>: nama, email, dan (bila diisi) nomor telepon.
                        </li>
                        <li>
                            <strong>Isi ucapan</strong>: nama penerima & pengirim, momen, pesan, foto, rekaman suara, pilihan lagu, pertanyaan/PIN gembok (PIN disimpan dalam
                            bentuk hash, bukan teks asli), dan jadwal buka.
                        </li>
                        <li>
                            <strong>Data pembayaran</strong>: kode pesanan, nominal, kode promo, nomor transaksi QRIS, serta nama pembayar dan aplikasi pembayaran yang
                            dilaporkan penyedia QRIS. Kami tidak pernah menerima atau menyimpan nomor kartu, saldo, maupun PIN aplikasi pembayaranmu.
                        </li>
                        <li>
                            <strong>Data dari penerima</strong>: waktu ucapan dibuka, jenis perangkat/peramban, serta sidik acak (hash) dari alamat IP — dipakai untuk menghitung
                            &ldquo;sudah dibuka&rdquo; dan mencegah penyalahgunaan. Juga reaksi emoji, jawaban pertanyaan, dan balasan yang dikirim penerima.
                        </li>
                        <li>
                            <strong>Video reaksi</strong>: hanya bila penerima menyetujui perekaman, menonton ulang, lalu memilih &ldquo;Kirim&rdquo;.
                        </li>
                        <li>
                            <strong>Tulisan patungan</strong>: nama, pesan, dan foto dari teman yang diundang pengirim.
                        </li>
                        <li>
                            <strong>Pengingat momen</strong>: nama orang dan tanggal (tanggal & bulan) yang kamu simpan.
                        </li>
                    </ul>
                </>
            ),
        },
        {
            id: 'tujuan',
            title: 'Untuk apa data dipakai',
            body: (
                <ul>
                    <li>Menyusun, menampilkan, dan mengirim ucapan sesuai pesananmu.</li>
                    <li>Memverifikasi pembayaran dan menerapkan kode promo/referral.</li>
                    <li>Mengirim notifikasi yang kamu harapkan: ucapan dibuka, reaksi/balasan, pengingat momen, dan status pesanan.</li>
                    <li>Menjaga keamanan layanan (membatasi percobaan PIN, mencegah spam dan penipuan).</li>
                </ul>
            ),
        },
        {
            id: 'foto-anak',
            title: 'Foto, anak, dan data penerima',
            body: (
                <>
                    <p>
                        Ucapan sering berisi foto dan nama orang lain, termasuk anak-anak. Dengan mengunggahnya, kamu menyatakan berhak membagikannya dan sudah mendapat izin
                        dari orang yang ada di foto (atau orang tua/walinya untuk anak).
                    </p>
                    <p>Mohon tidak mengunggah data sensitif seperti nomor identitas, data kesehatan, atau informasi keuangan di dalam ucapan.</p>
                </>
            ),
        },
        {
            id: 'video',
            title: 'Video reaksi penerima',
            body: (
                <ul>
                    <li>Kamera hanya menyala setelah penerima menekan &ldquo;Ya, rekam&rdquo;, dan tanda REC terlihat selama merekam.</li>
                    <li>Video baru diunggah setelah penerima menontonnya ulang dan mencentang persetujuan. Tanpa itu, video dibuang di perangkat penerima.</li>
                    <li>Video hanya bisa dilihat oleh pengirim ucapan, disimpan secara privat, dan bisa dihapus pengirim kapan saja.</li>
                    <li>Penerima dapat meminta videonya dihapus dengan menghubungi kami.</li>
                </ul>
            ),
        },
        {
            id: 'keamanan',
            title: 'Penyimpanan & keamanan',
            body: (
                <ul>
                    <li>Foto, rekaman, video, dan bukti transfer disimpan di penyimpanan privat dan hanya ditampilkan lewat tautan bertanda tangan yang kedaluwarsa.</li>
                    <li>Alamat ucapan memakai kode acak sehingga tidak bisa ditebak; gembok PIN menjaga isi tetap tertutup sampai dijawab benar.</li>
                    <li>Draf yang dibuat tanpa akun dan tidak disentuh selama {draftRetentionDays} hari dihapus otomatis beserta fotonya.</li>
                </ul>
            ),
        },
        {
            id: 'berbagi',
            title: 'Dengan siapa data dibagikan',
            body: (
                <>
                    <p>Kami tidak menjual data pribadimu. Data hanya dibagikan kepada:</p>
                    <ul>
                        <li>Penerima ucapan (isi ucapan yang kamu buat untuknya) dan pengirim (reaksi, jawaban, balasan, dan video dari penerima).</li>
                        <li>Penyedia layanan yang membantu kami beroperasi, seperti penyimpanan berkas, server, dan pengiriman email, dengan kewajiban menjaga kerahasiaan.</li>
                        <li>
                            Penyedia pembayaran QRIS (InterActive QRIS) dan bank/e-wallet yang kamu pakai, sebatas kode transaksi dan nominal yang diperlukan untuk memproses
                            pembayaran.
                        </li>
                        <li>Pihak berwenang bila diwajibkan oleh hukum yang berlaku.</li>
                    </ul>
                </>
            ),
        },
        {
            id: 'retensi',
            title: 'Berapa lama data disimpan',
            body: (
                <ul>
                    <li>Ucapan yang sudah terbit tetap aktif agar tautannya bisa dibuka kapan saja, sampai kamu meminta penghapusan.</li>
                    <li>Data pesanan dan bukti pembayaran disimpan selama diperlukan untuk pembukuan dan kewajiban hukum.</li>
                    <li>Data akun dihapus saat kamu menghapus akun, kecuali data yang wajib kami simpan menurut hukum.</li>
                </ul>
            ),
        },
        {
            id: 'hak',
            title: 'Hak kamu',
            body: (
                <>
                    <p>Sesuai UU Pelindungan Data Pribadi, kamu berhak untuk:</p>
                    <ul>
                        <li>Mengakses dan meminta salinan data pribadimu.</li>
                        <li>Memperbaiki data yang keliru (isi ucapan dapat diubah selama belum dibayar).</li>
                        <li>Menghapus akun melalui halaman Profil (akun dengan riwayat pesanan dihapus lewat permintaan ke kami), atau meminta penghapusan ucapan/video tertentu.</li>
                        <li>Menarik persetujuan, misalnya untuk video reaksi atau email pengingat.</li>
                    </ul>
                </>
            ),
        },
        {
            id: 'cookie',
            title: 'Cookie',
            body: (
                <p>
                    Kami memakai cookie yang diperlukan agar layanan berjalan: menjaga sesi login, mengingat draf yang kamu buat tanpa akun, dan menandai ucapan yang sudah
                    kamu buka. Kami tidak memakai cookie iklan.
                </p>
            ),
        },
        {
            id: 'perubahan',
            title: 'Perubahan kebijakan',
            body: <p>Bila kebijakan ini berubah, kami memperbarui tanggal di atas dan memberi tahu lewat email untuk perubahan yang penting.</p>,
        },
    ];

    return (
        <LegalDocument
            title="Kebijakan Privasi"
            intro={`${brand} membantu kamu mengirim ucapan yang personal. Karena ucapan sering berisi foto dan cerita pribadi, kami ingin jelas tentang data apa yang kami simpan, untuk apa, dan bagaimana kamu mengendalikannya.`}
            updatedAt={updatedAt}
            sections={sections}
            supportEmail={supportEmail}
            supportWhatsapp={supportWhatsapp}
        />
    );
}
