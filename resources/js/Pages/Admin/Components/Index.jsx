import Icon from '@/Components/Icon';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link } from '@inertiajs/react';

export default function Index({ components }) {
    return (
        <AuthenticatedLayout header={<h2 className="text-headline-sm text-on-surface">Komponen Ucapan</h2>}>
            <Head title="Admin • Komponen" />
            <div className="mx-auto max-w-5xl space-y-4 p-4 sm:p-6">
                <Link href={route('admin.components.create')} className="inline-flex rounded-full bg-primary px-4 py-2 text-label-md text-on-primary">
                    + Komponen Baru
                </Link>
                <div className="grid gap-3 sm:grid-cols-2">
                    {components.map((component) => (
                        <Link
                            key={component.id}
                            href={route('admin.components.edit', component.id)}
                            className="flex items-start gap-3 rounded-2xl bg-white p-4 shadow-card hover:ring-2 hover:ring-primary/30"
                        >
                            <span className="flex h-10 w-10 items-center justify-center rounded-xl bg-primary-fixed text-primary">
                                <Icon name={component.icon} />
                            </span>
                            <div className="min-w-0">
                                <p className="text-label-lg">
                                    {component.name} <span className="text-label-sm text-on-surface-variant">({component.key})</span>
                                </p>
                                <p className="text-body-sm text-on-surface-variant">{component.description}</p>
                                <p className="mt-1 text-label-sm text-on-surface-variant">
                                    {component.schema.length} field • {component.variants_count} varian {component.is_active ? '' : '• nonaktif'}
                                </p>
                            </div>
                        </Link>
                    ))}
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
