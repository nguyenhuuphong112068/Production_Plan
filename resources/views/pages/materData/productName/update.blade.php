
 <link href="{{ asset('css/bootstrap.min.css') }}" rel="stylesheet"> 
<!-- Modal -->
<div class="modal fade" id="productNameUpdateModal" tabindex="-1" role="dialog" aria-labelledby="productNameModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
   
  
    <form action="{{ route('pages.materData.productName.update') }}" method="POST">
      @csrf

      <div class="modal-content">
        <div class="modal-header">
          <a href="{{ route ('pages.general.home') }}">
              <img src="{{ asset('img/iconstella.svg') }}" style="opacity: 0.8 ; max-width:45px;">
          </a>

          <h4 class="modal-title w-100 text-center" id="productNameModalLabel" style="color: #CDC717">
              Cập Nhật Tên Sản Phẩm
          </h4>

          <input type="hidden" class="form-control" name="id" value="">

          <button type="button" class="close" data-dismiss="modal" aria-label="Đóng">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>

        <div class="modal-body">
          {{-- CODE --}}
          {{-- <div class="form-group">
            <label for="code">Mã Sản Phẩm</label>
            <input type="text" class="form-control" name="code" 
              value="{{ old('code') }}">
          </div>
          @error('code', 'updateErrors')
              <div class="alert alert-danger">{{ $message }}</div>
          @enderror --}}

          {{-- NAME --}}
          <div class="form-group">
            <label for="name">Tên Sản Phẩm</label>
            <input type="text" class="form-control" name="name" 
              value="{{ old('name') }}">
          </div>
          @error('name', 'updateErrors')
              <div class="alert alert-danger">{{ $message }}</div>
          @enderror

          {{-- SHORT NAME --}}
          <div class="form-group">
            <label for="shortName">Tên viết tắt</label>
            <input type="text" class="form-control" name="shortName" 
              value="{{ old('shortName') }}">
          </div>
          @error('shortName', 'updateErrors')
              <div class="alert alert-danger">{{ $message }}</div>
          @enderror

          {{-- PRODUCT TYPE --}}
          <div class="form-group">
            <label for="productType">Loại Sản Phẩm</label>
            <input type="text" class="form-control" name="productType" 
              value="{{ old('productType') }}">
          </div>
          @error('productType', 'updateErrors')
              <div class="alert alert-danger">{{ $message }}</div>
          @enderror
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
          <button type="submit" class="btn btn-primary">
                Cập Nhật
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
