<link href="{{ asset('css/bootstrap.min.css') }}" rel="stylesheet">

<!-- Modal -->
<div class="modal fade" id="createHistoryModal" tabindex="-1" role="dialog"  aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">

        <form action="{{ route('pages.Schedual.finished') }}" method="POST">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <a href="{{ route('pages.general.home') }}">
                        <img src="{{ asset('img/iconstella.svg') }}" style="opacity: 0.8; max-width:45px;">
                    </a>
                    <h4 class="modal-title w-100 text-center" id="" style="color: #CDC717">
                        Xác Nhận Hoàn Thành Kiểm Nghiệm Mẫu
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

                    <div class="form-group">
                        <div class="row">
                            <div class="col-md-9">
                                <label for="name">Số Lô - Chỉ Tiêu - Công Đoạn</label>
                                <input type="text" id="name" name="Batch_Testing_Stage" class="form-control" readonly value="{{ old('Batch_Testing_Stage') }}">
                            </div>

                            <div class="col-md-3">
                                <label for="name">Thời Gian Kiểm</label>
                                <input type="text" id="excution_time" name="excution_time" class="form-control" readonly value="{{ old('excution_time') }}">
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <div class="row">
                            <div class="col-md-6">
                                <label for="code">Ngày Trả Kết Quả</label>
                                <input type="text" id="experted_date" name="experted_date" class="form-control" readonly value="{{ old('experted_date') }}">
                            </div>
                            <div class="col-md-6">
                                <label for="name">Số Lượng Nhận</label>
                                <input type="text" id="imoported_amount" name="imoported_amount" class="form-control" readonly value="{{ old('imoported_amount') }}">
                            </div>
                        </div>
                    </div>

                    <!-- Ngày trả kết quả, số lượng nhận, đơn vị -->
                    <div class="form-group">
                        <div class="row">
                            <div class="col-md-6">
                                <label for="analyst">Kiểm Nghiệm Viên</label>
                                <select class="form-control" name="analyst" id="analyst">
                                    <option value="">-- Chọn Kiểm Nghiệm Viên --</option>
                                    @foreach ($analysts as $analyst)
                                        <option value="{{ $analyst->fullName }}"
                                            {{ old('analyst') == $analyst->fullName ? 'selected' : '' }}>
                                            {{ $analyst->fullName }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('analyst', 'createErrors')
                                    <div class="alert alert-danger">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label for="imoported_amount">Thiết Bị</label>
                                <select class="form-control" name="ins_Id" id="ins_Id">
                                    <option value="">-- Chọn Thiết Bị --</option>
                                    @foreach ($instruments as $instrument)
                                        
                                        {{-- @if ($instrument->instrument_type === request()->get('instrument_type')) --}}
                                        <option value="{{ $instrument->id }}"
                                            {{ old('ins_Id') === $instrument->id ? 'selected' : ''}}>
                                            {{ $instrument->name }}
                                        </option>
                                        {{-- @endif --}}
                                    @endforeach
                                </select>
                                @error('ins_Id', 'createErrors')
                                    <div class="alert alert-danger">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <div class="row">
                            <div class="col-md-6">
                                <label for="imoported_amount">Thời Gian Bắt Đầu Kiểm</label>
                                <input type="datetime-local" id="startDate" name="startDate" class="form-control" value="{{ old('startDate') ?? date('Y-m-d\TH:i') }}" min="{{ date('Y-m-d') }}">
                                @error('startDate', 'createErrors')
                                    <div class="alert alert-danger">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label for="endDate">Thời Gian Kết Thúc Kiểm</label>
                                <input type="datetime-local" id="endDate" name="endDate" class="form-control" value="{{ old('endDate') }}" min="{{ date('Y-m-d') }}">
                                @error('endDate', 'createErrors')
                                    <div class="alert alert-danger">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <div class="row">
                            <div class="col-md-6">
                                <label for="imoported_amount">Kết Quả</label>
                                 <select class="form-control" name="result" id="result">
                                    <option value="Đạt Tiêu Chuẩn">Đạt Tiêu Chuẩn</option>
                                    <option value="KHông Đạt Tiêu Chuẩn">KHông Đạt Tiêu Chuẩn</option>
                                </select>
                                @error('result', 'createErrors')
                                    <div class="alert alert-danger">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label for="relativeReport">Số Báo Cáo Liên Quan</label>
                                <input type="text" name="relativeReport" class="form-control" value="{{ old('endDate') }}" >
                                @error('relativeReport', 'createErrors')
                                    <div class="alert alert-danger">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>



                    <div class="form-group">
                        <div class="row">
                            <div class="col-md-12">
                                <label for="note">Ghi Chú</label>
                                <input type="text" id="note" name="note" class="form-control" value="{{ old('note') }}">
                                @error('note', 'createErrors')
                                    <div class="alert alert-danger">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>



                    <!-- Hidden field -->
                    {{-- <input type="hidden" name="instrument_type" value="{{ request()->get('instrument_type') }}"> --}}

                    <input type="hidden" name="schedual_id" value="{{ old('schedual_id') }}">

                    @error('schedual_id', 'createErrors')
                            <div class="alert alert-danger">{{ $message }}</div>
                     @enderror

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

@if ($errors->createHistoryErrors->any())
    {{ dd ($errors->createHistoryErrors); }}
    <script>
        $(document).ready(function() {
            $('#createHistoryModal').modal('show');
        });
    </script>
@endif
