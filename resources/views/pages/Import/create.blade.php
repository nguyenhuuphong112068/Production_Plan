<link href="{{ asset('css/bootstrap.min.css') }}" rel="stylesheet">

<!-- Modal -->
<div class="modal fade" id="createModal" tabindex="-1" role="dialog" aria-labelledby="productNameModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">

        <form action="{{ route('pages.Import.store') }}" method="POST">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <a href="{{ route('pages.general.home') }}">
                        <img src="{{ asset('img/iconstella.svg') }}" style="opacity: 0.8; max-width:45px;">
                    </a>
                    <h4 class="modal-title w-100 text-center" id="productNameModalLabel" style="color: #CDC717">
                        Nhận Mẫu
                    </h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Đóng">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>

                <div class="modal-body">

                    <!-- Mã và Tên sản phẩm -->
                    <div class="form-group">
                        <div class="row">
                            <div class="col-md-3">
                                <label for="code">Mã Sản Phẩm</label>
                                <input type="text" id="code" name="code" class="form-control" readonly value="{{ old('code') }}">
                            </div>
                            <div class="col-md-9">
                                <label for="name">Tên Sản Phẩm</label>
                                <input type="text" id="name" name="name" class="form-control" readonly value="{{ old('name') }}">
                            </div>
                        </div>
                    </div>

                    <!-- Chỉ tiêu kiểm, SL yêu cầu, Đơn vị -->
                    <div class="form-group">
                        <div class="row">
                            <div class="col-md-5">
                                <label for="testing">Chỉ Tiêu Kiểm</label>
                                <input type="text" id="testing" name="testing" class="form-control" readonly value="{{ old('testing') }}">
                            </div>
                            <div class="col-md-5">
                                <label for="sample_amout">Số lượng yêu cầu</label>
                                <input type="text" id="sample_amout" name="sample_amout" class="form-control" readonly value="{{ old('sample_amout') }}">
                            </div>
                            <div class="col-md-2">
                                <label for="unit">Đơn vị</label>
                                <input type="text" id="unit" name="unit" class="form-control" readonly value="{{ old('unit') }}">
                            </div>
                        </div>
                    </div>

                    <!-- Batch No & Stage -->
                    <div class="form-group">
                        <div class="row">
                            <div class="col-md-5">
                                <label for="batch_no">Số Lô</label>
                                <input type="text" id="batch_no" name="batch_no" class="form-control" value="{{ old('batch_no') }}">
                                @error('batch_no', 'createErrors')
                                    <div class="alert alert-danger">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-7">
                                <label for="stage">Công đoạn</label>
                                <input type="text" id="stage" name="stage" class="form-control" value="{{ old('stage') }}">
                                @error('stage', 'createErrors')
                                    <div class="alert alert-danger">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <!-- Ngày trả kết quả, số lượng nhận, đơn vị -->
                    <div class="form-group">
                        <div class="row">
                            <div class="col-md-5">
                                <label for="experted_date">Ngày Trả Kết Quả</label>
                                <input type="date" id="experted_date" name="experted_date" class="form-control" value="{{ old('experted_date') }}" min="{{ date('Y-m-d') }}">
                                @error('experted_date', 'createErrors')
                                    <div class="alert alert-danger">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-5">
                                <label for="imoported_amount">Số Lượng Nhận</label>
                                <input type="number" id="imoported_amount" name="imoported_amount" class="form-control" value="{{ old('imoported_amount') }}">
                                @error('imoported_amount', 'createErrors')
                                    <div class="alert alert-danger">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-2">
                                <label for="unit2">Đơn vị</label>
                                <input type="text" id="unit2" name="unit" class="form-control" readonly value="{{ old('unit') }}">
                            </div>
                        </div>
                    </div>

                    <!-- Hidden field -->
                    <input type="hidden" name="testing_code" value="{{ old('testing_code') }}">

                </div>

                <!-- Footer -->
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
                    <button type="submit" class="btn btn-primary">Lưu</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Scripts -->
<script src="{{ asset('js/vendor/jquery-1.12.4.min.js') }}"></script>
<script src="{{ asset('js/popper.min.js') }}"></script>
<script src="{{ asset('js/bootstrap.min.js') }}"></script>

<!-- Mở lại modal nếu có lỗi -->
@if ($errors->createErrors->any())
    <script>
        $(document).ready(function() {
            $('#createModal').modal('show');
        });
    </script>
@endif
