<style>
    .step-checkbox {
        width: 20px;
        height: 20px;
        cursor: pointer;
        accent-color: #007bff;
        /* màu xanh bootstrap */
    }

    .step-checkbox:checked {
        box-shadow: 0 0 5px #007bff;
    }

    .time {
        width: 100%;
        border: none;
        outline: none;
        background: transparent;
        text-align: center;
        height: 100%;
        padding: 2px 4px;
        box-sizing: border-box;
    }

    /* Khi focus thì chỉ có viền nhẹ để người dùng biết đang nhập */
    .time:focus {
        border: 1px solid #007bff;
        border-radius: 2px;
        background-color: #fff;
    }

    /* Tùy chọn: nếu bạn muốn chữ canh giữa theo chiều dọc */
    td input.time {
        display: block;
        margin: auto;
    }
</style>


<div class="content-wrapper">
    <!-- Main content -->
    <div class="card">

        <div class="card-header mt-4">
            {{-- <h3 class="card-title">Ghi Chú Nếu Có</h3> --}}

        </div>

        <!-- /.card-Body -->
        <div class="card-body">


            @php
                $auth_update = user_has_permission(
                    session('user')['userId'],
                    'category_maintenance_update',
                    'disabled',
                );
                $auth_deActive = user_has_permission(
                    session('user')['userId'],
                    'category_maintenance_deActive',
                    'disabled',
                );
            @endphp


            <table id="data_table_instrument" class="table table-bordered table-striped">

                <thead style = "position: sticky; top: 60px; background-color: white; z-index: 1020">

                    <tr>
                        <th>STT</th>
                        <th>Mã Thiết Bị Lớn</th>
                        <th>Mã Thiết Bị Con</th>
                        <th>Tên Thiết Bị</th>
                        <th>Tần Suất BT-HC</th>
                        <th>Vị Trí Lắp Đặt</th>
                        <th>Vị Trí Thẩm Định</th>
                        <th>Thời gian Thực Hiện</th>
                        <th>Có Thuộc Hệ Thông HVAC?</th>

                        <th>Người Tạo/Ngày Tạo</th>

                        <th>Vô Hiệu</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($datas as $data)
                        <tr>
                            <td>{{ $loop->iteration }} </td>
                            <td>{{ $data->parent_code }}</td>
                            @if ($data->active)
                                <td class="text-success"> {{ $data->code }}</td>
                            @else
                                <td class="text-danger"> {{ $data->code }}</td>
                            @endif
                            <td>{{ $data->name }}</td>
                            <td>{{ $data->room_code }}</td>
                            <td>{{ $data->sch_type }}</td>

                            <td>
                                @if ($data->exe_room_name)
                                    <span>
                                        {{ $data->exe_room_name }}
                                    </span>
                                    <input type="hidden" name="room_id" value="{{ $data->room_id }}">
                                @else
                                    <select class="form-control select-room" name="room_id"
                                        data-id="{{ $data->id }}">
                                        <option value="">-- Phòng Thực Hiện --</option>
                                        @foreach ($rooms as $room)
                                            <option value="{{ $room->id }}"
                                                {{ ($data->room_id ?? null) == $room->id ? 'selected' : '' }}>
                                                {{ $room->code . ' - ' . $room->name }}</option>
                                        @endforeach
                                    </select>
                                @endif
                            </td>



                            <td>
                                <input type= "text" class="time" name="quota" value = "{{ $data->quota }}"
                                    data-id={{ $data->id }} {{ $auth_update }}>
                            </td>

                            <td class="text-center">
                                <div class="form-check form-switch text-center">
                                    <input class="form-check-input step-checkbox" type="checkbox" role="switch"
                                        data-id="{{ $data->id }}" id="checkbox-{{ $data->id }}"
                                        {{ $data->is_HVAC ? 'checked' : '' }}>
                                </div>
                            </td>

                            <td>
                                <div> {{ $data->created_by }} </div>
                                <div>
                                    {{ $data->created_at ? \Carbon\Carbon::parse($data->created_at)->format('d/m/Y') : '' }}
                                </div>
                            </td>




                            <td class="text-center align-middle">
                                <button type="button" class="btn btn-danger btn-deActive"
                                    data-id="{{ $data->id }}" data-name="{{ $data->name }}">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                    @endforeach

                </tbody>

            </table>

        </div>
        <!-- /.card-body -->
    </div>
    <!-- /.content -->
</div>
<script src="{{ asset('js/vendor/jquery-1.12.4.min.js') }}"></script>
<script src="{{ asset('js/popper.min.js') }}"></script>
<script src="{{ asset('js/bootstrap.min.js') }}"></script>

<script src="{{ asset('js/sweetalert2.all.min.js') }}"></script>

@if (session('success'))
    <script>
        Swal.fire({
            title: 'Thành công!',
            text: '{{ session('success') }}',
            icon: 'success',
            timer: 2000, // tự đóng sau 2 giây
            showConfirmButton: false
        });
    </script>
@endif

<script>
    $(document).ready(function() {
        document.body.style.overflowY = "auto";
        $('.btn-edit').click(function() {

            const button = $(this);
            const modal = $('#update_modal');

            // Gán dữ liệu vào input
            modal.find('input[name="id"]').val(button.data('id'));
            modal.find('input[name="code"]').val(button.data('code'));
            modal.find('input[name="name"]').val(button.data('name'));
            modal.find('input[name="room"]').val(button.data('room'));
            modal.find('input[name="quota"]').val(button.data('quota'));
            modal.find('input[name="note"]').val(button.data('note'));


        });



        $('.form-deActive').on('submit', function(e) {
            e.preventDefault(); // chặn submit mặc định
            const form = this;
            const productName = $(form).find('button[type="submit"]').data('name');
            const active = $(form).find('button[type="submit"]').data('type');

            let title = 'Bạn chắc chắn muốn vô hiệu hóa danh mục?'
            if (!active) {
                title = 'Bạn chắc chắn muốn phục hồi danh mục?'
            }



            Swal.fire({
                title: title,
                text: `Danh Mục: ${productName}`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#28a745',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Đồng ý',
                cancelButtonText: 'Hủy'
            }).then((result) => {
                if (result.isConfirmed) {
                    form.submit(); // chỉ submit sau khi xác nhận
                }
            });
        });


        $('#data_table_instrument').DataTable({
            paging: true,
            lengthChange: true,
            searching: true,
            ordering: true,
            info: true,
            autoWidth: false,
            pageLength: 10,
            language: {
                search: "Tìm kiếm:",
                lengthMenu: "Hiển thị _MENU_ dòng",
                info: "Hiển thị _START_ đến _END_ của _TOTAL_ dòng",
                paginate: {
                    previous: "Trước",
                    next: "Sau"
                }
            },
            infoCallback: function(settings, start, end, max, total, pre) {
                return pre + ` (Tổng: ${total} thiết bị)`;
            }

        });

        // AJAX Vô hiệu hóa - xóa hàng khỏi bảng
        $(document).on('click', '.btn-deActive', function() {
            let btn = $(this);
            let id = btn.data('id');
            let name = btn.data('name');

            Swal.fire({
                title: 'Vô hiệu hóa thiết bị?',
                text: 'Thiết bị: ' + name,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Đồng ý',
                cancelButtonText: 'Hủy'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: "{{ route('pages.category.maintenance.deActive') }}",
                        type: 'POST',
                        dataType: 'json',
                        data: {
                            _token: '{{ csrf_token() }}',
                            id: id
                        },
                        success: function(res) {
                            if (res.success) {
                                var table = $('#data_table_instrument').DataTable();
                                table.row(btn.closest('tr')).remove().draw();
                                Swal.mixin({
                                    toast: true,
                                    position: 'top-end',
                                    showConfirmButton: false,
                                    timer: 2000
                                }).fire({
                                    icon: 'success',
                                    title: 'Đã vô hiệu hóa thành công'
                                });
                            }
                        }
                    });
                }
            });
        });

        $(document).on('focus', '.time', function() {
            $(this).data('old-value', $(this).val());
        });

        $(document).on('blur', '.time', function() {

            let id = $(this).data('id');
            let name = $(this).attr('name');
            let time = $(this).val();
            let oldValue = $(this).data('old-value');

            if (time === oldValue) return;


            $.ajax({
                url: "{{ route('pages.category.maintenance.updateTime') }}",
                type: 'POST',
                dataType: 'json',
                data: {
                    _token: '{{ csrf_token() }}',
                    id: id,
                    name: name,
                    time: time
                },
                success: function(res) {
                    if (res.success) {
                        Swal.mixin({
                            toast: true,
                            position: 'top-end',
                            showConfirmButton: false,
                            timer: 2000
                        }).fire({
                            icon: 'success',
                            title: 'Cập nhật thời gian thành công'
                        });
                    }
                }
            });
        });


        $(document).on('change', '.step-checkbox', function() {

            let id = $(this).data('id');

            let checked = $(this).is(':checked');
            //console.log (id, stage_code, checked)
            $.ajax({
                url: "{{ route('pages.category.maintenance.is_HVAC') }}",
                type: 'POST',
                dataType: 'json',
                data: {
                    _token: '{{ csrf_token() }}',
                    id: id,
                    checked: checked
                },
                success: function(res) {
                    if (res.success) {
                        Swal.mixin({
                            toast: true,
                            position: 'top-end',
                            showConfirmButton: false,
                            timer: 2000
                        }).fire({
                            icon: 'success',
                            title: 'Cập nhật HVAC thành công'
                        });
                    }
                }
            });
        });

        // AJAX cập nhật Phòng Thực Hiện
        $(document).on('change', '.select-room', function() {
            let id = $(this).data('id');
            let room_id = $(this).val();
            if (!room_id) return;

            $.ajax({
                url: "{{ route('pages.category.maintenance.updateRoom') }}",
                type: 'POST',
                dataType: 'json',
                data: {
                    _token: '{{ csrf_token() }}',
                    id: id,
                    room_id: room_id
                },
                success: function(res) {
                    if (res.success) {
                        Swal.mixin({
                            toast: true,
                            position: 'top-end',
                            showConfirmButton: false,
                            timer: 2000
                        }).fire({
                            icon: 'success',
                            title: 'Cập nhật phòng thực hiện thành công'
                        });
                    }
                }
            });
        });

    });
</script>
