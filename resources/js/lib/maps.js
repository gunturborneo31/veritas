/**
 * Utilitas peta untuk komponen Jejak Kenangan. Leaflet (± 40 KB) baru diunduh saat peta benar-benar tampil,
 * jadi halaman lain tidak ikut menanggung bobotnya.
 */
let leafletPromise = null;

export function loadLeaflet() {
    leafletPromise ??= Promise.all([import('leaflet'), import('leaflet/dist/leaflet.css')]).then(([module]) => module.default ?? module);

    return leafletPromise;
}

/** Titik tengah Indonesia, dipakai saat belum ada titik sama sekali. */
export const DEFAULT_CENTER = [-2.5, 118];

export const DEFAULT_ZOOM = 4;

export function hasCoordinates(place) {
    return [place?.lat, place?.lng].every((value) => value !== null && value !== undefined && value !== '' && Number.isFinite(Number(value)));
}

/**
 * Koordinat yang ditempel pengirim, mis. "-6.914744, 107.602243" (format salinan Google Maps).
 *
 * @returns {{ lat: number, lng: number } | null}
 */
export function parseCoordinates(text) {
    const match = String(text).trim().match(/^(-?\d{1,2}(?:\.\d+)?)\s*[,;\s]\s*(-?\d{1,3}(?:\.\d+)?)$/);

    if (!match) {
        return null;
    }

    const [lat, lng] = [Number(match[1]), Number(match[2])];

    return Math.abs(lat) <= 90 && Math.abs(lng) <= 180 ? { lat, lng } : null;
}

export function toLatLng(place) {
    return [Number(place.lat), Number(place.lng)];
}

/**
 * Lapisan ubin sesuai konfigurasi server (shared prop `maps`).
 */
export function addTiles(L, map, maps) {
    return L.tileLayer(maps?.tile_url ?? 'https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: maps?.attribution ?? '&copy; OpenStreetMap',
    }).addTo(map);
}

/**
 * Penanda tetesan bernomor dengan warna aksen tema (var(--color-primary)).
 */
export function numberedPin(L, number, { isActive = false } = {}) {
    const size = isActive ? 38 : 32;

    return L.divIcon({
        className: '',
        iconSize: [size, size],
        iconAnchor: [size / 2, size],
        html: `<span style="display:flex;width:${size}px;height:${size}px;align-items:center;justify-content:center;border-radius:50% 50% 50% 0;transform:rotate(-45deg);background:rgb(var(--color-primary));border:3px solid #fff;box-shadow:0 6px 14px rgba(40,21,38,.35)"><span style="transform:rotate(45deg);color:#fff;font:800 ${isActive ? 15 : 13}px/1 Epilogue,sans-serif">${number}</span></span>`,
    });
}

/**
 * Penanda "pejalan" yang bergerak di antara titik saat animasi berpindah.
 */
export function travelerIcon(L) {
    return L.divIcon({
        className: '',
        iconSize: [28, 28],
        iconAnchor: [14, 14],
        html: '<span style="display:flex;width:28px;height:28px;align-items:center;justify-content:center;border-radius:9999px;background:#fff;box-shadow:0 4px 12px rgba(40,21,38,.35);font-size:16px">💗</span>',
    });
}
