<link href="{{ asset('css/bootstrap.min.css') }}" rel="stylesheet">
<style>
    .updateModal-modal-size {
        max-width: 50% !important;
        width: 50% !important;
    }

    .bootstrap-switch {
        height: 100%;
        display: flex;
        align-items: center;
        /* căn giữa theo chiều dọc */
    }
</style>

<!-- Modal -->
<div class="modal fade" id="updateModal" tabindex="-1" role="dialog" aria-labelledby="ModalLabel" aria-hidden="true">
    <div class="modal-dialog updateModal-modal-size" role="document">

        <form class="form-update" action="{{ route('pages.plan.production.update') }}" method="POST">
            @csrf

            <div class="modal-content">
                <div class="modal-header">
                    <a href="{{ route('pages.general.home') }}">
                        <img src="{{ asset('img/iconstella.svg') }}" style="opacity: 0.8 ; max-width:45px;">
                    </a>

                    <h4 class="modal-title w-100 text-center" id="updateModalLabel" style="color: #CDC717">
                        {{ 'Cập Nhật Lô' }}
                    </h4>

                    <button type="button" class="close" data-dismiss="modal" aria-label="Đóng">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>

                <div class="modal-body">

                    {{-- San Phẩm --}}
                    <div class ="row">
                        <div class = "col-md-3">
                            <div class="form-group">
                                <label>Mã BTP</label>
                                <input type="text" class="form-control" name="intermediate_code" readonly
                                    value="{{ old('intermediate_code') }}" />
                            </div>
                        </div>
                        <div class = "col-md-3">
                            <div class="form-group">
                                <label>Mã TP</label>
                                <input type="text" class="form-control" name="finished_product_code" readonly
                                    value="{{ old('finished_product_code') }}" />
                            </div>
                        </div>
                        <div class = "col-md-6">
                            <div class="form-group">
                                <label>Qui Cánh - Thị Trường</label>
                                <input type="text" class="form-control" name="specification" readonly
                                    value="{{ old('specification') }}" />
                            </div>
                        </div>
                        <input type="hidden" name="id" value="{{ old('id') }}" />
                        

                    </div>

                    <div class="row">
                        <div class="col-md-9">
                            <div class="form-group">
                                <label>Tên Sản Phẩm</label>
                                <input type="text" class="form-control" name="name" readonly
                                    value="{{ old('name') }}" />
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Cở Lô</label>
                                <input type="text" class="form-control" name="batch_qty" readonly
                                    value="{{ old('batch_qty') }}" />
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">

                            <label>Số lô</label>
                            <div class="input-group">
                                <input type="text" class="form-control" name="batch"
                                    value="{{ old('batch') }}" />
                                <input type="number" min="1" step="1" class="form-control"
                                    name="number_of_batch" value="{{ old('number_of_batch', 1) }}"
                                    oninput="this.value = this.value.replace(/[^0-9]/g, '')" readonly>
                                <span class="input-group-text p-0" style="width: 105px">
                                    <input type="checkbox" name="format_batch_no" checked data-bootstrap-switch>
                                </span>
                            </div>


                            <label class = "mt-2">Nguồn</label>
                            <div class="input-group">
                                <textarea class="form-control" name="source_material_name" rows="3" value="{{ old('source_material_name') }}" readonly></textarea>
                                <button type="button" class = "btn btn-success" data-toggle="modal" data-target="#selectSourceModal"> 
                                    <i class="fas fa-plus"></i>
                                </button>
                                <input type="hidden" class="form-control" name="material_source_id" value="{{ old('material_source_id') }}" />
                            </div>

                            {{-- Lô thẩm định --}}
                            <label class ="mt-3">Lô Thẩm Định</label>
                            <div class="card ">
                                <div class="card-body">
                                <div class="row">
                                    <div class="col-md-4">        
                                        <div class="icheck-primary d-inline">
                                            <input type="checkbox" class="step-checkbox" id="update_checkbox1" 
                                                name = "first_val_batch">
                                            <label for="update_checkbox1">Lô thứ nhất</label>
                                            <input type="text" name="batchNo1" class ="batchNo updateInput" value="{{ old('batchNo1') }}"  readonly/>
                                            <input type="hidden" name="code_val_first" value="{{ old('code_val_first') }}"/>
                                        </div>
                                    </div>
                                    <div class="col-md-4"> 
                                        <div class="icheck-primary d-inline">
                                            <input type="checkbox" class="step-checkbox" id="update_checkbox2" 
                                                name = "second_val_batch">
                                            <label for="update_checkbox2">Lô thứ hai</label>
                                            <span  class = "batchNo batchNo2" ></span>
                                        </div>
                                    </div>
                                    <div class="col-md-4"> 
                                        <div class="icheck-primary d-inline">
                                            <input type="checkbox" class="step-checkbox" id="update_checkbox3" 
                                                name = "third_val_batch">
                                            <label for="update_checkbox3">Lô thứ ba</label>
                                            <span class = "batchNo batchNo3"></span>
                                        </div>
                                    </div>
                                </div>
                                </div>
                            </div>


                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Ngày dự kiên KCS</label>
                                <div class="input-group">
                                    <input type="date" class="form-control" data-inputmask-alias="datetime" name = "expected_date"
                                        data-inputmask-inputformat="dd/mm/yyyy" data-mask value="{{ old('expected_date', \Carbon\Carbon::now()->addMonth(2)->format('Y-m-d')) }}">
                                </div>
                            </div>

                            <div class="card card-success">
                                <div class="card-header">
                                    <h3 class="card-title">Mức Độ Ưu Tiên</h3>
                                </div>
                                <div class="card-body">
                                    <!-- Minimal style -->
                                    <div class="row">
                                        <div class="col-sm-12">
                                            <div class="form-group clearfix">
                                                <div class="icheck-danger d-inline">
                                                    <input type="radio" id="update_level_1" name="level" value = "1" {{ old('level') == 1 ||  old('level') === null ? 'checked':'' }}>
                                                    <label for="update_level_1">
                                                        1: Hàng Gấp, Hàng Thầu
                                                    </label>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-sm-12">
                                            <div class="form-group clearfix">
                                                <div class="icheck-warning d-inline">
                                                    <input type="radio" id="update_level_2" name="level" value = "2" {{ old('level') == 2 ? 'checked':'' }}>
                                                    <label for="update_level_2">
                                                        2: Hàng Gấp, Hàng sắp hết số đăng ký
                                                    </label>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-sm-12">
                                            <div class="form-group clearfix">
                                                <div class="icheck-primary d-inline">
                                                    <input type="radio" id="update_level_3" name="level" value = "3" {{ old('level') == 3 ? 'checked':'' }}>
                                                    <label for="update_level_3">
                                                        3: Hàng SX dự trù theo kế hoạch bán hàng, đăng ký thuốc
                                                    </label>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-sm-12">
                                            <div class="form-group clearfix">
                                                <div class="icheck-success d-inline">
                                                    <input type="radio" id="update_level_4" name="level" value = "4" {{ old('level') == 4 ? 'checked':'' }}>
                                                    <label for="update_level_4">
                                                        4: Hàng không cần gấp
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <label>Có thể cân từ ngày</label>
                                <div class="input-group">
                                    <input type="date" class="form-control" data-inputmask-alias="datetime" name = "after_weigth_date"
                                        data-inputmask-inputformat="dd/mm/yyyy" data-mask value="{{ old('after_weigth_date', date('Y-m-d')) }}"> 
                                </div>
                        </div>
                        
                        {{-- <div class="col-md-3">
                            <label>Cân Trước ngày</label>
                                <div class="input-group">
                                    <input type="date" class="form-control" data-inputmask-alias="datetime" name = "before_weigth_date"
                                        data-inputmask-inputformat="dd/mm/yyyy" data-mask value="{{ old('before_weigth_date', \Carbon\Carbon::now()->addYear()->format('Y-m-d')) }}">
                                </div>
                        </div> --}}
                        <div class="col-md-6">
                            <label>Có thể ĐG từ ngày</label>
                                <div class="input-group">
                                    <input type="date" class="form-control" data-inputmask-alias="datetime" name = "after_parkaging_date"
                                        data-inputmask-inputformat="dd/mm/yyyy" data-mask value="{{ old('after_parkaging_date', date('Y-m-d')) }}">
                                </div>
                        </div>
                        {{-- <div class="col-md-3">
                            <label>Đóng gói trước ngày</label>
                                <div class="input-group">
                                    <input type="date" class="form-control" data-inputmask-alias="datetime" name = "before_parkaging_date"
                                        data-inputmask-inputformat="dd/mm/yyyy" data-mask value="{{ old('before_parkaging_date', \Carbon\Carbon::now()->addYear()->format('Y-m-d')) }}">
                                </div>
                        </div> --}}
                    </div>
                    {{-- Ghi chú --}}
                    <div class="row mt-3" >
                            <div class="col-md-12">
                                <label >Ghi Chú</label>
                                <textarea class="form-control" name="note" rows="2"></textarea>
                            </div>
                    </div>

                    {{-- Lý do --}}
                    <div class="row mt-3" style="display: {{ $send == 1 ?'block':'none' }}">
                        <div class="col-md-12">
                                <label >Lý Do Cập Nhật</label>
                                <textarea class="form-control" name="reason" rows="2"></textarea>
                        </div>
                    </div>

                    <div class="modal-footer" >
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
                        <button type="submit" class="btn btn-primary" id="update_btnSave">
                            Cập Nhật
                        </button>

                    </div>
                </div>
            </div>
        </form>
    </div>
</div>


<!-- Scripts -->
<script src="{{ asset('js/vendor/jquery-1.12.4.min.js') }}"></script>
<script src="{{ asset('js/popper.min.js') }}"></script>
<script src="{{ asset('js/bootstrap.min.js') }}"></script>

{{-- //Show modal nếu có lỗi validation --}}
@if ($errors->update_finished_Errors->any())
    <script>
        $(document).ready(function() {
            $('#updateModal').modal('show');
        });
    </script>
@endif


<script>
    $(document).ready(function() {
        $("input[data-bootstrap-switch]").bootstrapSwitch({
            onText: 'AAMMYY',
            offText: 'YWWAA',
            onColor: 'success',
            offColor: 'danger'
        });
        // Khi trang load
        $("input[data-bootstrap-switch]").each(function() {
            $(this).bootstrapSwitch('state', $(this).prop('checked'));
        });

        // Nếu muốn khi modal mở mới khởi tạo
        $('#updateModal').on('shown.bs.modal', function() {
            $("input[data-bootstrap-switch]").each(function() {
                $(this).bootstrapSwitch('state', $(this).prop('checked'));
            });
        });



        $("#update_number_of_unit").on('input', function() {
            let numberOfUnit = parseInt($(this).val()) || 0;

            // Lấy batch_qty và lọc chỉ lấy số
            let batchQtyStr = $("#update_max_number_of_unit").val();

            let batchQty = parseInt(batchQtyStr.replace(/[^0-9]/g, '')) || 0;

            if (numberOfUnit > batchQty) {
                $(this).val(0);
                let batchQtyStr = $("#percent_packaging").val(0);
                Swal.fire({
                    icon: 'warning',
                    title: 'Giá trị vượt quá cỡ lô',
                    text: `Số đơn vị (${numberOfUnit}) không được lớn hơn cỡ lô (${batchQty}).`,
                    confirmButtonText: 'OK'
                });
            } else {

                $("#update_update_percent_packaging").val((numberOfUnit / batchQty).toFixed(4))
            }
        });
        
        $('#selectSourceModal').on('show.bs.modal', function (e) {
            const button = $(e.relatedTarget);
            const modal = $('#updateModal');
            const intermediateCode = modal.find('input[name="intermediate_code"]').val() || "";
            $('#source_material_list').DataTable().search(intermediateCode).draw();
        })

        $("#updateModal .step-checkbox").on("change", function() {

            let batch = $('#updateModal input[name="batch"]').val();
            let checkbox1 = $("#update_checkbox1").is(":checked") ? 1 : 0;
            let checkbox2 = $("#update_checkbox2").is(":checked") ? 1 : 0;
            let checkbox3 = $("#update_checkbox3").is(":checked") ? 1 : 0;

            let code_val = $('#updateModal input[name="code_val_first"]').val()|| null;
            let intermediate_code = $('#updateModal input[name="intermediate_code"]').val()|| "" ;
            let first_batch_modal =  $('#tbody_first_val_batch')
            let total = checkbox1 + checkbox2 + checkbox3;
        
            if (checkbox1 == 1){
                $("#update_checkbox1").prop("checked", false);
                $("#update_checkbox3").prop("checked", false);
            }else if (checkbox2 == 1){
                $("#update_checkbox2").prop("checked", false);
                $("#update_checkbox3").prop("checked", false);
            }else if (checkbox3 == 1){
                $("#update_checkbox1").prop("checked", false);
                $("#update_checkbox2").prop("checked", false);
            }
                
           
            if (checkbox1 == 1 && !batch || batch.trim() === '') {
                Swal.fire({
                    icon: 'warning',
                    title: 'Nhập số lô trước khi chọn lô thẩm định',
                    confirmButtonText: 'OK'
                });
                $('input[name="batchNo1"]').val('');
                $('input[name="code_val_first"]').val('');
                $("#update_checkbox1").prop("checked", false);
                $("#update_checkbox2").prop("checked", false);
                $("#update_checkbox3").prop("checked", false);
                return;
            }

            if (total == 0){
                $('input[name="batchNo1"]').val('');
                $('input[name="code_val_first"]').val(null);
            }
            
            // if ((checkbox1 == 0 && checkbox2 == 1 && code_val == null) || (checkbox1 == 0 && checkbox2 == 0 && checkbox3 == 1 && code_val == null)){
               
            //     first_batch_modal.empty();
            //     $.ajax({
            //         url: "{{ route('pages.plan.production.first_batch') }}",
            //         type: 'post',
            //         data: {
            //             intermediate_code: intermediate_code,
            //             _token: "{{ csrf_token() }}"
            //         },
            //         success: function(res) {

            //             if (res.length === 0) {
            //                 first_batch_modal.append(
            //                     `<tr><td colspan="13" class="text-center">Không có lịch sử</td></tr>`
            //                 );
            //             } else {
            //                 res.forEach((item, index) => {
            //                     // map màu level
            //                     const colors = {
            //                         1: 'background-color: #f44336; color: white;', // đỏ
            //                         2: 'background-color: #ff9800; color: white;', // cam
            //                         3: 'background-color: blue; color: white;', // xanh dương
            //                         4: 'background-color: #4caf50; color: white;', // xanh lá
            //                     };
            //                     first_batch_modal.append(`
            //                   <tr>
            //                       <td>${index + 1}</td>
            //                       <td class="${index === 0 ? 'text-success' : 'text-danger'}""> 
            //                           <div>${item.intermediate_code ?? ''}</div>
            //                           <div>${item.finished_product_code ?? ''}</div>
            //                       </td>

            //                       <td>${item.name ?? ''} (${item.batch_qty ?? ''} ${item.unit_batch_qty ?? ''})</td>
            //                       <td>${item.batch ?? ''}</td>
            //                       <td>
            //                           <div>${item.market ?? ''}</div>
            //                           <div>${item.specification ?? ''}</div>
            //                       </td>

            //                         <td>
            //                           <div>${item.expected_date ? moment(item.expected_date).format('DD/MM/YYYY') : ''}</div>
            //                       </td>

            //                       <td style="text-align: center; vertical-align: middle;">
            //                           <span style="display: inline-block; padding: 6px 10px; width: 50px; border-radius: 40px; ${colors[item.level] ?? ''}">
            //                               <b>${item.level ?? ''}</b>
            //                           </span>

            //                       </td>

            //                       <td class="text-center align-middle">
            //                           ${item.is_val ? '<i class="fas fa-check text-primary fs-4"></i>' : ''}
            //                           <br>
            //                            <span class="fw-bold text-success">Lô thứ ${item.code_val ? item.code_val.split('_')[1] ?? '' : ''}</span>
            //                       </td>

            //                       <td>${item.source_material_name ?? ''}</td>

            //                       <td>
            //                           <div>${item.after_weigth_date ? moment(item.after_weigth_date).format('DD/MM/YYYY') : ''}</div>
            //                           <div>${item.before_weigth_date ? moment(item.before_weigth_date).format('DD/MM/YYYY') : ''}</div>
            //                       </td>
            //                       <td>
            //                           <div>${item.after_parkaging_date ? moment(item.after_parkaging_date).format('DD/MM/YYYY') : ''}</div>
            //                           <div>${item.before_parkaging_date ? moment(item.before_parkaging_date).format('DD/MM/YYYY') : ''}</div>
            //                       </td>

            //                       <td>${item.note ?? ''}</td>
            //                     <td>
            //                         <button type="button" class="btn btn-success btn-confirm-first-batch" 
            //                         data-id="${item.id}"
            //                         data-code_val="${item.code_val}"
            //                         data-batch="${item.batch}"

            //                         data-dismiss="modal">
            //                         <i class="fas fa-plus"></i>
            //                         </button>
            //                     </td>
                            
            //                   </tr>
            //               `);
            //                 });
            //             }
            //         },
            //         error: function(xhr, status, error) {
            //                 console.error("❌ Lỗi Ajax:", {
            //                     status: status,
            //                     error: error,
            //                     responseText: xhr.responseText
            //                 });
            //                 first_batch_modal.empty().append(
            //                     `<tr><td colspan="13" class="text-center text-danger">Lỗi tải dữ liệu</td></tr>`
            //                 );
            //             }
            //     });

            //     $('#fist_batch_modal').modal('show');
            // }
            return
        });

         preventDoubleSubmit("#updateModal", "#update_btnSave");
         
    });
    
</script>
