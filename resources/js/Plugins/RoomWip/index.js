/**
 * Plugin THỬ NGHIỆM: chế độ xem tồn BTP trên Gantt (chỉ khi slot 1 ngày).
 *
 *  - Dòng "Σ Tổng tồn chờ" ở đầu mỗi nhóm công đoạn ĐH / BP / ĐG: tồn chờ vào
 *    công đoạn đó lúc 06:00 từng ngày, kể cả phần lô con chưa xếp phòng.
 *  - Dòng con "↳ Tồn chờ vào" dưới mỗi phòng ĐH / BP / ĐG: phần tồn chờ vào phòng đó.
 *  - Bấm một số: làm sáng lô NGUỒN (cam) và lô sẽ TIÊU THỤ (xanh), làm mờ lịch
 *    khác, ẩn phòng không liên quan, kèm bảng chi tiết.
 *
 * Bật / tắt bằng nút trên thanh trên cùng; nút chỉ hiện với user có quyền
 * schedual_wip_rows. Backend: app/Plugins/RoomWip. Mốc nhập kho giống trang Tồn
 * kho lý thuyết (lúc công đoạn nguồn bắt đầu), mốc xuất kho là lúc lô con bắt đầu chạy.
 *
 * FullCalender.jsx chỉ dùng:
 *   const roomWip = useRoomWip(calendarRef, events)
 *   roomWip.resources(list)        chèn dòng tồn vào danh sách phòng
 *   isRoomWipRow(resource)         nhận diện dòng tồn
 *   roomWip.labelContent / laneContent   nội dung nhãn / lane của dòng tồn
 *   roomWip.classesFor(event)      class làm sáng / làm mờ sự kiện
 *   roomWip.highlight              đưa vào calendarDeps
 *   <RoomWipToggle roomWip={roomWip} />   nút bật / tắt
 * Gỡ: xoá thư mục này và các dòng có chú thích "RoomWip" trong FullCalender.jsx.
 */
import React, { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import axios from 'axios';

const PREFIX = 'roomwip-';
const TOTAL_PREFIX = 'roomwip-total-';
const STAGE_GROUP = { 5: 'DH', 6: 'BP', 7: 'DG' };   // phòng nhận hàng -> nhóm đích của sổ tồn
const ROW_H = 22;
const SRC_COLOR = '#eb6834';
const DST_COLOR = '#2a78d6';
const STORAGE_KEY = 'roomWip.on';

const DAY = 86400000;
const toMs = (s) => (s ? new Date(String(s).replace(' ', 'T')).getTime() : null);
const pad = (n) => String(n).padStart(2, '0');
const toLocal = (d) => `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())} ${pad(d.getHours())}:${pad(d.getMinutes())}:00`;
const fmtMoment = (ms) => { const d = new Date(ms); return `${pad(d.getHours())}:${pad(d.getMinutes())} ${pad(d.getDate())}/${pad(d.getMonth() + 1)}`; };
const fmt = (n) => Math.round(n || 0).toLocaleString('vi-VN');
const compact = (n) => {
  if (n >= 1e6) return `${(n / 1e6).toLocaleString('vi-VN', { maximumFractionDigits: 1 })}tr`;
  if (n >= 1e3) return `${Math.round(n / 1e3)}k`;
  return fmt(n);
};
const esc = (s) => String(s ?? '').replace(/[&<>"]/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]));

export const isRoomWipRow = (resource) => !!resource && String(resource.id).startsWith(PREFIX);

let styleInjected = false;
const injectStyle = () => {
  if (styleInjected) return;
  styleInjected = true;
  const el = document.createElement('style');
  el.textContent = `
    [data-resource-id^="${PREFIX}"],
    [data-resource-id^="${PREFIX}"] .fc-datagrid-cell,
    [data-resource-id^="${PREFIX}"] .fc-datagrid-cell-frame,
    [data-resource-id^="${PREFIX}"] .fc-datagrid-cell-cushion,
    [data-resource-id^="${PREFIX}"] .fc-timeline-lane,
    [data-resource-id^="${PREFIX}"] .fc-timeline-lane-frame {
      height: ${ROW_H}px !important; min-height: ${ROW_H}px !important; max-height: ${ROW_H}px !important;
      padding: 0 !important; line-height: ${ROW_H}px !important;
    }
    [data-resource-id^="${PREFIX}"] .fc-datagrid-cell { background: #f5f8fc !important; }
    [data-resource-id^="${TOTAL_PREFIX}"] .fc-datagrid-cell { background: #e8f0fb !important; }
    .roomwip-label { font-size: 11px; font-weight: 600; color: #475569; padding-left: 15px; white-space: nowrap; }
    .roomwip-label.total { font-size: 12px; font-weight: 700; color: #1e3a8a; padding-left: 4px; }
    .roomwip-lane { position: absolute; inset: 0; z-index: 3; background: #f5f8fc; }
    .roomwip-lane.total { background: #e8f0fb; }
    .roomwip-cell { position: absolute; top: 1px; bottom: 1px; display: flex; align-items: center; justify-content: center;
      font-size: 11px; font-weight: 600; color: #1f2937; cursor: pointer; border-radius: 3px;
      font-variant-numeric: tabular-nums; user-select: none; }
    .roomwip-lane.total .roomwip-cell { font-weight: 700; color: #1e3a8a; }
    .roomwip-cell:hover { background: #dbeafe; }
    .roomwip-cell.active, .roomwip-lane.total .roomwip-cell.active { background: ${DST_COLOR}; color: #fff; }
    .fc-event.roomwip-src { outline: 3px solid ${SRC_COLOR} !important; outline-offset: 1px; z-index: 6 !important; }
    .fc-event.roomwip-dst { outline: 3px solid ${DST_COLOR} !important; outline-offset: 1px; z-index: 6 !important; }
    .fc-event.roomwip-dim { opacity: 0.18 !important; }
    .roomwip-panel { position: fixed; z-index: 99999; width: 620px; max-height: 60vh; display: flex; flex-direction: column;
      background: #fff; border: 1px solid #d1d5db; border-radius: 8px; box-shadow: 0 10px 30px rgba(0,0,0,.2);
      font-size: 12px; color: #111; }
    .roomwip-panel header { padding: 8px 10px; border-bottom: 1px solid #e5e7eb; display: flex; gap: 8px; align-items: flex-start; cursor: move; }
    .roomwip-panel header .close { margin-left: auto; cursor: pointer; font-size: 16px; line-height: 1; color: #6b7280; }
    .roomwip-panel .legend { display: flex; gap: 12px; color: #4b5563; margin-top: 3px; font-weight: 400; }
    .roomwip-panel .sw { display: inline-block; width: 10px; height: 10px; border-radius: 2px; margin-right: 4px; vertical-align: -1px; }
    .roomwip-panel .body { overflow: auto; }
    .roomwip-panel table { width: 100%; border-collapse: collapse; }
    .roomwip-panel th, .roomwip-panel td { padding: 3px 6px; border-bottom: 1px solid #f1f5f9; text-align: left; white-space: nowrap; }
    .roomwip-panel th { position: sticky; top: 0; background: #f8fafc; font-weight: 600; }
    .roomwip-panel td.num { text-align: right; font-variant-numeric: tabular-nums; }
  `;
  document.head.appendChild(el);
};

/** Slot của khung đang xem có đúng 1 ngày không */
const isDaySlotView = (api) => {
  const d = api?.currentData?.options?.slotDuration;
  return !!d && d.days === 1 && !d.months && !d.years && !d.milliseconds;
};

const inStock = (p, at) => p.entryMs !== null && p.entryMs <= at && (p.exitMs === null || p.exitMs > at);

export function useRoomWip(calendarRef, events) {
  const [allowed, setAllowed] = useState(false);
  const [on, setOn] = useState(() => {
    try { return localStorage.getItem(STORAGE_KEY) === '1'; } catch { return false; }
  });
  const [daySlot, setDaySlot] = useState(false);
  const [range, setRange] = useState(null);         // { fromMs, toMs }
  const [parts, setParts] = useState([]);
  const [dayStart, setDayStart] = useState(6);
  const [unit, setUnit] = useState('ĐVL');
  const [highlight, setHighlight] = useState(null);
  const lanes = useRef(new Map());                    // id dòng -> div lane
  const rows = useRef(new Map());                     // id dòng -> { roomId } | { codes: Set }
  const panelRef = useRef(null);
  const active = allowed && on && daySlot;

  useEffect(() => { injectStyle(); }, []);

  // Quyền thấy nút: hỏi server một lần
  useEffect(() => {
    axios.get('/Schedual/room-wip/access')
      .then((res) => setAllowed(!!res.data?.allowed))
      .catch(() => setAllowed(false));
  }, []);

  // Theo dõi khung xem: slot 1 ngày mới hiện dòng tồn
  useEffect(() => {
    const api = calendarRef.current?.getApi();
    if (!api) return undefined;
    const sync = () => {
      setDaySlot(isDaySlotView(api));
      setRange({ fromMs: api.view.activeStart.getTime(), toMs: api.view.activeEnd.getTime() });
    };
    sync();
    api.on('datesSet', sync);
    return () => api.off('datesSet', sync);
  }, [calendarRef]);

  // Tải dữ liệu khi bật, đổi khung hoặc lịch được lưu lại (debounce)
  const timer = useRef(null);
  const abort = useRef(null);
  useEffect(() => {
    if (!active || !range) return undefined;
    clearTimeout(timer.current);
    timer.current = setTimeout(() => {
      abort.current?.abort();
      const ctrl = new AbortController();
      abort.current = ctrl;
      axios.get('/Schedual/room-wip/data', {
        params: { from: toLocal(new Date(range.fromMs)), to: toLocal(new Date(range.toMs)) },
        signal: ctrl.signal,
      })
        .then((res) => {
          setDayStart(res.data.day_start ?? 6);
          setUnit(res.data.unit || 'ĐVL');
          setParts((res.data.parts || []).map((p) => ({ ...p, entryMs: toMs(p.entry), exitMs: toMs(p.exit) })));
        })
        .catch((err) => {
          if (err?.name === 'CanceledError') return;
          console.error('RoomWip:', err?.response?.data || err?.message);
        });
    }, 500);
    return () => clearTimeout(timer.current);
  }, [active, range, events]);

  // Mốc 06:00 của từng ngày trong khung
  const days = useMemo(() => {
    if (!range) return [];
    const list = [];
    for (let d = new Date(range.fromMs); d.getTime() < range.toMs; d.setDate(d.getDate() + 1)) {
      const dayMs = new Date(d.getFullYear(), d.getMonth(), d.getDate()).getTime();
      list.push({ dayMs, at: dayMs + dayStart * 3600000 });
    }
    return list;
  }, [range, dayStart]);

  const index = useMemo(() => {
    const byRoom = new Map();
    const byGroup = new Map();
    parts.forEach((p) => {
      const room = String(p.room);
      if (!byRoom.has(room)) byRoom.set(room, []);
      byRoom.get(room).push(p);
      if (!byGroup.has(p.group)) byGroup.set(p.group, []);
      byGroup.get(p.group).push(p);
    });
    return { byRoom, byGroup };
  }, [parts]);

  /** Các phần lô thuộc một dòng tồn */
  const partsOf = useCallback((rowId) => {
    const row = rows.current.get(rowId);
    if (!row) return [];
    if (row.roomId) return index.byRoom.get(row.roomId) || [];
    return [...row.codes].flatMap((code) => index.byGroup.get(code) || []);
  }, [index]);

  const select = useCallback((rowId, day, title) => {
    setHighlight((prev) => {
      if (prev && prev.rowId === rowId && prev.dayMs === day.dayMs) return null;   // bấm lại thì tắt
      const lots = partsOf(rowId).filter((p) => inStock(p, day.at)).sort((a, b) => b.qty - a.qty);
      if (!lots.length) return null;
      const src = new Set(lots.map((r) => `${r.src}-main`));
      const dst = new Set(lots.map((r) => `${r.dst}-main`));

      // Phòng còn giữ lại trên lịch: phòng được bấm + phòng có lô được làm sáng
      const api = calendarRef.current?.getApi();
      const rooms = new Set();
      const roomId = rows.current.get(rowId)?.roomId;
      if (roomId) rooms.add(roomId);
      [...src, ...dst].forEach((id) => {
        const resId = api?.getEventById(id)?.getResources?.()[0]?.id;
        if (resId) rooms.add(String(resId));
      });

      return { rowId, title, isTotal: !roomId, dayMs: day.dayMs, at: day.at, lots, src, dst, rooms };
    });
  }, [partsOf, calendarRef]);

  // Vẽ các ô số vào lane của một dòng; gọi lại cả khi FullCalendar vừa tạo lane mới
  const selectRef = useRef(select);
  selectRef.current = select;
  const drawRef = useRef(() => {});
  drawRef.current = (rowId, node) => {
    node.replaceChildren();
    if (!range) return;
    const span = range.toMs - range.fromMs;
    const rowParts = partsOf(rowId);
    days.forEach((day) => {
      const total = rowParts.reduce((s, p) => (inStock(p, day.at) ? s + p.qty : s), 0);
      if (total <= 0) return;
      const cell = document.createElement('div');
      cell.className = 'roomwip-cell';
      if (highlight && highlight.rowId === rowId && highlight.dayMs === day.dayMs) cell.classList.add('active');
      cell.style.left = `${((day.dayMs - range.fromMs) / span) * 100}%`;
      cell.style.width = `${(DAY / span) * 100}%`;
      cell.textContent = compact(total);
      cell.title = `${node.dataset.title} lúc ${fmtMoment(day.at)}: ${fmt(total)} ${unit}\nBấm để làm sáng lô nguồn và lô sẽ tiêu thụ`;
      cell.addEventListener('mousedown', (e) => e.stopPropagation());
      cell.addEventListener('click', (e) => { e.stopPropagation(); selectRef.current(rowId, day, node.dataset.title); });
      node.appendChild(cell);
    });
  };
  useEffect(() => {
    lanes.current.forEach((node, rowId) => drawRef.current(rowId, node));
  }, [days, index, highlight, range, unit]);

  // Bảng chi tiết của ô đang chọn
  useEffect(() => {
    panelRef.current?.remove();
    panelRef.current = null;
    if (!highlight) return undefined;

    const total = highlight.lots.reduce((s, r) => s + r.qty, 0);
    const panel = document.createElement('div');
    panel.className = 'roomwip-panel';
    panel.style.right = '24px';
    panel.style.top = '120px';
    panel.innerHTML = `
      <header>
        <div>
          <div style="font-weight:700">${esc(highlight.title)} lúc ${fmtMoment(highlight.at)}</div>
          <div>${highlight.lots.length} lô · <b>${fmt(total)}</b> ${esc(unit)}</div>
          <div class="legend"><span><span class="sw" style="background:${SRC_COLOR}"></span>Lô nguồn</span>
            <span><span class="sw" style="background:${DST_COLOR}"></span>Lô sẽ tiêu thụ</span></div>
          <div class="legend">Chỉ hiện ${highlight.rooms.size} phòng có lô liên quan · đóng bảng để hiện lại tất cả</div>
        </div>
        <span class="close" title="Đóng (Esc)">✕</span>
      </header>
      <div class="body"><table>
        <thead><tr><th>Số lô</th><th>Sản phẩm</th><th>Phòng nguồn</th><th>Phòng tiêu thụ</th><th class="num">Lượng</th><th>Dự kiến tiêu thụ</th></tr></thead>
        <tbody>${highlight.lots.map((r) => `<tr>
          <td>${esc(r.batch)}</td><td>${esc(r.product)}</td><td>${esc(r.src_room || '')}</td>
          <td>${r.dst_room ? esc(r.dst_room) : '<i>chưa xếp phòng</i>'}</td>
          <td class="num">${fmt(r.qty)}</td><td>${r.exitMs ? fmtMoment(r.exitMs) : '<i>chưa xếp lịch</i>'}</td></tr>`).join('')}</tbody>
      </table></div>`;
    document.body.appendChild(panel);
    panelRef.current = panel;

    const close = () => setHighlight(null);
    panel.querySelector('.close').addEventListener('click', close);
    const onKey = (e) => { if (e.key === 'Escape') close(); };
    window.addEventListener('keydown', onKey);

    // Kéo tiêu đề để dời bảng khỏi chỗ đang cần xem
    panel.querySelector('header').addEventListener('mousedown', (e) => {
      if (e.target.classList.contains('close')) return;
      const startX = e.clientX; const startY = e.clientY;
      const rect = panel.getBoundingClientRect();
      const move = (ev) => {
        panel.style.right = 'auto';
        panel.style.left = `${rect.left + ev.clientX - startX}px`;
        panel.style.top = `${rect.top + ev.clientY - startY}px`;
      };
      const up = () => { window.removeEventListener('mousemove', move); window.removeEventListener('mouseup', up); };
      window.addEventListener('mousemove', move);
      window.addEventListener('mouseup', up);
    });

    return () => { window.removeEventListener('keydown', onKey); panel.remove(); };
  }, [highlight, unit]);

  // Tắt chế độ, đổi khung hoặc dữ liệu thì bỏ chọn cũ
  useEffect(() => { setHighlight(null); }, [active, range, parts]);

  const resources = useCallback((list) => {
    if (!active || !list?.length) return list;
    const rooms = list.filter((r) => !r.is_personnel_sub && !isRoomWipRow(r));

    // Dòng tổng ở đầu mỗi nhóm công đoạn có phòng nhận hàng
    const groups = new Map();
    rooms.forEach((r) => {
      const code = STAGE_GROUP[Number(r.stage_code)];
      if (!code) return;
      if (!groups.has(r.stage_name)) groups.set(r.stage_name, new Set());
      groups.get(r.stage_name).add(code);
    });

    // Đang xem một ô tồn: chỉ giữ phòng được bấm và phòng có lô nguồn / tiêu thụ
    let kept = list;
    if (highlight) {
      kept = list.filter((r) => highlight.rooms.has(String(r.is_personnel_sub ? r.parentId : r.id)));
    }
    const keptGroups = new Set(kept.map((r) => r.stage_name));

    const totals = [...groups.entries()]
      .map(([stageName, codes]) => ({
        id: `${TOTAL_PREFIX}${encodeURIComponent(stageName)}`,
        title: `Σ Tổng tồn chờ ${stageName}`,
        stage_name: stageName,
        order_by: -999999,
        wip_codes: [...codes],
      }))
      .filter((t) => !highlight || keptGroups.has(t.stage_name) || t.id === highlight.rowId);

    const subs = kept
      .filter((r) => STAGE_GROUP[Number(r.stage_code)] && !r.is_personnel_sub && !isRoomWipRow(r))
      .map((r) => ({
        id: `${PREFIX}${r.id}`,
        parentId: String(r.id),
        title: 'Tồn chờ vào',
        stage_name: r.stage_name,
        stage_code: r.stage_code,
        order_by: r.order_by,
        room_title: r.title,
      }));

    // Bảng tra dòng -> phần lô, dùng khi vẽ và khi bấm
    rows.current = new Map([
      ...totals.map((t) => [t.id, { codes: new Set(t.wip_codes) }]),
      ...subs.map((s) => [s.id, { roomId: s.parentId }]),
    ]);

    return [...kept, ...totals, ...subs];
  }, [active, highlight]);

  const labelContent = useCallback((arg) => (
    String(arg.resource.id).startsWith(TOTAL_PREFIX)
      ? { html: `<div class="roomwip-label total">${esc(arg.resource.title)}</div>` }
      : { html: '<div class="roomwip-label">↳ Tồn chờ vào</div>' }
  ), []);

  const laneContent = useCallback((arg) => {
    if (!isRoomWipRow(arg.resource)) return undefined;
    const rowId = String(arg.resource.id);
    const isTotal = rowId.startsWith(TOTAL_PREFIX);
    let node = lanes.current.get(rowId);
    if (!node) {
      node = document.createElement('div');
      node.className = isTotal ? 'roomwip-lane total' : 'roomwip-lane';
      lanes.current.set(rowId, node);
    }
    node.dataset.title = isTotal
      ? arg.resource.title.replace('Σ ', '')
      : `Tồn chờ vào ${arg.resource.extendedProps?.room_title || ''}`;
    queueMicrotask(() => drawRef.current(rowId, node));
    return { domNodes: [node] };
  }, []);

  const classesFor = useCallback((event) => {
    if (!highlight || event.display === 'background') return [];
    if (highlight.src.has(event.id)) return ['roomwip-src'];
    if (highlight.dst.has(event.id)) return ['roomwip-dst'];
    return ['roomwip-dim'];
  }, [highlight]);

  const toggle = useCallback(() => {
    setOn((v) => {
      try { localStorage.setItem(STORAGE_KEY, v ? '0' : '1'); } catch { /* bỏ qua */ }
      return !v;
    });
  }, []);

  return { allowed, on, daySlot, toggle, resources, labelContent, laneContent, classesFor, highlight };
}

/** Nút bật / tắt chế độ xem tồn BTP; không có quyền thì không hiện */
export function RoomWipToggle({ roomWip }) {
  if (!roomWip.allowed) return null;
  const { on, daySlot } = roomWip;
  const title = !on
    ? 'Bật chế độ xem tồn BTP trên Gantt'
    : (daySlot ? 'Tắt chế độ xem tồn BTP' : 'Đang bật — chỉ hiện khi xem slot 1 ngày');
  return React.createElement(
    'div',
    {
      className: `flex align-items-center gap-2 px-3 py-1 border-round-2xl shadow-1 border-1 cursor-pointer transition-colors ${on
        ? 'bg-blue-100 text-blue-800 border-blue-300 hover:bg-blue-200'
        : 'bg-gray-100 text-gray-700 border-gray-300 hover:bg-gray-200'}`,
      onClick: roomWip.toggle,
      title,
    },
    React.createElement('i', { className: `pi ${on ? 'pi-eye' : 'pi-eye-slash'}` }),
    React.createElement('span', { className: 'font-bold text-sm' }, 'Tồn BTP'),
  );
}
