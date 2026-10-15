/**
 * Kalimat besar: satu kalimat penting yang tampil sendirian memenuhi layar.
 */
export default function BigQuote({ values, base }) {
    const quote = values.quote || `Untuk ${base.recipient_name || 'kamu'}, yang membuat hari-hari biasa terasa istimewa.`;

    return (
        <div className="relative flex w-full flex-col items-center gap-5 px-1 py-6 text-center">
            <span aria-hidden="true" className="font-display text-[96px] leading-none text-primary/25">
                “
            </span>
            <p className="-mt-10 animate-layer-in font-display text-[26px] font-bold leading-snug text-on-surface">{quote}</p>
            <span className="h-1 w-12 rounded-full bg-primary/60" />
            {values.caption && <p className="max-w-xs text-body-sm italic text-on-surface-variant">{values.caption}</p>}
        </div>
    );
}
