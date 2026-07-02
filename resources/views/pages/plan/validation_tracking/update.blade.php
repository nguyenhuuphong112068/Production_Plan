<div class="modal fade" id="updateModal" tabindex="-1" role="dialog" aria-labelledby="updateModalLabel" aria-hidden="true" style="z-index: 1050;">
    <div class="modal-dialog modal-xl" role="document" style="max-width: 80%;">
        <div class="modal-content">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title" id="updateModalLabel"><i class="fas fa-edit"></i> Cập Nhật Theo Dõi Thẩm Định</h5>
                <button type="button" class="close text-dark" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="formUpdateTracking">
                @csrf
                <input type="hidden" name="id" id="edit_id">
                <div class="modal-body bg-light" style="max-height: calc(100vh - 150px); overflow-y: auto;">
                    <!-- Thông tin chung -->
                    <div class="card card-outline card-warning mb-4">
                        <div class="card-header">
                            <h3 class="card-title font-weight-bold">Thông tin chung</h3>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6 form-group">
                                    <label>Mã Nguyên Liệu <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="MatID" id="edit_MatID" required>
                                </div>
                                <div class="col-md-6 form-group">
                                    <label>Tên Nguyên Liệu <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="MaterialName" id="edit_MaterialName" required>
                                </div>
                                <div class="col-md-6 form-group">
                                    <label>Mục Đích Thẩm Định</label>
                                    <input type="text" class="form-control" name="purpose" id="edit_purpose">
                                </div>
                                <div class="col-md-6 form-group">
                                    <label>Số Kiểm Soát Thay Đổi (CC_num)</label>
                                    <input type="text" class="form-control" name="CC_num" id="edit_CC_num">
                                </div>
                                <div class="col-md-12 form-group">
                                    <label>Ghi Chú Chung</label>
                                    <textarea class="form-control" name="note" id="edit_note" rows="2"></textarea>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Danh sách BTP -->
                    <div class="card card-outline card-success">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h3 class="card-title font-weight-bold">Danh sách Bán Thành Phẩm áp dụng</h3>
                            <button type="button" class="btn btn-sm btn-success ml-auto" data-toggle="modal" data-target="#select_intermediate_category_modal" onclick="window.activeICContainer = '#edit_ic_container'">
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
                                <tbody id="edit_ic_container">
                                    <!-- Render via JS -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Lưu Thay Đổi</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    $('#formUpdateTracking').on('submit', function(e) {
        e.preventDefault();
        if($('#edit_ic_container tr.ic-row').length === 0) {
            alert('Vui lòng chọn ít nhất 1 Bán thành phẩm áp dụng!');
            return;
        }

        let formData = $(this).serialize();
        $.ajax({
            url: "{{ route('pages.plan.validation_tracking.update') }}",
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
});
</script>
