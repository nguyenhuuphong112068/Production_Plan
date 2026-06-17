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
                                        @foreach($departments as $code => $name)
                                            <option value="{{ $code }}" {{ session('user')['production_code'] == $code ? 'selected' : '' }}>
                                                {{ $name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label>Tổ (Nhóm)</label>
                                    <select class="form-control" name="group_id" id="group_id">
                                        <option value="">-- Tất cả --</option>
                                        @foreach($groups as $g)
                                            <option value="{{ $g->code }}">{{ $g->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-2">
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
                                    <input type="date" class="form-control" name="date" id="date" value="{{ \Carbon\Carbon::now()->format('Y-m-d') }}">
                                </div>
                            </div>
                            <div class="col-md-2 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary w-100 mb-3"><i class="fas fa-search"></i> Xem báo cáo</button>
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
                    <div class="col-lg-2 col-6">
                        <div class="small-box bg-info">
                            <div class="inner">
                                <h3 id="kpi_total">0</h3>
                                <p>Tổng nhân sự</p>
                            </div>
                            <div class="icon"><i class="fas fa-users"></i></div>
                        </div>
                    </div>
                    <div class="col-lg-2 col-6">
                        <div class="small-box bg-secondary">
                            <div class="inner">
                                <h3 id="kpi_on_leave">0</h3>
                                <p>Nghỉ phép (P)</p>
                            </div>
                            <div class="icon"><i class="fas fa-bed"></i></div>
                        </div>
                    </div>
                    <div class="col-lg-2 col-6">
                        <div class="small-box bg-danger">
                            <div class="inner">
                                <h3 id="kpi_unassigned">0</h3>
                                <p>Chưa xếp lịch (0h)</p>
                            </div>
                            <div class="icon"><i class="fas fa-user-times"></i></div>
                        </div>
                    </div>
                    <div class="col-lg-2 col-6">
                        <div class="small-box bg-warning">
                            <div class="inner">
                                <h3 id="kpi_under8">0</h3>
                                <p>< 8h / ngày</p>
                            </div>
                            <div class="icon"><i class="fas fa-battery-half"></i></div>
                        </div>
                    </div>
                    <div class="col-lg-2 col-6">
                        <div class="small-box bg-success">
                            <div class="inner">
                                <h3 id="kpi_exact8">0</h3>
                                <p>Đủ 8h / ngày</p>
                            </div>
                            <div class="icon"><i class="fas fa-battery-full"></i></div>
                        </div>
                    </div>
                    <div class="col-lg-2 col-6">
                        <div class="small-box" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: #fff;">
                            <div class="inner">
                                <h3 id="kpi_total_ot">0h</h3>
                                <p><i class="fas fa-clock mr-1"></i>Tổng Tăng Ca (TC)</p>
                            </div>
                            <div class="icon"><i class="fas fa-fire-alt"></i></div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <!-- Biểu đồ tỷ lệ -->
                    <div class="col-md-4 mb-3">
                        <div class="card card-outline card-success h-100">
                            <div class="card-header">
                                <h3 class="card-title">Tỷ lệ phân công</h3>
                            </div>
                            <div class="card-body">
                                <canvas id="assignmentPieChart" style="min-height: 250px; height: 250px; max-height: 250px; max-width: 100%;"></canvas>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Biểu đồ tổng quan -->
                    <div class="col-md-4 mb-3">
                        <div class="card card-outline card-info h-100">
                            <div class="card-header">
                                <h3 class="card-title">Biểu đồ tổng quan</h3>
                            </div>
                            <div class="card-body">
                                <canvas id="assignmentBarChart" style="min-height: 250px; height: 250px; max-height: 250px; max-width: 100%;"></canvas>
                            </div>
                        </div>
                    </div>

                    <!-- Thống kê OT theo Tổ -->
                    <div class="col-md-4 mb-3" id="otSummaryRow">
                        <div class="card card-outline h-100" style="border-color: #f5576c;">
                            <div class="card-header" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color:#fff;">
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
                                                <th class="text-right">TB TC/NS (h)</th>
                                            </tr>
                                        </thead>
                                        <tbody id="otGroupTableBody">
                                            <tr><td colspan="4" class="text-center text-muted py-3">Chưa có dữ liệu</td></tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Bảng chi tiết nhân sự -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Danh sách chi tiết nhân sự</h3>
                        <div class="card-tools">
                            <input type="text" id="searchDetail" class="form-control form-control-sm" placeholder="Tìm kiếm..." style="width:200px;">
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

<!-- ChartJS -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
    let currentPieChart = null;
    let currentBarChart = null;
    let allDetails = [];

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
        if (data.available_groups) {
            data.available_groups.forEach(g => {
                const isSelected = (g.code == currentSelectedGroup) ? 'selected' : '';
                groupSelect.innerHTML += `<option value="${g.code}" ${isSelected}>${g.name}</option>`;
            });
        }

        // Update KPIs
        document.getElementById('kpi_total').innerText = data.total_personnel;
        document.getElementById('kpi_on_leave').innerText = data.stats.on_leave;
        document.getElementById('kpi_unassigned').innerText = data.stats.unassigned;
        document.getElementById('kpi_under8').innerText = data.stats.under_8h;
        document.getElementById('kpi_exact8').innerText = data.stats.exact_8h;
        document.getElementById('kpi_total_ot').innerText = (data.stats.total_ot_hours || 0) + 'h';

        // Update Period
        document.getElementById('period_text').innerText = `${data.period.start} đến ${data.period.end} (${data.period.days} ngày)`;

        // Render OT by Group table
        allDetails = data.details || [];
        const otGroupBody = document.getElementById('otGroupTableBody');
        if (data.overtime_by_group && data.overtime_by_group.length > 0) {
            otGroupBody.innerHTML = '';
            data.overtime_by_group.forEach(g => {
                const avgOT = g.count > 0 ? Math.round((g.ot_hours / g.count) * 100) / 100 : 0;
                const otBar = g.ot_hours > 0 ? `<div class="progress progress-xs mt-1" style="height:4px;"><div class="progress-bar bg-danger" style="width:${Math.min(100, g.ot_hours * 2)}%"></div></div>` : '';
                otGroupBody.innerHTML += `
                    <tr>
                        <td>${g.name}</td>
                        <td class="text-center">${g.count}</td>
                        <td class="text-right">
                            <strong class="text-danger">${g.ot_hours}h</strong>
                            ${otBar}
                        </td>
                        <td class="text-right text-muted">${avgOT}h</td>
                    </tr>
                `;
            });
        } else {
            otGroupBody.innerHTML = '<tr><td colspan="4" class="text-center text-muted py-3"><i class="fas fa-info-circle mr-1"></i>Không có dữ liệu tăng ca từ API lịch trực</td></tr>';
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

        const chartLabels = ['Nghỉ phép', 'Chưa phân công', '< 8h', 'Đủ 8h', '> 8h'];
        const chartData = [data.stats.on_leave, data.stats.unassigned, data.stats.under_8h, data.stats.exact_8h, data.stats.over_8h];
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
                    y: { beginAtZero: true, ticks: { stepSize: 1 } }
                },
                plugins: {
                    legend: { display: false }
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

            const ot = item.overtime_hours || 0;
            const otCell = ot > 0
                ? `<td><span class="badge" style="background:#f5576c;color:#fff;font-size:0.85rem;">${ot}h</span></td>`
                : `<td><span class="text-muted">—</span></td>`;

            let shiftHtml = '-';
            if (item.registered_shifts && item.registered_shifts.length > 0) {
                if (item.registered_shifts.length === 1 && !item.registered_shifts[0].includes(':')) {
                    shiftHtml = `<span class="badge badge-info" style="font-size:0.9rem;">${item.registered_shifts[0]}</span>`;
                } else {
                    const shiftsPills = item.registered_shifts.map(s => `<span class="badge badge-info mr-1 mb-1">${s}</span>`).join('');
                    shiftHtml = `<div style="max-height: 65px; overflow-y: auto; display: flex; flex-wrap: wrap; align-items: flex-start;">${shiftsPills}</div>`;
                }
            }

            const statusCell = statType === 'day' 
                ? `<td><span class="badge ${badgeClass}" style="font-size:0.9rem;">${item.status}</span></td>` 
                : '';

            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td>${item.code}</td>
                <td>${item.name}</td>
                <td><small class="text-muted">${item.group || '-'}</small></td>
                <td style="min-width: 150px; max-width: 250px;">${shiftHtml}</td>
                <td><strong>${item.total_hours} h</strong></td>
                <td>${item.eoffice_hours} h</td>
                ${otCell}
                ${statusCell}
            `;
            tbody.appendChild(tr);
        });

        // Re-apply search filter after render
        const q = document.getElementById('searchDetail').value.toLowerCase();
        if (q) filterDetailTable(q);
    }
</script>
@endsection
