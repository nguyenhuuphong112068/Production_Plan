<style>
    .note-content {
        white-space: pre-line;
    }
</style>

<div class="content-wrapper">
    <!-- Main content -->
    <div class="card">
        <div class="card-header mt-4"></div>
        @php

            $update_daily_report = user_has_permission(session('user')['userId'], 'update_daily_report', 'boolean');

            $stage_name = [
                1 => 'Cân Nguyên Liệu',
                3 => 'Pha Chế',
                4 => 'Trộn Hoàn Tất',
                5 => 'Định Hình',
                6 => 'Bao Phim',
                7 => 'ĐGSC - ĐGTC',
            ];

            $group_name = [
                1 => 'Cân Nguyên Liệu',
                3 => 'Pha Chế',
                4 => 'Sủi',
                5 => 'Định Hình',
                6 => 'Bao Phim',
                7 => 'ĐGSC - ĐGTC',
            ];

        @endphp
        <!-- /.card-Body -->
        <div class="card-body">
            <!-- Tiêu đề -->
            <div class ="row mx-2">
                <div class ="col-md-3">
                    <form id="filterForm" method="GET" action="{{ route('pages.report.daily_report.index') }}"
                        class="d-flex flex-wrap gap-0">
                        @csrf
                        <div class="row w-100 align-items-center">
                            <!-- Filter From/To -->
                            <div class="col-md-4 d-flex gap-2">
                                @php
                                    use Carbon\Carbon;
                                    $defaultFrom = $reportedDate
                                        ? Carbon::createFromFormat('!d/m/Y', trim($reportedDate))->format('Y-m-d')
                                        : Carbon::now()->format('Y-m-d');

                                @endphp
                                <div class="form-group d-flex align-items-center">
                                    <label for="reportedDate" class="mr-2 mb-0">Chọn Ngày:</label>
                                    <input type="date" id="reportedDate" name="reportedDate"
                                        value="{{ $defaultFrom }}" class="form-control"
                                        max="{{ \Carbon\Carbon::now()->format('Y-m-d') }}" />
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="col-md-6 text-center" style="font-size: 20px;color: #CDC717;">
                    <div>
                        Báo cáo được tính từ 06:00 ngày {{ Carbon::parse($defaultFrom)->format('d/m/Y') }}
                        đến 06:00 ngày {{ Carbon::parse($defaultFrom)->addDays(1)->format('d/m/Y') }}
                    </div>
                </div>
                <div class ="col-md-3">
                </div>
            </div>

            <!-- Sản Lượng -->
            <div class="card card-success mb-4">
                <div class="card-header border-transparent">
                    <h3 class="card-title">
                        Sản Lượng
                    </h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-tool" data-card-widget="collapse">
                            <i class="fas fa-minus"></i>
                        </button>
                        <button type="button" class="btn btn-tool" data-card-widget="remove">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>

                <div class="card-body">
                    <table id="data_table_yield" class="table table-bordered table-striped" style="font-size: 15px;">
                        <thead style="position: sticky; top: 60px; background-color: white; z-index: 1020;">
                            <tr style="color:#003A4F; font-size: 20px; font-weight: bold;">
                                <th class="text-center" style="max-width: 200px;">Phòng SX</th>
                                <th class="text-center" style="width: 3% ">ĐV</th>
                                <th class="text-center" style="width: 5% ">Sản lượng lý thuyết</th>
                                <th class="text-center" style="width: 5% ">Sản lượng thực tế</th>
                                <th class="text-center" style="width: 5% ">Phần trăm đáp ứng</th>
                                <th class="text-center">Chi tiết</th>
                            </tr>
                        </thead>

                        <tbody style="font-size: 20px;">
                            @php
                                $roomsByStage = $theory['yield_room']->groupBy('stage_code');
                            @endphp

                            @foreach ($roomsByStage as $stage_code => $rooms)
                                {{-- Tính tổng công đoạn trước --}}
                                @php
                                    $stageLT = [];
                                    $stageTT = [];

                                    $stagePercent = [];

                                    $dayLT = $theory['yield_day'] ?? collect();
                                    $stageLT = $dayLT->where('stage_code', $stage_code)->sum('total_qty');

                                    $dayTT = $yield_actual_detial['yield_day'];
                                    $stageTT = $dayTT->where('stage_code', $stage_code)->sum('total_qty');

                                    if ($stageLT == 0) {
                                        $stagePercent = 100;
                                    } else {
                                        $stagePercent = $stageLT > 0 ? ($stageTT / $stageLT) * 100 : 0;
                                    }

                                    if ($stage_code == 5) {
                                        $sum_coating = $dayTT
                                            ->where('stage_code', 5)
                                            ->where('table_type', 'coating')
                                            ->sum('total_qty');
                                        $sum_capsule = $dayTT
                                            ->where('stage_code', 5)
                                            ->where('table_type', 'capsule')
                                            ->sum('total_qty');
                                        $sum_tablet = $dayTT
                                            ->where('stage_code', 5)
                                            ->where('table_type', 'tablet')
                                            ->sum('total_qty');

                                        $sum_theory_coating = $dayLT
                                            ->where('stage_code', 5)
                                            ->where('table_type', 'coating')
                                            ->sum('total_qty');
                                        $sum_theory_capsule = $dayLT
                                            ->where('stage_code', 5)
                                            ->where('table_type', 'capsule')
                                            ->sum('total_qty');
                                        $sum_theory_tablet = $dayLT
                                            ->where('stage_code', 5)
                                            ->where('table_type', 'tablet')
                                            ->sum('total_qty');
                                    }

                                    if ($stage_code == 4) {
                                        $stageLT_unit = $dayLT->where('stage_code', 4)->sum('total_qty_unit');
                                        $stageTT_unit = $dayTT->where('stage_code', 4)->sum('total_qty_unit');
                                    }

                                @endphp

                                {{-- ⭐ Dòng tổng công đoạn --}}
                                <tr style="background:#CDC717; color:#003A4F; font-weight:bold; cursor: pointer;"
                                    class="stage-total" data-stage="{{ $stage_code }}">
                                    <td class="text-end">
                                        <button type="button" class="btn btn-sm btn-info toggle-stage"
                                            style="width: 20px; height: 20px; padding: 0; line-height: 0;"
                                            data-stage="{{ $stage_code }}">+</button>
                                        Công Đoạn {{ $stage_name[$stage_code] ?? $stage_code }}
                                    </td>

                                    {{-- @foreach ($allDates as $date) --}}
                                    <td class="text-center">{{ $stage_code <= 4 ? 'Kg' : 'ĐVL' }}</td>
                                    <td class="text-center">
                                        {{ number_format($stageLT, 2) }}
                                        {{ $stage_code == 4 ? '# ' . number_format($stageLT_unit, 2) : '' }}

                                        @if ($stage_code == 5)
                                            <br><b>Tablet:</b>{{ number_format($sum_theory_tablet, 2) }}
                                            <br><b>Coating:</b>{{ number_format($sum_theory_coating, 2) }}
                                            <br><b>Capsule:</b>{{ number_format($sum_theory_capsule, 2) }}
                                        @endif

                                        @php
                                            $dayLT_events = collect($yield_theoryl_detial['yield_day'] ?? []);
                                            $stageLT_detail = $dayLT_events
                                                ->where('stage_code', $stage_code)
                                                ->pluck('yield_theory_detial')
                                                ->filter()
                                                ->implode('<br>');
                                        @endphp
                                        {{-- <div
                                            style="font-size: 11px; color: #555; font-style: italic; margin-top: 5px; font-weight: normal;">
                                            {!! $stageLT_detail !!}
                                        </div> --}}
                                    </td>
                                    <td class="text-center">
                                        {{ number_format($stageTT, 2) }}
                                        {{ $stage_code == 4 ? '# ' . number_format($stageTT_unit, 2) : '' }}

                                        @if ($stage_code == 5)
                                            <br><b>Tablet:</b>{{ number_format($sum_tablet, 2) }}
                                            <br><b>Coating:</b>{{ number_format($sum_coating, 2) }}
                                            <br><b>Capsule:</b>{{ number_format($sum_capsule, 2) }}
                                        @endif
                                    </td>
                                    <td class="text-center "
                                        style="background: {{ number_format($stagePercent, 2) < 90 ? 'red' : '#CDC717' }}">
                                        {{ number_format($stagePercent, 2) }}%
                                    </td>

                                    <td class="text-left note-content">
                                        {{ trim($explanation[$stage_code] ?? '-') }}
                                        <button type="button" class="btn btn-sm btn-explain"
                                            data-stage_code="{{ $stage_code }}"
                                            data-reported_date="{{ $defaultFrom }}" data-toggle="modal"
                                            data-target="#explanation">
                                            📝
                                        </button>
                                    </td>
                                    {{-- @endforeach --}}


                                </tr>

                                {{-- ⭐ Lặp các phòng trong stage --}}
                                @foreach ($rooms as $roomLT)
                                    @php
                                        $resourceId = $roomLT->resourceId;
                                        $unit = $roomLT->unit;
                                    @endphp

                                    <tr class="stage-child stage-{{ $stage_code }}">
                                        <td class="align-middle">{{ $roomLT->room_code . ' - ' . $roomLT->room_name }}
                                        </td>

                                        @php
                                            // LT
                                            $dayLT = $theory['yield_day'] ?? collect();
                                            $qtyLT = $dayLT->where('resourceId', $resourceId)->sum('total_qty');
                                            $qtyLT_unit = $dayLT->where('resourceId', $resourceId)->sum('total_qty_unit');

                                            // TT
                                            $dayTT = $yield_actual_detial['yield_day'] ?? collect();
                                            $qtyTT = $dayTT->where('resourceId', $resourceId)->sum('total_qty');
                                            $qtyTT_unit = $dayTT->where('resourceId', $resourceId)->sum('total_qty_unit');

                                            // %

                                            if ($qtyTT > 0 && $qtyLT > 0) {
                                                $percent = ($qtyTT / $qtyLT) * 100;
                                            } elseif ($qtyTT == 0 && $qtyLT == 0) {
                                                $percent = 0;
                                            } elseif ($qtyTT > 0 && $qtyLT == 0) {
                                                $percent = 100;
                                            } else {
                                                $percent = 0;
                                            }

                                            // Chi tiết đúng chuẩn
                                            $detail = $detail = collect(
                                                $yield_actual_detial['actual_detail'] ?? [],
                                            )->where('resourceId', $resourceId);
                                            // ->where('reported_date', $date);
                                        @endphp


                                        {{-- ĐV --}}
                                        <td class="text-center">{{ $stage_code <= 4 ? 'Kg' : 'ĐVL' }}</td>

                                        {{-- LT --}}
                                        <td class="text-center" style="background:#93f486;">
                                            <div>{{ number_format($qtyLT, 2) }}</div>
                                            <div>{{ $stage_code == 4 ? '# ' . number_format($qtyLT_unit, 2) : '' }}
                                            </div>
                                            @php
                                                $dayLT_events = collect($yield_theoryl_detial['yield_day'] ?? []);
                                            @endphp
                                            <div
                                                style="font-size: 11px; color: #555; font-style: italic; margin-top: 5px;">
                                                @php
                                                    $itemLT_events = $dayLT_events->where('resourceId', $resourceId);
                                                @endphp
                                                {!! $itemLT_events->pluck('yield_theory_detial')->filter()->implode('<br>') !!}
                                            </div>
                                        </td>

                                        {{-- TT --}}
                                        <td class="text-center" style="background:#69b8f4;">
                                            {{ number_format($qtyTT, 2) }}
                                            {{ $stage_code == 4 ? '# ' . number_format($qtyTT_unit, 2) : '' }}
                                        </td>

                                        {{-- % --}}
                                        <td class="text-center"
                                            style="background: {{ $percent < 90 ? 'red' : 'none' }}">
                                            {{ number_format($percent, 2) }}%
                                        </td>



                                        {{-- CHI TIẾT --}}
                                        <td class="text-left" style="background:#d7eaff; font-size:14px;">
                                            @if ($update_daily_report)
                                                <button class="btn btn-success btn-sm btn-plus float-right"
                                                    style="width: 20px; height: 20px; padding: 0; line-height: 0;"
                                                    data-room_code = "{{ $roomLT->room_code }}"
                                                    data-room_name = "{{ $roomLT->room_name }}"
                                                    data-room_id = "{{ $roomLT->resourceId }}" data-toggle="modal"
                                                    data-target="#Modal"
                                                    title = "Tạo mới Báo Cáo Hoạt Động Khác">+</button>
                                            @endif

                                            @php
                                                // --- Cấu hình ca ---
                                                $shiftStart = Carbon::createFromFormat(
                                                    'd/m/Y H:i:s',
                                                    $reportedDate . ' 06:00:00',
                                                );
                                                $shiftEnd = $shiftStart->copy()->addDay();

                                                // Lưu các đoạn đã chuẩn hóa
                                                $intervals = [];

                                                foreach ($detail as $d) {
                                                    $start = Carbon::parse($d->start);
                                                    $end = Carbon::parse($d->end);

                                                    if ($end < $start) {
                                                        $end->addDay();
                                                    }

                                                    // Giới hạn trong ca
                                                    $realStart = $start->max($shiftStart);
                                                    $realEnd = $end->min($shiftEnd);

                                                    if ($realEnd > $realStart) {
                                                        $intervals[] = [
                                                            'start' => $realStart,
                                                            'end' => $realEnd,
                                                        ];
                                                    }
                                                }

                                                // Nếu không có khoảng nào
                                                if (count($intervals) === 0) {
                                                    $totalActiveSeconds = 0;
                                                } else {
                                                    // 1. Sắp xếp theo thời gian bắt đầu
                                                    usort($intervals, function ($a, $b) {
                                                        return $a['start']->timestamp <=> $b['start']->timestamp;
                                                    });

                                                    // 2. Gộp khoảng
                                                    $merged = [];
                                                    $current = $intervals[0];

                                                    foreach ($intervals as $int) {
                                                        if ($int['start'] <= $current['end']) {
                                                            // chồng nhau → kéo dài đoạn hiện tại
                                                            $current['end'] = $int['end']->max($current['end']);
                                                        } else {
                                                            // không chồng → add vào list
                                                            $merged[] = $current;
                                                            $current = $int;
                                                        }
                                                    }
                                                    $merged[] = $current;

                                                    // 3. Tính tổng thời gian
                                                    $totalActiveSeconds = 0;
                                                    foreach ($merged as $m) {
                                                        $totalActiveSeconds += $m['start']->diffInSeconds($m['end']);
                                                    }
                                                }

                                                // Tổng ca
                                                $totalShiftSeconds = $shiftStart->diffInSeconds($shiftEnd);

                                                // Thời gian chết
                                                $totalDeadSeconds = $totalShiftSeconds - $totalActiveSeconds;

                                                // Giờ phút
                                                $activityHours = floor($totalActiveSeconds / 3600);
                                                $activityMinutes = floor(($totalActiveSeconds % 3600) / 60);

                                                $deadHours = floor($totalDeadSeconds / 3600);
                                                $deadMinutes = floor(($totalDeadSeconds % 3600) / 60);
                                            @endphp

                                            @if ($detail->count())
                                                @php $i = 1; @endphp
                                                @foreach ($detail as $d)
                                                    <div style="display: flex; flex-direction: row; gap: 3px;">
                                                        @php
                                                            $start = \Carbon\Carbon::parse($d->start);
                                                            $end = \Carbon\Carbon::parse($d->end);

                                                            // Nếu end nhỏ hơn start => qua ngày hôm sau
                                                            if ($end->lessThan($start)) {
                                                                $end->addDay();
                                                            }

                                                            $minutes = $start->diffInMinutes($end);
                                                            $hours = intdiv($minutes, 60);
                                                            $mins = $minutes % 60;
                                                            // dd ($d);
                                                        @endphp

                                                        {{ $i++ . '. ' }}
                                                        {{ $d->title == null && $d->yields == null ? 'VS' : $d->title }}
                                                        ({{ $start->format('H:i') }} - {{ $end->format('H:i') }} = <b>
                                                            {{ $hours }}h{{ $mins }}p </b>)
                                                        @if ($d->yields)
                                                            || <b>{{ 'Sản Lượng: ' . number_format($d->yields, 2) }}
                                                                {{ $d->unit }}
                                                                {{ $d->yields_batch_qty ? "# $d->yields_batch_qty  ĐVL" : '' }}</b>
                                                        @endif

                                                        @if ($d->note && $d->note != 'NA')
                                                            || <b>{{ 'Ghi Chú: ' . $d->note }} </b>
                                                        @endif

                                                        @if ($d->is_order_action && $update_daily_report)
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

                                                            <form class="form-deActive"
                                                                action="{{ route('pages.report.daily_report.deActive') }}"
                                                                method="post">
                                                                @csrf
                                                                <input type="hidden" name="id"
                                                                    value="{{ $d->id }}">
                                                                <button class="btn btn-danger btn-sm btn-deactive"
                                                                    title = "Hủy Báo Cáo Hoạt Động Khác"
                                                                    style="width: 20px; height: 20px; padding: 0; line-height: 0;">
                                                                    <i class="fas fa-trash"></i>
                                                                </button>
                                                            </form>
                                                        @endif
                                                    </div>
                                                @endforeach
                                                <div>
                                                    <b>Tổng thời gian xác định:</b> {{ $activityHours }} giờ
                                                    {{ $activityMinutes }} phút
                                                    <br>
                                                    <b>Tổng thời gian không xác định:</b> {{ $deadHours }} giờ
                                                    {{ $deadMinutes }} phút
                                                </div>
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif

                                        </td>
                                    </tr>
                                @endforeach
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Tồn Kho -->
            <div class="card card-primary mb-4">
                <div class="card-header border-transparent">
                    <h3 class="card-title">
                        {{ 'Phân Bố Tồn Kho' }}
                    </h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-tool" data-card-widget="collapse">
                            <i class="fas fa-minus"></i>
                        </button>
                        <button type="button" class="btn btn-tool" data-card-widget="remove">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>

                <div class="card-body">
                    <table id="data_table_instrument" class="table table-bordered table-striped">
                        <thead style=" position: sticky; top: 60px;  z-index: 1020;">
                            <tr>
                                <th>STT</th>
                                <th>Tên Phòng - Thiết Bị Chính</th>
                                <th>Công Đoạn Tiếp Theo</th>
                                <th>Tồn Thực Tế Công Đoạn trước</th>
                                <th class ="text-center">Chi Tiết</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php $group_code_current = null; @endphp

                            @foreach ($sum_by_next_room as $key_room => $data)
                                @if ($group_code_current != $data->group_code)
                                    <tr style="background:#CDC717; color:#003A4F; font-weight:bold;">
                                        <td class="text-center" colspan="6">Tổ
                                            {{ $group_name[$data->group_code] }}</td>
                                    </tr>
                                    @php $group_code_current = $data->group_code; @endphp
                                @endif

                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>{{ $data->next_room }}</td>
                                    <td>{{ $data->stage }}</td>
                                    <td>
                                        {{ number_format($data->sum_yields, 2) }}
                                        {{ $data->stage_code <= 5 ? 'Kg' : 'ĐVL' }}
                                        @if ($data->stage_code == 5)
                                            # {{ number_format($data->sum_yields_unit ?? 0, 2) }} ĐVL
                                        @endif
                                    </td>

                                    <td class="text-center align-middle">
                                        <button type="button" class="btn btn-primary btn-detial"
                                            data-room_id ="{{ $data->room_id }}" data-toggle="modal"
                                            data-target="#detailModal">
                                            <i class="fas fa-eye"></i>
                                        </button>
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


<script src="{{ asset('js/vendor/jquery-1.12.4.min.js') }}"></script>
<script src="{{ asset('js/popper.min.js') }}"></script>
<script src="{{ asset('js/bootstrap.min.js') }}"></script>
<script src="{{ asset('js/sweetalert2.all.min.js') }}"></script>


<script>
    $(document).ready(function() {
        document.body.style.overflowY = "auto";

        const startDate = document.getElementById('reportedDate');
        const form = document.getElementById('filterForm');
        startDate.addEventListener('input', function() {
            form.submit();
        });
        const stageNameMap = @json($stage_name);

        $('.btn-detial').on('click', function() {

            const room_id = $(this).data('room_id');

            const history_modal = $('#data_table_detail_body')

            // Xóa dữ liệu cũ
            history_modal.empty();

            // Gọi Ajax lấy dữ liệu history
            $.ajax({
                url: "{{ route('pages.report.daily_report.detail') }}",
                type: 'post',
                data: {
                    reportedDate: startDate.value,
                    room_id: room_id,
                    _token: "{{ csrf_token() }}"
                },
                success: function(res) {
                    if (res.length === 0) {
                        history_modal.append(
                            `<tr><td colspan="8" class="text-center">Không có dữ liệu</td></tr>`
                        );
                    } else {
                        res.forEach((item, index) => {
                            // map màu level

                            history_modal.append(`
                                <tr>
                                    <td>${index + 1}</td>

                                    <td> 
                                        <div>${item.intermediate_code ?? ''}</div>
                                        <div>${item.finished_product_code ?? ''}</div>
                                    </td>

                                    <td>${item.product_name ?? ''} </td>
                                    <td>${item.batch ?? ''}</td>
                                    <td>${(item.pre_room ?? '') }</td>
                                    <td>${(item.yields ?? '') + (item.stage_code <= 4 ? " Kg" : " ĐVL")}</td>
                                    <td>${stageNameMap[item.next_stage] ?? ''}</td>
                                    
                                    <td>${moment(item.next_start).format('hh:mm DD/MM/YYYY') ?? ''}</td>
                                    <td>${item.quarantine_room_code ?? ''}</td>
                                </tr>
                            `);
                        });
                    }
                },
                error: function() {
                    history_modal.append(
                        `<tr><td colspan="8" class="text-center text-danger">Lỗi tải dữ liệu</td></tr>`
                    );
                }
            });
        });

        $('.btn-explain').on('click', function() {
            //alert ("sa")
            const button = $(this);
            const modal = $('#explanation')
            let stage_code = button.data('stage_code')
            let reported_date = button.data('reported_date')

            modal.find('input[name="stage_code"]').val(stage_code);
            modal.find('input[name="reported_date"]').val(reported_date);

            $.ajax({
                url: "{{ route('pages.report.daily_report.getExplainationContent') }}",
                type: 'post',
                data: {
                    stage_code: stage_code,
                    reported_date: reported_date,
                    _token: "{{ csrf_token() }}"
                },
                success: function(res) {

                    modal.find('textarea[name="note"]').val(res.content);
                    modal.find('input[name="created_by"]').val(res.created_by);
                    modal.find('input[name="created_at"]').val(res.updated_at || res
                        .created_at);
                },
                error: function() {
                    Swal.fire({
                        title: 'Lỗi!',
                        icon: 'error',
                        timer: 1000, // tự đóng sau 2 giây
                        showConfirmButton: false
                    });
                }
            });
        });

        $('.btn-plus').click(function() {
            const button = $(this);
            const modal = $('#Modal');
            modal.find('input[name="room_id"]').val(button.data('room_id'));
            modal.find('input[name="room_name"]').val(button.data('room_code') + " - " + button.data(
                'room_name'));

        });

        $('.btn-edit').click(function() {
            const button = $(this);
            const modal = $('#updateModal');

            modal.find('input[name="id"]').val(button.data('id'));
            modal.find('input[name="room_id"]').val(button.data('room_id'));
            modal.find('input[name="room_name"]').val(button.data('room_code') + " - " + button.data(
                'room_name'));
            modal.find('input[name="in_production"]').val(button.data('title'));
            modal.find('input[name="start"]').val(button.data('start'));
            modal.find('input[name="end"]').val(button.data('end'));
            modal.find('textarea[name="notification"]').val(button.data('note'));

        });


    });
</script>


<script>
    document.querySelectorAll('.toggle-stage').forEach(btn => {
        btn.addEventListener('click', function() {
            const stage = this.getAttribute('data-stage');
            const rows = document.querySelectorAll('.stage-' + stage);
            rows.forEach(row => {
                row.style.display = row.style.display === 'none' ? '' : 'none';
            });

            // đổi dấu + / -
            this.textContent = this.textContent === '+' ? '-' : '+';
        });
    });
</script>
