
<!-- Modal -->
<div class="modal fade" id="productNameUpdateModal" tabindex="-1" role="dialog" aria-labelledby="uproductNameModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
   
    <form 
      action="{{route('pages.materData.source_material.update')}}" 
      method="POST">
      @csrf

      <div class="modal-content">
        <div class="modal-header">
          <a href="{{ route ('pages.general.home') }}">
              <img src="{{ asset('img/iconstella.svg') }}" style="opacity: 0.8 ; max-width:45px;">
          </a>

          <h4 class="modal-title w-100 text-center" style="color: #CDC717">
              {{'Cập Nhật Mới Nguồn API' }}
          </h4>

          <button type="button" class="close" data-dismiss="modal" aria-label="Đóng">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>

        <div class="modal-body">

          <input type="hidden" class="form-control" name="id" value="{{ old('id') }}">
          {{-- SHORT NAME --}}
          <div class="form-group">
            <label for="intermediate_code">Mã Bán Thành Phẩm</label>
            <input type="text" class="form-control" name="intermediate_code" disabled
              value="{{ old('intermediate_code') }}">
          </div>
          @error('intermediate_code', 'updateErrors')
              <div class="alert alert-danger">{{ $message }}</div>
          @enderror
        

          {{-- NAME --}}
          <div class="form-group">
            <label for="name">Nguồn API</label>
            <input type="text" class="form-control" name="name" 
              value="{{ old('name') }}">
          </div>
          @error('name', 'updateErrors')
              <div class="alert alert-danger">{{ $message }}</div>
          @enderror


        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
          <button type="submit" class="btn btn-primary">
            {{ isset($product) ? 'Cập Nhật' : 'Lưu' }}
          </button>
        </div>
      </div>
    </form>
  </div>
</div>


{{-- //Show modal nếu có lỗi validation --}}
@if ($errors->updateErrors->any())
<script>
    $(document).ready(function () {
        $('#productNameUpdateModal').modal('show');
    });
</script>
@endif
