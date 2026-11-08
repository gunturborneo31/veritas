/**
 * Bingkai ponsel untuk menampilkan ucapan di Studio, Pratinjau, Demo, dan beranda.
 * Layar selalu berasio potret HP (9:19); di layar pendek seluruh bingkai mengecil proporsional,
 * jadi tidak pernah berubah menjadi kotak. Lihat .phone-frame di app.css.
 *
 * - `maxWidth`: lebar maksimum bingkai (px).
 * - `offset`: tinggi layar yang dipakai elemen lain di halaman (header, tombol), agar HP muat tanpa terpotong.
 * - `bare`: di layar kecil (HP sungguhan) bingkai dihilangkan dan kartu memakai seluruh lebar.
 */
export default function PhoneFrame({ children, className = '', screenClassName = '', maxWidth, offset, bare = false }) {
    const style = {
        ...(maxWidth ? { '--phone-max': `${maxWidth}px` } : {}),
        ...(offset ? { '--phone-offset': offset } : {}),
    };

    return (
        <div
            style={style}
            className={`phone-frame relative shrink-0 rounded-[2.8rem] bg-[#1f1b18] p-2.5 shadow-2xl shadow-[#2e1a2c]/20 ${
                bare
                    ? 'phone-frame--bare max-sm:rounded-[2rem] max-sm:bg-transparent max-sm:p-0 max-sm:shadow-[0_12px_32px_-12px_rgba(46,26,44,0.35)]'
                    : ''
            } ${className}`}
        >
            <div
                className={`phone-frame__screen relative flex w-full select-none flex-col overflow-hidden rounded-[2.3rem] bg-surface ${bare ? 'max-sm:rounded-[2rem]' : ''} ${screenClassName}`}
            >
                <div className={`absolute left-1/2 top-2 z-50 h-4 w-20 -translate-x-1/2 rounded-full bg-black ${bare ? 'max-sm:hidden' : ''}`} />
                {children}
            </div>
        </div>
    );
}
