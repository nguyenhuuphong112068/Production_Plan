<div class="content-wrapper">

    <div class="card">



        {{-- <div class="card-header mt-4">
              
              </div> --}}
        <!-- /.card-Body -->
        <div class="card-body">
            <div class="row mb-3">
                <div class="col-12">
                    <ul class="nav nav-tabs" id="historyTabs" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link {{ $main_type == 'production' ? 'active' : '' }}" 
                               href="{{ route('pages.History.list', ['main_type' => 'production']) }}"
                               style="font-size: 18px; font-weight: bold;">
                                <i class="fas fa-industry mr-2"></i>Lịch Sử Sản Xuất
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ $main_type == 'maintenance' ? 'active' : '' }}" 
                               href="{{ route('pages.History.list', ['main_type' => 'maintenance']) }}"
                               style="font-size: 18px; font-weight: bold;">
                                <i class="fas fa-tools mr-2"></i>Lịch Sử HC-BT
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
            <form id="filterForm" method="GET" action="{{ route('pages.History.list') }}" class="d-flex flex-wrap gap-0">
                <input type="hidden" name="main_type" value="{{ $main_type }}">
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
                        @if($main_type == 'production')
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
                        @else
                            <div class="form-group d-flex align-items-center" style="width: 300px">
                                <label class="mr-2 mb-0" style="white-space: nowrap">Loại:</label>
                                <select class="form-control" name="maintenance_type" style="text-align-last: center;"
                                    onchange="document.getElementById('filterForm').submit();">
                                    <option {{ $maintenanceType == 'HC' ? 'selected' : '' }} value="HC">Hiệu Chuẩn</option>
                                    <option {{ $maintenanceType == 'TB' ? 'selected' : '' }} value="TB">Bảo Trì</option>
                                    <option {{ $maintenanceType == 'TI' ? 'selected' : '' }} value="TI">Tiện Ích</option>
                                </select>
                            </div>
                        @endif
                    </div>
                </div>
            </form>

            <table id="data_table_hisory" class="table table-bordered table-striped " style="font-size: 20px">
                <thead style = "position: sticky; background-color: white; z-index: 1020">
                    <tr>
                        <th>STT</th>
                        @if($main_type == 'production')
                            <th>Mã Sản Phẩm</th>
                            <th>Sản Phẩm</th>
                            <th>Cỡ lô</th>
                        @else
                            <th>Mã Thiết Bị</th>
                            <th>Tên Thiết Bị</th>
                        @endif

                        <th>Ngày Dự Kiến KCS</th>
                        @if($main_type == 'production')
                            <th>Lô Thẩm Định</th>
                        @endif
                        <th>Phòng Sản Xuất</th>
                        <th>Thới Gian Sản Xuất</th>
                        <th>Thời Gian Vệ Sinh</th>
                        @if($main_type == 'production')
                            <th>Sản Lượng TT</th>
                        @else
                            <th>Kết Quả</th>
                        @endif
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
                            @if($main_type == 'production')
                                <td>
                                    <div> {{ $data->intermediate_code }} </div>
                                    <div> {{ $data->finished_product_code }} </div>
                                </td>
                                <td>{{ $data->name . ' - ' . $data->batch . ' - ' . $data->market }}</td>
                                <td>{{ $data->batch_qty . ' ' . $data->unit_batch_qty }}</td>
                            @else
                                <td>{{ $data->instrument_code }}</td>
                                <td>{{ $data->name }}</td>
                            @endif


                            <td>
                                <div>{{ \Carbon\Carbon::parse($data->expected_date)->format('d/m/Y') }} </div>
                            </td>
                            @if($main_type == 'production')
                                <td class="text-center align-middle">
                                    @if ($data->is_val)
                                        <i class="fas fa-check-circle text-primary fs-4"></i>
                                    @endif
                                </td>
                            @endif
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
                                <td> {{ $main_type == 'production' ? 'Chưa Xác nhận Hoàn Thành' : 'NA' }} </td>
                            @endif


                            @if($main_type == 'production')
                                <td> {{ $data->sum_actual_yeild }} {{ $stageCode <= 4 ? 'Kg' : 'ĐVL' }}</td>
                            @else
                                <td>
                                    @if($data->yields == 1)
                                        <span class="badge badge-success">Pass</span>
                                    @elseif($data->yields == 0)
                                        <span class="badge badge-danger">Fail</span>
                                    @elseif($data->yields == 2)
                                        <span class="badge badge-warning">Skip</span>
                                    @else
                                        <span class="badge badge-secondary">NA</span>
                                    @endif
                                </td>
                            @endif
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
                                        data-id =  "{{ $data->id }}">
                                        <i class="fas fa-undo"></i>
                                    </button>
                                </td>
                            @endif

                        </tr>
                    @endforeach

                </tbody>
            </table>
        </div>
        <!-- /.card-body -->
    </div>
    <!-- /.card -->
    <!-- /.container-fluid -->
    <!-- /.content -->
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
            url: "{{ route('pages.History.returnStage') }}",
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
</script>

<script>
    const form = document.getElementById('filterForm');
    const fromInput = document.getElementById('from_date');
    const toInput = document.getElementById('to_date');

    [fromInput, toInput].forEach(input => {
        input.addEventListener('input', function() {
            form.submit();
        });
    });
</script>

{{-- <script>
    let stages = @json($stages);
    let currentIndex = stages.findIndex(s => s.stage_code == {{ $stageCode ?? 'null' }});
    
    const filterForm = document.getElementById("filterForm");
    const stageNameEl = document.getElementById("stageName");
    const stageCodeEl = document.getElementById("stage_code");
    

    function updateStage() {
        stageNameEl.textContent = stages[currentIndex].stage;
        stageCodeEl.value = stages[currentIndex].stage_code;
    }

    document.getElementById("prevStage").addEventListener("click", function () {
        currentIndex = (currentIndex > 0) ? currentIndex - 1 : stages.length - 1;
        updateStage();
        filterForm.submit();
    });

    document.getElementById("nextStage").addEventListener("click", function () {
        currentIndex = (currentIndex < stages.length - 1) ? currentIndex + 1 : 0;
        updateStage();
        filterForm.submit();
    });
</script>  --}}





{{-- <script>
  document.addEventListener('DOMContentLoaded', function () {
      // Init tất cả stepper
      document.querySelectorAll('.bs-stepper').forEach(stepperEl => {
          new Stepper(stepperEl, { linear: false, animation: true });
      });
  });

</script> --}}
