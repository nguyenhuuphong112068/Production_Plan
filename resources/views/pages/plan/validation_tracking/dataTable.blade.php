            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <button type="button" class="btn btn-primary" data-toggle="modal"
                                data-target="#createModal">
                                <i class="fas fa-plus"></i> Thêm Nguyên Liệu Mới
                            </button>
                        </div>
                        <div class="card-body">
                            <div style="max-height: calc(100vh - 320px); overflow-y: auto; overflow-x: auto;">
                                <table class="table table-bordered table-striped" id="validationTrackingTable">
                                <thead>
                                    <tr>
                                        <th>Mã NL</th>
                                        <th>Tên Nguyên Liệu</th>
                                        <th>Mục Đích</th>
                                        <th>Số CC</th>
                                        <th>Trạng Thái</th>
                                        <th>Bán Thành Phẩm Theo Dõi</th>
                                        <th>Hành Động</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($trackings as $tracking)
                                        <tr>
                                            <td>{{ $tracking->MatID }}</td>
                                            <td>{{ $tracking->MaterialName }}</td>
                                            <td>{{ $tracking->purpose }}</td>
                                            <td>{{ $tracking->CC_num }}</td>
                                            <td>
                                                <span
                                                    class="badge btn-show-all-plan-masters
                                                    @if ($tracking->status == 'Chờ phê duyệt') badge-warning
                                                    @elseif($tracking->status == 'Đang theo dõi') badge-primary
                                                    @elseif($tracking->status == 'Hoàn thành') badge-success
                                                    @else badge-secondary @endif"
                                                    style="cursor: pointer;"
                                                    data-id="{{ $tracking->id }}"
                                                    data-name="{{ $tracking->MaterialName }}">
                                                    {{ $tracking->status }}
                                                </span>
                                            </td>
                                            <td>
                                                <div class="intermediates-container"
                                                    id="intermediates-{{ $tracking->id }}">
                                                    <ul class="list-group">
                                                        @foreach ($tracking->intermediateCategories as $ic)
                                                            <li
                                                                class="list-group-item d-flex justify-content-between align-items-center">
                                                                {{ $ic->intermediateCategory->productName->name ?? 'N/A' }}
                                                                <br><small>(Mã: {{ $ic->intermediateCategory->intermediate_code ?? 'N/A' }})</small>
                                                                <span class="badge badge-primary badge-pill btn-show-ic-plan-masters"
                                                                    style="cursor: pointer;"
                                                                    data-tracking-id="{{ $tracking->id }}"
                                                                    data-ic-id="{{ $ic->intermediateCategory->id }}"
                                                                    data-name="{{ $ic->intermediateCategory->productName->name ?? 'N/A' }}">
                                                                    {{ $ic->num_of_finished_batch }} /
                                                                    {{ $ic->num_of_tracking_batch }} lô
                                                                </span>
                                                            </li>
                                                        @endforeach
                                                    </ul>
                                                </div>
                                            </td>
                                            <td>
                                                @if ($tracking->status == 'Chờ phê duyệt')
                                                    <button class="btn btn-sm btn-success btn-approve"
                                                        data-id="{{ $tracking->id }}">Duyệt</button>
                                                @endif
                                                <button class="btn btn-sm btn-warning btn-edit"
                                                    data-tracking="{{ json_encode($tracking) }}">Sửa</button>
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

<!-- Modal Hiển thị Lô Sản Xuất -->
<div class="modal fade" id="planMastersModal" tabindex="-1" role="dialog" aria-labelledby="planMastersModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document" style="max-width: 95%;">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="planMastersModalLabel">Các lô sản xuất đã gắn</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body" id="planMastersTableBody">
                <!-- Data will be populated via AJAX -->
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        $('#validationTrackingTable').DataTable({
            "paging": false,
            "lengthChange": false,
            "searching": true,
            "ordering": true,
            "info": false,
            "autoWidth": false,
            "responsive": false,
        });

        $('.btn-approve').on('click', function() {
            let id = $(this).data('id');
            if (confirm('Bạn có chắc chắn muốn duyệt nguyên liệu này để bắt đầu theo dõi?')) {
                $.ajax({
                    url: '{{ route('pages.plan.validation_tracking.approve') }}',
                    method: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        id: id
                    },
                    success: function(res) {
                        if (res.success) {
                            toastr.success('Đã duyệt thành công!');
                            location.reload();
                        } else {
                            toastr.error('Có lỗi xảy ra!');
                        }
                    }
                });
            }
        });

        // Xử lý sự kiện click để xem Lô sản xuất gắn kèm theo BTP
        $('.btn-show-ic-plan-masters').on('click', function() {
            let trackingId = $(this).data('tracking-id');
            let icId = $(this).data('ic-id');
            let name = $(this).data('name');
            $('#planMastersModalLabel').text('Lô sản xuất liên quan: BTP ' + name);
            loadPlanMasters(trackingId, icId);
        });

        // Xử lý sự kiện click để xem tất cả Lô sản xuất gắn kèm
        $('.btn-show-all-plan-masters').on('click', function() {
            let trackingId = $(this).data('id');
            let name = $(this).data('name');
            $('#planMastersModalLabel').text('Tất cả lô sản xuất theo dõi NL: ' + name);
            loadPlanMasters(trackingId, null);
        });

        function loadPlanMasters(trackingId, icId = null) {
            $('#planMastersTableBody').html('<div class="text-center p-4">Đang tải dữ liệu...</div>');
            $('#planMastersModal').modal('show');

            let url = '{{ url("plan/validation_tracking/get_plan_masters") }}/' + trackingId;
            if (icId) {
                url += '?ic_id=' + icId;
            }

            $.ajax({
                url: url,
                method: 'GET',
                success: function(res) {
                    if (res && res.html) {
                        $('#planMastersTableBody').html(res.html);
                    } else {
                        $('#planMastersTableBody').html('<div class="text-center p-3">Không có lô sản xuất nào được gắn</div>');
                    }
                },
                error: function() {
                    $('#planMastersTableBody').html('<div class="text-center text-danger p-3">Lỗi khi tải dữ liệu</div>');
                }
            });
        }

        // Fix DataTables inside Bootstrap tabs
        $('a[data-toggle="pill"]').on('shown.bs.tab', function(e){
            $.fn.dataTable.tables({ visible: true, api: true }).columns.adjust().draw();
        });

        $('.btn-edit').on('click', function() {
            let tracking = $(this).data('tracking');
            $('#edit_id').val(tracking.id);
            $('#edit_MatID').val(tracking.MatID);
            $('#edit_MaterialName').val(tracking.MaterialName);
            $('#edit_purpose').val(tracking.purpose);
            $('#edit_CC_num').val(tracking.CC_num);
            $('#edit_note').val(tracking.note);

              // Rander intermediate categories
              let html = '';
              tracking.intermediate_categories.forEach(function(ic, index) {
                  let icCode = ic.intermediate_category ? ic.intermediate_category.intermediate_code : ic.intermediate_category_id;
                  let icName = ic.intermediate_category && ic.intermediate_category.product_name ? ic.intermediate_category.product_name.name : '';
                  html += `
                  <tr class="ic-row">
                      <td>
                          <input type="hidden" name="intermediate_category_ids[]" value="${ic.intermediate_category_id}">
                          <div class="font-weight-bold text-primary">${icCode}</div>
                          <div class="text-secondary">${icName}</div>
                      </td>
                      <td>
                          <input type="number" class="form-control form-control-sm" name="num_of_tracking_batches[]" value="${ic.num_of_tracking_batch}" min="1" required>
                      </td>
                      <td>
                          <input type="text" class="form-control form-control-sm" name="ic_notes[]" value="${ic.note || ''}" placeholder="...">
                      </td>
                      <td class="text-center">
                          <button type="button" class="btn btn-sm btn-outline-danger btn-remove-ic"><i class="fas fa-trash"></i></button>
                      </td>
                  </tr>`;
              });
              $('#edit_ic_container').html(html);

            $('#updateModal').modal('show');
        });
    });
</script>
