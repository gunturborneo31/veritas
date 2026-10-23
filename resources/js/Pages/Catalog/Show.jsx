import Icon from '@/Components/Icon';
import TemplateCover from '@/Components/TemplateCover';
import PublicLayout from '@/Layouts/PublicLayout';
import { rupiah } from '@/lib/format';
import { themeVars } from '@/lib/theme';
import { Link, router } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';

/**
 * Pemilih desain dalam satu seri: setiap desain punya alur, isi, dan warna sendiri.
 */
function DesignPicker({ designs, current }) {
    const listRef = useRef(null);
    const activeRef = useRef(null);

    // Geser daftar secara horizontal saja agar desain aktif terlihat, tanpa menggulir halaman.
    useEffect(() => {
        const [list, active] = [listRef.current, activeRef.current];

        if (list && active) {
            list.scrollLeft = active.offsetLeft - (list.clientWidth - active.clientWidth) / 2;
        }
    }, [current]);

    return (
        <section className="mt-space-md flex flex-col gap-space-xs">
            <div className="flex items-end justify-between px-margin">
                <h3 className="text-headline-sm text-on-surface">Pilih desain</h3>
                <span className="text-label-sm text-on-surface-variant">{designs.length} desain • warna bisa diganti</span>
            </div>
            <div ref={listRef} className="no-scrollbar relative flex gap-3 overflow-x-auto px-margin py-2">
                {designs.map((design) => {
                    const isActive = design.slug === current;

                    return (
                        <button
                            key={design.slug}
                            ref={isActive ? activeRef : null}
                            type="button"
                            onClick={() => !isActive && router.visit(route('catalog.show', design.slug), { preserveScroll: true, replace: true })}
                            aria-pressed={isActive}
                            className={`flex w-32 shrink-0 flex-col overflow-hidden rounded-2xl bg-surface-container-lowest text-left shadow-sm transition-all active:scale-95 ${
                                isActive ? 'ring-2 ring-offset-2' : 'opacity-90'
                            }`}
                            style={{ '--tw-ring-color': design.theme.accent }}
                        >
                            <div className="relative aspect-[4/3] w-full">
                                <TemplateCover template={design} size="sm" />
                                {isActive && (
                                    <span className="absolute right-1.5 top-1.5 flex h-5 w-5 items-center justify-center rounded-full text-white shadow" style={{ backgroundColor: design.theme.accent }}>
                                        <Icon name="check" size={13} />
                                    </span>
                                )}
                            </div>
                            <div className="flex flex-col gap-0.5 p-2.5">
                                <span className="truncate text-label-md text-on-surface">{design.design_name ?? design.name}</span>
                                <span className="text-xs text-on-surface-variant">{design.scene_count} halaman</span>
                                <span className="text-label-sm" style={{ color: design.theme.accent }}>
                                    {rupiah(design.price)}
                                </span>
                            </div>
                        </button>
                    );
                })}
            </div>
        </section>
    );
}

/**
 * Detail template: pilih desain, info, isi yang bisa ditambahkan, "Coba demo" / "Buat sekarang" (desain detailtemplate).
 */
export default function Show({ template }) {
    const [isCreating, setIsCreating] = useState(false);
    // Warna halaman ikut desain, tetapi huruf judul halaman tetap huruf aplikasi.
    const pageVars = { ...themeVars(template.theme), '--font-display': undefined };
    const title = template.series ? `${template.series.name} · ${template.design_name ?? template.name}` : template.name;

    const create = () =>
        router.post(route('studio.store', template.slug), {}, { onStart: () => setIsCreating(true), onFinish: () => setIsCreating(false) });

    return (
        <PublicLayout
            title={title}
            back={{ href: route('catalog.index', template.type === 'undangan' ? { jenis: 'undangan' } : {}), label: template.type === 'undangan' ? 'Katalog Undangan' : 'Katalog' }}
            actionBar={
                <div style={pageVars}>
                    <div className="flex items-center gap-space-sm">
                        <Link
                            href={route('catalog.demo', template.slug)}
                            className="flex h-12 shrink-0 items-center justify-center gap-1.5 whitespace-nowrap rounded-full bg-primary-fixed px-space-md text-label-lg text-on-primary-fixed active:scale-95"
                        >
                            <Icon name="play_circle" size={20} />
                            Coba demo
                        </Link>
                        <button
                            type="button"
                            onClick={create}
                            disabled={isCreating}
                            className="flex h-12 min-w-0 flex-1 items-center justify-center gap-2 whitespace-nowrap rounded-full bg-primary px-space-md text-label-lg text-on-primary shadow-lg shadow-primary/30 active:scale-95 disabled:opacity-60"
                        >
                            <Icon name="auto_awesome" size={20} />
                            Buat sekarang
                        </button>
                    </div>
                    <p className="mt-1.5 hidden text-center text-label-sm text-on-surface-variant sm:block">⚡ Tanpa aplikasi • Tanpa daftar • Kirim via WhatsApp</p>
                </div>
            }
        >
            <div className="flex w-full flex-col" style={pageVars}>
                <section className="relative flex w-full flex-col items-center px-margin pt-space-md">
                    <div className="pointer-events-none absolute top-12 -z-10 h-64 w-64 rounded-full bg-primary-fixed-dim/40 blur-3xl" />
                    {template.highlight_label && (
                        <div className="mb-space-sm inline-flex items-center gap-1.5 rounded-full bg-surface-container-high px-3 py-1 text-on-surface shadow-sm">
                            <Icon name={template.highlight_icon} size={16} className="text-primary" />
                            <span className="text-label-sm tracking-wide">✨ {template.highlight_label}</span>
                        </div>
                    )}
                    <div className="relative aspect-[9/16] w-full max-w-[280px] rounded-[2.5rem] border-4 border-surface-container-high/60 bg-surface-container-lowest p-2.5 shadow-2xl shadow-on-surface/10">
                        <div className="relative flex h-full w-full items-center justify-center overflow-hidden rounded-[2rem] bg-surface-container-low">
                            <div className="absolute top-2 z-30 h-4 w-20 rounded-full bg-on-surface/90" />
                            <TemplateCover template={template} />
                            <Link
                                href={route('catalog.demo', template.slug)}
                                className="absolute inset-0 flex flex-col items-center justify-center gap-space-sm"
                                aria-label="Coba demo"
                            >
                                <span className="relative flex h-16 w-16 items-center justify-center rounded-full bg-primary text-on-primary shadow-xl shadow-primary/40">
                                    <span className="absolute inset-0 animate-ping rounded-full bg-primary-container/40" />
                                    <Icon name="play_arrow" filled size={32} />
                                </span>
                                <span className="flex items-center gap-1.5 rounded-full bg-surface-container-lowest/90 px-3.5 py-1.5 text-label-md text-on-surface shadow-md backdrop-blur-md">
                                    <Icon name="touch_app" size={14} className="text-tertiary" />
                                    Sentuh untuk simulasi
                                </span>
                            </Link>
                        </div>
                    </div>
                </section>

                {template.designs.length > 1 && <DesignPicker designs={template.designs} current={template.slug} />}

                <section className="mt-space-md flex flex-col gap-space-xs px-margin">
                    <div className="flex flex-wrap items-center gap-space-xs">
                        <span className="inline-flex items-center gap-1 rounded-full bg-tertiary-fixed px-2.5 py-0.5 text-label-sm text-on-tertiary-fixed">
                            <Icon name="sell" size={14} /> {rupiah(template.price)} • Paket 1
                        </span>
                        <span className="inline-flex items-center gap-1 rounded-full bg-primary-fixed px-2.5 py-0.5 text-label-sm text-on-primary-fixed">{template.tier}</span>
                        <span className="inline-flex items-center gap-1 rounded-full bg-surface-container px-2.5 py-0.5 text-label-sm text-on-surface">
                            <Icon name="auto_stories" size={14} /> {template.scene_count} halaman • ~{template.duration_minutes} menit
                        </span>
                    </div>
                    {template.series && <p className="mt-1 text-label-md uppercase tracking-wider text-primary">{template.series.name}</p>}
                    <h2 className="text-headline-lg-mobile text-on-surface">{template.design_name ?? template.name}</h2>
                    {template.description && <p className="text-body-md leading-relaxed text-on-surface-variant">{template.description}</p>}
                    <div className="no-scrollbar -mx-margin flex items-center gap-2 overflow-x-auto px-margin py-2">
                        {template.category && (
                            <span className="flex shrink-0 items-center gap-1.5 rounded-full bg-surface-container px-3 py-1.5 text-label-md text-on-surface shadow-sm">
                                {template.category.emoji} Momen: {template.category.name}
                            </span>
                        )}
                        {template.tags.map((tag) => (
                            <span key={tag} className="shrink-0 rounded-full bg-surface-container px-3 py-1.5 text-label-md text-on-surface shadow-sm">
                                {tag}
                            </span>
                        ))}
                        <span className="shrink-0 rounded-full bg-surface-container px-3 py-1.5 text-label-md text-on-surface shadow-sm">📱 Semua HP</span>
                    </div>
                </section>

                <section className="mt-space-lg flex flex-col gap-space-sm px-margin">
                    <div>
                        <h3 className="text-headline-sm text-on-surface">Isi yang bisa kamu tambahkan</h3>
                        <p className="text-label-md text-on-surface-variant">Semua sudah termasuk harga Paket 1</p>
                    </div>
                    <div className="mt-1 grid grid-cols-1 gap-space-xs">
                        {template.components.map((component) => (
                            <div key={component.key} className="flex items-start gap-space-sm rounded-2xl bg-surface-container-lowest p-space-md shadow-sm">
                                <div className="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-primary-fixed text-primary">
                                    <Icon name={component.icon} size={22} />
                                </div>
                                <div className="min-w-0 flex-1">
                                    <div className="flex items-center justify-between">
                                        <h4 className="text-label-lg text-on-surface">{component.name}</h4>
                                        {!component.required && (
                                            <span className="rounded-full bg-surface-container-low px-2 py-0.5 text-label-sm text-on-surface-variant">Opsional</span>
                                        )}
                                    </div>
                                    <p className="mt-0.5 text-body-sm text-on-surface-variant">{component.description}</p>
                                </div>
                            </div>
                        ))}
                    </div>
                </section>
            </div>
        </PublicLayout>
    );
}
