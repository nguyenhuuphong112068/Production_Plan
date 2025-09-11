<link href="{{ asset('css/bootstrap.min.css') }}" rel="stylesheet">
<style>
    .update_modal-size {
        max-width: 50% !important;
        width: 50% !important;
    }

    .bootstrap-switch {
        height: 50%;
        display: flex;
        align-items: center;
        /* căn giữa theo chiều dọc */
    }
</style>


<!-- Modal -->
<div class="modal fade" id="update_modal" tabindex="-1" role="dialog" aria-labelledby="ModalLabel" aria-hidden="true">
    <div class="modal-dialog update_modal-size" role="document">

        <form action="{{ route('pages.plan.maintenance.update') }}" method="POST">
            @csrf

            <div class="modal-content">
                <div class="modal-header">


                    <a href="{{ route('pages.general.home') }}">
                        <img src="{{ asset('img/iconstella.svg') }}" style="opacity: 0.8 ; max-width:45px;">
                    </a>

                    <h4 class="modal-title w-100 text-center" id="pModalLabel" style="color: #CDC717">
                        {{ 'Tạo Kế Hoạch Hiệu Chuẩn Bảo Trì' }}
                    </h4>

                    <button type="button" class="close" data-dismiss="modal" aria-label="Đóng">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>

                <div class="modal-body">
                    <input type="hidden"  name="id"  value="{{ old('id') }}" />
                    {{-- San Phẩm --}}
                    <div class ="row">
                        <div class = "col-md-3">
                            <div class="form-group">
                                <label>Mã Thiết Bị</label>
                                <input type="text" class="form-control" name="code" readonly
                                    value="{{ old('code') }}" />
                            </div>
                        </div>
                        <div class = "col-md-9">
                            <div class="form-group">
                                <label>Tên Thiết Bị</label>
                                <input type="text" class="form-control" name="name" readonly
                                    value="{{ old('name') }}" />
                            </div>
                        </div>
                        <input type="hidden" name="plan_list_id" readonly value="{{ $plan_list_id }}" />
                        <input type="hidden" name="maintenance_category_ids" readonly value="{{ old('maintenance_category_ids') }}" />


                    </div>
                    <div class="row">
                        <div class="col-md-9">
                            <div class="form-group">
                                <label>Phòng Sản Xuất Liên Quan</label>
                                <input type="text" class="form-control" name="rooms" readonly
                                    value="{{ old('rooms') }}" />
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Thực Hiện Trước Ngày </label>
                                <input type="date" class="form-control" name="expected_date"  
                                    value="{{ old('expected_date') }}" />
                                @error('expected_date', 'create_Errors')
                                    <div class="alert alert-danger">{{ $message }}</div>
                                @enderror 
                            </div>
                        </div>
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
              
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
                        <button type="submit" class="btn btn-primary" id="update_btnSave">
                            Lưu
                        </button>
                    </div>

                </div>
            </div>
        </form>
    </div>
</div>


{{-- //Show modal nếu có lỗi validation --}}
@if ($errors->update_finished_Errors->any())
    <script>
        $(document).ready(function() {
            $('#update_modal').modal('show');
        });
    </script>
@endif


<script>
    $(document).ready(function() {
         preventDoubleSubmit("#update_modal", "#update_btnSave");
         
    });
    
</script>
