import Icon from '@/Components/Icon';

export const STUDIO_STEPS = ['Dasar', 'Isi', 'Gaya', 'Pratinjau', 'Bayar'];

/**
 * Indikator 5 langkah Studio: Dasar → Isi → Gaya → Pratinjau → Bayar.
 *
 * @param {number} current indeks langkah aktif (0-based)
 */
export default function Stepper({ current }) {
    const progress = (current / (STUDIO_STEPS.length - 1)) * 100;

    return (
        <div className="relative flex items-center justify-between">
            <div className="absolute left-4 right-4 top-[14px] h-[3px] rounded-full bg-surface-container-highest" />
            <div
                className="absolute left-4 top-[14px] h-[3px] rounded-full bg-primary transition-all"
                style={{ width: `calc((100% - 2rem) * ${progress / 100})` }}
            />
            {STUDIO_STEPS.map((label, index) => {
                const isDone = index < current;
                const isActive = index === current;

                return (
                    <div key={label} className="relative z-10 flex flex-col items-center gap-1">
                        <div
                            className={`flex h-7 w-7 items-center justify-center rounded-full ring-4 ring-surface ${
                                isDone
                                    ? 'bg-primary text-on-primary'
                                    : isActive
                                      ? 'scale-110 bg-primary-container text-on-primary shadow-md'
                                      : 'bg-surface-container-highest text-secondary'
                            }`}
                        >
                            {isDone ? <Icon name="check" size={15} /> : <span className="text-label-md">{index + 1}</span>}
                        </div>
                        <span
                            className={`text-label-sm ${isActive ? 'font-bold text-primary' : isDone ? 'text-on-surface' : 'text-on-surface-variant'}`}
                        >
                            {label}
                        </span>
                    </div>
                );
            })}
        </div>
    );
}
