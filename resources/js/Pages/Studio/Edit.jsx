import Icon from '@/Components/Icon';
import FieldInput from '@/Components/Greeting/fields/FieldInput';
import { toPresentedValues, toRawValues } from '@/Components/Greeting/fields/upload';
import PhoneFrame from '@/Components/Greeting/PhoneFrame';
import Player from '@/Components/Greeting/Player';
import CollaborationPanel from '@/Components/Studio/CollaborationPanel';
import MissingChecklist from '@/Components/Studio/MissingChecklist';
import StudioLayout from '@/Layouts/StudioLayout';
import { dateTime, time } from '@/lib/format';
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

/**
 * Kelas bingkai merah untuk kolom wajib yang masih kosong (sama dengan FieldInput).
 */
const missingFrame = (isMissing) => `scroll-mt-28 rounded-2xl transition-shadow ${isMissing ? '-mx-2 bg-error-container/20 p-2 ring-2 ring-error/40' : ''}`;

function MissingBadge() {
    return (
        <span className="ml-1.5 inline-flex items-center gap-0.5 rounded-full bg-error px-2 py-0.5 align-middle text-[11px] font-bold text-on-error">
            <Icon name="priority_high" size={12} />
            Wajib diisi
        </span>
    );
}

/**
 * Satu bagian form. Bagian dengan isian wajib yang kosong menampilkan jumlahnya dan dibuka
 * otomatis; `openSignal` yang berubah membuka bagian ini dari daftar periksa.
 */
function SectionCard({ id, number, icon, title, isComplete, missingCount = 0, openSignal = 0, children, defaultOpen = true }) {
    const [isOpen, setIsOpen] = useState(defaultOpen || missingCount > 0);

    useEffect(() => {
        if (openSignal > 0) {
            setIsOpen(true);
        }
    }, [openSignal]);

    return (
        <section
            id={id}
            className={`scroll-mt-24 space-y-space-md rounded-3xl bg-surface-container-lowest p-space-lg shadow-[0_4px_24px_rgba(46,26,44,0.05)] ${missingCount > 0 ? 'ring-1 ring-error/30' : ''}`}
        >
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
                    {missingCount > 0 ? (
                        <span className="whitespace-nowrap rounded-full bg-error-container px-2 py-0.5 text-label-sm text-on-error-container">{missingCount} belum diisi</span>
                    ) : (
                        isComplete && (
                            <span className="flex h-6 w-6 items-center justify-center rounded-full bg-primary-fixed text-on-primary-fixed-variant">
                                <Icon name="check" size={14} />
                            </span>
                        )
                    )}
                    <Icon name={isOpen ? 'expand_less' : 'expand_more'} size={20} className="text-on-surface-variant" />
                </div>
            </button>
            {isOpen && <div className="space-y-3.5">{children}</div>}
        </section>
    );
}

export default function Edit({ greeting, template, occasions, missing, musicTracks, collaboration, revision = null }) {
    const { errors } = usePage().props;
    const isInvitation = template.type === 'undangan';
    // Revisi undangan terbit: tanpa autosave, karena setiap simpan menjadi versi baru yang langsung dilihat tamu.
    const isRevising = revision !== null;
    const [hasUnsavedRevision, setHasUnsavedRevision] = useState(false);
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

    const saveRevision = () =>
        router.put(route('invitations.revise', greeting.id), payload(), {
            preserveScroll: true,
            preserveState: true,
            onStart: () => setSaveState({ tone: 'saving', label: 'Menyimpan revisi…' }),
            onSuccess: () => {
                setHasUnsavedRevision(false);
                setSaveState({ tone: 'saved', label: `Revisi tersimpan • ${time(new Date())}` });
            },
            onError: () => setSaveState({ tone: 'error', label: 'Periksa isian' }),
        });

    const leaveRevision = (go) => (!hasUnsavedRevision || confirm('Perubahan revisi belum disimpan. Tetap keluar?') ? go() : null);

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

        if (isRevising) {
            setHasUnsavedRevision(true);
            setSaveState({ tone: 'saving', label: 'Revisi belum disimpan' });

            return;
        }

        setSaveState({ tone: 'saving', label: 'Belum tersimpan' });
        timer.current = setTimeout(() => save(), AUTOSAVE_DELAY);

        return () => clearTimeout(timer.current);
    }, [base, values]); // eslint-disable-line react-hooks/exhaustive-deps

    const setField = (slotKey, fieldName, value) => setValues((current) => ({ ...current, [slotKey]: { ...current[slotKey], [fieldName]: value } }));
    const addMedia = (media) => setMediaCache((cache) => ({ ...cache, [media.id]: media }));

    const missingKeys = new Set(missing.map((item) => `${item.slot}.${item.field}`));
    const missingInSlot = (slotKey) => missing.filter((item) => item.slot === slotKey).length;
    const isBaseMissing = (field) => missingKeys.has(`dasar.${field}`);

    // Daftar periksa → buka bagiannya, gulir ke kolom yang kosong, lalu fokuskan isiannya.
    const [openSignals, setOpenSignals] = useState({});
    const jumpTo = useCallback((item) => {
        setOpenSignals((signals) => ({ ...signals, [item.slot]: (signals[item.slot] ?? 0) + 1 }));

        setTimeout(() => {
            const target = document.getElementById(`field-${item.slot}-${item.field}`) ?? document.getElementById(`isian-${item.slot}`);
            target?.scrollIntoView({ behavior: 'smooth', block: 'center' });
            target?.querySelector('input:not([type=hidden]), textarea, select, button')?.focus({ preventScroll: true });
        }, 80);
    }, []);

    // Tautan dari pratinjau/checkout (…#isian-<bagian>) langsung membuka bagian yang dimaksud.
    useEffect(() => {
        const slotKey = window.location.hash.match(/^#isian-(.+)$/)?.[1];
        const firstMissing = slotKey && missing.find((item) => item.slot === slotKey);

        if (slotKey) {
            jumpTo(firstMissing ?? { slot: slotKey, field: '' });
        }
    }, []); // eslint-disable-line react-hooks/exhaustive-deps
    // Nama & momen (+ minimal satu acara untuk undangan) selalu wajib di luar skema komponen.
    const requiredCount = greeting.components.reduce((count, slot) => count + slot.schema.filter((field) => field.required).length, isInvitation ? 3 : 2);
    const completeCount = Math.max(0, requiredCount - missing.length);
    const editableSlots = greeting.components;

    const livePreview = {
        ...greeting,
        base,
        values: toPresentedValues(greeting.components, values, mediaCache, musicTracks, pinState),
    };

    return (
        <StudioLayout
            title={isRevising ? 'Revisi Undangan' : 'Kustomisasi Kartu'}
            greetingId={greeting.id}
            beforeLeave={isRevising ? leaveRevision : (go) => (saveState.tone === 'saving' ? save({ onSuccess: go }) : go())}
            wide
            showStepper={!isRevising}
            step={(isInvitation ? base.sender_name : base.recipient_name) ? 1 : 0}
            saveState={saveState}
            onBack={() => router.visit(isRevising ? route('invitations.show', greeting.id) : route('catalog.show', template.slug))}
            actions={
                <>
                    <button
                        type="button"
                        onClick={() => setShowPreview(true)}
                        className="flex h-12 items-center justify-center gap-1 rounded-full bg-surface-container px-space-lg text-label-lg text-on-surface hover:bg-surface-container-high lg:hidden"
                    >
                        <Icon name="visibility" size={18} />
                        Lihat
                    </button>
                    {isRevising ? (
                        <button
                            type="button"
                            disabled={!hasUnsavedRevision || saveState.label === 'Menyimpan revisi…'}
                            onClick={saveRevision}
                            className="flex h-12 flex-1 items-center justify-center gap-space-xs rounded-full bg-primary px-space-lg text-label-lg text-on-primary shadow-[0_4px_16px_rgba(168,50,35,0.25)] hover:bg-primary-container active:scale-[0.98] disabled:opacity-50"
                        >
                            <Icon name="published_with_changes" size={18} />
                            <span>Simpan Revisi</span>
                        </button>
                    ) : (
                        <button
                            type="button"
                            onClick={() => save({ onSuccess: () => router.visit(route('studio.style', greeting.id)) })}
                            className="flex h-12 flex-1 items-center justify-center gap-space-xs rounded-full bg-primary px-space-lg text-label-lg text-on-primary shadow-[0_4px_16px_rgba(168,50,35,0.25)] hover:bg-primary-container active:scale-[0.98]"
                        >
                            <span>Lanjut: Gaya</span>
                            <Icon name="arrow_forward" size={18} />
                        </button>
                    )}
                </>
            }
        >
            <div className="lg:grid lg:grid-cols-[minmax(0,1fr)_340px] lg:items-start lg:gap-space-lg lg:pr-margin">
                <div className="flex w-full min-w-0 flex-col space-y-space-lg px-margin pb-10">
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
                        <h2 className="pt-1 text-headline-lg-mobile font-bold tracking-tight text-on-surface">
                            {isRevising ? 'Revisi Isi Undangan' : isInvitation ? 'Personalisasi Isi Undangan' : 'Personalisasi Isi Kartu'}
                        </h2>
                        <p className="text-body-sm text-on-surface-variant">
                            {isRevising
                                ? 'Ubah nama, foto, atau cerita, lalu tekan Simpan Revisi. Link undangan tetap sama dan tamu langsung melihat versi terbaru.'
                                : 'Tuliskan kata terindah dan pilih momen tak terlupakan. Semua tersimpan otomatis.'}
                        </p>
                    </div>

                    {isRevising && (
                        <div className="flex items-start gap-3 rounded-2xl bg-tertiary-fixed px-4 py-3 text-on-tertiary-fixed">
                            <Icon name="history_edu" size={22} className="shrink-0" />
                            <div className="text-body-sm">
                                <p className="text-label-md">Mode revisi — tanpa biaya tambahan</p>
                                <p className="opacity-80">Bisa direvisi sampai {dateTime(revision.revisable_until)}. Template dan gaya tidak bisa diganti.</p>
                                {errors.greeting && <p className="mt-1 font-semibold text-error">{errors.greeting}</p>}
                            </div>
                        </div>
                    )}

                    {!isRevising && <MissingChecklist missing={missing} greetingId={greeting.id} onJump={jumpTo} />}

                    <SectionCard
                        id="isian-dasar"
                        number={1}
                        icon="badge"
                        title={isInvitation ? 'Tuan Rumah & Acara' : 'Nama & Momen Spesial'}
                        isComplete={Boolean((isInvitation ? base.sender_name : base.recipient_name) && base.occasion)}
                        missingCount={missingInSlot('dasar')}
                        openSignal={openSignals.dasar ?? 0}
                    >
                        {isInvitation ? (
                            <div id="field-dasar-sender_name" className={missingFrame(isBaseMissing('sender_name'))}>
                                <label className="mb-1 block text-label-md font-semibold text-on-surface">
                                    Nama Mempelai / Tuan Rumah <span className="text-primary">*</span>
                                    {isBaseMissing('sender_name') && <MissingBadge />}
                                </label>
                                <input
                                    type="text"
                                    maxLength={80}
                                    value={base.sender_name}
                                    placeholder="Misal: Raka & Dinda"
                                    onChange={(event) => setBase({ ...base, sender_name: event.target.value })}
                                    className={inputClass}
                                />
                                <p className="mt-1 text-label-sm text-on-surface-variant">Nama tamu tidak diisi di sini — setiap tamu mendapat link dengan namanya sendiri.</p>
                            </div>
                        ) : (
                            <>
                        <div id="field-dasar-recipient_name" className={missingFrame(isBaseMissing('recipient_name'))}>
                            <label className="mb-1 block text-label-md font-semibold text-on-surface">
                                Nama Panggilan Penerima <span className="text-primary">*</span>
                                {isBaseMissing('recipient_name') && <MissingBadge />}
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
                            </>
                        )}
                        <div id="field-dasar-occasion" className={missingFrame(isBaseMissing('occasion'))}>
                            <label className="mb-2 block text-label-md font-semibold text-on-surface">
                                {isInvitation ? 'Jenis Acara' : 'Pilih Momen Ucapan'} <span className="text-primary">*</span>
                                {isBaseMissing('occasion') && <MissingBadge />}
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
                        {!isInvitation && (
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
                        )}
                    </SectionCard>

                    {isInvitation && greeting.invitation && (
                        <button
                            id="isian-acara"
                            type="button"
                            onClick={() => {
                                const url = `${route('invitations.show', greeting.id)}#${greeting.invitation.events.length === 0 ? 'acara' : 'tamu'}`;
                                isRevising ? leaveRevision(() => router.visit(url)) : save({ onSuccess: () => router.visit(url) });
                            }}
                            className={`flex scroll-mt-28 items-center gap-3 rounded-3xl px-4 py-3.5 text-left shadow-sm active:scale-[0.99] ${
                                greeting.invitation.events.length === 0 ? 'bg-tertiary-fixed text-on-tertiary-fixed ring-2 ring-error/40' : 'bg-primary-fixed text-on-primary-fixed'
                            }`}
                        >
                            <Icon name="event_note" size={24} className="shrink-0" />
                            <span className="min-w-0 flex-1">
                                <span className="block text-label-lg">Acara, Amplop & Daftar Tamu</span>
                                <span className="block text-body-sm opacity-80">
                                    {greeting.invitation.events.length === 0
                                        ? 'Wajib: tambahkan minimal satu acara (akad, resepsi, …).'
                                        : `${greeting.invitation.events.length} acara • ${greeting.invitation.gifts.length} amplop digital — atur di Kelola Undangan.`}
                                </span>
                            </span>
                            <Icon name="arrow_forward" size={20} className="shrink-0" />
                        </button>
                    )}

                    {editableSlots.map((slot, index) => {
                        const slotValues = values[slot.key] ?? {};
                        const toggleField = slot.schema.find((field) => field.name === 'enabled' && field.type === 'toggle');
                        const isEnabled = !toggleField || Boolean(slotValues.enabled);
                        const isComplete = !slot.schema.some((field) => missingKeys.has(`${slot.key}.${field.name}`));

                        return (
                            <SectionCard
                                key={slot.key}
                                id={`isian-${slot.key}`}
                                number={index + 2}
                                icon={slot.icon}
                                title={slot.title ?? slot.name}
                                isComplete={isComplete && isEnabled}
                                missingCount={missingInSlot(slot.key)}
                                openSignal={openSignals[slot.key] ?? 0}
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
                                            isMissing={missingKeys.has(`${slot.key}.${field.name}`)}
                                            fieldId={`field-${slot.key}-${field.name}`}
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

                    {!isInvitation && <CollaborationPanel greetingId={greeting.id} recipientName={base.recipient_name} collaboration={collaboration} />}
                </div>

                <aside className="sticky top-24 hidden flex-col items-center gap-2 lg:flex">
                    <PhoneFrame screenClassName="h-[600px] max-h-[calc(100vh-14rem)]">
                        <Player greeting={livePreview} mode="studio" watermark />
                    </PhoneFrame>
                    <p className="flex items-center gap-1 text-label-sm text-on-surface-variant">
                        <Icon name="bolt" size={14} className="text-primary" />
                        Pratinjau langsung
                    </p>
                </aside>
            </div>

            {showPreview && (
                <div
                    className="fixed inset-0 z-[70] flex items-center justify-center bg-black/60 p-4 backdrop-blur-sm"
                    onClick={(event) => event.target === event.currentTarget && setShowPreview(false)}
                >
                    <div className="flex w-full max-w-[340px] flex-col items-center gap-3">
                        <PhoneFrame bare screenClassName="h-[min(620px,calc(100dvh_-_7rem))] min-h-[420px]">
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
