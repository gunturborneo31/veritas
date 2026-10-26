/**
 * Smoke test end-to-end di Chrome sungguhan (headless, lewat DevTools Protocol, tanpa dependency).
 * Setiap halaman dicek: status HTTP, komponen Inertia, layar tidak kosong, dan tidak ada error JS.
 *
 * Alur:
 *  A. Pembeli anonim: katalog → buat ucapan → isi → gaya → pratinjau → checkout (daftar akun) → pesanan → kirim bukti
 *  B. Admin: login → konfirmasi pembayaran
 *  C. Pembeli: hasil → halaman penerima → dashboard
 *  D. Admin membuat undangan: isi + acara → pratinjau → checkout "Terbitkan Gratis" → hasil → kelola → revisi → halaman tamu
 */
import { spawn } from 'node:child_process';
import { mkdtempSync, rmSync, writeFileSync } from 'node:fs';
import { tmpdir } from 'node:os';
import { join } from 'node:path';

const BASE = (process.env.SMOKE_URL ?? 'http://127.0.0.1:8765').replace(/\/$/, '');
const CHROME = process.env.CHROME_PATH ?? 'C:/Program Files/Google/Chrome/Application/chrome.exe';
const DEBUG_PORT = 9333;
const sleep = (ms) => new Promise((resolve) => setTimeout(resolve, ms));

class Cdp {
    constructor(ws) {
        this.ws = ws;
        this.id = 0;
        this.pending = new Map();
        this.listeners = [];
        ws.onmessage = (event) => {
            const message = JSON.parse(event.data);

            if (message.id && this.pending.has(message.id)) {
                const { resolve, reject } = this.pending.get(message.id);
                this.pending.delete(message.id);
                message.error ? reject(new Error(message.error.message)) : resolve(message.result);
            } else {
                this.listeners.forEach((listener) => listener(message));
            }
        };
    }

    send(method, params = {}, sessionId = undefined) {
        const id = ++this.id;
        this.ws.send(JSON.stringify({ id, method, params, sessionId }));

        return new Promise((resolve, reject) => this.pending.set(id, { resolve, reject }));
    }
}

class Tab {
    constructor(cdp, sessionId, name) {
        this.cdp = cdp;
        this.sessionId = sessionId;
        this.name = name;
        this.errors = [];
        this.documentStatus = null;
        this.loaded = false;

        cdp.listeners.push((message) => {
            if (message.sessionId !== sessionId) {
                return;
            }

            const { method, params } = message;

            if (method === 'Runtime.exceptionThrown') {
                const details = params.exceptionDetails;
                this.errors.push(`JS exception: ${details.exception?.description?.split('\n')[0] ?? details.text}`);
            } else if (method === 'Runtime.consoleAPICalled' && params.type === 'error') {
                this.errors.push(`console.error: ${params.args.map((arg) => arg.value ?? arg.description ?? '').join(' ').slice(0, 300)}`);
            } else if (method === 'Network.responseReceived' && params.type === 'Document') {
                this.documentStatus = params.response.status;
            } else if (method === 'Network.responseReceived' && params.response.status >= 500) {
                this.errors.push(`HTTP ${params.response.status}: ${params.response.url}`);
            } else if (method === 'Page.loadEventFired') {
                this.loaded = true;
            }
        });
    }

    send(method, params) {
        return this.cdp.send(method, params, this.sessionId);
    }

    async init() {
        await Promise.all(['Page.enable', 'Runtime.enable', 'Network.enable'].map((method) => this.send(method)));
        // Dialog konfirmasi (window.confirm) di halaman admin selalu dijawab "OK".
        await this.send('Page.addScriptToEvaluateOnNewDocument', { source: 'window.confirm = () => true;' });
    }

    async eval(fn, ...args) {
        const expression = `(${fn})(...${JSON.stringify(args)})`;
        const result = await this.send('Runtime.evaluate', { expression, awaitPromise: true, returnByValue: true });

        if (result.exceptionDetails) {
            throw new Error(result.exceptionDetails.exception?.description ?? result.exceptionDetails.text);
        }

        return result.result.value;
    }

    async goto(path) {
        this.loaded = false;
        this.documentStatus = null;
        await this.send('Page.navigate', { url: BASE + path });
        await this.waitUntil(async () => this.loaded, `halaman ${path} selesai dimuat`);
        await sleep(700);
    }

    async waitUntil(check, label, timeout = 15000) {
        const started = Date.now();

        while (Date.now() - started < timeout) {
            if (await check().catch(() => false)) {
                return;
            }

            await sleep(200);
        }

        const screen = await this.eval(() => document.body.innerText.replace(/\s+/g, ' ').slice(0, 400)).catch(() => '-');
        throw new Error(`Timeout menunggu: ${label}\n   layar: ${screen}\n   error: ${this.errors.join(' | ') || '-'}`);
    }

    async waitForPath(prefix) {
        await this.waitUntil(() => this.eval((p) => location.pathname.startsWith(p), prefix), `URL ${prefix}…`);
        await sleep(900);
    }

    path() {
        return this.eval(() => location.pathname);
    }

    page() {
        return this.eval(() => {
            const page = window.history.state?.page ?? JSON.parse(document.querySelector('#app')?.dataset.page ?? 'null');

            return page && { component: page.component, props: page.props };
        });
    }

    click(text) {
        return this.eval((label) => {
            const target = [...document.querySelectorAll('button, a')].find((element) => element.innerText.trim().includes(label));

            if (!target) {
                throw new Error(`Tombol/tautan "${label}" tidak ditemukan`);
            }

            if (target.disabled) {
                throw new Error(`Tombol "${label}" masih nonaktif`);
            }

            target.click();
        }, text);
    }

    async setFile(selector, path) {
        const { root } = await this.send('DOM.getDocument');
        const { nodeId } = await this.send('DOM.querySelector', { nodeId: root.nodeId, selector });

        if (!nodeId) {
            throw new Error(`Input file ${selector} tidak ditemukan`);
        }

        await this.send('DOM.setFileInputFiles', { nodeId, files: [path] });
    }

    hasText(text) {
        return this.eval((label) => document.body.innerText.includes(label), text);
    }

    type(selector, value) {
        return this.eval(
            (sel, val) => {
                const element = document.querySelector(sel);

                if (!element) {
                    throw new Error(`Input ${sel} tidak ditemukan`);
                }

                const prototype = element.tagName === 'TEXTAREA' ? HTMLTextAreaElement.prototype : HTMLInputElement.prototype;
                Object.getOwnPropertyDescriptor(prototype, 'value').set.call(element, val);
                element.dispatchEvent(new Event('input', { bubbles: true }));
            },
            selector,
            value,
        );
    }

    /** Request dengan sesi & CSRF milik tab ini (dipakai untuk mengisi data tanpa mengetik di tiap field). */
    request(method, path, body = null) {
        return this.eval(
            async (m, p, b) => {
                const token = decodeURIComponent((document.cookie.match(/XSRF-TOKEN=([^;]+)/) ?? [])[1] ?? '');
                const response = await fetch(p, {
                    method: m,
                    credentials: 'same-origin',
                    redirect: 'manual',
                    headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-XSRF-TOKEN': token, 'X-Requested-With': 'XMLHttpRequest' },
                    body: b ? JSON.stringify(b) : undefined,
                });

                if (response.type === 'opaqueredirect') {
                    return { status: 302, body: '' };
                }

                const text = await response.text();

                return { status: response.status, body: response.status >= 400 ? text.slice(0, 400) : '' };
            },
            method,
            path,
            body,
        );
    }
}

const results = [];

async function check(tab, step, { component = null, text = null } = {}) {
    await sleep(300);
    const page = await tab.page().catch(() => null);
    const isBlank = await tab.eval(() => (document.querySelector('#app')?.innerText.trim().length ?? 0) < 10);
    const problems = [...tab.errors];
    tab.errors = [];

    if (tab.documentStatus && tab.documentStatus >= 400) {
        problems.push(`HTTP ${tab.documentStatus}`);
    }

    if (isBlank) {
        problems.push('LAYAR KOSONG (blank)');

        if (process.env.SMOKE_DEBUG) {
            console.log(await tab.eval(() => `${location.href}\n${document.documentElement.outerHTML.slice(0, 1500)}`));
        }
    }

    if (component && page?.component !== component) {
        problems.push(`komponen ${page?.component ?? '-'}, seharusnya ${component}`);
    }

    if (text && !(await tab.hasText(text))) {
        problems.push(`teks "${text}" tidak tampil`);
    }

    const path = await tab.path();
    results.push({ step, path, component: page?.component ?? '-', ok: problems.length === 0, problems });
    console.log(`${problems.length === 0 ? '✅' : '❌'} ${step.padEnd(42)} ${path}${problems.length ? '\n     ↳ ' + problems.join('\n     ↳ ') : ''}`);

    return page;
}

async function expectOk(response, label) {
    if (response.status >= 400) {
        throw new Error(`${label} gagal (HTTP ${response.status}): ${response.body}`);
    }

    return response;
}

const content = {
    components: {
        sampul: { title: 'Selamat Ulang Tahun!' },
        gembok: { enabled: false },
        surat: { body: 'Semoga panjang umur dan sehat selalu.' },
    },
};

// Bukti bayar palsu: PNG 1×1 piksel.
const PROOF_IMAGE = join(tmpdir(), 'veritas-smoke-bukti.png');
writeFileSync(PROOF_IMAGE, Buffer.from('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==', 'base64'));

const publicSlug =(props) => (JSON.stringify(props).match(/\/u\/([a-z0-9-]+)/) ?? [])[1];

async function run(browser) {
    const newTab = async (name) => {
        const { browserContextId } = await browser.send('Target.createBrowserContext');
        const { targetId } = await browser.send('Target.createTarget', { url: 'about:blank', browserContextId });
        const { sessionId } = await browser.send('Target.attachToTarget', { targetId, flatten: true });
        const tab = new Tab(browser, sessionId, name);
        await tab.init();

        return tab;
    };

    const buyer = await newTab('pembeli');
    const admin = await newTab('admin');
    const email = `pembeli${Date.now()}@smoke.test`;

    console.log('\n— A. Pembeli: buat ucapan sampai bayar —');
    await buyer.goto('/');
    await check(buyer, 'Beranda');
    await buyer.goto('/katalog');
    await check(buyer, 'Katalog', { component: 'Catalog/Index' });
    await buyer.goto('/katalog/smoke-ucapan');
    await check(buyer, 'Detail template', { component: 'Catalog/Show' });
    await buyer.goto('/katalog/smoke-ucapan/demo');
    await check(buyer, 'Demo template');

    await buyer.goto('/katalog/smoke-ucapan');
    await buyer.click('Buat sekarang');
    await buyer.waitForPath('/studio/');
    const greetingId = (await buyer.path()).split('/')[2];
    await check(buyer, 'Studio: isi kartu', { component: 'Studio/Edit' });

    await expectOk(await buyer.request('PUT', `/studio/${greetingId}`, { recipient_name: 'Sari', sender_name: 'Budi', occasion: 'Ulang Tahun', content }), 'Simpan isi kartu');
    await buyer.goto(`/studio/${greetingId}/gaya`);
    await check(buyer, 'Studio: gaya', { component: 'Studio/Style' });

    await buyer.click('Lanjut: Pratinjau');
    await buyer.waitForPath('/pratinjau/');
    await check(buyer, 'Pratinjau', { component: 'Preview/Show', text: 'Lanjut Bayar' });

    await buyer.click('Lanjut Bayar');
    await buyer.waitForPath('/checkout/');
    await check(buyer, 'Checkout (tamu, belum login)', { component: 'Checkout/Show', text: 'Bayar dengan QRIS' });

    await buyer.type('input[placeholder="Nama kamu"]', 'Pembeli Smoke');
    await buyer.type('input[type="email"]', email);
    await buyer.type('input[type="tel"]', `0812${String(Date.now()).slice(-8)}`);
    await buyer.type('input[placeholder="Minimal 8 karakter"]', 'rahasia-123');
    await buyer.eval(() => document.querySelector('input[type="checkbox"]').click());
    await sleep(300);
    await buyer.click('Bayar dengan QRIS');
    await buyer.waitForPath('/pesanan/');
    const orderCode = (await buyer.path()).split('/')[2];
    await check(buyer, 'Pesanan: langkah 1 scan QRIS', { component: 'Orders/Show', text: 'Scan QRIS' });
    await check(buyer, 'Pesanan: langkah 2 bayar nominal', { text: 'Bayar dengan nominal tepat' });
    await check(buyer, 'Pesanan: langkah 3 upload bukti', { text: 'Upload bukti pembayaran' });

    await buyer.setFile('input[type="file"]', PROOF_IMAGE);
    await buyer.waitUntil(() => buyer.eval(() => Boolean(document.querySelector('img[alt="Pratinjau bukti pembayaran"]'))), 'pratinjau bukti tampil');
    await buyer.click('Kirim Bukti Pembayaran');
    await buyer.waitUntil(() => buyer.hasText('Menunggu konfirmasi pembayaran admin'), 'status menunggu konfirmasi admin');
    await check(buyer, 'Pesanan: menunggu konfirmasi admin', { component: 'Orders/Show', text: 'Menunggu konfirmasi pembayaran admin' });

    console.log('\n— B. Admin: login & konfirmasi pembayaran —');
    await admin.goto('/login');
    await check(admin, 'Login', { component: 'Auth/Login' });
    await admin.type('#email', 'admin@smoke.test');
    await admin.type('#password', 'password');
    await admin.click('Masuk');
    await admin.waitUntil(async () => !(await admin.path()).startsWith('/login'), 'login admin');
    await sleep(800);
    await admin.goto('/admin/pesanan');
    await check(admin, 'Admin: daftar pesanan', { component: 'Admin/Orders/Index' });
    await admin.goto(`/admin/pesanan/${orderCode}`);
    await check(admin, 'Admin: detail pesanan', { component: 'Admin/Orders/Show' });
    await admin.click('Konfirmasi');
    await admin.waitUntil(() => admin.hasText('Lunas'), 'pesanan lunas');
    await check(admin, 'Admin: setelah konfirmasi', { component: 'Admin/Orders/Show' });

    console.log('\n— C. Pembeli: kartu terbit —');
    await buyer.goto(`/pesanan/${orderCode}`);
    const result = await check(buyer, 'Hasil (kartu terbit)', { component: 'Result/Show' });
    const slug = publicSlug(result?.props);
    await buyer.goto(`/u/${slug}`);
    await check(buyer, 'Halaman penerima', { component: 'Recipient/Gate' });
    await buyer.click('Buka');
    await buyer.waitUntil(async () => (await buyer.page())?.component === 'Recipient/Play', 'kartu terbuka setelah klik Buka');
    await check(buyer, 'Penerima: kartu dibuka', { component: 'Recipient/Play' });
    await buyer.goto('/dashboard');
    await check(buyer, 'Dashboard pembeli', { component: 'Dashboard' });

    console.log('\n— E. Admin: edit isi kartu milik pelanggan —');
    await admin.goto(`/admin/kartu?q=${slug}`);
    await check(admin, 'Admin: cari kartu', { component: 'Admin/Greetings/Index', text: `/u/${slug}` });
    await admin.click('Edit isi');
    await admin.waitForPath('/studio/');
    await check(admin, 'Admin: editor kartu pelanggan', { component: 'Studio/Edit', text: 'Mode admin' });
    await expectOk(await admin.request('PUT', `/studio/${greetingId}/revisi`, { recipient_name: 'Sari Revisi', sender_name: 'Budi', occasion: 'Ulang Tahun', content }), 'Simpan revisi admin');
    await buyer.goto(`/u/${slug}`);
    const revised = await check(buyer, 'Penerima: halaman setelah revisi');
    const shownName = revised?.props?.greeting?.base?.recipient_name ?? revised?.props?.recipient_name;
    results.push({ step: 'Penerima melihat hasil revisi admin', ok: shownName === 'Sari Revisi', problems: [] });
    console.log(`${shownName === 'Sari Revisi' ? '✅' : '❌'} ${'Penerima melihat hasil revisi admin'.padEnd(42)} nama penerima: ${shownName}`);

    console.log('\n— F. Pembeli: undangan berbayar → konfirmasi admin → tayang → tamu, RSVP, ucapan, check-in —');
    const host = await newTab('pembeli undangan');
    await host.goto('/katalog/smoke-undangan');
    await host.click('Buat sekarang');
    await host.waitForPath('/studio/');
    const paidId = (await host.path()).split('/')[2];
    await check(host, 'Undangan: studio', { component: 'Studio/Edit' });
    await expectOk(await host.request('PUT', `/studio/${paidId}`, { sender_name: 'Andi & Sinta', occasion: 'Pernikahan', content }), 'Simpan isi undangan');
    const paidEventDate = new Date(Date.now() + 45 * 86400000).toISOString().slice(0, 10);
    await expectOk(
        await host.request('POST', `/undangan/${paidId}/acara`, { name: 'Akad Nikah', starts_at: `${paidEventDate} 08:00`, timezone: 'Asia/Jakarta', venue_name: 'Masjid Raya' }),
        'Tambah acara',
    );
    await host.goto(`/studio/${paidId}/gaya`);
    await host.click('Lanjut: Pratinjau');
    await host.waitForPath('/pratinjau/');
    await check(host, 'Undangan: pratinjau', { component: 'Preview/Show', text: 'Lanjut Bayar' });
    await host.click('Lanjut Bayar');
    await host.waitForPath('/checkout/');
    await check(host, 'Undangan: checkout', { component: 'Checkout/Show', text: 'Bayar dengan QRIS' });
    await host.type('input[placeholder="Nama kamu"]', 'Andi Pratama');
    await host.type('input[type="email"]', `andi${Date.now()}@smoke.test`);
    await host.type('input[type="tel"]', `0813${String(Date.now()).slice(-8)}`);
    await host.type('input[placeholder="Minimal 8 karakter"]', 'rahasia-123');
    await host.eval(() => document.querySelector('input[type="checkbox"]').click());
    await sleep(300);
    await host.click('Bayar dengan QRIS');
    await host.waitForPath('/pesanan/');
    const paidOrder = (await host.path()).split('/')[2];
    await check(host, 'Undangan: halaman bayar QRIS', { component: 'Orders/Show', text: 'Scan QRIS' });
    await host.setFile('input[type="file"]', PROOF_IMAGE);
    await host.waitUntil(() => host.eval(() => Boolean(document.querySelector('img[alt="Pratinjau bukti pembayaran"]'))), 'pratinjau bukti');
    await host.click('Kirim Bukti Pembayaran');
    await host.waitUntil(() => host.hasText('Menunggu konfirmasi pembayaran admin'), 'menunggu konfirmasi');
    await check(host, 'Undangan: menunggu konfirmasi admin', { text: 'Menunggu konfirmasi pembayaran admin' });

    await admin.goto(`/admin/pesanan/${paidOrder}`);
    await check(admin, 'Admin: pesanan undangan', { component: 'Admin/Orders/Show' });
    await admin.click('Konfirmasi');
    await admin.waitUntil(() => admin.hasText('Lunas'), 'pesanan undangan lunas');
    await check(admin, 'Admin: undangan dikonfirmasi', { component: 'Admin/Orders/Show' });

    await host.goto(`/pesanan/${paidOrder}`);
    await check(host, 'Undangan: tayang (hasil)', { component: 'Result/Show' });
    await host.goto(`/undangan/${paidId}`);
    await check(host, 'Undangan: kelola (terbit)', { component: 'Invitation/Manage' });
    await expectOk(await host.request('POST', `/undangan/${paidId}/tamu`, { name: 'Budi Santoso', quota: 2, group: 'Keluarga' }), 'Tambah tamu');
    await host.goto(`/undangan/${paidId}`);
    const guest = (await host.page()).props.invitation.guests.find((g) => g.name === 'Budi Santoso');
    const paidSlug = new URL(guest.url).pathname.split('/')[2];

    const visitor = await newTab('tamu');
    await visitor.goto(new URL(guest.url).pathname + new URL(guest.url).search);
    await check(visitor, 'Tamu: link pribadi (nama tamu)', { component: 'Recipient/Gate', text: 'Budi Santoso' });
    await visitor.click('Buka');
    await visitor.waitUntil(async () => (await visitor.page())?.component === 'Recipient/Play', 'undangan terbuka untuk tamu');
    await check(visitor, 'Tamu: undangan dibuka', { component: 'Recipient/Play' });
    await expectOk(await visitor.request('POST', `/u/${paidSlug}/rsvp`, { attendance: 'hadir', party_size: 2 }), 'RSVP hadir');
    await expectOk(await visitor.request('POST', `/u/${paidSlug}/ucapan-doa`, { name: 'Budi Santoso', message: 'Selamat menempuh hidup baru, semoga sakinah!' }), 'Kirim ucapan & doa');
    await expectOk(await host.request('POST', `/undangan/${paidId}/check-in`, { code: guest.url }), 'Check-in tamu (pindai QR)');

    await host.goto(`/undangan/${paidId}`);
    const managed = (await host.page()).props.invitation;
    const after = managed.guests.find((g) => g.name === 'Budi Santoso');
    const wishText = JSON.stringify(managed);
    const facts = [
        ['tamu sudah membuka undangan', Boolean(after.opened_at)],
        ['RSVP hadir 2 orang tercatat', after.rsvp?.attendance === 'hadir' && after.rsvp?.party_size === 2],
        ['ucapan & doa masuk', wishText.includes('Selamat menempuh hidup baru')],
        ['tamu sudah check-in', Boolean(after.checked_in_at)],
    ];
    for (const [label, ok] of facts) {
        results.push({ step: `Kelola: ${label}`, ok, problems: ok ? [] : ['tidak tercatat'] });
        console.log(`${ok ? '✅' : '❌'} ${`Kelola: ${label}`.padEnd(42)}`);
    }

    console.log('\n— G. Setelah dibagikan: ubah isi & data, link dan template tetap —');
    const fact = (label, ok, detail = '') => {
        results.push({ step: label, ok, problems: ok ? [] : [detail || 'tidak sesuai'] });
        console.log(`${ok ? '✅' : '❌'} ${label.padEnd(42)} ${ok ? '' : detail}`);
    };
    const before = (await host.page()).props.invitation;
    const shareUrl = before.url;
    const templateName = before.template_name;

    // 1) Revisi isi lewat Studio (UI sungguhan): ubah nama tuan rumah lalu tekan "Simpan Revisi".
    await host.goto(`/studio/${paidId}`);
    await check(host, 'Revisi: Studio mode revisi', { component: 'Studio/Edit', text: 'Mode revisi' });
    await host.eval(() => {
        const input = [...document.querySelectorAll('input')].find((el) => el.value === 'Andi & Sinta');
        if (!input) throw new Error('Kolom nama tuan rumah tidak ditemukan');
        Object.getOwnPropertyDescriptor(HTMLInputElement.prototype, 'value').set.call(input, 'Andi & Sinta Pratama');
        input.dispatchEvent(new Event('input', { bubbles: true }));
    });
    await sleep(400);
    await host.click('Simpan Revisi');
    await host.waitUntil(() => host.hasText('Revisi tersimpan'), 'revisi tersimpan');
    await check(host, 'Revisi: tersimpan', { text: 'Revisi tersimpan' });
    await host.goto(`/studio/${paidId}/gaya`);
    fact('Gaya/template terkunci (halaman Gaya 404)', host.documentStatus === 404, `HTTP ${host.documentStatus}`);
    host.errors = [];

    // 2) Kelola: ubah acara, tambah amplop, ubah pengaturan, sembunyikan ucapan.
    await host.goto(`/undangan/${paidId}`);
    const manage = (await host.page()).props.invitation;
    const event = manage.events[0];
    await expectOk(await host.request('PUT', `/undangan/${paidId}/acara/${event.id}`, { name: 'Akad Nikah', starts_at: event.starts_at, timezone: event.timezone, venue_name: 'Masjid Agung Al-Azhar' }), 'Ubah acara');
    await expectOk(await host.request('POST', `/undangan/${paidId}/amplop`, { kind: 'bank', provider: 'BCA', account_number: '1234567890', account_holder: 'Andi Pratama' }), 'Tambah amplop');
    await expectOk(
        await host.request('PUT', `/undangan/${paidId}/pengaturan`, { rsvp_enabled: true, wishes_enabled: true, gifts_enabled: true, default_guest_quota: 3, broadcast_message: 'Halo {nama}, ini undangan kami: {link}' }),
        'Ubah pengaturan',
    );
    const wish = manage.wishes.find((w) => w.message.includes('Selamat menempuh hidup baru'));
    await expectOk(await host.request('PATCH', `/undangan/${paidId}/ucapan-doa/${wish.id}`), 'Sembunyikan ucapan');
    const qr = await host.request('GET', `/undangan/${paidId}/tamu/${guest.id}/qr.png`);
    fact('QR tamu bisa diunduh', qr.status === 200, `HTTP ${qr.status}`);

    // 3) Tamu membuka lagi link yang SAMA dan melihat perubahan terbaru.
    await visitor.goto(new URL(guest.url).pathname + new URL(guest.url).search);
    await sleep(500);
    const guestPage = await visitor.page();
    const shown = guestPage.component === 'Recipient/Play' ? guestPage.props.greeting : null;
    fact('Tamu: link lama tetap terbuka', guestPage.component === 'Recipient/Play', guestPage.component);
    fact('Tamu: nama tuan rumah hasil revisi', shown?.base?.sender_name === 'Andi & Sinta Pratama', shown?.base?.sender_name);
    fact('Tamu: lokasi acara terbaru', shown?.invitation?.events?.[0]?.venue_name === 'Masjid Agung Al-Azhar', shown?.invitation?.events?.[0]?.venue_name);
    fact('Tamu: amplop BCA tampil', JSON.stringify(shown?.invitation?.gifts ?? []).includes('1234567890'));
    fact('Tamu: ucapan yang disembunyikan tidak tampil', !JSON.stringify(shown?.invitation?.wishes ?? {}).includes('Selamat menempuh hidup baru'));

    // 4) Link dan template tidak berubah setelah semua perubahan.
    await host.goto(`/undangan/${paidId}`);
    const final = (await host.page()).props.invitation;
    fact('Link undangan tetap sama', final.url === shareUrl, `${shareUrl} → ${final.url}`);
    fact('Template tetap sama', final.template_name === templateName, `${templateName} → ${final.template_name}`);
    fact('Link pribadi tamu tetap sama', final.guests.find((g) => g.id === guest.id)?.url === guest.url);

    console.log('\n— D. Admin: undangan tanpa bayar —');
    await admin.goto('/katalog/smoke-undangan');
    await admin.click('Buat sekarang');
    await admin.waitForPath('/studio/');
    const invitationId = (await admin.path()).split('/')[2];
    await check(admin, 'Studio undangan', { component: 'Studio/Edit' });

    await expectOk(await admin.request('PUT', `/studio/${invitationId}`, { sender_name: 'Raka & Dinda', occasion: 'Pernikahan', content }), 'Simpan isi undangan');
    const eventDate = new Date(Date.now() + 30 * 86400000).toISOString().slice(0, 10);
    await expectOk(
        await admin.request('POST', `/undangan/${invitationId}/acara`, { name: 'Resepsi', starts_at: `${eventDate} 10:00`, timezone: 'Asia/Jakarta', venue_name: 'Gedung Serbaguna' }),
        'Tambah acara',
    );
    await admin.goto(`/undangan/${invitationId}`);
    await check(admin, 'Kelola undangan (draf)', { component: 'Invitation/Manage' });

    await admin.goto(`/studio/${invitationId}/gaya`);
    await check(admin, 'Studio undangan: gaya', { component: 'Studio/Style' });
    await admin.click('Lanjut: Pratinjau');
    await admin.waitForPath('/pratinjau/');
    await check(admin, 'Pratinjau undangan (admin)', { component: 'Preview/Show', text: 'Lanjut Terbitkan' });
    await admin.click('Lanjut Terbitkan');
    await admin.waitForPath('/checkout/');
    await check(admin, 'Checkout admin', { component: 'Checkout/Show', text: 'Terbitkan Gratis' });
    await admin.eval(() => document.querySelector('input[type="checkbox"]').click());
    await sleep(300);
    await admin.click('Terbitkan Gratis');
    await admin.waitForPath('/hasil/');
    const invitationResult = await check(admin, 'Hasil undangan terbit', { component: 'Result/Show' });

    await admin.goto(`/undangan/${invitationId}`);
    await check(admin, 'Kelola undangan (terbit)', { component: 'Invitation/Manage', text: 'Revisi isi undangan' });
    await admin.goto(`/studio/${invitationId}`);
    await check(admin, 'Revisi undangan', { component: 'Studio/Edit', text: 'Mode revisi' });
    await admin.goto(`/u/${publicSlug(invitationResult?.props)}`);
    await check(admin, 'Halaman tamu undangan', { component: 'Recipient/Gate' });
    await admin.click('Buka');
    await admin.waitUntil(async () => (await admin.page())?.component === 'Recipient/Play', 'undangan terbuka setelah klik Buka');
    await check(admin, 'Tamu: undangan dibuka', { component: 'Recipient/Play' });
}

const profile = mkdtempSync(join(tmpdir(), 'veritas-smoke-'));
const chrome = spawn(CHROME, ['--headless=new', `--remote-debugging-port=${DEBUG_PORT}`, `--user-data-dir=${profile}`, '--no-first-run', '--window-size=412,915'], { stdio: 'ignore' });
let exitCode = 0;

try {
    let version = null;

    for (let attempt = 0; attempt < 50 && !version; attempt++) {
        await sleep(200);
        version = await fetch(`http://127.0.0.1:${DEBUG_PORT}/json/version`).then((r) => r.json()).catch(() => null);
    }

    const ws = new WebSocket(version.webSocketDebuggerUrl);
    await new Promise((resolve) => (ws.onopen = resolve));
    const browser = new Cdp(ws);

    console.log(`Smoke test ke ${BASE}`);
    await run(browser);
} catch (error) {
    console.log(`\n💥 Berhenti: ${error.message}`);
    exitCode = 1;
} finally {
    const failed = results.filter((result) => !result.ok);
    console.log(`\nHasil: ${results.length - failed.length}/${results.length} halaman lolos${failed.length ? `, ${failed.length} gagal` : ''}.`);
    exitCode ||= failed.length ? 1 : 0;
    chrome.kill();
    await sleep(500);
    rmSync(profile, { recursive: true, force: true, maxRetries: 3 });
    process.exit(exitCode);
}
