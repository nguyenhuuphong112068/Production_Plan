{{-- Card 1 phòng sản xuất. Dùng chung cho render trang và render lại sau mỗi thao tác (AJAX).
     $readonly: trang công khai Trạng Thái Sản Xuất (không nút thao tác, không lộ token, không nhãn thiết bị). --}}
@php
    $PES = \App\Services\ProductionExecutionService::class;
    $readonly = $readonly ?? false;
    $st = $room->st;
    $plan = $st->plan;
    $next = $room->next_plan ?? null;
    [$stateClass, $stateIcon] = $PES::STATE_META[$st->display];

    $fmt = fn($t) => $t ? \Carbon\Carbon::parse($t)->format('H:i d/m') : '—';
    $iso = fn($t) => $t ? \Carbon\Carbon::parse($t)->format('Y-m-d\TH:i:s') : '';
    $num = fn($v) => rtrim(rtrim(number_format((float) $v, 2, ',', '.'), '0'), ',');

    $pct = $plan && $plan->Theoretical_yields > 0
        ? min(100, round($plan->total_confirmed / $plan->Theoretical_yields * 100))
        : 0;
    // Hủy thao tác chỉ trong 2 phút kể từ lúc thao tác: JS đếm ngược rồi bỏ nút (xem _live_js), server cũng chặn
    $undoUntil = $PES::undoUntil($st);
    $undoLeft = $undoUntil ? (int) now()->diffInSeconds($undoUntil, false) : 0;
    $canUndo = $undoLeft > 0;

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

    // Đang chuẩn bị (mở phòng chọn Chuẩn bị): đã có BĐSX, chưa bắt đầu tạo ra sản lượng
    $preparing = $plan && $st->state === $PES::PREPARING;
    $producing = $st->state === $PES::PRODUCING;

    // Lô đang chạy trong phòng (chuẩn bị / sản xuất / tạm dừng): thời gian SX tính từ BĐSX (lúc mở phòng) = các khoảng
    // chuẩn bị + các lần đã xác nhận + lần đang chạy, không tính khoảng tạm dừng; so với thời lượng theo lịch
    $live = $plan && in_array($st->state, [$PES::PRODUCING, $PES::PAUSED], true);
    if ($live || $preparing) {
        $ms = fn($t) => \Carbon\Carbon::parse($t)->getTimestampMs();
        $secs = fn($from, $to) => max(0, (int) \Carbon\Carbon::parse($from)->diffInSeconds(\Carbon\Carbon::parse($to), false));
        $segments = $plan->segments ?? collect();
        // Khoảng chuẩn bị đã xong; khoảng đang chuẩn bị là lần đang chạy của thanh thời gian
        $prep = ($plan->prep ?? collect())->filter(fn($p) => $p->ended_at)->values();
        $bdsx = \Carbon\Carbon::parse($plan->batch_start ?? $st->since);

        $runBase = (int) $plan->run_seconds + $prep->sum(fn($p) => $secs($p->started_at, $p->ended_at));
        $runSec = $runBase + ($producing ? $sinceSec : 0);
        $planned = $plan->start && $plan->end ? $secs($plan->start, $plan->end) : 0;
        $over = $planned && $runSec > $planned;

        // Thanh thời gian (JS vẽ, xem renderTimeline): mốc thời gian dạng mili giây; mỗi khoảng chuẩn bị 1 đoạn [BĐSX, BĐCM],
        // mỗi lần khai báo sản lượng 1 đoạn [BĐCM, KT, sản lượng]; since = lần đang chạy / đang chuẩn bị
        $timeline = [
            'start'     => min($ms($bdsx), $segments->isNotEmpty() ? $ms($segments->first()->start) : PHP_INT_MAX),
            'planned'   => $planned,
            'since'     => $producing || $preparing ? $ms($st->since) : null,
            'preparing' => $preparing,
            'unit'      => $plan->unit,
            'prep'      => $prep->map(fn($p) => [$ms($p->started_at), $ms($p->ended_at)])->values(),
            'segs'      => $segments->map(fn($s) => [$ms($s->start), $ms($s->end), round((float) $s->yield, 2)])->values(),
        ];
    }

    // Dữ liệu cho JS khi mở modal thao tác
    $ctx = [
        'room_id'    => $room->id,
        'room'       => $room->code . ' - ' . $room->name,
        'stage_code' => (int) $room->stage_code,
        'token'      => $st->token,
        'state'      => $st->state,
        'display'    => $st->display,
        'since'      => $st->since ? $st->since->format('Y-m-d\TH:i') : null,
        'level'      => $st->cleaning_level,
        'plan'       => $plan ? [
            'id'             => $plan->id,
            'label'          => $plan->label,
            'unit'           => $plan->unit,
            'theory'         => round((float) $plan->Theoretical_yields, 2),
            'confirmed'      => round((float) $plan->total_confirmed, 2),
            'boxes'          => $plan->number_of_boxes ?? 1,
            'batch'          => $plan->batch,
            'can_edit_batch' => (int) $plan->stage_code === 1 && !$plan->actual_start,
            'title_clearning' => $plan->title_clearning,
        ] : null,
    ];

    $isActive = $live || $preparing || $st->state === $PES::CLEANING || count($room->activities);

    // Nhân sự đang được phân công (Lịch Công Tác): chữ cái đầu của tên (bỏ phần " - WH", "(mã)")
    $staff = $room->staff ?? collect();
    $initial = fn($name) => mb_strtoupper(mb_substr(\Illuminate\Support\Str::afterLast(trim(preg_replace('/\s*[-(].*$/u', '', $name)), ' '), 0, 1));

    $search = mb_strtolower($room->code . ' ' . $room->name . ' ' . ($plan->label ?? '') . ' ' . ($plan->intermediate_code ?? '') . ' ' . ($plan->finished_product_code ?? '')
        . ' ' . $staff->flatMap(fn($s) => $s->people->pluck('name'))->implode(' '));
@endphp

<div class="col-xl-4 col-md-6 mb-3 exec-room-col" id="exec-room-{{ $room->id }}" data-state="{{ $st->display }}"
    data-search="{{ $search }}" @unless ($readonly) data-ctx="{{ json_encode($ctx, JSON_UNESCAPED_UNICODE) }}" @endunless>
    <div class="exec-room st-{{ $stateClass }} {{ $isActive ? 'is-active' : '' }}">

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

            {{-- ===== Lô đang chuẩn bị / sản xuất / tạm dừng: khối nổi bật, đồng hồ chạy theo giây ===== --}}
            @if ($live || $preparing)
                <div class="exec-live">
                    <div class="exec-live-product">
                        {{ $plan->product_name ?? $plan->title }}{{ (int) $plan->stage_code === 7 && $plan->market ? ' - ' . $plan->market : '' }}
                        @if ($plan->is_val)
                            <span class="exec-live-tag" title="Lô thẩm định"><i class="fas fa-check-circle"></i> TĐ</span>
                        @endif
                    </div>
                    <div class="exec-live-batch">
                        Lô <b>{{ $plan->batch }}</b> · {{ $plan->intermediate_code }}{{ $plan->finished_product_code ? ' / ' . $plan->finished_product_code : '' }}
                    </div>

                    @if ($live && !$producing)
                        <div class="exec-live-pause">
                            <i class="fas fa-pause-circle"></i> Tạm dừng từ {{ $fmt($st->since) }} ·
                            <span class="js-since" data-since="{{ $iso($st->since) }}">{{ $dur($sinceSec) }}</span>
                        </div>
                    @endif

                    <div class="exec-live-stats">
                        @if ($preparing)
                            <div class="exec-live-stat">
                                <div class="exec-live-label">Thời gian chuẩn bị</div>
                                <div class="exec-live-big {{ $sinceSec >= 86400 ? 'long' : '' }} js-clock" data-since="{{ $iso($st->since) }}" data-base="0">{{ $clock($sinceSec) }}</div>
                                <div class="exec-live-small">chưa bắt đầu tạo ra sản lượng</div>
                            </div>
                        @else
                            <div class="exec-live-stat">
                                <div class="exec-live-label" title="Tính từ BĐSX (lúc mở phòng), gồm thời gian chuẩn bị, không tính khoảng tạm dừng">Thời gian SX</div>
                                <div class="exec-live-big {{ $runSec >= 86400 ? 'long' : '' }} {{ $producing ? 'js-clock' : '' }}"
                                    data-since="{{ $iso($st->since) }}" data-base="{{ $runBase }}" data-planned="{{ $planned }}">{{ $clock($runSec) }}</div>
                                {{-- So với thời lượng theo lịch; cùng quy tắc với JS clocks() --}}
                                <div class="exec-live-small js-time-left" title="Thời lượng theo lịch: {{ $planned ? $dur($planned) : 'không có' }}">
                                    @if (!$planned)
                                        không có lịch
                                    @elseif ($over)
                                        vượt {{ $dur($runSec - $planned) }}
                                    @elseif ($planned - $runSec < 60)
                                        đúng lịch
                                    @else
                                        còn {{ $dur($planned - $runSec) }}
                                    @endif
                                </div>
                            </div>
                        @endif
                        <div class="exec-live-stat text-right">
                            <div class="exec-live-label">Sản lượng đã khai báo</div>
                            <div class="exec-live-big {{ $plan->total_confirmed > 0 ? '' : 'muted' }}">
                                {{ $plan->total_confirmed > 0 ? $num($plan->total_confirmed) : '—' }}
                            </div>
                            <div class="exec-live-small">
                                / {{ $num($plan->Theoretical_yields) }} {{ $plan->unit }} ·
                                {{ $plan->total_confirmed > 0 ? $pct . '%' : 'chưa khai báo' }}
                            </div>
                        </div>
                    </div>

                    {{-- Thanh thời gian từ BĐSX: khoảng chuẩn bị, các lần khai báo sản lượng (BĐCM → KT), khoảng dừng, lần đang chạy --}}
                    <div class="exec-tl js-timeline" data-tl="{{ json_encode($timeline) }}"></div>

                    <div class="exec-live-foot">
                        {{-- BĐSX nằm ở đầu trục thanh thời gian; lần đang chạy bắt đầu sau BĐSX thì ghi mốc của lần đó --}}
                        @if ($producing && $st->since && $st->since->gt($bdsx))
                            <span>{{ $plan->actual_start ? 'Chạy lại' : 'BĐCM' }} <b>{{ $fmt($st->since) }}</b></span>
                        @endif
                        <span>Lịch <b>{{ $fmt($plan->start) }} → {{ $fmt($plan->end) }}</b></span>
                    </div>
                </div>
                @if ($live && !$producing && $st->note)
                    <div class="exec-note"><i class="fas fa-info-circle"></i> {{ $st->note }}</div>
                @endif

            {{-- ===== Đang vệ sinh: khối nổi bật ===== --}}
            @elseif ($st->state === $PES::CLEANING)
                <div class="exec-live">
                    <div class="exec-live-product"><i class="fas fa-broom"></i> {{ $PES::CLEANING_LEVELS[$st->cleaning_level] ?? ($st->cleaning_level ?: 'Vệ sinh') }}</div>
                    @if ($plan)
                        <div class="exec-live-batch">Sau lô <b>{{ $plan->label }}</b></div>
                    @endif
                    <div class="exec-live-stats">
                        <div class="exec-live-stat">
                            <div class="exec-live-label">Thời gian vệ sinh</div>
                            <div class="exec-live-big {{ $sinceSec >= 86400 ? 'long' : '' }} js-clock" data-since="{{ $iso($st->since) }}" data-base="0">{{ $clock($sinceSec) }}</div>
                            <div class="exec-live-small">bắt đầu {{ $fmt($st->since) }}</div>
                        </div>
                    </div>
                    @if ($plan)
                        <div class="exec-live-foot">
                            <span>KT sản xuất <b>{{ $fmt($plan->actual_end) }}</b></span>
                            <span>Lịch VS <b>{{ $plan->title_clearning ?? '—' }}</b></span>
                        </div>
                    @endif
                </div>
                @if ($st->note)
                    <div class="exec-note"><i class="fas fa-info-circle"></i> {{ $st->note }}</div>
                @endif
            @endif

            {{-- ===== Hoạt động khác đang diễn ra (ghi vào Báo cáo ngày) ===== --}}
            @foreach ($room->activities as $activity)
                @php $actSec = max(0, (int) \Carbon\Carbon::parse($activity->start)->diffInSeconds(now(), false)); @endphp
                <div class="exec-activity">
                    <div class="exec-activity-main">
                        <div class="exec-activity-name"><i class="fas fa-bolt"></i> {{ $activity->in_production }}</div>
                        @if ($activity->notification && $activity->notification !== 'NA')
                            <div class="exec-activity-note">{{ $activity->notification }}</div>
                        @endif
                        <div class="exec-activity-time">
                            <span class="exec-activity-clock js-clock" data-since="{{ $iso($activity->start) }}" data-base="0">{{ $clock($actSec) }}</span>
                            từ {{ $fmt($activity->start) }}
                        </div>
                    </div>
                    @unless ($readonly)
                        <button type="button" class="btn btn-sm btn-warning js-act" data-act="activity_end"
                            data-activity-id="{{ $activity->id }}" data-activity-name="{{ $activity->in_production }}">
                            <i class="fas fa-stop-circle"></i> Kết thúc
                        </button>
                    @endunless
                </div>
            @endforeach

            {{-- ===== Nhân sự đang được phân công lúc này (Lịch Công Tác → Sản Xuất) ===== --}}
            @if ($staff->isNotEmpty())
                <div class="exec-staff">
                    @foreach ($staff as $shift)
                        <div class="exec-staff-shift">
                            <span class="exec-staff-head" title="Phân công trên Lịch Công Tác → Sản Xuất">
                                <i class="fas fa-user-friends"></i> {{ $shift->shift }}
                                <span>{{ \Carbon\Carbon::parse($shift->start)->format('H:i') }}–{{ \Carbon\Carbon::parse($shift->end)->format('H:i') }}</span>
                            </span>
                            @foreach ($shift->people as $person)
                                <span class="exec-staff-person"
                                    title="{{ $person->code }} · {{ $person->name }} · {{ \Carbon\Carbon::parse($person->start)->format('H:i') }}–{{ \Carbon\Carbon::parse($person->end)->format('H:i') }}{{ $person->note ? ' · ' . $person->note : '' }}">
                                    <i>{{ $initial($person->name) }}</i>{{ $person->name }}
                                </span>
                            @endforeach
                        </div>
                        @if ($shift->job)
                            <div class="exec-staff-job" title="{{ $shift->job }}">{{ $shift->job }}</div>
                        @endif
                    @endforeach
                </div>
            @elseif (in_array($st->state, [$PES::PREPARING, $PES::PRODUCING, $PES::CLEANING], true) || count($room->activities))
                <div class="exec-staff empty" title="Không có ai được phân công tại phòng này vào lúc này trên Lịch Công Tác → Sản Xuất">
                    <i class="fas fa-user-slash"></i> Chưa phân công nhân sự lúc này
                </div>
            @endif

            {{-- ===== Chờ vệ sinh ===== --}}
            @if ($st->state === $PES::NEED_CLEAN)
                @if ($plan)
                    <div class="exec-batch">
                        <div class="exec-batch-meta">Sau lô</div>
                        <div class="exec-batch-name">{{ $plan->label }}</div>
                        <div class="exec-batch-meta">KT sản xuất {{ $fmt($plan->actual_end) }} · Lịch vệ sinh: {{ $plan->title_clearning ?? '—' }}</div>
                    </div>
                @endif
                @if ($st->since)
                    <div class="exec-kv">
                        <span>Chờ vệ sinh từ</span>
                        <b>{{ $fmt($st->since) }} · <span class="js-since" data-since="{{ $iso($st->since) }}"></span></b>
                    </div>
                @endif
                @if ($st->note)
                    <div class="exec-note"><i class="fas fa-info-circle"></i> {{ $st->note }}</div>
                @endif

            {{-- ===== Phòng sạch / quá hạn ===== --}}
            @elseif ($st->state === $PES::CLEAN)
                <div class="exec-kv"><span>Sạch từ</span><b>{{ $fmt($st->since) }}{{ $st->cleaning_level ? ' · ' . $st->cleaning_level : '' }}</b></div>
                @if ($st->expired_at)
                    <div class="exec-kv">
                        <span>Hạn sạch</span>
                        <b class="{{ $st->display === $PES::EXPIRED ? 'text-danger' : '' }}">
                            {{ $fmt($st->expired_at) }} · <span class="js-until" data-until="{{ $iso($st->expired_at) }}"></span>
                        </b>
                    </div>
                @endif
            @endif

            @if ($st->derived && $st->note && !in_array($st->state, [$PES::NEED_CLEAN, $PES::PAUSED], true))
                <div class="exec-note"><i class="fas fa-info-circle"></i> {{ $st->note }}</div>
            @endif

            {{-- Lô kế tiếp theo lịch --}}
            @if ($next && !in_array($st->state, [$PES::PREPARING, $PES::PRODUCING, $PES::PAUSED], true))
                <div class="exec-next">
                    <i class="far fa-calendar-alt"></i> Lô kế tiếp theo lịch:
                    <b>{{ $next->label }}</b> ({{ $fmt($next->start) }})
                </div>
            @endif

            {{-- Mã thiết bị lớn + tình trạng nhãn hiệu chuẩn / bảo trì / tiện ích (JS điền sau khi tải); bấm 1 mã để xem nhãn của thiết bị đó --}}
            @unless ($readonly)
                <button type="button" class="exec-equip js-act" data-act="equipment"
                    title="Thiết bị của phòng theo danh mục Bảo trì hiệu chuẩn. Bấm vào mã thiết bị để xem nhãn">
                    <span class="exec-equip-head">
                        <span class="exec-equip-title"><i class="fas fa-tools"></i> Thiết bị</span>
                        <span class="exec-equip-status js-equip-status"><i class="fas fa-spinner fa-spin text-muted"></i></span>
                    </span>
                    <span class="exec-equip-list js-equip-list"></span>
                </button>
            @endunless
        </div>

        @unless ($readonly)
        <div class="exec-room-actions">
            @switch ($st->display)
                @case($PES::CLEAN)
                    <button type="button" class="btn btn-exec btn-exec-go js-act" data-act="start">
                        <i class="fas fa-door-open"></i> Mở phòng
                    </button>
                @break

                @case($PES::EXPIRED)
                    <button type="button" class="btn btn-exec btn-exec-danger js-act" data-act="clean_start">
                        <i class="fas fa-broom"></i> Vệ sinh lại
                    </button>
                @break

                @case($PES::PREPARING)
                    {{-- Đang chuẩn bị: chỉ Thực thi sản xuất (BĐCM); không chạy lô này thì Hủy thao tác --}}
                    <button type="button" class="btn btn-exec btn-exec-go js-act" data-act="execute">
                        <i class="fas fa-play"></i> Thực thi sản xuất
                    </button>
                @break

                @case($PES::PRODUCING)
                    <button type="button" class="btn btn-exec btn-exec-pause js-act" data-act="pause">
                        <i class="fas fa-pause"></i> Tạm dừng
                    </button>
                    <button type="button" class="btn btn-exec btn-exec-stop js-act" data-act="finish">
                        <i class="fas fa-flag-checkered"></i> Kết thúc SX
                    </button>
                @break

                @case($PES::PAUSED)
                    <button type="button" class="btn btn-exec btn-exec-go js-act" data-act="resume">
                        <i class="fas fa-play"></i> Bắt đầu lại
                    </button>
                    <button type="button" class="btn btn-exec btn-exec-stop js-act" data-act="finish_paused">
                        <i class="fas fa-flag-checkered"></i> Kết thúc SX
                    </button>
                @break

                @case($PES::NEED_CLEAN)
                    <button type="button" class="btn btn-exec btn-exec-clean js-act" data-act="clean_start">
                        <i class="fas fa-broom"></i> Bắt đầu vệ sinh
                    </button>
                @break

                @case($PES::CLEANING)
                    <button type="button" class="btn btn-exec btn-exec-cleanend js-act" data-act="clean_end">
                        <i class="fas fa-check-double"></i> Kết thúc vệ sinh
                    </button>
                @break
            @endswitch
        </div>

        <div class="exec-room-foot">
            {{-- Từ BĐSX đến KT phòng dành cho lô: không thêm hoạt động khác --}}
            @unless (in_array($st->state, $PES::BATCH_RUNNING_STATES, true))
                <button type="button" class="js-act" data-act="activity" title="Khai báo hoạt động khác (ghi vào Báo cáo ngày)"><i class="fas fa-plus"></i> Hoạt động</button>
            @endunless
            <button type="button" class="js-act" data-act="history"><i class="fas fa-history"></i> Lịch sử</button>
            @if ($canUndo)
                <button type="button" class="js-act js-undo text-danger" data-act="undo" data-until="{{ $iso($undoUntil) }}"
                    title="Chỉ hủy được trong {{ $PES::UNDO_SECONDS / 60 }} phút sau khi thao tác"><i class="fas fa-undo-alt"></i> Hủy thao tác
                    (<span class="exec-undo-left js-undo-left">{{ sprintf('%d:%02d', intdiv($undoLeft, 60), $undoLeft % 60) }}</span>)</button>
            @elseif (in_array($st->display, [$PES::CLEAN, $PES::EXPIRED], true))
                <button type="button" class="js-act" data-act="mark_dirty" title="Chuyển phòng sang Cần Vệ Sinh (sau bảo trì, sự cố...)"><i class="fas fa-ban"></i> Cần VS</button>
            @endif
        </div>
        @endunless
    </div>
</div>
