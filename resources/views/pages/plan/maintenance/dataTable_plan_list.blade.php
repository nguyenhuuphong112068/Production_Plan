<div class="content-wrapper">
    <!-- Main content -->
    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <!-- /.card-header -->
                    @php
                        $planType = request('type', 1);
                        $typeName =
                            ['1' => 'Hiệu Chuẩn', '2' => 'Bảo Trì', '3' => 'Tiện Ích'][$planType] ?? 'Hiệu Chuẩn';
                    @endphp
                    <div class="card">
                        <!-- /.card-Body -->
                        <div class="card-body mt-5">
                            @if (user_has_permission(session('user')['userId'], 'plan_maintenance_create_plan_list', 'boolean'))
                                <button type="button" class="btn btn-success mb-3" style="width: 300px"
                                    data-toggle="modal" data-target="#modal_auto_create">
                                    <i class="fas fa-magic"></i> Tạo Kế Hoạch {{ $typeName }}
                                </button>
                            @endif

                            <table id="example1" class="table table-bordered table-striped" style="font-size: 20px">

                                <thead style = "position: sticky; top: 60px; background-color: white; z-index: 1020">
                                    <tr>
                                        <th>STT</th>
                                        <th>Kế Hoạch</th>
                                        <th>Phân Xưởng</th>
                                        <th>Người Tạo</th>
                                        <th>Người Tạo</th>
                                        <th>Tình Trạng</th>
                                        <th>Người Gửi</th>
                                        <th>Ngày Gửi</th>
                                        <th>Xem</th>
                                    </tr>
                                </thead>
                                <tbody>

                                    @foreach ($datas as $data)
                                        <tr>
                                            <td>{{ $loop->iteration }} </td>
                                            {{-- <td>{{ $data->code}}</td> --}}
                                            <td>{{ $data->name }}</td>
                                            <td>{{ $data->deparment_code }}</td>
                                            <td>{{ $data->prepared_by }}</td>
                                            <td>{{ \Carbon\Carbon::parse($data->created_at)->format('d/m/Y H:i') }}</td>

                                            @php
                                                $colors = [
                                                    0 => 'background-color: #ffeb3b; color: white;', // vàng
                                                    1 => 'background-color: #4caf50; color: white;', // xanh lá
                                                ];
                                                $status = [
                                                    0 => 'Pending', // vàng
                                                    1 => 'Send', // xanh lá
                                                ];
                                            @endphp

                                            <td style="text-align: center; vertical-align: middle;">
                                                <span
                                                    style="padding: 6px 15px; border-radius: 20px; {{ $colors[$data->send] ?? '' }}">
                                                    {{ $status[$data->send] }}
                                                </span>
                                            </td>

                                            <td>{{ $data->send_by }}</td>
                                            <td>{{ \Carbon\Carbon::parse($data->send_date)->format('d/m/Y H:i') }}</td>


                                            <td class="text-center align-middle">
                                                <form action="{{ route('pages.plan.maintenance.open') }}"
                                                    method="get">
                                                    @csrf
                                                    <input type="hidden" name="plan_list_id"
                                                        value="{{ $data->id }}">
                                                    <input type="hidden" name="month" value="{{ $data->month }}">
                                                    <input type="hidden" name="send" value="{{ $data->send }}">
                                                    <input type="hidden" name="name" value="{{ $data->name }}">
                                                    <input type="hidden" name="type" value="{{ $planType }}">
                                                    <button type="submit" class="btn btn-success">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                </form>
                                            </td>

                                        </tr>
                                    @endforeach

                                </tbody>
                            </table>
                        </div>
                        <!-- /.card-body -->
                    </div>
                    <!-- /.card -->
                </div>
                <!-- /.col -->
            </div>
            <!-- /.row -->
        </div>
        <!-- /.container-fluid -->
    </section>
    <!-- /.content -->
</div>


<!-- Modal Tạo Tự Động -->
<div class="modal fade" id="modal_auto_create" tabindex="-1" role="dialog" aria-labelledby="modalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <form action="{{ route('pages.plan.maintenance.auto_create_plan') }}" method="POST" id="form_auto_create">
                @csrf
                @php
                    $planType = request('type', 1);
                    $typeName = ['1' => 'Hiệu Chuẩn', '2' => 'Bảo Trì', '3' => 'Tiện Ích'][$planType] ?? 'Hiệu Chuẩn';
                @endphp
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="autoGenerateModalLabel">
                        <i class="fas fa-magic"></i> Tạo Kế Hoạch {{ $typeName }} Tự Động
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 border-right">
                            <h6 class="font-weight-bold text-primary mb-3"><i class="fas fa-calendar-alt"></i> 1. Chọn
                                Khoảng Thời Gian</h6>
                            <div class="form-group">
                                <label for="m_from_date">Từ Ngày:</label>
                                <input type="date" class="form-control" name="from_date" id="m_from_date"
                                    value="{{ date('Y-m-01') }}" required>
                            </div>
                            <div class="form-group">
                                <label for="m_to_date">Đến Ngày:</label>
                                <input type="date" class="form-control" name="to_date" id="m_to_date"
                                    value="{{ date('Y-m-t') }}" required>
                            </div>
                            <input type="hidden" name="type" value="{{ $planType }}">
                        </div>
                        <div class="col-md-6 pl-4">
                            <h6 class="font-weight-bold text-primary mb-3"><i class="fas fa-industry"></i> 2. Chọn Phân
                                Xưởng (PX)</h6>
                            <div class="row">
                                @php
                                    $allDepts = ['PXV1', 'PXV2', 'PXVH', 'PXDN', 'PXTN'];
                                @endphp
                                @foreach ($allDepts as $dept)
                                    <div class="col-6 mb-2">
                                        <div class="custom-control custom-checkbox">
                                            <input type="checkbox" class="custom-control-input dept-checkbox"
                                                id="chk_{{ $dept }}" name="departments[]"
                                                value="{{ $dept }}" checked>
                                            <label class="custom-control-label"
                                                for="chk_{{ $dept }}">{{ $dept }}</label>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                            <hr>
                            <div class="custom-control custom-checkbox mt-2">
                                <input type="checkbox" class="custom-control-input" id="chk_all_depts" checked>
                                <label class="custom-control-label font-italic" for="chk_all_depts">Chọn/Bỏ chọn tất
                                    cả</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">
                        <i class="fas fa-times"></i> Đóng
                    </button>
                    <button type="submit" class="btn btn-success" id="btn_save_auto">
                        <i class="fas fa-save"></i> Lưu
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="{{ asset('js/vendor/jquery-1.12.4.min.js') }}"></script>
<script src="{{ asset('js/popper.min.js') }}"></script>
<script src="{{ asset('js/bootstrap.min.js') }}"></script>
<script src="{{ asset('js/sweetalert2.all.min.js') }}"></script>

@if (session('success'))
    <script>
        Swal.fire({
            title: 'Thành công!',
            text: '{{ session('success') }}',
            icon: 'success',
            timer: 2000,
            showConfirmButton: false
        });
    </script>
@endif

@if (session('error'))
    <script>
        Swal.fire({
            title: 'Cảnh báo!',
            html: `{!! session('error') !!}`,
            icon: 'warning',
            width: '80%',
            showConfirmButton: true
        });
    </script>
@endif

@if (session('warning'))
    <script>
        Swal.fire({
            title: 'Cảnh báo!',
            html: `{!! session('warning') !!}`,
            icon: 'warning',
            width: '30%',
            showConfirmButton: true
        });
    </script>
@endif

<script>
    $(document).ready(function() {
        document.body.style.overflowY = "auto";

        // Chọn/Bỏ chọn tất cả PX
        $('#chk_all_depts').on('change', function() {
            $('.dept-checkbox').prop('checked', $(this).is(':checked'));
        });

        $('.dept-checkbox').on('change', function() {
            if (!$(this).is(':checked')) {
                $('#chk_all_depts').prop('checked', false);
            } else if ($('.dept-checkbox:checked').length === $('.dept-checkbox').length) {
                $('#chk_all_depts').prop('checked', true);
            }
        });

        // Submit form tạo tự động
        $('#form_auto_create').on('submit', function(e) {
            e.preventDefault();

            if ($('.dept-checkbox:checked').length === 0) {
                Swal.fire('Lỗi', 'Vui lòng chọn ít nhất một phân xưởng!', 'error');
                return;
            }

            const fromDate = $('#m_from_date').val();
            const toDate = $('#m_to_date').val();

            if (new Date(fromDate) > new Date(toDate)) {
                Swal.fire('Lỗi', 'Ngày bắt đầu không được lớn hơn ngày kết thúc!', 'error');
                return;
            }

            Swal.fire({
                title: 'Tạo kế hoạch tự động?',
                text: 'Hệ thống sẽ quét lịch bảo trì cho các phân xưởng đã chọn.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#28a745',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Đồng ý',
                cancelButtonText: 'Hủy'
            }).then((result) => {
                if (result.isConfirmed) {
                    this.submit();
                    $('#btn_save_auto').prop('disabled', true).html(
                        '<i class="fas fa-spinner fa-spin"></i> Đang xử lý...');
                }
            });
        });
    });
</script>
