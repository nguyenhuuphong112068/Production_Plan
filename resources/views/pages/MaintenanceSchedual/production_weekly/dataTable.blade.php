@php
    use Carbon\Carbon;
    $defaultFrom = $selectedDate ? Carbon::parse($selectedDate)->format('Y-m-d') : Carbon::now()->format('Y-m-d');

    // Group rooms by stage_name for the table display
    $groupedByStage = $groupedByRoom->groupBy(function ($rooms) {
        return $rooms->first()->stage_name ?? 'Không xác định';
    })->sortBy(function ($roomsInStage) {
        return $roomsInStage->first()->first()->room_stage_code ?? 999;
    });

    // --- Xác nhận của Lead (chỉ PX Viên 1) ---
    // Đếm theo stage_plan, không đếm theo ô hiển thị vì 1 lịch có thể trải nhiều ngày.
    $leadConfirmEnabled = $leadConfirmEnabled ?? false;
    $canConfirmLead = $canConfirmLead ?? false;
    $leadTotalCount = 0;
    $leadConfirmedCount = 0;

    if ($leadConfirmEnabled) {
        $leadPlans = $groupedByRoom
            ->flatten(1)
            ->filter(fn($e) => $e->sp_id && empty($e->is_cleaning) && !$e->finished)
            ->keyBy('sp_id');
        $leadTotalCount = $leadPlans->count();
        $leadConfirmedCount = $leadPlans->where('comfirm_of_lead', 1)->count();
    }
@endphp

<div class="content-wrapper">
    <div class="card">
        <div class="card-header mt-4"></div>
        <div class="card-body">
            <!-- Filter & Title -->
            <div class="row mx-2">
                <div class="col-md-3">
                    <form id="filterForm" method="GET"
                        action="{{ route('pages.report.weekly_production_schedule.index') }}">
                        @csrf
                        <div class="form-group d-flex align-items-center">
                            <label for="reportedDate" class="mr-2 mb-0">Chọn Tuần:</label>
                            <input type="week" id="reportedDate" name="reportedDate" value="{{ $selectedDate }}"
                                class="form-control" />
                        </div>
                    </form>
                </div>
                <div class="col-md-4 text-center" style="font-size: 20px; color: #CDC717">
                    <div class="font-weight-bold">
                        {{ $displayWeek }}
                    </div>
                    @if ($leadConfirmEnabled)
                        <div id="lead-confirm-progress" class="mt-1" style="font-size: 13px; color: #6c757d;">
                            <i class="fas fa-check-circle text-success"></i>
                            Lead đã xác nhận:
                            <b id="lead-confirmed-count">{{ $leadConfirmedCount }}</b>/<span
                                id="lead-total-count">{{ $leadTotalCount }}</span> lịch
                            @if ($canConfirmLead)
                                <br><span style="font-size: 12px; font-style: italic;">
                                    Tick chọn từng lịch rồi bấm "Xác Nhận" ở tiêu đề công đoạn
                                </span>
                            @endif
                        </div>
                    @endif
                </div>
                <div class="col-md-5 text-right">
                    <button type="button" class="btn btn-info mr-2" onclick="$('.theory-time').toggle()">
                        <i class="fas fa-eye"></i> Hiện/Ẩn Lý Thuyết
                    </button>
                    <button class="btn btn-success mr-2" onclick="exportTableToExcel('excel_export_table', 'Ke_Hoach_Tuan_{{ $selectedDate }}')">
                        <i class="fas fa-file-excel"></i> Xuất Excel
                    </button>
                    <button class="btn btn-primary" onclick="window.print()">
                        <i class="fas fa-print"></i> In Kế Hoạch
                    </button>
                </div>
            </div>

            <!-- Table -->
            <div class="card card-primary mb-4 mt-3">
                <div class="card-header border-transparent" style="background-color: #CDC717;">
                    <h3 class="card-title">Chi Tiết Kế Hoạch Sản Xuất Tuần</h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-tool" data-card-widget="collapse">
                            <i class="fas fa-minus"></i>
                        </button>
                    </div>
                </div>

                <div class="card-body p-0">
                    <div class="table-responsive" style="max-height: 75vh; overflow-y: auto;">
                        <table id="production_weekly_table" class="table table-bordered" style="font-size: 13px;">
                            <thead class="bg-light" style="position: sticky; top: 0; z-index: 10; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                                <tr style="color:#003A4F; font-size: 16px; font-weight: bold;">
                                    <th class="text-center align-middle" width="40" rowspan="2">#</th>
                                    <th class="text-center align-middle" style="min-width: 150px;" rowspan="2">Phòng
                                        SX / Khu Vực</th>
                                    <th class="text-center align-middle" colspan="7">Kế Hoạch Sản Xuất</th>
                                </tr>
                                <tr style="color:#003A4F; font-size: 15px;">
                                    @foreach ($weekDays as $day)
                                        <th class="text-center" width="13%">
                                            {{ $day['label'] }}<br>
                                            <small class="text-muted">{{ $day['display'] }}</small>
                                        </th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    $roomIndex = 1;
                                    $stageCount = 0;
                                @endphp
                                @forelse($groupedByStage as $stageName => $roomsInStage)
                                    @php
                                        $stageId = 'stage_' . (++$stageCount);

                                        // Số liệu xác nhận của riêng công đoạn này (đếm theo stage_plan)
                                        $stageTotalCount = 0;
                                        $stageConfirmedCount = 0;
                                        if ($leadConfirmEnabled) {
                                            $stagePlans = $roomsInStage
                                                ->flatten(1)
                                                ->filter(fn($e) => $e->sp_id && empty($e->is_cleaning) && !$e->finished)
                                                ->keyBy('sp_id');
                                            $stageTotalCount = $stagePlans->count();
                                            $stageConfirmedCount = $stagePlans->where('comfirm_of_lead', 1)->count();
                                        }
                                    @endphp
                                    <!-- Stage Header Row -->
                                    <tr class="stage-header"
                                        style="cursor: pointer; background-color: #CDC717; color: #003a4f; border-top: 2px solid #CDC717;"
                                        data-target=".{{ $stageId }}">
                                        <td colspan="9" class="py-2 px-3">
                                            <div class="d-flex align-items-center">
                                                <i class="fas fa-minus-square mr-2 toggle-icon"
                                                    style="color: #003a4f;"></i>
                                                <span class="font-weight-bold" style="font-size: 15px;">
                                                    Công đoạn: {{ $stageName }}
                                                </span>
                                                <span class="badge ml-2"
                                                    style="background-color: rgba(0, 58, 79, 0.1); color: #003a4f; border: 1px solid #003a4f;">{{ count($roomsInStage) }}
                                                    Phòng</span>

                                                @if ($leadConfirmEnabled && $stageTotalCount > 0)
                                                    <span class="badge ml-2 lead-stage-progress"
                                                        data-stage="{{ $stageId }}"
                                                        style="background-color: #fff; color: #1e7e34; border: 1px solid #1e7e34;">
                                                        <i class="fas fa-check-circle"></i> Đã XN:
                                                        <b class="lead-stage-confirmed">{{ $stageConfirmedCount }}</b>/<span
                                                            class="lead-stage-total">{{ $stageTotalCount }}</span>
                                                    </span>
                                                @endif

                                                {{-- Luôn render để còn chỗ bấm lại sau khi Lead bỏ xác nhận; JS tự ẩn khi công đoạn đã xác nhận hết --}}
                                                @if ($canConfirmLead && $stageTotalCount > 0)
                                                    <div class="ml-auto lead-stage-tools d-flex align-items-center"
                                                        data-stage="{{ $stageId }}">
                                                        <label class="lead-stage-select-all-label">
                                                            <input type="checkbox"
                                                                class="lead-check lead-check-stage lead-stage-select-all"
                                                                data-stage="{{ $stageId }}">
                                                            <span>Chọn tất cả</span>
                                                        </label>
                                                        <button type="button" class="btn btn-success lead-stage-confirm"
                                                            data-stage="{{ $stageId }}" disabled>
                                                            <i class="far fa-check-circle"></i> Xác Nhận
                                                            <span class="lead-stage-badge"><span
                                                                    class="lead-stage-selected">0</span></span>
                                                        </button>
                                                    </div>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>

                                    <!-- Room Rows for this Stage -->
                                    @foreach ($roomsInStage as $roomId => $events)
                                        @php
                                            $firstEvent = $events->first();
                                            $validEventsFlat = $events->whereNotNull('sp_id');

                                            // $roomId chỉ là số thứ tự trong công đoạn (groupBy có callback
                                            // đánh lại chỉ số), nên phải dùng id phòng thật làm khoá.
                                            $roomKey = $firstEvent->room_id;

                                            // Số liệu xác nhận của riêng phòng này (đếm theo stage_plan)
                                            $roomTotalCount = 0;
                                            $roomConfirmedCount = 0;
                                            if ($leadConfirmEnabled) {
                                                $roomPlans = $validEventsFlat
                                                    ->filter(fn($e) => empty($e->is_cleaning) && !$e->finished)
                                                    ->keyBy('sp_id');
                                                $roomTotalCount = $roomPlans->count();
                                                $roomConfirmedCount = $roomPlans->where('comfirm_of_lead', 1)->count();
                                            }
                                        @endphp
                                        <tr class="{{ $stageId }}">
                                            <td class="text-center align-middle bg-light font-weight-bold">
                                                {{ $roomIndex++ }}</td>
                                            <td class="align-middle">
                                                <div class="d-flex align-items-center">
                                                    @if ($canConfirmLead && $roomTotalCount > 0)
                                                        <input type="checkbox"
                                                            class="lead-check lead-check-room lead-room-select-all mr-2"
                                                            data-room="{{ $roomKey }}" data-stage="{{ $stageId }}"
                                                            title="Chọn tất cả lịch chưa xác nhận của phòng này">
                                                    @endif
                                                    <div class="font-weight-bold text-primary" style="font-size: 14px;">
                                                        {{ $firstEvent->room_code }} - {{ $firstEvent->room_name }}
                                                    </div>
                                                </div>
                                                @if ($leadConfirmEnabled && $roomTotalCount > 0)
                                                    <div class="lead-room-progress" data-room="{{ $roomKey }}"
                                                        data-stage="{{ $stageId }}">
                                                        <i class="fas fa-check-circle"></i> Đã XN
                                                        <b class="lead-room-confirmed">{{ $roomConfirmedCount }}</b>/<span
                                                            class="lead-room-total">{{ $roomTotalCount }}</span>
                                                    </div>
                                                @endif
                                            </td>

                                            @foreach ($weekDays as $day)
                                                <td class="align-top p-2"
                                                    style="min-height: 80px; background-color: {{ Carbon::now()->format('Y-m-d') == $day['date'] ? '#e3f2fd' : 'transparent' }};">
                                                    @php
                                                        $dayEvents = $validEventsFlat->where('day_key', $day['date']);
                                                    @endphp

                                                    @if ($dayEvents->count())
                                                        @php $eventIdx = 1; @endphp
                                                        @foreach ($dayEvents as $e)
                                                            @php
                                                                $start = \Carbon\Carbon::parse(
                                                                    $e->slot_start ?? $e->planned_start,
                                                                );
                                                                $end = \Carbon\Carbon::parse(
                                                                    $e->slot_end ?? $e->planned_end,
                                                                );

                                                                $totalMins = $start->diffInMinutes($end);
                                                                $hours = (int) ($totalMins / 60);
                                                                $mins = $totalMins % 60;

                                                                $batchDisplay = $e->actual_batch ?? $e->batch;

                                                                // Thẻ của từng lịch: đổi nền theo trạng thái xác nhận của Lead
                                                                $cardClass = 'event-card';
                                                                if (isset($e->is_actual) && $e->is_actual) {
                                                                    $cardClass .= ' event-card-actual';
                                                                }
                                                                if ($leadConfirmEnabled && empty($e->is_cleaning) && !$e->finished) {
                                                                    $cardClass .= (int) ($e->comfirm_of_lead ?? 0) === 1
                                                                        ? ' event-card-confirmed'
                                                                        : ' event-card-pending';
                                                                }
                                                            @endphp
                                                            <div class="{{ $cardClass }}"
                                                                @if ($leadConfirmEnabled && empty($e->is_cleaning) && !$e->finished) data-sp-id="{{ $e->sp_id }}" @endif>
                                                                <div style="font-size: 13px; color: #333;">
                                                                    <b>{{ $eventIdx++ }}.</b>
                                                                    <span
                                                                        class="{{ isset($e->is_cleaning) && $e->is_cleaning ? 'text-success' : 'text-dark' }} font-weight-bold">
                                                                        {{ $e->display_title }}
                                                                        @if (!(isset($e->is_actual) && $e->is_actual))
                                                                            <span class="badge badge-success ml-1"
                                                                                style="font-weight: normal">Lý
                                                                                Thuyết</span>
                                                                        @endif
                                                                    </span>
                                                                    -
                                                                    <span
                                                                        class="text-danger font-weight-bold">{{ $batchDisplay }}</span>

                                                                    @if ($leadConfirmEnabled && empty($e->is_cleaning))
                                                                        @php
                                                                            $leadConfirmed = (int) ($e->comfirm_of_lead ?? 0) === 1;
                                                                            // Lịch đã hoàn thành thì không còn ý nghĩa để cam kết thực hiện
                                                                            $leadEditable = $canConfirmLead && !$e->finished;
                                                                            $leadTooltip = $leadConfirmed
                                                                                ? 'Lead đã xác nhận: ' .
                                                                                    ($e->comfirm_of_lead_by ?? '') .
                                                                                    ($e->comfirm_of_lead_at
                                                                                        ? ' - ' . Carbon::parse($e->comfirm_of_lead_at)->format('d/m/Y H:i')
                                                                                        : '') .
                                                                                    ($leadEditable ? ' (bấm để bỏ xác nhận)' : '')
                                                                                : 'Chọn để xác nhận sẽ thực hiện theo lịch này';
                                                                        @endphp
                                                                        {{-- Lịch đã hoàn thành và chưa từng được xác nhận thì không hiển thị gì --}}
                                                                        @if ($leadConfirmed || $leadEditable)
                                                                            <span class="lead-confirm"
                                                                                data-sp-id="{{ $e->sp_id }}"
                                                                                data-room="{{ $roomKey }}"
                                                                                data-stage="{{ $stageId }}"
                                                                                data-confirmed="{{ $leadConfirmed ? 1 : 0 }}"
                                                                                data-editable="{{ $leadEditable ? 1 : 0 }}">
                                                                                @if ($leadConfirmed)
                                                                                    <i class="fas fa-check-circle text-success ml-1 lead-confirm-tick {{ $leadEditable ? 'lead-confirm-toggle' : '' }}"
                                                                                        title="{{ $leadTooltip }}"></i>
                                                                                @else
                                                                                    {{-- Chưa xác nhận: tick chọn để Lead lọc lịch nào đồng ý thực hiện --}}
                                                                                    <input type="checkbox" class="lead-check lead-confirm-check ml-1"
                                                                                        data-sp-id="{{ $e->sp_id }}"
                                                                                        data-room="{{ $roomKey }}"
                                                                                        data-stage="{{ $stageId }}"
                                                                                        title="{{ $leadTooltip }}">
                                                                                @endif
                                                                            </span>
                                                                        @endif
                                                                    @endif

                                                                    @if (isset($e->is_actual) && $e->is_actual && empty($e->is_cleaning))
                                                                        @if ($e->finished)
                                                                            <span class="badge badge-primary ml-1">Hoàn
                                                                                thành</span>
                                                                        @endif
                                                                        @if ($e->yields !== null && $e->stage_code >= 3)
                                                                            <span class="badge badge-warning ml-1"
                                                                                style="color: #000;">SL:
                                                                                {{ (float) $e->yields }}
                                                                                {{ $e->stage_code <= 4 ? 'Kg' : 'ĐVL' }}
                                                                                {{ $e->yields_batch_qty > 0 ? '(' . $e->yields_batch_qty . ')' : '' }}</span>
                                                                        @endif
                                                                    @endif
                                                                </div>
                                                                <div class="{{ isset($e->is_actual) && $e->is_actual ? 'text-success' : 'text-primary' }} font-weight-bold mt-1"
                                                                    style="font-size: 12px;">
                                                                    {{ $start->format('H:i') }} ->
                                                                    {{ $end->format('H:i') }}
                                                                    <span class="text-dark"> =
                                                                        {{ $hours }}h{{ $mins }}p</span>
                                                                </div>
                                                                @if (isset($e->is_actual) && $e->is_actual && empty($e->is_cleaning) && $e->finished)
                                                                    @php
                                                                        $thStart = \Carbon\Carbon::parse(
                                                                            $e->planned_start_val,
                                                                        );
                                                                        $thEnd = \Carbon\Carbon::parse(
                                                                            $e->planned_end_val,
                                                                        );
                                                                        $thTotalMins = $thStart->diffInMinutes($thEnd);
                                                                        $thHours = (int) ($thTotalMins / 60);
                                                                        $thMins = $thTotalMins % 60;
                                                                    @endphp
                                                                    <div class="theory-time mt-1 text-secondary"
                                                                        style="display: none; font-size: 11px; font-style: italic;">
                                                                        LT: {{ $thStart->format('d/m H:i') }} ->
                                                                        {{ $thEnd->format('d/m H:i') }} =
                                                                        {{ $thHours }}h{{ $thMins }}p
                                                                    </div>
                                                                @endif
                                                            </div>
                                                        @endforeach
                                                    @endif
                                                </td>
                                            @endforeach
                                        </tr>
                                    @endforeach
                                @empty
                                    <tr>
                                        <td colspan="9" class="text-center py-5">Dữ liệu không tồn tại.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

    <table id="excel_export_table" style="display: none;">
        <thead>
            <tr>
                <th rowspan="2">#</th>
                <th rowspan="2">Phòng SX / Khu Vực</th>
                @foreach ($weekDays as $day)
                    <th colspan="4">{{ $day['label'] }} ({{ $day['display'] }})</th>
                @endforeach
            </tr>
            <tr>
                @foreach ($weekDays as $day)
                    <th>Tên Sản Phẩm</th>
                    <th>Số Lô</th>
                    <th>Tình trạng</th>
                    <th>Thời gian</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @php
                $roomIndexExport = 1;
                $stageCountExport = 0;
            @endphp
            @forelse($groupedByStage as $stageName => $roomsInStage)
                <tr>
                    <td colspan="30" style="font-weight: bold; background-color: #CDC717;">
                        Công đoạn: {{ $stageName }}
                    </td>
                </tr>
                @foreach ($roomsInStage as $roomId => $events)
                    @php
                        $firstEvent = $events->first();
                        $validEventsFlat = $events->whereNotNull('sp_id');
                        
                        $dayEventsMap = [];
                        $maxEvents = 1;
                        foreach ($weekDays as $day) {
                            $dayEvents = $validEventsFlat->where('day_key', $day['date'])->values();
                            $dayEventsMap[$day['date']] = $dayEvents;
                            if ($dayEvents->count() > $maxEvents) {
                                $maxEvents = $dayEvents->count();
                            }
                        }
                    @endphp
                    @for ($i = 0; $i < $maxEvents; $i++)
                        <tr>
                            @if ($i == 0)
                                <td rowspan="{{ $maxEvents }}">{{ $roomIndexExport++ }}</td>
                                <td rowspan="{{ $maxEvents }}">{{ $firstEvent->room_code }} - {{ $firstEvent->room_name }}</td>
                            @endif
                            @foreach ($weekDays as $day)
                                @php
                                    $e = $dayEventsMap[$day['date']][$i] ?? null;
                                @endphp
                                @if ($e)
                                    @php
                                        $start = \Carbon\Carbon::parse($e->slot_start ?? $e->planned_start);
                                        $end = \Carbon\Carbon::parse($e->slot_end ?? $e->planned_end);
                                        
                                        $timeStr = $start->format('H:i') . ' -> ' . $end->format('H:i');
                                        
                                        $batchDisplay = $e->actual_batch ?? $e->batch;
                                        
                                        $bgColor = '';
                                        if (!(isset($e->is_actual) && $e->is_actual)) {
                                            $status = 'Lý Thuyết';
                                            $bgColor = '#d4edda'; // Xanh lá nhạt
                                        } elseif (isset($e->is_cleaning) && $e->is_cleaning) {
                                            $status = 'Vệ sinh';
                                            $bgColor = '#fff3cd'; // Vàng nhạt
                                        } else {
                                            $status = $e->finished ? 'Hoàn thành' : 'Thực tế';
                                            $bgColor = $e->finished ? '#e3f2fd' : '#f0fdf4';
                                        }

                                        if ($leadConfirmEnabled && empty($e->is_cleaning) && (int) ($e->comfirm_of_lead ?? 0) === 1) {
                                            $status .= ' | Lead đã XN' . ($e->comfirm_of_lead_by ? ': ' . $e->comfirm_of_lead_by : '');
                                        }
                                    @endphp
                                    <td style="background-color: {{ $bgColor }};">{{ $e->display_title }}</td>
                                    <td style="background-color: {{ $bgColor }}; mso-number-format:'\@';">{{ $batchDisplay }}</td>
                                    <td style="background-color: {{ $bgColor }};">{{ $status }}</td>
                                    <td style="background-color: {{ $bgColor }};">{{ $timeStr }}</td>
                                @else
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                @endif
                            @endforeach
                        </tr>
                    @endfor
                @endforeach
            @empty
                <tr>
                    <td colspan="30" class="text-center">Dữ liệu không tồn tại.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

<style>
    /* Professional Sticky Header */
    #production_weekly_table thead th {
        background-color: #ffffff !important;
        z-index: 1050 !important;
        border-bottom: 2px solid #dee2e6 !important;
        color: #003a4f !important;
        box-shadow: 0 2px 2px -1px rgba(0, 0, 0, 0.1);
        position: sticky;
    }

    #production_weekly_table thead tr:nth-child(1) th {
        top: 0;
    }

    #production_weekly_table thead tr:nth-child(2) th {
        top: 45px;
        /* Adjust based on the exact height of the first row */
    }

    /* ---- Thẻ của từng lịch: bo góc, đổi nền theo trạng thái ---- */
    .event-card {
        margin-bottom: 6px;
        padding: 5px 7px;
        line-height: 1.3;
        border: 1px solid #e3e6e8;
        border-left: 3px solid #ced4da;
        border-radius: 6px;
        background-color: #fbfcfd;
        transition: background-color .15s ease, border-color .15s ease, box-shadow .15s ease;
    }

    .event-card:last-child {
        margin-bottom: 0;
    }

    /* Lịch đã chạy thực tế */
    .event-card-actual {
        background-color: #f4f8f5;
        border-left-color: #a9c7b2;
    }

    /* Lead CHƯA xác nhận: nền hổ phách nhạt để nổi lên là còn phải xử lý */
    .event-card-pending {
        background-color: #fff8e6;
        border-color: #f2dfae;
        border-left-color: #e0a800;
    }

    .event-card-pending:hover {
        background-color: #fff3d4;
        box-shadow: 0 1px 4px rgba(224, 168, 0, .25);
    }

    /* Lead ĐÃ xác nhận: nền xanh lá nhạt */
    .event-card-confirmed {
        background-color: #eaf7ee;
        border-color: #bfe3c9;
        border-left-color: #28a745;
    }

    .event-card-confirmed:hover {
        background-color: #ddf1e4;
        box-shadow: 0 1px 4px rgba(40, 167, 69, .25);
    }

    #production_weekly_table th,
    #production_weekly_table td {
        border: 1px solid #dee2e6 !important;
    }

    #production_weekly_table td {
        padding: 4px !important;
        vertical-align: top !important;
    }

    .bg-gray-light:hover {
        background-color: #e9ecef !important;
    }

    .toggle-icon {
        transition: transform 0.2s;
    }

    .stage-collapsed .toggle-icon {
        transform: rotate(-90deg);
    }

    /* Xác nhận của Lead */
    .lead-confirm-tick {
        font-size: 14px;
        vertical-align: middle;
        /* transform không áp dụng cho inline element */
        display: inline-block;
    }

    .lead-confirm-toggle {
        cursor: pointer;
    }

    .lead-confirm-toggle:hover {
        transform: scale(1.2);
        transition: transform 0.15s;
    }

    /* ---- Ô tick chọn lịch: vẽ lại hoàn toàn cho đồng nhất mọi trình duyệt ---- */
    .lead-check {
        -webkit-appearance: none;
        appearance: none;
        flex: 0 0 auto;
        display: inline-block;
        position: relative;
        margin: 0;
        width: 16px;
        height: 16px;
        border: 2px solid #b0b8bf;
        border-radius: 4px;
        background-color: #fff;
        cursor: pointer;
        vertical-align: middle;
        transition: border-color .15s ease, background-color .15s ease, box-shadow .15s ease;
    }

    .lead-check:hover {
        border-color: #28a745;
        box-shadow: 0 0 0 3px rgba(40, 167, 69, .18);
    }

    .lead-check:focus {
        outline: none;
        box-shadow: 0 0 0 3px rgba(40, 167, 69, .3);
    }

    .lead-check:checked,
    .lead-check:indeterminate {
        background-color: #28a745;
        border-color: #28a745;
    }

    /* Dấu tick */
    .lead-check:checked::after {
        content: '';
        position: absolute;
        left: 50%;
        top: 47%;
        width: 30%;
        height: 60%;
        border: solid #fff;
        border-width: 0 2px 2px 0;
        transform: translate(-50%, -50%) rotate(45deg);
    }

    /* Dấu gạch ngang khi chọn một phần */
    .lead-check:indeterminate::after {
        content: '';
        position: absolute;
        left: 50%;
        top: 50%;
        width: 55%;
        height: 2px;
        background: #fff;
        border-radius: 1px;
        transform: translate(-50%, -50%);
    }

    /* Ô tick cấp phòng: to hơn ô của từng lịch */
    .lead-check-room {
        width: 19px;
        height: 19px;
        border-radius: 5px;
    }

    /* Ô tick cấp công đoạn: to nhất, nằm trên nền vàng của tiêu đề */
    .lead-check-stage {
        width: 21px;
        height: 21px;
        border-radius: 5px;
        border-color: #003a4f;
    }

    .lead-check-stage:hover {
        border-color: #1e7e34;
        box-shadow: 0 0 0 3px rgba(0, 58, 79, .18);
    }

    .lead-confirm-busy {
        opacity: 0.4 !important;
        pointer-events: none;
    }

    /* ---- Cụm chọn / xác nhận trên tiêu đề công đoạn ---- */
    .lead-stage-tools {
        background-color: rgba(255, 255, 255, .55);
        border: 1px solid rgba(0, 58, 79, .25);
        border-radius: 6px;
        padding: 4px 6px 4px 10px;
    }

    .lead-stage-select-all-label {
        display: flex;
        align-items: center;
        gap: 7px;
        margin: 0 12px 0 0;
        font-size: 13.5px;
        font-weight: 600;
        color: #003a4f;
        cursor: pointer;
        white-space: nowrap;
        user-select: none;
    }

    .lead-stage-confirm {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        font-size: 13.5px;
        font-weight: 600;
        padding: 5px 14px;
        border-radius: 5px;
        box-shadow: 0 1px 2px rgba(0, 0, 0, .15);
        white-space: nowrap;
    }

    .lead-stage-confirm:disabled {
        opacity: .5;
        box-shadow: none;
        cursor: not-allowed;
    }

    /* Số lịch đang chọn */
    .lead-stage-badge {
        display: inline-block;
        min-width: 22px;
        padding: 1px 6px;
        border-radius: 10px;
        background-color: rgba(255, 255, 255, .28);
        font-size: 12.5px;
        line-height: 1.5;
        text-align: center;
    }

    /* ---- Tiến độ theo phòng ---- */
    .lead-room-progress {
        margin-top: 3px;
        font-size: 11.5px;
        color: #6c757d;
        white-space: nowrap;
    }

    .lead-room-progress i {
        color: #28a745;
    }

    @media print {
        @page {
            size: landscape;
            margin: 5mm;
        }

        .card-header mt-4,
        .card-tools,
        #filterForm,
        .btn-primary,
        .btn-secondary,
        .toggle-icon {
            display: none !important;
        }

        .content-wrapper {
            margin: 0 !important;
            padding: 0 !important;
        }

        #production_weekly_table {
            width: 100% !important;
            font-size: 10px !important;
        }

        .stage-header {
            background-color: #d1b400 !important;
            color: #003a4f !important;
            -webkit-print-color-adjust: exact;
        }

        /* Bản in chỉ giữ tick của lịch đã được Lead xác nhận */
        .lead-stage-tools,
        .lead-check,
        .lead-confirm[data-confirmed="0"] {
            display: none !important;
        }

        .lead-confirm-tick {
            color: #28a745 !important;
            -webkit-print-color-adjust: exact;
        }

        /* Giữ màu nền phân biệt đã/chưa xác nhận khi in */
        .event-card {
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
            break-inside: avoid;
        }
    }
</style>

<script src="{{ asset('js/vendor/jquery-1.12.4.min.js') }}"></script>
<script src="{{ asset('js/sweetalert2.all.min.js') }}"></script>
<script>
    $(document).ready(function() {
        document.body.style.overflowY = "auto";

        // Handle stage collapse/expand
        $('.stage-header').on('click', function(e) {
            // Bấm vào cụm chọn/xác nhận của Lead thì không thu gọn công đoạn
            if ($(e.target).closest('.lead-stage-tools').length) {
                return;
            }

            var target = $(this).data('target');
            $(target).toggle();
            $(this).toggleClass('stage-collapsed');

            // Update icon
            var icon = $(this).find('.toggle-icon');
            if ($(target).is(':visible')) {
                icon.removeClass('fa-plus-square').addClass('fa-minus-square');
            } else {
                icon.removeClass('fa-minus-square').addClass('fa-plus-square');
            }
        });
    });

    function toggleAllStages() {
        var isHiding = $('.stage-header').first().find('.toggle-icon').hasClass('fa-minus-square');

        $('.stage-header').each(function() {
            var target = $(this).data('target');
            var icon = $(this).find('.toggle-icon');

            if (isHiding) {
                $(target).hide();
                $(this).addClass('stage-collapsed');
                icon.removeClass('fa-minus-square').addClass('fa-plus-square');
            } else {
                $(target).show();
                $(this).removeClass('stage-collapsed');
                icon.removeClass('fa-plus-square').addClass('fa-minus-square');
            }
        });
    }

    $(function() {
        $('#reportedDate').on('change', function() {
            $('#filterForm').submit();
        });
    });

    // ================== XÁC NHẬN CỦA LEAD (chỉ PX Viên 1) ==================
    // Lead tick chọn từng lịch mình đồng ý thực hiện, rồi bấm "Xác Nhận"
    // ngay trên tiêu đề của công đoạn tương ứng.
    @if ($canConfirmLead)
        var LEAD_CONFIRM_URL = "{{ route('pages.report.weekly_production_schedule.confirmLead') }}";

        function leadAlert(icon, title, text) {
            if (window.Swal) {
                Swal.fire({
                    icon: icon,
                    title: title,
                    text: text || '',
                    timer: icon === 'success' ? 1500 : undefined,
                    showConfirmButton: icon !== 'success'
                });
            } else if (icon !== 'success') {
                alert(title + (text ? '\n' + text : ''));
            }
        }

        function leadAsk(title, text, okText, cb) {
            if (window.Swal) {
                Swal.fire({
                    icon: 'question',
                    title: title,
                    text: text,
                    showCancelButton: true,
                    confirmButtonText: okText,
                    cancelButtonText: 'Đóng'
                }).then(function(r) {
                    if (r.isConfirmed) cb();
                });
            } else if (confirm(title + (text ? '\n' + text : ''))) {
                cb();
            }
        }

        // Gom sp_id duy nhất từ một tập phần tử (1 lịch trải nhiều ngày => nhiều phần tử)
        function leadUniqueIds($els) {
            var ids = [];
            $els.each(function() {
                var id = $(this).attr('data-sp-id');
                if (ids.indexOf(id) === -1) ids.push(id);
            });
            return ids;
        }

        // Vẽ lại trạng thái của TẤT CẢ ô thuộc các stage_plan vừa đổi
        function leadRenderState($groups, confirmed, by, at) {
            $groups.each(function() {
                var $g = $(this);
                var editable = $g.attr('data-editable') == 1;

                $g.attr('data-confirmed', confirmed ? 1 : 0);

                // Đổi nền thẻ của lịch (thẻ và ô tick đều mang data-sp-id)
                $('.event-card[data-sp-id="' + $g.attr('data-sp-id') + '"]')
                    .toggleClass('event-card-confirmed', !!confirmed)
                    .toggleClass('event-card-pending', !confirmed);

                if (confirmed) {
                    $g.html($('<i>')
                        .addClass('fas fa-check-circle text-success ml-1 lead-confirm-tick')
                        .addClass(editable ? 'lead-confirm-toggle' : '')
                        .attr('title', 'Lead đã xác nhận: ' + (by || '') + (at ? ' - ' + at : '') +
                            ' (bấm để bỏ xác nhận)'));
                } else if (editable) {
                    $g.html($('<input type="checkbox">')
                        .addClass('lead-check lead-confirm-check ml-1')
                        .attr('data-sp-id', $g.attr('data-sp-id'))
                        .attr('data-room', $g.attr('data-room'))
                        .attr('data-stage', $g.attr('data-stage'))
                        .attr('title', 'Chọn để xác nhận sẽ thực hiện theo lịch này'));
                } else {
                    $g.html('');
                }
            });
        }

        // Đặt trạng thái cho một ô "chọn tất cả" theo số đang chọn / còn lại
        function leadSetMasterBox($box, selected, remaining) {
            $box.prop('checked', remaining > 0 && selected === remaining);
            if ($box.length && $box[0]) {
                $box[0].indeterminate = selected > 0 && selected < remaining;
            }
        }

        // Cập nhật ô "chọn tất cả" + số đã xác nhận của một phòng
        function leadRefreshRoom(roomId) {
            var $box = $('.lead-room-select-all[data-room="' + roomId + '"]');
            var $checks = $('.lead-confirm-check[data-room="' + roomId + '"]');
            var remaining = leadUniqueIds($checks).length;

            // Phòng đã xác nhận hết thì ẩn ô chọn cho gọn
            $box.toggle(remaining > 0);
            leadSetMasterBox($box, leadUniqueIds($checks.filter(':checked')).length, remaining);

            $('.lead-room-progress[data-room="' + roomId + '"] .lead-room-confirmed')
                .text(leadUniqueIds(
                    $('.lead-confirm[data-room="' + roomId + '"][data-editable="1"][data-confirmed="1"]')
                ).length);
        }

        function leadRefreshRoomsInStage(stageId) {
            $('.lead-room-progress[data-stage="' + stageId + '"]').each(function() {
                leadRefreshRoom($(this).attr('data-room'));
            });
        }

        // Cập nhật số đã xác nhận + nút của một công đoạn (tính lại từ DOM cho chắc)
        function leadRefreshStage(stageId) {
            var $tools = $('.lead-stage-tools[data-stage="' + stageId + '"]');
            var $checks = $('.lead-confirm-check[data-stage="' + stageId + '"]');
            var selected = leadUniqueIds($checks.filter(':checked')).length;
            var remaining = leadUniqueIds($checks).length;

            $tools.find('.lead-stage-selected').text(selected);
            $tools.find('.lead-stage-confirm').prop('disabled', selected === 0);
            // Công đoạn đã xác nhận hết thì ẩn ô chọn cho gọn
            $tools.toggle(remaining > 0);

            leadSetMasterBox($tools.find('.lead-stage-select-all'), selected, remaining);

            $('.lead-stage-progress[data-stage="' + stageId + '"] .lead-stage-confirmed')
                .text(leadUniqueIds(
                    $('.lead-confirm[data-stage="' + stageId + '"][data-editable="1"][data-confirmed="1"]')
                ).length);
        }

        function leadRefreshAll() {
            $('.lead-room-progress').each(function() {
                leadRefreshRoom($(this).attr('data-room'));
            });
            $('.lead-stage-progress').each(function() {
                leadRefreshStage($(this).attr('data-stage'));
            });
            $('#lead-confirmed-count').text(
                leadUniqueIds($('.lead-confirm[data-editable="1"][data-confirmed="1"]')).length
            );
        }

        function leadSendConfirm(ids, confirmed, $busy) {
            $busy.addClass('lead-confirm-busy');

            $.ajax({
                url: LEAD_CONFIRM_URL,
                type: 'POST',
                data: {
                    // Gửi dạng chuỗi để không vướng giới hạn max_input_vars khi chọn nhiều lịch
                    ids: ids.join(','),
                    confirmed: confirmed ? 1 : 0,
                    _token: "{{ csrf_token() }}"
                },
                success: function(res) {
                    var $groups = $();
                    $.each(res.ids, function(i, id) {
                        $groups = $groups.add('.lead-confirm[data-sp-id="' + id + '"]');
                    });

                    leadRenderState($groups, res.confirmed, res.comfirm_of_lead_by, res.comfirm_of_lead_at);
                    leadRefreshAll();

                    leadAlert('success',
                        res.confirmed ? 'Đã xác nhận ' + res.ids.length + ' lịch' : 'Đã bỏ xác nhận');
                },
                error: function(xhr) {
                    leadAlert('error', 'Không thể xác nhận',
                        (xhr.responseJSON && xhr.responseJSON.message) || 'Vui lòng thử lại.');
                },
                complete: function() {
                    $busy.removeClass('lead-confirm-busy');
                }
            });
        }

        // --- Tick chọn từng lịch: đồng bộ mọi ô của cùng 1 stage_plan ---
        $(document).on('click', '.lead-confirm-check', function(e) {
            e.stopPropagation();
        });

        $(document).on('change', '.lead-confirm-check', function() {
            var $me = $(this);
            $('.lead-confirm-check[data-sp-id="' + $me.attr('data-sp-id') + '"]')
                .prop('checked', $me.prop('checked'));
            leadRefreshRoom($me.attr('data-room'));
            leadRefreshStage($me.attr('data-stage'));
        });

        // --- Chọn tất cả lịch của một phòng ---
        $(document).on('change', '.lead-room-select-all', function() {
            var $me = $(this);
            var roomId = $me.attr('data-room');
            $('.lead-confirm-check[data-room="' + roomId + '"]').prop('checked', $me.prop('checked'));
            leadRefreshRoom(roomId);
            leadRefreshStage($me.attr('data-stage'));
        });

        // --- Chọn tất cả trong công đoạn ---
        $(document).on('click', '.lead-stage-select-all, .lead-stage-select-all-label', function(e) {
            e.stopPropagation();
        });

        $(document).on('change', '.lead-stage-select-all', function() {
            var stageId = $(this).attr('data-stage');
            $('.lead-confirm-check[data-stage="' + stageId + '"]').prop('checked', $(this).prop('checked'));
            leadRefreshRoomsInStage(stageId);
            leadRefreshStage(stageId);
        });

        // --- Nút xác nhận trên tiêu đề công đoạn ---
        $(document).on('click', '.lead-stage-confirm', function(e) {
            e.stopPropagation();

            var $btn = $(this);
            var stageId = $btn.attr('data-stage');
            var ids = leadUniqueIds($('.lead-confirm-check[data-stage="' + stageId + '"]:checked'));

            if (!ids.length) {
                leadAlert('info', 'Chưa chọn lịch nào', 'Tick chọn các lịch muốn xác nhận trước.');
                return;
            }

            leadAsk('Xác nhận ' + ids.length + ' lịch của công đoạn này?',
                'Bạn cam kết sẽ thực hiện theo lịch của người sắp lịch đặt ra.',
                'Xác nhận',
                function() {
                    leadSendConfirm(ids, true, $('.lead-confirm[data-stage="' + stageId + '"]'));
                });
        });

        // --- Bấm tick xanh để bỏ xác nhận 1 lịch ---
        $(document).on('click', '.lead-confirm-toggle', function(e) {
            e.stopPropagation();

            var spId = $(this).closest('.lead-confirm').attr('data-sp-id');
            leadAsk('Bỏ xác nhận lịch này?', '', 'Bỏ xác nhận', function() {
                leadSendConfirm([spId], false, $('.lead-confirm[data-sp-id="' + spId + '"]'));
            });
        });

        $(function() {
            leadRefreshAll();
        });
    @endif


    function exportTableToExcel(tableID, filename = ''){
        var downloadLink;
        var dataType = 'application/vnd.ms-excel';
        var tableSelect = document.getElementById(tableID);
        var tableHTML = '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40"><head><meta charset="utf-8" /><style>table { border-collapse: collapse; } th, td { border: 1px solid black; white-space: nowrap; }</style></head><body>' + tableSelect.outerHTML + '</body></html>';
        
        filename = filename ? filename + '.xls' : 'KeHoachTuan.xls';
        
        var blob = new Blob(['\ufeff' + tableHTML], {
            type: dataType
        });
        
        if (navigator.msSaveOrOpenBlob) {
            navigator.msSaveOrOpenBlob(blob, filename);
        } else {
            downloadLink = document.createElement("a");
            document.body.appendChild(downloadLink);
            downloadLink.href = URL.createObjectURL(blob);
            downloadLink.download = filename;
            downloadLink.click();
            document.body.removeChild(downloadLink);
        }
    }
</script>
