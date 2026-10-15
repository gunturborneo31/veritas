import Icon from '@/Components/Icon';
import { router } from '@inertiajs/react';
import { useRef, useState } from 'react';
import { Confetti } from './shared';

const DODGE_LABELS = ['Yakin? 🥺', 'Coba pikir lagi…', 'Nggak bisa 😜', 'Salah tombol!', 'Hmm? 🤭'];

/**
 * "Satu Pertanyaan": tombol "tidak" kabur setiap mau disentuh, tombol "ya" makin besar.
 * Di halaman penerima, jawaban dikirim ke pengirim (dashboard + email).
 */
export default function AskQuestion({ slot, values, base, mode, slug, answers = {} }) {
    const areaRef = useRef(null);
    const [dodges, setDodges] = useState(0);
    const [noPosition, setNoPosition] = useState(null);
    const [answer, setAnswer] = useState(answers[slot.key] ?? null);

    const yesLabel = values.yes_label || 'Mau! 💘';
    const noLabel = dodges === 0 ? values.no_label || 'Nggak' : DODGE_LABELS[(dodges - 1) % DODGE_LABELS.length];

    const dodge = (event) => {
        event.preventDefault();
        const area = areaRef.current?.getBoundingClientRect();

        if (!area) {
            return;
        }

        setNoPosition({
            left: Math.random() * Math.max(area.width - 120, 0),
            top: Math.random() * Math.max(area.height - 48, 0),
        });
        setDodges((count) => count + 1);
        navigator.vibrate?.(15);
    };

    const sayYes = () => {
        setAnswer(yesLabel);
        navigator.vibrate?.([30, 40, 80]);

        if (mode === 'recipient' && slug) {
            router.post(route('recipient.react', slug), { kind: 'answer', slot_key: slot.key, value: yesLabel }, { preserveScroll: true, preserveState: true });
        }
    };

    if (answer) {
        return (
            <div className="relative flex w-full flex-col items-center gap-3 overflow-hidden rounded-3xl bg-surface-container-lowest/90 px-6 py-8 text-center shadow-[0_18px_40px_-18px_rgba(40,21,38,0.35)]">
                <Confetti />
                <span className="text-6xl">💞</span>
                <p className="font-display text-headline-md text-primary">{answer}</p>
                <p className="text-body-md text-on-surface">{values.yes_message || 'Yeay! Terima kasih sudah menjawab 🥰'}</p>
                {mode === 'recipient' && (
                    <p className="flex items-center gap-1 text-label-sm text-on-surface-variant">
                        <Icon name="mark_email_read" size={14} />
                        Jawabanmu sudah sampai ke {base.sender_name || 'pengirim'}
                    </p>
                )}
            </div>
        );
    }

    return (
        <div className="flex w-full flex-col items-center gap-4 text-center">
            <span className="text-5xl">💘</span>
            <p className="font-display text-headline-lg-mobile leading-tight text-on-surface">{values.question || 'Mau jadi pacarku?'}</p>
            {dodges >= 3 && <p className="text-label-sm text-on-surface-variant">Pilihannya cuma satu kok 😌</p>}

            <div ref={areaRef} className="relative h-48 w-full">
                <button
                    type="button"
                    onClick={sayYes}
                    className="absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2 rounded-full bg-primary px-8 py-4 font-display text-label-lg text-on-primary shadow-xl shadow-primary/30 transition-transform duration-300 active:scale-95"
                    style={{ transform: `translate(-50%, -50%) scale(${1 + Math.min(dodges, 6) * 0.12})` }}
                >
                    {yesLabel}
                </button>
                <button
                    type="button"
                    onPointerEnter={dodge}
                    onPointerDown={dodge}
                    onClick={dodge}
                    className="absolute rounded-full bg-surface-container-lowest px-5 py-2.5 text-label-md text-on-surface-variant shadow-md transition-all duration-200"
                    style={noPosition ? { left: noPosition.left, top: noPosition.top } : { left: '50%', bottom: 0, transform: 'translateX(-50%)' }}
                >
                    {noLabel}
                </button>
            </div>
        </div>
    );
}
