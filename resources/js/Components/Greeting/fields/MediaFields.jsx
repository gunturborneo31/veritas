import Icon from '@/Components/Icon';
import { useRef, useState } from 'react';
import { uploadMedia } from './upload';

function useUploader(greetingId, onUploaded, kind = 'image') {
    const [isUploading, setIsUploading] = useState(false);
    const [error, setError] = useState(null);

    const upload = async (files) => {
        setError(null);
        setIsUploading(true);

        try {
            for (const file of files) {
                onUploaded(await uploadMedia(greetingId, file, kind));
            }
        } catch (exception) {
            setError(exception.message);
        } finally {
            setIsUploading(false);
        }
    };

    return { upload, isUploading, error };
}

function UploadButton({ label, hint, multiple = false, accept = 'image/jpeg,image/png,image/webp', onFiles, isUploading }) {
    const inputRef = useRef(null);

    return (
        <>
            <input
                ref={inputRef}
                type="file"
                accept={accept}
                multiple={multiple}
                className="hidden"
                onChange={(event) => {
                    onFiles([...event.target.files]);
                    event.target.value = '';
                }}
            />
            <button
                type="button"
                disabled={isUploading}
                onClick={() => inputRef.current?.click()}
                className="flex w-full flex-col items-center justify-center gap-1 rounded-2xl bg-surface-container-low py-4 text-on-surface transition-colors hover:bg-surface-container active:scale-[0.99] disabled:opacity-60"
            >
                <span className="flex h-10 w-10 items-center justify-center rounded-full bg-surface-container-lowest text-primary shadow-sm">
                    <Icon name={isUploading ? 'progress_activity' : 'add_photo_alternate'} size={20} className={isUploading ? 'animate-spin' : ''} />
                </span>
                <span className="text-label-md font-semibold text-primary">{isUploading ? 'Mengunggah…' : label}</span>
                <span className="text-label-sm text-on-surface-variant">{hint}</span>
            </button>
        </>
    );
}

export function ImageField({ value, onChange, greetingId, mediaCache, addMedia }) {
    const { upload, isUploading, error } = useUploader(greetingId, (media) => {
        addMedia(media);
        onChange(media.id);
    });
    const media = value ? mediaCache[value] : null;

    return (
        <div className="flex flex-col gap-2">
            {media ? (
                <div className="relative h-48 w-full overflow-hidden rounded-2xl bg-surface-container">
                    <img src={media.url} alt="" className="h-full w-full object-cover" />
                    <button
                        type="button"
                        onClick={() => onChange(null)}
                        aria-label="Hapus foto"
                        className="absolute right-2 top-2 flex h-8 w-8 items-center justify-center rounded-full bg-surface-container-lowest/85 text-primary shadow"
                    >
                        <Icon name="close" size={16} />
                    </button>
                </div>
            ) : (
                <UploadButton label="Unggah Foto" hint="JPG / PNG / WebP hingga 10MB" onFiles={(files) => upload(files.slice(0, 1))} isUploading={isUploading} />
            )}
            {error && <p className="text-label-sm text-error">{error}</p>}
        </div>
    );
}

export function ImagesField({ field, value = [], onChange, greetingId, mediaCache, addMedia }) {
    const max = field.max ?? 12;
    const items = value ?? [];
    const latest = useRef(items);
    latest.current = items;

    const { upload, isUploading, error } = useUploader(greetingId, (media) => {
        addMedia(media);
        latest.current = [...latest.current, { media: media.id, caption: '' }];
        onChange(latest.current);
    });

    return (
        <div className="flex flex-col gap-3">
            <div className="flex items-center justify-between">
                <p className="text-body-sm text-on-surface-variant">Minimal {field.min ?? 1} foto.</p>
                <span className="rounded-full bg-primary-fixed px-2.5 py-0.5 text-label-sm font-bold text-primary">
                    {items.length} / {max} Foto
                </span>
            </div>
            <div className="grid grid-cols-2 gap-3.5">
                {items.map((item, index) => (
                    <div
                        key={`${item.media}-${index}`}
                        className="relative rounded-2xl bg-surface-container-low p-2 shadow-sm transition-transform hover:rotate-0"
                        style={{ transform: `rotate(${index % 2 ? 2 : -1.5}deg)` }}
                    >
                        <div className="relative h-32 w-full overflow-hidden rounded-xl bg-surface-container">
                            {mediaCache[item.media] && <img src={mediaCache[item.media].url} alt="" className="h-full w-full object-cover" />}
                            <button
                                type="button"
                                aria-label="Hapus foto"
                                onClick={() => onChange(items.filter((_, itemIndex) => itemIndex !== index))}
                                className="absolute right-1.5 top-1.5 flex h-7 w-7 items-center justify-center rounded-full bg-surface-container-lowest/80 text-primary shadow"
                            >
                                <Icon name="close" size={16} />
                            </button>
                        </div>
                        <input
                            type="text"
                            maxLength={80}
                            value={item.caption ?? ''}
                            placeholder="Keterangan…"
                            onChange={(event) => onChange(items.map((current, itemIndex) => (itemIndex === index ? { ...current, caption: event.target.value } : current)))}
                            className="mt-2 w-full border-0 bg-transparent px-1 py-0 text-center text-label-sm text-on-surface-variant focus:ring-0"
                        />
                    </div>
                ))}
            </div>
            {items.length < max && (
                <UploadButton
                    multiple
                    label="Unggah Foto Kenangan"
                    hint="PNG / JPG hingga 10MB"
                    onFiles={(files) => upload(files.slice(0, max - items.length))}
                    isUploading={isUploading}
                />
            )}
            {error && <p className="text-label-sm text-error">{error}</p>}
        </div>
    );
}

/**
 * Rekam pesan suara langsung (MediaRecorder) atau unggah file audio.
 */
export function AudioField({ value, onChange, greetingId, mediaCache, addMedia }) {
    const [isRecording, setIsRecording] = useState(false);
    const recorderRef = useRef(null);
    const { upload, isUploading, error } = useUploader(
        greetingId,
        (media) => {
            addMedia(media);
            onChange(media.id);
        },
        'audio',
    );
    const media = value ? mediaCache[value] : null;

    const startRecording = async () => {
        const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
        const recorder = new MediaRecorder(stream);
        const chunks = [];

        recorder.ondataavailable = (event) => chunks.push(event.data);
        recorder.onstop = () => {
            stream.getTracks().forEach((track) => track.stop());
            const blob = new Blob(chunks, { type: recorder.mimeType || 'audio/webm' });
            upload([new File([blob], 'pesan-suara.webm', { type: blob.type })]);
        };

        recorder.start();
        recorderRef.current = recorder;
        setIsRecording(true);
        setTimeout(() => recorderRef.current?.state === 'recording' && stopRecording(), 60_000);
    };

    const stopRecording = () => {
        recorderRef.current?.stop();
        setIsRecording(false);
    };

    return (
        <div className="flex flex-col gap-3 rounded-2xl bg-surface-container-low p-4">
            {media ? (
                <audio controls src={media.url} className="w-full" />
            ) : (
                <p className="text-body-sm text-on-surface-variant">Belum ada rekaman. Maksimal 1 menit.</p>
            )}
            <div className="flex flex-wrap items-center gap-2">
                <button
                    type="button"
                    disabled={isUploading}
                    onClick={isRecording ? stopRecording : startRecording}
                    className={`inline-flex items-center gap-1.5 rounded-full px-3 py-1.5 text-label-sm ${
                        isRecording ? 'animate-pulse bg-error text-on-error' : 'bg-primary text-on-primary'
                    }`}
                >
                    <Icon name={isRecording ? 'stop' : 'mic'} size={16} />
                    {isRecording ? 'Berhenti & Simpan' : media ? 'Rekam Ulang' : 'Mulai Rekam'}
                </button>
                <label className="inline-flex cursor-pointer items-center gap-1.5 rounded-full bg-surface-container px-3 py-1.5 text-label-sm text-on-surface">
                    <Icon name="upload" size={16} />
                    Unggah file
                    <input type="file" accept="audio/*" className="hidden" onChange={(event) => upload([...event.target.files])} />
                </label>
                {media && (
                    <button type="button" onClick={() => onChange(null)} className="inline-flex items-center gap-1.5 rounded-full bg-error-container px-3 py-1.5 text-label-sm text-on-error-container">
                        <Icon name="delete" size={16} />
                        Hapus
                    </button>
                )}
                {isUploading && <span className="text-label-sm text-on-surface-variant">Mengunggah…</span>}
            </div>
            {error && <p className="text-label-sm text-error">{error}</p>}
        </div>
    );
}
