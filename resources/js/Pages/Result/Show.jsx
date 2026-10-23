import Icon from '@/Components/Icon';
import QrStyler from '@/Components/QrStyler';
import TemplateCover from '@/Components/TemplateCover';
import PublicLayout from '@/Layouts/PublicLayout';
import { dateTime } from '@/lib/format';
import { Link } from '@inertiajs/react';
import { useState } from 'react';

/**
 * Hasil: link unik + QR + bagikan WhatsApp (desain hasillinkqr).
 * Untuk undangan, langkah utamanya adalah mengirim link pribadi ke setiap tamu dari Kelola Undangan;
 * link umum di sini untuk grup WhatsApp atau media sosial.
 */
export default function Show({ greeting, template, orderCode }) {
    const isInvitation = greeting.type === 'undangan';
    const [copied, setCopied] = useState(false);
    const [shareMode, setShareMode] = useState('link');
    const displayUrl = greeting.url.replace(/^https?:\/\//, '');
    const whatsappUrl = `https://api.whatsapp.com/send?text=${encodeURIComponent(greeting.whatsapp_text)}`;

    const copy = () => {
        navigator.clipboard?.writeText(greeting.url);
        setCopied(true);
        setTimeout(() => setCopied(false), 2400);
    };

    const share = (url) => window.open(url, '_blank', 'noopener');

    return (
        <PublicLayout title={isInvitation ? 'Undangan Siap Disebar' : 'Kartu Siap Dikirim'} back={{ href: route('dashboard'), label: 'Kartu Saya' }}>
            <div className="relative flex flex-col gap-space-lg overflow-hidden px-margin pb-space-xl">
                <div className="pointer-events-none absolute -right-10 -top-12 -z-10 h-48 w-48 rounded-full bg-primary-fixed-dim/30 blur-3xl" />

                <section className="flex flex-col items-center pt-space-md text-center">
                    <div className="relative mb-space-md flex h-24 w-24 items-center justify-center rounded-full bg-surface-container-high shadow-lg">
                        <Icon name={isInvitation ? 'celebration' : 'mark_email_unread'} size={48} filled className="text-primary" />
                        <Icon name="auto_awesome" size={24} className="absolute -right-1 -top-1 animate-pulse text-tertiary-container" />
                        <div className="absolute -bottom-1 -right-1 rounded-full bg-surface-container-lowest p-1 shadow-md">
                            <div className="flex h-7 w-7 items-center justify-center rounded-full bg-[#1da851] text-white">
                                <Icon name="check" size={18} />
                            </div>
                        </div>
                    </div>
                    <div className="mb-space-sm inline-flex items-center gap-space-xs rounded-full bg-surface-container px-3 py-1 text-label-sm text-on-surface-variant">
                        <span className="h-2 w-2 rounded-full bg-[#1da851]" />
                        Pembayaran Lunas • Terbit
                    </div>
                    {isInvitation ? (
                        <>
                            <h1 className="mb-space-xs text-headline-lg-mobile tracking-tight text-on-surface">Undangan {greeting.sender_name} Sudah Terbit! 🎉</h1>
                            <p className="mb-space-sm max-w-sm text-body-sm leading-relaxed text-on-surface-variant">
                                <strong className="text-on-surface">{template.name}</strong> kini aktif. Jadwal, lokasi, daftar tamu, dan amplop digital tetap bisa kamu ubah kapan saja.
                            </p>
                        </>
                    ) : (
                        <>
                            <h1 className="mb-space-xs text-headline-lg-mobile tracking-tight text-on-surface">Yeay, Kartu {greeting.recipient_name} Sudah Siap! 🎉</h1>
                            <p className="mb-space-sm max-w-sm text-body-sm leading-relaxed text-on-surface-variant">
                                Tautan <strong className="text-on-surface">{template.name}</strong> untuk <strong className="text-on-surface">{greeting.recipient_name}</strong> kini aktif
                                dan isinya terkunci permanen.
                            </p>
                        </>
                    )}
                    {greeting.opens_at && new Date(greeting.opens_at) > new Date() && (
                        <div className="mb-space-sm inline-flex items-center gap-1 rounded-full bg-tertiary-fixed px-3 py-1 text-label-sm text-on-tertiary-fixed">
                            <Icon name="schedule_send" size={14} />
                            Kirim link-nya sekarang — isinya terbuka {dateTime(greeting.opens_at)}
                        </div>
                    )}
                    {orderCode && (
                        <div className="rounded-full bg-surface-container-low px-space-sm py-0.5 text-label-sm tracking-wide text-secondary">
                            No. Pesanan: <span className="font-semibold text-on-surface">{orderCode}</span>
                        </div>
                    )}
                </section>

                <section className="flex flex-col gap-space-xs">
                    {isInvitation && (
                        <Link
                            href={`${greeting.manage_url}#tamu`}
                            className="mb-space-xs flex min-h-[52px] w-full items-center justify-center gap-space-sm rounded-full bg-primary px-space-lg text-label-lg text-on-primary shadow-md active:scale-[0.98]"
                        >
                            <Icon name="group" size={20} />
                            Kirim ke Tamu dengan Nama Pribadi ➔
                        </Link>
                    )}
                    <a
                        href={whatsappUrl}
                        target="_blank"
                        rel="noopener noreferrer"
                        className={`flex min-h-[52px] w-full items-center justify-center gap-space-sm rounded-full px-space-lg text-label-lg shadow-md active:scale-[0.98] ${
                            isInvitation ? 'bg-surface-container-lowest text-on-surface' : 'bg-whatsapp text-white'
                        }`}
                    >
                        <Icon name="chat" size={20} className={isInvitation ? 'text-whatsapp' : ''} />
                        {isInvitation ? 'Bagikan Link Umum ke Grup WhatsApp' : `Kirim ke WhatsApp ${greeting.recipient_name} ➔`}
                    </a>
                    <p className="flex items-start gap-space-xs px-space-xs text-label-sm text-secondary">
                        <Icon name="mode_comment" size={16} className="mt-0.5 text-tertiary" />
                        Otomatis menyertakan pesan: “{greeting.whatsapp_text.slice(0, 60)}…”
                    </p>
                </section>

                <div className="flex flex-col gap-space-xs">
                    <h2 className="text-headline-sm text-on-surface">Bagikan dengan 2 cara</h2>
                    <div role="tablist" aria-label="Cara berbagi" className="grid grid-cols-2 gap-1 rounded-full bg-surface-container-low p-1">
                        {[
                            ['link', 'link', 'Lewat Link'],
                            ['qr', 'qr_code_2', 'Lewat QR Code'],
                        ].map(([key, icon, label]) => (
                            <button
                                key={key}
                                type="button"
                                role="tab"
                                aria-selected={shareMode === key}
                                onClick={() => setShareMode(key)}
                                className={`flex h-10 items-center justify-center gap-1.5 rounded-full text-label-md transition-colors ${
                                    shareMode === key ? 'bg-primary text-on-primary shadow-sm' : 'text-on-surface-variant'
                                }`}
                            >
                                <Icon name={icon} size={18} />
                                {label}
                            </button>
                        ))}
                    </div>
                </div>

                {shareMode === 'link' ? (
                <section className="flex flex-col gap-space-sm rounded-3xl bg-surface-container-lowest p-space-md shadow-sm">
                    <div className="flex items-center justify-between">
                        <span className="flex items-center gap-space-xs text-label-md text-on-surface">
                            <Icon name="link" size={18} className="text-primary" /> {isInvitation ? 'Link Umum Undangan' : 'Tautan Privat Kartu'}
                        </span>
                        <span className="text-label-sm font-semibold text-primary">Siap Digunakan</span>
                    </div>
                    <div className="flex items-center justify-between gap-space-xs rounded-2xl bg-surface-container-low p-space-xs">
                        <span className="min-w-0 select-all truncate pl-space-sm text-label-md text-on-surface">{displayUrl}</span>
                        <button
                            type="button"
                            onClick={copy}
                            className="flex shrink-0 items-center gap-1 rounded-full bg-primary px-space-md py-2 text-label-sm font-semibold text-on-primary shadow-sm active:scale-95"
                        >
                            <Icon name={copied ? 'done' : 'content_copy'} size={16} />
                            {copied ? 'Tersalin!' : 'Salin Link'}
                        </button>
                    </div>
                    <span className="mb-space-xs text-label-sm text-secondary">Atau bagikan melalui:</span>
                    <div className="grid grid-cols-3 gap-space-xs">
                        {[
                            ['Telegram', 'send', '#229ED9', `https://t.me/share/url?url=${encodeURIComponent(greeting.url)}`],
                            ['Email', 'mail', '#a83223', `mailto:?subject=${encodeURIComponent(isInvitation ? `Undangan ${greeting.occasion} ${greeting.sender_name}` : 'Ada ucapan untukmu')}&body=${encodeURIComponent(greeting.whatsapp_text)}`],
                            ['Salin Teks', 'chat', '#68615e', null],
                        ].map(([label, icon, color, url]) => (
                            <button
                                key={label}
                                type="button"
                                onClick={() => (url ? share(url) : navigator.clipboard?.writeText(greeting.whatsapp_text))}
                                className="flex flex-col items-center justify-center gap-1 rounded-2xl bg-surface p-2 hover:bg-surface-container"
                            >
                                <span className="flex h-9 w-9 items-center justify-center rounded-full text-white" style={{ backgroundColor: color }}>
                                    <Icon name={icon} size={18} />
                                </span>
                                <span className="text-label-sm text-on-surface-variant">{label}</span>
                            </button>
                        ))}
                    </div>
                </section>
                ) : (
                <section className="flex flex-col items-center gap-space-sm rounded-3xl bg-surface-container-low p-space-md text-center shadow-sm">
                    <div className="flex items-center gap-space-xs text-headline-sm text-on-surface">
                        <Icon name="qr_code_2" size={20} className="text-tertiary-container" />
                        {isInvitation ? 'QR Code Undangan' : 'QR Code Kenangan'}
                    </div>
                    <p className="text-body-sm text-on-surface-variant">Pilih model bingkainya, lalu unduh untuk dibagikan atau dicetak.</p>
                    <QrStyler
                        svg={greeting.qr_svg}
                        theme={template.theme}
                        title={isInvitation ? greeting.sender_name : `Untuk ${greeting.recipient_name}`}
                        caption={isInvitation ? greeting.occasion : 'Scan untuk membuka kartumu'}
                        defaultStyle={isInvitation ? 'invitation' : 'gift'}
                        filename={`qr-${greeting.url.split('/').pop()}`}
                        plainDownloadUrl={greeting.qr_download_url}
                    />
                    <p className="max-w-xs text-body-sm text-on-surface-variant">
                        {isInvitation
                            ? 'Cetak di undangan fisik atau papan penerima tamu. QR check-in per tamu ada di Kelola Undangan.'
                            : 'Pindai dengan kamera ponsel untuk membuka kartu. Cocok dicetak pada kado fisik!'}
                    </p>
                </section>
                )}

                <section className="flex flex-col gap-space-sm rounded-3xl bg-surface-container-lowest p-space-md shadow-sm">
                    <div className="flex items-center justify-between">
                        <span className="text-label-sm font-semibold uppercase tracking-wider text-secondary">{isInvitation ? 'Undangan' : 'Kartu'}</span>
                        <span className="inline-flex items-center gap-1 text-label-sm text-[#1da851]">
                            <span className="h-1.5 w-1.5 rounded-full bg-[#1da851]" /> Aktif
                        </span>
                    </div>
                    <div className="flex items-center gap-space-md rounded-2xl bg-surface p-space-sm">
                        <div className="h-16 w-16 shrink-0 overflow-hidden rounded-xl">
                            <TemplateCover template={template} />
                        </div>
                        <div className="flex min-w-0 flex-col">
                            <h2 className="truncate text-headline-sm text-on-surface">
                                {greeting.occasion} • {isInvitation ? greeting.sender_name : greeting.recipient_name}
                            </h2>
                            <span className="truncate text-body-sm text-on-surface-variant">{template.name}</span>
                        </div>
                    </div>
                    <a href={greeting.url} target="_blank" rel="noopener noreferrer" className="inline-flex items-center justify-center gap-1 py-1 text-label-md text-primary hover:underline">
                        {isInvitation ? 'Buka & Uji Undangan Kamu Sendiri' : 'Buka & Uji Kartu Kamu Sendiri'}
                        <Icon name="open_in_new" size={16} />
                    </a>
                    <p className="text-center text-label-sm text-on-surface-variant">
                        Bukaan oleh akunmu sendiri tidak dihitung sebagai “dibuka {isInvitation ? 'tamu' : 'penerima'}”.
                    </p>
                </section>

                <Link
                    href={route('catalog.index')}
                    className="mx-auto flex min-h-[44px] items-center gap-space-xs rounded-full bg-surface-container-high px-space-lg text-label-lg text-on-surface shadow-sm active:scale-95"
                >
                    <Icon name="add_circle" size={20} className="text-primary" />
                    Buat Kartu Ucapan Lainnya
                </Link>
            </div>
        </PublicLayout>
    );
}
