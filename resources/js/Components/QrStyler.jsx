import Icon from '@/Components/Icon';
import { useMemo, useState } from 'react';

const WIDTH = 600;
const HEIGHT = 760;

const escapeXml = (value) => String(value ?? '').replace(/[&<>"']/g, (char) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&apos;' })[char]);

/**
 * Tempel SVG QR dari server ke posisi tertentu di dalam bingkai (sebagai <svg> bersarang).
 */
function placeQr(qrSvg, x, y, size) {
    if (typeof window === 'undefined' || !qrSvg) {
        return '';
    }

    const element = new DOMParser().parseFromString(qrSvg, 'image/svg+xml').documentElement;
    element.setAttribute('x', x);
    element.setAttribute('y', y);
    element.setAttribute('width', size);
    element.setAttribute('height', size);

    return new XMLSerializer().serializeToString(element);
}

/**
 * Model bingkai QR. Setiap model mengembalikan isi <svg> 600×760; warna mengikuti tema kartu.
 */
const STYLES = {
    classic: {
        label: 'Klasik',
        icon: 'qr_code_2',
        render: ({ qr, accent, soft, title, caption }) => `
            <rect width="${WIDTH}" height="${HEIGHT}" rx="48" fill="#ffffff"/>
            <rect x="24" y="24" width="${WIDTH - 48}" height="${HEIGHT - 48}" rx="36" fill="${soft}" opacity="0.35"/>
            <rect x="80" y="80" width="440" height="440" rx="28" fill="#ffffff"/>
            ${placeQr(qr, 100, 100, 400)}
            <text x="300" y="600" text-anchor="middle" font-family="Georgia, serif" font-size="38" font-weight="700" fill="${accent}">${escapeXml(title)}</text>
            <text x="300" y="652" text-anchor="middle" font-family="Arial, sans-serif" font-size="24" fill="#58413d">${escapeXml(caption)}</text>`,
    },
    love: {
        label: 'Love',
        icon: 'favorite',
        render: ({ qr, accent, soft, title, caption }) => `
            <rect width="${WIDTH}" height="${HEIGHT}" rx="48" fill="${soft}"/>
            <g opacity="0.35" fill="${accent}">
                <path d="M70 90c0-14 18-20 26-8 8-12 26-6 26 8 0 16-26 30-26 30s-26-14-26-30z"/>
                <path d="M480 60c0-10 13-15 19-6 6-9 19-4 19 6 0 12-19 22-19 22s-19-10-19-22z"/>
                <path d="M500 640c0-12 15-17 22-7 7-10 22-5 22 7 0 14-22 26-22 26s-22-12-22-26z"/>
            </g>
            <path d="M300 580 C 70 440, 20 280, 100 170 C 175 70, 270 95, 300 175 C 330 95, 425 70, 500 170 C 580 280, 530 440, 300 580 Z" fill="${accent}"/>
            <rect x="175" y="185" width="250" height="250" rx="24" fill="#ffffff"/>
            ${placeQr(qr, 187, 197, 226)}
            <text x="300" y="650" text-anchor="middle" font-family="Georgia, serif" font-size="36" font-weight="700" fill="${accent}">${escapeXml(title)}</text>
            <text x="300" y="698" text-anchor="middle" font-family="Arial, sans-serif" font-size="24" fill="#58413d">${escapeXml(caption)} 💗</text>`,
    },
    gift: {
        label: 'Kado',
        icon: 'redeem',
        render: ({ qr, accent, soft, title, caption }) => `
            <rect width="${WIDTH}" height="${HEIGHT}" rx="48" fill="#fffaf5"/>
            <text x="300" y="92" text-anchor="middle" font-family="Georgia, serif" font-size="36" font-weight="700" fill="${accent}">${escapeXml(title)}</text>
            <ellipse cx="250" cy="200" rx="62" ry="38" fill="none" stroke="${soft}" stroke-width="26" transform="rotate(-20 250 200)"/>
            <ellipse cx="350" cy="200" rx="62" ry="38" fill="none" stroke="${soft}" stroke-width="26" transform="rotate(20 350 200)"/>
            <rect x="80" y="225" width="440" height="80" rx="16" fill="${accent}"/>
            <rect x="105" y="300" width="390" height="370" rx="20" fill="${accent}" opacity="0.92"/>
            <rect x="275" y="225" width="50" height="445" fill="${soft}"/>
            <circle cx="300" cy="222" r="26" fill="${soft}"/>
            <rect x="170" y="345" width="260" height="260" rx="20" fill="#ffffff"/>
            ${placeQr(qr, 182, 357, 236)}
            <text x="300" y="722" text-anchor="middle" font-family="Arial, sans-serif" font-size="24" fill="#58413d">${escapeXml(caption)} 🎁</text>`,
    },
    invitation: {
        label: 'Undangan',
        icon: 'local_florist',
        render: ({ qr, accent, soft, title, caption }) => `
            <rect width="${WIDTH}" height="${HEIGHT}" rx="28" fill="#fffdf8"/>
            <rect x="26" y="26" width="${WIDTH - 52}" height="${HEIGHT - 52}" rx="18" fill="none" stroke="${accent}" stroke-width="3"/>
            <rect x="40" y="40" width="${WIDTH - 80}" height="${HEIGHT - 80}" rx="12" fill="none" stroke="${accent}" stroke-width="1.5" opacity="0.6"/>
            ${[
                [62, 62],
                [538, 62],
                [62, 698],
                [538, 698],
            ]
                .map(([cx, cy]) => `<g fill="${soft}"><circle cx="${cx}" cy="${cy}" r="16"/><circle cx="${cx - 16}" cy="${cy}" r="9"/><circle cx="${cx + 16}" cy="${cy}" r="9"/><circle cx="${cx}" cy="${cy - 16}" r="9"/><circle cx="${cx}" cy="${cy + 16}" r="9"/></g><circle cx="${cx}" cy="${cy}" r="6" fill="${accent}"/>`)
                .join('')}
            <text x="300" y="118" text-anchor="middle" font-family="Georgia, serif" font-size="22" letter-spacing="6" fill="${accent}">UNDANGAN</text>
            <text x="300" y="168" text-anchor="middle" font-family="Georgia, serif" font-size="40" font-style="italic" fill="#281526">${escapeXml(title)}</text>
            <line x1="220" y1="192" x2="380" y2="192" stroke="${accent}" stroke-width="1.5"/>
            <rect x="140" y="220" width="320" height="320" rx="16" fill="#ffffff" stroke="${soft}" stroke-width="4"/>
            ${placeQr(qr, 155, 235, 290)}
            <text x="300" y="600" text-anchor="middle" font-family="Arial, sans-serif" font-size="24" fill="#58413d">Scan untuk membuka undangan</text>
            <text x="300" y="640" text-anchor="middle" font-family="Georgia, serif" font-size="22" font-style="italic" fill="${accent}">${escapeXml(caption)}</text>`,
    },
    polaroid: {
        label: 'Polaroid',
        icon: 'photo_camera',
        render: ({ qr, accent, soft, title, caption }) => `
            <rect width="${WIDTH}" height="${HEIGHT}" fill="${soft}"/>
            <g transform="rotate(-3 300 380)">
                <rect x="70" y="60" width="460" height="620" rx="10" fill="#000000" opacity="0.12" transform="translate(8 10)"/>
                <rect x="70" y="60" width="460" height="620" rx="10" fill="#ffffff"/>
                <rect x="100" y="90" width="400" height="400" fill="#ffffff"/>
                ${placeQr(qr, 100, 90, 400)}
                <text x="300" y="570" text-anchor="middle" font-family="'Comic Sans MS', 'Segoe Print', cursive" font-size="36" fill="${accent}">${escapeXml(title)}</text>
                <text x="300" y="616" text-anchor="middle" font-family="'Comic Sans MS', 'Segoe Print', cursive" font-size="24" fill="#58413d">${escapeXml(caption)}</text>
            </g>
            <rect x="250" y="40" width="100" height="34" fill="${accent}" opacity="0.45" transform="rotate(-6 300 57)"/>`,
    },
};

function buildSvg(styleKey, props) {
    return `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 ${WIDTH} ${HEIGHT}" width="${WIDTH}" height="${HEIGHT}">${STYLES[styleKey].render(props)}</svg>`;
}

/**
 * Ubah SVG bingkai menjadi PNG (2× resolusi) lalu unduh.
 */
async function downloadPng(svg, filename) {
    const image = new Image();
    image.src = `data:image/svg+xml;charset=utf-8,${encodeURIComponent(svg)}`;
    await image.decode();

    const canvas = document.createElement('canvas');
    canvas.width = WIDTH * 2;
    canvas.height = HEIGHT * 2;
    canvas.getContext('2d').drawImage(image, 0, 0, canvas.width, canvas.height);

    const link = document.createElement('a');
    link.href = canvas.toDataURL('image/png');
    link.download = filename;
    link.click();
}

/**
 * QR dengan beberapa model bingkai (Klasik, Love, Kado, Undangan, Polaroid) untuk dibagikan
 * atau dicetak. QR aslinya tetap dari server; bingkai hanya hiasan di sekelilingnya.
 */
export default function QrStyler({ svg, theme, title, caption, filename = 'qr-kartukita', defaultStyle = 'classic', plainDownloadUrl = null }) {
    const [styleKey, setStyleKey] = useState(defaultStyle in STYLES ? defaultStyle : 'classic');
    const [isSaving, setIsSaving] = useState(false);
    const props = { qr: svg, accent: theme?.accent ?? '#a83223', soft: theme?.soft ?? '#ffdad4', title, caption };
    const markup = useMemo(() => buildSvg(styleKey, props), [styleKey, svg, theme?.accent, theme?.soft, title, caption]); // eslint-disable-line react-hooks/exhaustive-deps

    const save = async () => {
        setIsSaving(true);

        try {
            await downloadPng(markup, `${filename}-${styleKey}.png`);
        } finally {
            setIsSaving(false);
        }
    };

    return (
        <div className="flex w-full flex-col items-center gap-3">
            <div className="no-scrollbar -mx-1 flex max-w-full gap-1.5 overflow-x-auto px-1 py-0.5">
                {Object.entries(STYLES).map(([key, style]) => (
                    <button
                        key={key}
                        type="button"
                        onClick={() => setStyleKey(key)}
                        aria-pressed={styleKey === key}
                        className={`flex h-9 shrink-0 items-center gap-1 rounded-full px-3 text-label-md transition-colors ${
                            styleKey === key ? 'bg-primary text-on-primary shadow-sm' : 'bg-surface-container-lowest text-on-surface-variant'
                        }`}
                    >
                        <Icon name={style.icon} size={16} />
                        {style.label}
                    </button>
                ))}
            </div>

            <div className="w-full max-w-[260px] overflow-hidden rounded-2xl shadow-md [&>svg]:h-auto [&>svg]:w-full" role="img" aria-label={`QR model ${STYLES[styleKey].label}`} dangerouslySetInnerHTML={{ __html: markup }} />

            <div className="flex flex-wrap justify-center gap-2">
                <button
                    type="button"
                    onClick={save}
                    disabled={isSaving}
                    className="inline-flex items-center gap-1.5 rounded-full bg-primary px-4 py-2 text-label-md text-on-primary shadow-sm active:scale-95 disabled:opacity-60"
                >
                    <Icon name={isSaving ? 'progress_activity' : 'download'} size={18} className={isSaving ? 'animate-spin' : ''} />
                    Unduh QR {STYLES[styleKey].label}
                </button>
                {plainDownloadUrl && (
                    <a href={plainDownloadUrl} download className="inline-flex items-center gap-1.5 rounded-full bg-surface-container px-4 py-2 text-label-md text-on-surface shadow-sm active:scale-95">
                        <Icon name="print" size={18} className="text-primary" />
                        QR polos (cetak HD)
                    </a>
                )}
            </div>
        </div>
    );
}
