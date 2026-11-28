/**
 * Sắp lịch cân BÁN TỰ ĐỘNG trên Gantt Lịch Sản Xuất (yêu cầu user 20/11/2026, sửa 25/11/2026).
 *
 *  - Nút [⚖ Lô cần cân]: chế độ tập trung. Chỉ hiện lô công đoạn sau còn công đoạn cân chưa xong
 *    (Cân NL → PC/THT, Cân NL khác → ĐH/BP/ĐG, dò theo nextcessor_code của dòng cân) và lịch cân chưa
 *    cân xong; ẩn mọi lịch khác và phòng không liên quan (phòng cân luôn hiện). Huy hiệu ⚖:
 *    cam = chưa có lịch cân, xanh = đã có lịch cân. Biểu tượng ☑ trong nút: chọn hết lô chưa có lịch cân.
 *  - Nút [Gợi ý lịch cân]: với các lô công đoạn sau đang chọn, backend xếp từng dòng cân chưa có lịch vào
 *    phòng cân trống SỚM NHẤT (tránh ngày nghỉ). Kết quả hiện thành KHỐI CÂN NHÁP trên Gantt (chỉ ở giao
 *    diện, nằm trong "Thay đổi chưa lưu"): người dùng kéo thả chỉnh tay rồi bấm 💾 mới ghi
 *    (FullCalender.handleSaveChanges tách thay đổi của nháp gửi /Schedual/weighing/apply).
 *    Huỷ thay đổi (✕ ở "Thay đổi chưa lưu") thì nháp biến mất.
 *    Không cân sớm hơn 3 ngày trước ngày bắt đầu công đoạn sau (25/11/2026).
 *  - Nút [Ghép cân chung]: như Gợi ý nhưng mọi lô đang chọn cùng BTP + loại cân cân chung một khối (25/11/2026).
 *    Khối nháp đang chọn được gợi ý lại thay khối cũ: chọn khối ghép + Gợi ý = tách; chọn vài khối + Ghép = gộp.
 * Nháp dùng id thật của dòng cân ("<id>,<id>-main" / "-cleaning", giống khối cân gộp của getEvents), vị trí
 * mới nhất lấy từ pendingChanges nên tải lại / đổi khung xem không làm mất chỉnh sửa.
 * Backend: App\Services\WeighingAssistService, route /Schedual/weighing/{status,suggest,apply}.
 *
 * FullCalender.jsx chỉ dùng:
 *   const weighAssist = useWeighAssist({ calendarRef, events, resources, authorization, selectedEvents,
 *                                        onSelect, pendingChanges, setPendingChanges })
 *   weighAssist.draftEvents                  khối cân nháp, gộp vào calendarEvents
 *   weighAssist.classesFor(event)            class ẩn / huy hiệu ⚖ / nháp (eventClassNames)
 *   weighAssist.filterResources(list, all)   chỉ giữ phòng cân + phòng có lô cần cân khi đang bật
 *   weighAssist.splitChanges(pending)        { draft, other } khi bấm 💾
 *   weighAssist.saveDrafts(draft, range)     ghi nháp, trả dữ liệu như store()
 *   weighAssist.clearDrafts()                bỏ nháp (Huỷ thay đổi)
 *   weighAssist.on, weighAssist.targets      đưa vào calendarDeps
 *   <WeighAssistBar wa={weighAssist} />      2 nút trên thanh trên cùng
 */
import React, { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import axios from 'axios';
import Swal from 'sweetalert2';

const WEIGH_STAGES = [1, 2];
const NEXT_STAGES = [3, 4, 5, 6, 7];
const STAGE_SHORT = { 1: 'Cân NL', 2: 'Cân NL khác', 3: 'PC', 4: 'THT', 5: 'ĐH', 6: 'BP', 7: 'ĐG' };
const DRAFT_COLOR = '#0ea5e9';      // khối cân nháp
const DRAFT_LATE_COLOR = '#4d4b4bff'; // nháp cân xong sau giờ bắt đầu công đoạn sau (cùng màu lỗi đen)
const CLEAN_COLOR = '#a1a2a2ff';
const MAX_DAYS_BEFORE = 3;          // = WeighingAssistService::MAX_DAYS_BEFORE (chỉ để hiện chú thích)
const NO_EVENTS = [];

const pad = (n) => String(n).padStart(2, '0');
const toDate = (s) => (s instanceof Date ? s : new Date(String(s).replace(' ', 'T')));
const hm = (s) => { const d = toDate(s); return `${pad(d.getHours())}:${pad(d.getMinutes())} ${pad(d.getDate())}/${pad(d.getMonth() + 1)}`; };
const localIso = (d) => `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}T${pad(d.getHours())}:${pad(d.getMinutes())}:${pad(d.getSeconds())}`;
const sqlTime = (s) => { const d = toDate(s); return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())} ${pad(d.getHours())}:${pad(d.getMinutes())}:${pad(d.getSeconds())}`; };
const durText = (m) => (m >= 60 ? `${Math.floor(m / 60)}h${pad(m % 60)}` : `${m}p`);
const esc = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
const planId = (id) => String(id).split('-')[0];
const parseJson = (data) => (typeof data === 'string' ? JSON.parse(data.replace(/^<!--.*?-->/, '').trim()) : data);

/** id stage_plan của các lô công đoạn sau (PC → ĐG) trong danh sách đang chọn */
const nextStageIds = (selected) => [...new Set((selected || [])
  .filter((s) => !String(s.id).includes('-theory') && NEXT_STAGES.includes(Number(s.stage_code)))
  .map((s) => Number(planId(s.id)))
  .filter(Boolean))];

/**
 * Loại lịch cân để gắn nhãn phía trên thanh lịch (yêu cầu user 25/11/2026; 26/11 thêm thanh Nhận / Trả phòng): NL PC / NL THT (Cân NL, stage 1),
 * Nang rỗng (Cân NL khác → ĐH), NL BP (Cân NL khác → BP), NL ĐG (Cân NL khác → ĐG, hiếm). Theo công đoạn nhận
 * (hậu tố nextcessor_code; khối nháp mang next_stage), thiếu thì theo hậu tố tên lịch "-Nang Rỗng" / "-Tá dược BP".
 * Trả class CSS fc-weigh-kind-* (calendar.css) hoặc null với lịch không phải cân / vệ sinh.
 */
/** Chữ trên nhãn loại cân (fc-weigh-kind-* → nhãn treo .fc-hang-badge, FullCalender eventDidMount; màu trong calendar.css) */
export const WEIGH_KIND_LABELS = {
  'fc-weigh-kind-pc': '🧪 NL PC',
  'fc-weigh-kind-tht': '🧪 NL THT',
  'fc-weigh-kind-nang': '💊 Nang rỗng',
  'fc-weigh-kind-bp': '🖌️ NL BP',
  'fc-weigh-kind-dg': '📦 NL ĐG',
};

export function weighKindClass(event) {
  const p = event?.extendedProps || {};
  // Thanh Nhận / Trả phòng (is_running) mang run_stage_code để không lọt vào các kiểm tra theo stage_code của lịch
  const stage = Number(p.is_running ? p.run_stage_code : p.stage_code);
  if (!WEIGH_STAGES.includes(stage) || p.is_clearning) return null;
  const next = Number(p.next_stage ?? String(p.nextcessor_code ?? '').split('_').pop()) || null;
  if (stage === 1) return next === 4 ? 'fc-weigh-kind-tht' : 'fc-weigh-kind-pc';
  if (next === 5) return 'fc-weigh-kind-nang';
  if (next === 6) return 'fc-weigh-kind-bp';
  if (next === 7) return 'fc-weigh-kind-dg';
  const title = String(event.title || '');
  if (/Nang Rỗng$/i.test(title)) return 'fc-weigh-kind-nang';
  if (/Tá dược BP$/i.test(title)) return 'fc-weigh-kind-bp';
  return null;
}

/** Khối gợi ý -> 2 sự kiện nháp (cân + vệ sinh), cùng dạng sự kiện thật của getEvents */
const draftPair = (b) => {
  const base = b.lots.map((l) => l.id).join(',');
  const first = b.lots[0];
  const late = b.lots.filter((l) => l.late_minutes > 0);
  const title = '✎ ' + (b.lots.length > 1
    ? `${first.product_name} (${b.lots.map((l) => l.batch).join(', ')})`
    : first.title);
  const subtitle = 'Gợi ý lịch cân - CHƯA LƯU (bấm 💾 để lưu)'
    + late.map((l) => `\n➡️ ${l.title}: cân xong trễ ${durText(l.late_minutes)} so với ${STAGE_SHORT[l.next_stage]} ${hm(l.next_start)}`).join('')
    + (b.warnings || []).map((w) => `\n⚠ ${w}`).join('');
  const common = {
    plan_id: first.id,
    resourceId: String(b.room_id),
    plan_master_id: first.plan_master_id,
    stage_code: b.stage_code,
    finished: 0,
    submit: 0,
    process_code: `${b.intermediate_code}_NA_${b.room_id}`,
    campaign_code: b.campaign_code,
    product_name: first.product_name,
    batch_name: b.lots.map((l) => l.batch).join(', '),
    code: first.code,
    next_stage: first.next_stage,  // nhãn loại cân (weighKindClass)
    title_clearning: b.clearning_type,
    is_weigh_draft: true,
  };
  return {
    base,
    block: b,
    events: [
      {
        ...common,
        id: `${base}-main`,
        title,
        start: b.start,
        end: b.end,
        color: late.length ? DRAFT_LATE_COLOR : DRAFT_COLOR,
        textColor: '#ffffff',
        is_clearning: false,
        first_in_campaign: 1,
        subtitle,
        violation_colors: [],
      },
      {
        ...common,
        id: `${base}-cleaning`,
        title: b.clearning_type,
        start: b.start_clearning,
        end: b.end_clearning,
        color: CLEAN_COLOR,
        textColor: '#ffffff',
        is_clearning: true,
      },
    ],
  };
};

/**
 * Vệ sinh của lịch cân (công đoạn 1, 2 - kể cả khối nháp) luôn chạy NGAY SAU khi cân xong, cùng phòng, giữ
 * thời lượng vệ sinh (yêu cầu user 25/11/2026). Nhận các thay đổi vừa kéo thả / kéo dãn (id, start, end,
 * resourceId), trả thêm thay đổi cho sự kiện vệ sinh tương ứng (kể cả khi người dùng kéo riêng vệ sinh: kéo về
 * lại sau khối cân). Gọi placeWeighCleaning() để đặt lên lịch rồi gộp vào pendingChanges.
 */
export function followWeighCleaning(api, updates) {
  if (!api || !updates?.length) return [];
  const byId = new Map(updates.map((u) => [String(u.id), u]));
  const mains = new Set();
  updates.forEach((u) => {
    const id = String(u.id);
    if (id.endsWith('-main')) mains.add(id);
    else if (id.endsWith('-cleaning')) mains.add(id.replace(/-cleaning$/, '-main'));
  });

  const out = [];
  mains.forEach((mainId) => {
    const main = api.getEventById(mainId);
    if (!main || !WEIGH_STAGES.includes(Number(main.extendedProps?.stage_code))) return;
    const cleanId = mainId.replace(/-main$/, '-cleaning');
    const clean = api.getEventById(cleanId);
    if (!clean || !clean.start || Number(clean.extendedProps?.finished) === 1 || clean.extendedProps?.is_running) return;

    const mu = byId.get(mainId);
    const cu = byId.get(cleanId);
    const mainEnd = new Date(mu ? mu.end : main.end);
    const room = String(mu?.resourceId ?? main.getResources()[0]?.id ?? '');
    const cs = cu ? new Date(cu.start) : clean.start;
    const ce = cu ? new Date(cu.end) : (clean.end || clean.start);
    const curRoom = String(cu?.resourceId ?? clean.getResources()[0]?.id ?? '');
    const s = mainEnd;
    const e = new Date(mainEnd.getTime() + Math.max(0, ce - cs));
    if (!cu && s.getTime() === cs.getTime() && e.getTime() === ce.getTime() && curRoom === room) return;

    out.push({
      id: cleanId,
      start: s.toISOString(),
      end: e.toISOString(),
      resourceId: room || null,
      title: clean.title,
      submit: clean.extendedProps?.submit,
    });
  });
  return out;
}

/** Đặt các sự kiện vệ sinh do followWeighCleaning() tính lên lịch (giờ + phòng) */
export function placeWeighCleaning(api, follow) {
  if (!api || !follow?.length) return;
  api.batchRendering(() => follow.forEach((f) => {
    const ev = api.getEventById(f.id);
    if (!ev) return;
    ev.setDates(new Date(f.start), new Date(f.end), { maintainDuration: false });
    if (f.resourceId && String(ev.getResources()[0]?.id) !== String(f.resourceId)) ev.setResources([String(f.resourceId)]);
  }));
}

export function useWeighAssist({ calendarRef, events, resources, authorization, selectedEvents, onSelect, pendingChanges, setPendingChanges, onGoto }) {
  const [on, setOn] = useState(false);
  const [targets, setTargets] = useState(null); // Map id stage_plan công đoạn sau -> 'need' | 'planned'
  const [drafts, setDrafts] = useState([]);     // [{ base, block, events: [main, cleaning] }]
  const reqRef = useRef(0);

  const loadStatus = useCallback(async () => {
    const req = ++reqRef.current;
    try {
      const { data } = await axios.post('/Schedual/weighing/status');
      if (req !== reqRef.current) return;
      setTargets(new Map(Object.entries(parseJson(data).targets || {})));
    } catch (e) {
      console.error('weighing/status', e);
    }
  }, []);

  // Bật thì tải trạng thái; lịch đổi (lưu, gợi ý, làm mới...) thì tải lại
  useEffect(() => {
    if (!on) { reqRef.current++; setTargets(null); return undefined; }
    const timer = setTimeout(loadStatus, 250);
    return () => clearTimeout(timer);
  }, [on, events, loadStatus]);

  const draftIds = useMemo(() => new Set(drafts.flatMap((d) => d.events.map((e) => e.id))), [drafts]);
  const pendingRef = useRef(pendingChanges);
  pendingRef.current = pendingChanges;

  /** Nháp ở vị trí mới nhất: người dùng kéo thả thì pendingChanges giữ giờ / phòng mới */
  const placed = useCallback(() => {
    if (!drafts.length) return NO_EVENTS;
    const moved = new Map((pendingRef.current || []).filter((p) => draftIds.has(String(p.id))).map((p) => [String(p.id), p]));
    return drafts.flatMap((d) => d.events.map((e) => {
      const p = moved.get(e.id);
      return p ? { ...e, start: p.start, end: p.end, resourceId: p.resourceId != null ? String(p.resourceId) : e.resourceId } : e;
    }));
  }, [drafts, draftIds]);

  // CHỈ dựng lại khi thêm / bỏ nháp hoặc lịch tải lại (events đổi). Đổi danh tính mảng ở mỗi lần kéo thả
  // làm FullCalendar nạp lại mọi sự kiện từ props -> mất vị trí kéo thả chưa lưu của cả lịch thật.
  const draftEvents = useMemo(() => placed(), [placed, events]); // eslint-disable-line react-hooks/exhaustive-deps

  // Thêm / bỏ nháp làm FullCalendar nạp lại sự kiện từ props: đặt lại vị trí của mọi thay đổi chưa lưu
  // (kể cả lịch thật đang kéo thả dở) để không mất thao tác của người dùng.
  const firstRender = useRef(true);
  useEffect(() => {
    if (firstRender.current) { firstRender.current = false; return undefined; }
    const timer = setTimeout(() => {
      const api = calendarRef.current?.getApi();
      if (!api) return;
      api.batchRendering(() => (pendingRef.current || []).forEach((p) => {
        const ev = api.getEventById(p.id);
        if (!ev || !p.start || !p.end) return;
        if (ev.start?.getTime() !== new Date(p.start).getTime() || ev.end?.getTime() !== new Date(p.end).getTime()) {
          ev.setDates(p.start, p.end);
        }
        if (p.resourceId != null && String(ev.getResources()[0]?.id) !== String(p.resourceId)) ev.setResources([String(p.resourceId)]);
      }));
    }, 0);
    return () => clearTimeout(timer);
  }, [drafts, calendarRef]);

  const weighRoomIds = useMemo(() => new Set((resources || [])
    .filter((r) => !r.is_personnel_sub && WEIGH_STAGES.includes(Number(r.stage_code)))
    .map((r) => String(r.id))), [resources]);

  // Phòng có lô cần cân trong khung đang xem
  const targetRoomIds = useMemo(() => {
    if (!targets) return null;
    const rooms = new Set();
    (events || []).forEach((e) => {
      if (!e.is_clearning && targets.has(planId(e.id)) && e.resourceId != null) rooms.add(String(e.resourceId));
    });
    return rooms;
  }, [targets, events]);

  const counts = useMemo(() => {
    if (!targets) return null;
    const need = new Set();
    const planned = new Set();
    (events || []).forEach((e) => {
      const id = planId(e.id);
      const st = targets.get(id);
      if (st === 'need') need.add(id); else if (st === 'planned') planned.add(id);
    });
    return { need: need.size, planned: planned.size };
  }, [targets, events]);

  // Lô công đoạn sau đã có khối cân nháp (chưa lưu): huy hiệu ⚖ cùng màu khối nháp
  const draftNextIds = useMemo(() => new Set(drafts.flatMap((d) => d.block.lots.map((l) => String(l.next_id)))), [drafts]);

  const classesFor = useCallback((event) => {
    if (event.display === 'background') return [];
    const p = event.extendedProps || {};
    const draft = p.is_weigh_draft ? ['fc-weigh-draft'] : [];
    if (!on || !targets) return draft;
    if (p.is_personnel || String(event.id).startsWith('personnel-')) return [];
    if (String(event.id).includes('-theory')) return ['fc-event-hidden'];

    const stage = Number(p.stage_code);
    if (WEIGH_STAGES.includes(stage)) return Number(p.finished) === 1 ? ['fc-event-hidden'] : draft;

    const st = targets.get(planId(event.id));
    if (st) {
      if (p.is_clearning) return [];
      if (draftNextIds.has(planId(event.id))) return ['fc-weigh-drafted'];
      return [st === 'need' ? 'fc-weigh-need' : 'fc-weigh-planned'];
    }

    // BT-HC-TI / phòng đang bận trên phòng cân vẫn hiện để thấy vì sao phòng không trống
    const resId = String(event._def?.resourceIds?.[0] ?? '');
    return weighRoomIds.has(resId) ? [] : ['fc-event-hidden'];
  }, [on, targets, weighRoomIds, draftNextIds]);

  /** filtered: danh sách phòng sau bộ lọc của người dùng; all: trước bộ lọc công đoạn / phòng */
  const filterResources = useCallback((filtered, all) => {
    if (!on || !targetRoomIds || !filtered) return filtered;
    const keep = new Set(filtered.map((r) => String(r.id)));
    return (all || filtered).filter((r) => {
      const room = String(r.parentId ?? r.id);
      return weighRoomIds.has(room) || (keep.has(String(r.id)) && targetRoomIds.has(room));
    });
  }, [on, targetRoomIds, weighRoomIds]);

  const toggle = useCallback(() => setOn((v) => !v), []);

  /** Chọn hết lô chưa có lịch cân đang có trên lịch */
  const selectNeed = useCallback(() => {
    const api = calendarRef.current?.getApi();
    if (!api || !targets) return;
    const list = api.getEvents()
      .filter((ev) => String(ev.id).endsWith('-main') && targets.get(planId(ev.id)) === 'need')
      .map((ev) => ({ id: ev.id, stage_code: ev.extendedProps.stage_code, plan_master_id: ev.extendedProps.plan_master_id }));
    onSelect?.(list);
  }, [calendarRef, targets, onSelect]);

  /** Khối nháp đang được chọn trên lịch (chọn để gợi ý lại / ghép với lô khác) */
  const selectedDrafts = useMemo(() => {
    const sel = new Set((selectedEvents || []).map((s) => String(s.id).replace(/-cleaning$/, '-main')));
    return drafts.filter((d) => sel.has(`${d.base}-main`));
  }, [selectedEvents, drafts]);

  /** id lô công đoạn sau cần gợi ý: lô đang chọn + lô của khối nháp đang chọn */
  const requestIds = useMemo(() => [...new Set([
    ...nextStageIds(selectedEvents),
    ...selectedDrafts.flatMap((d) => d.block.lots.map((l) => Number(l.next_id))),
  ])], [selectedEvents, selectedDrafts]);

  const eligibleCount = requestIds.length;

  /** Vị trí hiện tại (đã kéo thả) của các khối nháp, dùng để giữ chỗ khi gợi ý thêm (trừ khối sắp gợi ý lại) */
  const currentBlocks = useCallback((exclude = new Set()) => {
    const byId = new Map(placed().map((e) => [e.id, e]));
    return drafts.filter((d) => !exclude.has(d.base)).map((d) => {
      const main = byId.get(`${d.base}-main`);
      const clean = byId.get(`${d.base}-cleaning`);
      return { ids: d.block.lots.map((l) => l.id), room_id: Number(main.resourceId), start: sqlTime(main.start), end_clearning: sqlTime(clean?.end ?? main.end) };
    });
  }, [drafts, placed]);

  /**
   * merge = false: gợi ý theo mặc định (lô lẻ / chiến dịch có sẵn). merge = true: GHÉP mọi lô đang chọn cùng
   * BTP + loại cân thành một khối. Khối nháp đang chọn được gợi ý lại (thay khối cũ): chọn khối ghép rồi bấm
   * Gợi ý = tách ra; chọn vài khối nháp rồi bấm Ghép = gộp lại.
   */
  const suggest = useCallback(async (merge = false) => {
    if (!authorization) return;
    const ids = requestIds;
    if (!ids.length || (merge && ids.length < 2)) {
      Swal.fire({
        icon: 'info',
        title: merge ? 'Chọn ít nhất 2 lô để ghép cân chung' : 'Chọn lô cần chuẩn bị cân',
        html: 'Bấm chọn trên lịch các lô <b>PC / THT</b> (cần Cân NL) hoặc <b>ĐH / BP</b> (cần Cân NL khác) '
          + '(hoặc các khối cân nháp ✎), rồi bấm lại <b>' + (merge ? 'Ghép cân chung' : 'Gợi ý lịch cân') + '</b>.'
          + '<br><small>Bật <b>⚖ Lô cần cân</b> để chỉ hiện các lô này; '
          + 'biểu tượng ☑ trong nút chọn nhanh mọi lô chưa có lịch cân.</small>',
      });
      return;
    }

    const redo = new Set(selectedDrafts.map((d) => d.base));
    Swal.fire({ title: 'Đang tìm giờ trống phòng cân...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
    let res;
    try {
      // Khối nháp chưa lưu (trừ khối đang chọn để gợi ý lại): không gợi ý lại lô đó, giữ chỗ phòng ở vị trí nháp
      res = parseJson((await axios.post('/Schedual/weighing/suggest', { ids, reserved: currentBlocks(redo), merge })).data);
    } catch (e) {
      Swal.fire({ icon: 'error', title: 'Không gợi ý được lịch cân', text: e.response?.data?.message || e.message });
      return;
    }

    const extra = extraHtml(res);
    if (!res.blocks?.length) {
      Swal.fire({ icon: 'info', title: 'Không có lô nào cần xếp lịch cân', html: extra || '', width: 760 });
      return;
    }

    const pairs = res.blocks.map(draftPair);
    const dropIds = new Set(drafts.filter((d) => redo.has(d.base)).flatMap((d) => d.events.map((e) => e.id)));
    setDrafts((prev) => [...prev.filter((d) => !redo.has(d.base)), ...pairs]);
    // Nháp nằm trong "Thay đổi chưa lưu" để 💾 lưu / ✕ huỷ như kéo thả thường
    setPendingChanges((prev) => [...prev.filter((p) => !dropIds.has(String(p.id))), ...pairs.flatMap((d) => d.events.map((e) => ({
      id: e.id, start: e.start, end: e.end, resourceId: e.resourceId, title: e.title, submit: 0,
    })))]);
    onSelect?.([]);

    const late = res.blocks.filter((b) => b.lots.some((l) => l.late_minutes > 0)).length;
    const first = res.blocks.map((b) => b.start).sort()[0];
    const view = calendarRef.current?.getApi()?.view;
    const inView = view && toDate(first) >= view.activeStart && toDate(first) < view.activeEnd;
    const merged = res.blocks.filter((b) => b.lots.length > 1).length;
    const { isConfirmed } = await Swal.fire({
      icon: 'success',
      title: `Đã ${redo.size ? 'gợi ý lại' : 'thêm'} ${res.blocks.length} khối cân nháp lên lịch`
        + (merged ? ` (${merged} khối cân chung nhiều lô)` : ''),
      html: `Khối nháp màu <span style="background:${DRAFT_COLOR};color:#fff;padding:0 6px;border-radius:3px">xanh dương</span>, tên có dấu ✎, `
        + `sớm nhất lúc <b>${hm(first)}</b>. Kéo thả để chỉnh phòng / giờ, rồi bấm <b>💾</b> (Ctrl + S) để lưu; `
        + 'bấm ✕ ở <b>Thay đổi chưa lưu</b> để bỏ toàn bộ.'
        + (late ? `<div style="margin-top:8px;color:#b91c1c">${late} khối màu đen: cân xong sau giờ bắt đầu công đoạn sau (rê chuột xem chi tiết).</div>` : '')
        + extra,
      width: extra ? 760 : 560,
      confirmButtonText: inView ? 'Đã hiểu' : 'Xem khối nháp',
      showCancelButton: !inView,
      cancelButtonText: 'Ở lại khung này',
    });
    // Khối sớm nhất nằm ngoài khung đang xem: chuyển lịch tới ngày đó
    if (isConfirmed && !inView) onGoto?.(toDate(first));
  }, [authorization, requestIds, selectedDrafts, drafts, currentBlocks, setPendingChanges, onSelect, calendarRef, onGoto]);

  /** Tách thay đổi chưa lưu: của khối nháp / của lịch thật */
  const splitChanges = useCallback((changes) => ({
    draft: (changes || []).filter((c) => draftIds.has(String(c.id))),
    other: (changes || []).filter((c) => !draftIds.has(String(c.id))),
  }), [draftIds]);

  /** Ghi các khối nháp theo vị trí hiện tại; trả dữ liệu của /Schedual/weighing/apply */
  const saveDrafts = useCallback(async ({ startDate, endDate, theory }) => {
    const byId = new Map(placed().map((e) => [e.id, e]));
    const blocks = drafts.map((d) => {
      const main = byId.get(`${d.base}-main`);
      const clean = byId.get(`${d.base}-cleaning`);
      const mainEnd = toDate(main.end);
      let cs = toDate(clean.start);
      let ce = toDate(clean.end);
      // Vệ sinh bị bỏ lại phòng cũ / nằm trước lúc cân xong -> đặt ngay sau khối cân, giữ thời lượng
      if (String(clean.resourceId) !== String(main.resourceId) || cs < mainEnd) {
        const dur = ce - cs;
        cs = mainEnd;
        ce = new Date(mainEnd.getTime() + dur);
      }
      return {
        room_id: Number(main.resourceId),
        ids: d.block.lots.map((l) => l.id),
        start: sqlTime(main.start),
        end: sqlTime(main.end),
        start_clearning: sqlTime(cs),
        end_clearning: sqlTime(ce),
        clearning_type: d.block.clearning_type,
      };
    });
    const data = parseJson((await axios.put('/Schedual/weighing/apply', { blocks, startDate, endDate, theory })).data);
    setDrafts([]);
    return data;
  }, [drafts, placed]);

  const clearDrafts = useCallback(() => setDrafts([]), []);

  return {
    on, toggle, targets, counts, classesFor, filterResources, selectNeed,
    authorization, eligibleCount, suggest,
    draftEvents, draftCount: drafts.length, splitChanges, saveDrafts, clearDrafts,
  };
}

function extraHtml(res) {
  const notes = (res.notes || []).map((n) => `<li>${esc(n)}</li>`).join('');
  const skipped = (res.skipped || []).map((s) => `<li><b>${esc(s.title)}</b>: ${esc(s.reason)}</li>`).join('');
  if (!notes && !skipped) return '';
  return `<div style="text-align:left;font-size:13px;margin-top:10px">
    ${notes ? `<ul style="color:#64748b;margin:0 0 6px 18px">${notes}</ul>` : ''}
    ${skipped ? `<details ${res.blocks?.length ? '' : 'open'}><summary style="cursor:pointer;color:#b45309"><b>${res.skipped.length} dòng cân không gợi ý</b></summary>
      <ul style="margin:6px 0 0 18px;max-height:25vh;overflow:auto">${skipped}</ul></details>` : ''}
  </div>`;
}

/** 2 nút trên thanh trên cùng của Gantt */
export function WeighAssistBar({ wa }) {
  const { on, counts } = wa;
  const n = wa.eligibleCount;
  const canSuggest = n > 0;
  const canMerge = n > 1;

  // Nút trong cụm: luôn hiện (không nhảy chỗ), mờ đi khi chưa dùng được và rê chuột để biết vì sao
  const ON = {
    green: 'bg-green-100 text-green-800 border-green-300 hover:bg-green-200 cursor-pointer',
    cyan: 'bg-cyan-100 text-cyan-800 border-cyan-300 hover:bg-cyan-200 cursor-pointer',
  };
  const step = (enabled, color) => 'flex align-items-center gap-2 px-3 py-1 border-round-2xl border-1 transition-colors '
    + (enabled ? ON[color] : 'bg-gray-50 text-gray-400 border-gray-200 cursor-not-allowed');

  return (
    <div
      className="flex align-items-center gap-2 px-2 py-1 border-round-2xl border-1 border-blue-200 bg-blue-50 shadow-1"
      style={{ whiteSpace: 'nowrap', userSelect: 'none' }}
    >
      <span className="text-xs font-bold text-blue-800 px-1" title="Sắp lịch cân bán tự động: 1. lọc lô cần cân, 2. chọn lô trên lịch, 3. gợi ý hoặc ghép cân chung, chỉnh tay rồi bấm 💾">
        ⚖ Sắp lịch cân
      </span>
      <div
        className={`flex align-items-center gap-2 px-3 py-1 border-round-2xl border-1 cursor-pointer transition-colors ${on
          ? 'bg-orange-100 text-orange-800 border-orange-300 hover:bg-orange-200'
          : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-100'}`}
        onClick={wa.toggle}
        title={on
          ? `Đang chỉ hiện lô cần chuẩn bị cân${counts ? ` trong khung đang xem: ${counts.need} lô chưa có lịch cân (⚖ cam), ${counts.planned} lô đã có lịch cân (⚖ xanh)` : ''}. Bấm để hiện lại toàn bộ lịch.`
          : 'Chỉ hiện các lô PC/THT/ĐH/BP còn công đoạn cân chưa xong và lịch cân chưa cân xong'}
      >
        <i className={`pi ${on ? 'pi-eye' : 'pi-eye-slash'}`}></i>
        <span className="font-bold text-sm">Lô cần cân{on && counts ? ` (${counts.need})` : ''}</span>
        {on && counts?.need > 0 && wa.authorization && (
          <i
            className="pi pi-check-square ml-1 hover:text-orange-600"
            title="Chọn hết các lô chưa có lịch cân đang hiện trên lịch"
            onClick={(e) => { e.stopPropagation(); wa.selectNeed(); }}
          ></i>
        )}
      </div>
      {wa.authorization && (
        <>
          <div
            className={step(canSuggest, 'green')}
            onClick={() => canSuggest && wa.suggest(false)}
            title={canSuggest
              ? 'Xếp công đoạn cân của các lô đang chọn vào phòng cân trống sớm nhất (tránh ngày nghỉ, không sớm hơn '
                + `${MAX_DAYS_BEFORE} ngày trước ngày bắt đầu công đoạn sau): hiện khối cân nháp trên lịch, chỉnh tay rồi bấm 💾 để lưu. `
                + 'Chọn khối nháp rồi bấm = gợi ý lại (tách khối ghép thành từng lô).'
              : 'Chọn trên lịch các lô PC / THT / ĐH / BP cần cân (hoặc khối cân nháp) rồi bấm'}
          >
            <i className="pi pi-sparkles"></i>
            <span className="font-bold text-sm">Gợi ý lịch cân{canSuggest ? ` (${n})` : ''}</span>
          </div>
          <div
            className={step(canMerge, 'cyan')}
            onClick={() => canMerge && wa.suggest(true)}
            title={canMerge
              ? 'Ghép các lô đang chọn (kể cả lô trong khối cân nháp đang chọn) cùng BTP + cùng loại cân thành MỘT khối cân chung, xếp vào phòng cân trống sớm nhất'
              : 'Chọn từ 2 lô cùng BTP trở lên rồi bấm để ghép thành một khối cân chung'}
          >
            <i className="pi pi-link"></i>
            <span className="font-bold text-sm">Ghép cân chung{canMerge ? ` (${n})` : ''}</span>
          </div>
        </>
      )}
    </div>
  );
}
