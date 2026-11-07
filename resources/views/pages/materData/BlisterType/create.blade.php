<!-- Modal -->
<div class="modal fade" id="create_modal" tabindex="-1" role="dialog" aria-labelledby="ModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">

        <form action="{{ route('pages.materData.blister_type.store') }}" method="POST">
            @csrf

            <div class="modal-content">
                <div class="modal-header">
                    <a href="{{ route('pages.general.home') }}">
                        <img src="{{ asset('img/iconstella.svg') }}" style="opacity: 0.8 ; max-width:45px;">
                    </a>

                    <h4 class="modal-title w-100 text-center" style="color: #CDC717">
                        {{ 'Tạo Mới Loại Máy' }}
                    </h4>

                    <button type="button" class="close" data-dismiss="modal" aria-label="Đóng">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>

                <div class="modal-body">
                    {{-- STAGE --}}
                    <div class="form-group">
                        <label for="stage_code">Công Đoạn</label>
                        <select class="form-control" name="stage_code">
                            @foreach ($stages as $stageCode => $stageName)
                                <option value="{{ $stageCode }}" {{ (string) old('stage_code', 7) === (string) $stageCode ? 'selected' : '' }}>{{ $stageName }}</option>
                            @endforeach
                        </select>
                        <small class="text-muted">Đóng Gói: máy ép vỉ, khớp khuôn ép vỉ. Định Hình: máy dập viên / đóng nang, khớp khuôn Định Hình.</small>
                    </div>
                    @error('stage_code', 'createErrors')
                        <div class="alert alert-danger">{{ $message }}</div>
                    @enderror

                    {{-- NAME --}}
                    <div class="form-group">
                        <label for="name">Tên Loại Máy </label>
                        <input type="text" class="form-control" name="name" value="{{ old('name') }}">
                    </div>
                    @error('name', 'createErrors')
                        <div class="alert alert-danger">{{ $message }}</div>
                    @enderror

                    {{-- CODE --}}
                    <div class="form-group mt-3">
                        <label for="code">Mã Nhóm Máy (Code) </label>
                        <input type="number" class="form-control" name="code" value="{{ old('code') }}" placeholder="Nhập mã (VD: 1, 2, 3, 4...)">
                        <small class="text-muted">Các loại máy lắp lẫn khuôn cho nhau thì dùng chung một mã. Mã không được trùng với loại máy của công đoạn khác.</small>
                    </div>
                    @error('code', 'createErrors')
                        <div class="alert alert-danger">{{ $message }}</div>
                    @enderror
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
                    <button type="submit" class="btn btn-primary">
                        Lưu
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Scripts -->

{{-- //Show modal nếu có lỗi validation --}}
@if ($errors->createErrors->any())
    <script>
        $(document).ready(function() {
            $('#create_modal').modal('show');
        });
    </script>
@endif



@if (session('success'))
    <script>
        Swal.fire({
            title: 'Thành công!',
            text: '{{ session('success') }}',
            icon: 'success',
            timer: 1000, // tự đóng sau 2 giây
            showConfirmButton: false
        });
    </script>
@endif
