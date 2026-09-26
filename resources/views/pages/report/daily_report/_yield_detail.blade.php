{{-- Ô Chi tiết của 1 phòng: các khoảng đã ghi nhận (sản xuất, chuẩn bị, vệ sinh, hoạt động, tạm dừng) xen với các khoảng
     "Không hoạt động" suy ra (DailyRoomTimelineService), dòng thời gian 06:00 → 06:00 và tổng thời gian.
     Cần: $detail (actual_detail của phòng), $tl (dòng thời gian của phòng), $kinds, $shiftStart, $roomLT, $update_daily_report. --}}
@php
    $TL = \App\Services\DailyRoomTimelineService::class;
    $w0 = $shiftStart->getTimestamp();
    // Vị trí trên dòng thời gian (%), 1% = 864 giây
    $pos = function ($start, $end) use ($w0) {
        $from = max(0, $start->getTimestamp() - $w0);
        $to = min(86400, $end->getTimestamp() - $w0);
        return [round($from / 864, 3), $to > $from ? round(($to - $from) / 864, 3) : 0];
    };
    $short = function (int $sec) {
        $m = intdiv(max(0, $sec), 60);
        [$h, $mm] = [intdiv($m, 60), $m % 60];
        return $h ? ($mm ? "{$h}h{$mm}p" : "{$h}h") : "{$mm}p";
    };
    $long = fn($sec) => intdiv((int) $sec, 3600) . ' giờ ' . intdiv((int) $sec % 3600, 60) . ' phút';

    $rows = collect();
    foreach ($detail as $d) {
        $start = \Carbon\Carbon::parse($d->start);
        $end = \Carbon\Carbon::parse($d->end);
        if ($end->lessThan($start)) {
            $end->addDay(); // qua ngày hôm sau
        }
        // Dòng từ trang Thực Thi SX gắn với khoảng tạm dừng / vệ sinh không gắn lô lấy loại theo trạng thái phòng
        $kind = $d->is_order_action
            ? match ((int) $d->execution_state) {
                $PES::PAUSED => 'paused',
                $PES::CLEANING => 'cleaning',
                default => 'activity',
            }
            : (str_ends_with($d->id, '-clearning') ? 'cleaning' : 'producing');
        $rows->push((object) ['idle' => false, 'kind' => $kind, 'start' => $start, 'end' => $end, 'title' => $d->title,
            'note' => $d->note && $d->note != 'NA' ? $d->note : null, 'live' => false, 'd' => $d]);
    }
    foreach ($tl->extra ?? [] as $e) {
        $rows->push((object) ['idle' => false, 'kind' => $e->kind, 'start' => $e->start, 'end' => $e->end, 'title' => $e->title,
            'note' => $e->note, 'live' => $e->live, 'd' => null]);
    }
    foreach ($tl->idle ?? [] as $e) {
        $rows->push((object) ['idle' => true, 'kind' => $e->reason, 'start' => $e->start, 'end' => $e->end,
            'title' => $TL::IDLE_REASONS[$e->reason], 'note' => $e->note, 'live' => false, 'd' => null]);
    }
    $rows = $rows->sortBy(fn($r) => $r->start->getTimestamp())->values();

    foreach ($rows as $r) {
        [$r->left, $r->width] = $pos($r->start, $r->end);
        $r->dur = $short($r->end->getTimestamp() - $r->start->getTimestamp());
        $r->range = $r->start->format('H:i') . ' – ' . $r->end->format('H:i');
        if (!$r->idle) {
            [$r->cls, $r->icon, $r->label] = $kinds[$r->kind];
            $r->title = $r->title ?: ($r->kind === 'cleaning' ? 'Vệ sinh' : '—');
            // Tên trùng nhãn loại (vd. "Tạm dừng SX") thì lấy ghi chú làm nội dung chính
            if ($r->note && mb_strtolower($r->title) === mb_strtolower($r->label)) {
                [$r->title, $r->note] = [$r->note, null];
            }
        }
    }

    $until = $tl->until ?? null;
    $isToday = $until && $until->getTimestamp() < $w0 + 86400;
@endphp

@if ($rows->isNotEmpty())
    <div class="dr-items">
        @foreach ($rows as $r)
            @if ($r->idle)
                <div class="dr-item is-idle">
                    <span class="dr-time">{{ $r->range }}</span>
                    <span class="dr-dur">{{ $r->dur }}</span>
                    <span><span class="dr-state dr-state-idle"
                            title="Không có dữ liệu ghi nhận: phòng không hoạt động (lý do suy ra từ trạng thái phòng và các lô trước / sau)"><i
                                class="fas fa-power-off"></i> Không hoạt động</span></span>
                    <div class="dr-main">
                        <div class="dr-title"><i class="dr-dot dr-idle"></i>{{ $r->title }}</div>
                        @if ($r->note)
                            <div class="dr-note">{{ $r->note }}</div>
                        @endif
                    </div>
                    <div class="dr-end"></div>
                </div>
            @else
                @php $d = $r->d; @endphp
                <div class="dr-item">
                    <span class="dr-time">{{ $r->range }}</span>
                    <span class="dr-dur">{{ $r->dur }}</span>
                    <span><span class="dr-state dr-state-{{ $r->cls }}"><i class="fas {{ $r->icon }}"></i> {{ $r->label }}</span></span>
                    <div class="dr-main">
                        <div class="dr-title">
                            {{ $r->title }}
                            @if ($r->live)
                                <span class="dr-live">đang diễn ra</span>
                            @endif
                        </div>
                        @if ($r->note)
                            <div class="dr-note"><i class="far fa-comment-dots"></i> {{ $r->note }}</div>
                        @endif
                    </div>
                    <div class="dr-end">
                        @if ($d && $d->yields)
                            <div class="dr-yield">
                                {{ number_format($d->yields, 2) }} {{ $d->unit }}
                                @if ($d->yields_batch_qty)
                                    <small># {{ $d->yields_batch_qty }} ĐVL</small>
                                @endif
                            </div>
                        @elseif ($d && $d->is_order_action && !$d->from_execution && $update_daily_report)
                            {{-- Hoạt động nhập tay: sửa / hủy (dòng tạo từ trang Thực Thi SX không sửa/xóa ở đây) --}}
                            <button class="btn btn-edit dr-act dr-act-edit"
                                data-id = "{{ $d->id }}"
                                data-title = "{{ $d->title }}"
                                data-start = "{{ $d->start }}"
                                data-end = "{{ $d->end }}"
                                data-note = "{{ $d->note }}"
                                data-room_id = "{{ $roomLT->resourceId }}"
                                data-room_code = "{{ $roomLT->room_code }}"
                                data-room_name = "{{ $roomLT->room_name }}"
                                title = "Cập Nhật Báo Cáo Hoạt Động Khác"
                                data-toggle="modal" data-target="#updateModal">
                                <i class="fas fa-pen"></i>
                            </button>
                            <form class="form-deActive m-0" action="{{ route('pages.report.daily_report.deActive') }}" method="post">
                                @csrf
                                <input type="hidden" name="id" value="{{ $d->id }}">
                                <button class="btn btn-deactive dr-act dr-act-del" title = "Hủy Báo Cáo Hoạt Động Khác">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            @endif
        @endforeach
    </div>

    {{-- Dòng thời gian trong ngày --}}
    <div class="dr-day">
        <div class="dr-day-track">
            @foreach ($rows as $r)
                @if ($r->width > 0)
                    <span class="dr-seg {{ $r->idle ? 'dr-idle' : 'dr-seg-' . $r->cls }}"
                        style="left: {{ $r->left }}%; width: {{ $r->width }}%;"
                        title="{{ $r->range }} · {{ $r->idle ? 'Không hoạt động' : $r->label }}: {{ $r->title }}"></span>
                @endif
            @endforeach
            @if ($isToday)
                <span class="dr-now" style="left: {{ round(($until->getTimestamp() - $w0) / 864, 3) }}%;"
                    title="Bây giờ {{ $until->format('H:i') }}"></span>
            @endif
        </div>
        <div class="dr-day-ticks">
            <span style="left: 0">06:00</span><span style="left: 25%">12:00</span><span style="left: 50%">18:00</span><span style="left: 75%">00:00</span><span style="left: 100%">06:00</span>
        </div>
    </div>
@endif

<div class="dr-foot">
    @if ($rows->isNotEmpty())
        <span class="dr-total dr-total-ok"><i class="fas fa-check-circle"></i> Xác định <b>{{ $long($tl->recorded_seconds ?? 0) }}</b></span>
        <span class="dr-total dr-total-idle"><i class="fas fa-power-off"></i> Không hoạt động <b>{{ $long($tl->idle_seconds ?? 0) }}</b></span>
    @else
        <span class="dr-empty">Chưa có dữ liệu trong ngày</span>
    @endif
</div>
{{-- Thời gian không hoạt động theo lý do --}}
<div class="dr-reasons">
    @foreach ($tl->idle_by_reason ?? [] as $reason => $sec)
        <span class="dr-reason"><i class="dr-dot dr-idle"></i>{{ $TL::IDLE_REASONS[$reason] }}<b>{{ $short($sec) }}</b></span>
    @endforeach
    @if ($update_daily_report)
        <button class="btn btn-outline-success btn-plus dr-add"
            data-room_code = "{{ $roomLT->room_code }}"
            data-room_name = "{{ $roomLT->room_name }}"
            data-room_id = "{{ $roomLT->resourceId }}" data-toggle="modal"
            data-target="#Modal"
            title = "Tạo mới Báo Cáo Hoạt Động Khác"><i class="fas fa-plus"></i> Thêm hoạt động</button>
    @endif
</div>
