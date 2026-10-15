import { requestJson } from '@/lib/http';

export async function uploadMedia(greetingId, file, kind) {
    const body = new FormData();
    body.append('file', file);
    body.append('kind', kind);

    const { media } = await requestJson(route('studio.media.store', greetingId), { method: 'POST', body });

    return media;
}

/**
 * Ubah nilai yang sudah "dipresentasikan" server (objek media/trek) menjadi nilai mentah
 * yang dikirim balik saat autosave (id media/trek). Sekaligus kumpulkan cache media.
 */
export function toRawValues(components, presentedValues) {
    const values = {};
    const mediaCache = {};

    for (const slot of components) {
        const slotValues = presentedValues[slot.key] ?? {};
        values[slot.key] = {};

        for (const field of slot.schema) {
            const value = slotValues[field.name];

            switch (field.type) {
                case 'image':
                case 'audio':
                    if (value?.id) {
                        mediaCache[value.id] = value;
                    }
                    values[slot.key][field.name] = value?.id ?? null;
                    break;
                case 'images':
                    values[slot.key][field.name] = (value ?? []).map((item) => {
                        mediaCache[item.media.id] = item.media;

                        return { media: item.media.id, caption: item.caption ?? '' };
                    });
                    break;
                case 'music':
                    values[slot.key][field.name] = value?.id ?? null;
                    break;
                case 'pin':
                    values[slot.key][field.name] = '';
                    break;
                case 'toggle':
                    values[slot.key][field.name] = Boolean(value);
                    break;
                case 'list':
                case 'quiz':
                    values[slot.key][field.name] = value ?? [];
                    break;
                default:
                    values[slot.key][field.name] = value ?? '';
            }
        }
    }

    return { values, mediaCache };
}

/**
 * Kebalikan dari toRawValues: dipakai untuk pratinjau langsung di Studio.
 */
export function toPresentedValues(components, rawValues, mediaCache, musicTracks, pinState) {
    const values = {};

    for (const slot of components) {
        values[slot.key] = {};

        for (const field of slot.schema) {
            const value = rawValues[slot.key]?.[field.name];

            switch (field.type) {
                case 'image':
                case 'audio':
                    values[slot.key][field.name] = value ? mediaCache[value] ?? null : null;
                    break;
                case 'images':
                    values[slot.key][field.name] = (value ?? []).map((item) => ({ ...item, media: mediaCache[item.media] }));
                    break;
                case 'music':
                    values[slot.key][field.name] = musicTracks.find((track) => track.id === value) ?? null;
                    break;
                case 'pin':
                    values[slot.key][field.name] = { is_set: pinState[slot.key] || Boolean(value) };
                    break;
                default:
                    values[slot.key][field.name] = value;
            }
        }
    }

    return values;
}
