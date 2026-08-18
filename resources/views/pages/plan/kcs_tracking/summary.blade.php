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

@php $lateReasons = $summary['late_reasons']; @endphp

<div class="row mt-3">
    <div class="col-12">
        <div class="card">
            <div class="card-header py-2">
                <h3 class="card-title">
                    Nguyên Nhân Lô KCS Trễ - Năm {{ $summaryYear }}
                    <small class="text-muted ml-2">{{ $lateReasons['total'] }} lô trễ</small>
                </h3>
            </div>
            <div class="card-body p-0">
                @if ($lateReasons['total'] === 0)
                    <div class="p-3 text-muted text-center">
                        Không có lô trễ hạn trong năm {{ $summaryYear }}.
                    </div>
                @else
                    <table class="table table-bordered table-sm mb-0">
                        <thead class="bg-light text-center">
                            <tr>
                                <th style="width: 30px;">#</th>
                                <th class="text-left"
                                    title="Mốc hoàn tất muộn nhất của lô - khâu giữ hồ sơ lâu nhất trước khi lô đủ điều kiện KCS.">
                                    Nguyên Nhân (Mốc Quyết Định)
                                </th>
                                <th style="width: 110px;">Số Lô Trễ</th>
                                <th style="width: 90px;">Tỉ Lệ</th>
                                <th style="width: 130px;"
                                    title="Trung bình số ngày từ Ngày Đủ Điều Kiện đến Ngày KCS của nhóm này.">
                                    Số Ngày HT TB
                                </th>
                                <th>Mức Độ Ảnh Hưởng</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($lateReasons['rows'] as $index => $reason)
                                <tr>
                                    <td class="text-center">{{ $index + 1 }}</td>
                                    <td>{{ $reason['reason'] }}</td>
                                    <td class="text-center font-weight-bold">{{ $reason['total'] }}</td>
                                    <td class="text-center font-weight-bold">{{ $reason['rate'] }}%</td>
                                    <td class="text-center">
                                        {{ $reason['avg_days'] === null ? '-' : $reason['avg_days'] }}
                                    </td>
                                    <td>
                                        <div class="progress" style="height: 18px;">
                                            <div class="progress-bar {{ $index === 0 ? 'bg-danger' : 'bg-warning' }}"
                                                style="width: {{ $reason['rate'] }}%">
                                                {{ $reason['rate'] }}%
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="bg-light font-weight-bold">
                            <tr>
                                <td colspan="2" class="text-right">Tổng số lô trễ</td>
                                <td class="text-center">{{ $lateReasons['total'] }}</td>
                                <td class="text-center">100%</td>
                                <td colspan="2"></td>
                            </tr>
                        </tfoot>
                    </table>
                    <div class="small text-muted p-2 border-top">
                        <i class="fas fa-info-circle"></i>
                        Tỉ lệ tính trên tổng số lô <b>trễ</b> (không phải trên tổng số lô đã chấm), trả lời
                        "nguyên nhân này chiếm bao nhiêu phần trong các lô trễ".
                        Nguyên nhân lấy theo cột <b>Mốc Quyết Định</b> - mốc hoàn tất muộn nhất khiến lô chậm
                        đủ điều kiện KCS.
                    </div>
                @endif
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
