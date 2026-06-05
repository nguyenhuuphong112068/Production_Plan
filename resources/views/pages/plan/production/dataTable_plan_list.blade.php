<div class="content-wrapper">
    <div class="p-3">
        <!-- Bảng Kế hoạch gom theo tháng -->


        <!-- Bảng Kế hoạch gốc -->
        <div class="card card-success mt-5">
            <div class="card-header">
                <h3 class="card-title">Danh Sách Kế Hoạch Chi Tiết</h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                        <i class="fas fa-minus"></i>
                    </button>
                </div>
            </div>
            <div class="card-body" style="height: 95vh; overflow-y: auto;">
                @php
                    $auth_view_material = user_has_permission(
                        session('user')['userId'],
                        'plan_production_view_material',
                        'disabled',
                    );
                @endphp
                @if (user_has_permission(session('user')['userId'], 'plan_production_create_plan_list', 'boolean'))
                    <button class="btn btn-success btn-create mb-2" data-toggle="modal"
                        data-target="#create_plan_list_modal" style="width: 155px">
                        <i class="fas fa-plus"></i> Thêm
                    </button>
                @endif
                <table id="example1" class="table table-bordered table-striped" style="font-size: 20px">
                    <thead>
                        <tr>
                            <th rowspan="2">STT</th>
                            <th rowspan="2">Kế Hoạch</th>
                            <th rowspan="2">Phân Xưởng</th>
                            <th rowspan="2">Người Tạo</th>
                            <th rowspan="2">Ngày Tạo</th> <!-- ✅ SỬA -->
                            <th rowspan="2">Tình Trạng</th>
                            <th rowspan="2">Sản Lượng Lý Thuyết (ĐVL)</th>

                            <th colspan="9" style="text-align:center;">
                                Tình Trạng Sản Xuất
                            </th>

                            <th rowspan="2">Người Gửi</th>
                            <th rowspan="2">Ngày Gửi</th>
                            <th rowspan="2">Chi Tiết</th>
                            {{-- <th rowspan="2">Tạm tính NL/BB</th> --}}
                        </tr>

                        <tr>
                            <th>Tổng Lô</th>
                            <th>Chưa Làm</th>
                            <th>Đã Cân</th>
                            <th>Đã PC</th>
                            <th>Đã THT</th>
                            <th>Đã ĐH</th>
                            <th>Đã BP</th>
                            <th>Đã ĐG</th>
                            <th>Hủy Kế Hoạch</th>
                        </tr>
                    </thead>

                    <tbody>

                        @foreach ($datas as $data)
                            <tr>
                                <td>{{ $loop->iteration }}

                                </td>
                                {{-- <td>{{ $data->code}}</td> --}}
                                <td>{{ $data->name }}</td>
                                <td>{{ $data->deparment_code }}
                                    @if (session('user')['userGroup'] == 'Admin')
                                        <div> {{ $data->id }} </div>
                                    @endif
                                </td>
                                <td>{{ $data->prepared_by ?? 'NA' }}</td>
                                <td>{{ $data->created_at ? \Carbon\Carbon::parse($data->created_at ?? now())->format('d/m/Y H:i') : '' }}
                                </td>

                                @php
                                    $colors = [
                                        0 => 'background-color: #ffeb3b; color: white;', // vàng
                                        1 => 'background-color: #4caf50; color: white;', // xanh lá
                                    ];
                                    $status = [
                                        0 => 'Pending', // vàng
                                        1 => 'Send', // xanh lá
                                    ];
                                @endphp

                                <td style="text-align: center; vertical-align: middle;">
                                    <span
                                        style="padding: 6px 15px; border-radius: 20px; {{ $colors[$data->send ?? 1] ?? '' }}">
                                        {{ $status[$data->send ?? 1] }}
                                    </span>
                                </td>
                                <td>
                                    {{ number_format($data->total_batch_qty) }} <br>
                                    {{-- {{ number_format($data->batch_qty_pending) }} --}}
                                </td>

                                <td>{{ $data->tong_lo }}</td>
                                <td>{{ $data->status_counts['Chưa làm'] ?? 0 }}</td>
                                <td>{{ $data->status_counts['Đã Cân'] ?? 0 }}</td>
                                <td>{{ $data->status_counts['Đã Pha chế'] ?? 0 }}</td>
                                <td>{{ $data->status_counts['Đã THT'] ?? 0 }}</td>
                                <td>{{ $data->status_counts['Đã định hình'] ?? 0 }}</td>
                                <td>{{ $data->status_counts['Đã Bao phim'] ?? 0 }}</td>
                                <td>{{ $data->status_counts['Hoàn Tất ĐG'] ?? 0 }}</td>
                                <td>{{ $data->status_counts['Hủy'] ?? 0 }}</td>


                                <td>{{ $data->send_by ?? 'NA' }}</td>

                                <td>{{ $data->send_date ? \Carbon\Carbon::parse($data->send_date)->format('d/m/Y') : '' }}
                                </td>


                                <td class="text-center align-middle">
                                    <form action="{{ route('pages.plan.production.open') }}" method="get">
                                        @csrf
                                        <input type="hidden" name="plan_list_id" value="{{ $data->id }}">
                                        <input type="hidden" name="month" value="{{ $data->month }}">
                                        <input type="hidden" name="send" value="{{ $data->send }}">
                                        <input type="hidden" name="name" value="{{ $data->name }}">

                                        <button type="submit" class="btn btn-success">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </form>
                                </td>

                                {{-- <td class="text-center align-middle">
                                    <form action="{{ route('pages.plan.production.open_stock') }}" method="get">
                                        @csrf
                                        <input type="hidden" name="plan_list_id" value="{{ $data->id }}">
                                        <input type="hidden" name="current_url" value="{{ url()->full() }}">
                                        <button type="submit" class="btn btn-success" {{ $auth_view_material }}>
                                            <i class="fas fa-table"></i>
                                        </button>
                                    </form>
                                </td> --}}

                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        @if (isset($summary_by_month) && count($summary_by_month) > 0)
            <div class="card card-info">
                <div class="card-header">
                    <h3 class="card-title">Tổng Hợp Kế Hoạch Theo Tháng KCS</h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-tool" data-card-widget="collapse">
                            <i class="fas fa-minus"></i>
                        </button>
                    </div>
                </div>
                <div class="card-body" style="overflow-x: auto;">
                    <table id="table_month" class="table table-bordered table-striped" style="font-size: 20px">
                        <thead>
                            <tr>
                                <th rowspan="2">STT</th>
                                <th rowspan="2">Tháng</th>
                                <th rowspan="2">Sản Lượng Lý Thuyết (ĐVL)</th>
                                <th colspan="9" style="text-align:center;">Tình Trạng Sản Xuất</th>
                                <th rowspan="2">Chi Tiết</th>
                                <th rowspan="2">Lịch Chưa Sắp</th>
                            </tr>
                            <tr>
                                <th>Tổng Lô</th>
                                <th>Chưa Làm</th>
                                <th>Đã Cân</th>
                                <th>Đã PC</th>
                                <th>Đã THT</th>
                                <th>Đã ĐH</th>
                                <th>Đã BP</th>
                                <th>Đã ĐG</th>
                                <th>Hủy</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php $month_stt = 1; @endphp
                            @foreach ($summary_by_month as $month_data)
                                <tr>
                                    <td>{{ $month_stt++ }}</td>
                                    <td>{{ $month_data->month }}</td>
                                    <td>{{ number_format($month_data->total_batch_qty) }}</td>
                                    <td>{{ $month_data->tong_lo }}</td>
                                    <td>{{ $month_data->status_counts['Chưa làm'] ?? 0 }}</td>
                                    <td>{{ $month_data->status_counts['Đã Cân'] ?? 0 }}</td>
                                    <td>{{ $month_data->status_counts['Đã Pha chế'] ?? 0 }}</td>
                                    <td>{{ $month_data->status_counts['Đã THT'] ?? 0 }}</td>
                                    <td>{{ $month_data->status_counts['Đã định hình'] ?? 0 }}</td>
                                    <td>{{ $month_data->status_counts['Đã Bao phim'] ?? 0 }}</td>
                                    <td>{{ $month_data->status_counts['Hoàn Tất ĐG'] ?? 0 }}</td>
                                    <td>{{ $month_data->status_counts['Hủy'] ?? 0 }}</td>
                                    <td class="text-center align-middle">
                                        <form action="{{ route('pages.plan.production.open') }}" method="get">
                                            @csrf
                                            <input type="hidden" name="plan_list_id" value="-2">
                                            <input type="hidden" name="expected_month"
                                                value="{{ $month_data->month }}">
                                            <input type="hidden" name="name"
                                                value="KẾ HOẠCH THÁNG {{ $month_data->month }}">

                                            <button type="submit" class="btn btn-success">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                        </form>
                                    </td>
                                    <td class="text-center align-middle">
                                        <button class="btn btn-warning btn-show-waiting"
                                            data-month="{{ $month_data->month }}" title="Danh sách lịch chưa sắp">
                                            <i class="fas fa-list"></i>
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        <!-- Modal Danh sách chờ -->
        <div class="modal fade" id="waitingPlanModal" tabindex="-1" role="dialog"
            aria-labelledby="waitingPlanModalLabel" aria-hidden="true">
            <div class="modal-dialog" style="max-width: 95%;" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <a href="{{ route('pages.general.home') }}">
                            <img src="{{ asset('img/iconstella.svg') }}" style="opacity: 0.8; max-width:45px;">
                        </a>
                        <h4 class="modal-title w-100 text-center" id="waitingPlanModalLabel"
                            style="color: #CDC717; font-size: 30px;">
                            Danh Sách Lịch Chưa Sắp <span id="waitingMonthTitle"></span>
                        </h4>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="row mb-3 align-items-center">
                            <div class="col-md-6 d-flex align-items-center">
                                <label for="stageFilter" class="mb-0 mr-2"
                                    style="white-space: nowrap; font-size: 20px;">Lọc theo Công Đoạn: </label>
                                <select id="stageFilter" class="form-control" style="width: 250px;">
                                    <option value="1">Cân Nguyên Liệu</option>
                                    <option value="2">Cân Nguyên Liệu Khác</option>
                                    <option value="3">Pha Chế</option>
                                    <option value="4">Trộn Hoàn Tất</option>
                                    <option value="5">Định Hình</option>
                                    <option value="6">Bao Phim</option>
                                    <option value="7">ĐGSC - ĐGTC</option>
                                </select>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table id="table_waiting_plan" class="table table-bordered table-striped"
                                style="width: 100%; font-size: 20px;">
                                <thead>
                                    <tr>
                                        <th>STT</th>
                                        <th>Kế hoạch tháng</th>
                                        <th>Tên Sản Phẩm</th>
                                        <th>Số Lô</th>
                                        <th>Ngày Dự Kiến KCS</th>
                                        <th>Ngày Đáp Ứng</th>
                                        <th>Mức độ Ưu tiên</th>
                                    </tr>
                                </thead>
                                <tbody>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<script src="{{ asset('js/vendor/jquery-1.12.4.min.js') }}"></script>
<script src="{{ asset('js/popper.min.js') }}"></script>
<script src="{{ asset('js/bootstrap.min.js') }}"></script>

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
        $('#table_month').DataTable({
            "responsive": true,
            "autoWidth": false,
        });

        let waitingTable = null;

        $('.btn-show-waiting').on('click', function() {
            let month = $(this).data('month');
            $('#waitingMonthTitle').text('(Tháng ' + month + ')');
            $('#waitingPlanModal').modal('show');

            if (waitingTable) {
                waitingTable.destroy();
                $('#table_waiting_plan tbody').empty();
            }

            // Gọi AJAX lấy dữ liệu
            $.ajax({
                url: "{{ route('pages.plan.production.get_waiting_plans') }}",
                type: "GET",
                data: {
                    month: month
                },
                success: function(res) {
                    let html = '';
                    let stageNames = {
                        1: "Cân Nguyên Liệu",
                        3: "Pha Chế",
                        4: "Trộn Hoàn Tất",
                        5: "Định Hình",
                        6: "Bao Phim",
                        7: "ĐGSC - ĐGTC",
                        8: "N/A"
                    };

                    res.forEach(function(item, index) {
                        let levelColors = {
                            1: 'badge-danger',
                            2: 'badge-warning text-dark',
                            3: 'badge-primary',
                            4: 'badge-success'
                        };
                        let levelClass = levelColors[item.level] || 'badge-secondary';
                        let levelHtml = item.level ?
                            `<span class="badge ${levelClass}" style="font-size: 16px; padding: 6px 10px; width: 40px;">${item.level}</span>` : '';
                        
                        let expectedDate = item.expected_date ? item.expected_date.split('-').reverse().join('/') : '';
                        let responsedDate = item.responsed_date ? item.responsed_date.split('-').reverse().join('/') : '';
                        let monthStr = item.month ? item.month.replace('-', '/') : '';

                        html += `<tr data-stage="${item.stage_code}">
                            <td>${index + 1}</td>
                            <td>${monthStr}</td>
                            <td>${item.name || ''}</td>
                            <td>${item.batch || ''}</td>
                            <td>${expectedDate}</td>
                            <td>${responsedDate}</td>
                            <td>${levelHtml}</td>
                        </tr>`;
                    });

                    $('#table_waiting_plan tbody').html(html);

                    waitingTable = $('#table_waiting_plan').DataTable({
                        "responsive": true,
                        "autoWidth": false,
                        "pageLength": 10
                    });

                    // Add custom filter logic if not added
                    if (!window.customStageFilterAdded) {
                        $.fn.dataTable.ext.search.push(function(settings, data, dataIndex, rowData, counter) {
                            if (settings.nTable.id !== 'table_waiting_plan') return true;
                            let selectedStage = $('#stageFilter').val();
                            if (!selectedStage) return true;
                            let trNode = settings.aoData[dataIndex].nTr;
                            let rowStage = $(trNode).attr('data-stage');
                            return selectedStage == rowStage;
                        });
                        window.customStageFilterAdded = true;
                    }

                    $('#stageFilter').off('change').on('change', function() {
                        waitingTable.draw();
                    });
                    
                    // Lọc hiển thị ngay từ đầu theo giá trị mặc định của select box
                    waitingTable.draw();
                }
            });
        });
    });
</script>
