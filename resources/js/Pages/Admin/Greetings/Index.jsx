import Icon from '@/Components/Icon';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { dateTime } from '@/lib/format';
import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';

/**
 * Kartu yang sudah terbit. Admin bisa mencari lalu merevisi isinya (nama, kalimat, foto) lewat Studio;
 * setiap simpan menjadi revisi baru dan link kartu tetap sama.
 */
export default function Index({ greetings, search }) {
    const [query, setQuery] = useState(search);

    const submit = (event) => {
        event.preventDefault();
        router.get(route('admin.greetings.index'), query ? { q: query } : {}, { preserveState: true });
    };

    const title = (greeting) => (greeting.type === 'undangan' ? greeting.sender_name : `${greeting.sender_name ?? '-'} → ${greeting.recipient_name}`);

    return (
        <AuthenticatedLayout header={<h2 className="text-headline-sm text-on-surface">Kartu Terbit</h2>}>
            <Head title="Admin • Kartu" />
            <div className="mx-auto max-w-6xl space-y-4 p-4 sm:p-6">
                <form onSubmit={submit} className="flex gap-2">
                    <input
                        type="search"
                        value={query}
                        onChange={(event) => setQuery(event.target.value)}
                        placeholder="Cari link (mis. amanda), nama penerima/pengirim, atau email pemilik"
                        className="min-w-0 flex-1 rounded-full border-0 bg-white px-4 py-2.5 text-body-md shadow-card focus:ring-2 focus:ring-primary/40"
                    />
                    <button type="submit" className="flex items-center gap-1 rounded-full bg-primary px-4 text-label-md text-on-primary">
                        <Icon name="search" size={18} />
                        Cari
                    </button>
                </form>

                <div className="space-y-2">
                    {greetings.data.length === 0 && (
                        <p className="rounded-2xl bg-white px-4 py-8 text-center text-body-sm text-on-surface-variant shadow-card">Tidak ada kartu terbit yang cocok.</p>
                    )}
                    {greetings.data.map((greeting) => (
                        <div key={greeting.id} className="flex flex-col gap-3 rounded-2xl bg-white p-4 shadow-card sm:flex-row sm:items-center">
                            <div className="min-w-0 flex-1">
                                <p className="flex flex-wrap items-center gap-2">
                                    <span className="truncate text-label-lg text-on-surface">{title(greeting)}</span>
                                    <span className="rounded-full bg-surface-container px-2 py-0.5 text-label-sm text-on-surface-variant">
                                        {greeting.type === 'undangan' ? 'Undangan' : 'Ucapan'}
                                    </span>
                                </p>
                                <p className="truncate text-body-sm text-on-surface-variant">
                                    /u/{greeting.slug} • {greeting.template_name}
                                </p>
                                <p className="truncate text-label-sm text-on-surface-variant">
                                    {greeting.owner ?? 'Tanpa akun'} • terbit {greeting.published_at ? dateTime(greeting.published_at) : '-'}
                                </p>
                            </div>
                            <div className="flex shrink-0 gap-2">
                                <a
                                    href={greeting.public_url}
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    className="flex h-10 items-center gap-1 rounded-full bg-surface-container px-4 text-label-md text-on-surface"
                                >
                                    <Icon name="open_in_new" size={16} />
                                    Lihat
                                </a>
                                <Link href={route('studio.edit', greeting.id)} className="flex h-10 items-center gap-1 rounded-full bg-primary px-4 text-label-md text-on-primary">
                                    <Icon name="edit" size={16} />
                                    Edit isi
                                </Link>
                            </div>
                        </div>
                    ))}
                </div>

                <div className="flex flex-wrap gap-1">
                    {greetings.links.map((link, index) =>
                        link.url ? (
                            <Link
                                key={index}
                                href={link.url}
                                className={`rounded-lg px-3 py-1 text-label-md ${link.active ? 'bg-primary text-on-primary' : 'bg-white'}`}
                                dangerouslySetInnerHTML={{ __html: link.label }}
                            />
                        ) : (
                            <span key={index} className="px-3 py-1 text-label-md text-on-surface-variant" dangerouslySetInnerHTML={{ __html: link.label }} />
                        ),
                    )}
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
