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
        border-bottom: 1.5px solid #ced4da;
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
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
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
                                    <option value="{{ $r->id }}" {{ $filterRoomId == $r->id ? 'selected' : '' }}>
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
                        <th style="width: 150px;">Phân Xưởng Công Tác Tạm Thời</th>
                        <th style="width: 180px;">Tổ Được Phép Công Tác</th>
                        <th style="width: 500px;">Phòng Được Phép Công Tác</th>
                        <th style="width: 150px;">Trạng Thái Nhân Sự</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($datas as $data)
                        <tr>
                            <td>{{ $loop->iteration }} </td>
                            <td>{{ $data->code }}</td>
                            <td>{{ $data->name }}</td>
                            <td class="text-center">
                                <span class="badge badge-info shadow-sm">{{ $data->main_production }}</span>
                            </td>
                            <td>
                                <div class="temp-productions-list" data-employee-id="{{ $data->id }}">
                                    @php
                                        $tempProds = $data->temp_productions ? explode('|', $data->temp_productions) : [];
                                        $tempMap = [];
                                        foreach($tempProds as $tp) {
                                            $parts = explode(':', $tp);
                                            if(count($parts) >= 2) {
                                                $tempMap[$parts[0]] = [
                                                    'active' => $parts[1],
                                                    'user' => $parts[2] ?? 'N/A',
                                                    'date' => $parts[3] ?? ''
                                                ];
                                            }
                                        }
                                        $allProds = ['PXV1', 'PXV2', 'PXVH', 'PXTN', 'PXDN'];
                                    @endphp
                                    @foreach ($allProds as $prod)
                                        @if($prod != $data->main_production)
                                            @php 
                                                $info = $tempMap[$prod] ?? null;
                                                $isActive = $info && $info['active'] == 1;
                                            @endphp
                                            <div class="assignment-item">
                                                <span class="badge badge-toggle {{ $isActive ? 'badge-info active' : 'inactive' }} btn-toggle-prod"
                                                      data-prod="{{ $prod }}"
                                                      data-active="{{ $isActive ? 1 : 0 }}"
                                                      title="{{ $isActive ? 'Nhấn để vô hiệu hóa' : 'Nhấn để kích hoạt' }}">
                                                    {{ $prod }}
                                                </span>
                                                @if($info)
                                                    <span class="assignment-meta">
                                                        <i class="fas fa-user-edit"></i> {{ $info['user'] }} <br>
                                                        <i class="fas fa-calendar-check"></i> {{ $info['date'] }}
                                                    </span>
                                                @endif
                                            </div>
                                        @endif
                                    @endforeach
                                </div>
                            </td>
                            <td>
                                <div class="groups-list-container" data-employee-id="{{ $data->id }}">
                                    <div class="groups-badges mb-2">
                                        @php
                                            $groupPermissions = $data->allowed_groups ? explode('|', $data->allowed_groups) : [];
                                        @endphp
                                        @foreach($groupPermissions as $gp)
                                            @php 
                                                $parts = explode(':', $gp);
                                                $gId = $parts[0];
                                                $isActive = ($parts[1] ?? 1) == 1;
                                                $gUser = $parts[2] ?? 'N/A';
                                                $gDate = $parts[3] ?? '';
                                                $groupName = $groups->where('id', $gId)->first()->name ?? "N/A";
                                            @endphp
                                            <div class="assignment-item">
                                                <span class="badge badge-toggle {{ $isActive ? 'badge-primary active' : 'inactive' }} btn-toggle-group"
                                                      data-group-id="{{ $gId }}"
                                                      data-active="{{ $isActive ? 1 : 0 }}"
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
                                    <select class="form-control form-control-sm select-add-group" style="width: 100%;">
                                        <option value="">+ Thêm Tổ...</option>
                                        @foreach ($groups as $g)
                                            @php 
                                                $isAlreadyInList = false;
                                                foreach($groupPermissions as $gp) {
                                                    if(explode(':', $gp)[0] == $g->id) { $isAlreadyInList = true; break; }
                                                }
                                            @endphp
                                            @if(!$isAlreadyInList)
                                                <option value="{{ $g->id }}">{{ $g->name }}</option>
                                            @endif
                                        @endforeach
                                    </select>
                                </div>
                            </td>
                            <td>
                                <div class="room-assignments-container" data-employee-id="{{ $data->id }}">
                                    <div class="room-list">
                                        @php
                                            $groupPermissions = $data->allowed_groups ? explode('|', $data->allowed_groups) : [];
                                            $assignments = $data->allowed_rooms_with_levels
                                                ? explode('|', $data->allowed_rooms_with_levels)
                                                : [];
                                        @endphp
                                        @foreach ($assignments as $as)
                                            @php
                                                $parts = explode(':', $as);
                                                $rId = $parts[0];
                                                $rLvl = $parts[1] ?? 1;
                                                $rActive = $parts[2] ?? 1;
                                                $rUser = $parts[3] ?? 'N/A';
                                                $rDate = $parts[4] ?? '';

                                                $selectedGroupIds = [];
                                                foreach ($groupPermissions as $gp) {
                                                    $gParts = explode(':', $gp);
                                                    if (($gParts[1] ?? 1) == 1) {
                                                        $selectedGroupIds[] = $gParts[0];
                                                    }
                                                }

                                                $selectedGroupCodes = $groups
                                                    ->whereIn('id', $selectedGroupIds)
                                                    ->pluck('code')
                                                    ->toArray();
                                            @endphp
                                            <div class="room-assignment-row d-flex align-items-center mb-1 {{ $rActive == 0 ? 'inactive' : '' }}"
                                                data-active="{{ $rActive }}">
                                                <select class="form-control form-control-sm room-id-select mr-1"
                                                    style="width: 350px;">
                                                    <option value="">-- Phòng --</option>
                                                    @foreach ($rooms->whereIn('group_code', $selectedGroupCodes) as $r)
                                                        <option value="{{ $r->id }}"
                                                            {{ $r->id == $rId ? 'selected' : '' }}>
                                                            {{ $r->code }} - {{ $r->name }} -
                                                            {{ $r->main_equiment_name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                                <select
                                                    class="form-control form-control-sm room-level-select mr-1 lvl-{{ $rLvl }}"
                                                    style="width: 70px;">
                                                    <option value="1" {{ $rLvl == 1 ? 'selected' : '' }}>1
                                                    </option>
                                                    <option value="2" {{ $rLvl == 2 ? 'selected' : '' }}>2
                                                    </option>
                                                    <option value="3" {{ $rLvl == 3 ? 'selected' : '' }}>3
                                                    </option>
                                                    <option value="4" {{ $rLvl == 4 ? 'selected' : '' }}>4
                                                    </option>
                                                </select>

                                                @php
                                                    $hYear = $workHours[$data->id][$rId]['year'] ?? 0;
                                                    $hTotal = $workHours[$data->id][$rId]['total'] ?? 0;
                                                @endphp
                                                <span class="work-hours-badge" title="Thời gian đã làm việc tại phòng này">
                                                    <span class="yearly" title="Năm hiện tại"><i
                                                            class="fas fa-calendar-alt"></i>{{ $hYear }}h</span>
                                                    |
                                                    <span class="total" title="Tổng cộng"><i
                                                            class="fas fa-history"></i>{{ $hTotal }}h</span>
                                                </span>

                                                <span class="assignment-meta mx-2" style="min-width: 100px;">
                                                    <i class="fas fa-user-edit"></i> {{ $rUser }} <br>
                                                    <i class="fas fa-calendar-check"></i> {{ $rDate }}
                                                </span>

                                                <button
                                                    class="btn btn-sm btn-{{ $rActive == 1 ? 'danger' : 'success' }} btn-toggle-room-active ml-1"
                                                    title="{{ $rActive == 1 ? 'Vô hiệu hóa' : 'Kích hoạt' }}">
                                                    <i class="fas fa-{{ $rActive == 1 ? 'times' : 'undo' }}"></i>
                                                </button>
                                            </div>
                                        @endforeach
                                    </div>
                                    <button class="btn btn-sm btn-outline-primary btn-add-room-row mt-1"><i
                                            class="fas fa-plus"></i> Thêm phòng</button>
                                </div>
                            </td>
                            <td class="text-center align-middle">
                                @if ($data->active)
                                    <span class="badge badge-success mb-1">Đang làm việc</span>
                                @else
                                    <span class="badge badge-danger mb-1">Nghỉ việc</span>
                                @endif
                                <br>
                                <a href="{{ route('pages.assignment.personnel.deActive', $data->id) }}"
                                    class="btn btn-{{ $data->active ? 'danger' : 'success' }} btn-sm"
                                    title="{{ $data->active ? 'Vô hiệu hóa' : 'Kích hoạt' }}">
                                    <i class="fas fa-{{ $data->active ? 'user-slash' : 'user-check' }}"></i>
                                    {{ $data->active ? 'Vô hiệu hóa' : 'Kích hoạt' }}
                                </a>
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

@if (session('success'))
    <script>
        Swal.fire({
            title: 'Thành công!',
            text: '{{ session('success') }}',
            icon: 'success',
            timer: 1500,
            showConfirmButton: false
        });
    </script>
@endif

@if (session('error'))
    <script>
        Swal.fire({
            title: 'Lỗi!',
            text: '{{ session('error') }}',
            icon: 'error'
        });
    </script>
@endif

<script>
    $(document).ready(function() {
        document.body.style.overflowY = "auto";

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
            const $select = $(this);
            const employeeId = $select.closest('.groups-list-container').data('employee-id');
            const groupId = $select.val();
            if (!groupId) return;

            // Collect all current groups including the new one
            const groupData = [];
            $select.closest('.groups-list-container').find('.btn-toggle-group').each(function() {
                groupData.push($(this).data('group-id') + ':' + $(this).data('active'));
            });
            groupData.push(groupId + ':1');

            updatePermissionAjax(employeeId, 'group', groupData);
            
            // Reload page to reflect changes (or we could append badge manually)
            location.reload();
        });

        // Toggle Production Badge
        $(document).on('click', '.btn-toggle-prod', function() {
            const $badge = $(this);
            const employeeId = $badge.closest('.temp-productions-list').data('employee-id');
            
            // Collect all prod statuses
            const prodData = [];
            $badge.closest('.temp-productions-list').find('.btn-toggle-prod').each(function() {
                let active = $(this).data('active');
                if (this === $badge[0]) active = (active == 1 ? 0 : 1);
                if (active == 1 || $(this).hasClass('active') || this === $badge[0]) {
                    // Only send those that are in DB (active or inactive)
                    // If it was never active, we might not need to send it, 
                    // but for Prods we have a fixed list.
                    prodData.push($(this).data('prod') + (active == 1 ? '' : ':inactive'));
                }
            });

            // Need to update the AJAX handler or payload for updateProductions
            // Let's refine the payload for productions
            const finalProds = [];
            $badge.closest('.temp-productions-list').find('.btn-toggle-prod').each(function() {
                let active = $(this).data('active');
                if (this === $badge[0]) active = (active == 1 ? 0 : 1);
                if (active == 1) finalProds.push($(this).data('prod'));
            });

            // Actually, the current updateProductions logic deletes/updates based on the list.
            // If I want to support "inactive" in DB for prods, I need to send the full list with status.
            // But let's stay consistent with the "ids" pattern.
            
            $.ajax({
                url: "{{ route('pages.assignment.personnel.updateProductions') }}",
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    employee_id: employeeId,
                    productions: finalProds // The controller currently sets active=0 for those NOT in this list
                },
                success: function(res) {
                    if (res.success) {
                        $badge.data('active', $badge.data('active') == 1 ? 0 : 1);
                        $badge.toggleClass('badge-info active').toggleClass('inactive');
                        Swal.mixin({ toast: true, position: 'top-end', showConfirmButton: false, timer: 2000 }).fire({ icon: 'success', title: res.message });
                    }
                }
            });
        });

        // Toggle Group Badge
        $(document).on('click', '.btn-toggle-group', function() {
            const $badge = $(this);
            const employeeId = $badge.closest('.groups-list-container').data('employee-id');
            
            const groupData = [];
            $badge.closest('.groups-list-container').find('.btn-toggle-group').each(function() {
                let active = $(this).data('active');
                if (this === $badge[0]) active = (active == 1 ? 0 : 1);
                groupData.push($(this).data('group-id') + ':' + active);
            });

            updatePermissionAjax(employeeId, 'group', groupData);
            
            $badge.data('active', $badge.data('active') == 1 ? 0 : 1);
            $badge.toggleClass('badge-primary active').toggleClass('inactive');
        });

        // Handle Room Assignment Actions
        $(document).on('click', '.btn-add-room-row', function() {
            const $tr = $(this).closest('tr');
            const $list = $tr.find('.room-list');
            const selectedGroupIds = $tr.find('.select-groups').val() || [];

            if (selectedGroupIds.length === 0) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Chú ý',
                    text: 'Vui lòng chọn ít nhất một Tổ ở cột "Tổ Cho Phép" trước khi thêm phòng.',
                    timer: 3000
                });
                return;
            }

            const selectedCodes = groupsData
                .filter(g => selectedGroupIds.includes(g.id.toString()))
                .map(g => g.code);

            const filteredRooms = roomsData.filter(r => selectedCodes.includes(r.group_code));

            // Find rooms already selected in this row
            const alreadySelectedIds = [];
            $tr.find('.room-id-select').each(function() {
                const val = $(this).val();
                if (val) alreadySelectedIds.push(val.toString());
            });

            // Find first available room not yet selected
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

            let roomOptions = '<option value="">-- Phòng --</option>';
            filteredRooms.forEach(r => {
                const isSelected = availableRoom && r.id === availableRoom.id;
                roomOptions +=
                    `<option value="${r.id}" ${isSelected ? 'selected' : ''}>${r.code} - ${r.name} - ${r.main_equiment_name}</option>`;
            });

            const newRow = `
                <div class="room-assignment-row d-flex align-items-center mb-1" data-active="1">
                    <select class="form-control form-control-sm room-id-select mr-1" style="width: 350px;">
                        ${roomOptions}
                    </select>
                    <select class="form-control form-control-sm room-level-select mr-1 lvl-4" style="width: 70px;">
                        <option value="1">1</option>
                        <option value="2">2</option>
                        <option value="3">3</option>
                        <option value="4" selected>4</option>
                    </select>
                    <button class="btn btn-sm btn-danger btn-toggle-room-active ml-1" title="Vô hiệu hóa">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            `;
            $list.append(newRow);

            // Trigger update if we auto-selected a room
            if (availableRoom) {
                triggerRoomUpdate($tr.find('.room-assignments-container'));
            }
        });

        $(document).on('click', '.btn-toggle-room-active', function() {
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
            const employeeId = $container.data('employee-id');
            const idsWithLevels = [];
            const selectedRoomIds = new Set();
            let duplicateFound = false;

            $container.find('.room-assignment-row').each(function() {
                const $row = $(this);
                const rId = $row.find('.room-id-select').val();
                const rLvl = $row.find('.room-level-select').val();
                const rActive = $row.attr('data-active') || '1';

                if (rId && rId !== "") {
                    if (selectedRoomIds.has(rId)) {
                        duplicateFound = true;
                    } else {
                        selectedRoomIds.add(rId);
                        idsWithLevels.push(rId + ':' + rLvl + ':' + rActive);
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
                return; // BLOCK SAVING
            }

            updatePermissionAjax(employeeId, 'room', idsWithLevels);
        }

        function updatePermissionAjax(employeeId, type, ids) {
            $.ajax({
                url: "{{ route('pages.assignment.personnel.updatePermissions') }}",
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
                    } else {
                        Swal.fire('Lỗi', res.message, 'error');
                    }
                },
                error: function(xhr) {
                    Swal.fire('Lỗi server', 'Không thể cập nhật phân quyền', 'error');
                }
            });
        }

        $('.btn-edit').click(function() {
            const button = $(this);
            const modal = $('#update_modal');

            modal.find('input[name="id"]').val(button.data('id'));
            modal.find('input[name="code"]').val(button.data('code'));
            modal.find('input[name="name"]').val(button.data('name'));
            modal.find('input[name="deparment_code"]').val(button.data('deparment_code'));
        });

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
            }
        });
    });
</script>
