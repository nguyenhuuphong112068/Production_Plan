
<link href="{{ asset('css/bootstrap.min.css') }}" rel="stylesheet">

<!-- Modal -->
<div class="modal fade" id="explanation" tabindex="-1" role="dialog" aria-labelledby="ModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
   
    <form 

      action="{{route('pages.report.daily_report.explain')}}" 
      method="POST">
      @csrf

       @php
            $auth_update = user_has_permission(session('user')['userId'], 'report_explanation', 'disabled');
        @endphp

      <div class="modal-content">
        <div class="modal-header">
          <a href="{{ route ('pages.general.home') }}">
              <img src="{{ asset('img/iconstella.svg') }}" style="opacity: 0.8 ; max-width:45px;">
          </a>

          <h4 class="modal-title w-100 text-center" style="color: #CDC717">
              {{'Ghi Chú' }}
          </h4>

          <button type="button" class="close" data-dismiss="modal" aria-label="Đóng">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>

        <div class="modal-body">
            {{-- NAME --}}
            <div class="form-group" >
              <label for="name">Nội Dung Ghi Chú </label>
              <textarea type="text" class="form-control" name="note"  {{ $auth_update }} > {{ old('content') }} </textarea>
            </div>

            <div class="form-group">
              <label for="name">Người Ghi Chú </label>
              <input type="text" class="form-control" name="created_by" readonly> 
            </div>

            <div class="form-group">
              <label for="name">Ngày Ghi Chú </label>
              <input type="text" class="form-control" name="created_at" readonly> 
            </div>

        </div>

        <input type="hidden" class="form-control" name="reported_date" value="{{ old('reported_date') }}">
        <input type="hidden" class="form-control" name="stage_code" value="{{ old('stage_code') }}">

        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
          @if (user_has_permission(session('user')['userId'], 'report_explanation', 'boolean'))
            <button type="submit" class="btn btn-primary">
                Lưu
            </button>
          @endif

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
