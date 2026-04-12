<div class="modal fade" id="update_modal" tabindex="-1" role="dialog" aria-labelledby="update_modal_label" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form action="{{ route('pages.assignment.personnel.update') }}" method="POST">
                @csrf
                <input type="hidden" name="id">
                <div class="modal-header">
                    <h5 class="modal-title" id="update_modal_label">Cập Nhật Thông Tin Nhân Sự</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Mã Nhân Viên <span class="text-danger">*</span></label>
                        <input type="text" name="code" class="form-control" required>
                        @if ($errors->updateErrors->has('code'))
                            <span class="text-danger small">{{ $errors->updateErrors->first('code') }}</span>
                        @endif
                    </div>
                    <div class="form-group">
                        <label>Tên Nhân Viên <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" required>
                        @if ($errors->updateErrors->has('name'))
                            <span class="text-danger small">{{ $errors->updateErrors->first('name') }}</span>
                        @endif
                    </div>
                    <div class="form-group">
                        <label>Mã Phòng Ban</label>
                        <input type="text" name="deparment_code" class="form-control">
                    </div>
                    <div class="form-group">
                        <label>Tên Tổ</label>
                        <input type="text" name="group_name" class="form-control">
                    </div>
                    <div class="form-group">
                        <label>Mã Tổ</label>
                        <input type="text" name="group_code" class="form-control">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-warning">Cập Nhật</button>
                </div>
            </form>
        </div>
    </div>
</div>
