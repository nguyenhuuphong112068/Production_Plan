<link href="{{ asset('css/bootstrap.min.css') }}" rel="stylesheet">
<div class="content-wrapper">
    <div class="card">

        <div class="card-header mt-4">
            {{-- <h3 class="card-title">Ghi Chú Nếu Có</h3> --}}
        </div>

        <!-- /.card-Body -->
        <div class="card-body ">
            @if (user_has_permission(session('user')['userId'], 'materData_productName_store', 'boolean'))
                <button class="btn btn-success btn-create mb-2" data-toggle="modal" data-target="#productNameModal"
                    style="width: 155px">
                    <i class="fas fa-plus"></i> Thêm
                </button>
            @endif

            @php
                $auth_update = user_has_permission(session('user')['userId'], 'materData_productName_update', 'disabled');
                $auth_deActive = user_has_permission(session('user')['userId'], 'materData_productName_deActive', 'disabled');
            @endphp

            <table id="data_table_Product_Name" class="table table-bordered table-striped">

                <thead style = "position: sticky; top: 60px; background-color: white; z-index: 1020">

                    <tr>
                        <th style = "width: 15px">STT</th>

                        <th>Tên Sản Phẩm</th>
                        <th>Tên Viết Tắt</th>
                        <th>Loại Sản Phẩm</th>
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
                                <td class="text-success"> {{ $data->name }}</td>
                            @else
                                <td class="text-danger"> {{ $data->name }}</td>
                            @endif

                            <td>{{ $data->shortName }}</td>
                            <td>{{ $data->productType }}</td>
                            <td>{{ $data->prepareBy }}</td>
                            <td>{{ \Carbon\Carbon::parse($data->created_at)->format('d/m/Y') }}</td>

                            <td class="text-center align-middle">
                                <button type="button" class="btn btn-warning btn-edit" data-id="{{ $data->id }}" 
                                    {{-- data-code="{{ $data->code }}" --}} data-name="{{ $data->name }}"
                                    data-shortname="{{ $data->shortName }}" data-producttype="{{ $data->productType }}"
                                    data-toggle="modal" data-target="#productNameUpdateModal"
                                    {{$auth_update }}
                                    >
                                    <i class="fas fa-edit"></i>
                                </button>
                            </td>


                            <td class="text-center align-middle">

                                <form class="form-deActive"
                                    action="{{ route('pages.materData.productName.deActive') }}" method="post">

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

            console.log(button.data('code'))

            // Gán dữ liệu vào input
            // modal.find('input[name="code"]').val(button.data('code'));
            modal.find('input[name="name"]').val(button.data('name'));
            modal.find('input[name="shortName"]').val(button.data('shortname'));
            modal.find('input[name="productType"]').val(button.data('producttype'));
            modal.find('input[name="id"]').val(button.data('id'));
            const id = button.data('id');

        });

        $('.btn-create').click(function() {
            const modal = $('#productNameModal');
        });

        $('.form-deActive').on('submit', function(e) {
            e.preventDefault(); // chặn submit mặc định
            const form = this;
            const productName = $(form).find('button[type="submit"]').data('name');
            const active = $(form).find('button[type="submit"]').data('active');
            let title = 'Bạn chắc chắn muốn vô hiệu hóa danh mục?'
            if (!active) {
                title = 'Bạn chắc chắn muốn phục hồi tên sản phẩm?'
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

        $('#data_table_Product_Name').DataTable({
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
