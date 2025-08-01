
<link href="{{ asset('css/bootstrap.min.css') }}" rel="stylesheet">

<!-- Modal -->
<div class="modal fade" id="Modal" tabindex="-1" role="dialog" aria-labelledby="ModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
   
    <form 
      action="{{route('pages.materData.Analyst.store')}}" 
      method="POST">
      @csrf

      <div class="modal-content">
        <div class="modal-header">

           
          <a href="{{ route ('pages.general.home') }}">
              <img src="{{ asset('img/iconstella.svg') }}" style="opacity: 0.8 ; max-width:45px;">
          </a>

          <h4 class="modal-title w-100 text-center" id="pModalLabel" style="color: #CDC717">
              {{'Tạo Mới Dữ Liệu KNV' }}
          </h4>

          <button type="button" class="close" data-dismiss="modal" aria-label="Đóng">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>

        <div class="modal-body">
          {{-- CODE --}}
            <div class="form-group">
              <label for="code">Mã Số Nhân Viên</label>
              <input type="text" class="form-control" name="code" 
                value="{{ old('code') }}">
            </div>
            @error('code', 'createErrors')
                <div class="alert alert-danger">{{ $message }}</div>
            @enderror

            {{-- NAME --}}
            <div class="form-group">
              <label for="fullName">Tên Kiểm Nghiệm Viên</label>
              <input type="text" class="form-control" name="fullName" 
                value="{{ old('fullName') }}">
            </div>
            @error('name', 'createErrors')
                <div class="alert alert-danger">{{ $message }}</div>
            @enderror


            {{-- Gourp_ID--}}
            <div class="form-group">
                <label for="groupName">Tổ Quản Lý</label>
                <select class="form-control" name="groupName" id="groupName">
                    <option value="">-- Chọn nhóm --</option>

                    @foreach ($groups as $group)
                        <option value="{{ $group->name }}" 
                            {{ old('groupName') == $group->name ? 'selected' : '' }}>
                            {{ $group->name }}
                        </option>
                    @endforeach

                </select>
                @error('groupName', 'createErrors')
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
