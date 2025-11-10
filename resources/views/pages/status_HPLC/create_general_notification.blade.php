<!-- Modal -->
<div class="modal fade " id="notification_Modal" tabindex="-1" role="dialog" aria-labelledby="ModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">

        <form action="{{ route('pages.status.store_general_notification') }}" method="POST">
            @csrf

            <div class="modal-content">
                <div class="modal-header">
                    <a href="{{ route('pages.general.home') }}">
                        <img src="{{ asset('img/iconstella.svg') }}" style="opacity: 0.8 ; max-width:45px;">
                    </a>

                    <h4 class="modal-title w-100 text-center" id="pModalLabel" style="color: #CDC717">
                        {{ 'Cập Nhật Nội Dung Thông Báo Chung' }}
                    </h4>

                    <button type="button" class="close" data-dismiss="modal" aria-label="Đóng">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>

                <div class="modal-body">
                    <div class="row mt-3">
                        <div class="col-md-12">
                            <label>Nội Dung Thông báo</label>
                            <textarea class="form-control" name="notification" rows="3"></textarea>
                        </div>
                    </div>
           
                    <div class="row">
                        <div class="col-md-12">
                            <label>Thời Hạn Thông báo</label>
                            <div class="input-group">
                                <input type="datetime-local" class="form-control" name = "durability" value="{{ old('durability', \Carbon\Carbon::now()->format('Y-m-d\TH:i')) }}" >
                            </div>
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


{{-- //Show modal nếu có lỗi validation --}}
@if ($errors->notification_Errors->any())
    <script>
        $(document).ready(function() {
            $('#notification_Modal').modal('show');
        });
    </script>
@endif
