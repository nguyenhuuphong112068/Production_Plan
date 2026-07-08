<style>
    /* Màu trạng thái step */
    .step-pending .bs-stepper-circle {
        background-color: #6c757d !important;
        /* Xám */
        color: white;
    }

    .step-scheduled .bs-stepper-circle {
        background-color: #28a745 !important;
        /* Xanh lá */
        color: white;
    }

    .step-finished .bs-stepper-circle {
        background-color: #007bff !important;
        /* Xanh dương */
        color: white;
    }

    .step-delay .bs-stepper-circle {
        background-color: #dc3545 !important;
        /* Đỏ */
        color: white;
    }

    .step-warning .bs-stepper-circle {
        background-color: #e39235 !important;
        /* Cam cảnh báo */
        color: white;
    }

    /* Mũi tên pointer */
    .step.step-pointer .bs-stepper-circle::before {
        content: "";
        position: absolute;
        top: 0%;
        left: 50%;
        transform: translateX(-50%);
        width: 0;
        height: 0;
        border-left: 14px solid transparent;
        border-right: 14px solid transparent;
        border-top: 18px solid #007bff;
        filter: drop-shadow(0 2px 2px rgba(0, 0, 0, 0.4));
    }

    /* Style riêng cho dòng tổng kết 5-6-7 */
    .timeline-info div {
        font-size: 14px;
        margin-bottom: 2px;
    }

    .timeline-info .text-success {
        font-weight: 600;
    }

    .waiting-label {
        width: 10%;
        border-top: 2px solid;
        margin-top: 14px;
        font-size: 16px;
        color: rgb(0, 55, 255);
        font-weight: 500;
        text-align: center;
        white-space: nowrap;
        position: relative;
    }

    .waiting-label::before {
        content: "Thời Gian Biệt trữ";
        position: absolute;
        top: -24px;
        /* đẩy chữ lên trên border */
        left: 50%;
        transform: translateX(-50%);
        padding: 0 6px;
        font-size: 13px;
        font-weight: bold;
    }
</style>

<link rel="stylesheet" href="{{ asset('libs/bs-stepper/css/bs-stepper.min.css') }}">

<div class="content-wrapper mt-5">
    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="card">

                        <div class="card-body">
                            <div class="row">
                                <div class="col-12">
                                    @php
                                        use Carbon\Carbon;
                                        $defaultFrom = Carbon::now()->subMonth()->toDateString();
                                        $defaultTo = Carbon::now()->addMonth()->toDateString();
                                        $isFilterOverdue = request('filter_overdue') == '1';
                                    @endphp

                                    <form id="dateFilterForm" method="GET"
                                        action="{{ route('pages.Schedual.step.list') }}"
                                        class="d-flex flex-wrap gap-3 align-items-center w-100">
                                        <input type="hidden" name="filter_overdue" id="filter_overdue_input"
                                            value="{{ request('filter_overdue', '0') }}">

                                        <div class="form-group d-flex align-items-center mb-0">
                                            <label for="from_date" class="mr-2 mb-0 text-nowrap">From:</label>
                                            <input type="date" id="from_date" name="from_date"
                                                value="{{ request('from_date') ?? $defaultFrom }}" class="form-control"
                                                {{ $isFilterOverdue ? 'disabled' : '' }} />
                                        </div>

                                        <div class="form-group d-flex align-items-center mb-0">
                                            <label for="to_date" class="mr-2 mb-0 text-nowrap">To:</label>
                                            <input type="date" id="to_date" name="to_date"
                                                value="{{ request('to_date') ?? $defaultTo }}" class="form-control"
                                                {{ $isFilterOverdue ? 'disabled' : '' }} />
                                        </div>

                                        <button type="button" id="btnFilterOverdue"
                                            class="btn {{ $isFilterOverdue ? 'btn-danger' : 'btn-warning' }} mb-0 ml-auto" style="box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                                            @if ($isFilterOverdue)
                                                <i class="fas fa-times mr-1"></i> Bỏ Lọc Quá Hạn
                                            @else
                                                <i class="fas fa-exclamation-triangle mr-1"></i> Lọc Quá Hạn Biệt Trữ
                                            @endif
                                        </button>
                                    </form>
                                </div>
                            </div>

                            <div class="table-responsive shadow-sm rounded"
                                style="max-height: calc(100vh - 200px); overflow-y: auto; overflow-x: auto; background: #fff;">
                                <table id="data_table_Schedual_step" class="table table-bordered table-striped"
                                    style="font-size: 20px; min-width: 1500px;">
                                    <thead
                                        style="position: sticky; top: 0; background-color: white; z-index: 1020; box-shadow: 0 2px 2px -1px rgba(0, 0, 0, 0.4);">
                                        <tr>
                                            <th>STT</th>
                                            <th>Sản Phẩm</th>
                                            <th>Dự Kiến KCS</th>
                                            <th>Số lô</th>
                                            <th>Tiến Trình</th>
                                            <th>Tổng kết</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($datas as $plan_master_id => $stages)
                                            @php

                                                $plan = $stages->first();
                                                $lastFinished = collect($stages)
                                                    ->where('finished', '1')
                                                    ->sortByDesc('stage_code')
                                                    ->first();
                                                $sortedStages = $stages->sortBy('stage_code')->values();
                                                $firstStage = null;
                                                $lastStage = null;
                                                foreach ($sortedStages as $sortedStage) {
                                                    if ($sortedStage->start != null) {
                                                        $firstStage = $sortedStage;
                                                        break;
                                                    }
                                                }
                                                foreach ($sortedStages->reverse() as $sortedStage) {
                                                    if ($sortedStage->start != null) {
                                                        $lastStage = $sortedStage;
                                                        break;
                                                    }
                                                }

                                                if ($firstStage == null && $lastStage == null) {
                                                    continue;
                                                }

                                                $startTs = strtotime($firstStage->start);
                                                $endTs = strtotime($lastStage->end);
                                                $diffSecs = $endTs - $startTs;
                                                $diffDays = floor($diffSecs / 86400);
                                                $diffHours = floor(($diffSecs % 86400) / 3600);
                                                $totalDuration = $diffDays . 'd-' . $diffHours . 'h';
                                                // Tổng thời gian sản xuất (tính giờ làm trong từng stage)
                                                $totalProductionHours = 0;
                                                // Tổng thời gian vệ sinh (khoảng trống giữa các stage)
                                                $totalCleaningHours = 0;
                                                //dd($sortedStages) ;
                                                foreach ($sortedStages as $index => $stage) {
                                                    $stageStartTs = strtotime($stage->start);
                                                    $stageEndTs = strtotime($stage->end);
                                                    $stageStart_clearningTs = strtotime($stage->start_clearning);
                                                    $stageEnd_clearningTs = strtotime($stage->end_clearning);

                                                    if ($stage->start && $stage->end) {
                                                        $totalProductionHours += ($stageEndTs - $stageStartTs) / 3600;
                                                    }
                                                    if ($stage->start_clearning && $stage->end_clearning) {
                                                        $totalCleaningHours +=
                                                            ($stageEnd_clearningTs - $stageStart_clearningTs) / 3600;
                                                    }
                                                }
                                                // Format gọn lại
                                                $totalProductionHours = round($totalProductionHours, 2);
                                                $totalCleaningHours = round($totalCleaningHours, 2);
                                            @endphp

                                            {{-- Hàng 1: Stepper --}}
                                            <tr>
                                                <td>{{ $loop->iteration }}</td>
                                                <td>{{ $plan->product_name . '-' . $plan->batch_qty . ' ' . $plan->unit_batch_qty }}
                                                </td>
                                                <td>{{ date('d/m/Y', strtotime($plan->expected_date)) }}</td>
                                                <td>{{ $plan->batch }}</td>
                                                <td>
                                                    @php
                                                        $mainStages = $stages
                                                            ->filter(function ($s) {
                                                                return $s->stage_code != 2;
                                                            })
                                                            ->values();
                                                        $branchStages = $stages
                                                            ->filter(function ($s) {
                                                                return $s->stage_code == 2;
                                                            })
                                                            ->values();
                                                    @endphp

                                                    <div id="stepper-{{ $plan_master_id }}" class="bs-stepper">
                                                        <div class="bs-stepper-header" role="tablist">

                                                            @foreach ($mainStages as $i => $stage)
                                                                @php

                                                                    $stageKey = Str::slug($stage->stage_code, '-');
                                                                    $statusClass = 'step-pending';
                                                                    if ($stage->status == 'scheduled') {
                                                                        $statusClass = 'step-scheduled';
                                                                    } elseif ($stage->status == 'finished') {
                                                                        $statusClass = 'step-finished';
                                                                    } elseif (
                                                                        !empty($stage->end) &&
                                                                        strtotime($stage->end) >
                                                                            strtotime($plan->expected_date)
                                                                    ) {
                                                                        $statusClass = 'step-delay';
                                                                    } elseif (
                                                                        $stage->stage_code == 1 &&
                                                                        $stage->start !== null &&
                                                                        !(
                                                                            !empty($plan->after_weigth_date) &&
                                                                            !empty($plan->before_weigth_date) &&
                                                                            strtotime($stage->start) >=
                                                                                strtotime($plan->after_weigth_date) &&
                                                                            strtotime($stage->start) <=
                                                                                strtotime($plan->before_weigth_date)
                                                                        )
                                                                    ) {
                                                                        $statusClass = 'step-warning';
                                                                    } elseif (
                                                                        $stage->stage_code >= 7 &&
                                                                        $stage->start !== null &&
                                                                        !(
                                                                            !empty($plan->after_parkaging_date) &&
                                                                            !empty($plan->before_parkaging_date) &&
                                                                            strtotime($stage->start) >=
                                                                                strtotime(
                                                                                    $plan->after_parkaging_date,
                                                                                ) &&
                                                                            strtotime($stage->start) <=
                                                                                strtotime($plan->before_parkaging_date)
                                                                        )
                                                                    ) {
                                                                        $statusClass = 'step-warning';
                                                                    }

                                                                    if (
                                                                        $lastFinished &&
                                                                        $stage->id == $lastFinished->id
                                                                    ) {
                                                                        $statusClass .= ' step-pointer';
                                                                    }
                                                                    // tính thời gian biệt trữ
                                                                    $waiting = null;
                                                                    $stdWaiting = null;
                                                                    $isWarning = false;

                                                                    // Tìm công đoạn tiếp theo dựa vào nextcessor_code
                                                                    $next = $stages->firstWhere(
                                                                        'code',
                                                                        $stage->nextcessor_code,
                                                                    );

                                                                    // Lấy thời gian biệt trữ chuẩn
                                                                    $stdValue = null;
                                                                    if (in_array($stage->stage_code, [1, 2])) {
                                                                        $stdValue = $stage->quarantine_weight;
                                                                    } elseif ($stage->stage_code == 3) {
                                                                        $stdValue = $stage->quarantine_preparing;
                                                                    } elseif ($stage->stage_code == 4) {
                                                                        $stdValue = $stage->quarantine_blending;
                                                                    } elseif ($stage->stage_code == 5) {
                                                                        $stdValue = $stage->quarantine_forming;
                                                                    } elseif ($stage->stage_code == 6) {
                                                                        $stdValue = $stage->quarantine_coating;
                                                                    }

                                                                    if ($stdValue !== null) {
                                                                        $unitStr =
                                                                            $stage->quarantine_time_unit == 1
                                                                                ? 'd'
                                                                                : 'h';
                                                                        $stdWaiting = $stdValue . $unitStr;
                                                                    }

                                                                    $color_div =
                                                                        $next && $next->finished
                                                                            ? '#007bff'
                                                                            : '#28a745';
                                                                    $expirationDate = null;
                                                                    $stdInMinutes = null;

                                                                    if ($stage->end && $stdValue !== null && $stdValue > 0) {
                                                                        $endTsCopy = strtotime($stage->end);
                                                                        if ($stage->quarantine_time_unit == 1) {
                                                                            $expirationDateTs = strtotime(
                                                                                "+$stdValue days",
                                                                                $endTsCopy,
                                                                            );
                                                                        } else {
                                                                            $expirationDateTs = strtotime(
                                                                                "+$stdValue hours",
                                                                                $endTsCopy,
                                                                            );
                                                                        }
                                                                        $expirationDate = date(
                                                                            'd/m H:i',
                                                                            $expirationDateTs,
                                                                        );
                                                                        $stdInMinutes =
                                                                            $stage->quarantine_time_unit == 1
                                                                                ? $stdValue * 24 * 60
                                                                                : $stdValue * 60;
                                                                    }

                                                                    if ($next && $stage->end && $next->start) {
                                                                        $endTs = strtotime($stage->end);
                                                                        $startNextTs = strtotime($next->start);
                                                                        $diffSecs = $startNextTs - $endTs;
                                                                        $diffDays = floor($diffSecs / 86400);
                                                                        $diffHours = floor(($diffSecs % 86400) / 3600);
                                                                        $waiting =
                                                                            $diffDays . 'd - ' . $diffHours . 'h ';

                                                                        if ($stdInMinutes !== null && $stdInMinutes > 0) {
                                                                            $diffInMinutes = $diffSecs / 60;
                                                                            if ($diffInMinutes > $stdInMinutes) {
                                                                                $isWarning = true;
                                                                            }
                                                                        }
                                                                    } elseif ($stage->end && $stdInMinutes !== null && $stdInMinutes > 0) {
                                                                        // Chưa có next->start, kiểm tra xem hiện tại đã quá hạn biệt trữ chưa
                                                                        $endTs = strtotime($stage->end);
                                                                        $nowTs = time();
                                                                        $diffInMinutes = ($nowTs - $endTs) / 60;
                                                                        if ($diffInMinutes > $stdInMinutes) {
                                                                            $isWarning = true;
                                                                        }
                                                                    }
                                                                @endphp

                                                                <div class="step {{ $loop->first ? 'active' : '' }} {{ $statusClass }}"
                                                                    data-target="#step-{{ $plan_master_id }}-{{ $stageKey }}">
                                                                    <button type="button"
                                                                        class="step-trigger position-relative"
                                                                        role="tab"
                                                                        id="stepper-{{ $plan_master_id }}-trigger-{{ $stageKey }}">
                                                                        <span
                                                                            class="bs-stepper-circle">{{ $stage->stage_code }}</span>
                                                                        <span class="bs-stepper-label">
                                                                            {{ $stage->stage_name }}
                                                                            <small
                                                                                class="d-block">{{ $stage->room_name }}</small>
                                                                            <small
                                                                                class="d-block">{{ $stage->start == null ? '' : date('d/m/Y H:i', strtotime($stage->start)) }}</small>
                                                                            <small
                                                                                class="d-block">{{ $stage->start == null ? '' : date('d/m/Y H:i', strtotime($stage->end)) }}</small>

                                                                            @if (!is_null($stage->yields))
                                                                                <small class="d-block">Yield:
                                                                                    {{ $stage->yields }}
                                                                                    {{ $stage->stage_code <= 4 ? 'Kg' : 'ĐVL' }}</small>
                                                                            @endif
                                                                        </span>
                                                                    </button>
                                                                </div>

                                                                @if (!$loop->last)
                                                                    @if ($next)
                                                                        <div class="waiting-label"
                                                                            style="color: {{ $isWarning ? '#dc3545' : $color_div }} "
                                                                            title="Thực tế: {{ $waiting ?? 'Chưa xếp lịch' }} | Chuẩn: {{ $stdWaiting }}">
                                                                            <span
                                                                                style="font-size: 10px; color: #888;">→
                                                                                {{ $next->stage_name }}</span><br>
                                                                            @if ($waiting)
                                                                                {{ $waiting }}
                                                                            @else
                                                                                <span
                                                                                    style="font-size: 11px; color: #aaa;">--
                                                                                    chờ xếp --</span>
                                                                            @endif
                                                                            @if ($stdWaiting)
                                                                                <br>
                                                                                <span
                                                                                    class="badge {{ $isWarning ? 'badge-danger' : 'badge-light border text-muted' }}"
                                                                                    style="font-size: 11px; font-weight: normal; margin-top: 2px;">
                                                                                    HBT: {{ $stdWaiting }}
                                                                                </span>
                                                                            @endif
                                                                            @if ($expirationDate)
                                                                                <br>
                                                                                <span
                                                                                    style="font-size: 10px; color: {{ $isWarning ? '#dc3545' : '#888' }};">Hạn:
                                                                                    {{ $expirationDate }}</span>
                                                                            @endif
                                                                        </div>
                                                                    @else
                                                                        <div class="waiting-label"
                                                                            style="border-top-color: transparent;">
                                                                        </div>
                                                                    @endif
                                                                @endif
                                                            @endforeach
                                                        </div>

                                                        @if ($branchStages->isNotEmpty())
                                                            <div class="bs-stepper-header" role="tablist"
                                                                style="margin-top: 20px; justify-content: flex-start; padding-left: 20px;">
                                                                @foreach ($branchStages as $i => $stage)
                                                                    @php

                                                                        $stageKey = Str::slug($stage->stage_code, '-');
                                                                        $statusClass = 'step-pending';
                                                                        if ($stage->status == 'scheduled') {
                                                                            $statusClass = 'step-scheduled';
                                                                        } elseif ($stage->status == 'finished') {
                                                                            $statusClass = 'step-finished';
                                                                        } elseif (
                                                                            !empty($stage->end) &&
                                                                            strtotime($stage->end) >
                                                                                strtotime($plan->expected_date)
                                                                        ) {
                                                                            $statusClass = 'step-delay';
                                                                        }

                                                                        if (
                                                                            $lastFinished &&
                                                                            $stage->id == $lastFinished->id
                                                                        ) {
                                                                            $statusClass .= ' step-pointer';
                                                                        }
                                                                        // tính thời gian biệt trữ
                                                                        $waiting = null;
                                                                        $stdWaiting = null;
                                                                        $isWarning = false;

                                                                        // Tìm công đoạn tiếp theo dựa vào nextcessor_code
                                                                        $next = $stages->firstWhere(
                                                                            'code',
                                                                            $stage->nextcessor_code,
                                                                        );

                                                                        // Lấy thời gian biệt trữ chuẩn (công đoạn 2 luôn là cân NL khác)
                                                                        $stdValue = $stage->quarantine_weight;
                                                                        $stdWaiting = null;

                                                                        if ($stdValue !== null) {
                                                                            $unitStr =
                                                                                $stage->quarantine_time_unit == 1
                                                                                    ? 'd'
                                                                                    : 'h';
                                                                            $stdWaiting = $stdValue . $unitStr;
                                                                        }

                                                                        $color_div =
                                                                            $next && $next->finished
                                                                                ? '#007bff'
                                                                                : '#28a745';
                                                                        $expirationDate = null;
                                                                        $stdInMinutes = null;

                                                                        if ($stage->end && $stdValue !== null && $stdValue > 0) {
                                                                            $endTsCopy = strtotime($stage->end);
                                                                            if ($stage->quarantine_time_unit == 1) {
                                                                                $expirationDateTs = strtotime(
                                                                                    "+$stdValue days",
                                                                                    $endTsCopy,
                                                                                );
                                                                            } else {
                                                                                $expirationDateTs = strtotime(
                                                                                    "+$stdValue hours",
                                                                                    $endTsCopy,
                                                                                );
                                                                            }
                                                                            $expirationDate = date(
                                                                                'd/m H:i',
                                                                                $expirationDateTs,
                                                                            );
                                                                            $stdInMinutes =
                                                                                $stage->quarantine_time_unit == 1
                                                                                    ? $stdValue * 24 * 60
                                                                                    : $stdValue * 60;
                                                                        }

                                                                        if ($next && $stage->end && $next->start) {
                                                                            $endTs = strtotime($stage->end);
                                                                            $startNextTs = strtotime($next->start);
                                                                            $diffSecs = $startNextTs - $endTs;
                                                                            $diffDays = floor($diffSecs / 86400);
                                                                            $diffHours = floor(
                                                                                ($diffSecs % 86400) / 3600,
                                                                            );
                                                                            $waiting =
                                                                                $diffDays . 'd - ' . $diffHours . 'h ';

                                                                            if ($stdInMinutes !== null && $stdInMinutes > 0) {
                                                                                $diffInMinutes = $diffSecs / 60;
                                                                                if ($diffInMinutes > $stdInMinutes) {
                                                                                    $isWarning = true;
                                                                                }
                                                                            }
                                                                        } elseif (
                                                                            $stage->end &&
                                                                            $stdInMinutes !== null &&
                                                                            $stdInMinutes > 0
                                                                        ) {
                                                                            // Chưa có next->start, kiểm tra xem hiện tại đã quá hạn biệt trữ chưa
                                                                            $endTs = strtotime($stage->end);
                                                                            $nowTs = time();
                                                                            $diffInMinutes = ($nowTs - $endTs) / 60;
                                                                            if ($diffInMinutes > $stdInMinutes) {
                                                                                $isWarning = true;
                                                                            }
                                                                        }
                                                                    @endphp

                                                                    <div class="step {{ $loop->first ? 'active' : '' }} {{ $statusClass }}"
                                                                        data-target="#step-{{ $plan_master_id }}-{{ $stageKey }}">
                                                                        <button type="button"
                                                                            class="step-trigger position-relative"
                                                                            role="tab"
                                                                            id="stepper-{{ $plan_master_id }}-trigger-{{ $stageKey }}">
                                                                            <span
                                                                                class="bs-stepper-circle">{{ $stage->stage_code }}</span>
                                                                            <span class="bs-stepper-label">
                                                                                {{ $stage->stage_name }}
                                                                                <small
                                                                                    class="d-block">{{ $stage->room_name }}</small>
                                                                                <small
                                                                                    class="d-block">{{ $stage->start == null ? '' : date('d/m/Y H:i', strtotime($stage->start)) }}</small>
                                                                                <small
                                                                                    class="d-block">{{ $stage->start == null ? '' : date('d/m/Y H:i', strtotime($stage->end)) }}</small>

                                                                                @if (!is_null($stage->yields))
                                                                                    <small class="d-block">Yield:
                                                                                        {{ $stage->yields }}
                                                                                        {{ $stage->stage_code <= 4 ? 'Kg' : 'ĐVL' }}</small>
                                                                                @endif
                                                                            </span>
                                                                        </button>
                                                                    </div>

                                                                    @if ($next)
                                                                        <div class="waiting-label"
                                                                            style="color: {{ $isWarning ? '#dc3545' : $color_div }}; width: 150px; flex: 0 0 auto;"
                                                                            title="Thực tế: {{ $waiting ?? 'Chưa xếp lịch' }} | Chuẩn: {{ $stdWaiting }}">
                                                                            <span
                                                                                style="font-size: 10px; color: #888;">→
                                                                                {{ $next->stage_name }}</span><br>
                                                                            @if ($waiting)
                                                                                {{ $waiting }}
                                                                            @else
                                                                                <span
                                                                                    style="font-size: 11px; color: #aaa;">--
                                                                                    chờ xếp --</span>
                                                                            @endif
                                                                            @if ($stdWaiting)
                                                                                <br>
                                                                                <span
                                                                                    class="badge {{ $isWarning ? 'badge-danger' : 'badge-light border text-muted' }}"
                                                                                    style="font-size: 11px; font-weight: normal; margin-top: 2px;">
                                                                                    HBT: {{ $stdWaiting }}
                                                                                </span>
                                                                            @endif
                                                                            @if ($expirationDate)
                                                                                <br>
                                                                                <span
                                                                                    style="font-size: 10px; color: {{ $isWarning ? '#dc3545' : '#888' }};">Hạn:
                                                                                    {{ $expirationDate }}</span>
                                                                            @endif
                                                                        </div>
                                                                    @else
                                                                        <div class="waiting-label"
                                                                            style="border-top-color: transparent; width: 150px; flex: 0 0 auto;">
                                                                        </div>
                                                                    @endif
                                                                @endforeach
                                                            </div>
                                                        @endif
                                                        <!-- Dummy content để tránh lỗi JS Cannot read properties of null -->
                                                        <div class="bs-stepper-content d-none">
                                                            @foreach ($stages as $i => $stage)
                                                                @php $stageKey = Str::slug($stage->stage_code, '-'); @endphp
                                                                <div id="step-{{ $plan_master_id }}-{{ $stageKey }}"
                                                                    class="content" role="tabpanel"></div>
                                                            @endforeach
                                                        </div>
                                                    </div>
                                                </td>
                                                <td style="min-width: 220px; font-size: 14px;">
                                                    <div class="timeline-info mt-2 p-2 border rounded bg-light"
                                                        style="box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                                                        <div
                                                            class="d-flex justify-content-between align-items-center mb-2">
                                                            <span class="text-muted" style="font-size: 12px;"><i
                                                                    class="fas fa-play text-success mr-1"></i> Bắt
                                                                đầu:</span>
                                                            <span
                                                                class="font-weight-bold">{{ date('d/m/Y H:i', strtotime($firstStage->start)) }}</span>
                                                        </div>
                                                        <div
                                                            class="d-flex justify-content-between align-items-center mb-2 pb-2 border-bottom">
                                                            <span class="text-muted" style="font-size: 12px;"><i
                                                                    class="fas fa-stop text-danger mr-1"></i> Kết
                                                                thúc:</span>
                                                            <span
                                                                class="font-weight-bold">{{ date('d/m/Y H:i', strtotime($lastStage->end)) }}</span>
                                                        </div>

                                                        <div
                                                            class="d-flex justify-content-between align-items-center mb-1">
                                                            <span class="text-muted" style="font-size: 12px;"><i
                                                                    class="fas fa-cogs text-primary mr-1"></i>
                                                                TGSX:</span>
                                                            <span
                                                                class="badge badge-primary">{{ $totalProductionHours }}h</span>
                                                        </div>
                                                        <div
                                                            class="d-flex justify-content-between align-items-center mb-2 pb-2 border-bottom">
                                                            <span class="text-muted" style="font-size: 12px;"><i
                                                                    class="fas fa-broom text-warning mr-1"></i>
                                                                TGVS:</span>
                                                            <span
                                                                class="badge badge-warning">{{ $totalCleaningHours }}h</span>
                                                        </div>

                                                        <div
                                                            class="d-flex justify-content-between align-items-center mt-2">
                                                            <strong class="text-success" style="font-size: 12px;"><i
                                                                    class="fas fa-clock mr-1"></i> Tổng TGSX:</strong>
                                                            <strong class="text-success">{{ $totalDuration }}</strong>
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
    </section>
</div>

<script src="{{ asset('js/vendor/jquery-1.12.4.min.js') }}"></script>
<script src="{{ asset('js/popper.min.js') }}"></script>
<script src="{{ asset('js/bootstrap.min.js') }}"></script>
<script src="{{ asset('libs/bs-stepper/js/bs-stepper.min.js') }}"></script>

<script>
    $(document).ready(function() {
        document.body.style.overflowY = "auto";

        $('#data_table_Schedual_step').DataTable({
            paging: true,
            lengthChange: true,
            searching: true,
            ordering: true,
            info: true,
            autoWidth: false,
            pageLength: 10,
            lengthMenu: [
                [10, 25, 50, 100, -1],
                [10, 25, 50, 100, "Tất cả"]
            ],
            language: {
                search: "Tìm kiếm:",
                lengthMenu: "Hiển thị _MENU_ dòng",
                info: "Hiển thị _START_ đến _END_ của _TOTAL_ dòng",
                paginate: {
                    previous: "Trước",
                    next: "Sau"
                }
            },
        });

        $('#btnFilterOverdue').on('click', function() {
            let input = $('#filter_overdue_input');
            if (input.val() == '1') {
                input.val('0');
            } else {
                input.val('1');
                // Bỏ disable các input date để form có thể gửi đi, dù backend sẽ ignore nó
                $('#from_date').prop('disabled', false);
                $('#to_date').prop('disabled', false);
            }
            $('#dateFilterForm').submit();
        });

    })

    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.bs-stepper').forEach(stepperEl => {
            new Stepper(stepperEl, {
                linear: false,
                animation: true
            });
        });
    });

    const form = document.getElementById('dateFilterForm');
    const fromInput = document.getElementById('from_date');
    const toInput = document.getElementById('to_date');

    [fromInput, toInput].forEach(input => {
        input.addEventListener('input', function() {
            form.submit();
        });
    });
</script>
