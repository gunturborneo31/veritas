import Icon from '@/Components/Icon';

/**
 * Surat di atas kertas bergaris dengan segel lilin, seperti surat tulisan tangan.
 */
export default function Letter({ values, base }) {
    return (
        <div className="relative w-full pt-5">
            <div className="absolute left-1/2 top-0 z-10 flex h-11 w-11 -translate-x-1/2 items-center justify-center rounded-full bg-primary text-on-primary shadow-lg ring-4 ring-white/70">
                <Icon name="favorite" filled size={20} />
            </div>
            <article
                className="relative w-full rotate-[-0.6deg] overflow-hidden rounded-2xl bg-[#fffdf8] px-6 pb-6 pt-9 shadow-[0_18px_40px_-16px_rgba(40,21,38,0.35)]"
                style={{
                    backgroundImage: 'repeating-linear-gradient(transparent, transparent 27px, rgba(140,113,108,0.16) 28px)',
                    backgroundPositionY: '58px',
                }}
            >
                <div className="absolute inset-y-0 left-4 w-px bg-primary/20" />
                <p className="mb-1 text-label-sm uppercase tracking-[0.25em] text-primary">Untuk {base.recipient_name || 'kamu'}</p>
                {values.greeting_line && <p className="font-display text-headline-sm italic leading-7 text-on-surface">{values.greeting_line}</p>}
                <p className="mt-1 whitespace-pre-line text-body-md leading-7 text-on-surface">{values.body || 'Tulis isi suratmu di Studio…'}</p>
                <p className="mt-3 text-right font-display text-label-lg italic text-primary">— {values.signature || base.sender_name}</p>
            </article>
        </div>
    );
}
