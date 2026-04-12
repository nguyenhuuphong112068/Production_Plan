<div class="content-wrapper">
    <div class="card">
        <div class="card-body">
            <div class="row mb-3">
                <div class="col-12">
                    <ul class="nav nav-tabs" id="historyTabs" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active" href="{{ route('pages.History.maintenance.list') }}"
                                style="font-size: 18px; font-weight: bold;">
                                <i class="fas fa-tools mr-2"></i>Lịch Sử HC-BT
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
            <form id="filterForm" method="GET" action="{{ route('pages.History.maintenance.list') }}"
                class="d-flex flex-wrap gap-0">
                <input type="hidden" name="main_type" value="maintenance">
                <div class="row w-100 align-items-center">
                    <!-- Filter From/To -->
                    <div class="col-md-6 d-flex gap-2">
                        @php
                            use Carbon\Carbon;
                            $defaultFrom = Carbon::now()->subMonth(1)->toDateString();
                            $defaultTo = Carbon::now()->addDays(1)->toDateString();
                        @endphp
                        <div class="form-group d-flex align-items-center">
                            <label for="from_date" class="mr-2 mb-0">From:</label>
                            <input type="date" id="from_date" name="from_date"
                                value="{{ request('from_date') ?? $defaultFrom }}" class="form-control" />
                        </div>
                        <div class="form-group d-flex align-items-center">
                            <label for="to_date" class="mr-2 mb-0">To:</label>
                            <input type="date" id="to_date" name="to_date"
                                value="{{ request('to_date') ?? $defaultTo }}" class="form-control" />
                        </div>
                    </div>

                    <!-- Type Selector -->
                    <div class="col-md-6 d-flex justify-content-end">
                        <div class="form-group d-flex align-items-center" style="width: 300px">
                            <label class="mr-2 mb-0" style="white-space: nowrap">Loại:</label>
                            <select class="form-control" name="maintenance_type" style="text-align-last: center;"
                                onchange="document.getElementById('filterForm').submit();">
                                <option {{ $maintenanceType == 'HC' ? 'selected' : '' }} value="HC">Hiệu Chuẩn
                                </option>
                                <option {{ $maintenanceType == 'TB' ? 'selected' : '' }} value="TB">Bảo Trì
                                </option>
                                <option {{ $maintenanceType == 'TI' ? 'selected' : '' }} value="TI">Tiện Ích
                                </option>
                            </select>
                        </div>
                    </div>
                </div>
            </form>

            <table id="data_table_hisory" class="table table-bordered table-striped " style="font-size: 20px">
                <thead style="position: sticky; background-color: white; z-index: 1020">
                    <tr>
                        <th>STT</th>
                        <th>Mã TB Lớn</th>
                        <th style="width: 15%;">Tên TB Lớn</th>
                        <th>Mã TB Con</th>
                        <th style="width: 15%;">Tên TB Con</th>
                        <th>Phòng</th>
                        <th>Thời Gian Thực Hiện</th>
                        <th>Kết Quả</th>
                        <th class="text-center" style="width: 120px;">Người Thực Hiện</th>
                        {{-- @if (session('user')['userName'] == 'Admin' || session('user')['userName'] == '19764' || session('user')['userName'] == '19713')
                            <th>Return</th>
                        @endif --}}
                    </tr>
                </thead>
                <tbody>
                    @foreach ($datas as $data)
                        <tr>
                            <td>{{ $loop->iteration }}
                                @if (session('user')['userGroup'] == 'Admin')
                                    <div> {{ $data->id }} </div>
                                @endif
                            </td>
                            <td>{{ $data->parent_instrument_code }}</td>
                            <td>{{ $data->parent_instrument_name }}</td>
                            <td>{{ $data->instrument_code }}</td>
                            <td>{{ $data->name }}</td>

                            <td> {{ $data->room_name . ' - ' . $data->room_code }} </td>
                            @if ($data->actual_start)
                                <td> {{ \Carbon\Carbon::parse($data->actual_start)->format('d/m/Y H:i') . ' - ' . \Carbon\Carbon::parse($data->actual_end)->format('d/m/Y H:i') }}
                                </td>
                            @else
                                <td> {{ 'Chưa Xác nhận Hoàn Thành' }} </td>
                            @endif

                            <td>
                                @if ($data->yields == 1)
                                    <span class="badge badge-success">Pass</span>
                                @elseif($data->yields == 0)
                                    <span class="badge badge-danger">Fail</span>
                                @elseif($data->yields == 2)
                                    <span class="badge badge-warning">Skip</span>
                                @else
                                    <span class="badge badge-secondary">NA</span>
                                @endif
                            </td>
                            <td class="text-center">{{ $data->finished_by }}</td>
                            {{-- @if (session('user')['userName'] == 'Admin' || session('user')['userName'] == '19764' || session('user')['userName'] == '19713')
                                <td class="text-center align-middle">
                                    <button type="button" class="btn btn-return btn-danger"
                                        data-id="{{ $data->id }}">
                                        <i class="fas fa-undo"></i>
                                    </button>
                                </td>
                            @endif --}}
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="{{ asset('js/vendor/jquery-1.12.4.min.js') }}"></script>
<script src="{{ asset('js/popper.min.js') }}"></script>
<script src="{{ asset('js/bootstrap.min.js') }}"></script>
<script src="{{ asset('js/sweetalert2.all.min.js') }}"></script>

<script>
    $(document).ready(function() {
        document.body.style.overflowY = "auto";
        $('#data_table_hisory').DataTable({
            paging: true,
            lengthChange: true,
            searching: true,
            ordering: true,
            info: true,
            autoWidth: false,
            pageLength: 10,
            language: {
                search: "Tìm kiếm:",
                lengthMenu: "Hiển thị _MENU_ dòng",
                info: "Hiển thị _START_ đến _END_ của _TOTAL_ dòng",
                paginate: {
                    previous: "Trước",
                    next: "Sau"
                }
            },
        });
    });

    $(document).on('click', '.btn-return', function() {
        const button = $(this);
        const stage_plan_id = button.data('id');
        $.ajax({
            url: "{{ route('pages.History.maintenance.returnStage') }}",
            type: 'POST',
            data: {
                stage_plan_id: stage_plan_id,
                _token: "{{ csrf_token() }}"
            },
            success: function(res) {
                if (res.success) {
                    Swal.fire({
                        icon: "success",
                        title: res.message || "Thành công",
                        timer: 2000,
                        showConfirmButton: false
                    });
                    button.closest('tr').fadeOut(400, function() {
                        $(this).remove();
                    });
                }
            }
        });
    });

    const form = document.getElementById('filterForm');
    const fromInput = document.getElementById('from_date');
    const toInput = document.getElementById('to_date');
    [fromInput, toInput].forEach(input => {
        input.addEventListener('input', function() {
            form.submit();
        });
    });
</script>
