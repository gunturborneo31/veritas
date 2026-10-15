import Icon from '@/Components/Icon';
import PhoneFrame from '@/Components/Greeting/PhoneFrame';
import Player from '@/Components/Greeting/Player';
import StudioLayout from '@/Layouts/StudioLayout';
import { dateTime, rupiah } from '@/lib/format';
import { Link, router } from '@inertiajs/react';

/**
 * Langkah 4 — Pratinjau berwatermark. Interaksi bisa dicoba, tapi bukan link final.
 */
export default function Show({ greeting, template, missing, quote, isOwner }) {
    const isComplete = missing.length === 0;
    const photoCount = greeting.components
        .filter((slot) => slot.schema.some((field) => field.type === 'images' || field.type === 'image'))
        .reduce((count, slot) => {
            const slotValues = greeting.values[slot.key] ?? {};

            return count + slot.schema.reduce((sum, field) => sum + (field.type === 'images' ? (slotValues[field.name] ?? []).length : slotValues[field.name] ? 1 : 0), 0);
        }, 0);

    return (
        <StudioLayout
            title="Pratinjau Kartu"
            step={3}
            onBack={() => (isOwner ? router.visit(route('studio.style', greeting.id)) : router.visit(route('home')))}
            actions={
                isOwner && (
                    <>
                        <Link
                            href={route('studio.edit', greeting.id)}
                            className="flex h-12 items-center justify-center rounded-full bg-surface-container px-space-lg text-label-lg text-on-surface hover:bg-surface-container-high"
                        >
                            Ubah
                        </Link>
                        {isComplete ? (
                            <Link
                                href={route('checkout.show', greeting.id)}
                                className="flex h-12 flex-1 items-center justify-center gap-space-xs rounded-full bg-primary px-space-lg text-label-lg text-on-primary shadow-[0_4px_16px_rgba(168,50,35,0.25)] hover:bg-primary-container"
                            >
                                <span>Lanjut Bayar</span>
                                <Icon name="send" size={18} />
                            </Link>
                        ) : (
                            <Link
                                href={route('studio.edit', greeting.id)}
                                className="flex h-12 flex-1 items-center justify-center gap-space-xs rounded-full bg-tertiary-container px-space-lg text-label-lg text-on-tertiary"
                            >
                                Lengkapi {missing.length} isian
                            </Link>
                        )}
                    </>
                )
            }
        >
            <section className="mb-3 px-margin">
                <div className="flex items-start gap-2.5 rounded-2xl bg-surface-container p-3 shadow-sm">
                    <div className="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-tertiary-fixed text-tertiary">
                        <Icon name="visibility" size={18} />
                    </div>
                    <div className="min-w-0 flex-1">
                        <span className="rounded-full bg-surface-container-lowest px-2 py-0.5 text-label-sm font-bold uppercase tracking-wider text-primary">Mode Pratinjau</span>
                        <p className="mt-1 text-body-sm leading-snug text-on-surface">
                            Semua elemen interaktif bisa dicoba. Tautan ini kedaluwarsa {dateTime(greeting.expires_at)} dan bukan tautan final untuk penerima.
                        </p>
                    </div>
                </div>
            </section>

            <section className="mb-6 flex justify-center px-margin">
                <PhoneFrame>
                    <Player greeting={greeting} mode="preview" watermark />
                </PhoneFrame>
            </section>

            {!isComplete && (
                <section className="mb-4 px-margin">
                    <div className="rounded-2xl bg-error-container/50 p-space-md text-on-error-container">
                        <p className="text-label-md font-bold">Belum lengkap:</p>
                        <p className="text-body-sm">{missing.map((item) => item.label).join(', ')}</p>
                    </div>
                </section>
            )}

            <section className="mb-6 px-margin">
                <div className="rounded-3xl bg-surface-container-lowest p-4 shadow-sm">
                    <div className="flex items-center justify-between pb-3">
                        <div>
                            <span className="text-label-sm font-bold uppercase tracking-wider text-primary">Ringkasan</span>
                            <h3 className="text-headline-sm font-bold text-on-surface">Spesifikasi Kartu</h3>
                        </div>
                        <Icon name="verified" size={22} className="text-primary" />
                    </div>
                    <dl className="flex flex-col gap-2.5 text-body-sm">
                        {[
                            ['Penerima', greeting.base.recipient_name || '—'],
                            ['Momen & Template', `${greeting.base.occasion || '—'} • ${template.name}`],
                            ['Foto', `${photoCount} foto`],
                            ['Gembok', greeting.gate ? `PIN ${greeting.gate.length} digit` : 'Tidak dipakai'],
                            ['Harga', rupiah(quote.total)],
                        ].map(([label, value]) => (
                            <div key={label} className="flex items-center justify-between rounded-xl bg-surface-container-low/60 px-3 py-1">
                                <dt className="text-on-surface-variant">{label}</dt>
                                <dd className="font-semibold text-on-surface">{value}</dd>
                            </div>
                        ))}
                    </dl>
                    <p className="mt-3 flex items-center gap-2 text-label-sm leading-snug text-on-surface-variant">
                        <Icon name="lock_clock" size={16} className="text-tertiary" />
                        Tautan privat dibuat setelah pembayaran dikonfirmasi, siap dikirim via WhatsApp.
                    </p>
                </div>
            </section>
        </StudioLayout>
    );
}
