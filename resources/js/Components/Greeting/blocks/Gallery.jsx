import { MediaImage } from './shared';

export default function Gallery({ slot, values }) {
    const photos = values.photos ?? [];

    return (
        <div className="flex w-full flex-col gap-2">
            <div className="flex items-center justify-between px-1">
                <span className="text-label-sm font-bold text-on-surface">{slot?.title ?? `Kenangan Foto (${photos.length})`}</span>
                <span className="text-label-sm text-on-surface-variant">Geser ➔</span>
            </div>
            <div className="no-scrollbar flex w-full snap-x snap-mandatory gap-3 overflow-x-auto pb-3">
                {(photos.length ? photos : [{}, {}, {}]).map((photo, index) => (
                    <figure
                        key={index}
                        className="w-48 shrink-0 snap-center rounded-xl bg-surface-container-lowest p-2 pb-3 shadow-md"
                        style={{ transform: `rotate(${index % 2 ? 2 : -2}deg)` }}
                    >
                        <div className="mb-2 h-40 w-full overflow-hidden rounded-lg bg-surface-container">
                            <MediaImage media={photo.media} />
                        </div>
                        {photo.caption && <figcaption className="truncate text-center text-label-sm text-on-surface">{photo.caption}</figcaption>}
                    </figure>
                ))}
            </div>
        </div>
    );
}
