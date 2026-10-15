import BottomSheet from '@/Components/BottomSheet';
import Icon from '@/Components/Icon';
import { Link, router, useForm } from '@inertiajs/react';
import { useState } from 'react';

const MONTHS = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
const OCCASIONS = ['Ulang Tahun', 'Anniversary', 'Hari Jadi', 'Wisuda', 'Hari Ibu'];

const inputClass = 'w-full rounded-2xl border-0 bg-surface-container-low px-4 py-3 text-body-md text-on-surface focus:ring-2 focus:ring-primary/40';

/**
 * Pengingat momen orang tersayang: muncul di sini dan dikirim lewat email H-n.
 */
export default function MomentReminders({ reminders }) {
    const [isOpen, setIsOpen] = useState(false);
    const form = useForm({ person_name: '', occasion: 'Ulang Tahun', month: new Date().getMonth() + 1, day: 1, remind_days_before: 7 });

    const submit = (event) => {
        event.preventDefault();
        form.post(route('reminders.store'), {
            preserveScroll: true,
            onSuccess: () => {
                form.reset('person_name');
                setIsOpen(false);
            },
        });
    };

    return (
        <section className="px-margin pb-space-lg">
            <div className="mb-space-sm flex items-center justify-between">
                <h2 className="font-display text-headline-sm text-on-surface">Momen mendatang</h2>
                <button type="button" onClick={() => setIsOpen(true)} className="flex items-center gap-1 text-label-md text-primary">
                    <Icon name="add_alarm" size={18} />
                    Tambah
                </button>
            </div>

            {reminders.length === 0 ? (
                <button
                    type="button"
                    onClick={() => setIsOpen(true)}
                    className="flex w-full items-center gap-3 rounded-3xl bg-gradient-to-r from-primary-fixed to-tertiary-fixed p-4 text-left shadow-sm"
                >
                    <span className="text-3xl">📅</span>
                    <span>
                        <span className="block font-display text-label-lg text-on-surface">Jangan sampai lupa lagi</span>
                        <span className="block text-body-sm text-on-surface-variant">Simpan ulang tahun & hari spesial orang tersayang, kami ingatkan sebelum harinya.</span>
                    </span>
                </button>
            ) : (
                <div className="no-scrollbar -mx-margin flex gap-3 overflow-x-auto px-margin pb-1">
                    {reminders.map((reminder) => (
                        <div key={reminder.id} className="flex w-40 shrink-0 flex-col gap-1 rounded-3xl bg-surface-container-lowest p-4 shadow-card">
                            <div className="flex items-start justify-between">
                                <span
                                    className={`rounded-full px-2 py-0.5 text-label-sm ${
                                        reminder.days_until <= reminder.remind_days_before ? 'bg-primary text-on-primary' : 'bg-surface-container text-on-surface-variant'
                                    }`}
                                >
                                    {reminder.days_until === 0 ? 'Hari ini!' : `${reminder.days_until} hari lagi`}
                                </span>
                                <button
                                    type="button"
                                    aria-label="Hapus pengingat"
                                    onClick={() => confirm('Hapus pengingat ini?') && router.delete(route('reminders.destroy', reminder.id), { preserveScroll: true })}
                                    className="text-on-surface-variant"
                                >
                                    <Icon name="close" size={16} />
                                </button>
                            </div>
                            <p className="mt-1 truncate font-display text-label-lg text-on-surface">{reminder.person_name}</p>
                            <p className="text-body-sm text-on-surface-variant">
                                {reminder.occasion} • {reminder.day} {MONTHS[reminder.month - 1]}
                            </p>
                            <Link href={route('catalog.index')} className="mt-1 text-label-sm text-primary">
                                Siapkan kejutan →
                            </Link>
                        </div>
                    ))}
                </div>
            )}

            <BottomSheet open={isOpen} onClose={() => setIsOpen(false)} title="Pengingat momen" icon="event">
                <form onSubmit={submit} className="flex flex-col gap-3">
                    <input
                        type="text"
                        placeholder="Nama orangnya, misal: Ibu"
                        value={form.data.person_name}
                        onChange={(event) => form.setData('person_name', event.target.value)}
                        className={inputClass}
                    />
                    <div className="flex flex-wrap gap-2">
                        {OCCASIONS.map((occasion) => (
                            <button
                                key={occasion}
                                type="button"
                                onClick={() => form.setData('occasion', occasion)}
                                className={`rounded-full px-3 py-1.5 text-label-sm ${form.data.occasion === occasion ? 'bg-primary text-on-primary' : 'bg-surface-container text-on-surface'}`}
                            >
                                {occasion}
                            </button>
                        ))}
                    </div>
                    <div className="grid grid-cols-[90px_1fr] gap-2">
                        <input
                            type="number"
                            min={1}
                            max={31}
                            value={form.data.day}
                            onChange={(event) => form.setData('day', event.target.value)}
                            className={inputClass}
                            aria-label="Tanggal"
                        />
                        <select value={form.data.month} onChange={(event) => form.setData('month', event.target.value)} className={inputClass} aria-label="Bulan">
                            {MONTHS.map((month, index) => (
                                <option key={month} value={index + 1}>
                                    {month}
                                </option>
                            ))}
                        </select>
                    </div>
                    <label className="flex items-center justify-between gap-2 text-body-sm text-on-surface">
                        Ingatkan
                        <select
                            value={form.data.remind_days_before}
                            onChange={(event) => form.setData('remind_days_before', event.target.value)}
                            className="rounded-full border-0 bg-surface-container-low text-body-sm"
                        >
                            {[1, 3, 7, 14].map((days) => (
                                <option key={days} value={days}>
                                    {days} hari sebelumnya
                                </option>
                            ))}
                        </select>
                    </label>
                    {Object.values(form.errors).map((error) => (
                        <p key={error} className="text-label-sm text-error">
                            {error}
                        </p>
                    ))}
                    <button type="submit" disabled={form.processing || !form.data.person_name.trim()} className="h-12 rounded-full bg-primary text-label-lg text-on-primary shadow-md disabled:opacity-50">
                        Simpan pengingat
                    </button>
                </form>
            </BottomSheet>
        </section>
    );
}
