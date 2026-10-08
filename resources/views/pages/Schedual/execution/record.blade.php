{{-- Trang "Nhận – Trả Phòng" (trước 08/10/2026 tên "Ghi Nhận Sản Xuất"; route / quyền vẫn là record / layout_production_record): card các phòng mà người đăng nhập đang được phân công lúc này, Nhận phòng / Trả phòng
     như trang Thực Thi Sản Xuất. Trang đứng riêng (không menu) vì role Executor chỉ vào được trang này; dùng được trên
     máy tính bảng đặt tại phòng. Tự làm mới mỗi phút để phòng hiện / ẩn theo giờ ca. --}}
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Nhận – Trả Phòng – {{ $production }}</title>
    <link rel="icon" type="image/png" href="{{ asset('img/iconstella.svg') }}">
    <link rel="stylesheet" href="{{ asset('css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('dataTable/plugins/fontawesome-free/css/all.min.css') }}">
    @include('pages.Schedual.execution._styles')
    <style>
        body { background: #eef1f5; margin: 0; }

        .rec-head {
            position: sticky; top: 0; z-index: 50; display: flex; align-items: center; flex-wrap: wrap; gap: 6px 14px;
            padding: 10px 16px; background: #c5c500; color: #003A4F; box-shadow: 0 2px 5px rgba(0, 0, 0, .2);
        }
        .rec-head h1 { margin: 0 auto 0 0; font-size: 1.3rem; font-weight: 800; }
        .rec-head h1 small { font-size: .85rem; font-weight: 600; opacity: .8; margin-left: 6px; }
        .rec-clock { font-size: 1.25rem; font-weight: 800; font-variant-numeric: tabular-nums; }
        .rec-user { font-weight: 700; line-height: 1.15; text-align: right; }
        .rec-user small { display: block; font-weight: 500; opacity: .8; }
        .rec-btn { color: #003A4F; border: 1px solid #003A4F; border-radius: 6px; padding: 4px 10px; font-size: .85rem; font-weight: 600; white-space: nowrap; }
        .rec-btn:hover { background: #003A4F; color: #fff; text-decoration: none; }

        .rec-body { padding: 14px 14px 40px; }
        .rec-bar { display: flex; align-items: center; flex-wrap: wrap; gap: 8px; margin-bottom: 10px; }
        .rec-shifts { display: flex; align-items: center; flex-wrap: wrap; gap: 6px; }
        .rec-bar .rec-shifts { margin-left: 8px; padding-left: 14px; border-left: 1px solid #cbd5e1; }
        .rec-shifts-label { font-weight: 700; color: var(--navy); }
        .rec-dept-title { font-weight: 800; color: var(--navy); margin: 14px 0 8px; }
        /* EN / QA: tab phân xưởng + ô tìm phòng / thiết bị */
        .rec-tabs { display: flex; flex-wrap: wrap; gap: 6px; margin: 4px 0 12px; }
        .rec-tabs.searching { display: none; }
        .rec-tab { border: 1px solid #cbd5e1; background: #fff; color: var(--navy); border-radius: 999px; padding: 6px 16px; font-weight: 700; }
        .rec-tab.active { background: var(--navy); border-color: var(--navy); color: #fff; }
        .rec-tab-count { display: inline-block; min-width: 22px; margin-left: 4px; padding: 0 6px; border-radius: 999px; background: #e2e8f0; color: #0f172a; font-size: .8rem; }
        .rec-tab.active .rec-tab-count { background: #fff; }
        .rec-search { flex: 1 1 260px; max-width: 420px; margin-left: 8px; }
        .exec-equip-hit { margin-top: 4px; font-size: .78rem; color: #92400e; background: #fef3c7; border-radius: 6px; padding: 2px 6px; }
        .rec-shift { background: #fff; border: 1px solid #d5dde6; border-radius: 999px; padding: 3px 12px; font-size: .85rem; }
        .rec-offline { color: #b91c1c; font-weight: 700; }

        @media (max-width: 576px) {
            .rec-clock { display: none; }
            .rec-head h1 { font-size: 1.05rem; }
        }
    </style>
</head>

<body>
    <div class="exec-page">
        <header class="rec-head">
            <h1><i class="fas fa-clipboard-check"></i> NHẬN – TRẢ PHÒNG <small>{{ isset($boards) ? ($teamName ?: 'Bảo trì') . ' · ' . ($boards->keys()->implode(', ') ?: '—') : (session('user')['production_name'] ?? $production) }}</small></h1>
            <span class="rec-clock js-now"></span>
            <span class="rec-user">
                {{ session('user')['fullName'] }}
                <small>MSNV {{ $employee->code ?? session('user')['userName'] }}</small>
            </span>
            @unless ($executorOnly)
                <a href="{{ route('pages.general.home') }}" class="rec-btn"><i class="fas fa-home"></i> Trang chủ</a>
            @endunless
            <a href="{{ route('logout') }}" class="rec-btn"><i class="fas fa-sign-out-alt"></i> Đăng xuất</a>
        </header>

        <div class="rec-body">
            @if ($readonly)
                <div class="small text-danger font-weight-bold mb-2">
                    <i class="fas fa-lock"></i> Chỉ xem: bạn thuộc phân xưởng {{ session('user')['department'] }},
                    không được thao tác trên phòng của {{ $production }}.
                </div>
            @endif

            <div class="rec-bar">
                <button type="button" id="execRefresh" class="btn btn-sm btn-outline-secondary" title="Tải lại">
                    <i class="fas fa-sync-alt"></i>
                </button>
                <span class="exec-updated">Cập nhật lúc <span id="execUpdatedAt">{{ now()->format('H:i') }}</span> · tự làm mới mỗi phút</span>
                @isset($boards)
                    <input type="search" id="recSearch" class="form-control form-control-sm rec-search" autocomplete="off"
                        placeholder="Tìm phòng hoặc thiết bị (mã / tên trong danh mục BT - TI - HC)...">
                @endisset
                {{-- Dải ca đang phân công (_record_body) được afterRender() chuyển lên cùng dòng này --}}
                <span id="recShiftsSlot"></span>
            </div>

            <div id="execBoard">
                @include('pages.Schedual.execution._record_body')
            </div>
        </div>

        @include('pages.Schedual.execution._receive_modal')
        @include('pages.Schedual.execution._history_modal')
    </div>

    <script src="{{ asset('dataTable/plugins/jquery/jquery.min.js') }}"></script>
    <script src="{{ asset('dataTable/plugins/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('js/sweetalert2.all.min.js') }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const R = {
                index: @json(route('pages.Schedual.record.index')),
                plans: @json(route('pages.Schedual.record.plans')),
                receive: @json(route('pages.Schedual.record.receive')),
                release: @json(route('pages.Schedual.record.release')),
                receiveCleaning: @json(route('pages.Schedual.record.receive_cleaning')),
                undo: @json(route('pages.Schedual.record.undo')),
                history: @json(route('pages.Schedual.record.history')),
            };

            @include('pages.Schedual.execution._live_js')

            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });

            const collapsed = new Set(); // công đoạn đang thu gọn, giữ qua các lần tự làm mới

            function afterRender() {
                // Dải ca render cùng lưới phòng (đổi ca thì đổi theo) → đưa lên dòng "Cập nhật lúc"
                const $shifts = $('#execBoard .rec-shifts');
                $('#recShiftsSlot').html($shifts.length ? $shifts.detach() : '');
                $('.exec-stage').each(function() {
                    $(this).toggleClass('collapsed', collapsed.has($(this).attr('data-stage')));
                });
                applyTabs();
                tick();
                clocks();
            }

            // ===== EN / QA: tab phân xưởng + tìm phòng / thiết bị (giữ qua các lần tự làm mới) =====
            let activeDept = null;
            try { activeDept = localStorage.getItem('recDeptTab'); } catch (e) {}

            function applyTabs() {
                const $tabs = $('[data-dept-tab]');
                if (!$tabs.length) return;
                if (!activeDept || !$tabs.filter(`[data-dept-tab="${activeDept}"]`).length) {
                    activeDept = $tabs.first().attr('data-dept-tab');
                }
                const q = ($('#recSearch').val() || '').trim().toLowerCase();
                const $panes = $('[data-dept-pane]');

                $('.rec-tabs').toggleClass('searching', !!q);
                $tabs.each(function() {
                    $(this).toggleClass('active', $(this).attr('data-dept-tab') === activeDept);
                });

                if (!q) {
                    $('.exec-room-col, .exec-stage').removeClass('d-none');
                    $('.exec-equip-hit').addClass('d-none').empty();
                    $panes.each(function() {
                        $(this).toggleClass('d-none', $(this).attr('data-dept-pane') !== activeDept);
                    });
                    $('.rec-dept-title').addClass('d-none');
                    $('.rec-search-empty').addClass('d-none');
                    return;
                }

                // Đang tìm: lọc trên mọi phân xưởng, hiện tên phân xưởng có kết quả; thiết bị khớp ghi dưới tên phòng
                let any = false;
                $panes.each(function() {
                    let n = 0;
                    $(this).find('.exec-room-col').each(function() {
                        const hit = (this.dataset.search || '').includes(q);
                        $(this).toggleClass('d-none', !hit);
                        if (hit) n++;
                        const $hint = $(this).find('.exec-equip-hit');
                        const equip = (this.dataset.equip || '').split('\n').filter(e => e && e.toLowerCase().includes(q));
                        $hint.toggleClass('d-none', !hit || !equip.length)
                            .text(equip.length ? '🔧 ' + equip.slice(0, 3).join(' | ') + (equip.length > 3 ? ` (+${equip.length - 3})` : '') : '');
                    });
                    $(this).find('.exec-stage').each(function() {
                        $(this).toggleClass('d-none', !$(this).find('.exec-room-col:not(.d-none)').length);
                    });
                    $(this).toggleClass('d-none', !n);
                    $(this).find('.rec-dept-title').toggleClass('d-none', !n);
                    if (n) any = true;
                });
                $('.rec-search-empty').toggleClass('d-none', any);
            }

            $(document).on('click', '[data-dept-tab]', function() {
                activeDept = $(this).attr('data-dept-tab');
                try { localStorage.setItem('recDeptTab', activeDept); } catch (e) {}
                applyTabs();
            });
            $('#recSearch').on('input', applyTabs);

            function refreshBoard(force) {
                if (!force && ($('.modal.show').length || busy)) return;
                $.get(R.index, {
                    partial: 1
                }).done(html => {
                    // Hết phiên đăng nhập: CheckLogin chuyển hướng về trang đăng nhập (cả trang HTML) → tải lại trang
                    if (/<html/i.test(html)) return location.reload();
                    $('#execBoard').html(html);
                    const now = serverNow();
                    $('#execUpdatedAt').removeClass('rec-offline').text(`${pad(now.getHours())}:${pad(now.getMinutes())}`);
                    afterRender();
                }).fail(xhr => {
                    // Hết phiên đăng nhập → về trang đăng nhập
                    if (xhr.status === 401 || xhr.status === 419) return location.reload();
                    $('#execUpdatedAt').addClass('rec-offline').text('mất kết nối, đang thử lại...');
                });
            }

            @include('pages.Schedual.execution._actions_js')

            $(document).on('click', '[data-toggle-stage]', function() {
                const key = $(this).closest('.exec-stage').attr('data-stage');
                if (collapsed.has(key)) collapsed.delete(key); else collapsed.add(key);
                afterRender();
            });
            $('#execRefresh').on('click', () => refreshBoard(true));

            afterRender();
            setInterval(clocks, 1000);
            setInterval(tick, 30000);
            setInterval(() => refreshBoard(false), 60000);
        });
    </script>
</body>

</html>
