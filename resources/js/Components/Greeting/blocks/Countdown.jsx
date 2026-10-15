import { useEffect, useState } from 'react';
import { SceneCard, SceneLabel } from './shared';

export function useRemaining(target) {
    const [now, setNow] = useState(() => Date.now());

    useEffect(() => {
        const timer = setInterval(() => setNow(Date.now()), 1000);

        return () => clearInterval(timer);
    }, []);

    const remaining = Math.max(0, new Date(target).getTime() - now);

    return {
        remaining,
        days: Math.floor(remaining / 86_400_000),
        hours: Math.floor((remaining / 3_600_000) % 24),
        minutes: Math.floor((remaining / 60_000) % 60),
        seconds: Math.floor((remaining / 1000) % 60),
    };
}

export function CountdownDigits({ target }) {
    const { days, hours, minutes, seconds } = useRemaining(target);

    return (
        <div className="grid grid-cols-4 gap-2">
            {[
                [days, 'Hari'],
                [hours, 'Jam'],
                [minutes, 'Menit'],
                [seconds, 'Detik'],
            ].map(([value, label]) => (
                <div key={label} className="flex flex-col items-center rounded-2xl bg-primary-fixed/50 py-3">
                    <span className="font-display text-headline-md text-primary">{String(value).padStart(2, '0')}</span>
                    <span className="text-label-sm text-on-surface-variant">{label}</span>
                </div>
            ))}
        </div>
    );
}

export default function Countdown({ slot, values }) {
    if (!values.target_at) {
        return null;
    }

    return (
        <SceneCard>
            <SceneLabel icon="timer">{values.label || slot?.title || 'Menuju hari spesial'}</SceneLabel>
            <CountdownDigits target={values.target_at} />
        </SceneCard>
    );
}
