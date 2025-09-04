
<link href="{{ asset('css/bootstrap.min.css') }}" rel="stylesheet">

<!-- Modal -->
<div class="modal fade" id="create_modal" tabindex="-1" role="dialog" aria-labelledby="ModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
   
    <form
      action="{{route('pages.materData.Instrument.store')}}" 
      method="POST">
      @csrf

      <div class="modal-content">
        <div class="modal-header">

           
          <a href="{{ route ('pages.general.home') }}">
              <img src="{{ asset('img/iconstella.svg') }}" style="opacity: 0.8 ; max-width:45px;">
          </a>

          <h4 class="modal-title w-100 text-center" id="pModalLabel" style="color: #CDC717">
              {{'Tạo Mới Dữ Liệu Gốc Thiết Bị Sản Xuất'}}
          </h4>

          <button type="button" class="close" data-dismiss="modal" aria-label="Đóng">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>

        <div class="modal-body">
          {{-- CODE --}}
            <div class="form-group">
              <label for="code">Mã Thiết Bị</label>
              <input type="text" class="form-control" name="code" 
                value="{{ old('code') }}">
            </div>
            @error('code', 'createErrors')
                <div class="alert alert-danger">{{ $message }}</div>
            @enderror

            {{-- NAME --}}
            <div class="form-group">
              <label for="name">Tên Thiết Bị</label>
              <input type="text" class="form-control" name="name" 
                value="{{ old('name') }}">
            </div>
            @error('name', 'createErrors')
                <div class="alert alert-danger">{{ $message }}</div>
            @enderror


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
@if ($errors->createErrors->any())
<script>
    $(document).ready(function () {
        $('#create_modal').modal('show');
    });
</script>
@endif
