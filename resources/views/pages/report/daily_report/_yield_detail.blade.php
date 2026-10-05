{{-- Ô Chi tiết của 1 phòng, hiển thị như trước khi có trang Thực Thi Sản Xuất: danh sách các khoảng đã ghi nhận
     (sản xuất, vệ sinh, hoạt động khác) + tổng thời gian xác định / không xác định trong ca 06:00 → 06:00.
     Cần: $detail (actual_detail của phòng), $shiftStart, $shiftEnd, $roomLT, $update_daily_report. --}}
@php
    $rows = $detail->map(function ($d) {
        $start = \Carbon\Carbon::parse($d->start);
        $end = \Carbon\Carbon::parse($d->end);
        if ($end->lessThan($start)) {
            $end->addDay(); // qua ngày hôm sau
        }
        return (object) ['d' => $d, 'start' => $start, 'end' => $end];
    })->values();

    // Tổng thời gian xác định = hợp các khoảng (giới hạn trong ca), không xác định = phần còn lại của ca
    $intervals = $rows->map(fn($r) => [$r->start->max($shiftStart), $r->end->min($shiftEnd)])
        ->filter(fn($i) => $i[1] > $i[0])
        ->sortBy(fn($i) => $i[0]->getTimestamp())
        ->values();
    $activeSeconds = 0;
    $current = null;
    foreach ($intervals as $i) {
        if ($current && $i[0] <= $current[1]) {
            $current[1] = $i[1]->max($current[1]);
            continue;
        }
        if ($current) {
            $activeSeconds += $current[0]->diffInSeconds($current[1]);
        }
        $current = $i;
    }
    if ($current) {
        $activeSeconds += $current[0]->diffInSeconds($current[1]);
    }
    $deadSeconds = max(0, $shiftStart->diffInSeconds($shiftEnd) - $activeSeconds);
    $hm = fn($sec) => intdiv((int) $sec, 3600) . ' giờ ' . intdiv((int) $sec % 3600, 60) . ' phút';
@endphp

@if ($update_daily_report)
    <button class="btn btn-success btn-sm btn-plus float-right"
        style="width: 20px; height: 20px; padding: 0; line-height: 0;"
        data-room_code = "{{ $roomLT->room_code }}"
        data-room_name = "{{ $roomLT->room_name }}"
        data-room_id = "{{ $roomLT->resourceId }}" data-toggle="modal"
        data-target="#Modal"
        title = "Tạo mới Báo Cáo Hoạt Động Khác">+</button>
@endif

@if ($rows->isNotEmpty())
    @foreach ($rows as $r)
        @php
            $d = $r->d;
            $minutes = (int) $r->start->diffInMinutes($r->end);
        @endphp
        <div style="display: flex; flex-direction: row; gap: 3px;">
            {{ $loop->iteration . '. ' }}
            {{ $d->title == null && $d->yields == null ? 'VS' : $d->title }}
            ({{ $r->start->format('H:i') }} - {{ $r->end->format('H:i') }} = <b>{{ intdiv($minutes, 60) }}h{{ $minutes % 60 }}p</b>)
            @if ($d->yields)
                || <b>{{ 'Sản Lượng: ' . number_format($d->yields, 2) }} {{ $d->unit }}
                    {{ $d->yields_batch_qty ? "# $d->yields_batch_qty  ĐVL" : '' }}</b>
            @endif
            @if ($d->note && $d->note != 'NA')
                || <b>{{ 'Ghi Chú: ' . $d->note }}</b>
            @endif

            {{-- Hoạt động nhập tay: sửa / hủy (dòng do trang Thực Thi SX bản cũ tạo thì không sửa/xóa ở đây) --}}
            @if ($d->is_order_action && !$d->from_execution && $update_daily_report)
                <button class="btn btn-warning btn-sm btn-edit"
                    style="width: 20px; height: 20px; padding: 0; line-height: 0;"
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
                <form class="form-deActive" action="{{ route('pages.report.daily_report.deActive') }}" method="post">
                    @csrf
                    <input type="hidden" name="id" value="{{ $d->id }}">
                    <button class="btn btn-danger btn-sm btn-deactive" title = "Hủy Báo Cáo Hoạt Động Khác"
                        style="width: 20px; height: 20px; padding: 0; line-height: 0;">
                        <i class="fas fa-trash"></i>
                    </button>
                </form>
            @endif
        </div>
    @endforeach
    <div>
        <b>Tổng thời gian xác định:</b> {{ $hm($activeSeconds) }}
        <br>
        <b>Tổng thời gian không xác định:</b> {{ $hm($deadSeconds) }}
    </div>
@else
    <span class="text-muted">—</span>
@endif
