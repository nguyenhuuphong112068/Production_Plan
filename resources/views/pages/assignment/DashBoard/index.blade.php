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
                    <div class="col-lg-3 col-6">
                        <div class="small-box bg-info">
                            <div class="inner">
                                <h3 id="kpi_total">0</h3>
                                <p>Tổng nhân sự</p>
                            </div>
                            <div class="icon"><i class="fas fa-users"></i></div>
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
                    <div class="col-lg-3 col-6">
                        <div class="small-box bg-primary">
                            <div class="inner">
                                <h3 id="kpi_over8">0</h3>
                                <p>> 8h / ngày</p>
                            </div>
                            <div class="icon"><i class="fas fa-fire"></i></div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <!-- Biểu đồ -->
                    <div class="col-md-6">
                        <div class="card card-outline card-success">
                            <div class="card-header">
                                <h3 class="card-title">Tỷ lệ phân công</h3>
                            </div>
                            <div class="card-body">
                                <canvas id="assignmentPieChart" style="min-height: 250px; height: 250px; max-height: 250px; max-width: 100%;"></canvas>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card card-outline card-info">
                            <div class="card-header">
                                <h3 class="card-title">Biểu đồ tổng quan</h3>
                            </div>
                            <div class="card-body">
                                <canvas id="assignmentBarChart" style="min-height: 250px; height: 250px; max-height: 250px; max-width: 100%;"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Bảng chi tiết -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Danh sách chi tiết nhân sự</h3>
                    </div>
                    <div class="card-body table-responsive p-0">
                        <table class="table table-head-fixed text-nowrap table-striped table-hover" id="detailTable">
                            <thead>
                                <tr>
                                    <th>Mã NS</th>
                                    <th>Họ và Tên</th>
                                    <th>Tổ</th>
                                    <th>Tổng số giờ phân công</th>
                                    <th>TB giờ / ngày</th>
                                    <th>Trạng thái</th>
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
                // Use == instead of === because g.code might be a number and currentSelectedGroup is a string
                const isSelected = (g.code == currentSelectedGroup) ? 'selected' : '';
                groupSelect.innerHTML += `<option value="${g.code}" ${isSelected}>${g.name}</option>`;
            });
        }

        // Update KPIs
        document.getElementById('kpi_total').innerText = data.total_personnel;
        document.getElementById('kpi_unassigned').innerText = data.stats.unassigned;
        document.getElementById('kpi_under8').innerText = data.stats.under_8h;
        document.getElementById('kpi_exact8').innerText = data.stats.exact_8h;
        document.getElementById('kpi_over8').innerText = data.stats.over_8h;

        // Update Period
        document.getElementById('period_text').innerText = `${data.period.start} đến ${data.period.end} (${data.period.days} ngày)`;

        // Render Table
        const tbody = document.querySelector('#detailTable tbody');
        tbody.innerHTML = '';
        
        data.details.forEach(item => {
            let badgeClass = 'badge-secondary';
            if (item.status === 'Chưa phân công') badgeClass = 'badge-danger';
            else if (item.status === '< 8h') badgeClass = 'badge-warning';
            else if (item.status === 'Đủ 8h') badgeClass = 'badge-success';
            else if (item.status === '> 8h') badgeClass = 'badge-primary';

            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td>${item.code}</td>
                <td>${item.name}</td>
                <td><small class="text-muted">${item.group || '-'}</small></td>
                <td><strong>${item.total_hours} h</strong></td>
                <td>${item.avg_hours_per_day} h/ngày</td>
                <td><span class="badge ${badgeClass}" style="font-size:0.9rem;">${item.status}</span></td>
            `;
            tbody.appendChild(tr);
        });

        // Render Charts
        const ctxPie = document.getElementById('assignmentPieChart').getContext('2d');
        const ctxBar = document.getElementById('assignmentBarChart').getContext('2d');

        const chartLabels = ['Chưa phân công', '< 8h', 'Đủ 8h', '> 8h'];
        const chartData = [data.stats.unassigned, data.stats.under_8h, data.stats.exact_8h, data.stats.over_8h];
        const chartColors = ['#dc3545', '#ffc107', '#28a745', '#007bff'];

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
</script>
@endsection
