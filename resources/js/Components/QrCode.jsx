import Icon from '@/Components/Icon';

/**
 * QR Code link ucapan. SVG dibuat di server (chillerlan/php-qrcode) dengan warna tema template;
 * tombol unduh memberikan PNG resolusi tinggi untuk dicetak.
 */
export default function QrCode({ svg, downloadUrl = null, size = 176 }) {
    return (
        <div className="flex flex-col items-center gap-3">
            <div
                className="rounded-xl bg-white p-2 [&>svg]:h-full [&>svg]:w-full"
                style={{ width: size, height: size }}
                role="img"
                aria-label="QR Code tautan ucapan"
                dangerouslySetInnerHTML={{ __html: svg ?? '' }}
            />
            {downloadUrl && (
                <a
                    href={downloadUrl}
                    download
                    className="inline-flex items-center gap-1.5 rounded-full bg-surface-container px-4 py-2 text-label-md text-on-surface shadow-sm active:scale-95"
                >
                    <Icon name="download" size={18} className="text-primary" />
                    Unduh QR (PNG HD)
                </a>
            )}
        </div>
    );
}
