<!-- Modal -->
<div class="modal fade " id="Modal" tabindex="-1" role="dialog" aria-labelledby="ModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">

        <form action="{{ route('pages.status.history.update') }}" method="POST">
            @csrf

            <div class="modal-content">
                <div class="modal-header">
                    <a href="{{ route('pages.general.home') }}">
                        <img src="{{ asset('img/iconstella.svg') }}" style="opacity: 0.8 ; max-width:45px;">
                    </a>

                    <h4 class="modal-title w-100 text-center" id="pModalLabel" style="color: #CDC717">
                        {{ 'Cập Nhật Trạng Thái Phòng Sản Xuất' }}
                    </h4>

                    <button type="button" class="close" data-dismiss="modal" aria-label="Đóng">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>

                <div class="modal-body">

                    <input type ="hidden" class="form-control" name="id" id="id">

                    <div class="form-group">
                        <label for="name">Phòng Sản Xuất</label>
                        <input type="text" class="form-control" name="room_name" readonly
                            value="{{ old('room_name') }}">
                    </div>

                     <div class="form-group">
                       <label for="in_production">Sản Phẩm Đang Sản Xuất</label>
                        <input class="form-control"  name="in_production" id="in_production">
                    </div>


                    <div class="card card-success">
                        <div class="card-header">
                            <h3 class="card-title">Trạng Thái Phòng Sản Xuẩt</h3>
                        </div>
                        <div class="card-body">
                            <!-- Minimal style -->
                            <div class="row">
                                <div class="col-md-6">
                                    <!-- radio -->
                                    <div class="form-group clearfix">
                                        <div class="icheck-primary d-inline">
                                            <input type="radio" id="Status1" name="status" value = "1" checked>
                                            <label for="Status1">
                                                Sản Xuất
                                            </label>
                                        </div>
                                    </div>

                                    <div class="form-group clearfix">
                                        <div class="icheck-primary d-inline">
                                            <input type="radio" id="Status2" name="status" value = "2">
                                            <label for="Status2">
                                                Vệ Sinh
                                            </label>
                                        </div>
                                    </div>

                                    <div class="form-group clearfix">
                                        <div class="icheck-primary d-inline">
                                            <input type="radio" id="Status4" name="status" value = "4">
                                            <label for="Status4">
                                                Máy Hư
                                            </label>
                                        </div>
                                    </div>
                                </div> 
                                <div class="col-md-6">  

                                     <div class="form-group clearfix">
                                        <div class="icheck-primary d-inline">
                                            <input type="radio" id="Status0" name="status" value = "0">
                                            <label for="Status0">
                                                Không Sản Xuất
                                            </label>
                                        </div>
                                    </div>

                                    <div class="form-group clearfix">
                                        <div class="icheck-primary d-inline">
                                            <input type="radio" id="Status3" name="status" value = "3">
                                            <label for="Status3">
                                                Bảo Trì
                                            </label>
                                        </div>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <label>Thời Gian Bắt Đầu</label>
                            <div class="input-group">
                                <input type="datetime-local" class="form-control" name = "start" value="{{ old('start', \Carbon\Carbon::now()->format('Y-m-d\TH:i')) }}" >
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label>Dự Kiến Kết Thúc</label>
                            <div class="input-group">
                                <input type="datetime-local" class="form-control" name = "end"  value="{{ old('end', \Carbon\Carbon::now()->format('Y-m-d\TH:i')) }}">
                            </div>
                        </div>
                    </div>

                    <div class="row mt-3">
                        <div class="col-md-12">
                            <label>Thông báo</label>
                            <textarea class="form-control" name="notification" rows="2"></textarea>
                        </div>
                        @error('notification', 'createErrors')
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
        $(document).ready(function() {
            $('#Modal').modal('show');
        });
    </script>
@endif

{{-- <script>
    document.addEventListener("DOMContentLoaded", function () {

        const input = document.getElementById('in_production');
        const radios = document.querySelectorAll('input[name="status"]');

        function setStatus(statusId, lock) {
            const radio = document.getElementById(statusId);
            if (radio) radio.checked = true;

            // Khóa hoặc mở khóa tất cả radio
            radios.forEach(r => r.disabled = lock);
            if (lock) radio.disabled = false; // giữ radio được chọn vẫn mở
        }

        input.addEventListener('input', function () {
            const val = input.value.trim();

            if (val === "Đang Vệ Sinh") {
                setStatus("Status2", true);
            }
            else if (val === "Bảo Trì") {
                setStatus("Status3", true);
            }
            else if (val === "Máy Hư") {
                setStatus("Status4", true);
            }
            else if (val === "Không Sản Xuất") {
                setStatus("Status0", true);
            }
            else {
                // Trường hợp khác: mở khóa radio để chọn bình thường
                setStatus("Status1", true);
                //radios.forEach(r => r.disabled = false);
            }
        });

    });
</script> --}}

