import Icon from '@/Components/Icon';
import { CountdownDigits } from './Countdown';
import { eventClock, eventDate, ManagedHint } from './invitationShared';
import { SceneCard, SceneLabel } from './shared';

function ActionLink({ href, icon, children }) {
    return (
        <a
            href={href}
            target="_blank"
            rel="noopener noreferrer"
            className="inline-flex items-center gap-1 rounded-full bg-primary-fixed/60 px-3 py-1.5 text-label-sm text-on-primary-fixed active:scale-95"
        >
            <Icon name={icon} size={14} />
            {children}
        </a>
    );
}

/**
 * Rangkaian acara: hitung mundur ke acara pertama, lalu jadwal, tempat, petunjuk arah, dan kalender.
 */
export default function EventDetails({ slot, values, mode, invitation }) {
    const events = invitation?.events ?? [];
    const firstEvent = events[0];
    const isUpcoming = firstEvent && new Date(firstEvent.starts_at) > new Date();

    return (
        <div className="flex w-full flex-col gap-3">
            <div className="text-center">
                <SceneLabel icon="event">{values.title || slot?.title || 'Rangkaian Acara'}</SceneLabel>
            </div>

            <ManagedHint mode={mode}>Jadwal & lokasi diisi di halaman Kelola Undangan, dan tetap bisa diubah setelah undangan disebar.</ManagedHint>

            {events.length === 0 && (
                <SceneCard className="items-center text-center">
                    <Icon name="event_busy" size={32} className="text-on-surface-variant" />
                    <p className="text-body-sm text-on-surface-variant">Detail acara belum diisi.</p>
                </SceneCard>
            )}

            {isUpcoming && <CountdownDigits target={firstEvent.starts_at} />}

            {events.map((event, index) => (
                <SceneCard key={index} className="text-left">
                    <p className="font-display text-headline-sm text-primary">{event.name}</p>
                    <div className="flex items-start gap-2 text-body-sm text-on-surface">
                        <Icon name="calendar_month" size={18} className="mt-0.5 shrink-0 text-primary" />
                        <span>
                            {eventDate(event.starts_at, event.timezone)}
                            <br />
                            <span className="text-on-surface-variant">
                                {eventClock(event.starts_at, event.timezone)}
                                {event.ends_at ? ` – ${eventClock(event.ends_at, event.timezone)}` : ' – selesai'} {event.timezone_label}
                            </span>
                        </span>
                    </div>
                    <div className="flex items-start gap-2 text-body-sm text-on-surface">
                        <Icon name="location_on" size={18} className="mt-0.5 shrink-0 text-primary" />
                        <span>
                            <span className="font-semibold">{event.venue_name}</span>
                            {event.address && <span className="block text-on-surface-variant">{event.address}</span>}
                        </span>
                    </div>
                    <div className="flex flex-wrap gap-1.5 pt-1">
                        {event.directions_url && (
                            <ActionLink href={event.directions_url} icon="directions">
                                Petunjuk Arah
                            </ActionLink>
                        )}
                        <ActionLink href={event.calendar_url} icon="event_upcoming">
                            Simpan ke Kalender
                        </ActionLink>
                        {event.livestream_url && (
                            <ActionLink href={event.livestream_url} icon="live_tv">
                                Live Streaming
                            </ActionLink>
                        )}
                    </div>
                </SceneCard>
            ))}
        </div>
    );
}
