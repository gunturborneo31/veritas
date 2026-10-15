import { useEffect, useState } from 'react';
import { SceneLabel } from './shared';

/**
 * Pisahkan "730 hari kita bareng" menjadi angka besar dan keterangannya.
 */
export function splitStat(text) {
    const match = text.trim().match(/^([\d.,]+[+%]?|∞)\s+(.+)$/u);

    return match ? { value: match[1], label: match[2] } : { value: null, label: text };
}

function CountUp({ value }) {
    const target = Number(value.replace(/[.,]/g, '').replace(/[+%]$/, ''));
    const suffix = value.match(/[+%]$/)?.[0] ?? '';
    const [current, setCurrent] = useState(0);

    useEffect(() => {
        if (!Number.isFinite(target)) {
            return;
        }

        const startedAt = performance.now();
        let frame;

        const tick = (now) => {
            const progress = Math.min((now - startedAt) / 1200, 1);
            setCurrent(Math.round(target * (1 - Math.pow(1 - progress, 3))));

            if (progress < 1) {
                frame = requestAnimationFrame(tick);
            }
        };

        frame = requestAnimationFrame(tick);

        return () => cancelAnimationFrame(frame);
    }, [target]);

    if (!Number.isFinite(target)) {
        return value;
    }

    return `${current.toLocaleString('id-ID')}${suffix}`;
}

/**
 * Angka-angka kita: statistik kecil hubungan yang menghitung naik saat dibuka.
 */
export default function Numbers({ slot, values }) {
    const stats = (values.stats ?? []).filter(Boolean).map(splitStat);

    return (
        <div className="flex w-full flex-col gap-3">
            <SceneLabel icon="monitoring">{values.title || slot?.title || 'Kita dalam angka'}</SceneLabel>
            <div className="grid grid-cols-2 gap-2.5">
                {stats.map((stat, index) => (
                    <div
                        key={index}
                        className={`flex animate-layer-in flex-col justify-between gap-1 rounded-2xl p-4 shadow-sm ${
                            index % 3 === 0 ? 'col-span-2 bg-primary text-on-primary' : 'bg-surface-container-lowest/90 text-on-surface'
                        }`}
                        style={{ animationDelay: `${index * 110}ms` }}
                    >
                        {stat.value && (
                            <span className="font-display text-[34px] font-extrabold leading-none tracking-tight">
                                <CountUp value={stat.value} />
                            </span>
                        )}
                        <span className={`text-body-sm leading-snug ${index % 3 === 0 ? 'text-on-primary/85' : 'text-on-surface-variant'}`}>{stat.label}</span>
                    </div>
                ))}
            </div>
            {values.footnote && <p className="text-center text-label-md text-on-surface-variant">{values.footnote}</p>}
        </div>
    );
}
