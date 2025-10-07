


<!-- Modal -->
<div class="modal fade" id="createModal" tabindex="-1" role="dialog" aria-labelledby="ModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
   
    <form 
      action="{{route('pages.materData.room.store')}}" 
      method="POST">
      @csrf

      <div class="modal-content">
        <div class="modal-header">

           
          <a href="{{ route ('pages.general.home') }}">
              <img src="{{ asset('img/iconstella.svg') }}" style="opacity: 0.8 ; max-width:45px;">
          </a>

          <h4 class="modal-title w-100 text-center" id="pModalLabel" style="color: #CDC717">
              {{'Tạo Mới Phòng Sản Xuất' }}
          </h4>

          <button type="button" class="close" data-dismiss="modal" aria-label="Đóng">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>

        <div class="modal-body">
          {{-- CODE --}}
            <div class="form-group">
              <label for="code">Mã Phòng</label>
              <input type="text" class="form-control" name="code" 
                value="{{ old('code') }}">
            </div>
            @error('code', 'createErrors')
                <div class="alert alert-danger">{{ $message }}</div>
            @enderror

            {{-- NAME --}}
            <div class="form-group">
              <label for="name">Tên Phòng</label>
              <input type="text" class="form-control" name="name" 
                value="{{ old('name') }}">
            </div>
            @error('name', 'createErrors')
                <div class="alert alert-danger">{{ $message }}</div>
            @enderror


            {{-- Stage --}}
            <div class="form-group">
                <label for="belongGroup_id">Công Đoạn</label>
                <select class="form-control" name="stage_code" >
                    <option value="">-- Chọn Công Đoạn --</option>
                    @foreach ($stages as $stage)
                        <option value="{{ $stage->code }}" 
                            {{ old('code') == $stage->code ? 'selected' : '' }}>
                            {{ $stage->name }}
                        </option>
                    @endforeach

                </select>
                @error('stage_code', 'createErrors')
                    <div class="alert alert-danger mt-1">{{ $message }}</div>
                @enderror
            </div>

            {{-- Stage_groups --}}
            <div class="form-group">
                <label for="belongGroup_id">Tổ Quản Lý</label>
                <select class="form-control" name="production_group" >
                    <option value="">-- Chọn nhóm --</option>

                    @foreach ($stage_groups as $stage_group)
                        <option value="{{ $stage_group->name }}" 
                            {{ old('production_group') == $stage_group->name ? 'selected' : '' }}>
                            {{ $stage_group->name }}
                        </option>
                    @endforeach

                </select>
                @error('production_group', 'createErrors')
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


{{-- //Show modal nếu có lỗi validation --}}
@if ($errors->createErrors->any())
<script>
    $(document).ready(function () {
        $('#createModal').modal('show');
    });
</script>
@endif
