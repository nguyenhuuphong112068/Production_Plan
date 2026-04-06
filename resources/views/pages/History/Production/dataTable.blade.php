<div class="content-wrapper">
    <div class="card">
        <div class="card-body">
            <div class="row mb-3">
                <div class="col-12">
                    <ul class="nav nav-tabs" id="historyTabs" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active" href="{{ route('pages.History.production.list') }}"
                                style="font-size: 18px; font-weight: bold;">
                                <i class="fas fa-industry mr-2"></i>Lịch Sử Sản Xuất
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
            <form id="filterForm" method="GET" action="{{ route('pages.History.production.list') }}"
                class="d-flex flex-wrap gap-0">
                <input type="hidden" name="main_type" value="production">
                <div class="row w-100 align-items-center">
                    <!-- Filter From/To -->
                    <div class="col-md-6 d-flex gap-2">
                        @php
                            use Carbon\Carbon;
                            $defaultFrom = Carbon::now()->subMonth(1)->toDateString();
                            $defaultTo = Carbon::now()->addDays(1)->toDateString();
                        @endphp
                        <div class="form-group d-flex align-items-center">
                            <label for="from_date" class="mr-2 mb-0">From:</label>
                            <input type="date" id="from_date" name="from_date"
                                value="{{ request('from_date') ?? $defaultFrom }}" class="form-control" />
                        </div>
                        <div class="form-group d-flex align-items-center">
                            <label for="to_date" class="mr-2 mb-0">To:</label>
                            <input type="date" id="to_date" name="to_date"
                                value="{{ request('to_date') ?? $defaultTo }}" class="form-control" />
                        </div>
                    </div>

                    <!-- Type Selector -->
                    <div class="col-md-6 d-flex justify-content-end">
                        <div class="form-group d-flex align-items-center" style="width: 300px">
                            <label class="mr-2 mb-0" style="white-space: nowrap">Công đoạn:</label>
                            <select class="form-control" name="stage_code" style="text-align-last: center;"
                                onchange="document.getElementById('filterForm').submit();">
                                <option {{ $stageCode == 1 ? 'selected' : '' }} value=1>Cân NL</option>
                                <option {{ $stageCode == 2 ? 'selected' : '' }} value=2>Cân NL Khác</option>
                                <option {{ $stageCode == 3 ? 'selected' : '' }} value=3>Pha Chế</option>
                                <option {{ $stageCode == 4 ? 'selected' : '' }} value=4>Trộn Hoàn Tất</option>
                                <option {{ $stageCode == 5 ? 'selected' : '' }} value=5>Định Hình</option>
                                <option {{ $stageCode == 6 ? 'selected' : '' }} value=6>Bao Phim</option>
                                <option {{ $stageCode == 7 ? 'selected' : '' }} value=7>Đóng Gói</option>
                            </select>
                        </div>
                    </div>
                </div>
            </form>

            <table id="data_table_hisory" class="table table-bordered table-striped " style="font-size: 20px">
                <thead style="position: sticky; background-color: white; z-index: 1020">
                    <tr>
                        <th>STT</th>
                        <th>Mã Sản Phẩm</th>
                        <th>Sản Phẩm</th>
                        <th>Cỡ lô</th>
                        <th>Ngày Dự Kiến KCS</th>
                        <th>Lô Thẩm Định</th>
                        <th>Phòng Sản Xuất</th>
                        <th>Thới Gian Sản Xuất</th>
                        <th>Thời Gian Vệ Sinh</th>
                        <th>Sản Lượng TT</th>
                        <th>Phòng BT</th>
                        <th>Ghi Chú</th>
                        <th>Người Tạo/ Ngày Tạo</th>
                        @if (session('user')['userName'] == 'Admin' ||
                                session('user')['userName'] == '19764' ||
                                session('user')['userName'] == '19713')
                            <th>Return</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @foreach ($datas as $data)
                        <tr>
                            <td>{{ $loop->iteration }}
                                @if (session('user')['userGroup'] == 'Admin')
                                    <div> {{ $data->id }} </div>
                                @endif
                            </td>
                            <td>
                                <div> {{ $data->intermediate_code }} </div>
                                <div> {{ $data->finished_product_code }} </div>
                            </td>
                            <td>{{ $data->name . ' - ' . $data->batch . ' - ' . $data->market }}</td>
                            <td>{{ $data->batch_qty . ' ' . $data->unit_batch_qty }}</td>
                            <td>
                                <div>{{ \Carbon\Carbon::parse($data->expected_date)->format('d/m/Y') }} </div>
                            </td>
                            <td class="text-center align-middle">
                                @if ($data->is_val)
                                    <i class="fas fa-check-circle text-primary fs-4"></i>
                                @endif
                            </td>
                            <td> {{ $data->room_name . ' - ' . $data->room_code }} </td>
                            @if ($data->actual_start)
                                <td> {{ \Carbon\Carbon::parse($data->actual_start)->format('d/m/Y H:i') . ' - ' . \Carbon\Carbon::parse($data->actual_end)->format('d/m/Y H:i') }}
                                </td>
                            @else
                                <td> {{ 'Chưa Xác nhận Hoàn Thành' }} </td>
                            @endif

                            @if ($data->actual_start_clearning)
                                <td> {{ \Carbon\Carbon::parse($data->actual_start_clearning)->format('d/m/Y H:i') . ' - ' . \Carbon\Carbon::parse($data->actual_end_clearning)->format('d/m/Y H:i') }}
                                </td>
                            @else
                                <td> {{ 'Chưa Xác nhận Hoàn Thành' }} </td>
                            @endif

                            <td> {{ $data->sum_actual_yeild }} {{ $stageCode <= 4 ? 'Kg' : 'ĐVL' }}</td>
                            <td> {{ $data->quarantine_room_code }} </td>
                            <td> {{ $data->note }} </td>

                            <td>
                                <div> {{ $data->finished_by }} </div>
                                <div>{{ \Carbon\Carbon::parse($data->finished_date)->format('d/m/Y') }} </div>
                            </td>

                            @if (session('user')['userName'] == 'Admin' ||
                                    session('user')['userName'] == '19764' ||
                                    session('user')['userName'] == '19713')
                                <td class="text-center align-middle">
                                    <button type="button" class="btn btn-return btn-danger"
                                        data-id="{{ $data->id }}">
                                        <i class="fas fa-undo"></i>
                                    </button>
                                </td>
                            @endif
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="{{ asset('js/vendor/jquery-1.12.4.min.js') }}"></script>
<script src="{{ asset('js/popper.min.js') }}"></script>
<script src="{{ asset('js/bootstrap.min.js') }}"></script>
<script src="{{ asset('js/sweetalert2.all.min.js') }}"></script>

<script>
    $(document).ready(function() {
        document.body.style.overflowY = "auto";
        $('#data_table_hisory').DataTable({
            paging: true,
            lengthChange: true,
            searching: true,
            ordering: true,
            info: true,
            autoWidth: false,
            pageLength: 10,
            language: {
                search: "Tìm kiếm:",
                lengthMenu: "Hiển thị _MENU_ dòng",
                info: "Hiển thị _START_ đến _END_ của _TOTAL_ dòng",
                paginate: {
                    previous: "Trước",
                    next: "Sau"
                }
            },
        });
    });

    $(document).on('click', '.btn-return', function() {
        const button = $(this);
        const stage_plan_id = button.data('id');
        $.ajax({
            url: "{{ route('pages.History.production.returnStage') }}",
            type: 'POST',
            data: {
                stage_plan_id: stage_plan_id,
                _token: "{{ csrf_token() }}"
            },
            success: function(res) {
                if (res.success) {
                    Swal.fire({
                        icon: "success",
                        title: res.message || "Thành công",
                        timer: 2000,
                        showConfirmButton: false
                    });
                    button.closest('tr').fadeOut(400, function() {
                        $(this).remove();
                    });
                }
            }
        });
    });

    const form = document.getElementById('filterForm');
    const fromInput = document.getElementById('from_date');
    const toInput = document.getElementById('to_date');
    [fromInput, toInput].forEach(input => {
        input.addEventListener('input', function() {
            form.submit();
        });
    });
</script>
