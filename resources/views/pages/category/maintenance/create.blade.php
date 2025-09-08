<!-- Create Modal -->
<div class="modal fade" id="create_modal" tabindex="-1" role="dialog" aria-labelledby="uModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">

        <form action="{{ route('pages.category.maintenance.store') }}" method="POST">
            @csrf

            <div class="modal-content">
                <div class="modal-header">


                    <a href="{{ route('pages.general.home') }}">
                        <img src="{{ asset('img/iconstella.svg') }}" style="opacity: 0.8 ; max-width:45px;">
                    </a>

                    <h4 class="modal-title w-100 text-center" id="ModalLabel" style="color: #CDC717">
                        {{ 'Tạo Mới Danh Mục Bảo Trì - Hiệu Chuẩn' }}
                    </h4>

                    <button type="button" class="close" data-dismiss="modal" aria-label="Đóng">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>

                <div class="modal-body">
                    {{-- CODE --}}
                    <div class="form-group">
                        <label for="code">Mã Thiết Bị</label>
                        <input type="text" class="form-control" name="code" id = "code"
                            value="{{ old('code') }}">
                    </div>
                    @error('code', 'createErrors')
                        <div class="alert alert-danger">{{ $message }}</div>
                    @enderror

                    {{-- NAME --}}
                    <div class="form-group">
                        <label for="name">Tên Thiết Bị</label>
                        <input type="text" class="form-control" name="name" value="{{ old('name') }}">
                    </div>
                    @error('name', 'createErrors')
                        <div class="alert alert-danger">{{ $message }}</div>
                    @enderror

                    {{-- Room --}}
                    <div class="form-group">
                        <label>Phòng Sản Xuất</label>
                        <select class="select2" multiple="multiple" data-placeholder="Select a State" id ="room_id"
                            style="width: 100%; height:30mm" name="room_id[]">
                            @foreach ($rooms as $item)
                                <option value="{{ $item->id }}"
                                    {{ collect(old('room_id', []))->contains($item->id) ? 'selected' : '' }}>
                                    {{ $item->code . ' - ' . $item->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('room_id', 'createErrors')
                            <div class="alert alert-danger">{{ $message }}</div>
                        @enderror
                        <div id="check_result" class="mt-2"></div>
                    </div>

                    {{-- quota --}}
                    <div class="form-group">
                        <label for="p_time">Thời Gian Thực Hiện</label>
                        <input type="text" class="form-control" name="quota" value="{{ old('quota') }}"
                            placeholder="HH:mm" pattern="^(?:\d{1,2}|1\d{2}|200):(00|15|30|45)$"
                            title="Nhập giờ hợp lệ">
                    </div>
                    @error('quota', 'createErrors')
                        <div class="alert alert-danger">{{ $message }}</div>
                    @enderror

                    {{-- Ghi chú --}}
                    <div class="form-group">
                        <label for="note">Ghi Chú</label>
                        <input type="text" class="form-control" name="note" value="{{ old('note') }}">
                    </div>


                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
                        <button type="submit" class="btn btn-primary" id = "btnSave">
                            Lưu
                        </button>
                    </div>
                </div>
              </div>
        </form>
    </div>
</div>



{{-- //Show modal nếu có lỗi validation --}}
@if ($errors->createErrors->any())
    <script>
        $(document).ready(function() {
            $('#create_modal').modal('show');
        });
    </script>
@endif

<script>
    preventDoubleSubmit("#create_modal", "#btnSave");
    $(document).ready(function() {
        $('#room_id').on('change', function() {
            let code = $("#code").val();
            let roomId = $(this).val()?.slice(-1)[0]; // lấy room_id cuối cùng vừa chọn
            let $select = $(this);
            let html = "";
            if (!code) {


                // reset select về rỗng để tránh chọn nhầm
                html =
                    '<div class="text-danger"> Vui Lòng Nhập Mã Thiết Bị Trước Khi Chọn Vị Trí Lắp Đặt!</div>';
                $('#room_id').val([]);
                $('#check_result').html(html);
                return;
            }

            if (roomId && code) {
                $.ajax({
                    url: "{{ route('pages.category.maintenance.check_code_room_id') }}",
                    type: "POST",
                    data: {
                        room_id: roomId, // gửi 1 giá trị
                        code: code,
                        _token: "{{ csrf_token() }}"
                    },
                    success: function(response) {
                        // let html = "";
                        if (response.exists) {
                            html = '<div class="text-danger"> Dữ liệu đã tồn tại!</div>';
                            let selected = $select.val() || [];
                            selected = selected.filter(id => id !== roomId);
                            $select.val(selected).trigger('change');
                        }
                        $('#check_result').html(html);
                    }
                });
            } else {
                $('#check_result').html('');
            }
        });

    });
</script>
