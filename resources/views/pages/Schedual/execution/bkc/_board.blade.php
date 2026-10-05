{{-- Lưới card công đoạn → card phòng. Trả riêng khi tự làm mới (?partial=1). --}}
@php
    $PES = \App\Services\ProductionExecutionService::class;
@endphp

@forelse ($stages as $group => $rooms)
    @php
        $meta = $PES::STAGE_GROUPS[$group] ?? ['label' => $rooms->first()->stage, 'icon' => 'fa-industry', 'grad' => 'g-slate'];
        $counts = $rooms->countBy(fn($r) => $r->st->display);
    @endphp
    <div class="exec-stage" data-stage="{{ $group }}">
        <div class="exec-stage-head {{ $meta['grad'] }}" data-toggle-stage>
            <div class="exec-stage-icon"><i class="fas {{ $meta['icon'] }}"></i></div>
            <div class="exec-stage-title">
                {{ $meta['label'] }}
                <small>{{ $rooms->count() }} phòng</small>
            </div>
            <div class="exec-stage-counts">
                @foreach ($PES::DISPLAY_ORDER as $s)
                    @if ($counts->get($s))
                        <span class="exec-count st-{{ $PES::STATE_META[$s][0] }}" title="{{ $PES::STATE_LABELS[$s] }}">
                            <i class="fas {{ $PES::STATE_META[$s][1] }}"></i> {{ $counts->get($s) }}
                        </span>
                    @endif
                @endforeach
            </div>
            <i class="fas fa-chevron-up exec-stage-caret"></i>
        </div>
        <div class="exec-stage-body">
            <div class="row">
                @foreach ($rooms as $room)
                    @include('pages.Schedual.execution._room_card', ['room' => $room, 'readonly' => $readonly ?? false])
                @endforeach
            </div>
            <div class="exec-stage-empty d-none">Không có phòng phù hợp bộ lọc.</div>
        </div>
    </div>
@empty
    <div class="exec-empty">
        <i class="fas fa-door-closed fa-3x mb-3"></i>
        <div>Phân xưởng {{ $production }} chưa có phòng sản xuất nào đang hoạt động.</div>
    </div>
@endforelse
