<link href="{{ asset('css/bootstrap.min.css') }}" rel="stylesheet">

<style>
    .role-page {
        --role-primary: #003A4F;
        --role-primary-light: #0B5A75;
        --role-accent: #CDC717;
        --role-soft: #EEF5F7;
        --role-line: #E6EEF1;
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
    }

    .role-search {
        max-width: 320px;
        border-radius: 8px;
    }

    .btn-outline-role {
        color: var(--role-primary);
        border: 1px solid var(--role-primary);
        background: #fff;
    }

    .btn-outline-role:hover {
        color: #fff;
        background: var(--role-primary);
    }

    /* ===== Mỗi nhóm chức năng = 1 card ===== */
    .perm-model-card {
        border: none;
        border-radius: 14px;
        box-shadow: 0 4px 16px rgba(0, 58, 79, .10);
        overflow: hidden;
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

    /* ===== Bảng quyền trong card ===== */
    /* Mỗi card có vùng cuộn riêng (ngang + dọc); tiêu đề role dính khi cuộn dọc */
    .perm-table-scroll {
        overflow: auto;
        max-height: 68vh;
    }

    .perm-table {
        width: max-content;
        min-width: 100%;
        table-layout: fixed;
        border-collapse: separate;
        border-spacing: 0;
        font-size: 14.5px;
    }

    .perm-table col.perm-col-name {
        width: 340px;
    }

    .perm-table col.perm-col-role {
        width: 118px;
    }

    .perm-table th,
    .perm-table td {
        padding: 10px 16px;
        border-bottom: 1px solid var(--role-line);
    }

    .perm-table thead th {
        position: sticky;
        top: 0;
        z-index: 3;
        background: #fff;
        color: var(--role-primary);
        font-weight: 700;
        box-shadow: inset 0 -2px 0 var(--role-accent);
    }

    .perm-table thead th+th {
        text-align: center;
        font-size: 12px;
        line-height: 1.25;
        padding: 10px 6px;
        vertical-align: middle;
    }

    /* Ghim cột "Quyền" khi cuộn ngang; ô góc trên-trái ghim cả hai chiều */
    .perm-table th:first-child,
    .perm-table td:first-child {
        position: sticky;
        left: 0;
        background: #fff;
        z-index: 2;
        box-shadow: inset -1px 0 0 var(--role-line);
    }

    .perm-table thead th:first-child {
        z-index: 4;
        box-shadow: inset 0 -2px 0 var(--role-accent), inset -1px 0 0 var(--role-line);
    }

    .perm-table tbody tr.permission-row:hover td {
        background: var(--role-soft);
    }

    .perm-name-cell .permission-name {
        color: #2D3748;
    }

    .perm-name-cell .permission-code {
        font-size: 11.5px;
        color: #9AA7B4;
        font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
    }

    .perm-cell {
        text-align: center;
        vertical-align: middle;
    }

    .step-checkbox {
        width: 19px;
        height: 19px;
        cursor: pointer;
        accent-color: var(--role-primary);
        transition: box-shadow .2s ease, transform .1s ease;
    }

    .step-checkbox:checked {
        box-shadow: 0 0 5px var(--role-primary-light);
    }

    .step-checkbox:active {
        transform: scale(.9);
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
                    placeholder="Tìm quyền theo tên...">
                <button type="button" class="btn btn-outline-role ml-auto" id="btnToggleAllCards">
                    <i class="fas fa-compress-alt mr-1"></i> Thu gọn tất cả
                </button>
            </div>
        </div>

        {{-- Mỗi nhóm chức năng một card riêng --}}
        @foreach ($tree as $groupKey => $group)
            <div class="card perm-model-card" data-model="{{ $groupKey }}">
                <div class="card-header perm-model-header">
                    <span class="perm-model-icon"><i class="fas {{ $group['icon'] }}"></i></span>
                    <span class="perm-model-name">{{ $group['label'] }}</span>
                    <span class="perm-model-count">{{ count($group['permissions']) }}</span>
                    <i class="fas fa-chevron-down perm-model-toggle"></i>
                </div>

                <div class="card-body">
                    <div class="perm-table-scroll">
                        <table class="perm-table">
                            <colgroup>
                                <col class="perm-col-name">
                                @foreach ($roles as $role)
                                    <col class="perm-col-role">
                                @endforeach
                            </colgroup>
                            <thead>
                                <tr>
                                    <th>Quyền</th>
                                    @foreach ($roles as $role)
                                        <th title="{{ $role->description }}">{{ $role->name }}</th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($group['permissions'] as $permission)
                                    <tr class="permission-row">
                                        <td class="perm-name-cell">
                                            <div class="permission-name">
                                                {{ $permission->display_name ?: $permission->name }}
                                            </div>
                                            <div class="permission-code">{{ $permission->name }}</div>
                                        </td>

                                        @foreach ($roles as $role)
                                            <td class="perm-cell">
                                                <input class="step-checkbox" type="checkbox"
                                                    data-role="{{ $role->id }}"
                                                    data-permission="{{ $permission->id }}"
                                                    id="checkbox-{{ $permission->id }}-{{ $role->id }}"
                                                    name="permission"
                                                    {{ isset($assigned[$role->id . '-' . $permission->id]) ? 'checked' : '' }}>
                                            </td>
                                        @endforeach
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="perm-empty" hidden>Không có quyền nào khớp từ khoá tìm kiếm.</div>
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
            text: '{{ session('success') }}',
            icon: 'success',
            timer: 2000, // tự đóng sau 2 giây
            showConfirmButton: false
        });
    </script>
@endif

<script>
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

    // ----- Ma trận phân quyền: bật / tắt 1 quyền cho 1 nhóm -----
    $(document).on('change', '.step-checkbox', function() {
        var input = $(this);
        var checked = input.is(':checked');

        $.ajax({
            url: "{{ route('pages.User.role.store_or_update') }}",
            type: 'POST',
            dataType: 'json', // 👉 ép jQuery hiểu rõ kiểu dữ liệu trả về
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
            }
        });
    });

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

    // ----- Lọc theo tên quyền: ẩn dòng / cả card không còn kết quả -----
    $(document).on('keyup', '#rolePermissionSearch', function() {
        var keyword = $(this).val().toLowerCase();
        var searching = keyword !== '';

        if (!searching) {
            applyStoredCollapse();
        }

        $('.perm-model-card').each(function() {
            var card = $(this);
            var hasMatch = false;

            card.find('tr.permission-row').each(function() {
                var name = $(this).find('td:first').text().toLowerCase();
                var show = name.indexOf(keyword) !== -1;
                $(this).toggle(show);
                if (show) hasMatch = true;
            });

            // Khi đang tìm: mở tạm card có kết quả (không ghi nhớ), ẩn card không khớp
            if (searching) {
                card.removeClass('is-collapsed');
                card.children('.card-body').toggle(hasMatch);
            }

            card.toggle(hasMatch || !searching);
        });
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
