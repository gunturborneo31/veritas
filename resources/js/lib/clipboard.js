/**
 * Salin teks ke clipboard. navigator.clipboard hanya ada di HTTPS dan sering diblokir di browser bawaan
 * aplikasi (WhatsApp, Instagram), jadi ada cadangan lewat textarea tersembunyi + execCommand.
 * Mengembalikan true bila berhasil.
 */
export async function copyText(text) {
    const value = String(text ?? '');

    try {
        if (window.isSecureContext && navigator.clipboard?.writeText) {
            await navigator.clipboard.writeText(value);

            return true;
        }
    } catch {
        // Lanjut ke cara cadangan.
    }

    const textarea = document.createElement('textarea');
    textarea.value = value;
    textarea.setAttribute('readonly', '');
    textarea.style.position = 'fixed';
    textarea.style.top = '0';
    textarea.style.left = '0';
    textarea.style.opacity = '0';
    document.body.appendChild(textarea);
    textarea.focus();
    textarea.select();
    textarea.setSelectionRange(0, value.length);

    let copied = false;

    try {
        copied = document.execCommand('copy');
    } catch {
        copied = false;
    }

    document.body.removeChild(textarea);

    return copied;
}
