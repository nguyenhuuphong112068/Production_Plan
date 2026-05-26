<link href="{{ asset('css/bootstrap.min.css') }}" rel="stylesheet">
<link href="{{ asset('assets/vendor/select2/select2.min.css') }}" rel="stylesheet" />
<link rel="stylesheet" href="{{ asset('assets/vendor/select2/select2-bootstrap4.min.css') }}">

<style>
    .select-permission-wrapper .select2-container {
        width: 100% !important;
        min-width: 150px;
    }

    .select2-selection--multiple {
        max-height: 100px;
        overflow-y: auto !important;
        font-size: 0.85rem;
    }

    #data_table_personnel td {
        vertical-align: middle;
    }

    .filter-section {
        background-color: #f8f9fa;
        padding: 15px;
        border-radius: 8px;
        margin-bottom: 15px;
        border: 1px solid #e9ecef;
    }

    .badge-toggle {
        cursor: pointer;
        transition: all 0.2s;
        margin-right: 4px;
        margin-bottom: 4px;
        display: inline-block;
        padding: 5px 10px;
        font-size: 0.85rem;
    }

    .badge-toggle:hover {
        transform: translateY(-1px);
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }

    .badge-toggle.inactive {
        background-color: #fff !important;
        border: 1.5px dashed #dee2e6 !important;
        color: #6c757d !important;
        opacity: 0.7;
    }

    .badge-toggle.active {
        border: 1.5px solid transparent;
    }

    .assignment-item {
        display: flex;
        align-items: center;
        margin-bottom: 6px;
        padding-bottom: 4px;
        border-bottom: 1px solid #f0f0f0;
    }

    .assignment-item:last-child {
        border-bottom: none;
    }

    .assignment-meta {
        font-size: 0.75rem;
        color: #999;
        margin-left: 8px;
        white-space: nowrap;
    }
</style>

<div class="content-wrapper">
    <div class="card">
        <div class="card-header mt-4">
            <h3 class="card-title">Danh sách nhân sự ({{ $currentDepartment }})</h3>
        </div>
        <div class="card-body">
            <!-- Dashboard Section -->
            @php
                $depMapping = [
                    'EN' => 3,
                    'QA' => 9,
                    'PXTN' => 6,
                    'PXV1' => 15,
                    'PXVH' => 30,
                    'PXDN' => 34,
                    'PXV2' => 31,
                ];
                $apiDepId = $depMapping[$currentDepartment] ?? 15;

                // Phân quyền: chi có người phòng đó mới có thể điều chỉnh nhân sự của bộ phận mình. Ngoài ra admin toàn quyền.
                $user = session('user');
                $isAdmin = ($user['userGroup'] ?? '') == 'Admin';
                $isOwnDepartment = ($user['production_code'] ?? '') == ($user['department'] ?? '');
                $canEdit = $isAdmin || $isOwnDepartment;
                $disabled = $canEdit ? '' : 'disabled';
                $pointerNone = $canEdit ? '' : 'pointer-events: none; opacity: 0.7;';
            @endphp
            <div class="mb-3 d-flex justify-content-between align-items-center">
                <h5 class="font-weight-bold text-primary mb-0"><i class="fas fa-users"></i> Quản Lý Nhân Sự</h5>
                <button id="btn-toggle-dashboard" class="btn btn-sm btn-outline-info shadow-sm">
                    <i class="fas fa-chart-pie"></i> <span id="text-toggle-dashboard">Hiển thị Dashboard</span>
                </button>
            </div>

            <div id="personnel-dashboard" class="mb-4" style="display: none;">
                <div class="card shadow-sm border-info" style="border-top: 3px solid #17a2b8;">
                    <div class="card-header bg-light py-2">
                        <h6 class="mb-0 font-weight-bold text-info"><i class="fas fa-chart-line"></i> Báo Cáo Phân Tích
                            ({{ $currentDepartment }})</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <!-- Local Data Column -->
                            <div class="col-md-6 border-right">
                                <h6 class="font-weight-bold text-secondary mb-3">Tình Hình Phân Công Nhân Sự</h6>
                                <div class="row text-center mb-4">
                                    <div class="col-6">
                                        <h3 class="text-primary font-weight-bold mb-0" id="dash-total-emp">
                                            {{ count($datas) }}</h3>
                                        <span class="small font-weight-bold text-muted">Tổng Nhân Sự</span>
                                    </div>
                                    <div class="col-6">
                                        <h3 class="text-info font-weight-bold mb-0" id="dash-active-groups-count">0</h3>
                                        <span class="small font-weight-bold text-muted">Tổ Hoạt Động</span>
                                    </div>
                                </div>

                                <h6 class="font-weight-bold text-secondary mb-2 small">Phân bổ nhân sự:</h6>
                                <ul class="nav nav-tabs mb-2" id="dash-stats-tab" role="tablist">
                                    <li class="nav-item">
                                        <a class="nav-link active small py-1" id="dash-group-tab" data-toggle="tab"
                                            href="#dash-group-content" role="tab">Theo Tổ/Vị Trí</a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link small py-1" id="dash-summary-tab" data-toggle="tab"
                                            href="#dash-summary-content" role="tab">Tổng Hợp Chi Tiết</a>
                                    </li>
                                </ul>


                                <div class="tab-content" id="dash-stats-tabContent">
                                    <div class="tab-pane fade show active" id="dash-group-content" role="tabpanel">
                                        <div id="dash-group-bars-container"
                                            style="max-height: 250px; overflow-y: auto; overflow-x: hidden; padding-right: 5px;">
                                            <!-- Group stats render here -->
                                        </div>
                                    </div>
                                    <div class="tab-pane fade" id="dash-summary-content" role="tabpanel">
                                        <div id="dash-summary-table-container"
                                            style="max-height: 250px; overflow-y: auto; padding-top: 5px;">
                                            <table class="table table-sm table-bordered mb-0"
                                                style="font-size: 0.8rem;">
                                                <thead class="bg-light">
                                                    <tr>
                                                        <th>Nhóm/Tổ</th>
                                                        <th class="text-center">Active</th>
                                                        <th class="text-center">Inactive</th>
                                                        <th class="text-center">Tổng</th>
                                                    </tr>
                                                </thead>
                                                <tbody id="dash-summary-tbody">
                                                    <!-- Table rows render here -->
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>

                            </div>
                            <!-- API Data Column -->
                            <div class="col-md-6">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <h6 class="font-weight-bold text-secondary mb-0">Tình Hình Đi Ca</h6>
                                    <span id="shift-range-title" class="small text-muted font-weight-bold"></span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <div class="d-flex align-items-center flex-wrap">
                                        <select id="dash-shift-range-select"
                                            class="form-control form-control-sm mr-2 mb-2" style="width: 80px;">
                                            <option value="day">Ngày</option>
                                            <option value="week">Tuần</option>
                                            <option value="month">Tháng</option>
                                        </select>

                                        <!-- Inputs for Week/Month -->
                                        <div id="input-group-day" class="shift-input-group mr-2 mb-2">
                                            <input type="date" id="dash-shift-date-input"
                                                class="form-control form-control-sm" style="width: 140px;"
                                                value="{{ \Carbon\Carbon::now()->format('Y-m-d') }}">
                                        </div>
                                        <div id="input-group-week" class="shift-input-group mr-2 mb-2"
                                            style="display: none;">
                                            <div class="input-group input-group-sm" style="width: 160px;">
                                                <input type="number" id="dash-shift-week-input" class="form-control"
                                                    placeholder="Tuần" min="1" max="53"
                                                    value="{{ \Carbon\Carbon::now()->weekOfYear }}">
                                                <div class="input-group-append"><span
                                                        class="input-group-text">/</span></div>
                                                <input type="number" id="dash-shift-week-year" class="form-control"
                                                    placeholder="Năm" value="{{ \Carbon\Carbon::now()->year }}">
                                            </div>
                                        </div>
                                        <div id="input-group-month" class="shift-input-group mr-2 mb-2"
                                            style="display: none;">
                                            <div class="input-group input-group-sm" style="width: 160px;">
                                                <select id="dash-shift-month-input" class="form-control">
                                                    @for ($i = 1; $i <= 12; $i++)
                                                        <option value="{{ $i }}"
                                                            {{ \Carbon\Carbon::now()->month == $i ? 'selected' : '' }}>
                                                            Tháng {{ $i }}</option>
                                                    @endfor
                                                </select>
                                                <input type="number" id="dash-shift-month-year" class="form-control"
                                                    placeholder="Năm" value="{{ \Carbon\Carbon::now()->year }}">
                                            </div>
                                        </div>

                                        <button class="btn btn-sm btn-outline-secondary mb-2" id="btn-refresh-shifts"
                                            title="Tải lại dữ liệu">
                                            <i class="fas fa-sync-alt"></i>
                                        </button>
                                    </div>
                                </div>

                                <div id="shift-data-container" class="d-flex flex-column">
                                    <div class="text-center py-2 text-muted" id="shift-loading">

                                        <div class="spinner-border spinner-border-sm text-info mb-2" role="status">
                                        </div>
                                        <br><span class="small font-weight-bold">Đang kết nối server nội bộ lấy dữ liệu
                                            ca...</span>
                                    </div>
                                    <div id="shift-content" style="display: none;">
                                        <!-- Card View (for Day) -->
                                        <div id="shift-card-view">
                                            <div class="row text-center mb-2">
                                                <div class="col-4">
                                                    <div class="p-2 bg-light rounded shadow-sm border border-success">
                                                        <h4 class="text-success font-weight-bold mb-0"
                                                            id="dash-shift-hc">0</h4>
                                                        <span class="small font-weight-bold text-muted">HC</span>
                                                    </div>
                                                </div>
                                                <div class="col-4">
                                                    <div class="p-2 bg-light rounded shadow-sm border border-warning">
                                                        <h4 class="text-warning font-weight-bold mb-0"
                                                            id="dash-shift-c1">0</h4>
                                                        <span class="small font-weight-bold text-muted">Ca 1</span>
                                                    </div>
                                                </div>
                                                <div class="col-4">
                                                    <div class="p-2 bg-light rounded shadow-sm border border-primary">
                                                        <h4 class="text-primary font-weight-bold mb-0"
                                                            id="dash-shift-c2">0</h4>
                                                        <span class="small font-weight-bold text-muted">Ca 2</span>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="row text-center">
                                                <div class="col-4">
                                                    <div
                                                        class="p-2 bg-light rounded shadow-sm border border-secondary">
                                                        <h4 class="text-secondary font-weight-bold mb-0"
                                                            id="dash-shift-c3">0</h4>
                                                        <span class="small font-weight-bold text-muted">Ca 3</span>
                                                    </div>
                                                </div>
                                                <div class="col-4">
                                                    <div class="p-2 bg-light rounded shadow-sm border border-info">
                                                        <h4 class="text-info font-weight-bold mb-0"
                                                            id="dash-shift-c4">0</h4>
                                                        <span class="small font-weight-bold text-muted">Ca 4</span>
                                                    </div>
                                                </div>
                                                <div class="col-4">
                                                    <div class="p-2 bg-light rounded shadow-sm border border-danger">
                                                        <h4 class="text-danger font-weight-bold mb-0"
                                                            id="dash-shift-p">0</h4>
                                                        <span class="small font-weight-bold text-muted">Nghỉ</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Table View (for Week/Month) -->
                                        <div id="shift-table-view" style="display: none;" class="mt-2">
                                            <div class="table-responsive"
                                                style="max-height: 280px; overflow-y: auto;">
                                                <table
                                                    class="table table-sm table-bordered table-striped text-center mb-0"
                                                    style="font-size: 0.7rem;">
                                                    <thead class="bg-light sticky-top">
                                                        <tr id="shift-table-head">
                                                            <th style="width: 80px;">Ngày</th>
                                                            <th>HC</th>
                                                            <th>C1</th>
                                                            <th>C2</th>
                                                            <th>C3</th>
                                                            <th>C4</th>
                                                            <th>P</th>

                                                        </tr>
                                                    </thead>
                                                    <tbody id="shift-table-body">
                                                        <!-- Data rows here -->
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filter Section -->
            <div class="filter-section">
                <form action="{{ url()->current() }}" method="GET" id="filter-form">
                    <div class="row align-items-end">
                        <div class="col-md-3">
                            <label class="small font-weight-bold">Lọc theo Tổ:</label>
                            <select name="group_id" class="form-control form-control-sm select2-filter"
                                onchange="this.form.submit()">
                                <option value="">-- Tất cả các Tổ --</option>
                                @foreach ($groups as $g)
                                    <option value="{{ $g->code }}"
                                        {{ $filterGroupId == $g->code ? 'selected' : '' }}>{{ $g->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <a href="{{ url()->current() }}" class="btn btn-sm btn-secondary shadow-sm mb-0">
                                <i class="fas fa-undo"></i> Reset
                            </a>
                        </div>
                    </div>
                </form>
            </div>

            <table id="data_table_personnel" class="table table-bordered table-striped">
                <thead style="position: sticky; top: 60px; background-color: white; z-index: 1020">
                    <tr>
                        <th style="width: 40px;">STT</th>
                        <th style="width: 80px;">Mã NV</th>
                        <th style="width: 150px;">Tên Nhân Viên</th>
                        <th style="width: 100px;">Bộ Phận</th>
                        <th style="width: 180px;">Tổ Được Phép Công Tác</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($datas as $data)
                        <tr>
                            <td>{{ $loop->iteration }} <br>
                                @if (session('user')['userGroup'] == 'Admin')
                                    {{ $data->id }}
                                @endif
                            </td>
                            <td>{{ $data->code }}</td>
                            <td>{{ $data->name }}</td>

                            <td class="text-center">
                                <span class="badge badge-info shadow-sm">{{ $data->main_production }}</span>
                            </td>

                            <td>
                                <div class="groups-list-container" data-employee-id="{{ $data->id }}">
                                    <div class="groups-badges mb-2">
                                        @php
                                            $groupPermissions = $data->allowed_groups
                                                ? explode('|', $data->allowed_groups)
                                                : [];
                                        @endphp
                                        @foreach ($groupPermissions as $gp)
                                            @php
                                                $parts = explode(':', $gp);
                                                $gId = $parts[0];
                                                $isActive = ($parts[1] ?? 1) == 1;
                                                $gUser = $parts[2] ?? 'N/A';
                                                $gDate = $parts[3] ?? '';
                                                $groupName = $groups->where('code', $gId)->first()->name ?? 'N/A';
                                            @endphp
                                            <div class="assignment-item">
                                                <span
                                                    class="badge badge-toggle {{ $isActive ? 'badge-primary active' : 'inactive' }} btn-toggle-group"
                                                    data-group-id="{{ $gId }}"
                                                    data-active="{{ $isActive ? 1 : 0 }}"
                                                    style="{{ $pointerNone }}"
                                                    title="{{ $isActive ? 'Nhấn để vô hiệu hóa' : 'Nhấn để kích hoạt' }}">
                                                    {{ $groupName }}
                                                </span>
                                                <span class="assignment-meta">
                                                    <i class="fas fa-user-edit"></i> {{ $gUser }} <br>
                                                    <i class="fas fa-calendar-check"></i> {{ $gDate }}
                                                </span>
                                            </div>
                                        @endforeach
                                    </div>
                                    @if ($departments != 'QA')
                                        <select class="form-control form-control-sm select-add-group"
                                            style="width: 100%;" {{ $disabled }}>
                                            <option value="">+ Thêm Tổ...</option>
                                            @foreach ($groups as $g)
                                                @php
                                                    $isAlreadyInList = false;
                                                    foreach ($groupPermissions as $gp) {
                                                        if (explode(':', $gp)[0] == $g->code) {
                                                            $isAlreadyInList = true;
                                                            break;
                                                        }
                                                    }
                                                @endphp
                                                @if (!$isAlreadyInList)
                                                    <option value="{{ $g->code }}">{{ $g->name }}</option>
                                                @endif
                                            @endforeach
                                        </select>
                                    @endif

                                </div>
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

<script src="{{ asset('js/vendor/jquery-1.12.4.min.js') }}"></script>
<script src="{{ asset('js/popper.min.js') }}"></script>
<script src="{{ asset('js/bootstrap.min.js') }}"></script>
<script src="{{ asset('js/sweetalert2.all.min.js') }}"></script>
<script src="{{ asset('assets/vendor/select2/select2.min.js') }}"></script>


<script>
    $(document).ready(function() {
        document.body.style.overflowY = "auto";
        const canEdit = {{ $canEdit ? 'true' : 'false' }};

        const groupsData = @json($groups);

        function initPermissionsSelect2() {
            $('.select2-filter').select2({
                theme: 'bootstrap4'
            });
            $('.select-add-group').select2({
                placeholder: "+ Thêm Tổ...",
                allowClear: true,
                theme: 'bootstrap4'
            });
        }

        $('#data_table_personnel').DataTable({
            paging: true,
            lengthChange: true,
            searching: true,
            ordering: true,
            info: true,
            autoWidth: false,
            pageLength: 25,
            lengthMenu: [
                [10, 25, 50, 100, -1],
                [10, 25, 50, 100, "Tất cả"]
            ],
            language: {
                search: "Tìm kiếm:",
                lengthMenu: "Hiển thị _MENU_ dòng",
                info: "Hiển thị _START_ đến _END_ của _TOTAL_ dòng",
                paginate: {
                    previous: "Trước",
                    next: "Sau"
                }
            },
            drawCallback: function() {
                initPermissionsSelect2();
                if (typeof updateDashboard === 'function' && $('#personnel-dashboard').is(
                        ':visible')) {
                    updateDashboard();
                }
            }
        });

        // Handle Quick Add Group
        $(document).on('change', '.select-add-group', function() {
            if (!canEdit) return;
            const $select = $(this);
            const employeeId = $select.closest('.groups-list-container').data('employee-id');
            const groupId = $select.val();
            if (!groupId) return;

            const groupData = [];
            $select.closest('.groups-list-container').find('.btn-toggle-group').each(function() {
                groupData.push($(this).data('group-id') + ':' + $(this).data('active'));
            });
            groupData.push(groupId + ':1');

            updatePermissionAjax(employeeId, 'group', groupData);
            location.reload();
        });

        // Toggle Organizational Group Badge
        $(document).on('click', '.btn-toggle-group', function() {
            if (!canEdit) return;
            const $badge = $(this);
            const employeeId = $badge.closest('.groups-list-container').data('employee-id');
            const isActivating = !($badge.data('active') == 1);

            const $container = $badge.closest('.groups-list-container');

            if (isActivating) {
                $badge.data('active', 1).addClass('badge-primary active').removeClass('inactive');
                $badge.attr('title', 'Nhấn để vô hiệu hóa');
            } else {
                const activeCount = $container.find('.btn-toggle-group.active').length;
                if (activeCount <= 1) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Thao tác bị chặn',
                        text: 'Một nhân viên luôn luôn phải có ít nhất 1 tổ hoạt động.'
                    });
                    return;
                }
                $badge.data('active', 0).removeClass('badge-primary active').addClass('inactive');
                $badge.attr('title', 'Nhấn để kích hoạt');
            }

            const groupData = [];
            $container.find('.btn-toggle-group').each(function() {
                groupData.push($(this).data('group-id') + ':' + $(this).data('active'));
            });
            updatePermissionAjax(employeeId, 'group', groupData);
        });

        function updatePermissionAjax(employeeId, type, ids) {
            $.ajax({
                url: "{{ route('pages.quota.personnel.updatePermissions') }}",
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    employee_id: employeeId,
                    type: type,
                    ids: ids
                },
                success: function(res) {
                    if (res.success) {
                        Swal.mixin({
                            toast: true,
                            position: 'top-end',
                            showConfirmButton: false,
                            timer: 2000,
                            timerProgressBar: true
                        }).fire({
                            icon: 'success',
                            title: res.message
                        });
                        updateDashboard();
                    } else {
                        Swal.fire('Lỗi', res.message, 'error');
                        location.reload();
                    }
                },
                error: function() {
                    Swal.fire('Lỗi', 'Không thể kết nối máy chủ', 'error');
                }
            });
        }

        // Dashboard logic
        $('#btn-toggle-dashboard').click(function() {
            const $dashboard = $('#personnel-dashboard');
            const $text = $('#text-toggle-dashboard');
            const isVisible = $dashboard.is(':visible');

            if (isVisible) {
                $dashboard.slideUp();
                $text.text('Hiển thị Dashboard');
            } else {
                $dashboard.slideDown();
                $text.text('Ẩn Dashboard');
                updateDashboard();
                fetchShiftData();
            }
        });

        function updateDashboard() {
            const groupStats = {};
            var table = $('#data_table_personnel').DataTable();
            var allRows = table.rows().nodes();

            $(allRows).each(function() {
                const $tr = $(this);
                // Group stats
                $tr.find('.btn-toggle-group').each(function() {
                    const name = $(this).text().trim();
                    const isActive = $(this).hasClass('active');
                    if (!groupStats[name]) groupStats[name] = {
                        active: 0,
                        inactive: 0
                    };
                    if (isActive) groupStats[name].active++;
                    else groupStats[name].inactive++;
                });
            });

            // Render bars
            renderDashboardBars($('#dash-group-bars-container'), groupStats, 'primary');

            // Render summary table
            renderSummaryTable(groupStats);

            // Update header counts
            $('#dash-active-groups-count').text(Object.keys(groupStats).length);
        }

        function renderDashboardBars($container, stats, colorClass) {
            $container.empty();
            const totalItems = Object.keys(stats).length;
            if (totalItems === 0) {
                $container.append('<div class="text-center py-4 text-muted small">Chưa có dữ liệu</div>');
                return;
            }

            Object.keys(stats).sort().forEach(name => {
                const s = stats[name];
                const total = s.active + s.inactive;
                const percent = total > 0 ? (s.active / total * 100) : 0;

                const html = `
                    <div class="mb-3">
                        <div class="d-flex justify-content-between mb-1 small">
                            <span class="font-weight-bold text-dark">${name}</span>
                            <span class="text-muted">${s.active}/${total} (${Math.round(percent)}%)</span>
                        </div>
                        <div class="progress shadow-sm" style="height: 10px; border-radius: 5px;">
                            <div class="progress-bar bg-${colorClass}" role="progressbar" 
                                 style="width: ${percent}%; border-radius: 5px;" 
                                 aria-valuenow="${percent}" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                    </div>
                `;
                $container.append(html);
            });
        }

        function renderSummaryTable(groupStats) {
            const $tbody = $('#dash-summary-tbody');
            $tbody.empty();

            Object.keys(groupStats).sort().forEach(name => {
                const s = groupStats[name];
                $tbody.append(`
                    <tr>
                        <td class="font-weight-bold">${name}</td>
                        <td class="text-center text-success font-weight-bold">${s.active}</td>
                        <td class="text-center text-danger">${s.inactive}</td>
                        <td class="text-center font-weight-bold">${s.active + s.inactive}</td>
                    </tr>
                `);
            });
        }

        function fetchShiftData() {
            const range = $('#dash-shift-range-select').val();
            const department = '{{ $currentDepartment }}';
            const depId = '{{ $apiDepId }}';

            let url = `/quota/personnel/stats/${depId}`;
            let params = {
                range: range,
                group_id: '{{ $filterGroupId }}'
            };

            if (range === 'day') {
                params.date = $('#dash-shift-date-input').val();
            } else if (range === 'week') {
                params.week = $('#dash-shift-week-input').val();
                params.year = $('#dash-shift-week-year').val();
            } else if (range === 'month') {
                params.month = $('#dash-shift-month-input').val();
                params.year = $('#dash-shift-month-year').val();
            }

            $('#shift-loading').show();
            $('#shift-content').hide();

            $.ajax({
                url: url,
                method: 'GET',
                data: params,
                timeout: 10000,
                success: function(res) {
                    $('#shift-loading').hide();
                    $('#shift-content').show();

                    if (range === 'day') {
                        $('#shift-card-view').show();
                        $('#shift-table-view').hide();
                        updateShiftCards(res.data);
                        $('#shift-range-title').text(params.date);
                    } else {
                        $('#shift-card-view').hide();
                        $('#shift-table-view').show();
                        renderShiftTable(res.data, range);
                        if (range === 'week') $('#shift-range-title').text(
                            `Tuần ${params.week}/${params.year}`);
                        else $('#shift-range-title').text(`Tháng ${params.month}/${params.year}`);
                    }
                },
                error: function() {
                    $('#shift-loading').html(
                        '<span class="text-danger small font-weight-bold"><i class="fas fa-exclamation-triangle"></i> Không thể kết nối server API (1.248)</span>'
                    );
                }
            });
        }

        function updateShiftCards(data) {
            const d = data[0] || {};
            $('#dash-shift-hc').text(d.hc || 0);
            $('#dash-shift-c1').text(d.c1 || 0);
            $('#dash-shift-c2').text(d.c2 || 0);
            $('#dash-shift-c3').text(d.c3 || 0);
            $('#dash-shift-c4').text(d.c4 || 0);
            $('#dash-shift-p').text(d.p || 0);
        }

        function renderShiftTable(data, range) {
            const $tbody = $('#shift-table-body');
            $tbody.empty();
            data.forEach(d => {
                $tbody.append(`
                    <tr>
                        <td class="font-weight-bold">${d.date || d.day_of_month}</td>
                        <td class="text-success font-weight-bold">${d.hc || 0}</td>
                        <td>${d.c1 || 0}</td>
                        <td>${d.c2 || 0}</td>
                        <td>${d.c3 || 0}</td>
                        <td>${d.c4 || 0}</td>
                        <td class="text-danger">${d.p || 0}</td>
                    </tr>
                `);
            });
        }

        $('#dash-shift-range-select').change(function() {
            const val = $(this).val();
            $('.shift-input-group').hide();
            $(`#input-group-${val}`).show();
            fetchShiftData();
        });

        $('#btn-refresh-shifts').click(fetchShiftData);
        $('#dash-shift-date-input, #dash-shift-week-input, #dash-shift-week-year, #dash-shift-month-input, #dash-shift-month-year')
            .change(fetchShiftData);

    });
</script>
