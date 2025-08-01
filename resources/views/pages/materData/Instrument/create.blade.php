
<link href="{{ asset('css/bootstrap.min.css') }}" rel="stylesheet">

<!-- Modal -->
<div class="modal fade" id="Modal" tabindex="-1" role="dialog" aria-labelledby="ModalLabel" aria-hidden="true">
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
              {{'Tạo Mới Chỉ Tiêu Kiểm' }}
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

            {{-- Instrement Type --}}
            <div class="form-group">
              <label for="name">Loại Thiết Bị</label>
              <input type="text" class="form-control" name="instrument_type" 
                value="{{ old('instrument_type') }}">
            </div>
            @error('instrument_type', 'createErrors')
                <div class="alert alert-danger">{{ $message }}</div>
            @enderror

            {{-- SHORT NAME --}}
            <div class="form-group">
              <label for="shortName">Tên viết tắt</label>
              <input type="text" class="form-control" name="shortName" 
                value="{{ old('shortName') }}">
            </div>
            @error('shortName', 'createErrors')
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
                @error('belongGroup_id', 'createErrors')
                    <div class="alert alert-danger mt-1">{{ $message }}</div>
                @enderror
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

<!-- Scripts -->
<script src="{{ asset('js/vendor/jquery-1.12.4.min.js') }}"></script>
<script src="{{ asset('js/popper.min.js') }}"></script>
<script src="{{ asset('js/bootstrap.min.js') }}"></script>

{{-- //Show modal nếu có lỗi validation --}}
@if ($errors->createErrors->any())
<script>
    $(document).ready(function () {
        $('#Modal').modal('show');
    });
</script>
@endif
