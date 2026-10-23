import ApplicationLogo from '@/Components/ApplicationLogo';
import FlashToast from '@/Components/FlashToast';
import Icon from '@/Components/Icon';
import Stepper from '@/Components/Stepper';
import { Head, router, usePage } from '@inertiajs/react';

/**
 * Kerangka langkah Studio → Bayar (desain studio, pilihgaya, pratinjau, ringkasan).
 * Mode fokus tanpa menu utama, tetapi selalu ada dua jalan yang jelas:
 * panah kiri = langkah sebelumnya, "Keluar" = kembali ke Kartu Saya (draf tersimpan otomatis).
 * `greetingId` membuat langkah yang sudah selesai di stepper bisa diketuk.
 * `beforeLeave(go)` dipanggil sebelum keluar/pindah langkah, mis. untuk menyimpan draf yang belum tersimpan.
 * `wide` memberi ruang untuk pratinjau berdampingan di layar besar.
 * `showStepper` dimatikan di mode revisi undangan, karena langkah Gaya & Bayar tidak berlaku lagi.
 */
export default function StudioLayout({ title, step, saveState = null, onBack, actions, greetingId = null, beforeLeave = null, wide = false, showStepper = true, children }) {
    const { auth } = usePage().props;
    const containerWidth = wide ? 'max-w-3xl lg:max-w-6xl' : 'max-w-3xl';
    const exit = auth.user ? { href: route('dashboard'), label: 'Kartu Saya' } : { href: route('home'), label: 'Beranda' };
    const guard = (go) => (beforeLeave ? beforeLeave(go) : go());
    const leave = (href) => guard(() => router.visit(href));
    const goBack = onBack ?? (() => window.history.back());

    return (
        <div className="relative flex min-h-screen flex-col bg-surface">
            <Head title={title} />

            <header className="pt-safe fixed inset-x-0 top-0 z-50 bg-surface/85 shadow-[0_1px_12px_rgba(46,26,44,0.04)] backdrop-blur-xl">
                <div className={`mx-auto flex h-16 items-center justify-between gap-space-sm px-margin ${containerWidth}`}>
                    <div className="flex min-w-0 items-center gap-space-xs">
                        <button
                            type="button"
                            aria-label="Langkah sebelumnya"
                            onClick={() => guard(goBack)}
                            className="-ml-2 flex h-11 w-11 items-center justify-center rounded-full text-on-surface hover:bg-surface-container-high"
                        >
                            <Icon name="arrow_back" size={22} />
                        </button>
                        <ApplicationLogo className="hidden h-8 w-8 shrink-0 sm:block" />
                    </div>
                    <div className="flex min-w-0 flex-1 flex-col items-center px-space-xs text-center">
                        <h1 className="w-full truncate text-headline-sm leading-tight text-on-surface">{title}</h1>
                        {saveState && (
                            <div className="mt-0.5 inline-flex items-center gap-1 rounded-full bg-surface-container-low px-2 py-0.5">
                                <span
                                    className={`h-1.5 w-1.5 rounded-full ${
                                        saveState.tone === 'saving'
                                            ? 'animate-pulse bg-tertiary-fixed-dim'
                                            : saveState.tone === 'error'
                                              ? 'bg-error'
                                              : 'bg-success'
                                    }`}
                                />
                                <span className="text-label-sm text-on-surface-variant">{saveState.label}</span>
                            </div>
                        )}
                    </div>
                    <button
                        type="button"
                        onClick={() => leave(exit.href)}
                        title={`Keluar ke ${exit.label} — draf tersimpan otomatis`}
                        className="-mr-1 flex h-10 shrink-0 items-center gap-1 rounded-full px-2.5 text-label-md text-on-surface-variant hover:bg-surface-container-high"
                    >
                        <Icon name="close" size={20} />
                        <span>Keluar</span>
                    </button>
                </div>
            </header>

            <FlashToast />

            <main className={`mx-auto flex w-full flex-1 flex-col pb-32 pt-16 ${containerWidth}`}>
                {showStepper && (
                    <div className="px-margin pb-space-md pt-space-sm">
                        <Stepper current={step} greetingId={greetingId} onNavigate={leave} />
                    </div>
                )}
                {children}
            </main>

            {actions && (
                <aside className="pb-safe fixed inset-x-0 bottom-0 z-50 bg-surface/90 shadow-bar backdrop-blur-xl">
                    <div className={`mx-auto flex min-h-20 items-center justify-between gap-space-md px-margin py-space-sm ${containerWidth}`}>
                        {actions}
                    </div>
                </aside>
            )}
        </div>
    );
}
