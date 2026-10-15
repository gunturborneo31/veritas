import BottomSheet from '@/Components/BottomSheet';
import Icon from '@/Components/Icon';
import { inspirationsFor, personalize } from '@/lib/inspirations';
import { useState } from 'react';

/**
 * Tombol "Inspirasi kata": kalimat siap pakai sesuai momen & nada, langsung dipersonalisasi nama penerima.
 */
export default function Inspiration({ base, onPick }) {
    const [isOpen, setIsOpen] = useState(false);
    const groups = inspirationsFor(base?.occasion);
    const tones = Object.keys(groups);
    const [tone, setTone] = useState(tones[0]);
    const activeTone = groups[tone] ? tone : tones[0];

    return (
        <>
            <button
                type="button"
                onClick={() => setIsOpen(true)}
                className="inline-flex items-center gap-1 rounded-full bg-tertiary-fixed/50 px-2.5 py-1 text-label-sm text-tertiary transition-colors hover:bg-tertiary-fixed"
            >
                <Icon name="magic_button" size={14} />
                Inspirasi kata
            </button>

            <BottomSheet open={isOpen} onClose={() => setIsOpen(false)} title="Inspirasi kata" icon="magic_button">
                <p className="-mt-2 text-body-sm text-on-surface-variant">Ketuk kalimat untuk memakainya, lalu ubah sesukamu.</p>
                <div className="no-scrollbar flex gap-2 overflow-x-auto pb-1">
                    {tones.map((item) => (
                        <button
                            key={item}
                            type="button"
                            onClick={() => setTone(item)}
                            className={`shrink-0 rounded-full px-3 py-1.5 text-label-sm ${item === activeTone ? 'bg-primary text-on-primary' : 'bg-surface-container text-on-surface-variant'}`}
                        >
                            {item}
                        </button>
                    ))}
                </div>
                <div className="flex max-h-[50vh] flex-col gap-2 overflow-y-auto">
                    {groups[activeTone].map((phrase) => {
                        const text = personalize(phrase, base?.recipient_name);

                        return (
                            <button
                                key={phrase}
                                type="button"
                                onClick={() => {
                                    onPick(text);
                                    setIsOpen(false);
                                }}
                                className="rounded-2xl bg-surface-container-low p-3 text-left text-body-sm text-on-surface transition-colors hover:bg-primary-fixed/50"
                            >
                                {text}
                            </button>
                        );
                    })}
                </div>
            </BottomSheet>
        </>
    );
}
