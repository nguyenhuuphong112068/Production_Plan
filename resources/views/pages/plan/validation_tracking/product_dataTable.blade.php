<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header bg-success text-white">
                <h3 class="card-title"><i class="fas fa-pills"></i> Thống Kê Theo Bán Thành Phẩm</h3>
            </div>
            <div class="card-body">
                <div style="max-height: calc(100vh - 320px); overflow-y: auto; overflow-x: auto;">
                    <table id="productTrackingTable" class="table table-bordered table-striped w-100">
                        <thead style="background-color: #f4f6f9;">
                            <tr>
                                <th style="width: 40%;">Bán Thành Phẩm Theo Dõi</th>
                                <th style="width: 10%;">Mã Nguyên Liệu</th>
                                <th style="width: 20%;">Tên Nguyên Liệu</th>
                                <th style="width: 10%;" class="text-center">Tiến độ lô</th>
                                <th style="width: 10%;" class="text-center">Trạng thái</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($products as $product)
                                @if($product->validationTrackings->count() > 0)
                                    @foreach ($product->validationTrackings as $index => $vt_ic)
                                        @if($vt_ic->validationTracking)
                                        <tr>
                                            <td class="font-weight-bold text-primary">
                                                {{ $product->intermediate_code }} - {{ $product->productName->name ?? 'N/A' }} - Cỡ lô: {{ number_format((float)$product->batch_size, 0, ',', '.') }} {{ $product->unit_batch_size ?? '' }} | ĐVL: {{ number_format((float)$product->batch_qty, 0, ',', '.') }} {{ $product->unit_batch_qty ?? '' }}
                                            </td>
                                            <td class="font-weight-bold text-secondary">{{ $vt_ic->validationTracking->MatID ?? 'N/A' }}</td>
                                            <td>{{ $vt_ic->validationTracking->MaterialName ?? 'N/A' }}<br><small class="text-muted">CC: {{ $vt_ic->validationTracking->CC_num }}</small></td>
                                            <td class="text-center">
                                                <span class="badge badge-primary badge-pill">
                                                    {{ $vt_ic->num_of_finished_batch }} / {{ $vt_ic->num_of_tracking_batch }} lô
                                                </span>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge 
                                                    @if ($vt_ic->validationTracking->status == 'Chờ phê duyệt') badge-warning
                                                    @elseif($vt_ic->validationTracking->status == 'Đang theo dõi') badge-primary
                                                    @elseif($vt_ic->validationTracking->status == 'Hoàn thành') badge-success
                                                    @else badge-secondary @endif">
                                                    {{ $vt_ic->validationTracking->status }}
                                                </span>
                                            </td>
                                        </tr>
                                        @endif
                                    @endforeach
                                @else
                                    <tr>
                                        <td class="font-weight-bold text-primary">{{ $product->intermediate_code }}</td>
                                        <td>{{ $product->productName->name ?? 'N/A' }}</td>
                                        <td class="text-muted font-italic text-center">Không có</td>
                                        <td class="text-muted font-italic text-center">Không có</td>
                                        <td class="text-muted font-italic text-center">-</td>
                                        <td class="text-muted font-italic text-center">-</td>
                                    </tr>
                                @endif
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Fix for body not scrolling if modal was stuck
        $('body').removeClass('modal-open');
        $('.modal-backdrop').remove();

        $('#productTrackingTable').DataTable({
            "paging": false,
            "lengthChange": false,
            "searching": true,
            "ordering": false,
            "info": false,
            "autoWidth": false,
            "responsive": false,
            "drawCallback": function (settings) {
                var api = this.api();
                var rows = api.rows({page: 'current'}).nodes();
                var lastCode = null;
                var rowspan = 1;
                var cellCodeToModify = null;
                var cellNameToModify = null;

                // Reset tất cả các ô td về trạng thái ban đầu trước khi tính toán lại
                $(rows).find('td:eq(0), td:eq(1)').show().attr('rowspan', 1).css('vertical-align', 'middle');

                api.column(0, {page: 'current'}).data().each(function (code, i) {
                    var tr = rows[i];
                    var tdCode = $(tr).find('td:eq(0)');
                    var tdName = $(tr).find('td:eq(1)');
                    
                    // Lấy text thuần của mã code để so sánh
                    var currentCode = tdCode.text().trim();

                    if (lastCode === currentCode && currentCode !== '') {
                        rowspan++;
                        $(cellCodeToModify).attr('rowspan', rowspan);
                        $(cellNameToModify).attr('rowspan', rowspan);
                        tdCode.hide();
                        tdName.hide();
                    } else {
                        rowspan = 1;
                        lastCode = currentCode;
                        cellCodeToModify = tdCode;
                        cellNameToModify = tdName;
                    }
                });
            }
        });

        // Fix DataTables width/height inside Bootstrap tabs
        $('a[data-toggle="pill"]').on('shown.bs.tab', function(e){
            $.fn.dataTable.tables({ visible: true, api: true }).columns.adjust().draw();
        });
    });
</script>
