{{-- Lưới card công đoạn → card phòng. Trả riêng khi tự làm mới (?partial=1). --}}
@php
    $ROS = \App\Services\RoomOccupancyService::class;
@endphp

{{-- Phân xưởng đang sắp lịch tự động / khóa thủ công: Nhận / Trả phòng bị chặn (server trả 423) --}}
@if ($lockMessage = \App\Services\SchedulingLock::executionBlock($production))
    <div class="alert alert-warning font-weight-bold mb-3"><i class="fas fa-lock"></i> {{ $lockMessage }}</div>
@endif

@forelse ($stages as $group => $rooms)
    @php
        $meta = $ROS::STAGE_GROUPS[$group] ?? ['label' => $rooms->first()->stage, 'icon' => 'fa-industry', 'grad' => 'g-slate'];
        $counts = $rooms->countBy(fn($r) => $r->st->state);
    @endphp
    <div class="exec-stage" data-stage="{{ $group }}">
        <div class="exec-stage-head {{ $meta['grad'] }}" data-toggle-stage>
            <div class="exec-stage-icon"><i class="fas {{ $meta['icon'] }}"></i></div>
            <div class="exec-stage-title">
                {{ $meta['label'] }}
                <small>{{ $rooms->count() }} phòng</small>
            </div>
            <div class="exec-stage-counts">
                @foreach ($ROS::DISPLAY_ORDER as $s)
                    @if ($counts->get($s))
                        <span class="exec-count st-{{ $ROS::STATE_META[$s][0] }}" title="{{ $ROS::STATE_LABELS[$s] }}">
                            <i class="fas {{ $ROS::STATE_META[$s][1] }}"></i> {{ $counts->get($s) }}
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
