<div class="content-wrapper">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>Theo Dõi Thẩm Định Nguyên Liệu</h1>
                </div>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#createModal">
                                <i class="fas fa-plus"></i> Thêm Nguyên Liệu Mới
                            </button>
                        </div>
                        <div class="card-body">
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
                                                <span class="badge 
                                                    @if($tracking->status == 'Chờ phê duyệt') badge-warning
                                                    @elseif($tracking->status == 'Đang theo dõi') badge-primary
                                                    @elseif($tracking->status == 'Hoàn thành') badge-success
                                                    @else badge-secondary @endif">
                                                    {{ $tracking->status }}
                                                </span>
                                            </td>
                                            <td>
                                                <button class="btn btn-sm btn-info btn-toggle-intermediates" data-id="{{ $tracking->id }}">
                                                    <i class="fas fa-list"></i> Xem BTP ({{ $tracking->intermediateCategories->count() }})
                                                </button>
                                                <div class="intermediates-container d-none" id="intermediates-{{ $tracking->id }}" style="margin-top: 10px;">
                                                    <ul class="list-group">
                                                        @foreach ($tracking->intermediateCategories as $ic)
                                                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                                                {{ $ic->intermediateCategory->productName->name ?? 'N/A' }} 
                                                                (Mã: {{ $ic->intermediateCategory->code ?? 'N/A' }})
                                                                <span class="badge badge-primary badge-pill">
                                                                    {{ $ic->num_of_finished_batch }} / {{ $ic->num_of_tracking_batch }} lô
                                                                </span>
                                                            </li>
                                                        @endforeach
                                                    </ul>
                                                </div>
                                            </td>
                                            <td>
                                                @if($tracking->status == 'Chờ phê duyệt')
                                                    <button class="btn btn-sm btn-success btn-approve" data-id="{{ $tracking->id }}">Duyệt</button>
                                                @endif
                                                <button class="btn btn-sm btn-warning btn-edit" data-tracking="{{ json_encode($tracking) }}">Sửa</button>
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

<script>
document.addEventListener('DOMContentLoaded', function () {
    $('#validationTrackingTable').DataTable({
        "paging": true,
        "lengthChange": true,
        "searching": true,
        "ordering": true,
        "info": true,
        "autoWidth": false,
        "responsive": true,
    });

    $('.btn-toggle-intermediates').on('click', function() {
        let id = $(this).data('id');
        $('#intermediates-' + id).toggleClass('d-none');
    });

    $('.btn-approve').on('click', function() {
        let id = $(this).data('id');
        if (confirm('Bạn có chắc chắn muốn duyệt nguyên liệu này để bắt đầu theo dõi?')) {
            $.ajax({
                url: "{{ route('pages.plan.validation_tracking.approve') }}",
                type: 'POST',
                data: {
                    _token: "{{ csrf_token() }}",
                    id: id
                },
                success: function (res) {
                    if (res.success) {
                        alert(res.message);
                        location.reload();
                    } else {
                        alert(res.message);
                    }
                }
            });
        }
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
            html += `
                <div class="row mb-2 ic-row">
                    <div class="col-md-5">
                        <select class="form-control select2bs4" name="intermediate_category_ids[]" required>
                            <option value="${ic.intermediate_category_id}" selected>${ic.intermediate_category ? ic.intermediate_category.code : ic.intermediate_category_id}</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <input type="number" class="form-control" name="num_of_tracking_batches[]" value="${ic.num_of_tracking_batch}" min="1" required>
                    </div>
                    <div class="col-md-3">
                        <input type="text" class="form-control" name="ic_notes[]" value="${ic.note || ''}">
                    </div>
                    <div class="col-md-1">
                        <button type="button" class="btn btn-danger btn-remove-ic"><i class="fas fa-trash"></i></button>
                    </div>
                </div>
            `;
        });
        $('#edit_ic_container').html(html);

        $('#updateModal').modal('show');
    });
});
</script>
