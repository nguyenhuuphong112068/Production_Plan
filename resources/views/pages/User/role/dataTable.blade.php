<link href="{{ asset('css/bootstrap.min.css') }}" rel="stylesheet">

<style>
    .role-page {
        --role-primary: #003A4F;
        --role-primary-light: #0B5A75;
        --role-accent: #CDC717;
        --role-soft: #EEF5F7;
        --role-line: #E2EBEF;
        --role-lighter: #9FC3CF;
    }

    /* ===== Thanh công cụ ===== */
    .perm-toolbar-card {
        border: none;
        border-radius: 12px;
        box-shadow: 0 2px 6px rgba(0, 0, 0, .06);
        /* topNAV cố định cao ~60px che phần đầu content-wrapper */
        margin-top: 66px;
        margin-bottom: 20px;
    }

    .perm-toolbar-card .card-body {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 12px;
        padding: 16px 20px;
    }

    .perm-toolbar-card .btn {
        border-radius: 8px;
        font-weight: 600;
        font-size: 13.5px;
    }

    .role-search {
        max-width: 300px;
        border-radius: 8px;
    }

    .btn-outline-role {
        color: var(--role-primary);
        border: 1px solid var(--role-primary);
        background: #fff;
    }

    .btn-outline-role:hover,
    .perm-role-filter.is-filtered>.btn {
        color: #fff;
        background: var(--role-primary);
    }

    /* Menu chọn nhóm quyền hiển thị */
    .perm-role-filter {
        position: relative;
    }

    .perm-role-menu {
        position: absolute;
        top: calc(100% + 6px);
        left: 0;
        z-index: 20;
        min-width: 300px;
        max-height: 380px;
        overflow-y: auto;
        padding: 8px 0;
        background: #fff;
        border-radius: 8px;
        box-shadow: 0 6px 20px rgba(0, 0, 0, .14);
    }

    .perm-role-menu-actions {
        display: flex;
        justify-content: space-between;
        padding: 2px 14px 8px;
        margin-bottom: 4px;
        border-bottom: 1px solid var(--role-line);
        font-size: 13px;
    }

    .perm-role-menu-actions a {
        color: var(--role-primary-light);
    }

    .perm-role-option {
        display: flex;
        align-items: center;
        gap: 8px;
        margin: 0;
        padding: 6px 14px;
        font-weight: 400;
        font-size: 14px;
        cursor: pointer;
    }

    .perm-role-option:hover {
        background: var(--role-soft);
    }

    .perm-role-option .role-user-count {
        margin-left: auto;
        font-size: 12px;
        color: #94A3B8;
    }

    .perm-role-option input,
    .perm-only-granted input {
        accent-color: var(--role-primary);
    }

    .perm-only-granted {
        display: flex;
        align-items: center;
        gap: 6px;
        margin: 0;
        font-weight: 600;
        font-size: 13.5px;
        color: var(--role-primary);
        cursor: pointer;
    }

    .perm-only-granted.is-disabled {
        color: #A0AEC0;
        cursor: default;
    }

    .perm-hint {
        font-size: 12.5px;
        color: #7B8A97;
    }

    .perm-readonly-note {
        width: 100%;
        margin: 0;
        padding: 8px 12px;
        border-radius: 8px;
        background: #FFF8E1;
        color: #8A6D00;
        font-size: 13.5px;
    }

    /* ===== Mỗi nhóm chức năng = 1 card ===== */
    .perm-model-card {
        border: none;
        border-radius: 14px;
        box-shadow: 0 4px 16px rgba(0, 58, 79, .10);
        overflow: clip;
        margin-bottom: 22px;
    }

    .perm-model-header {
        display: flex;
        align-items: center;
        gap: 11px;
        padding: 9px 18px;
        border: none;
        border-bottom: 3px solid var(--role-accent);
        background: linear-gradient(135deg, var(--role-primary), var(--role-primary-light));
        color: #fff;
        cursor: pointer;
        user-select: none;
        transition: filter .15s ease;
    }

    .perm-model-header:hover {
        filter: brightness(1.12);
    }

    .perm-model-icon {
        width: 28px;
        height: 28px;
        flex: 0 0 28px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 13px;
        border-radius: 50%;
        background: rgba(255, 255, 255, .18);
    }

    .perm-model-name {
        font-size: 13.5px;
        font-weight: 700;
        letter-spacing: .8px;
        text-transform: uppercase;
    }

    .perm-model-count {
        font-size: 12px;
        padding: 1px 9px;
        border-radius: 10px;
        background: rgba(255, 255, 255, .18);
    }

    .perm-model-toggle {
        margin-left: auto;
        font-size: 13px;
        transition: transform .2s ease;
    }

    .perm-model-card.is-collapsed .perm-model-toggle {
        transform: rotate(-90deg);
    }

    .perm-model-card>.card-body {
        padding: 0;
    }

    /* ===== Bảng quyền trong card: ma trận gọn, tên nhóm quyền xoay dọc ===== */
    /* Card hiện đủ mọi quyền (không cuộn dọc), chỉ cuộn ngang khi nhiều cột nhóm quyền */
    .perm-table-scroll {
        overflow-x: auto;
    }

    .perm-table {
        width: max-content;
        min-width: 100%;
        table-layout: fixed;
        border-collapse: separate;
        border-spacing: 0;
        font-size: 13.5px;
    }

    .perm-table col.perm-col-name {
        width: 320px;
    }

    .perm-table col.perm-col-role {
        width: 44px;
    }

    .perm-table th,
    .perm-table td {
        padding: 6px 8px;
        border-right: 1px solid var(--role-line);
        border-bottom: 1px solid var(--role-line);
    }

    .perm-table .is-role-hidden {
        display: none;
    }

    /* Tiêu đề: JS (syncStickyHeads) dịch xuống ngay dưới topNAV khi cuộn trang.
       Không dùng position: sticky vì vùng cuộn ngang chặn dính theo trang */
    .perm-table thead th {
        position: relative;
        z-index: 3;
        background: #fff;
        color: var(--role-primary);
        font-weight: 700;
        box-shadow: inset 0 -2px 0 var(--role-accent);
    }

    .perm-table thead th.perm-name-head {
        vertical-align: bottom;
        font-size: 13.5px;
    }

    .perm-table thead th[data-role-col] {
        padding: 8px 2px;
        text-align: center;
        vertical-align: bottom;
        font-size: 12px;
    }

    .perm-table thead th[data-role-col]>span {
        display: inline-block;
        writing-mode: vertical-rl;
        transform: rotate(180deg);
        max-height: 180px;
        line-height: 1.15;
        text-align: left;
    }

    .perm-table thead th.is-admin {
        background: #FBFAE6;
    }

    /* Ghim cột "Quyền" khi cuộn ngang */
    .perm-table thead th.perm-name-head,
    .perm-table td:first-child {
        position: sticky;
        left: 0;
        z-index: 2;
    }

    .perm-table td:first-child {
        background: #fff;
    }

    .perm-table thead th.perm-name-head {
        z-index: 4;
    }

    .perm-table tbody tr.permission-row:hover td {
        background: var(--role-soft);
    }

    /* Cột đang rê chuột: tô nhạt cả cột để dò đúng nhóm quyền */
    .perm-table td.is-col-hover,
    .perm-table th.is-col-hover {
        background: var(--role-soft);
    }

    /* Hàng số quyền đã cấp của từng nhóm quyền trong card */
    .perm-count-row td {
        background: var(--role-soft) !important;
        color: var(--role-primary);
        font-weight: 700;
        font-size: 12.5px;
        text-align: center;
        border-bottom: 2px solid var(--role-lighter);
    }

    .perm-count-row td:first-child {
        text-align: left;
    }

    .perm-name-cell {
        position: relative;
        padding-right: 36px !important;
    }

    .perm-name-cell .permission-name {
        color: #2D3748;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    /* Mã quyền + diễn giải: chỉ để ô tìm kiếm đọc được, xem bằng tooltip */
    .perm-name-cell .permission-code,
    .perm-name-cell .permission-desc {
        display: none;
    }

    /* Nút tick / bỏ tick một quyền cho mọi nhóm quyền đang hiện, hiện khi rê chuột vào dòng */
    .perm-row-all {
        position: absolute;
        top: 50%;
        right: 6px;
        width: 24px;
        height: 22px;
        padding: 0;
        transform: translateY(-50%);
        border: 1px solid var(--role-lighter);
        border-radius: 6px;
        background: #fff;
        color: var(--role-primary);
        font-size: 11px;
        opacity: 0;
        cursor: pointer;
        transition: opacity .2s ease, background .2s ease, color .2s ease;
    }

    .permission-row:hover .perm-row-all,
    .perm-row-all:focus {
        opacity: 1;
    }

    .perm-row-all:hover {
        background: var(--role-primary);
        color: #fff;
    }

    /* Đang kéo chọn nhiều ô: không bôi đen chữ, ô dưới chuột sáng lên */
    body.perm-dragging,
    body.perm-dragging * {
        user-select: none;
        cursor: cell !important;
    }

    body.perm-dragging td.perm-cell:hover {
        background: var(--role-lighter) !important;
    }

    .perm-cell {
        padding: 4px 2px !important;
        text-align: center;
        vertical-align: middle;
    }

    .step-checkbox {
        width: 16px;
        height: 16px;
        margin: 0;
        vertical-align: middle;
        cursor: pointer;
        accent-color: var(--role-primary);
        transition: transform .1s ease;
    }

    .step-checkbox:active {
        transform: scale(.9);
    }

    .step-checkbox[aria-readonly="true"] {
        cursor: default;
    }

    .step-checkbox[aria-readonly="true"]:active {
        transform: none;
    }

    .perm-empty {
        padding: 40px 20px;
        text-align: center;
        color: #94A3B8;
    }

    /* ===== Modal quản lý nhóm quyền ===== */
    #manageRoleModal .modal-title {
        color: #003A4F;
        font-weight: 700;
    }

    #manageRoleModal .role-list-scroll {
        max-height: 46vh;
        overflow-y: auto;
    }

    #manageRoleModal .role-list-table th {
        position: sticky;
        top: 0;
        z-index: 1;
        background-color: #EEF5F7;
        color: #003A4F;
        font-weight: 700;
    }

    #manageRoleModal .role-list-table tr.role-inactive td {
        background: #F8F9FA;
        color: #9AA7B4;
    }

    #manageRoleModal .role-form-box {
        background-color: #F7FAFB;
        border: 1px solid #E6EEF1;
        border-radius: 8px;
        padding: 16px;
        margin-top: 14px;
    }

    #manageRoleModal .role-form-box .form-title {
        font-weight: 700;
        color: #003A4F;
        margin-bottom: 10px;
    }
</style>

<div class="content-wrapper role-page">
    <section class="content">

        {{-- Thanh công cụ --}}
        <div class="card perm-toolbar-card">
            <div class="card-body">
                @if ($canManage)
                    <button type="button" class="btn btn-success" data-toggle="modal" data-target="#manageRoleModal">
                        <i class="fas fa-users-cog mr-1"></i> Quản Lý Nhóm Quyền
                    </button>
                @endif

                <input type="text" class="form-control role-search" id="rolePermissionSearch"
                    placeholder="Tìm quyền theo tên hoặc mã...">

                <div class="perm-role-filter">
                    <button type="button" class="btn btn-outline-role" id="btnRoleFilter">
                        <i class="fas fa-users mr-1"></i> <span id="roleFilterLabel">Tất cả nhóm quyền</span>
                        <i class="fas fa-caret-down ml-1"></i>
                    </button>
                    <div class="perm-role-menu" id="roleFilterMenu" hidden>
                        <div class="perm-role-menu-actions">
                            <a href="#" id="roleFilterAll">Chọn tất cả</a>
                            <a href="#" id="roleFilterNone">Bỏ chọn</a>
                        </div>
                        @foreach ($roles as $role)
                            <label class="perm-role-option">
                                <input type="checkbox" class="role-filter-item" value="{{ $role->id }}">
                                <span class="role-filter-name">{{ $role->name }}</span>
                                <span class="role-user-count" title="Số người dùng thuộc nhóm">{{ $role->user_count }}
                                    <i class="fas fa-user"></i></span>
                            </label>
                        @endforeach
                    </div>
                </div>

                <label class="perm-only-granted"
                    title="Chỉ hiện quyền mà ít nhất một nhóm quyền đang chọn đã được cấp">
                    <input type="checkbox" id="onlyGranted" disabled> Chỉ quyền đã cấp
                </label>

                <button type="button" class="btn btn-outline-role ml-auto" id="btnToggleAllCards">
                    <i class="fas fa-compress-alt mr-1"></i> Thu gọn tất cả
                </button>

                @if ($canManage)
                    <div class="perm-hint w-100">
                        <i class="fas fa-mouse-pointer mr-1"></i> Bấm giữ rồi kéo để tick / bỏ tick nhiều ô cùng lúc.
                        Nút <i class="fas fa-check-double"></i> cuối tên quyền: cấp quyền đó cho mọi nhóm quyền đang hiện.
                        Rê chuột vào tên quyền để xem mã và diễn giải.
                    </div>
                @else
                    <p class="perm-readonly-note">
                        <i class="fas fa-lock mr-1"></i> Chế độ chỉ xem: bạn không có quyền phân quyền cho nhóm.
                    </p>
                @endif
            </div>
        </div>

        @if (empty($tree))
            <div class="card perm-model-card">
                <div class="card-body p-3">
                    <div class="alert alert-warning mb-0">Chưa có quyền nào trong hệ thống.</div>
                </div>
            </div>
        @endif

        {{-- Mỗi nhóm chức năng một card riêng --}}
        @foreach ($tree as $groupKey => $group)
            <div class="card perm-model-card" data-model="{{ $groupKey }}">
                <div class="card-header perm-model-header">
                    <span class="perm-model-icon"><i class="fas {{ $group['icon'] }}"></i></span>
                    <span class="perm-model-name">{{ $group['label'] }}</span>
                    <span class="perm-model-count" title="Số quyền trong nhóm chức năng">{{ count($group['permissions']) }}</span>
                    <i class="fas fa-chevron-down perm-model-toggle"></i>
                </div>

                <div class="card-body">
                    <div class="perm-table-scroll">
                        <table class="perm-table">
                            <colgroup>
                                <col class="perm-col-name">
                                @foreach ($roles as $role)
                                    <col class="perm-col-role" data-role-col="{{ $role->id }}">
                                @endforeach
                            </colgroup>
                            <thead>
                                <tr>
                                    <th class="perm-name-head">Quyền</th>
                                    @foreach ($roles as $role)
                                        <th class="{{ $role->id == 1 ? 'is-admin' : '' }}" data-role-col="{{ $role->id }}"
                                            title="{{ $role->name }}{{ $role->description ? ' - ' . $role->description : '' }} ({{ $role->user_count }} người dùng){{ $role->id == 1 ? "\nNhóm Admin luôn giữ toàn quyền" : '' }}">
                                            <span>@if ($role->id == 1)<i class="fas fa-lock mb-1" style="font-size: 10px"></i> @endif{{ $role->name }}</span>
                                        </th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                <tr class="perm-count-row">
                                    <td title="Số quyền đã tick của từng nhóm quyền trong nhóm chức năng này">Số quyền đã cấp</td>
                                    @foreach ($roles as $role)
                                        <td data-role-col="{{ $role->id }}" data-count-role="{{ $role->id }}"
                                            title="{{ $role->name }}"></td>
                                    @endforeach
                                </tr>

                                @foreach ($group['permissions'] as $permission)
                                    <tr class="permission-row">
                                        <td class="perm-name-cell"
                                            title="{{ $permission->name }}{{ !empty($permission->description) && $permission->description !== $permission->display_name ? "\n" . $permission->description : '' }}">
                                            <div class="permission-name">{{ $permission->display_name ?: $permission->name }}</div>
                                            <div class="permission-code">{{ $permission->name }}</div>
                                            <div class="permission-desc">{{ $permission->description }}</div>
                                            @if ($canManage)
                                                <button type="button" class="perm-row-all"
                                                    title="Tick quyền này cho tất cả nhóm quyền đang hiện (đã tick hết thì bỏ tick hết)">
                                                    <i class="fas fa-check-double"></i>
                                                </button>
                                            @endif
                                        </td>

                                        @foreach ($roles as $role)
                                            <td class="perm-cell" data-role-col="{{ $role->id }}">
                                                <input class="step-checkbox" type="checkbox"
                                                    data-role="{{ $role->id }}"
                                                    data-permission="{{ $permission->id }}"
                                                    id="checkbox-{{ $permission->id }}-{{ $role->id }}"
                                                    name="permission"
                                                    @unless ($canManage) aria-readonly="true" tabindex="-1" @endunless
                                                    {{ isset($assigned[$role->id . '-' . $permission->id]) ? 'checked' : '' }}>
                                            </td>
                                        @endforeach
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="perm-empty" hidden>Không có quyền nào khớp bộ lọc.</div>
                </div>
            </div>
        @endforeach

    </section>
    <!-- /.content -->
</div>

@if ($canManage)
    <div class="modal fade" id="manageRoleModal" tabindex="-1" role="dialog" aria-labelledby="manageRoleLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title w-100" id="manageRoleLabel">
                        <i class="fas fa-users-cog mr-2"></i> Quản Lý Nhóm Quyền
                    </h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Đóng">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>

                <div class="modal-body">
                    @if ($errors->roleErrors->any())
                        <div class="alert alert-danger">
                            @foreach ($errors->roleErrors->all() as $message)
                                <div>{{ $message }}</div>
                            @endforeach
                        </div>
                    @endif

                    <div class="role-list-scroll">
                        <table class="table table-bordered role-list-table mb-0" style="font-size: 14px">
                            <thead>
                                <tr>
                                    <th style="width: 50px">STT</th>
                                    <th style="width: 210px">Tên Nhóm Quyền</th>
                                    <th>Diễn Giải</th>
                                    <th style="width: 70px" class="text-center">User</th>
                                    <th style="width: 110px" class="text-center">Trạng Thái</th>
                                    <th style="width: 110px" class="text-center">Thao Tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($allRoles as $role)
                                    <tr class="{{ $role->active ? '' : 'role-inactive' }}">
                                        <td>{{ $loop->iteration }}</td>
                                        <td>{{ $role->name }}</td>
                                        <td>{{ $role->description ?? '—' }}</td>
                                        <td class="text-center">{{ $role->user_count }}</td>
                                        <td class="text-center">
                                            @if ($role->active)
                                                <span class="badge badge-success">Đang dùng</span>
                                            @else
                                                <span class="badge badge-secondary">Vô hiệu</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            @if ($role->id == 1)
                                                <span class="badge badge-secondary">
                                                    <i class="fas fa-lock"></i> Khoá
                                                </span>
                                            @else
                                                <button type="button" class="btn btn-sm btn-outline-primary btn-edit-role"
                                                    title="Sửa" data-id="{{ $role->id }}" data-name="{{ $role->name }}"
                                                    data-description="{{ $role->description }}">
                                                    <i class="fas fa-pen"></i>
                                                </button>
                                                <form action="{{ route('pages.User.role.deActive', $role->id) }}"
                                                    method="POST" class="d-inline form-deactive-role"
                                                    data-name="{{ $role->name }}" data-active="{{ $role->active }}"
                                                    data-users="{{ $role->user_count }}">
                                                    @csrf
                                                    @if ($role->active)
                                                        <button type="submit" class="btn btn-sm btn-outline-danger"
                                                            title="Vô hiệu hoá">
                                                            <i class="fas fa-ban"></i>
                                                        </button>
                                                    @else
                                                        <button type="submit" class="btn btn-sm btn-outline-success"
                                                            title="Kích hoạt lại">
                                                            <i class="fas fa-undo"></i>
                                                        </button>
                                                    @endif
                                                </form>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <form action="{{ route('pages.User.role.saveRole') }}" method="POST" class="role-form-box"
                        id="roleForm">
                        @csrf
                        <input type="hidden" name="id" id="roleFormId" value="{{ old('id') }}">

                        <div class="form-title" id="roleFormTitle">
                            <i class="fas fa-plus-circle mr-1"></i> Thêm Nhóm Quyền Mới
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group mb-2">
                                    <label for="roleFormName">Tên Nhóm Quyền</label>
                                    <input type="text" class="form-control" name="name" id="roleFormName"
                                        value="{{ old('name') }}" placeholder="VD: QC Manager">
                                </div>
                            </div>
                            <div class="col-md-8">
                                <div class="form-group mb-2">
                                    <label for="roleFormDescription">Diễn Giải</label>
                                    <input type="text" class="form-control" name="description"
                                        id="roleFormDescription" value="{{ old('description') }}"
                                        placeholder="Mô tả ngắn về nhóm quyền">
                                </div>
                            </div>
                        </div>

                        <div class="text-right">
                            <button type="button" class="btn btn-secondary d-none" id="btnResetRoleForm">
                                Huỷ sửa
                            </button>
                            <button type="submit" class="btn btn-primary" id="btnSubmitRoleForm">
                                <i class="fas fa-save mr-1"></i> Lưu
                            </button>
                        </div>
                    </form>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
                </div>
            </div>
        </div>
    </div>
@endif

<script src="{{ asset('js/vendor/jquery-1.12.4.min.js') }}"></script>
<script src="{{ asset('js/popper.min.js') }}"></script>
<script src="{{ asset('js/bootstrap.min.js') }}"></script>
<script src="{{ asset('js/sweetalert2.all.min.js') }}"></script>

@if (session('success'))
    <script>
        Swal.fire({
            title: 'Thành công!',
            text: @json(session('success')),
            icon: 'success',
            timer: 2000, // tự đóng sau 2 giây
            showConfirmButton: false
        });
    </script>
@endif

<script>
    var ADMIN_ROLE_ID = '1';

    // ----- Thu gọn / mở rộng card -----
    function setCardCollapsed($card, collapsed, save) {
        $card.toggleClass('is-collapsed', collapsed);
        $card.children('.card-body').stop(true, true)[collapsed ? 'slideUp' : 'slideDown'](150);

        if (save) {
            try {
                localStorage.setItem('pmsPermCard:' + $card.data('model'), collapsed ? '1' : '0');
            } catch (e) {}
        }
    }

    function applyStoredCollapse() {
        $('.perm-model-card').each(function() {
            var $c = $(this);
            var stored;
            try {
                stored = localStorage.getItem('pmsPermCard:' + $c.data('model'));
            } catch (e) {}

            var collapsed = stored === '1';
            $c.toggleClass('is-collapsed', collapsed);
            $c.children('.card-body').toggle(!collapsed);
        });
        refreshToggleAllLabel();
    }

    function refreshToggleAllLabel() {
        var anyOpen = $('.perm-model-card').not('.is-collapsed').length > 0;
        $('#btnToggleAllCards').html(anyOpen ?
            '<i class="fas fa-compress-alt mr-1"></i> Thu gọn tất cả' :
            '<i class="fas fa-expand-alt mr-1"></i> Mở tất cả');
    }

    $(document).ready(function() {
        document.body.style.overflowY = "auto";
        applyStoredCollapse();

        @if ($errors->roleErrors->any())
            $('#manageRoleModal').modal('show');

            // Lỗi khi đang sửa: giữ form ở chế độ sửa
            if ($('#roleFormId').val()) {
                $('#roleFormTitle').html('<i class="fas fa-pen mr-1"></i> Đang Sửa Nhóm Quyền');
                $('#btnResetRoleForm').removeClass('d-none');
            }
        @endif
    });

    $(document).on('click', '.perm-model-header', function() {
        var $card = $(this).closest('.perm-model-card');
        setCardCollapsed($card, !$card.hasClass('is-collapsed'), true);
        refreshToggleAllLabel();
    });

    $(document).on('click', '#btnToggleAllCards', function() {
        var collapseAll = $('.perm-model-card').not('.is-collapsed').length > 0;
        $('.perm-model-card').each(function() {
            setCardCollapsed($(this), collapseAll, true);
        });
        refreshToggleAllLabel();
    });

    // ----- Ô chỉ đọc (không có quyền phân quyền): chặn đổi trạng thái -----
    $(document).on('click', '.step-checkbox[aria-readonly="true"]', function(e) {
        e.preventDefault();
    });

    // Trang nạp 2 bản jQuery (1.12 ở cuối trang này, bản mới ở layout nạp sau): handler 'change' bên dưới gắn bằng
    // bản cũ, còn code chạy lúc bấm dùng $ của bản mới nên .trigger('change') không tới handler -> phát sự kiện gốc
    function fireChange($boxes) {
        $boxes.each(function() {
            this.dispatchEvent(new Event('change', {
                bubbles: true
            }));
        });
    }

    // ----- Bấm giữ rồi kéo qua nhiều ô: mọi ô đi qua nhận cùng trạng thái mới của ô bắt đầu -----
    // mousedown tự đổi ô bắt đầu nên click ngay sau đó của chính checkbox bị chặn (không đổi 2 lần);
    // bấm phím cách trên ô vẫn đổi bình thường vì không có mousedown
    var permDrag = null;
    var permSuppressClick = false;

    function paintCell(td) {
        var box = $(td).find('.step-checkbox');
        if (!box.length || box.is('[aria-readonly]') || $(td).hasClass('is-role-hidden')) return;
        if (box.prop('checked') === permDrag.checked) return;
        // Nhóm Admin luôn giữ toàn quyền nên không bỏ tick
        if (!permDrag.checked && String(box.data('role')) === ADMIN_ROLE_ID) return;
        fireChange(box.prop('checked', permDrag.checked));
    }

    $(document).on('mousedown', 'td.perm-cell', function(e) {
        var box = $(this).find('.step-checkbox');
        if (e.which !== 1 || !box.length || box.is('[aria-readonly]')) return;
        e.preventDefault();
        permDrag = {
            checked: !box.prop('checked')
        };
        permSuppressClick = $(e.target).is('.step-checkbox');
        $('body').addClass('perm-dragging');
        paintCell(this);
    });

    $(document).on('mouseenter', 'td.perm-cell', function() {
        if (permDrag) paintCell(this);
    });

    $(document).on('mouseup', function() {
        if (!permDrag) return;
        permDrag = null;
        $('body').removeClass('perm-dragging');
    });

    $(document).on('click', '.step-checkbox', function(e) {
        if (permSuppressClick) {
            e.preventDefault();
            permSuppressClick = false;
        }
    });

    // ----- Tô nhạt cột nhóm quyền đang rê chuột -----
    $(document).on('mouseenter', '.perm-table [data-role-col]', function() {
        var col = $(this).data('role-col');
        $(this).closest('.perm-table').find('[data-role-col="' + col + '"]').not('col').addClass('is-col-hover');
    });

    $(document).on('mouseleave', '.perm-table [data-role-col]', function() {
        $(this).closest('.perm-table').find('.is-col-hover').removeClass('is-col-hover');
    });

    // ----- Một quyền cho mọi nhóm quyền đang hiện: chưa tick hết thì tick hết, đã tick hết thì bỏ hết -----
    $(document).on('click', '.perm-row-all', function() {
        var row = $(this).closest('tr');
        var boxes = row.find('td.perm-cell:not(.is-role-hidden) .step-checkbox').not('[aria-readonly]');
        var allOn = boxes.not(':checked').length === 0;
        // Nhóm Admin luôn giữ toàn quyền nên không bỏ tick
        var targets = allOn ?
            boxes.filter(':checked').filter(function() {
                return String($(this).data('role')) !== ADMIN_ROLE_ID;
            }) :
            boxes.not(':checked');

        if (!targets.length) return;

        var apply = function() {
            fireChange(targets.prop('checked', !allOn));
        };

        if (!allOn) {
            apply();
            return;
        }

        Swal.fire({
            title: 'Bỏ quyền cho tất cả?',
            text: 'Bỏ quyền "' + $.trim(row.find('.permission-name').text()) + '" khỏi ' + targets.length +
                ' nhóm quyền đang hiện?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Bỏ tick',
            cancelButtonText: 'Huỷ',
            confirmButtonColor: '#DC2626'
        }).then(function(result) {
            if (result.isConfirmed) apply();
        });
    });

    // ----- Hàng "Số quyền đã cấp" của từng card: đếm ô đã tick theo nhóm quyền -----
    function refreshCounts($cards) {
        ($cards || $('.perm-model-card')).each(function() {
            var card = $(this);
            card.find('[data-count-role]').each(function() {
                var count = card.find('.step-checkbox[data-role="' + $(this).data('count-role') + '"]:checked').length;
                $(this).text(count || '—');
            });
        });
    }

    $(document).ready(function() {
        refreshCounts();
    });

    $(document).on('change', '.step-checkbox', function() {
        refreshCounts($(this).closest('.perm-model-card'));
    });

    // ----- Ma trận phân quyền: bật / tắt 1 quyền cho 1 nhóm -----
    $(document).on('change', '.step-checkbox', function() {
        var input = $(this);
        var checked = input.is(':checked');

        $.ajax({
            url: "{{ route('pages.User.role.store_or_update') }}",
            type: 'POST',
            dataType: 'json',
            data: {
                _token: '{{ csrf_token() }}',
                role_id: input.data('role'),
                permission_id: input.data('permission'),
                checked: checked
            },
            error: function(xhr) {
                var message = (xhr.responseJSON && xhr.responseJSON.error) ?
                    xhr.responseJSON.error : 'Không lưu được phân quyền';

                Swal.fire({
                    title: 'Lỗi!',
                    text: message,
                    icon: 'error'
                });

                input.prop('checked', !checked);
                refreshCounts(input.closest('.perm-model-card'));
            }
        });
    });

    // ----- Tiêu đề dính: khi cuộn trang, dịch tiêu đề của bảng đang cắt ngang topNAV xuống ngay dưới nó -----
    (function() {
        var pending = false;

        function syncStickyHeads() {
            pending = false;
            var nav = document.querySelector('.main-header');
            var top = nav ? nav.getBoundingClientRect().bottom : 0;

            document.querySelectorAll('.perm-table').forEach(function(table) {
                var head = table.tHead;
                if (!head || !table.offsetParent) return;
                var rect = table.getBoundingClientRect();
                var offset = Math.max(0, Math.min(top - rect.top, rect.height - head.offsetHeight));
                var value = offset > 0 ? 'translateY(' + offset + 'px)' : '';
                head.querySelectorAll('th').forEach(function(th) {
                    if (th.style.transform !== value) th.style.transform = value;
                });
            });
        }

        function schedule() {
            if (pending) return;
            pending = true;
            requestAnimationFrame(syncStickyHeads);
        }

        $(window).on('scroll resize load', schedule);
        $(document).ready(function() {
            schedule();
            // Mở / thu card, lọc cột làm đổi kích thước bảng
            if (window.ResizeObserver) {
                var observer = new ResizeObserver(schedule);
                document.querySelectorAll('.perm-table').forEach(function(el) {
                    observer.observe(el);
                });
            }
        });
    })();

    // ----- Đồng bộ cuộn ngang giữa các card -----
    (function() {
        var panes = document.querySelectorAll('.perm-table-scroll');
        var lock = false;

        panes.forEach(function(pane) {
            pane.addEventListener('scroll', function() {
                if (lock) return;
                lock = true;
                panes.forEach(function(other) {
                    if (other !== pane) other.scrollLeft = pane.scrollLeft;
                });
                lock = false;
            });
        });
    })();

    // ----- Bộ lọc: tên / mã quyền + nhóm quyền hiển thị + chỉ quyền đã cấp -----
    var permFilter = {
        roles: [],
        onlyGranted: false
    };

    try {
        var storedFilter = JSON.parse(localStorage.getItem('pmsPermRoleFilter') || 'null');
        if (storedFilter) $.extend(permFilter, storedFilter);
    } catch (e) {}

    function saveFilter() {
        try {
            localStorage.setItem('pmsPermRoleFilter', JSON.stringify(permFilter));
        } catch (e) {}
    }

    function applyFilters() {
        var keyword = $('#rolePermissionSearch').val().toLowerCase();
        var searching = keyword !== '';
        var roles = permFilter.roles;
        var onlyGranted = permFilter.onlyGranted && roles.length > 0;
        var narrowing = searching || onlyGranted;

        // Cột nhóm quyền: chỉ giữ các nhóm đang chọn (không chọn = tất cả)
        $('.perm-table [data-role-col]').each(function() {
            var hidden = roles.length > 0 && roles.indexOf(String($(this).data('role-col'))) === -1;
            $(this).toggleClass('is-role-hidden', hidden);
        });

        $('.perm-model-card').each(function() {
            var card = $(this);
            var hasMatch = false;

            card.find('tr.permission-row').each(function() {
                var row = $(this);
                var show = row.find('td:first').text().toLowerCase().indexOf(keyword) !== -1;

                if (show && onlyGranted) {
                    show = row.find('td.perm-cell:not(.is-role-hidden) .step-checkbox:checked').length > 0;
                }

                row.toggle(show);
                if (show) hasMatch = true;
            });

            // Khi đang tìm theo tên: mở tạm card có kết quả (không ghi nhớ)
            if (searching) {
                card.removeClass('is-collapsed');
                card.children('.card-body').toggle(hasMatch);
            }

            card.find('.perm-table-scroll').toggle(hasMatch);
            card.find('.perm-empty').prop('hidden', hasMatch || !narrowing);
            card.toggle(hasMatch || !narrowing);
        });
    }

    // permFilter.roles rỗng = tất cả nhóm quyền (kể cả nhóm tạo sau);
    // syncChecks = false khi người dùng đang tick dở trong menu (vd. vừa Bỏ chọn) để không tick lại hết
    function refreshFilterControls(syncChecks) {
        if (syncChecks !== false) {
            $('.role-filter-item').each(function() {
                this.checked = permFilter.roles.length === 0 || permFilter.roles.indexOf(this.value) !== -1;
            });
        }

        var names = $('.role-filter-item').filter(function() {
            return permFilter.roles.indexOf(this.value) !== -1;
        }).map(function() {
            return $.trim($(this).siblings('.role-filter-name').text());
        }).get();

        $('#roleFilterLabel').text(names.length === 0 ? 'Tất cả nhóm quyền' :
            (names.length <= 2 ? names.join(', ') : names.length + ' nhóm quyền'));
        $('.perm-role-filter').toggleClass('is-filtered', names.length > 0);

        $('#onlyGranted').prop('disabled', names.length === 0).prop('checked', permFilter.onlyGranted && names.length > 0);
        $('.perm-only-granted').toggleClass('is-disabled', names.length === 0);
    }

    function onFilterChanged(syncChecks) {
        saveFilter();
        refreshFilterControls(syncChecks);
        if (!$('#rolePermissionSearch').val()) applyStoredCollapse();
        applyFilters();
    }

    $(document).ready(function() {
        // Bỏ nhóm quyền đã nhớ nhưng nay không còn (đã vô hiệu hoá)
        var roleIds = $('.role-filter-item').map(function() {
            return this.value;
        }).get();
        permFilter.roles = $.grep(permFilter.roles, function(id) {
            return roleIds.indexOf(id) !== -1;
        });
        refreshFilterControls();
        applyFilters();
    });

    $(document).on('keyup', '#rolePermissionSearch', function() {
        if (!$(this).val()) applyStoredCollapse();
        applyFilters();
    });

    $(document).on('click', '#btnRoleFilter', function(e) {
        e.stopPropagation();
        $('#roleFilterMenu').prop('hidden', !$('#roleFilterMenu').prop('hidden'));
    });

    $(document).on('click', function(e) {
        if (!$(e.target).closest('.perm-role-filter').length) $('#roleFilterMenu').prop('hidden', true);
    });

    $(document).on('change', '.role-filter-item', function() {
        var checked = $('.role-filter-item:checked').map(function() {
            return this.value;
        }).get();
        // Tick đủ hoặc bỏ hết = không lọc, nhóm quyền tạo sau vẫn hiện
        permFilter.roles = checked.length === $('.role-filter-item').length ? [] : checked;
        onFilterChanged(checked.length > 0);
    });

    $(document).on('click', '#roleFilterAll, #roleFilterNone', function(e) {
        e.preventDefault();
        permFilter.roles = [];
        if (this.id === 'roleFilterNone') {
            $('.role-filter-item').prop('checked', false);
        }
        onFilterChanged(this.id === 'roleFilterAll');
    });

    $(document).on('change', '#onlyGranted', function() {
        permFilter.onlyGranted = this.checked;
        onFilterChanged();
    });

    // ----- Quản lý nhóm quyền: nạp dữ liệu 1 role vào form để sửa -----
    $(document).on('click', '.btn-edit-role', function() {
        var btn = $(this);
        $('#roleFormId').val(btn.data('id'));
        $('#roleFormName').val(btn.data('name'));
        $('#roleFormDescription').val(btn.data('description'));
        $('#roleFormTitle').html('<i class="fas fa-pen mr-1"></i> Đang Sửa: ').append(document.createTextNode(btn.data('name')));
        $('#btnResetRoleForm').removeClass('d-none');
        $('#roleFormName').focus();
    });

    $(document).on('click', '#btnResetRoleForm', function() {
        $('#roleFormId').val('');
        $('#roleFormName').val('');
        $('#roleFormDescription').val('');
        $('#roleFormTitle').html('<i class="fas fa-plus-circle mr-1"></i> Thêm Nhóm Quyền Mới');
        $(this).addClass('d-none');
    });

    // ----- Vô hiệu hoá / kích hoạt lại nhóm quyền: xác nhận trước khi gửi -----
    $(document).on('submit', '.form-deactive-role', function(e) {
        e.preventDefault();
        var form = this;
        var name = $(form).data('name');
        var active = $(form).data('active') == 1;
        var users = parseInt($(form).data('users'), 10) || 0;

        var text = active ?
            'Nhóm quyền "' + name + '" sẽ không còn cấp quyền cho người dùng.' +
            (users > 0 ? ' Đang có ' + users + ' người dùng thuộc nhóm này.' : '') :
            'Kích hoạt lại nhóm quyền "' + name + '"?';

        Swal.fire({
            title: active ? 'Vô hiệu hoá nhóm quyền?' : 'Kích hoạt lại?',
            text: text,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: active ? 'Vô hiệu hoá' : 'Kích hoạt',
            cancelButtonText: 'Huỷ',
            confirmButtonColor: active ? '#DC2626' : '#28a745'
        }).then(function(result) {
            if (result.isConfirmed) {
                form.submit();
            }
        });
    });
</script>
