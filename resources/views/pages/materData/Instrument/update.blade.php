
 <link href="{{ asset('css/bootstrap.min.css') }}" rel="stylesheet"> 
<!-- Modal -->
<div class="modal fade" id="UpdateModal" tabindex="-1" role="dialog" aria-labelledby="ModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
   
  
    <form action="{{ route('pages.materData.Instrument.update') }}" method="POST">
      @csrf

      <div class="modal-content">
        <div class="modal-header">


          <a href="{{ route ('pages.general.home') }}">
              <img src="{{ asset('img/iconstella.svg') }}" style="opacity: 0.8 ; max-width:45px;">
          </a>

          <h4 class="modal-title w-100 text-center" id="ModalLabel" style="color: #CDC717">
              Cập Nhật Tên Sản Phẩm
          </h4>


          <button type="button" class="close" data-dismiss="modal" aria-label="Đóng">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>

          {{-- ID --}}
        <input type="hidden" class="form-control" name="id" value="">

        <div class="modal-body">
              {{-- CODE --}}
          <div class="form-group">
            <label for="code">Mã Thiết Bị</label>
            <input type="text" class="form-control" name="code" 
              value="{{ old('code') }}">
          </div>
          @error('code', 'updateErrors')
              <div class="alert alert-danger">{{ $message }}</div>
          @enderror

          {{-- NAME --}}
          <div class="form-group">
            <label for="name">Thiết Bị Kiểm Nghiệm</label>
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

         {{-- Instrement Type --}}
          <div class="form-group">
            <label for="instrument_type">Loại Thiết Bị</label>
            <input type="text" class="form-control" name="instrument_type" 
                value="{{ old('instrument_type') }}">
          </div>
          @error('instrument_type', 'createErrors')
              <div class="alert alert-danger">{{ $message }}</div>
          @enderror

          {{-- Gourp_ID--}}
          <div class="form-group">
              <label for="belongGroup_id">Tổ Quản Lý</label>
              <select class="form-control" name="belongGroup_id" id="belongGroup_id">
                    <option value="">-- Chọn nhóm --</option>
                    @foreach ($groups as $group)
                        <option value="{{ $group->id }}" 
                            {{ old('belongGroup_id') == $group->id ? 'selected' : '' }}>
                            {{ $group->name }}
                        </option>
                    @endforeach

              </select>
              @error('belongGroup_id', 'updateErrors')
                    <div class="alert alert-danger mt-1">{{ $message }}</div>
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
        $('#UpdateModal').modal('show');
    });
</script>
@endif
