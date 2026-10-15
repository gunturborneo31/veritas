import Icon from '@/Components/Icon';
import TemplateCover from '@/Components/TemplateCover';
import { rupiah } from '@/lib/format';
import { Link } from '@inertiajs/react';
import { useState } from 'react';

/**
 * Daftar adegan yang benar-benar dilihat penerima (komponen latar seperti musik tidak dihitung).
 */
export function visibleFlow(template) {
    return (template.flow ?? []).filter((step) => step.key !== 'musik_latar');
}

/**
 * Lapisan "intip alur": urutan pengalaman penerima, tanpa harus membuka demo.
 */
export function FlowPeek({ template, onClose }) {
    const flow = visibleFlow(template);

    return (
        <div className="absolute inset-0 z-20 flex flex-col bg-inverse-surface/85 p-3 text-inverse-on-surface backdrop-blur-md">
            <div className="mb-2 flex items-center justify-between">
                <span className="text-label-sm uppercase tracking-widest text-tertiary-fixed-dim">Alur penerima</span>
                <button type="button" onClick={onClose} aria-label="Tutup" className="flex h-7 w-7 items-center justify-center rounded-full bg-white/15">
                    <Icon name="close" size={16} />
                </button>
            </div>
            <ol className="no-scrollbar flex flex-1 flex-col gap-1.5 overflow-y-auto">
                {flow.map((step, index) => (
                    <li key={`${step.key}-${index}`} className="flex items-center gap-2">
                        <span className="flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-white/15 text-[10px] font-bold">{index + 1}</span>
                        <Icon name={step.icon} size={15} className="text-tertiary-fixed-dim" />
                        <span className="truncate text-label-md">{step.name}</span>
                        {step.optional && <span className="shrink-0 rounded-full bg-white/15 px-1.5 text-[9px] uppercase tracking-wider text-white/80">opsional</span>}
                    </li>
                ))}
            </ol>
        </div>
    );
}

/**
 * Kartu template katalog: sampul bertema, intip alur, info durasi & interaksi.
 */
export default function TemplateCard({ template, wide = false }) {
    const [isPeeking, setIsPeeking] = useState(false);
    const accent = template.theme?.accent ?? '#a83223';
    const flow = visibleFlow(template);

    return (
        <article
            className={`group flex h-full min-w-0 overflow-hidden rounded-[1.75rem] bg-surface-container-lowest shadow-card transition-all duration-300 hover:-translate-y-1 hover:shadow-[0_18px_40px_-12px_rgba(40,21,38,0.25)] ${
                wide ? 'flex-row sm:flex-col' : 'flex-col'
            }`}
        >
            <div className={`relative overflow-hidden ${wide ? 'w-[45%] shrink-0 sm:aspect-[4/5] sm:w-full' : 'aspect-[4/5] w-full'}`}>
                <Link href={route('catalog.show', template.slug)} aria-label={template.name} className="block h-full w-full">
                    <TemplateCover template={template} />
                </Link>

                <span className="absolute left-2.5 top-2.5 z-10 rounded-full bg-white/80 px-2 py-0.5 text-label-sm text-on-surface shadow-sm backdrop-blur">
                    {template.category?.emoji} {template.category?.name}
                </span>

                {flow.length > 0 && (
                    <button
                        type="button"
                        onClick={() => setIsPeeking(true)}
                        className="absolute bottom-2.5 left-2.5 right-2.5 z-10 flex items-center justify-center gap-1 rounded-full bg-white/85 py-1.5 text-label-sm shadow-sm backdrop-blur transition-transform active:scale-95"
                        style={{ color: accent }}
                    >
                        <Icon name="visibility" size={14} />
                        Ketuk untuk mengintip
                    </button>
                )}

                {isPeeking && <FlowPeek template={template} onClose={() => setIsPeeking(false)} />}
            </div>

            <div className="flex flex-1 flex-col gap-1.5 p-3">
                <h2 className="line-clamp-1 font-display text-headline-sm text-on-surface">{template.name}</h2>
                {template.story && <p className="line-clamp-2 text-[12.5px] leading-snug text-on-surface-variant">{template.story}</p>}

                <div className="mt-auto flex flex-wrap items-center gap-x-2 gap-y-1 pt-1 text-[11px] text-on-surface-variant">
                    <span className="inline-flex items-center gap-0.5">
                        <Icon name="schedule" size={13} />~{template.duration_minutes} mnt
                    </span>
                    <span className="inline-flex items-center gap-0.5">
                        <Icon name="auto_stories" size={13} />
                        {flow.filter((step) => !step.optional).length} bagian
                    </span>
                    {template.needs_microphone && (
                        <span className="inline-flex items-center gap-0.5">
                            <Icon name="mic" size={13} />
                            Mikrofon
                        </span>
                    )}
                </div>

                <div className="flex items-center justify-between gap-2 pt-1.5">
                    <span className="font-display text-label-lg" style={{ color: accent }}>
                        {rupiah(template.price)}
                    </span>
                    <Link
                        href={route('catalog.demo', template.slug)}
                        className="inline-flex items-center gap-0.5 rounded-full px-2.5 py-1 text-label-sm text-on-primary shadow-sm transition-transform active:scale-95"
                        style={{ backgroundColor: accent }}
                    >
                        <Icon name="play_arrow" size={14} />
                        Demo
                    </Link>
                </div>
            </div>
        </article>
    );
}
