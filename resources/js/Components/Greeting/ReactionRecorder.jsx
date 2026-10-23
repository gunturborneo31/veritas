import Icon from '@/Components/Icon';
import { router } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';

const MAX_SECONDS = 90;

function pickMimeType() {
    return ['video/webm;codecs=vp9,opus', 'video/webm', 'video/mp4'].find((type) => window.MediaRecorder?.isTypeSupported?.(type)) ?? '';
}

/**
 * Video reaksi penerima: hanya merekam setelah penerima setuju, terlihat jelas saat merekam,
 * dan baru terkirim setelah ditonton ulang lalu dipilih "Kirim". Tanpa itu, video dibuang.
 */
export default function ReactionRecorder({ slug, senderName, alreadySent }) {
    const isSupported = typeof window !== 'undefined' && Boolean(navigator.mediaDevices?.getUserMedia) && typeof window.MediaRecorder !== 'undefined';
    const [phase, setPhase] = useState(alreadySent ? 'sent' : 'ask');
    const [seconds, setSeconds] = useState(0);
    const [videoUrl, setVideoUrl] = useState(null);
    const [consent, setConsent] = useState(false);
    const [isUploading, setIsUploading] = useState(false);
    const previewRef = useRef(null);
    const recorderRef = useRef(null);
    const streamRef = useRef(null);
    const blobRef = useRef(null);

    useEffect(() => () => streamRef.current?.getTracks().forEach((track) => track.stop()), []);

    useEffect(() => {
        if (phase !== 'recording') {
            return;
        }

        const timer = setInterval(() => setSeconds((value) => value + 1), 1000);

        return () => clearInterval(timer);
    }, [phase]);

    useEffect(() => {
        if (phase === 'recording' && seconds >= MAX_SECONDS) {
            stop();
        }
    }, [seconds, phase]); // eslint-disable-line react-hooks/exhaustive-deps

    if (!isSupported || phase === 'declined' || phase === 'sent') {
        return null;
    }

    const start = async () => {
        try {
            const stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'user', width: 480, height: 640 }, audio: true });
            streamRef.current = stream;
            const mimeType = pickMimeType();
            const recorder = new MediaRecorder(stream, mimeType ? { mimeType, videoBitsPerSecond: 900_000 } : undefined);
            const chunks = [];

            recorder.ondataavailable = (event) => event.data.size && chunks.push(event.data);
            recorder.onstop = () => {
                stream.getTracks().forEach((track) => track.stop());
                blobRef.current = new Blob(chunks, { type: recorder.mimeType || 'video/webm' });
                setVideoUrl(URL.createObjectURL(blobRef.current));
                setPhase('review');
            };

            recorder.start(1000);
            recorderRef.current = recorder;
            setPhase('recording');
            requestAnimationFrame(() => {
                if (previewRef.current) {
                    previewRef.current.srcObject = stream;
                }
            });
        } catch {
            setPhase('declined');
        }
    };

    const stop = () => recorderRef.current?.state === 'recording' && recorderRef.current.stop();

    const discard = () => {
        if (videoUrl) {
            URL.revokeObjectURL(videoUrl);
        }

        blobRef.current = null;
        setPhase('declined');
    };

    const send = () => {
        const extension = blobRef.current.type.includes('mp4') ? 'mp4' : 'webm';
        const file = new File([blobRef.current], `reaksi.${extension}`, { type: blobRef.current.type.split(';')[0] });

        router.post(
            route('recipient.video', slug),
            { consent: true, video: file },
            {
                forceFormData: true,
                preserveScroll: true,
                preserveState: true,
                onStart: () => setIsUploading(true),
                onFinish: () => setIsUploading(false),
                onSuccess: () => setPhase('sent'),
            },
        );
    };

    if (phase === 'ask') {
        return (
            <div className="fixed inset-x-0 bottom-0 z-[80] mx-auto max-w-md p-3 pb-[calc(env(safe-area-inset-bottom)_+_0.75rem)]">
                <div className="flex flex-col gap-3 rounded-3xl bg-inverse-surface p-4 text-inverse-on-surface shadow-2xl">
                    <div className="flex items-start gap-3">
                        <span className="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-primary text-on-primary">
                            <Icon name="videocam" size={20} />
                        </span>
                        <div>
                            <p className="font-display text-label-lg">Rekam reaksimu untuk {senderName || 'pengirim'}?</p>
                            <p className="text-label-sm opacity-80">
                                Kamera depan merekam selama kamu membuka kartu (maks. 90 detik). Videonya hanya terkirim kalau di akhir kamu tonton dan pilih “Kirim”.{' '}
                                <a href={`${route('legal.privacy')}#video`} target="_blank" rel="noopener noreferrer" className="underline">
                                    Selengkapnya
                                </a>
                            </p>
                        </div>
                    </div>
                    <div className="flex gap-2">
                        <button type="button" onClick={() => setPhase('declined')} className="h-10 flex-1 rounded-full bg-white/15 text-label-md">
                            Tidak usah
                        </button>
                        <button type="button" onClick={start} className="h-10 flex-1 rounded-full bg-primary text-label-md text-on-primary">
                            Ya, rekam 🎥
                        </button>
                    </div>
                </div>
            </div>
        );
    }

    if (phase === 'recording') {
        return (
            <div className="fixed right-3 top-24 z-[80] flex flex-col items-center gap-1.5">
                <div className="relative h-28 w-20 overflow-hidden rounded-2xl border-2 border-white shadow-xl">
                    <video ref={previewRef} autoPlay muted playsInline className="h-full w-full scale-x-[-1] object-cover" />
                    <span className="absolute left-1 top-1 flex items-center gap-1 rounded-full bg-error px-1.5 text-[11px] font-bold text-white">
                        <span className="h-1.5 w-1.5 animate-pulse rounded-full bg-white" />
                        REC {seconds}s
                    </span>
                </div>
                <button type="button" onClick={stop} className="rounded-full bg-inverse-surface px-3 py-1 text-xs text-inverse-on-surface shadow">
                    Selesai
                </button>
            </div>
        );
    }

    return (
        <div className="fixed inset-0 z-[90] flex items-end justify-center bg-black/60 p-3 backdrop-blur-sm">
            <div className="flex w-full max-w-md flex-col gap-3 rounded-3xl bg-surface p-4 pb-[calc(env(safe-area-inset-bottom)_+_1rem)] shadow-2xl">
                <p className="font-display text-headline-sm text-on-surface">Kirim video reaksimu?</p>
                <video src={videoUrl} controls playsInline className="max-h-[50vh] w-full rounded-2xl bg-black" />
                <label className="flex items-start gap-2 text-body-sm text-on-surface">
                    <input type="checkbox" checked={consent} onChange={(event) => setConsent(event.target.checked)} className="mt-0.5 rounded text-primary focus:ring-primary" />
                    Aku setuju video ini dikirim ke {senderName || 'pengirim'} dan hanya bisa dilihat olehnya.
                </label>
                <div className="flex gap-2">
                    <button type="button" onClick={discard} className="h-11 flex-1 rounded-full bg-surface-container text-label-md text-on-surface">
                        Hapus
                    </button>
                    <button
                        type="button"
                        disabled={!consent || isUploading}
                        onClick={send}
                        className="flex h-11 flex-1 items-center justify-center gap-1 rounded-full bg-primary text-label-md text-on-primary disabled:opacity-50"
                    >
                        <Icon name={isUploading ? 'progress_activity' : 'send'} size={18} className={isUploading ? 'animate-spin' : ''} />
                        Kirim
                    </button>
                </div>
            </div>
        </div>
    );
}
