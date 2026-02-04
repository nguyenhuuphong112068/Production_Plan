
<!-- Modal -->
<div class="modal fade" id="productNameModal" tabindex="-1" role="dialog" aria-labelledby="productNameModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
   
    <form 
      action="{{route('pages.materData.source_material.store')}}" 
      method="POST">
      @csrf

      <div class="modal-content">
        <div class="modal-header">
          <a href="{{ route ('pages.general.home') }}">
              <img src="{{ asset('img/iconstella.svg') }}" style="opacity: 0.8 ; max-width:45px;">
          </a>

          <h4 class="modal-title w-100 text-center" id="productNameModalLabel" style="color: #CDC717">
              {{'Tạo Mới Nguồn API' }}
          </h4>

          <button type="button" class="close" data-dismiss="modal" aria-label="Đóng">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>

        <div class="modal-body">

          {{-- SHORT NAME --}}
          <div class="form-group">
            <label for="intermediate_code">Mã Bán Thành Phẩm</label>
            <input type="text" class="form-control" name="intermediate_code" 
              value="{{ old('intermediate_code') }}">
          </div>
          @error('intermediate_code', 'createErrors')
              <div class="alert alert-danger">{{ $message }}</div>
          @enderror
        

          {{-- NAME --}}
          <div class="form-group">
            <label for="name">Nguồn API</label>
            <input type="text" class="form-control" name="name" 
              value="{{ old('name') }}">
          </div>
          @error('name', 'createErrors')
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
@if ($errors->createErrors->any())
<script>
    $(document).ready(function () {
        $('#productNameModal').modal('show');
    });
</script>
@endif
