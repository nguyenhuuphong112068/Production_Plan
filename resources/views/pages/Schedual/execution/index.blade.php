@extends('layout.master')

@section('topNAV')
    @include('layout.topNAV')
@endsection

@section('leftNAV')
    @include('layout.leftNAV')
@endsection

@php
    $ROS = \App\Services\RoomOccupancyService::class;
@endphp

@section('mainContent')
    @include('pages.Schedual.execution._styles')

    <div class="content-wrapper exec-page"
        style="height: 100vh; overflow-y: auto; overflow-x: hidden; padding: 70px 14px 60px;">

        <div class="exec-toolbar">
            @if ($foreignDepartment)
                <div class="exec-title">
                    <div class="small text-danger font-weight-bold mt-1">
                        <i class="fas fa-lock"></i> Chỉ xem: bạn thuộc phân xưởng {{ $foreignDepartment }},
                        không được thao tác trên phòng của {{ $production }}.
                    </div>
                </div>
            @endif

            <div class="exec-filters">
                <button type="button" class="exec-pill js-filter-stage active" data-stage="all">Tất cả công đoạn</button>
                @foreach ($stages as $group => $rooms)
                    <button type="button" class="exec-pill js-filter-stage" data-stage="{{ $group }}">
                        {{ $ROS::STAGE_GROUPS[$group]['label'] ?? $rooms->first()->stage }}
                    </button>
                @endforeach
            </div>

            <div class="exec-filters mt-2">
                @foreach ($ROS::DISPLAY_ORDER as $s)
                    <button type="button" class="exec-filter-state st-{{ $ROS::STATE_META[$s][0] }}"
                        data-state="{{ $s }}" title="Lọc phòng {{ $ROS::STATE_LABELS[$s] }}">
                        <i class="fas {{ $ROS::STATE_META[$s][1] }}"></i> {{ $ROS::STATE_LABELS[$s] }}
                        <b>{{ $stateCounts->get($s, 0) }}</b>
                    </button>
                @endforeach
                <input type="search" id="execSearch" class="form-control form-control-sm exec-search"
                    placeholder="Tìm phòng, sản phẩm, số lô...">
                <button type="button" id="execRefresh" class="btn btn-sm btn-outline-secondary"
                    title="Tải lại trạng thái các phòng">
                    <i class="fas fa-sync-alt"></i>
                </button>
                <span class="exec-updated">Cập nhật lúc <span id="execUpdatedAt">{{ now()->format('H:i') }}</span></span>
            </div>
        </div>

        <div id="execBoard">
            @include('pages.Schedual.execution._board')
        </div>
    </div>
@endsection

@section('model')
    <div class="exec-page">
        @include('pages.Schedual.execution._receive_modal')
    </div>
@endsection

@section('script')
    <script src="{{ asset('js/sweetalert2.all.min.js') }}"></script>
    <script>
        // jQuery/Bootstrap được layout nạp sau section này → chờ DOMContentLoaded
        document.addEventListener('DOMContentLoaded', function() {
            const R = {
                index: @json(route('pages.Schedual.execution.index')),
                plans: @json(route('pages.Schedual.execution.plans')),
                receive: @json(route('pages.Schedual.execution.receive')),
                release: @json(route('pages.Schedual.execution.release')),
            };
            const STATE_META = @json($ROS::STATE_META);
            const STATE_LABELS = @json($ROS::STATE_LABELS);
            const STATE_ORDER = @json($ROS::DISPLAY_ORDER);

            @include('pages.Schedual.execution._live_js')

            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });


            /* ---------- Lưu bộ lọc cho lần mở sau (không bắt buộc) ---------- */
            const store = {
                get(k, d) {
                    try {
                        const v = localStorage.getItem('exec.' + k);
                        return v == null ? d : JSON.parse(v);
                    } catch (e) {
                        return d;
                    }
                },
                set(k, v) {
                    try {
                        localStorage.setItem('exec.' + k, JSON.stringify(v));
                    } catch (e) {}
                },
            };
            const filter = {
                stage: String(store.get('stage', 'all')),
                state: null,
                q: ''
            };
            const collapsed = new Set(store.get('collapsed', []).map(String));

            /* ---------- Hiển thị ---------- */
            function countChip(s, n) {
                return `<span class="exec-count st-${STATE_META[s][0]}" title="${esc(STATE_LABELS[s])}"><i class="fas ${STATE_META[s][1]}"></i> ${n}</span>`;
            }

            function updateCounts() {
                $('.exec-filter-state').each(function() {
                    $(this).find('b').text($(`.exec-room-col[data-state="${$(this).attr('data-state')}"]`).length);
                });
                $('.exec-stage').each(function() {
                    const $stage = $(this);
                    $stage.find('.exec-stage-counts').html(STATE_ORDER.map(s => {
                        const n = $stage.find(`.exec-room-col[data-state="${s}"]`).length;
                        return n ? countChip(s, n) : '';
                    }).join(''));
                });
            }

            function applyFilters() {
                $('.js-filter-stage').removeClass('active').filter(`[data-stage="${filter.stage}"]`).addClass('active');
                $('.exec-filter-state').removeClass('active').filter(`[data-state="${filter.state}"]`).addClass('active');

                $('.exec-stage').each(function() {
                    const $stage = $(this);
                    const stageOk = filter.stage === 'all' || $stage.attr('data-stage') === filter.stage;
                    let visible = 0;
                    $stage.find('.exec-room-col').each(function() {
                        const $c = $(this);
                        const ok = (!filter.state || $c.attr('data-state') === filter.state) &&
                            (!filter.q || ($c.attr('data-search') || '').includes(filter.q));
                        $c.toggleClass('d-none', !ok);
                        if (ok) visible++;
                    });
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

            function refreshBoard(force) {
                if (!force && ($('.modal.show').length || busy)) return;
                $.get(R.index, {
                    partial: 1
                }).done(html => {
                    $('#execBoard').html(html);
                    const now = serverNow();
                    $('#execUpdatedAt').text(`${pad(now.getHours())}:${pad(now.getMinutes())}`);
                    afterRender();
                });
            }

            @include('pages.Schedual.execution._actions_js')

            /* ---------- Bộ lọc, thu gọn, làm mới ---------- */
            $(document).on('click', '.js-filter-stage', function() {
                filter.stage = String($(this).data('stage'));
                store.set('stage', filter.stage);
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
                collapsed.has(key) ? collapsed.delete(key) : collapsed.add(key);
                store.set('collapsed', [...collapsed]);
                applyFilters();
            });
            $('#execRefresh').on('click', () => refreshBoard(true));

            if (!$(`.js-filter-stage[data-stage="${filter.stage}"]`).length) filter.stage = 'all';
            afterRender();
            setInterval(tick, 30000);
            setInterval(clocks, 1000);
            setInterval(() => refreshBoard(false), 120000);
        });
    </script>
@endsection
