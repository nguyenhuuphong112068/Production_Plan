
<link href="{{ asset('css/bootstrap.min.css') }}" rel="stylesheet">
<style>
  .custom-modal-size {
    max-width: 50% !important;
    width: 50% !important;
  }
</style>

<!-- Modal -->
<div class="modal fade" id="create_intermediate" tabindex="-1" role="dialog" aria-labelledby="ModalLabel" aria-hidden="true">
  <div class="modal-dialog custom-modal-size" role="document">
   
    <form 
      action="{{route('pages.quota.production.store')}}" 
      method="POST">
      @csrf

      <div class="modal-content">
        <div class="modal-header">

           
          <a href="{{ route ('pages.general.home') }}">
              <img src="{{ asset('img/iconstella.svg') }}" style="opacity: 0.8 ; max-width:45px;">
          </a>

          <h4 class="modal-title w-100 text-center" id="pModalLabel" style="color: #CDC717">
              {{'Định Mức Sản Xuất'}}
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
                    <select class="form-control" name="name" id="productName">
                      <option value="">-- Chọn Bán Thành Phẩm --</option>
                        @foreach ($intermediate_category as $item)
                  
                            <option value="{{ $item->intermediate_code }}" 
                                data-code="{{ $item->intermediate_code }}"
                                {{ old('intermediate_code') == $item->intermediate_code ? 'selected' : '' }}>
                                {{ $item->product_name . " - " . $item->intermediate_code}}
                            </option>
                      
                        @endforeach
                    </select>
                    @error('intermediate_code', 'create_inter_Errors')
                        <div class="alert alert-danger mt-1">{{ $message }}</div>
                    @enderror

                </div>
              </div>
              <div class="col-md-3">

                <label for="code">Mã Sản Phẩm</label>
                <input type="text" class="form-control" name="intermediate_code" id ="code" readonly  value="{{ old('intermediate_code') }}"/>
              </div>
            </div>

          {{-- PHòng Sản Xuất --}}  
          <div class="row">          
            <div class="col-md-12">
                <div class="form-group">
                  <label>Phòng Sản Xuất</label>
                    <select class="select2" multiple="multiple" data-placeholder="Select a State" 
                            style="width: 100%; height:30mm" name="room_id[]">
                        @foreach ($room as $item)

                            <option value="{{ $item->id }}" 
                                {{ collect(old('room_id', []))->contains($item->id) ? 'selected' : '' }}>
                                {{ $item->code . " - " . $item->name }}
                            </option>
                            
                        @endforeach
                    </select>
                @error('room_id', 'create_inter_Errors')
                  <div class="alert alert-danger">{{ $message }}</div>
                @enderror 
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
              @error('p_time', 'create_inter_Errors')
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
              @error('m_time', 'create_inter_Errors')
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
              @error('C1_time', 'create_inter_Errors')
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
              @error('C2_time', 'create_inter_Errors')
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
              @error('maxofbatch_campaign', 'create_inter_Errors')
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
              @error('note', 'create_inter_Errors')
                  <div class="alert alert-danger">{{ $message }}</div>
              @enderror 
            </div>
          </div>


          <input type="hidden" name="stage_code" value="{{ old('stage_code') }}">
          

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

{{-- //Show modal nếu có lỗi validation --}}
@if ($errors->create_inter_Errors->any())
<script>
    $(document).ready(function () {
        $('#create_intermediate').modal('show');
    });
</script>
@endif


<script>
document.addEventListener('DOMContentLoaded', function () {
    
    const intermediate = document.getElementById('productName');
    const intermediateCodeInput = document.getElementById('code');

    // Khi chọn bán thành phẩm → cập nhật code vào cả 2 input
    intermediate.addEventListener('change', function () {
        const option = intermediate.options[intermediate.selectedIndex];
        const code = option.getAttribute('data-code') || '';
        intermediateCodeInput.value = code;
       
    });

  
});
</script>
