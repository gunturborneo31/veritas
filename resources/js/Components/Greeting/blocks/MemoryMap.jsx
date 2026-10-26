import Icon from '@/Components/Icon';
import { addTiles, DEFAULT_CENTER, DEFAULT_ZOOM, hasCoordinates, loadLeaflet, numberedPin, toLatLng, travelerIcon } from '@/lib/maps';
import { usePage } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';
import { MediaImage, SceneLabel } from './shared';

const PLACE_ZOOM = 15;

const TRAVEL_MS = 1600;

const prefersReducedMotion = () => window.matchMedia?.('(prefers-reduced-motion: reduce)').matches ?? false;

const easeInOut = (t) => (t < 0.5 ? 2 * t * t : 1 - (-2 * t + 2) ** 2 / 2);

/**
 * Jalankan callback setelah peta selesai bergerak, dengan cadangan waktu bila event `moveend` tidak muncul
 * (mis. dua titik di posisi yang sama).
 */
function afterMove(map, callback) {
    let isDone = false;
    const finish = () => {
        if (isDone) {
            return;
        }

        isDone = true;
        clearTimeout(timer);
        map.off('moveend', finish);
        callback();
    };
    const timer = setTimeout(finish, 2500);
    map.once('moveend', finish);
}

/**
 * Jejak Kenangan: penerima diajak berpindah dari titik ke titik di peta asli (OpenStreetMap).
 * Tiap titik membuka foto, teks, atau keduanya; di akhir seluruh jejak ditampilkan sekaligus.
 */
export default function MemoryMap({ slot, values, mode }) {
    const { maps } = usePage().props;
    const places = (values.places ?? []).filter(hasCoordinates);
    const title = values.title || slot?.title || 'Jejak Kenangan';
    const [step, setStep] = useState(0);
    const [phase, setPhase] = useState('arrived');
    const [isReady, setIsReady] = useState(false);
    const containerRef = useRef(null);
    const mapRef = useRef(null);
    const leafletRef = useRef(null);
    const layerRef = useRef(null);
    const frameRef = useRef(null);
    const resizeTimerRef = useRef(null);
    const placesKey = places.map((place) => `${place.lat},${place.lng}`).join('|');

    useEffect(() => {
        let isMounted = true;

        loadLeaflet().then((L) => {
            if (!isMounted || !containerRef.current) {
                return;
            }

            leafletRef.current = L;
            const map = L.map(containerRef.current, {
                zoomControl: false,
                dragging: false,
                touchZoom: false,
                scrollWheelZoom: false,
                doubleClickZoom: false,
                boxZoom: false,
                keyboard: false,
            }).setView(places[0] ? toLatLng(places[0]) : DEFAULT_CENTER, places[0] ? PLACE_ZOOM : DEFAULT_ZOOM);
            addTiles(L, map, maps);
            layerRef.current = L.layerGroup().addTo(map);
            mapRef.current = map;
            // Adegan masuk dengan animasi skala; ukur ulang setelahnya agar ubin tidak terpotong.
            resizeTimerRef.current = setTimeout(() => mapRef.current === map && map.invalidateSize(), 500);
            setIsReady(true);
        });

        // Penerima bisa pindah adegan saat peta masih beranimasi: hentikan semuanya sebelum peta dihapus,
        // agar Leaflet tidak mengukur elemen yang sudah hilang (error `_leaflet_pos`).
        return () => {
            isMounted = false;
            clearTimeout(resizeTimerRef.current);
            cancelAnimationFrame(frameRef.current);
            mapRef.current?.stop();
            mapRef.current?.remove();
            mapRef.current = null;
        };
    }, []); // eslint-disable-line react-hooks/exhaustive-deps

    // Data berubah (mis. pengirim mengedit di Studio): kembali ke titik pertama.
    useEffect(() => {
        setStep(0);
        setPhase('arrived');

        if (mapRef.current && places[0]) {
            mapRef.current.setView(toLatLng(places[0]), PLACE_ZOOM);
        }
    }, [placesKey]); // eslint-disable-line react-hooks/exhaustive-deps

    useEffect(() => {
        const L = leafletRef.current;
        const layer = layerRef.current;

        if (!L || !layer || phase === 'moving') {
            return;
        }

        layer.clearLayers();
        const visited = phase === 'overview' ? places : places.slice(0, step + 1);

        if (visited.length > 1) {
            L.polyline(visited.map(toLatLng), { color: 'rgb(var(--color-primary))', weight: 4, opacity: 0.85 }).addTo(layer);
        }

        visited.forEach((place, index) => {
            L.marker(toLatLng(place), { icon: numberedPin(L, index + 1, { isActive: phase !== 'overview' && index === step }), interactive: false }).addTo(layer);
        });
    }, [isReady, step, phase, placesKey]); // eslint-disable-line react-hooks/exhaustive-deps

    const travelTo = (nextStep) => {
        const L = leafletRef.current;
        const map = mapRef.current;
        const from = toLatLng(places[step]);
        const to = toLatLng(places[nextStep]);

        if (!L || !map) {
            return;
        }

        const arrive = () => {
            setStep(nextStep);
            setPhase('arrived');
        };

        if (prefersReducedMotion()) {
            map.setView(to, PLACE_ZOOM);
            arrive();

            return;
        }

        setPhase('moving');
        const trail = L.polyline([from], { color: 'rgb(var(--color-primary))', weight: 4, dashArray: '2 8', lineCap: 'round' }).addTo(layerRef.current);
        const traveler = L.marker(from, { icon: travelerIcon(L), interactive: false }).addTo(layerRef.current);

        afterMove(map, () => {
            // Adegan sudah ditutup sebelum animasi selesai.
            if (mapRef.current !== map) {
                return;
            }

            const startedAt = performance.now();

            const tick = (now) => {
                if (mapRef.current !== map) {
                    return;
                }

                const progress = easeInOut(Math.min((now - startedAt) / TRAVEL_MS, 1));
                const position = [from[0] + (to[0] - from[0]) * progress, from[1] + (to[1] - from[1]) * progress];
                traveler.setLatLng(position);
                trail.setLatLngs([from, position]);

                if (progress < 1) {
                    frameRef.current = requestAnimationFrame(tick);

                    return;
                }

                afterMove(map, () => mapRef.current === map && arrive());
                map.flyTo(to, PLACE_ZOOM, { duration: 1 });
            };

            frameRef.current = requestAnimationFrame(tick);
        });
        map.flyToBounds(L.latLngBounds([from, to]), { paddingTopLeft: [40, 40], paddingBottomRight: [40, 90], duration: 1, maxZoom: PLACE_ZOOM });
    };

    const showOverview = () => {
        const L = leafletRef.current;

        setPhase('overview');

        if (L && mapRef.current && places.length > 0) {
            mapRef.current.flyToBounds(L.latLngBounds(places.map(toLatLng)), { paddingTopLeft: [36, 36], paddingBottomRight: [36, 90], duration: prefersReducedMotion() ? 0 : 1.2, maxZoom: PLACE_ZOOM });
        }
    };

    const restart = () => {
        setStep(0);
        setPhase('arrived');
        mapRef.current?.flyTo(toLatLng(places[0]), PLACE_ZOOM, { duration: prefersReducedMotion() ? 0 : 1.2 });
    };

    if (places.length === 0) {
        return (
            <div className="flex w-full flex-col items-center gap-3 text-center">
                <SceneLabel icon="map">{title}</SceneLabel>
                <div className="flex h-48 w-full flex-col items-center justify-center gap-2 rounded-3xl bg-surface-container-lowest/80 p-6 text-on-surface-variant shadow-sm">
                    <Icon name="add_location_alt" size={36} className="text-primary" />
                    <p className="text-body-sm">{mode === 'studio' ? 'Tambahkan titik kenangan di formulir untuk melihat petanya.' : 'Belum ada titik kenangan.'}</p>
                </div>
            </div>
        );
    }

    const current = places[step];
    const isLast = step >= places.length - 1;

    return (
        <div className="flex w-full flex-col gap-3">
            <SceneLabel icon="map">{title}</SceneLabel>

            <div className="relative isolate z-0 h-[clamp(190px,34dvh,270px)] w-full overflow-hidden rounded-3xl bg-surface-container shadow-[0_14px_30px_-18px_rgba(40,21,38,0.55)] ring-4 ring-surface-container-lowest">
                <div ref={containerRef} className="h-full w-full" />
                <div className="pointer-events-none absolute left-2.5 top-2.5 z-[500] flex gap-1">
                    {places.map((place, index) => (
                        <span
                            key={index}
                            className={`h-1.5 rounded-full transition-all ${index <= step || phase === 'overview' ? 'w-5 bg-primary' : 'w-2.5 bg-on-surface/20'}`}
                        />
                    ))}
                </div>
                {places.length > 1 && (
                    <button
                        type="button"
                        disabled={phase === 'moving'}
                        onClick={phase === 'overview' ? restart : isLast ? showOverview : () => travelTo(step + 1)}
                        className="absolute inset-x-2.5 bottom-7 z-[500] flex items-center justify-center gap-1.5 rounded-full bg-primary px-4 py-2.5 text-label-md text-on-primary shadow-lg transition-transform active:scale-[0.98] disabled:opacity-70"
                    >
                        <Icon name={phase === 'moving' ? 'progress_activity' : phase === 'overview' ? 'replay' : isLast ? 'map' : 'near_me'} size={18} className={phase === 'moving' ? 'animate-spin' : ''} />
                        <span className="truncate">
                            {phase === 'moving'
                                ? 'Sedang menuju…'
                                : phase === 'overview'
                                  ? 'Ulangi perjalanan'
                                  : isLast
                                    ? 'Lihat seluruh jejak'
                                    : `Ke titik ${step + 2}: ${places[step + 1].name || 'berikutnya'}`}
                        </span>
                    </button>
                )}
            </div>

            {phase === 'overview' ? (
                <div className="flex animate-layer-in flex-col gap-2 rounded-2xl bg-surface-container-lowest/90 p-3.5 shadow-sm">
                    <p className="text-label-md text-primary">Semua jejak kita</p>
                    <ol className="flex flex-col gap-1.5">
                        {places.map((place, index) => (
                            <li key={index} className="flex items-center gap-2 text-body-sm text-on-surface">
                                <span className="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-primary text-label-sm text-on-primary">{index + 1}</span>
                                {place.name || `Titik ${index + 1}`}
                            </li>
                        ))}
                    </ol>
                </div>
            ) : (
                <div key={step} className={`flex items-start gap-3 rounded-2xl bg-surface-container-lowest/90 p-3 shadow-sm ${phase === 'moving' ? 'opacity-40' : 'animate-layer-in'}`}>
                    {current.media?.url && (
                        <div className={`shrink-0 overflow-hidden rounded-xl ${current.caption ? 'h-20 w-20' : 'h-24 w-32'}`}>
                            <MediaImage media={current.media} />
                        </div>
                    )}
                    <div className="flex min-w-0 flex-col gap-1">
                        <p className="flex items-center gap-1 font-display text-label-lg text-on-surface">
                            <Icon name="location_on" filled size={18} className="shrink-0 text-primary" />
                            <span className="truncate">{current.name || `Titik ${step + 1}`}</span>
                        </p>
                        {current.caption && <p className="line-clamp-4 text-body-sm leading-snug text-on-surface-variant">{current.caption}</p>}
                    </div>
                </div>
            )}
        </div>
    );
}
