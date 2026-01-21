<!-- Modal -->
<div class="modal fade " id="updateModal" tabindex="-1" role="dialog" aria-labelledby="ModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">

        <form action="{{ route('pages.report.daily_report.update') }}" method="POST">
            @csrf

            <div class="modal-content">
                <div class="modal-header">
                    <a href="{{ route('pages.general.home') }}">
                        <img src="{{ asset('img/iconstella.svg') }}" style="opacity: 0.8 ; max-width:45px;">
                    </a>

                    <h4 class="modal-title w-100 text-center" id="uModalLabel" style="color: #CDC717">
                        {{ 'Cập Nhật Hoạt Động Khác Trong Ngày' }}
                    </h4>

                    <button type="button" class="close" data-dismiss="modal" aria-label="Đóng">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>

                <div class="modal-body">
                    <input type="hidden" class="form-control" name="room_id"  value="{{ old('room_id') }}">
                    <input type="hidden" class="form-control" name="id"  value="{{ old('id') }}">

                    <div class="form-group">
                        <label for="name">Phòng Sản Xuất</label>
                        <input type="text" class="form-control" name="room_name" readonly
                            value="{{ old('room_name') }}">
                    </div>

                     <div class="form-group">
                       <label for="in_production">Hoạt Động</label>
                        <input class="form-control" list="in_production_list" name="in_production">
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <label>Thời Gian Bắt Đầu</label>
                            <div class="input-group">
                                <input type="datetime-local" class="form-control" name = "start" value="{{ old('start', now()->format('Y-m-d\TH:i')) }}" >
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label>Thời Gian Kết Thúc</label>
                            <div class="input-group">
                                <input type="datetime-local" class="form-control" name = "end"  value="{{ old('start', now()->format('Y-m-d\TH:i')) }}">
                            </div>
                        </div>
                    </div>

                    <div class="row mt-3">
                        <div class="col-md-12">
                            <label>Ghi chú</label>
                            <textarea class="form-control" name="notification" rows="2"></textarea>
                        </div>
                        @error('notification', 'createErrors')
                            <div class="alert alert-danger mt-1">{{ $message }}</div>
                        @enderror
                    </div>


                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
                    <button type="submit" class="btn btn-primary"> Lưu </button>
                </div>
            </div>
        </form>
    </div>
</div>


{{-- //Show modal nếu có lỗi validation --}}
@if ($errors->updateErrors->any())
    <script>
        $(document).ready(function() {
            $('#updateModal').modal('show');
        });
    </script>
@endif

