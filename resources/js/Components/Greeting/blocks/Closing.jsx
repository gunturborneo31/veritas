import Icon from '@/Components/Icon';
import { router, useForm } from '@inertiajs/react';
import { useState } from 'react';

/**
 * Penutup + balasan. Form balasan hanya aktif di halaman penerima asli;
 * di Studio/Pratinjau/Demo ditampilkan penjelasan agar tidak terlihat seperti tombol rusak.
 */
const PREVIEW_EMOJIS = ['ðŸ¥º', 'ðŸ˜­', 'ðŸ¥°', 'ðŸ˜‚', 'ðŸ¤—', 'ðŸ”¥'];

/**
 * Reaksi satu ketuk â€” paling mudah dilakukan penerima, langsung sampai ke pengirim.
 */
function ReactionBar({ base, mode, slug, reacted, reactionEmojis }) {
    const [sent, setSent] = useState(reacted);
    const emojis = reactionEmojis.length ? reactionEmojis : PREVIEW_EMOJIS;

    const react = (emoji) => {
        if (sent.includes(emoji)) {
            return;
        }

        setSent((current) => [...current, emoji]);
        navigator.vibrate?.(20);

        if (mode === 'recipient') {
            router.post(route('recipient.react', slug), { kind: 'emoji', value: emoji }, { preserveScroll: true, preserveState: true });
        }
    };

    return (
        <div className="flex flex-col items-center gap-2 rounded-3xl bg-surface-container-lowest/90 p-4 shadow-sm">
            <p className="text-label-sm uppercase tracking-[0.2em] text-primary">Bagaimana perasaanmu?</p>
            <div className="flex flex-wrap justify-center gap-1">
                {emojis.map((emoji) => (
                    <button
                        key={emoji}
                        type="button"
                        onClick={() => react(emoji)}
                        aria-label={`Reaksi ${emoji}`}
                        className={`flex h-9 w-9 items-center justify-center rounded-full text-lg transition-all active:scale-125 ${
                            sent.includes(emoji) ? 'scale-110 bg-primary-fixed ring-2 ring-primary' : 'bg-surface-container-low hover:scale-110'
                        }`}
                    >
                        {emoji}
                    </button>
                ))}
            </div>
            {sent.length > 0 && <p className="text-label-sm text-on-surface-variant">Terkirim ke {base.sender_name || 'pengirim'} ðŸ’Œ</p>}
        </div>
    );
}

export default function Closing({ slot, values, base, mode, slug, replied = false, reacted = [], reactionEmojis = [] }) {
    const form = useForm({ sender_name: base.recipient_name ?? '', message: '' });
    const allowsReply = values.allow_reply !== false;
    const isRecipient = mode === 'recipient';

    const submit = (event) => {
        event.preventDefault();
        form.post(route('recipient.reply', slug), { preserveScroll: true, onSuccess: () => form.reset('message') });
    };

    return (
        <div className="flex w-full flex-col gap-4">
            <div className="relative flex flex-col items-center gap-3 overflow-hidden rounded-3xl bg-surface-container-lowest/90 px-6 py-7 text-center shadow-[0_18px_40px_-18px_rgba(40,21,38,0.35)]">
                <div aria-hidden="true" className="pointer-events-none absolute inset-x-0 top-0 h-20 bg-gradient-to-b from-primary-fixed/70 to-transparent" />
                {[
                    ['left-4 top-3', 'âœ¨'],
                    ['right-5 top-4', 'ðŸ’«'],
                    ['bottom-3 left-5', 'ðŸ¤'],
                    ['bottom-4 right-4', 'âœ¨'],
                ].map(([position, emoji]) => (
                    <span key={position} aria-hidden="true" className={`pointer-events-none absolute text-base opacity-80 ${position}`}>
                        {emoji}
                    </span>
                ))}
                <span className="flex h-14 w-14 items-center justify-center rounded-full bg-primary-fixed text-primary">
                    <Icon name="favorite" filled size={30} className="animate-pulse" />
                </span>
                <p className="relative whitespace-pre-line font-display text-headline-sm leading-snug text-on-surface">
                    {values.closing || 'Terima kasih sudah membuka ucapan ini.'}
                </p>
                {base.sender_name && <p className="relative font-display text-label-lg italic text-primary">â€” {base.sender_name}</p>}
            </div>

            <ReactionBar base={base} mode={mode} slug={slug} reacted={reacted} reactionEmojis={reactionEmojis} />

            {allowsReply && (
                <div className="flex flex-col gap-2 rounded-3xl bg-surface-container-lowest/90 p-4 shadow-sm">
                    <p className="flex items-center gap-1.5 text-label-sm uppercase tracking-[0.2em] text-primary">
                        <Icon name="forum" size={16} />
                        {slot?.title ?? 'Balas ucapan ini'}
                    </p>

                    {!isRecipient && (
                        <div className="flex items-start gap-2 rounded-2xl border border-dashed border-primary/30 bg-primary-fixed/40 p-3">
                            <Icon name="chat_bubble" size={18} className="mt-0.5 text-primary" />
                            <p className="text-body-sm text-on-surface-variant">
                                Di sini <strong className="text-on-surface">{base.recipient_name || 'penerima'}</strong> bisa membalas langsung. Balasannya masuk ke dashboard
                                {base.sender_name ? ` ${base.sender_name}` : ' pengirim'}.
                            </p>
                        </div>
                    )}

                    {isRecipient && replied && (
                        <p className="rounded-2xl bg-primary-fixed/50 p-3 text-center text-body-sm text-on-surface">Balasanmu sudah terkirim ke {base.sender_name || 'pengirim'} ðŸ’Œ</p>
                    )}

                    {isRecipient && !replied && (
                        <form onSubmit={submit} className="flex items-end gap-2">
                            <textarea
                                rows={2}
                                value={form.data.message}
                                onChange={(event) => form.setData('message', event.target.value)}
                                placeholder={`Tulis balasan untuk ${base.sender_name || 'pengirim'}â€¦`}
                                className="flex-1 resize-none rounded-2xl border-0 bg-surface-container-low px-4 py-3 text-body-md focus:ring-2 focus:ring-primary/40"
                            />
                            <button
                                type="submit"
                                aria-label="Kirim balasan"
                                disabled={form.processing || !form.data.message.trim()}
                                className="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-primary text-on-primary shadow-md transition-transform active:scale-95 disabled:opacity-40"
                            >
                                <Icon name="send" size={20} />
                            </button>
                        </form>
                    )}
                    {form.errors.message && <p className="text-label-sm text-error">{form.errors.message}</p>}
                </div>
            )}

            <a
                href={mode === 'recipient' && slug ? route('catalog.index', { dari: slug }) : route('catalog.index')}
                className="group flex items-center gap-3 rounded-3xl bg-inverse-surface p-4 text-left text-inverse-on-surface shadow-lg"
            >
                <span className="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-primary text-on-primary">
                    <Icon name="redeem" size={22} />
                </span>
                <span className="flex-1">
                    <span className="block font-display text-label-lg">Balas dengan kejutan juga?</span>
                    <span className="block text-label-sm opacity-80">Bikin kartu interaktif untuk {base.sender_name || 'dia'} â€” coba gratis, tanpa daftar.</span>
                </span>
                <Icon name="arrow_forward" size={20} className="transition-transform group-hover:translate-x-1" />
            </a>
        </div>
    );
}
