<div class="modal fade" id="createModal" tabindex="-1" role="dialog" aria-labelledby="createModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="createModalLabel">Thêm Mới Theo Dõi Thẩm Định</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="formCreateTracking">
                @csrf
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label>Mã Nguyên Liệu</label>
                            <input type="text" class="form-control" name="MatID" required>
                        </div>
                        <div class="col-md-6 form-group">
                            <label>Tên Nguyên Liệu</label>
                            <input type="text" class="form-control" name="MaterialName" required>
                        </div>
                        <div class="col-md-6 form-group">
                            <label>Mục Đích Thẩm Định</label>
                            <input type="text" class="form-control" name="purpose">
                        </div>
                        <div class="col-md-6 form-group">
                            <label>Số Kiểm Soát Thay Đổi (CC_num)</label>
                            <input type="text" class="form-control" name="CC_num">
                        </div>
                        <div class="col-md-12 form-group">
                            <label>Ghi Chú Chung</label>
                            <textarea class="form-control" name="note" rows="2"></textarea>
                        </div>
                    </div>

                    <hr>
                    <h6>Danh sách Bán Thành Phẩm áp dụng</h6>
                    <div id="ic_container">
                        <div class="row mb-2 ic-row">
                            <div class="col-md-5">
                                <label>Mã Bán Thành Phẩm</label>
                                <select class="form-control select2bs4" name="intermediate_category_ids[]" required>
                                    <option value="">Chọn BTP</option>
                                    @foreach(App\Models\IntermediateCategory::with('productName')->get() as $ic)
                                        <option value="{{ $ic->id }}">{{ $ic->code }} - {{ $ic->productName->name ?? '' }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label>Số lô theo dõi</label>
                                <input type="number" class="form-control" name="num_of_tracking_batches[]" value="1" min="1" required>
                            </div>
                            <div class="col-md-3">
                                <label>Ghi chú</label>
                                <input type="text" class="form-control" name="ic_notes[]" placeholder="...">
                            </div>
                            <div class="col-md-1 d-flex align-items-end">
                                <button type="button" class="btn btn-success btn-add-ic"><i class="fas fa-plus"></i></button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
                    <button type="submit" class="btn btn-primary">Lưu Lại</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    $('.select2bs4').select2({
        theme: 'bootstrap4'
    });

    $(document).on('click', '.btn-add-ic', function() {
        let firstRow = $('.ic-row:first').clone();
        firstRow.find('input').val('');
        firstRow.find('input[type="number"]').val(1);
        firstRow.find('.btn-add-ic').removeClass('btn-add-ic btn-success').addClass('btn-remove-ic btn-danger').html('<i class="fas fa-trash"></i>');
        // Reset select2
        firstRow.find('.select2-container').remove();
        firstRow.find('select').removeClass('select2-hidden-accessible').removeAttr('data-select2-id').val('');
        
        $('#ic_container').append(firstRow);
        
        firstRow.find('.select2bs4').select2({
            theme: 'bootstrap4'
        });
    });

    $(document).on('click', '.btn-remove-ic', function() {
        $(this).closest('.ic-row').remove();
    });

    $('#formCreateTracking').on('submit', function(e) {
        e.preventDefault();
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
});
</script>
