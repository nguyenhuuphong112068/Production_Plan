
<link href="{{ asset('css/bootstrap.min.css') }}" rel="stylesheet">

<!-- Modal -->
<div class="modal fade" id="update_modal" tabindex="-1" role="dialog" aria-labelledby="productNameModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        
        <form action="{{ route('pages.category.product.update') }}" method="POST">
            @csrf

            <div class="modal-content">
                <div class="modal-header">
                    <a href="{{ route('pages.general.home') }}">
                        <img src="{{ asset('img/iconstella.svg') }}" style="opacity: 0.8; max-width:45px;">
                    </a>

                    <h4 class="modal-title w-100 text-center" id="productNameModalLabel" style="color: #CDC717">
                        Cập Nhật Danh Mục Sản Phẩm Công Đoạn Đóng Gói
                    </h4>

                    <button type="button" class="close" data-dismiss="modal" aria-label="Đóng">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>

                <div class="modal-body">
                    {{-- Mã Sản Phẩm --}}
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="intermediate_code">Mã Bán Thành Phẩm</label>
                                <input type="text" class="form-control" name="intermediate_code" value="{{ old('intermediate_code') }} " readonly>
                                @error('intermediate_code', 'updateErrors')
                                    <div class="alert alert-danger">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="finished_product_code">Mã Thành Phẩm</label>
                                <input type="text" class="form-control" name="finished_product_code" value="{{ old('finished_product_code') }}" readonly>
                                @error('finished_product_code', 'updateErrors')
                                    <div class="alert alert-danger">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                    
                    <input type="hidden" class="form-control" name="id" value="{{ old('id') }}" readonly>
                    {{-- NAME --}}
                    <div class="form-group">
                        <label for="name">Tên Sản Phẩm Theo BPR</label>
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
                     

                    {{--Cở lô--}}
                    <div class="row">
                        <div class="col-md-6">
                           
                            <div class="form-group">
                                <div class="row">
                                    <div class="col-md-8">
                                        <label for="batch_size">Cỡ Lô Theo Khối Lượng</label>
                                        <input type="number" min = "0" class="form-control" name="batch_size" value="{{ old('batch_size') }}" readonly>
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
                                        <input type="number" min = "0" class="form-control" name="batch_qty" value="{{ old('batch_qty') }}"  > 
                                        @error('batch_qty', 'updateErrors')
                                            <div class="alert alert-danger">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-4">
                                        <label for="unit_batch_qty">Đơn Vị</label>
                                        <input type="text" class="form-control" name="unit_batch_qty" value="{{ old('unit_batch_qty') }}" readonly>
                                        @error('unit_batch_qty', 'updateErrors')
                                            <div class="alert alert-danger">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Thị Trường - Qui Cách --}}
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="market_id"> Thị Trường </label>
                                 <select class="form-control" name="market_id" >
                                    <option> - Chọn Thị Trường - </option>
                                    @foreach ($markets as $market)
                                        <option value="{{ $market->id }}"
                                            {{ old('market_id') == $market->id ? 'selected' : '' }}>
                                            {{ $market->code}}
                                        </option>
                                    @endforeach
                                </select>
                                @error('market_id', 'updateErrors')
                                    <div class="alert alert-danger">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="specification_id">Qui Cách</label>
                                 <select class="form-control" name="specification_id" >
                                    <option> - Chọn Qui Cách - </option>
                                    @foreach ($specifications as $specification)
                                        <option value="{{ $specification->id }}"
                                            {{ old('specification_id') == $specification->id ? 'selected' : '' }}>
                                            {{ $specification->name}}
                                        </option>
                                    @endforeach
                                </select>
                                @error('specification_id', 'updateErrors')
                                    <div class="alert alert-danger">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>  

                    <div class="row">
                        <div class="col-md-6">
                            <label>Công Đoạn Bao Gồm</label>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group border p-2 rounded">
                                <!-- Cân Nguyên Liệu -->
                                <div class="form-group row align-items-center mb-2">
                                    <div class="col-md-6">
                                        <div class="icheck-primary">
                                            <input type="checkbox" class="step-checkbox" id="update_checkbox1" checked name = "primary_parkaging">
                                            <label for="update_checkbox1">ĐGSC - ĐGTC</label>
                                        </div>
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
       
        // Set process_code
        const intermediateInput = $('input[name="intermediate_code"]');
        const finishedInput = $('input[name="finished_product_code"]');
        const processInput = $('input[name="process_code"]');

        finishedInput.on('input change', function () {
            const intermediateCode = intermediateInput.val() || "";
            const finishedCode = $(this).val() || "";
            processInput.val(intermediateCode + "_" + finishedCode);
        });
         
    });
    
</script>
