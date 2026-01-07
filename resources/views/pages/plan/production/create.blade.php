<link href="{{ asset('css/bootstrap.min.css') }}" rel="stylesheet">
<style>
    .createModal-modal-size {
        max-width: 70% !important;
        width: 70% !important;
    }

    .bootstrap-switch {
        height: 100%;
        display: flex;
        align-items: center;
        /* căn giữa theo chiều dọc */
    }

    .batchNo {
        display: flex;
        justify-content: center;   /* canh giữa ngang */
        align-items: center;       /* canh giữa dọc */
        font-weight: bold;         /* in đậm */
        color: green;              /* màu chữ xanh */
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



</style>

<!-- Modal -->
<div class="modal fade" id="createModal" tabindex="-1" role="dialog" aria-labelledby="ModalLabel" aria-hidden="true">
    <div class="modal-dialog createModal-modal-size" role="document">

        <form action="{{ route('pages.plan.production.store') }}" method="POST">
            @csrf

            <div class="modal-content">
                <div class="modal-header">
                    <a href="{{ route('pages.general.home') }}">
                        <img src="{{ asset('img/iconstella.svg') }}" style="opacity: 0.8 ; max-width:45px;">
                    </a>
                    <h4 class="modal-title w-100 text-center" id="pModalLabel" style="color: #CDC717">
                        {{ 'Tạo Lô' }}
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
                        <input type="hidden" name="product_caterogy_id" 
                            value="{{ old('product_caterogy_id') }}" />
                        <input type="hidden" name="plan_list_id" readonly value="{{ old('plan_list_id') }}" />
                    </div>

                    <div class="row mt-0">
                        <div class="col-md-9">
                            <div class="form-group">
                                <label>Tên Sản Phẩm</label>
                                <input type="text" class="form-control" name="name" readonly
                                    value="{{ old('name') }}" />
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Cỡ Lô</label>
                                <input type="text" class="form-control" name="batch_qty" readonly
                                    value="{{ old('batch_qty') }}" />
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">

                            <label>Số lô</label>
                            <div class="input-group">
                                <input type="text" class="form-control batchNo" name="batch"
                                    value="{{ old('batch') }}" />
                                <input type="number" min="1" step="1" class="form-control"
                                    name="number_of_batch" value="{{ old('number_of_batch', 1) }}"
                                    oninput="this.value = this.value.replace(/[^0-9]/g, '')">
                                <span class="input-group-text p-0" style="width: 105px">
                                    <input type="checkbox" name="format_batch_no" checked data-bootstrap-switch>
                                </span>
                            </div>
                            <input type="hidden" name="max_number_of_unit" id ="max_number_of_unit" >
                            
                            <label class ="mt-2">Nguồn</label>
                            <div class="input-group">
                                <textarea class="form-control" name="source_material_name" rows="4"
                                    value="{{ old('source_material_name') }}"></textarea>
                                <button type="button" class = "btn btn-success" id = "add_source_material"
                                    data-toggle="modal" data-target="#selectSourceModal">
                                    <i class="fas fa-plus"></i>
                                </button>
                                <input type="hidden" class="form-control" name="material_source_id"
                                    value="{{ old('material_source_id') }}" />
                            </div>

                            <label class ="mt-3">Lô Thẩm Định</label>
                            <div class="card ">
                                <div class="card-body">
                                <div class="row">
                                    <div class="col-md-4">        
                                        <div class="icheck-primary d-inline">
                                            <input type="checkbox" class="step-checkbox" id="checkbox1" 
                                                name = "first_val_batch">
                                            <label for="checkbox1">Lô thứ nhất</label>
                                            <input type="text" name="batchNo1" class ="batchNo updateInput" value="{{ old('batchNo1') }}"  readonly/>
                                            <input type="hidden" name="code_val_first" value="{{ old('code_val_first') }}"/>
                                        </div>
                                    </div>
                                    <div class="col-md-4"> 
                                        <div class="icheck-primary d-inline">
                                            <input type="checkbox" class="step-checkbox" id="checkbox2" 
                                                name = "second_val_batch">
                                            <label for="checkbox2">Lô thứ hai</label>
                                            <span  class = "batchNo batchNo2" ></span>
                                        </div>
                                    </div>
                                    <div class="col-md-4"> 
                                        <div class="icheck-primary d-inline">
                                            <input type="checkbox" class="step-checkbox" id="checkbox3" 
                                                name = "third_val_batch">
                                            <label for="checkbox3">Lô thứ ba</label>
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
                                    <input type="date" class="form-control" data-inputmask-alias="datetime"
                                        name = "expected_date" data-inputmask-inputformat="dd/mm/yyyy" data-mask
                                        value="{{ old('expected_date', \Carbon\Carbon::now()->addMonth(2)->format('Y-m-d')) }}">
                                </div>
                            </div>

                            <div class="card card-success">
                                <div class="card-header">
                                    <h3 class="card-title">Mức Độ Ưu Tiên</h3>
                                </div>
                                <div class="card-body">
                                    <!-- Minimal style -->
                                    <div class="row">
                                        <div class="col-sm-12 mb-1">
                                            <div class="form-group clearfix">
                                                <div class="icheck-danger d-inline">
                                                    <input type="radio" id="radioDanger" name="level"
                                                        value = "1"
                                                        {{ old('level') == 1 || old('level') === null ? 'checked' : '' }}>
                                                    <label for="radioDanger">
                                                        1: Hàng Gấp, Hàng Thầu
                                                    </label>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-sm-12 mb-1">
                                            <div class="form-group clearfix">
                                                <div class="icheck-warning d-inline">
                                                    <input type="radio" id="radioWarning" name="level"
                                                        value = "2" {{ old('level') == 2 ? 'checked' : '' }}>
                                                    <label for="radioWarning">
                                                        2: Hàng Gấp, Hàng sắp hết số đăng ký
                                                    </label>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-sm-12 mb-1">
                                            <div class="form-group clearfix">
                                                <div class="icheck-primary d-inline">
                                                    <input type="radio" id="radioPrimary" name="level"
                                                        value = "3" {{ old('level') == 3 ? 'checked' : '' }}>
                                                    <label for="radioPrimary">
                                                        3: Hàng SX dự trù theo kế hoạch bán hàng, đăng ký thuốc
                                                    </label>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-sm-12 mb-1">
                                            <div class="form-group clearfix">
                                                <div class="icheck-success d-inline">
                                                    <input type="radio" id="radioSuccess" name="level"
                                                        value = "4" {{ old('level') == 4 ? 'checked' : '' }}>
                                                    <label for="radioSuccess">
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

                    {{-- Ngày ràng buột --}}
                    <div class="row">
                        <div class="col-md-6">
                        <label>Ngày có đủ nguyên liệu Pha Chế (Nếu chưa xác định thì để trống)</label>
                            <div class="input-group">
                                <input type="date" class="form-control" data-inputmask-alias="datetime"
                                    name = "after_weigth_date" data-inputmask-inputformat="dd/mm/yyyy" data-mask id = "after_weigth_date"
                                    value="{{ old('after_weigth_date')}}">
                            </div>
                        </div>


                        <div class="col-md-6">
                            <label>Ngày có đủ bao bì đóng gói (Nếu chưa xác định thì để trống)</label>
                            <div class="input-group">
                                <input type="date" class="form-control" data-inputmask-alias="datetime"
                                    name = "after_parkaging_date" data-inputmask-inputformat="dd/mm/yyyy" data-mask
                                    value="{{ old('after_parkaging_date')}}">
                            </div>
                        </div>

                    </div>

                    {{--  Ngày được phép cân - hết hạn nguyên liệu --}}
                    <div class="row mt-2">
                        <div class="col-md-6">
                        <label>Ngày được phép cân (Mặc định theo ngày có đủ NL)</label>
                            <div class="input-group">
                                <input type="date" class="form-control" data-inputmask-alias="datetime"
                                    name = "allow_weight_before_date" data-inputmask-inputformat="dd/mm/yyyy" data-mask data-mask id = "allow_weight_before_date"
                                    value="{{ old('allow_weight_before_date') }}">
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label> Ngày hết hạn nguyên liệu (Nếu có) </label>
                            <div class="input-group">
                                <input type="date" class="form-control" data-inputmask-alias="datetime"
                                    name = "expired_material_date" data-inputmask-inputformat="dd/mm/yyyy" data-mask
                                    value="{{ old('expired_material_date') }}">
                            </div>
                        </div>
                    </div>      
                    
                    {{-- Cân Trước ngày --}}
                    <div class="row mt-2">
                        <div class="col-md-4">
                            <label>Pha chế trước ngày (Nếu có)</label>
                                <div class="input-group">
                                    <input type="date" class="form-control" data-inputmask-alias="datetime"
                                        name = "preperation_before_date" data-inputmask-inputformat="dd/mm/yyyy" data-mask
                                        value="{{ old('preperation_before_date') }}">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label>Trộn hoàn tất trước ngày (Nếu có)</label>
                            <div class="input-group">
                                <input type="date" class="form-control" data-inputmask-alias="datetime"
                                    name = "blending_before_date" data-inputmask-inputformat="dd/mm/yyyy" data-mask
                                    value="{{ old('blending_before_date') }}">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label>Bao Phim trước ngày (nếu có)</label>
                            <div class="input-group">
                                <input type="date" class="form-control" data-inputmask-alias="datetime"
                                    name = "coating_before_date" data-inputmask-inputformat="dd/mm/yyyy" data-mask
                                    value="{{ old('coating_before_date') }}">
                            </div>
                        </div>
                    </div>  


                    {{-- Ghi chú --}}
                    <div class="row mt-2">
                        <div class="col-md-12">
                            <label>Ghi Chú (nếu có)</label>
                            <textarea class="form-control" name="note" rows="2"></textarea>
                        </div>
                    </div>


                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
                    <button type="submit" class="btn btn-primary" id="btnSave">
                        Lưu
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>


{{-- //Show modal nếu có lỗi validation --}}
@if ($errors->create_finished_Errors->any())
    <script>
        $(document).ready(function() {
            $('#createModal').modal('show');
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
        $('#createModal').on('shown.bs.modal', function() {
            $("input[data-bootstrap-switch]").each(function() {
                $(this).bootstrapSwitch('state', $(this).prop('checked'));
            });
        });


        $("#number_of_unit").on('input', function() {
            let numberOfUnit = parseInt($(this).val()) || 0;

            // Lấy batch_qty và lọc chỉ lấy số
            let batchQtyStr = $("#max_number_of_unit").val();

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

                $("#percent_packaging").val((numberOfUnit / batchQty).toFixed(4))
            }
        });

        $('#selectSourceModal').on('show.bs.modal', function(e) {
            const button = $(e.relatedTarget);
            const modal = $('#createModal');
            const intermediateCode = modal.find('input[name="intermediate_code"]').val() || "";
            $('#source_material_list').DataTable().search(intermediateCode).draw();
        })


        $("#createModal .step-checkbox").on("change", function() {
          
            let batch = $('#createModal input[name="batch"]').val();
            let checkbox1 = $("#createModal #checkbox1").is(":checked") ? 1 : 0;
            let checkbox2 = $("#createModal #checkbox2").is(":checked") ? 1 : 0;
            let checkbox3 = $("#createModal #checkbox3").is(":checked") ? 1 : 0;
            let code_val = $('input[name="code_val_first"]').val()|| null;
            let intermediate_code = $('input[name="intermediate_code"]').val()|| "" ;
            let first_batch_modal =  $('#tbody_first_val_batch')
            let total = checkbox1 + checkbox2 + checkbox3;

            if (checkbox1 == 1 && !batch || batch.trim() === '') {
                Swal.fire({
                    icon: 'warning',
                    title: 'Nhập số lô trước khi chọn lô thẩm định',
                    confirmButtonText: 'OK'
                });
                $('input[name="batchNo1"]').val('');
                $('input[name="code_val_first"]').val('');
                $("#createModal #checkbox1").prop("checked", false);
                $("#createModal #checkbox2").prop("checked", false);
                $("#createModal #checkbox3").prop("checked", false);
                return;
            }

            if (total == 0){
                $('input[name="batchNo1"]').val('');
                $('input[name="code_val_first"]').val('');
            }
                        
            $('input[name="number_of_batch"]').val(total);

            if (checkbox1 == 1 && code_val == null ){

                $.ajax({
                    url: "{{ route('pages.plan.production.get_last_id') }}",
                    type: 'post',
                    data: {
                        table: "plan_master",
                        _token: "{{ csrf_token() }}"
                    },
                    success: function(res) {
                      
                        $('input[name="batchNo1"]').val(batch);
                        $('input[name="code_val_first"]').val((res.last_id + 1) + "_1");
                    },
                    error: function(xhr, status, error) {
                            console.error("❌ Lỗi Ajax:", {
                                status: status,
                                error: error,
                                responseText: xhr.responseText
                            });
                        }
                });

                         
                return;

            }
            

            if ((checkbox1 == 0 && checkbox2 == 1 && code_val == null) || (checkbox1 == 0 && checkbox2 == 0 && checkbox3 == 1 && code_val == null)){
                first_batch_modal.empty();
                $.ajax({
                    url: "{{ route('pages.plan.production.first_batch') }}",
                    type: 'post',
                    data: {
                        intermediate_code: intermediate_code,
                        _token: "{{ csrf_token() }}"
                    },
                    success: function(res) {
          

                        if (res.length === 0) {
                            first_batch_modal.append(
                                `<tr><td colspan="13" class="text-center">Không có lịch sử</td></tr>`
                            );
                        } else {
                            res.forEach((item, index) => {
                                // map màu level
                                const colors = {
                                    1: 'background-color: #f44336; color: white;', // đỏ
                                    2: 'background-color: #ff9800; color: white;', // cam
                                    3: 'background-color: blue; color: white;', // xanh dương
                                    4: 'background-color: #4caf50; color: white;', // xanh lá
                                };
                                first_batch_modal.append(`
                              <tr>
                                  <td>${index + 1}</td>
                                  <td class="${index === 0 ? 'text-success' : 'text-danger'}""> 
                                      <div>${item.intermediate_code ?? ''}</div>
                                      <div>${item.finished_product_code ?? ''}</div>
                                  </td>

                                  <td>${item.name ?? ''} (${item.batch_qty ?? ''} ${item.unit_batch_qty ?? ''})</td>
                                  <td>${item.batch ?? ''}</td>
                                  <td>
                                      <div>${item.market ?? ''}</div>
                                      <div>${item.specification ?? ''}</div>
                                  </td>

                                    <td>
                                      <div>${item.expected_date ? moment(item.expected_date).format('DD/MM/YYYY') : ''}</div>
                                  </td>

                                  <td style="text-align: center; vertical-align: middle;">
                                      <span style="display: inline-block; padding: 6px 10px; width: 50px; border-radius: 40px; ${colors[item.level] ?? ''}">
                                          <b>${item.level ?? ''}</b>
                                      </span>

                                  </td>

                                  <td class="text-center align-middle">
                                      ${item.is_val ? '<i class="fas fa-check text-primary fs-4"></i>' : ''}
                                      <br>
                                       <span class="fw-bold text-success">Lô thứ ${item.code_val ? item.code_val.split('_')[1] ?? '' : ''}</span>
                                  </td>

                                  <td>${item.source_material_name ?? ''}</td>

                                  <td>
                                      <div>${item.after_weigth_date ? moment(item.after_weigth_date).format('DD/MM/YYYY') : ''}</div>
                                  </td>
                                  <td>
                                      <div>${item.after_parkaging_date ? moment(item.after_parkaging_date).format('DD/MM/YYYY') : ''}</div>   
                                  </td>

                                  <td>${item.note ?? ''}</td>
                                <td>
                                    <button type="button" class="btn btn-success btn-confirm-first-batch" 
                                    data-id="${item.id}"
                                    data-code_val="${item.code_val}"
                                    data-batch="${item.batch}"

                                    data-dismiss="modal">
                                    <i class="fas fa-plus"></i>
                                    </button>
                                </td>
                            
                              </tr>
                          `);
                            });
                        }
                    },
                    error: function(xhr, status, error) {
                            console.error("❌ Lỗi Ajax:", {
                                status: status,
                                error: error,
                                responseText: xhr.responseText
                            });
                            first_batch_modal.empty().append(
                                `<tr><td colspan="13" class="text-center text-danger">Lỗi tải dữ liệu</td></tr>`
                            );
                        }
                });

                $('#fist_batch_modal').modal('show');
            }

        });

        $("#after_weigth_date").on("change", function () {
            $("#allow_weight_before_date").val($(this).val());
        });


        preventDoubleSubmit("#createModal", "#btnSave");


    });
</script>
