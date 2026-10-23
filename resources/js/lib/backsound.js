/**
 * Musik latar ucapan. Satu elemen audio disimpan di luar komponen supaya musik yang mulai di
 * halaman gerbang tetap berlanjut tanpa putus saat kartu dibuka (pindah halaman lewat Inertia).
 *
 * Browser memblokir audio bersuara sebelum pengunjung berinteraksi, jadi pemutaran otomatis
 * dicoba dulu, lalu dilanjutkan pada sentuhan/ketukan pertama di mana saja.
 */
const VOLUME = 0.45;
const FADE_IN_MS = 1500;
const GESTURE_EVENTS = ['pointerdown', 'touchend', 'keydown'];

let audio = null;

export function backsound(url) {
    if (typeof window === 'undefined' || !url) {
        return null;
    }

    if (!audio) {
        audio = new Audio();
        audio.loop = true;
        audio.preload = 'auto';
    }

    if (audio.dataset.src !== url) {
        audio.src = url;
        audio.dataset.src = url;
    }

    return audio;
}

/**
 * Putar dengan fade-in ke volume latar. Bila sudah berbunyi, biarkan (tidak diulang dari awal).
 */
export function playBacksound(url) {
    const element = backsound(url);

    if (!element) {
        return Promise.reject(new Error('Tidak ada musik latar.'));
    }

    if (!element.paused) {
        return Promise.resolve();
    }

    element.volume = 0;

    return element.play().then(() => {
        const startedAt = performance.now();
        const step = (now) => {
            // Timestamp frame bisa sedikit lebih awal dari `startedAt`, jadi dibatasi ke 0–1.
            const progress = Math.min(1, Math.max(0, (now - startedAt) / FADE_IN_MS));
            element.volume = VOLUME * progress;

            if (progress < 1 && !element.paused) {
                requestAnimationFrame(step);
            }
        };
        requestAnimationFrame(step);
    });
}

/**
 * Coba putar sekarang; jika diblokir browser, mulai pada interaksi pertama. Mengembalikan fungsi pembersih.
 */
export function startBacksoundOnFirstGesture(url) {
    if (!url) {
        return () => {};
    }

    let isCancelled = false;
    const start = () => {
        playBacksound(url)
            .then(stopListening)
            .catch(() => {});
    };
    const stopListening = () => GESTURE_EVENTS.forEach((event) => window.removeEventListener(event, start, true));

    playBacksound(url).catch(() => {
        if (!isCancelled) {
            GESTURE_EVENTS.forEach((event) => window.addEventListener(event, start, true));
        }
    });

    return () => {
        isCancelled = true;
        stopListening();
    };
}

export function stopBacksound() {
    if (audio) {
        audio.pause();
        audio.muted = false;
    }
}
