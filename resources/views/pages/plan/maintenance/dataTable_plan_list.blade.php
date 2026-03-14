<div class="content-wrapper">
    <!-- Main content -->
    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <!-- /.card-header -->
                    <div class="card">
                        <!-- /.card-Body -->
                        <div class="card-body mt-5">
                            @if (user_has_permission(session('user')['userId'], 'plan_maintenance_create_plan_list', 'boolean'))
                                <form action="{{ route('pages.plan.maintenance.create_plan_list') }}" method="POST"
                                    id="form_create_auto" class="form-inline">
                                    @csrf
                                    <div class="form-group mr-2">
                                        <label for="from_date" class="mr-1">Từ</label>
                                        <input type="date" class="form-control" name="from_date" id="from_date"
                                            value="{{ date('Y-m-01') }}">
                                    </div>
                                    <div class="form-group mr-2">
                                        <label for="to_date" class="mr-1">Đến</label>
                                        <input type="date" class="form-control" name="to_date" id="to_date"
                                            value="{{ date('Y-m-t') }}">
                                    </div>
                                    <button type="submit" class="btn btn-success mb-0" style="width: 200px"
                                        id="btn_create_auto">
                                        <i class="fas fa-plus"></i> Tạo Tự Động
                                    </button>
                                </form>
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
            title: 'Lỗi!',
            text: '{{ session('error') }}',
            icon: 'error',
            showConfirmButton: true
        });
    </script>
@endif

@if (session('warning'))
    <script>
        Swal.fire({
            title: 'Cảnh báo!',
            text: '{{ session('warning') }}',
            icon: 'warning',
            showConfirmButton: true
        });
    </script>
@endif

<script>
    $(document).ready(function() {
        preventDoubleSubmit("#form_create_auto", "#btn_create_auto");
        document.body.style.overflowY = "auto";
        $('#form_create_auto').on('submit', function(e) {
            e.preventDefault();
            const form = this;
            Swal.fire({
                title: 'Tạo kế hoạch tự động?',
                text: 'Hệ thống sẽ tạo kế hoạch bảo trì hiệu chuẩn từ lịch bảo trì tháng hiện tại.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#28a745',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Đồng ý',
                cancelButtonText: 'Hủy'
            }).then((result) => {
                if (result.isConfirmed) {
                    form.submit();
                }
            });
        });
    });
</script>
