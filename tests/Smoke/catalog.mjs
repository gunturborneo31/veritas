/**
 * Uji katalog di Chrome: untuk SETIAP template → demo → Studio → Gaya → Pratinjau → Checkout (draf),
 * lalu kartu terbit → halaman penerima → PIN/Buka → telusuri semua adegan sampai terakhir.
 * Pakai: node catalog.mjs <cards.json>
 */
import { spawn } from 'node:child_process';
import { mkdtempSync, readFileSync, rmSync } from 'node:fs';
import { tmpdir } from 'node:os';
import { join } from 'node:path';

const BASE = (process.env.SMOKE_URL ?? 'http://127.0.0.1:8765').replace(/\/$/, '');
// Out-File di PowerShell 5.1 menulis BOM di awal file.
const cards = JSON.parse(readFileSync(process.argv[2], 'utf8').replace(/^﻿/, '')).filter((card) => !process.env.ONLY || card.template === process.env.ONLY);
const started = Date.now();
const sleep = (ms) => new Promise((r) => setTimeout(r, ms));

let id = 0;
const pending = new Map();
const listeners = [];
let ws;
const send = (method, params = {}, sessionId) =>
    new Promise((resolve, reject) => {
        const i = ++id;
        pending.set(i, { resolve, reject });
        ws.send(JSON.stringify({ id: i, method, params, sessionId }));
    });

async function openTab() {
    const { targetId } = await send('Target.createTarget', { url: 'about:blank' });
    const { sessionId } = await send('Target.attachToTarget', { targetId, flatten: true });
    const tab = { sessionId, errors: [], status: null, loaded: false };
    listeners.push(({ method, params, sessionId: sid }) => {
        if (sid !== sessionId) return;
        if (method === 'Runtime.exceptionThrown') tab.errors.push(`JS: ${params.exceptionDetails.exception?.description?.split('\n')[0] ?? params.exceptionDetails.text}`);
        if (method === 'Runtime.consoleAPICalled' && params.type === 'error') tab.errors.push(`console.error: ${params.args.map((a) => a.value ?? a.description ?? '').join(' ').slice(0, 200)}`);
        if (method === 'Network.responseReceived' && params.type === 'Document') tab.status = params.response.status;
        if (method === 'Network.responseReceived' && params.response.status >= 500) tab.errors.push(`HTTP ${params.response.status} ${params.response.url}`);
        if (method === 'Page.loadEventFired') tab.loaded = true;
        if (process.env.DEBUG) {
            const at = ((Date.now() - started) / 1000).toFixed(1).padStart(6);
            if (method === 'Network.requestWillBeSent' && !/\.(js|css|png|svg|webp|woff2?)(\?|$)|fonts\.g|tile\.openstreetmap/.test(params.request.url)) console.log(`${at}s → ${params.request.method} ${params.request.url.replace(BASE, '')}`);
            if (method === 'Network.responseReceived' && ['Document', 'XHR', 'Fetch'].includes(params.type)) console.log(`${at}s ← ${params.response.status} ${params.response.url.replace(BASE, '')}`);
            if (method === 'Network.loadingFailed' && !params.canceled) console.log(`${at}s ✗ ${params.errorText} ${params.type}`);
        }
    });
    tab.send = (method, params) => send(method, params, sessionId);
    tab.eval = async (fn, ...args) => {
        const r = await tab.send('Runtime.evaluate', { expression: `(${fn})(...${JSON.stringify(args)})`, awaitPromise: true, returnByValue: true });
        if (r.exceptionDetails) throw new Error(r.exceptionDetails.exception?.description ?? r.exceptionDetails.text);
        return r.result.value;
    };
    tab.waitUntil = async (check, label, timeout = 15000) => {
        const start = Date.now();
        while (Date.now() - start < timeout) {
            if (await check().catch(() => false)) return;
            await sleep(200);
        }
        throw new Error(`Timeout: ${label}`);
    };
    tab.goto = async (path) => {
        tab.loaded = false;
        tab.status = null;
        await tab.send('Page.navigate', { url: BASE + path });
        await tab.waitUntil(async () => tab.loaded, `memuat ${path}`, 30000);
        await sleep(600);
    };
    tab.page = () => tab.eval(() => window.history.state?.page?.component ?? JSON.parse(document.querySelector('#app')?.dataset.page ?? 'null')?.component);
    tab.path = () => tab.eval(() => location.pathname);
    tab.click = (label) =>
        tab.eval((text) => {
            const el = [...document.querySelectorAll('button, a')].find((e) => e.innerText.trim().includes(text) && !e.disabled);
            if (!el) throw new Error(`Tombol "${text}" tidak ditemukan/aktif`);
            el.click();
        }, label);
    tab.text = () => tab.eval(() => document.body.innerText);
    await Promise.all(['Page.enable', 'Runtime.enable', 'Network.enable'].map((m) => tab.send(m)));
    // Server uji (php -S) hanya melayani satu request sekaligus; unduhan MP3 yang ditahan Chrome
    // akan mengantrekan request lain (mis. kirim PIN). Musik diuji terpisah dengan music-probe.mjs.
    await tab.send('Network.setBlockedURLs', { urls: ['*.mp3'] });
    return tab;
}

/** Periksa halaman saat ini; kembalikan daftar masalah (kosong = lolos). */
async function inspect(tab, component) {
    await sleep(250);
    const problems = tab.errors.splice(0);
    const page = await tab.page().catch(() => null);
    const textLength = await tab.eval(() => document.querySelector('#app')?.innerText.trim().length ?? 0).catch(() => 0);
    if (tab.status >= 400) problems.push(`HTTP ${tab.status}`);
    if (textLength < 10) problems.push('LAYAR KOSONG');
    if (component && page !== component) problems.push(`komponen ${page ?? '-'} (seharusnya ${component})`);
    return problems;
}

async function testCard(tab, card) {
    const steps = [];
    const step = async (label, run) => {
        try {
            const problems = (await run()) ?? [];
            steps.push({ label, problems });
        } catch (error) {
            steps.push({ label, problems: [error.message.split('\n')[0], ...tab.errors.splice(0)] });
            throw error;
        }
    };

    try {
        await step('demo', async () => (await tab.goto(`/katalog/${card.template}/demo`), inspect(tab)));
        await step('studio', async () => (await tab.goto(`/studio/${card.draft}`), inspect(tab, 'Studio/Edit')));
        await step('gaya', async () => (await tab.goto(`/studio/${card.draft}/gaya`), inspect(tab, 'Studio/Style')));
        await step('pratinjau', async () => {
            await tab.click('Lanjut: Pratinjau');
            await tab.waitUntil(async () => (await tab.path()).startsWith('/pratinjau/'), 'pratinjau');
            await sleep(800);
            return inspect(tab, 'Preview/Show');
        });
        await step('checkout', async () => {
            await tab.click('Lanjut Bayar');
            await tab.waitUntil(async () => (await tab.path()).startsWith('/checkout/'), 'checkout');
            await sleep(800);
            return inspect(tab, 'Checkout/Show');
        });
        await step('gerbang', async () => (await tab.goto(`/u/${card.slug}`), inspect(tab, 'Recipient/Gate')));
        await step(card.has_pin ? 'masukkan PIN' : 'buka', async () => {
            if (card.has_pin) {
                // Seperti pengunjung di HP: ketuk tombol angka di layar.
                for (const digit of card.pin) {
                    await tab.eval((d) => {
                        const button = [...document.querySelectorAll('button')].find((b) => b.innerText.trim() === d);
                        if (!button) throw new Error(`Tombol angka ${d} tidak ada`);
                        button.click();
                    }, digit);
                    await sleep(200);
                }
                if (process.env.DEBUG) {
                    await sleep(2000);
                    console.log('   layar setelah PIN:', (await tab.text()).replace(/\s+/g, ' ').slice(0, 300));
                }
            } else {
                await tab.click('Buka');
            }
            await tab.waitUntil(async () => (await tab.page()) === 'Recipient/Play', 'kartu terbuka', 20000);
            await sleep(1000);
            return inspect(tab, 'Recipient/Play');
        });

        // Telusuri setiap adegan sampai "Bagian N dari N".
        for (let guard = 0; guard < 40; guard++) {
            const match = (await tab.text()).match(/Bagian (\d+) dari (\d+)/);
            if (!match) throw new Error('Penanda "Bagian X dari N" tidak ditemukan');
            const [current, total] = [Number(match[1]), Number(match[2])];
            const sceneProblems = await inspect(tab, 'Recipient/Play');
            steps.push({ label: `adegan ${current}/${total}`, problems: sceneProblems });
            if (current >= total) break;
            await tab.click('Lanjut');
            await tab.waitUntil(async () => (await tab.text()).includes(`Bagian ${current + 1} dari`), `adegan ${current + 1}`);
            await sleep(700);
        }
    } catch {
        // Langkah yang gagal sudah tercatat di `steps`.
    }

    return steps;
}

const profile = mkdtempSync(join(tmpdir(), 'veritas-catalog-'));
const chrome = spawn(process.env.CHROME_PATH ?? 'C:/Program Files/Google/Chrome/Application/chrome.exe', ['--headless=new', '--remote-debugging-port=9336', `--user-data-dir=${profile}`, '--window-size=412,915', '--autoplay-policy=no-user-gesture-required', '--use-fake-ui-for-media-stream', '--use-fake-device-for-media-stream'], { stdio: 'ignore' });
let exitCode = 0;

try {
    let version;
    for (let i = 0; i < 150 && !version; i++) {
        await sleep(200);
        version = await fetch('http://127.0.0.1:9336/json/version').then((r) => r.json()).catch(() => null);
    }
    if (!version) throw new Error('Chrome headless tidak menyala dalam 30 detik.');
    ws = new WebSocket(version.webSocketDebuggerUrl);
    await new Promise((r) => (ws.onopen = r));
    ws.onmessage = (event) => {
        const m = JSON.parse(event.data);
        if (m.id && pending.has(m.id)) {
            const { resolve, reject } = pending.get(m.id);
            pending.delete(m.id);
            m.error ? reject(new Error(m.error.message)) : resolve(m.result);
        } else listeners.forEach((l) => l(m));
    };

    const tab = await openTab();
    await tab.goto('/login');
    await tab.eval(() => {
        const set = (sel, val) => {
            const el = document.querySelector(sel);
            Object.getOwnPropertyDescriptor(HTMLInputElement.prototype, 'value').set.call(el, val);
            el.dispatchEvent(new Event('input', { bubbles: true }));
        };
        set('#email', 'katalog@smoke.test');
        set('#password', 'password');
    });
    await tab.click('Masuk');
    await tab.waitUntil(async () => !(await tab.path()).startsWith('/login'), 'login');
    tab.errors.splice(0);

    let passed = 0;
    for (const card of cards) {
        if (card.error) {
            console.log(`❌ ${card.name.padEnd(36)} SETUP GAGAL: ${card.error}`);
            exitCode = 1;
            continue;
        }
        const steps = await testCard(tab, card);
        const failed = steps.filter((s) => s.problems.length);
        const scenes = steps.filter((s) => s.label.startsWith('adegan')).length;
        const reachedEnd = steps.some((s) => /^adegan (\d+)\/\1$/.test(s.label));
        const ok = failed.length === 0 && reachedEnd;
        passed += ok ? 1 : 0;
        console.log(`${ok ? '✅' : '❌'} ${card.name.padEnd(36)} ${card.type.padEnd(8)} ${card.has_pin ? 'PIN ' : '    '}${scenes} adegan${reachedEnd ? ' (sampai akhir)' : ''}`);
        failed.forEach((s) => console.log(`     ↳ ${s.label}: ${s.problems.join(' | ')}`));
        if (!reachedEnd && failed.length === 0) console.log('     ↳ tidak sampai adegan terakhir');
        if (!ok) exitCode = 1;
    }
    console.log(`\nHasil: ${passed}/${cards.length} template lolos dari hulu ke hilir.`);
} catch (error) {
    console.log(`💥 Berhenti: ${error.message}`);
    exitCode = 1;
} finally {
    chrome.kill();
    await sleep(500);
    try {
        rmSync(profile, { recursive: true, force: true, maxRetries: 3 });
    } catch {
        // Chrome kadang masih mengunci folder profil sementara; tidak memengaruhi hasil uji.
    }
    process.exit(exitCode);
}
