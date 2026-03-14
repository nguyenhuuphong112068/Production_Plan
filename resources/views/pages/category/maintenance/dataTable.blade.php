<style>
    .step-checkbox {
        width: 20px;
        height: 20px;
        cursor: pointer;
        accent-color: #007bff;
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

    .time:focus {
        border: 1px solid #007bff;
        border-radius: 2px;
        background-color: #fff;
    }

    td input.time {
        display: block;
        margin: auto;
    }
</style>

<div class="content-wrapper">
    <!-- Main content -->
    <div class="card">

        <div class="card-header mt-4"></div>

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
                        <th>Block</th>
                        <th>Mã Thiết Bị Lớn</th>
                        <th>Mã Thiết Bị Con</th>
                        <th>Tên Thiết Bị</th>
                        <th>Tần Suất BT-HC</th>
                        <th>Vị Trí Lắp Đặt</th>
                        <th>Phòng SX Liên Quan</th>
                        <th>Phân Xưởng</th>
                        <th>Thời gian Thực Hiện</th>
                        <th>Có Thuộc Hệ Thống HVAC?</th>
                        <th>Người Tạo/Ngày Tạo</th>
                        <th>Vô Hiệu</th>
                    </tr>
                </thead>
                <tbody></tbody>

            </table>

        </div>
    </div>
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
            timer: 2000,
            showConfirmButton: false
        });
    </script>
@endif

<script>
    $(document).ready(function() {
        document.body.style.overflowY = "auto";

        var authUpdate = '{{ $auth_update }}';
        var disabledAttr = authUpdate ? 'disabled' : '';

         // Danh sách phòng cho select option
        var roomOptions = '<option value="">-- Phòng Thực Hiện --</option>';
        @foreach ($rooms as $room)
            roomOptions +=
                '<option value="{{ $room->id }}">{{ $room->code }} - {{ $room->name }}</option>';
        @endforeach

        // Danh sách phân xưởng
        var departmentOptions = '<option value="">-- Chọn PX --</option>';
        var depts = ['PXV1', 'PXV2', 'PXVH', 'PXDN', 'PXTN'];
        depts.forEach(function(dept) {
            departmentOptions += '<option value="' + dept + '">' + dept + '</option>';
        });

        // Dữ liệu JSON từ server
        var tableData = @json($datas);

        var table = $('#data_table_instrument').DataTable({
            data: tableData,
            deferRender: true,
            paging: true,
            lengthChange: true,
            searching: true,
            ordering: true,
            info: true,
            autoWidth: false,
            pageLength: 25,
            language: {
                search: "Tìm kiếm:",
                lengthMenu: "Hiển thị _MENU_ dòng",
                info: "Hiển thị _START_ đến _END_ của _TOTAL_ dòng",
                paginate: {
                    previous: "Trước",
                    next: "Sau"
                }
            },
            columns: [{
                    data: null,
                    orderable: false,
                    searchable: false,
                    render: function(data, type, row, meta) {
                        return meta.row + 1;
                    }
                },
                {
                    data: 'block',
                    defaultContent: ''
                },
                {
                    data: 'parent_code',
                    defaultContent: ''
                },
                {
                    data: 'code',
                    render: function(data, type, row) {
                        return '<span class="text-success">' + (data || '') + '</span>';
                    }
                },
                {
                    data: 'name',
                    defaultContent: ''
                },
                {
                    data: 'sch_type',
                    defaultContent: ''
                },
                {
                    data: 'room_code',
                    defaultContent: ''
                },
                {
                    data: 'exe_room_name',
                    render: function(data, type, row) {
                        if (data) {
                            return '<span>' + data + '</span>';
                        }
                        var opts = roomOptions.replace(
                            'value="' + (row.room_id || '') + '"',
                            'value="' + (row.room_id || '') + '" selected'
                        );
                        return '<select class="form-control select-room" name="room_id" data-id="' +
                            row.id + '">' + opts + '</select>';
                    }
                },
                {
                    data: 'deparment_code',
                    render: function(data, type, row) {
                        var opts = departmentOptions.replace(
                            'value="' + (data || '') + '"',
                            'value="' + (data || '') + '" selected'
                        );
                        return '<select class="form-control select-department" name="deparment_code" data-id="' +
                            row.id + '">' + opts + '</select>';
                    }
                },
                {
                    data: 'quota',
                    render: function(data, type, row) {
                        return '<input type="text" class="time" name="quota" value="' + (data ||
                            '') + '" data-id="' + row.id + '" ' + disabledAttr + '>';
                    }
                },
                {
                    data: 'is_HVAC',
                    className: 'text-center',
                    render: function(data, type, row) {
                        var checked = (data == 1 || data == '1' || data === true) ? 'checked' :
                            '';
                        return '<div class="form-check form-switch text-center">' +
                            '<input class="form-check-input step-checkbox" type="checkbox" role="switch" data-id="' +
                            row.id + '" ' + checked + '>' +
                            '</div>';
                    }
                },
                {
                    data: null,
                    render: function(data, type, row) {
                        var date = '';
                        if (row.created_at) {
                            try {
                                var d = new Date(row.created_at);
                                date = ('0' + d.getDate()).slice(-2) + '/' + ('0' + (d
                                    .getMonth() + 1)).slice(-2) + '/' + d.getFullYear();
                            } catch (e) {
                                date = row.created_at;
                            }
                        }
                        return '<div>' + (row.created_by || '') + '</div><div>' + date +
                            '</div>';
                    }
                },
                {
                    data: null,
                    className: 'text-center align-middle',
                    orderable: false,
                    searchable: false,
                    render: function(data, type, row) {
                        return '<button type="button" class="btn btn-danger btn-sm btn-deActive" data-id="' +
                            row.id + '" data-name="' + (row.name || '') + '">' +
                            '<i class="fas fa-trash"></i></button>';
                    }
                }
            ],
            infoCallback: function(settings, start, end, max, total, pre) {
                return pre + ' (Tổng: ' + total + ' thiết bị)';
            }
        });

        // AJAX Vô hiệu hóa
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

        // AJAX Cập nhật Thời gian
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

        // AJAX Cập nhật HVAC
        $(document).on('change', '.step-checkbox', function() {
            let id = $(this).data('id');
            let checked = $(this).is(':checked');

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

        // AJAX cập nhật Phân Xưởng
        $(document).on('change', '.select-department', function() {
            let id = $(this).data('id');
            let deparment_code = $(this).val();
            if (!deparment_code) return;

            $.ajax({
                url: "{{ route('pages.category.maintenance.updateDepartment') }}",
                type: 'POST',
                dataType: 'json',
                data: {
                    _token: '{{ csrf_token() }}',
                    id: id,
                    deparment_code: deparment_code
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
                            title: 'Cập nhật phân xưởng thành công'
                        });
                    }
                }
            });
        });

    });
</script>
