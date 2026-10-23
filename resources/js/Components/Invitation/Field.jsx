import Icon from '@/Components/Icon';
import InputError from '@/Components/InputError';

export const inputClass =
    'w-full rounded-2xl border-0 bg-surface-container-low px-4 py-3 text-body-md text-on-surface placeholder:text-on-surface-variant/60 focus:bg-surface-container-lowest focus:ring-2 focus:ring-primary/40 disabled:opacity-60';

/**
 * Label + kontrol + pesan error untuk form kelola undangan.
 */
export default function Field({ label, hint = null, error = null, children }) {
    return (
        <label className="flex flex-col gap-1.5">
            <span className="text-label-md text-on-surface">{label}</span>
            {children}
            {hint && !error && <span className="text-label-sm font-normal text-on-surface-variant">{hint}</span>}
            <InputError message={error} />
        </label>
    );
}

/**
 * Pilihan cepat berbentuk chip (mis. nama acara, nama bank).
 */
export function Suggestions({ items, onPick }) {
    return (
        <div className="no-scrollbar -mt-0.5 flex gap-1.5 overflow-x-auto">
            {items.map((item) => (
                <button
                    key={item}
                    type="button"
                    onClick={() => onPick(item)}
                    className="whitespace-nowrap rounded-full bg-surface-container px-3 py-1 text-label-sm text-on-surface-variant hover:bg-surface-container-high"
                >
                    {item}
                </button>
            ))}
        </div>
    );
}

/**
 * Tombol simpan di bagian bawah lembar form.
 */
export function SubmitButton({ processing, children }) {
    return (
        <button
            type="submit"
            disabled={processing}
            className="flex h-12 w-full items-center justify-center gap-2 rounded-full bg-primary text-label-lg text-on-primary shadow-md hover:bg-primary-container active:scale-[0.99] disabled:opacity-50"
        >
            {children}
        </button>
    );
}

/**
 * Kartu kosong dengan ajakan bertindak.
 */
export function EmptyState({ icon, title, body, action = null }) {
    return (
        <div className="flex flex-col items-center gap-2 rounded-3xl bg-surface-container-low px-space-md py-space-xl text-center">
            <Icon name={icon} size={40} className="text-on-surface-variant" />
            <p className="text-label-lg text-on-surface">{title}</p>
            <p className="max-w-xs text-body-sm text-on-surface-variant">{body}</p>
            {action}
        </div>
    );
}
