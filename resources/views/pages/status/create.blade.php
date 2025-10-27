<!-- Modal -->
<div class="modal fade " id="Modal" tabindex="-1" role="dialog" aria-labelledby="ModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">

        <form action="{{ route('pages.status.store') }}" method="POST">
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

                    <div class="form-group">
                        <label for="name">Phòng Sản Xuất</label>
                        <input type="text" class="form-control" name="room_name" readonly
                            value="{{ old('room_name') }}">
                    </div>




                    <div class="form-group">
                        <label for="belongGroup_id">Sản Phẩm Đang Sản Xuất</label>
                        <select class="form-control" name="in_production">
                            <option value="">-- Chọn Lô Sản Phẩm --</option>
                            <option value="Không Sản Xuất">Không Sản Xuất</option>
                            <option value="Đang Vệ Sinh">Đang Vệ Sinh</option>
                            <option value="Bảo Trì">Bảo Trì</option>
                            @foreach ($planWaitings as $plan)
                                <option value="{{ $plan->name . '_' . $plan->batch }}"
                                    {{ old('in_production') == $plan->name . '_' . $plan->batch ? 'selected' : '' }}>
                                    {{ $plan->name . '_' . $plan->batch }}
                                </option>
                            @endforeach

                        </select>
                        @error('in_production', 'createErrors')
                            <div class="alert alert-danger mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="card card-success">
                        <div class="card-header">
                            <h3 class="card-title">Trạng Thái Phòng Sản Xuẩt</h3>
                        </div>
                        <div class="card-body">
                            <!-- Minimal style -->
                            <div class="row">
                                <div class="col-md-4">
                                    <!-- radio -->
                                   

                                    <div class="form-group clearfix">
                                        <div class="icheck-primary d-inline">
                                            <input type="radio" id="Status2" name="status" value = "1" checked>
                                            <label for="Status2">
                                                Đang Sản Xuất
                                            </label>
                                        </div>
                                    </div>

                                    <div class="form-group clearfix">
                                        <div class="icheck-primary d-inline">
                                            <input type="radio" id="Status3" name="status" value = "2">
                                            <label for="Status3">
                                                Đang Vệ Sinh
                                            </label>
                                        </div>
                                    </div>

                                     <div class="form-group clearfix">
                                        <div class="icheck-primary d-inline">
                                            <input type="radio" id="Status1" name="status" value = "3">
                                            <label for="Status1">
                                                Không Sản Xuất
                                            </label>
                                        </div>
                                    </div>

                                    <div class="form-group clearfix">
                                        <div class="icheck-primary d-inline">
                                            <input type="radio" id="Status4" name="status" value = "0">
                                            <label for="Status4">
                                                Đang Bảo Trì
                                            </label>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-4">
                                    <!-- radio -->
                                    <div class="form-group clearfix">
                                        <div class="icheck-primary d-inline">
                                            <input type="radio" id="sheet1" name="sheet" value = "1" checked>
                                            <label for="sheet1">
                                                Đầu Ca
                                            </label>
                                        </div>
                                    </div>

                                    <div class="form-group clearfix">
                                        <div class="icheck-primary d-inline">
                                            <input type="radio" id="sheet2" name="sheet" value = "2">
                                            <label for="sheet2">
                                                Giữa Ca
                                            </label>
                                        </div>
                                    </div>

                                    <div class="form-group clearfix">
                                        <div class="icheck-primary d-inline">
                                            <input type="radio" id="sheet3" name="sheet" value = "3">
                                            <label for="sheet3">
                                                Cuối Ca
                                            </label>
                                        </div>
                                    </div>

                                    <div class="form-group clearfix">
                                        <div class="icheck-primary d-inline">
                                            <input type="radio" id="sheet4" name="sheet" value = "0">
                                            <label for="sheet4">
                                                NA
                                            </label>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-4">
                                    <!-- radio -->
                                    <div class="form-group clearfix">
                                        <div class="icheck-primary d-inline">
                                            <input type="radio" id="step_batch1" name="step_batch" value = "1" checked>
                                            <label for="step_batch1">
                                                Đầu Lô
                                            </label>
                                        </div>
                                    </div>

                                    <div class="form-group clearfix">
                                        <div class="icheck-primary d-inline">
                                            <input type="radio" id="step_batch2" name="step_batch" value = "2">
                                            <label for="step_batch2">
                                                Giữa Lô
                                            </label>
                                        </div>
                                    </div>

                                    <div class="form-group clearfix">
                                        <div class="icheck-primary d-inline">
                                            <input type="radio" id="step_batch3" name="step_batch" value = "3">
                                            <label for="step_batch3">
                                                Cuối Lô
                                            </label>
                                        </div>
                                    </div>

                                    <div class="form-group clearfix">
                                        <div class="icheck-primary d-inline">
                                            <input type="radio" id="step_batch4" name="step_batch" value = "0">
                                            <label for="step_batch4">
                                                NA
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
