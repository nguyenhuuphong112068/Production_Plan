<div class="modal fade" id="updateModal" tabindex="-1" role="dialog" aria-labelledby="updateModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="updateModalLabel">Cập Nhật Thông Tin Khuôn Mẫu</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="{{ route('pages.materData.blister_mold.update') }}" method="POST">
                @csrf
                <input type="hidden" name="id" id="update_id">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="update_code">Mã Khuôn Mẫu <span class="text-danger">*</span></label>
                        <input type="text" name="code" id="update_code" class="form-control @error('code', 'updateErrors') is-invalid @enderror" required maxlength="15">
                        @error('code', 'updateErrors')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="form-group">
                        <label for="update_amount">Số Lượng</label>
                        <input type="number" name="amount" id="update_amount" min="0" class="form-control @error('amount', 'updateErrors') is-invalid @enderror">
                        @error('amount', 'updateErrors')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-primary">Lưu thay đổi</button>
                </div>
            </form>
        </div>
    </div>
</div>

@if($errors->updateErrors->any())
    <script>
        $(document).ready(function() {
            $('#updateModal').modal('show');
        });
    </script>
@endif
