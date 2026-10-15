const rupiahFormatter = new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    maximumFractionDigits: 0,
});

export function rupiah(amount) {
    return rupiahFormatter.format(amount ?? 0).replace(/\s/g, '');
}

export function dateTime(value) {
    if (!value) {
        return '';
    }

    return new Intl.DateTimeFormat('id-ID', {
        day: 'numeric',
        month: 'long',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    }).format(new Date(value));
}

export function time(value) {
    if (!value) {
        return '';
    }

    return new Intl.DateTimeFormat('id-ID', { hour: '2-digit', minute: '2-digit' }).format(new Date(value));
}

export function duration(seconds) {
    const total = Math.max(0, Math.round(seconds ?? 0));

    return `${Math.floor(total / 60)}:${String(total % 60).padStart(2, '0')}`;
}
