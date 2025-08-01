
<link href="{{ asset('css/bootstrap.min.css') }}" rel="stylesheet">

<!-- Modal -->
<div class="modal fade" id="UpdateModal" tabindex="-1" role="dialog" aria-labelledby="ModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
   
    <form 
      action="{{route('pages.User.update')}}" 
      method="POST">
      @csrf

      <div class="modal-content">
        <div class="modal-header">
          <a href="{{ route ('pages.general.home') }}">
              <img src="{{ asset('img/iconstella.svg') }}" style="opacity: 0.8 ; max-width:45px;">
          </a>

          <h4 class="modal-title w-100 text-center" id="ModalLabel" style="color: #CDC717">
              {{'Cập Nhật User' }}
          </h4>

          <button type="button" class="close" data-dismiss="modal" aria-label="Đóng">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>

        <div class="modal-body">
        
        <input type="hidden" class="form-control" name="id" value="">
        {{-- USER NAME --}}
              
          <div class="row">
              <div class="col-md-6">
                  <div class="form-group">
                    <label for="userName">Tên Đăng Nhập</label>
                    <input type="text" class="form-control" name="userName"  disabled
                      value="{{ old('userName') }}" placeholder="Mã Số Nhân Viên">
                  </div>
                  @error('userName','updateErrors')
                      <div class="alert alert-danger">{{ $message }}</div>
                  @enderror

              </div>
              <div class="col-md-6">
                  {{-- PW--}}
                  <div class="form-group">
                    <label for="passWord">Mật Khẩu</label>
                    <input type="text" class="form-control" name="passWord"  disabled 
                      value=" ***** ">
                  </div>
                  @error('passWord','updateErrors')
                      <div class="alert alert-danger">{{ $message }}</div>
                  @enderror
            </div>
          </div>

           {{-- Full Name--}}
            <div class="form-group">
              <label for="fullName">Tên Người Dùng</label>
              <input type="text" class="form-control" name="fullName"  placeholder="Tên Đầy Đủ"
                value="{{ old('fullName') }}">
            </div>
            @error('fullName','updateErrors')
                <div class="alert alert-danger">{{ $message }}</div>
            @enderror

            {{-- USER GROUP --}}
              <div class="form-group">
                  <label for="userGroupUpdate">Phân Quyền</label>
                  <select class="form-control" name="userGroup" id="userGroupUpdate">
                      <option value="">-- Chọn phân quyền --</option>
                      @foreach ($userGroups as $userGroup)
                          <option value="{{ $userGroup->name }}" 
                              {{ old('userGroup') == $userGroup->name ? 'selected' : '' }}>
                              {{ $userGroup->name }}
                          </option>
                      @endforeach
                  </select>
                  @error('userGroup','updateErrors')
                        <div class="alert alert-danger mt-1">{{ $message }}</div>
                  @enderror
              </div>


          <div class="row">
              <div class="col-md-6">
                <div class="form-group">

                  {{-- GROUP IN DEPARTMENT --}}
                    <label for="groupNameUpdate">Tổ</label>
                    <select class="form-control" name="groupName" id="groupNameUpdate">
                        <option value="">-- Chọn Tổ --</option>
                        @foreach ($groups as $group)
                            <option value="{{ $group->name }}" 
                                {{ old('groupName') == $group->name ? 'selected' : '' }}>
                                {{ $group->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('groupName','updateErrors')
                          <div class="alert alert-danger mt-1">{{ $message }}</div>
                    @enderror
                </div>               
              </div>
              
              <div class="col-md-6">
                  {{-- DEPARTMENT--}}
                  <div class="form-group">
                      <label for="deparmentUpdate">Phòng Ban</label>
                      <select class="form-control" name="deparment" id="deparmentUpdate">
                          <option value="">-- Chọn phòng ban --</option>

                          @foreach ($deparments as $department)
                              <option value="{{ $department->name }}" 
                                  {{ old('deparment') == $department->name ? 'selected' : '' }}>
                                  {{ $department->name }}
                              </option>
                          @endforeach
                      </select>

                      @error('deparment','updateErrors')
                          <div class="alert alert-danger mt-1">{{ $message }}</div>
                      @enderror
                  </div>
              </div>
          </div>
           
            {{-- Mail--}}
            <div class="form-group">
              <label for="mail"> {{'Mail (Nếu Có)'}}</label>
              <input type="text" class="form-control" name="mail" placeholder="Không Bắt Buốc"
                value="{{ old('mail') }}">
            </div>
            @error('mail','createErrors')
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
@if ($errors->updateErrors->any())
<script>
    $(document).ready(function () {
        $('#UpdateModal').modal('show');
    });
</script>
@endif
