import Icon from '@/Components/Icon';
import { MediaImage } from './shared';

export default function Cover({ values, base }) {
    return (
        <div className="flex w-full flex-col items-center gap-4 text-center">
            <div className="inline-flex items-center gap-1.5 rounded-full bg-primary-fixed/60 px-3 py-0.5">
                <Icon name="favorite" filled size={13} className="text-primary" />
                <span className="text-label-sm text-on-primary-fixed">{base.occasion || 'Ucapan spesial'}</span>
            </div>
            {values.photo?.url && (
                <div className="h-56 w-56 overflow-hidden rounded-3xl bg-surface-container-lowest p-2 shadow-lg shadow-primary/10">
                    <MediaImage media={values.photo} className="rounded-2xl" />
                </div>
            )}
            <h2 className="text-headline-lg-mobile text-on-surface">
                {values.title || `Untuk ${base.recipient_name || 'kamu'}`}
            </h2>
            {values.subtitle && <p className="max-w-xs text-body-sm text-on-surface-variant">{values.subtitle}</p>}
            {base.sender_name && <p className="text-label-md text-primary">Dari {base.sender_name}</p>}
        </div>
    );
}
