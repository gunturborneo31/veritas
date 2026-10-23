import BottomSheet from '@/Components/BottomSheet';
import Icon from '@/Components/Icon';
import Illustration from '@/Components/Illustration';
import { resolveTheme, themeVars } from '@/lib/theme';
import { CountdownDigits, useRemaining } from '@/Components/Greeting/blocks/Countdown';
import { startBacksoundOnFirstGesture } from '@/lib/backsound';
import { dateTime } from '@/lib/format';
import { Head, router, useForm, usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';

const KEYS = ['1', '2', '3', '4', '5', '6', '7', '8', '9'];

/**
 * Layar gerbang penerima (desain layarpenerimagembok). Jawaban diverifikasi di server;
 * hash PIN tidak pernah dikirim ke browser. Tanpa gembok, layar ini hanya tombol "Buka"
 * sehingga bot pratinjau WhatsApp tidak tercatat sebagai pembuka.
 */
export default function Gate(props) {
    return props.opens_at ? <ScheduledScreen {...props} /> : <UnlockScreen {...props} />;
}

/**
 * Kirim terjadwal: sebelum waktunya, penerima hanya melihat hitung mundur.
 */
function ScheduledScreen({ recipient_name, sender_name, occasion, opens_at, theme: rawTheme }) {
    const theme = resolveTheme(rawTheme);
    const { remaining } = useRemaining(opens_at);

    return (
        <div
            className="flex min-h-screen w-full flex-col items-center justify-center gap-5 px-margin text-center"
            style={{ ...themeVars(theme), background: `linear-gradient(180deg, ${theme.gradient.join(', ')})` }}
        >
            <Head title={`Untuk ${recipient_name}`}>
                <meta name="robots" content="noindex" />
            </Head>
            <Illustration theme={theme} className="h-36 w-36" />
            <div className="max-w-sm space-y-1">
                <p className="text-label-sm uppercase tracking-[0.3em] text-primary">{occasion || 'Kejutan'} untukmu</p>
                <h1 className="font-display text-headline-lg-mobile text-on-surface">Hai {recipient_name}, sabar sebentar ya…</h1>
                <p className="text-body-sm text-on-surface-variant">
                    {sender_name ? `${sender_name} menyiapkan` : 'Ada'} kejutan yang baru boleh dibuka pada {dateTime(opens_at)}.
                </p>
            </div>
            <div className="w-full max-w-sm rounded-3xl bg-white/80 p-4 shadow-card backdrop-blur">
                <CountdownDigits target={opens_at} />
            </div>
            {remaining === 0 && (
                <button type="button" onClick={() => router.reload()} className="rounded-full bg-primary px-6 py-3 text-label-lg text-on-primary shadow-lg">
                    Waktunya tiba — buka sekarang 🎉
                </button>
            )}
            <p className="flex items-center gap-1 text-label-sm text-on-surface-variant">
                <Icon name="bookmark_add" size={14} />
                Simpan link ini dan kembali lagi nanti
            </p>
        </div>
    );
}

function UnlockScreen({ slug, type, recipient_name, sender_name, occasion, gate, theme: rawTheme, music = null }) {
    const { brand } = usePage().props;

    // Musik latar mulai sejak halaman ini (atau pada sentuhan pertama) dan berlanjut saat kartu dibuka.
    useEffect(() => startBacksoundOnFirstGesture(music?.url), [music?.url]);
    const isInvitation = type === 'undangan';
    const theme = resolveTheme(rawTheme);
    const form = useForm({ answer: '' });
    const [showHint, setShowHint] = useState(false);
    const [shake, setShake] = useState(false);
    const length = gate?.length ?? 4;

    // `transform()` di Inertia v2 tidak mengembalikan form, jadi tidak bisa dirangkai dengan `.post()`.
    const submit = (answer) => {
        form.transform(() => ({ answer }));
        form.post(route('recipient.open', slug), {
            onError: () => {
                navigator.vibrate?.([40, 60, 40]);
                setShake(true);
                setTimeout(() => {
                    setShake(false);
                    form.setData('answer', '');
                }, 450);
            },
        });
    };

    const press = (digit) => {
        if (form.processing || form.data.answer.length >= length) {
            return;
        }

        navigator.vibrate?.(20);
        const next = form.data.answer + digit;
        form.setData('answer', next);

        if (next.length === length) {
            submit(next);
        }
    };

    useEffect(() => {
        if (!gate) {
            return;
        }

        const onKeyDown = (event) => {
            if (/^\d$/.test(event.key)) {
                press(event.key);
            } else if (event.key === 'Backspace') {
                form.setData('answer', form.data.answer.slice(0, -1));
            }
        };
        window.addEventListener('keydown', onKeyDown);

        return () => window.removeEventListener('keydown', onKeyDown);
    });

    return (
        <div
            className="relative min-h-screen w-full select-none overflow-hidden"
            style={{ ...themeVars(theme), background: `linear-gradient(180deg, ${theme.gradient.join(', ')})` }}
        >
            <Head title={isInvitation ? `Undangan ${occasion ?? ''} ${sender_name ?? ''}`.trim() : `Untuk ${recipient_name}`}>
                <meta name="robots" content="noindex" />
            </Head>

            <div className="pointer-events-none absolute inset-0 z-0 overflow-hidden">
                <div className="absolute -left-16 -top-16 h-72 w-72 rounded-full bg-primary-fixed-dim/30 mix-blend-multiply blur-3xl" />
                <div className="absolute -right-20 top-1/3 h-80 w-80 rounded-full bg-surface-container-high/60 mix-blend-multiply blur-3xl" />
                <div className="absolute bottom-20 left-4 h-72 w-72 rounded-full bg-tertiary-fixed-dim/25 mix-blend-multiply blur-3xl" />
            </div>

            <div className="relative z-10 mx-auto flex min-h-[100dvh] w-full max-w-md flex-col items-center justify-between px-margin pb-[calc(env(safe-area-inset-bottom)_+_1.5rem)] pt-4">
                <div className="flex w-full flex-col items-center space-y-1.5 text-center">
                    <div className="inline-flex items-center space-x-1.5 rounded-full bg-surface-container-lowest/70 px-3 py-1 shadow-sm backdrop-blur-md">
                        <span className="h-1.5 w-1.5 animate-pulse rounded-full bg-primary" />
                        <span className="text-label-sm uppercase tracking-widest text-on-surface-variant">{brand} • {isInvitation ? 'Undangan Digital' : 'Kado Digital'}</span>
                    </div>
                    <div className="flex items-center space-x-1 text-on-surface-variant">
                        <Icon name="headphones" size={14} className="text-tertiary" />
                        <span className="text-xs text-on-surface-variant">Nyalakan suara untuk pengalaman terbaik</span>
                    </div>
                </div>

                <div className="my-auto flex w-full flex-col items-center space-y-4 text-center">
                    <div className="relative flex items-center justify-center pt-2">
                        <div className="pointer-events-none absolute h-44 w-44 scale-125 rounded-full bg-gradient-to-tr from-tertiary-fixed-dim/40 via-primary-fixed/30 to-surface-container-high/60 blur-2xl" />
                        <div className="relative flex h-36 w-56 items-center justify-center rounded-2xl bg-surface-container-lowest shadow-xl">
                            <div className="absolute inset-2 flex flex-col items-center justify-center rounded-xl bg-surface-container-low/40 p-3">
                                <div className="mb-1 h-0.5 w-12 rounded-full bg-outline-variant/40" />
                                <div className="h-0.5 w-20 rounded-full bg-outline-variant/30" />
                            </div>
                            <div className="relative z-10 flex h-16 w-16 items-center justify-center rounded-full bg-gradient-to-br from-tertiary-fixed via-tertiary to-tertiary-container shadow-lg">
                                <Icon name={gate ? 'lock' : 'favorite'} filled size={26} className="text-tertiary-fixed" />
                                <div className="absolute inset-0 -z-10 animate-ping rounded-full bg-tertiary-fixed/30" />
                            </div>
                        </div>
                    </div>

                    <div className="flex max-w-sm flex-col items-center space-y-1 px-4">
                        <div className="inline-flex items-center space-x-1.5 rounded-full bg-primary-fixed/60 px-3 py-0.5">
                            <Icon name="favorite" filled size={13} className="text-primary" />
                            <span className="text-label-sm text-on-primary-fixed">{occasion || 'Sebuah Rahasia Manis'}</span>
                        </div>
                        {isInvitation ? (
                            <>
                                {sender_name && <h1 className="mt-1 font-display text-headline-lg-mobile tracking-tight text-on-surface">{sender_name}</h1>}
                                <p className="pt-3 text-label-sm uppercase tracking-widest text-on-surface-variant">Kepada Yth. Bapak/Ibu/Saudara/i</p>
                                <p className="text-headline-sm text-on-surface">{recipient_name}</p>
                                <p className="max-w-xs pt-1 text-body-sm text-on-surface-variant">Tanpa mengurangi rasa hormat, kami mengundang Anda untuk hadir di acara kami.</p>
                            </>
                        ) : (
                            <>
                                <h1 className="mt-1 text-headline-lg-mobile tracking-tight text-on-surface">Ada ucapan untukmu, {recipient_name}</h1>
                                {sender_name && <p className="text-body-sm text-on-surface-variant">dari {sender_name}</p>}
                            </>
                        )}
                    </div>

                    {gate ? (
                        <div className="flex w-full flex-col items-center space-y-2.5 pt-1">
                            <div className={`flex items-center justify-center gap-3 transition-transform ${shake ? 'translate-x-1' : ''}`}>
                                {Array.from({ length }, (_, index) => (
                                    <div
                                        key={index}
                                        className={`flex h-14 w-12 items-center justify-center rounded-2xl shadow-md backdrop-blur-md transition-all ${
                                            shake ? 'bg-error-container' : index < form.data.answer.length ? 'bg-primary-fixed/40' : 'bg-surface-container-lowest/90'
                                        }`}
                                    >
                                        <div className={`h-3.5 w-3.5 rounded-full transition-all ${index < form.data.answer.length ? 'scale-125 bg-primary' : 'bg-outline-variant/60'}`} />
                                    </div>
                                ))}
                            </div>
                            <div className="flex h-5 items-center justify-center">
                                {form.errors.answer ? (
                                    <span className="flex items-center gap-1 text-label-sm font-bold text-error">
                                        <Icon name="error" size={14} />
                                        {form.errors.answer}
                                    </span>
                                ) : (
                                    <span className="text-label-sm text-on-surface-variant">{form.processing ? 'Memeriksa…' : `Masukkan ${length} digit PIN`}</span>
                                )}
                            </div>
                            {(gate.question || gate.hint) && (
                                <div className="flex max-w-xs flex-col items-center space-y-1 rounded-2xl bg-surface-container-lowest/65 px-4 py-2.5 shadow-sm backdrop-blur-md">
                                    <div className="flex items-center space-x-1 text-tertiary">
                                        <Icon name="quiz" size={15} />
                                        <span className="text-label-md">Pertanyaan Rahasia</span>
                                    </div>
                                    {gate.question && <p className="text-center text-body-sm leading-snug text-on-surface">“{gate.question}”</p>}
                                    {gate.hint && (
                                        <button type="button" onClick={() => setShowHint(true)} className="inline-flex items-center space-x-1 pt-0.5 text-primary">
                                            <Icon name="lightbulb" size={14} />
                                            <span className="text-label-sm font-semibold underline underline-offset-2">Minta petunjuk</span>
                                        </button>
                                    )}
                                </div>
                            )}
                        </div>
                    ) : (
                        <button
                            type="button"
                            disabled={form.processing}
                            onClick={() => submit('')}
                            className="flex items-center gap-2 rounded-full bg-primary px-8 py-4 text-label-lg text-on-primary shadow-lg active:scale-95"
                        >
                            <Icon name="drafts" size={20} />
                            {isInvitation ? 'Buka Undangan' : 'Buka Ucapan'}
                        </button>
                    )}
                </div>

                {gate && (
                    <div className="mb-3 mt-2 grid w-full max-w-xs grid-cols-3 gap-3">
                        {KEYS.map((key) => (
                            <button
                                key={key}
                                type="button"
                                onClick={() => press(key)}
                                className="flex h-14 items-center justify-center rounded-2xl bg-surface-container-lowest/70 text-headline-sm text-on-surface shadow-sm backdrop-blur-md transition-transform active:scale-95 active:bg-primary-fixed"
                            >
                                {key}
                            </button>
                        ))}
                        <button type="button" onClick={() => form.setData('answer', '')} className="flex h-14 items-center justify-center text-xs uppercase tracking-wider text-on-surface-variant">
                            Hapus
                        </button>
                        <button
                            type="button"
                            onClick={() => press('0')}
                            className="flex h-14 items-center justify-center rounded-2xl bg-surface-container-lowest/70 text-headline-sm text-on-surface shadow-sm backdrop-blur-md active:scale-95 active:bg-primary-fixed"
                        >
                            0
                        </button>
                        <button
                            type="button"
                            aria-label="Hapus satu digit"
                            onClick={() => form.setData('answer', form.data.answer.slice(0, -1))}
                            className="flex h-14 items-center justify-center rounded-2xl bg-surface-container-lowest/50 text-on-surface-variant shadow-sm active:scale-95"
                        >
                            <Icon name="backspace" size={22} />
                        </button>
                    </div>
                )}

                <footer className="flex w-full flex-col items-center space-y-1 pt-1 text-center">
                    <p className="flex items-center gap-1.5 text-xs text-on-surface-variant">
                        <Icon name="lock" size={13} className="text-tertiary" />
                        Dibuat khusus untuk <strong className="font-semibold text-on-surface">{recipient_name}</strong> • Dilindungi privasi
                    </p>
                </footer>
            </div>

            <BottomSheet open={showHint} onClose={() => setShowHint(false)} title="Petunjuk Rahasia" icon="lightbulb">
                <p className="text-body-sm leading-relaxed text-on-surface-variant">“{gate?.hint}”</p>
                <button type="button" onClick={() => setShowHint(false)} className="w-full rounded-full bg-primary py-3 text-label-lg text-on-primary shadow-md">
                    Saya Mengerti
                </button>
            </BottomSheet>
        </div>
    );
}
