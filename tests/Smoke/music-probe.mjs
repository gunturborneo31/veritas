// Uji musik latar halaman penerima.
// Pakai: node music-probe.mjs <url> [strict]
//   tanpa "strict": Chrome mengizinkan autoplay → musik harus langsung berbunyi.
//   dengan "strict": autoplay diblokir seperti di HP → musik baru mulai setelah sentuhan pertama.
import { spawn } from 'node:child_process';
import { mkdtempSync } from 'node:fs';
import { tmpdir } from 'node:os';
import { join } from 'node:path';

const [url, mode] = process.argv.slice(2);
const strict = mode === 'strict';
const sleep = (ms) => new Promise((r) => setTimeout(r, ms));
const args = ['--headless=new', '--remote-debugging-port=9335', `--user-data-dir=${mkdtempSync(join(tmpdir(), 'music-'))}`, '--window-size=412,915'];
args.push(strict ? '--autoplay-policy=document-user-activation-required' : '--autoplay-policy=no-user-gesture-required');
const chrome = spawn('C:/Program Files/Google/Chrome/Application/chrome.exe', args, { stdio: 'ignore' });

let version;
for (let i = 0; i < 50 && !version; i++) {
    await sleep(200);
    version = await fetch('http://127.0.0.1:9335/json/version').then((r) => r.json()).catch(() => null);
}
const ws = new WebSocket(version.webSocketDebuggerUrl);
await new Promise((r) => (ws.onopen = r));
let id = 0;
const pending = new Map();
const errors = [];
ws.onmessage = (e) => {
    const m = JSON.parse(e.data);
    if (m.id && pending.has(m.id)) return pending.get(m.id)(m.result), pending.delete(m.id);
    if (m.method === 'Runtime.exceptionThrown') errors.push(m.params.exceptionDetails.exception?.description?.split('\n')[0]);
};
const send = (method, params = {}, sessionId) => new Promise((r) => { const i = ++id; pending.set(i, r); ws.send(JSON.stringify({ id: i, method, params, sessionId })); });
const { targetId } = await send('Target.createTarget', { url: 'about:blank' });
const { sessionId } = await send('Target.attachToTarget', { targetId, flatten: true });
const s = (method, params) => send(method, params, sessionId);
await Promise.all(['Page.enable', 'Runtime.enable'].map((m) => s(m)));
// Elemen audio dibuat lewat `new Audio()` (tidak ada di DOM), jadi catat setiap elemen yang diputar.
await s('Page.addScriptToEvaluateOnNewDocument', { source: `window.__audios = new Set(); const p = HTMLMediaElement.prototype.play; HTMLMediaElement.prototype.play = function () { window.__audios.add(this); return p.call(this); };` });
const evaluate = async (expression) => (await s('Runtime.evaluate', { expression, awaitPromise: true, returnByValue: true })).result?.value;
const state = () => evaluate(`JSON.stringify({ component: window.history.state?.page?.component, audio: [...window.__audios].map(a => ({ file: a.currentSrc.split('/').pop(), playing: !a.paused, time: +a.currentTime.toFixed(1), volume: +a.volume.toFixed(2) })) })`);
const tap = async (x, y) => {
    for (const type of ['mousePressed', 'mouseReleased']) await s('Input.dispatchMouseEvent', { type, x, y, button: 'left', clickCount: 1 });
};

console.log(`Mode: ${strict ? 'autoplay DIBLOKIR (seperti HP)' : 'autoplay diizinkan'}`);
await s('Page.navigate', { url });
await sleep(3500);
console.log('1. Halaman gerbang dibuka         :', await state());

if (strict) {
    await tap(30, 860);
    await sleep(2500);
    console.log('2. Setelah sentuhan pertama       :', await state());
}

const box = await evaluate(`(() => { const b = [...document.querySelectorAll('button')].find(e => e.innerText.includes('Buka')); if (!b) return null; const r = b.getBoundingClientRect(); return { x: r.x + r.width / 2, y: r.y + r.height / 2 }; })()`);
await tap(box.x, box.y);
await sleep(3000);
console.log(`${strict ? '3' : '2'}. Setelah klik "Buka Ucapan"     :`, await state());
console.log('Error JS:', errors.length ? errors : 'tidak ada');
chrome.kill();
process.exit(0);
