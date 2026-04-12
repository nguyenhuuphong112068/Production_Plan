
<style>
    .create-modal-size {
        max-width: 100% !important;
        width: 100% !important;
    }
    .bootstrap-switch {
        height: 100%;
        display: flex;
        align-items: center;
        /* căn giữa theo chiều dọc */
    }
</style>

<!-- Modal -->
<div class="modal fade" id="create_modal" tabindex="-1" role="dialog" aria-labelledby="ModalLabel" aria-hidden="true">
    <div class="modal-dialog create-modal-size" role="document">

        <form action="{{ route('pages.plan.maintenance.store') }}" method="POST">
            @csrf

            <div class="modal-content">
                <div class="modal-header">


                    <a href="{{ route('pages.general.home') }}">
                        <img src="{{ asset('img/iconstella.svg') }}" style="opacity: 0.8 ; max-width:45px;">
                    </a>

                    <h4 class="modal-title w-100 text-center" id="pModalLabel" style="color: #CDC717">
                        {{ 'Tạo Kế Hoạch Hiệu Chuẩn Bảo Trì' }}
                    </h4>

                    <button type="button" class="close" data-dismiss="modal" aria-label="Đóng">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>

                <div class="modal-body">
                    <input type="hidden" name="plan_list_id" readonly value="{{ $plan_list_id }}" />
                    <div id ="selected_instruments_container"> 
                    </div>
                </div>

                <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
                        <button type="submit" class="btn btn-primary" id="btnSave">
                            Lưu
                        </button>
                </div>

            </div>
        </form>
    </div>
</div>



{{-- //Show modal nếu có lỗi validation --}}
@if ($errors->create_finished_Errors->any())
    <script>
        $(document).ready(function() {
            $('#create_modal').modal('show');
        });
    </script>
@endif


<script>
    $(document).ready(function() {
         preventDoubleSubmit("#create_modal", "#btnSave");
    });
</script>
