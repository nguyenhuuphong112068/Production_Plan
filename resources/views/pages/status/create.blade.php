

<!-- Modal -->
<div class="modal fade " id="Modal" tabindex="-1" role="dialog" aria-labelledby="ModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
   
    <form 
      action="{{route('pages.status.store')}}" 
      method="POST">
      @csrf

      <div class="modal-content">
        <div class="modal-header">
          <a href="{{ route ('pages.general.home') }}">
              <img src="{{ asset('img/iconstella.svg') }}" style="opacity: 0.8 ; max-width:45px;">
          </a>

          <h4 class="modal-title w-100 text-center" id="pModalLabel" style="color: #CDC717">
              {{'Cập Nhật Trạng Thái Phòng Sản Xuất' }}
          </h4>

          <button type="button" class="close" data-dismiss="modal" aria-label="Đóng">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>

        <div class="modal-body">
          
          <div class="form-group">
            <label for="name">Phòng Sản Xuất</label>
            <input type="text" class="form-control" name="room_name" readonly
              value="{{ old('room_name') }}">
          </div>

          {{-- NAME --}}
          <div class="form-group">
            <label for="name">Tên Sản Phẩm</label>
            <input type="text" class="form-control" name="name" 
              value="{{ old('name') }}">
          </div>
          @error('name', 'createErrors')
              <div class="alert alert-danger">{{ $message }}</div>
          @enderror

          {{-- SHORT NAME --}}
          <div class="form-group">
            <label for="batch">Số Lô</label>
            <input type="text" class="form-control" name="batch" 
              value="{{ old('batch') }}">
          </div>
          @error('batch', 'createErrors')
              <div class="alert alert-danger">{{ $message }}</div>
          @enderror

          <div class="card card-success">
              <div class="card-header">
                <h3 class="card-title">Trạng Thái Phòng Sản Xuất</h3>
              </div>
              <div class="card-body">

                <!-- Minimal red style -->
                <div class="row">

                  <div class="col-sm-6">
                    <div class="form-group clearfix">
                      <div class="icheck-success d-inline">
                        <input type="radio" name="status" checked id="status1">
                        <label for="status1">
                          Đang Sản Xuất
                        </label>
                      </div>
                    </div>
                    <div class="form-group clearfix">
                      <div class="icheck-success d-inline">
                        <input type="radio" name="status" checked id="status2">
                        <label for="status2">
                          Đang Vệ Sinh
                        </label>
                      </div>
                    </div>
                  </div>

                <div class="col-sm-6">
                    <div class="form-group clearfix">
                      <div class="icheck-success d-inline">
                        <input type="radio" name="status" checked id="status3">
                        <label for="status3">
                          Bảo Trì
                        </label>
                      </div>
                    </div>
                
                    <div class="form-group clearfix">
                      <div class="icheck-success d-inline">
                        <input type="radio" name="status" checked id="status4">
                        <label for="status4">
                          Không Sản Xuất
                        </label>
                      </div>
                    </div>

                  </div>

                </div>
              </div>
          </div>

          <div class="row mt-3">
            <div class="col-md-12">
            <label>Thông báo</label>
            <textarea class="form-control" name="notification" rows="2"></textarea>
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


{{-- //Show modal nếu có lỗi validation --}}
@if ($errors->createErrors->any())
<script>
    $(document).ready(function () {
        $('#Modal').modal('show');
    });
</script>
@endif
