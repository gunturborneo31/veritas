import Icon from '@/Components/Icon';
import { useEffect } from 'react';

/**
 * Lembar bawah (bottom sheet) seperti modal demo katalog & upgrade Paket 2.
 */
export default function BottomSheet({ open, onClose, title, icon = null, children }) {
    useEffect(() => {
        if (!open) {
            return;
        }

        const onKeyDown = (event) => event.key === 'Escape' && onClose();
        window.addEventListener('keydown', onKeyDown);

        return () => window.removeEventListener('keydown', onKeyDown);
    }, [open, onClose]);

    if (!open) {
        return null;
    }

    return (
        <div
            className="fixed inset-0 z-[70] flex items-end justify-center bg-inverse-surface/60 backdrop-blur-sm"
            onClick={(event) => event.target === event.currentTarget && onClose()}
        >
            <div role="dialog" aria-modal="true" className="pb-safe flex w-full max-w-lg flex-col gap-space-md rounded-t-3xl bg-surface p-space-lg shadow-2xl">
                <div className="mx-auto -mt-2 h-1.5 w-12 rounded-full bg-surface-container-high" />
                <div className="flex items-start justify-between gap-space-sm">
                    <div className="flex items-center gap-3">
                        {icon && (
                            <div className="flex h-12 w-12 items-center justify-center rounded-2xl bg-tertiary-fixed text-tertiary">
                                <Icon name={icon} size={28} />
                            </div>
                        )}
                        <h3 className="text-headline-sm text-on-surface">{title}</h3>
                    </div>
                    <button
                        type="button"
                        onClick={onClose}
                        aria-label="Tutup"
                        className="flex h-8 w-8 items-center justify-center rounded-full bg-surface-container text-on-surface-variant"
                    >
                        <Icon name="close" size={18} />
                    </button>
                </div>
                {children}
            </div>
        </div>
    );
}
