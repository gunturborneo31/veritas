import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, router, useForm } from '@inertiajs/react';

function CategoryRow({ category }) {
    const form = useForm({ name: category.name, slug: category.slug, emoji: category.emoji ?? '', sort_order: category.sort_order });

    return (
        <form
            onSubmit={(event) => {
                event.preventDefault();
                form.put(route('admin.categories.update', category.id), { preserveScroll: true });
            }}
            className="grid grid-cols-[60px_1fr_1fr_80px_auto_auto] items-center gap-2 border-t border-surface-container px-4 py-2"
        >
            <input value={form.data.emoji} onChange={(event) => form.setData('emoji', event.target.value)} className="rounded-lg border-surface-container" />
            <input value={form.data.name} onChange={(event) => form.setData('name', event.target.value)} className="rounded-lg border-surface-container" />
            <input value={form.data.slug} onChange={(event) => form.setData('slug', event.target.value)} className="rounded-lg border-surface-container" />
            <input type="number" value={form.data.sort_order} onChange={(event) => form.setData('sort_order', event.target.value)} className="rounded-lg border-surface-container" />
            <button type="submit" className="rounded-full bg-primary px-3 py-1.5 text-label-md text-on-primary">
                Simpan
            </button>
            <button
                type="button"
                disabled={category.templates_count > 0}
                title={category.templates_count > 0 ? 'Masih dipakai template' : ''}
                onClick={() => confirm('Hapus kategori?') && router.delete(route('admin.categories.destroy', category.id), { preserveScroll: true })}
                className="text-label-md text-error disabled:opacity-40"
            >
                Hapus
            </button>
            {Object.values(form.errors).map((error) => (
                <p key={error} className="col-span-6 text-label-sm text-error">
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
                    <div className="grid grid-cols-[60px_1fr_1fr_80px_auto_auto] gap-2 px-4 py-3 text-label-md text-on-surface-variant">
                        <span>Emoji</span>
                        <span>Nama</span>
                        <span>Slug</span>
                        <span>Urutan</span>
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
                    className="grid grid-cols-[60px_1fr_1fr_80px_auto] items-center gap-2 rounded-2xl bg-white p-4 shadow-card"
                >
                    <input placeholder="🎂" value={form.data.emoji} onChange={(event) => form.setData('emoji', event.target.value)} className="rounded-lg border-surface-container" />
                    <input placeholder="Nama" value={form.data.name} onChange={(event) => form.setData('name', event.target.value)} className="rounded-lg border-surface-container" />
                    <input placeholder="slug" value={form.data.slug} onChange={(event) => form.setData('slug', event.target.value)} className="rounded-lg border-surface-container" />
                    <input type="number" value={form.data.sort_order} onChange={(event) => form.setData('sort_order', event.target.value)} className="rounded-lg border-surface-container" />
                    <button type="submit" className="rounded-full bg-primary px-4 py-2 text-label-md text-on-primary">
                        Tambah
                    </button>
                    {Object.values(form.errors).map((error) => (
                        <p key={error} className="col-span-5 text-label-sm text-error">
                            {error}
                        </p>
                    ))}
                </form>
            </div>
        </AuthenticatedLayout>
    );
}
