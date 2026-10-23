import TemplateCover from '@/Components/TemplateCover';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { FONTS, MOTIFS, loadFont, paletteFromAccent } from '@/lib/theme';
import { Head, router, useForm } from '@inertiajs/react';
import { useEffect } from 'react';

function ColorInput({ label, value, onChange, error }) {
    return (
        <label className="flex flex-col gap-1">
            <span className="text-label-md">{label}</span>
            <span className="flex items-center gap-2 rounded-xl border border-surface-container px-2 py-1.5">
                <input type="color" value={value} onChange={(event) => onChange(event.target.value)} className="h-8 w-10 cursor-pointer rounded border-0 p-0" />
                <input value={value} onChange={(event) => onChange(event.target.value)} className="w-full border-0 p-0 font-mono text-body-sm focus:ring-0" />
            </span>
            {error && <span className="text-label-sm text-error">{error}</span>}
        </label>
    );
}

/**
 * Template = daftar komponen berurutan. Urutan baris di sini = urutan adegan untuk penerima.
 */
export default function Form({ template, categories, series, availableComponents, types, tiers, defaultTheme }) {
    useEffect(() => Object.keys(FONTS).forEach(loadFont), []);

    const form = useForm({
        category_id: template?.category_id ?? categories[0]?.id ?? '',
        series_id: template?.series_id ?? series[0]?.id ?? '',
        new_series_name: '',
        slug: template?.slug ?? '',
        name: template?.name ?? '',
        design_name: template?.design_name ?? '',
        tagline: template?.tagline ?? '',
        story: template?.story ?? '',
        description: template?.description ?? '',
        type: template?.type ?? 'ucapan',
        tier: template?.tier ?? 'interaktif',
        price: template?.price ?? 29000,
        duration_minutes: template?.duration_minutes ?? 3,
        theme: template?.theme ?? defaultTheme,
        cover_path: template?.cover_path ?? '',
        highlight_icon: template?.highlight_icon ?? '',
        highlight_label: template?.highlight_label ?? '',
        is_published: template?.is_published ?? false,
        sort_order: template?.sort_order ?? 0,
        components: (template?.components ?? []).map((slot) => ({
            component_id: slot.component_id,
            key: slot.key,
            title: slot.title ?? '',
            is_required: slot.is_required,
        })),
    });

    const setSlot = (index, patch) => form.setData('components', form.data.components.map((slot, slotIndex) => (slotIndex === index ? { ...slot, ...patch } : slot)));
    const moveSlot = (index, direction) => {
        const slots = [...form.data.components];
        const target = index + direction;

        if (target < 0 || target >= slots.length) {
            return;
        }

        [slots[index], slots[target]] = [slots[target], slots[index]];
        form.setData('components', slots);
    };

    const setTheme = (patch) => form.setData('theme', { ...form.data.theme, ...patch });
    const seriesName = series.find((item) => String(item.id) === String(form.data.series_id))?.name ?? form.data.new_series_name;

    const submit = (event) => {
        event.preventDefault();
        template ? form.put(route('admin.templates.update', template.slug)) : form.post(route('admin.templates.store'));
    };

    const field = (name, label, props = {}) => (
        <label className="flex flex-col gap-1">
            <span className="text-label-md">{label}</span>
            <input {...props} value={form.data[name]} onChange={(event) => form.setData(name, event.target.value)} className="rounded-xl border-surface-container" />
            {form.errors[name] && <span className="text-label-sm text-error">{form.errors[name]}</span>}
        </label>
    );

    return (
        <AuthenticatedLayout header={<h2 className="text-headline-sm text-on-surface">{template ? `Ubah ${template.name}` : 'Template Baru'}</h2>}>
            <Head title="Admin • Template" />
            <form onSubmit={submit} className="mx-auto max-w-4xl space-y-6 p-4 sm:p-6">
                {template && (
                    <div className="flex justify-end">
                        <button
                            type="button"
                            onClick={() => router.post(route('admin.templates.duplicate', template.slug))}
                            className="rounded-full bg-surface-container px-4 py-2 text-label-md"
                        >
                            ⧉ Duplikat jadi desain baru
                        </button>
                    </div>
                )}

                <section className="grid gap-4 rounded-2xl bg-white p-6 shadow-card sm:grid-cols-2">
                    <h3 className="text-headline-sm sm:col-span-2">Seri & desain</h3>
                    <label className="flex flex-col gap-1">
                        <span className="text-label-md">Seri (fitur)</span>
                        <select value={form.data.series_id} onChange={(event) => form.setData('series_id', event.target.value)} className="rounded-xl border-surface-container">
                            {series.map((item) => (
                                <option key={item.id} value={item.id}>
                                    {item.name}
                                </option>
                            ))}
                            <option value="">+ Seri baru…</option>
                        </select>
                        {form.errors.series_id && <span className="text-label-sm text-error">{form.errors.series_id}</span>}
                    </label>
                    {form.data.series_id === '' ? field('new_series_name', 'Nama seri baru', { placeholder: 'mis. Balon Terbang' }) : <span />}
                    {field('design_name', 'Nama desain', { placeholder: 'mis. Pastel Manis' })}
                    {field('name', 'Nama lengkap (dashboard & invoice)', { placeholder: seriesName ? `${seriesName} · ${form.data.design_name || '…'}` : '' })}
                    {field('slug', 'Slug URL')}
                    <label className="flex flex-col gap-1">
                        <span className="text-label-md">Kategori</span>
                        <select value={form.data.category_id} onChange={(event) => form.setData('category_id', event.target.value)} className="rounded-xl border-surface-container">
                            {categories.map((category) => (
                                <option key={category.id} value={category.id}>
                                    {category.name}
                                </option>
                            ))}
                        </select>
                    </label>
                    <label className="flex flex-col gap-1">
                        <span className="text-label-md">Jenis produk</span>
                        <select value={form.data.type} onChange={(event) => form.setData('type', event.target.value)} className="rounded-xl border-surface-container">
                            {types.map((type) => (
                                <option key={type.value} value={type.value}>
                                    {type.label}
                                </option>
                            ))}
                        </select>
                        <span className="text-label-sm text-on-surface-variant">
                            {form.data.type === 'undangan'
                                ? 'Undangan: banyak tamu, RSVP & amplop digital, tampil di katalog Undangan. Pakai komponen acara_undangan, rsvp, amplop_digital, ucapan_doa.'
                                : 'Ucapan: satu penerima, isi dibekukan setelah bayar.'}
                        </span>
                        {form.errors.type && <span className="text-label-sm text-error">{form.errors.type}</span>}
                    </label>
                    <label className="flex flex-col gap-1">
                        <span className="text-label-md">Kelas</span>
                        <select value={form.data.tier} onChange={(event) => form.setData('tier', event.target.value)} className="rounded-xl border-surface-container">
                            {tiers.map((tier) => (
                                <option key={tier.value} value={tier.value}>
                                    {tier.label}
                                </option>
                            ))}
                        </select>
                    </label>
                    {field('price', 'Harga Paket 1 (Rp)', { type: 'number', min: 1000 })}
                    {field('sort_order', 'Urutan', { type: 'number', min: 0 })}
                    {field('duration_minutes', 'Durasi (menit)', { type: 'number', min: 1, max: 30 })}
                    {field('tagline', 'Tagline')}
                    {field('story', 'Cerita singkat (kartu katalog)')}
                    {field('cover_path', 'Path / URL sampul')}
                    {field('highlight_icon', 'Ikon sorotan')}
                    {field('highlight_label', 'Label sorotan')}
                    <label className="flex flex-col gap-1 sm:col-span-2">
                        <span className="text-label-md">Deskripsi</span>
                        <textarea rows={3} value={form.data.description} onChange={(event) => form.setData('description', event.target.value)} className="rounded-xl border-surface-container" />
                    </label>
                    <label className="flex items-center gap-2">
                        <input type="checkbox" checked={form.data.is_published} onChange={(event) => form.setData('is_published', event.target.checked)} className="rounded text-primary" />
                        <span className="text-body-sm">Tampilkan di katalog</span>
                    </label>
                </section>

                <section className="grid gap-4 rounded-2xl bg-white p-6 shadow-card sm:grid-cols-[1fr_200px]">
                    <div className="grid gap-4 sm:grid-cols-2">
                        <h3 className="text-headline-sm sm:col-span-2">Tema visual</h3>
                        <ColorInput label="Warna utama" value={form.data.theme.accent} onChange={(accent) => setTheme({ accent })} error={form.errors['theme.accent']} />
                        <div className="flex items-end">
                            <button type="button" onClick={() => setTheme(paletteFromAccent(form.data.theme.accent))} className="rounded-full bg-surface-container px-3 py-2 text-label-sm">
                                ✨ Hitung warna lembut & latar otomatis
                            </button>
                        </div>
                        <ColorInput label="Warna lembut" value={form.data.theme.soft} onChange={(soft) => setTheme({ soft })} error={form.errors['theme.soft']} />
                        <label className="flex flex-col gap-1">
                            <span className="text-label-md">Emoji sampul</span>
                            <input value={form.data.theme.emoji} onChange={(event) => setTheme({ emoji: event.target.value })} className="rounded-xl border-surface-container text-xl" />
                        </label>
                        {form.data.theme.gradient.map((color, index) => (
                            <ColorInput
                                key={index}
                                label={`Latar ${index + 1}`}
                                value={color}
                                onChange={(value) => setTheme({ gradient: form.data.theme.gradient.map((item, itemIndex) => (itemIndex === index ? value : item)) })}
                                error={form.errors[`theme.gradient.${index}`]}
                            />
                        ))}
                        <label className="flex flex-col gap-1">
                            <span className="text-label-md">Hiasan</span>
                            <select value={form.data.theme.motif} onChange={(event) => setTheme({ motif: event.target.value })} className="rounded-xl border-surface-container">
                                {Object.entries(MOTIFS).map(([key, motif]) => (
                                    <option key={key} value={key}>
                                        {motif.emoji} {motif.label}
                                    </option>
                                ))}
                            </select>
                        </label>
                        <label className="flex flex-col gap-1">
                            <span className="text-label-md">Huruf judul</span>
                            <select value={form.data.theme.font} onChange={(event) => setTheme({ font: event.target.value })} className="rounded-xl border-surface-container">
                                {Object.entries(FONTS).map(([key, font]) => (
                                    <option key={key} value={key}>
                                        {font.label}
                                    </option>
                                ))}
                            </select>
                        </label>
                    </div>
                    <div className="flex flex-col gap-2">
                        <span className="text-label-md">Pratinjau sampul</span>
                        <div className="aspect-[4/5] overflow-hidden rounded-2xl shadow-card">
                            <TemplateCover template={{ name: form.data.name, theme: form.data.theme, highlight_icon: form.data.highlight_icon || null }} />
                        </div>
                        <p className="text-center text-headline-sm" style={{ fontFamily: FONTS[form.data.theme.font]?.family, color: form.data.theme.accent }}>
                            {form.data.design_name || 'Nama desain'}
                        </p>
                    </div>
                </section>

                <section className="space-y-3 rounded-2xl bg-white p-6 shadow-card">
                    <div className="flex items-center justify-between">
                        <h3 className="text-headline-sm">Susunan Komponen</h3>
                        <button
                            type="button"
                            onClick={() =>
                                form.setData('components', [
                                    ...form.data.components,
                                    { component_id: availableComponents[0]?.id, key: `slot_${form.data.components.length + 1}`, title: '', is_required: true },
                                ])
                            }
                            className="rounded-full bg-surface-container px-3 py-1.5 text-label-md"
                        >
                            + Tambah slot
                        </button>
                    </div>
                    {form.errors.components && <p className="text-label-sm text-error">{form.errors.components}</p>}
                    {form.data.components.map((slot, index) => (
                        <div key={index} className="grid grid-cols-[auto_1fr_1fr_1fr_auto_auto] items-center gap-2">
                            <span className="text-label-md text-on-surface-variant">{index + 1}.</span>
                            <select value={slot.component_id} onChange={(event) => setSlot(index, { component_id: Number(event.target.value) })} className="rounded-xl border-surface-container">
                                {availableComponents.map((component) => (
                                    <option key={component.id} value={component.id}>
                                        {component.name}
                                    </option>
                                ))}
                            </select>
                            <input placeholder="key slot" value={slot.key} onChange={(event) => setSlot(index, { key: event.target.value })} className="rounded-xl border-surface-container" />
                            <input placeholder="Judul (opsional)" value={slot.title} onChange={(event) => setSlot(index, { title: event.target.value })} className="rounded-xl border-surface-container" />
                            <label className="flex items-center gap-1 text-label-sm">
                                <input type="checkbox" checked={slot.is_required} onChange={(event) => setSlot(index, { is_required: event.target.checked })} className="rounded text-primary" />
                                Wajib
                            </label>
                            <div className="flex gap-1">
                                <button type="button" onClick={() => moveSlot(index, -1)} className="px-1">
                                    ↑
                                </button>
                                <button type="button" onClick={() => moveSlot(index, 1)} className="px-1">
                                    ↓
                                </button>
                                <button type="button" onClick={() => form.setData('components', form.data.components.filter((_, slotIndex) => slotIndex !== index))} className="px-1 text-error">
                                    ✕
                                </button>
                            </div>
                        </div>
                    ))}
                </section>

                <button type="submit" disabled={form.processing} className="rounded-full bg-primary px-6 py-2.5 text-label-lg text-on-primary">
                    Simpan Template
                </button>
            </form>
        </AuthenticatedLayout>
    );
}
