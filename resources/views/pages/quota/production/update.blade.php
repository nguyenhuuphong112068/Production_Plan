
<style>
  .custom-modal-size {
    max-width: 60% !important;
    width: 60% !important;
  }
</style>

<!-- Modal -->
<div class="modal fade" id="update_modal" tabindex="-1" role="dialog" aria-labelledby="ModalLabel" aria-hidden="true">
  <div class="modal-dialog custom-modal-size" role="document">
   
    <form 
      action="{{route('pages.quota.production.update')}}" 
      method="POST">
      @csrf

      <div class="modal-content">
        <div class="modal-header">

           
          <a href="{{ route ('pages.general.home') }}">
              <img src="{{ asset('img/iconstella.svg') }}" style="opacity: 0.8 ; max-width:45px;">
          </a>

          <h4 class="modal-title w-100 text-center" id="uModalLabel" style="color: #CDC717">
              {{'Cập Nhật Định Mức Sản Xuất'}}
          </h4>

          <button type="button" class="close" data-dismiss="modal" aria-label="Đóng">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>

        <div class="modal-body">

            
            {{-- San Phẩm--}}
            <div class="row">
              <div class="col-md-9">
                <div class="form-group">
                    <label >Tên Sản Phẩm</label>
                    <input type="text" class="form-control" name="product_name" readonly value="{{ old('product_name') }}" />
                </div>
              </div>
              <div class="col-md-3">

                <label for="code">Mã Sản Phẩm</label>

                <input type="hidden" name="id" value="{{ old('id') }}"/>
                <input type="text" class="form-control" name="intermediate_code"  readonly  value="{{ old('intermediate_code') }}"/>
                <input type="text" class="form-control" name="finished_product_code"  readonly  value="{{ old('finished_product_code') }}"/>
              </div>
            </div>

          {{-- PHòng Sản Xuất --}}  
          <div class="row">          
            <div class="col-md-12">
                <div class="form-group">
                <label>Phòng Sản Xuất</label>
                <input type="text" class="form-control" name="room_id" readonly value="{{ old('room_id') }}" readonly  />
                </div>
            </div>
          </div>

          {{-- Thời Gian --}}
          <div class="row">          
            <div class="col-md-3 col-sm-6">
              <div class="form-group">
                <label for="p_time">Thời Gian Chuẩn Bi</label>
                <input type="text" class="form-control" name="p_time" 
                  value="{{ old('p_time') }}"
                  placeholder="HH:mm" 
                  pattern="^(?:\d{1,2}|1\d{2}|200):(00|15|30|45)$"  
                  title="Nhập giờ hợp lệ"
                  >
              </div>
              @error('p_time', 'update_Errors')
                  <div class="alert alert-danger">{{ $message }}</div>
              @enderror 
            </div>       
         
            <div class="col-md-3 col-sm-6">
              <div class="form-group">
                <label for="m_time">Thời Gian Sản Xuất</label>
                <input type="text" class="form-control" name="m_time" 
                  value="{{ old('m_time') }}"
                  placeholder="HH:mm" 
                  pattern="^(?:\d{1,2}|1\d{2}|200):(00|15|30|45)$"  
                  title="Nhập giờ hợp lệ">
              </div>
              @error('m_time', 'update_Errors')
                  <div class="alert alert-danger">{{ $message }}</div>
              @enderror 
            </div>

            <div class="col-md-3 col-sm-6">
              <div class="form-group">
                <label for="C1_time">Vệ Sịnh Cấp I</label>
                <input type="text" class="form-control" name="C1_time" 
                  value="{{ old('C1_time') }}"
                  placeholder="HH:mm" 
                  pattern="^(?:\d{1,2}|1\d{2}|200):(00|15|30|45)$"  
                  title="Nhập giờ hợp lệ">
              </div>
              @error('C1_time', 'update_Errors')
                  <div class="alert alert-danger">{{ $message }}</div>
              @enderror 
            </div>

            <div class="col-md-3 col-sm-6">
              <div class="form-group">
                <label for="C2_time">Vệ Sịnh Cấp II</label>
                <input type="text" class="form-control" name="C2_time" 
                  value="{{ old('C2_time') }}"
                  placeholder="HH:mm" 
                  pattern="^(?:\d{1,2}|1\d{2}|200):(00|15|30|45)$"  
                  title="Nhập giờ hợp lệ">
              </div>
              @error('C2_time', 'update_Errors')
                  <div class="alert alert-danger">{{ $message }}</div>
              @enderror 
            </div>
          </div>

          {{-- Biệt Trữ--}}
          <div class="row">          
            <div class="col-md-3 col-sm-6">
              <div class="form-group">
                <label for="code">Số lô chiện dịch tối đa</label>
                <input type="number" class="form-control" name="maxofbatch_campaign" 
                      value="{{ old('maxofbatch_campaign') }}" min="1" step="1" 
                      placeholder="Nhập số nguyên dương">
              </div>
              @error('maxofbatch_campaign', 'update_Errors')
                  <div class="alert alert-danger">{{ $message }}</div>
              @enderror 
            </div>

            <div class="col-md-9 col-sm-6">
              <div class="form-group">
                <label for="note">Ghi Chú</label>
                <input type="text" class="form-control" name="note" 
                      value="{{ old('note') }}"
                      placeholder="Ghi chú nếu có">
              </div>
              @error('note', 'update_Errors')
                  <div class="alert alert-danger">{{ $message }}</div>
              @enderror 
            </div>
          </div>

        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
          <button type="submit" class="btn btn-primary" id = "btnUpdate">
              Lưu
          </button>
        </div>
      </div>
    </form>
  </div>
</div>


{{-- //Show modal nếu có lỗi validation --}}
@if ($errors->update_Errors->any())
<script>
    $(document).ready(function () {
        $('#update_modal').modal('show');
    });
</script>
@endif




<script>
  $(document).ready(function() {
      preventDoubleSubmit("#update_modal", "#btnUpdate");
          
  });
</script>