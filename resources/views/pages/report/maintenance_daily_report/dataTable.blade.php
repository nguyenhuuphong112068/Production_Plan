@php
    use Carbon\Carbon;
    $defaultFrom = $reportedDate
        ? Carbon::createFromFormat('d/m/Y', trim($reportedDate))->format('Y-m-d')
        : Carbon::now()->format('Y-m-d');
@endphp

<div class="content-wrapper">
    <div class="card">
        <div class="card-header mt-4"></div>
        <div class="card-body">
            <!-- Tiêu đề -->
            <div class="row mx-2">
                <div class="col-md-3">
                    <form id="filterForm" method="GET"
                        action="{{ route('pages.report.maintenance_daily_report.index') }}"
                        class="d-flex flex-wrap gap-0">
                        @csrf
                        <div class="row w-100 align-items-center">
                            <div class="col-md-12 d-flex gap-2">
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
                <div class="col-md-6 text-center" style="font-size: 20px; color: #CDC717;">
                    <div>
                        Báo cáo được tính từ 06:00 ngày {{ Carbon::parse($defaultFrom)->format('d/m/Y') }}
                        đến 06:00 ngày {{ Carbon::parse($defaultFrom)->addDays(1)->format('d/m/Y') }}
                    </div>
                </div>
                <div class="col-md-3">
                </div>
            </div>

            <!-- Nội dung báo cáo -->
            <div class="card card-success mb-4">
                <div class="card-header border-transparent">
                    <h3 class="card-title">Chi Tiết Bảo Trì - Hiệu Chuẩn</h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-tool" data-card-widget="collapse">
                            <i class="fas fa-minus"></i>
                        </button>
                    </div>
                </div>

                <div class="card-body">
                    <table id="maintenance_report_table" class="table table-bordered table-striped"
                        style="font-size: 15px;">
                        <thead style="position: sticky; top: 60px; background-color: white; z-index: 1020;">
                            <tr style="color:#003A4F; font-size: 20px; font-weight: bold;">
                                <th class="text-center" width="50">#</th>
                                <th class="text-center" style="width: 15%;">Phòng SX / Khu Vực</th>
                                <th class="text-center">Lịch Lý Thuyết </th>
                                <th class="text-center">Lịch Thực Tế</th>
                                <th class="text-center" width="100">Kết Quả</th>
                                <th class="text-center" style="width: 120px;">Người Thực Hiện</th>
                            </tr>
                        </thead>
                        <tbody style="font-size: 17px;">
                            @php $roomIndex = 1; @endphp
                            @forelse($groupedDatas as $roomId => $events)
                                @php
                                    $firstEvent = $events->first();
                                    $validEvents = $events->whereNotNull('sp_id');
                                @endphp
                                <tr>
                                    <td class="text-center align-middle">{{ $roomIndex++ }}</td>
                                    <td class="align-middle">
                                        <div class="font-weight-bold text-primary">{{ $firstEvent->room_code }} -
                                            {{ $firstEvent->room_name }}</div>
                                    </td>

                                    {{-- Cột Lịch Lý Thuyết --}}
                                    <td class="align-middle">
                                        @if ($validEvents->count())
                                            @foreach ($validEvents as $index => $e)
                                                @php
                                                    $start = \Carbon\Carbon::parse($e->planned_start);
                                                    $end = \Carbon\Carbon::parse($e->planned_end);
                                                    $minutes = $start->diffInMinutes($end);
                                                    $hours = intdiv($minutes, 60);
                                                    $mins = $minutes % 60;
                                                @endphp
                                                <div class="mb-1" style="font-size: 16px;">
                                                    {{ $index + 1 }}.
                                                    <span class="text-dark font-weight-bold">{{ $e->type_name }}</span>
                                                    _
                                                    @if ($e->parent_eqp_id == $e->inst_id && $e->Eqp_name == $e->inst_name)
                                                        <span
                                                            class="text-primary font-weight-bold">{{ $e->inst_id }}</span>
                                                        _
                                                        <span class="text-dark">{{ $e->inst_name ?? '—' }}</span> _
                                                    @else
                                                        <span
                                                            class="text-secondary">{{ $e->parent_eqp_id ?? '—' }}</span>
                                                        _
                                                        <span class="text-muted">{{ $e->Eqp_name ?? '—' }}</span> _
                                                        <span
                                                            class="text-primary font-weight-bold">{{ $e->inst_id }}</span>
                                                        _
                                                        <span class="text-dark">{{ $e->inst_name ?? '—' }}</span> _
                                                    @endif
                                                    <span
                                                        class="text-success font-weight-bold">{{ $start->format('H:i') }}
                                                        -> {{ $end->format('H:i') }}</span>
                                                    = <b>{{ $hours }}h{{ $mins }}p</b>
                                                </div>
                                            @endforeach
                                        @else
                                            <div class="text-center text-muted text-sm italic">Không có lịch sắp</div>
                                        @endif
                                    </td>

                                    {{-- Cột Lịch Thực Tế --}}
                                    <td class="align-middle">
                                        @php $hasActual = $validEvents->whereNotNull('actual_start')->count(); @endphp
                                        @if ($hasActual)
                                            @php $actualIndex = 1; @endphp
                                            @foreach ($validEvents->whereNotNull('actual_start') as $e)
                                                @php
                                                    $start = \Carbon\Carbon::parse($e->actual_start);
                                                    $end = \Carbon\Carbon::parse($e->actual_end);
                                                    $minutes = $start->diffInMinutes($end);
                                                    $hours = intdiv($minutes, 60);
                                                    $mins = $minutes % 60;
                                                @endphp
                                                <div class="mb-1 text-info" style="font-size: 16px;">
                                                    {{ $actualIndex++ }}.
                                                    <span class="font-weight-bold text-info">{{ $e->type_name }}</span>
                                                    _
                                                    @if ($e->parent_eqp_id == $e->inst_id && $e->Eqp_name == $e->inst_name)
                                                        <span
                                                            class="font-weight-bold text-primary">{{ $e->inst_id }}</span>
                                                        _
                                                        <span class="text-dark">{{ $e->inst_name ?? '—' }}</span> _
                                                    @else
                                                        <span
                                                            class="text-secondary">{{ $e->parent_eqp_id ?? '—' }}</span>
                                                        _
                                                        <span class="text-muted">{{ $e->Eqp_name ?? '—' }}</span> _
                                                        <span
                                                            class="font-weight-bold text-primary">{{ $e->inst_id }}</span>
                                                        _
                                                        <span class="text-dark">{{ $e->inst_name ?? '—' }}</span> _
                                                    @endif
                                                    <span class="font-weight-bold">{{ $start->format('H:i') }} ->
                                                        {{ $end->format('H:i') }}</span>
                                                    = <b>{{ $hours }}h{{ $mins }}p</b>
                                                </div>
                                            @endforeach
                                        @else
                                            <div class="text-center text-muted">—</div>
                                        @endif
                                    </td>

                                    {{-- Cột Kết Quả --}}
                                    <td class="text-center align-middle">
                                        @if ($validEvents->count())
                                            @foreach ($validEvents as $index => $e)
                                                <div class="mb-1 d-flex flex-column align-items-center">
                                                    <span class="badge badge-{{ $e->badge_color }} p-1 mb-1"
                                                        style="font-size: 14px; min-width: 60px;">
                                                        {{ $e->status_text }}
                                                    </span>
                                                </div>
                                            @endforeach
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>

                                    {{-- Cột Người Thực Hiện --}}
                                    <td class="text-center align-middle">
                                        @if ($validEvents->count())
                                            @foreach ($validEvents as $index => $e)
                                                <div class="mb-1">
                                                    <span class="font-weight-bold"
                                                        style="font-size: 14px;">{{ $e->finished_by ?? '—' }}</span>
                                                </div>
                                            @endforeach
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center py-5">
                                        <div class="text-muted">
                                            <i class="fas fa-info-circle fa-2x mb-2"></i><br>
                                            Không tìm thấy phòng sản xuất nào.
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="{{ asset('js/vendor/jquery-1.12.4.min.js') }}"></script>
<script src="{{ asset('js/sweetalert2.all.min.js') }}"></script>

<script>
    $(document).ready(function() {
        document.body.style.overflowY = "auto";
    });
    $(function() {
        $('#reportedDate').on('change', function() {
            $('#filterForm').submit();
        });
    });
</script>
