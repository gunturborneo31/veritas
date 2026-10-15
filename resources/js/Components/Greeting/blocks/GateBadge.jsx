import Icon from '@/Components/Icon';

/**
 * Di halaman penerima, gembok ditangani server sebelum isi dikirim.
 * Di Studio/Pratinjau cukup tampil sebagai penanda.
 */
export default function GateBadge({ values }) {
    if (!values.enabled) {
        return null;
    }

    return (
        <div className="flex w-full items-center justify-between rounded-xl bg-surface-container-low px-3 py-2">
            <div className="flex items-center gap-1.5">
                <Icon name="lock" size={15} className="text-tertiary" />
                <span className="text-label-sm text-on-surface">Gembok: “{values.question || 'Kode rahasia'}”</span>
            </div>
            <span className="text-label-sm font-bold text-primary">{values.pin?.is_set ? 'Terpasang' : 'Belum diatur'}</span>
        </div>
    );
}
