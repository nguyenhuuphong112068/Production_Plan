{{-- JS hiển thị dùng chung (đồng hồ, thời gian đã trôi). Include vào trong callback DOMContentLoaded:
     cần jQuery. Trang include tự định nghĩa phần còn lại (bộ lọc, làm mới...). --}}
            // Giờ theo đồng hồ máy chủ (máy người xem có thể lệch giờ)
            const clockOffset = {{ (int) now()->getTimestampMs() }} - Date.now();
            const serverNow = () => new Date(Date.now() + clockOffset);
            const pad = n => String(n).padStart(2, '0');
            const esc = s => $('<div>').text(s == null ? '' : String(s)).html();
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
            }

            // Đồng hồ phòng bận (cập nhật mỗi giây). Định dạng giống $clock trong _room_card.
            // data-planned: thời lượng giữ phòng theo lịch (giây).
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
                    $(this).closest('.exec-live').find('.exec-progress > div').css('width', Math.min(100, sec / planned * 100) + '%');
                });
                // Giờ hệ thống trong modal / đầu trang (thời gian ghi nhận = lúc bấm nút)
                $('.js-now').text(`${pad(now.getHours())}:${pad(now.getMinutes())}:${pad(now.getSeconds())} ${pad(now.getDate())}/${pad(now.getMonth() + 1)}`);
            }
