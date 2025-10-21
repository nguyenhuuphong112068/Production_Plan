<link href="{{ asset('css/bootstrap.min.css') }}" rel="stylesheet">
<style>
    .splittingModal-modal-size {
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
<div class="modal fade" id="splittingModal" tabindex="-1" role="dialog" aria-labelledby="ModalLabel" aria-hidden="true">
    <div class="modal-dialog splittingModal-modal-size" role="document">

        <form class="form-splitting" action="{{ route('pages.plan.production.splitting') }}" method="POST">
            @csrf

            <div class="modal-content">
                <div class="modal-header">
                    <a href="{{ route('pages.general.home') }}">
                        <img src="{{ asset('img/iconstella.svg') }}" style="opacity: 0.8 ; max-width:45px;">
                    </a>

                    <h4 class="modal-title w-100 text-center" id="splittingModalLabel" style="color: #CDC717">
                        Chia Lô Đóng Gói
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
                                    value="{{ old('batch') }}"/>
                                <input type="number" min="1" step="1" class="form-control"
                                    name="number_of_batch" value="{{ old('number_of_batch', 1) }}"
                                    oninput="this.value = this.value.replace(/[^0-9]/g, '')" readonly >
                                <span class="input-group-text p-0" style="width: 105px">
                                    <input type="checkbox" name="format_batch_no" checked data-bootstrap-switch>
                                </span>
                            </div>

                            <label class="mt-2">% Đóng gói</label>
                            <div class="input-group">
                                <!-- number_of_unit -->
                                <input type="hidden" name="max_number_of_unit" id ="splitting_max_number_of_unit">

                                <input type="number" class="form-control" name="number_of_unit" id="splitting_number_of_unit"
                                    placeholder="số lượng đóng gói" min="1" value="{{ old('number_of_unit')}}">

                                <!-- percent_packaging -->
                                <input type="number" step="0.01" min="0" max="1"
                                    placeholder="phần trăm đóng gói" readonly class="form-control"
                                    name="percent_packaging" id="percent_packaging"
                                    value="{{ old('percent_packaging', 1) }}">
                            </div>


                            <label>Nguồn</label>
                            <div class="input-group">
                                <textarea class="form-control" name="source_material_name" rows="6" value="{{ old('source_material_name') }}" readonly></textarea>
                                {{-- <button type="button" class = "btn btn-success"  data-toggle="modal" data-target="#selectSourceModal"> 
                                    <i class="fas fa-plus"></i>
                                </button> --}}
                                <input type="hidden" class="form-control" name="material_source_id" value="{{ old('material_source_id') }}" />
                            </div>
                            {{-- Lô thẩm định --}}
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
                                                    <input type="radio" id="splitting_level_1" name="level" value = "1" {{ old('level') == 1 ||  old('level') === null ? 'checked':'' }}>
                                                    <label for="splitting_level_1">
                                                        1: Hàng Gấp, Hàng Thầu
                                                    </label>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-sm-12">
                                            <div class="form-group clearfix">
                                                <div class="icheck-warning d-inline">
                                                    <input type="radio" id="splitting_level_2" name="level" value = "2" {{ old('level') == 2 ? 'checked':'' }}>
                                                    <label for="splitting_level_2">
                                                        2: Hàng Gấp, Hàng sắp hết số đăng ký
                                                    </label>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-sm-12">
                                            <div class="form-group clearfix">
                                                <div class="icheck-primary d-inline">
                                                    <input type="radio" id="splitting_level_3" name="level" value = "3" {{ old('level') == 3 ? 'checked':'' }}>
                                                    <label for="splitting_level_3">
                                                        3: Hàng SX dự trù theo kế hoạch bán hàng, đăng ký thuốc
                                                    </label>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-sm-12">
                                            <div class="form-group clearfix">
                                                <div class="icheck-success d-inline">
                                                    <input type="radio" id="splitting_level_4" name="level" value = "4" {{ old('level') == 4 ? 'checked':'' }}>
                                                    <label for="splitting_level_4">
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
                            <label>Ngày có đủ NL</label>
                                <div class="input-group">
                                    <input type="date" class="form-control" data-inputmask-alias="datetime" name = "after_weigth_date"
                                        data-inputmask-inputformat="dd/mm/yyyy" data-mask value="{{ old('after_weigth_date', date('Y-m-d')) }}" readonly> 
                                </div>
                        </div>
                        
                        {{-- <div class="col-md-3">
                            <label>Cân Trước ngày</label>
                                <div class="input-group">
                                    <input type="date" class="form-control" data-inputmask-alias="datetime" name = "before_weigth_date"
                                        data-inputmask-inputformat="dd/mm/yyyy" data-mask value="{{ old('before_weigth_date', \Carbon\Carbon::now()->addYear()->format('Y-m-d')) }}" readonly>
                                </div>
                        </div> --}}
                        <div class="col-md-6">
                            <label>Ngày có đủ BB</label>
                                <div class="input-group">
                                    <input type="date" class="form-control" data-inputmask-alias="datetime" name = "after_parkaging_date"
                                        data-inputmask-inputformat="dd/mm/yyyy" data-mask value="{{ old('after_parkaging_date', date('Y-m-d')) }}" readonly>
                                </div>
                        </div>
                        {{-- <div class="col-md-3">
                            <label>Đóng gói trước ngày</label>
                                <div class="input-group">
                                    <input type="date" class="form-control" data-inputmask-alias="datetime" name = "before_parkaging_date"
                                        data-inputmask-inputformat="dd/mm/yyyy" data-mask value="{{ old('before_parkaging_date', \Carbon\Carbon::now()->addYear()->format('Y-m-d')) }}" readonly>
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


                    <div class="modal-footer" >
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
                        <button type="submit" class="btn btn-primary" id="splitting_btnSave">
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
@if ($errors->splitting_finished_Errors->any())
    <script>
        $(document).ready(function() {
            $('#splittingModal').modal('show');
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
        $('#splittingModal').on('shown.bs.modal', function() {
            $("input[data-bootstrap-switch]").each(function() {
                $(this).bootstrapSwitch('state', $(this).prop('checked'));
            });
        });



        $("#splitting_number_of_unit").on('input', function() {
            //alert ("sa")
            let numberOfUnit = parseInt($(this).val()) || 0;

            // Lấy batch_qty và lọc chỉ lấy số
            let batchQtyStr = $("#splitting_max_number_of_unit").val();

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

                $("#splitting_splitting_percent_packaging").val((numberOfUnit / batchQty).toFixed(4))
            }
        });
        
       

         preventDoubleSubmit("#splittingModal", "#splitting_btnSave");
         
    });
    
</script>
