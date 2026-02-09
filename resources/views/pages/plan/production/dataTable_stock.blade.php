<link href="{{ asset('css/bootstrap.min.css') }}" rel="stylesheet">

<style>
    .step-checkbox {
    width: 20px;
    height: 20px;
    cursor: pointer;
    accent-color: #007bff; /* màu xanh bootstrap */
    }

    .step-checkbox2 {
    width: 20px;
    height: 20px;
    cursor: pointer;
    accent-color: #007bff; /* màu xanh bootstrap */
    }

    .step-checkbox:checked {
        box-shadow: 0 0 5px #007bff;
    }
    .updateInput {
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
    .updateInput:focus {
        border: 1px solid #007bff;
        border-radius: 2px;
        background-color: #fff;
    }

  /* Tùy chọn: nếu bạn muốn chữ canh giữa theo chiều dọc */
    td input.updateInput {
        display: block;
        margin: auto;
    }
</style>

<div class="content-wrapper">
    <div class="card" style="min-height: 100vh">

        <div class="card-header mt-4" >
            {{-- <h3 class="card-title">Ghi Chú Nếu Có</h3> --}}
            
        </div>
        @php
            $auth_update = user_has_permission(session('user')['userId'], 'plan_production_update', 'disabled');
            $auth_deActive = user_has_permission(session('user')['userId'], 'plan_production_deActive', 'disabled');

                        $material_status = [
                                0 => "Biệt Trữ",
                                1 => "Approver Bởi Thủ Kho",
                                2 => "Đã Lấy Mẫu Gọp",
                                3 => "3 ??",
                                4 => "Chờ Lấy Mẫu ĐT",
                                5 => "Đã Lấy Mẫu ĐT",
                                6 => "6 ??",
                                7 => "Chờ Tái Kiểm",
                        ];

                        function lable_status(int $GRNSts, ?string $ARNO): array{
                                // Chờ tái kiểm
                                if (!empty($ARNO) && $GRNSts == 7) {
                                    return [
                                        'text'  => 'Chờ Tái Kiểm',
                                        'color' => '#dc2626', // đỏ đậm
                                    ];
                                }

                                // Chấp nhận
                                if (!empty($ARNO) && $GRNSts >= 2 && $GRNSts <= 5) {
                                    return [
                                        'text'  => 'Chấp Nhận',
                                        'color' => '#166534', // xanh lá đậm
                                    ];
                                }

                                // Đã lấy mẫu
                                if (empty($ARNO) && $GRNSts >= 2 && $GRNSts <= 5) {
                                    return [
                                        'text'  => 'Đã Lấy Mẫu',
                                        'color' => '#ca8a04', // vàng đậm
                                    ];
                                }

                                // Biệt trữ
                                return [
                                    'text'  => 'Biệt Trữ',
                                    'color' =>  '#facc15', // vàng nhạt
                                ];
                        }
                @endphp

        <!-- /.card-Body -->
        <div class="card-body">
            <div class = >
            <input id="globalSearch"
                class="form-control mb-2"
                placeholder="🔍 Tìm theo mã NL / tên / lô / SP"
            >
            </div>

            @if (!$send)
                <div class="row">
                    <div class="col-md-2">
                        @if (user_has_permission(session('user')['userId'], 'plan_production_create', 'boolean'))
                            <button class="btn btn-success btn-add mb-2" data-toggle="modal"
                                data-target="#selectProductModal" style="width: 155px;">
                                <i class="fas fa-plus"></i> Thêm
                            </button>
                        @endif
                    </div>

                    <div class="col-md-8"></div>
                    <div class="col-md-2" style="text-align: right;">

                        <form id = "send_form" action="{{ route('pages.plan.production.send') }}" method="post">

                            @csrf
                            <input type="hidden" name="plan_list_id" value="{{ $plan_list_id }}">
                            <input type="hidden" name="month" value="{{ $month }}">
                            <input type="hidden" name="production" value="{{ $production }}">
                            @if (user_has_permission(session('user')['userId'], 'plan_production_send', 'boolean'))
                            <button class="btn btn-success btn-send mb-2 " style="width: 177px;">
                                <i id = "send_btn" class="fas fa-paper-plane"></i> Gửi
                            </button>
                            @endif
                        </form>

                    </div>
                </div>
            @endif

            <table id="data_table_raw_material"
                class="table table-bordered table-striped"
                style="font-size:16px; width:100%">

                <thead style="position: sticky; top: 60px; background-color: white; z-index: 1020">
                    <tr>
                        <th rowspan="2" class="text-center">STT</th>
                        <th rowspan="2" class="text-center">Mã Nguyên Liệu</th>
                        <th rowspan="2" class="text-center">Tên Nguyên Liệu</th>
                        <th rowspan="2" class="text-center">Mã Sản Phẩm</th>
                        <th rowspan="2" class="text-center">Tên Sản Phẩm</th>
                        <th rowspan="2" class="text-center">Khối Lượng Công Thức</th>
                        <th rowspan="2" class="text-center">Số Lượng Lô</th>
                        <th rowspan="2" class="text-center">Khối Lượng Cần Dùng</th>
                        <th colspan="7" class="text-center">Tồn Kho</th>
                    </tr>
                    <tr>
                        <th>Số Lô NSX</th>
                        <th>Số Lô NB</th>
                        <th>Hạn Dùng / Retest</th>
                        <th>Nhà SX</th>
                        <th>Nhập</th>
                        <th>Tồn</th>
                        <th>Tình Trạng Nhãn / PKN</th>
                    </tr>
                </thead>

                <tbody>
                    @foreach ($datas as $data)
                        @php
                            $stocks  = $data->stock ?? collect();
                            $rowspan = max($stocks->count(), 1);
                        @endphp

                        {{-- DÒNG ĐẦU TIÊN --}}
                        <tr>
                            <td rowspan="{{ $rowspan }}">{{ $loop->iteration }}</td>
                            <td rowspan="{{ $rowspan }}">{{ $data->MatID }}</td>
                            <td rowspan="{{ $rowspan }}">{{ $data->MaterialName }}</td>
                            <td rowspan="{{ $rowspan }}">{{ $data->PrdID }}</td>
                            <td rowspan="{{ $rowspan }}">{{ $data->MatNM }}</td>
                            <td rowspan="{{ $rowspan }}">{{ round($data->MatQty,5) }} {{ $data->uom }}</td>
                            <td rowspan="{{ $rowspan }}">{{ $data->NumberOfBatch }}</td>
                            <td rowspan="{{ $rowspan }}">{{ round($data->TotalMatQty,5) }} {{ $data->uom }}</td>

                            @if ($stocks->count())
                                @php $stock = $stocks->first(); @endphp
                                @include('pages.plan.production.stock_row', ['stock' => $stock])
                            @else
                                <td colspan="7" class="text-center text-danger fw-bold">
                                    Không có tồn kho
                                </td>
                            @endif
                        </tr>

                        {{-- CÁC DÒNG STOCK TIẾP THEO --}}
                        @foreach ($stocks->skip(1) as $stock)
                            <tr>
                                @include('pages.plan.production.stock_row', ['stock' => $stock])
                            </tr>
                        @endforeach
                    @endforeach
                </tbody>
            </table>

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
                timer: 1000, // tự đóng sau 2 giây
                showConfirmButton: false
            });
        </script>
    @endif

    <script>

        $(document).ready(function() {
            document.body.style.overflowY = "auto";
            $('#globalSearch').on('keyup', function () {
                $('#data_table_raw_material').DataTable().search(this.value).draw();
            });

        });

        
    </script>
