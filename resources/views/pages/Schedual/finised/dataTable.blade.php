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
                        <th>Xác Nhận Toàn Bộ </th>


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
                            <td> 
                                {{ $data->product_name }} {{$stageCode == 7? "- ". $data->market:'' }}
                                <input type="hidden" name="title" value = "{{$data->product_name ."-". $data->batch}}">

                            </td>


                            <td>
                                @if (!$data->actual_start && $stageCode == 1)
                                    <input style="color: red"  type="text" class="time actual_batch" id = "actual_batch" name="actual_batch"  value = "{{ $data->batch }}">
                                @else
                                    @if ( $data->actual_batch)
                                        <div style="color: blue"> {{ $data->batch }} </div>
                                    @else
                                        <div style="color: rgb(0, 0, 0)"> {{ $data->batch }} </div>
                                    @endif
                                @endif

                                @if ($data->is_val)
                                        <i class="fas fa-check-circle text-primary fs-4"></i>
                                @endif
                            </td>


                            <td>
                                @if ($data->actual_start)
                                    <span>
                                        {{ $data->room_name . ' - ' . $data->room_code }}
                                    </span>
                                    <input type="hidden" name="resourceId" value="{{ $data->resourceId}}">
                                @else
                                    <select class="form-control" name="resourceId" id ="room_id" >
                                        <option value="">-- Phòng Sản Xuất --</option>
                                            @foreach ($room_stages as $room_stage)
                                                <option value="{{ $room_stage->id}}" 
                                                    {{ ($data->resourceId ?? null) == $room_stage->id ? 'selected' : '' }}
                                                    >{{ $room_stage->code . ' - ' .  $room_stage->name}}</option>
                                            @endforeach
                                    </select>
                                @endif 
                            </td>

                        
                            <td>
                                <div>BD: </div>
                                <div>KT: </div>
                            </td>
                            <td>
                                @if (!empty($data->actual_start) || !empty($data->start))
                                    <input type="datetime-local" class="time" id = "start" name="start"  {{ $semi_finished }}
                                        value = "{{ \Carbon\Carbon::parse($data->actual_start??$data->start)->format('Y-m-d\TH:i') }}">
                                    <input type="datetime-local" class="time" id = "end" name="end"  {{ $semi_finished }}
                                        value = "{{ \Carbon\Carbon::parse($data->actual_end??$data->end)->format('Y-m-d\TH:i') }}">
                                @else
                                    <input type="datetime-local" class="time" id = "start"  name="start" {{ $semi_finished }}
                                       >
                                    <input type="datetime-local" class="time" id = "end" name="end" {{ $semi_finished }}
                                         >
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
                                @if (!empty($data->actual_start_clearning) || !empty($data->start_clearning))
                                    <input type="datetime-local" class="time" id = "start_clearning" name="start_clearning"
                                        value="{{ \Carbon\Carbon::parse($data->start_clearning)->format('Y-m-d\TH:i') }}">
                                    <input type="datetime-local" class="time"  id = "end_clearning" name="end_clearning"
                                        value = "{{ \Carbon\Carbon::parse($data->end_clearning)->format('Y-m-d\TH:i') }}">
                                @else
                                    <input type="datetime-local" class="time" id = "start_clearning" name="start_clearning">
                                    <input type="datetime-local" class="time" id = "end_clearning" name="end_clearning">
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
                
                //const resourceInput = row.querySelector('[name="resourceId"]');
                const resourceInput = row.querySelector('[name="resourceId"]');
                const resourceId = resourceInput ? resourceInput.value : null;
                
                
                if (!resourceId || resourceId == "") {
                    Swal.fire({
                        icon: "warning",
                        title: "Phòng Sản Xuất không hợp lệ",
                        text: "Chọn Phòng Sản Xuất!",
                        timer: 2000
                    });
                    return;
                }
                
                const startProdInput = row.querySelector('#start');
                const endProdInput   = row.querySelector('#end');

                const startCleanInput = row.querySelector('#start_clearning');
                const endCleanInput   = row.querySelector('#end_clearning');

                if (
                    !startProdInput.value ||
                    !endProdInput.value ||
                    !startCleanInput.value ||
                    !endCleanInput.value
                ) {

                    Swal.fire({
                        icon: "warning",
                        title: "Thời gian không hợp lệ",
                        text: "Thời Gian Không Được Để Trống!",
                        timer: 2000
                    });

                    return;
                }

                const startProd  = new Date(startProdInput.value);
                const endProd    = new Date(endProdInput.value);
                const startClean = new Date(startCleanInput.value);
                const endClean   = new Date(endCleanInput.value);

                // 1️⃣ SX: start < end
                if (startProd >= endProd) {
                    Swal.fire({
                        icon: "warning",
                        title: "Thời gian không hợp lệ",
                        text: "Thời gian bắt đầu sản xuất phải nhỏ hơn thời gian kết thúc!",
                        timer: 2000
                    });
                    return;
                }

                // 2️⃣ Cleaning: start < end
                if (startClean >= endClean) {
                    Swal.fire({
                        icon: "warning",
                        title: "Thời gian không hợp lệ",
                        text: "Thời gian bắt đầu vệ sinh phải nhỏ hơn thời gian kết thúc vệ sinh!",
                        timer: 2000
                    });
                    return;
                }

                // 3️⃣ Cleaning phải sau SX
                if (startClean < endProd) {
                    Swal.fire({
                        icon: "warning",
                        title: "Thời gian không hợp lệ",
                        text: "Thời gian bắt đầu vệ sinh phải lớn hơn hoặc bằng thời gian kết thúc sản xuất!",
                        timer: 2000
                    });
                    return;
                }




                const inputs = [
                    ...row.querySelectorAll('#start'),
                    ...row.querySelectorAll('#end'),
                    ...row.querySelectorAll('#start_clearning'),
                    ...row.querySelectorAll('#end_clearning')
                ];

                for (let input of inputs) {
                    if (input && input.value) {
                        let valTime = new Date(input.value);
                        
                        if (valTime > now || input.value == "" || input.value == null) {
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

                const resourceInput = row.querySelector('[name="resourceId"]');
                const resourceId = resourceInput ? resourceInput.value : null;
               
                if (!resourceId || resourceId == "" ) {
                    Swal.fire({
                       icon: "warning",
                        title: "Phòng Sản Xuất không hợp lệ",
                        text: "Chọn Phòng Sản Xuất!",
                        timer: 2000
                    });
                    return;
                }

                const start = row.querySelector('#start').value;
                const end   = row.querySelector('#end').value;

                if (start && end && start >= end) {
                    Swal.fire({
                        icon: "warning",
                        title: "Thời gian không hợp lệ",
                        text: "Thời gian bắt đầu phải nhỏ hơn thời gian kết thúc!",
                        timer: 2000
                    });
                    return;
                }

                const inputs = [start, end];
                
                for (let input of inputs) {
                   
                    if (input && input.value == "") {
                        let valTime = new Date(input.value);

                        if (valTime > now || input.value == "" || input.value == null) {
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
                    
                    $(row).find('.actual_batch').addClass('text-primary');

                    if (actionType === 'finised') {
                        $(row).find('.btn-semi-finised')
                            .addClass('disabled')
                            .text('✓ Đã hoàn thành');
                        
                    }
                    
                },
                error: function(xhr) {
                     btn.disabled = false;

                    let message = 'Có lỗi xảy ra';

                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        message = xhr.responseJSON.message;
                    }

                    Swal.fire({
                        icon: 'warning',
                        title: 'Không thể hoàn thành',
                        text: message
                    });
                }
            });
        });

    });
</script>
