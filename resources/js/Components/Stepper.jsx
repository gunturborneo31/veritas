import Icon from '@/Components/Icon';
import { router } from '@inertiajs/react';

export const STUDIO_STEPS = ['Dasar', 'Isi', 'Gaya', 'Pratinjau', 'Bayar'];

/**
 * Rute tiap langkah. "Dasar" dan "Isi" berada di halaman Studio yang sama; "Bayar" tidak pernah jadi tujuan mundur.
 */
const STEP_ROUTES = ['studio.edit', 'studio.edit', 'studio.style', 'preview.show', null];

/**
 * Indikator 5 langkah Studio: Dasar → Isi → Gaya → Pratinjau → Bayar.
 * Dengan `greetingId`, langkah yang sudah selesai bisa diketuk untuk kembali ke sana (lewat `onNavigate`).
 *
 * @param {number} current indeks langkah aktif (0-based)
 */
export default function Stepper({ current, greetingId = null, onNavigate = (href) => router.visit(href) }) {
    const progress = (current / (STUDIO_STEPS.length - 1)) * 100;

    return (
        <nav aria-label="Langkah pembuatan kartu" className="relative flex items-center justify-between">
            <div className="absolute left-4 right-4 top-[14px] h-[3px] rounded-full bg-surface-container-highest" />
            <div
                className="absolute left-4 top-[14px] h-[3px] rounded-full bg-primary transition-all"
                style={{ width: `calc((100% - 2rem) * ${progress / 100})` }}
            />
            {STUDIO_STEPS.map((label, index) => {
                const isDone = index < current;
                const isActive = index === current;
                const href = isDone && greetingId && STEP_ROUTES[index] ? route(STEP_ROUTES[index], greetingId) : null;
                const content = (
                    <>
                        <span
                            className={`flex h-7 w-7 items-center justify-center rounded-full ring-4 ring-surface ${
                                isDone
                                    ? 'bg-primary text-on-primary'
                                    : isActive
                                      ? 'scale-110 bg-primary-container text-on-primary shadow-md'
                                      : 'bg-surface-container-highest text-secondary'
                            }`}
                        >
                            {isDone ? <Icon name="check" size={15} /> : <span className="text-label-md">{index + 1}</span>}
                        </span>
                        <span
                            className={`text-label-sm ${isActive ? 'font-bold text-primary' : isDone ? 'text-on-surface' : 'text-on-surface-variant'} ${
                                href ? 'underline decoration-primary/30 underline-offset-2' : ''
                            }`}
                        >
                            {label}
                        </span>
                    </>
                );

                return href ? (
                    <button key={label} type="button" onClick={() => onNavigate(href)} className="relative z-10 flex flex-col items-center gap-1 rounded-xl p-0.5 active:scale-95">
                        {content}
                    </button>
                ) : (
                    <div key={label} aria-current={isActive ? 'step' : undefined} className="relative z-10 flex flex-col items-center gap-1 p-0.5">
                        {content}
                    </div>
                );
            })}
        </nav>
    );
}
