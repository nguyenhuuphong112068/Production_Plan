{{-- Trang "Ghi Nhận Sản Xuất": card các phòng mà người đăng nhập đang được phân công lúc này, Nhận phòng / Trả phòng
     như trang Thực Thi Sản Xuất. Trang đứng riêng (không menu) vì role Executor chỉ vào được trang này; dùng được trên
     máy tính bảng đặt tại phòng. Tự làm mới mỗi phút để phòng hiện / ẩn theo giờ ca. --}}
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Ghi Nhận Sản Xuất – {{ $production }}</title>
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
        .rec-shifts { display: flex; align-items: center; flex-wrap: wrap; gap: 6px; margin-bottom: 12px; }
        .rec-shifts-label { font-weight: 700; color: var(--navy); }
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
            <h1><i class="fas fa-clipboard-check"></i> GHI NHẬN SẢN XUẤT <small>{{ session('user')['production_name'] ?? $production }}</small></h1>
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
            </div>

            <div id="execBoard">
                @include('pages.Schedual.execution._record_body')
            </div>
        </div>

        @include('pages.Schedual.execution._receive_modal')
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
            };

            @include('pages.Schedual.execution._live_js')

            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });

            const collapsed = new Set(); // công đoạn đang thu gọn, giữ qua các lần tự làm mới

            function afterRender() {
                $('.exec-stage').each(function() {
                    $(this).toggleClass('collapsed', collapsed.has($(this).attr('data-stage')));
                });
                tick();
                clocks();
            }

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
