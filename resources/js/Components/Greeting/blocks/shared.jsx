import Icon from '@/Components/Icon';

/**
 * Bagian-bagian kecil yang dipakai bersama oleh blok komponen ucapan.
 */
export function SceneCard({ children, className = '' }) {
    return (
        <div className={`flex w-full flex-col gap-2.5 rounded-2xl bg-surface-container-lowest/85 p-5 shadow-sm backdrop-blur-md ${className}`}>
            {children}
        </div>
    );
}

export function SceneLabel({ icon = 'auto_awesome', children }) {
    return (
        <div className="flex items-center gap-1.5 text-primary">
            <Icon name={icon} size={16} />
            <span className="text-label-sm font-bold uppercase tracking-widest">{children}</span>
        </div>
    );
}

export function Polaroid({ image, caption, tilt = 1.8 }) {
    return (
        <div className="relative w-full transition-transform duration-500" style={{ transform: `rotate(${tilt}deg)` }}>
            <div
                className="absolute -top-3 left-1/2 z-30 h-6 w-24 -translate-x-1/2 rounded-[2px] bg-tertiary-fixed/70 opacity-90 shadow-sm"
                style={{ transform: 'rotate(-1deg)', clipPath: 'polygon(4% 0%, 96% 0%, 100% 100%, 0% 100%)' }}
            />
            <div className="flex w-full flex-col items-center rounded-xl bg-surface-container-lowest p-3.5 pb-5 shadow-xl">
                <div className="relative aspect-square w-full overflow-hidden rounded-md bg-surface-container shadow-inner">
                    <MediaImage media={image} />
                </div>
                {caption && (
                    <div className="flex w-full items-center justify-between px-1 pt-3.5">
                        <p className="font-display text-headline-sm italic tracking-tight text-on-surface opacity-90">{caption}</p>
                        <Icon name="favorite" filled size={18} className="text-primary" />
                    </div>
                )}
            </div>
        </div>
    );
}

export function MediaImage({ media, className = '' }) {
    if (!media?.url) {
        return (
            <div className={`flex h-full w-full items-center justify-center bg-gradient-to-br from-primary-fixed to-tertiary-fixed ${className}`}>
                <Icon name="image" size={40} className="text-primary/50" />
            </div>
        );
    }

    return <img src={media.url} alt="" loading="lazy" className={`h-full w-full object-cover ${className}`} />;
}

export function Confetti() {
    return (
        <div aria-hidden="true" className="pointer-events-none absolute inset-0 overflow-hidden">
            {[
                ['🎉', 3, 4],
                ['✨', 86, 6],
                ['🎈', 2, 58],
                ['🎊', 88, 48],
                ['💖', 6, 88],
                ['✨', 84, 86],
            ].map(([emoji, left, top], index) => (
                <span
                    key={index}
                    className="absolute animate-bounce text-xl"
                    style={{ left: `${left}%`, top: `${top}%`, animationDelay: `${index * 120}ms` }}
                >
                    {emoji}
                </span>
            ))}
        </div>
    );
}
