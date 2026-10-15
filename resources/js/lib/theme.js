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
    klasik: { label: 'Klasik', family: '"Playfair Display", Georgia, serif' },
    tulisan: { label: 'Tulisan Tangan', family: 'Caveat, "Segoe Print", cursive' },
};

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

function mixHex(from, to, weight) {
    return `#${mixRgb(from, to, weight)
        .map((channel) => channel.toString(16).padStart(2, '0'))
        .join('')}`;
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

export function resolveTheme(theme) {
    return { ...DEFAULT_THEME, ...(theme ?? {}) };
}

/**
 * CSS variable keluarga warna `primary` dan huruf judul untuk satu tema template.
 * Pasang di elemen pembungkus, maka semua kelas Tailwind `*-primary*` di dalamnya ikut tema tersebut.
 */
export function themeVars(theme) {
    const { accent, soft, font } = resolveTheme(theme);

    return {
        '--color-primary': mix(accent, '#000000', 0),
        '--color-primary-container': mix(accent, '#ffffff', 0.18),
        '--color-primary-fixed': mix(soft, soft, 0),
        '--color-primary-fixed-dim': mix(soft, accent, 0.3),
        '--color-on-primary-fixed': mix(accent, '#000000', 0.55),
        '--font-display': (FONTS[font] ?? FONTS.modern).family,
    };
}
