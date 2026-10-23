import BottomSheet from '@/Components/BottomSheet';
import Icon from '@/Components/Icon';
import InputError from '@/Components/InputError';
import Field, { EmptyState, inputClass, Suggestions, SubmitButton } from '@/Components/Invitation/Field';
import { router, useForm } from '@inertiajs/react';
import { useEffect, useState } from 'react';

const MAX_ACCOUNTS = 6;

const KINDS = {
    bank: { label: 'Rekening Bank', icon: 'account_balance', providers: ['BCA', 'BRI', 'Mandiri', 'BNI', 'BSI', 'CIMB Niaga', 'Jago'] },
    ewallet: { label: 'Dompet Digital', icon: 'account_balance_wallet', providers: ['GoPay', 'OVO', 'DANA', 'ShopeePay', 'LinkAja'] },
    alamat: { label: 'Alamat Kado', icon: 'local_shipping', providers: [] },
};

const EMPTY_ACCOUNT = { kind: 'bank', provider: '', account_number: '', account_holder: '', address: '' };

function GiftFormSheet({ invitation, account, open, onClose }) {
    const { data, setData, post, put, processing, errors, clearErrors, transform } = useForm(EMPTY_ACCOUNT);
    const isAddress = data.kind === 'alamat';

    // Kolom milik jenis lain dikosongkan agar sisa isian tidak ikut tersimpan.
    transform((values) => (values.kind === 'alamat' ? { ...values, provider: '', account_number: '' } : { ...values, address: '' }));

    useEffect(() => {
        if (open) {
            clearErrors();
            setData(account ? Object.fromEntries(Object.keys(EMPTY_ACCOUNT).map((key) => [key, account[key] ?? ''])) : EMPTY_ACCOUNT);
        }
    }, [open, account]);

    const submit = (event) => {
        event.preventDefault();
        const options = { preserveScroll: true, onSuccess: onClose };

        account ? put(route('invitations.gifts.update', [invitation.id, account.id]), options) : post(route('invitations.gifts.store', invitation.id), options);
    };

    return (
        <BottomSheet open={open} onClose={onClose} title={account ? 'Ubah Amplop Digital' : 'Tambah Amplop Digital'} icon="redeem">
            <form onSubmit={submit} className="flex flex-col gap-space-md">
                <div className="grid grid-cols-3 gap-space-xs">
                    {Object.entries(KINDS).map(([kind, meta]) => (
                        <button
                            key={kind}
                            type="button"
                            onClick={() => setData('kind', kind)}
                            className={`flex flex-col items-center gap-1 rounded-2xl px-2 py-3 text-label-sm ${
                                data.kind === kind ? 'bg-primary text-on-primary' : 'bg-surface-container text-on-surface-variant'
                            }`}
                        >
                            <Icon name={meta.icon} size={22} />
                            {meta.label}
                        </button>
                    ))}
                </div>
                {!isAddress && (
                    <>
                        <Field label={data.kind === 'bank' ? 'Nama bank' : 'Nama dompet digital'} error={errors.provider}>
                            <input value={data.provider} onChange={(e) => setData('provider', e.target.value)} maxLength={40} className={inputClass} />
                            <Suggestions items={KINDS[data.kind].providers} onPick={(provider) => setData('provider', provider)} />
                        </Field>
                        <Field label={data.kind === 'bank' ? 'Nomor rekening' : 'Nomor HP / akun'} error={errors.account_number}>
                            <input inputMode="numeric" value={data.account_number} onChange={(e) => setData('account_number', e.target.value)} maxLength={40} className={inputClass} />
                        </Field>
                    </>
                )}
                <Field label={isAddress ? 'Nama penerima kado' : 'Atas nama'} error={errors.account_holder}>
                    <input value={data.account_holder} onChange={(e) => setData('account_holder', e.target.value)} maxLength={80} className={inputClass} />
                </Field>
                {isAddress && (
                    <Field label="Alamat pengiriman" error={errors.address}>
                        <textarea rows={3} value={data.address} onChange={(e) => setData('address', e.target.value)} maxLength={500} className={`${inputClass} resize-none`} />
                    </Field>
                )}
                <InputError message={errors.kind} />
                <SubmitButton processing={processing}>
                    <Icon name="check" size={20} />
                    Simpan
                </SubmitButton>
            </form>
        </BottomSheet>
    );
}

/**
 * Rekening, e-wallet, dan alamat kado. Tamu menyalin nomornya lalu transfer sendiri.
 */
export default function GiftsPanel({ invitation, readOnly }) {
    const [editing, setEditing] = useState(null);
    const [isFormOpen, setIsFormOpen] = useState(false);

    const openForm = (account = null) => {
        setEditing(account);
        setIsFormOpen(true);
    };

    const remove = (account) => {
        if (confirm('Hapus amplop digital ini?')) {
            router.delete(route('invitations.gifts.destroy', [invitation.id, account.id]), { preserveScroll: true });
        }
    };

    return (
        <div className="flex flex-col gap-space-md">
            {!invitation.settings.gifts_enabled && (
                <p className="flex items-center gap-2 rounded-2xl bg-tertiary-fixed/60 px-space-sm py-2 text-label-md text-on-tertiary-fixed">
                    <Icon name="visibility_off" size={18} />
                    Amplop digital sedang disembunyikan dari tamu (lihat Pengaturan).
                </p>
            )}

            {invitation.gifts.length === 0 ? (
                <EmptyState icon="redeem" title="Belum ada amplop digital" body="Tamu yang berhalangan hadir tetap bisa mengirim hadiah. Uang langsung masuk ke rekeningmu, tidak lewat KartuKita." />
            ) : (
                <ul className="flex flex-col gap-space-sm">
                    {invitation.gifts.map((account) => (
                        <li key={account.id} className="flex items-start gap-3 rounded-3xl bg-surface-container-lowest p-space-md shadow-card">
                            <div className="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-tertiary-fixed text-tertiary">
                                <Icon name={KINDS[account.kind].icon} size={22} />
                            </div>
                            <div className="min-w-0 flex-1">
                                <p className="text-label-sm text-on-surface-variant">{account.provider ?? account.kind_label}</p>
                                {account.account_number && <p className="font-mono text-label-lg tracking-wide text-on-surface">{account.account_number}</p>}
                                <p className="text-body-sm text-on-surface">a.n. {account.account_holder}</p>
                                {account.address && <p className="text-body-sm text-on-surface-variant">{account.address}</p>}
                            </div>
                            {!readOnly && (
                                <div className="flex shrink-0 gap-1">
                                    <button type="button" onClick={() => openForm(account)} aria-label="Ubah" className="flex h-8 w-8 items-center justify-center rounded-full text-on-surface-variant hover:bg-surface-container">
                                        <Icon name="edit" size={18} />
                                    </button>
                                    <button type="button" onClick={() => remove(account)} aria-label="Hapus" className="flex h-8 w-8 items-center justify-center rounded-full text-error hover:bg-error-container/40">
                                        <Icon name="delete_outline" size={18} />
                                    </button>
                                </div>
                            )}
                        </li>
                    ))}
                </ul>
            )}

            {!readOnly && invitation.gifts.length < MAX_ACCOUNTS && (
                <button
                    type="button"
                    onClick={() => openForm()}
                    className="flex h-11 items-center justify-center gap-1.5 rounded-full border-2 border-dashed border-outline-variant text-label-md text-on-surface-variant hover:border-primary hover:text-primary"
                >
                    <Icon name="add" size={18} /> Tambah Rekening / Alamat
                </button>
            )}

            <GiftFormSheet invitation={invitation} account={editing} open={isFormOpen} onClose={() => setIsFormOpen(false)} />
        </div>
    );
}
