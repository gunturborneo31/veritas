import Icon from '@/Components/Icon';
import { compressImage } from '@/Components/Greeting/fields/upload';
import { router, useForm } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';

const VIDEO_MAX_MB = 30;

/**
 * Lampiran balasan: satu foto (dikecilkan di perangkat dulu) atau satu video, dengan pratinjau sebelum dikirim.
 */
function AttachmentPicker({ attachment, onChange, error }) {
    const photoInput = useRef(null);
    const videoInput = useRef(null);
    const [previewUrl, setPreviewUrl] = useState(null);
    const [localError, setLocalError] = useState(null);
    const [isPreparing, setIsPreparing] = useState(false);

    useEffect(() => {
        if (!attachment) {
            setPreviewUrl(null);

            return undefined;
        }

        const url = URL.createObjectURL(attachment);
        setPreviewUrl(url);

        return () => URL.revokeObjectURL(url);
    }, [attachment]);

    const pick = async (event) => {
        const file = event.target.files?.[0];
        event.target.value = '';
        setLocalError(null);

        if (!file) {
            return;
        }

        if (file.type.startsWith('video/')) {
            if (file.size > VIDEO_MAX_MB * 1024 * 1024) {
                setLocalError(`Video terlalu besar (maksimal ${VIDEO_MAX_MB} MB). Coba rekam lebih singkat.`);

                return;
            }

            onChange(file);

            return;
        }

        setIsPreparing(true);
        onChange(await compressImage(file));
        setIsPreparing(false);
    };

    const isVideo = attachment?.type?.startsWith('video/');

    return (
        <div className="flex flex-col gap-2">
            <input ref={photoInput} type="file" accept="image/jpeg,image/png,image/webp" className="hidden" onChange={pick} />
            <input ref={videoInput} type="file" accept="video/mp4,video/webm,video/quicktime" className="hidden" onChange={pick} />

            {attachment && previewUrl ? (
                <div className="relative overflow-hidden rounded-2xl bg-black/5">
                    {isVideo ? (
                        <video src={previewUrl} controls playsInline className="max-h-64 w-full bg-black" />
                    ) : (
                        <img src={previewUrl} alt="Lampiran balasan" className="max-h-64 w-full object-cover" />
                    )}
                    <button
                        type="button"
                        onClick={() => onChange(null)}
                        aria-label="Hapus lampiran"
                        className="absolute right-2 top-2 flex h-8 w-8 items-center justify-center rounded-full bg-black/60 text-white"
                    >
                        <Icon name="close" size={18} />
                    </button>
                </div>
            ) : (
                <div className="flex gap-2">
                    <button
                        type="button"
                        onClick={() => photoInput.current?.click()}
                        disabled={isPreparing}
                        className="flex h-10 flex-1 items-center justify-center gap-1.5 rounded-full bg-surface-container-low text-label-md text-on-surface disabled:opacity-60"
                    >
                        <Icon name={isPreparing ? 'progress_activity' : 'add_photo_alternate'} size={18} className={isPreparing ? 'animate-spin text-primary' : 'text-primary'} />
                        Foto
                    </button>
                    <button
                        type="button"
                        onClick={() => videoInput.current?.click()}
                        className="flex h-10 flex-1 items-center justify-center gap-1.5 rounded-full bg-surface-container-low text-label-md text-on-surface"
                    >
                        <Icon name="videocam" size={18} className="text-primary" />
                        Video
                    </button>
                </div>
            )}
            {(localError || error) && <p className="text-label-sm text-error">{localError || error}</p>}
        </div>
    );
}

/**
 * Penutup + balasan. Form balasan hanya aktif di halaman penerima asli;
 * di Studio/Pratinjau/Demo ditampilkan penjelasan agar tidak terlihat seperti tombol rusak.
 */
const PREVIEW_EMOJIS = ['🥺', '😭', '🥰', '😂', '🤗', '🔥'];

/**
 * Reaksi satu ketuk — paling mudah dilakukan penerima, langsung sampai ke pengirim.
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
            {sent.length > 0 && <p className="text-label-sm text-on-surface-variant">Terkirim ke {base.sender_name || 'pengirim'} 💌</p>}
        </div>
    );
}

export default function Closing({ slot, values, base, mode, slug, replied = false, reacted = [], reactionEmojis = [] }) {
    const form = useForm({ sender_name: base.recipient_name ?? '', message: '', attachment: null });
    const allowsReply = values.allow_reply !== false;
    const isRecipient = mode === 'recipient';
    const canSend = Boolean(form.data.message.trim() || form.data.attachment);

    const submit = (event) => {
        event.preventDefault();
        form.post(route('recipient.reply', slug), { preserveScroll: true, forceFormData: true, onSuccess: () => form.reset('message', 'attachment') });
    };

    return (
        <div className="flex w-full flex-col gap-4">
            <div className="relative flex flex-col items-center gap-3 overflow-hidden rounded-3xl bg-surface-container-lowest/90 px-6 py-7 text-center shadow-[0_18px_40px_-18px_rgba(40,21,38,0.35)]">
                <div aria-hidden="true" className="pointer-events-none absolute inset-x-0 top-0 h-20 bg-gradient-to-b from-primary-fixed/70 to-transparent" />
                {[
                    ['left-4 top-3', '✨'],
                    ['right-5 top-4', '💫'],
                    ['bottom-3 left-5', '🤍'],
                    ['bottom-4 right-4', '✨'],
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
                {base.sender_name && <p className="relative font-display text-label-lg italic text-primary">— {base.sender_name}</p>}
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
                                Di sini <strong className="text-on-surface">{base.recipient_name || 'penerima'}</strong> bisa membalas langsung dengan tulisan, foto, atau video. Balasannya masuk ke dashboard
                                {base.sender_name ? ` ${base.sender_name}` : ' pengirim'}.
                            </p>
                        </div>
                    )}

                    {isRecipient && replied && (
                        <p className="rounded-2xl bg-primary-fixed/50 p-3 text-center text-body-sm text-on-surface">Balasanmu sudah terkirim ke {base.sender_name || 'pengirim'} 💌</p>
                    )}

                    {isRecipient && !replied && (
                        <form onSubmit={submit} className="flex flex-col gap-2">
                            <div className="flex items-end gap-2">
                                <textarea
                                    rows={2}
                                    value={form.data.message}
                                    onChange={(event) => form.setData('message', event.target.value)}
                                    placeholder={`Tulis balasan untuk ${base.sender_name || 'pengirim'}…`}
                                    className="flex-1 resize-none rounded-2xl border-0 bg-surface-container-low px-4 py-3 text-body-md focus:ring-2 focus:ring-primary/40"
                                />
                                <button
                                    type="submit"
                                    aria-label="Kirim balasan"
                                    disabled={form.processing || !canSend}
                                    className="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-primary text-on-primary shadow-md transition-transform active:scale-95 disabled:opacity-40"
                                >
                                    <Icon name={form.processing ? 'progress_activity' : 'send'} size={20} className={form.processing ? 'animate-spin' : ''} />
                                </button>
                            </div>
                            <AttachmentPicker attachment={form.data.attachment} onChange={(file) => form.setData('attachment', file)} error={form.errors.attachment} />
                            {form.progress && (
                                <div className="h-1.5 overflow-hidden rounded-full bg-surface-container">
                                    <div className="h-full bg-primary transition-all" style={{ width: `${form.progress.percentage ?? 0}%` }} />
                                </div>
                            )}
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
                    <span className="block text-label-sm opacity-80">Bikin kartu interaktif untuk {base.sender_name || 'dia'} — coba gratis, tanpa daftar.</span>
                </span>
                <Icon name="arrow_forward" size={20} className="transition-transform group-hover:translate-x-1" />
            </a>
        </div>
    );
}
