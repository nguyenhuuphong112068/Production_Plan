@extends('layout.master')

@section('topNAV')
    @include('layout.topNAV')
@endsection

@section('leftNAV')
    @include('layout.leftNAV')
@endsection

@section('mainContent')
    <div class="content-wrapper" style="height: 100vh; overflow-y: auto; overflow-x: hidden; padding-bottom: 50px;">
        <section class="content-header" style="padding-top: 60px;">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1>Dashboard Tình Hình Nhân Sự</h1>
                    </div>
                    <div class="col-sm-6 text-right">
                    </div>
                </div>
            </div>
        </section>

        <section class="content">
            <div class="container-fluid">
                <!-- Filter Form -->
                <div class="card card-primary card-outline">
                    <div class="card-body">
                        <form id="filterForm">
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label>Phân xưởng</label>
                                        <select class="form-control" name="production_code" id="production_code">
                                            @foreach ($departments as $code => $name)
                                                <option value="{{ $code }}"
                                                    {{ session('user')['production_code'] == $code ? 'selected' : '' }}>
                                                    {{ $name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label>Tổ (Nhóm)</label>
                                        <select class="form-control" name="group_id" id="group_id">
                                            <option value="">-- Tất cả --</option>
                                            @foreach ($groups as $g)
                                                <option value="{{ $g->code }}">{{ $g->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label>Loại thống kê</label>
                                        <select class="form-control" name="type" id="type">
                                            <option value="day">Theo Ngày</option>
                                            <option value="week">Theo Tuần</option>
                                            <option value="month">Theo Tháng</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label>Ngày (chọn để xác định thời điểm)</label>
                                        <input type="date" class="form-control" name="date" id="date"
                                            value="{{ \Carbon\Carbon::now()->format('Y-m-d') }}">
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <div id="loading" class="text-center my-4" style="display:none;">
                    <div class="spinner-border text-primary" role="status">
                        <span class="sr-only">Loading...</span>
                    </div>
                    <p>Đang tải dữ liệu...</p>
                </div>

                <div id="dashboardContent" style="display:none;">
                    <p class="text-muted">Khoảng thời gian: <span id="period_text" class="font-weight-bold"></span></p>

                    <!-- KPI Cards -->
                    <div class="row">
                        <div class="col-lg col-md-4 col-6 mb-3">
                            <div class="small-box bg-info h-100 mb-0">
                                <div class="inner">
                                    <h3 id="kpi_total">0</h3>
                                    <p id="kpi_total_label">Tổng nhân sự</p>
                                </div>
                                <div class="icon"><i class="fas fa-users"></i></div>
                            </div>
                        </div>
                        <div class="col-lg col-md-4 col-6 mb-3">
                            <div class="small-box bg-maroon h-100 mb-0"
                                onclick="showDailyStats('maternity_leave', 'Nghỉ thai sản', 'bg-maroon')"
                                style="cursor:pointer;" title="Click để xem chi tiết theo ngày">
                                <div class="inner">
                                    <h3 id="kpi_maternity">0</h3>
                                    <p id="kpi_maternity_label">Nghỉ thai sản</p>
                                </div>
                                <div class="icon"><i class="fas fa-baby"></i></div>
                            </div>
                        </div>
                        <div class="col-lg col-md-4 col-6 mb-3">
                            <div class="small-box bg-secondary h-100 mb-0"
                                onclick="showDailyStats('on_leave', 'Nghỉ phép (P)', 'bg-secondary')"
                                style="cursor:pointer;" title="Click để xem chi tiết theo ngày">
                                <div class="inner">
                                    <h3 id="kpi_on_leave">0</h3>
                                    <p id="kpi_on_leave_label">Nghỉ phép (P)</p>
                                </div>
                                <div class="icon"><i class="fas fa-bed"></i></div>
                            </div>
                        </div>
                        <div class="col-lg col-md-4 col-6 mb-3">
                            <div class="small-box bg-danger h-100 mb-0"
                                onclick="showDailyStats('unassigned', 'Chưa xếp lịch (0h)', 'bg-danger')"
                                style="cursor:pointer;" title="Click để xem chi tiết theo ngày">
                                <div class="inner">
                                    <h3 id="kpi_unassigned">0</h3>
                                    <p id="kpi_unassigned_label">Chưa xếp lịch (0h)</p>
                                </div>
                                <div class="icon"><i class="fas fa-user-times"></i></div>
                            </div>
                        </div>
                        <div class="col-lg col-md-4 col-6 mb-3">
                            <div class="small-box bg-warning h-100 mb-0"
                                onclick="showDailyStats('under_8h', '< 8h / ngày', 'bg-warning')" style="cursor:pointer;"
                                title="Click để xem chi tiết theo ngày">
                                <div class="inner">
                                    <h3 id="kpi_under8">0</h3>
                                    <p id="kpi_under8_label">
                                        < 8h / ngày</p>
                                </div>
                                <div class="icon"><i class="fas fa-battery-half"></i></div>
                            </div>
                        </div>
                        <div class="col-lg col-md-4 col-6 mb-3">
                            <div class="small-box bg-success h-100 mb-0"
                                onclick="showDailyStats('exact_8h', 'Đủ 8h / ngày', 'bg-success')" style="cursor:pointer;"
                                title="Click để xem chi tiết theo ngày">
                                <div class="inner">
                                    <h3 id="kpi_exact8">0</h3>
                                    <p id="kpi_exact8_label">Đủ 8h / ngày</p>
                                </div>
                                <div class="icon"><i class="fas fa-battery-full"></i></div>
                            </div>
                        </div>
                        <div class="col-lg col-md-4 col-6 mb-3">
                            <div class="small-box bg-primary h-100 mb-0"
                                onclick="showDailyStats('over_8h', '> 8h / ngày', 'bg-primary')" style="cursor:pointer;"
                                title="Click để xem chi tiết theo ngày">
                                <div class="inner">
                                    <h3 id="kpi_over8">0</h3>
                                    <p id="kpi_over8_label">> 8h / ngày</p>
                                </div>
                                <div class="icon"><i class="fas fa-battery-full text-white-50"></i><i
                                        class="fas fa-plus"
                                        style="font-size: 0.5em; position: absolute; top: 0; right: 0;"></i></div>
                            </div>
                        </div>
                        <div class="col-lg col-md-4 col-6 mb-3">
                            <div class="small-box h-100 mb-0"
                                onclick="showDailyStats('total_ot_hours', 'Tổng Tăng Ca (TC)', '', 'linear-gradient(135deg, #f093fb 0%, #f5576c 100%)')"
                                style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: #fff; cursor:pointer;"
                                title="Click để xem chi tiết theo ngày">
                                <div class="inner">
                                    <h3 id="kpi_total_ot">0h <span style="font-size: 0.5em; font-weight: normal;"
                                            id="kpi_total_ot_people"></span></h3>
                                    <p id="kpi_total_ot_label"><i class="fas fa-clock mr-1"></i>Tổng Tăng Ca theo e-office
                                        (TC)</p>
                                </div>
                                <div class="icon"><i class="fas fa-fire-alt"></i></div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <!-- Card cấu hình chính sách -->

                        <div class="col-md-8 mb-3">
                            <div class="card card-outline card-warning h-100 shadow-sm">
                                <div class="card-header bg-warning text-dark">
                                    <h3 class="card-title font-weight-bold"><i class="fas fa-cogs"></i> Chính Sách Tăng Ca
                                    </h3>
                                    <div class="card-tools">
                                        <button class="btn btn-sm btn-info text-white shadow-sm" style="border:none;"
                                            onclick="openHistoryModal()"><i class="fas fa-history"></i> Xem lịch
                                            sử</button>
                                    </div>
                                </div>
                                <div class="card-body p-0">
                                    <div style="max-height: 280px; overflow-y: auto;">
                                        <form id="policyForm">
                                            <table class="table table-sm table-striped mb-0">
                                                <thead class="bg-light" style="position:sticky;top:0;z-index:1;">
                                                    <tr>
                                                        <th>Cấp độ</th>
                                                        <th>Tổ / Nhóm</th>
                                                        <th style="width:150px" class="text-center">Tối đa Người/Ngày</th>
                                                        <th style="width:150px" class="text-center">Tối đa Giờ/Ngày</th>
                                                    </tr>
                                                </thead>
                                                <tbody id="policyTableBody">
                                                    <tr>
                                                        <td colspan="4" class="text-center text-muted py-3">Đang tải...
                                                        </td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </form>
                                    </div>
                                </div>
                                <div class="card-footer text-right p-2 bg-light">
                                    <button type="button" class="btn btn-primary btn-sm px-4 shadow-sm"
                                        onclick="savePolicy()" {{ user_has_permission(session('user')['userId'], 'Authorize_overtime', 'boolean') ? '' : 'disabled' }}><i class="fas fa-save"></i> Lưu Cấu Hình</button>
                                </div>
                            </div>
                        </div>


                        <!-- Thống kê OT theo Tổ -->
                        <div class="col-md-4 mb-3" id="otSummaryRow">
                            <div class="card card-outline h-100 shadow-sm" style="border-color: #f5576c;">
                                <div class="card-header"
                                    style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color:#fff;">
                                    <h3 class="card-title"><i class="fas fa-layer-group mr-2"></i>Tăng Ca Theo Tổ</h3>
                                </div>
                                <div class="card-body p-0">
                                    <div style="max-height: 280px; overflow-y: auto;">
                                        <table class="table table-sm table-striped mb-0">
                                            <thead class="bg-light" style="position:sticky;top:0;z-index:1;">
                                                <tr>
                                                    <th>Tổ / Nhóm</th>
                                                    <th class="text-center">Số NS</th>
                                                    <th class="text-right text-danger font-weight-bold">Tổng TC (h)</th>
                                                    <th class="text-right">Số nhân sự TC</th>
                                                </tr>
                                            </thead>
                                            <tbody id="otGroupTableBody">
                                                <tr>
                                                    <td colspan="4" class="text-center text-muted py-3">Chưa có dữ liệu
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <!-- Biểu đồ tỷ lệ -->
                        <div class="col-md-6 mb-3">
                            <div class="card card-outline card-success h-100 shadow-sm">
                                <div class="card-header">
                                    <h3 class="card-title">Tỷ lệ phân công</h3>
                                </div>
                                <div class="card-body">
                                    <canvas id="assignmentPieChart"
                                        style="min-height: 250px; height: 250px; max-height: 250px; max-width: 100%;"></canvas>
                                </div>
                            </div>
                        </div>

                        <!-- Biểu đồ tổng quan -->
                        <div class="col-md-6 mb-3">
                            <div class="card card-outline card-info h-100 shadow-sm">
                                <div class="card-header">
                                    <h3 class="card-title">Biểu đồ tổng quan</h3>
                                </div>
                                <div class="card-body">
                                    <canvas id="assignmentBarChart"
                                        style="min-height: 250px; height: 250px; max-height: 250px; max-width: 100%;"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Bảng chi tiết nhân sự -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Danh sách chi tiết nhân sự</h3>
                            <div class="card-tools">
                                <input type="text" id="searchDetail" class="form-control form-control-sm"
                                    placeholder="Tìm kiếm..." style="width:200px;">
                            </div>
                        </div>
                        <div class="card-body table-responsive p-0">
                            <table class="table table-head-fixed text-nowrap table-striped table-hover" id="detailTable">
                                <thead>
                                    <tr>
                                        <th>Mã NS</th>
                                        <th>Họ và Tên</th>
                                        <th>Tổ</th>
                                        <th>Đăng ký đi ca</th>
                                        <th>Tổng giờ phân công</th>
                                        <th>Giờ làm việc theo e-office</th>
                                        <th class="text-danger"><i class="fas fa-clock mr-1"></i>TC (h)</th>
                                        <th id="th_status">Trạng thái</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- Data populated via JS -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>



    <!-- Modal Lịch Sử -->
    <div class="modal fade" id="historyModal" tabindex="-1" role="dialog" aria-hidden="true" style="z-index: 1060;">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title font-weight-bold"><i class="fas fa-history"></i> Lịch Sử Thay Đổi Chính Sách
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body" style="max-height: 500px; overflow-y: auto;">
                    <div id="historyContent">
                        <!-- JS gen -->
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Thống Kê Theo Ngày -->
    <div class="modal fade" id="dailyStatsModal" tabindex="-1" role="dialog" aria-hidden="true"
        style="z-index: 1060;">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white" id="dailyStatsHeader">
                    <h5 class="modal-title font-weight-bold" id="dailyStatsTitle">Chi Tiết Theo Ngày</h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body" style="max-height: 500px; overflow-y: auto;">
                    <table class="table table-bordered table-striped text-center table-sm">
                        <thead class="bg-light">
                            <tr>
                                <th>Ngày</th>
                                <th id="dailyStatsUnit">Số Lượng (Người)</th>
                            </tr>
                        </thead>
                        <tbody id="dailyStatsBody">
                            <!-- JS gen -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- ChartJS -->
    <script src="{{ asset('assets/plugins/local_cdn/chart.min.js') }}"></script>
    <!-- SweetAlert2 -->
    <script src="{{ asset('assets/plugins/local_cdn/sweetalert2.min.js') }}"></script>

    <script>
        let globalStatsDaily = [];
        let currentPieChart = null;
        let currentBarChart = null;
        let allDetails = [];
        let globalAvailableGroups = [];
        document.getElementById('filterForm').addEventListener('submit', function(e) {
            e.preventDefault();
            loadData();
        });

        // Auto load on init
        document.addEventListener("DOMContentLoaded", function() {
            loadData();
        });

        // Reset Tổ và tự động load lại khi đổi Phân xưởng
        document.getElementById('production_code').addEventListener('change', function() {
            document.getElementById('group_id').value = '';
            loadData();
        });

        // Auto load khi thay đổi các filter khác
        document.getElementById('group_id').addEventListener('change', loadData);
        document.getElementById('type').addEventListener('change', loadData);
        document.getElementById('date').addEventListener('change', loadData);

        // Search detail table
        document.getElementById('searchDetail').addEventListener('input', function() {
            const q = this.value.toLowerCase();
            filterDetailTable(q);
        });

        function filterDetailTable(q) {
            const tbody = document.querySelector('#detailTable tbody');
            const rows = tbody.querySelectorAll('tr');
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = (!q || text.includes(q)) ? '' : 'none';
            });
        }

        function loadData() {
            const formData = new FormData(document.getElementById('filterForm'));
            const params = new URLSearchParams(formData).toString();

            document.getElementById('loading').style.display = 'block';
            document.getElementById('dashboardContent').style.display = 'none';

            fetch(`{{ route('pages.assignment.dashboard.data') }}?${params}`)
                .then(res => res.json())
                .then(data => {
                    document.getElementById('loading').style.display = 'none';

                    if (data.success) {
                        renderDashboard(data);
                        document.getElementById('dashboardContent').style.display = 'block';
                    } else {
                        alert('Lỗi tải dữ liệu');
                    }
                })
                .catch(err => {
                    console.error(err);
                    document.getElementById('loading').style.display = 'none';
                    alert('Lỗi kết nối máy chủ');
                });
        }

        function renderDashboard(data) {
            // Update Groups Dropdown dynamically
            const groupSelect = document.getElementById('group_id');
            const currentSelectedGroup = groupSelect.value;
            groupSelect.innerHTML = '<option value="">-- Tất cả --</option>';
            globalAvailableGroups = data.available_groups || [];
            if (globalAvailableGroups.length > 0) {
                globalAvailableGroups.forEach(g => {
                    const isSelected = (g.code == currentSelectedGroup) ? 'selected' : '';
                    groupSelect.innerHTML += `<option value="${g.code}" ${isSelected}>${g.name}</option>`;
                });
            }

            loadPolicyData();

            // Update KPIs
            const days = data.period ? data.period.days : 1;
            const totalLaps = data.total_personnel * days;

            // Luôn hiển thị Số Người ở h3 (to)
            document.getElementById('kpi_total').innerText = data.total_personnel;
            document.getElementById('kpi_maternity').innerText = data.stats_people.maternity_leave || 0;
            document.getElementById('kpi_on_leave').innerText = data.stats_people.on_leave;
            document.getElementById('kpi_unassigned').innerText = data.stats_people.unassigned;
            document.getElementById('kpi_under8').innerText = data.stats_people.under_8h;
            document.getElementById('kpi_exact8').innerText = data.stats_people.exact_8h;
            document.getElementById('kpi_over8').innerText = data.stats_people.over_8h || 0;
            document.getElementById('kpi_total_ot').innerText = (data.stats_people.total_ot_hours || 0) + 'h';
            if (document.getElementById('kpi_total_ot_people')) {
                document.getElementById('kpi_total_ot_people').innerText = '(' + (data.stats_people.over_8h || 0) +
                    ' người)';
            }

            // Hiển thị Số Lượt (Laps) ở thẻ p (nhỏ)
            if (days > 1) {
                document.getElementById('kpi_total_label').innerText = 'Tổng nhân sự (' + totalLaps + ' lượt)';
                document.getElementById('kpi_maternity_label').innerText = 'Nghỉ thai sản - ' + (data.stats_laps
                    .maternity_leave || 0) + ' lượt';
                document.getElementById('kpi_on_leave_label').innerText = 'Nghỉ phép (P) - ' + data.stats_laps.on_leave +
                    ' lượt';
                document.getElementById('kpi_unassigned_label').innerText = 'Chưa xếp lịch - ' + data.stats_laps
                    .unassigned + ' lượt';
                document.getElementById('kpi_under8_label').innerText = '< 8h/ngày - ' + data.stats_laps.under_8h + ' lượt';
                document.getElementById('kpi_exact8_label').innerText = 'Đủ 8h/ngày - ' + data.stats_laps.exact_8h +
                    ' lượt';
                document.getElementById('kpi_over8_label').innerText = '> 8h/ngày - ' + (data.stats_laps.over_8h || 0) +
                    ' lượt';
                document.getElementById('kpi_total_ot_label').innerHTML =
                    '<i class="fas fa-clock mr-1"></i>Tổng Tăng Ca (' + (data.stats_laps.total_ot_hours || 0) + 'h)';
                if (document.getElementById('kpi_total_ot_people')) {
                    document.getElementById('kpi_total_ot_people').innerText = '';
                }
            } else {
                document.getElementById('kpi_total_label').innerText = 'Tổng nhân sự';
                document.getElementById('kpi_maternity_label').innerText = 'Nghỉ thai sản';
                document.getElementById('kpi_on_leave_label').innerText = 'Nghỉ phép (P)';
                document.getElementById('kpi_unassigned_label').innerText = 'Chưa xếp lịch (0h)';
                document.getElementById('kpi_under8_label').innerText = '< 8h / ngày';
                document.getElementById('kpi_exact8_label').innerText = 'Đủ 8h / ngày';
                document.getElementById('kpi_over8_label').innerText = '> 8h / ngày';
                document.getElementById('kpi_total_ot_label').innerHTML =
                    '<i class="fas fa-clock mr-1"></i>Tổng Tăng Ca theo ';
            }

            globalStatsDaily = data.stats_daily || [];

            // Update Period
            document.getElementById('period_text').innerText =
                `${data.period.start} đến ${data.period.end} (${data.period.days} ngày)`;

            // Render OT by Group table
            allDetails = data.details || [];
            const otGroupBody = document.getElementById('otGroupTableBody');
            if (data.overtime_by_group && data.overtime_by_group.length > 0) {
                otGroupBody.innerHTML = '';
                let totalCount = 0;
                let totalOTHours = 0;
                let totalOTPeople = 0;

                data.overtime_by_group.forEach(g => {
                    totalCount += g.count;
                    totalOTHours += g.ot_hours;
                    totalOTPeople += g.ot_people_count;

                    const avgOT = g.count > 0 ? Math.round((g.ot_hours / g.count) * 100) / 100 : 0;
                    const otBar = g.ot_hours > 0 ?
                        `<div class="progress progress-xs mt-1" style="height:4px;"><div class="progress-bar bg-danger" style="width:${Math.min(100, g.ot_hours * 2)}%"></div></div>` :
                        '';
                    otGroupBody.innerHTML += `
                    <tr>
                        <td>${g.name}</td>
                        <td class="text-center">${g.count}</td>
                        <td class="text-right">
                            <strong class="text-danger">${g.ot_hours}h</strong>
                            ${otBar}
                        </td>
                        <td class="text-right text-muted">${g.ot_people_count}</td>
                    </tr>
                `;
                });

                // Add summary row
                totalOTHours = Math.round(totalOTHours * 100) / 100;
                otGroupBody.innerHTML += `
                    <tr style="background-color: #f8f9fa;">
                        <td><strong>Tổng cộng</strong></td>
                        <td class="text-center"><strong>${totalCount}</strong></td>
                        <td class="text-right"><strong class="text-danger" style="font-size:1.1em;">${totalOTHours}h</strong></td>
                        <td class="text-right text-dark"><strong>${totalOTPeople}</strong></td>
                    </tr>
                `;
            } else {
                otGroupBody.innerHTML =
                    '<tr><td colspan="4" class="text-center text-muted py-3"><i class="fas fa-info-circle mr-1"></i>Không có dữ liệu tăng ca từ API lịch trực</td></tr>';
            }

            const statType = document.getElementById('type').value;
            const thStatus = document.getElementById('th_status');
            if (statType !== 'day') {
                thStatus.style.display = 'none';
            } else {
                thStatus.style.display = '';
            }

            // Render Detail Table
            renderDetailTable(data.details, statType);

            // Render Charts
            const ctxPie = document.getElementById('assignmentPieChart').getContext('2d');
            const ctxBar = document.getElementById('assignmentBarChart').getContext('2d');

            // Use stats_laps for charts to reflect Man-Days correctly for Week/Month
            const statsChart = data.period.days > 1 ? data.stats_laps : data.stats_people;
            const chartLabels = ['Nghỉ phép', 'Chưa phân công', '< 8h', 'Đủ 8h', '> 8h'];
            const chartData = [statsChart.on_leave, statsChart.unassigned, statsChart.under_8h, statsChart.exact_8h,
                statsChart.over_8h
            ];
            const chartColors = ['#6c757d', '#dc3545', '#ffc107', '#28a745', '#007bff'];

            if (currentPieChart) currentPieChart.destroy();
            currentPieChart = new Chart(ctxPie, {
                type: 'doughnut',
                data: {
                    labels: chartLabels,
                    datasets: [{
                        data: chartData,
                        backgroundColor: chartColors,
                    }]
                },
                options: {
                    maintainAspectRatio: false,
                    responsive: true,
                }
            });

            if (currentBarChart) currentBarChart.destroy();
            currentBarChart = new Chart(ctxBar, {
                type: 'bar',
                data: {
                    labels: chartLabels,
                    datasets: [{
                        label: 'Số lượng nhân sự',
                        data: chartData,
                        backgroundColor: chartColors,
                    }]
                },
                options: {
                    maintainAspectRatio: false,
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                stepSize: 1
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            display: false
                        }
                    }
                }
            });
        }

        function renderDetailTable(details, statType = 'day') {
            const tbody = document.querySelector('#detailTable tbody');
            tbody.innerHTML = '';

            details.forEach(item => {
                let badgeClass = 'badge-secondary';
                if (item.status === 'Nghỉ phép (P)') badgeClass = 'badge-secondary';
                else if (item.status === 'Chưa phân công') badgeClass = 'badge-danger';
                else if (item.status === '< 8h') badgeClass = 'badge-warning';
                else if (item.status === 'Đủ 8h') badgeClass = 'badge-success';
                else if (item.status === '> 8h') badgeClass = 'badge-primary';
                else if (item.status === 'Thai sản') badgeClass = 'badge-danger';

                const ot = item.overtime_hours || 0;
                const otCell = ot > 0 ?
                    `<td><span class="badge" style="background:#f5576c;color:#fff;font-size:0.85rem;">${ot}h</span></td>` :
                    `<td><span class="text-muted">—</span></td>`;

                let shiftHtml = '-';
                if (item.registered_shifts && item.registered_shifts.length > 0) {
                    if (item.registered_shifts.length === 1 && !item.registered_shifts[0].includes(':')) {
                        shiftHtml =
                            `<span class="badge badge-info" style="font-size:0.9rem;">${item.registered_shifts[0]}</span>`;
                    } else {
                        const shiftsPills = item.registered_shifts.map(s =>
                            `<span class="badge badge-info mr-1 mb-1">${s}</span>`).join('');
                        shiftHtml =
                            `<div style="max-height: 65px; overflow-y: auto; display: flex; flex-wrap: wrap; align-items: flex-start;">${shiftsPills}</div>`;
                    }
                }

                const statusCell = statType === 'day' ?
                    `<td><span class="badge ${badgeClass}" style="font-size:0.9rem;">${item.status}</span></td>` :
                    '';

                const eoffice = item.eoffice_hours || 0;
                const eofficeTotal = Math.round((eoffice + ot) * 100) / 100;

                const nameHtml = item.status === 'Thai sản' ?
                    `${item.name} <span class="badge badge-danger text-white ml-1" style="font-size:0.6rem; padding:1px 3px;" title="Nghỉ thai sản dài hạn"><i class="fas fa-baby"></i> Thai sản</span>` :
                    item.name;

                const tr = document.createElement('tr');
                tr.innerHTML = `
                <td>${item.code}</td>
                <td>${nameHtml}</td>
                <td><small class="text-muted">${item.group || '-'}</small></td>
                <td style="min-width: 150px; max-width: 250px;">${shiftHtml}</td>
                <td><strong>${item.total_hours} h</strong></td>
                <td><span class="badge bg-primary shadow-sm" style="font-size: 0.9rem; padding: 0.4em 0.6em; border-radius: 4px;">${eofficeTotal} h</span></td>
                ${otCell}
                ${statusCell}
            `;
                tbody.appendChild(tr);
            });

            // Re-apply search filter after render
            const q = document.getElementById('searchDetail').value.toLowerCase();
            if (q) filterDetailTable(q);
        }

        // POLICY LOGIC
        function loadPolicyData() {
            const prodCode = document.getElementById('production_code').value;

            // Fetch current policy
            fetch(`/assignemnt/overtime-policy?production_code=${prodCode}`)
                .then(res => res.json())
                .then(res => {
                    if (res.success) {
                        renderPolicyForm(prodCode, res.data);
                    } else {
                        document.getElementById('policyTableBody').innerHTML =
                            '<tr><td colspan="4" class="text-center text-muted py-3">Lỗi tải dữ liệu</td></tr>';
                    }
                })
                .catch(err => {
                    document.getElementById('policyTableBody').innerHTML =
                        '<tr><td colspan="4" class="text-center text-muted py-3">Không thể kết nối với máy chủ</td></tr>';
                });
        }

        function renderPolicyForm(prodCode, currentPolicies) {
            const canAuthorizeOvertime = {{ user_has_permission(session('user')['userId'], 'Authorize_overtime', 'boolean') ? 'true' : 'false' }};
            const disabledAttr = canAuthorizeOvertime ? '' : 'disabled';
            const tbody = document.getElementById('policyTableBody');
            tbody.innerHTML = '';

            const getPol = (groupId) => {
                return currentPolicies.find(p => p.group_id == groupId) || {
                    max_personnel_per_day: '',
                    max_hours_per_day: ''
                };
            };

            const deptPol = getPol(null);
            tbody.innerHTML += `
            <tr style="background-color: #fff3cd;">
                <td><strong>Toàn phân xưởng</strong></td>
                <td>-</td>
                <td><input type="number" min="0" class="form-control form-control-sm pol-personnel" data-group="" value="${deptPol.max_personnel_per_day || ''}" placeholder="Ko giới hạn" ${disabledAttr}></td>
                <td><input type="number" min="0" step="0.5" class="form-control form-control-sm pol-hours" data-group="" value="${deptPol.max_hours_per_day || ''}" placeholder="Ko giới hạn" ${disabledAttr}></td>
            </tr>
        `;

            if (globalAvailableGroups.length > 0) {
                globalAvailableGroups.forEach(g => {
                    const gPol = getPol(g.code);
                    tbody.innerHTML += `
                    <tr>
                        <td>Tổ / Nhóm</td>
                        <td>${g.name}</td>
                        <td><input type="number" min="0" class="form-control form-control-sm pol-personnel" data-group="${g.code}" value="${gPol.max_personnel_per_day || ''}" placeholder="Ko giới hạn" ${disabledAttr}></td>
                        <td><input type="number" min="0" step="0.5" class="form-control form-control-sm pol-hours" data-group="${g.code}" value="${gPol.max_hours_per_day || ''}" placeholder="Ko giới hạn" ${disabledAttr}></td>
                    </tr>
                `;
                });
            }
        }

        function savePolicy() {
            const prodCode = document.getElementById('production_code').value;
            const rows = document.querySelectorAll('#policyTableBody tr');
            let policies = [];

            rows.forEach(tr => {
                const inputP = tr.querySelector('.pol-personnel');
                const inputH = tr.querySelector('.pol-hours');
                if (inputP && inputH) {
                    const gId = inputP.getAttribute('data-group');
                    const pVal = parseInt(inputP.value) || 0;
                    const hVal = parseFloat(inputH.value) || 0;

                    if (pVal > 0 || hVal > 0) {
                        policies.push({
                            group_id: gId ? gId : null,
                            max_personnel: pVal,
                            max_hours: hVal
                        });
                    }
                }
            });

            fetch(`/assignemnt/overtime-policy/store`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        production_code: prodCode,
                        policies: policies
                    })
                })
                .then(res => res.json())
                .then(res => {
                    if (res.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Đã lưu cấu hình thành công',
                            showConfirmButton: false,
                            timer: 1500
                        });
                    } else {
                        alert('Có lỗi xảy ra: ' + res.message);
                    }
                });
        }

        function openHistoryModal() {
            const prodCode = document.getElementById('production_code').value;
            fetch(`/assignemnt/overtime-policy/history?production_code=${prodCode}`)
                .then(res => res.json())
                .then(res => {
                    if (res.success) {
                        let html = '';
                        if (res.data.length === 0) {
                            html = '<p class="text-muted text-center mt-3">Chưa có lịch sử thay đổi.</p>';
                        } else {
                            res.data.forEach(history => {
                                const badge = history.active == 1 ?
                                    '<span class="badge badge-success">Đang áp dụng</span>' :
                                    '<span class="badge badge-secondary">Lịch sử</span>';
                                let detailHtml = '<ul class="mb-0 pl-3">';
                                history.policies.forEach(p => {
                                    let scopeName = 'Toàn phân xưởng';
                                    if (p.group_id) {
                                        const gObj = globalAvailableGroups.find(x => x.code == p
                                            .group_id);
                                        scopeName = gObj ? `Tổ ${gObj.name}` : `Tổ ${p.group_id}`;
                                    }
                                    detailHtml +=
                                        `<li><strong>${scopeName}:</strong> Tối đa ${p.max_personnel_per_day} người, ${p.max_hours_per_day} giờ</li>`;
                                });
                                if (history.policies.length === 0) detailHtml +=
                                    '<li>Xóa giới hạn (Không giới hạn)</li>';
                                detailHtml += '</ul>';

                                html += `
                                <div class="card mb-2 shadow-sm border">
                                    <div class="card-body p-2">
                                        <div class="d-flex justify-content-between mb-1">
                                            <span><i class="far fa-clock text-info"></i> ${history.time}</span>
                                            ${badge}
                                        </div>
                                        <div class="mb-1 text-sm"><i class="far fa-user text-muted"></i> Thay đổi bởi: <strong class="text-primary">${history.created_by || 'System'}</strong></div>
                                        <div class="bg-light p-2 rounded text-sm">
                                            ${detailHtml}
                                        </div>
                                    </div>
                                </div>
                            `;
                            });
                        }
                        document.getElementById('historyContent').innerHTML = html;
                        $('#historyModal').modal('show');
                    }
                });
        }

        function showDailyStats(key, title, bgClass, bgStyle) {
            if (!globalStatsDaily || globalStatsDaily.length <= 1) {
                // Nếu chỉ có 1 ngày thì không cần show modal
                return;
            }

            const header = document.getElementById('dailyStatsHeader');
            header.className = 'modal-header text-white ' + (bgClass || 'bg-primary');
            if (bgStyle) {
                header.style.background = bgStyle;
            } else {
                header.style.background = ''; // reset
            }

            const unitTh = document.getElementById('dailyStatsUnit');
            const isHours = key === 'total_ot_hours';
            unitTh.innerText = isHours ? 'Số Lượng (Giờ)' : 'Số Lượng (Người)';

            document.getElementById('dailyStatsTitle').innerText = title;
            const tbody = document.getElementById('dailyStatsBody');
            tbody.innerHTML = '';

            let total = 0;
            globalStatsDaily.forEach(day => {
                const val = day[key] || 0;
                total += val;
                const displayVal = isHours ? val + 'h' : val;
                tbody.innerHTML += `
                <tr>
                    <td>${day.date}</td>
                    <td class="font-weight-bold text-primary">${displayVal}</td>
                </tr>
            `;
            });

            const totalDisplay = isHours ? (Math.round(total * 100) / 100) + 'h' : total;
            // Thêm dòng tổng
            tbody.innerHTML += `
            <tr class="bg-light">
                <td class="font-weight-bold">Tổng Cộng</td>
                <td class="font-weight-bold text-danger">${totalDisplay}</td>
            </tr>
        `;

            $('#dailyStatsModal').modal('show');
        }
    </script>
@endsection
