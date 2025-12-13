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
                        {{-- @php
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
                        </div> --}}
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
                       
                        
                        <th class = "text-center">Sản Lượng Thực Tế
                            @if ($stageCode <= 4)
                                {{ '(Kg)' }}
                            @else
                                {{ '(ĐVL)' }}
                            @endif
                        </th>
                        <th class = "text-center">Số Thùng</th>
                        <th>Ghi Chú</th>
                         <th>Xác Nhận Sản Xuất</th>

                        <th colspan="2">Thời Gian Vệ Sinh</th>
                        <th>Xác Nhận Tòa Bộ </th>


                    </tr>
                </thead>
                <tbody>

                    @foreach ($datas as $data)
                        @if ($data->yields && $data->quarantine_room_code)
                            @php $finisedRow = true @endphp
                        @else
                            @php $finisedRow = false @endphp
                        @endif

                        @if ($data->actual_start || $data->actual_start_clearning)
                            @php $semi_finished = 'disabled' @endphp
                        @else
                            @php $semi_finished = '' @endphp
                        @endif

                        


                        <tr data-id="{{ $data->id }}">
                            <td>{{ $loop->iteration }} 
                                @if(session('user')['userGroup'] == "Admin") <div> {{ $data->id}} </div> @endif
                            </td>
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
                                @if ($data->start)
                                    <input type="datetime-local" class="time" id = "start" {{ $semi_finished }}
                                        name="start"value="{{ \Carbon\Carbon::parse( $data->actual_start??$data->start )->format('Y-m-d\TH:i') }}">
                                    <input type="datetime-local" class="time" name="end" id = "end" {{ $semi_finished }}
                                        value = "{{ \Carbon\Carbon::parse($data->actual_end??$data->end)->format('Y-m-d\TH:i') }}">
                                @else
                                 <input type="datetime-local" class="time" id = "start" {{ $semi_finished }}
                                        name="start">
                                    <input type="datetime-local" class="time" id = "end" {{ $semi_finished }}
                                         name="end">
                                @endif
                                
                            </td>
                            <td>

                                <input type="text" class="time" name="yields" {{ $semi_finished }}
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
                                <input type="text" class="time" name="number_of_boxes" {{ $semi_finished }}
                                    value="{{ $data->number_of_boxes ?? 1 }}"
                                    oninput="
                                        // Chỉ cho nhập số nguyên
                                        this.value = this.value.replace(/[^0-9]/g, '');

                                      
                                        let val = parseInt(this.value);

                                        // Nếu nhỏ hơn 1 thì xóa
                                        if (!isNaN(val) && val <= 1) {
                                            this.value = '';
                                            return;
                                        }
                                      
                                    ">
                            </td>

                            <td> 
                                <textarea  class="updateInput text-left" name="note" > {{ $data->note }} </textarea>
                            </td>
                           



                            <td class="text-center align-middle">
                                @if ($semi_finished == 'disabled' || $data->actual_start_clearning)
                                    <button type="button" class="btn btn-success" disabled>
                                        ✓ Đã hoàn thành
                                    </button>  
                                @else
                                    <button type="button" class="btn btn-success btn-semi-finised position-relative" 
                                        {{ $finisedRow ? 'disabled' : '' }} data-id="{{ $data->id }}"
                                        data-toggle="modal" data-target="#finisedModal">
                                        <i class="fas fa-check"></i>
                                    </button>  
                                @endif

                            </td>

                            <td>
                                <div>BĐ: </div>
                                <div>KT: </div>
                            </td>
                            <td>
                                @if ($data->start_clearning)
                                    <input type="datetime-local" class="time" id = "start_clearning"
                                        name="start_clearning"value="{{ \Carbon\Carbon::parse($data->start_clearning)->format('Y-m-d\TH:i') }}">
                                    <input type="datetime-local" class="time" name="end_clearning" id = "end_clearning"
                                        value = "{{ \Carbon\Carbon::parse($data->end_clearning)->format('Y-m-d\TH:i') }}">
                                @else
                                    <input type="datetime-local" class="time" id = "start_clearning"
                                        name="start_clearning">
                                    <input type="datetime-local" class="time"  id = "end_clearning"
                                        name="end_clearning">
                                @endif

                            </td>


                            <td class="text-center align-middle">
                                <button type="button" class="btn btn-success btn-finised position-relative"
                                    {{ $data->actual_start_clearning ? 'disabled' : '' }} data-id="{{ $data->id }}"
                                    data-toggle="modal" data-target="#finisedModal">
                                    <i class="fas fa-check"></i><i class="fas fa-check" style="margin-left:-6px;"></i>
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
    document.addEventListener('DOMContentLoaded', function() {
      
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
      

        $(document).on('click', '.btn-finised, .btn-semi-finised', function(e) {
            e.preventDefault();
            const stage_code = $('#stage_code').val();
            const btn = this; // nút vừa click
            const row = btn.closest('tr');
            const id = row.dataset.id;
            const now = new Date();
            let actionType = "";

           
       

            if (btn.classList.contains('btn-finised')) {
                actionType = "finised"; 

                const inputs = [
                    ...row.querySelectorAll('#start'),
                    ...row.querySelectorAll('#end'),
                    ...row.querySelectorAll('#start_clearning'),
                    ...row.querySelectorAll('#end_clearning')
                ];

                for (let input of inputs) {
                    if (input && input.value) {
                        let valTime = new Date(input.value);

                        if (valTime > now) {
                            Swal.fire({
                                icon: "warning",
                                title: "Thời gian không hợp lệ",
                                text: "Không được nhập thời gian hoàn thành lớn hơn hiện tại!",
                                timer: 2000
                            });
                            return;
                        }
                    }
                }
            }

            if (btn.classList.contains('btn-semi-finised')) {

                actionType = "semi-finised";  

                const start = row.querySelector('#start');
                const end = row.querySelector('#end');

                const inputs = [start, end];

                for (let input of inputs) {
                    if (input && input.value) {
                        let valTime = new Date(input.value);

                        if (valTime > now) {
                            Swal.fire({
                                icon: "warning",
                                title: "Thời gian không hợp lệ",
                                text: "Không được nhập thời gian lớn hơn hiện tại!",
                                timer: 2000
                            });
                            return;
                        }
                    }
                }
            }



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
                    _token: "{{ csrf_token() }}",
                    actionType: actionType,
                    stage_code: stage_code
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

                    if (actionType === 'finised') {
                        $(row).find('.btn-semi-finised')
                            .addClass('disabled')
                            .text('✓ Đã hoàn thành');
                    }
                    
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
