import ApplicationLogo from '@/Components/ApplicationLogo';
import FlashToast from '@/Components/FlashToast';
import Icon from '@/Components/Icon';
import Stepper from '@/Components/Stepper';
import { Head } from '@inertiajs/react';

/**
 * Kerangka langkah Studio → Bayar (desain studio, pilihgaya, pratinjau, ringkasan).
 */
export default function StudioLayout({ title, step, saveState = null, onBack, actions, children }) {
    return (
        <div className="relative flex min-h-screen flex-col bg-surface">
            <Head title={title} />

            <header className="pt-safe fixed inset-x-0 top-0 z-50 bg-surface/85 shadow-[0_1px_12px_rgba(46,26,44,0.04)] backdrop-blur-xl">
                <div className="mx-auto flex h-16 max-w-3xl items-center justify-between gap-space-sm px-margin">
                    <div className="flex min-w-0 items-center gap-space-sm">
                        <button
                            type="button"
                            aria-label="Kembali"
                            onClick={onBack ?? (() => window.history.back())}
                            className="-ml-2 flex h-11 w-11 items-center justify-center rounded-full text-on-surface hover:bg-surface-container-high"
                        >
                            <Icon name="arrow_back" size={22} />
                        </button>
                        <ApplicationLogo className="h-8 w-8 shrink-0" />
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
                    <div className="w-11" />
                </div>
            </header>

            <FlashToast />

            <main className="mx-auto flex w-full max-w-3xl flex-1 flex-col pb-32 pt-16">
                <div className="px-margin pb-space-md pt-space-sm">
                    <Stepper current={step} />
                </div>
                {children}
            </main>

            {actions && (
                <aside className="pb-safe fixed inset-x-0 bottom-0 z-50 bg-surface/90 shadow-bar backdrop-blur-xl">
                    <div className="mx-auto flex min-h-20 max-w-3xl items-center justify-between gap-space-md px-margin py-space-sm">
                        {actions}
                    </div>
                </aside>
            )}
        </div>
    );
}
