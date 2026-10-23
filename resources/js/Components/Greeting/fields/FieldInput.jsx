import Icon from '@/Components/Icon';
import Toggle from '@/Components/Toggle';
import Inspiration from './Inspiration';
import { QuizField, ListField } from './ListFields';
import LocationsField from './LocationsField';
import { AudioField, ImageField, ImagesField } from './MediaFields';
import MusicField from './MusicField';
import PinField from './PinField';
import SongField from './SongField';

const inputClass =
    'w-full rounded-2xl border-0 bg-surface-container-low px-4 py-3 text-body-md text-on-surface placeholder:text-on-surface-variant/60 focus:bg-surface-container-lowest focus:ring-2 focus:ring-primary/40';

/**
 * Field form Studio yang dibangkitkan dari skema komponen. `isMissing` menandai field wajib
 * yang masih kosong (bingkai & label merah); `fieldId` menjadi tujuan gulir dari daftar periksa.
 */
export default function FieldInput({ field, value, onChange, error, context, isMissing = false, fieldId = undefined }) {
    const control = (() => {
        switch (field.type) {
            case 'textarea':
                return (
                    <div className="rounded-2xl bg-surface-container-low p-3.5 focus-within:bg-surface-container-lowest focus-within:ring-2 focus-within:ring-primary/40">
                        <textarea
                            rows={4}
                            maxLength={field.max ?? 1500}
                            value={value ?? ''}
                            placeholder={field.placeholder ?? 'Tuliskan dari lubuk hatimu…'}
                            onChange={(event) => onChange(event.target.value)}
                            className="w-full resize-none border-0 bg-transparent p-0 text-body-md leading-relaxed text-on-surface focus:ring-0"
                        />
                        <div className="flex items-center justify-between pt-2 text-label-sm text-on-surface-variant">
                            <Inspiration base={context.base} onPick={(text) => onChange(value ? `${value}\n\n${text}` : text)} />
                            <span>
                                {(value ?? '').length} / {field.max ?? 1500} karakter
                            </span>
                        </div>
                    </div>
                );
            case 'date':
                return <input type="date" value={value ?? ''} onChange={(event) => onChange(event.target.value)} className={inputClass} />;
            case 'datetime':
                return <input type="datetime-local" value={value ?? ''} onChange={(event) => onChange(event.target.value)} className={inputClass} />;
            case 'toggle':
                return <Toggle checked={Boolean(value)} onChange={onChange} label={field.label} />;
            case 'image':
                return <ImageField value={value} onChange={onChange} {...context} />;
            case 'images':
                return <ImagesField field={field} value={value} onChange={onChange} {...context} />;
            case 'audio':
                return <AudioField value={value} onChange={onChange} {...context} />;
            case 'music':
                return <MusicField value={value} onChange={onChange} musicTracks={context.musicTracks} />;
            case 'list':
                return (
                    <div className="flex flex-col gap-2">
                        <ListField field={field} value={value} onChange={onChange} />
                        {(value ?? []).length < (field.max ?? 10) && (
                            <div className="self-start">
                                <Inspiration base={context.base} onPick={(text) => onChange([...(value ?? []), text])} />
                            </div>
                        )}
                    </div>
                );
            case 'song':
                return <SongField value={value} onChange={onChange} />;
            case 'locations':
                return <LocationsField field={field} value={value} onChange={onChange} {...context} />;
            case 'quiz':
                return <QuizField field={field} value={value} onChange={onChange} />;
            case 'pin':
                return <PinField field={field} value={value} onChange={onChange} isSet={context.isPinSet} />;
            default:
                return (
                    <input
                        type="text"
                        maxLength={field.max ?? 120}
                        value={value ?? ''}
                        placeholder={field.placeholder ?? ''}
                        onChange={(event) => onChange(event.target.value)}
                        className={inputClass}
                    />
                );
        }
    })();

    if (field.type === 'toggle') {
        return (
            <div className="flex items-center justify-between gap-3">
                <span className="text-label-md text-on-surface">{field.label}</span>
                {control}
            </div>
        );
    }

    return (
        <div
            id={fieldId}
            className={`flex scroll-mt-28 flex-col gap-1 rounded-2xl transition-shadow ${isMissing ? '-mx-2 bg-error-container/20 p-2 ring-2 ring-error/40' : ''}`}
        >
            <label className="flex flex-wrap items-center gap-x-1.5 text-label-md font-semibold text-on-surface">
                <span>
                    {field.label}
                    {field.required && <span className="text-primary"> *</span>}
                </span>
                {isMissing && (
                    <span className="inline-flex items-center gap-0.5 rounded-full bg-error px-2 py-0.5 text-[11px] font-bold text-on-error">
                        <Icon name="priority_high" size={12} />
                        Wajib diisi
                    </span>
                )}
            </label>
            {control}
            {field.help && <span className="text-label-sm text-on-surface-variant">{field.help}</span>}
            {error && <span className="text-label-sm text-error">{error}</span>}
        </div>
    );
}
