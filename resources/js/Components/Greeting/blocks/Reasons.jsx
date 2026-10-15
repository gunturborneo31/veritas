import Icon from '@/Components/Icon';
import { useState } from 'react';
import { SceneLabel } from './shared';

/**
 * Daftar alasan: kartu bernomor yang dibuka satu per satu, lengkap dengan penghitung.
 */
export default function Reasons({ slot, values, base }) {
    const reasons = (values.reasons ?? []).filter(Boolean);
    const [revealed, setRevealed] = useState(1);
    const title = values.title || slot?.title || `Alasan-alasan untuk ${base.recipient_name || 'kamu'}`;
    const isComplete = revealed >= reasons.length;

    return (
        <div className="flex w-full flex-col gap-3">
            <SceneLabel icon="format_list_numbered">{title}</SceneLabel>

            <div className="flex flex-col gap-2">
                {reasons.slice(0, revealed).map((reason, index) => (
                    <div
                        key={index}
                        className="flex animate-layer-in items-start gap-3 rounded-2xl bg-surface-container-lowest/90 p-4 shadow-[0_10px_24px_-16px_rgba(40,21,38,0.45)]"
                    >
                        <span className="font-display text-headline-md leading-none text-primary">{String(index + 1).padStart(2, '0')}</span>
                        <p className="flex-1 text-body-md leading-relaxed text-on-surface">{reason}</p>
                    </div>
                ))}
            </div>

            {isComplete ? (
                <p className="text-center text-label-md text-primary">…dan masih banyak lagi yang tak muat di sini 🤍</p>
            ) : (
                <button
                    type="button"
                    onClick={() => setRevealed((count) => count + 1)}
                    className="flex items-center justify-center gap-2 rounded-full border-2 border-dashed border-primary/40 bg-surface-container-lowest/70 py-3 text-label-lg text-primary transition-transform active:scale-[0.98]"
                >
                    <Icon name="add_circle" size={20} />
                    Alasan berikutnya
                    <span className="rounded-full bg-primary/10 px-2 text-label-sm">
                        {revealed}/{reasons.length}
                    </span>
                </button>
            )}
        </div>
    );
}
