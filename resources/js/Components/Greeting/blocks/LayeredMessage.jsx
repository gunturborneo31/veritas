import Icon from '@/Components/Icon';
import { useState } from 'react';

/**
 * Pesan berlapis: setiap ketukan membuka satu kartu dari tumpukan.
 */
export default function LayeredMessage({ slot, values }) {
    const layers = (values.layers ?? []).filter(Boolean);
    const [revealed, setRevealed] = useState(1);
    const remaining = layers.length - revealed;

    return (
        <div className="flex w-full flex-col items-center gap-3">
            {slot?.title && <p className="self-start text-label-sm uppercase tracking-[0.2em] text-primary">{slot.title}</p>}

            {layers.slice(0, revealed).map((layer, index) => {
                const isLatest = index === revealed - 1;

                return (
                    <div
                        key={index}
                        className={`relative w-full rounded-2xl p-5 shadow-sm transition-all ${
                            isLatest ? 'animate-layer-in bg-surface-container-lowest shadow-[0_14px_30px_-14px_rgba(40,21,38,0.35)]' : 'bg-surface-container-lowest/70'
                        }`}
                    >
                        <span className="absolute -left-2 -top-2 flex h-7 w-7 items-center justify-center rounded-full bg-primary text-[12px] font-bold text-on-primary shadow">
                            {index + 1}
                        </span>
                        <p className={`text-body-md leading-relaxed ${isLatest ? 'text-on-surface' : 'text-on-surface-variant'}`}>{layer}</p>
                    </div>
                );
            })}

            {remaining > 0 && (
                <button type="button" onClick={() => setRevealed((count) => count + 1)} className="group relative mt-2 w-full">
                    {remaining > 1 && <span className="absolute inset-x-6 -bottom-2 h-full rounded-2xl bg-primary/25" />}
                    <span className="absolute inset-x-3 -bottom-1 h-full rounded-2xl bg-primary/50" />
                    <span className="relative flex w-full items-center justify-center gap-2 rounded-2xl bg-primary px-5 py-4 text-label-lg text-on-primary shadow-lg transition-transform group-active:scale-[0.98]">
                        <Icon name="redeem" size={20} />
                        Ketuk untuk membuka lapisan berikutnya
                        <span className="rounded-full bg-white/20 px-2 text-label-sm">{remaining}</span>
                    </span>
                </button>
            )}
        </div>
    );
}
