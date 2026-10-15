import Icon from '@/Components/Icon';
import TemplateCover from '@/Components/TemplateCover';
import PublicLayout from '@/Layouts/PublicLayout';
import { rupiah } from '@/lib/format';
import { Link, router } from '@inertiajs/react';
import { useState } from 'react';

/**
 * Detail template: info, isi yang bisa ditambahkan, "Coba demo" / "Buat sekarang" (desain detailtemplate).
 */
export default function Show({ template }) {
    const [isCreating, setIsCreating] = useState(false);

    const create = () =>
        router.post(route('studio.store', template.slug), {}, { onStart: () => setIsCreating(true), onFinish: () => setIsCreating(false) });

    return (
        <PublicLayout
            title={template.name}
            hideBottomNav
            header={
                <div className="flex min-w-0 items-center gap-space-sm">
                    <button
                        type="button"
                        aria-label="Kembali"
                        onClick={() => window.history.back()}
                        className="-ml-2 flex h-11 w-11 items-center justify-center rounded-full text-on-surface hover:bg-surface-container-high/60"
                    >
                        <Icon name="arrow_back" size={24} />
                    </button>
                    <h1 className="truncate text-headline-sm text-on-surface">Detail Template</h1>
                </div>
            }
        >
            <div className="flex w-full flex-col pb-40">
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

                <section className="mt-space-md flex flex-col gap-space-xs px-margin">
                    <div className="flex flex-wrap items-center gap-space-xs">
                        <span className="inline-flex items-center gap-1 rounded-full bg-tertiary-fixed px-2.5 py-0.5 text-label-sm text-on-tertiary-fixed">
                            <Icon name="sell" size={14} /> {rupiah(template.price)} • Paket 1
                        </span>
                        <span className="inline-flex items-center gap-1 rounded-full bg-primary-fixed px-2.5 py-0.5 text-label-sm text-on-primary-fixed">{template.tier}</span>
                    </div>
                    <h2 className="mt-1 text-headline-lg-mobile text-on-surface">{template.name}</h2>
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

            <footer className="pb-safe fixed inset-x-0 bottom-0 z-40 bg-surface/95 px-margin py-3 shadow-bar backdrop-blur-xl">
                <div className="mx-auto flex max-w-lg items-center gap-space-sm">
                    <Link
                        href={route('catalog.demo', template.slug)}
                        className="flex h-12 items-center justify-center gap-1.5 rounded-full bg-secondary-container px-space-md text-label-lg text-on-secondary-container active:scale-95"
                    >
                        <Icon name="play_circle" size={20} />
                        Coba demo
                    </Link>
                    <button
                        type="button"
                        onClick={create}
                        disabled={isCreating}
                        className="flex h-12 flex-1 items-center justify-center gap-2 rounded-full bg-primary px-space-lg text-label-lg text-on-primary shadow-lg shadow-primary/30 active:scale-95 disabled:opacity-60"
                    >
                        <Icon name="auto_awesome" size={20} />
                        Buat sekarang
                    </button>
                </div>
                <p className="mt-2 text-center text-label-sm text-on-surface-variant">⚡ Tanpa aplikasi • Tanpa daftar dulu • Kirim via WhatsApp</p>
            </footer>
        </PublicLayout>
    );
}
