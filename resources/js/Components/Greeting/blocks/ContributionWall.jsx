import Icon from '@/Components/Icon';
import { MediaImage } from './shared';

const TILTS = [-2, 1.5, -1, 2, -1.5, 1];
const TINTS = ['bg-[#fff8d6]', 'bg-[#ffe8ef]', 'bg-[#e8f4ff]', 'bg-[#eafbe9]', 'bg-[#f3e9ff]', 'bg-[#fff0e0]'];

/**
 * Dinding ucapan dari teman-teman pengirim (mode patungan), seperti tempelan sticky note.
 */
export default function ContributionWall({ values, base }) {
    const items = values.items ?? [];

    return (
        <div className="flex w-full flex-col gap-3">
            <div className="flex items-center gap-2 text-primary">
                <Icon name="groups" size={18} />
                <span className="text-label-sm font-bold uppercase tracking-[0.2em]">
                    {items.length} orang ikut menitip pesan untuk {base.recipient_name || 'kamu'}
                </span>
            </div>
            <div className="columns-2 gap-3 [column-fill:_balance]">
                {items.map((item, index) => (
                    <figure
                        key={index}
                        className={`mb-3 break-inside-avoid rounded-2xl p-3 shadow-[0_10px_20px_-12px_rgba(40,21,38,0.35)] ${TINTS[index % TINTS.length]}`}
                        style={{ transform: `rotate(${TILTS[index % TILTS.length]}deg)` }}
                    >
                        {item.photo?.url && (
                            <div className="mb-2 aspect-square overflow-hidden rounded-xl">
                                <MediaImage media={item.photo} />
                            </div>
                        )}
                        <p className="whitespace-pre-line text-body-sm leading-snug text-on-surface">{item.message}</p>
                        <figcaption className="mt-2 text-right font-display text-label-md italic text-primary">— {item.name}</figcaption>
                    </figure>
                ))}
            </div>
        </div>
    );
}
