<style>
    .time {
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
    .time:focus {
        border: 1px solid #007bff;
        border-radius: 2px;
        background-color: #fff;
    }

    /* Tùy chọn: nếu bạn muốn chữ canh giữa theo chiều dọc */
    td input.time {
        display: block;
        margin: auto;
    }
</style>

<div class="content-wrapper">

    <div class="card">

        <div class="card-header mt-4">
            {{-- <h3 class="card-title">Ghi Chú Nếu Có</h3> --}}
        </div>
        <!-- /.card-Body -->
        <div class="card-body">


            <form id="filterForm" method="GET" action="{{ route('pages.Schedual.finised.index') }}"
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
            </form>

            <table id="data_table_Schedual_list" class="table table-bordered table-striped" style="font-size: 20px">
                <thead style = "position: sticky; top: 60px; background-color: white; z-index: 1020">
                    <tr>
                        <th>STT</th>
                        <th>Mã Sản Phẩm</th>
                        <th>Sản Phẩm</th>
                        <th>Số lô</th>
                        <th>Phòng Sản Xuất</th>
                        <th colspan="2">Thới Gian Sản Xuất</th>
                        <th colspan="2">Thời Gian Vệ Sinh</th>
                        <th>Sản Lượng Thực Tế
                            @if ($stageCode <= 4)
                                {{ '(Kg)' }}
                            @else
                                {{ '(ĐVL)' }}
                            @endif
                        </th>
                        <th>Phòng Biệt Trữ</th>
                        <th>Xác Nhận</th>


                    </tr>
                </thead>
                <tbody>

                    @foreach ($datas as $data)
                        @if ($data->yields && $data->quarantine_room_code)
                            @php $finisedRow = true @endphp
                        @else
                            @php $finisedRow = false @endphp
                        @endif


                        <tr data-id="{{ $data->id }}">
                            <td>{{ $loop->iteration }} </td>
                            <td>
                                <div> {{ $data->intermediate_code }} </div>
                                <div> {{ $data->finished_product_code }} </div>
                            </td>
                            <td>{{ $data->product_name }}</td>
                            <td>{{ $data->batch }}
                                @if ($data->is_val)
                                    <i class="fas fa-check-circle text-primary fs-4"></i>
                                @endif
                            </td>

                            <td> {{ $data->room_name . ' - ' . $data->room_code }} </td>
                            <td>
                                <div>BD: </div>
                                <div>KT: </div>
                            </td>
                            <td>
                                <input type="datetime-local" class="time"
                                    name="start"value="{{ \Carbon\Carbon::parse($data->start)->format('Y-m-d\TH:i') }}">
                                <input type="datetime-local" class="time" name="end"
                                    value = "{{ \Carbon\Carbon::parse($data->end)->format('Y-m-d\TH:i') }}">
                            </td>

                            <td>
                                <div>BĐ: </div>
                                <div>KT: </div>
                            </td>
                            <td>
                                <input type="datetime-local" class="time"
                                    name="start_clearning"value="{{ \Carbon\Carbon::parse($data->start_clearning)->format('Y-m-d\TH:i') }}">
                                <input type="datetime-local" class="time" name="end_clearning"
                                    value = "{{ \Carbon\Carbon::parse($data->end_clearning)->format('Y-m-d\TH:i') }}">
                            </td>

                            <td>

                                <input type="text" class="time" name="yields"
                                    data-max="{{ $data->Theoretical_yields }}"
                                    value="{{ $data->yields ?? ($data->Theoretical_yields ?? '') }}"
                                    oninput="
                                            this.value = this.value
                                                .replace(',', '.')
                                                .replace(/[^0-9.]/g, '')
                                                .replace(/(\..*)\./g, '$1');

                                            const max = parseFloat(this.dataset.max);
                                            const val = parseFloat(this.value);
                                            if (!isNaN(val) && val > max) this.value = max;
                                        ">

                            </td>

                            <td>

                                <select class="form-control" name="quarantine_room_code">
                                    @foreach ($quarantine_room as $item)
                                        <option value="{{ $item->code }}"
                                            {{ $data->quarantine_room_code == $item->code ? 'selected' : '' }}>
                                            {{ $item->code }}
                                        </option>
                                    @endforeach
                                </select>

                            </td>


                            <td class="text-center align-middle">
                                <button type="button" class="btn btn-success btn-finised position-relative"
                                    {{ $finisedRow ? 'disabled' : '' }} data-id="{{ $data->id }}"
                                    data-toggle="modal" data-target="#finisedModal">
                                    <i class="fas fa-check"></i>
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

    document.getElementById("prevStage").addEventListener("click", function() {
        currentIndex = (currentIndex > 0) ? currentIndex - 1 : stages.length - 1;
        updateStage();
        filterForm.submit();
    });

    document.getElementById("nextStage").addEventListener("click", function() {
        currentIndex = (currentIndex < stages.length - 1) ? currentIndex + 1 : 0;
        updateStage();
        filterForm.submit();
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

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Init tất cả stepper
        document.querySelectorAll('.bs-stepper').forEach(stepperEl => {
            new Stepper(stepperEl, {
                linear: false,
                animation: true
            });
        });
    });
</script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Gắn sự kiện bằng delegation để không bị mất sau khi search/reload
        $(document).on('click', '.btn-finised', function(e) {
            e.preventDefault();

            const btn = this; // nút vừa click
            const row = btn.closest('tr');
            const id = row.dataset.id;

            // Lấy dữ liệu input trong dòng đó
            const data = {};
            row.querySelectorAll('input, select, textarea').forEach(input => {
                data[input.name] = input.value;
            });
            data['id'] = id;

            // Disable tạm thời nút trong lúc gửi request
            btn.disabled = true;

            $.ajax({
                url: "{{ route('pages.Schedual.finised.store') }}",
                type: 'post',
                data: {
                    ...data,
                    _token: "{{ csrf_token() }}"
                },
                success: function(res) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Hoàn Thành',
                        timer: 1500,
                        showConfirmButton: false,
                    });

                    // Giữ nút ở trạng thái disabled sau khi hoàn thành
                    $(btn).addClass('disabled').text('✓ Đã hoàn thành');
                },
                error: function() {
                    alert("Lỗi khi gửi dữ liệu");
                    // Nếu lỗi → bật lại nút
                    btn.disabled = false;
                }
            });
        });
    });
</script>
