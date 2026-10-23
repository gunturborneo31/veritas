import { MediaImage, SceneLabel } from './shared';

/**
 * Yang dirayakan (anak yang diaqiqah, yang berulang tahun, keluarga yang bersyukur, …):
 * foto, nama, keterangan, dan catatan singkat. Padanan blok Mempelai untuk acara non-pernikahan.
 */
export default function Honoree({ slot, values }) {
    return (
        <div className="flex w-full flex-col items-center gap-3 text-center">
            <SceneLabel icon="favorite">{values.label || slot?.title || 'Yang Kami Rayakan'}</SceneLabel>
            {values.photo?.url && (
                <div className="h-40 w-40 overflow-hidden rounded-full border-4 border-surface-container-lowest shadow-lg shadow-primary/10">
                    <MediaImage media={values.photo} />
                </div>
            )}
            <p className="font-display text-headline-lg-mobile leading-tight text-primary">{values.name || '…'}</p>
            {values.description && <p className="max-w-xs text-body-sm text-on-surface-variant">{values.description}</p>}
            {values.note && <p className="max-w-xs text-body-sm italic leading-relaxed text-on-surface">{values.note}</p>}
        </div>
    );
}
