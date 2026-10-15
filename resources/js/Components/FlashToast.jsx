import Icon from '@/Components/Icon';
import { usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';

/**
 * Toast singkat. Tanpa prop `message` menampilkan flash dari server (success / error);
 * dengan prop `message` hanya menampilkan pesan lokal tersebut.
 */
export default function FlashToast({ message, tone = 'success' }) {
    const { flash } = usePage().props;
    const [visible, setVisible] = useState(null);
    const isLocal = message !== undefined;

    useEffect(() => {
        const next = isLocal
            ? message && { text: message, tone }
            : flash?.error
              ? { text: flash.error, tone: 'error' }
              : flash?.success && { text: flash.success, tone: 'success' };

        if (!next) {
            return;
        }

        setVisible(next);
        const timer = setTimeout(() => setVisible(null), 2800);

        return () => clearTimeout(timer);
    }, [isLocal, message, tone, flash]);

    return (
        <div
            role="status"
            className={`fixed left-1/2 top-20 z-[60] flex max-w-[90vw] -translate-x-1/2 items-center gap-space-sm rounded-full px-space-md py-space-sm shadow-xl transition-all duration-300 ${
                visible ? 'translate-y-0 opacity-100' : 'pointer-events-none -translate-y-6 opacity-0'
            } ${visible?.tone === 'error' ? 'bg-error text-on-error' : 'bg-inverse-surface text-inverse-on-surface'}`}
        >
            <Icon name={visible?.tone === 'error' ? 'error' : 'check_circle'} size={18} className="text-tertiary-fixed-dim" />
            <span className="text-label-md">{visible?.text}</span>
        </div>
    );
}
