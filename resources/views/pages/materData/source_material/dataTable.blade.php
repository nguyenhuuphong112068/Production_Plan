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
