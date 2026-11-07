// Chụp màn hình thật cho Hướng Dẫn Sử Dụng qua Chrome DevTools Protocol (Node >= 22, không cần thư viện).
// Chuyển từ bộ công cụ WMS, chỉnh cho PMS.
//
//   node shoot.mjs <plan.json> <thư mục ảnh> [baseUrl=http://127.0.0.1:8099]
//
// Mỗi dòng của plan (xem plan.php): name, url, user, production, width/height, wait, tab (id vùng cần mở),
// js (chạy sau khi mở tab), afterJs, highlight (selector viền đỏ), clip (selector vùng cắt, gộp mọi phần tử
// khớp), pad, maxHeight. Phiên đăng nhập tạo bằng mint.php; xoá phiên bằng cleanup.php sau khi chụp.
//
// AN TOÀN DỮ LIỆU (CSDL local là dữ liệu thật): mọi request không phải GET tới ứng dụng đều bị CHẶN, trừ các
// endpoint chỉ đọc trong READ_ONLY_POST. Request bị chặn được in ra ("blocked") để biết màn hình nào thiếu dữ liệu;
// chỉ thêm vào READ_ONLY_POST sau khi đã đọc controller và chắc chắn endpoint không ghi gì.
import { spawn, execFileSync } from 'node:child_process';
import { readFileSync, writeFileSync, mkdirSync, rmSync, existsSync } from 'node:fs';
import { tmpdir } from 'node:os';
import { join, dirname } from 'node:path';
import { fileURLToPath } from 'node:url';

const READ_ONLY_POST = [
    /^\/Schedual\/view$/,              // SchedualController::view - đọc lịch, tài nguyên, danh sách KHSX
    /^\/MaintenanceSchedual\/view$/,   // MaintenanceSchedualController::view - đọc lịch HC-BT, danh sách chờ
    /^\/Schedual\/wip_coverage\/(view|room_view|detail)$/, // WipCoverageController - tính tồn lý thuyết (WipCoverageService chỉ đọc)
];

const here = dirname(fileURLToPath(import.meta.url));
const [planFile, outDir, baseUrl = 'http://127.0.0.1:8099'] = process.argv.slice(2);
const plan = JSON.parse(readFileSync(planFile, 'utf8'));
mkdirSync(outDir, { recursive: true });

const BROWSER = ['C:/Program Files/Google/Chrome/Application/chrome.exe',
    'C:/Program Files (x86)/Google/Chrome/Application/chrome.exe',
    'C:/Program Files (x86)/Microsoft/Edge/Application/msedge.exe',
    'C:/Program Files/Microsoft/Edge/Application/msedge.exe'].find(p => existsSync(p));
const profile = join(tmpdir(), 'pms-ug-browser');
const browser = spawn(BROWSER, ['--headless=new', '--remote-debugging-port=9334', '--user-data-dir=' + profile,
    '--hide-scrollbars', '--force-device-scale-factor=1', 'about:blank'], { stdio: 'ignore' });

const sleep = ms => new Promise(r => setTimeout(r, ms));
let target;
for (let i = 0; i < 60 && !target; i++) {
    await sleep(300);
    try { target = (await (await fetch('http://127.0.0.1:9334/json')).json()).find(t => t.type === 'page'); } catch {}
}
const ws = new WebSocket(target.webSocketDebuggerUrl);
await new Promise(r => ws.onopen = r);
let seq = 0;
const pending = new Map();
const waiters = [];
const listeners = new Map();
ws.onmessage = e => {
    const m = JSON.parse(e.data);
    if (m.id && pending.has(m.id)) { pending.get(m.id)(m); pending.delete(m.id); }
    if (m.method) {
        waiters.filter(w => w.method === m.method).forEach(w => { w.fn(m); waiters.splice(waiters.indexOf(w), 1); });
        (listeners.get(m.method) || []).forEach(fn => fn(m.params));
    }
};
const send = (method, params = {}) => new Promise(r => { const i = ++seq; pending.set(i, r); ws.send(JSON.stringify({ id: i, method, params })); });
const once = method => new Promise(fn => waiters.push({ method, fn }));
const on = (method, fn) => listeners.set(method, [...(listeners.get(method) || []), fn]);
const evaluate = async expr => (await Promise.race([
    send('Runtime.evaluate', { expression: expr, returnByValue: true, awaitPromise: true }),
    new Promise(r => setTimeout(() => r({}), 20000)),   // JS lỗi / Promise treo thì bỏ qua sau 20s
])).result?.result?.value;

// Chặn request ghi
const appOrigin = new URL(baseUrl).origin;
let blocked = [];
on('Fetch.requestPaused', p => {
    const url = new URL(p.request.url);
    const write = p.request.method !== 'GET' && p.request.method !== 'HEAD' && p.request.method !== 'OPTIONS';
    if (url.origin === appOrigin && write && !READ_ONLY_POST.some(re => re.test(url.pathname))) {
        blocked.push(p.request.method + ' ' + url.pathname);
        send('Fetch.failRequest', { requestId: p.requestId, errorReason: 'BlockedByClient' });
    } else {
        send('Fetch.continueRequest', { requestId: p.requestId });
    }
});
await send('Fetch.enable', { patterns: [{ urlPattern: appOrigin + '/*' }] });

// Hàm phụ dùng trong 'js' của plan
const PRELUDE = `window.__ug = {
  visible: e => !!e && e.getClientRects().length > 0,
  tab(ids) {
    for (const p of ids) {
      const a = document.querySelector('[href="#' + p + '"],[data-target="#' + p + '"],[aria-controls="' + p + '"],[data-tab="' + p + '"],[data-pane="' + p + '"]');
      if (a) { a.click(); return true; }
    }
    return false;
  },
  click(sel) {
    const all = [...document.querySelectorAll(sel)];
    const el = all.find(e => this.visible(e)) || all[0];
    if (el) el.click();
    return !!el;
  },
  // Chỉ bấm nút "Thêm..." MỞ HỘP THOẠI (data-toggle="modal" / data-target): nút ghi thẳng không bị bấm nhầm
  clickCreate() {
    const panes = [...document.querySelectorAll('.tab-pane.active, .tab-pane.show')].filter(this.visible);
    const scope = panes.length ? panes[panes.length - 1] : document;
    const btn = [...scope.querySelectorAll('button, a.btn')].filter(this.visible)
      .filter(b => b.matches('[data-toggle="modal"],[data-bs-toggle="modal"],[data-target^="#"],[data-bs-target^="#"]'))
      .find(b => /^\\s*\\+?\\s*Thêm/i.test(b.textContent) || (b.querySelector('.fa-plus') && b.textContent.trim().length < 40));
    if (btn) btn.click();
    return !!btn;
  },
};
const s = document.createElement('style');
s.textContent = '*{animation:none!important;transition:none!important} .modal.fade{opacity:1} .ug-hl{outline:3px solid #e53935!important;outline-offset:3px;border-radius:6px}';
document.head.appendChild(s);`;

await send('Page.enable');
await send('Network.enable');
const result = { ok: [], missing: [] };

for (const shot of plan) {
    blocked = [];
    await send('Network.clearBrowserCookies');
    const minted = JSON.parse(execFileSync('php', [join(here, 'mint.php'), shot.user || 'Admin', shot.production || '']).toString());
    await send('Network.setCookie', { name: minted.name, value: minted.value, domain: new URL(baseUrl).hostname, path: '/' });
    await send('Emulation.setDeviceMetricsOverride', { width: shot.width || 1600, height: shot.height || 1800, deviceScaleFactor: 1, mobile: false });
    const loaded = once('Page.loadEventFired');
    await send('Page.navigate', { url: baseUrl + shot.url });
    await Promise.race([loaded, sleep(30000)]);
    await sleep(shot.wait || 2500);
    await evaluate(PRELUDE);
    const settle = async () => {
        for (let i = 0; i < 40 && await evaluate(`/Đang tải|Loading\\.\\.\\.|Processing/.test(document.body.innerText)`); i++) await sleep(500);
        await sleep(300);
    };
    if (shot.tab?.length) { await evaluate(`__ug.tab(${JSON.stringify(shot.tab)})`); await sleep(1500); }
    await settle();
    if (shot.js) { await evaluate(shot.js); await sleep(shot.afterJs || 1200); await settle(); }
    for (const sel of shot.highlight || []) await evaluate(`document.querySelectorAll(${JSON.stringify(sel)}).forEach(e => e.classList.add('ug-hl'))`);

    await evaluate(`(() => { const e = [...document.querySelectorAll(${JSON.stringify(shot.clip || 'body')})].find(x => x.getClientRects().length);
        if (e && e.getBoundingClientRect().bottom > innerHeight) e.scrollIntoView({ block: 'start' }); })()`);
    await sleep(400);
    const rect = await evaluate(`(() => {
        const els = [...document.querySelectorAll(${JSON.stringify(shot.clip || 'body')})].filter(e => e.getClientRects().length);
        if (!els.length) return null;
        const r = els.map(e => e.getBoundingClientRect());
        return { x: Math.min(...r.map(a => a.left)) + scrollX, y: Math.min(...r.map(a => a.top)) + scrollY,
                 r: Math.max(...r.map(a => a.right)) + scrollX, b: Math.max(...r.map(a => a.bottom)) + scrollY };
    })()`);
    if (blocked.length) console.log('blocked', shot.name, [...new Set(blocked)].join(', '));
    if (!rect || rect.r - rect.x < 20 || rect.b - rect.y < 20) { result.missing.push(shot.name); console.log('MISSING', shot.name, shot.clip); continue; }

    const pad = shot.pad ?? 12;
    const clip = { x: Math.max(0, rect.x - pad), y: Math.max(0, rect.y - pad), width: rect.r - rect.x + 2 * pad,
        height: Math.min(shot.maxHeight || 1400, rect.b - rect.y + 2 * pad), scale: 1 };
    const png = await send('Page.captureScreenshot', { format: 'png', clip, captureBeyondViewport: false });
    writeFileSync(join(outDir, shot.name + '.png'), Buffer.from(png.result.data, 'base64'));
    result.ok.push(shot.name);
    console.log('ok', shot.name, Math.round(clip.width) + 'x' + Math.round(clip.height));
}

ws.close();
browser.kill();
await sleep(500);
try { rmSync(profile, { recursive: true, force: true }); } catch {}
console.log(`Xong: ${result.ok.length} ảnh, thiếu ${result.missing.length}${result.missing.length ? ' (' + result.missing.join(', ') + ')' : ''}`);
