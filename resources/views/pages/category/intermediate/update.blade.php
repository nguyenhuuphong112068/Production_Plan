
<link href="{{ asset('css/bootstrap.min.css') }}" rel="stylesheet">

<!-- Modal -->
<div class="modal fade" id="update_modal" tabindex="-1" role="dialog" aria-labelledby="productNameModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        
        <form action="{{ route('pages.category.intermediate.update') }}" method="POST">
            @csrf

            <div class="modal-content">
                <div class="modal-header">
                    <a href="{{ route('pages.general.home') }}">
                        <img src="{{ asset('img/iconstella.svg') }}" style="opacity: 0.8; max-width:45px;">
                    </a>

                    <h4 class="modal-title w-100 text-center" id="productNameModalLabel" style="color: #CDC717">
                        Cập Nhật Danh Mục Sản Phẩm Công Đoạn Sản Xuất
                    </h4>

                    <button type="button" class="close" data-dismiss="modal" aria-label="Đóng">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>

                <div class="modal-body">

                    {{-- NAME --}}
                    <div class="form-group">
                        <label for="name">Tên Sản Phẩm</label>
                        <select class="form-control" name="product_name_id" >
                            <option> --- Chọn Sản Phẩm --- </option>
                            @foreach ($productNames as $productName)
                                <option value="{{ $productName->id }}"
                                    {{ old('product_name_id') == $productName->id ? 'selected' : '' }}>
                                    {{ $productName->name}}
                                </option>
                            @endforeach
                        </select>
                        @error(' product_name_id', 'updateErrors')
                            <div class="alert alert-danger mt-1">{{ $message }}</div>
                        @enderror
                    </div> 
                    <input type="hidden" class="form-control" name="id" value="{{ old('id') }}">
    
                    {{-- Mã TBP và Dạng Bào Chế--}}
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="intermediate_code">Mã Bán Thành Phẩm</label>
                                <input type="text" class="form-control" name="intermediate_code" value="{{ old('intermediate_code') }}" readonly>
                                @error('intermediate_code', 'updateErrors')
                                    <div class="alert alert-danger">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="unit">Dạng Bào Chế</label>
                                <select class="form-control" name="dosage_id" >
                                    <option> --- Chọn Dạng Bào Chế --- </option>
                                    @foreach ($dosages as $dosage)
                                        <option value="{{ $dosage->id }}"
                                            {{ old('dosage_id') == $dosage->id ? 'selected' : '' }}>
                                            {{ $dosage->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('dosage_id', 'updateErrors')
                                    <div class="alert alert-danger mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>


                    {{--Cở lô--}}
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <div class="row">
                                    <div class="col-md-8">
                                        <label for="batch_size">Cỡ Lô Theo Khối Lượng</label>
                                        <input type="number" min = "0" class="form-control" name="batch_size" value="{{ old('batch_size') }}">
                                        @error('batch_size', 'updateErrors')
                                            <div class="alert alert-danger">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    
                                    <div class="col-md-4">
                                        <label for="unit_batch_size">Đơn Vị</label>
                                        <input type="text" class="form-control" name="unit_batch_size" value="Kg" readonly>
                                    </div>
                                </div>
                            </div>

                        </div>
                        
                        <div class="col-md-6">
                            <div class="form-group">
                                <div class="row">
                                    <div class="col-md-8">
                                        <label for="batch_qty">Cỡ Lô Theo Đơn Vị Liều </label>
                                        <input type="number" min = "0" class="form-control" name="batch_qty" value="{{ old('batch_qty') }}">
                                        @error('batch_qty', 'updateErrors')
                                            <div class="alert alert-danger">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-4">
                                        <label for="unit_batch_qty">Đơn Vị</label>
                                         <select class="form-control" name="unit_batch_qty" >
                                            <option> - Chọn ĐV - </option>
                                            @foreach ($units as $unit)
                                                <option value="{{ $unit->code }}"
                                                    {{ old('unit_batch_qty') == $unit->code ? 'selected' : '' }}>
                                                    {{ $unit->code}}
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('unit_batch_qty', 'updateErrors')
                                            <div class="alert alert-danger">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <label>Công Đoạn Bao Gồm</label>
                        </div>
                        <div class="col-md-6">
                            <div class="d-flex justify-content-between align-items-center">
                                <label class="mb-0">Thời Gian Biệt Trữ</label>
                                <input type="checkbox" name="quarantine_time_unit" checked data-bootstrap-switch>
                            </div>
                        </div>

                    </div>

                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group border p-2 rounded">

                                <!-- Cân Nguyên Liệu -->
                                <div class="form-group row align-items-center mb-2">
                                    <div class="col-md-6">
                                        <div class="icheck-primary">
                                            <input type="checkbox" class="step-checkbox" id="update_checkbox1"  name = "weight_1">
                                            <label for="update_checkbox1">Cân Nguyên Liệu</label>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <input type="number" min="0" class="form-control step-input" placeholder="Biệt trữ sau cân" name ="quarantine_weight">
                                    </div>
                                </div>

                                <!-- Pha Chế -->
                                <div class="form-group row align-items-center mb-2">
                                    <div class="col-md-6">
                                        <div class="icheck-primary">
                                            <input type="checkbox" class="step-checkbox" id="update_checkbox2"  name = "prepering">
                                            <label for="update_checkbox2">Pha Chế</label>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <input type="number" min="0" class="form-control step-input" placeholder="Biệt trữ sau pha chế" name ="quarantine_preparing">
                                    </div>
                                </div>

                                <!-- Trộn Hoàn Tất -->
                                <div class="form-group row align-items-center mb-2">
                                    <div class="col-md-6">
                                        <div class="icheck-primary">
                                            <input type="checkbox" class="step-checkbox" id="update_checkbox3"  name = "blending">
                                            <label for="update_checkbox3">Trộn Hoàn Tất</label>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <input type="number" min="0" class="form-control step-input" placeholder="Biệt trữ sau trộn hoàn tất" name ="quarantine_blending">
                                    </div>
                                </div>

                                <!-- Định Hình -->
                                <div class="form-group row align-items-center mb-2">
                                    <div class="col-md-6">
                                        <div class="icheck-primary">
                                            <input type="checkbox" class="step-checkbox" id="update_checkbox4"  name = "forming">
                                            <label for="update_checkbox4">Định Hình</label>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <input type="number" min="0" class="form-control step-input" placeholder="Biệt trữ sau định hình" name ="quarantine_forming">
                                    </div>
                                </div>

                                <!-- Bao Phim -->
                                <div class="form-group row align-items-center mb-2">
                                    <div class="col-md-6">
                                        <div class="icheck-primary">
                                            <input type="checkbox" class="step-checkbox" id="update_checkbox5"  name = "coating">
                                            <label for="update_checkbox5">Bao Phim</label>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <input type="number" min="0" class="form-control step-input" placeholder="Biệt trữ sau bao phim" name ="quarantine_coating">
                                    </div>
                                </div>

                                <!-- Tổng -->
                                <div class="form-group row align-items-center mb-2">
                                    <div class="col-md-6">
                                        <div class="icheck-danger">
                                            <input type="checkbox" class="step-checkbox" id="update_checkbox6" name ="quarantine_total_checked">
                                            <label for="update_checkbox6">Thời gian biệt trữ từ Cân đến trước ĐGSC</label>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <input type="number" min="0" class="form-control step-input" placeholder="Biệt trữ từ Cân - ĐGSC" name ="quarantine_total">
                                    </div>
                                </div>

                            </div>
                        </div>
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

<!-- Scripts -->
<script src="{{ asset('js/vendor/jquery-1.12.4.min.js') }}"></script>
<script src="{{ asset('js/popper.min.js') }}"></script>
<script src="{{ asset('js/bootstrap.min.js') }}"></script>

{{-- Tự động mở modal nếu có lỗi --}}
@if ($errors->updateErrors->any()) 
    <script>
       
        $(document).ready(function() {
            $('#update_modal').modal('show');
        });
    </script>
@endif

{{-- Gán mã chỉ tiêu tương ứng với chọn lựa --}}
<script>
    $(document).ready(function() {
        $("input[data-bootstrap-switch]").bootstrapSwitch({
            onText: 'Ngày',
            offText: 'Giờ',
            onColor: 'success',
            offColor: 'danger'
        });
        // Khi trang load
        $("input[data-bootstrap-switch]").each(function() {
            $(this).bootstrapSwitch('state', $(this).prop('checked'));
        });

        // Nếu muốn khi modal mở mới khởi tạo
        $('#update_modal').on('shown.bs.modal', function() {
            $("input[data-bootstrap-switch]").each(function() {
                $(this).bootstrapSwitch('state', $(this).prop('checked'));
            });
        });


        // Xử lý check
        function updateInputs() {
            if ($("#update_checkbox6").is(":checked")) {
               
                // Chỉ tác động input 1-5, không đổi trạng thái checkbox
                for (let i = 1; i <= 5; i++) {
                    const cb = $("#update_checkbox" + i);
                    const input = cb.closest(".form-group.row").find(".step-input");
                    input.val(0).prop("readonly", true);
                }
                $("#update_checkbox6").closest(".form-group.row").find(".step-input").prop("readonly", false);
            } else {
                // Quay lại logic cũ

                for (let i = 1; i <= 5; i++) {
                    const cb = $("#update_checkbox" + i);
                    const input = cb.closest(".form-group.row").find(".step-input");

                    if (cb.is(":checked")) {
                        input.prop("readonly", false);
                    } else {
                        input.val(0).prop("readonly", true);
                    }
                }
                $("#update_checkbox6").closest(".form-group.row").find(".step-input").val(0).prop("readonly", true);
            }
        }

        // Lắng nghe thay đổi của tất cả checkbox
        $(".step-checkbox, #update_checkbox6").on("change", function() {
            updateInputs();
        });

        // Chạy khi load trang
        updateInputs();

         
    });
    
</script>
