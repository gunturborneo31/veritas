export const DEFAULT_THEME = {
    accent: '#a83223',
    soft: '#ffdad4',
    gradient: ['#fff7f9', '#ffeff8', '#ffdff7'],
    emoji: '💌',
    motif: 'hearts',
};

function toRgb(hex) {
    const value = hex.replace('#', '');
    const full = value.length === 3 ? [...value].map((char) => char + char).join('') : value;

    return [0, 2, 4].map((offset) => parseInt(full.slice(offset, offset + 2), 16));
}

function mix(from, to, weight) {
    const [a, b] = [toRgb(from), toRgb(to)];

    return a.map((channel, index) => Math.round(channel + (b[index] - channel) * weight)).join(' ');
}

export function resolveTheme(theme) {
    return { ...DEFAULT_THEME, ...(theme ?? {}) };
}

/**
 * CSS variable keluarga warna `primary` untuk satu tema template. Pasang di elemen
 * pembungkus, maka semua kelas Tailwind `*-primary*` di dalamnya ikut tema tersebut.
 */
export function themeVars(theme) {
    const { accent, soft } = resolveTheme(theme);

    return {
        '--color-primary': mix(accent, '#000000', 0),
        '--color-primary-container': mix(accent, '#ffffff', 0.18),
        '--color-primary-fixed': mix(soft, soft, 0),
        '--color-primary-fixed-dim': mix(soft, accent, 0.3),
        '--color-on-primary-fixed': mix(accent, '#000000', 0.55),
    };
}
