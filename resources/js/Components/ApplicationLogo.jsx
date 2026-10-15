/**
 * Logo KartuKita: amplop terbuka dengan hati (coral, plum, emas).
 */
export default function ApplicationLogo(props) {
    return (
        <svg {...props} viewBox="0 0 48 48" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
            <rect x="4" y="12" width="40" height="30" rx="7" fill="#c94a38" />
            <path d="M4 18 L24 31 L44 18" fill="none" stroke="#fff7f9" strokeWidth="3" strokeLinejoin="round" />
            <path
                d="M24 22c-2.6-4.4-9-3.2-9 1.5 0 3.8 5.2 6.9 9 9.5 3.8-2.6 9-5.7 9-9.5 0-4.7-6.4-5.9-9-1.5z"
                fill="#fff7f9"
                transform="translate(0 -12) scale(1)"
            />
            <circle cx="40" cy="8" r="2.5" fill="#fabc4d" />
            <circle cx="34" cy="4" r="1.5" fill="#fabc4d" />
        </svg>
    );
}
