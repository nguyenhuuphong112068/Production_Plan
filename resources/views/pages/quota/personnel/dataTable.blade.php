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
        vertical-align: top !important;
        padding: 4px 8px !important;
    }

    /* Professional Maternity Switch */
    .maternity-switch.custom-switch .custom-control-input:checked~.custom-control-label::before {
        background-color: #e83e8c;
        border-color: #e83e8c;
    }

    .maternity-switch .custom-control-label {
        font-weight: 500;
        transition: color 0.2s ease-in-out;
    }

    .maternity-switch .custom-control-input:checked~.custom-control-label {
        color: #e83e8c !important;
        font-weight: 600;
    }

    /* Style Phân nhóm Tổ - Phòng */
    .group-segment {
        border-bottom: 2px solid #dee2e6 !important;
        padding-top: 8px;
        padding-bottom: 8px;
    }

    .group-segment:last-child {
        border-bottom: none !important;
    }

    .room-list .group-segment {
        min-height: 72px;
        display: flex;
        flex-direction: column;
        justify-content: center;
    }

    /* Level Color Gradient */
    .room-level-select.lvl-1 {
        background-color: #e3f2fd !important;
        color: #0d47a1 !important;
        font-weight: bold;
    }

    .room-level-select.lvl-2 {
        background-color: #bbdefb !important;
        color: #0d47a1 !important;
        font-weight: bold;
    }

    .room-level-select.lvl-3 {
        background-color: #64b5f6 !important;
        color: #ffffff !important;
        font-weight: bold;
    }

    .room-level-select.lvl-4 {
        background-color: #1565c0 !important;
        color: #ffffff !important;
        font-weight: bold;
    }

    .work-hours-badge {
        font-size: 0.7rem;
        padding: 2px 5px;
        border-radius: 10px;
        background-color: #f0f4f8;
        color: #546e7a;
        border: 1px solid #cfd8dc;
        white-space: nowrap;
        margin-left: 5px;
        display: inline-block;
    }

    .work-hours-badge i {
        font-size: 0.6rem;
        margin-right: 2px;
    }

    .work-hours-badge .yearly {
        color: #1e88e5;
        font-weight: bold;
    }

    .work-hours-badge .total {
        color: #43a047;
        font-weight: bold;
    }

    .filter-section {
        background-color: #f8f9fa;
        padding: 15px;
        border-radius: 8px;
        margin-bottom: 15px;
        border: 1px solid #e9ecef;
    }

    .room-assignment-row {
        padding-top: 5px;
        padding-bottom: 5px;
        border-bottom: 1px dashed #dee2e6;
    }

    .room-assignment-row:last-child {
        border-bottom: none;
    }

    .room-assignment-row.inactive {
        background-color: #f8f9fa;
        opacity: 0.6;
    }

    .room-assignment-row.inactive select {
        background-color: #e9ecef;
        pointer-events: none;
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
            <h3 class="card-title">Danh sách nhân sự</h3>
        </div>
        <div class="card-body">
            <!-- Dashboard Section -->
            @php
                $depMapping = [
                    'PXV1' => 15,
                    'PXV2' => 31,
                    'PXVH' => 30,
                    'PXDN' => 30,
                    'EN' => 3,
                    'PXN' => 6,
                    'PXTN' => 6,
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
                                    <div class="col-4">
                                        <h3 class="text-primary font-weight-bold mb-0" id="dash-total-emp">
                                            {{ count($datas) }}</h3>
                                        <span class="small font-weight-bold text-muted">Tổng Nhân Sự</span>
                                    </div>
                                    <div class="col-4">
                                        <h3 class="text-success font-weight-bold mb-0" id="dash-active-emp-count">0</h3>
                                        <span class="small font-weight-bold text-muted">Đã Phân Công</span>
                                    </div>
                                    <div class="col-4">
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
                                        <a class="nav-link small py-1" id="dash-room-tab" data-toggle="tab"
                                            href="#dash-room-content" role="tab">Theo Phòng</a>
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
                                    <div class="tab-pane fade" id="dash-room-content" role="tabpanel">
                                        <div id="dash-room-bars-container"
                                            style="max-height: 250px; overflow-y: auto; overflow-x: hidden; padding-right: 5px;">
                                            <!-- Room stats render here -->
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
                                    <option value="{{ $g->id }}"
                                        {{ $filterGroupId == $g->id ? 'selected' : '' }}>{{ $g->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="small font-weight-bold">Lọc theo Phòng:</label>
                            <select name="room_id" class="form-control form-control-sm select2-filter"
                                onchange="this.form.submit()">
                                <option value="">-- Tất cả các Phòng --</option>
                                @foreach ($rooms as $r)
                                    <option value="{{ $r->id }}"
                                        {{ $filterRoomId == $r->id ? 'selected' : '' }}>
                                        {{ $r->code }} - {{ $r->name }} - {{ $r->main_equiment_name }}
                                    </option>
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

                        <th style="width: 100px;">Phân Xưởng Trực Thuộc</th>

                        <th style="width: 680px; padding: 0;">
                            <div class="d-flex w-100" style="margin: 0;">
                                <div
                                    style="width: 180px; min-width: 180px; max-width: 180px; padding: .75rem; border-right: 1px solid #dee2e6; font-weight: bold;">
                                    Tổ Được Phép Công Tác</div>
                                <div
                                    style="width: 500px; min-width: 500px; max-width: 500px; padding: .75rem; font-weight: bold;">
                                    Phòng Được Phép Công Tác</div>
                            </div>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $roomsById = clone $rooms;
                        $roomsById = $roomsById->keyBy('id')->all();

                        $groupsByCode = clone $groups;
                        $groupsByCode = $groupsByCode->keyBy('code')->all();

                        $groupsById = clone $groups;
                        $groupsById = $groupsById->keyBy('id')->all();

                        $roomOptionsHtml = '';
                        foreach ($rooms as $r) {
                            $roomOptionsHtml .=
                                '<option value="' .
                                $r->id .
                                '">' .
                                htmlspecialchars($r->code . ' - ' . $r->name . ' - ' . $r->main_equiment_name) .
                                '</option>';
                        }
                    @endphp
                    @foreach ($datas as $data)
                        <tr class="main-employee-row">
                            <td>{{ $loop->iteration }} <br>
                                @if (session('user')['userGroup'] == 'Admin')
                                    {{ $data->id }}
                                @endif
                            </td>
                            <td>{{ $data->code }}</td>
                            <td>
                                <div class="font-weight-bold">{{ $data->name }}</div>
                                <div class="custom-control custom-switch maternity-switch mt-2 mx-4">
                                    <input type="checkbox" class="custom-control-input toggle-maternity-leave"
                                        id="maternity_{{ $data->id }}" data-id="{{ $data->id }}"
                                        {{ $data->on_maternity_leave ? 'checked' : '' }} {{ $disabled }}>
                                    <label class="custom-control-label small text-muted"
                                        for="maternity_{{ $data->id }}" style="cursor:pointer;"
                                        title="Đánh dấu nếu nhân sự này đang nghỉ thai sản">
                                        <i class="fas fa-baby mr-1" style="font-size: 0.9em; opacity: 0.8;"></i>Đang
                                        nghỉ thai sản
                                    </label>
                                    @if($data->maternity_leave_updated_by)
                                        <div class="small text-muted mt-1" style="font-size: 0.75rem;">
                                            <i class="fas fa-user-edit mr-1"></i>{{ $data->maternity_leave_updated_by }} | {{ \Carbon\Carbon::parse($data->maternity_leave_updated_at)->format('d/m/y') }}
                                        </div>
                                    @endif
                                </div>
                            </td>

                            <td class="text-center">
                                <span class="badge badge-info shadow-sm">{{ $data->main_production }}</span>
                            </td>

                            <td class="p-0">
                                @php
                                    $groupPermissions = $data->allowed_groups
                                        ? explode('|', $data->allowed_groups)
                                        : [];
                                    $assignments = $data->allowed_rooms_with_levels
                                        ? explode('|', $data->allowed_rooms_with_levels)
                                        : [];

                                    // Gom nhóm phòng theo ID tổ
                                    $assignmentsByGroup = [];
                                    foreach ($assignments as $as) {
                                        $parts = explode(':', $as);
                                        $rId = $parts[0];
                                        $rLvl = $parts[1] ?? 1;
                                        $rActive = $parts[2] ?? 1;
                                        $rUser = $parts[3] ?? 'N/A';
                                        $rDate = $parts[4] ?? '';

                                        $rGrpId_passed = $parts[5] ?? null;
                                        $rPriorityLevel = $parts[6] ?? 1;

                                        $rObj = $roomsById[$rId] ?? null;
                                        $rGrpId = 0;
                                        if (is_numeric($rGrpId_passed) && $rGrpId_passed > 0) {
                                            $rGrpId = (int) $rGrpId_passed;
                                        } elseif ($rObj) {
                                            $rGrpCode = $rObj->group_code;
                                            $rGrpId = isset($groupsByCode[$rGrpCode])
                                                ? $groupsByCode[$rGrpCode]->id
                                                : 0;
                                        }
                                        $assignmentsByGroup[$rGrpId][] = [
                                            'as' => $as,
                                            'rId' => $rId,
                                            'rLvl' => $rLvl,
                                            'rActive' => $rActive,
                                            'rUser' => $rUser,
                                            'rDate' => $rDate,
                                            'rPriorityLevel' => $rPriorityLevel,
                                        ];
                                    }

                                    $renderedGroupIds = [];
                                    foreach ($groupPermissions as $gp) {
                                        $renderedGroupIds[] = (int) explode(':', $gp)[0];
                                    }

                                    // Gom các phòng không thuộc về tổ nào đang hiển thị
                                    $otherAssignments = [];
                                    foreach ($assignments as $as) {
                                        $parts = explode(':', $as);
                                        $rId = $parts[0];
                                        $rGrpId_passed = $parts[5] ?? null;
                                        $rObj = $roomsById[$rId] ?? null;
                                        $rGrpId = 0;
                                        if (is_numeric($rGrpId_passed) && $rGrpId_passed > 0) {
                                            $rGrpId = (int) $rGrpId_passed;
                                        } elseif ($rObj) {
                                            $rGrpCode = $rObj->group_code;
                                            $rGrpId = (int) (isset($groupsByCode[$rGrpCode])
                                                ? $groupsByCode[$rGrpCode]->id
                                                : 0);
                                        }
                                        if (!in_array($rGrpId, $renderedGroupIds)) {
                                            $otherAssignments[] = [
                                                'as' => $as,
                                                'rId' => $rId,
                                                'rLvl' => $parts[1] ?? 1,
                                                'rActive' => $parts[2] ?? 1,
                                                'rUser' => $parts[3] ?? 'N/A',
                                                'rDate' => $parts[4] ?? '',
                                                'rGrpId' => $rGrpId,
                                                'rPriorityLevel' => $parts[6] ?? 1,
                                            ];
                                        }
                                    }
                                @endphp
                                <table class="w-100"
                                    style="margin: 0; border: none; border-collapse: collapse; background: transparent; table-layout: fixed;">
                                    @foreach ($groupPermissions as $index => $gp)
                                        @php
                                            $parts = explode(':', $gp);
                                            $gId = $parts[0];
                                            $isActive = ($parts[1] ?? 1) == 1;
                                            $gUser = $parts[2] ?? 'N/A';
                                            $gDate = $parts[3] ?? '';
                                            $groupName = isset($groupsById[$gId]) ? $groupsById[$gId]->name : 'N/A';

                                            $groupAssignments = $assignmentsByGroup[$gId] ?? [];
                                        @endphp
                                        <tr style="border-bottom: 2px solid #dee2e6; background: transparent;">
                                            <!-- Cột Tổ -->
                                            <td
                                                style="width: 180px; min-width: 180px; max-width: 180px; border: none; border-right: 1px solid #dee2e6; vertical-align: top; padding: 8px;">
                                                <div class="groups-list-container"
                                                    data-employee-id="{{ $data->id }}">
                                                    <div class="assignment-item"
                                                        style="border-bottom: none; margin-bottom: 0; padding-bottom: 0;">
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
                                                    @if ($isActive)
                                                        @php
                                                            $noRoomGroups = [4, 9]; // Văn Phòng, VSCN + Kho BTP
                                                            $isRoomSupported = !in_array((int) $gId, $noRoomGroups);
                                                            $addRoomDisabled =
                                                                $disabled === 'disabled' || !$isRoomSupported
                                                                    ? 'disabled'
                                                                    : '';
                                                        @endphp
                                                        <div class="mt-2 text-left">
                                                            <button
                                                                class="btn btn-xs btn-outline-primary btn-add-room-row"
                                                                data-group-id="{{ $gId }}"
                                                                {{ $addRoomDisabled }}
                                                                @if (!$isRoomSupported && $disabled !== 'disabled') title="Tổ này không có phòng công tác" @endif><i
                                                                    class="fas fa-plus"></i> Thêm phòng</button>
                                                        </div>
                                                    @endif
                                                </div>
                                            </td>

                                            <!-- Cột Phòng -->
                                            <td
                                                style="border: none; vertical-align: top; padding: 8px; width: 500px; min-width: 500px; max-width: 500px;">
                                                <div class="room-assignments-container"
                                                    data-employee-id="{{ $data->id }}">
                                                    <div class="room-list">
                                                        @if (empty($groupAssignments))
                                                            <div class="text-muted py-1 font-italic small"
                                                                style="padding-left: 10px;">Chưa gán phòng cho tổ này
                                                            </div>
                                                        @else
                                                            @foreach ($groupAssignments as $item)
                                                                @php
                                                                    $rId = $item['rId'];
                                                                    $rLvl = $item['rLvl'];
                                                                    $rActive = $item['rActive'];
                                                                    $rUser = $item['rUser'];
                                                                    $rDate = $item['rDate'];
                                                                    $rGrpId = $gId;
                                                                    $rPriorityLevel = $item['rPriorityLevel'] ?? 1;
                                                                @endphp
                                                                <div class="room-assignment-row d-flex align-items-center mb-1 {{ $rActive == 0 ? 'inactive' : '' }}"
                                                                    data-active="{{ $rActive }}"
                                                                    data-group-id="{{ $rGrpId }}"
                                                                    data-priority="{{ $rPriorityLevel }}">
                                                                    <i class="fas fa-grip-vertical text-muted mr-2 cursor-grab"
                                                                        style="cursor: grab;"
                                                                        title="Kéo thả để sắp xếp độ ưu tiên"></i>
                                                                    <span
                                                                        class="badge badge-secondary priority-badge mr-1"
                                                                        style="width: 20px; text-align: center;"
                                                                        title="Ưu tiên {{ $rPriorityLevel }}">{{ $rPriorityLevel }}</span>
                                                                    <div class="input-group input-group-sm mr-1"
                                                                        style="width: 250px;">
                                                                        @php
                                                                            $roomNameDisp =
                                                                                $rId && isset($roomsById[$rId])
                                                                                    ? $roomsById[$rId]->code .
                                                                                        ' - ' .
                                                                                        $roomsById[$rId]->name .
                                                                                        ' - ' .
                                                                                        $roomsById[$rId]
                                                                                            ->main_equiment_name
                                                                                    : '-- Phòng --';
                                                                        @endphp
                                                                        <input type="text"
                                                                            class="form-control room-name-display"
                                                                            readonly value="{{ $roomNameDisp }}"
                                                                            style="font-size: 0.8rem; background-color: #fff; cursor: pointer;"
                                                                            title="{{ $roomNameDisp }}">
                                                                        <input type="hidden" class="room-id-select"
                                                                            value="{{ $rId }}">
                                                                        <div class="input-group-append">
                                                                            <button
                                                                                class="btn btn-outline-info btn-edit-room-modal"
                                                                                type="button" {{ $disabled }}
                                                                                title="Đổi phòng"><i
                                                                                    class="fas fa-edit"></i></button>
                                                                        </div>
                                                                    </div>
                                                                    <select
                                                                        class="form-control form-control-sm room-level-select mr-1 lvl-{{ $rLvl }}"
                                                                        style="width: 70px;" {{ $disabled }}>
                                                                        <option value="1"
                                                                            {{ $rLvl == 1 ? 'selected' : '' }}>1
                                                                        </option>
                                                                        <option value="2"
                                                                            {{ $rLvl == 2 ? 'selected' : '' }}>2
                                                                        </option>
                                                                        <option value="3"
                                                                            {{ $rLvl == 3 ? 'selected' : '' }}>3
                                                                        </option>
                                                                        <option value="4"
                                                                            {{ $rLvl == 4 ? 'selected' : '' }}>4
                                                                        </option>
                                                                    </select>

                                                                    @php
                                                                        $hYear =
                                                                            $workHours[$data->id][$rId]['year'] ?? 0;
                                                                        $hTotal =
                                                                            $workHours[$data->id][$rId]['total'] ?? 0;
                                                                    @endphp
                                                                    <span class="work-hours-badge"
                                                                        title="Thời gian đã làm việc tại phòng này">
                                                                        <span class="yearly" title="Năm hiện tại"><i
                                                                                class="fas fa-calendar-alt"></i>{{ $hYear }}h</span>
                                                                        |
                                                                        <span class="total" title="Tổng cộng"><i
                                                                                class="fas fa-history"></i>{{ $hTotal }}h</span>
                                                                    </span>

                                                                    <span class="assignment-meta mx-2"
                                                                        style="min-width: 100px;">
                                                                        <i class="fas fa-user-edit"></i>
                                                                        {{ $rUser }} <br>
                                                                        <i class="fas fa-calendar-check"></i>
                                                                        {{ $rDate }}
                                                                    </span>

                                                                    <button
                                                                        class="btn btn-sm btn-{{ $rActive == 1 ? 'danger' : 'success' }} btn-toggle-room-active ml-1"
                                                                        {{ $disabled }}
                                                                        title="{{ $rActive == 1 ? 'Vô hiệu hóa' : 'Kích hoạt' }}">
                                                                        <i
                                                                            class="fas fa-{{ $rActive == 1 ? 'times' : 'undo' }}"></i>
                                                                    </button>
                                                                    <button
                                                                        class="btn btn-sm btn-danger btn-remove-room-row ml-1"
                                                                        {{ $disabled }} title="Xóa phòng">
                                                                        <i class="fas fa-trash"></i>
                                                                    </button>
                                                                </div>
                                                            @endforeach
                                                        @endif
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach

                                    @if (!empty($otherAssignments))
                                        <tr style="border-bottom: 2px solid #dee2e6; background: transparent;">
                                            <td
                                                style="width: 180px; min-width: 180px; max-width: 180px; border: none; border-right: 1px solid #dee2e6; vertical-align: top; padding: 8px;">
                                                <span class="badge badge-secondary">Khác</span>
                                            </td>
                                            <td
                                                style="border: none; vertical-align: top; padding: 8px; width: 500px; min-width: 500px; max-width: 500px;">
                                                <div class="room-assignments-container"
                                                    data-employee-id="{{ $data->id }}">
                                                    <div class="room-list">
                                                        <div class="text-warning small mb-1 font-weight-bold"><i
                                                                class="fas fa-exclamation-triangle"></i> Khác (Không
                                                            thuộc Tổ đã chọn)</div>
                                                        @foreach ($otherAssignments as $item)
                                                            @php
                                                                $rId = $item['rId'];
                                                                $rLvl = $item['rLvl'];
                                                                $rActive = $item['rActive'];
                                                                $rUser = $item['rUser'];
                                                                $rDate = $item['rDate'];
                                                                $rGrpId = $item['rGrpId'] ?? 0;
                                                                $rPriorityLevel = $item['rPriorityLevel'] ?? 1;
                                                            @endphp
                                                            <div class="room-assignment-row d-flex align-items-center mb-1 {{ $rActive == 0 ? 'inactive' : '' }}"
                                                                data-active="{{ $rActive }}"
                                                                data-group-id="{{ $rGrpId }}"
                                                                data-priority="{{ $rPriorityLevel }}">
                                                                <i class="fas fa-grip-vertical text-muted mr-2 cursor-grab"
                                                                    style="cursor: grab;"
                                                                    title="Kéo thả để sắp xếp độ ưu tiên"></i>
                                                                <span class="badge badge-secondary priority-badge mr-1"
                                                                    style="width: 20px; text-align: center;"
                                                                    title="Ưu tiên {{ $rPriorityLevel }}">{{ $rPriorityLevel }}</span>
                                                                <div class="input-group input-group-sm mr-1"
                                                                    style="width: 250px;">
                                                                    @php
                                                                        $roomNameDisp =
                                                                            $rId && isset($roomsById[$rId])
                                                                                ? $roomsById[$rId]->code .
                                                                                    ' - ' .
                                                                                    $roomsById[$rId]->name .
                                                                                    ' - ' .
                                                                                    $roomsById[$rId]->main_equiment_name
                                                                                : '-- Phòng --';
                                                                    @endphp
                                                                    <input type="text"
                                                                        class="form-control room-name-display" readonly
                                                                        value="{{ $roomNameDisp }}"
                                                                        style="font-size: 0.8rem; background-color: #fff; cursor: pointer;"
                                                                        title="{{ $roomNameDisp }}">
                                                                    <input type="hidden" class="room-id-select"
                                                                        value="{{ $rId }}">
                                                                    <div class="input-group-append">
                                                                        <button
                                                                            class="btn btn-outline-info btn-edit-room-modal"
                                                                            type="button" {{ $disabled }}
                                                                            title="Đổi phòng"><i
                                                                                class="fas fa-edit"></i></button>
                                                                    </div>
                                                                </div>
                                                                <select
                                                                    class="form-control form-control-sm room-level-select mr-1 lvl-{{ $rLvl }}"
                                                                    style="width: 70px;" {{ $disabled }}>
                                                                    <option value="1"
                                                                        {{ $rLvl == 1 ? 'selected' : '' }}>1</option>
                                                                    <option value="2"
                                                                        {{ $rLvl == 2 ? 'selected' : '' }}>2</option>
                                                                    <option value="3"
                                                                        {{ $rLvl == 3 ? 'selected' : '' }}>3</option>
                                                                    <option value="4"
                                                                        {{ $rLvl == 4 ? 'selected' : '' }}>4</option>
                                                                </select>

                                                                @php
                                                                    $hYear = $workHours[$data->id][$rId]['year'] ?? 0;
                                                                    $hTotal = $workHours[$data->id][$rId]['total'] ?? 0;
                                                                @endphp
                                                                <span class="work-hours-badge"
                                                                    title="Thời gian đã làm việc tại phòng này">
                                                                    <span class="yearly" title="Năm hiện tại"><i
                                                                            class="fas fa-calendar-alt"></i>{{ $hYear }}h</span>
                                                                    |
                                                                    <span class="total" title="Tổng cộng"><i
                                                                            class="fas fa-history"></i>{{ $hTotal }}h</span>
                                                                </span>

                                                                <span class="assignment-meta mx-2"
                                                                    style="min-width: 100px;">
                                                                    <i class="fas fa-user-edit"></i>
                                                                    {{ $rUser }} <br>
                                                                    <i class="fas fa-calendar-check"></i>
                                                                    {{ $rDate }}
                                                                </span>

                                                                <button
                                                                    class="btn btn-sm btn-{{ $rActive == 1 ? 'danger' : 'success' }} btn-toggle-room-active ml-1"
                                                                    {{ $disabled }}
                                                                    title="{{ $rActive == 1 ? 'Vô hiệu hóa' : 'Kích hoạt' }}">
                                                                    <i
                                                                        class="fas fa-{{ $rActive == 1 ? 'times' : 'undo' }}"></i>
                                                                </button>
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    @endif

                                    <tr style="background: transparent;">
                                        <!-- Cột Tổ: dropdown + Thêm Tổ -->
                                        <td
                                            style="width: 180px; min-width: 180px; max-width: 180px; border: none; border-right: 1px solid #dee2e6; padding: 8px; vertical-align: bottom;">
                                            <div class="groups-list-container"
                                                data-employee-id="{{ $data->id }}">
                                                <select class="form-control form-control-sm select-add-group"
                                                    style="width: 100%;" {{ $disabled }}>
                                                    <option value="">+ Thêm Tổ...</option>
                                                    @foreach ($groups as $g)
                                                        @php
                                                            $isAlreadyInList = false;
                                                            foreach ($groupPermissions as $gp) {
                                                                if (explode(':', $gp)[0] == $g->id) {
                                                                    $isAlreadyInList = true;
                                                                    break;
                                                                }
                                                            }
                                                        @endphp
                                                        @if (!$isAlreadyInList)
                                                            <option value="{{ $g->id }}">{{ $g->name }}
                                                            </option>
                                                        @endif
                                                    @endforeach
                                                </select>
                                            </div>
                                        </td>

                                        <!-- Cột Phòng trống -->
                                        <td
                                            style="border: none; padding: 8px; vertical-align: bottom; width: 500px; min-width: 500px; max-width: 500px;">
                                        </td>
                                    </tr>
                                </table>
                    @endforeach

                </tbody>
            </table>
        </div>
        <!-- /.card-body -->
    </div>
    <!-- /.card -->
</div>

<script src="{{ asset('assets/plugins/local_cdn/Sortable.min.js') }}"></script>
<script src="{{ asset('js/vendor/jquery-1.12.4.min.js') }}"></script>
<script src="{{ asset('js/popper.min.js') }}"></script>
<script src="{{ asset('js/bootstrap.min.js') }}"></script>
<script src="{{ asset('js/sweetalert2.all.min.js') }}"></script>
<script src="{{ asset('assets/vendor/select2/select2.min.js') }}"></script>


<!-- Modal Chọn Phòng -->
<div class="modal fade" id="editRoomModal" tabindex="-1" role="dialog" aria-hidden="true"
    style="z-index: 1060;">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title"><i class="fas fa-door-open"></i> Chọn Phòng Công Tác</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label>Phòng</label>
                    <select id="modalRoomSelect" class="form-control select2" style="width: 100%;">
                        <option value="">-- Chọn Phòng --</option>
                        {!! $roomOptionsHtml !!}
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
                <button type="button" class="btn btn-primary" id="btn-save-room-modal">Lưu thay đổi</button>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        document.body.style.overflowY = "auto";
        const canEdit = {{ $canEdit ? 'true' : 'false' }};

        const groupsData = @json($groups);
        const roomsData = @json($rooms);

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

        initPermissionsSelect2();

        // Handle Quick Add Group
        $(document).on('change', '.select-add-group', function() {
            if (!canEdit) return;
            const $select = $(this);
            const $trMain = $select.closest('.main-employee-row');
            const employeeId = $select.closest('.groups-list-container').data('employee-id');
            const groupId = $select.val();
            const groupName = $select.find('option:selected').text();
            if (!groupId) return;

            const groupData = [];
            $trMain.find('.btn-toggle-group').each(function() {
                groupData.push($(this).data('group-id') + ':' + $(this).data('active'));
            });
            groupData.push(groupId + ':1');

            updatePermissionAjax(employeeId, 'group', groupData);

            // Remove option so it cannot be added again
            $select.find(`option[value="${groupId}"]`).remove();
            $select.val('');

            // Destory and re-init select2 to reflect changes visually
            $select.select2('destroy');
            $select.select2({
                placeholder: "+ Thêm Tổ...",
                allowClear: true,
                theme: 'bootstrap4'
            });

            const today = new Date();
            const day = String(today.getDate()).padStart(2, '0');
            const month = String(today.getMonth() + 1).padStart(2, '0');
            const year = String(today.getFullYear()).slice(-2);
            const todayStr = `${day}/${month}/${year}`;

            const isRoomSupported = !([4, 9].includes(parseInt(groupId)));
            let addRoomBtnHtml = '';
            if (isRoomSupported) {
                addRoomBtnHtml = `
                    <div class="mt-2 text-left">
                        <button class="btn btn-xs btn-outline-primary btn-add-room-row" data-group-id="${groupId}">
                            <i class="fas fa-plus"></i> Thêm phòng
                        </button>
                    </div>
                `;
            } else {
                addRoomBtnHtml = `
                    <div class="mt-2 text-left">
                        <button class="btn btn-xs btn-outline-primary btn-add-room-row" data-group-id="${groupId}" disabled title="Tổ này không có phòng công tác">
                            <i class="fas fa-plus"></i> Thêm phòng
                        </button>
                    </div>
                `;
            }

            const newGroupRow = `
                <tr style="border-bottom: 2px solid #dee2e6; background: transparent;">
                    <td style="width: 180px; min-width: 180px; max-width: 180px; border: none; border-right: 1px solid #dee2e6; vertical-align: top; padding: 8px;">
                        <div class="groups-list-container" data-employee-id="${employeeId}">
                            <div class="assignment-item" style="border-bottom: none; margin-bottom: 0; padding-bottom: 0;">
                                <span class="badge badge-toggle badge-primary active btn-toggle-group" data-group-id="${groupId}" data-active="1" title="Nhấn để vô hiệu hóa">
                                    ${groupName}
                                </span>
                                <span class="assignment-meta">
                                    <i class="fas fa-user-edit"></i> System Sync <br>
                                    <i class="fas fa-calendar-check"></i> ${todayStr}
                                </span>
                            </div>
                            ${addRoomBtnHtml}
                        </div>
                    </td>
                    <td style="border: none; vertical-align: top; padding: 8px; width: 500px; min-width: 500px; max-width: 500px;">
                        <div class="room-assignments-container" data-employee-id="${employeeId}">
                            <div class="room-list">
                                <div class="text-muted py-1 font-italic small" style="padding-left: 10px;">Chưa gán phòng cho tổ này</div>
                            </div>
                        </div>
                    </td>
                </tr>
            `;

            $select.closest('tr').before(newGroupRow);
        });



        // Toggle Organizational Group Badge
        $(document).on('click', '.btn-toggle-group', function() {
            if (!canEdit) return;
            const $badge = $(this);
            const employeeId = $badge.closest('.groups-list-container').data('employee-id');
            const isActivating = !($badge.data('active') == 1);

            const $container = $badge.closest('.groups-list-container');
            const $tr = $badge.closest('.main-employee-row');

            if (isActivating) {
                $badge.data('active', 1).addClass('badge-primary active').removeClass('inactive');
                $badge.attr('title', 'Nhấn để vô hiệu hóa');
                const currentGroupId = $badge.data('group-id');
                $tr.find('.room-assignment-row[data-group-id="' + currentGroupId + '"]').each(
                    function() {
                        const $row = $(this);
                        $row.attr('data-active', 1).removeClass('inactive');
                        const $btn = $row.find('.btn-toggle-room-active');
                        $btn.addClass('btn-danger').removeClass('btn-success').attr('title',
                            'Vô hiệu hóa');
                        $btn.find('i').addClass('fas fa-times').removeClass('fas fa-undo');
                    });
            } else {
                const activeCount = $tr.find('.btn-toggle-group.active').length;
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
                const groupId = $badge.data('group-id');
                $tr.find('.room-assignment-row[data-group-id="' + groupId + '"]').each(function() {
                    const $row = $(this);
                    $row.attr('data-active', 0).addClass('inactive');
                    const $btn = $row.find('.btn-toggle-room-active');
                    $btn.removeClass('btn-danger').addClass('btn-success').attr('title',
                        'Kích hoạt');
                    $btn.find('i').removeClass('fas fa-times').addClass('fas fa-undo');
                });
            }

            const groupData = [];
            $tr.find('.btn-toggle-group').each(function() {
                groupData.push($(this).data('group-id') + ':' + $(this).data('active'));
            });
            updatePermissionAjax(employeeId, 'group', groupData);
        });

        // Handle Room Assignment Actions
        $(document).on('click', '.btn-add-room-row', function() {
            if (!canEdit) return;
            const $btn = $(this);
            const groupId = $btn.data('group-id');
            const $tr = $btn.closest('.main-employee-row');

            // Find the room-list within the same row of the nested table
            const $list = $btn.closest('tr').find('.room-list');

            if (!groupId) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Chú ý',
                    text: 'Không xác định được Tổ để thêm phòng.',
                    timer: 3000
                });
                return;
            }

            const selectedGroupIds = [groupId.toString()];
            const selectedCodes = groupsData
                .filter(g => selectedGroupIds.includes(g.id.toString()))
                .map(g => g.code.toString());

            // ĐGTC (8) được phép chọn phòng của ĐGSC (7)
            if (selectedCodes.includes('8') && !selectedCodes.includes('7')) {
                selectedCodes.push('7');
            }

            const filteredRooms = roomsData.filter(r => selectedCodes.includes(r.group_code ? r
                .group_code.toString() : ''));
            const alreadySelectedIds = [];
            $list.find('.room-id-select').each(function() {
                const val = $(this).val();
                if (val) alreadySelectedIds.push(val.toString());
            });

            const availableRoom = filteredRooms.find(r => !alreadySelectedIds.includes(r.id
                .toString()));

            if (!availableRoom && filteredRooms.length > 0 && alreadySelectedIds.length >= filteredRooms
                .length) {
                Swal.fire({
                    icon: 'info',
                    title: 'Thông báo',
                    text: 'Tất cả các phòng thuộc Tổ đã chọn đều đã được gán cho nhân sự này.',
                    timer: 3000
                });
                return;
            }

            let newRoomText = '-- Chọn phòng --';
            let newRoomVal = '';
            if (availableRoom) {
                newRoomText =
                    `${availableRoom.code} - ${availableRoom.name} - ${availableRoom.main_equiment_name}`;
                newRoomVal = availableRoom.id;
            }

            const today = new Date();
            const day = String(today.getDate()).padStart(2, '0');
            const month = String(today.getMonth() + 1).padStart(2, '0');
            const year = String(today.getFullYear()).slice(-2);
            const todayStr = `${day}/${month}/${year}`;

            const newRow = `
                <div class="room-assignment-row d-flex align-items-center mb-1" data-active="1" data-group-id="${groupId}">
                    <i class="fas fa-grip-vertical text-muted mr-2 cursor-grab" style="cursor: grab;" title="Kéo thả để sắp xếp độ ưu tiên"></i>
                    <span class="badge badge-secondary priority-badge mr-1" style="width: 20px; text-align: center;">-</span>
                    
                    <div class="input-group input-group-sm mr-1" style="width: 250px;">
                        <input type="text" class="form-control room-name-display" readonly value="${newRoomText}" style="font-size: 0.8rem; background-color: #fff; cursor: pointer;" title="${newRoomText}">
                        <input type="hidden" class="room-id-select" value="${newRoomVal}">
                        <div class="input-group-append">
                            <button class="btn btn-outline-info btn-edit-room-modal" type="button" title="Đổi phòng"><i class="fas fa-edit"></i></button>
                        </div>
                    </div>
                    <select class="form-control form-control-sm room-level-select mr-1 lvl-4" style="width: 70px;">
                        <option value="1">1</option>
                        <option value="2">2</option>
                        <option value="3">3</option>
                        <option value="4" selected>4</option>
                    </select>
                    
                    <span class="work-hours-badge" title="Thời gian đã làm việc tại phòng này">
                        <span class="yearly" title="Năm hiện tại"><i class="fas fa-calendar-alt"></i>0h</span>
                        |
                        <span class="total" title="Tổng cộng"><i class="fas fa-history"></i>0h</span>
                    </span>
                    
                    <span class="assignment-meta mx-2" style="min-width: 100px;">
                        <i class="fas fa-user-edit"></i> System Sync <br>
                        <i class="fas fa-calendar-check"></i> ${todayStr}
                    </span>
                    
                    <button class="btn btn-sm btn-danger btn-toggle-room-active ml-1" title="Vô hiệu hóa">
                        <i class="fas fa-times"></i>
                    </button>
                    <button class="btn btn-sm btn-danger btn-remove-room-row ml-1" title="Xóa phòng">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            `;

            // Clear the placeholder text if empty
            if ($list.find('.room-assignment-row').length === 0) {
                $list.empty();
            }

            $list.append(newRow);
            if (availableRoom) {
                const $newSelect = $list.find('.room-id-select').last();
                $newSelect.val(availableRoom.id);
                // Cập nhật text hiển thị
                $newSelect.siblings('.room-name-display').val(
                    `${availableRoom.code} - ${availableRoom.name} - ${availableRoom.main_equiment_name}`
                );

                // Dùng mode=add để chỉ INSERT/UPDATE phòng mới, không xóa phòng cũ
                const $trMain = $btn.closest('.main-employee-row');
                const empId = $trMain.find('.room-assignments-container').first().data('employee-id');
                $.ajax({
                    url: "{{ route('pages.quota.personnel.updatePermissions') }}",
                    type: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        employee_id: empId,
                        type: 'room',
                        mode: 'add',
                        ids: [availableRoom.id + ':4:1:' + groupId + ':1']
                    },
                    success: function(res) {
                        if (res.success) {
                            Swal.mixin({
                                    toast: true,
                                    position: 'top-end',
                                    showConfirmButton: false,
                                    timer: 2000
                                })
                                .fire({
                                    icon: 'success',
                                    title: res.message
                                });
                        }
                    }
                });
            }
        });

        $(document).on('click', '.btn-remove-room-row', function() {
            if (!canEdit) return;
            const $container = $(this).closest('.room-assignments-container');
            $(this).closest('.room-assignment-row').remove();
            triggerRoomUpdate($container);
        });

        $(document).on('click', '.btn-toggle-room-active', function() {
            if (!canEdit) return;
            const $btn = $(this);
            const $row = $btn.closest('.room-assignment-row');
            const $container = $btn.closest('.room-assignments-container');
            const currentActive = $row.attr('data-active') == '1' ? 1 : 0;
            const newActive = currentActive === 1 ? 0 : 1;

            $row.attr('data-active', newActive);
            if (newActive === 0) {
                $row.addClass('inactive');
                $btn.removeClass('btn-danger').addClass('btn-success').attr('title', 'Kích hoạt');
                $btn.find('i').removeClass('fas fa-times').addClass('fas fa-undo');
            } else {
                $row.removeClass('inactive');
                $btn.removeClass('btn-success').addClass('btn-danger').attr('title', 'Vô hiệu hóa');
                $btn.find('i').removeClass('fas fa-undo').addClass('fas fa-times');
            }
            triggerRoomUpdate($container);
        });

        $(document).on('change', '.room-id-select, .room-level-select', function() {
            if (!canEdit) return;
            const $select = $(this);
            if ($select.hasClass('room-level-select')) {
                updateLevelStyle($select);
            }
            const $container = $(this).closest('.room-assignments-container');
            triggerRoomUpdate($container);
        });

        function updateLevelStyle($select) {
            const val = $select.val();
            $select.removeClass('lvl-1 lvl-2 lvl-3 lvl-4');
            $select.addClass('lvl-' + val);
        }

        function triggerRoomUpdate($container) {
            const $tr = $container.closest('.main-employee-row');
            const employeeId = $tr.find('.room-assignments-container').first().data('employee-id');
            const idsWithLevels = [];
            const selectedRoomIds = new Set();
            let duplicateFound = false;

            $tr.find('.room-assignment-row').each(function(index) {
                const $row = $(this);
                const rPriorityLevel = index + 1;
                $row.attr('data-priority', rPriorityLevel);
                $row.find('.priority-badge').text(rPriorityLevel).attr('title', 'Ưu tiên ' +
                    rPriorityLevel);

                const rId = $row.find('.room-id-select').val();
                const rLvl = $row.find('.room-level-select').val();
                const rActive = $row.attr('data-active') || '1';
                const rGroupId = $row.attr('data-group-id') || $row.data('group-id');

                if (rId && rId !== "") {
                    const uniqueKey = rId + '_' + rGroupId;
                    if (selectedRoomIds.has(uniqueKey)) {
                        duplicateFound = true;
                    } else {
                        selectedRoomIds.add(uniqueKey);
                        idsWithLevels.push(rId + ':' + rLvl + ':' + rActive + ':' + rGroupId + ':' +
                            rPriorityLevel);
                    }
                }
            });

            if (duplicateFound) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Trùng lặp phòng',
                    text: 'Mỗi phòng chỉ được phép gán một lần cho một nhân sự. Vui lòng kiểm tra lại.',
                    timer: 3000
                });
                return;
            }
            updatePermissionAjax(employeeId, 'room', idsWithLevels);
        }

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
                            timer: 2000
                        }).fire({
                            icon: 'success',
                            title: res.message
                        });
                        // Không reload lại trang để tránh nhảy vị trí cuộn
                    }
                }
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
                if (typeof renderLocalDashboard === 'function' && $('#personnel-dashboard').is(
                        ':visible')) {
                    renderLocalDashboard();
                }

                // Khởi tạo lại Sortable khi chuyển trang hoặc thay đổi số dòng
                if (typeof initSortable === 'function') {
                    initSortable();
                }
            }
        });

        // Dashboard Logic
        const apiDepId = {{ $apiDepId ?? 15 }};

        function renderLocalDashboard() {
            var table = $('#data_table_personnel').DataTable();
            var allRows = table.rows().nodes();

            let totalCount = allRows.length;
            let activeEmps = 0;
            let activeGroupsSet = new Set();

            let groupStats = {}; // { groupName: { active: 0, total: 0 } }
            let dutyStats = {};
            let roomStats = {};
            let unassignedCount = 0;

            $(allRows).each(function() {

                let $badges = $(this).find('.btn-toggle-group');
                let $activeRooms = $(this).find(
                    '.room-assignment-row[data-active="1"] .room-id-select');

                let isAssignedAnyActive = false;
                let hasAnyGroup = $badges.length > 0;



                $badges.each(function() {
                    let groupName = $(this).text().trim();
                    let isActive = $(this).hasClass('active');

                    if (!groupStats[groupName]) groupStats[groupName] = {
                        active: 0,
                        total: 0
                    };
                    groupStats[groupName].total++;
                    if (isActive) {
                        groupStats[groupName].active++;
                        isAssignedAnyActive = true;
                        activeGroupsSet.add(groupName);
                    }
                });

                if (isAssignedAnyActive) {
                    activeEmps++;
                    $activeRooms.each(function() {
                        let roomText = $(this).find('option:selected').text();
                        if (roomText && $(this).val()) {
                            let parts = roomText.split('-');
                            let cleanName = parts.length > 1 ? parts[0].trim() + ' - ' + parts[
                                1].trim() : roomText.trim();
                            if (!roomStats[cleanName]) roomStats[cleanName] = 0;
                            roomStats[cleanName]++;
                        }
                    });
                } else {
                    unassignedCount++;
                }
            });


            $('#dash-total-emp').text(totalCount);
            $('#dash-active-emp-count').text(activeEmps);
            $('#dash-active-groups-count').text(activeGroupsSet.size);


            let gContainer = $('#dash-group-bars-container');
            gContainer.empty();
            let gKeys = Object.keys(groupStats);
            gKeys.sort((a, b) => groupStats[b].active - groupStats[a].active);



            gKeys.forEach(function(key) {
                let stats = groupStats[key];
                let percentage = totalCount > 0 ? Math.round((stats.active / totalCount) * 100) : 0;

                gContainer.append(`
                    <div class="d-flex justify-content-between small font-weight-bold mb-1 mt-2">
                        <span>${key}</span>
                        <span><span class="text-success">${stats.active}</span> <span class="text-muted small">/ ${stats.total}</span></span>
                        <span class="text-muted small">(${percentage}%)</span>
                    </div>
                    <div class="progress" style="height: 6px; background-color: #e9ecef;">
                        <div class="progress-bar bg-success" style="width: ${percentage}%"></div>
                    </div>
                `);
            });

            if (unassignedCount > 0) {
                let percentage = totalCount > 0 ? Math.round((unassignedCount / totalCount) * 100) : 0;
                gContainer.append(`
                    <div class="d-flex justify-content-between small font-weight-bold mb-1 mt-2">
                        <span class="text-danger">Chưa phân công (Active)</span>
                        <span class="text-danger">${unassignedCount} (${percentage}%)</span>
                    </div>
                    <div class="progress" style="height: 6px;">
                        <div class="progress-bar bg-danger" style="width: ${percentage}%"></div>
                    </div>
                `);
            }

            let sBody = $('#dash-summary-tbody');
            sBody.empty();



            gKeys.forEach(function(key) {
                let stats = groupStats[key];
                sBody.append(`
                    <tr>
                        <td>${key}</td>
                        <td class="text-center text-success font-weight-bold">${stats.active}</td>
                        <td class="text-center text-secondary">${stats.total - stats.active}</td>
                        <td class="text-center font-weight-bold">${stats.total}</td>
                    </tr>
                `);
            });

            if (unassignedCount > 0) {
                sBody.append(`
                    <tr class="table-danger">
                        <td>Chưa phân công (Active)</td>
                        <td class="text-center text-danger font-weight-bold">${unassignedCount}</td>
                        <td class="text-center">-</td>
                        <td class="text-center font-weight-bold">${unassignedCount}</td>
                    </tr>
                `);
            }

            let rContainer = $('#dash-room-bars-container');
            rContainer.empty();
            let rKeys = Object.keys(roomStats);
            rKeys.sort((a, b) => roomStats[b] - roomStats[a]);

            if (rKeys.length === 0) {
                rContainer.append('<div class="text-center text-muted small mt-3">Chưa có dữ liệu phòng</div>');
            } else {
                rKeys.forEach(function(key) {
                    let count = roomStats[key];
                    let percentage = activeEmps > 0 ? Math.round((count / activeEmps) * 100) : 0;
                    rContainer.append(`
                        <div class="d-flex justify-content-between small font-weight-bold mb-1 mt-2">
                            <span class="text-secondary text-truncate" style="max-width: 80%;" title="${key}">${key}</span>
                            <span class="text-secondary">${count}</span>
                        </div>
                        <div class="progress" style="height: 6px;">
                            <div class="progress-bar bg-info" style="width: ${percentage}%"></div>
                        </div>
                    `);
                });
            }
        }

        function fetchShiftData() {
            $('#shift-loading').show();
            $('#shift-content').hide();

            const range = $('#dash-shift-range-select').val();
            let currentYear, currentMonth;

            if (range === 'day') {
                const dateStr = $('#dash-shift-date-input').val();
                if (!dateStr) return;
                const d = new Date(dateStr);
                currentYear = d.getFullYear();
                currentMonth = d.getMonth() + 1;
            } else if (range === 'week') {
                currentYear = $('#dash-shift-week-year').val();
                const weekNum = $('#dash-shift-week-input').val();
                const firstDayOfYear = new Date(currentYear, 0, 1);
                const days = (weekNum - 1) * 7;
                const weekDate = new Date(firstDayOfYear.setDate(firstDayOfYear.getDate() + days));
                currentMonth = weekDate.getMonth() + 1;
            } else {
                currentYear = $('#dash-shift-month-year').val();
                currentMonth = $('#dash-shift-month-input').val();
            }

            const url =
                `/assignemnt/production/shifts?month=${currentMonth}&year=${currentYear}&department=${apiDepId}`;

            $.ajax({
                url: url,
                type: 'GET',
                success: function(data) {
                    $('#shift-loading').hide();
                    $('#shift-content').show();

                    let daysToSum = [];
                    let labels = {}; // { dayKey: label }

                    if (range === 'day') {
                        const day = new Date($('#dash-shift-date-input').val()).getDate();
                        daysToSum.push('day' + day);
                        $('#shift-range-title').text('Tình trạng ca ngày ' + day);
                    } else if (range === 'week') {
                        const weekNum = $('#dash-shift-week-input').val();
                        const firstDayOfYear = new Date(currentYear, 0, 1);
                        const days = (weekNum - 1) * 7;
                        let monday = new Date(firstDayOfYear.setDate(firstDayOfYear.getDate() +
                            days));
                        const dayOfWeek = monday.getDay();
                        const diff = monday.getDate() - dayOfWeek + (dayOfWeek === 0 ? -6 : 1);
                        monday = new Date(monday.setDate(diff));

                        for (let i = 0; i < 7; i++) {
                            let d = new Date(monday);
                            d.setDate(monday.getDate() + i);
                            if (d.getMonth() + 1 == currentMonth) {
                                let dk = 'day' + d.getDate();
                                daysToSum.push(dk);
                                labels[dk] = d.getDate() + '/' + (d.getMonth() + 1);
                            }
                        }
                        $('#shift-range-title').text('Chi tiết ca Tuần ' + weekNum + ' (' +
                            currentYear + ')');
                    } else if (range === 'month') {
                        const lastDay = new Date(currentYear, currentMonth, 0).getDate();
                        for (let i = 1; i <= lastDay; i++) {
                            let dk = 'day' + i;
                            daysToSum.push(dk);
                            labels[dk] = i + '/' + currentMonth;
                        }
                        $('#shift-range-title').text('Chi tiết ca Tháng ' + currentMonth + '/' +
                            currentYear);
                    }

                    let dailyStats = {}; // { dayKey: { hc, c1, c2, c3, c4, p } }
                    daysToSum.forEach(dk => {
                        dailyStats[dk] = {
                            hc: 0,
                            c1: 0,
                            c2: 0,
                            c3: 0,
                            c4: 0,
                            p: 0
                        };
                    });


                    if (Array.isArray(data)) {
                        data.forEach(function(emp) {
                            if (emp.days) {
                                daysToSum.forEach(dayKey => {
                                    let shift = emp.days[dayKey] || 'HC';
                                    if (shift === 'HC') dailyStats[dayKey].hc++;
                                    else if (shift === 'C1') dailyStats[dayKey]
                                        .c1++;
                                    else if (shift === 'C2') dailyStats[dayKey]
                                        .c2++;
                                    else if (shift === 'C3') dailyStats[dayKey]
                                        .c3++;
                                    else if (shift === 'C4') dailyStats[dayKey]
                                        .c4++;
                                    else if (shift === 'P') dailyStats[dayKey].p++;

                                });

                            }
                        });
                    }

                    if (range === 'day') {
                        const dStats = dailyStats[daysToSum[0]] || {
                            hc: 0,
                            c1: 0,
                            c2: 0,
                            c3: 0,
                            c4: 0,
                            p: 0
                        };
                        $('#dash-shift-hc').text(dStats.hc);
                        $('#dash-shift-c1').text(dStats.c1);
                        $('#dash-shift-c2').text(dStats.c2);
                        $('#dash-shift-c3').text(dStats.c3);
                        $('#dash-shift-c4').text(dStats.c4);
                        $('#dash-shift-p').text(dStats.p);

                    } else {
                        const tbody = $('#shift-table-body');
                        tbody.empty();
                        daysToSum.forEach(dk => {
                            const s = dailyStats[dk];
                            tbody.append(`
                                <tr>
                                    <td class="font-weight-bold">${labels[dk]}</td>
                                    <td class="text-success">${s.hc || '-'}</td>
                                    <td class="text-warning">${s.c1 || '-'}</td>
                                    <td class="text-primary">${s.c2 || '-'}</td>
                                    <td class="text-secondary">${s.c3 || '-'}</td>
                                    <td class="text-info">${s.c4 || '-'}</td>
                                    <td class="text-danger">${s.p || '-'}</td>

                                </tr>
                            `);
                        });
                    }
                },
                error: function() {
                    $('#shift-loading').html(
                        '<span class="text-danger small font-weight-bold"><i class="fas fa-exclamation-triangle"></i> Lỗi kết nối máy chủ.</span>'
                    );
                }
            });
        }


        $('#btn-toggle-dashboard').click(function(e) {
            e.preventDefault();
            const dash = $('#personnel-dashboard');
            if (dash.is(':visible')) {
                dash.slideUp();
                $('#text-toggle-dashboard').text('Hiển thị Dashboard');
                $(this).removeClass('btn-info').addClass('btn-outline-info');
            } else {
                dash.slideDown();
                $('#text-toggle-dashboard').text('Ẩn Dashboard');
                $(this).removeClass('btn-outline-info').addClass('btn-info');
                renderLocalDashboard();
                fetchShiftData();
            }
        });

        // Range Selection UI Handling
        $('#dash-shift-range-select').change(function() {
            const range = $(this).val();
            $('.shift-input-group').hide();
            $('#input-group-' + range).show();

            if (range === 'day') {
                $('#shift-card-view').show();
                $('#shift-table-view').hide();
            } else {
                $('#shift-card-view').hide();
                $('#shift-table-view').show();
            }
            fetchShiftData();
        });

        $('#btn-refresh-shifts').click(function(e) {
            e.preventDefault();
            fetchShiftData();
        });
        $('#dash-shift-date-input, #dash-shift-week-input, #dash-shift-week-year, #dash-shift-month-input, #dash-shift-month-year')
            .on('change input', function() {
                fetchShiftData();
            });




        $(document).on('click', '.btn-toggle-group', function() {
            if ($('#personnel-dashboard').is(':visible')) setTimeout(renderLocalDashboard, 100);
        });

        let $currentEditingRow = null;

        $(document).on('click', '.btn-edit-room-modal, .room-name-display', function() {
            if (!canEdit) return;
            $currentEditingRow = $(this).closest('.room-assignment-row');
            let currentRoomId = $currentEditingRow.find('.room-id-select').val();

            $('#modalRoomSelect').val(currentRoomId).trigger('change');
            $('#editRoomModal').modal('show');
        });

        $('#btn-save-room-modal').on('click', function() {
            if (!$currentEditingRow) return;

            let selectedRoomId = $('#modalRoomSelect').val();
            let selectedRoomText = $('#modalRoomSelect option:selected').text();

            if (!selectedRoomId) {
                Swal.fire('Lỗi', 'Vui lòng chọn một phòng', 'error');
                return;
            }

            $currentEditingRow.find('.room-id-select').val(selectedRoomId);
            $currentEditingRow.find('.room-name-display').val(selectedRoomText).attr('title',
                selectedRoomText);

            $('#editRoomModal').modal('hide');

            // Trigger update
            const $container = $currentEditingRow.closest('.room-assignments-container');
            triggerRoomUpdate($container);
        });

        $('#editRoomModal').on('shown.bs.modal', function() {
            $('#modalRoomSelect').select2({
                dropdownParent: $('#editRoomModal')
            });
        });

        // Initialize Sortable on all room lists
        function initSortable() {
            if (typeof Sortable !== 'undefined' && canEdit) {
                $('.room-list').each(function() {
                    if (this.sortableInstance) return; // Prevent double initialization
                    this.sortableInstance = new Sortable(this, {
                        handle: '.cursor-grab',
                        animation: 150,
                        onEnd: function(evt) {
                            const $container = $(evt.item).closest(
                                '.room-assignments-container');
                            triggerRoomUpdate($container);
                        }
                    });
                });
            }
        }

        initSortable();

        $(document).on('change', '.toggle-maternity-leave', function() {
            if (!canEdit) return;
            const $checkbox = $(this);
            const employeeId = $checkbox.data('id');
            const status = $checkbox.is(':checked') ? 1 : 0;

            $.ajax({
                url: "{{ route('pages.quota.personnel.toggleMaternityLeave') }}",
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    id: employeeId,
                    status: status
                },
                success: function(res) {
                    if (res.success) {
                        Swal.mixin({
                            toast: true,
                            position: 'top-end',
                            showConfirmButton: false,
                            timer: 2000
                        }).fire({
                            icon: 'success',
                            title: res.message
                        });
                    }
                },
                error: function() {
                    $checkbox.prop('checked', !status);
                    Swal.fire('Lỗi', 'Không thể cập nhật trạng thái!', 'error');
                }
            });
        });

    });
</script>
