{{-- Card 1 phòng sản xuất. Dùng chung cho render trang và render lại sau mỗi thao tác (AJAX).
     $readonly: trang công khai Trạng Thái Sản Xuất / nhân viên phân xưởng khác (không nút thao tác). --}}
@php
    $ROS = \App\Services\RoomOccupancyService::class;
    $readonly = $readonly ?? false;
    $st = $room->st;
    $plans = $st->plans;
    $plan = $plans->first();
    $busy = $st->state === $ROS::BUSY;
    [$stateClass, $stateIcon] = $ROS::STATE_META[$st->state];

    $fmt = fn($t) => $t ? \Carbon\Carbon::parse($t)->format('H:i d/m') : '—';
    $iso = fn($t) => $t ? \Carbon\Carbon::parse($t)->format('Y-m-d\TH:i:s') : '';
    $num = fn($v) => rtrim(rtrim(number_format((float) $v, 2, ',', '.'), '0'), ',');

    // Đồng hồ: dưới 1 ngày HH:MM:SS, từ 1 ngày trở lên "N ngày HH:MM" (JS dùng cùng định dạng)
    $clock = function (int $sec) {
        $sec = max(0, $sec);
        $d = intdiv($sec, 86400);
        return $d
            ? sprintf('%d ngày %02d:%02d', $d, intdiv($sec % 86400, 3600), intdiv($sec % 3600, 60))
            : sprintf('%02d:%02d:%02d', intdiv($sec, 3600), intdiv($sec % 3600, 60), $sec % 60);
    };
    $dur = function (int $sec) {
        $m = intdiv(max(0, $sec), 60);
        $d = intdiv($m, 1440);
        $h = intdiv($m % 1440, 60);
        return $d ? "$d ngày $h giờ" : ($h ? "$h giờ " . ($m % 60) . ' phút' : ($m % 60) . ' phút');
    };
    $sinceSec = $st->since ? max(0, (int) $st->since->diffInSeconds(now(), false)) : 0;

    if ($busy) {
        // Thời lượng giữ phòng theo lịch: bắt đầu lô → hết vệ sinh theo lịch (không có vệ sinh thì tới KT theo lịch)
        $planEnd = $plans->max(fn($p) => $p->end_clearning ?? $p->end);
        $planned = $plan->start && $planEnd ? max(0, (int) \Carbon\Carbon::parse($plan->start)->diffInSeconds(\Carbon\Carbon::parse($planEnd), false)) : 0;
        $pct = $planned ? min(100, round($sinceSec / $planned * 100)) : 0;
        $title = $plan->maintenance ? $plan->title : ($plan->product_name ?? $plan->title) . ((int) $plan->stage_code === 7 && $plan->market ? ' - ' . $plan->market : '');
    }

    // Dữ liệu cho JS khi bấm nút
    $ctx = [
        'room_id'  => $room->id,
        'room'     => $room->code . ' - ' . $room->name,
        'state'    => $st->state,
        'weighing' => in_array((int) $room->stage_code, $ROS::GROUP_STAGES, true),
        'plans'    => $plans->pluck('label')->values(),
        'since'    => $st->since ? $fmt($st->since) : null,
    ];

    // Nhân sự đang được phân công (Lịch Công Tác): nhãn A, B, C... theo đúng thứ tự hiển thị bên Lịch Công Tác → Sản Xuất
    $staff = $room->staff ?? collect();

    $search = mb_strtolower($room->code . ' ' . $room->name . ' ' . $plans->pluck('label')->implode(' ') . ' ' . ($plan->intermediate_code ?? '') . ' ' . ($plan->finished_product_code ?? '')
        . ' ' . $staff->flatMap(fn($s) => $s->people->pluck('name'))->implode(' '));
@endphp

<div class="col-xl-4 col-md-6 mb-3 exec-room-col" id="exec-room-{{ $room->id }}" data-state="{{ $st->state }}"
    data-search="{{ $search }}" @unless ($readonly) data-ctx="{{ json_encode($ctx, JSON_UNESCAPED_UNICODE) }}" @endunless>
    <div class="exec-room st-{{ $stateClass }} {{ $busy ? 'is-active' : '' }}">

        <div class="exec-room-head">
            <div class="exec-room-title">
                <span class="exec-room-code">{{ $room->code }}</span>
                <div class="exec-room-name" title="{{ $room->name }}">{{ $room->name }}</div>
                @if ($room->main_equiment_name)
                    <div class="exec-room-equip" title="{{ $room->main_equiment_name }}">{{ $room->main_equiment_name }}</div>
                @endif
            </div>
            <span class="exec-chip"><i class="fas {{ $stateIcon }}"></i> {{ $st->label }}</span>
        </div>

        <div class="exec-room-body">
            @if ($busy)
                {{-- ===== Phòng bận: lô / lịch bảo trì đang giữ phòng, đồng hồ chạy theo giây ===== --}}
                <div class="exec-live">
                    <div class="exec-live-product">
                        @if ($plan->maintenance)
                            <i class="fas fa-tools"></i>
                        @endif
                        {{ $title }}
                        @if ($plan->is_val)
                            <span class="exec-live-tag" title="Lô thẩm định"><i class="fas fa-check-circle"></i> TĐ</span>
                        @endif
                    </div>
                    @if ($plans->count() > 1)
                        <div class="exec-live-batch"><b>{{ $plans->count() }} lô</b> cân chung · {{ $plan->intermediate_code }}</div>
                        <div class="exec-group">
                            @foreach ($plans as $b)
                                <div class="exec-group-row">
                                    <span class="exec-group-batch"><i class="fas fa-circle-notch"></i> {{ $b->batch }}</span>
                                    <span class="exec-group-qty">LT {{ $num($b->Theoretical_yields) }} {{ $b->unit }}</span>
                                    <span class="exec-group-state">từ {{ $fmt($b->actual_start) }}</span>
                                </div>
                            @endforeach
                        </div>
                    @elseif (!$plan->maintenance)
                        <div class="exec-live-batch">
                            Lô <b>{{ $plan->batch }}</b> · {{ $plan->intermediate_code }}{{ $plan->finished_product_code ? ' / ' . $plan->finished_product_code : '' }}
                        </div>
                    @endif

                    <div class="exec-live-stats">
                        <div class="exec-live-stat">
                            <div class="exec-live-label" title="Tính từ lúc nhận phòng">Thời gian sử dụng phòng</div>
                            <div class="exec-live-big {{ $sinceSec >= 86400 ? 'long' : '' }} js-clock"
                                data-since="{{ $iso($st->since) }}" data-base="0" data-planned="{{ $planned }}">{{ $clock($sinceSec) }}</div>
                            {{-- So với thời lượng giữ phòng theo lịch; cùng quy tắc với JS clocks() --}}
                            <div class="exec-live-small js-time-left" title="Theo lịch: {{ $planned ? $dur($planned) : 'không có' }}">
                                @if (!$planned)
                                    không có lịch
                                @elseif ($sinceSec > $planned)
                                    vượt {{ $dur($sinceSec - $planned) }}
                                @elseif ($planned - $sinceSec < 60)
                                    đúng lịch
                                @else
                                    còn {{ $dur($planned - $sinceSec) }}
                                @endif
                            </div>
                        </div>
                    </div>
                    @if ($planned)
                        <div class="exec-progress" title="So với thời lượng theo lịch {{ $dur($planned) }}"><div style="width: {{ $pct }}%"></div></div>
                    @endif

                    <div class="exec-live-foot">
                        <span>Nhận phòng <b>{{ $fmt($st->since) }}</b></span>
                        <span>Lịch <b>{{ $fmt($plan->start) }} → {{ $fmt($planEnd) }}</b></span>
                    </div>
                </div>
            @endif

            {{-- ===== Nhân sự đang được phân công lúc này (Lịch Công Tác → Sản Xuất) ===== --}}
            @if ($staff->isNotEmpty())
                <div class="exec-staff">
                    @foreach ($staff as $shift)
                        <div class="exec-staff-shift" title="Phân công trên Lịch Công Tác → Sản Xuất">
                            @foreach ($shift->people as $person)
                                <span class="exec-staff-person"
                                    title="{{ $person->code }} · {{ $person->name }} · {{ \Carbon\Carbon::parse($person->start)->format('H:i') }}–{{ \Carbon\Carbon::parse($person->end)->format('H:i') }}{{ $person->note ? ' · ' . $person->note : '' }}">
                                    <i>{{ $person->label }}</i>{{ $person->name }}
                                    <small>({{ \Carbon\Carbon::parse($person->start)->format('H:i') }} - {{ \Carbon\Carbon::parse($person->end)->format('H:i') }})</small>
                                </span>
                            @endforeach
                        </div>
                    @endforeach
                </div>
            @elseif ($busy)
                <div class="exec-staff empty" title="Không có ai được phân công tại phòng này vào lúc này trên Lịch Công Tác → Sản Xuất">
                    <i class="fas fa-user-slash"></i> Chưa phân công nhân sự lúc này
                </div>
            @endif
        </div>

        @unless ($readonly)
            <div class="exec-room-actions">
                @if ($busy)
                    <button type="button" class="btn btn-exec btn-exec-stop js-act" data-act="release">
                        <i class="fas fa-sign-out-alt"></i> Trả phòng
                    </button>
                @else
                    <button type="button" class="btn btn-exec btn-exec-go js-act" data-act="receive">
                        <i class="fas fa-door-open"></i> Nhận phòng
                    </button>
                @endif
            </div>
        @endunless
    </div>
</div>
