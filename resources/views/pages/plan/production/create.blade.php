<link href="{{ asset('css/bootstrap.min.css') }}" rel="stylesheet">
<style>
    .createModal-modal-size {
        max-width: 50% !important;
        width: 50% !important;
    }
    .bootstrap-switch {
    height: 100%;
    display: flex;
    align-items: center; /* căn giữa theo chiều dọc */
    }
</style>

<!-- Modal -->
<div class="modal fade" id="createModal" tabindex="-1" role="dialog" aria-labelledby="ModalLabel" aria-hidden="true">
    <div class="modal-dialog createModal-modal-size" role="document">

        <form action="{{ route('pages.quota.production.store') }}" method="POST">
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
                        <input type="hidden" name="product_caterogy_id" readonly value="{{ old('product_caterogy_id') }}" />
                        <input type="hidden" name="plan_list_id" readonly value="{{ old('plan_list_id') }}" />
                      
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
                                <input type="text" class="form-control" name="batch"  value="{{ old('batch') }}" />
                                <input type="number" min="1" step="1" class="form-control" name="number_of_batch" 
                                                value="{{ old('number_of_batch') }}" oninput="this.value = this.value.replace(/[^0-9]/g, '')">
                                <span class="input-group-text p-0" style="width: 105px">
                                    <input type="checkbox" name="my-checkbox" checked data-bootstrap-switch >
                                </span>  
                            </div>

                            <label>% Đóng gói</label>
                            <div class="input-group">
                                <!-- number_of_unit -->
                                <input type="hidden" name="max_number_of_unit" id ="max_number_of_unit" >

                                <input type="number" class="form-control" name="number_of_unit" id="number_of_unit" placeholder="số lượng đóng gói" 
                                    min="1"
                                    value="{{ old('number_of_unit') }}">

                                <!-- percent_packaging -->
                                <input type="number" step="0.01" min="0" max="1" placeholder="phần trăm đóng gói" disabled
                                    class="form-control" name="percent_packaging" id="percent_packaging"
                                    value="{{ old('percent_packaging') }}">
                            </div>
                            
                            
                            <label>Nguồn</label>
                            <div class="input-group">
                                <input type="text" class="form-control" name="batch"  value="{{ old('batch') }}" />
                                <button type="button" class = "btn btn-success"> <i class="fas fa-plus"></i> </button>
                            </div>

                            <div class="form-group">
                                <label>Ngày dự kiên KCS</label>
                                <div class="input-group">
                                    <input type="date" class="form-control" data-inputmask-alias="datetime" data-inputmask-inputformat="dd/mm/yyyy" data-mask>
                                </div>
                            </div>


                        </div>                                
                          
                    <div class="col-md-6">
                            <div class="form-group">
                                <label>Ngày dự kiên KCS</label>
                                <div class="input-group">
                                    <input type="date" class="form-control" data-inputmask-alias="datetime" data-inputmask-inputformat="dd/mm/yyyy" data-mask>
                                </div>
                            </div>

            <div class="card card-success">
              <div class="card-header">
                <h3 class="card-title">iCheck Bootstrap - Checkbox &amp; Radio Inputs</h3>
              </div>
              <div class="card-body">
                <!-- Minimal style -->
                <div class="row">
                  <div class="col-sm-6">
                    <!-- checkbox -->
                    <div class="form-group clearfix">
                      <div class="icheck-primary d-inline">
                        <input type="checkbox" id="checkboxPrimary1" checked>
                        <label for="checkboxPrimary1">
                        </label>
                      </div>
                      <div class="icheck-primary d-inline">
                        <input type="checkbox" id="checkboxPrimary2">
                        <label for="checkboxPrimary2">
                        </label>
                      </div>
                      <div class="icheck-primary d-inline">
                        <input type="checkbox" id="checkboxPrimary3" disabled>
                        <label for="checkboxPrimary3">
                          Primary checkbox
                        </label>
                      </div>
                    </div>
                  </div>
                  <div class="col-sm-6">
                    <!-- radio -->
                    <div class="form-group clearfix">
                      <div class="icheck-primary d-inline">
                        <input type="radio" id="radioPrimary1" name="r1" checked>
                        <label for="radioPrimary1">
                        </label>
                      </div>
                      <div class="icheck-primary d-inline">
                        <input type="radio" id="radioPrimary2" name="r1">
                        <label for="radioPrimary2">
                        </label>
                      </div>
                      <div class="icheck-primary d-inline">
                        <input type="radio" id="radioPrimary3" name="r1" disabled>
                        <label for="radioPrimary3">
                          Primary radio
                        </label>
                      </div>
                    </div>
                  </div>
                </div>
                <!-- Minimal red style -->
                <div class="row">
                  <div class="col-sm-6">
                    <!-- checkbox -->
                    <div class="form-group clearfix">
                      <div class="icheck-danger d-inline">
                        <input type="checkbox" checked id="checkboxDanger1">
                        <label for="checkboxDanger1">
                        </label>
                      </div>
                      <div class="icheck-danger d-inline">
                        <input type="checkbox" id="checkboxDanger2">
                        <label for="checkboxDanger2">
                        </label>
                      </div>
                      <div class="icheck-danger d-inline">
                        <input type="checkbox" disabled id="checkboxDanger3">
                        <label for="checkboxDanger3">
                          Danger checkbox
                        </label>
                      </div>
                    </div>
                  </div>
                  <div class="col-sm-6">
                    <!-- radio -->
                    <div class="form-group clearfix">
                      <div class="icheck-danger d-inline">
                        <input type="radio" name="r2" checked id="radioDanger1">
                        <label for="radioDanger1">
                        </label>
                      </div>
                      <div class="icheck-danger d-inline">
                        <input type="radio" name="r2" id="radioDanger2">
                        <label for="radioDanger2">
                        </label>
                      </div>
                      <div class="icheck-danger d-inline">
                        <input type="radio" name="r2" disabled id="radioDanger3">
                        <label for="radioDanger3">
                          Danger radio
                        </label>
                      </div>
                    </div>
                  </div>
                </div>
                <!-- Minimal red style -->
                <div class="row">
                  <div class="col-sm-6">
                    <!-- checkbox -->
                    <div class="form-group clearfix">
                      <div class="icheck-success d-inline">
                        <input type="checkbox" checked id="checkboxSuccess1">
                        <label for="checkboxSuccess1">
                        </label>
                      </div>
                      <div class="icheck-success d-inline">
                        <input type="checkbox" id="checkboxSuccess2">
                        <label for="checkboxSuccess2">
                        </label>
                      </div>
                      <div class="icheck-success d-inline">
                        <input type="checkbox" disabled id="checkboxSuccess3">
                        <label for="checkboxSuccess3">
                          Success checkbox
                        </label>
                      </div>
                    </div>
                  </div>
                  <div class="col-sm-6">
                    <!-- radio -->
                    <div class="form-group clearfix">
                      <div class="icheck-success d-inline">
                        <input type="radio" name="r3" checked id="radioSuccess1">
                        <label for="radioSuccess1">
                        </label>
                      </div>
                      <div class="icheck-success d-inline">
                        <input type="radio" name="r3" id="radioSuccess2">
                        <label for="radioSuccess2">
                        </label>
                      </div>
                      <div class="icheck-success d-inline">
                        <input type="radio" name="r3" disabled id="radioSuccess3">
                        <label for="radioSuccess3">
                          Success radio
                        </label>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
              <!-- /.card-body -->
              <div class="card-footer">
                Many more skins available. <a href="https://bantikyan.github.io/icheck-bootstrap/">Documentation</a>
              </div>
            </div>




                      </div>
                    </div>

                <div class="row">
                    <div class="col-md-12">
                       <textarea class="form-control" name="note" rows="4" ></textarea>
                    </div>
                </div>



                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
                        <button type="submit" class="btn btn-primary">
                            Lưu
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
    $("input[data-bootstrap-switch]").each(function(){
        $(this).bootstrapSwitch('state', $(this).prop('checked'));
    });

    // Nếu muốn khi modal mở mới khởi tạo
    $('#createModal').on('shown.bs.modal', function () {
        $("input[data-bootstrap-switch]").each(function(){
            $(this).bootstrapSwitch('state', $(this).prop('checked'));
        });
    });
    


    $("#number_of_unit").on('input', function () {
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
        }
        else {
           
            $("#percent_packaging").val(( numberOfUnit/batchQty).toFixed(4))
        }
    });


    });
</script>