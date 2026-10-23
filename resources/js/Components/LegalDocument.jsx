import Icon from '@/Components/Icon';
import PublicLayout from '@/Layouts/PublicLayout';
import { Link } from '@inertiajs/react';

/**
 * Kerangka halaman dokumen legal: judul, tanggal pembaruan, daftar isi, dan bagian bernomor.
 *
 * @param {{ id: string, title: string, body: import('react').ReactNode }[]} sections
 */
export default function LegalDocument({ title, intro, updatedAt, sections, supportEmail, supportWhatsapp }) {
    const updated = updatedAt ? new Intl.DateTimeFormat('id-ID', { dateStyle: 'long' }).format(new Date(updatedAt)) : null;

    return (
        <PublicLayout title={title} back={{ href: route('home'), label: 'Beranda' }}>
            <article className="px-margin py-space-lg">
                <header className="mb-space-lg">
                    <p className="text-label-sm uppercase tracking-[0.25em] text-primary">Legal</p>
                    <h1 className="mt-1 font-display text-headline-lg-mobile text-on-surface">{title}</h1>
                    {updated && <p className="mt-1 text-label-sm text-on-surface-variant">Terakhir diperbarui {updated}</p>}
                    <p className="mt-3 text-body-md leading-relaxed text-on-surface-variant">{intro}</p>
                </header>

                <nav className="mb-space-lg rounded-3xl bg-surface-container-low p-4">
                    <p className="mb-2 text-label-md text-on-surface">Daftar isi</p>
                    <ol className="grid gap-1 text-body-sm sm:grid-cols-2">
                        {sections.map((section, index) => (
                            <li key={section.id}>
                                <a href={`#${section.id}`} className="text-primary hover:underline">
                                    {index + 1}. {section.title}
                                </a>
                            </li>
                        ))}
                    </ol>
                </nav>

                <div className="flex flex-col gap-space-lg">
                    {sections.map((section, index) => (
                        <section key={section.id} id={section.id} className="scroll-mt-24">
                            <h2 className="mb-2 font-display text-headline-sm text-on-surface">
                                {index + 1}. {section.title}
                            </h2>
                            <div className="flex flex-col gap-2 text-body-md leading-relaxed text-on-surface-variant [&_li]:ml-5 [&_li]:list-disc [&_strong]:text-on-surface">
                                {section.body}
                            </div>
                        </section>
                    ))}
                </div>

                <footer className="mt-space-xl flex flex-col gap-3 rounded-3xl bg-surface-container-lowest p-5 shadow-card">
                    <p className="font-display text-label-lg text-on-surface">Ada pertanyaan?</p>
                    <div className="flex flex-wrap gap-2">
                        {supportEmail && (
                            <a href={`mailto:${supportEmail}`} className="inline-flex items-center gap-1 rounded-full bg-surface-container px-4 py-2 text-label-md text-on-surface">
                                <Icon name="mail" size={18} className="text-primary" />
                                {supportEmail}
                            </a>
                        )}
                        {supportWhatsapp && (
                            <a
                                href={`https://wa.me/${supportWhatsapp.replace(/\D/g, '')}`}
                                className="inline-flex items-center gap-1 rounded-full bg-whatsapp px-4 py-2 text-label-md text-white"
                            >
                                <Icon name="chat" size={18} />
                                WhatsApp
                            </a>
                        )}
                    </div>
                    <p className="text-label-sm text-on-surface-variant">
                        Baca juga{' '}
                        <Link href={route(route().current('legal.privacy') ? 'legal.terms' : 'legal.privacy')} className="text-primary underline">
                            {route().current('legal.privacy') ? 'Syarat & Ketentuan' : 'Kebijakan Privasi'}
                        </Link>
                        .
                    </p>
                </footer>
            </article>
        </PublicLayout>
    );
}
