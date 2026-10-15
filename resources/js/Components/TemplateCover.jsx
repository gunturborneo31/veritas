import Icon from '@/Components/Icon';

const FALLBACK_THEME = { accent: '#a83223', soft: '#ffdad4', gradient: ['#fff7f9', '#ffeff8', '#ffdff7'], emoji: '💌', motif: 'hearts' };

/**
 * Ornamen latar sesuai motif tema template.
 */
function Motif({ motif, accent }) {
    const items = {
        confetti: [
            [12, 14, 'rect', 10, 25],
            [80, 10, 'circle', 7],
            [20, 72, 'circle', 5],
            [86, 64, 'rect', 9, -30],
            [55, 8, 'circle', 4],
            [70, 84, 'rect', 7, 45],
            [8, 44, 'rect', 6, 60],
        ],
        hearts: [
            [14, 16, 'heart', 14],
            [82, 12, 'heart', 10],
            [86, 70, 'heart', 16],
            [10, 74, 'heart', 9],
            [52, 8, 'circle', 4],
        ],
        stars: [
            [14, 14, 'star', 12],
            [80, 18, 'star', 8],
            [88, 58, 'star', 6],
            [12, 66, 'star', 7],
            [50, 6, 'circle', 3],
            [64, 82, 'star', 5],
        ],
        rain: [
            [12, 8, 'drop', 10],
            [30, 30, 'drop', 8],
            [78, 12, 'drop', 11],
            [88, 44, 'drop', 7],
            [16, 62, 'drop', 9],
            [66, 78, 'drop', 8],
            [50, 6, 'drop', 6],
        ],
        film: [
            [8, 0, 'film'],
            [92, 0, 'film'],
        ],
    }[motif] ?? [];

    return (
        <svg aria-hidden="true" className="pointer-events-none absolute inset-0 h-full w-full" viewBox="0 0 100 100" preserveAspectRatio="none">
            {items.map(([x, y, shape, size = 6, rotate = 0], index) => {
                const props = { fill: accent, opacity: 0.22 + (index % 3) * 0.08 };

                if (shape === 'circle') {
                    return <circle key={index} {...props} cx={x} cy={y} r={size / 2} />;
                }

                if (shape === 'rect') {
                    return <rect key={index} {...props} x={x} y={y} width={size / 2} height={size} rx="1" transform={`rotate(${rotate} ${x} ${y})`} />;
                }

                if (shape === 'heart') {
                    const scale = size / 20;

                    return (
                        <path
                            key={index}
                            {...props}
                            transform={`translate(${x} ${y}) scale(${scale})`}
                            d="M0 4C-2-2-10-1-10 5c0 5 7 9 10 12 3-3 10-7 10-12 0-6-8-7-10-1z"
                        />
                    );
                }

                if (shape === 'drop') {
                    return <rect key={index} {...props} x={x} y={y} width="1.2" height={size} rx="0.6" transform={`rotate(15 ${x} ${y})`} />;
                }

                if (shape === 'star') {
                    const scale = size / 20;

                    return <path key={index} {...props} transform={`translate(${x} ${y}) scale(${scale})`} d="M0-10 2.5-2.5 10 0 2.5 2.5 0 10-2.5 2.5-10 0-2.5-2.5z" />;
                }

                return (
                    <g key={index} opacity="0.25" fill={accent}>
                        {Array.from({ length: 10 }, (_, hole) => (
                            <rect key={hole} x={x - 2} y={hole * 10 + 2} width="4" height="5" rx="1" />
                        ))}
                    </g>
                );
            })}
        </svg>
    );
}

/**
 * Sampul template: gambar jika ada, selain itu ilustrasi bertema (gradien, motif, emoji, ikon interaksi).
 */
export default function TemplateCover({ template, size = 'md', className = '' }) {
    const theme = { ...FALLBACK_THEME, ...(template.theme ?? {}) };

    if (template.cover_url) {
        return <img src={template.cover_url} alt={template.name} className={`h-full w-full object-cover ${className}`} />;
    }

    const emojiSize = { sm: 'text-3xl', md: 'text-6xl', lg: 'text-8xl' }[size];

    return (
        <div
            className={`relative flex h-full w-full items-center justify-center overflow-hidden ${className}`}
            style={{ background: `linear-gradient(160deg, ${theme.gradient.join(', ')})` }}
        >
            <Motif motif={theme.motif} accent={theme.accent} />
            <div className="absolute -bottom-10 left-1/2 h-2/3 w-2/3 -translate-x-1/2 rounded-full blur-2xl" style={{ backgroundColor: theme.accent, opacity: 0.18 }} />
            <div className="relative flex flex-col items-center gap-2">
                <span className={`${emojiSize} drop-shadow-[0_8px_16px_rgba(40,21,38,0.18)] transition-transform duration-500 group-hover:-rotate-6 group-hover:scale-110`}>
                    {theme.emoji}
                </span>
                {size !== 'sm' && template.highlight_icon && (
                    <span className="flex h-9 w-9 items-center justify-center rounded-full bg-white/70 shadow-sm backdrop-blur" style={{ color: theme.accent }}>
                        <Icon name={template.highlight_icon} size={20} />
                    </span>
                )}
            </div>
        </div>
    );
}
