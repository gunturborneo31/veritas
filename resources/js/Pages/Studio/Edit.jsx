import Icon from '@/Components/Icon';
import FieldInput from '@/Components/Greeting/fields/FieldInput';
import { toPresentedValues, toRawValues } from '@/Components/Greeting/fields/upload';
import PhoneFrame from '@/Components/Greeting/PhoneFrame';
import Player from '@/Components/Greeting/Player';
import CollaborationPanel from '@/Components/Studio/CollaborationPanel';
import StudioLayout from '@/Layouts/StudioLayout';
import { time } from '@/lib/format';
import { router, usePage } from '@inertiajs/react';
import { useCallback, useEffect, useMemo, useRef, useState } from 'react';

const AUTOSAVE_DELAY = 1200;

const pad = (number) => String(number).padStart(2, '0');

/** Format nilai input datetime-local (zona waktu perangkat). */
const toLocalInput = (date) => `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}T${pad(date.getHours())}:${pad(date.getMinutes())}`;

const nextMidnight = () => {
    const date = new Date();
    date.setHours(24, 0, 0, 0);

    return date;
};

const tomorrowAt = (hour) => {
    const date = new Date();
    date.setDate(date.getDate() + 1);
    date.setHours(hour, 0, 0, 0);

    return date;
};

const inputClass =
    'w-full rounded-2xl border-0 bg-surface-container-low px-4 py-3 text-body-md text-on-surface placeholder:text-on-surface-variant/60 focus:bg-surface-container-lowest focus:ring-2 focus:ring-primary/40';

function SectionCard({ number, icon, title, isComplete, children, defaultOpen = true }) {
    const [isOpen, setIsOpen] = useState(defaultOpen);

    return (
        <section className="space-y-space-md rounded-3xl bg-surface-container-lowest p-space-lg shadow-[0_4px_24px_rgba(46,26,44,0.05)]">
            <button type="button" onClick={() => setIsOpen((open) => !open)} className="flex w-full items-center justify-between text-left">
                <div className="flex items-center gap-2.5">
                    <div className="flex h-8 w-8 items-center justify-center rounded-full bg-surface-container-low text-primary">
                        <Icon name={icon} size={18} />
                    </div>
                    <h3 className="text-headline-sm font-bold text-on-surface">
                        {number}. {title}
                    </h3>
                </div>
                <div className="flex items-center gap-2">
                    {isComplete && (
                        <span className="flex h-6 w-6 items-center justify-center rounded-full bg-primary-fixed text-on-primary-fixed-variant">
                            <Icon name="check" size={14} />
                        </span>
                    )}
                    <Icon name={isOpen ? 'expand_less' : 'expand_more'} size={20} className="text-on-surface-variant" />
                </div>
            </button>
            {isOpen && <div className="space-y-3.5">{children}</div>}
        </section>
    );
}

export default function Edit({ greeting, template, occasions, missing, musicTracks, collaboration }) {
    const { errors } = usePage().props;
    const initial = useMemo(() => toRawValues(greeting.components, greeting.values), []); // eslint-disable-line react-hooks/exhaustive-deps

    const [base, setBase] = useState({
        recipient_name: greeting.base.recipient_name ?? '',
        sender_name: greeting.base.sender_name ?? '',
        occasion: greeting.base.occasion ?? '',
        opens_at: greeting.opens_at ? toLocalInput(new Date(greeting.opens_at)) : '',
    });
    const [values, setValues] = useState(initial.values);
    const [mediaCache, setMediaCache] = useState(initial.mediaCache);
    const [pinState, setPinState] = useState(() =>
        Object.fromEntries(greeting.components.filter((slot) => slot.component === 'gembok_pin').map((slot) => [slot.key, Boolean(greeting.gate)])),
    );
    const [saveState, setSaveState] = useState({ tone: 'saved', label: greeting.last_edited_at ? `Tersimpan • ${time(greeting.last_edited_at)}` : 'Tersimpan otomatis' });
    const [showPreview, setShowPreview] = useState(false);
    const isFirstRender = useRef(true);
    const timer = useRef(null);

    const payload = useCallback(
        () => ({ ...base, opens_at: base.opens_at ? new Date(base.opens_at).toISOString() : null, content: { components: values } }),
        [base, values],
    );

    const save = useCallback(
        (options = {}) => {
            clearTimeout(timer.current);
            const sentValues = values;

            router.put(route('studio.update', greeting.id), payload(), {
                preserveScroll: true,
                preserveState: true,
                only: ['missing', 'errors', 'flash'],
                onStart: () => setSaveState({ tone: 'saving', label: 'Menyimpan…' }),
                onSuccess: () => {
                    setSaveState({ tone: 'saved', label: `Tersimpan • ${time(new Date())}` });
                    const savedPins = greeting.components.filter((slot) => slot.component === 'gembok_pin' && sentValues[slot.key]?.pin);

                    if (savedPins.length > 0) {
                        setPinState((state) => ({ ...state, ...Object.fromEntries(savedPins.map((slot) => [slot.key, true])) }));
                        setValues((current) => {
                            const next = { ...current };
                            savedPins.forEach((slot) => (next[slot.key] = { ...next[slot.key], pin: '' }));

                            return next;
                        });
                    }

                    options.onSuccess?.();
                },
                onError: () => setSaveState({ tone: 'error', label: 'Periksa isian' }),
            });
        },
        [greeting.id, greeting.components, payload, values],
    );

    useEffect(() => {
        if (isFirstRender.current) {
            isFirstRender.current = false;

            return;
        }

        setSaveState({ tone: 'saving', label: 'Belum tersimpan' });
        timer.current = setTimeout(() => save(), AUTOSAVE_DELAY);

        return () => clearTimeout(timer.current);
    }, [base, values]); // eslint-disable-line react-hooks/exhaustive-deps

    const setField = (slotKey, fieldName, value) => setValues((current) => ({ ...current, [slotKey]: { ...current[slotKey], [fieldName]: value } }));
    const addMedia = (media) => setMediaCache((cache) => ({ ...cache, [media.id]: media }));

    const missingKeys = new Set(missing.map((item) => `${item.slot}.${item.field}`));
    const requiredCount = greeting.components.reduce((count, slot) => count + slot.schema.filter((field) => field.required).length, 2);
    const completeCount = Math.max(0, requiredCount - missing.length);
    const editableSlots = greeting.components;

    const livePreview = {
        ...greeting,
        base,
        values: toPresentedValues(greeting.components, values, mediaCache, musicTracks, pinState),
    };

    return (
        <StudioLayout
            title="Kustomisasi Kartu"
            step={base.recipient_name ? 1 : 0}
            saveState={saveState}
            onBack={() => router.visit(route('catalog.show', template.slug))}
            actions={
                <>
                    <button
                        type="button"
                        onClick={() => setShowPreview(true)}
                        className="flex h-12 items-center justify-center gap-1 rounded-full bg-surface-container px-space-lg text-label-lg text-on-surface hover:bg-surface-container-high"
                    >
                        <Icon name="visibility" size={18} />
                        Lihat
                    </button>
                    <button
                        type="button"
                        onClick={() => save({ onSuccess: () => router.visit(route('studio.style', greeting.id)) })}
                        className="flex h-12 flex-1 items-center justify-center gap-space-xs rounded-full bg-primary px-space-lg text-label-lg text-on-primary shadow-[0_4px_16px_rgba(168,50,35,0.25)] hover:bg-primary-container active:scale-[0.98]"
                    >
                        <span>Lanjut: Gaya</span>
                        <Icon name="arrow_forward" size={18} />
                    </button>
                </>
            }
        >
            <div className="flex w-full flex-col space-y-space-lg px-margin pb-10">
                <div className="flex flex-col space-y-1.5">
                    <div className="flex items-center justify-between">
                        <div className="inline-flex items-center gap-1.5 rounded-full bg-surface-container-low px-3 py-1 text-on-surface-variant">
                            <Icon name={template.highlight_icon ?? 'auto_awesome'} size={16} className="text-tertiary-container" />
                            <span className="text-label-sm font-semibold">{template.name}</span>
                        </div>
                        <span className="text-label-sm font-bold text-primary">
                            {completeCount}/{requiredCount} Lengkap
                        </span>
                    </div>
                    <h2 className="pt-1 text-headline-lg-mobile font-bold tracking-tight text-on-surface">Personalisasi Isi Kartu</h2>
                    <p className="text-body-sm text-on-surface-variant">Tuliskan kata terindah dan pilih momen tak terlupakan. Semua tersimpan otomatis.</p>
                </div>

                <SectionCard number={1} icon="badge" title="Nama & Momen Spesial" isComplete={Boolean(base.recipient_name && base.occasion)}>
                    <div>
                        <label className="mb-1 block text-label-md font-semibold text-on-surface">
                            Nama Panggilan Penerima <span className="text-primary">*</span>
                        </label>
                        <input
                            type="text"
                            maxLength={80}
                            value={base.recipient_name}
                            placeholder="Misal: Sarah"
                            onChange={(event) => setBase({ ...base, recipient_name: event.target.value })}
                            className={inputClass}
                        />
                        {errors.recipient_name && <span className="text-label-sm text-error">{errors.recipient_name}</span>}
                    </div>
                    <div>
                        <label className="mb-1 block text-label-md font-semibold text-on-surface">Dari Siapa?</label>
                        <input
                            type="text"
                            maxLength={80}
                            value={base.sender_name}
                            placeholder="Misal: Sahabatmu, Nadia"
                            onChange={(event) => setBase({ ...base, sender_name: event.target.value })}
                            className={inputClass}
                        />
                    </div>
                    <div>
                        <label className="mb-2 block text-label-md font-semibold text-on-surface">
                            Pilih Momen Ucapan <span className="text-primary">*</span>
                        </label>
                        <div className="flex flex-wrap gap-2">
                            {occasions.map((occasion) => (
                                <button
                                    key={occasion}
                                    type="button"
                                    onClick={() => setBase({ ...base, occasion })}
                                    className={`rounded-full px-4 py-2 text-label-md ${
                                        base.occasion === occasion ? 'bg-primary text-on-primary shadow-sm' : 'bg-surface-container text-on-surface hover:bg-surface-container-high'
                                    }`}
                                >
                                    {occasion}
                                </button>
                            ))}
                        </div>
                        <input
                            type="text"
                            maxLength={80}
                            value={base.occasion}
                            placeholder="Atau tulis momen lain…"
                            onChange={(event) => setBase({ ...base, occasion: event.target.value })}
                            className={`${inputClass} mt-2`}
                        />
                    </div>
                    <div className="rounded-2xl bg-surface-container-low/70 p-3.5">
                        <label className="mb-1 flex items-center gap-1.5 text-label-md font-semibold text-on-surface">
                            <Icon name="schedule_send" size={18} className="text-primary" />
                            Kapan boleh dibuka?
                        </label>
                        <p className="mb-2 text-label-sm text-on-surface-variant">Kirim link-nya kapan saja; isinya baru terbuka di waktu ini. Cocok untuk kejutan tengah malam.</p>
                        <div className="flex flex-wrap gap-2">
                            {[
                                ['Langsung', ''],
                                ['Tengah malam ini', toLocalInput(nextMidnight())],
                                ['Besok 07.00', toLocalInput(tomorrowAt(7))],
                            ].map(([label, value]) => (
                                <button
                                    key={label}
                                    type="button"
                                    onClick={() => setBase({ ...base, opens_at: value })}
                                    className={`rounded-full px-3 py-1.5 text-label-sm ${
                                        base.opens_at === value ? 'bg-primary text-on-primary' : 'bg-surface-container-lowest text-on-surface'
                                    }`}
                                >
                                    {label}
                                </button>
                            ))}
                        </div>
                        <input
                            type="datetime-local"
                            value={base.opens_at}
                            min={toLocalInput(new Date())}
                            onChange={(event) => setBase({ ...base, opens_at: event.target.value })}
                            className={`${inputClass} mt-2`}
                        />
                        {errors.opens_at && <span className="text-label-sm text-error">Waktu buka harus di masa depan.</span>}
                    </div>
                </SectionCard>

                {editableSlots.map((slot, index) => {
                    const slotValues = values[slot.key] ?? {};
                    const toggleField = slot.schema.find((field) => field.name === 'enabled' && field.type === 'toggle');
                    const isEnabled = !toggleField || Boolean(slotValues.enabled);
                    const isComplete = !slot.schema.some((field) => missingKeys.has(`${slot.key}.${field.name}`));

                    return (
                        <SectionCard
                            key={slot.key}
                            number={index + 2}
                            icon={slot.icon}
                            title={slot.title ?? slot.name}
                            isComplete={isComplete && isEnabled}
                            defaultOpen={index < 3}
                        >
                            {!slot.required && <p className="text-label-sm text-on-surface-variant">Opsional — kosongkan jika tidak dipakai.</p>}
                            {slot.schema
                                .filter((field) => isEnabled || field === toggleField)
                                .map((field) => (
                                    <FieldInput
                                        key={field.name}
                                        field={field}
                                        value={slotValues[field.name]}
                                        onChange={(value) => setField(slot.key, field.name, value)}
                                        error={errors[`content.components.${slot.key}.${field.name}`]}
                                        context={{
                                            greetingId: greeting.id,
                                            mediaCache,
                                            addMedia,
                                            musicTracks,
                                            isPinSet: pinState[slot.key],
                                            base,
                                        }}
                                    />
                                ))}
                        </SectionCard>
                    );
                })}

                <CollaborationPanel greetingId={greeting.id} recipientName={base.recipient_name} collaboration={collaboration} />
            </div>

            {showPreview && (
                <div
                    className="fixed inset-0 z-[70] flex items-center justify-center bg-black/60 p-4 backdrop-blur-sm"
                    onClick={(event) => event.target === event.currentTarget && setShowPreview(false)}
                >
                    <div className="flex flex-col items-center gap-3">
                        <PhoneFrame>
                            <Player greeting={livePreview} mode="studio" watermark />
                        </PhoneFrame>
                        <button type="button" onClick={() => setShowPreview(false)} className="rounded-full bg-primary px-6 py-3 text-label-lg text-on-primary shadow-md">
                            Lanjut Sunting Isi
                        </button>
                    </div>
                </div>
            )}
        </StudioLayout>
    );
}
