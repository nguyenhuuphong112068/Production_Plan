<link href="{{ asset('css/bootstrap.min.css') }}" rel="stylesheet">
<div class="content-wrapper">
    <!-- /.card-header -->
    <div class="card">
        <div class="card-header mt-4">
            {{-- <h3 class="card-title">Ghi Chú Nếu Có</h3> --}}

        </div>
        <!-- /.card-Body -->
        <div class="card-body">
            @if (user_has_permission(session('user')['userId'], 'category_intermediate_create', 'boolean'))
                <button class="btn btn-success btn-create mb-2" data-toggle="modal" data-target="#create_modal"
                    style="width: 155px">
                    <i class="fas fa-plus"></i> Thêm
                </button>
            @endif

            @php
                $auth_update = user_has_permission(session('user')['userId'], 'category_intermediate_update', 'disabled');
                $auth_deActive = user_has_permission(session('user')['userId'], 'category_intermediate_deActive', 'disabled');
            @endphp

            <table id="data_table_intermediate_category" class="table table-bordered table-striped">

                <thead style = "position: sticky; top: 60px; background-color: white; z-index: 1020">

                    <tr>
                        <th rowspan="2">STT</th>
                        <th rowspan="2">Mã BTP</th>
                        <th rowspan="2">Tên Sản Phẩm</th>
                        <th rowspan="2">Cỡ Lô</th>
                        <th rowspan="2">Dạng Bào Chế</th>

                        <!-- Gom nhóm 6 cột -->
                        <th colspan="6" class="text-center">Công Đoạn/Thời gian Biệt Trữ</th>

                        <th rowspan="2">Phân Xưởng</th>
                        <th rowspan="2">Người Tạo/ Ngày Tạo</th>
                        <th rowspan="2">Cập Nhật</th>
                        <th rowspan="2">Vô Hiệu</th>
                        <th rowspan="2">Công Thức</th>
                    </tr>
                    <tr>
                        <th>Cân NL</th>
                        <th>Cân NL Khác</th>
                        <th>PC</th>
                        <th>THT</th>
                        <th>ĐH</th>
                        <th>BP</th>
                    </tr>

                </thead>


                <tbody>

                    @foreach ($datas as $data)
                        @php
                            $data->quarantine_time_unit == 1
                                ? ($quarantine_time_unit = 'ngày')
                                : ($quarantine_time_unit = 'giờ');
                        @endphp

                        <tr>
                            <td>{{ $loop->iteration }} 
                                @if(session('user')['userGroup'] == "Admin") <div> {{ $data->id}} </div> @endif
                            </td>
                            @if ($data->active)
                                <td class="text-success"> {{ $data->intermediate_code }}</td>
                            @else
                                <td class="text-danger"> {{ $data->intermediate_code }}</td>
                            @endif

                            <td>{{ $data->product_name }}</td>
                            <td>
                                <div> {{ $data->batch_size . ' ' . $data->unit_batch_size . '#' }} </div>
                                <div> {{ $data->batch_qty . ' ' . $data->unit_batch_qty }} </div>
                            </td>
                            <td> {{ $data->dosage_name }}</td>

                            <td class="text-center align-middle">
                                <div class="d-flex flex-column align-items-center">
                                    @if ($data->weight_1)
                                        <i class="fas fa-check-circle text-primary fs-4"></i>
                                        <span>
                                            {{ $data->quarantine_weight . ' ' . $quarantine_time_unit }}
                                            
                                        </span>
                                    @endif
                                </div>
                            </td>

                            <td class="text-center align-middle">
                                <div class="d-flex flex-column align-items-center">
                                    @if ($data->weight_2)
                                        <i class="fas fa-check-circle text-primary fs-4"></i>
                                        <span>
                                            @if ($data->quarantine_total == 0)
                                                {{ $data->quarantine_weight . ' ' . $quarantine_time_unit }}
                                            @endif
                                        </span>
                                    @endif
                                </div>
                            </td>

                            <td class="text-center align-middle">
                                <div class="d-flex flex-column align-items-center">
                                    @if ($data->prepering)
                                        <i class="fas fa-check-circle text-primary fs-4"></i>
                                        <span>
                                            @if ($data->quarantine_total == 0)
                                                {{ $data->quarantine_preparing . ' ' . $quarantine_time_unit }}
                                            @endif
                                        </span>
                                    @endif
                                </div>
                            </td>

                            <td class="text-center align-middle">
                                <div class="d-flex flex-column align-items-center">
                                    @if ($data->blending)
                                        <i class="fas fa-check-circle text-primary fs-4"></i>
                                        <span>
                                            @if ($data->quarantine_total == 0)
                                                {{ $data->quarantine_blending . ' ' . $quarantine_time_unit }}
                                            @endif
                                        </span>
                                    @endif

                                </div>
                            </td>

                            <td class="text-center align-middle">
                                <div class="d-flex flex-column align-items-center">
                                    @if ($data->forming)
                                        <i class="fas fa-check-circle text-primary fs-4"></i>
                                        <span>
                                            @if ($data->quarantine_total == 0)
                                                {{ $data->quarantine_forming . ' ' . $quarantine_time_unit }}
                                            @endif
                                        </span>
                                    @endif

                                </div>
                            </td>

                            <td class="text-center align-middle">
                                <div class="d-flex flex-column align-items-center">
                                    @if ($data->coating)
                                        <i class="fas fa-check-circle text-primary fs-4"></i>
                                        <span>
                                            @if ($data->quarantine_total == 0)
                                                {{ $data->quarantine_coating . ' ' . $quarantine_time_unit }}
                                            @else
                                                {{ 'total:' . $data->quarantine_total . ' ' . $quarantine_time_unit }}
                                            @endif
                                        </span>
                                    @endif

                                </div>
                            </td>
                            <td>{{ $data->deparment_code }}</td>
                            <td>
                                <div> {{ $data->prepared_by }} </div>
                                <div>{{ $data->created_at?\Carbon\Carbon::parse($data->created_at)->format('d/m/Y') : '' }}</div>
                            </td>
                            
                            <td class="text-center align-middle">
                                <button type="button" class="btn btn-warning btn-edit" data-id="{{ $data->id }}"
                                    data-intermediate_code="{{ $data->intermediate_code }}"
                                    data-product_name_id="{{ $data->product_name_id }}"
                                    data-batch_size="{{ $data->batch_size }}"
                                    data-unit_batch_size="{{ $data->unit_batch_size }}"
                                    data-batch_qty="{{ $data->batch_qty }}"
                                    data-unit_batch_qty="{{ $data->unit_batch_qty }}"
                                    data-dosage_id="{{ $data->dosage_id }}" data-weight_1="{{ $data->weight_1 }}"
                                    data-prepering="{{ $data->prepering }}" data-blending="{{ $data->blending }}"
                                    data-forming="{{ $data->forming }}" data-coating="{{ $data->coating }}"
                                    data-quarantine_total="{{ $data->quarantine_total }}"
                                    data-quarantine_weight="{{ $data->quarantine_weight }}"
                                    data-quarantine_preparing="{{ $data->quarantine_preparing }}"
                                    data-quarantine_blending="{{ $data->quarantine_blending }}"
                                    data-quarantine_forming="{{ $data->quarantine_forming }}"
                                    data-quarantine_coating="{{ $data->quarantine_coating }}"
                                    data-quarantine_time_unit="{{ $data->quarantine_time_unit }}" 
                                    data-toggle="modal"
                                    data-target="#update_modal"
                                    {{ $auth_update }}>
                                    <i class="fas fa-edit"></i>
                                </button>
                            </td>


                            <td class="text-center align-middle">
                                <form class="form-deActive"
                                    action="{{ route('pages.category.intermediate.deActive') }}" method="post">
                                    @csrf
                                    <input type="hidden" name="id" value = "{{ $data->id }}">
                                    <input type="hidden" name="active" value="{{ $data->active }}">

                                    @if ($data->active)
                                        <button type="submit" class="btn btn-danger" data-type="{{ $data->active }}"
                                            data-name="{{ $data->intermediate_code . ' - ' . $data->product_name }}"
                                            {{ $auth_deActive }}>
                                            <i class="fas fa-lock"></i>
                                        </button>
                                    @else
                                        <button type="submit" class="btn btn-success" data-type="{{ $data->active }}"
                                            data-name="{{ $data->intermediate_code . ' - ' . $data->product_name }}"
                                            {{ $auth_deActive }}>
                                            <i class="fas fa-unlock"></i>
                                        </button>
                                    @endif


                                </form>

                            </td>

                            <td class="text-center align-middle">
                                <button type="button"
                                    class="btn btn-recipe btn-primary "
                                    data-intermediate_code="{{ $data->intermediate_code }}"
                                    data-product_name="{{ $data->product_name}} - {{$data->batch_size}} {{$data->unit_batch_size }}"
                                   
                                    data-toggle="modal"
                                    data-target="#intermediateRecipeModal"
                                    >
                                    <i class="fas fa-list-alt"></i>
                                </button>
                            </td>

                        </tr>
                    @endforeach

                </tbody>
            </table>

        </div>
        <!-- /.card-body -->
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
            modal.find('input[name="intermediate_code"]').val(button.data('intermediate_code'));
            modal.find('select[name="product_name_id"]').val(button.data('product_name_id'));
            modal.find('input[name="batch_size"]').val(button.data('batch_size'));
            modal.find('input[name="batch_qty"]').val(button.data('batch_qty'));
            modal.find('select[name="unit_batch_qty"]').val(button.data('unit_batch_qty'));
            modal.find('input[name="excution_time"]').val(button.data('excution-time'));
            modal.find('select[name="dosage_id"]').val(button.data('dosage_id'));


            modal.find('input[name="weight_1"]').prop('checked', button.data('weight_1'));
            modal.find('input[name="prepering"]').prop('checked', button.data('prepering'));
            modal.find('input[name="blending"]').prop('checked', button.data('blending'));
            modal.find('input[name="forming"]').prop('checked', button.data('forming'));
            modal.find('input[name="coating"]').prop('checked', button.data('coating'));




            modal.find('input[name="quarantine_time_unit"]')
                .prop('checked', button.data('quarantine_time_unit')) // set trạng thái checkbox gốc
                .bootstrapSwitch('state', button.data('quarantine_time_unit'));


            if (button.data('quarantine_total') > 0) {
                modal.find('input[name="quarantine_weight"]').val(0).prop('readonly', true);
                modal.find('input[name="quarantine_preparing"]').val(0).prop('readonly', true);
                modal.find('input[name="quarantine_blending"]').val(0).prop('readonly', true);
                modal.find('input[name="quarantine_forming"]').val(0).prop('readonly', true);
                modal.find('input[name="quarantine_coating"]').val(0).prop('readonly', true);

                modal.find('input[name="quarantine_total"]').val(button.data('quarantine_total')).prop(
                    'readonly', false);
                modal.find('input[name="quarantine_total_checked"]').prop('checked', true)
            } else {

                modal.find('input[name="quarantine_weight"]').val(button.data('quarantine_weight'))
                    .prop('readonly', !button.data('weight_1'));;
                modal.find('input[name="quarantine_preparing"]').val(button.data(
                    'quarantine_preparing')).prop('readonly', !button.data('prepering'));;
                modal.find('input[name="quarantine_blending"]').val(button.data('quarantine_blending'))
                    .prop('readonly', !button.data('blending'));;
                modal.find('input[name="quarantine_forming"]').val(button.data('quarantine_forming'))
                    .prop('readonly', !button.data('forming'));;
                modal.find('input[name="quarantine_coating"]').val(button.data('quarantine_coating'))
                    .prop('readonly', !button.data('coating'));;

                modal.find('input[name="quarantine_total"]').val(0).prop('readonly', true);
                modal.find('input[name="quarantine_total_checked"]').prop('checked', false)
            }

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

        $('#data_table_intermediate_category').DataTable({
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

        $('.btn-recipe').on('click', function() {
                const history_modal = $('#data_table_recipe_body')
                const intermediate_code = $(this).data('intermediate_code');
                const product_name = $(this).data('product_name');

                $('#recipe_intermediate_code').text(`${intermediate_code} - ${product_name}`);

                // Xóa dữ liệu cũ
                history_modal.empty();

                // Gọi Ajax lấy dữ liệu history
                $.ajax({
                    url: "{{ route('pages.category.intermediate.recipe') }}",
                    type: 'post',
                    data: {
                        intermediate_code: intermediate_code,
                        _token: "{{ csrf_token() }}"
                    },
                    success: function(res) {
                        if (res.length === 0) {
                            history_modal.append(
                                `<tr><td colspan="5" class="text-center">Không có công thức</td></tr>`
                            );
                        } else {
                            res.forEach((item, index) => {
                                // map màu level
                       
                                history_modal.append(`
                              <tr>
                                    <td>${index + 1}</td>
                                    <td>${item.MatID ?? ''}</td>
                                    <td>${item.MaterialName ?? ''}</td>
                                    <td style="text-align:center">
                                        ${
                                            item.MatQty != null
                                            ? Number(item.MatQty).toLocaleString(undefined, {
                                                minimumFractionDigits: 0,
                                                maximumFractionDigits: 3
                                            })
                                            : ''
                                        }
                                    </td>

                                    <td style="text-align:center">
                                        ${item.uom ?? ''}
                                    </td>

                                    <td>${Math.round(item.Revno1 ?? 0)}</td>
                              </tr>
                          `);
                            });
                        }
                    },
                    error: function() {
                        history_modal.append(
                            `<tr><td colspan="5" class="text-center text-danger">Lỗi tải dữ liệu</td></tr>`
                        );
                    }
                });
            });


    });


</script>
