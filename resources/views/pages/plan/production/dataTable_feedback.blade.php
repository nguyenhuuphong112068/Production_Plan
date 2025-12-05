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

            $auth_update = user_has_permission(session('user')['userId'], 'plan_production_deActive', 'disabled');

            $colors = [
                1 => 'background-color: #f44336; color: white;', // đỏ
                2 => 'background-color: #ff9800; color: white;', // cam
                3 => 'background-color: blue; color: white;', // vàng
                4 => 'background-color: #4caf50; color: white;', // xanh lá
                ];
         @endphp

        <!-- /.card-Body -->
        <div class="card-body">
            <table id="data_table_plan_master" class="table table-bordered table-striped" style="font-size: 16px">
                <thead style = "position: sticky; top: 60px; background-color: white; z-index: 1020">
                    <tr>
                        <th>STT</th>
                        <th >Mã Sản Phẩm</th>
                        <th style="width:10%" >Sản Phẩm</th>
                        <th>
                            <div>{{"Kế Hoạch"}}</div>
                            <div>{{"(1) Ngày cần hàng"}}</div>
                            <div>{{"(2) Mức độ ưu tiên"}}</div>
                        </th>
                        <th>
                            <div>{{"Phân Xưởng "}}</div>
                            <div>{{"(1) Dự Kiến KT ĐG "}}</div>
                            <div>{{"(2) Ngày ra Hồ sơ PX"}} </div>
                            <div>{{"(3) Phản Hồi"}} </div>
                             @if ($department == $production)
                                <button class = "btn btn-success"
                                        data-toggle="modal"
                                        data-target="#pro_feedback_modal"
                                >Phản hồi toàn bộ</button>
                            @endif
                        </th>
                        <th>
                            <div>{{"Đảm Bảo Chất Lượng"}}</div>
                            <div>{{"(1) Tình hình hồ sơ lô "}}</div>
                            <div>{{"(2) Hồ sơ thực tế?"}} </div>
                            <div>{{"(3) Phản hồi"}} </div>
                            @if ($department == "QA")
                                <button class = "btn btn-success"
                                        data-toggle="modal"
                                        data-target="#qa_feedback_modal"
                                >Phản hồi toàn bộ</button>
                           @endif
                        <th>
                            <div>{{"Kỹ Thuật Bảo Trì"}}</div>
                            <div>{{"(1) Tình hình CC - KM "}}</div>
                            <div>{{"(2) Phản hồi"}} </div>
                            @if ($department == "EN")
                                <button class = "btn btn-success btn-en-feedback"
                                    data-toggle="modal"
                                    data-target="#en_feedback_modal"
                                >Phản hồi toàn bộ</button>
                            @endif
                        </th>
                        <th>
                            <div>{{"Kiểm Tra Chất Lượng"}}</div>
                            <div>{{"(1) Ngày yêu cầu ra phiếu TP"}}</div>
                            <div>{{"(2) Phản hồi"}} </div>
                            @if ($department == "QC")
                                <button class = "btn btn-success"
                                    data-toggle="modal"
                                    data-target="#qc_feedback_modal"
                                >Phản hồi toàn bộ</button>
                            @endif
                        </th>                       
                        <th>Ngày KCS thực tế </th>
                    </tr>
                </thead>
                <tbody>

                    @foreach ($datas as $data)
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
                                <div> {{ $data->name ."-". $data->batch  ."-". $data->market}} </div>
                                <div>  {{'(' . $data->batch_qty . ' ' . $data->unit_batch_qty . ')'}} </div>
                                <div>  {{ $data->specification }} </div>
                            </td>

                            <td class = "text-center">

                                @if (\Carbon\Carbon::parse($data->expected_date)->toDateString() < \Carbon\Carbon::parse($data->end)->addDays(5)->toDateString())
                                        <div class ="text-red font-weight-bold">{{ \Carbon\Carbon::parse($data->expected_date)->format('d/m/Y')}}</div>
                                @else
                                        <div class ="text-green font-weight-bold">{{ \Carbon\Carbon::parse($data->expected_date)->format('d/m/Y')}}</div>
                                @endif
                                
                                <div
                                    class = "text-center mt-1" style="display: inline-block; padding: 6px 10py; width: 50px; border-radius: 40px; {{ $colors[$data->level] ?? '' }}">
                                    {{$data->level}}
                                </div>

                                @if (\Carbon\Carbon::parse($data->expected_date)->toDateString() < \Carbon\Carbon::parse($data->end)->addDays(5)->toDateString())
                                    <div>
                                        <button class = "btn btn-success btn-accept mt-1 "
                                            data-id = {{ $data->id }}
                                            data-new_expected_date = {{ \Carbon\Carbon::parse($data->end)->addDays(5)->format('Y-m-d')}}
                                        > 
                                            <div> Chấp Nhận </div>
                                            <div> {{ "(".  \Carbon\Carbon::parse($data->end)->addDays(5)->format('d/m/Y') .")" }} </div>
                                        </button>
                                    </div>
                                @endif
                            </td>


                            {{-- PX Phản hồi --}}
                            <td class="text-left " > 

                                <div><b>{{"(1): " .\Carbon\Carbon::parse($data->end)->format('d/m/Y H:n') }} </b></div>

                                {{-- <div><b>{{"(2): " .\Carbon\Carbon::parse($data->end)->addDays(2)->format('d/m/Y') }} </b> </div> --}}
                                <b> {{"(2):"}}</b>
                                <input type= "date" class="updateInput" name="actual_record_date"  value="{{$data->actual_record_date}}" data-id = {{ $data->id }} {{ $auth_update }}></input>
                                <b> {{ "(3):" }}</b> 
                                @if ($department == $production)  
                                    <textarea class="updateInput text-left"
                                        name="pro_feedback"
                                        data-id="{{ $data->id }}"
                                        placeholder="Phân Xưởng Phản Hồi Tại Đây"
                                    >@if(!empty($data->pro_feedback)){{ $data->pro_feedback }}@endif</textarea>
                                @else
                                    {{empty($data->pro_feedback)?  "Chưa có phản hồi" : $data->pro_feedback }}
                                @endif             
                            </td>

                            {{-- QA Phản hồi --}}
                            <td class="text-center"> 
                                <div class="input-group mx-4">
                                    <label for="{{ $data->id }}"> : Hồ sơ lô</label>
                                    <input class="form-check-input step-checkbox"
                                        type="checkbox" 
                                        name ="has_BMR"
                                        data-id="{{ $data->id }}"
                                        id="{{ $data->id }}"
                                        {{ $auth_update != ''?'readOnly':''}}
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
                                      {{ $auth_update != ''?'readOnly':''}}
                                      {{ $data->actual_record ? 'checked' : '' }}
                                      >
                                </div>

                                <div>
                                    @if ($department == "QA")
                                        <textarea class="updateInput text-left"
                                            name="pro_feedback"
                                            data-id="{{ $data->id }}"
                                            placeholder="QA Phản Hồi Tại Đây"
                                        >@if(!empty($data->qa_feedback)){{ $data->qa_feedback }}@endif</textarea>
                                    @else
                                        {{empty($data->qa_feedback)?  "Chưa có phản hồi" : $data->qa_feedback }}
                                    @endif
                                </div>

                            </td>

                            {{-- EN Phản hồi --}}
                            <td class="text-center "> 
                                <div class="input-group mx-4">
                                    <label for="{{ $data->id }}"> : Chày cối - Khuôn mẫu sẳn sàng</label>
                                    <input class="form-check-input step-checkbox"
                                      type="checkbox"
                                      name ="has_punch_die_mold"
                                      data-id="{{ $data->id }}"
                                      id="{{ $data->id }}"
                                      {{ $auth_update != ''?'readOnly':''}}
                                      {{ $data->has_punch_die_mold ? 'checked' : '' }}
                                      >
                                </div>
           

                                 <div>
                                    @if ($department == "EN")
                                        <textarea class="updateInput text-left"
                                            name="en_feedback"
                                            data-id="{{ $data->id }}"
                                            placeholder="EN Phản Hồi Tại Đây"
                                        >@if(!empty($data->en_feedback)){{ $data->en_feedback }}@endif</textarea>
                                    @else
                                        {{empty($data->en_feedback)?  "Chưa có phản hồi" : $data->qa_feedback }}
                                    @endif
                                </div>
                            </td>

                            {{-- Qc Phản hồi --}}
                            <td class="text-left"> 
                              
                                <b> {{"(1):"}}</b>
                                @if ($department == "QC")
                                    <input type= "date" class="updateInput" name="actual_CoA_date"  value="{{$data->actual_CoA_date}}" data-id = {{ $data->id }} {{ $auth_update }}></input>
                                @else
                                    {{ $data->actual_CoA_date?? "Chưa có phản hồi"}}
                                @endif
                                <br>
                                <b> {{"(2):"}}</b>
                                <div>
                                    @if ($department == "QC")
                                        <textarea class="updateInput text-left"
                                            name="qc_feedback"
                                            data-id="{{ $data->id }}"
                                            placeholder="QC Phản Hồi Tại Đây"
                                        >@if(!empty($data->qc_feedback)){{ $data->qc_feedback }}@endif</textarea>
                                    @else
                                        {{empty($data->qc_feedback)?  "Chưa có phản hồi" : $data->qc_feedback }}
                                    @endif
                                </div>

                            </td>

                            {{-- KCS thực tế Phản hồi --}}
                            <td class="text-center "> 
                                @if ($department == "QA")
                                    <input type= "date" class="updateInput" name="actual_KCS"  value="{{$data->actual_KCS}}" data-id = {{ $data->id }} {{ $auth_update }}></input>
                                @else
                                    {{$data->actual_KCS}}
                                @endif
                            </td> 

                        </tr>
                    @endforeach
                </tbody>
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
                },
                infoCallback: function(settings, start, end, max, total, pre) {
                    let activeCount = 0;
                    let inactiveCount = 0;

                    settings.aoData.forEach(function(row) {
                        // row.anCells là danh sách <td> của từng hàng
                        const lastTd = row.anCells[row.anCells.length -
                            1]; // cột cuối (Vô Hiệu)
                        const btn = $(lastTd).find('button[type="submit"]');
                        const status = btn.data('type'); // lấy 1 hoặc 0

                        if (status == 1) activeCount++;
                        else inactiveCount++;
                    });

                    return pre + ` (Đang hiệu lực: ${activeCount}, Vô hiệu: ${inactiveCount})`;
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
                let name = $(this).attr('name');
                let checked = $(this).is(':checked');
                
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
                const new_expected_date = $(this).data('new_expected_date');

                // Gọi Ajax lấy dữ liệu history
                $.ajax({
                    url: "{{ route('pages.plan.production.accept_expected_date') }}",
                    type: 'post',
                    data: {
                        id: planMasterId,
                        new_expected_date: new_expected_date,
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
            });


        });
    </script>
