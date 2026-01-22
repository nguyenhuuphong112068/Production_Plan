<style>
    .step-checkbox {
        width: 20px;
        height: 20px;
        cursor: pointer;
        accent-color: #007bff;
        /* màu xanh bootstrap */
    }

    .step-checkbox:checked {
        box-shadow: 0 0 5px #007bff;
    }

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
    <!-- Main content -->
            <div class="card">
              @php

                //$update_daily_report = user_has_permission(session('user')['userId'], 'update_daily_report', 'boolean');
                $auth_update = user_has_permission(session('user')['userId'], 'quota_production_update', 'disabled');

                $stage_name = [
                      1 => "Cân Nguyên Liệu",
                      3 => "Pha Chế",
                      4 => "Trộn Hoàn Tất",
                      5 => "Định Hình",
                      6 => "Bao Phim",
                      7 => "ĐGSC - ĐGTC",
                  ]
              @endphp ---
              <!-- /.card-Body -->
              <div class="card-body mt-4">
                 <!-- Tiêu đề -->
                <div class ="row mx-2 mb-2">
                    <form id="filterForm" method="GET" action="{{ route('pages.report.monthly_report.index') }}"
                        class="d-flex flex-wrap gap-2">
                        @csrf
                        <div class="row w-100 align-items-center">
                            @php

                                $defaultWeek = \Carbon\Carbon::now()->weekOfYear;
                                $defaultMonth = \Carbon\Carbon::now()->month;
                                $defaultYear = \Carbon\Carbon::now()->year;
                             @endphp

              

                            <div class="col-md-12 d-flex justify-content-end">
                                {{-- <select id="week_number" name="week_number" class="form-control mr-2">
                                    @for ($i = 1; $i <= 52; $i++)
                                        <option value="{{ $i }}"
                                            {{ (request('week_number') ?? $defaultWeek) == $i ? 'selected' : '' }}>
                                            Tuần {{ $i }}
                                        </option>
                                    @endfor
                                </select> --}}
                                
                                <select id="month" name="month" class="form-control mr-2">
                                    @for ($m = 1; $m <= 12; $m++)
                                        <option value="{{ $m }}"
                                            {{ (request('month') ?? $defaultMonth) == $m ? 'selected' : '' }}>
                                            Tháng {{ $m }}
                                        </option>
                                    @endfor
                                </select>

                                <select id="year" name="year" class="form-control" style="width: 500px">
                                    @php $currentYear = now()->year; @endphp
                                    @for ($y = $currentYear - 5; $y <= $currentYear + 5; $y++)
                                        <option value="{{ $y }}"
                                            {{ (request('year') ?? $defaultYear) == $y ? 'selected' : '' }}>
                                            {{ $y }}
                                        </option>
                                    @endfor
                                </select>
                            </div>
                        </div>
                    </form>
                </div>

                <div>
                    <table id="data_table_quota" class="table table-bordered table-striped" style="font-size: 20px">
                        <thead style = "position: sticky; top: 60px; background-color: white; z-index: 1020">
                            <tr>
                                <th rowspan="2" class="text-center">STT</th>
                                <th rowspan="2" class="text-center">Tên Máy</th>
                                <th rowspan="2" class="text-center">Công Suất (1)</th>
                                <th colspan="3" class="text-center">Thời Gian Làm Việc TT Trong Tuần (2)</th>

                                <th rowspan="2" class="text-center" >Sản lượng lý thuyết (3)</th>
                                <th rowspan="2" class="text-center">Sản lượng thực tế (4)</th>

                                <th rowspan="2" class="text-center">OEE %
                                     (5)</th>
                                
                                <th colspan="3" class="text-center">Thời gian làm việc LT tối đã trong tuần (6)</th>
                                <th rowspan="2" class="text-center">Loading % (7)</th>
                                <th rowspan="2" class="text-center">TEEP % (8)</th>
                            </tr>
                            <tr>
                                <th>Thời Gian Sản Xuất (a)</th>
                                <th>Thời Gian Vệ Sinh (b)</th>
                                <th>Tổng Thời Gian (c) = (a) + (b)</th>


                                <th>Ca (a)</th>
                                <th>Số ngày trong tuần (b)</th>
                                <th>(6c)=(6a)x(6b)x8</th>
                               
                            </tr>

               
                        </thead>

                        @php $current_Stage = 0 ;@endphp

                        <tbody>
                           
                                @foreach ($datas as $data)
                                    @if ($current_Stage != $data->stage_code)
                                        
                                        <tr style="background:#CDC717; color:#003A4F; font-weight:bold; cursor: pointer;"
                                            class="stage-total" data-stage="{{ $data->stage_code }}">
                                            <td colspan="14"  class="text-end">
                                                <button type="button" class="btn btn-sm btn-info toggle-stage" 
                                                style="width: 20px; height: 20px; padding: 0; line-height: 0;"
                                                data-stage="{{ $data->stage_code }}">+</button>
                                                Công Đoạn {{ $stage_name[$data->stage_code] }}
                                            </td>
                                        </tr>

                                        @php $current_Stage = $data->stage_code ;@endphp
                                    @endif

                                    <tr class="stage-child stage-{{$data->stage_code}}">
                                        <td> {{$loop->iteration  }}
                                            @if (session('user')['userGroup'] == 'Admin')
                                                <div> {{ $data->id }} </div>
                                            @endif
                                        </td>

                                        <td> {{$data->room_code . " - " . $data->room_name}} {{ "(" .$data->main_equiment_name ." )"}} </td>
                                        <td> {{number_format($data->capacity) }} </td>

                                        <td> {{$data->work_hours }} </td>
                                        <td> {{$data->cleaning_hours }} </td>
                                        <td> {{$data->busy_hours }} </td>
                                        <td class="text-end"> {{number_format($data->output_thery) }} </td>
                                        <td class="text-end"> {{number_format($data->yield_actual) }} </td>
                                        <td> {{$data->OEE }} </td>

                                        {{-- shift --}}
                                        <td> 
                                            <input type= "text" class="time" name="shift" value = "{{ $data->shift }}" data-id={{ $data->id }} {{ $auth_update }}>
                                        </td> 

                                        {{-- day in week --}}
                                        <td> 
                                            <input type= "text" class="time" name="day_in_month" value = "{{ $data->day_in_month }}" data-id={{ $data->id }} {{ $auth_update }}>
                                        </td>  

                                        <td> {{$data->H_in_month }} </td>
                                        <td> {{$data->loading }} </td>
                                        <td> {{$data->TEEP }} </td>
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
    $(document).ready(function () {
        document.body.style.overflowY = "auto";     

    });

    $(document).on('focus', '.time', function() {
        $(this).data('old-value', $(this).val());
    });

    $(document).on('blur', '.time', function() {
       
        let id = $(this).data('id');
        let name = $(this).attr('name');
        let time = $(this).val();
        let oldValue = $(this).data('old-value');

        if (time === oldValue) return;
        

        if (name === "shift") {
            const value = Number(time);

            if (!Number.isInteger(value) || value < 0 || value > 3) {
                Swal.fire({
                    title: 'Lỗi định dạng!',
                    text: 'Ngày phải là số nguyên từ 0 đến 3',
                    icon: 'error',
                    timer: 2000,
                    showConfirmButton: false
                });
                $(this).focus();
                $(this).css('border', '1px solid red');
                return;
            } else {
                $(this).css('border', '');
            }
        }else if (name === "day_in_month") {
            const value = Number(time);

            if (!Number.isInteger(value) || value < 0 || value > 31) {
                Swal.fire({
                    title: 'Lỗi định dạng!',
                    text: 'Ngày phải là số nguyên từ 0 đến 31',
                    icon: 'error',
                    timer: 2000,
                    showConfirmButton: false
                });
                $(this).focus();
                $(this).css('border', '1px solid red');
                return;
            } else {
                $(this).css('border', '');
            }
        }

        $.ajax({
            url: "{{ route('pages.report.monthly_report.updateInput') }}",
            type: 'POST',
            dataType: 'json',
            data: {
                _token: '{{ csrf_token() }}',
                id: id,
                name: name,
                time: time
            }
        });
    });
    
</script>


<script>
    document.querySelectorAll('.toggle-stage').forEach(btn => {
        btn.addEventListener('click', function() {
            const stage = this.getAttribute('data-stage');
           
            const rows = document.querySelectorAll('.stage-' + stage);
            rows.forEach(row => {
                row.style.display = row.style.display === 'none' ? '' : 'none';
            });

            // đổi dấu + / -
            this.textContent = this.textContent === '+' ? '-' : '+';
        });
    });
</script>

<script>
    const form = document.getElementById('filterForm');
    const weekInput = document.getElementById('month');
    const yearInput = document.getElementById('year');

    function submitForm() {
        form.requestSubmit();
    }

    weekInput.addEventListener('change', submitForm);
    yearInput.addEventListener('change', submitForm);
</script>