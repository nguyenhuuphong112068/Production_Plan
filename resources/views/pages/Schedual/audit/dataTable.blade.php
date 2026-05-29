<div class="content-wrapper">

    <div class="card">

        <div class="card-header mt-4">
            {{-- <h3 class="card-title">Ghi Chú Nếu Có</h3> --}}
        </div>
        <!-- /.card-Body -->
        <div class="card-body">


            {{-- <form id="filterForm" method="GET" action="{{ route('pages.Schedual.audit.index') }}"
                class="d-flex flex-wrap gap-2">
                @csrf
                <div class="row w-100 align-items-center">

                    <!-- Filter From/To -->
                    <div class="col-md-4 d-flex gap-2">
                        @php
                            use Carbon\Carbon;
                            $defaultFrom = Carbon::now()->toDateString();
                            $defaultTo = Carbon::now()->addMonth(2)->toDateString();
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

                    <!-- Stage Selector -->
                    <div class="col-md-4 d-flex justify-content-center align-items-center"
                        style="gap: 10px; height: 40px;">
                        <input type="hidden" name="stage_code" id="stage_code" value="{{ $stageCode }}">
                        <button type="button" id="prevStage" class="btn btn-link stage-btn"
                            style="font-size: 25px;">&laquo;</button>
                        <span id="stageName" class="fw-bold text-center" style="font-size: 25px;">
                            {{ optional($stages->firstWhere('stage_code', $stageCode))->stage ?? 'Không có công đoạn' }}
                        </span>
                        <button type="button" id="nextStage" class="btn btn-link stage-btn"
                            style="font-size: 25px;">&raquo;</button>
                        
                    </div>
                

                </div>
            </form> --}}

            <form id="filterForm" method="GET"
                action="{{ isset($plan_list_id) ? route('pages.Schedual.audit.open') : route('pages.Schedual.audit.index') }}"
                class="d-flex flex-wrap gap-2">
                @csrf
                @if (isset($plan_list_id))
                    <input type="hidden" name="plan_list_id" value="{{ $plan_list_id }}">
                @endif
                {{-- <div class="row w-100 align-items-center">
                        <!-- Stage Selector -->
                        <div class="col-md-4 d-flex justify-content-center align-items-center"
                            style="gap: 10px; height: 40px;">
                            <input type="hidden" name="stage_code" id="stage_code" value="{{ $stageCode }}">
                            <button type="button" id="prevStage" class="btn btn-link stage-btn"
                                style="font-size: 25px;">&laquo;</button>
                            <span id="stageName" class="fw-bold text-center" style="font-size: 25px;">
                                {{ optional($stages->firstWhere('stage_code', $stageCode))->stage ?? 'Không có công đoạn' }}
                            </span>
                            <button type="button" id="nextStage" class="btn btn-link stage-btn"
                                style="font-size: 25px;">&raquo;</button>
                        </div>
                    </div> --}}

                <div class="row w-100 align-items-center">
                    <div class="col-md-8"></div>

                    <!-- Stage Selector -->
                    <div class="col-md-4 d-flex justify-content-end" style="gap: 10px; height: 40px;">
                        <div class="form-group" style="width: 177px">
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

            <table id="data_table_Schedual_list" class="table table-bordered table-striped" style="font-size: 20px">
                <thead style = "position: sticky; top: 60px; background-color: white; z-index: 1020">
                    <tr>
                        <th>STT</th>
                        <th>Mã Sản Phẩm</th>
                        <th>Sản Phẩm</th>
                        <th>Cỡ lô</th>
                        <th>Số Lô</th>
                        <th>Ngày Dự Kiến KCS</th>
                        <th>Lô Thẩm Định</th>
                        <th>Phòng Sản Xuất</th>
                        <th>Thời Gian Sản Xuất</th>
                        <th>Thời Gian Vệ Sinh</th>
                        <th>Lý Do Tạo Lịch</th>
                        <th>Người Tạo/ Ngày Tạo</th>
                        <th>Version</th>
                        <th>Lịch Sử</th>

                    </tr>
                </thead>
                <tbody>

                    @foreach ($datas as $data)
                        <tr>
                            <td>{{ $loop->iteration }}
                                @if (session('user')['userGroup'] == 'Admin')
                                    <div> {{ $data->id }} </div> <br>
                                    <div> {{ $data->stage_plan_id }} </div>
                                @endif
                            </td>
                            <td>
                                <div> {{ $data->intermediate_code }} </div>
                                <div> {{ $data->finished_product_code }} </div>
                            </td>
                            <td>{{ $data->product_name . '-' . $data->batch }}</td>
                            <td>{{ $data->batch_qty . ' ' . $data->unit_batch_qty }}</td>
                            <td>{{ $data->batch }} </td>

                            <td>
                                <div>{{ \Carbon\Carbon::parse($data->expected_date)->format('d/m/Y') }} </div>
                            </td>
                            <td class="text-center align-middle">
                                @if ($data->is_val)
                                    <i class="fas fa-check-circle text-primary fs-4"></i>
                                @endif
                            </td>
                            <td> {{ $data->room_name . ' - ' . $data->room_code }} </td>
                            <td> {{ \Carbon\Carbon::parse($data->start)->format('d/m/Y H:i') . ' - ' . \Carbon\Carbon::parse($data->end)->format('d/m/Y H:i') }}
                            </td>
                            <td> {{ \Carbon\Carbon::parse($data->start_clearning)->format('d/m/Y H:i') . ' - ' . \Carbon\Carbon::parse($data->end_clearning)->format('d/m/Y H:i') }}
                            </td>

                            <td> {{ $data->type_of_change }} </td>


                            <td>
                                <div> {{ $data->schedualed_by }} </div>
                                <div>{{ \Carbon\Carbon::parse($data->schedualed_at)->format('d/m/Y') }} </div>
                            </td>
                            <td class="text-center align-middle">
                                {{ $data->version }}
                            </td>
                            <td class="text-center align-middle">
                                @if ($data->version > 1)
                                    <button type="button" class="btn btn-warning btn-sm btn-history position-relative"
                                        data-id="{{ $data->stage_plan_id }}" data-toggle="modal"
                                        data-target="#historyModal">
                                        <i class="fas fa-history"></i>
                                        <span class="badge badge-danger"
                                            style="position: absolute; top: -5px; right: -5px; border-radius: 50%; font-size: 10px; min-width: 18px;">
                                            {{ $data->version }}
                                        </span>
                                    </button>
                                @else
                                    <span class="text-muted" style="font-size:12px;">—</span>
                                @endif
                            </td>

                        </tr>
                    @endforeach

                </tbody>
            </table>
        </div>
        <!-- /.card-body -->
    </div>
</div>

{{-- Modal Lịch Sử --}}
@include('pages.Schedual.audit.history')


<script src="{{ asset('js/vendor/jquery-1.12.4.min.js') }}"></script>
<script src="{{ asset('js/popper.min.js') }}"></script>
<script src="{{ asset('js/bootstrap.min.js') }}"></script>
<script src="{{ asset('js/sweetalert2.all.min.js') }}"></script>



<script>
    $(document).ready(function() {
        document.body.style.overflowY = "auto";
        $('#data_table_Schedual_list').DataTable({
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
        });

        // Nút Lịch Sử: load tất cả version
        let historyDT = null;

        $(document).on('click', '.btn-history', function() {
            const stagePlanId = $(this).data('id');

            // Reset bảng
            if (historyDT) {
                historyDT.destroy();
                historyDT = null;
            }
            $('#data_table_history_body').empty();
            $('#historyModal').modal('show');

            $.ajax({
                url: '{{ route('pages.Schedual.audit.history') }}',
                method: 'GET',
                data: {
                    id: stagePlanId
                },
                success: function(rows) {
                    if (!rows || rows.length === 0) {
                        $('#data_table_history_body').html(
                            '<tr><td colspan="11" class="text-center text-muted">Không có lịch sử</td></tr>'
                            );
                    } else {
                        rows.forEach(function(r, idx) {
                            const isLatest = (idx === 0);
                            const rowClass = isLatest ?
                                'table-success font-weight-bold' : '';
                            const badge = isLatest ?
                                ' <span class="badge badge-success" style="font-size:11px;">Latest</span>' :
                                '';
                            // Helper: "yyyy-mm-dd HH:mm:ss" → "dd/mm/yyyy HH:mm"
                            function fmtDT(val) {
                                if (!val) return '-';
                                const s = val.replace('T', ' ');
                                const [datePart, timePart] = s.split(' ');
                                if (!datePart) return '-';
                                const [y, m, d] = datePart.split('-');
                                const hm = timePart ? timePart.substring(0, 5) : '';
                                return `${d}/${m}/${y}${hm ? ' ' + hm : ''}`;
                            }
                            const code = (r.intermediate_code || '') + (r
                                .finished_product_code ? '<br>' + r
                                .finished_product_code : '');
                            const start = fmtDT(r.start);
                            const end = fmtDT(r.end);
                            const scStart = fmtDT(r.start_clearning);
                            const scEnd = fmtDT(r.end_clearning);
                            const scheAt = r.schedualed_at ? fmtDT(r.schedualed_at
                                    .substring(0, 10) + ' 00:00:00').split(' ')[0] :
                                '-';
                            const batchQty = (r.batch_qty || '') + ' ' + (r
                                .unit_batch_qty || '');
                            $('#data_table_history_body').append(
                                `<tr class="${rowClass}">
                                    <td class="text-center">${idx + 1}</td>
                                    <td>${code}</td>
                                    <td>${r.product_name || '-'}</td>
                                    <td>${batchQty}</td>
                                    <td>${r.batch || '-'}</td>
                                    <td>${(r.room_name || '-') + ' - ' + (r.room_code || '')}</td>
                                    <td>${start}<br>${end}</td>
                                    <td>${scStart}<br>${scEnd}</td>
                                    <td>${r.type_of_change || '-'}</td>
                                    <td>${r.schedualed_by || '-'}<br>${scheAt}</td>
                                    <td class="text-center">${r.version}${badge}</td>
                                </tr>`
                            );
                        });
                    }
                    // Khởi tạo DataTable
                    historyDT = $('#data_table_history').DataTable({
                        paging: false,
                        searching: true,
                        ordering: true,
                        info: false,
                        autoWidth: false,
                        language: {
                            search: "Tìm kiếm:",
                        },
                    });
                },
                error: function(xhr) {
                    console.error('History error:', xhr.status, xhr.responseText);
                    $('#data_table_history_body').html(
                        '<tr><td colspan="11" class="text-center text-danger">Lỗi tải dữ liệu (HTTP ' +
                        xhr.status + ')</td></tr>');
                }
            });
        });

    });
</script>

<script>
    let stages = @json($stages);
    let currentIndex = stages.findIndex(s => s.stage_code == {{ $stageCode ?? 'null' }});

    const filterForm = document.getElementById("filterForm");
    const stageNameEl = document.getElementById("stageName");
    const stageCodeEl = document.getElementById("stage_code");


    function updateStage() {
        stageNameEl.textContent = stages[currentIndex].stage;
        stageCodeEl.value = stages[currentIndex].stage_code;
    }

    // document.getElementById("prevStage").addEventListener("click", function() {
    //     currentIndex = (currentIndex > 0) ? currentIndex - 1 : stages.length - 1;
    //     updateStage();
    //     filterForm.submit();
    // });

    // document.getElementById("nextStage").addEventListener("click", function() {
    //     currentIndex = (currentIndex < stages.length - 1) ? currentIndex + 1 : 0;
    //     updateStage();
    //     filterForm.submit();
    // });
</script>




{{-- <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Init tất cả stepper
        document.querySelectorAll('.bs-stepper').forEach(stepperEl => {
            new Stepper(stepperEl, {
                linear: false,
                animation: true
            });
        });
    });
</script> --}}
