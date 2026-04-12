<div class="modal fade" id="create_modal" tabindex="-1" role="dialog" aria-labelledby="create_modal_label" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form action="{{ route('pages.assignment.personnel.store') }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="create_modal_label">Thêm Nhân Sự Mới</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Mã Nhân Viên <span class="text-danger">*</span></label>
                        <input type="text" name="code" class="form-control" required placeholder="Ví dụ: NV001">
                        @if ($errors->createErrors->has('code'))
                            <span class="text-danger small">{{ $errors->createErrors->first('code') }}</span>
                        @endif
                    </div>
                    <div class="form-group">
                        <label>Tên Nhân Viên <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" required placeholder="Ví dụ: Nguyễn Văn A">
                        @if ($errors->createErrors->has('name'))
                            <span class="text-danger small">{{ $errors->createErrors->first('name') }}</span>
                        @endif
                    </div>
                    <div class="form-group">
                        <label>Mã Phòng Ban</label>
                        <input type="text" name="deparment_code" class="form-control" value="{{ $currentDepartment ?? '' }}" placeholder="Ví dụ: QC">
                    </div>
                    <div class="form-group">
                        <label>Tên Tổ</label>
                        <input type="text" name="group_name" class="form-control" placeholder="Ví dụ: Tổ HC">
                    </div>
                    <div class="form-group">
                        <label>Mã Tổ</label>
                        <input type="text" name="group_code" class="form-control" placeholder="Ví dụ: THC">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-primary">Lưu Lại</button>
                </div>
            </form>
        </div>
    </div>
</div>
