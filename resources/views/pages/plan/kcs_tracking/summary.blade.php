@php
    use App\Models\PlanMasterKcs;

    // Mục tiêu (xanh) >= 98%, phải hành động (đỏ) < 80%, còn lại là cảnh báo (vàng)
    $rateClass = function ($rate) {
        if ($rate === null) {
            return '';
        }

        if ($rate >= PlanMasterKcs::TARGET_RATE) {
            return 'bg-success text-white';
        }

        return $rate < PlanMasterKcs::CRITICAL_RATE ? 'bg-danger text-white' : 'bg-warning';
    };
@endphp

<div class="form-row align-items-end mb-3">
    <div class="col-md-2">
        <label class="mb-1"><i class="fas fa-industry text-primary"></i> Phân Xưởng</label>
        <select class="form-control form-control-sm" id="summary_department">
            <option value="">Tất cả phân xưởng</option>
            @foreach ($departments as $code => $name)
                <option value="{{ $code }}" {{ $code === $department ? 'selected' : '' }}>{{ $name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-2">
        <label class="mb-1"><i class="fas fa-calendar text-primary"></i> Năm KCS</label>
        <select class="form-control form-control-sm" id="summary_year">
            @foreach ($summaryYears as $year)
                <option value="{{ $year }}" {{ $year === $summaryYear ? 'selected' : '' }}>{{ $year }}</option>
            @endforeach
        </select>
    </div>
</div>

<div class="alert alert-light border">
    <b>Công thức:</b> Số lô đúng hạn / Số lô đã chấm được × 100%.
    Một lô đúng hạn khi <b>Ngày KCS</b> cách <b>Ngày đủ điều kiện</b> không quá
    {{ PlanMasterKcs::ON_TIME_DAYS }} ngày.
    <span class="ml-3">
        <span class="badge badge-success">Mục tiêu ≥ {{ PlanMasterKcs::TARGET_RATE }}%</span>
        <span class="badge badge-warning">Cảnh báo</span>
        <span class="badge badge-danger">Phải hành động &lt; {{ PlanMasterKcs::CRITICAL_RATE }}%</span>
    </span>
    <div class="small text-muted mt-2">
        <i class="fas fa-info-circle"></i>
        Bảng chỉ thống kê lô <b>đã chấm được kết quả</b>. Lô đã có Ngày KCS nhưng chưa nhập đủ mốc để ra
        Ngày Đủ Điều Kiện thì chưa đánh giá được nên không được đếm ở đây - vì vậy số lô trong bảng này
        nhỏ hơn số lô có Ngày KCS ở tab Theo Dõi Hồ Sơ.
        Muốn xem đúng nhóm lô này trên lưới, chọn <b>Tháng KCS</b> tương ứng, để khoảng tháng kế hoạch
        cả năm và lọc <b>Kết Quả</b> = Đáp Ứng hoặc Trễ Hạn.
    </div>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header py-2">
                <h3 class="card-title">Tỉ Lệ Lô KCS Đúng Hạn Theo Tháng - Năm {{ $summaryYear }}</h3>
            </div>
            <div class="card-body p-0">
                <table class="table table-bordered table-sm mb-0 text-center">
                    <thead class="bg-light">
                        <tr>
                            <th>Thời Gian Đo</th>
                            <th title="Chỉ tính lô đã có Ngày Đủ Điều Kiện nên chấm được kết quả.">
                                Tổng Số Lô Đã Chấm</th>
                            <th>Số Lô Đúng Hạn</th>
                            <th>Số Lô Trễ</th>
                            <th>Tỉ Lệ Theo Tháng</th>
                            <th>Tỉ Lệ Theo Quý</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($summary['months'] as $month => $row)
                            @php $quarter = $summary['quarters'][intdiv($month - 1, 3) + 1]; @endphp
                            <tr>
                                <td class="text-left font-weight-bold">{{ $summaryYear }}_Tháng {{ $month }}</td>
                                <td>{{ $row['total'] ?: '' }}</td>
                                <td>{{ $row['total'] ? $row['on_time'] : '' }}</td>
                                <td class="{{ $row['late'] ? 'text-danger font-weight-bold' : '' }}">
                                    {{ $row['total'] ? $row['late'] : '' }}
                                </td>
                                <td class="font-weight-bold {{ $rateClass($row['rate']) }}">
                                    {{ $row['rate'] === null ? '-' : $row['rate'] . '%' }}
                                </td>
                                @if ($month % 3 === 1)
                                    <td class="align-middle font-weight-bold {{ $rateClass($quarter['rate']) }}"
                                        rowspan="3">
                                        Quý {{ $quarter['quarter'] }}<br>
                                        {{ $quarter['rate'] === null ? '-' : $quarter['rate'] . '%' }}
                                    </td>
                                @endif
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="bg-light font-weight-bold">
                        <tr>
                            <td class="text-left">Cả năm {{ $summaryYear }}</td>
                            <td>{{ $summary['total']['total'] }}</td>
                            <td>{{ $summary['total']['on_time'] }}</td>
                            <td class="{{ $summary['total']['late'] ? 'text-danger' : '' }}">
                                {{ $summary['total']['late'] }}
                            </td>
                            <td colspan="2" class="{{ $rateClass($summary['total']['rate']) }}">
                                {{ $summary['total']['rate'] === null ? '-' : $summary['total']['rate'] . '%' }}
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card">
            <div class="card-header py-2">
                <h3 class="card-title">Tỉ Lệ Cả Năm</h3>
            </div>
            <div class="card-body text-center">
                @php $totalRate = $summary['total']['rate']; @endphp
                <div class="display-4 font-weight-bold
                    {{ $totalRate === null ? 'text-muted' : ($totalRate >= PlanMasterKcs::TARGET_RATE ? 'text-success' : ($totalRate < PlanMasterKcs::CRITICAL_RATE ? 'text-danger' : 'text-warning')) }}">
                    {{ $totalRate === null ? '-' : $totalRate . '%' }}
                </div>
                <div class="progress mt-3" style="height: 22px;">
                    <div class="progress-bar {{ $totalRate === null ? 'bg-secondary' : ($totalRate >= PlanMasterKcs::TARGET_RATE ? 'bg-success' : ($totalRate < PlanMasterKcs::CRITICAL_RATE ? 'bg-danger' : 'bg-warning')) }}"
                        style="width: {{ $totalRate ?? 0 }}%">
                        {{ $summary['total']['on_time'] }}/{{ $summary['total']['total'] }} lô
                    </div>
                </div>
                <p class="text-muted mt-3 mb-0">
                    {{ $summary['total']['late'] }} lô KCS trễ hạn trong năm {{ $summaryYear }}
                </p>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Tải lại riêng khối tổng kết để không mất bộ lọc / dữ liệu đang nhập ở tab theo dõi
        $('#summary_container').off('change.kcsSummary').on('change.kcsSummary',
            '#summary_department, #summary_year',
            function() {
                $.ajax({
                    url: '{{ route('pages.plan.kcs_tracking.summary') }}',
                    method: 'GET',
                    data: {
                        department: $('#summary_department').val(),
                        year: $('#summary_year').val()
                    },
                    success: function(res) {
                        $('#summary_container').html(res.html);
                    },
                    error: function() {
                        toastr.error('Lỗi khi tải bảng tổng kết');
                    }
                });
            });
    });
</script>
