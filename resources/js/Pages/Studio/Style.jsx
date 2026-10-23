import Icon from '@/Components/Icon';
import PhoneFrame from '@/Components/Greeting/PhoneFrame';
import Player from '@/Components/Greeting/Player';
import StudioLayout from '@/Layouts/StudioLayout';
import { FONTS, MOTIFS, PALETTES, loadFont, paletteFromAccent, readableAccent } from '@/lib/theme';
import { router } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';

function Section({ title, hint, children }) {
    return (
        <section className="flex flex-col gap-space-sm px-margin pb-space-lg">
            <div>
                <h3 className="text-headline-sm text-on-surface">{title}</h3>
                {hint && <p className="text-body-sm text-on-surface-variant">{hint}</p>}
            </div>
            {children}
        </section>
    );
}

function Swatch({ palette, name, isActive, onClick }) {
    return (
        <button type="button" onClick={onClick} className="flex flex-col items-center gap-1.5" aria-pressed={isActive}>
            <span
                className={`relative flex h-14 w-14 items-center justify-center rounded-2xl shadow-sm transition-all ${isActive ? 'scale-105 ring-2 ring-offset-2' : 'active:scale-95'}`}
                style={{ background: `linear-gradient(150deg, ${palette.gradient.join(', ')})`, '--tw-ring-color': palette.accent }}
            >
                <span className="h-6 w-6 rounded-full border-2 border-white shadow" style={{ backgroundColor: palette.accent }} />
                {isActive && (
                    <span className="absolute -right-1.5 -top-1.5 flex h-5 w-5 items-center justify-center rounded-full text-white shadow" style={{ backgroundColor: palette.accent }}>
                        <Icon name="check" size={13} />
                    </span>
                )}
            </span>
            <span className="max-w-[4.5rem] truncate text-xs font-semibold text-on-surface-variant">{name}</span>
        </button>
    );
}

/**
 * Langkah Gaya — pengirim bebas mengganti warna, latar, hiasan, dan huruf judul. Tersimpan otomatis.
 */
export default function Style({ greeting, template, templateTheme, style: savedStyle }) {
    const [style, setStyle] = useState(savedStyle);
    const [saveState, setSaveState] = useState({ tone: 'saved', label: 'Tersimpan' });
    const [isSubmitting, setIsSubmitting] = useState(false);
    const isFirstRender = useRef(true);

    const theme = { ...templateTheme, ...(style ?? {}) };
    const current = style ?? templateTheme;
    const ink = readableAccent(current.accent);

    useEffect(() => Object.keys(FONTS).forEach(loadFont), []);

    useEffect(() => {
        if (isFirstRender.current) {
            isFirstRender.current = false;

            return;
        }

        setSaveState({ tone: 'saving', label: 'Menyimpan…' });
        const timer = setTimeout(
            () =>
                router.put(
                    route('studio.style.update', greeting.id),
                    { style },
                    {
                        preserveScroll: true,
                        preserveState: true,
                        onSuccess: () => setSaveState({ tone: 'saved', label: 'Tersimpan' }),
                        onError: () => setSaveState({ tone: 'error', label: 'Gagal menyimpan' }),
                    },
                ),
            450,
        );

        return () => clearTimeout(timer);
    }, [style]); // eslint-disable-line react-hooks/exhaustive-deps

    const update = (changes) =>
        setStyle({
            accent: current.accent,
            soft: current.soft,
            gradient: current.gradient,
            motif: current.motif,
            font: current.font,
            ...changes,
        });

    const isPalette = (palette) => style !== null && current.accent === palette.accent && current.gradient.join() === palette.gradient.join();
    const isCustomColor = style !== null && !PALETTES.some(isPalette) && current.accent !== templateTheme.accent;

    const goToPreview = () => router.post(route('studio.preview', greeting.id), {}, { onStart: () => setIsSubmitting(true), onFinish: () => setIsSubmitting(false) });

    return (
        <StudioLayout
            title="Kustomisasi Gaya"
            greetingId={greeting.id}
            beforeLeave={(go) => (saveState.tone === 'saving' ? router.put(route('studio.style.update', greeting.id), { style }, { preserveScroll: true, onSuccess: go }) : go())}
            wide
            step={2}
            saveState={saveState}
            onBack={() => router.visit(route('studio.edit', greeting.id))}
            actions={
                <>
                    <button
                        type="button"
                        onClick={() => router.visit(route('studio.edit', greeting.id))}
                        className="flex h-12 items-center justify-center rounded-full bg-surface-container px-space-lg text-label-lg text-on-surface hover:bg-surface-container-high"
                    >
                        Kembali
                    </button>
                    <button
                        type="button"
                        disabled={isSubmitting || saveState.tone === 'saving'}
                        onClick={goToPreview}
                        className="flex h-12 flex-1 items-center justify-center gap-space-xs rounded-full px-space-lg text-label-lg text-on-primary shadow-md transition-colors hover:brightness-110 disabled:opacity-60"
                        style={{ backgroundColor: ink }}
                    >
                        <span>Lanjut: Pratinjau</span>
                        <Icon name="arrow_forward" size={18} />
                    </button>
                </>
            }
        >
            <div className="lg:grid lg:grid-cols-[minmax(0,1fr)_340px] lg:items-start lg:gap-space-lg lg:pr-margin">
                <div className="mb-space-lg px-margin lg:sticky lg:top-24 lg:order-last lg:mb-0 lg:px-0">
                    <div className="rounded-3xl bg-surface-container-low p-space-md shadow-sm">
                        <div className="mb-space-sm flex items-center justify-between gap-2">
                            <div className="flex items-center gap-2">
                                <span className="h-2.5 w-2.5 animate-pulse rounded-full" style={{ backgroundColor: theme.accent }} />
                                <span className="text-label-md text-on-surface">Pratinjau Langsung</span>
                            </div>
                            <span className="truncate rounded-full bg-surface-container px-2.5 py-1 text-label-sm font-semibold text-on-surface-variant">{template.design_name ?? template.name}</span>
                        </div>
                        <div className="flex justify-center">
                            <PhoneFrame className="max-w-[230px] sm:max-w-[280px]" screenClassName="h-[380px] sm:h-[520px] lg:h-[min(560px,calc(100dvh_-_16rem))]">
                                <Player greeting={{ ...greeting, theme }} mode="studio" watermark />
                            </PhoneFrame>
                        </div>
                    </div>
                </div>

                <div className="min-w-0">
                    <div className="mb-space-md flex items-start justify-between gap-3 px-margin">
                        <div>
                            <h2 className="text-headline-lg-mobile leading-snug text-on-surface">Jadikan gayamu sendiri</h2>
                            <p className="mt-1 text-body-sm text-on-surface-variant">Ganti warna, hiasan, dan huruf sesukamu. Gratis, sudah termasuk Paket 1.</p>
                        </div>
                        {style !== null && (
                            <button
                                type="button"
                                onClick={() => setStyle(null)}
                                className="flex shrink-0 items-center gap-1 rounded-full bg-surface-container px-3 py-2 text-label-sm text-on-surface hover:bg-surface-container-high"
                            >
                                <Icon name="restart_alt" size={15} />
                                Warna asli
                            </button>
                        )}
                    </div>

                    <Section title="Palet warna" hint="Warna tombol, aksen, dan latar kartu.">
                        <div className="grid grid-cols-4 gap-x-2 gap-y-3 sm:grid-cols-7">
                            <Swatch palette={templateTheme} name="Asli" isActive={style === null} onClick={() => setStyle(null)} />
                            {PALETTES.map((palette) => (
                                <Swatch
                                    key={palette.key}
                                    palette={palette}
                                    name={palette.name}
                                    isActive={isPalette(palette)}
                                    onClick={() => update({ accent: palette.accent, soft: palette.soft, gradient: palette.gradient })}
                                />
                            ))}
                        </div>
                        <label
                            className={`mt-1 flex cursor-pointer items-center gap-3 rounded-2xl bg-surface-container-lowest p-3 shadow-sm ${isCustomColor ? 'ring-2' : ''}`}
                            style={{ '--tw-ring-color': current.accent }}
                        >
                            <span className="relative h-11 w-11 shrink-0 overflow-hidden rounded-xl shadow-inner" style={{ backgroundColor: current.accent }}>
                                <input
                                    type="color"
                                    value={current.accent}
                                    onChange={(event) => update(paletteFromAccent(event.target.value))}
                                    className="absolute inset-0 h-full w-full cursor-pointer opacity-0"
                                    aria-label="Pilih warna sendiri"
                                />
                            </span>
                            <span className="flex-1">
                                <span className="block text-label-lg text-on-surface">Warna sendiri</span>
                                <span className="block text-body-sm text-on-surface-variant">Ketuk untuk memilih warna favoritnya, latar menyesuaikan otomatis.</span>
                            </span>
                            <span className="font-mono text-label-sm uppercase text-on-surface-variant">{current.accent}</span>
                        </label>
                        {ink !== current.accent && (
                            <p className="flex items-start gap-1.5 text-body-sm text-on-surface-variant">
                                <Icon name="contrast" size={16} className="mt-0.5 shrink-0" />
                                Warna ini cukup terang, jadi tombol dan teks kartu kami gelapkan sedikit agar tetap terbaca oleh penerima.
                            </p>
                        )}
                    </Section>

                    <Section title="Hiasan latar">
                        <div className="no-scrollbar -mx-margin flex gap-2 overflow-x-auto px-margin pb-1">
                            {Object.entries(MOTIFS).map(([key, motif]) => {
                                const isActive = current.motif === key;

                                return (
                                    <button
                                        key={key}
                                        type="button"
                                        onClick={() => update({ motif: key })}
                                        className={`flex shrink-0 items-center gap-1.5 rounded-full px-3.5 py-2 text-label-md transition-all active:scale-95 ${
                                            isActive ? 'text-white shadow-md' : 'bg-surface-container-lowest text-on-surface shadow-sm'
                                        }`}
                                        style={isActive ? { backgroundColor: ink } : undefined}
                                    >
                                        <span>{motif.emoji}</span>
                                        {motif.label}
                                    </button>
                                );
                            })}
                        </div>
                    </Section>

                    <Section title="Huruf judul">
                        <div className="grid grid-cols-3 gap-2">
                            {Object.entries(FONTS).map(([key, font]) => {
                                const isActive = current.font === key;

                                return (
                                    <button
                                        key={key}
                                        type="button"
                                        onClick={() => update({ font: key })}
                                        className={`flex flex-col items-center gap-1 rounded-2xl bg-surface-container-lowest p-3 shadow-sm transition-all active:scale-95 ${isActive ? 'ring-2' : ''}`}
                                        style={{ '--tw-ring-color': current.accent }}
                                    >
                                        <span className="text-[28px] font-bold leading-none" style={{ fontFamily: font.family, color: ink }}>
                                            Aa
                                        </span>
                                        <span className="text-label-sm text-on-surface-variant">{font.label}</span>
                                    </button>
                                );
                            })}
                        </div>
                    </Section>
                </div>
            </div>
        </StudioLayout>
    );
}
