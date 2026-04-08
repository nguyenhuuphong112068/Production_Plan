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

    /* Select2 customization */
    .select2-container--default .select2-selection--multiple {
        border: 1px solid #ced4da;
        border-radius: .25rem;
        min-height: 38px;
    }

    .select2-container {
        width: 100% !important;
    }

    /* Premium Select2 Styling for AdminLTE */
    .select2-container--bootstrap4 .select2-selection--multiple {
        border: 1px solid #ced4da !important;
        border-radius: 4px !important;
        padding: 1px 5px !important;
        background-color: #fdfdfd !important;
        height: auto !important;
        min-height: 38px !important;
        display: flex !important;
        flex-wrap: wrap !important;
        align-items: center !important;
    }

    .select2-container--bootstrap4.select2-container--focus .select2-selection--multiple {
        border-color: #80bdff !important;
        box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, .25) !important;
    }

    .select2-container--bootstrap4 .select2-selection--multiple .select2-selection__choice {
        background-color: #e9ecef !important;
        border: 1px solid #dee2e6 !important;
        color: #495057 !important;
        border-radius: 3px !important;
        padding: 0 8px !important;
        margin-top: 2px !important;
        margin-bottom: 2px !important;
        font-size: 0.85rem !important;
        font-weight: 500 !important;
        transition: all 0.2s ease;
    }

    .select2-container--bootstrap4 .select2-selection--multiple .select2-selection__choice:hover {
        background-color: #dee2e6 !important;
        color: #212529 !important;
    }

    .select2-container--bootstrap4 .select2-selection--multiple .select2-selection__choice__remove {
        color: #dc3545 !important;
        margin-right: 5px !important;
        font-weight: bold !important;
    }

    .select2-container--bootstrap4 .select2-selection--multiple .select2-selection__choice__remove:hover {
        color: #bd2130 !important;
    }

    .select2-container .select2-search--inline .select2-search__field {
        margin-top: 0 !important;
        height: 26px !important;
    }

    /* Constrain Room selection column width */
    .room-select-cell {
        min-width: 200px !important;
        max-width: 450px !important;
        width: 350px !important;
    }

    .select-room-wrapper .select2-container {
        width: 100% !important;
        max-width: 100% !important;
    }

    .select2-selection--multiple {
        max-height: 150px;
        overflow-y: auto !important;
    }
</style>
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link rel="stylesheet"
    href="https://cdn.jsdelivr.net/npm/@ttskch/select2-bootstrap4-theme@1.5.2/dist/select2-bootstrap4.min.css">

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

            @endphp

            <table id="data_table_instrument" class="table table-bordered table-striped">

                <thead style = "position: sticky; top: 60px; background-color: white; z-index: 1020">
                    <tr>
                        <th>STT</th>
                        <th>Mã Thiết Bị Lớn</th>
                        <th style="width: 15%;">Tên Thiết Bị Lớn</th>
                        <th>Mã Thiết Bị Con</th>
                        <th style="width: 15%;">Tên Thiết Bị Con</th>
                        <th>Tần Suất BT-HC</th>
                        <th>Vị Trí Lắp Đặt</th>
                        <th style="width: 10%;">Phân Xưởng</th>
                        <th class="room-select-cell">Phòng SX Liên Quan</th>
                        <th style="display: none;">Room Search Hidden</th>
                        <th>Thời gian Thực Hiện</th>
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
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

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

        // Danh sách phòng cho select option - Cache as array for performance
        var roomsData = @json(
            $rooms->map(function ($r) {
                return ['id' => $r->id, 'text' => $r->code . ' - ' . $r->name];
            }));

        // Danh sách phân xưởng theo Block
        var deptOptionsMap = {
            'B1': ['PXV1', 'PXTN'],
            'B2': ['PXV2', 'PXVH', 'PXDN']
        };

        function getDepartmentOptions(block, selectedValue) {
            var options = '<option value="">-- Chọn PX --</option>';
            // Hỗ trợ cả block cũ (B1, B2) và block mới (HC-B1, BT-B1...)
            var blockSuffix = block.includes('-') ? block.split('-')[1] : block;
            var depts = deptOptionsMap[blockSuffix] || ['PXV1', 'PXV2', 'PXVH', 'PXDN', 'PXTN'];

            depts.forEach(function(dept) {
                var selected = (dept === selectedValue) ? ' selected' : '';
                options += '<option value="' + dept + '"' + selected + '>' + dept + '</option>';
            });
            return options;
        }

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
            lengthMenu: [
                [10, 25, 50, 100, 500],
                [10, 25, 50, 100, 500]
            ],
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
                    data: 'parent_code',
                    defaultContent: ''
                },
                {
                    data: 'Eqp_name',
                    defaultContent: ''
                },
                {
                    data: 'code',
                    render: function(data, type, row) {
                        return '<span class="text-success">' + (data || '') + '</span>';
                    }
                },
                {
                    data: 'Inst_Name',
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
                    data: 'deparment_code',
                    render: function(data, type, row) {
                        if (type === 'display') {
                            var opts = getDepartmentOptions(row.block, data);
                            return '<select ' + disabledAttr +
                                ' class="form-control select-department" name="deparment_code" data-id="' +
                                row.id + '">' + opts + '</select>';
                        }
                        return data || '';
                    }
                },
                {
                    data: 'room_ids',
                    className: 'room-select-cell',
                    searchable: false,
                    render: function(data, type, row) {
                        var rawIds = data;
                        if (typeof rawIds === 'string' && rawIds.startsWith('[')) {
                            try {
                                rawIds = JSON.parse(rawIds);
                            } catch (e) {
                                rawIds = [];
                            }
                        }
                        var selectedIds = (Array.isArray(rawIds) ? rawIds : (rawIds ? [rawIds] :
                            [])).map(id => parseInt(id));

                        var options = roomsData.map(function(room) {
                            var isSelected = selectedIds.indexOf(parseInt(room.id)) !==
                                -1 ?
                                'selected' : '';
                            return '<option value="' + room.id + '" ' + isSelected +
                                '>' + room.text + '</option>';
                        }).join('');

                        return '<div class="select-room-wrapper"><select ' + disabledAttr +
                            ' class="form-control select-room" multiple="multiple" data-id="' +
                            row.id + '">' + options + '</select></div>';
                    }
                },
                {
                    data: null, // Sử dụng null để không bị trùng lặp với data của cột khác
                    visible: false,
                    searchable: true,
                    render: function(data, type, row) {
                        var rawIds = row.room_ids; // Lấy room_ids từ object row
                        if (typeof rawIds === 'string' && rawIds.startsWith('[')) {
                            try {
                                rawIds = JSON.parse(rawIds);
                            } catch (e) {
                                rawIds = [];
                            }
                        }
                        var selectedIds = (Array.isArray(rawIds) ? rawIds : (rawIds ? [rawIds] :
                            [])).map(id => parseInt(id));

                        return roomsData.filter(function(room) {
                            return selectedIds.indexOf(parseInt(room.id)) !== -1;
                        }).map(function(room) {
                            return room.text;
                        }).join(' ');
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
                        return '<button ' + disabledAttr +
                            ' type="button" class="btn btn-danger btn-sm btn-deActive" data-id="' +
                            row.id + '" data-name="' + (row.Inst_Name || '') + '">' +
                            '<i class="fas fa-trash"></i></button>';
                    }
                }
            ],
            infoCallback: function(settings, start, end, max, total, pre) {
                return pre + ' (Tổng: ' + total + ' thiết bị)';
            },
            drawCallback: function() {
                var selects = $('.select-room:not(.select2-hidden-accessible)');
                if (selects.length === 0) return;

                // Nếu số lượng ít, init luôn
                if (selects.length <= 50) {
                    selects.select2({
                        placeholder: "-- Chọn phòng --",
                        allowClear: true,
                        theme: 'bootstrap4'
                    });
                } else {
                    // Nếu quá nhiều (vd: 500 dòng), init từng đợt để tránh treo UI
                    var index = 0;
                    var chunkSize = 30;

                    function initNextChunk() {
                        var chunk = selects.slice(index, index + chunkSize);
                        if (chunk.length > 0) {
                            chunk.select2({
                                placeholder: "-- Chọn phòng --",
                                allowClear: true,
                                theme: 'bootstrap4'
                            });
                            index += chunkSize;
                            setTimeout(initNextChunk, 10); // Nghỉ 10ms để browser kịp vẽ
                        }
                    }
                    initNextChunk();
                }
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
                                table.row(btn.closest('tr')).remove().draw(false);
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

            if (name != "note") {
                const pattern = /^\d{1,2}:[0-5]\d$/;
                if (time && !pattern.test(time)) {
                    Swal.fire({
                        title: 'Lỗi định dạng!',
                        text: 'Thời gian phải có dạng hh:mm (ví dụ 01:30, giờ từ 0-99, phút từ 0-59)',
                        icon: 'error',
                        timer: 2000,
                        showConfirmButton: false
                    });
                    $(this).focus();
                    $(this).css('border', '1px solid red');
                    return;
                } else {
                    $(this).css('border', '');
                }
            }

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

        $(document).on('change', '.select-room', function() {
            let select = $(this);
            let id = select.data('id');
            let room_ids = select.val(); // Đây sẽ là mảng các ID được chọn

            $.ajax({
                url: "{{ route('pages.category.maintenance.updateRoom') }}",
                type: 'POST',
                dataType: 'json',
                data: {
                    _token: '{{ csrf_token() }}',
                    id: id,
                    room_ids: room_ids
                },
                success: function(res) {
                    if (res.success) {
                        // Cập nhật dữ liệu trong DataTable để search được ngay lập tức
                        var row = table.row(select.closest('tr'));
                        var rowData = row.data();
                        rowData.room_ids = room_ids;
                        row.data(rowData).invalidate().draw(false);

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

        $(document).on('change', '.select-department', function() {
            let select = $(this);
            let id = select.data('id');
            let department_code = select.val();
            if (!department_code) return;

            $.ajax({
                url: "{{ route('pages.category.maintenance.updateDepartment') }}",
                type: 'POST',
                dataType: 'json',
                data: {
                    _token: '{{ csrf_token() }}',
                    id: id,
                    deparment_code: department_code
                },
                success: function(res) {
                    if (res.success) {
                        // Cập nhật dữ liệu trong DataTable để search được ngay
                        var row = table.row(select.closest('tr'));
                        var rowData = row.data();
                        rowData.deparment_code = department_code;
                        row.data(rowData).invalidate().draw(false);

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
