<div class="table-responsive" style="max-height: 70vh; overflow-y: auto;">
    <table class="table table-bordered table-striped table-hover text-center align-middle" id="inProgressTable" style="font-size: 13px; width: 100%;">
        <thead class="bg-primary text-white sticky-top">
            <tr>
                <th style="width: 50px;">STT</th>
                <th>Mã sản phẩm</th>
                <th>Tên sản phẩm</th>
                <th>Cỡ lô</th>
                <th>Số lô</th>
                <th>Phân xưởng</th>
                <th>Thời gian đóng gói dự kiến</th>
                <th>Trạng thái</th>
                <th>Thao tác</th>
            </tr>
        </thead>
        <tbody>
            @forelse($inProgressPlans as $index => $plan)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td class="font-weight-bold text-success">{{ $plan->finished_product_code }}</td>
                    <td>{{ $plan->product_name }}</td>
                    <td>
                        <span class="badge badge-info" style="font-size: 12px;">{{ number_format($plan->batch_qty) }} {{ $plan->unit_batch_qty }}</span>
                    </td>
                    <td class="font-weight-bold">
                        {{ $plan->actual_batch ? $plan->actual_batch : $plan->batch }}
                    </td>
                    <td>{{ $plan->deparment_code }}</td>
                    <td>
                        @if($plan->packaging_start && $plan->packaging_end)
                            {{ \Carbon\Carbon::parse($plan->packaging_start)->format('d/m/Y') }} - {{ \Carbon\Carbon::parse($plan->packaging_end)->format('d/m/Y') }}
                        @else
                            <span class="text-muted">Chưa có</span>
                        @endif
                    </td>
                    <td>
                        @if($plan->finished)
                            <span class="badge badge-success">Đã hoàn thành ĐG</span>
                        @elseif($plan->actual_start)
                            <span class="badge badge-warning">Đang thực hiện</span>
                        @elseif($plan->schedualed)
                            <span class="badge badge-primary">Đã xếp lịch</span>
                        @else
                            <span class="badge badge-secondary">Chưa xếp lịch</span>
                        @endif
                    </td>
                    <td>
                        <a href="{{ route('pages.plan.production.open') }}?plan_list_id={{ \App\Models\PlanMaster::find($plan->id)->plan_list_id }}&name={{ \App\Models\PlanMaster::find($plan->id)->batch }}" target="_blank" class="btn btn-sm btn-outline-primary" title="Mở kế hoạch">
                            <i class="fas fa-external-link-alt"></i>
                        </a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="text-center py-4">
                        <div class="text-muted">
                            <i class="fas fa-inbox fa-3x mb-3"></i>
                            <p>Không có sản phẩm nào đang lên kế hoạch chờ đóng gói</p>
                        </div>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<script>
$(document).ready(function() {
    $('#inProgressTable').DataTable({
        "paging": true,
        "lengthChange": true,
        "searching": true,
        "ordering": true,
        "info": true,
        "autoWidth": false,
        "responsive": true,
        "language": {
            "url": "//cdn.datatables.net/plug-ins/1.10.21/i18n/Vietnamese.json"
        }
    });
});
</script>
