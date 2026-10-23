import Icon from '@/Components/Icon';
import { dateTime } from '@/lib/format';
import { useState } from 'react';

/**
 * Program referral: teman dapat potongan di pesanan pertama, pemilik kode dapat voucher setelah teman membayar.
 */
export default function ReferralCard({ referral }) {
    const [copied, setCopied] = useState(false);

    const copy = () => {
        navigator.clipboard?.writeText(referral.code);
        setCopied(true);
        setTimeout(() => setCopied(false), 2000);
    };

    return (
        <section className="px-margin pb-space-lg">
            <div className="relative overflow-hidden rounded-[1.75rem] bg-inverse-surface p-5 text-inverse-on-surface shadow-card">
                <div className="pointer-events-none absolute -right-10 -top-10 h-40 w-40 rounded-full bg-primary/40 blur-3xl" />
                <p className="relative text-label-sm uppercase tracking-[0.25em] text-tertiary-fixed-dim">Ajak teman, sama-sama hemat</p>
                <h2 className="relative mt-1 font-display text-headline-sm">
                    Temanmu hemat {referral.discount}, kamu dapat voucher {referral.reward}
                </h2>
                <p className="relative mt-1 text-body-sm opacity-80">Voucher dikirim otomatis setelah pesanan pertama temanmu lunas.</p>

                <div className="relative mt-4 flex items-center gap-2">
                    <button
                        type="button"
                        onClick={copy}
                        className="flex flex-1 items-center justify-between rounded-2xl border border-dashed border-white/40 bg-white/10 px-4 py-3"
                    >
                        <span className="font-display text-headline-sm tracking-[0.2em]">{referral.code}</span>
                        <span className="flex items-center gap-1 text-label-sm">
                            <Icon name={copied ? 'done' : 'content_copy'} size={16} />
                            {copied ? 'Tersalin' : 'Salin'}
                        </span>
                    </button>
                    <a
                        href={`https://api.whatsapp.com/send?text=${encodeURIComponent(referral.share_text)}`}
                        target="_blank"
                        rel="noopener noreferrer"
                        aria-label="Bagikan lewat WhatsApp"
                        className="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-whatsapp text-white"
                    >
                        <Icon name="share" size={20} />
                    </a>
                </div>
                <p className="relative mt-2 text-label-sm opacity-70">Sudah dipakai {referral.uses} teman</p>

                {referral.vouchers.length > 0 && (
                    <div className="relative mt-4 flex flex-col gap-2">
                        <p className="text-label-sm uppercase tracking-wider text-tertiary-fixed-dim">Voucher kamu</p>
                        {referral.vouchers.map((voucher) => (
                            <div key={voucher.code} className="flex items-center justify-between rounded-2xl bg-white/10 px-4 py-2.5">
                                <div>
                                    <p className="font-display text-label-lg tracking-wider">{voucher.code}</p>
                                    {voucher.ends_at && <p className="text-xs opacity-70">Berlaku sampai {dateTime(voucher.ends_at)}</p>}
                                </div>
                                <span className="rounded-full bg-primary px-3 py-1 text-label-sm text-on-primary">{voucher.value}</span>
                            </div>
                        ))}
                    </div>
                )}
            </div>
        </section>
    );
}
