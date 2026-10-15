import Icon from '@/Components/Icon';

/**
 * Titik integrasi QR Code.
 *
 * TODO: render QR asli. Butuh dependensi pembuat QR (mis. paket npm `qrcode`
 * atau `chillerlan/php-qrcode` di server) yang belum disetujui untuk dipasang.
 * Sampai saat itu komponen ini menampilkan placeholder berisi tautan.
 */
export default function QrCode({ value, size = 176 }) {
    return (
        <div
            className="relative flex flex-col items-center justify-center gap-2 rounded-xl bg-surface-container-high/40 p-3 text-center"
            style={{ width: size, height: size }}
            data-qr-value={value}
        >
            <Icon name="qr_code_2" size={size * 0.45} className="text-primary/70" />
            <span className="text-[10px] leading-tight text-on-surface-variant">QR segera hadir</span>
        </div>
    );
}
