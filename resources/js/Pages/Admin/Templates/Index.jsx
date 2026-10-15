import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { rupiah } from '@/lib/format';
import { Head, Link, router } from '@inertiajs/react';

export default function Index({ templates }) {
    return (
        <AuthenticatedLayout header={<h2 className="text-headline-sm text-on-surface">Template</h2>}>
            <Head title="Admin • Template" />
            <div className="mx-auto max-w-5xl space-y-4 p-4 sm:p-6">
                <Link href={route('admin.templates.create')} className="inline-flex rounded-full bg-primary px-4 py-2 text-label-md text-on-primary">
                    + Template Baru
                </Link>
                <div className="overflow-x-auto rounded-2xl bg-white shadow-card">
                    <table className="min-w-full text-left text-body-sm">
                        <thead className="bg-surface-container-low text-label-md text-on-surface-variant">
                            <tr>
                                {['Seri / Desain', 'Kategori', 'Kelas', 'Harga', 'Komponen', 'Status', ''].map((heading) => (
                                    <th key={heading} className="px-4 py-3">
                                        {heading}
                                    </th>
                                ))}
                            </tr>
                        </thead>
                        <tbody>
                            {templates.map((template) => (
                                <tr key={template.id} className="border-t border-surface-container">
                                    <td className="px-4 py-3">
                                        <span className="flex items-center gap-2">
                                            <span className="h-4 w-4 shrink-0 rounded-full" style={{ background: `linear-gradient(135deg, ${template.theme.accent}, ${template.theme.gradient[2]})` }} />
                                            <span>
                                                <span className="block text-label-sm text-on-surface-variant">{template.series ?? 'Tanpa seri'}</span>
                                                <span className="font-semibold">{template.design_name ?? template.name}</span>
                                            </span>
                                        </span>
                                    </td>
                                    <td className="px-4 py-3">{template.category}</td>
                                    <td className="px-4 py-3">{template.tier}</td>
                                    <td className="px-4 py-3">{rupiah(template.price)}</td>
                                    <td className="px-4 py-3">{template.components_count}</td>
                                    <td className="px-4 py-3">{template.is_published ? 'Terbit' : 'Draf'}</td>
                                    <td className="space-x-3 whitespace-nowrap px-4 py-3">
                                        <Link href={route('admin.templates.edit', template.slug)} className="text-primary underline">
                                            Ubah
                                        </Link>
                                        <button type="button" onClick={() => router.post(route('admin.templates.duplicate', template.slug))} className="text-on-surface-variant underline">
                                            Duplikat
                                        </button>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
