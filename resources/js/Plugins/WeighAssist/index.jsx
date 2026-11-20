/**
 * Sắp lịch cân BÁN TỰ ĐỘNG trên Gantt Lịch Sản Xuất (yêu cầu user 20/11/2026).
 *
 *  - Nút [⚖ Lô cần cân]: chế độ tập trung. Chỉ hiện lô công đoạn sau còn công đoạn cân chưa xong
 *    (Cân NL → PC/THT, Cân NL khác → ĐH/BP/ĐG, dò theo nextcessor_code của dòng cân) và lịch cân chưa
 *    cân xong; ẩn mọi lịch khác và phòng không liên quan (phòng cân luôn hiện). Huy hiệu ⚖ góc dưới trái:
 *    cam = chưa có lịch cân, xanh = đã có lịch cân. Biểu tượng ☑ trong nút: chọn hết lô chưa có lịch cân.
 *  - Nút [Gợi ý lịch cân]: với các lô công đoạn sau đang chọn, backend xếp từng dòng cân chưa có lịch vào
 *    phòng cân trống SỚM NHẤT (tránh ngày nghỉ); người dùng duyệt bảng gợi ý rồi mới ghi.
 * Backend: App\Services\WeighingAssistService, route /Schedual/weighing/{status,suggest,apply}.
 *
 * FullCalender.jsx chỉ dùng:
 *   const weighAssist = useWeighAssist({ calendarRef, events, resources, authorization, selectedEvents, onSelect, onApplied })
 *   weighAssist.classesFor(event)            class ẩn / huy hiệu ⚖ (eventClassNames)
 *   weighAssist.filterResources(list, all)   chỉ giữ phòng cân + phòng có lô cần cân khi đang bật
 *   weighAssist.on, weighAssist.targets      đưa vào calendarDeps
 *   <WeighAssistBar wa={weighAssist} />      2 nút trên thanh trên cùng
 */
import React, { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import axios from 'axios';
import Swal from 'sweetalert2';

const WEIGH_STAGES = [1, 2];
const NEXT_STAGES = [3, 4, 5, 6, 7];
const STAGE_SHORT = { 1: 'Cân NL', 2: 'Cân NL khác', 3: 'PC', 4: 'THT', 5: 'ĐH', 6: 'BP', 7: 'ĐG' };

const pad = (n) => String(n).padStart(2, '0');
const toDate = (s) => new Date(String(s).replace(' ', 'T'));
const hm = (s) => { const d = toDate(s); return `${pad(d.getHours())}:${pad(d.getMinutes())} ${pad(d.getDate())}/${pad(d.getMonth() + 1)}`; };
const localIso = (d) => `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}T${pad(d.getHours())}:${pad(d.getMinutes())}:${pad(d.getSeconds())}`;
const durText = (m) => (m >= 60 ? `${Math.floor(m / 60)}h${pad(m % 60)}` : `${m}p`);
const esc = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
const planId = (id) => String(id).split('-')[0];
const parseJson = (data) => (typeof data === 'string' ? JSON.parse(data.replace(/^<!--.*?-->/, '').trim()) : data);

/** id stage_plan của các lô công đoạn sau (PC → ĐG) trong danh sách đang chọn */
const nextStageIds = (selected) => [...new Set((selected || [])
  .filter((s) => !String(s.id).includes('-theory') && NEXT_STAGES.includes(Number(s.stage_code)))
  .map((s) => Number(planId(s.id)))
  .filter(Boolean))];

export function useWeighAssist({ calendarRef, events, resources, authorization, selectedEvents, onSelect, onApplied }) {
  const [on, setOn] = useState(false);
  const [targets, setTargets] = useState(null); // Map id stage_plan công đoạn sau -> 'need' | 'planned'
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

  const classesFor = useCallback((event) => {
    if (!on || !targets || event.display === 'background') return [];
    const p = event.extendedProps || {};
    if (p.is_personnel || String(event.id).startsWith('personnel-')) return [];
    if (String(event.id).includes('-theory')) return ['fc-event-hidden'];

    const stage = Number(p.stage_code);
    if (WEIGH_STAGES.includes(stage)) return Number(p.finished) === 1 ? ['fc-event-hidden'] : [];

    const st = targets.get(planId(event.id));
    if (st) return p.is_clearning ? [] : [st === 'need' ? 'fc-weigh-need' : 'fc-weigh-planned'];

    // BT-HC-TI / phòng đang bận trên phòng cân vẫn hiện để thấy vì sao phòng không trống
    const resId = String(event._def?.resourceIds?.[0] ?? '');
    return weighRoomIds.has(resId) ? [] : ['fc-event-hidden'];
  }, [on, targets, weighRoomIds]);

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

  const eligibleCount = useMemo(() => nextStageIds(selectedEvents).length, [selectedEvents]);

  const suggest = useCallback(async () => {
    if (!authorization) return;
    const ids = nextStageIds(selectedEvents);
    if (!ids.length) {
      Swal.fire({
        icon: 'info',
        title: 'Chọn lô cần chuẩn bị cân',
        html: 'Bấm chọn trên lịch các lô <b>PC / THT</b> (cần Cân NL) hoặc <b>ĐH / BP</b> (cần Cân NL khác), '
          + 'rồi bấm lại <b>Gợi ý lịch cân</b>.<br><small>Bật <b>⚖ Lô cần cân</b> để chỉ hiện các lô này; '
          + 'biểu tượng ☑ trong nút chọn nhanh mọi lô chưa có lịch cân.</small>',
      });
      return;
    }

    Swal.fire({ title: 'Đang tìm giờ trống phòng cân...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
    let res;
    try {
      res = parseJson((await axios.post('/Schedual/weighing/suggest', { ids })).data);
    } catch (e) {
      Swal.fire({ icon: 'error', title: 'Không gợi ý được lịch cân', text: e.response?.data?.message || e.message });
      return;
    }

    const extra = extraHtml(res);
    if (!res.blocks?.length) {
      Swal.fire({ icon: 'info', title: 'Không có lô nào cần xếp lịch cân', html: extra || '', width: 760 });
      return;
    }

    const { isConfirmed, value: keys } = await Swal.fire({
      title: `Gợi ý lịch cân · ${res.blocks.length} khối`,
      html: previewHtml(res.blocks) + extra,
      width: 1200,
      showCancelButton: true,
      confirmButtonText: 'Xếp lịch cân',
      cancelButtonText: 'Hủy',
      confirmButtonColor: '#16a34a',
      didOpen: () => {
        const all = document.getElementById('wa-all');
        const boxes = () => [...document.querySelectorAll('.wa-pick')];
        all?.addEventListener('change', () => boxes().forEach((b) => { b.checked = all.checked; }));
      },
      preConfirm: () => {
        const picked = [...document.querySelectorAll('.wa-pick:checked')].map((b) => b.value);
        if (!picked.length) Swal.showValidationMessage('Chưa chọn khối cân nào');
        return picked;
      },
    });
    if (!isConfirmed) return;

    const chosen = res.blocks.filter((b) => keys.includes(b.key));
    const view = calendarRef.current?.getApi()?.view;
    Swal.fire({ title: 'Đang ghi lịch cân...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
    try {
      const data = parseJson((await axios.put('/Schedual/weighing/apply', {
        blocks: chosen.map((b) => ({
          room_id: b.room_id,
          ids: b.lots.map((l) => l.id),
          start: b.start,
          end: b.end,
          end_clearning: b.end_clearning,
          clearning_type: b.clearning_type,
        })),
        startDate: view ? localIso(view.activeStart) : null,
        endDate: view ? localIso(view.activeEnd) : null,
        theory: JSON.parse(sessionStorage.getItem('theoryHidden')),
      })).data);

      onApplied?.(data);

      const problems = data.problems || [];
      Swal.fire({
        icon: problems.length ? 'warning' : 'success',
        title: `Đã xếp lịch cân ${data.saved_lots} lô (${data.saved_blocks} khối)`,
        html: 'Lịch cân mới ở trạng thái <b>chưa submit</b>: kiểm tra trên lịch (kéo thả chỉnh được) rồi bấm 📤 Submit.'
          + (problems.length ? `<div style="text-align:left;margin-top:10px;color:#b45309"><b>Không ghi:</b><ul>${problems.map((p) => `<li>${esc(p)}</li>`).join('')}</ul></div>` : ''),
        width: problems.length ? 760 : 520,
      });
    } catch (e) {
      Swal.fire({ icon: 'error', title: 'Không ghi được lịch cân', text: e.response?.data?.message || e.message });
    }
  }, [authorization, selectedEvents, calendarRef, onApplied]);

  return {
    on, toggle, targets, counts, classesFor, filterResources, selectNeed,
    authorization, eligibleCount, suggest,
  };
}

function previewHtml(blocks) {
  const rows = blocks.map((b) => {
    const late = b.lots.some((l) => l.late_minutes > 0);
    const lots = b.lots.map((l) => esc(l.title)).join('<br>');
    const nexts = b.lots.map((l) => `${STAGE_SHORT[l.next_stage] || ''} ${hm(l.next_start)}`
      + (l.late_minutes > 0 ? ` <span style="color:#dc2626;font-weight:600">· cân xong trễ ${durText(l.late_minutes)}</span>` : '')).join('<br>');
    const warns = (b.warnings || []).map((w) => `<div style="color:#dc2626;font-size:12px">⚠ ${esc(w)}</div>`).join('');
    return `<tr style="${late ? 'background:#fef2f2' : ''}">
      <td style="text-align:center"><input type="checkbox" class="wa-pick" value="${esc(b.key)}" checked></td>
      <td>${STAGE_SHORT[b.stage_code]}${b.lots.length > 1 ? `<br><small style="color:#64748b">${b.lots.length} lô cân chung</small>` : ''}</td>
      <td style="white-space:nowrap">${esc(b.room)}</td>
      <td style="white-space:nowrap">${hm(b.start)}</td>
      <td style="white-space:nowrap">${hm(b.end)}<br><small style="color:#64748b">${esc(b.clearning_type)} xong ${hm(b.end_clearning)}</small></td>
      <td>${lots}${warns}</td>
      <td style="white-space:nowrap">${nexts}</td>
    </tr>`;
  }).join('');

  return `<div style="text-align:left;font-size:13px">
    <div style="margin-bottom:6px;color:#475569">Mỗi dòng là một khối cân (lô cùng chiến dịch cân chung, tối đa "Số lô tối đa" của định mức), xếp vào phòng cân
      trống sớm nhất, tránh ngày nghỉ. Bỏ tick khối không muốn xếp. Dòng đỏ: phòng cân không kịp trước giờ bắt đầu công đoạn sau.</div>
    <div style="max-height:55vh;overflow:auto;border:1px solid #e2e8f0;border-radius:6px">
      <table style="width:100%;border-collapse:collapse" class="wa-table">
        <thead style="position:sticky;top:0;background:#f1f5f9;z-index:1"><tr>
          <th style="padding:6px"><input type="checkbox" id="wa-all" checked title="Chọn / bỏ chọn tất cả"></th>
          <th style="padding:6px">Cân</th><th style="padding:6px">Phòng</th><th style="padding:6px">Bắt đầu</th>
          <th style="padding:6px">Kết thúc</th><th style="padding:6px">Lô</th><th style="padding:6px">CĐ sau bắt đầu</th>
        </tr></thead>
        <tbody>${rows}</tbody>
      </table>
    </div>
    <style>.wa-table td{padding:5px 6px;border-top:1px solid #e2e8f0;vertical-align:top}</style>
  </div>`;
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
  const showSuggest = wa.authorization && (on || wa.eligibleCount > 0);

  return (
    <>
      <div
        className={`flex align-items-center gap-2 px-3 py-1 border-round-2xl shadow-1 border-1 cursor-pointer transition-colors ${on
          ? 'bg-orange-100 text-orange-800 border-orange-300 hover:bg-orange-200'
          : 'bg-gray-100 text-gray-700 border-gray-300 hover:bg-gray-200'}`}
        onClick={wa.toggle}
        title={on
          ? `Đang chỉ hiện lô cần chuẩn bị cân${counts ? ` trong khung đang xem: ${counts.need} lô chưa có lịch cân (⚖ cam), ${counts.planned} lô đã có lịch cân (⚖ xanh)` : ''}. Bấm để hiện lại toàn bộ lịch.`
          : 'Chỉ hiện các lô PC/THT/ĐH/BP còn công đoạn cân chưa xong và lịch cân chưa cân xong'}
        style={{ userSelect: 'none', whiteSpace: 'nowrap' }}
      >
        <i className={`pi ${on ? 'pi-eye' : 'pi-eye-slash'}`}></i>
        <span className="font-bold text-sm">
          ⚖ Lô cần cân{on && counts ? ` (${counts.need})` : ''}
        </span>
        {on && counts?.need > 0 && wa.authorization && (
          <i
            className="pi pi-check-square ml-1 hover:text-orange-600"
            title="Chọn hết các lô chưa có lịch cân đang hiện trên lịch"
            onClick={(e) => { e.stopPropagation(); wa.selectNeed(); }}
          ></i>
        )}
      </div>
      {showSuggest && (
        <div
          className="flex align-items-center gap-2 bg-green-100 text-green-800 px-3 py-1 border-round-2xl shadow-1 border-1 border-green-300 cursor-pointer hover:bg-green-200 transition-colors"
          onClick={wa.suggest}
          title="Xếp công đoạn cân của các lô đang chọn vào phòng cân trống sớm nhất (tránh ngày nghỉ); xem bảng gợi ý trước khi ghi"
          style={{ userSelect: 'none', whiteSpace: 'nowrap' }}
        >
          <i className="pi pi-sparkles"></i>
          <span className="font-bold text-sm">Gợi ý lịch cân{wa.eligibleCount > 0 ? ` (${wa.eligibleCount})` : ''}</span>
        </div>
      )}
    </>
  );
}
