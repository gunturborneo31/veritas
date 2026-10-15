import Brand from '@/Components/Brand';
import Icon from '@/Components/Icon';
import TemplateCover from '@/Components/TemplateCover';
import PublicLayout from '@/Layouts/PublicLayout';
import { rupiah } from '@/lib/format';
import { Link, usePage } from '@inertiajs/react';
import { useState } from 'react';

const STEPS = [
    ['palette', 'bg-primary-fixed text-primary', '1. Pilih Konsep', 'Pilih template interaktif yang pas dengan momenmu: tiup lilin, kartu gosok, hingga surat bergembok.'],
    ['edit_note', 'bg-tertiary-fixed text-tertiary', '2. Isi & Personalisasi', 'Tulis pesan dari hati, tambahkan foto bersama, lagu, hingga rekaman suaramu. Tanpa perlu daftar dulu.'],
    ['send', 'bg-secondary-fixed text-on-surface', '3. Bagikan Sekejap', 'Kirim tautan via WhatsApp atau QR Code. Penerima langsung membuka tanpa install aplikasi.'],
];

/**
 * Beranda (desain promptgayaumum / Jelajah).
 */
export default function Home({ categories, featured }) {
    const { brand } = usePage().props;
    const [isOpen, setIsOpen] = useState(false);

    return (
        <PublicLayout title="Ucapan Digital Interaktif">
            <section className="relative flex flex-col items-center overflow-hidden px-margin pb-space-lg pt-space-md text-center">
                <div className="pointer-events-none absolute -left-16 -top-12 h-56 w-56 rounded-full bg-primary-fixed/40 blur-3xl" />
                <div className="pointer-events-none absolute -right-16 top-24 h-60 w-60 rounded-full bg-tertiary-fixed/35 blur-3xl" />

                <div className="mb-space-sm inline-flex items-center gap-1.5 rounded-full bg-surface-container px-3 py-1 text-label-sm text-primary shadow-sm">
                    <Icon name="auto_awesome" filled size={15} className="text-tertiary" />
                    Kirim Rasa, Bukan Cuma Pesan Singkat
                </div>
                <h1 className="mb-space-sm max-w-sm text-headline-lg-mobile tracking-tight text-on-surface">
                    Ucapan yang bikin dia{' '}
                    <span className="text-primary underline decoration-tertiary-fixed-dim decoration-4 underline-offset-4">berhenti sejenak</span>
                </h1>
                <p className="mb-space-md max-w-xs text-body-md text-on-surface-variant">
                    Bukan sekadar teks biasa. Kirim kartu digital penuh interaksi—bisa ditiup lilinnya, digosok kartunya, hingga diputar lagunya.
                </p>
                <Link
                    href={route('catalog.index')}
                    className="inline-flex h-12 items-center justify-center gap-2 rounded-full bg-primary px-space-lg text-label-lg text-on-primary shadow-md shadow-primary/25 transition-all active:scale-95"
                >
                    Lihat Katalog
                    <Icon name="arrow_forward" size={20} className="text-tertiary-fixed" />
                </Link>

                <div className="relative mt-space-lg w-full max-w-sm">
                    <div className="relative flex flex-col items-center rounded-3xl bg-surface-container-lowest p-5 shadow-lg shadow-on-surface/5">
                        <div className="absolute -top-3 right-5 flex items-center gap-1 rounded-full bg-tertiary-container px-3 py-0.5 text-on-tertiary shadow-sm">
                            <Icon name="favorite" size={14} />
                            <span className="text-label-sm">Spesial</span>
                        </div>
                        <button
                            type="button"
                            onClick={() => setIsOpen((open) => !open)}
                            className="relative flex h-44 w-full items-center justify-center overflow-hidden rounded-2xl bg-surface-container-low"
                        >
                            <div className="absolute inset-0 bg-[radial-gradient(#e0bfba_1px,transparent_1px)] opacity-40 [background-size:12px_12px]" />
                            <div
                                className={`flex h-32 w-48 flex-col items-center justify-center rounded-xl bg-surface-container-lowest p-3 text-center shadow-md transition-all duration-500 ${
                                    isOpen ? '-translate-y-6 scale-105' : 'translate-y-3'
                                }`}
                            >
                                <Icon name="celebration" size={28} className="animate-bounce text-primary" />
                                <p className="mt-1 text-headline-sm text-on-surface">Selamat Ya! 🎉</p>
                                <p className="text-label-sm text-on-surface-variant">Dari: Sahabat Tersayang</p>
                            </div>
                            <div
                                className={`absolute z-10 flex h-12 w-12 items-center justify-center rounded-full bg-primary text-on-primary shadow-md transition-all duration-300 ${
                                    isOpen ? 'scale-125 opacity-0' : ''
                                }`}
                            >
                                <Icon name="favorite" size={24} />
                            </div>
                        </button>
                        <div className="mt-3 flex items-center gap-1.5 text-tertiary">
                            <Icon name="touch_app" size={16} className="animate-pulse" />
                            <span className="text-label-md font-semibold">Ketuk untuk buka kartu ✨</span>
                        </div>
                    </div>
                </div>
            </section>

            <section className="w-full overflow-hidden py-space-sm pl-margin">
                <div className="no-scrollbar flex gap-2 overflow-x-auto py-1 pr-margin">
                    <Link href={route('catalog.index')} className="shrink-0 rounded-full bg-primary px-4 py-2 text-label-md text-on-primary shadow-sm">
                        ✨ Semua
                    </Link>
                    {categories.map((category) => (
                        <Link
                            key={category.slug}
                            href={route('catalog.index', { kategori: category.slug })}
                            className="shrink-0 rounded-full bg-surface-container px-4 py-2 text-label-md text-on-surface shadow-sm"
                        >
                            {category.emoji} {category.name}
                        </Link>
                    ))}
                </div>
            </section>

            <section className="flex flex-col gap-space-md px-margin py-space-md">
                <div className="flex items-end justify-between">
                    <div>
                        <p className="text-label-sm uppercase tracking-wider text-primary">Pilihan Kami</p>
                        <h2 className="text-headline-md text-on-surface">Kartu Paling Disukai</h2>
                    </div>
                    <Link href={route('catalog.index')} className="flex items-center gap-0.5 text-label-md text-primary">
                        Semua
                        <Icon name="chevron_right" size={18} />
                    </Link>
                </div>
                {featured.map((template) => (
                    <div key={template.slug} className="flex flex-col gap-3 rounded-3xl bg-surface-container-lowest p-4 shadow-md shadow-on-surface/5">
                        <div className="relative h-48 w-full overflow-hidden rounded-2xl">
                            <TemplateCover template={template} />
                            {template.highlight_label && (
                                <div className="absolute left-3 top-3 flex items-center gap-1 rounded-full bg-surface-container/90 px-2.5 py-1 text-label-sm text-on-surface shadow-sm backdrop-blur-md">
                                    <Icon name={template.highlight_icon} size={14} className="text-primary" />
                                    {template.highlight_label}
                                </div>
                            )}
                            <div className="absolute right-3 top-3 rounded-full bg-tertiary px-2.5 py-1 text-label-sm text-on-tertiary shadow-sm">Coba Gratis</div>
                            {template.tagline && (
                                <div className="absolute bottom-3 left-3 right-3 truncate rounded-xl bg-inverse-surface/80 px-3 py-1.5 text-body-sm text-inverse-on-surface backdrop-blur-md">
                                    {template.tagline}
                                </div>
                            )}
                        </div>
                        <div className="flex items-center justify-between px-1">
                            <h3 className="text-headline-sm text-on-surface">{template.name}</h3>
                            <span className="text-label-md font-bold text-tertiary">{rupiah(template.price)}</span>
                        </div>
                        <p className="px-1 text-body-sm text-on-surface-variant">
                            {template.category?.emoji} {template.category?.name} • {template.tags.join(' & ')}
                        </p>
                        <div className="flex items-center gap-2 pt-1">
                            <Link
                                href={route('catalog.demo', template.slug)}
                                className="flex h-11 flex-1 items-center justify-center gap-1 rounded-full bg-primary text-label-md text-on-primary shadow-sm active:scale-95"
                            >
                                <Icon name="visibility" size={18} />
                                Lihat Pratinjau
                            </Link>
                            <Link
                                href={route('catalog.show', template.slug)}
                                aria-label="Detail template"
                                className="flex h-11 w-11 items-center justify-center rounded-full bg-surface-container text-on-surface active:scale-95"
                            >
                                <Icon name="arrow_forward" size={20} />
                            </Link>
                        </div>
                    </div>
                ))}
            </section>

            <section className="my-space-sm flex flex-col gap-space-md bg-surface-container-low/70 px-margin py-space-lg">
                <div className="text-center">
                    <p className="text-label-sm uppercase tracking-wider text-primary">Mudah & Menyenangkan</p>
                    <h2 className="text-headline-md text-on-surface">Cara Kerja {brand}</h2>
                </div>
                {STEPS.map(([icon, tone, title, body]) => (
                    <div key={title} className="flex items-start gap-4 rounded-3xl bg-surface-container-lowest p-5 shadow-sm">
                        <div className={`flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl shadow-sm ${tone}`}>
                            <Icon name={icon} size={26} />
                        </div>
                        <div className="flex flex-col gap-1">
                            <h3 className="text-headline-sm text-on-surface">{title}</h3>
                            <p className="text-body-sm text-on-surface-variant">{body}</p>
                        </div>
                    </div>
                ))}
            </section>

            <footer className="mt-space-lg flex flex-col gap-space-lg rounded-t-3xl bg-surface-container-high/60 px-margin pb-space-xl pt-space-lg">
                <div className="flex flex-col gap-2">
                    <Brand />
                    <p className="max-w-xs text-body-sm text-on-surface-variant">Hangatkan setiap jarak dengan ucapan yang berkesan.</p>
                </div>
                <div className="grid grid-cols-2 gap-4 text-body-sm text-on-surface-variant">
                    <div className="flex flex-col gap-2">
                        <p className="text-label-sm font-bold uppercase tracking-wider text-primary">Navigasi</p>
                        <Link href={route('catalog.index')}>Katalog</Link>
                        <Link href={route('dashboard')}>Kartu Saya</Link>
                    </div>
                    <div className="flex flex-col gap-2">
                        <p className="text-label-sm font-bold uppercase tracking-wider text-primary">Dukungan</p>
                        {/* TODO: halaman Syarat & Ketentuan dan Kebijakan Privasi (wajib mencakup foto & data penerima). */}
                        <span>Syarat & Ketentuan</span>
                        <span>Kebijakan Privasi</span>
                    </div>
                </div>
                <p className="border-t border-outline-variant/30 pt-space-md text-center text-label-sm text-on-surface-variant">
                    © {new Date().getFullYear()} {brand}. Dibuat dengan penuh cinta di Indonesia. 🇮🇩
                </p>
            </footer>
        </PublicLayout>
    );
}
