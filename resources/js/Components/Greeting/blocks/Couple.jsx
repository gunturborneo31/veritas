import { MediaImage } from './shared';

function Person({ name, parents, photo }) {
    return (
        <div className="flex flex-col items-center gap-2 text-center">
            {photo?.url && (
                <div className="h-28 w-28 overflow-hidden rounded-full border-4 border-surface-container-lowest shadow-lg shadow-primary/10">
                    <MediaImage media={photo} />
                </div>
            )}
            <p className="font-display text-headline-md leading-tight text-primary">{name || '…'}</p>
            {parents && <p className="max-w-[16rem] text-body-sm text-on-surface-variant">{parents}</p>}
        </div>
    );
}

/**
 * Mempelai: kalimat pembuka, lalu kedua mempelai beserta keterangan orang tua.
 */
export default function Couple({ values }) {
    return (
        <div className="flex w-full flex-col items-center gap-4 text-center">
            {values.opening && <p className="max-w-xs text-body-sm italic leading-relaxed text-on-surface-variant">{values.opening}</p>}
            <Person name={values.groom_name} parents={values.groom_parents} photo={values.groom_photo} />
            <span className="font-display text-headline-lg-mobile text-primary/70">&amp;</span>
            <Person name={values.bride_name} parents={values.bride_parents} photo={values.bride_photo} />
        </div>
    );
}
