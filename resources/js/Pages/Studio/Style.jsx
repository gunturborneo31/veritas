import BottomSheet from '@/Components/BottomSheet';
import Icon from '@/Components/Icon';
import PhoneFrame from '@/Components/Greeting/PhoneFrame';
import Player from '@/Components/Greeting/Player';
import StudioLayout from '@/Layouts/StudioLayout';
import { router } from '@inertiajs/react';
import { useState } from 'react';

/**
 * Langkah 3 — Gaya. Paket 1 memakai gaya default; varian lain tampil terkunci (Paket 2).
 */
export default function Style({ greeting, template, styleGroups }) {
    const [activeGroup, setActiveGroup] = useState(styleGroups[0]?.key ?? null);
    const [lockedFeature, setLockedFeature] = useState(null);
    const [isSubmitting, setIsSubmitting] = useState(false);
    const group = styleGroups.find((item) => item.key === activeGroup);

    const goToPreview = () => router.post(route('studio.preview', greeting.id), {}, { onStart: () => setIsSubmitting(true), onFinish: () => setIsSubmitting(false) });

    return (
        <StudioLayout
            title="Kustomisasi Kartu"
            step={2}
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
                        disabled={isSubmitting}
                        onClick={goToPreview}
                        className="flex h-12 flex-1 items-center justify-center gap-space-xs rounded-full bg-primary px-space-lg text-label-lg text-on-primary shadow-[0_4px_16px_rgba(168,50,35,0.25)] hover:bg-primary-container disabled:opacity-60"
                    >
                        <span>Lanjut: Pratinjau</span>
                        <Icon name="arrow_forward" size={18} />
                    </button>
                </>
            }
        >
            <div className="mb-space-lg px-margin">
                <div className="rounded-3xl bg-surface-container-low p-space-md shadow-sm">
                    <div className="mb-space-sm flex items-center justify-between">
                        <div className="flex items-center gap-2">
                            <span className="h-2.5 w-2.5 animate-pulse rounded-full bg-primary" />
                            <span className="text-label-md text-on-surface">Pratinjau Langsung</span>
                        </div>
                        <span className="rounded-full bg-surface-container px-2.5 py-1 text-label-sm font-semibold text-primary">Gaya Paket 1</span>
                    </div>
                    <div className="flex justify-center">
                        <PhoneFrame className="max-w-[280px]">
                            <Player greeting={greeting} mode="studio" watermark />
                        </PhoneFrame>
                    </div>
                </div>
            </div>

            <div className="mb-space-md px-margin">
                <h2 className="text-headline-lg-mobile leading-snug text-on-surface">Personalisasi Gaya Visual</h2>
                <p className="mt-1 text-body-sm text-on-surface-variant">
                    Paket 1 sudah termasuk gaya terbaik untuk {template.name}. Ganti gaya per komponen akan tersedia di Paket 2.
                </p>
            </div>

            <div className="no-scrollbar mb-space-lg flex items-center gap-space-xs overflow-x-auto px-margin">
                {styleGroups.map((item) => (
                    <button
                        key={item.key}
                        type="button"
                        onClick={() => setActiveGroup(item.key)}
                        className={`whitespace-nowrap rounded-full px-4 py-2 text-label-md transition-all ${
                            activeGroup === item.key ? 'bg-primary text-on-primary shadow-sm' : 'bg-surface-container text-on-surface'
                        }`}
                    >
                        {item.name}
                    </button>
                ))}
            </div>

            {group && (
                <div className="grid grid-cols-2 gap-space-md px-margin">
                    {group.variants.map((variant) => (
                        <button
                            key={variant.key}
                            type="button"
                            onClick={() => variant.is_locked && setLockedFeature(variant.name)}
                            className={`relative flex flex-col gap-2 rounded-2xl p-space-sm text-left transition-all ${
                                variant.is_locked ? 'bg-surface-container-low opacity-90 shadow-sm' : 'bg-surface-container-lowest shadow-md ring-2 ring-primary'
                            }`}
                        >
                            <div className="relative flex aspect-[4/3] w-full items-center justify-center overflow-hidden rounded-xl bg-gradient-to-br from-primary-fixed to-tertiary-fixed">
                                <Icon name="palette" size={32} className="text-primary/50" />
                                {variant.is_locked ? (
                                    <>
                                        <div className="absolute inset-0 flex items-center justify-center bg-inverse-surface/40 backdrop-blur-[2px]">
                                            <span className="flex h-9 w-9 items-center justify-center rounded-full bg-surface-container-lowest/90 text-primary shadow">
                                                <Icon name="lock" size={20} />
                                            </span>
                                        </div>
                                        <span className="absolute bottom-2 left-2 right-2 rounded-full bg-inverse-surface/85 px-1.5 py-0.5 text-center text-[10px] font-medium text-surface">
                                            🔒 Tersedia di Paket 2
                                        </span>
                                    </>
                                ) : (
                                    <span className="absolute right-2 top-2 flex h-6 w-6 items-center justify-center rounded-full bg-primary text-on-primary shadow">
                                        <Icon name="check" size={14} />
                                    </span>
                                )}
                            </div>
                            <span className="text-label-md font-semibold text-on-surface">{variant.name}</span>
                            <span className={`text-[10px] font-semibold ${variant.is_locked ? 'text-primary' : 'text-tertiary-container'}`}>
                                {variant.is_locked ? 'Paket 2' : 'Termasuk di Paket 1'}
                            </span>
                        </button>
                    ))}
                </div>
            )}

            <BottomSheet open={lockedFeature !== null} onClose={() => setLockedFeature(null)} title="Tersedia di Paket 2" icon="workspace_premium">
                <p className="text-body-sm font-semibold text-primary">{lockedFeature}</p>
                <p className="text-body-sm text-on-surface-variant">
                    Ganti gaya visual per komponen akan hadir di Paket 2. Untuk saat ini kartumu memakai gaya Paket 1 yang sudah disiapkan khusus untuk template ini.
                </p>
                <button type="button" onClick={() => setLockedFeature(null)} className="h-11 rounded-full bg-primary text-label-md text-on-primary shadow-md">
                    Mengerti
                </button>
            </BottomSheet>
        </StudioLayout>
    );
}
