{{-- Trang công khai "Trạng Thái Sản Xuất": xem (chỉ đọc) trạng thái hiện tại các phòng của 1 phân xưởng theo dữ liệu
     Thực Thi Sản Xuất. Không cần đăng nhập; tự làm mới, đồng hồ chạy theo giây. Bộ lọc công đoạn lưu trên URL (?stage=)
     để màn hình treo tường mở thẳng đúng công đoạn. --}}
@php
    $PES = \App\Services\ProductionExecutionService::class;
@endphp
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trạng Thái Sản Xuất – {{ $production }}</title>
    <link rel="icon" type="image/png" href="{{ asset('img/iconstella.svg') }}">
    <link rel="stylesheet" href="{{ asset('css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('dataTable/plugins/fontawesome-free/css/all.min.css') }}">
    @include('pages.Schedual.execution._styles')
    <style>
        body { background: #eef1f5; margin: 0; }

        .pub-head {
            position: sticky; top: 0; z-index: 50; display: flex; align-items: center; flex-wrap: wrap;
            padding: 10px 16px; background: #c5c500; color: #003A4F; box-shadow: 0 2px 5px rgba(0, 0, 0, .2);
        }
        .pub-head h1 { margin: 0 auto 0 0; font-size: 1.3rem; font-weight: 800; }
        .pub-head h1 small { font-size: .85rem; font-weight: 600; opacity: .8; margin-left: 6px; }
        .pub-clock { font-size: 1.25rem; font-weight: 800; font-variant-numeric: tabular-nums; margin: 0 16px; }
        .pub-login { color: #003A4F; border: 1px solid #003A4F; border-radius: 6px; padding: 4px 10px; font-size: .85rem; font-weight: 600; white-space: nowrap; }
        .pub-login:hover { background: #003A4F; color: #fff; text-decoration: none; }

        .pub-body { padding: 14px 14px 40px; }
        .pub-depts { display: flex; flex-wrap: wrap; margin: -4px -4px 10px; }
        .pub-dept {
            margin: 4px; padding: 6px 14px; border-radius: 10px; background: #fff; border: 1px solid #d5dde6; color: var(--navy);
            font-weight: 800; line-height: 1.15; text-decoration: none;
        }
        .pub-dept small { display: block; font-weight: 500; color: #6b7785; font-size: .72rem; }
        .pub-dept:hover { border-color: var(--navy); text-decoration: none; color: var(--navy); }
        .pub-dept.active { background: var(--navy); border-color: var(--navy); color: #fff; }
        .pub-dept.active small { color: rgba(255, 255, 255, .8); }
        .pub-offline { color: #b91c1c; font-weight: 700; }

        @media (max-width: 576px) {
            .pub-clock { display: none; }
            .pub-head h1 { font-size: 1.05rem; }
        }
    </style>
</head>

<body>
    <div class="exec-page">
        <header class="pub-head">
            <h1><i class="fas fa-industry"></i> TRẠNG THÁI SẢN XUẤT <small>{{ $departments[$production] ?? $production }}</small></h1>
            <span class="pub-clock js-now"></span>
            <a href="{{ route('login') }}" class="pub-login"><i class="fas fa-sign-in-alt"></i> Đăng nhập</a>
        </header>

        <div class="pub-body">
            <div class="exec-toolbar">
                <div class="pub-depts">
                    @foreach ($departments as $code => $name)
                        <a href="{{ route('pages.execution.public', ['production_code' => $code]) }}"
                            class="pub-dept {{ $code === $production ? 'active' : '' }}">{{ $code }}<small>{{ $name }}</small></a>
                    @endforeach
                </div>

                <div class="exec-filters">
                    <button type="button" class="exec-pill js-filter-stage active" data-stage="all">Tất cả công đoạn</button>
                    @foreach ($stages as $group => $rooms)
                        <button type="button" class="exec-pill js-filter-stage" data-stage="{{ $group }}">
                            {{ $PES::STAGE_GROUPS[$group]['label'] ?? $rooms->first()->stage }}
                        </button>
                    @endforeach
                </div>

                <div class="exec-filters mt-2">
                    @foreach ($PES::DISPLAY_ORDER as $s)
                        <button type="button" class="exec-filter-state st-{{ $PES::STATE_META[$s][0] }}" data-state="{{ $s }}"
                            title="Lọc phòng {{ $PES::STATE_LABELS[$s] }}">
                            <i class="fas {{ $PES::STATE_META[$s][1] }}"></i> {{ $PES::STATE_LABELS[$s] }} <b>{{ $stateCounts->get($s, 0) }}</b>
                        </button>
                    @endforeach
                    <input type="search" id="execSearch" class="form-control form-control-sm exec-search"
                        placeholder="Tìm phòng, sản phẩm, số lô, nhân sự...">
                    <button type="button" id="execRefresh" class="btn btn-sm btn-outline-secondary" title="Tải lại trạng thái các phòng">
                        <i class="fas fa-sync-alt"></i>
                    </button>
                    <span class="exec-updated">Cập nhật lúc <span id="execUpdatedAt">{{ now()->format('H:i') }}</span> · tự làm mới mỗi phút</span>
                </div>
            </div>

            <div id="execBoard">
                @include('pages.Schedual.execution._board')
            </div>
        </div>
    </div>

    <script src="{{ asset('dataTable/plugins/jquery/jquery.min.js') }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            @include('pages.Schedual.execution._live_js')

            const PARTIAL_URL = @json(route('pages.execution.public', ['production_code' => $production, 'partial' => 1]));
            const params = new URLSearchParams(location.search);
            const filter = { stage: params.get('stage') || 'all', state: null, q: '' };
            const collapsed = new Set(); // công đoạn đang thu gọn, giữ qua các lần tự làm mới

            function updateCounts() {
                $('.exec-filter-state').each(function() {
                    $(this).find('b').text($(`.exec-room-col[data-state="${$(this).attr('data-state')}"]`).length);
                });
            }

            function applyFilters() {
                $('.js-filter-stage').removeClass('active').filter(`[data-stage="${filter.stage}"]`).addClass('active');
                $('.exec-filter-state').removeClass('active').filter(`[data-state="${filter.state}"]`).addClass('active');
                $('.exec-stage').each(function() {
                    const $stage = $(this);
                    let visible = 0;
                    $stage.find('.exec-room-col').each(function() {
                        const ok = (!filter.state || $(this).attr('data-state') === filter.state) &&
                            (!filter.q || ($(this).attr('data-search') || '').includes(filter.q));
                        $(this).toggleClass('d-none', !ok);
                        if (ok) visible++;
                    });
                    const stageOk = filter.stage === 'all' || $stage.attr('data-stage') === filter.stage;
                    $stage.toggleClass('d-none', !stageOk || (visible === 0 && !!(filter.state || filter.q)));
                    $stage.find('.exec-stage-empty').toggleClass('d-none', visible > 0);
                    $stage.toggleClass('collapsed', collapsed.has($stage.attr('data-stage')));
                });
            }

            function afterRender() {
                updateCounts();
                applyFilters();
                tick();
                clocks();
            }

            function refreshBoard() {
                $.get(PARTIAL_URL).done(html => {
                    $('#execBoard').html(html);
                    $('#execUpdatedAt').removeClass('pub-offline').text(`${pad(serverNow().getHours())}:${pad(serverNow().getMinutes())}`);
                    afterRender();
                }).fail(() => {
                    $('#execUpdatedAt').addClass('pub-offline').text('mất kết nối, đang thử lại...');
                });
            }

            $(document).on('click', '.js-filter-stage', function() {
                filter.stage = String($(this).data('stage'));
                // Ghi công đoạn lên URL để màn hình treo tường mở lại đúng công đoạn
                const url = new URL(location.href);
                if (filter.stage === 'all') url.searchParams.delete('stage'); else url.searchParams.set('stage', filter.stage);
                history.replaceState(null, '', url);
                applyFilters();
            });
            $(document).on('click', '.exec-filter-state', function() {
                const s = $(this).attr('data-state');
                filter.state = filter.state === s ? null : s;
                applyFilters();
            });
            $('#execSearch').on('input', function() {
                filter.q = this.value.toLowerCase().trim();
                applyFilters();
            });
            $(document).on('click', '[data-toggle-stage]', function() {
                const key = $(this).closest('.exec-stage').attr('data-stage');
                if (collapsed.has(key)) collapsed.delete(key); else collapsed.add(key);
                applyFilters();
            });
            $('#execRefresh').on('click', refreshBoard);

            afterRender();
            setInterval(clocks, 1000);
            setInterval(tick, 30000);
            setInterval(refreshBoard, 60000);
        });
    </script>
</body>

</html>
