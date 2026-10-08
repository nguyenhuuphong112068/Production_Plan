/**
 * Plugin Kiểm soát tồn BTP khi sắp lịch tự động (backend: app/Plugins/WipControl).
 *
 * FullCalender.jsx chỉ gọi 4 hàm:
 *   wipControlSectionHtml()  chèn khối nhập Max vào modal Sắp lịch tự động
 *   initWipControl()         nạp giá trị đã lưu của phân xưởng (didOpen)
 *   collectWipControl()      đọc giá trị khi bấm Chạy (preConfirm)
 *   runWipControl(payload)   chạy sau khi scheduleAll xong, hiện báo cáo
 * Plugin tắt ở backend thì /settings trả 404, khối tự ẩn và collect trả null.
 */
import axios from 'axios';
import Swal from 'sweetalert2';

const GROUPS = [
  { key: 'max_dh', label: 'Tồn chờ Định hình (ĐH)' },
  { key: 'max_bp', label: 'Tồn chờ Bao phim (BP)' },
  { key: 'max_dg', label: 'Tồn chờ Đóng gói / Ép vỉ (ĐG)' },
];

const GROUP_NAMES = { DH: 'Chờ Định hình', BP: 'Chờ Bao phim', DG: 'Chờ Đóng gói' };

const METHODS = {
  gate: 'Ngưng nguồn (chờ tồn giảm)',
  swap: 'Đổi chỗ',
  stretch: 'Giãn lịch',
  reschedule: 'Sắp lại lô',
};
const PULL_METHODS = { gate: 'Ngưng nguồn: chạy thay', swap: 'Đổi chỗ' };

const STAGE_SHORT = { 3: 'PC', 4: 'THT', 5: 'ĐH', 6: 'BP', 7: 'ĐG' };

const STATUS = {
  ok: { icon: 'success', text: 'Đạt: tồn mọi nhóm đã nằm trong Max' },
  hard_date: { icon: 'warning', text: 'Dừng để không vi phạm ngày NL/BB' },
  infeasible: { icon: 'warning', text: 'Không khả thi: không còn lô nào lùi được để giảm tồn (thường do năng lực công đoạn sau thấp hơn đầu nguồn)' },
  max_iterations: { icon: 'warning', text: 'Hết số vòng lặp, tồn vẫn còn vượt Max' },
  timeout: { icon: 'warning', text: 'Hết thời gian cho phép, tồn vẫn còn vượt Max' },
  skipped: { icon: 'info', text: 'Không chạy kiểm soát tồn' },
};

let enabled = false;

const fmt = (n) => (n === null || n === undefined ? '—' : Number(n).toLocaleString('vi-VN', { maximumFractionDigits: 0 }));
const fmtTime = (s) => (s ? dayjsLike(s) : '—');
const esc = (s) => String(s ?? '').replace(/[&<>"]/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]));

// "2026-10-06 13:45:00" -> "06/10 13:45"
function dayjsLike(s) {
  const m = String(s).match(/^(\d{4})-(\d{2})-(\d{2})(?:[ T](\d{2}):(\d{2}))?/);
  if (!m) return esc(s);
  return m[4] ? `${m[3]}/${m[2]} ${m[4]}:${m[5]}` : `${m[3]}/${m[2]}/${m[1]}`;
}

export function wipControlSectionHtml() {
  // Dùng chung lớp asm-* của modal Sắp lịch tự động (FullCalender.jsx); các style
  // inline còn lại để khối vẫn đọc được nếu modal đổi lớp
  const input = 'height:32px;padding:0 8px;border:1px solid #d9d9d9;border-radius:4px;font-size:13px;width:134px;flex:0 0 134px';
  return `
    <div id="wip-control-section" class="asm-section" style="display:none">
      <div class="asm-row">
        <label class="asm-label" for="wip-enabled">Giới hạn tồn BTP (viên)</label>
        <label class="switch">
          <input id="wip-enabled" type="checkbox">
          <span class="slider round"></span>
          <span class="switch-labels"><span class="off">No</span><span class="on">Yes</span></span>
        </label>
      </div>
      <div id="wip-fields" style="display:none;flex-direction:column;gap:6px">
        <p class="asm-hint">
          Để trống = không giới hạn. Vượt Max thì lùi các công đoạn tạo ra tồn của lô có ngày cần hàng muộn nhất; công đoạn tiêu thụ giữ nguyên lịch.
        </p>
        ${GROUPS.map((g) => `
          <div class="asm-row">
            <label class="asm-label" for="wip-${g.key}" style="font-weight:500">${g.label}</label>
            <input id="wip-${g.key}" type="number" min="0" step="1" placeholder="Không giới hạn" class="wip-input" style="${input}">
          </div>`).join('')}
        <p class="asm-hint">
          Luôn giữ đúng các ngày NL/BB (được phép cân, HH NL chính, HH BB, PC / THT / ĐH / BP trước): lô bị lùi không được vi phạm các ngày này.<br/>
          Ngưng nguồn: lô chỉ vào công đoạn nguồn khi tồn nhóm nó sắp vào còn dưới Max, phòng chạy thay lô đi nhóm không cài Max hoặc để trống; lô vì thế trễ thì công đoạn sau lùi theo.<br/>
          Chờ ĐH → ngưng THT (PC). Chờ BP → ngưng ĐH lô bao phim. Chờ ĐG → ngưng BP và ĐH lô không bao phim. Cài 1, 2 hoặc cả 3 nhóm.
        </p>
      </div>
    </div>`;
}

export function initWipControl() {
  const section = document.getElementById('wip-control-section');
  if (!section) return;

  const toggle = document.getElementById('wip-enabled');
  const fields = document.getElementById('wip-fields');
  toggle.addEventListener('change', () => {
    fields.style.display = toggle.checked ? 'flex' : 'none';
  });

  axios.get('/Schedual/wip-control/settings')
    .then(({ data }) => {
      if (!data?.enabled) return;
      enabled = true;
      section.style.display = '';

      GROUPS.forEach((g) => {
        const el = document.getElementById(`wip-${g.key}`);
        if (el && data[g.key] !== null && data[g.key] !== undefined) el.value = Math.round(data[g.key]);
      });

      // Đã từng cài Max thì bật sẵn
      const hasLimit = GROUPS.some((g) => data[g.key] !== null && data[g.key] !== undefined);
      toggle.checked = hasLimit;
      fields.style.display = hasLimit ? 'flex' : 'none';
    })
    .catch(() => {
      enabled = false;
      section.style.display = 'none';
    });
}

/** null = không chạy plugin (tắt, hoặc không có Max nào) */
export function collectWipControl() {
  if (!enabled || !document.getElementById('wip-enabled')?.checked) return null;

  const values = {};
  GROUPS.forEach((g) => {
    const v = document.getElementById(`wip-${g.key}`)?.value?.trim();
    values[g.key] = v === '' || v === undefined ? null : Number(v);
  });
  values.lock_validation = false;   // lô thẩm định luôn được lùi

  return values;
}

export function hasWipLimits(values) {
  return !!values && GROUPS.some((g) => values[g.key] !== null && values[g.key] > 0);
}

export function runWipControl(payload) {
  Swal.fire({
    title: 'Đang kiểm soát tồn BTP...',
    html: 'Đo tồn theo lịch vừa sắp, lùi các lô vượt Max và sắp lại.<br/>Có thể mất vài phút.',
    allowOutsideClick: false,
    didOpen: () => Swal.showLoading(),
  });

  return axios.post('/Schedual/wip-control/run', payload, { timeout: 1200000 })
    .then(({ data }) => showReport(data))
    .catch((err) => {
      Swal.fire({
        icon: 'error',
        title: 'Kiểm soát tồn BTP lỗi',
        html: `${esc(err.response?.data?.message || err.message)}<br/><br/>Lịch sắp tự động vẫn được giữ nguyên.`,
      });
    });
}

function groupTable(title, summary) {
  if (!summary) return '';
  return `
    <div style="flex:1;min-width:260px">
      <div style="font-weight:700;margin-bottom:4px">${title}</div>
      <table class="table table-sm table-bordered" style="font-size:12px;margin:0">
        <thead><tr><th>Nhóm</th><th>Max</th><th>Đỉnh tồn</th><th>Số ngày vượt</th></tr></thead>
        <tbody>
          ${summary.groups.map((g) => `
            <tr>
              <td>${GROUP_NAMES[g.group] ?? esc(g.name)}</td>
              <td style="text-align:right">${fmt(g.max)}</td>
              <td style="text-align:right;${g.peak > g.max ? 'color:#c0392b;font-weight:700' : ''}">${fmt(g.peak)}${g.peak_date ? ` <small>(${fmtTime(g.peak_date)})</small>` : ''}${g.peak_stuck ? `<br/><small style="color:#7f8c8d;font-weight:400" title="Hàng đã vào kho trước ngày sắp lịch: lùi lịch không bớt được">đã vào kho: ${fmt(g.peak_stuck)}</small>` : ''}</td>
              <td style="text-align:center">${g.violation_days}</td>
            </tr>`).join('')}
        </tbody>
      </table>
    </div>`;
}

function showReport(data) {
  const st = STATUS[data.status] ?? STATUS.skipped;

  if (data.status === 'skipped') {
    const extra = data.out_of_scope?.length
      ? `<br/>Nhóm ${data.out_of_scope.map((g) => GROUP_NAMES[g] ?? g).join(', ')} nằm ngoài phạm vi bước sắp lịch nên không xét.`
      : '';
    return Swal.fire({ icon: 'info', title: 'Kiểm soát tồn BTP', html: `${esc(data.message || st.text)}${extra}` });
  }

  const delayed = data.delayed ?? [];
  const late = delayed.filter((d) => d.late);
  const unscheduled = delayed.filter((d) => d.unscheduled);

  const rounds = (data.rounds ?? []).map((r) => `
    <tr><td style="text-align:center">${r.round}</td><td style="text-align:center">${r.violation_days}</td>
    <td style="text-align:right">${fmt(r.total_excess)}</td>
    <td style="text-align:center">${(r.mix_delayed ?? 0) + (r.mix_pulled ?? 0) ? `${r.mix_delayed ?? 0} / ${r.mix_pulled ?? 0}` : 0}</td>
    <td style="text-align:center">${r.swapped ?? 0}</td><td style="text-align:center">${r.stretched ?? 0}</td>
    <td style="text-align:center">${r.moved_lots}</td><td style="text-align:center">${r.reverted_lots ?? 0}</td></tr>`).join('');

  const delayedRows = delayed.map((d) => `
    <tr style="${d.late ? 'background:#fdecea' : d.unscheduled ? 'background:#fff4e5' : ''}">
      <td>${esc(d.batch)}</td>
      <td>${esc(d.product_name ?? d.intermediate ?? '')}</td>
      <td>${GROUP_NAMES[d.group] ?? esc(d.group)}${d.seed ? '' : ' <small>(cùng campaign / phòng)</small>'}</td>
      <td>${METHODS[d.method] ?? esc(d.method)}${d.stage ? ` <small>(${STAGE_SHORT[d.stage] ?? d.stage})</small>` : ''}</td>
      <td>${fmtTime(d.old_start)}</td>
      <td>${d.unscheduled && !d.new_start ? '<b style="color:#e67e22">Chưa sắp được</b>' : fmtTime(d.new_start)}</td>
      <td>${fmtTime(d.last_end)}</td>
      <td>${fmtTime(d.expected_date)}${d.late ? ' <b style="color:#c0392b">TRỄ</b>' : ''}${d.no_consumer ? ' <small style="color:#7f8c8d">(công đoạn sau chưa có lịch)</small>' : ''}</td>
    </tr>`).join('');

  const shifted = data.bp_shifted ?? [];
  const shiftedLate = shifted.filter((s) => s.late);

  const reasons = Object.entries(data.skipped_by_reason ?? {})
    .sort((a, b) => b[1] - a[1])
    .map(([reason, count]) => `<li>${esc(reason)}: <b>${count}</b> lô</li>`).join('');

  const warnings = [];
  if (late.length) warnings.push(`<b style="color:#c0392b">${late.length} lô xong phần đã lùi sau ngày cần hàng</b>${late.every((d) => d.no_consumer) ? ' (đều là lô công đoạn sau chưa có lịch)' : ''}.`);
  if (unscheduled.length) warnings.push(`<b style="color:#e67e22">${unscheduled.length} lô chưa sắp lại được</b>, cần xử lý tay.`);
  if (data.gate_warnings?.length) warnings.push(`<b style="color:#e67e22">Dữ liệu bất thường (giữ nguyên, cần kiểm tra Nhận / Trả phòng):</b><br/>${data.gate_warnings.map(esc).join('<br/>')}`);
  if (data.hard_blocked?.length) warnings.push(`<b style="color:#c0392b">Đã huỷ vòng cuối vì làm vi phạm ngày NL/BB không được vi phạm:</b><br/>${data.hard_blocked.map(esc).join('<br/>')}`);
  if (data.hard_forced) warnings.push(`${data.hard_forced} lô phải chạy đúng hạn (không ngưng) để giữ ngày NL/BB.`);
  if (data.gate_error) warnings.push(`<b style="color:#c0392b">Không ngưng nguồn được:</b> ${esc(data.gate_error)}. Lịch giữ nguyên ở bước này.`);
  if (shifted.length) warnings.push(`<b style="color:#e67e22">${new Set(shifted.map((s) => s.plan_master_id)).size} lô bị lùi công đoạn sau theo</b>${shiftedLate.length ? `, <b style="color:#c0392b">${new Set(shiftedLate.map((s) => s.plan_master_id)).size} lô xong sau ngày cần hàng</b>` : ''}: xem bảng bên dưới.`);
  if (data.maintenance_shifted?.length) {
    const d = (s) => s.substring(8, 10) + '/' + s.substring(5, 7) + ' ' + s.substring(11, 16);
    warnings.push(`Lịch BT-HC-TI bị lô sản xuất xếp đè / trễ hạn BT:<br/>${data.maintenance_shifted
      .map(m => `${esc(m.room)} – ${esc(m.title)}: ${m.to === m.from ? 'giữ ' + d(m.from) : d(m.from) + ' → ' + d(m.to)}${m.to < m.from ? ' (dời sớm để kịp hạn)' : ''}${m.late
        ? ` <b style="color:#c0392b">– trễ hạn BT ${esc(m.due.split('-').reverse().join('/'))}</b>` : ''}`).join('<br/>')}`);
  }
  if (data.new_overdue?.length) warnings.push(`Campaign mới bị quá hạn biệt trữ: <b>${data.new_overdue.map(esc).join(', ')}</b>.`);
  if (delayed.length) warnings.push('Công đoạn tiêu thụ trở về sau giữ nguyên lịch. Đổi chỗ / giãn lịch chỉ dời lô trong cùng phòng; lô "Sắp lại lô" có thể đã lùi Pha chế: kiểm tra và chạy lại lịch <b>Cân NL (CNL)</b>.');
  if ((data.pulled ?? []).length) warnings.push(`${data.pulled.length} lô được kéo lên sớm (đi sang nhóm không cài Max): tồn của nhóm đó sẽ tăng.`);
  if (data.undo_code) warnings.push(`Đã sao lưu lịch trước khi lùi lô, mã <b>${esc(data.undo_code)}</b>: muốn quay lại thì mở Sắp lịch tự động → chọn mã này ở mục Khôi phục.`);

  return Swal.fire({
    icon: st.icon,
    title: 'Kết quả kiểm soát tồn BTP',
    width: '1200px',
    customClass: { htmlContainer: 'cfg-html-left' },
    html: `
      <div style="text-align:left;font-size:13px">
        <div style="margin-bottom:8px">${data.iterations} vòng · ${data.duration_seconds}s</div>
        ${warnings.length ? `<ul style="margin:0 0 10px 18px">${warnings.map((w) => `<li>${w}</li>`).join('')}</ul>` : ''}
        <div style="display:flex;gap:16px;flex-wrap:wrap;margin-bottom:10px">
          ${groupTable('Trước', data.before)}
          ${groupTable('Sau', data.after)}
        </div>
        ${rounds ? `
          <div style="font-weight:700;margin:6px 0 4px">Từng vòng</div>
          <table class="table table-sm table-bordered" style="font-size:12px;max-width:820px">
            <thead><tr><th>Vòng</th><th>Số ngày vượt</th><th>Tổng lượng vượt (viên)</th><th>Ngưng nguồn (lùi / chạy thay)</th><th>Đổi chỗ</th><th>Giãn lịch</th><th>Sắp lại lô</th><th>Trả về lịch cũ</th></tr></thead>
            <tbody>${rounds}</tbody>
          </table>` : ''}
        ${delayed.length ? `
          <div style="font-weight:700;margin:6px 0 4px">Lô bị lùi (${delayed.length})</div>
          <div style="max-height:320px;overflow:auto">
            <table class="table table-sm table-bordered" style="font-size:12px">
              <thead style="position:sticky;top:0;background:#fff"><tr>
                <th>Số lô</th><th>Sản phẩm</th><th>Lý do (nhóm vượt)</th><th>Cách dời</th><th>Bắt đầu cũ</th><th>Bắt đầu mới</th><th>Xong công đoạn cuối</th><th>Ngày cần hàng</th>
              </tr></thead>
              <tbody>${delayedRows}</tbody>
            </table>
          </div>` : ''}
        ${(data.pulled ?? []).length ? `
          <div style="font-weight:700;margin:10px 0 4px">Lô được kéo lên sớm / chạy thay (${data.pulled.length})</div>
          <div style="max-height:220px;overflow:auto">
            <table class="table table-sm table-bordered" style="font-size:12px;max-width:820px">
              <thead style="position:sticky;top:0;background:#fff"><tr><th>Số lô</th><th>Sản phẩm</th><th>Cách kéo</th><th>Bắt đầu cũ</th><th>Bắt đầu mới</th><th>Ngày cần hàng</th></tr></thead>
              <tbody>${data.pulled.map((p) => `<tr><td>${esc(p.batch)}</td><td>${esc(p.product_name ?? '')}</td><td>${PULL_METHODS[p.method] ?? esc(p.method)}${p.stage ? ` <small>(${STAGE_SHORT[p.stage] ?? p.stage})</small>` : ''}</td>
                <td>${p.old_start ? fmtTime(p.old_start) : '<i>chưa có lịch</i>'}</td><td>${fmtTime(p.new_start)}</td><td>${fmtTime(p.expected_date)}</td></tr>`).join('')}</tbody>
            </table>
          </div>` : ''}
        ${shifted.length ? `
          <div style="font-weight:700;margin:10px 0 4px">Công đoạn sau bị lùi theo (${shifted.length})</div>
          <div style="max-height:260px;overflow:auto">
            <table class="table table-sm table-bordered" style="font-size:12px;max-width:900px">
              <thead style="position:sticky;top:0;background:#fff"><tr><th>Số lô</th><th>Sản phẩm</th><th>Công đoạn</th><th>Bắt đầu cũ</th><th>Bắt đầu mới</th><th>Xong công đoạn cuối</th><th>Ngày cần hàng</th></tr></thead>
              <tbody>${shifted.map((s) => `<tr style="${s.late ? 'background:#fdecea' : ''}"><td>${esc(s.batch)}</td><td>${esc(s.product_name ?? '')}</td><td>${STAGE_SHORT[s.stage] ?? s.stage}</td>
                <td>${fmtTime(s.old_start)}</td><td>${fmtTime(s.new_start)}</td><td>${fmtTime(s.last_end)}</td><td>${fmtTime(s.expected_date)}${s.late ? ' <b style="color:#c0392b">TRỄ</b>' : ''}</td></tr>`).join('')}</tbody>
            </table>
          </div>` : ''}
        ${reasons ? `
          <div style="font-weight:700;margin:10px 0 4px">Lô nằm trong kho ngày vượt nhưng không lùi được</div>
          <ul style="margin:0 0 0 18px">${reasons}</ul>` : ''}
        <div style="margin-top:10px"><a href="/Schedual/wip_coverage" target="_blank">Xem biểu đồ tồn BTP ↗</a></div>
      </div>`,
  });
}
