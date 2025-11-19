<!-- Modal -->
<div class="modal fade " id="Modal" tabindex="-1" role="dialog" aria-labelledby="ModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">

        <form action="{{ route('pages.status.store') }}" method="POST">
            @csrf

            <div class="modal-content">
                <div class="modal-header">
                    <a href="{{ route('pages.general.home') }}">
                        <img src="{{ asset('img/iconstella.svg') }}" style="opacity: 0.8 ; max-width:45px;">
                    </a>

                    <h4 class="modal-title w-100 text-center" id="pModalLabel" style="color: #CDC717">
                        {{ 'Cập Nhật Trạng Thái Phòng Sản Xuất' }}
                    </h4>

                    <button type="button" class="close" data-dismiss="modal" aria-label="Đóng">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>

                <div class="modal-body">
                  
                    <div class="form-group">
                        <label for="name">Phòng Sản Xuất</label>
                        <input type="text" class="form-control" name="room_name" readonly
                            value="{{ old('room_name') }}">
                    </div>

                     <div class="form-group">
                       <label for="in_production">Sản Phẩm Đang Sản Xuất</label>
                        <input class="form-control" list="in_production_list" name="in_production" id="in_production">
                        <datalist id="in_production_list">
                            {{-- <option value="Không Sản Xuất">
                            <option value="Đang Vệ Sinh">
                            <option value="Bảo Trì">
                            <option value="Máy Hư">
                            @foreach ($planWaitings as $plan)
                                <option value="{{ $plan->name . '_' . $plan->batch }}"
                                    data-stage_code="{{ $plan->stage_code }}"
                                    data-resource_id="{{ $plan->resourceId }}">
                            @endforeach --}}
                        </datalist>
                    </div>


                    <div class="card card-success">
                        <div class="card-header">
                            <h3 class="card-title">Trạng Thái Phòng Sản Xuẩt</h3>
                        </div>
                        <div class="card-body">
                            <!-- Minimal style -->
                            <div class="row">
                                <div class="col-md-6">
                                    <!-- radio -->
                                    <div class="form-group clearfix">
                                        <div class="icheck-primary d-inline">
                                            <input type="radio" id="Status1" name="status" value = "1" checked>
                                            <label for="Status1">
                                                Đang Sản Xuất
                                            </label>
                                        </div>
                                    </div>

                                    <div class="form-group clearfix">
                                        <div class="icheck-primary d-inline">
                                            <input type="radio" id="Status2" name="status" value = "2" >
                                            <label for="Status2">
                                                Đang Vệ Sinh
                                            </label>
                                        </div>
                                    </div>

                                    <div class="form-group clearfix">
                                        <div class="icheck-primary d-inline">
                                            <input type="radio" id="Status4" name="status" value = "4" >
                                            <label for="Status4">
                                                Máy Hư
                                            </label>
                                        </div>
                                    </div>
                                </div> 
                                <div class="col-md-6">  

                                     <div class="form-group clearfix">
                                        <div class="icheck-primary d-inline">
                                            <input type="radio" id="Status0" name="status" value = "0" >
                                            <label for="Status0">
                                                Không Sản Xuất
                                            </label>
                                        </div>
                                    </div>

                                    <div class="form-group clearfix">
                                        <div class="icheck-primary d-inline">
                                            <input type="radio" id="Status3" name="status" value = "3" >
                                            <label for="Status3">
                                                Đang Bảo Trì
                                            </label>
                                        </div>
                                    </div>
                                </div>

                                {{-- <div class="col-md-4">
                                    <!-- radio -->
                                    <div class="form-group clearfix">
                                        <div class="icheck-primary d-inline">
                                            <input type="radio" id="sheet1" name="sheet" value = "1" checked>
                                            <label for="sheet1">
                                                Đầu Ca
                                            </label>
                                        </div>
                                    </div>

                                    <div class="form-group clearfix">
                                        <div class="icheck-primary d-inline">
                                            <input type="radio" id="sheet2" name="sheet" value = "2">
                                            <label for="sheet2">
                                                Giữa Ca
                                            </label>
                                        </div>
                                    </div>

                                    <div class="form-group clearfix">
                                        <div class="icheck-primary d-inline">
                                            <input type="radio" id="sheet3" name="sheet" value = "3">
                                            <label for="sheet3">
                                                Cuối Ca
                                            </label>
                                        </div>
                                    </div>

                                    <div class="form-group clearfix">
                                        <div class="icheck-primary d-inline">
                                            <input type="radio" id="sheet4" name="sheet" value = "0">
                                            <label for="sheet4">
                                                NA
                                            </label>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-4">
                                    <!-- radio -->
                                    <div class="form-group clearfix">
                                        <div class="icheck-primary d-inline">
                                            <input type="radio" id="step_batch1" name="step_batch" value = "1" checked>
                                            <label for="step_batch1">
                                                Đầu Lô
                                            </label>
                                        </div>
                                    </div>

                                    <div class="form-group clearfix">
                                        <div class="icheck-primary d-inline">
                                            <input type="radio" id="step_batch2" name="step_batch" value = "2">
                                            <label for="step_batch2">
                                                Giữa Lô
                                            </label>
                                        </div>
                                    </div>

                                    <div class="form-group clearfix">
                                        <div class="icheck-primary d-inline">
                                            <input type="radio" id="step_batch3" name="step_batch" value = "3">
                                            <label for="step_batch3">
                                                Cuối Lô
                                            </label>
                                        </div>
                                    </div>

                                    <div class="form-group clearfix">
                                        <div class="icheck-primary d-inline">
                                            <input type="radio" id="step_batch4" name="step_batch" value = "0">
                                            <label for="step_batch4">
                                                NA
                                            </label>
                                        </div>
                                    </div>

                                </div> --}}


                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-3">
                            <label>Thời Gian Chuẩn bị</label>
                            <div class="input-group">
                                <input type="text" class="form-control" id = "p_time" readonly >
                            </div>
                        </div>
                        <div class="col-md-3">
                            <label>Thời Gian Sản Xuất</label>
                            <div class="input-group">
                                 <input type="text" class="form-control" id = "m_time" readonly >
                            </div>
                        </div>
                        <div class="col-md-3">
                            <label>Thời Gian VS-I</label>
                            <div class="input-group">
                                 <input type="text" class="form-control" id = "C1_time" readonly>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <label>Thời Gian VS-II</label>
                            <div class="input-group">
                                <input type="text" class="form-control" id = "C2_time" readonly>
                            </div>
                        </div>
                    </div>
                    

                    <div class="row">
                        <div class="col-md-6">
                            <label>Thời Gian Bắt Đầu</label>
                            <div class="input-group">
                                <input type="datetime-local" class="form-control" name = "start" value="{{ old('start', \Carbon\Carbon::now()->format('Y-m-d\TH:i')) }}" >
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label>Dự Kiến Kết Thúc</label>
                            <div class="input-group">
                                <input type="datetime-local" class="form-control" name = "end"  value="{{ old('end', \Carbon\Carbon::now()->format('Y-m-d\TH:i')) }}">
                            </div>
                        </div>
                    </div>

                    <div class="row mt-3">
                        <div class="col-md-12">
                            <label>Thông báo</label>
                            <textarea class="form-control" name="notification" rows="2"></textarea>
                        </div>
                        @error('notification', 'createErrors')
                            <div class="alert alert-danger mt-1">{{ $message }}</div>
                        @enderror
                    </div>


                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
                    <button type="submit" class="btn btn-primary">
                        Lưu
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>


{{-- //Show modal nếu có lỗi validation --}}
@if ($errors->createErrors->any())
    <script>
        $(document).ready(function() {
            $('#Modal').modal('show');
        });
    </script>
@endif

<script>
    $(document).ready(function() {

    $('#in_production').on('input', function() {
        const inputVal = $(this).val();

        const selectedOption = $('#in_production_list option').filter(function() {
            return $(this).val() === inputVal;
        });
       
        if (selectedOption.length === 0) return; // không tìm thấy => dừng

        // Lấy intermediate_code từ option
        const intermediate_code = selectedOption.data('intermediate_code');
        const finished_product_code = selectedOption.data('finished_product_code');
        const stage_code = selectedOption.data('stage_code');
        let process_code = null;
        if (stage_code == 2){
             process_code = intermediate_code + "_NA_" + selectedOption.data('resource_id') + "_2"
        }else if (stage_code == 7) {
            process_code = intermediate_code + "_" + finished_product_code + "_" + selectedOption.data('resource_id')           
        }else {
            process_code = intermediate_code + "_NA_" + selectedOption.data('resource_id')          
        }
        
        if ( process_code == null){

            $('#p_time').val('00:00');
            $('#m_time').val('00:00');
            $('#C1_time').val('00:00');
            $('#C2_time').val('00:00');

            return
        }
      
        // Gửi AJAX
        $.ajax({
            url: "{{ route('pages.status.getQuota') }}",  // route trả về quota
            type: "POST",
            data: {
                process_code: process_code,
                _token: "{{ csrf_token() }}"
            },
            success: function(res) {
               
                const q = res[0];
                // Cập nhật lên các field nếu muốn
                $('#p_time').val(q.p_time ?? '');
                $('#m_time').val(q.m_time ?? '');
                $('#C1_time').val(q.C1_time ?? '');
                $('#C2_time').val(q.C2_time ?? '');
            },
            error: function(err) {
                console.error(err);
            }
        });

    });
        // Khi user đổi thời gian bắt đầu
    $('input[name="start"]').on('change input', updateEndTime);
        $('#m_time').on('change input', updateEndTime);

    });
</script>

<script>

    function parseDurationToMinutes(v) {
        if (!v) return 0;
        v = v.toString().trim();
        if (v === '') return 0;

        if (v.includes(':')) {
            const [hh, mm] = v.split(':').map(x => parseInt(x, 10) || 0);
            return hh * 60 + mm;
        }

        // dạng số phút
        const num = Number(v);
        return isNaN(num) ? 0 : Math.round(num);
    }

    function updateEndTime() {
        const startVal = $('input[name="start"]').val();
        if (!startVal) return;

        // get status (radio name="status")
        const status = $('input[name="status"]:checked').val();

        // parse times
        const pTime  = parseDurationToMinutes($('#p_time').val());
        const mTime  = parseDurationToMinutes($('#m_time').val());
        const C2Time = parseDurationToMinutes($('#C2_time').val());
        
        let totalMinutes = 0;

        // logic theo status
        if (status == "1") {
            totalMinutes = pTime + mTime;
        } 
        else if (status == "2") {
            totalMinutes = C2Time;
        } 
        else {
            // fallback nếu status khác
            totalMinutes = mTime;
        }

        // tạo Date từ datetime-local
        const startDate = new Date(startVal);
        startDate.setMinutes(startDate.getMinutes() + totalMinutes);

        // format yyyy-MM-ddTHH:mm
        const pad = (n) => n.toString().padStart(2, '0');

        const formattedEnd =
            `${startDate.getFullYear()}-${pad(startDate.getMonth() + 1)}-${pad(startDate.getDate())}`
            + `T${pad(startDate.getHours())}:${pad(startDate.getMinutes())}`;

        $('input[name="end"]').val(formattedEnd);
    }

 

</script>

<script>
    document.addEventListener("DOMContentLoaded", function () {

        const input = document.getElementById('in_production');
        const radios = document.querySelectorAll('input[name="status"]');

        function setStatus(statusId, lock) {
            const radio = document.getElementById(statusId);
            if (radio) radio.checked = true;

            // Khóa hoặc mở khóa tất cả radio
            //radios.forEach(r => r.disabled = lock);
            //if (lock) radio.disabled = false; // giữ radio được chọn vẫn mở
        }

        input.addEventListener('input', function () {
            const val = input.value.trim();

            if (val === "Đang Vệ Sinh") {
                setStatus("Status2", true);
            }
            else if (val === "Bảo Trì") {
                setStatus("Status3", true);
            }
            else if (val === "Máy Hư") {
                setStatus("Status4", true);
            }
            else if (val === "Không Sản Xuất") {
                setStatus("Status0", true);
            }
            else {
                // Trường hợp khác: mở khóa radio để chọn bình thường
                setStatus("Status1", true);
                //radios.forEach(r => r.disabled = false);
            }
        });

    });
</script>
