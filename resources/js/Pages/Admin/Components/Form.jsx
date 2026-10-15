import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, useForm } from '@inertiajs/react';
import { useState } from 'react';

/**
 * Skema field diedit sebagai JSON. Tipe yang didukung: text, textarea, date, datetime,
 * image, images, audio, music, list, quiz, pin, toggle. Komponen baru juga perlu blok
 * React di resources/js/Components/Greeting/blocks/index.js.
 */
export default function Form({ component }) {
    const form = useForm({
        key: component?.key ?? '',
        name: component?.name ?? '',
        description: component?.description ?? '',
        icon: component?.icon ?? 'widgets',
        schema: component?.schema ?? [],
        permissions: component?.permissions ?? [],
        is_active: component?.is_active ?? true,
    });
    const [schemaText, setSchemaText] = useState(JSON.stringify(form.data.schema, null, 2));
    const [jsonError, setJsonError] = useState(null);

    const submit = (event) => {
        event.preventDefault();

        try {
            form.transform((data) => ({ ...data, schema: JSON.parse(schemaText) }));
            setJsonError(null);
        } catch {
            setJsonError('JSON skema tidak valid.');

            return;
        }

        component ? form.put(route('admin.components.update', component.id)) : form.post(route('admin.components.store'));
    };

    const input = (name, props = {}) => (
        <label className="flex flex-col gap-1">
            <span className="text-label-md">{props.label ?? name}</span>
            <input value={form.data[name]} onChange={(event) => form.setData(name, event.target.value)} className="rounded-xl border-surface-container" />
            {form.errors[name] && <span className="text-label-sm text-error">{form.errors[name]}</span>}
        </label>
    );

    return (
        <AuthenticatedLayout header={<h2 className="text-headline-sm text-on-surface">{component ? `Ubah ${component.name}` : 'Komponen Baru'}</h2>}>
            <Head title="Admin • Komponen" />
            <form onSubmit={submit} className="mx-auto max-w-3xl space-y-4 rounded-2xl bg-white p-6 shadow-card sm:mt-6">
                <div className="grid gap-4 sm:grid-cols-2">
                    {input('key', { label: 'Key (unik, dipakai di kode)' })}
                    {input('name', { label: 'Nama' })}
                    {input('icon', { label: 'Ikon Material Symbols' })}
                    {input('description', { label: 'Deskripsi' })}
                </div>
                <label className="flex flex-col gap-1">
                    <span className="text-label-md">Skema field (JSON)</span>
                    <textarea rows={14} value={schemaText} onChange={(event) => setSchemaText(event.target.value)} className="rounded-xl border-surface-container font-mono text-sm" />
                    {(jsonError || form.errors.schema) && <span className="text-label-sm text-error">{jsonError ?? form.errors.schema}</span>}
                    {Object.entries(form.errors)
                        .filter(([key]) => key.startsWith('schema.'))
                        .map(([key, error]) => (
                            <span key={key} className="text-label-sm text-error">
                                {key}: {error}
                            </span>
                        ))}
                </label>
                <label className="flex items-center gap-2">
                    <input
                        type="checkbox"
                        checked={form.data.permissions.includes('microphone')}
                        onChange={(event) => form.setData('permissions', event.target.checked ? ['microphone'] : [])}
                        className="rounded text-primary"
                    />
                    <span className="text-body-sm">Butuh izin mikrofon</span>
                </label>
                <label className="flex items-center gap-2">
                    <input type="checkbox" checked={form.data.is_active} onChange={(event) => form.setData('is_active', event.target.checked)} className="rounded text-primary" />
                    <span className="text-body-sm">Aktif</span>
                </label>
                <button type="submit" disabled={form.processing} className="rounded-full bg-primary px-6 py-2.5 text-label-lg text-on-primary">
                    Simpan
                </button>
            </form>
        </AuthenticatedLayout>
    );
}
