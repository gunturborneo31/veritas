import BottomSheet from '@/Components/BottomSheet';
import Icon from '@/Components/Icon';
import Field, { inputClass, SubmitButton } from '@/Components/Invitation/Field';
import { router, useForm } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';

/**
 * Tambah atau ubah satu tamu.
 */
export function GuestFormSheet({ invitation, guest, open, onClose }) {
    const { data, setData, post, put, processing, errors, reset, clearErrors } = useForm({
        name: '',
        phone: '',
        group: '',
        quota: invitation.settings.default_guest_quota,
    });

    useEffect(() => {
        if (open) {
            clearErrors();
            setData({
                name: guest?.name ?? '',
                phone: guest?.phone ?? '',
                group: guest?.group ?? '',
                quota: guest?.quota ?? invitation.settings.default_guest_quota,
            });
        }
    }, [open, guest]);

    const submit = (event) => {
        event.preventDefault();
        const options = { preserveScroll: true, onSuccess: () => (guest ? onClose() : reset('name', 'phone')) };

        guest ? put(route('invitations.guests.update', [invitation.id, guest.id]), options) : post(route('invitations.guests.store', invitation.id), options);
    };

    return (
        <BottomSheet open={open} onClose={onClose} title={guest ? 'Ubah Tamu' : 'Tambah Tamu'} icon="person_add">
            <form onSubmit={submit} className="flex flex-col gap-space-md">
                <Field label="Nama tamu" error={errors.name} hint="Ditampilkan di sampul: “Kepada Yth. …”">
                    <input autoFocus value={data.name} onChange={(event) => setData('name', event.target.value)} maxLength={80} className={inputClass} placeholder="Bapak Budi & Keluarga" />
                </Field>
                <Field label="No. WhatsApp (opsional)" error={errors.phone} hint="Agar tombol kirim langsung membuka chat tamu ini.">
                    <input inputMode="tel" value={data.phone} onChange={(event) => setData('phone', event.target.value)} maxLength={20} className={inputClass} placeholder="0812…" />
                </Field>
                <div className="grid grid-cols-[1fr_auto] gap-space-sm">
                    <Field label="Grup (opsional)" error={errors.group}>
                        <input
                            list="guest-groups"
                            value={data.group}
                            onChange={(event) => setData('group', event.target.value)}
                            maxLength={40}
                            className={inputClass}
                            placeholder="Keluarga, Kantor…"
                        />
                        <datalist id="guest-groups">
                            {invitation.groups.map((group) => (
                                <option key={group} value={group} />
                            ))}
                        </datalist>
                    </Field>
                    <Field label="Jumlah orang" error={errors.quota}>
                        <input
                            type="number"
                            min={1}
                            max={invitation.max_guest_quota}
                            value={data.quota}
                            onChange={(event) => setData('quota', event.target.value)}
                            className={`${inputClass} w-24`}
                        />
                    </Field>
                </div>
                <SubmitButton processing={processing}>
                    <Icon name="check" size={20} />
                    {guest ? 'Simpan Perubahan' : 'Tambah & Lanjut Isi'}
                </SubmitButton>
            </form>
        </BottomSheet>
    );
}

/**
 * Tempel daftar tamu dari Excel / Google Sheets (kolom dipisah Tab).
 */
export function ImportGuestsSheet({ invitation, open, onClose }) {
    const { data, setData, post, processing, errors, reset } = useForm({ guests: '' });
    const rowCount = data.guests.split('\n').filter((line) => line.trim() !== '').length;

    const submit = (event) => {
        event.preventDefault();
        post(route('invitations.guests.import', invitation.id), {
            preserveScroll: true,
            onSuccess: () => {
                reset();
                onClose();
            },
        });
    };

    return (
        <BottomSheet open={open} onClose={onClose} title="Impor Daftar Tamu" icon="upload_file">
            <form onSubmit={submit} className="flex flex-col gap-space-md">
                <div className="rounded-2xl bg-surface-container-low p-space-sm text-body-sm text-on-surface-variant">
                    <p className="text-label-md text-on-surface">Salin kolom dari Excel / Google Sheets lalu tempel di bawah.</p>
                    <p className="mt-1">
                        Urutan kolom: <strong>Nama</strong> · No. WA · Grup · Jumlah orang. Hanya nama yang wajib; nama yang sudah ada otomatis dilewati.
                    </p>
                </div>
                <Field label={`Daftar tamu${rowCount > 0 ? ` (${rowCount} baris)` : ''}`} error={errors.guests}>
                    <textarea
                        rows={8}
                        value={data.guests}
                        onChange={(event) => setData('guests', event.target.value)}
                        className={`${inputClass} resize-none font-mono text-body-sm`}
                        placeholder={'Bapak Budi & Keluarga\t081234567890\tKeluarga\t4\nRina Wulandari\t\tKantor\t2\nAndi, S.T.'}
                    />
                </Field>
                <SubmitButton processing={processing || rowCount === 0}>
                    <Icon name="playlist_add" size={20} />
                    Impor {rowCount > 0 ? `${rowCount} Tamu` : ''}
                </SubmitButton>
            </form>
        </BottomSheet>
    );
}

/**
 * Mode penerima tamu di lokasi: pindai QR tamu (kamera, bila browser mendukung BarcodeDetector)
 * atau ketik kodenya. Hasil ditampilkan di dalam lembar agar bisa langsung lanjut ke tamu berikutnya.
 */
export function CheckInSheet({ invitation, open, onClose }) {
    const [code, setCode] = useState('');
    const [processing, setProcessing] = useState(false);
    const [error, setError] = useState(null);
    const [isScanning, setIsScanning] = useState(false);
    const [lastResult, setLastResult] = useState(null);
    const videoRef = useRef(null);
    const canScan = typeof window !== 'undefined' && 'BarcodeDetector' in window;

    const submit = (value) => {
        router.post(
            route('invitations.check-in', invitation.id),
            { code: value },
            {
                preserveScroll: true,
                preserveState: true,
                onStart: () => setProcessing(true),
                onFinish: () => setProcessing(false),
                onSuccess: (page) => {
                    const { flash } = page.props;
                    setError(null);
                    setCode('');
                    setLastResult(flash?.success ? { tone: 'success', text: flash.success } : flash?.error ? { tone: 'error', text: flash.error } : null);
                },
                onError: (errors) => {
                    setLastResult(null);
                    setError(errors.code ?? null);
                },
            },
        );
    };

    useEffect(() => {
        if (!open) {
            setIsScanning(false);
            setLastResult(null);
            setError(null);
        }
    }, [open]);

    useEffect(() => {
        if (!isScanning) {
            return;
        }

        let stream;
        let timer;
        let isCancelled = false;
        const detector = new window.BarcodeDetector({ formats: ['qr_code'] });

        navigator.mediaDevices
            .getUserMedia({ video: { facingMode: 'environment' } })
            .then((mediaStream) => {
                if (isCancelled) {
                    mediaStream.getTracks().forEach((track) => track.stop());

                    return;
                }

                stream = mediaStream;
                videoRef.current.srcObject = mediaStream;
                videoRef.current.play();

                let lastCode = null;
                timer = setInterval(async () => {
                    const [detected] = await detector.detect(videoRef.current).catch(() => []);

                    if (detected?.rawValue && detected.rawValue !== lastCode) {
                        lastCode = detected.rawValue;
                        submit(detected.rawValue);
                    }
                }, 600);
            })
            .catch(() => setIsScanning(false));

        return () => {
            isCancelled = true;
            clearInterval(timer);
            stream?.getTracks().forEach((track) => track.stop());
        };
    }, [isScanning]);

    return (
        <BottomSheet open={open} onClose={onClose} title="Check-in Tamu" icon="qr_code_scanner">
            {lastResult && (
                <div
                    role="status"
                    className={`flex items-center gap-2 rounded-2xl px-space-sm py-3 ${
                        lastResult.tone === 'success' ? 'bg-success/10 text-success' : 'bg-error-container text-on-error-container'
                    }`}
                >
                    <Icon name={lastResult.tone === 'success' ? 'how_to_reg' : 'warning'} size={22} />
                    <span className="text-label-lg">{lastResult.text}</span>
                </div>
            )}

            {isScanning ? (
                <div className="relative overflow-hidden rounded-2xl bg-inverse-surface">
                    <video ref={videoRef} playsInline muted className="aspect-square w-full object-cover" />
                    <div className="pointer-events-none absolute inset-10 rounded-3xl border-4 border-white/70" />
                    <button
                        type="button"
                        onClick={() => setIsScanning(false)}
                        className="absolute bottom-3 left-1/2 -translate-x-1/2 rounded-full bg-surface/90 px-4 py-2 text-label-md text-on-surface"
                    >
                        Berhenti memindai
                    </button>
                </div>
            ) : (
                canScan && (
                    <button
                        type="button"
                        onClick={() => setIsScanning(true)}
                        className="flex h-12 items-center justify-center gap-2 rounded-full bg-primary text-label-lg text-on-primary shadow-md"
                    >
                        <Icon name="photo_camera" size={20} />
                        Pindai QR Tamu
                    </button>
                )
            )}

            <form
                onSubmit={(event) => {
                    event.preventDefault();
                    submit(code);
                }}
                className="flex flex-col gap-space-sm"
            >
                <Field label={canScan ? 'Atau ketik kode tamu' : 'Kode tamu'} error={error} hint="Kode ada di akhir link tamu (…?tamu=kode).">
                    <div className="flex gap-space-xs">
                        <input
                            value={code}
                            onChange={(event) => setCode(event.target.value)}
                            className={inputClass}
                            autoCapitalize="none"
                            autoComplete="off"
                            placeholder="ab12cd34"
                        />
                        <button
                            type="submit"
                            disabled={processing || code.trim() === ''}
                            className="shrink-0 rounded-2xl bg-surface-container px-4 text-label-md text-on-surface disabled:opacity-50"
                        >
                            Check-in
                        </button>
                    </div>
                </Field>
            </form>
        </BottomSheet>
    );
}
