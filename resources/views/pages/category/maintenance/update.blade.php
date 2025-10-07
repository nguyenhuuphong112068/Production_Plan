<link href="{{ asset('css/bootstrap.min.css') }}" rel="stylesheet">
<!-- Update Modal -->
<div class="modal fade" id="update_modal" tabindex="-1" role="dialog" aria-labelledby="update_ModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <form action="{{ route('pages.category.maintenance.update') }}" method="POST">
            @csrf
            <div class="modal-content">

                {{-- Header --}}
                <div class="modal-header">
                    <a href="{{ route('pages.general.home') }}">
                        <img src="{{ asset('img/iconstella.svg') }}" style="opacity: 0.8; max-width:45px;">
                    </a>
                    <h4 class="modal-title w-100 text-center" id="update_ModalLabel" style="color: #CDC717">
                        {{ 'Cập Nhật Danh Mục Bảo Trì - Hiệu Chuẩn' }}
                    </h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Đóng">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>

                {{-- Body --}}
                <div class="modal-body">

                    {{-- CODE --}}
                    <div class="form-group">
                        <label for="code">Mã Thiết Bị</label>
                        <input type="text" class="form-control" name="code" value="{{ old('code') }}" readonly>
                    </div>
                 

                    {{-- NAME --}}
                    <div class="form-group">
                        <label for="name">Tên Thiết Bị</label>
                        <input type="text" class="form-control" name="name" value="{{ old('name') }}" readonly>
                    </div>
                   

                    {{-- Room --}}
                    <div class="form-group">
                        <label>Phòng Sản Xuất</label>
                        <input type="text" class="form-control" name="room" value="{{ old('room') }}" readonly>
                    </div>

                    {{-- QUOTA --}}
                    <div class="form-group">
                        <label for="p_time">Thời Gian Thực Hiện</label>
                        <input type="text" class="form-control" name="quota"
                            value="{{ old('quota') }}"
                            placeholder="HH:mm"
                            pattern="^(?:\d{1,2}|1\d{2}|200):(00|15|30|45)$"
                            title="Nhập giờ hợp lệ">
                    </div>
                    @error('quota', 'updateErrors')
                        <div class="alert alert-danger">{{ $message }}</div>
                    @enderror

                    {{-- NOTE --}}
                    <div class="form-group">
                        <label for="note">Ghi Chú</label>
                        <input type="text" class="form-control" name="note" value="{{ old('note') }}">
                    </div>

                </div> {{-- /modal-body --}}
                <input type="hidden" class="form-control" name="id" value="{{ old('id') }}">
                {{-- Footer --}}
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
                    <button type="submit" class="btn btn-primary" id ="btnUpdate">Lưu</button>
                </div>

            </div> {{-- /modal-content --}}
        </form>
    </div> {{-- /modal-dialog --}}
</div> {{-- /modal --}}

<!-- Scripts -->


{{-- Show modal nếu có lỗi validation --}}
@if ($errors->updateErrors->any())
    <script>
        $(document).ready(function() {
            $('#update_modal').modal('show');
        });
    </script>
@endif

<script>
    $(document).ready(function() {
       preventDoubleSubmit("#update_modal", "#btnSave");   
    });
</script>
