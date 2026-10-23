import Icon from '@/Components/Icon';
import { useState } from 'react';
import { ManagedHint } from './invitationShared';
import { SceneCard, SceneLabel } from './shared';

const ICONS = { bank: 'account_balance', ewallet: 'account_balance_wallet', alamat: 'local_shipping' };

/**
 * Amplop digital: tamu menyalin nomor rekening/e-wallet atau alamat kado, lalu mengirim sendiri.
 */
export default function GiftEnvelope({ slot, values, mode, invitation }) {
    const [copiedId, setCopiedId] = useState(null);
    const gifts = invitation?.gifts ?? [];

    const copy = (key, text) => {
        navigator.clipboard?.writeText(text);
        setCopiedId(key);
        setTimeout(() => setCopiedId(null), 2000);
    };

    return (
        <div className="flex w-full flex-col gap-3">
            <div className="text-center">
                <SceneLabel icon="redeem">{values.title || slot?.title || 'Amplop Digital'}</SceneLabel>
            </div>
            <ManagedHint mode={mode}>Rekening & alamat diisi di halaman Kelola Undangan → Amplop.</ManagedHint>
            {values.note && <p className="text-center text-body-sm text-on-surface-variant">{values.note}</p>}

            {gifts.map((gift, index) => {
                const key = gift.id ?? index;
                const copyText = gift.kind === 'alamat' ? `${gift.account_holder}\n${gift.address}` : gift.account_number;

                return (
                    <SceneCard key={key} className="text-left">
                        <div className="flex items-center gap-3">
                            <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-primary-fixed text-primary">
                                <Icon name={ICONS[gift.kind]} size={20} />
                            </div>
                            <div className="min-w-0 flex-1">
                                <p className="text-label-sm text-on-surface-variant">{gift.provider ?? gift.kind_label}</p>
                                {gift.account_number && <p className="font-mono text-label-lg tracking-wider text-on-surface">{gift.account_number}</p>}
                                <p className="text-body-sm text-on-surface">a.n. {gift.account_holder}</p>
                                {gift.address && <p className="text-body-sm text-on-surface-variant">{gift.address}</p>}
                            </div>
                        </div>
                        <button
                            type="button"
                            onClick={() => copy(key, copyText)}
                            className="flex items-center justify-center gap-1.5 rounded-full bg-surface-container py-2 text-label-md text-on-surface active:scale-[0.98]"
                        >
                            <Icon name={copiedId === key ? 'check' : 'content_copy'} size={16} className="text-primary" />
                            {copiedId === key ? 'Tersalin!' : gift.kind === 'alamat' ? 'Salin Alamat' : 'Salin Nomor'}
                        </button>
                    </SceneCard>
                );
            })}
        </div>
    );
}
