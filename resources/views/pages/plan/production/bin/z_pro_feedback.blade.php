


<!-- Modal -->
<div class="modal fade" id="pro_feedback_modal" tabindex="-1" role="dialog" aria-labelledby="ModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
   
    <form 
      action="{{route('pages.plan.production.all_feedback')}}" 
      method="POST">
      @csrf

      <div class="modal-content">
        <div class="modal-header">
          <a href="{{ route ('pages.general.home') }}">
              <img src="{{ asset('img/iconstella.svg') }}" style="opacity: 0.8 ; max-width:45px;">
          </a>

          <h4 class="modal-title w-100 text-center" style="color: #CDC717">
              {{'Phân Xưởng Phản Hồi' }}
          </h4>

          <button type="button" class="close" data-dismiss="modal" aria-label="Đóng">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>

        <input type="hidden" name="plan_list_id" value="{{ $plan_list_id ?? old('plan_list_id') }}"/>
        
        <div class="modal-body">
          <div class="row">

            <div class="col-12">
              <label for="name" class="form-label">Phản hồi</label>
              <textarea 
                    class="form-control"
                    id="pro_feedback"
                    name="pro_feedback"
                    value="{{ old('pro_feedback') }}"
                    placeholder="Nhập phản hồi..."> </textarea>
              @error('pro_feedback', 'createErrors')
                <div class="text-danger small mt-1">{{ $message }}</div>
              @enderror
            </div>
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

{{-- //Show modal nếu có lỗi validation --}}
@if ($errors->createErrors->any())
<script>
    $(document).ready(function () {
        $('#create_modal').modal('show');
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
