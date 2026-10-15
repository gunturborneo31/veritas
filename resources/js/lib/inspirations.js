/**
 * Pustaka kalimat siap pakai untuk Studio ("Inspirasi kata"). {nama} diganti nama penerima.
 * Dikelompokkan per momen lalu per nada, sesuai pilihan nada di desain Studio.
 */
const LIBRARY = {
    ulangTahun: {
        keywords: ['ulang tahun', 'ultah', 'birthday', 'seventeen'],
        tones: {
            '✨ Lucu & Hangat': [
                'Selamat ulang tahun, {nama}! Umur boleh nambah, tapi kelakuan tetap lucu ya. Semoga tahun ini banyak tawa dan sedikit drama 🎂',
                'Hari ini kamu resmi naik level, {nama}! Semoga semua quest hidupmu lancar dan bonus rezekinya melimpah.',
                '{nama}, terima kasih sudah lahir ke dunia dan bikin hari-hariku jauh lebih seru.',
            ],
            '🕊️ Doa Tulus': [
                'Selamat ulang tahun, {nama}. Semoga Allah/Tuhan selalu menjaga langkahmu, melapangkan rezekimu, dan menenangkan hatimu.',
                'Di usia barumu, semoga setiap doa yang kamu bisikkan pelan-pelan menemukan jalannya untuk terkabul, {nama}.',
            ],
            '🌸 Puitis Manis': [
                'Untuk {nama}, yang hadirnya seperti matahari pagi: tidak berisik, tapi menghangatkan semuanya.',
                'Satu tahun lagi kamu tumbuh jadi versi yang lebih indah. Aku beruntung bisa menyaksikannya, {nama}.',
            ],
        },
    },
    pasangan: {
        keywords: ['anniversary', 'valentine', 'pasangan', 'jadian', 'tanpa alasan'],
        tones: {
            '💞 Romantis': [
                'Dari semua hal yang terjadi tahun ini, kamu tetap bagian favoritku, {nama}.',
                'Terima kasih sudah memilih bertahan di hari-hari baik maupun yang berat. Aku sayang kamu, {nama}.',
            ],
            '✨ Lucu & Hangat': [
                'Selamat anniversary, {nama}! Terima kasih sudah sabar menghadapi aku yang lapar dan bawel.',
                'Satu tahun lagi bareng kamu dan aku masih belum bosan. Itu prestasi, {nama} 😌',
            ],
        },
    },
    keluarga: {
        keywords: ['ibu', 'ayah', 'orang tua', 'keluarga', 'kakak', 'adik'],
        tones: {
            '🕊️ Doa Tulus': [
                'Terima kasih untuk setiap doa yang tak pernah putus, {nama}. Semua yang kucapai hari ini berawal dari pelukanmu.',
                'Semoga sehat selalu, panjang umur, dan bahagia, {nama}. Giliranku sekarang yang menjaga.',
            ],
            '🌸 Puitis Manis': [
                'Rumah bukan soal tempat, {nama}. Rumah adalah kamu yang selalu menunggu dengan senyum yang sama.',
            ],
        },
    },
    hariRaya: {
        keywords: ['idulfitri', 'lebaran', 'iduladha', 'ramadan', 'natal', 'imlek'],
        tones: {
            '🕊️ Doa Tulus': [
                'Taqabbalallahu minna wa minkum. Mohon maaf lahir dan batin, {nama}. Semoga kita dipertemukan lagi dengan hari raya berikutnya.',
                'Semoga hari yang fitri ini membawa ketenangan, kesehatan, dan rezeki yang berkah untuk {nama} sekeluarga.',
            ],
            '✨ Lucu & Hangat': ['Maaf lahir batin ya, {nama}! Termasuk maaf karena sering ngutang gorengan 🤭'],
        },
    },
    maaf: {
        keywords: ['maaf', 'baikan', 'salah paham'],
        tones: {
            '🤍 Tulus': [
                '{nama}, aku salah. Aku tidak minta kamu langsung lupa, aku cuma ingin kamu tahu aku sadar dan mau memperbaikinya.',
                'Maaf sudah membuatmu merasa tidak didengar, {nama}. Mulai sekarang aku mau belajar mendengar sampai selesai.',
            ],
            '🌧️ Puitis': ['Setelah hujan, langit biasanya lebih jernih. Semoga setelah ini, kita juga, {nama}.'],
        },
    },
    kangen: {
        keywords: ['kangen', 'ldr', 'rindu', 'perantauan'],
        tones: {
            '💭 Rindu': [
                'Jarak cuma angka, {nama}. Yang kurasakan tiap malam jauh lebih besar dari itu.',
                'Hari-hariku tetap jalan, tapi selalu ada yang kurang kalau tidak ada kamu, {nama}.',
            ],
            '✨ Lucu & Hangat': ['{nama}, cepat pulang ya. Stok cerita random-ku sudah numpuk dan cuma kamu yang mau dengar 😆'],
        },
    },
    nembak: {
        keywords: ['nembak', 'confess', 'kencan'],
        tones: {
            '💘 Berani': [
                '{nama}, dari semua obrolan random kita, aku sadar hariku selalu lebih ringan kalau ada kamu.',
                'Aku sudah lama menyimpan ini, {nama}. Hari ini aku mau berani jujur sekali saja.',
            ],
            '✨ Lucu & Hangat': ['{nama}, aku sudah latihan ngomong ini di depan cermin 27 kali. Jadi tolong dibaca sampai habis ya 🙈'],
        },
    },
};

const GENERAL = {
    '✨ Lucu & Hangat': ['{nama}, terima kasih sudah jadi orang yang bikin hari-hariku lebih seru.'],
    '🕊️ Doa Tulus': ['Semoga kebahagiaan selalu menemukan jalannya ke hidupmu, {nama}.'],
};

/**
 * Pilih kelompok kalimat berdasarkan momen yang diisi pengirim.
 *
 * @returns {Record<string, string[]>}
 */
export function inspirationsFor(occasion) {
    const text = (occasion ?? '').toLowerCase();
    const group = Object.values(LIBRARY).find((entry) => entry.keywords.some((keyword) => text.includes(keyword)));

    return group?.tones ?? GENERAL;
}

export function personalize(phrase, recipientName) {
    return phrase.replaceAll('{nama}', recipientName?.trim() || 'kamu');
}
