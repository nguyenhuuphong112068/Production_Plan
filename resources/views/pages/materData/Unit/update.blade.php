

<!-- Modal -->
<div class="modal fade" id="update_modal" tabindex="-1" role="dialog" aria-labelledby="ModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
   
    <form 

      action="{{route('pages.materData.Unit.update')}}" 
      method="POST">
      @csrf

      <div class="modal-content">
        <div class="modal-header">
          <a href="{{ route ('pages.general.home') }}">
              <img src="{{ asset('img/iconstella.svg') }}" style="opacity: 0.8 ; max-width:45px;">
          </a>

          <h4 class="modal-title w-100 text-center" style="color: #CDC717">
              {{'Cập Nhật Dữ Liệu Gốc Đơn Vị Tính' }}
          </h4>

          <button type="button" class="close" data-dismiss="modal" aria-label="Đóng">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>

        <div class="modal-body">
             {{-- NAME --}}
            <div class="form-group">
              <label for="code">Đơn Vị Tính </label>
              <input type="text" class="form-control" name="code"  placeholder="vd: Kg"
                value="{{ old('code') }}">
            </div>
            @error('code', 'updateErrors')
                <div class="alert alert-danger">{{ $message }}</div>
            @enderror

            {{-- NAME --}}
            <div class="form-group">
              <label for="name">Đơn Vị Tính Đầy Đủ </label>
              <input type="text" class="form-control" name="name"  placeholder="vd: Kilogram"
                value="{{ old('name') }}">
            </div>
            @error('name', 'updateErrors')
                <div class="alert alert-danger">{{ $message }}</div>
            @enderror
        </div>

        <input type="hidden" class="form-control" name="id" value="{{ old('id') }}">

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
@if ($errors->updateErrors->any())
<script>
    $(document).ready(function () {
        $('#update_modal').modal('show');
    });
</script>
@endif



@if (session('success'))
<script>
    Swal.fire({
        title: 'Thành công!',
        text: '{{ session('success') }}',
        icon: 'success',
        timer: 1000, // tự đóng sau 2 giây
        showConfirmButton: false
    });
</script>
@endif
