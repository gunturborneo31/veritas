export default function Toggle({ checked, onChange, label, tone = 'primary' }) {
    return (
        <button
            type="button"
            role="switch"
            aria-checked={checked}
            aria-label={label}
            onClick={() => onChange(!checked)}
            className={`flex h-7 w-12 shrink-0 items-center rounded-full p-1 shadow-inner transition-colors ${
                checked ? (tone === 'tertiary' ? 'justify-end bg-tertiary-container' : 'justify-end bg-primary') : 'justify-start bg-surface-container-highest'
            }`}
        >
            <span className="h-5 w-5 rounded-full bg-white shadow-sm" />
        </button>
    );
}
