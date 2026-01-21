<link href="{{ asset('css/bootstrap.min.css') }}" rel="stylesheet">
<div class="content-wrapper">
    <!-- Main content -->
    <div class="card">

        <div class="card-header mt-4">
            {{-- <h3 class="card-title">Ghi Chú Nếu Có</h3> --}}

        </div>

        <!-- /.card-Body -->
        <div class="card-body">
             @if (user_has_permission(session('user')['userId'], 'materData_room_store', 'boolean'))
            <button class="btn btn-success btn-create mb-2" data-toggle="modal" data-target="#createModal"
                style="width: 155px">
                <i class="fas fa-plus"></i> Thêm
            </button>
            @endif
            
            @php
                $auth_update = user_has_permission(session('user')['userId'], 'materData_room_update', 'disabled');
                $auth_deActive = user_has_permission(session('user')['userId'], 'materData_room_deActive', 'disabled');
            @endphp
            

            <table id="data_tabale_room" class="table table-bordered table-striped">

                <thead style = "position: sticky; top: 60px; background-color: white; z-index: 1020">

                    <tr>
                        <th>STT</th>
                        <th>Mã Phòng</th>
                        <th>Tên Phòng</th>
                        <th>Thiết Bị Chính</th>
                        <th>Công Suất (ĐVL/Giờ)</th>
                        <th>Công Đoạn</th>
                        <th>Tổ Quản Lý</th>
                        <th>Phân Xưởng</th>
                        <th>Người Tạo</th>
                        <th>Ngày Tạo</th>
                        <th>Edit</th>
                        <th>DeActive</th>
                    </tr>
                </thead>
                <tbody>

                    @foreach ($datas as $data)
                        <tr>
                            <td>{{ $loop->iteration }} </td>
                            @if ($data->active)
                                <td class="text-success"> {{ $data->code }}</td>
                            @else
                                <td class="text-danger"> {{ $data->code }}</td>
                            @endif
                            <td>{{ $data->name }}</td>
                            <td>{{ $data->main_equiment_name }}</td>
                            <td>{{ $data->capacity}}</td>
                            <td>{{ $data->stage }}</td>
                            <td>{{ $data->production_group }}</td>
                            <td>{{ $data->deparment_code }}</td>

                            <td>{{ $data->prepareBy }}</td>
                            <td>{{ \Carbon\Carbon::parse($data->created_at)->format('d/m/Y') }}</td>

                            <td class="text-center align-middle">
                                <button type="button" class="btn btn-warning btn-edit" data-id="{{ $data->id }}"
                                    data-code="{{ $data->code }}" 
                                    data-name="{{ $data->name }}"
                                    data-stage_code="{{ $data->stage_code }}"
                                    data-production_group="{{ $data->production_group }}" 
                                    data-capacity="{{ $data->capacity }}"
                                    data-main_equiment_name="{{ $data->main_equiment_name }}"

                                    data-toggle="modal"
                                    data-target="#updateModal"
                                    {{$auth_update}}
                                    >
                                    <i class="fas fa-edit"></i>
                                </button>
                            </td>


                            <td class="text-center align-middle">

                                <form class="form-deActive" action="{{ route('pages.materData.room.deActive') }}"
                                    method="post">
                                    @csrf
                                    <input type="hidden" name="id" value = "{{ $data->id }}">
                                    <input type="hidden" name="active" value="{{ $data->active }}">

                                    @if ($data->active)
                                        <button type="submit" class="btn btn-danger" data-active="{{ $data->active }}"
                                            data-name="{{ $data->code . ' - ' . $data->name }}"
                                            {{ $auth_deActive }}
                                            >
                                            <i class="fas fa-lock"></i>
                                        </button>
                                    @else
                                        <button type="submit" class="btn btn-success"
                                            data-active="{{ $data->active }}"
                                            data-name="{{ $data->code . ' - ' . $data->name }}"
                                            {{ $auth_deActive }}
                                            >
                                            <i class="fas fa-unlock"></i>
                                        </button>
                                    @endif
                                </form>
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
            const modal = $('#updateModal');

            // Gán dữ liệu vào input
            modal.find('input[name="id"]').val(button.data('id'));
            modal.find('input[name="code"]').val(button.data('code'));
            modal.find('input[name="name"]').val(button.data('name'));
            modal.find('select[name="stage_code"]').val(button.data('stage_code'));
            modal.find('select[name="production_group"]').val(button.data('production_group'));
            modal.find('input[name="main_equiment_name"]').val(button.data('main_equiment_name'));
            modal.find('input[name="capacity"]').val(button.data('capacity'));


        });

        $('.btn-create').click(function() {
            const modal = $('#Modal');
        });

        $('.form-deActive').on('submit', function(e) {
            e.preventDefault(); // chặn submit mặc định
            const form = this;
            const productName = $(form).find('button[type="submit"]').data('name');

            const active = $(form).find('button[type="submit"]').data('active');
            let title = 'Bạn chắc chắn muốn vô hiệu hóa danh mục?'
            if (!active) {
                title = 'Bạn chắc chắn muốn phục hồi phòng sản xuất?'
            }

            Swal.fire({
                title: title,
                text: ` ${productName}`,
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


        $('#data_tabale_room').DataTable({
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
</script>
