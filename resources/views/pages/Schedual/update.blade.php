<link href="{{ asset('css/bootstrap.min.css') }}" rel="stylesheet">

<!-- Modal -->
<div class="modal fade" id="updateModal" tabindex="-1" role="dialog" aria-labelledby="productNameModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">

        <form action="{{ route('pages.Schedual.update') }}" method="POST">
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
                                <input type="text" name="code" class="form-control" readonly value="{{ old('code') }}">
                            </div>
                            <div class="col-md-9">
                                <label for="name">Tên Sản Phẩm</label>
                                <input type="text"  name="name" class="form-control" readonly value="{{ old('name') }}">
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <div class="row">
                            <div class="col-md-9">
                                <label for="name">Số Lô - Chỉ Tiêu - Công Đoạn</label>
                                <input type="text"  name="Batch_Testing_Stage" class="form-control" readonly value="{{ old('Batch_Testing_Stage') }}">
                            </div>

                            <div class="col-md-3">
                                <label for="name">Thời Gian Kiểm</label>
                                <input type="text" id ="update_excution_time" name="excution_time" class="form-control" readonly value="{{ old('excution_time') }}">
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <div class="row">
                            <div class="col-md-6">
                                <label for="code">Ngày Trả Kết Quả</label>
                                <input type="text"  name="experted_date" class="form-control" readonly value="{{ old('experted_date') }}">
                            </div>
                            <div class="col-md-6">
                                <label for="name">Số Lượng Nhận</label>
                                <input type="text"  name="imoported_amount" class="form-control" readonly value="{{ old('imoported_amount') }}">
                            </div>
                        </div>
                    </div>

                    <!-- Ngày trả kết quả, số lượng nhận, đơn vị -->
                    <div class="form-group">
                        <div class="row">
                            <div class="col-md-6">
                                <label for="update_analyst">Kiểm Nghiệm Viên</label>
                                <select class="form-control" name="analyst">
                                    
                                    {{-- <option value="">-- Chọn Kiểm Nghiệm Viên --</option> --}}
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
                                <label for="update_Ins_Id">Thiết Bị</label>
                                <select class="form-control" name="ins_Id" id="update_Ins_Id">
                                    
                                    @foreach ($instruments as $instrument)
                                        
                                        {{-- @if ($instrument->instrument_type === request()->get('instrument_type')) --}}
                                        <option value="{{ $instrument->id }}"
                                            {{ old('ins_Id') == $instrument->id ? 'selected' : '' }}>
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
                                <input type="datetime-local" id="update_startDate" name="startDate" class="form-control" value="{{ old('startDate') ?? date('Y-m-d\TH:i') }}" min="{{ date('Y-m-d') }}">
                                @error('startDate', 'createErrors')
                                    <div class="alert alert-danger">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label for="endDate">Thời Gian Kết Thúc Kiểm</label>
                                <input type="datetime-local" id="update_endDate" name="endDate" class="form-control" value="{{ old('endDate') }}">
                                @error('endDate', 'createErrors')
                                    <div class="alert alert-danger">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <div class="row">
                            <div class="col-md-12">
                                <label for="note">Ghi Chú</label>
                                <input type="text"  name="note" class="form-control" value="{{ old('note') }}">
                                @error('note', 'createErrors')
                                    <div class="alert alert-danger">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                    <!-- Hidden field -->
                    <input type="hidden" name="id" value="{{  old('id') }}">


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
@if ($errors->updateErrors->any())

    <script>
        $(document).ready(function() {
            $('#updateModal').modal('show');
        });
    </script>
@endif

<script>


    $(document).ready(function () {
        $('#update_startDate').on('change', function () {
            
            const execution_time = $('#update_excution_time').val(); 

            if (!execution_time || !execution_time.includes(' ')) {
                console.warn("Giá trị execution_time không hợp lệ:", execution_time);
                return;
            }
            
            const parts = execution_time.split(' ');
            const addedHours = parseInt(parts[0]) || 0;

            let startDate = new Date($(this).val());
            if (isNaN(startDate.getTime())) {
                console.warn("Ngày không hợp lệ:", $(this).val());
                return;
            }
                
            startDate.setHours(startDate.getHours() + addedHours);
            const formatted = startDate.toISOString().slice(0, 16); 
            // Gán giá trị vào input endDate
            $('#update_endDate').val(formatted);
        });


    });

</script>

