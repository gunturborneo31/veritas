import Icon from '@/Components/Icon';
import { dateTime } from '@/lib/format';

export const ATTENDANCE = {
    hadir: { label: 'Hadir', tone: 'bg-success/10 text-success', icon: 'check_circle' },
    tidak_hadir: { label: 'Tidak Hadir', tone: 'bg-error-container text-on-error-container', icon: 'cancel' },
    ragu: { label: 'Masih Ragu', tone: 'bg-tertiary-fixed text-on-tertiary-fixed', icon: 'help' },
};

function Stat({ icon, tone, value, label }) {
    return (
        <div className="flex flex-col items-center justify-center rounded-2xl bg-surface-container-low p-space-sm text-center shadow-sm">
            <div className={`mb-1 flex h-7 w-7 items-center justify-center rounded-full ${tone}`}>
                <Icon name={icon} size={16} />
            </div>
            <span className="text-headline-sm font-bold text-on-surface">{value}</span>
            <span className="text-label-sm leading-tight text-on-surface-variant">{label}</span>
        </div>
    );
}

/**
 * Ringkasan: progres pengiriman, rekap RSVP, check-in, dan RSVP dari link umum.
 */
export default function SummaryPanel({ invitation, onNavigate }) {
    const { summary } = invitation;
    const responseRate = summary.guests > 0 ? Math.round(((summary.guests - summary.awaiting) / summary.guests) * 100) : 0;

    return (
        <div className="flex flex-col gap-space-md">
            <div className="grid grid-cols-3 gap-space-xs">
                <Stat icon="group" tone="bg-primary-fixed text-primary" value={summary.guests} label="Tamu" />
                <Stat icon="send" tone="bg-surface-container-highest text-primary" value={summary.invited} label="Terkirim" />
                <Stat icon="visibility" tone="bg-tertiary-fixed text-tertiary" value={summary.opened} label="Dibuka" />
            </div>

            <section className="flex flex-col gap-space-sm rounded-3xl bg-surface-container-lowest p-space-md shadow-card">
                <div className="flex items-center justify-between">
                    <h3 className="text-label-lg text-on-surface">Konfirmasi Kehadiran</h3>
                    <span className="text-label-sm text-on-surface-variant">{responseRate}% tamu menjawab</span>
                </div>
                <div className="h-2 overflow-hidden rounded-full bg-surface-container-high">
                    <div className="h-full rounded-full bg-primary transition-all" style={{ width: `${responseRate}%` }} />
                </div>
                <div className="grid grid-cols-2 gap-space-xs">
                    <div className="rounded-2xl bg-success/10 p-space-sm">
                        <p className="text-headline-md font-bold text-success">{summary.attendees}</p>
                        <p className="text-label-sm text-on-surface-variant">orang akan hadir ({summary.attending} undangan)</p>
                    </div>
                    <div className="rounded-2xl bg-surface-container-low p-space-sm">
                        <p className="text-headline-md font-bold text-on-surface">{summary.awaiting}</p>
                        <p className="text-label-sm text-on-surface-variant">tamu belum menjawab</p>
                    </div>
                    <div className="rounded-2xl bg-surface-container-low p-space-sm">
                        <p className="text-headline-sm font-bold text-on-surface">{summary.not_attending}</p>
                        <p className="text-label-sm text-on-surface-variant">tidak hadir</p>
                    </div>
                    <div className="rounded-2xl bg-surface-container-low p-space-sm">
                        <p className="text-headline-sm font-bold text-on-surface">{summary.unsure}</p>
                        <p className="text-label-sm text-on-surface-variant">masih ragu</p>
                    </div>
                </div>
                {summary.checked_in > 0 && (
                    <div className="flex items-center gap-2 rounded-2xl bg-primary-fixed/50 px-space-sm py-2 text-on-primary-fixed">
                        <Icon name="how_to_reg" size={18} />
                        <span className="text-label-md">{summary.checked_in} tamu sudah check-in di lokasi</span>
                    </div>
                )}
            </section>

            {invitation.walk_in_rsvps.length > 0 && (
                <section className="flex flex-col gap-space-sm rounded-3xl bg-surface-container-lowest p-space-md shadow-card">
                    <div>
                        <h3 className="text-label-lg text-on-surface">RSVP dari link umum</h3>
                        <p className="text-body-sm text-on-surface-variant">Tamu yang membuka link tanpa nama pribadi dan menulis namanya sendiri.</p>
                    </div>
                    <ul className="flex flex-col divide-y divide-outline-variant/30">
                        {invitation.walk_in_rsvps.map((rsvp, index) => (
                            <li key={index} className="flex items-center justify-between gap-2 py-2">
                                <span className="min-w-0">
                                    <span className="block truncate text-label-md text-on-surface">{rsvp.name}</span>
                                    <span className="block text-label-sm font-normal text-on-surface-variant">{dateTime(rsvp.created_at)}</span>
                                </span>
                                <span className={`shrink-0 rounded-full px-2.5 py-0.5 text-label-sm ${ATTENDANCE[rsvp.attendance].tone}`}>
                                    {ATTENDANCE[rsvp.attendance].label}
                                    {rsvp.attendance === 'hadir' && ` • ${rsvp.party_size} org`}
                                </span>
                            </li>
                        ))}
                    </ul>
                </section>
            )}

            {summary.guests === 0 && (
                <button
                    type="button"
                    onClick={() => onNavigate('tamu')}
                    className="flex items-center gap-3 rounded-2xl bg-primary-fixed px-4 py-3 text-left text-on-primary-fixed shadow-sm active:scale-[0.99]"
                >
                    <Icon name="person_add" size={22} className="shrink-0 text-primary" />
                    <span className="min-w-0 flex-1">
                        <span className="block text-label-md">Tambahkan daftar tamu</span>
                        <span className="block text-body-sm opacity-80">Setiap tamu mendapat link dengan namanya sendiri.</span>
                    </span>
                    <Icon name="arrow_forward" size={20} className="shrink-0 text-primary" />
                </button>
            )}
        </div>
    );
}
