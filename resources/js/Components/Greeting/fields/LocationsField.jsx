import Icon from '@/Components/Icon';
import { requestJson } from '@/lib/http';
import { addTiles, DEFAULT_CENTER, DEFAULT_ZOOM, hasCoordinates, loadLeaflet, numberedPin, parseCoordinates, toLatLng } from '@/lib/maps';
import { usePage } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';
import { ImageField } from './MediaFields';

const inputClass =
    'w-full rounded-2xl border-0 bg-surface-container-low px-4 py-3 text-body-md text-on-surface placeholder:text-on-surface-variant/60 focus:bg-surface-container-lowest focus:ring-2 focus:ring-primary/40';

const emptyPlace = () => ({ name: '', lat: null, lng: null, caption: '', media: null });

const round = (value) => Math.round(Number(value) * 1e6) / 1e6;

/**
 * Peta editor: semua titik tampil bernomor. Ketukan di peta / geser penanda memindahkan titik yang sedang ditandai.
 * `focus` ({ lat, lng, nonce }) menerbangkan peta ke titik yang baru dipilih lewat pencarian atau link.
 */
function EditorMap({ places, activeIndex, focus, onPick }) {
    const { maps } = usePage().props;
    const containerRef = useRef(null);
    const mapRef = useRef(null);
    const layerRef = useRef(null);
    const leafletRef = useRef(null);
    const onPickRef = useRef(onPick);
    const [isReady, setIsReady] = useState(false);
    const locatedCount = places.filter(hasCoordinates).length;

    onPickRef.current = onPick;

    useEffect(() => {
        let isMounted = true;

        loadLeaflet().then((L) => {
            if (!isMounted || !containerRef.current) {
                return;
            }

            leafletRef.current = L;
            const map = L.map(containerRef.current, { zoomControl: false, attributionControl: true }).setView(DEFAULT_CENTER, DEFAULT_ZOOM);
            L.control.zoom({ position: 'bottomleft' }).addTo(map);
            addTiles(L, map, maps);
            map.on('click', (event) => onPickRef.current(event.latlng));
            layerRef.current = L.layerGroup().addTo(map);
            mapRef.current = map;
            setIsReady(true);
            setTimeout(() => map.invalidateSize(), 50);
        });

        return () => {
            isMounted = false;
            mapRef.current?.remove();
            mapRef.current = null;
        };
    }, []); // eslint-disable-line react-hooks/exhaustive-deps

    useEffect(() => {
        const L = leafletRef.current;
        const layer = layerRef.current;

        if (!L || !layer) {
            return;
        }

        layer.clearLayers();
        const located = places.map((place, index) => ({ place, index })).filter(({ place }) => hasCoordinates(place));

        if (located.length > 1) {
            L.polyline(located.map(({ place }) => toLatLng(place)), { color: 'rgb(var(--color-primary))', weight: 3, dashArray: '6 8', opacity: 0.8 }).addTo(layer);
        }

        located.forEach(({ place, index }) => {
            const marker = L.marker(toLatLng(place), { icon: numberedPin(L, index + 1, { isActive: index === activeIndex }), draggable: true }).addTo(layer);
            marker.on('dragend', () => onPickRef.current(marker.getLatLng(), index));
        });
    });

    // Bingkai ulang saat jumlah titik berlokasi berubah (bukan setiap penanda digeser).
    useEffect(() => {
        const L = leafletRef.current;
        const map = mapRef.current;
        const located = places.filter(hasCoordinates);

        if (!L || !map || located.length === 0) {
            return;
        }

        if (located.length === 1) {
            map.setView(toLatLng(located[0]), 15);
        } else {
            map.fitBounds(L.latLngBounds(located.map(toLatLng)), { padding: [36, 36], maxZoom: 15 });
        }
    }, [locatedCount, isReady]); // eslint-disable-line react-hooks/exhaustive-deps

    useEffect(() => {
        if (focus && mapRef.current) {
            mapRef.current.flyTo([focus.lat, focus.lng], 16, { duration: 0.8 });
        }
    }, [focus?.nonce, isReady]); // eslint-disable-line react-hooks/exhaustive-deps

    return (
        <div className="relative isolate z-0">
            <div ref={containerRef} className="h-64 w-full cursor-crosshair overflow-hidden rounded-2xl bg-surface-container" />
            <div className="pointer-events-none absolute inset-x-2 top-2 z-[500] flex justify-center">
                <span className="flex items-center gap-1.5 rounded-full bg-primary px-3 py-1.5 text-label-sm text-on-primary shadow-md">
                    <Icon name="ads_click" size={16} />
                    {places.length === 0 ? 'Ketuk peta untuk menaruh Titik 1' : `Ketuk peta untuk menandai Titik ${activeIndex + 1}`}
                </span>
            </div>
        </div>
    );
}

/**
 * Satu kotak untuk tiga cara: nama tempat, link Google Maps, atau koordinat (mis. "-6.9147, 107.6022").
 */
function PlaceSearch({ onSelect }) {
    const [query, setQuery] = useState('');
    const [results, setResults] = useState(null);
    const [error, setError] = useState(null);
    const [isSearching, setIsSearching] = useState(false);

    const choose = (result) => {
        onSelect(result);
        setResults(null);
        setQuery('');
    };

    const search = async () => {
        const text = query.trim();
        const coordinates = parseCoordinates(text);

        setError(null);
        setResults(null);

        if (coordinates) {
            choose({ name: '', ...coordinates });

            return;
        }

        if (text.length < 3) {
            setError('Ketik minimal 3 huruf.');

            return;
        }

        setIsSearching(true);

        try {
            if (/^https?:\/\//i.test(text)) {
                const data = await requestJson(route('locations.link', { url: text }));
                choose(data.results[0]);
            } else {
                const data = await requestJson(route('locations.search', { q: text }));
                setResults(data.results);
            }
        } catch (exception) {
            setError(exception.message);
        } finally {
            setIsSearching(false);
        }
    };

    return (
        <div className="flex flex-col gap-1.5">
            <div className="flex gap-2">
                <input
                    type="search"
                    value={query}
                    placeholder="Nama tempat, link Google Maps, atau koordinat"
                    onChange={(event) => setQuery(event.target.value)}
                    onKeyDown={(event) => {
                        if (event.key === 'Enter') {
                            event.preventDefault();
                            search();
                        }
                    }}
                    className={`${inputClass} min-w-0 flex-1`}
                />
                <button
                    type="button"
                    onClick={search}
                    disabled={isSearching}
                    aria-label="Cari tempat"
                    className="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-primary text-on-primary disabled:opacity-60"
                >
                    <Icon name={isSearching ? 'progress_activity' : 'search'} size={20} className={isSearching ? 'animate-spin' : ''} />
                </button>
            </div>
            {error && <span className="text-label-sm text-error">{error}</span>}
            {results !== null && (
                <div className="flex flex-col overflow-hidden rounded-2xl bg-surface-container-lowest shadow-sm">
                    {results.length === 0 && (
                        <p className="px-4 py-3 text-body-sm text-on-surface-variant">
                            Tidak ketemu. Coba nama yang lebih umum (mis. nama jalan atau kota), tempel link Google Maps, atau ketuk langsung di peta.
                        </p>
                    )}
                    {results.map((result) => (
                        <button
                            key={`${result.lat},${result.lng}`}
                            type="button"
                            onClick={() => choose(result)}
                            className="flex items-start gap-2 border-t border-surface-container px-4 py-2.5 text-left first:border-t-0 hover:bg-surface-container-low"
                        >
                            <Icon name="location_on" size={18} className="mt-0.5 shrink-0 text-primary" />
                            <span className="min-w-0">
                                <span className="block truncate text-label-md text-on-surface">{result.name}</span>
                                <span className="line-clamp-2 text-label-sm font-normal text-on-surface-variant">{result.label}</span>
                            </span>
                        </button>
                    ))}
                </div>
            )}
        </div>
    );
}

/**
 * Field `locations`: titik kenangan. Tiap titik = lokasi + nama, lalu teks, foto, atau keduanya.
 */
export default function LocationsField({ field, value, onChange, greetingId, mediaCache, addMedia }) {
    const places = value ?? [];
    const max = field.max ?? 3;
    const [activeIndex, setActiveIndex] = useState(0);
    const [focus, setFocus] = useState(null);
    const [addresses, setAddresses] = useState({});
    const placesRef = useRef(places);
    const mapWrapperRef = useRef(null);

    placesRef.current = places;

    const update = (index, changes) => onChange(placesRef.current.map((place, current) => (current === index ? { ...place, ...changes } : place)));

    /**
     * Tandai lokasi sebuah titik. Bila namanya masih kosong, cari nama tempat dari koordinatnya.
     */
    const locate = async (index, { lat, lng, name = '', label = '' }, { fly = false } = {}) => {
        const coordinates = { lat: round(lat), lng: round(lng) };
        const current = placesRef.current;

        if (index >= current.length) {
            onChange([...current, { ...emptyPlace(), ...coordinates, name }]);
        } else {
            update(index, { ...coordinates, name: current[index].name || name });
        }

        setAddresses((state) => ({ ...state, [index]: label }));

        if (fly) {
            setFocus({ ...coordinates, nonce: Date.now() });
        }

        if (name || placesRef.current[index]?.name) {
            return;
        }

        try {
            const { results } = await requestJson(route('locations.reverse', coordinates));
            const place = placesRef.current[index];

            if (results[0] && place && place.lat === coordinates.lat && place.lng === coordinates.lng && !place.name) {
                update(index, { name: results[0].name });
                setAddresses((state) => ({ ...state, [index]: results[0].label }));
            }
        } catch {
            // Nama tempat hanya pelengkap; pengirim tetap bisa mengetiknya sendiri.
        }
    };

    const markOnMap = (index) => {
        setActiveIndex(index);
        mapWrapperRef.current?.scrollIntoView({ behavior: 'smooth', block: 'center' });
    };

    const add = () => {
        onChange([...places, emptyPlace()]);
        markOnMap(places.length);
    };

    const remove = (index) => {
        onChange(places.filter((_, current) => current !== index));
        setAddresses({});
        setActiveIndex(0);
    };

    return (
        <div className="flex flex-col gap-3">
            <div ref={mapWrapperRef}>
                <EditorMap places={places} activeIndex={activeIndex} focus={focus} onPick={(latlng, index = activeIndex) => locate(index, latlng)} />
            </div>

            {places.map((place, index) => {
                const isActive = index === activeIndex;
                const isLocated = hasCoordinates(place);

                return (
                    <div
                        key={index}
                        onFocusCapture={() => setActiveIndex(index)}
                        className={`flex flex-col gap-2.5 rounded-2xl bg-surface-container-low/60 p-3.5 transition-shadow ${isActive ? 'ring-2 ring-primary/50' : ''}`}
                    >
                        <div className="flex items-center justify-between gap-2">
                            <span className="flex items-center gap-2 text-label-lg text-on-surface">
                                <span className="flex h-7 w-7 items-center justify-center rounded-full bg-primary text-label-md text-on-primary">{index + 1}</span>
                                Titik {index + 1}
                            </span>
                            <button type="button" onClick={() => remove(index)} aria-label={`Hapus titik ${index + 1}`} className="flex h-9 w-9 items-center justify-center rounded-full text-error hover:bg-error-container">
                                <Icon name="delete" size={18} />
                            </button>
                        </div>

                        <div className="flex flex-wrap items-center gap-2">
                            <button
                                type="button"
                                onClick={() => markOnMap(index)}
                                className={`flex h-10 items-center gap-1.5 rounded-full px-3.5 text-label-md ${isActive ? 'bg-primary text-on-primary' : 'bg-surface-container-lowest text-primary shadow-sm'}`}
                            >
                                <Icon name="ads_click" size={18} />
                                {isActive ? 'Sedang ditandai di peta' : 'Tandai di peta'}
                            </button>
                            <span className={`flex min-w-0 items-center gap-1 text-label-sm ${isLocated ? 'text-success' : 'text-on-surface-variant'}`}>
                                <Icon name={isLocated ? 'check_circle' : 'location_searching'} size={15} />
                                {isLocated ? 'Lokasi sudah ditandai' : 'Belum ada lokasi'}
                            </span>
                        </div>
                        {addresses[index] && <p className="line-clamp-2 text-label-sm font-normal text-on-surface-variant">📍 {addresses[index]}</p>}

                        <PlaceSearch onSelect={(result) => locate(index, result, { fly: true })} />

                        <input
                            type="text"
                            maxLength={80}
                            value={place.name ?? ''}
                            placeholder="Nama tempat, mis. Kafe tempat pertama ketemu"
                            onChange={(event) => update(index, { name: event.target.value })}
                            className={inputClass}
                        />
                        <textarea
                            rows={2}
                            maxLength={200}
                            value={place.caption ?? ''}
                            placeholder="Ceritanya (boleh dikosongkan kalau pakai foto)"
                            onChange={(event) => update(index, { caption: event.target.value })}
                            className={`${inputClass} resize-none`}
                        />
                        <ImageField value={place.media} onChange={(media) => update(index, { media })} greetingId={greetingId} mediaCache={mediaCache} addMedia={addMedia} />
                        {!place.caption && !place.media && <span className="text-label-sm text-tertiary">Isi teks, foto, atau keduanya untuk titik ini.</span>}
                    </div>
                );
            })}

            {places.length < max && (
                <button
                    type="button"
                    onClick={add}
                    className="flex items-center justify-center gap-2 rounded-2xl border-2 border-dashed border-primary/40 py-3 text-label-lg text-primary"
                >
                    <Icon name="add_location_alt" size={20} />
                    Tambah titik ({places.length}/{max})
                </button>
            )}

            <p className="flex items-start gap-1.5 rounded-2xl bg-tertiary-fixed/40 px-3 py-2.5 text-label-sm font-normal text-on-tertiary-fixed">
                <Icon name="shield_person" size={16} className="shrink-0" />
                Lokasi ini terlihat oleh siapa pun yang membuka kartu. Hindari menandai alamat rumah persis.
            </p>
        </div>
    );
}
