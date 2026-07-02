<div class="modal fade" id="updateModal" tabindex="-1" role="dialog" aria-labelledby="updateModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="updateModalLabel">Cập Nhật Theo Dõi Thẩm Định</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="formUpdateTracking">
                @csrf
                <input type="hidden" name="id" id="edit_id">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label>Mã Nguyên Liệu</label>
                            <input type="text" class="form-control" name="MatID" id="edit_MatID" required>
                        </div>
                        <div class="col-md-6 form-group">
                            <label>Tên Nguyên Liệu</label>
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

                    <hr>
                    <h6>Danh sách Bán Thành Phẩm áp dụng</h6>
                    <div class="row mb-1">
                        <div class="col-md-5"><b>Bán Thành Phẩm</b></div>
                        <div class="col-md-3"><b>Số lô theo dõi</b></div>
                        <div class="col-md-3"><b>Ghi chú</b></div>
                        <div class="col-md-1">
                            <button type="button" class="btn btn-success btn-sm btn-add-ic-edit"><i class="fas fa-plus"></i></button>
                        </div>
                    </div>
                    <div id="edit_ic_container">
                        <!-- Render via JS -->
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
                    <button type="submit" class="btn btn-primary">Lưu Thay Đổi</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // We already have generic ic-row addition for update
    $(document).on('click', '.btn-add-ic-edit', function() {
        let options = `<option value="">Chọn BTP</option>`;
        @foreach(App\Models\IntermediateCategory::with('productName')->get() as $ic)
            options += `<option value="{{ $ic->id }}">{{ $ic->code }} - {{ $ic->productName->name ?? '' }}</option>`;
        @endforeach

        let newRow = `
            <div class="row mb-2 ic-row">
                <div class="col-md-5">
                    <select class="form-control select2bs4" name="intermediate_category_ids[]" required>
                        ${options}
                    </select>
                </div>
                <div class="col-md-3">
                    <input type="number" class="form-control" name="num_of_tracking_batches[]" value="1" min="1" required>
                </div>
                <div class="col-md-3">
                    <input type="text" class="form-control" name="ic_notes[]" placeholder="...">
                </div>
                <div class="col-md-1">
                    <button type="button" class="btn btn-danger btn-remove-ic"><i class="fas fa-trash"></i></button>
                </div>
            </div>
        `;
        $('#edit_ic_container').append(newRow);
        
        $('#edit_ic_container').find('.select2bs4').last().select2({
            theme: 'bootstrap4'
        });
    });

    $('#formUpdateTracking').on('submit', function(e) {
        e.preventDefault();
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
