import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, router, useForm } from '@inertiajs/react';

const inputClass = 'min-w-0 rounded-lg border-surface-container';

/**
 * Di HP: emoji • nama • urutan di baris pertama, slug di baris kedua, tombol di baris ketiga.
 * Mulai `sm` semua sejajar satu baris seperti tabel.
 */
const rowGrid = 'grid grid-cols-[56px_minmax(0,1fr)_72px] items-center gap-2 sm:grid-cols-[60px_minmax(0,1fr)_minmax(0,1fr)_80px_auto]';

function CategoryRow({ category }) {
    const form = useForm({ name: category.name, slug: category.slug, emoji: category.emoji ?? '', sort_order: category.sort_order });

    return (
        <form
            onSubmit={(event) => {
                event.preventDefault();
                form.put(route('admin.categories.update', category.id), { preserveScroll: true });
            }}
            className={`${rowGrid} border-t border-surface-container px-4 py-3 sm:py-2`}
        >
            <input aria-label="Emoji" value={form.data.emoji} onChange={(event) => form.setData('emoji', event.target.value)} className={`${inputClass} text-center`} />
            <input aria-label="Nama" value={form.data.name} onChange={(event) => form.setData('name', event.target.value)} className={inputClass} />
            <input
                aria-label="Urutan"
                type="number"
                value={form.data.sort_order}
                onChange={(event) => form.setData('sort_order', event.target.value)}
                className={`${inputClass} sm:order-4`}
            />
            <input
                aria-label="Slug"
                value={form.data.slug}
                onChange={(event) => form.setData('slug', event.target.value)}
                className={`${inputClass} col-span-3 sm:order-3 sm:col-span-1`}
            />
            <div className="col-span-3 flex items-center justify-end gap-3 sm:order-5 sm:col-span-1">
                <button
                    type="button"
                    disabled={category.templates_count > 0}
                    title={category.templates_count > 0 ? 'Masih dipakai template' : ''}
                    onClick={() => confirm('Hapus kategori?') && router.delete(route('admin.categories.destroy', category.id), { preserveScroll: true })}
                    className="px-2 py-2 text-label-md text-error disabled:opacity-40"
                >
                    Hapus
                </button>
                <button type="submit" className="rounded-full bg-primary px-4 py-2 text-label-md text-on-primary">
                    Simpan
                </button>
            </div>
            {Object.values(form.errors).map((error) => (
                <p key={error} className="col-span-3 text-label-sm text-error sm:order-last sm:col-span-5">
                    {error}
                </p>
            ))}
        </form>
    );
}

export default function Index({ categories }) {
    const form = useForm({ name: '', slug: '', emoji: '', sort_order: categories.length });

    return (
        <AuthenticatedLayout header={<h2 className="text-headline-sm text-on-surface">Kategori Momen</h2>}>
            <Head title="Admin • Kategori" />
            <div className="mx-auto max-w-4xl space-y-4 p-4 sm:p-6">
                <div className="rounded-2xl bg-white shadow-card">
                    <div className={`${rowGrid} hidden px-4 py-3 text-label-md text-on-surface-variant sm:grid`}>
                        <span>Emoji</span>
                        <span>Nama</span>
                        <span className="sm:order-4">Urutan</span>
                        <span className="sm:order-3">Slug</span>
                    </div>
                    {categories.map((category) => (
                        <CategoryRow key={category.id} category={category} />
                    ))}
                </div>

                <form
                    onSubmit={(event) => {
                        event.preventDefault();
                        form.post(route('admin.categories.store'), { onSuccess: () => form.reset() });
                    }}
                    className={`${rowGrid} rounded-2xl bg-white p-4 shadow-card`}
                >
                    <input placeholder="🎂" aria-label="Emoji" value={form.data.emoji} onChange={(event) => form.setData('emoji', event.target.value)} className={`${inputClass} text-center`} />
                    <input placeholder="Nama" value={form.data.name} onChange={(event) => form.setData('name', event.target.value)} className={inputClass} />
                    <input
                        aria-label="Urutan"
                        type="number"
                        value={form.data.sort_order}
                        onChange={(event) => form.setData('sort_order', event.target.value)}
                        className={`${inputClass} sm:order-4`}
                    />
                    <input placeholder="slug" value={form.data.slug} onChange={(event) => form.setData('slug', event.target.value)} className={`${inputClass} col-span-3 sm:order-3 sm:col-span-1`} />
                    <button type="submit" className="col-span-3 rounded-full bg-primary px-4 py-2.5 text-label-md text-on-primary sm:order-5 sm:col-span-1">
                        Tambah
                    </button>
                    {Object.values(form.errors).map((error) => (
                        <p key={error} className="col-span-3 text-label-sm text-error sm:order-last sm:col-span-5">
                            {error}
                        </p>
                    ))}
                </form>
            </div>
        </AuthenticatedLayout>
    );
}
