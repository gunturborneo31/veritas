/**
 * Ikon Material Symbols (dipakai di seluruh desain Stitch).
 */
export default function Icon({ name, size = 20, filled = false, className = '', style = {} }) {
    return (
        <span
            aria-hidden="true"
            className={`material-symbols-outlined ${filled ? 'icon-fill' : ''} ${className}`}
            style={{ fontSize: size, ...style }}
        >
            {name}
        </span>
    );
}
