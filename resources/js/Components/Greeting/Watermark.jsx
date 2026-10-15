/**
 * Watermark diagonal untuk pratinjau & demo (desain pratinjauwatermark).
 * Cukup jelas untuk mencegah dipakai sebagai versi final, tapi isi tetap terbaca.
 */
export default function Watermark({ recipientName }) {
    const line = `PRATINJAU KARTUKITA • UNTUK ${(recipientName || 'PENERIMA').toUpperCase()} • BUKAN VERSI FINAL`;

    return (
        <div
            aria-hidden="true"
            className="pointer-events-none absolute inset-0 z-40 flex select-none flex-col justify-around overflow-hidden opacity-[0.08]"
            style={{ transform: 'rotate(-24deg) scale(1.6)' }}
        >
            {Array.from({ length: 6 }, (_, index) => (
                <div key={index} className="whitespace-nowrap text-[12px] font-extrabold uppercase tracking-[0.3em] text-on-surface">
                    {line} • {line}
                </div>
            ))}
        </div>
    );
}
