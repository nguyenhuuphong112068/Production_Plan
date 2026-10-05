{{-- JS hiển thị dùng chung (đồng hồ, thời gian đã trôi, thanh tiến trình lô). Include vào trong callback DOMContentLoaded:
     cần jQuery. Trang include tự định nghĩa phần còn lại (bộ lọc, làm mới...). --}}
            // Giờ theo đồng hồ máy chủ (máy người xem có thể lệch giờ)
            const clockOffset = {{ (int) now()->getTimestampMs() }} - Date.now();
            const serverNow = () => new Date(Date.now() + clockOffset);
            const pad = n => String(n).padStart(2, '0');
            const esc = s => $('<div>').text(s == null ? '' : String(s)).html();
            const escAttr = s => esc(s).replace(/"/g, '&quot;');
            const num = v => (Math.round((+v || 0) * 100) / 100).toLocaleString('vi-VN');

            function dur(ms) {
                const m = Math.max(0, Math.floor(ms / 60000));
                const d = Math.floor(m / 1440), h = Math.floor((m % 1440) / 60), mm = m % 60;
                if (d) return `${d} ngày ${h} giờ`;
                if (h) return `${h} giờ ${mm} phút`;
                return `${mm} phút`;
            }

            function tick() {
                const now = serverNow();
                $('.js-since').each(function() {
                    const t = new Date($(this).attr('data-since'));
                    if (!isNaN(t)) $(this).text(dur(now - t));
                });
                $('.js-until').each(function() {
                    const t = new Date($(this).attr('data-until'));
                    if (isNaN(t)) return;
                    const left = t - now;
                    $(this).text(left > 0 ? 'còn ' + dur(left) : 'quá hạn ' + dur(-left));
                });
            }

            // Đồng hồ các hoạt động đang diễn ra (cập nhật mỗi giây). Định dạng giống $clock trong _room_card.
            // data-base: số giây đã chạy ở các lần trước; data-planned: thời lượng theo lịch (chỉ lô đang SX).
            function clockText(sec) {
                const d = Math.floor(sec / 86400), h = Math.floor(sec % 86400 / 3600), m = Math.floor(sec % 3600 / 60);
                return d ? `${d} ngày ${pad(h)}:${pad(m)}` : `${pad(h)}:${pad(m)}:${pad(sec % 60)}`;
            }

            function clocks() {
                const now = serverNow();
                $('.js-clock').each(function() {
                    const t = new Date($(this).attr('data-since'));
                    if (isNaN(t)) return;
                    const sec = (+$(this).attr('data-base') || 0) + Math.max(0, Math.floor((now - t) / 1000));
                    $(this).text(clockText(sec));
                    if ($(this).hasClass('exec-live-big')) $(this).toggleClass('long', sec >= 86400);

                    const planned = +$(this).attr('data-planned');
                    if (!planned) return;
                    $(this).closest('.exec-live-stat').find('.js-time-left').text(sec > planned ? 'vượt ' + dur((sec - planned) * 1000) :
                        (planned - sec < 60 ? 'đúng lịch' : 'còn ' + dur((planned - sec) * 1000)));
                });
                $('.js-timeline').each(function() {
                    renderTimeline(this, now.getTime());
                });
                // Nút Hủy thao tác: đếm ngược thời gian còn được hủy, hết hạn thì bỏ nút
                $('.js-undo').each(function() {
                    const left = Math.ceil((new Date($(this).attr('data-until')) - now) / 1000);
                    if (left <= 0) {
                        $(this).remove();
                        return;
                    }
                    $(this).find('.js-undo-left').text(`${Math.floor(left / 60)}:${pad(left % 60)}`);
                });
                // Giờ hệ thống trong các modal thao tác (thời gian ghi nhận = lúc bấm nút)
                $('.js-now').text(`${pad(now.getHours())}:${pad(now.getMinutes())}:${pad(now.getSeconds())} ${pad(now.getDate())}/${pad(now.getMonth() + 1)}`);
            }

            // Thanh tiến trình thời gian của lô từ BĐSX (lúc mở phòng). Cả thanh = thời lượng theo lịch (vượt lịch thì = tổng
            // thời gian đã dùng, có vạch mốc hết lịch). Các khoảng nối tiếp nhau, dài đúng thời gian: chuẩn bị (BĐSX → BĐCM),
            // lần đã khai báo ghi sản lượng (BĐCM → KT), lần đang chạy / đang chuẩn bị có sọc; khoảng dừng giữa 2 lần chỉ là
            // vạch ngăn (không tính độ dài).
            const fmtMs = ms => {
                const d = new Date(ms);
                return `${pad(d.getHours())}:${pad(d.getMinutes())} ${pad(d.getDate())}/${pad(d.getMonth() + 1)}`;
            };

            function renderTimeline(el, now) {
                const d = $(el).data('tl');
                if (!d) return;
                const runs = (d.prep || []).map(g => ({ s: g[0], e: g[1], prep: true }))
                    .concat(d.segs.map(g => ({ s: g[0], e: g[1], y: g[2] })))
                    .sort((a, b) => a.s - b.s);
                if (d.since) runs.push({ s: d.since, e: Math.max(d.since, now), live: true, prep: !!d.preparing });

                if (!el.childElementCount) {
                    let n = 0; // số thứ tự lần chạy, không tính chuẩn bị
                    const part = r => {
                        if (r.prep) return r.live ?
                            `<div class="exec-tl-run prep" title="${escAttr(`Đang chuẩn bị từ ${fmtMs(r.s)}`)}"></div>` :
                            `<div class="exec-tl-prep" title="${escAttr(`Chuẩn bị ${fmtMs(r.s)} → ${fmtMs(r.e)} (${dur(r.e - r.s)})`)}"></div>`;
                        n++;
                        return r.live ?
                            `<div class="exec-tl-run" title="Lần ${n}: đang chạy từ ${fmtMs(r.s)}"></div>` :
                            `<div class="exec-tl-seg" title="${escAttr(`Lần ${n}: ${fmtMs(r.s)} → ${fmtMs(r.e)} (${dur(r.e - r.s)}) · ${num(r.y)} ${d.unit}`)}"><span>${esc(num(r.y))}</span></div>`;
                    };
                    // Vạch dừng chỉ khi 2 khoảng liền nhau có hở (chuẩn bị → BĐCM nối liền, không có vạch)
                    $(el).html(`<div class="exec-tl-track">
                            ${runs.map(part).join('')}
                            ${runs.slice(1).map((r, i) => r.s > runs[i].e ?
                                `<div class="exec-tl-gap" data-after="${i}" title="${escAttr(`Dừng ${fmtMs(runs[i].e)} → ${fmtMs(r.s)} (${dur(r.s - runs[i].e)})`)}"></div>` : '').join('')}
                            <div class="exec-tl-plan" title="Hết thời lượng theo lịch"></div>
                        </div>
                        <div class="exec-tl-axis"><span>BĐSX ${fmtMs(d.start)}</span><span class="js-tl-end"></span></div>`);
                }

                const planned = d.planned * 1000;
                const runMs = runs.reduce((s, r) => s + r.e - r.s, 0);
                const total = Math.max(planned, runMs, 60000);
                const over = planned > 0 && runMs > planned;
                const $track = $(el).children('.exec-tl-track');

                let x = 0;
                const edges = [];
                $track.children('.exec-tl-prep, .exec-tl-seg, .exec-tl-run').each(function(i) {
                    const w = (runs[i].e - runs[i].s) / total * 100;
                    $(this).css({ left: x + '%', width: w + '%' }).toggleClass('nolabel', w < 12);
                    x += w;
                    edges.push(x);
                });
                $track.children('.exec-tl-gap').each(function() {
                    $(this).css('left', edges[+$(this).attr('data-after')] + '%');
                });
                $track.children('.exec-tl-run').toggleClass('over', over);
                $track.children('.exec-tl-plan').toggle(over).css('left', planned / total * 100 + '%');

                $(el).find('.js-tl-end').text(!planned ? 'chưa có thời lượng theo lịch' :
                    (d.since && !over ? 'dự kiến xong ' + fmtMs(now + planned - runMs) : 'theo lịch ' + dur(planned)));
            }
