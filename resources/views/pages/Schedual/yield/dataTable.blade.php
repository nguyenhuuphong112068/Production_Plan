<style>
    .card-body {
        max-height: none !important;
        overflow-y: visible !important;
    }

    #data_table_yield {
        width: 100%;
        table-layout: auto;
    }


</style>

<div class="content-wrapper">
    <div class="card">
        <div class="card-header mt-4"></div>

        <div class="card-body">
            <form id="filterForm" method="GET" action="{{ route('pages.Schedual.yield.index') }}"
                class="d-flex flex-wrap gap-2">
                @csrf

                <div class="row w-100 align-items-center mt-3">
                    <div class="col-md-4 d-flex gap-2">

                        @php
                            $defaultFrom = \Carbon\Carbon::now()->addDays(2)->startOfMonth()->toDateString();
                            $defaultTo   = \Carbon\Carbon::now()->endOfMonth()->toDateString();
                            $defaultWeek = \Carbon\Carbon::parse($defaultTo)->weekOfYear;
                            $defaultMonth = \Carbon\Carbon::parse($defaultTo)->month;
                            $defaultYear = \Carbon\Carbon::parse($defaultTo)->year;

                            $stage_name = [
                                1 => 'Cân Nguyên Liệu',
                                3 => 'Pha Chế',
                                4 => 'THT',
                                5 => 'Định Hình',
                                6 => 'Bao Phim',
                                7 => 'ĐGSC-ĐGTC'
                            ];
                        @endphp

                        <div class="form-group d-flex align-items-center mr-2">
                            <label class="mr-2 mb-0">From:</label>
                            <input type="date" id="from_date" name="from_date"
                                value="{{ request('from_date') ?? $defaultFrom }}" class="form-control" />
                        </div>

                        <div class="form-group d-flex align-items-center mr-2">
                            <label class="mr-2 mb-0">To:</label>
                            <input type="date" id="to_date" name="to_date"
                                value="{{ request('to_date') ?? $defaultTo }}" class="form-control" />
                        </div>
                    </div>

                    <div class="col-md-2">
                        <span style="display:inline-block; width:40px; height:20px; background:#93f486; margin-right:5px;"></span> Lý Thuyết
                        <span style="display:inline-block; width:40px; height:20px; background:#69b8f4; margin-left:15px; margin-right:5px;"></span> Thực Tế
                    </div>

                    <div class="col-md-6 d-flex gap-2 justify-content-end">
                        <select id="week_number" name="week_number" class="form-control mr-2">
                            @for ($i = 1; $i <= 52; $i++)
                                <option value="{{ $i }}"
                                    {{ (request('week_number') ?? $defaultWeek) == $i ? 'selected' : '' }}>
                                    Tuần {{ $i }}
                                </option>
                            @endfor
                        </select>

                        <select id="month" name="month" class="form-control mr-2">
                            @for ($m = 1; $m <= 12; $m++)
                                <option value="{{ $m }}"
                                    {{ (request('month') ?? $defaultMonth) == $m ? 'selected' : '' }}>
                                    Tháng {{ $m }}
                                </option>
                            @endfor
                        </select>

                        <select id="year" name="year" class="form-control">
                            @php $currentYear = now()->year; @endphp
                            @for ($y = $currentYear - 5; $y <= $currentYear + 5; $y++)
                                <option value="{{ $y }}"
                                    {{ (request('year') ?? $defaultYear) == $y ? 'selected' : '' }}>
                                    {{ $y }}
                                </option>
                            @endfor
                        </select>
                    </div>
                </div>
            </form>

            {{-- ============================= TABLE ============================= --}}
       
            <table id="data_table_yield" class="table table-bordered table-striped" style="font-size: 15px;">
                <thead style="position: sticky; top: 60px; background-color: white; z-index: 1020;">
                    <tr style="background-color: #CDC717; color:#003A4F; font-size: 20px; font-weight: bold;">
                        <th class="text-center" style="min-width: 200px;">Phòng SX</th>
                        <th class="text-center">ĐV</th>

                        {{-- @foreach ($theory['yield_day'] as $date => $dayData)
                            <th class="text-center">{{ \Carbon\Carbon::parse($date)->format('d/m/y') }}</th>
                        @endforeach --}}

                        @php
                            $allDates = $theory['yield_day']->keys()
                                ->merge($actual['yield_day']->keys())
                                ->unique()
                                ->sort();
                        @endphp

                        @foreach ($allDates as $date)
                            <th class="text-center">{{ \Carbon\Carbon::parse($date)->format('d/m/y') }}</th>
                        @endforeach


                        <th class="text-center">Tổng</th>
                        <th class="text-center">ĐV</th>
                    </tr>
                </thead>

                <tbody style="font-size: 20px;">
                    @foreach ($theory['yield_room'] as $index => $roomLT)
                        @php
                            $resourceId = $roomLT->resourceId;
                            $unit = $roomLT->unit;
                            $roomTT = $actual['yield_room']->firstWhere('resourceId', $resourceId);
                        @endphp

                        {{-- ------------------- LÝ THUYẾT ------------------- --}}
                        <tr >
                            <td class="text-center align-middle" rowspan="2">{{ $roomLT->room_code . ' - ' . $roomLT->room_name }}</td>
                            <td class="text-center align-middle" rowspan="2">{{ $unit }}</td>

                            @php 
                                $sumLT = 0; 
                                $sumLT_unit = 0;
                            @endphp
                            @foreach ($allDates as $date)
                                @php
                                    $dayLT = $theory['yield_day'][$date] ?? collect();
                                    $item = $dayLT->firstWhere('resourceId', $resourceId);
                                    $qty = $item['total_qty'] ?? 0;
                                    $qty_unit = $item['total_qty_unit'] ?? 0;

                                    $sumLT += $qty;
                                    $sumLT_unit += $qty_unit;
                                    
                                @endphp
                                <td class="text-end" style="background:#93f486;" >{{ number_format($qty, 2)}}   {{ $roomLT->stage_code == 4 ? " # " . number_format($qty_unit, 2) : ''}}</td>
                            @endforeach
                  
                            <td class="text-end fw-bold" style="background:#93f486;">{{ number_format($sumLT, 2) }} {{ $roomLT->stage_code == 4 ? " # " .number_format($sumLT_unit, 2) :''}} </td>
                            <td class="text-center" style="background:#93f486;">{{ $unit }}</td>
                        </tr>

                        {{-- ------------------- THỰC TẾ ------------------- --}}
                        <tr >
                            @php $sumTT = 0; @endphp
                            @foreach ($allDates as $date)
                                @php
                                    $dayTT = $actual['yield_day'][$date] ?? collect();
                                    $itemTT = $dayTT->firstWhere('resourceId', $resourceId);
                                    $qtyTT = $itemTT['total_qty'] ?? 0;
                                    $sumTT += $qtyTT;
                                @endphp
                                <td class="text-end" style="color:#003A4F; background:#69b8f4;">
                                    {{ number_format($qtyTT, 2) }}
                                </td>
                            @endforeach

                            <td class="text-end fw-bold" style="background:#69b8f4;">{{ number_format($sumTT, 2) }}</td>
                            <td class="text-center" style="background:#69b8f4;">{{ $unit }}</td>
                        </tr>

                        {{-- ------------- TỔNG THEO CÔNG ĐOẠN (LT + TT) --------------- --}}
                        @php
                            $nextItem = $theory['yield_room'][$index + 1] ?? null;
                            $nextStage = $nextItem->stage_code ?? null;
                        @endphp



                        @if ($nextStage != $roomLT->stage_code)
                            @php
                                $stage_code = $roomLT->stage_code;
                                // Tính tổng LT/TT theo công đoạn
                                $stageLT = [];
                                $stageTT = [];

                                foreach ($allDates as $date) {
                                    $dayLT = $theory['yield_day'][$date] ?? collect();
                                    $stageLT[$date] = $dayLT->where('stage_code', $stage_code)->sum('total_qty');
                                    $stageLT_unit[$date] = $dayLT->where('stage_code', $stage_code)->sum('total_qty_unit');

                                    $dayTT = $actual['yield_day'][$date] ?? collect();
                                    $stageTT[$date] = $dayTT->where('stage_code', $stage_code)->sum('total_qty');
                                   
                                }
                            @endphp

                            {{-- ⭐ Tổng LT --}}
                            <tr style="background:#CDC717; color:#003A4F; font-weight:bold;">
                                <td class="text-center align-middle" rowspan="2">{{ 'Công Đoạn ' . ($stage_name[$stage_code] ?? $stage_code) }}</td>
                                <td class="text-center align-middle" rowspan="2">{{ $unit }}</td>

                                @foreach ($allDates as $date)
                                    <td class="text-end" >{{ number_format($stageLT[$date], 2) }} {{$roomLT->stage_code == 4 ? " # " . number_format($stageLT_unit[$date], 2) : '' }} </td>
                                @endforeach

                                <td class="text-end" >{{ number_format(array_sum($stageLT), 2) }} {{$roomLT->stage_code == 4 ? " # " . number_format(array_sum($stageLT_unit), 2) : '' }} </td>
                                <td class="text-center">{{ $unit }}</td>
                            </tr>

                            {{-- ⭐ Tổng TT --}}
                            <tr style="background:#CDC717; color:#003A4F; font-weight:bold;">
                                @foreach ($allDates as $date)
                                    <td class="text-end" >{{ number_format($stageTT[$date], 2) }}</td>
                                @endforeach

                                <td class="text-end" >{{ number_format(array_sum($stageTT), 2) }}</td>
                                <td class="text-center">{{ $unit }}</td>
                            </tr>
                        @endif
                    @endforeach
                </tbody>
            </table>

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
        document.body.style.overflowX = "auto";
        // const cardBody = document.querySelector('.card-body');
        // cardBody.style.overflowX = "auto";
        // window.scrollTo({ top: 0, behavior: 'smooth' }); 
        // cardBody.scrollTop = 0;
    });
</script>


{{-- <script>
    const form = document.getElementById('filterForm');
    const fromInput = document.getElementById('from_date');
    const toInput = document.getElementById('to_date');
    const weekInput = document.getElementById('week_number');
    const monthInput = document.getElementById('month');
    const yearInput = document.getElementById('year');

    // Submit form với kiểm tra From/To
    function submitForm() {
        const fromDate = new Date(fromInput.value);
        const toDate = new Date(toInput.value);

        if (fromDate > toDate) {
            Swal.fire({
                icon: "warning",
                title: "Ngày không hợp lệ",
                text: "⚠️ Ngày bắt đầu (From) không được lớn hơn ngày kết thúc (To).",
                confirmButtonText: "OK"
            });
            return;
        }
        form.requestSubmit();
    }

    // Khi thay đổi From/To => cập nhật tháng/năm theo From
    function updateMonthYearFromDates() {
        const fromDate = new Date(fromInput.value);
        if (isNaN(fromDate)) return;
        monthInput.value = fromDate.getMonth() + 1;
        yearInput.value = fromDate.getFullYear();
    }

    // Tính tuần ISO dựa trên ngày
    function getWeekNumber(date) {
        const d = new Date(Date.UTC(date.getFullYear(), date.getMonth(), date.getDate()));
        const dayNum = d.getUTCDay() || 7;
        d.setUTCDate(d.getUTCDate() + 4 - dayNum);
        const yearStart = new Date(Date.UTC(d.getUTCFullYear(), 0, 1));
        return Math.ceil((((d - yearStart) / 86400000) + 1) / 7);
    }

    // Khi thay đổi tuần => cập nhật From/To dựa trên tuần/month/year
    function updateDatesFromWeekMonthYear() {
        const year = parseInt(yearInput.value);
        const week = parseInt(weekInput.value);
        if (!year || !week) return;

        // ISO tuần: ngày đầu tuần là thứ 2
        const simple = new Date(year, 0, 1 + (week - 1) * 7);
        const dayOfWeek = simple.getDay();
        // điều chỉnh để ngày đầu tuần là thứ 2
        const diff = simple.getDay() <= 0 ? 1 : 2 - dayOfWeek; // Chủ nhật=0
        const fromDate = new Date(simple);
        fromDate.setDate(simple.getDate() + diff);

        const toDate = new Date(fromDate);
        toDate.setDate(fromDate.getDate() + 6);

        fromInput.value = fromDate.toISOString().slice(0, 10);
        toInput.value = toDate.toISOString().slice(0, 10);
    }

    // Khi thay đổi tháng => cập nhật From/To dựa trên tháng
    function updateDatesFromMonth() {
        const year = parseInt(yearInput.value);
        const month = parseInt(monthInput.value);
        if (!year || !month) return;

        const fromDate = new Date(year, month - 1, 1);
        const toDate = new Date(year, month, 0);

        fromInput.value = fromDate.toISOString().slice(0, 10);
        toInput.value = toDate.toISOString().slice(0, 10);

        weekInput.value = getWeekNumber(toDate);
    }

    function updateDatesFromYear() {
        const year = parseInt(yearInput.value);
        if (!year) return;

        // Ngày đầu năm
        const fromDate = new Date(year, 0, 1);
        // Ngày cuối năm
        const toDate = new Date(year, 11, 31);

        fromInput.value = fromDate.toISOString().slice(0, 10);
        toInput.value = toDate.toISOString().slice(0, 10);

        // Tuần cuối năm theo ISO week
        weekInput.value = getWeekNumber(toDate);
    }

    // Hàm lấy số tuần ISO
    function getWeekNumber(d) {
        d = new Date(Date.UTC(d.getFullYear(), d.getMonth(), d.getDate()));
        const dayNum = d.getUTCDay() || 7;
        d.setUTCDate(d.getUTCDate() + 4 - dayNum);
        const yearStart = new Date(Date.UTC(d.getUTCFullYear(), 0, 1));
        return Math.ceil((((d - yearStart) / 86400000) + 1) / 7);
    }

    // Lắng nghe event
    [fromInput, toInput].forEach(input => {
        input.addEventListener('change', () => {
            updateMonthYearFromDates();
            submitForm();
        });
    });

    weekInput.addEventListener('change', () => {
        updateDatesFromWeekMonthYear();
        submitForm();
    });

    monthInput.addEventListener('change', () => {
        updateDatesFromMonth();
        submitForm();
    });

    yearInput.addEventListener('change', () => {
        updateDatesFromYear();
        submitForm();
    });
</script> --}}

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Init tất cả stepper
        document.querySelectorAll('.bs-stepper').forEach(stepperEl => {
            new Stepper(stepperEl, {
                linear: false,
                animation: true
            });
        });
    });
</script>

<script>
    const form = document.getElementById('filterForm');
    const fromInput = document.getElementById('from_date');
    const toInput = document.getElementById('to_date');
    const weekInput = document.getElementById('week_number');
    const monthInput = document.getElementById('month');
    const yearInput = document.getElementById('year');

    // Submit form với kiểm tra From/To
    function submitForm() {
        const fromDate = new Date(fromInput.value);
        const toDate = new Date(toInput.value);

        if (fromDate > toDate) {
            Swal.fire({
                icon: "warning",
                title: "Ngày không hợp lệ",
                text: "⚠️ Ngày bắt đầu (From) không được lớn hơn ngày kết thúc (To).",
                confirmButtonText: "OK"
            });
            return;
        }
        form.requestSubmit();
    }

    // Khi thay đổi From/To => cập nhật tháng/năm theo From
    function updateMonthYearFromDates() {
        const fromDate = new Date(fromInput.value);
        if (isNaN(fromDate)) return;
        monthInput.value = fromDate.getMonth() + 1;
        yearInput.value = fromDate.getFullYear();
    }

    // Tính tuần ISO dựa trên ngày
    function getWeekNumber(date) {
        const d = new Date(Date.UTC(date.getFullYear(), date.getMonth(), date.getDate()));
        const dayNum = d.getUTCDay() || 7;
        d.setUTCDate(d.getUTCDate() + 4 - dayNum);
        const yearStart = new Date(Date.UTC(d.getUTCFullYear(), 0, 1));
        return Math.ceil((((d - yearStart) / 86400000) + 1) / 7);
    }

    // Khi thay đổi tuần => cập nhật From/To dựa trên tuần/month/year
    function updateDatesFromWeekMonthYear() {
        const year = parseInt(yearInput.value);
        const week = parseInt(weekInput.value);
        if (!year || !week) return;

        // ISO tuần: ngày đầu tuần là thứ 2
        const simple = new Date(year, 0, 1 + (week - 1) * 7);
        const dayOfWeek = simple.getDay();
        // điều chỉnh để ngày đầu tuần là thứ 2
        const diff = simple.getDay() <= 0 ? 1 : 2 - dayOfWeek; // Chủ nhật=0
        const fromDate = new Date(simple);
        fromDate.setDate(simple.getDate() + diff);

        const toDate = new Date(fromDate);
        toDate.setDate(fromDate.getDate() + 6);

        fromInput.value = fromDate.toISOString().slice(0, 10);
        toInput.value = toDate.toISOString().slice(0, 10);
    }

    // Khi thay đổi tháng => cập nhật From/To dựa trên tháng
    function updateDatesFromMonth() {
        const year = parseInt(yearInput.value);
        const month = parseInt(monthInput.value);
        if (!year || !month) return;

        const fromDate = new Date(year, month - 1, 1);
        const toDate = new Date(year, month, 0);

        fromInput.value = fromDate.toISOString().slice(0, 10);
        toInput.value = toDate.toISOString().slice(0, 10);

        weekInput.value = getWeekNumber(toDate);
    }

    function updateDatesFromYear() {
        const year = parseInt(yearInput.value);
        if (!year) return;

        // Ngày đầu năm
        const fromDate = new Date(year, 0, 1);
        // Ngày cuối năm
        const toDate = new Date(year, 11, 31);

        fromInput.value = fromDate.toISOString().slice(0, 10);
        toInput.value = toDate.toISOString().slice(0, 10);

        // Tuần cuối năm theo ISO week
        weekInput.value = getWeekNumber(toDate);
    }

    // Hàm lấy số tuần ISO
    function getWeekNumber(d) {
        d = new Date(Date.UTC(d.getFullYear(), d.getMonth(), d.getDate()));
        const dayNum = d.getUTCDay() || 7;
        d.setUTCDate(d.getUTCDate() + 4 - dayNum);
        const yearStart = new Date(Date.UTC(d.getUTCFullYear(), 0, 1));
        return Math.ceil((((d - yearStart) / 86400000) + 1) / 7);
    }

    // Lắng nghe event
    [fromInput, toInput].forEach(input => {
        input.addEventListener('change', () => {
            updateMonthYearFromDates();
            submitForm();
        });
    });

    weekInput.addEventListener('change', () => {
        updateDatesFromWeekMonthYear();
        submitForm();
    });

    monthInput.addEventListener('change', () => {
        updateDatesFromMonth();
        submitForm();
    });

    yearInput.addEventListener('change', () => {
        updateDatesFromYear();
        submitForm();
    });
</script>

