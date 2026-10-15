import Icon from '@/Components/Icon';
import TemplateCard, { FlowPeek, cardTitle, visibleFlow } from '@/Components/TemplateCard';
import TemplateCover from '@/Components/TemplateCover';
import PublicLayout from '@/Layouts/PublicLayout';
import { rupiah } from '@/lib/format';
import { Link, router } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';

const PROMISES = [
    ['visibility', 'Coba dulu, bayar belakangan', 'Pratinjau penuh tanpa daftar'],
    ['lock', 'Bisa dikunci PIN', 'Hanya dia yang bisa membuka'],
    ['smartphone', 'Tanpa aplikasi', 'Cukup link & QR via WhatsApp'],
];

/**
 * Sorotan satu template di atas grid supaya katalog kecil tetap terasa terkurasi.
 */
function Spotlight({ template }) {
    const [isPeeking, setIsPeeking] = useState(false);
    const accent = template.theme.accent;
    const flow = visibleFlow(template);

    return (
        <article
            className="relative overflow-hidden rounded-[2rem] p-1 shadow-[0_24px_48px_-20px_rgba(40,21,38,0.35)]"
            style={{ background: `linear-gradient(135deg, ${accent}, ${template.theme.gradient[2]})` }}
        >
            <div className="grid overflow-hidden rounded-[1.75rem] bg-surface-container-lowest sm:grid-cols-[1fr_1.1fr]">
                <div className="group relative aspect-[16/10] sm:aspect-auto">
                    <TemplateCover template={template} size="lg" />
                    <span className="absolute left-3 top-3 inline-flex items-center gap-1 rounded-full bg-white/85 px-2.5 py-1 text-label-sm shadow-sm backdrop-blur" style={{ color: accent }}>
                        <Icon name="local_fire_department" filled size={14} />
                        Sorotan minggu ini
                    </span>
                    {isPeeking && <FlowPeek template={template} onClose={() => setIsPeeking(false)} />}
                </div>
                <div className="flex flex-col gap-3 p-5">
                    <div className="flex flex-wrap items-center gap-1.5">
                        <span className="rounded-full px-2.5 py-0.5 text-label-sm" style={{ backgroundColor: template.theme.soft, color: accent }}>
                            {template.category?.emoji} {template.category?.name}
                        </span>
                        {template.tags.map((tag) => (
                            <span key={tag} className="rounded-full bg-surface-container px-2.5 py-0.5 text-label-sm text-on-surface-variant">
                                {tag}
                            </span>
                        ))}
                    </div>
                    <h2 className="font-display text-headline-lg-mobile leading-tight text-on-surface">{cardTitle(template)}</h2>
                    <p className="text-body-sm text-on-surface-variant">{template.series?.story ?? template.story ?? template.tagline}</p>
                    {template.designs?.length > 1 && (
                        <div className="no-scrollbar -mx-1 flex gap-1.5 overflow-x-auto px-1 py-0.5">
                            {template.designs.map((design) => (
                                <Link
                                    key={design.slug}
                                    href={route('catalog.show', design.slug)}
                                    className="flex shrink-0 items-center gap-1.5 rounded-full bg-surface-container-low py-1 pl-1 pr-2.5 text-[11px] font-semibold text-on-surface"
                                >
                                    <span className="h-4 w-4 rounded-full" style={{ background: `linear-gradient(135deg, ${design.theme.accent}, ${design.theme.gradient[2]})` }} />
                                    {design.design_name ?? design.name}
                                </Link>
                            ))}
                        </div>
                    )}

                    <ol className="flex flex-wrap items-center gap-1 text-[11px] text-on-surface">
                        {flow.map((step, index) => (
                            <li key={`${step.key}-${index}`} className="inline-flex items-center gap-1">
                                <span
                                    title={step.optional ? 'Opsional' : undefined}
                                    className={`inline-flex items-center gap-1 rounded-full px-2 py-1 ${
                                        step.optional ? 'border border-dashed border-outline-variant text-on-surface-variant' : 'bg-surface-container-low'
                                    }`}
                                >
                                    <Icon name={step.icon} size={13} style={{ color: accent }} />
                                    {step.name}
                                </span>
                                {index < flow.length - 1 && <Icon name="chevron_right" size={14} className="text-outline" />}
                            </li>
                        ))}
                    </ol>

                    <div className="mt-auto flex items-end justify-between gap-3 pt-2">
                        <div>
                            <p className="text-label-sm text-on-surface-variant">
                                Paket 1 • {template.scene_count} halaman • ~{template.duration_minutes} menit
                            </p>
                            <p className="font-display text-headline-md" style={{ color: accent }}>
                                {rupiah(template.price)}
                            </p>
                        </div>
                        <button type="button" onClick={() => setIsPeeking(true)} className="text-label-sm font-bold underline-offset-2 hover:underline sm:hidden" style={{ color: accent }}>
                            Intip alur
                        </button>
                    </div>
                    <div className="flex gap-2">
                        <Link
                            href={route('catalog.demo', template.slug)}
                            className="flex h-11 flex-1 items-center justify-center gap-1 rounded-full bg-surface-container text-label-md text-on-surface active:scale-95"
                        >
                            <Icon name="play_circle" size={18} />
                            Coba demo
                        </Link>
                        <Link
                            href={route('catalog.show', template.slug)}
                            className="flex h-11 flex-1 items-center justify-center gap-1 rounded-full text-label-md text-white shadow-md active:scale-95"
                            style={{ backgroundColor: accent }}
                        >
                            <Icon name="auto_awesome" size={18} />
                            Buat sekarang
                        </Link>
                    </div>
                </div>
            </div>
        </article>
    );
}

/**
 * Katalog template (desain katalogtemplate, dikembangkan dengan pola katalog kado digital yang beredar:
 * kategori berbasis emosi, intip alur, sorotan, dan teaser momen berikutnya).
 */
export default function Index({ categories, templates, filters }) {
    const [search, setSearch] = useState(filters.cari ?? '');
    const isFirstRender = useRef(true);

    const visit = (params) =>
        router.get(route('catalog.index'), { kategori: filters.kategori, cari: search || undefined, ...params }, { preserveState: true, preserveScroll: true, replace: true });

    useEffect(() => {
        if (isFirstRender.current) {
            isFirstRender.current = false;

            return;
        }

        const timer = setTimeout(() => visit({ cari: search || undefined }), 350);

        return () => clearTimeout(timer);
    }, [search]); // eslint-disable-line react-hooks/exhaustive-deps

    const isFiltered = Boolean(filters.kategori || filters.cari);
    const [spotlight, ...rest] = templates;
    const gridTemplates = isFiltered ? templates : rest;
    const upcoming = categories.filter((category) => category.templates_count === 0);
    const activeCategory = categories.find((category) => category.slug === filters.kategori);
    const totalTemplates = categories.reduce((sum, category) => sum + category.templates_count, 0);
    const totalSeries = templates.length;

    const chipClass = (isActive) =>
        `flex h-9 shrink-0 items-center gap-1.5 rounded-full px-3.5 text-label-md transition-all active:scale-95 ${
            isActive ? 'bg-on-surface text-surface-container-lowest shadow-md' : 'bg-surface-container-lowest text-on-surface shadow-sm hover:bg-surface-container'
        }`;

    return (
        <PublicLayout title="Katalog Template">
            <section className="relative overflow-hidden px-margin pb-space-md pt-space-lg">
                <div className="pointer-events-none absolute -right-20 -top-24 h-64 w-64 rounded-full bg-primary-fixed/50 blur-3xl" />
                <div className="pointer-events-none absolute -left-24 top-10 h-56 w-56 rounded-full bg-tertiary-fixed/40 blur-3xl" />
                <div className="relative">
                    <span className="inline-flex items-center gap-1.5 rounded-full bg-surface-container-lowest px-3 py-1 text-label-sm text-primary shadow-sm">
                        <Icon name="auto_awesome" filled size={14} className="text-tertiary" />
                        {isFiltered ? 'Katalog terkurasi' : `${totalSeries} cerita interaktif`} • {totalTemplates} desain
                    </span>
                    <h1 className="mt-3 max-w-md font-display text-headline-lg-mobile leading-tight text-on-surface sm:text-headline-lg">
                        Pilih cara kamu bilang <span className="text-primary">“aku peduli”</span>
                    </h1>
                    <p className="mt-1.5 max-w-md text-body-sm text-on-surface-variant">
                        Setiap template adalah cerita kecil yang dibuka penerima langkah demi langkah — kamu tinggal isi nama, foto, dan pesanmu.
                    </p>

                    <div className="relative mt-space-md flex w-full items-center">
                        <Icon name="search" size={20} className="pointer-events-none absolute left-4 text-on-surface-variant" />
                        <input
                            type="search"
                            value={search}
                            onChange={(event) => setSearch(event.target.value)}
                            placeholder="Cari momen: ulang tahun, kangen, lebaran…"
                            className="h-12 w-full rounded-full border-0 bg-surface-container-lowest pl-11 pr-4 text-body-sm text-on-surface shadow-card placeholder:text-on-surface-variant/60 focus:ring-2 focus:ring-primary/30"
                        />
                    </div>
                </div>
            </section>

            <section className="sticky top-16 z-30 bg-surface/90 py-2 backdrop-blur-xl">
                <div className="no-scrollbar flex items-center gap-2 overflow-x-auto px-margin py-1">
                    <button type="button" onClick={() => visit({ kategori: undefined })} className={chipClass(!filters.kategori)}>
                        ✨ Semua
                    </button>
                    {categories.map((category) => (
                        <button key={category.slug} type="button" onClick={() => visit({ kategori: category.slug })} className={chipClass(filters.kategori === category.slug)}>
                            <span>{category.emoji}</span>
                            <span>{category.name}</span>
                            {category.templates_count === 0 && (
                                <span className="rounded-full bg-tertiary-fixed px-1.5 text-[9px] font-bold uppercase text-on-tertiary-fixed">Segera</span>
                            )}
                        </button>
                    ))}
                </div>
            </section>

            {!isFiltered && spotlight && (
                <section className="px-margin pb-space-lg pt-space-sm">
                    <Spotlight template={spotlight} />
                </section>
            )}

            {gridTemplates.length > 0 && (
                <section className="px-margin pb-space-lg">
                    <div className="mb-space-sm flex items-end justify-between">
                        <h2 className="font-display text-headline-sm text-on-surface">
                            {isFiltered ? `${templates.length} cerita ditemukan` : 'Cerita lainnya'}
                        </h2>
                        {isFiltered && (
                            <button type="button" onClick={() => router.get(route('catalog.index'))} className="text-label-md text-primary">
                                Reset filter
                            </button>
                        )}
                    </div>
                    <div className="grid grid-cols-2 gap-3 sm:grid-cols-3">
                        {gridTemplates.map((template, index) => {
                            const isOrphan = gridTemplates.length % 2 === 1 && index === gridTemplates.length - 1;

                            return (
                                <div key={template.slug} className={isOrphan ? 'col-span-2 sm:col-span-1' : ''}>
                                    <TemplateCard template={template} wide={isOrphan} />
                                </div>
                            );
                        })}
                    </div>
                </section>
            )}

            {templates.length === 0 && (
                <section className="mx-margin mb-space-lg flex flex-col items-center gap-2 rounded-[1.75rem] bg-surface-container-lowest px-6 py-space-xl text-center shadow-card">
                    <span className="text-5xl">{activeCategory?.emoji ?? '🔍'}</span>
                    <h2 className="font-display text-headline-sm text-on-surface">
                        {activeCategory && !filters.cari ? `Template ${activeCategory.name} sedang kami siapkan` : 'Belum ada yang cocok'}
                    </h2>
                    <p className="max-w-xs text-body-sm text-on-surface-variant">
                        {activeCategory && !filters.cari
                            ? 'Sementara itu, template lain tetap bisa kamu pakai untuk momen ini — isinya bebas kamu tulis sendiri.'
                            : 'Coba kata kunci lain, misalnya “ulang tahun” atau “keluarga”.'}
                    </p>
                    <button type="button" onClick={() => router.get(route('catalog.index'))} className="mt-2 rounded-full bg-primary px-5 py-2.5 text-label-md text-on-primary shadow-md">
                        Lihat semua template
                    </button>
                </section>
            )}

            {!isFiltered && upcoming.length > 0 && (
                <section className="px-margin pb-space-lg">
                    <div className="mb-space-sm">
                        <p className="text-label-sm uppercase tracking-wider text-primary">Segera hadir</p>
                        <h2 className="font-display text-headline-sm text-on-surface">Momen berikutnya yang sedang kami racik</h2>
                    </div>
                    <div className="no-scrollbar -mx-margin flex gap-3 overflow-x-auto px-margin pb-1">
                        {upcoming.map((category) => (
                            <div
                                key={category.slug}
                                className="flex w-36 shrink-0 flex-col items-start gap-2 rounded-3xl border border-dashed border-outline-variant bg-surface-container-lowest/70 p-4"
                            >
                                <span className="text-3xl grayscale-[30%]">{category.emoji}</span>
                                <span className="font-display text-label-lg text-on-surface">{category.name}</span>
                                <span className="inline-flex items-center gap-1 text-[11px] text-on-surface-variant">
                                    <Icon name="hourglass_top" size={13} />
                                    Sedang disiapkan
                                </span>
                            </div>
                        ))}
                    </div>
                </section>
            )}

            <section className="px-margin pb-space-xl">
                <div className="grid gap-2 rounded-[1.75rem] bg-surface-container-low p-4 sm:grid-cols-3">
                    {PROMISES.map(([icon, title, body]) => (
                        <div key={title} className="flex items-center gap-3">
                            <span className="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-surface-container-lowest text-primary shadow-sm">
                                <Icon name={icon} size={20} />
                            </span>
                            <div>
                                <p className="text-label-md text-on-surface">{title}</p>
                                <p className="text-[12px] text-on-surface-variant">{body}</p>
                            </div>
                        </div>
                    ))}
                </div>
            </section>
        </PublicLayout>
    );
}
