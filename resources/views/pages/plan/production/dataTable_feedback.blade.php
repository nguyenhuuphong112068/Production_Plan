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

            //$auth_update = user_has_permission(session('user')['userId'], 'plan_production_deActive', 'disabled') ?? false;
            $plan_feedback = user_has_permission(session('user')['userId'], 'plan_feedback', 'boolean');
            $Record_KCS_Date = user_has_permission(session('user')['userId'], 'Record_KCS_Date', 'boolean');
            //dd ($department, $plan_feedback );
            $colors = [
                1 => 'background-color: #f44336; color: white;', // đỏ
                2 => 'background-color: #ff9800; color: white;', // cam
                3 => 'background-color: blue; color: white;', // vàng
                4 => 'background-color: #4caf50; color: white;', // xanh lá
                ];
              $end_packaging = 0;
              $missingOrders = 0;
              $actual_CoA_Count = 0;
              $actual_KCS_Count = 0;
              $actual_Mod_Count = 0;
              $actual_export_record_Count = 0;
              $has_BMR = 0;
         @endphp

        <!-- /.card-Body -->
        <div class="card-body">
            <table id="data_table_plan_master" class="table table-bordered table-striped" style="font-size: 16px">

                <tbody>
                    

                    @foreach ($datas as $data)
                        @php
                            if ($data->actual_CoA_date == null) {$actual_CoA_Count++;}
                            if ($data->actual_KCS == null) {$actual_KCS_Count++;}
                            if ($data->actual_record_date == null) {$actual_export_record_Count++;}
                            if ($data->has_BMR == 0) {$has_BMR++;}
                            if ($data->has_punch_die_mold == 0) {$actual_Mod_Count++;}
                            if ($data->end == null) {$end_packaging++;}
                        @endphp


                        <tr>
                            <td>
                                <div> {{ $loop->iteration }} </div>
                                @if(session('user')['userGroup'] == "Admin") <div> {{ $data->id}} </div> @endif
                            </td>

                            @if (!$data->cancel)
                                <td class="text-success">
                                    <div> {{ $data->intermediate_code }} </div>
                                    <div> {{ $data->finished_product_code }} </div>
                                </td>
                            @else
                                <td class="text-danger">
                                    <div> {{ $data->intermediate_code }} </div>
                                    <div> {{ $data->finished_product_code }} </div>
                                    
                                </td>
                            @endif

                            <td>
                                <div> {{ $data->name }} - {{ $data->actual_batch ??$data->batch }} - {{$data->market}} </div>
                                <div>  {{'(' . $data->batch_qty . ' ' . $data->unit_batch_qty . ')'}} </div>
                                <div>  {{ $data->specification }} </div>
                            </td>


                             {{-- KH Phản hồi --}}
                            <td class = "text-left">
                               
                                @if (!$data->end)
                                        <div class ="text-black font-weight-bold">{{ "(1): " . \Carbon\Carbon::parse($data->expected_date)->format('d/m/Y')}}</div>
                                @elseif (\Carbon\Carbon::parse($data->expected_date)->toDateString() < \Carbon\Carbon::parse($data->end)->addDays(5)->toDateString())
                                        <div class ="text-red font-weight-bold">{{ "(1): ". \Carbon\Carbon::parse($data->expected_date)->format('d/m/Y')}}</div>
                                @else
                                        <div class ="text-green font-weight-bold">{{ "(1): ". \Carbon\Carbon::parse($data->expected_date)->format('d/m/Y')}}</div>
                                @endif
                            

                                @if ($department == "PL" && $plan_feedback && $data->end && (\Carbon\Carbon::parse($data->expected_date)->toDateString() < \Carbon\Carbon::parse($data->end)->addDays(5)->toDateString()))
                                    <div class = "text-center">
                                        <button class = "btn btn-success btn-accept mt-1"
                                            data-id = {{ $data->id }}
                                            data-new_expected_date = {{ \Carbon\Carbon::parse($data->end)->addDays(5)->format('Y-m-d')}}
                                        > 
                                            <div> Cập Nhật Ngày Dự Kiên KCS </div>
                                            <div> {{ "(".  \Carbon\Carbon::parse($data->end)->addDays(5)->format('d/m/Y') .")" }} </div>
                                        </button>
                                    </div>
                                    <div> {{"Updated_by: " . $data->order_by }} </div>
                                    <div>{{"Updated_date: "}} {{$data->order_date ? \Carbon\Carbon::parse($data->pro_feedback_date)->format('d/m/Y') : '' }}</div>   
                                @endif

                                {{-- 
                                <b> {{"(2):"}}</b>
                                @if ($department == "PL" && $plan_feedback && !$data->order_number)
                                    <div class="text-center">
                                        <button class = "btn btn-success btn-order mt-1"
                                            data-id = {{ $data->id }}
                                            data-batch = {{ $data->batch }}
                                        > 
                                            <div> Cập Nhật Số Lệnh </div>
                                            <div> {{ $data->batch }} </div>
                                        </button>
                                    </div>
                                    @php
                                         $missingOrders++;
                                    @endphp
                                @elseif ($data->order_number)
                                    {{ $data->order_number }}
                                    <div> {{"Updated_by: " . $data->order_by }} </div>
                                    <div>{{"Updated_date: "}} {{$data->order_date ? \Carbon\Carbon::parse($data->pro_feedback_date)->format('d/m/Y') : '' }}</div>  
                                @endif --}}

 

                            </td>


                            {{-- PX Phản hồi --}}
                            <td class="text-left" > 

                                @if ($data->end)
                                    <div class = "{{ \Carbon\Carbon::parse($data->expected_date)->toDateString() < \Carbon\Carbon::parse($data->end)->addDays(5)->toDateString()?"text-red":"text-green" }}"
                                    ><b>{{"(1): " .\Carbon\Carbon::parse($data->end)->format('d/m/Y H:n') }} </b></div>
                                @else
                                    <div><b>{{"(1): Chưa có ngày kết thúc đóng gói " }} </b></div>
                                @endif
                                
                               
                                
                                @if ($department == $production && $plan_feedback)  
                                    <b> {{"(2):"}}</b>
                                    <input type= "date" class="updateInput" name="actual_record_date"  value="{{$data->actual_record_date}}" data-id = {{ $data->id }} ></input>
                                    <b> {{ "(3):" }}</b> 
                                    <textarea class="updateInput text-left"
                                        name="pro_feedback"
                                        data-id="{{ $data->id }}"
                                        placeholder="Phân Xưởng Phản Hồi Tại Đây"
                                    >@if(!empty($data->pro_feedback)){{ $data->pro_feedback }}@endif</textarea>
                                @else
                                    <b> {{"(2):"}}</b>
                                    {{empty($data->actual_record_date) || $data->actual_record_date == null ?  "Chưa có ngày ra hồ sơ" : \Carbon\Carbon::parse($data->actual_record_date)->format('d/m/Y')}}
                                    <br>
                                    <b> {{ "(3):" }}</b> 
                                    {{empty($data->pro_feedback)?  "Chưa có phản hồi" : $data->pro_feedback }}
                                @endif  

                                <div> {{"Updated_by: " . $data->pro_feedback_by }} </div>
                                <div>{{"Updated_date: "}} {{$data->pro_feedback_date ? \Carbon\Carbon::parse($data->pro_feedback_date)->format('d/m/Y') : '' }}</div>           
                            </td>

                            {{-- QA Phản hồi --}}
                            <td class="text-left"> 
                                <div class="input-group mx-4">
                                    <label for="{{ $data->id }}"> : Hồ sơ lô</label>
                                    <input class="form-check-input step-checkbox"
                                        type="checkbox" 
                                        name ="has_BMR"
                                        id="{{ $data->id }}"
                                        data-id="{{ $data->id }}"
                                        data-permission="{{ $department == "QA" && $plan_feedback?'1': "0" }}"
                                        {{ $data->has_BMR ? 'checked' : '' }}
                                      >
                                </div>

                                <div class="input-group mx-4">
                                    <label for="{{ $data->id }}"> : HSTT </label>
                                    <input class="form-check-input step-checkbox"
                                      type="checkbox"
                                      name ="actual_record"
                                      data-id="{{ $data->id }}"
                                      id="{{ $data->id }}"
                                      data-permission="{{ $department == "QA" && $plan_feedback?'1': "0" }}"
                                      {{ $data->actual_record ? 'checked' : '' }}
                                      >
                                </div>

                                <div>
                                    @if ($department == "QA" && $plan_feedback)
                                        <textarea class="updateInput text-left"
                                            name="qa_feedback"
                                            data-id="{{ $data->id }}"
                                            placeholder="QA Phản Hồi Tại Đây"
                                        >@if(!empty($data->qa_feedback)){{ $data->qa_feedback }}@endif</textarea>
                                    @else
                                        {{empty($data->qa_feedback)?  "Chưa có phản hồi" : $data->qa_feedback }}
                                    @endif
                                </div>

                                <div> {{"Updated_by: " . $data->qa_feedback_by }} </div>
                                <div>{{"Updated_date: "}} {{$data->qa_feedback_date ? \Carbon\Carbon::parse($data->qa_feedback_date)->format('d/m/Y') : '' }}</div>

                            </td>

                            {{-- EN Phản hồi --}}
                            <td class="text-left"> 

                                <div class="input-group mx-4">
                                    <label for="{{ $data->id }}"> : Chày cối - Khuôn mẫu sẳn sàng</label>

                                    <input class="form-check-input step-checkbox"
                                      type="checkbox"
                                      name ="has_punch_die_mold" 
                                      data-id="{{ $data->id }}"
                                      id="{{ $data->id }}"
                                      data-permission="{{ $department == "EN" && $plan_feedback?'1': "0" }}"
                                      {{ $data->has_punch_die_mold ? 'checked' : '' }}
                                      >

                                </div>
           

                                 <div>
                                    @if ($department == "EN" && $plan_feedback)
                                        <textarea class="updateInput text-left"
                                            name="en_feedback"
                                            data-id="{{ $data->id }}"
                                            placeholder="EN Phản Hồi Tại Đây"
                                        >@if(!empty($data->en_feedback)){{ $data->en_feedback }}@endif</textarea>
                                    @else
                                        {{empty($data->en_feedback)?  "Chưa có phản hồi" : $data->qa_feedback }}
                                    @endif
                                </div>

                                <div> {{"Updated_by: " . $data->en_feedback_by }} </div>
                                <div>{{"Updated_date: "}} {{$data->en_feedback_date ? \Carbon\Carbon::parse($data->en_feedback_date)->format('d/m/Y') : '' }}</div>
                            </td>

                            {{-- Qc Phản hồi --}}
                            <td class="text-left"> 
                              
                                <b> {{"(1):"}}</b>
                                @if (!$data->actual_CoA_date && $department == "QC" && $plan_feedback)
                                    <input type= "date" class="updateInput" name="actual_CoA_date"  value="{{$data->actual_CoA_date}}" data-id = {{ $data->id }} ></input>
                                @else
                                    <b class="{{$data->actual_CoA_date?'text-green':''}}"> {{ $data->actual_CoA_date? \Carbon\Carbon::parse($data->actual_CoA_date)->format('d/m/Y'): "Chưa có ngày ra phiếu"}} </b>
                                @endif
                                <br>
                                <b> {{"(2):"}}</b>
                                <div>
                                    @if ($department == "QC" && $plan_feedback)
                                        <textarea class="updateInput text-left"
                                            name="qc_feedback"
                                            data-id="{{ $data->id }}"
                                            placeholder="QC Phản Hồi Tại Đây"
                                        >@if(!empty($data->qc_feedback)){{ $data->qc_feedback }}@endif</textarea>
                                    @else
                                        {{empty($data->qc_feedback)?  "Chưa có phản hồi" : $data->qc_feedback }}
                                    @endif
                                </div>

                                <div> {{"Updated_by: " . $data->qc_feedback_by }} </div>
                                <div>{{"Updated_date: "}} {{$data->qc_feedback_date ? \Carbon\Carbon::parse($data->qc_feedback_date)->format('d/m/Y') : '' }}</div>

                            </td>

                            {{-- KCS thực tế Phản hồi --}}
                            <td class="text-left"> 
                                
                                @if (!$data->actual_KCS && $department == "QA" && $Record_KCS_Date)
                                    <input type= "date" class="updateInput" name="actual_KCS"  value="{{$data->actual_KCS}}" data-id = {{ $data->id }} ></input>
                                @else
                                    <b class="{{$data->actual_KCS?'text-green':''}}"> {{$data->actual_KCS? \Carbon\Carbon::parse($data->actual_KCS)->format('d/m/Y') : "Chưa có ngày KCS"}} </b>
                                @endif

                                <div> {{"Updated_by: " . $data->kcs_record_by }} </div>
                                <div>{{"Updated_date: "}}{{ $data->kcs_record_date ? \Carbon\Carbon::parse($data->kcs_record_date)->format('d/m/Y') : '' }}</div>
                            </td> 

                        </tr>
                    @endforeach
                </tbody>

                <thead style = "position: sticky; top: 60px; background-color: white; z-index: 1020">
                    <tr>
                        <th>STT</th>
                        <th >Mã Sản Phẩm</th>
                        <th style="width:10%" >Sản Phẩm</th>
                        <th >
                            <div>{{"KẾ HOẠCH"}}</div>
                            <div>{{"(1) Ngày dự kiến KCS"}}</div>
                            {{-- <div>{{ "(2) Số lệnh" }} <span class ="text-red"> {{"(chưa có:  $missingOrders lô)"}} </span> </div> --}}
                            
                        </th>
                        <th>
                            <div>{{"PHÂN XƯỞNG"}}</div>
                            <div>{{"(1) Dự Kiến KT ĐG"}} <span class ="text-red"> {{"(chưa có:  $end_packaging lô)"}} </span> </div>
                            <div>{{"(2) Ngày ra Hồ sơ PX" }} <span class ="text-red"> {{"(chưa có:  $actual_export_record_Count lô)"}} </span></div>
                            <div>{{"(3) Phản Hồi"}} </div>

                            {{-- @if ($department == $production && $plan_feedback)
                                <button class = "btn btn-success"
                                        data-toggle="modal"
                                        data-target="#pro_feedback_modal"
                                >Phản hồi toàn bộ</button>
                            @endif --}}

                        </th>
                        <th>
                            <div>{{"ĐẢM BẢO CHẤT LƯỢNG"}}</div>
                            <div>{{"(1) Tình hình hồ sơ lô" }} <span class ="text-red"> {{"(chưa có:  $has_BMR lô)"}} </span></div>
                            <div>{{"(2) Hồ sơ thực tế?"}} </div>
                            <div>{{"(3) Phản hồi"}} </div>
                            {{-- @if ($department == "QA" && $plan_feedback)
                                <button class = "btn btn-success"
                                        data-toggle="modal"
                                        data-target="#qa_feedback_modal"
                                >Phản hồi toàn bộ</button>
                           @endif --}}
                        <th>
                            <div>{{"KỸ THUẬT BẢO TRÌ"}}</div>
                            <div>{{"(1) Tình hình CC - KM"}} <span class ="text-red"> {{"(chưa có:  $actual_Mod_Count lô)"}} </span> </div>
                            <div>{{"(2) Phản hồi"}} </div>
                            @if ($department == "EN" && $plan_feedback)
                                <button class = "btn btn-success btn-en-feedback"
                                    data-toggle="modal"
                                    data-target="#en_feedback_modal"
                                >Phản hồi toàn bộ</button>
                            @endif
                        </th>
                        <th>
                            <div>{{"KIỂM TRA CHẤT LƯỢNG"}}</div>
                            <div>{{"(1) Ngày ra phiếu TP"}} <span class ="text-red"> {{"(chưa có:  $actual_CoA_Count lô)"}} </span></div>
                            <div>{{"(2) Phản hồi"}} </div>
                            {{-- @if ($department == "QC")
                                <button class = "btn btn-success"
                                    data-toggle="modal"
                                    data-target="#qc_feedback_modal"
                                >Phản hồi toàn bộ</button>
                            @endif --}}
                        </th>                       
                        <th> 
                            <div>{{ "Ngày KCS thực tế"}}</div>
                            <div class ="text-red"> {{"(chưa có:  $actual_KCS_Count lô)"}} </div>

                        </th>
                    </tr>
                </thead>
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
            preventDoubleSubmit("#send_form", "#send_btn");


            $('#data_table_plan_master').DataTable({
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
                }

            });

            $(document).on('focus', '.updateInput', function () {
                $(this).data('old-value', $(this).val());
            });

            $(document).on('blur', '.updateInput', function () {
                let id = $(this).data('id');
                let name = $(this).attr('name');
                let updateValue = $(this).val();
                let oldValue = $(this).data('old-value');
               
                if (updateValue === oldValue)return;
    
                $.ajax({
                    url: "{{ route('pages.plan.production.updateInput') }}",
                    type: 'POST',
                    dataType: 'json',
                    data: {
                    _token: '{{ csrf_token() }}',
                    id: id,
                    name: name,
                    updateValue: updateValue
                    }
                });
            });

            $(document).on('change', '.step-checkbox', function () {
                let id = $(this).data('id');
                let permission = $(this).data('permission');
                let name = $(this).attr('name');
                let checked = $(this).is(':checked');
                
                if (permission == 0){
                    Swal.fire({
                            title: 'Bạn Không Có Phân Quyền Thực Hiện Thao Tác Này',
                            icon: 'warning',
                            timer: 1000,
                            showConfirmButton: false
                    });
                     $(this).prop("checked", !checked);
                    return
                }


                if (checked){
                    checked = 1
                }else{
                    checked = 0
                }

                $.ajax({
                    url: "{{ route('pages.plan.production.updateInput') }}",
                    type: 'POST',
                    dataType: 'json',
                    data: {
                    _token: '{{ csrf_token() }}',
                    id: id,
                    name: name,
                    updateValue: checked
                    },
                    success: function(res) {
                        Swal.fire({
                            title: 'Hoàn Thành',
                            icon: 'success',
                            timer: 1000,
                            showConfirmButton: false
                        });

    
                    }
                });
            });

            $('.btn-accept').on('click', function() {
                const planMasterId = $(this).data('id');
                const new_expected_date = $(this).data('new_expected_date'); // yyyy-mm-dd

                Swal.fire({
                    title: 'Chấp Nhận Thay Đổi Ngày Dự Kiến KCS thành:',
                    html: `
                        <input 
                            type="date" 
                            id="swal_new_expected_date" 
                            class="swal2-input"
                            value="${new_expected_date}"
                        >
                    `,
                    showCancelButton: true,
                    confirmButtonText: 'Lưu',
                    cancelButtonText: 'Hủy',
                    focusConfirm: false,
                    preConfirm: () => {
                        const val = document.getElementById('swal_new_expected_date').value;
                        if (!val) {
                            Swal.showValidationMessage("Vui lòng chọn ngày!");
                        }
                        return val;
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        const selectedDate = result.value;

                        $.ajax({
                            url: "{{ route('pages.plan.production.accept_expected_date') }}",
                            type: 'post',
                            data: {
                                id: planMasterId,
                                new_expected_date: selectedDate,
                                _token: "{{ csrf_token() }}"
                            },
                            success: function(res) {
                                Swal.fire({
                                    title: 'Hoàn Thành',
                                    icon: 'success',
                                    timer: 1000,
                                    showConfirmButton: false
                                });

                                setTimeout(() => {
                                    location.reload();
                                }, 500);
                            }
                        });
                    }
                });
            });

            $('.btn-order').on('click', function() {

                const planMasterId = $(this).data('id');
                const batch = $(this).data('batch'); // giá trị mặc định
                
                Swal.fire({
                    title: 'Cập Nhật Thông Tin Ra Lệnh Sản Xuất',
                    html: `
                        <div style="text-align:left; margin-bottom:10px;">
                            <label for="swal_batch_no">Số lô:</label>
                            <input 
                                type="text" 
                                id="swal_batch_no" 
                                class="swal2-input" 
                                placeholder="Nhập số lô"
                                style="width:80%;"
                                value="${batch}" 
                            >
                        </div>
                        <div style="text-align:left; margin-bottom:10px;">
                            <label for="swal_order_no">Số lệnh:</label>
                            <input 
                                type="text" 
                                id="swal_order_no" 
                                class="swal2-input" 
                                placeholder="Nhập số lệnh"
                                style="width:80%;"
                            >
                        </div>
                    `,
                    showCancelButton: true,
                    confirmButtonText: 'Lưu',
                    cancelButtonText: 'Hủy',
                    focusConfirm: false,
                    preConfirm: () => {
                        const batchNo = document.getElementById('swal_batch_no').value;
                        const orderNo = document.getElementById('swal_order_no').value;

                        if (!batchNo || !orderNo) {
                            Swal.showValidationMessage("Vui lòng điền đầy đủ số lô và số lệnh!");
                            return false;
                        }

                        return { batchNo, orderNo };
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        const { batchNo, orderNo } = result.value;
                        $.ajax({
                            url: "{{ route('pages.plan.production.order') }}",
                            type: 'post',
                            data: {
                                id: planMasterId,
                                batch: batchNo,
                                order_number: orderNo,
                                _token: "{{ csrf_token() }}"
                            },
                            success: function(res) {

                                Swal.fire({
                                    title: 'Hoàn Thành',
                                    icon: 'success',
                                    timer: 1000,
                                    showConfirmButton: false
                                }).then(() => {
                                    location.reload(); // reload ngay sau khi popup đóng
                                });
                            }
                        });
                    }
                });
            });


        });
    </script>
