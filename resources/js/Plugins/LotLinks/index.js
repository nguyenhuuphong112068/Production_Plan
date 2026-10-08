/**
 * Plugin THỬ NGHIỆM: đường nối nguồn -> tiêu thụ của chuỗi lô trên Gantt.
 *
 * Khi đang tập trung lô (Ctrl + double-click), vẽ đường gấp khúc từ cuối lịch
 * công đoạn nguồn tới đầu lịch công đoạn tiêu thụ, suốt chuỗi Cân -> Đóng gói.
 * Màu theo thời gian BTP nằm chờ, nhãn ghi số giờ / ngày chờ.
 *
 * Chuỗi lần theo code <-> predecessor_code nên kéo theo cả lô đóng gói một phần
 * (khác plan_master_id, chung code công đoạn trước).
 *
 * FullCalender.jsx chỉ dùng:
 *   const lotLinks = useLotLinks(calendarRef, activePlanMasterIds, events)
 *   lotLinks.focusPmIds   Set plan_master_id đang tập trung, đã mở rộng theo chuỗi
 *   lotLinks.filterResources(list)   chỉ giữ phòng có lịch của chuỗi khi đang tập trung
 * Gỡ: xoá thư mục này và các dòng có chú thích "LotLinks" trong FullCalender.jsx.
 */
import { useCallback, useEffect, useMemo, useRef } from 'react';

const FIRST_STAGE = 1;   // Cân nguyên liệu
const WEIGH_EXCIPIENT = 2;   // Cân tá dược BP / nang rỗng
const LAST_STAGE = 7;    // Đóng gói
const SVG_ID = 'lot-links-overlay';

// Màu theo thời gian chờ; chữ đi màu đậm của cùng tông để đọc được trên nền lịch
const LEVELS = [
  { key: 'over', color: '#e34948', label: 'chồng giờ' },   // tiêu thụ bắt đầu trước khi nguồn xong
  { key: 'ok', color: '#008300' },                          // < 24h
  { key: 'warn', color: '#c98500' },                        // 24h - 72h
  { key: 'long', color: '#e34948' },                        // > 72h
];

const levelOf = (hours) => {
  if (hours < 0) return LEVELS[0];
  if (hours < 24) return LEVELS[1];
  if (hours < 72) return LEVELS[2];
  return LEVELS[3];
};

const waitText = (hours) => {
  if (hours < 0) return `chồng ${Math.round(-hours)}h`;
  if (hours < 48) return `chờ ${Math.round(hours)}h`;
  return `chờ ${(hours / 24).toLocaleString('vi-VN', { maximumFractionDigits: 1 })} ngày`;
};

const isChainEvent = (e) => {
  const stage = Number(e.stage_code);
  return !e.is_clearning && !e.is_running && stage >= FIRST_STAGE && stage <= LAST_STAGE && e.code;
};

/** Mở rộng tập plan_master_id theo chuỗi code <-> predecessor_code, cả hai chiều */
const expandChain = (events, seedPmIds) => {
  const nodes = events.filter(isChainEvent);
  const pms = new Set(seedPmIds.map(String));
  for (let round = 0; round < 6; round++) {
    const codes = new Set();
    const preds = new Set();
    nodes.forEach((e) => {
      if (pms.has(String(e.plan_master_id))) {
        codes.add(e.code);
        if (e.predecessor_code) preds.add(e.predecessor_code);
      }
    });
    const before = pms.size;
    nodes.forEach((e) => {
      if (codes.has(e.predecessor_code) || preds.has(e.code)) pms.add(String(e.plan_master_id));
    });
    if (pms.size === before) break;
  }
  return pms;
};

/** Danh sách cạnh nguồn -> tiêu thụ trong chuỗi, đọc từ sự kiện hiện có của lịch */
const linksOf = (apiEvents, pms) => {
  const chain = apiEvents
    .map((ev) => ({ ev, ...ev.extendedProps }))
    .filter((e) => isChainEvent(e) && pms.has(String(e.plan_master_id)));

  const byCode = new Map();
  chain.forEach((e) => {
    if (!byCode.has(e.code)) byCode.set(e.code, []);
    byCode.get(e.code).push(e);
  });

  const links = [];
  chain.forEach((target) => {
    let sources = (byCode.get(target.predecessor_code) || []).filter((s) => s !== target);
    // Thiếu predecessor_code: nối từ công đoạn gần nhất phía trước của cùng lô
    // (trừ Cân công đoạn 2: tá dược / nang rỗng đi thẳng vào BP / ĐH, xử lý riêng bên dưới)
    if (!sources.length && Number(target.stage_code) > FIRST_STAGE) {
      const prev = chain
        .filter((s) => s.plan_master_id === target.plan_master_id
          && Number(s.stage_code) < Number(target.stage_code) && Number(s.stage_code) !== WEIGH_EXCIPIENT)
        .sort((a, b) => Number(b.stage_code) - Number(a.stage_code))[0];
      if (prev) sources = [prev];
    }
    sources.forEach((source) => links.push({ source: source.ev, target: target.ev }));
  });

  // Dữ liệu lỗi: chèn thêm công đoạn vào lô mà quên sửa predecessor_code của công
  // đoạn sau, nên một nguồn có lịch nhận ở nhiều công đoạn (vd lô 11177: Cân ->
  // THT và Cân -> ĐG). Mỗi nguồn chỉ giữ cạnh tới công đoạn gần nhất; lô đóng gói
  // một phần (nhiều lịch ĐG cùng nguồn) cùng công đoạn nên vẫn giữ đủ nhánh.
  const nearest = new Map();
  links.forEach(({ source, target }) => {
    const stage = Number(target.extendedProps.stage_code);
    nearest.set(source.id, Math.min(nearest.get(source.id) ?? Infinity, stage));
  });
  for (let i = links.length - 1; i >= 0; i--) {
    if (Number(links[i].target.extendedProps.stage_code) !== nearest.get(links[i].source.id)) links.splice(i, 1);
  }

  // Lịch vừa mất cạnh tắt thì nối từ công đoạn gần nhất phía trước của cùng lô,
  // để đường đi tuần tự Cân -> THT -> ĐH -> ĐG thay vì nhảy cóc
  const hasIncoming = new Set(links.map((l) => l.target.id));
  chain.forEach((target) => {
    const stage = Number(target.stage_code);
    if (hasIncoming.has(target.ev.id) || stage <= FIRST_STAGE || stage === WEIGH_EXCIPIENT) return;
    const prev = chain
      .filter((s) => s.plan_master_id === target.plan_master_id
        && Number(s.stage_code) < stage && Number(s.stage_code) !== WEIGH_EXCIPIENT)
      .sort((a, b) => Number(b.stage_code) - Number(a.stage_code))[0];
    if (prev) links.push({ source: prev.ev, target: target.ev });
  });

  // Cân công đoạn 2 không là predecessor của lịch nào: tá dược BP vào Bao phim,
  // nang rỗng vào Định hình của cùng lô (nhận theo hậu tố tên lịch, xem w2 ở SchedualController)
  chain
    .filter((e) => Number(e.stage_code) === WEIGH_EXCIPIENT)
    .forEach((source) => {
      const title = source.ev.title || '';
      const stage = /BP|phim/i.test(title) ? 6 : (/nang/i.test(title) ? 5 : null);
      const target = stage && chain.find((t) => t.plan_master_id === source.plan_master_id && Number(t.stage_code) === stage);
      if (target) links.push({ source: source.ev, target: target.ev });
    });

  return links;
};

const svgEl = (tag, attrs) => {
  const el = document.createElementNS('http://www.w3.org/2000/svg', tag);
  Object.entries(attrs).forEach(([k, v]) => el.setAttribute(k, v));
  return el;
};

const removeOverlay = () => document.getElementById(SVG_ID)?.remove();

const draw = (calendarRef, pms) => {
  const api = calendarRef.current?.getApi();
  const root = api?.el || document;
  const body = root.querySelector('.fc-timeline-body');
  if (!body || !pms || pms.size === 0) { removeOverlay(); return; }

  const links = linksOf(api.getEvents(), pms);

  let svg = document.getElementById(SVG_ID);
  if (!svg || svg.parentNode !== body) {
    svg?.remove();
    svg = svgEl('svg', { id: SVG_ID });
    svg.style.cssText = 'position:absolute;left:0;top:0;pointer-events:none;z-index:4;overflow:visible';
    body.appendChild(svg);
  }
  svg.setAttribute('width', body.scrollWidth);
  svg.setAttribute('height', body.scrollHeight);
  svg.replaceChildren();

  const defs = svgEl('defs', {});
  LEVELS.forEach((l) => {
    const marker = svgEl('marker', {
      id: `lot-link-arrow-${l.key}`, viewBox: '0 0 10 10', refX: 9, refY: 5,
      markerWidth: 7, markerHeight: 7, orient: 'auto-start-reverse',
    });
    marker.appendChild(svgEl('path', { d: 'M0,0 L10,5 L0,10 z', fill: l.color }));
    defs.appendChild(marker);
  });
  svg.appendChild(defs);

  const box = body.getBoundingClientRect();
  const rectOf = (ev) => {
    const el = body.querySelector(`.fc-event[data-event-id="${CSS.escape(String(ev.id))}"]`);
    if (!el || el.offsetParent === null) return null;
    const r = el.getBoundingClientRect();
    return { left: r.left - box.left, right: r.right - box.left, mid: r.top - box.top + r.height / 2 };
  };

  const labels = [];
  links.forEach(({ source, target }) => {
    const a = rectOf(source);
    const b = rectOf(target);
    if (!a || !b || !source.end || !target.start) return;

    const hours = (target.start.getTime() - source.end.getTime()) / 3600000;
    const level = levelOf(hours);
    const x1 = a.right;
    const y1 = a.mid;
    const x2 = b.left;
    const y2 = b.mid;

    // Gấp khúc: ra khỏi nguồn một đoạn ngắn, đi dọc, rồi ngang tới tiêu thụ.
    // Tiêu thụ bắt đầu trước khi nguồn xong (chồng giờ) thì vẽ cong ngược lại.
    const xm = x1 + 10;
    const d = x2 - xm >= 14
      ? `M${x1},${y1} H${xm} V${y2} H${x2 - 1}`
      : `M${x1},${y1} C${x1 + 60},${y1} ${x2 - 60},${y2} ${x2 - 1},${y2}`;

    svg.appendChild(svgEl('path', {
      d, fill: 'none', stroke: '#ffffff', 'stroke-width': 5, 'stroke-linejoin': 'round', opacity: 0.9,
    }));
    svg.appendChild(svgEl('path', {
      d, fill: 'none', stroke: level.color, 'stroke-width': 2, 'stroke-linejoin': 'round',
      'stroke-dasharray': hours < 0 ? '5 3' : 'none', 'marker-end': `url(#lot-link-arrow-${level.key})`,
    }));
    svg.appendChild(svgEl('circle', { cx: x1, cy: y1, r: 3, fill: level.color, stroke: '#fff', 'stroke-width': 1.5 }));

    // Nhãn nằm trên đoạn ngang cuối, sát mũi tên; đoạn quá ngắn thì đặt sau điểm gập
    const long = x2 - xm > 70;
    labels.push({ x: long ? x2 - 6 : xm + 4, y: y2 - 5, anchor: long ? 'end' : 'start', text: waitText(hours), color: level.color });
  });

  // Nhãn vẽ sau cùng để không bị đường khác đè
  labels.forEach((l) => {
    const t = svgEl('text', {
      x: l.x, y: l.y, 'text-anchor': l.anchor,
      'font-size': 11, 'font-weight': 700, fill: l.color,
      stroke: '#ffffff', 'stroke-width': 3, 'paint-order': 'stroke', 'stroke-linejoin': 'round',
    });
    t.textContent = l.text;
    svg.appendChild(t);
  });
};

export function useLotLinks(calendarRef, activePlanMasterIds, events) {
  const focusPmIds = useMemo(
    () => (activePlanMasterIds?.length ? expandChain(events || [], activePlanMasterIds) : null),
    [activePlanMasterIds, events]
  );

  // Phòng có lịch của chuỗi lô đang tập trung
  const focusRoomIds = useMemo(() => {
    if (!focusPmIds) return null;
    const rooms = new Set();
    (events || []).forEach((e) => {
      if (e.resourceId != null && focusPmIds.has(String(e.plan_master_id))) rooms.add(String(e.resourceId));
    });
    return rooms;
  }, [focusPmIds, events]);

  /** Đang tập trung lô: chỉ giữ phòng có lịch của chuỗi (kèm dòng con của phòng đó) */
  const filterResources = useCallback((list) => {
    if (!focusRoomIds || !list?.length) return list;
    return list.filter((r) => focusRoomIds.has(String(r.parentId ?? r.id)));
  }, [focusRoomIds]);

  const pmsRef = useRef(focusPmIds);
  pmsRef.current = focusPmIds;
  const timerRef = useRef(null);

  // FullCalendar vẽ lại DOM sau khi state đổi, chờ một nhịp rồi mới đo vị trí
  const scheduleRef = useRef(() => {
    clearTimeout(timerRef.current);
    timerRef.current = setTimeout(() => draw(calendarRef, pmsRef.current), 80);
  });

  useEffect(() => {
    const schedule = scheduleRef.current;
    if (!focusPmIds) { clearTimeout(timerRef.current); removeOverlay(); return undefined; }

    schedule();
    const api = calendarRef.current?.getApi();
    api?.on('eventsSet', schedule);
    api?.on('datesSet', schedule);
    window.addEventListener('resize', schedule);
    const observer = typeof ResizeObserver !== 'undefined' ? new ResizeObserver(schedule) : null;
    const body = (api?.el || document).querySelector('.fc-timeline-body');
    if (body) observer?.observe(body);

    return () => {
      api?.off('eventsSet', schedule);
      api?.off('datesSet', schedule);
      window.removeEventListener('resize', schedule);
      observer?.disconnect();
    };
  }, [focusPmIds, calendarRef]);

  useEffect(() => () => { clearTimeout(timerRef.current); removeOverlay(); }, []);

  return { focusPmIds, filterResources };
}
