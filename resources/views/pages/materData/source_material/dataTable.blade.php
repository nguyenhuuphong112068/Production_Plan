<link href="{{ asset('css/bootstrap.min.css') }}" rel="stylesheet">
<div class="content-wrapper">
    <div class="card">

        <div class="card-header mt-4">
            {{-- <h3 class="card-title">Ghi Chú Nếu Có</h3> --}}
        </div>

        <!-- /.card-Body -->
        <div class="card-body ">
            @if (user_has_permission(session('user')['userId'], 'materData_source_material_create', 'boolean'))
                <button class="btn btn-success btn-create mb-2" data-toggle="modal" data-target="#productNameModal"
                    style="width: 155px">
                    <i class="fas fa-plus"></i> Thêm
                </button>
            @endif

            @php
                $auth_update = user_has_permission(session('user')['userId'], 'materData_source_material_update', 'disabled');
                $auth_deActive = user_has_permission(session('user')['userId'], 'materData_source_material_deActive', 'disabled');
            @endphp

            <table id="data_table" class="table table-bordered table-striped">

                <thead style = "position: sticky; top: 60px; background-color: white; z-index: 1020">

                    <tr>
                        <th style = "width: 15px">STT</th>

                        <th>Mã Bán Thánh Phẩm Liên Quan</th>
                        <th>Nguồn</th>
                        <th>Người Tạo</th>
                        <th>Ngày Tạo</th>
                        <th style = "width: 15px">Edit</th>
                        <th style = "width: 15px"> DeActive</th>
                        <th class="text-center align-middle">Lịch Sử</th>
                    </tr>
                </thead>
                <tbody>

                    @foreach ($datas as $data)
                        <tr>
                            <td>{{ $loop->iteration }} </td>

                            @if ($data->active)
                                <td class="text-success"> {{ $data->intermediate_code }}</td>
                                <td class="text-success">{{ $data->name }}</td>
                            @else
                                <td class="text-danger"> {{ $data->intermediate_code }}</td>
                                <td class="text-danger">{{ $data->name }}</td>
                            @endif

            

                            <td>{{ $data->prepared_by??'' }}</td>
                            <td>{{ $data->created_at?\Carbon\Carbon::parse($data->created_at)->format('d/m/Y') : '' }}</td>

                            <td class="text-center align-middle">
                                <button type="button" class="btn btn-warning btn-edit" 
                                    data-id="{{ $data->id }}" 
                                    data-name="{{ $data->name }}"
                                    data-intermediate_code="{{ $data->intermediate_code }}"
                                   
                                    data-toggle="modal" data-target="#productNameUpdateModal"
                                    {{$auth_update }}
                                    >
                                    <i class="fas fa-edit"></i>
                                </button>
                            </td>

                            {{-- <td class="text-center align-middle">

                                <form class="form-deActive"
                                    action="{{ route('pages.materData.source_material.deActive') }}" method="post">

                                    @csrf
                                    <input type="hidden" name="id" value = "{{ $data->id }}">
                                    <input type="hidden" name="active" value="{{ $data->active }}">

                                    @if ($data->active)
                                        <button type="submit" class="btn btn-danger" data-active="{{ $data->active }}" 
                                            {{$auth_deActive}}
                                            data-name="{{ $data->name }}">
                                            <i class="fas fa-lock"></i>
                                        </button>
                                    @else
                                        <button type="submit" class="btn btn-success" 
                                            {{$auth_deActive}}
                                            data-active="{{ $data->active }}" data-name="{{ $data->name }}">
                                            <i class="fas fa-unlock"></i>
                                        </button>
                                    @endif

                                </form>

                            </td> --}}

                            <td class="text-center align-middle">
                                <button type="button"
                                    class="btn btn-toggle-active {{ $data->active ? 'btn-danger' : 'btn-success' }}"
                                    data-id="{{ $data->id }}" 
                                    data-active="{{ $data->active }}"
                                    {{ $auth_deActive }} >
                                    <i class="fas {{ $data->active ? 'fa-lock' : 'fa-unlock' }}"></i>
                                </button>
                            </td>
                            <td class="text-center align-middle">
                                <button class="btn btn-info btn-history mb-1 position-relative" data-id="{{ $data->id }}" title="Lịch sử thay đổi">
                                    <i class="fas fa-history"></i>
                                    @if(isset($historyCounts) && isset($historyCounts[$data->id]))
                                        <span class="badge badge-danger" style="position: absolute; top: -5px; right: -5px; padding: 4px 6px; border-radius: 50%; font-size: 10px;">{{ $historyCounts[$data->id]->total }}</span>
                                    @endif
                                </button>
                            </td>
                        </tr>
                    @endforeach

                </tbody>
                </div>
            </table>
        
        <!-- /.card-body -->
    </div>
    <!-- /.card -->
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
            timer: 1000, // tự đóng sau 2 giây
            showConfirmButton: false
        });
    </script>
@endif

<script>
    $(document).ready(function() {
        document.body.style.overflowY = "auto";
        $('.btn-edit').click(function() {
            const button = $(this);
            const modal = $('#productNameUpdateModal');
            modal.find('input[name="name"]').val(button.data('name'));
            modal.find('input[name="id"]').val(button.data('id'));
            modal.find('input[name="intermediate_code"]').val(button.data('intermediate_code'));
            
            const id = button.data('id');

        });

        $('.btn-create').click(function() {
            const modal = $('#productNameModal');
        });

        $('#data_table').DataTable({
            paging: true,
            lengthChange: true,
            searching: true,
            ordering: true,
            info: true,
            autoWidth: false,
            pageLength: 10,
            lengthMenu: [
                [10, 25, 50, 100, -1],
                [10, 25, 50, 100, "Tất cả"]
            ],
            language: {
                search: "Tìm kiếm:",
                lengthMenu: "Hiển thị _MENU_ dòng",
                info: "Hiển thị _START_ đến _END_ của _TOTAL_ dòng",
                paginate: {
                    previous: "Trước",
                    next: "Sau"
                }
            }
        });
        
    });

    $(document).on('click', '.btn-toggle-active', function () {

        const btn = $(this);
        const url = "{{ route('pages.materData.source_material.deActive') }}";
        const id = btn.data('id');
        let active = parseInt(btn.data('active'));

        let title = active === 1
            ? 'Bạn chắc chắn muốn vô hiệu hóa nguồn API?'
            : 'Bạn chắc chắn muốn phục hồi nguồn API?';

        Swal.fire({
            title: title,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#28a745',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Đồng ý',
            cancelButtonText: 'Hủy'
        }).then((result) => {

            if (!result.isConfirmed) return;

            btn.prop('disabled', true);

            $.ajax({
                url: url,
                type: 'POST',
                dataType: 'json',
                data: {
                    _token: '{{ csrf_token() }}',
                    id: id,
                    active: active
                },
                success: function (res) {

                    if (!res.success) {
                        Swal.fire('Lỗi', res.message || 'Có lỗi xảy ra', 'error');
                        return;
                    }
                    
                    const newActive = res.active;
                    btn.data('active', !newActive);

                    if (newActive === 0) {
                        btn.removeClass('btn-success').addClass('btn-danger');
                        btn.html('<i class="fas fa-lock"></i>');
                    } else {
                        btn.removeClass('btn-danger').addClass('btn-success');
                        btn.html('<i class="fas fa-unlock"></i>');
                    }

                    Swal.fire('Thành công!', 'Cập nhật thành công', 'success');
                },
                error: function (xhr) {
                    console.error(xhr);
                    Swal.fire('Lỗi server', 'Không thể xử lý yêu cầu', 'error');
                },
                complete: function () {
                    btn.prop('disabled', false);
                }
            });
        });
    });


</script>








<script>
    $(document).ready(function() {
        $('.btn-history').off('click').on('click', function() {
            var id = $(this).data('id');
            $.ajax({
                url: "{{ route('pages.materData.source_material.history') }}",
                type: "GET",
                data: { id: id },
                success: function(res) {
                    var tbody = $('#data_table_history_body');
                    tbody.empty();
                    var current = res.current;
                    if (current) {
                        var modifier = current.created_by || current.prepareBy || current.prepared_by || '';
                        var html = '<tr style="background-color: #e8f4f8; font-weight: bold;">';
                        html += '<td class="text-center align-middle">Hiện Hành</td>';
                        html += '<td class="text-center align-middle">' + modifier + '</td>';
                        html += '<td class="text-center align-middle">' + (current.active !== null && current.active !== undefined ? current.active : '') + '</td>';
                        html += '<td class="text-center align-middle">' + (current.intermediate_code !== null && current.intermediate_code !== undefined ? current.intermediate_code : '') + '</td>';
                        html += '<td class="text-center align-middle">' + (current.name !== null && current.name !== undefined ? current.name : '') + '</td>';
                        html += '</tr>';
                        tbody.append(html);
                    }

                    if(res.history.length === 0) {
                        tbody.append('<tr><td colspan="100%" class="text-center align-middle">Chưa có lịch sử thay đổi</td></tr>');
                    } else {
                        res.history.forEach(function(item) {
                            var modifier = item.created_by || item.prepareBy || item.prepared_by || '';
                            var html = '<tr>';
                            html += '<td class="text-center align-middle">' + (item.updated_at ? item.updated_at : item.created_at) + '</td>';
                            html += '<td class="text-center align-middle">' + modifier + '</td>';
                            html += '<td class="text-center align-middle">' + (item.active !== null && item.active !== undefined ? item.active : '') + '</td>';
                            html += '<td class="text-center align-middle">' + (item.intermediate_code !== null && item.intermediate_code !== undefined ? item.intermediate_code : '') + '</td>';
                            html += '<td class="text-center align-middle">' + (item.name !== null && item.name !== undefined ? item.name : '') + '</td>';
                            html += '</tr>';
                            tbody.append(html);
                        });
                    }
                    $('#historyModal').modal('show');
                },
                error: function() {
                    Swal.fire('Lỗi', 'Không thể lấy lịch sử thay đổi', 'error');
                }
            });
        });
    });
</script>
