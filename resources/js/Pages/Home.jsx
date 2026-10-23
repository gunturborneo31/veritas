import Brand from '@/Components/Brand';
import PhoneFrame from '@/Components/Greeting/PhoneFrame';
import Player from '@/Components/Greeting/Player';
import Icon from '@/Components/Icon';
import TemplateCard from '@/Components/TemplateCard';
import PublicLayout from '@/Layouts/PublicLayout';
import { Link, usePage } from '@inertiajs/react';

const PROMISES = [
    ['visibility', 'Coba gratis', 'Isi & pratinjau tanpa daftar'],
    ['smartphone', 'Tanpa aplikasi', 'Cukup link & QR via WhatsApp'],
    ['lock', 'Bisa dikunci PIN', 'Hanya dia yang bisa membuka'],
];

const STEPS = [
    ['palette', 'bg-primary-fixed text-primary', '1. Pilih Konsep', 'Pilih template interaktif yang pas dengan momenmu: tiup lilin, kartu gosok, hingga surat bergembok.'],
    ['edit_note', 'bg-tertiary-fixed text-tertiary', '2. Isi & Personalisasi', 'Tulis pesan dari hati, tambahkan foto bersama, lagu, hingga rekaman suaramu. Tanpa perlu daftar dulu.'],
    ['send', 'bg-secondary-fixed text-on-surface', '3. Bagikan Sekejap', 'Kirim tautan via WhatsApp atau QR Code. Penerima langsung membuka tanpa install aplikasi.'],
];

const numberFormatter = new Intl.NumberFormat('id-ID');

/**
 * Demo kartu asli di hero: pengunjung langsung merasakan interaksinya, bukan sekadar membaca.
 */
function ShowcasePhone({ showcase }) {
    return (
        <div className="relative flex w-full flex-col items-center gap-3">
            <PhoneFrame className="max-w-[300px]" screenClassName="h-[540px] lg:h-[600px]">
                <Player greeting={showcase.greeting} mode="demo" watermark />
            </PhoneFrame>
            <div className="flex items-center gap-1.5 text-label-md text-on-surface-variant">
                <Icon name="touch_app" size={18} className="text-primary" />
                Ini kartu sungguhan — coba ketuk &amp; geser
            </div>
            <Link href={route('catalog.show', showcase.template.slug)} className="text-label-md text-primary underline-offset-4 hover:underline">
                {showcase.template.series?.name ?? showcase.template.name} • lihat detail
            </Link>
        </div>
    );
}

/**
 * Beranda (desain promptgayaumum / Jelajah).
 */
export default function Home({ categories, featured, showcase, stats }) {
    const { brand } = usePage().props;

    return (
        <PublicLayout title="Ucapan Digital Interaktif" wide>
            <section className="relative grid items-center gap-space-xl overflow-hidden px-margin pb-space-xl pt-space-lg md:grid-cols-[1.1fr_1fr] md:pt-space-xl">
                <div className="pointer-events-none absolute -left-16 -top-12 h-56 w-56 rounded-full bg-primary-fixed/40 blur-3xl md:h-80 md:w-80" />

                <div className="relative flex flex-col items-center text-center md:items-start md:text-left">
                    <div className="mb-space-md inline-flex items-center gap-1.5 rounded-full bg-surface-container px-3 py-1 text-label-sm text-primary">
                        <Icon name="auto_awesome" filled size={15} className="text-tertiary" />
                        Kirim Rasa, Bukan Cuma Pesan Singkat
                    </div>
                    <h1 className="mb-space-md max-w-md text-display-hero-mobile text-on-surface md:max-w-lg md:text-display-hero lg:text-[52px] lg:leading-[60px]">
                        Ucapan yang bikin dia <span className="text-primary underline decoration-tertiary-fixed-dim decoration-4 underline-offset-4">berhenti sejenak</span>
                    </h1>
                    <p className="mb-space-lg max-w-sm text-body-md text-on-surface-variant md:max-w-md md:text-body-lg">
                        Bukan sekadar teks biasa. Kirim kartu digital penuh interaksi—bisa ditiup lilinnya, digosok kartunya, hingga diputar lagunya.
                    </p>
                    <div className="flex w-full max-w-sm flex-col gap-2 sm:flex-row md:max-w-none">
                        <Link
                            href={route('catalog.index')}
                            className="inline-flex h-12 items-center justify-center gap-2 rounded-full bg-primary px-space-lg text-label-lg text-on-primary shadow-md shadow-primary/25 transition-all active:scale-95"
                        >
                            Pilih Kartu
                            <Icon name="arrow_forward" size={20} />
                        </Link>
                        {showcase && (
                            <Link
                                href={route('catalog.demo', showcase.template.slug)}
                                className="inline-flex h-12 items-center justify-center gap-2 rounded-full bg-surface-container-lowest px-space-lg text-label-lg text-on-surface shadow-sm transition-all active:scale-95 md:hidden"
                            >
                                <Icon name="play_circle" size={20} />
                                Coba Demo Layar Penuh
                            </Link>
                        )}
                    </div>

                    <ul className="mt-space-lg grid w-full max-w-sm gap-3 text-left sm:max-w-none sm:grid-cols-3">
                        {PROMISES.map(([icon, title, body]) => (
                            <li key={title} className="flex items-center gap-3 sm:flex-col sm:items-start sm:gap-1.5">
                                <span className="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-surface-container-lowest text-primary shadow-sm">
                                    <Icon name={icon} size={20} />
                                </span>
                                <span>
                                    <span className="block text-label-md text-on-surface">{title}</span>
                                    <span className="block text-body-sm text-on-surface-variant">{body}</span>
                                </span>
                            </li>
                        ))}
                    </ul>
                </div>

                {showcase && <ShowcasePhone showcase={showcase} />}
            </section>

            {stats && (
                <section className="px-margin pb-space-lg">
                    <div className="grid grid-cols-2 divide-x divide-outline-variant/40 rounded-3xl bg-surface-container-lowest py-space-md text-center shadow-card">
                        <div>
                            <p className="font-display text-headline-lg-mobile text-primary md:text-headline-lg">{numberFormatter.format(stats.sent)}+</p>
                            <p className="text-body-sm text-on-surface-variant">ucapan sudah terkirim</p>
                        </div>
                        <div>
                            <p className="font-display text-headline-lg-mobile text-primary md:text-headline-lg">{numberFormatter.format(stats.replies)}</p>
                            <p className="text-body-sm text-on-surface-variant">balasan dari penerima</p>
                        </div>
                    </div>
                </section>
            )}

            <section className="flex flex-col gap-space-md px-margin py-space-md">
                <div className="flex items-end justify-between">
                    <div>
                        <p className="text-label-sm uppercase tracking-wider text-primary">Pilihan Kami</p>
                        <h2 className="text-headline-md text-on-surface md:text-headline-lg">Kartu Paling Disukai</h2>
                    </div>
                    <Link href={route('catalog.index')} className="flex items-center gap-0.5 text-label-md text-primary">
                        Semua
                        <Icon name="chevron_right" size={18} />
                    </Link>
                </div>

                <div className="no-scrollbar -mx-margin flex gap-2 overflow-x-auto px-margin py-1">
                    {categories.map((category) => (
                        <Link
                            key={category.slug}
                            href={route('catalog.index', { jenis: category.jenis ?? undefined, kategori: category.slug })}
                            className="flex h-9 shrink-0 items-center gap-1.5 rounded-full bg-surface-container-lowest px-3.5 text-label-md text-on-surface shadow-sm hover:bg-surface-container"
                        >
                            {category.emoji} {category.name}
                        </Link>
                    ))}
                </div>

                <div className="grid grid-cols-2 gap-3 md:grid-cols-3">
                    {featured.map((template, index) => {
                        const isOrphan = featured.length % 2 === 1 && index === featured.length - 1;

                        return (
                            <div key={template.slug} className={isOrphan ? 'col-span-2 md:col-span-1' : ''}>
                                <TemplateCard template={template} wide={isOrphan} />
                            </div>
                        );
                    })}
                </div>
            </section>

            <section className="my-space-md flex flex-col gap-space-md bg-surface-container-low/70 px-margin py-space-xl md:rounded-[2rem]">
                <div className="text-center">
                    <p className="text-label-sm uppercase tracking-wider text-primary">Mudah & Menyenangkan</p>
                    <h2 className="text-headline-md text-on-surface md:text-headline-lg">Cara Kerja {brand}</h2>
                </div>
                <div className="grid gap-space-md md:grid-cols-3">
                    {STEPS.map(([icon, tone, title, body]) => (
                        <div key={title} className="flex items-start gap-4 rounded-3xl bg-surface-container-lowest p-5 shadow-sm md:flex-col">
                            <div className={`flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl ${tone}`}>
                                <Icon name={icon} size={26} />
                            </div>
                            <div className="flex flex-col gap-1">
                                <h3 className="text-headline-sm text-on-surface">{title}</h3>
                                <p className="text-body-sm text-on-surface-variant">{body}</p>
                            </div>
                        </div>
                    ))}
                </div>
                <Link
                    href={route('catalog.index')}
                    className="mx-auto inline-flex h-12 items-center justify-center gap-2 rounded-full bg-primary px-space-lg text-label-lg text-on-primary shadow-md shadow-primary/25 transition-all active:scale-95"
                >
                    Mulai Buat Kartu
                    <Icon name="arrow_forward" size={20} />
                </Link>
            </section>

            <footer className="mt-space-lg flex flex-col gap-space-lg rounded-t-3xl bg-surface-container-high/60 px-margin pb-space-xl pt-space-lg md:grid md:grid-cols-[1.5fr_1fr_1fr]">
                <div className="flex flex-col gap-2">
                    <Brand />
                    <p className="max-w-xs text-body-sm text-on-surface-variant">Hangatkan setiap jarak dengan ucapan yang berkesan.</p>
                </div>
                <div className="flex flex-col gap-2 text-body-sm text-on-surface-variant">
                    <p className="text-label-sm font-bold uppercase tracking-wider text-primary">Navigasi</p>
                    <Link href={route('catalog.index')}>Katalog</Link>
                    <Link href={route('dashboard')}>Kartu Saya</Link>
                </div>
                <div className="flex flex-col gap-2 text-body-sm text-on-surface-variant">
                    <p className="text-label-sm font-bold uppercase tracking-wider text-primary">Dukungan</p>
                    <Link href={route('legal.terms')}>Syarat & Ketentuan</Link>
                    <Link href={route('legal.privacy')}>Kebijakan Privasi</Link>
                </div>
                <p className="border-t border-outline-variant/30 pt-space-md text-center text-label-sm text-on-surface-variant md:col-span-3">
                    © {new Date().getFullYear()} {brand}. Dibuat dengan penuh cinta di Indonesia. 🇮🇩
                </p>
            </footer>
        </PublicLayout>
    );
}
