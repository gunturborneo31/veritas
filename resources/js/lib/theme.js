export const DEFAULT_THEME = {
    accent: '#a83223',
    soft: '#ffdad4',
    gradient: ['#fff7f9', '#ffeff8', '#ffdff7'],
    emoji: '💌',
    motif: 'hearts',
    font: 'modern',
};

/**
 * Gaya huruf judul kartu. Nilai dipasang ke CSS variable `--font-display`.
 */
export const FONTS = {
    modern: { label: 'Modern', family: 'Epilogue, ui-sans-serif, system-ui, sans-serif' },
    klasik: { label: 'Klasik', family: '"Playfair Display", Georgia, serif', stylesheet: 'family=Playfair+Display:ital,wght@0,600;0,700;1,600' },
    tulisan: { label: 'Tulisan Tangan', family: 'Caveat, "Segoe Print", cursive', stylesheet: 'family=Caveat:wght@600;700' },
};

/**
 * Muat huruf judul tambahan hanya saat tema memakainya, supaya halaman lain tetap ringan.
 */
export function loadFont(font) {
    const stylesheet = FONTS[font]?.stylesheet;

    if (!stylesheet || typeof document === 'undefined' || document.querySelector(`link[data-font="${font}"]`)) {
        return;
    }

    const link = document.createElement('link');
    link.rel = 'stylesheet';
    link.href = `https://fonts.googleapis.com/css2?${stylesheet}&display=swap`;
    link.dataset.font = font;
    document.head.appendChild(link);
}

export const MOTIFS = {
    confetti: { label: 'Konfeti', emoji: '🎊' },
    hearts: { label: 'Hati', emoji: '💗' },
    stars: { label: 'Bintang', emoji: '✨' },
    petals: { label: 'Kelopak', emoji: '🌸' },
    rain: { label: 'Gerimis', emoji: '🌧️' },
    film: { label: 'Roll Film', emoji: '🎞️' },
    none: { label: 'Polos', emoji: '⬜' },
};

/**
 * Palet siap pakai untuk langkah Gaya di Studio.
 */
export const PALETTES = [
    { key: 'stroberi', name: 'Stroberi', accent: '#c94a38', soft: '#ffdad4', gradient: ['#fff4e8', '#ffe0d1', '#ffc7b8'] },
    { key: 'mawar', name: 'Mawar', accent: '#9c2f5f', soft: '#ffd9ea', gradient: ['#fff0f6', '#ffd9ea', '#f6b8d3'] },
    { key: 'permen', name: 'Permen Kapas', accent: '#d6336c', soft: '#ffd6e5', gradient: ['#fff4f8', '#ffe1ec', '#e3dcff'] },
    { key: 'lavender', name: 'Lavender', accent: '#6b4fa3', soft: '#e8defa', gradient: ['#f8f4ff', '#ebe1fb', '#ffe3ef'] },
    { key: 'samudra', name: 'Samudra', accent: '#1d5f8c', soft: '#d6e9f6', gradient: ['#f2f8fc', '#dcebf6', '#e8f4ee'] },
    { key: 'tosca', name: 'Tosca', accent: '#127475', soft: '#d5efee', gradient: ['#f3fbfa', '#dcf1ef', '#fde8d7'] },
    { key: 'zaitun', name: 'Hijau Ketupat', accent: '#1f6b52', soft: '#d6f0e3', gradient: ['#f3fbf6', '#dff3e8', '#f7e7b6'] },
    { key: 'senja', name: 'Senja', accent: '#c2410c', soft: '#ffe1cc', gradient: ['#fff7ed', '#ffe4cc', '#fbcfe8'] },
    { key: 'madu', name: 'Madu', accent: '#8a5a12', soft: '#ffe8c2', gradient: ['#fdf7ec', '#f6e6c8', '#ecd2a6'] },
    { key: 'navy', name: 'Navy Emas', accent: '#24386b', soft: '#dde4f6', gradient: ['#f5f7fc', '#e3e9f7', '#f6e7c1'] },
    { key: 'malam', name: 'Langit Malam', accent: '#3b3486', soft: '#e0ddf7', gradient: ['#eeedfb', '#dcd8f5', '#cfd9f2'] },
];

function toRgb(hex) {
    const value = hex.replace('#', '');
    const full = value.length === 3 ? [...value].map((char) => char + char).join('') : value;

    return [0, 2, 4].map((offset) => parseInt(full.slice(offset, offset + 2), 16));
}

function mixRgb(from, to, weight) {
    const [a, b] = [toRgb(from), toRgb(to)];

    return a.map((channel, index) => Math.round(channel + (b[index] - channel) * weight));
}

function mix(from, to, weight) {
    return mixRgb(from, to, weight).join(' ');
}

export function mixHex(from, to, weight) {
    return `#${mixRgb(from, to, weight)
        .map((channel) => channel.toString(16).padStart(2, '0'))
        .join('')}`;
}

function luminance(hex) {
    const [red, green, blue] = toRgb(hex).map((channel) => {
        const value = channel / 255;

        return value <= 0.03928 ? value / 12.92 : ((value + 0.055) / 1.055) ** 2.4;
    });

    return 0.2126 * red + 0.7152 * green + 0.0722 * blue;
}

/**
 * Rasio kontras WCAG antara warna dan putih.
 */
export function contrastWithWhite(hex) {
    return 1.05 / (luminance(hex) + 0.05);
}

/**
 * Versi warna aksen yang aman dipakai di bawah teks putih (dan sebagai warna teks di atas latar terang).
 * Warna pilihan pengirim yang terlalu terang digelapkan bertahap sampai kontrasnya minimal 4.5:1.
 */
export function readableAccent(accent) {
    let color = accent;

    for (let step = 1; contrastWithWhite(color) < 4.5 && step <= 20; step++) {
        color = mixHex(accent, '#000000', step * 0.05);
    }

    return color;
}

/**
 * Turunkan warna lembut & gradien latar dari satu warna utama pilihan pengirim.
 */
export function paletteFromAccent(accent) {
    return {
        accent,
        soft: mixHex(accent, '#ffffff', 0.8),
        gradient: [mixHex(accent, '#ffffff', 0.95), mixHex(accent, '#ffffff', 0.87), mixHex(accent, '#ffe9d6', 0.78)],
    };
}

/**
 * Tema lengkap dengan nilai bawaan. `ink` adalah aksen yang sudah dijamin terbaca untuk tombol dan teks.
 */
export function resolveTheme(theme) {
    const resolved = { ...DEFAULT_THEME, ...(theme ?? {}) };

    return { ...resolved, ink: readableAccent(resolved.accent) };
}

/**
 * CSS variable keluarga warna `primary` dan huruf judul untuk satu tema template.
 * Pasang di elemen pembungkus, maka semua kelas Tailwind `*-primary*` di dalamnya ikut tema tersebut.
 */
export function themeVars(theme) {
    const { ink, soft, font } = resolveTheme(theme);
    loadFont(font);

    return {
        '--color-primary': mix(ink, '#000000', 0),
        '--color-primary-container': mix(ink, '#ffffff', 0.18),
        '--color-primary-fixed': mix(soft, soft, 0),
        '--color-primary-fixed-dim': mix(soft, ink, 0.3),
        '--color-on-primary-fixed': mix(ink, '#000000', 0.55),
        '--font-display': (FONTS[font] ?? FONTS.modern).family,
    };
}
