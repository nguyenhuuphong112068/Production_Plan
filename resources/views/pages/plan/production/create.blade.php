<link href="{{ asset('css/bootstrap.min.css') }}" rel="stylesheet">
<style>
    .createModal-modal-size {
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
                        <input type="hidden" name="product_caterogy_id" readonly
                            value="{{ old('product_caterogy_id') }}" />
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
                                <input type="text" class="form-control" name="batch"
                                    value="{{ old('batch') }}" />
                                <input type="number" min="1" step="1" class="form-control"
                                    name="number_of_batch" value="{{ old('number_of_batch', 1) }}"
                                    oninput="this.value = this.value.replace(/[^0-9]/g, '')">
                                <span class="input-group-text p-0" style="width: 105px">
                                    <input type="checkbox" name="format_batch_no" checked data-bootstrap-switch>
                                </span>
                            </div>

                            <label>% Đóng gói</label>
                            <div class="input-group">
                                <!-- number_of_unit -->
                                <input type="hidden" name="max_number_of_unit" id ="max_number_of_unit">

                                <input type="number" class="form-control" name="number_of_unit" id="number_of_unit"
                                    placeholder="số lượng đóng gói" min="1" value="{{ old('number_of_unit')}}">

                                <!-- percent_packaging -->
                                <input type="number" step="0.01" min="0" max="1"
                                    placeholder="phần trăm đóng gói" readonly class="form-control"
                                    name="percent_packaging" id="percent_packaging"
                                    value="{{ old('percent_packaging', 1) }}">
                            </div>


                            <label>Nguồn</label>
                            <div class="input-group">
                                <textarea class="form-control" name="source_material_name" rows="4" value="{{ old('source_material_name') }}"></textarea>
                                <button type="button" class = "btn btn-success" id = "add_source_material" data-toggle="modal" data-target="#selectSourceModal"> 
                                    <i class="fas fa-plus"></i>
                                </button>
                                <input type="hidden" class="form-control" name="material_source_id" value="{{ old('material_source_id') }}" />
                            </div>
                            

                            {{-- Lô thẩm định  --}}
                            <div class="form-group px-3 mt-4">
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" class="custom-control-input" id="customSwitch1" name ="is_val">
                                    <label class="custom-control-label" for="customSwitch1">Ba Lô Thẩm Định Ban Đầu</label>
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
                                                    <input type="radio" id="radioDanger" name="level" value = "1" {{ old('level') == 1 ||  old('level') === null ? 'checked':'' }}>
                                                    <label for="radioDanger">
                                                        1: Hàng Gấp, Hàng Thầu
                                                    </label>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-sm-12">
                                            <div class="form-group clearfix">
                                                <div class="icheck-warning d-inline">
                                                    <input type="radio" id="radioWarning" name="level" value = "2" {{ old('level') == 2 ? 'checked':'' }}>
                                                    <label for="radioWarning">
                                                        2: Hàng Gấp, Hàng sắp hết số đăng ký
                                                    </label>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-sm-12">
                                            <div class="form-group clearfix">
                                                <div class="icheck-primary d-inline">
                                                    <input type="radio" id="radioPrimary" name="level" value = "3" {{ old('level') == 3 ? 'checked':'' }}>
                                                    <label for="radioPrimary">
                                                        3: Hàng SX dự trù theo kế hoạch bán hàng, đăng ký thuốc
                                                    </label>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-sm-12">
                                            <div class="form-group clearfix">
                                                <div class="icheck-success d-inline">
                                                    <input type="radio" id="radioSuccess" name="level" value = "4" {{ old('level') == 4 ? 'checked':'' }}>
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

                    <div class="row">
                        <div class="col-md-3">
                            <label>Có thể cân từ ngày</label>
                                <div class="input-group">
                                    <input type="date" class="form-control" data-inputmask-alias="datetime" name = "after_weigth_date"
                                        data-inputmask-inputformat="dd/mm/yyyy" data-mask value="{{ old('after_weigth_date', date('Y-m-d')) }}"> 
                                </div>
                        </div>
                        
                        <div class="col-md-3">
                            <label>Cân Trước ngày</label>
                                <div class="input-group">
                                    <input type="date" class="form-control" data-inputmask-alias="datetime" name = "before_weigth_date"
                                        data-inputmask-inputformat="dd/mm/yyyy" data-mask value="{{ old('before_weigth_date', \Carbon\Carbon::now()->addYear()->format('Y-m-d')) }}">
                                </div>
                        </div>
                        <div class="col-md-3">
                            <label>Có thể ĐG từ ngày</label>
                                <div class="input-group">
                                    <input type="date" class="form-control" data-inputmask-alias="datetime" name = "after_parkaging_date"
                                        data-inputmask-inputformat="dd/mm/yyyy" data-mask value="{{ old('after_parkaging_date', date('Y-m-d')) }}">
                                </div>
                        </div>
                        <div class="col-md-3">
                            <label>Đóng gói trước ngày</label>
                                <div class="input-group">
                                    <input type="date" class="form-control" data-inputmask-alias="datetime" name = "before_parkaging_date"
                                        data-inputmask-inputformat="dd/mm/yyyy" data-mask value="{{ old('before_parkaging_date', \Carbon\Carbon::now()->addYear()->format('Y-m-d')) }}">
                                </div>
                        </div>
                    </div>


                    {{-- Ghi chú --}}
                    <div class="row mt-3" >
                        <div class="col-md-12">
                            <textarea class="form-control" name="note" rows="4"></textarea>
                        </div>
                    </div>



                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
                        <button type="submit" class="btn btn-primary" id="btnSave">
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

        // truyền ma btp vào ô search 
        $('#selectSourceModal').on('show.bs.modal', function (e) {
            const button = $(e.relatedTarget); // nút mở modal
            const intermediateCode = button.data('intermediate_code');
            
            // Giả sử bạn có input search trong modal
            $(this).find('input[type="search"]').val(intermediateCode).trigger('input');
            
            // Hoặc nếu bạn dùng DataTables trong modal
            $('#source_table').DataTable().search(intermediateCode).draw();
        });

         preventDoubleSubmit("#createModal", "#btnSave");
         
         
    });
    
</script>
