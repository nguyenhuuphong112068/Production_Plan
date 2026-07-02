<div class="modal fade" id="createModal" tabindex="-1" role="dialog" aria-labelledby="createModalLabel" aria-hidden="true" style="z-index: 1050;">
    <div class="modal-dialog modal-xl" role="document" style="max-width: 80%;">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="createModalLabel"><i class="fas fa-plus-circle"></i> Thêm Mới Theo Dõi Thẩm Định</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="formCreateTracking">
                @csrf
                <div class="modal-body bg-light" style="max-height: calc(100vh - 150px); overflow-y: auto;">
                    <!-- Thông tin chung -->
                    <div class="card card-outline card-primary mb-4">
                        <div class="card-header">
                            <h3 class="card-title font-weight-bold">Thông tin chung</h3>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6 form-group">
                                    <label>Mã Nguyên Liệu <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="MatID" placeholder="VD: 12110120001" required>
                                </div>
                                <div class="col-md-6 form-group">
                                    <label>Tên Nguyên Liệu <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="MaterialName" placeholder="VD: Kollidon VA 64" required>
                                </div>
                                <div class="col-md-6 form-group">
                                    <label>Mục Đích Thẩm Định</label>
                                    <input type="text" class="form-control" name="purpose" placeholder="VD: Bổ sung nguồn, theo dõi độ ổn định...">
                                </div>
                                <div class="col-md-6 form-group">
                                    <label>Số Kiểm Soát Thay Đổi (CC_num)</label>
                                    <input type="text" class="form-control" name="CC_num" placeholder="VD: QA/CC010126">
                                </div>
                                <div class="col-md-12 form-group">
                                    <label>Ghi Chú Chung</label>
                                    <textarea class="form-control" name="note" rows="2" placeholder="Ghi chú thêm..."></textarea>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Danh sách BTP -->
                    <div class="card card-outline card-success">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h3 class="card-title font-weight-bold">Danh sách Bán Thành Phẩm áp dụng</h3>
                            <button type="button" class="btn btn-sm btn-success ml-auto" data-toggle="modal" data-target="#select_intermediate_category_modal" onclick="window.activeICContainer = '#ic_container'">
                                <i class="fas fa-list-check"></i> Chọn từ danh mục BTP
                            </button>
                        </div>
                        <div class="card-body p-0">
                            <table class="table table-bordered table-striped mb-0">
                                <thead class="thead-light">
                                    <tr>
                                        <th style="width: 45%;">Mã / Tên Bán Thành Phẩm</th>
                                        <th style="width: 20%;">Số lô theo dõi</th>
                                        <th style="width: 25%;">Ghi chú (BTP)</th>
                                        <th style="width: 10%;" class="text-center">Thao tác</th>
                                    </tr>
                                </thead>
                                <tbody id="ic_container">
                                    <tr id="empty-ic-row">
                                        <td colspan="4" class="text-center text-muted py-4">
                                            <em>Chưa có Bán thành phẩm nào được chọn. Hãy bấm "Chọn từ danh mục BTP" để thêm.</em>
                                        </td>
                                    </tr>
                                    <!-- Các hàng BTP sẽ được gen ra đây -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Lưu Lại</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {

    // Remove row event
    $(document).on('click', '.btn-remove-ic', function() {
        $(this).closest('tr').remove();
        if($('#ic_container tr.ic-row').length === 0) {
            $('#empty-ic-row').show();
        }
    });

    // Form submit
    $('#formCreateTracking').on('submit', function(e) {
        e.preventDefault();
        if($('#ic_container tr.ic-row').length === 0) {
            alert('Vui lòng chọn ít nhất 1 Bán thành phẩm áp dụng!');
            return;
        }

        let formData = $(this).serialize();
        $.ajax({
            url: "{{ route('pages.plan.validation_tracking.store') }}",
            type: 'POST',
            data: formData,
            success: function(res) {
                if (res.success) {
                    alert(res.message);
                    location.reload();
                } else {
                    alert(res.message);
                }
            },
            error: function(err) {
                alert('Có lỗi xảy ra!');
                console.log(err);
            }
        });
    });

    // Listen to confirm button from multi-select modal
    $('#btnConfirmICSelection').on('click', function() {
        let selectedCheckboxes = $('.ic-checkbox:checked');
        if(selectedCheckboxes.length === 0) {
            alert("Vui lòng chọn ít nhất một Bán thành phẩm!");
            return;
        }

        $('#empty-ic-row').hide();
        let html = '';
        selectedCheckboxes.each(function() {
            let icId = $(this).val();
            let icCode = $(this).data('code');
            let icName = $(this).data('name');

            let container = window.activeICContainer || '#ic_container';
            // Prevent duplicate adding
            if($(container + ' input[value="'+icId+'"]').length === 0) {
                html += `
                <tr class="ic-row">
                    <td>
                        <input type="hidden" name="intermediate_category_ids[]" value="${icId}">
                        <div class="font-weight-bold text-primary">${icCode}</div>
                        <div class="text-secondary">${icName}</div>
                    </td>
                    <td>
                        <input type="number" class="form-control form-control-sm" name="num_of_tracking_batches[]" value="1" min="1" required>
                    </td>
                    <td>
                        <input type="text" class="form-control form-control-sm" name="ic_notes[]" placeholder="...">
                    </td>
                    <td class="text-center">
                        <button type="button" class="btn btn-sm btn-outline-danger btn-remove-ic"><i class="fas fa-trash"></i></button>
                    </td>
                </tr>`;
            }
        });

        let container = window.activeICContainer || '#ic_container';
        if(html !== '') {
            $(container).append(html);
        }

        // Đóng modal chọn nhiều
        $('#select_intermediate_category_modal').modal('hide');
        
        // Uncheck all for next time
        $('.ic-checkbox').prop('checked', false);
        $('#selectAllIC').prop('checked', false);
    });
});
</script>
