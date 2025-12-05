<link href="{{ asset('css/bootstrap.min.css') }}" rel="stylesheet">
<div class="content-wrapper">
    <div class="card">
        <div class="card-header mt-4">
            {{-- <h3 class="card-title">Ghi Chú Nếu Có</h3> --}}
        </div>

        <!-- /.card-Body -->
        <div class="card-body">
            @if (user_has_permission(session('user')['userId'], 'category_product_create', 'boolean'))
                <button class="btn btn-success btn-create mb-2" data-toggle="modal" data-target="#intermediate_category"
                    style="width: 155px">
                    <i class="fas fa-plus"></i> Thêm
                </button>
            @endif
                                    
            @php
                $auth_update = user_has_permission(session('user')['userId'], 'category_product_update','disabled');
                $auth_deActive = user_has_permission(session('user')['userId'], 'category_product_deActive','disabled');
               
            @endphp

            <table id="data_table_product_category" class="table table-bordered table-striped">

                <thead style = "position: sticky; top: 60px; background-color: white; z-index: 1020">

                    <tr>
                        <th>STT</th>
                        <th>Mã sản Phẩm</th>
                        <th>Tên Sản Phẩm</th>
                        <th>Cỡ Lô</th>
                        <th>Thị Trường</th>
                        <th>Qui Cách</th>
                        <th>Đóng gói</th>
                        <th>Phân Xưởng</th>
                        <th>Người Tạo/ Ngày Tạo</th>
                        <th>Cập Nhật</th>
                        <th>Vô Hiệu</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($datas as $data)
                        <tr>
                            <td>{{ $loop->iteration }} 
                                @if(session('user')['userGroup'] == "Admin") <div> {{ $data->id}} </div> @endif
                            </td>
                            @if ($data->active)
                                <td class="text-success">
                                    <div>{{ $data->finished_product_code }} </div>
                                    <div>{{ $data->intermediate_code }} </div>
                                </td>
                            @else
                                <td class="text-danger">
                                    <div>{{ $data->finished_product_code }} </div>
                                    <div>{{ $data->intermediate_code }} </div>
                                </td>
                            @endif
                            <td>{{ $data->product_name }}</td>
                            <td>
                                <div> {{ $data->batch_size . ' ' . $data->unit_batch_size . '#' }} </div>
                                <div> {{ $data->batch_qty . ' ' . $data->unit_batch_qty }} </div>
                            </td>
                            <td> {{ $data->market }}</td>
                            <td> {{ $data->specification }}</td>

                            <td class="text-center align-middle">
                                <div class="d-flex flex-column align-items-center">
                                    @if ($data->primary_parkaging)
                                        <i class="fas fa-check-circle text-primary fs-4"></i>
                                    @endif
                                </div>
                            </td>


                            <td>{{ $data->deparment_code }}</td>
                            <td>
                                <div> {{ $data->prepared_by }} </div>
                                <div>{{ \Carbon\Carbon::parse($data->created_at)->format('d/m/Y') }} </div>
                            </td>

                            <td class="text-center align-middle">
                                <button type="button" class="btn btn-warning btn-edit" data-id="{{ $data->id }}"
                                    data-finished_product_code="{{ $data->finished_product_code }}"
                                    data-intermediate_code="{{ $data->intermediate_code }}"
                                    data-product_name_id="{{ $data->product_name_id }}"
                                    data-market_id="{{ $data->market_id }}"
                                    data-specification_id="{{ $data->specification_id }}"
                                    data-batch_size="{{ $data->batch_size }}" data-batch_qty="{{ $data->batch_qty }}"
                                    data-unit_batch_qty="{{ $data->unit_batch_qty }}"
                                    data-primary_parkaging="{{ $data->primary_parkaging }}" data-toggle="modal"
                                    data-target="#update_modal"
                                    {{ $auth_update }}>
                                    <i class="fas fa-edit"></i>
                                </button>
                            </td>


                            <td class="text-center align-middle">
                                <form class="form-deActive" action="{{ route('pages.category.product.deActive') }}"
                                    method="post">
                                    @csrf
                                    <input type="hidden" name="id" value = "{{ $data->id }}">
                                    <input type="hidden" name="active" value="{{ $data->active }}">
                                    @if ($data->active)
                                        <button type="submit" class="btn btn-danger" {{ $auth_deActive }} data-type="{{ $data->active }}"
                                            data-name="{{ $data->finished_product_code . ' - ' . $data->intermediate_code . ' - ' . $data->product_name }}"
                                            >
                                            <i class="fas fa-lock"></i>
                                        </button>
                                    @else
                                        <button type="submit" class="btn btn-success" {{ $auth_deActive }} data-type="{{ $data->active }}" 
                                            data-name="{{ $data->finished_product_code . ' - ' . $data->intermediate_code . ' - ' . $data->product_name }}"
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
            const modal = $('#update_modal');

            // Gán dữ liệu vào input
            modal.find('input[name="id"]').val(button.data('id'));
            modal.find('input[name="finished_product_code"]').val(button.data('finished_product_code'));
            modal.find('input[name="intermediate_code"]').val(button.data('intermediate_code'));
            modal.find('select[name="product_name_id"]').val(button.data('product_name_id'));
            modal.find('select[name="market_id"]').val(button.data('market_id'));
            modal.find('select[name="specification_id"]').val(button.data('specification_id'));
            modal.find('input[name="batch_size"]').val(button.data('batch_size'));
            modal.find('input[name="batch_qty"]').val(button.data('batch_qty'));
            modal.find('input[name="unit_batch_qty"]').val(button.data('unit_batch_qty'));
            modal.find('input[name="primary_parkaging"]').prop('checked', button.data(
                'primary_parkaging'));


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
                text: `Sản phẩm: ${productName}`,
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



        $('#data_table_product_category').DataTable({
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
            },
            infoCallback: function(settings, start, end, max, total, pre) {
                // Đếm số bản ghi active = 1 và active = 0
                let activeCount = 0;
                let inactiveCount = 0;

                // lấy toàn bộ data trong DataTable
                settings.aoData.forEach(function(row) {
                    // row._aData là dữ liệu thô của từng <tr>
                    // bạn có thể dựa vào class text-success / text-danger hoặc thêm 1 cột hidden active
                    const td = $(row.anCells[1]); // cột thứ 2 là intermediate_code
                    if (td.hasClass('text-success')) {
                        activeCount++;
                    } else if (td.hasClass('text-danger')) {
                        inactiveCount++;
                    }
                });

                return pre + ` (Đang hiệu lực: ${activeCount}, Vô hiệu: ${inactiveCount})`;
            }
        });


    });
</script>
