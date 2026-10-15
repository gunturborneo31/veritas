import Icon from '@/Components/Icon';
import { dateTime } from '@/lib/format';
import { router } from '@inertiajs/react';

/**
 * Aktivitas terbaru: kartu dibuka, reaksi, jawaban, balasan, dan pengingat momen.
 */
export default function ActivityFeed({ activities }) {
    const unreadCount = activities.filter((activity) => activity.is_unread).length;

    return (
        <section id="aktivitas" className="px-margin pb-space-lg">
            <div className="mb-space-sm flex items-center justify-between">
                <h2 className="flex items-center gap-2 font-display text-headline-sm text-on-surface">
                    Aktivitas terbaru
                    {unreadCount > 0 && <span className="rounded-full bg-primary px-2 text-label-sm text-on-primary">{unreadCount} baru</span>}
                </h2>
                {unreadCount > 0 && (
                    <button type="button" onClick={() => router.post(route('notifications.read'), {}, { preserveScroll: true })} className="text-label-md text-primary">
                        Tandai dibaca
                    </button>
                )}
            </div>

            {activities.length === 0 ? (
                <div className="flex items-center gap-3 rounded-3xl border border-dashed border-outline-variant p-4 text-body-sm text-on-surface-variant">
                    <Icon name="notifications_active" size={22} className="text-primary" />
                    Kamu akan dikabari di sini saat penerima membuka kartu, memberi reaksi, atau membalas.
                </div>
            ) : (
                <ol className="flex flex-col gap-2">
                    {activities.map((activity) => (
                        <li
                            key={activity.id}
                            className={`flex items-start gap-3 rounded-2xl p-3 ${activity.is_unread ? 'bg-primary-fixed/50' : 'bg-surface-container-lowest'} shadow-sm`}
                        >
                            <span className="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-surface-container-lowest text-primary shadow-sm">
                                <Icon name={activity.icon} size={18} />
                            </span>
                            <div className="min-w-0 flex-1">
                                {activity.url ? (
                                    <a href={activity.url} className="text-label-md text-primary underline-offset-2 hover:underline">
                                        {activity.title}
                                    </a>
                                ) : (
                                    <p className="text-label-md text-on-surface">{activity.title}</p>
                                )}
                                <p className="truncate text-body-sm text-on-surface-variant">{activity.body}</p>
                                <p className="text-[11px] text-on-surface-variant/80">{dateTime(activity.created_at)}</p>
                            </div>
                            {activity.is_unread && <span className="mt-1.5 h-2 w-2 shrink-0 rounded-full bg-primary" />}
                        </li>
                    ))}
                </ol>
            )}
        </section>
    );
}
