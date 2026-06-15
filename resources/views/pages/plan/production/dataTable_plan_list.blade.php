<div class="content-wrapper">
    <style>
        .btn-status-count {
            min-width: 40px;
            border-radius: 20px;
            font-weight: bold;
            padding: 4px 10px;
            transition: all 0.2s ease-in-out;
            background-color: #f8f9fa;
            border: 1px solid #17a2b8;
            color: #17a2b8;
            cursor: pointer;
        }

        .btn-status-count:hover:not(:disabled) {
            background-color: #17a2b8;
            color: white;
            transform: scale(1.1);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
        }

        .btn-status-count:disabled {
            border-color: #e9ecef;
            color: #ced4da;
            background-color: transparent;
            cursor: not-allowed;
        }
    </style>
    <div class="p-3">
        <!-- Bảng Kế hoạch gom theo tháng -->

        <!-- Bảng Kế hoạch gốc -->
        <div class="card card-success mt-5">
            <div class="card-header">
                <h3 class="card-title">Danh Sách Kế Hoạch Chi Tiết</h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                        <i class="fas fa-minus"></i>
                    </button>
                </div>
            </div>
            <div class="card-body" style="max-height: 95vh; overflow-y: auto;">
                @php
                    $auth_view_material = user_has_permission(
                        session('user')['userId'],
                        'plan_production_view_material',
                        'disabled',
                    );
                @endphp
                @if (user_has_permission(session('user')['userId'], 'plan_production_create_plan_list', 'boolean'))
                    <button class="btn btn-success btn-create mb-2" data-toggle="modal"
                        data-target="#create_plan_list_modal" style="width: 155px">
                        <i class="fas fa-plus"></i> Thêm
                    </button>
                @endif
                <table id="example1" class="table table-bordered table-striped" style="font-size: 20px">
                    <thead>
                        <tr>
                            <th rowspan="2">STT</th>
                            <th rowspan="2">Kế Hoạch</th>
                            <th rowspan="2">Phân Xưởng</th>
                            <th rowspan="2">Người Tạo</th>
                            <th rowspan="2">Ngày Tạo</th> <!-- ✅ SỬA -->
                            <th rowspan="2">Tình Trạng</th>
                            <th rowspan="2">Sản Lượng Lý Thuyết (ĐVL)</th>

                            <th colspan="9" style="text-align:center;">
                                Tình Trạng Sản Xuất
                            </th>

                            <th rowspan="2">Người Gửi</th>
                            <th rowspan="2">Ngày Gửi</th>
                            <th rowspan="2">Phân bổ<br>Thiết bị</th>
                            <th rowspan="2">Chi Tiết</th>
                            {{-- <th rowspan="2">Tạm tính NL/BB</th> --}}
                        </tr>

                        <tr>
                            <th>Tổng Lô</th>
                            <th>Chưa Làm</th>
                            <th>Đã Cân</th>
                            <th>Đã PC</th>
                            <th>Đã THT</th>
                            <th>Đã ĐH</th>
                            <th>Đã BP</th>
                            <th>Đã ĐG</th>
                            <th>Hủy Kế Hoạch</th>
                        </tr>
                    </thead>

                    <tbody>

                        @foreach ($datas as $data)
                            <tr>
                                <td>{{ $loop->iteration }}

                                </td>
                                {{-- <td>{{ $data->code}}</td> --}}
                                <td>
                                    {{ $data->name }}
                                    <span style="display: none;">{{ $data->product_names }} {{ $data->product_codes }} {{ $data->intermediate_codes }}</span>
                                </td>
                                <td>{{ $data->deparment_code }}
                                    @if (session('user')['userGroup'] == 'Admin')
                                        <div> {{ $data->id }} </div>
                                    @endif
                                </td>
                                <td>{{ $data->prepared_by ?? 'NA' }}</td>
                                <td>{{ $data->created_at ? \Carbon\Carbon::parse($data->created_at ?? now())->format('d/m/Y H:i') : '' }}
                                </td>

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
                                        style="padding: 6px 15px; border-radius: 20px; {{ $colors[$data->send ?? 1] ?? '' }}">
                                        {{ $status[$data->send ?? 1] }}
                                    </span>
                                </td>
                                <td>
                                    {{ number_format($data->total_batch_qty) }} <br>
                                    {{-- {{ number_format($data->batch_qty_pending) }} --}}
                                </td>

                                <td>{{ $data->tong_lo }}</td>
                                <td><button class="btn-status-count btn-show-status-batches"
                                        data-plan-list-id="{{ $data->id }}" data-status="Chưa làm"
                                        {{ ($data->status_counts['Chưa làm'] ?? 0) == 0 ? 'disabled' : '' }}>{{ $data->status_counts['Chưa làm'] ?? 0 }}</button>
                                </td>
                                <td><button class="btn-status-count btn-show-status-batches"
                                        data-plan-list-id="{{ $data->id }}" data-status="Đã Cân"
                                        {{ ($data->status_counts['Đã Cân'] ?? 0) == 0 ? 'disabled' : '' }}>{{ $data->status_counts['Đã Cân'] ?? 0 }}</button>
                                </td>
                                <td><button class="btn-status-count btn-show-status-batches"
                                        data-plan-list-id="{{ $data->id }}" data-status="Đã Pha chế"
                                        {{ ($data->status_counts['Đã Pha chế'] ?? 0) == 0 ? 'disabled' : '' }}>{{ $data->status_counts['Đã Pha chế'] ?? 0 }}</button>
                                </td>
                                <td><button class="btn-status-count btn-show-status-batches"
                                        data-plan-list-id="{{ $data->id }}" data-status="Đã THT"
                                        {{ ($data->status_counts['Đã THT'] ?? 0) == 0 ? 'disabled' : '' }}>{{ $data->status_counts['Đã THT'] ?? 0 }}</button>
                                </td>
                                <td><button class="btn-status-count btn-show-status-batches"
                                        data-plan-list-id="{{ $data->id }}" data-status="Đã định hình"
                                        {{ ($data->status_counts['Đã định hình'] ?? 0) == 0 ? 'disabled' : '' }}>{{ $data->status_counts['Đã định hình'] ?? 0 }}</button>
                                </td>
                                <td><button class="btn-status-count btn-show-status-batches"
                                        data-plan-list-id="{{ $data->id }}" data-status="Đã Bao phim"
                                        {{ ($data->status_counts['Đã Bao phim'] ?? 0) == 0 ? 'disabled' : '' }}>{{ $data->status_counts['Đã Bao phim'] ?? 0 }}</button>
                                </td>
                                <td><button class="btn-status-count btn-show-status-batches"
                                        data-plan-list-id="{{ $data->id }}" data-status="Hoàn Tất ĐG"
                                        {{ ($data->status_counts['Hoàn Tất ĐG'] ?? 0) == 0 ? 'disabled' : '' }}>{{ $data->status_counts['Hoàn Tất ĐG'] ?? 0 }}</button>
                                </td>
                                <td><button class="btn-status-count btn-show-status-batches"
                                        data-plan-list-id="{{ $data->id }}" data-status="Hủy"
                                        {{ ($data->status_counts['Hủy'] ?? 0) == 0 ? 'disabled' : '' }}>{{ $data->status_counts['Hủy'] ?? 0 }}</button>
                                </td>


                                <td>{{ $data->send_by ?? 'NA' }}</td>

                                <td>{{ $data->send_date ? \Carbon\Carbon::parse($data->send_date)->format('d/m/Y') : '' }}
                                </td>

                                <td class="text-center align-middle">
                                    <button type="button" class="btn btn-info btn-equipment-allocation" data-plan-list-id="{{ $data->id }}" data-plan-name="{{ $data->name }}" title="Xem phân bổ thiết bị">
                                        <i class="fas fa-chart-pie"></i>
                                    </button>
                                </td>

                                <td class="text-center align-middle">
                                    <form action="{{ route('pages.plan.production.open') }}" method="get">
                                        @csrf
                                        <input type="hidden" name="plan_list_id" value="{{ $data->id }}">
                                        <input type="hidden" name="month" value="{{ $data->month }}">
                                        <input type="hidden" name="send" value="{{ $data->send }}">
                                        <input type="hidden" name="name" value="{{ $data->name }}">

                                        <button type="submit" class="btn btn-success">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </form>
                                </td>

                                {{-- <td class="text-center align-middle">
                                    <form action="{{ route('pages.plan.production.open_stock') }}" method="get">
                                        @csrf
                                        <input type="hidden" name="plan_list_id" value="{{ $data->id }}">
                                        <input type="hidden" name="current_url" value="{{ url()->full() }}">
                                        <button type="submit" class="btn btn-success" {{ $auth_view_material }}>
                                            <i class="fas fa-table"></i>
                                        </button>
                                    </form>
                                </td> --}}

                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        @if (isset($summary_by_month) && count($summary_by_month) > 0)
            <div class="card card-info">
                <div class="card-header">
                    <h3 class="card-title">Tổng Hợp Kế Hoạch Theo Tháng KCS</h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-tool" data-card-widget="collapse">
                            <i class="fas fa-minus"></i>
                        </button>
                    </div>
                </div>
                <div class="card-body" style="overflow-x: auto;">
                    <table id="table_month" class="table table-bordered table-striped" style="font-size: 20px">
                        <thead>
                            <tr>
                                <th rowspan="2">STT</th>
                                <th rowspan="2">Tháng</th>
                                <th rowspan="2">Sản Lượng Lý Thuyết (ĐVL)</th>
                                <th colspan="9" style="text-align:center;">Tình Trạng Sản Xuất</th>
                                <th rowspan="2">Chi Tiết</th>
                                <th rowspan="2">Lịch Chưa Sắp</th>
                            </tr>
                            <tr>
                                <th>Tổng Lô</th>
                                <th>Chưa Làm</th>
                                <th>Đã Cân</th>
                                <th>Đã PC</th>
                                <th>Đã THT</th>
                                <th>Đã ĐH</th>
                                <th>Đã BP</th>
                                <th>Đã ĐG</th>
                                <th>Hủy Kế Hoạch</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php $month_stt = 1; @endphp
                            @foreach ($summary_by_month as $month_data)
                                <tr>
                                    <td>{{ $month_stt++ }}</td>
                                    <td>{{ $month_data->month }}</td>
                                    <td>{{ number_format($month_data->total_batch_qty) }}</td>
                                    <td>{{ $month_data->tong_lo }}</td>
                                    <td><button class="btn-status-count btn-show-status-batches"
                                            data-filter-type="expected" data-month="{{ $month_data->month }}"
                                            data-status="Chưa làm"
                                            {{ ($month_data->status_counts['Chưa làm'] ?? 0) == 0 ? 'disabled' : '' }}>{{ $month_data->status_counts['Chưa làm'] ?? 0 }}</button>
                                    </td>
                                    <td><button class="btn-status-count btn-show-status-batches"
                                            data-filter-type="expected" data-month="{{ $month_data->month }}"
                                            data-status="Đã Cân"
                                            {{ ($month_data->status_counts['Đã Cân'] ?? 0) == 0 ? 'disabled' : '' }}>{{ $month_data->status_counts['Đã Cân'] ?? 0 }}</button>
                                    </td>
                                    <td><button class="btn-status-count btn-show-status-batches"
                                            data-filter-type="expected" data-month="{{ $month_data->month }}"
                                            data-status="Đã Pha chế"
                                            {{ ($month_data->status_counts['Đã Pha chế'] ?? 0) == 0 ? 'disabled' : '' }}>{{ $month_data->status_counts['Đã Pha chế'] ?? 0 }}</button>
                                    </td>
                                    <td><button class="btn-status-count btn-show-status-batches"
                                            data-filter-type="expected" data-month="{{ $month_data->month }}"
                                            data-status="Đã THT"
                                            {{ ($month_data->status_counts['Đã THT'] ?? 0) == 0 ? 'disabled' : '' }}>{{ $month_data->status_counts['Đã THT'] ?? 0 }}</button>
                                    </td>
                                    <td><button class="btn-status-count btn-show-status-batches"
                                            data-filter-type="expected" data-month="{{ $month_data->month }}"
                                            data-status="Đã định hình"
                                            {{ ($month_data->status_counts['Đã định hình'] ?? 0) == 0 ? 'disabled' : '' }}>{{ $month_data->status_counts['Đã định hình'] ?? 0 }}</button>
                                    </td>
                                    <td><button class="btn-status-count btn-show-status-batches"
                                            data-filter-type="expected" data-month="{{ $month_data->month }}"
                                            data-status="Đã Bao phim"
                                            {{ ($month_data->status_counts['Đã Bao phim'] ?? 0) == 0 ? 'disabled' : '' }}>{{ $month_data->status_counts['Đã Bao phim'] ?? 0 }}</button>
                                    </td>
                                    <td><button class="btn-status-count btn-show-status-batches"
                                            data-filter-type="expected" data-month="{{ $month_data->month }}"
                                            data-status="Hoàn Tất ĐG"
                                            {{ ($month_data->status_counts['Hoàn Tất ĐG'] ?? 0) == 0 ? 'disabled' : '' }}>{{ $month_data->status_counts['Hoàn Tất ĐG'] ?? 0 }}</button>
                                    </td>
                                    <td><button class="btn-status-count btn-show-status-batches"
                                            data-filter-type="expected" data-month="{{ $month_data->month }}"
                                            data-status="Hủy"
                                            {{ ($month_data->status_counts['Hủy'] ?? 0) == 0 ? 'disabled' : '' }}>{{ $month_data->status_counts['Hủy'] ?? 0 }}</button>
                                    </td>
                                    <td class="text-center align-middle">
                                        <form action="{{ route('pages.plan.production.open') }}" method="get">
                                            @csrf
                                            <input type="hidden" name="plan_list_id" value="-2">
                                            <input type="hidden" name="expected_month"
                                                value="{{ $month_data->month }}">
                                            <input type="hidden" name="name"
                                                value="KẾ HOẠCH THÁNG {{ $month_data->month }}">

                                            <button type="submit" class="btn btn-success">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                        </form>
                                    </td>
                                    <td class="text-center align-middle">
                                        <button class="btn btn-warning btn-show-waiting"
                                            data-month="{{ $month_data->month }}" title="Danh sách lịch chưa sắp">
                                            <i class="fas fa-list"></i>
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        @if (isset($summary_by_actual_month) && count($summary_by_actual_month) > 0)
            <div class="card card-primary mt-3">
                <div class="card-header">
                    <h3 class="card-title">Tổng Hợp Tiến Độ Theo Tháng (Thực Tế)</h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-tool" data-card-widget="collapse">
                            <i class="fas fa-minus"></i>
                        </button>
                    </div>
                </div>
                <div class="card-body" style="overflow-x: auto;">
                    <table id="table_actual_month" class="table table-bordered table-striped"
                        style="font-size: 20px">
                        <thead>
                            <tr>
                                <th rowspan="2">STT</th>
                                <th rowspan="2">Tháng</th>
                                <th colspan="5" style="text-align:center;">Tình Trạng Sản Xuất</th>
                                {{-- <th rowspan="2">Tổng TG SX</th>
                                <th rowspan="2">Tổng TG VS</th> --}}
                                <th rowspan="2">Ngày Nghỉ</th>
                                <th rowspan="2">Ngày Làm</th>
                            </tr>
                            <tr>
                                <th>Pha Chế</th>
                                <th>THT</th>
                                <th>Định Hình</th>
                                <th>Bao Phim</th>
                                <th>Đóng Gói</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php
                                $actual_month_stt = 1;
                                if (!function_exists('formatMins')) {
                                    function formatMins($mins)
                                    {
                                        if (!$mins) {
                                            return '0 giờ';
                                        }

                                        // Làm tròn số phút thành số giờ
                                        $total_hours = round($mins / 60);

                                        if ($total_hours == 0) {
                                            return '0 giờ';
                                        }

                                        $d = floor($total_hours / 24);
                                        $h = $total_hours % 24;

                                        if ($d > 0 && $h > 0) {
                                            return "{$d} ngày {$h} giờ";
                                        }
                                        if ($d > 0) {
                                            return "{$d} ngày";
                                        }
                                        return "{$h} giờ";
                                    }
                                }
                            @endphp
                            @foreach ($summary_by_actual_month as $month_data)
                                <tr>
                                    <td>{{ $actual_month_stt++ }}</td>
                                    <td>{{ $month_data->month }}</td>

                                    <td><button class="btn-status-count btn-show-actual-status-batches"
                                            data-filter-type="actual" data-month="{{ $month_data->month }}"
                                            data-status="Đã Pha chế"
                                            {{ ($month_data->status_counts['Đã Pha chế'] ?? 0) == 0 ? 'disabled' : '' }}>{{ $month_data->status_counts['Đã Pha chế'] ?? 0 }}</button>
                                        @if (($month_data->status_counts['Đã Pha chế'] ?? 0) > 0)
                                            <div class="mt-2">
                                                <span class="badge badge-success px-2 py-1"
                                                    style="font-size: 14px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                                                    {{ number_format($month_data->status_yields['Đã Pha chế'] ?? 0) }}
                                                    <span style="font-size: 11px; font-weight: normal;">Kg</span>
                                                </span>
                                            </div>
                                            <div class="mt-1 text-muted" style="font-size: 13px; line-height: 1.4;">
                                                <div><i class="fas fa-industry text-primary"></i> <span
                                                        class="font-weight-bold">SX:</span>
                                                    {{ formatMins($month_data->status_production_minutes['Đã Pha chế'] ?? 0) }}
                                                </div>
                                                <div><i class="fas fa-broom text-warning"></i> <span
                                                        class="font-weight-bold">VS:</span>
                                                    {{ formatMins($month_data->status_cleaning_minutes['Đã Pha chế'] ?? 0) }}
                                                </div>
                                            </div>
                                        @endif
                                    </td>
                                    <td><button class="btn-status-count btn-show-actual-status-batches"
                                            data-filter-type="actual" data-month="{{ $month_data->month }}"
                                            data-status="Đã THT"
                                            {{ ($month_data->status_counts['Đã THT'] ?? 0) == 0 ? 'disabled' : '' }}>{{ $month_data->status_counts['Đã THT'] ?? 0 }}</button>
                                        @if (($month_data->status_counts['Đã THT'] ?? 0) > 0)
                                            <div class="mt-2">
                                                <span class="badge badge-success px-2 py-1"
                                                    style="font-size: 14px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                                                    {{ number_format($month_data->status_yields['Đã THT'] ?? 0) }}
                                                    <span style="font-size: 11px; font-weight: normal;">Kg</span>
                                                </span>
                                            </div>
                                            <div class="mt-1 text-muted" style="font-size: 13px; line-height: 1.4;">
                                                <div><i class="fas fa-industry text-primary"></i> <span
                                                        class="font-weight-bold">SX:</span>
                                                    {{ formatMins($month_data->status_production_minutes['Đã THT'] ?? 0) }}
                                                </div>
                                                <div><i class="fas fa-broom text-warning"></i> <span
                                                        class="font-weight-bold">VS:</span>
                                                    {{ formatMins($month_data->status_cleaning_minutes['Đã THT'] ?? 0) }}
                                                </div>
                                            </div>
                                        @endif
                                    </td>
                                    <td><button class="btn-status-count btn-show-actual-status-batches"
                                            data-filter-type="actual" data-month="{{ $month_data->month }}"
                                            data-status="Đã định hình"
                                            {{ ($month_data->status_counts['Đã định hình'] ?? 0) == 0 ? 'disabled' : '' }}>{{ $month_data->status_counts['Đã định hình'] ?? 0 }}</button>
                                        @if (($month_data->status_counts['Đã định hình'] ?? 0) > 0)
                                            <div class="mt-2">
                                                <span class="badge badge-success px-2 py-1"
                                                    style="font-size: 14px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                                                    {{ number_format($month_data->status_yields['Đã định hình'] ?? 0) }}
                                                    <span style="font-size: 11px; font-weight: normal;">ĐVL</span>
                                                </span>
                                            </div>
                                            <div class="mt-1 text-muted" style="font-size: 13px; line-height: 1.4;">
                                                <div><i class="fas fa-industry text-primary"></i> <span
                                                        class="font-weight-bold">SX:</span>
                                                    {{ formatMins($month_data->status_production_minutes['Đã định hình'] ?? 0) }}
                                                </div>
                                                <div><i class="fas fa-broom text-warning"></i> <span
                                                        class="font-weight-bold">VS:</span>
                                                    {{ formatMins($month_data->status_cleaning_minutes['Đã định hình'] ?? 0) }}
                                                </div>
                                            </div>
                                        @endif
                                    </td>
                                    <td><button class="btn-status-count btn-show-actual-status-batches"
                                            data-filter-type="actual" data-month="{{ $month_data->month }}"
                                            data-status="Đã Bao phim"
                                            {{ ($month_data->status_counts['Đã Bao phim'] ?? 0) == 0 ? 'disabled' : '' }}>{{ $month_data->status_counts['Đã Bao phim'] ?? 0 }}</button>
                                        @if (($month_data->status_counts['Đã Bao phim'] ?? 0) > 0)
                                            <div class="mt-2">
                                                <span class="badge badge-success px-2 py-1"
                                                    style="font-size: 14px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                                                    {{ number_format($month_data->status_yields['Đã Bao phim'] ?? 0) }}
                                                    <span style="font-size: 11px; font-weight: normal;">ĐVL</span>
                                                </span>
                                            </div>
                                            <div class="mt-1 text-muted" style="font-size: 13px; line-height: 1.4;">
                                                <div><i class="fas fa-industry text-primary"></i> <span
                                                        class="font-weight-bold">SX:</span>
                                                    {{ formatMins($month_data->status_production_minutes['Đã Bao phim'] ?? 0) }}
                                                </div>
                                                <div><i class="fas fa-broom text-warning"></i> <span
                                                        class="font-weight-bold">VS:</span>
                                                    {{ formatMins($month_data->status_cleaning_minutes['Đã Bao phim'] ?? 0) }}
                                                </div>
                                            </div>
                                        @endif
                                    </td>
                                    <td><button class="btn-status-count btn-show-actual-status-batches"
                                            data-filter-type="actual" data-month="{{ $month_data->month }}"
                                            data-status="Hoàn Tất ĐG"
                                            {{ ($month_data->status_counts['Hoàn Tất ĐG'] ?? 0) == 0 ? 'disabled' : '' }}>{{ $month_data->status_counts['Hoàn Tất ĐG'] ?? 0 }}</button>
                                        @if (($month_data->status_counts['Hoàn Tất ĐG'] ?? 0) > 0)
                                            <div class="mt-2">
                                                <span class="badge badge-success px-2 py-1"
                                                    style="font-size: 14px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                                                    {{ number_format($month_data->status_yields['Hoàn Tất ĐG'] ?? 0) }}
                                                    <span style="font-size: 11px; font-weight: normal;">ĐVL</span>
                                                </span>
                                            </div>
                                            <div class="mt-1 text-muted" style="font-size: 13px; line-height: 1.4;">
                                                <div><i class="fas fa-industry text-primary"></i> <span
                                                        class="font-weight-bold">SX:</span>
                                                    {{ formatMins($month_data->status_production_minutes['Hoàn Tất ĐG'] ?? 0) }}
                                                </div>
                                                <div><i class="fas fa-broom text-warning"></i> <span
                                                        class="font-weight-bold">VS:</span>
                                                    {{ formatMins($month_data->status_cleaning_minutes['Hoàn Tất ĐG'] ?? 0) }}
                                                </div>
                                            </div>
                                        @endif
                                    </td>
                                    @php
                                        $daysInMonth = \Carbon\Carbon::createFromFormat('m-Y', $month_data->month)
                                            ->daysInMonth;
                                        $workingDays = $daysInMonth - ($month_data->off_days ?? 0);
                                    @endphp

                                    <td class="align-middle text-center">{{ $month_data->off_days ?? 0 }}</td>
                                    <td class="align-middle text-center">{{ $workingDays }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        <!-- Biểu đồ tương quan Thực Tế -->
        @if (isset($summary_by_actual_month) && count($summary_by_actual_month) > 0)
            <div class="card card-info mt-3">
                <div class="card-header">
                    <h3 class="card-title">Biểu Đồ Tương Quan Thực Tế (Theo Tháng)</h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-tool" data-card-widget="collapse">
                            <i class="fas fa-minus"></i>
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-3">
                            <label for="correlationStageSelect">Chọn Công Đoạn:</label>
                            <select id="correlationStageSelect" class="form-control">
                                <option value="Đã Pha chế">Pha Chế</option>
                                <option value="Đã THT">Trộn Hoàn Tất</option>
                                <option value="Đã định hình">Định Hình</option>
                                <option value="Đã Bao phim">Bao Phim</option>
                                <option value="Hoàn Tất ĐG">Đóng Gói</option>
                            </select>
                        </div>
                    </div>
                    <div style="height: 800px; width: 100%;">
                        <canvas id="correlationChart"></canvas>
                    </div>
                </div>
            </div>
        @endif

        <!-- Modal Danh sách chờ -->
        <div class="modal fade" id="waitingPlanModal" tabindex="-1" role="dialog"
            aria-labelledby="waitingPlanModalLabel" aria-hidden="true">
            <div class="modal-dialog" style="max-width: 95%;" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <a href="{{ route('pages.general.home') }}">
                            <img src="{{ asset('img/iconstella.svg') }}" style="opacity: 0.8; max-width:45px;">
                        </a>
                        <h4 class="modal-title w-100 text-center" id="waitingPlanModalLabel"
                            style="color: #CDC717; font-size: 30px;">
                            Danh Sách Lịch Chưa Sắp <span id="waitingMonthTitle"></span>
                        </h4>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="row mb-3 align-items-center">
                            <div class="col-md-6 d-flex align-items-center">
                                <label for="stageFilter" class="mb-0 mr-2"
                                    style="white-space: nowrap; font-size: 20px;">Lọc theo Công Đoạn: </label>
                                <select id="stageFilter" class="form-control" style="width: 250px;">
                                    <option value="1">Cân Nguyên Liệu</option>
                                    <option value="2">Cân Nguyên Liệu Khác</option>
                                    <option value="3">Pha Chế</option>
                                    <option value="4">Trộn Hoàn Tất</option>
                                    <option value="5">Định Hình</option>
                                    <option value="6">Bao Phim</option>
                                    <option value="7">ĐGSC - ĐGTC</option>
                                </select>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table id="table_waiting_plan" class="table table-bordered table-striped"
                                style="width: 100%; font-size: 20px;">
                                <thead>
                                    <tr>
                                        <th>STT</th>
                                        <th>Kế hoạch tháng</th>
                                        <th>Tên Sản Phẩm</th>
                                        <th>Số Lô</th>
                                        <th>Ngày Dự Kiến KCS</th>
                                        <th>Ngày Đáp Ứng</th>
                                        <th>Mức độ Ưu tiên</th>
                                    </tr>
                                </thead>
                                <tbody>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Danh sách lô theo trạng thái -->
        <div class="modal fade" id="statusBatchesModal" tabindex="-1" role="dialog"
            aria-labelledby="statusBatchesModalLabel" aria-hidden="true">
            <div class="modal-dialog" style="max-width: 95%;" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <a href="{{ route('pages.general.home') }}">
                            <img src="{{ asset('img/iconstella.svg') }}" style="opacity: 0.8; max-width:45px;">
                        </a>
                        <h4 class="modal-title w-100 text-center" id="statusBatchesModalLabel"
                            style="color: #CDC717; font-size: 30px;">
                            Danh Sách Lô <span id="statusBatchesTitle"></span>
                        </h4>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="table-responsive">
                            <table id="table_status_batches" class="table table-bordered table-striped"
                                style="width: 100%; font-size: 20px;">
                                <thead>
                                    <tr>
                                        <th>STT</th>
                                        <th>Mã sản phẩm</th>
                                        <th>Tên sản phẩm</th>
                                        <th>Số lô</th>
                                        <th>Công đoạn tiếp theo</th>
                                        <th>Thời gian bắt đầu công đoạn tiếp theo</th>
                                        <th>Sản lượng</th>
                                    </tr>
                                </thead>
                                <tbody>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Danh sách lô theo trạng thái (Thực tế) -->
<div class="modal fade" id="actualStatusBatchesModal" tabindex="-1" role="dialog"
    aria-labelledby="actualStatusBatchesModalLabel" aria-hidden="true">
    <div class="modal-dialog" style="max-width: 95%;" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <a href="{{ route('pages.general.home') }}">
                    <img src="{{ asset('img/iconstella.svg') }}" style="opacity: 0.8; max-width:45px;">
                </a>
                <h4 class="modal-title w-100 text-center" id="actualStatusBatchesModalLabel"
                    style="color: #CDC717; font-size: 30px;">
                    Danh Sách Lô (Thực Tế) <span id="actualStatusBatchesTitle"></span>
                </h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table id="table_actual_status_batches" class="table table-bordered table-striped"
                        style="width: 100%; font-size: 20px;">
                        <thead>
                            <tr>
                                <th>STT</th>
                                <th>Mã sản phẩm</th>
                                <th>Tên sản phẩm</th>
                                <th>Số lô</th>
                                <th>Phòng sản xuất</th>
                                <th>thời gian sản xuất thực tế </th>
                                <th>thời gian vệ sinh thực tế </th>
                                <th>Sản lượng</th>
                            </tr>
                        </thead>
                        <tbody>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="{{ asset('js/vendor/jquery-1.12.4.min.js') }}"></script>
<script src="{{ asset('js/popper.min.js') }}"></script>
<script src="{{ asset('js/bootstrap.min.js') }}"></script>

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

<!-- Modal Phân bổ thiết bị -->
<div class="modal fade" id="equipmentAllocationModal" tabindex="-1" role="dialog" aria-labelledby="equipmentAllocationModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document" style="max-width: 90%;">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title" id="equipmentAllocationModalLabel"><i class="fas fa-chart-pie mr-2"></i>Phân bổ thiết bị cho kế hoạch</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <h5 id="equipmentAllocationPlanName" class="font-weight-bold text-center mb-2 text-primary"></h5>
                <div class="d-flex justify-content-center align-items-center mb-4 flex-wrap">
                    <div class="custom-control custom-switch mr-4">
                        <input type="checkbox" class="custom-control-input" id="groupByLineSwitch">
                        <label class="custom-control-label font-weight-bold text-secondary" style="cursor: pointer;" for="groupByLineSwitch">Thống kê theo dòng máy</label>
                    </div>
                    <div class="form-group mb-0 d-flex align-items-center">
                        <label for="stageCodeSelect" class="font-weight-bold text-secondary mb-0 mr-2">Công đoạn:</label>
                        <select id="stageCodeSelect" class="form-control form-control-sm" style="width: auto; min-width: 150px;">
                            <option value="all">Tất cả</option>
                            <option value="3">Pha chế</option>
                            <option value="4">Trộn hoàn tất</option>
                            <option value="5">Định hình</option>
                            <option value="6">Bao phim</option>
                            <option value="7" selected>Đóng gói</option>
                        </select>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered table-hover" id="equipmentAllocationTable">
                        <thead class="thead-light">
                            <tr>
                                <th class="text-center align-middle" style="width: 10%;">Mã Thiết bị</th>
                                <th class="text-center align-middle" style="width: 25%;">Tên Thiết bị</th>
                                <th class="text-center align-middle" style="width: 20%;">Loại Thiết bị</th>
                                <th class="text-center align-middle" colspan="2" style="width: 45%;">So Sánh</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Dữ liệu sẽ được load qua AJAX -->
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
            </div>
        </div>
    </div>
</div>

<script>
    $(document).ready(function() {
        var currentPlanListId = null;

        function loadEquipmentAllocation() {
            if (!currentPlanListId) return;
            
            var isGroupByLine = $('#groupByLineSwitch').is(':checked');
            var stageCode = $('#stageCodeSelect').val();
            var url = '{{ url("plan/production/equipment_allocation") }}/' + currentPlanListId + '?stage_code=' + stageCode + '&department_code={{ $production_code }}';
            if (isGroupByLine) {
                url += '&group_by=line';
            }
            
            $('#equipmentAllocationTable tbody').html('<tr><td colspan="5" class="text-center py-4"><i class="fas fa-spinner fa-spin fa-2x text-info"></i><br>Đang tải dữ liệu...</td></tr>');

            $.ajax({
                url: url,
                type: 'GET',
                success: function(response) {
                    if (response.success) {
                        var tbody = '';
                        if (response.data.length === 0) {
                            tbody = '<tr><td colspan="5" class="text-center py-4">Không có dữ liệu định mức thiết bị cho kế hoạch này.</td></tr>';
                        } else {
                            // Sort data: blister_type_code -> room_order_by -> total_batches
                            response.data.sort(function(a, b) {
                                // 1. Sort by blister_type_code
                                if (a.blister_type_code !== null && b.blister_type_code !== null) {
                                    if (a.blister_type_code !== b.blister_type_code) {
                                        return a.blister_type_code - b.blister_type_code;
                                    }
                                } else if (a.blister_type_code !== null) {
                                    return -1;
                                } else if (b.blister_type_code !== null) {
                                    return 1;
                                }
                                
                                // 2. Sort by room_order_by
                                var orderA = (a.room_order_by !== null && a.room_order_by !== undefined) ? parseInt(a.room_order_by) : 9999;
                                var orderB = (b.room_order_by !== null && b.room_order_by !== undefined) ? parseInt(b.room_order_by) : 9999;
                                if (orderA !== orderB) {
                                    return orderA - orderB;
                                }
                                
                                // 3. Fallback to total_batches descending
                                return b.total_batches - a.total_batches;
                            });
                            
                            var maxBatches = 0;
                            var maxQty = 0;
                            response.data.forEach(function(item) {
                                if (item.total_batches > maxBatches) maxBatches = item.total_batches;
                                if (item.total_quantity > maxQty) maxQty = item.total_quantity;
                            });
                            if (maxBatches === 0) maxBatches = 1;
                            if (maxQty === 0) maxQty = 1;

                            response.data.forEach(function(item) {
                                var qty = (item.total_quantity || 0).toLocaleString('en-US');
                                
                                var widthBatches = (item.total_batches / maxBatches) * 100;
                                var widthQty = (item.total_quantity / maxQty) * 100;
                                
                                var batchBarHtml = '<div style="width: 100%; height: 24px; position: relative;">' +
                                    '<div style="background-color: #e83e8c; width: ' + Math.max(widthBatches, 5) + '%; height: 100%; border-radius: 6px; display: flex; align-items: center; justify-content: center; transition: width 0.5s ease; min-width: fit-content; padding: 0 10px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">' +
                                    '<span style="color: white; font-weight: bold; font-size: 0.85rem; white-space: nowrap;">' + item.total_batches + '</span>' +
                                    '</div>' +
                                    '</div>';
                                    
                                var qtyBarHtml = '<div style="width: 100%; height: 24px; position: relative;">' +
                                    '<div style="background-color: #28a745; width: ' + Math.max(widthQty, 5) + '%; height: 100%; border-radius: 6px; display: flex; align-items: center; justify-content: center; transition: width 0.5s ease; min-width: fit-content; padding: 0 10px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">' +
                                    '<span style="color: white; font-weight: bold; font-size: 0.85rem; white-space: nowrap;">' + qty + '</span>' +
                                    '</div>' +
                                    '</div>';

                                var isUnfinishedPlan = (currentPlanListId == -1 || currentPlanListId === '-1');
                                var rowspan = isUnfinishedPlan ? 4 : 2;

                                tbody += '<tr>' +
                                    '<td class="text-center font-weight-bold align-middle" rowspan="' + rowspan + '" style="border-bottom: 2px solid #dee2e6;">' + (item.equipment_code || 'NA') + '</td>' +
                                    '<td class="align-middle" rowspan="' + rowspan + '" style="border-bottom: 2px solid #dee2e6;">' + (item.equipment_name || 'NA') + '</td>' +
                                    '<td class="text-center align-middle" rowspan="' + rowspan + '" style="border-bottom: 2px solid #dee2e6;">' + (item.main_equipment_name || 'NA') + '</td>' +
                                    '<td class="text-right align-middle border-bottom-0 text-secondary pr-4 py-1" style="width: 15%; font-size: 0.9rem;">Tổng số Lô Có Thể Sắp</td>' +
                                    '<td class="align-middle border-bottom-0 p-1" style="width: 30%;">' + batchBarHtml + '</td>' +
                                    '</tr>' +
                                    '<tr>' +
                                    '<td class="text-right align-middle border-top-0 text-secondary pr-4 py-1" style="' + (isUnfinishedPlan ? 'border-bottom-0' : 'border-bottom: 2px solid #dee2e6;') + ' font-size: 0.9rem;">Sản lượng lý thuyết</td>' +
                                    '<td class="align-middle border-top-0 p-1" style="' + (isUnfinishedPlan ? 'border-bottom-0' : 'border-bottom: 2px solid #dee2e6;') + '">' + qtyBarHtml + '</td>' +
                                    '</tr>';

                                if (isUnfinishedPlan) {
                                    var scheduled = item.scheduled_batches || 0;
                                    var inventory = item.inventory_qty || 0;
                                    
                                    var widthScheduled = maxBatches > 0 ? (scheduled / maxBatches) * 100 : 0;
                                    var widthInventory = maxQty > 0 ? (inventory / maxQty) * 100 : 0;
                                    
                                    if (widthScheduled > 100) widthScheduled = 100;
                                    if (widthInventory > 100) widthInventory = 100;
                                    
                                    var invDisplay = parseFloat(inventory).toLocaleString('en-US');

                                    var schedBarHtml = '<div style="width: 100%; height: 24px; position: relative;">' +
                                        '<div style="background-color: #17a2b8; width: ' + Math.max(widthScheduled, 5) + '%; height: 100%; border-radius: 6px; display: flex; align-items: center; justify-content: center; transition: width 0.5s ease; min-width: fit-content; padding: 0 10px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">' +
                                        '<span style="color: white; font-weight: bold; font-size: 0.85rem; white-space: nowrap;">' + scheduled + '</span>' +
                                        '</div></div>';

                                    var invBarHtml = '<div style="width: 100%; height: 24px; position: relative;">' +
                                        '<div style="background-color: #ffc107; width: ' + Math.max(widthInventory, 5) + '%; height: 100%; border-radius: 6px; display: flex; align-items: center; justify-content: center; transition: width 0.5s ease; min-width: fit-content; padding: 0 10px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">' +
                                        '<span style="color: white; font-weight: bold; font-size: 0.85rem; white-space: nowrap;">' + invDisplay + '</span>' +
                                        '</div></div>';

                                    tbody += '<tr>' +
                                        '<td class="text-right align-middle border-top-0 text-secondary pr-4 py-1" style="border-bottom-0; font-size: 0.9rem;">Số lượng lô đã sắp</td>' +
                                        '<td class="align-middle border-top-0 p-1" style="border-bottom-0;">' + schedBarHtml + '</td>' +
                                        '</tr>' +
                                        '<tr>' +
                                        '<td class="text-right align-middle border-top-0 text-secondary pr-4 py-1" style="border-bottom: 2px solid #dee2e6; font-size: 0.9rem;">Sản lượng tồn kho (công đoạn trước)</td>' +
                                        '<td class="align-middle border-top-0 p-1" style="border-bottom: 2px solid #dee2e6;">' + invBarHtml + '</td>' +
                                        '</tr>';
                                }
                            });
                        }
                        $('#equipmentAllocationTable tbody').html(tbody);
                    } else {
                        $('#equipmentAllocationTable tbody').html('<tr><td colspan="5" class="text-center text-danger">Có lỗi xảy ra khi tải dữ liệu.</td></tr>');


                    }
                },
                error: function() {
                    $('#equipmentAllocationTable tbody').html('<tr><td colspan="5" class="text-center text-danger">Lỗi kết nối máy chủ.</td></tr>');
                }
            });
        }

        $(document).on('click', '.btn-equipment-allocation', function() {
            currentPlanListId = $(this).data('plan-list-id');
            var planName = $(this).data('plan-name');
            
            $('#equipmentAllocationPlanName').text('Kế hoạch: ' + planName);
            $('#groupByLineSwitch').prop('checked', false);
            $('#equipmentAllocationModal').modal('show');
            
            loadEquipmentAllocation();
        });

        $('#groupByLineSwitch, #stageCodeSelect').change(function() {
            loadEquipmentAllocation();
        });

        document.body.style.overflowY = "auto";
        $('#table_month').DataTable({
            "responsive": true,
            "autoWidth": false,
        });

        let waitingTable = null;

        $('.btn-show-waiting').on('click', function() {
            let month = $(this).data('month');
            $('#waitingMonthTitle').text('(Tháng ' + month + ')');
            $('#waitingPlanModal').modal('show');

            if (waitingTable) {
                waitingTable.destroy();
                $('#table_waiting_plan tbody').empty();
            }

            // Gọi AJAX lấy dữ liệu
            $.ajax({
                url: "{{ route('pages.plan.production.get_waiting_plans') }}",
                type: "GET",
                data: {
                    month: month
                },
                success: function(res) {
                    let html = '';
                    let stageNames = {
                        1: "Cân Nguyên Liệu",
                        3: "Pha Chế",
                        4: "Trộn Hoàn Tất",
                        5: "Định Hình",
                        6: "Bao Phim",
                        7: "ĐGSC - ĐGTC",
                        8: "N/A"
                    };

                    res.forEach(function(item, index) {
                        let levelColors = {
                            1: 'badge-danger',
                            2: 'badge-warning text-dark',
                            3: 'badge-primary',
                            4: 'badge-success'
                        };
                        let levelClass = levelColors[item.level] ||
                            'badge-secondary';
                        let levelHtml = item.level ?
                            `<span class="badge ${levelClass}" style="font-size: 16px; padding: 6px 10px; width: 40px;">${item.level}</span>` :
                            '';

                        let expectedDate = item.expected_date ? item.expected_date
                            .split('-').reverse().join('/') : '';
                        let responsedDate = item.responsed_date ? item
                            .responsed_date.split('-').reverse().join('/') : '';
                        let monthStr = item.month ? item.month.replace('-', '/') :
                            '';

                        html += `<tr data-stage="${item.stage_code}">
                            <td>${index + 1}</td>
                            <td>${monthStr}</td>
                            <td>${item.name || ''}</td>
                            <td>${item.batch || ''}</td>
                            <td>${expectedDate}</td>
                            <td>${responsedDate}</td>
                            <td>${levelHtml}</td>
                        </tr>`;
                    });

                    $('#table_waiting_plan tbody').html(html);

                    waitingTable = $('#table_waiting_plan').DataTable({
                        "responsive": true,
                        "autoWidth": false,
                        "pageLength": 10
                    });

                    // Add custom filter logic if not added
                    if (!window.customStageFilterAdded) {
                        $.fn.dataTable.ext.search.push(function(settings, data, dataIndex,
                            rowData, counter) {
                            if (settings.nTable.id !== 'table_waiting_plan')
                                return true;
                            let selectedStage = $('#stageFilter').val();
                            if (!selectedStage) return true;
                            let trNode = settings.aoData[dataIndex].nTr;
                            let rowStage = $(trNode).attr('data-stage');
                            return selectedStage == rowStage;
                        });
                        window.customStageFilterAdded = true;
                    }

                    $('#stageFilter').off('change').on('change', function() {
                        waitingTable.draw();
                    });

                    // Lọc hiển thị ngay từ đầu theo giá trị mặc định của select box
                    waitingTable.draw();
                }
            });
        });

        let statusBatchesTable = null;

        $('.btn-show-status-batches').on('click', function() {
            let month = $(this).data('month');
            let plan_list_id = $(this).data('plan-list-id');
            let status = $(this).data('status');
            let filter_type = $(this).data('filter-type') || 'expected';

            let titleText = `- Trạng thái: ${status}`;
            if (month) titleText += ` (Tháng ${month})`;

            $('#statusBatchesTitle').text(titleText);
            $('#statusBatchesModal').modal('show');

            if (statusBatchesTable) {
                statusBatchesTable.destroy();
                $('#table_status_batches tbody').empty();
            }

            $.ajax({
                url: "{{ route('pages.plan.production.get_batches_by_status') }}",
                type: "GET",
                data: {
                    month: month,
                    plan_list_id: plan_list_id,
                    status: status,
                    filter_type: filter_type
                },
                success: function(res) {
                    let html = '';
                    res.forEach(function(item, index) {
                        html += `<tr>
                            <td>${index + 1}</td>
                            <td>${item.ma_san_pham || ''}</td>
                            <td>${item.ten_san_pham || ''}</td>
                            <td>${item.so_lo || ''}</td>
                            <td>${item.cong_doan_tiep_theo || ''}</td>
                            <td>${item.thoi_gian_bat_dau || ''}</td>
                            <td>${new Intl.NumberFormat().format(item.san_luong || 0)}</td>
                        </tr>`;
                    });

                    $('#table_status_batches tbody').html(html);

                    statusBatchesTable = $('#table_status_batches').DataTable({
                        "responsive": true,
                        "autoWidth": false,
                        "pageLength": 10
                    });
                }
            });
        });
        let actualStatusBatchesTable = null;

        $('.btn-show-actual-status-batches').on('click', function() {
            let month = $(this).data('month');
            let plan_list_id = $(this).data('plan-list-id');
            let status = $(this).data('status');
            let filter_type = $(this).data('filter-type') || 'actual';

            let titleText = `- Trạng thái: ${status}`;
            if (month) titleText += ` (Tháng ${month})`;

            $('#actualStatusBatchesTitle').text(titleText);
            $('#actualStatusBatchesModal').modal('show');

            if (actualStatusBatchesTable) {
                actualStatusBatchesTable.destroy();
                $('#table_actual_status_batches tbody').empty();
            }

            $.ajax({
                url: "{{ route('pages.plan.production.get_batches_by_status') }}",
                type: "GET",
                data: {
                    month: month,
                    plan_list_id: plan_list_id,
                    status: status,
                    filter_type: filter_type
                },
                success: function(res) {
                    let html = '';
                    res.forEach(function(item, index) {
                        html += `<tr>
                            <td>${index + 1}</td>
                            <td>${item.ma_san_pham || ''}</td>
                            <td>${item.ten_san_pham || ''}</td>
                            <td>${item.so_lo || ''}</td>
                            <td>${item.phong_san_xuat || ''}</td>
                            <td>${item.thoi_gian_san_xuat_thuc_te || ''}</td>
                            <td>${item.thoi_gian_ve_sinh_thuc_te || ''}</td>
                            <td>${new Intl.NumberFormat().format(item.san_luong || 0)}</td>
                        </tr>`;
                    });

                    $('#table_actual_status_batches tbody').html(html);

                    actualStatusBatchesTable = $('#table_actual_status_batches').DataTable({
                        "responsive": true,
                        "autoWidth": false,
                        "pageLength": 10
                    });
                }
            });
        });
    });
</script>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        @if (isset($summary_by_actual_month) && count($summary_by_actual_month) > 0)
            const actualSummaryData = @json(array_values($summary_by_actual_month->toArray()));
            let correlationChart = null;

            function renderCorrelationChart() {
                const stage = document.getElementById('correlationStageSelect').value;
                const canvas = document.getElementById('correlationChart');
                if (!canvas) return;
                const ctx = canvas.getContext('2d');

                if (correlationChart) {
                    correlationChart.destroy();
                }

                // Prepare data arrays
                const labels = [];
                const yieldData = [];
                const batchCounts = [];
                const prodHours = [];
                const cleanHours = [];
                const workingDays = [];
                const offDays = [];

                // Sort data by month string "MM-YYYY" chronological
                const sortedData = [...actualSummaryData].sort((a, b) => {
                    let [m1, y1] = a.month.split('-');
                    let [m2, y2] = b.month.split('-');
                    return new Date(y1, m1 - 1) - new Date(y2, m2 - 1);
                });

                function getDaysInMonth(monthStr) {
                    let parts = monthStr.split('-');
                    return new Date(parts[1], parts[0], 0).getDate();
                }

                sortedData.forEach(item => {
                    labels.push(item.month);
                    yieldData.push(item.status_yields ? (item.status_yields[stage] || 0) : 0);
                    batchCounts.push(item.status_counts ? (item.status_counts[stage] || 0) : 0);
                    
                    let pMin = item.status_production_minutes ? (item.status_production_minutes[stage] || 0) : 0;
                    let cMin = item.status_cleaning_minutes ? (item.status_cleaning_minutes[stage] || 0) : 0;
                    prodHours.push((pMin / 60).toFixed(1));
                    cleanHours.push((cMin / 60).toFixed(1));

                    let totalD = getDaysInMonth(item.month);
                    let offD = item.off_days || 0;
                    offDays.push(offD);
                    workingDays.push(totalD - offD);
                });

                correlationChart = new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: labels,
                        datasets: [
                            {
                                label: 'Sản lượng (Kg/ĐVL)',
                                type: 'bar',
                                backgroundColor: 'rgba(54, 162, 235, 0.6)',
                                borderColor: 'rgba(54, 162, 235, 1)',
                                borderWidth: 1,
                                yAxisID: 'y-axis-yield',
                                data: yieldData
                            },
                            {
                                label: 'Số lượng lô',
                                type: 'bar',
                                backgroundColor: 'rgba(23, 162, 184, 0.6)',
                                borderColor: 'rgba(23, 162, 184, 1)',
                                borderWidth: 1,
                                yAxisID: 'y-axis-batch',
                                data: batchCounts
                            },
                            {
                                label: 'TG Sản xuất (Giờ)',
                                type: 'line',
                                fill: false,
                                borderColor: '#ffc107',
                                backgroundColor: '#ffc107',
                                yAxisID: 'y-axis-time',
                                data: prodHours
                            },
                            {
                                label: 'TG Vệ sinh (Giờ)',
                                type: 'line',
                                fill: false,
                                borderColor: '#dc3545',
                                backgroundColor: '#dc3545',
                                yAxisID: 'y-axis-time',
                                data: cleanHours
                            },
                            {
                                label: 'Ngày Làm',
                                type: 'line',
                                fill: false,
                                borderColor: '#28a745',
                                backgroundColor: '#28a745',
                                yAxisID: 'y-axis-days',
                                data: workingDays
                            },
                            {
                                label: 'Ngày Nghỉ',
                                type: 'line',
                                fill: false,
                                borderColor: '#6c757d',
                                backgroundColor: '#6c757d',
                                yAxisID: 'y-axis-days',
                                data: offDays
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        tooltips: {
                            mode: 'index',
                            intersect: false,
                        },
                        scales: {
                            yAxes: [
                                {
                                    id: 'y-axis-yield',
                                    type: 'linear',
                                    position: 'left',
                                    scaleLabel: {
                                        display: true,
                                        labelString: 'Sản lượng'
                                    },
                                    ticks: { beginAtZero: true }
                                },
                                {
                                    id: 'y-axis-batch',
                                    type: 'linear',
                                    position: 'left',
                                    scaleLabel: {
                                        display: true,
                                        labelString: 'Số lô'
                                    },
                                    ticks: { beginAtZero: true },
                                    gridLines: { drawOnChartArea: false }
                                },
                                {
                                    id: 'y-axis-time',
                                    type: 'linear',
                                    position: 'right',
                                    scaleLabel: {
                                        display: true,
                                        labelString: 'Thời gian (Giờ)'
                                    },
                                    ticks: { beginAtZero: true },
                                    gridLines: { drawOnChartArea: false }
                                },
                                {
                                    id: 'y-axis-days',
                                    type: 'linear',
                                    position: 'right',
                                    scaleLabel: {
                                        display: true,
                                        labelString: 'Số ngày'
                                    },
                                    ticks: { beginAtZero: true },
                                    gridLines: { drawOnChartArea: false }
                                }
                            ]
                        }
                    }
                });
            }

            document.getElementById('correlationStageSelect').addEventListener('change', renderCorrelationChart);
            
            // Init chart
            renderCorrelationChart();
        @endif
    });
</script>
