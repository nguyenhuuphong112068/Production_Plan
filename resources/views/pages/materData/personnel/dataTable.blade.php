<link href="{{ asset('css/bootstrap.min.css') }}" rel="stylesheet">
<div class="content-wrapper">
    <div class="card">
        <div class="card-header mt-4">
            <h3 class="card-title">Danh sách nhân sự</h3>
        </div>
        <div class="card-body">

            {{-- @if (user_has_permission(session('user')['userId'], 'materData_personnel_store', 'boolean')) --}}
            <button class="btn btn-success btn-create mb-2" data-toggle="modal" data-target="#create_modal"
                style="width: 155px">
                <i class="fas fa-plus"></i> Thêm nhân sự
            </button>
            {{-- @endif --}}

            <table id="data_table_personnel" class="table table-bordered table-striped">
                <thead style="position: sticky; top: 60px; background-color: white; z-index: 1020">
                    <tr>
                        <th>STT</th>
                        <th>Mã NV</th>
                        <th>Tên Nhân Viên</th>
                        <th>Phòng Ban</th>
                        <th>Tổ</th>
                        <th>Trạng Thái</th>
                        <th>Thao Tác</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($datas as $data)
                        <tr>
                            <td>{{ $loop->iteration }} </td>
                            <td>{{ $data->code }}</td>
                            <td>{{ $data->name }}</td>
                            <td>{{ $data->deparment_code }}</td>
                            <td>{{ $data->group_name }} ({{ $data->group_code }})</td>
                            <td class="text-center">
                                @if($data->active)
                                    <span class="badge badge-success">Đang làm việc</span>
                                @else
                                    <span class="badge badge-danger">Nghỉ việc</span>
                                @endif
                            </td>
                            <td class="text-center align-middle">
                                <button type="button" class="btn btn-warning btn-edit" 
                                    data-id="{{ $data->id }}" 
                                    data-code="{{ $data->code }}"
                                    data-name="{{ $data->name }}"
                                    data-deparment_code="{{ $data->deparment_code }}"
                                    data-group_name="{{ $data->group_name }}"
                                    data-group_code="{{ $data->group_code }}"
                                    data-toggle="modal" data-target="#update_modal">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <a href="{{ route('pages.assignment.personnel.deActive', $data->id) }}" 
                                   class="btn btn-{{ $data->active ? 'danger' : 'success' }} btn-sm" 
                                   title="{{ $data->active ? 'Vô hiệu hóa' : 'Kích hoạt' }}">
                                    <i class="fas fa-{{ $data->active ? 'user-slash' : 'user-check' }}"></i>
                                </a>
                            </td>
                        </tr>
                    @endforeach

                </tbody>
            </table>
        </div>
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
            timer: 1500,
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

            modal.find('input[name="id"]').val(button.data('id'));
            modal.find('input[name="code"]').val(button.data('code'));
            modal.find('input[name="name"]').val(button.data('name'));
            modal.find('input[name="deparment_code"]').val(button.data('deparment_code'));
            modal.find('input[name="group_name"]').val(button.data('group_name'));
            modal.find('input[name="group_code"]').val(button.data('group_code'));
        });

        $('#data_table_personnel').DataTable({
            paging: true,
            lengthChange: true,
            searching: true,
            ordering: true,
            info: true,
            autoWidth: false,
            pageLength: 25,
            lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "Tất cả"]],
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
</script>
