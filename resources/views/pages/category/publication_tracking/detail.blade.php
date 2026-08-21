@extends ('layout.master')

@section('topNAV')
    @include('layout.topNAV')
@endsection

@section('leftNAV')
    @include('layout.leftNAV')
@endsection

@section('mainContent')
    @php
        $locked = $period->status === 'Đã chốt';

        // Kỳ của phân xưởng khác chỉ được xem, không chỉnh sửa
        $readOnly = $locked || !$canEdit;

        // 2 cách thêm nội dung được phân quyền riêng: tạo hàng loạt (nút trên đầu trang)
        // và thêm cho từng mã (nút trong ô). Kỳ đã chốt / phân xưởng khác thì cấm cả hai.
        // Quyền thêm từng mã còn giới hạn ở mã do chính người đang xem phụ trách,
        // nên phải xét theo từng dòng trong bảng.
        $showCreateTask = !$readOnly && $canCreateTask;
        $showAddTask = !$readOnly && $canAddTask;
        $currentUserId = session('user')['userId'];

        // Danh sách mã cho bảng chọn trong modal "Tạo nội dung theo dõi".
        // Không có quyền tạo thì khỏi nhúng, kỳ nào cũng cả nghìn mã.
        $pickerItems = !$showCreateTask
            ? collect()
            : $intermediates
            ->concat($products)
            ->map(
                fn($i) => [
                    'id' => (string) $i->id,
                    'code' => $i->code,
                    'name' => $i->product_name,
                    'type' => $i->category_type,
                ],
            )
            ->values();
    @endphp

    <style>
        /* Mỗi nội dung theo dõi là 1 dòng con trong ô. Quyết Định và Kết Quả giờ
           chỉ có 1 ô cho cả mã nên không cần ép chiều cao để canh hàng nữa. */
        .pt-task-line {
            padding: 3px 2px;
            border-bottom: 1px dashed #dee2e6;
        }

        /* Badge nội dung theo dõi: thanh 3 nút nằm trên, góc trái */
        .pt-badge {
            position: relative;
            border: 1px solid #ced4da;
            border-left: 3px solid #007bff;
            border-radius: 6px;
            background: #f8f9fa;
            padding: 6px 8px 6px 8px;
        }

        /* Hàng đầu badge: 4 nút + tên người sửa và thời gian, xếp chung 1 hàng cho gọn */
        .pt-badge-head {
            display: flex;
            align-items: center;
            margin-bottom: 4px;
        }

        .pt-badge-tools .btn {
            padding: 1px 7px;
            line-height: 1.3;
            font-size: 11px;
        }

        .pt-badge-meta {
            margin-left: 6px;
            font-size: 10.5px;
            color: #6c757d;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .pt-badge-text {
            white-space: pre-wrap;
            word-break: break-word;
        }

        /* Nội dung đã được chuyển sang kỳ sau: vẫn giữ ở kỳ này nhưng phải nhìn ra ngay */
        .pt-badge-moved {
            border-left-color: #fd7e14;
            background: #fff6ec;
        }

        .pt-badge-moved-note {
            margin-top: 3px;
            font-size: 11px;
            color: #c05600;
            font-weight: 600;
        }

        .pt-task-line:last-child {
            border-bottom: none;
        }

        .pt-items-table td {
            vertical-align: middle;
        }

        .pt-items-table textarea.pt-comment,
        .pt-items-table textarea.pt-opinion {
            resize: vertical;
        }

        /* Ý kiến DSPT nằm cuối ô Nội Dung Theo Dõi, tách khỏi các badge bằng 1 vạch */
        .pt-opinion-box {
            margin-top: 6px;
            padding-top: 5px;
            border-top: 1px dashed #ced4da;
        }

        .pt-opinion-label {
            font-size: 10.5px;
            font-weight: 600;
            color: #6c757d;
            text-transform: uppercase;
        }

        /* Quyết định là 1 nút bật/tắt: xanh = có thực hiện, xám = không thực hiện */
        .btn-decision {
            white-space: normal;
        }

        /* Chưa xét được đáp ứng thì ô kết quả không chừa chỗ trống */
        .pt-result-badge:empty {
            display: none;
        }

        /* Người và thời điểm thao tác của cột Quyết Định / Kết Quả Thực Hiện */
        .pt-meta {
            margin-top: 3px;
            font-size: 10.5px;
            color: #6c757d;
            text-align: center;
            word-break: break-word;
        }

        .pt-meta:empty {
            display: none;
        }
    </style>

    <div class="content-wrapper">
        <section class="content-header mt-5">
            <div class="container-fluid">
                <div class="row mb-2 align-items-center">
                    <div class="col-sm-7">
                        <h1 class="m-0">
                            <i class="fas fa-clipboard-list text-primary"></i> Theo Dõi Lên Ấn Bản &mdash;
                            {{ $period->label }}
                        </h1>
                        <small class="text-muted">
                            Chu kỳ: <strong>{{ $period->range_label }}</strong> &mdash; Phân xưởng:
                            <strong>{{ $departmentName }}</strong>
                            <span
                                class="badge {{ $locked ? 'badge-secondary' : 'badge-success' }} ml-1">{{ $period->status }}</span>
                            @unless ($canEdit)
                                <span class="badge badge-info ml-1">
                                    <i class="fas fa-eye"></i> Chỉ xem &mdash; phân xưởng khác
                                </span>
                            @endunless
                        </small>
                    </div>
                    <div class="col-sm-5 text-right">
                        <a href="{{ route('pages.category.publication_tracking.list') }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Danh sách kỳ
                        </a>
                        @if ($canEdit)
                            @unless ($locked)
                                @if ($canCreateTask)
                                    <button type="button" class="btn btn-primary" id="btnOpenAddTask">
                                        <i class="fas fa-plus"></i> Tạo nội dung theo dõi
                                    </button>
                                @endif
                                <button type="button" class="btn btn-info" id="btnSync">
                                    <i class="fas fa-sync-alt"></i> Đồng bộ mã hiệu lực
                                </button>
                            @endunless
                        @endif
                    </div>
                </div>
            </div>
        </section>

        <section class="content">
            <div class="container-fluid">
                <div class="card card-primary card-outline card-outline-tabs">
                    <div class="card-header p-0 border-bottom-0">
                        <ul class="nav nav-tabs" id="publicationTabs" role="tablist">
                            <li class="nav-item">
                                <a class="nav-link active font-weight-bold" id="tab-btp-tab" data-toggle="pill"
                                    href="#tab-btp" role="tab" aria-controls="tab-btp" aria-selected="true">
                                    <i class="fas fa-flask text-primary"></i> BMR
                                    <span class="badge badge-primary ml-1">{{ $intermediates->count() }}</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link font-weight-bold" id="tab-tp-tab" data-toggle="pill" href="#tab-tp"
                                    role="tab" aria-controls="tab-tp" aria-selected="false">
                                    <i class="fas fa-pills text-success"></i> BPR
                                    <span class="badge badge-success ml-1">{{ $products->count() }}</span>
                                </a>
                            </li>
                        </ul>
                    </div>
                    <div class="card-body">
                        <div class="tab-content" id="publicationTabsContent">
                            <div class="tab-pane fade show active" id="tab-btp" role="tabpanel"
                                aria-labelledby="tab-btp-tab">
                                @include('pages.category.publication_tracking.items_table', [
                                    'items' => $intermediates,
                                    'tableId' => 'data_table_btp',
                                    'type' => 'BTP',
                                    'codeLabel' => 'Mã BTP',
                                    'showMarket' => false,
                                    'bomRevisions' => $bomRevisions,
                                    'plannedBatches' => $plannedBatches,
                                    'hasMonthlyPlan' => $hasMonthlyPlan,
                                    'planLabel' => $period->label,
                                    'locked' => $readOnly,
                                    'showPicker' => $showCreateTask,
                                    'manageAll' => $showCreateTask,
                                    'manageOwn' => $showAddTask,
                                    'currentUserId' => $currentUserId,
                                ])
                            </div>
                            <div class="tab-pane fade" id="tab-tp" role="tabpanel" aria-labelledby="tab-tp-tab">
                                @include('pages.category.publication_tracking.items_table', [
                                    'items' => $products,
                                    'tableId' => 'data_table_tp',
                                    'type' => 'TP',
                                    'codeLabel' => 'Mã TP',
                                    'showMarket' => true,
                                    'bomRevisions' => $bomRevisions,
                                    'plannedBatches' => $plannedBatches,
                                    'hasMonthlyPlan' => $hasMonthlyPlan,
                                    'planLabel' => $period->label,
                                    'locked' => $readOnly,
                                    'showPicker' => $showCreateTask,
                                    'manageAll' => $showCreateTask,
                                    'manageOwn' => $showAddTask,
                                    'currentUserId' => $currentUserId,
                                ])
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>

    {{-- Modal tạo 1 nội dung theo dõi và gán cho nhiều mã BTP/TP cùng lúc --}}
    @if ($showCreateTask)
    <div class="modal fade" id="addTaskModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-xl" role="document" style="max-width: 900px;">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-plus text-primary"></i> Tạo nội dung theo dõi</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Đóng">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label for="task_content">Nội dung công việc <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="task_content" rows="3"
                            placeholder="Ví dụ: Rà soát lại cỡ lô trên hồ sơ lô..."></textarea>
                    </div>

                    <label>
                        Áp dụng cho <span id="task_target_count" class="badge badge-primary">0</span> mã
                    </label>

                    <div class="row mb-2">
                        <div class="col-md-5">
                            <input type="text" class="form-control form-control-sm" id="task_search"
                                placeholder="Tìm theo mã hoặc tên sản phẩm...">
                        </div>
                        <div class="col-md-3">
                            <select class="form-control form-control-sm" id="task_filter_type">
                                <option value="">Tất cả (BMR + BPR)</option>
                                <option value="BTP">Chỉ BMR</option>
                                <option value="TP">Chỉ BPR</option>
                            </select>
                        </div>
                        <div class="col-md-4 text-right">
                            <button type="button" class="btn btn-sm btn-outline-primary" id="btnPickAll">
                                Chọn tất cả kết quả lọc
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="btnPickNone">
                                Bỏ chọn hết
                            </button>
                        </div>
                    </div>

                    <div id="task_picker_list" class="border rounded p-2"
                        style="max-height: 320px; overflow-y: auto; font-size: 13px;"></div>
                    <small class="text-muted" id="task_picker_hint"></small>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Hủy</button>
                    <button type="button" class="btn btn-primary" id="btnSaveTask">
                        <i class="fas fa-save"></i> Lưu
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- Lịch sử thay đổi của 1 nội dung theo dõi --}}
    <div class="modal fade" id="taskHistoryModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-history text-info"></i>
                        <span id="taskHistoryTitle">Lịch sử thay đổi nội dung</span>
                    </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Đóng">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body" id="taskHistoryBody" style="max-height: 70vh; overflow-y: auto;"></div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
                </div>
            </div>
        </div>
    </div>

    <script src="{{ asset('js/sweetalert2.all.min.js') }}"></script>
    <script>
        // layout.js (jQuery, DataTables) được nạp sau mainContent nên phải đợi
        // DOMContentLoaded, không được gọi $ ngay ở cấp cao nhất
        document.addEventListener('DOMContentLoaded', function() {
            document.body.style.overflowY = "auto";

            const csrfToken = '{{ csrf_token() }}';
            const periodId = {{ $period->id }};
            const nextPeriodLabel = @json($nextPeriodLabel);

            // Được thêm / sửa / xoá / chuyển kỳ nội dung của mã này không: server đã xét
            // sẵn theo quyền và dược sĩ phụ trách rồi đóng dấu lên từng dòng
            const canManageAll = {{ $showCreateTask ? 'true' : 'false' }};

            // Cột Quyết Định có quyền riêng, không theo dược sĩ phụ trách của từng mã
            const canDecide = {{ !$readOnly && $canDecide ? 'true' : 'false' }};

            function canManage($el) {
                return $el.closest('tr').attr('data-can-manage') === '1';
            }

            const tables = {
                BTP: '#data_table_btp',
                TP: '#data_table_tp'
            };

            // Giữ id đã chọn ngoài DOM vì DataTables gỡ các dòng không thuộc trang hiện tại
            const selected = {
                BTP: new Set(),
                TP: new Set()
            };

            const dt = {};

            // ----- Dựng thanh nút cho các badge đang hiển thị -----
            // Markup này giống hệt nhau ở mọi badge nên in sẵn từ server rất tốn dung lượng;
            // DataTables chỉ gắn vào DOM các dòng của trang hiện tại nên dựng ở đây là đủ.
            const TOOLS_HTML =
                '<div class="pt-badge-tools btn-group btn-group-sm" role="group">' +
                '<button type="button" class="btn btn-outline-primary btn-edit-task" title="Sửa nội dung"><i class="fas fa-pen"></i></button>' +
                '<button type="button" class="btn btn-outline-danger btn-remove-task" title="Xoá nội dung khỏi mã này"><i class="fas fa-times"></i></button>' +
                '<button type="button" class="btn btn-outline-warning btn-carry-task"><i class="fas fa-share"></i></button>' +
                '<button type="button" class="btn btn-outline-info btn-task-history" title="Lịch sử thay đổi"><i class="fas fa-history"></i></button>' +
                '</div>';

            // Tên người sửa gần nhất + thời gian, nằm cùng hàng với các nút cho đỡ tốn chỗ
            function metaText($badge) {
                const by = $badge.attr('data-updated-by') || '';
                const at = $badge.attr('data-updated-at') || '';

                if (!by && !at) {
                    return '';
                }
                return by && at ? by + ' · ' + at : (by || at);
            }

            const ADD_HTML =
                '<button type="button" class="btn btn-outline-primary btn-add-one" title="Thêm nội dung theo dõi cho mã này">' +
                '<i class="fas fa-plus"></i> Thêm nội dung</button>';

            // Lịch sử chung của cả ô: gồm mọi nội dung đã / đang gắn cho mã này,
            // kể cả nội dung đã bị xoá nên không còn badge để bấm lịch sử riêng
            const CELL_HISTORY_HTML =
                '<button type="button" class="btn btn-outline-info btn-detail-history" title="Lịch sử thay đổi nội dung của mã này">' +
                '<i class="fas fa-history"></i> Lịch sử</button>';

            // Badge mới do client dựng sau khi thêm nội dung, khỏi phải tải lại trang
            function badgeHtml(itemId, taskId, content, count, updatedBy, updatedAt) {
                return '<div class="pt-task-line"><div class="pt-badge" data-item-id="' + itemId +
                    '" data-task-id="' + taskId + '" data-task-count="' + count +
                    '" data-moved-to="" data-moved-by="" data-updated-by="' +
                    escapeHtml(updatedBy || '') + '" data-updated-at="' + escapeHtml(updatedAt || '') +
                    '"><div class="pt-badge-text">' + escapeHtml(content) + '</div></div></div>';
            }

            // DataTables giữ dòng của trang khác ở dạng node rời khỏi document,
            // nên phải tìm qua rows().nodes() chứ không dùng $('...') toàn trang được
            function allRows() {
                let $rows = $();
                Object.keys(tables).forEach(function(type) {
                    if (dt[type]) {
                        $rows = $rows.add(dt[type].rows().nodes().to$());
                    }
                });
                return $rows;
            }

            function rowByDetailId(id) {
                return allRows().filter('[data-detail-id="' + id + '"]');
            }

            function movedNoteHtml(label, by) {
                return '<div class="pt-badge-moved-note"><i class="fas fa-share"></i> Đã chuyển sang ' +
                    escapeHtml(label || 'kỳ sau') + (by ? ' — ' + escapeHtml(by) : '') + '</div>';
            }

            // Đồng bộ giao diện badge theo trạng thái đã chuyển kỳ; chuyển rồi thì
            // khoá nút lại, mỗi nội dung trên 1 mã chỉ chuyển sang kỳ sau 1 lần
            function paintMoved($badge, label, by) {
                const moved = !!label;

                $badge.attr('data-moved-to', label || '')
                    .attr('data-moved-by', by || '')
                    .toggleClass('pt-badge-moved', moved);

                $badge.find('.btn-carry-task')
                    .prop('disabled', moved)
                    .attr('title', moved ?
                        'Đã chuyển sang kỳ ' + label :
                        'Chuyển nội dung này sang kỳ ' + nextPeriodLabel);

                $badge.find('.pt-badge-moved-note').remove();
                if (moved) {
                    $badge.append(movedNoteHtml(label, by));
                }
            }

            // 1 nút bật/tắt thay cho cặp nút Có/Không; chưa chọn gì thì mặc định là
            // "Không thực hiện" nên ô chỉ có 2 trạng thái để nhìn
            function decisionHtml(decision, due) {
                const yes = decision === '1';
                const dis = canDecide ? '' : ' disabled';

                return decisionBtnHtml(yes, dis) +
                    '<input type="date" class="form-control form-control-sm mt-1 pt-due-date' +
                    (yes ? '' : ' d-none') + '" value="' + (due || '') +
                    '" title="Ngày hoàn thành dự kiến"' + dis + '>';
            }

            function decisionBtnHtml(yes, dis) {
                return '<button type="button" class="btn btn-sm btn-block btn-decision ' +
                    (yes ? 'btn-success' : 'btn-secondary') + '" data-decision="' + (yes ? '1' : '0') +
                    '"' + (dis || '') + '><i class="fas ' + (yes ? 'fa-check' : 'fa-ban') + '"></i> ' +
                    (yes ? 'Có thực hiện' : 'Không thực hiện') + '</button>';
            }

            // Đáp ứng hay không là suy ra từ ngày, không lưu riêng: hoàn thành đúng
            // hoặc trước ngày dự kiến là đáp ứng, trễ hơn là không đáp ứng
            function resultHtml(decision, due, completed) {
                if (decision !== '1') {
                    return '';
                }
                if (!completed) {
                    return '<span class="badge badge-light border text-muted">Chưa hoàn thành</span>';
                }
                if (!due) {
                    return '';
                }
                // Chuỗi yyyy-mm-dd so sánh trực tiếp được, khỏi dựng Date
                return completed <= due ?
                    '<span class="badge badge-success">Đáp ứng</span>' :
                    '<span class="badge badge-danger">Không đáp ứng</span>';
            }

            function paintResult($row) {
                $row.find('.pt-result-badge').html(resultHtml(
                    String($row.find('.btn-decision').attr('data-decision') || ''),
                    $row.find('.pt-due-date').val(),
                    $row.find('.pt-completed-date').val()
                ));
            }

            // Chạy cả khi chỉ xem: người xem vẫn phải thấy trạng thái, chỉ là không sửa được
            function decorate(scope) {
                $(scope).find('.pt-decision-slot').each(function() {
                    const $slot = $(this);
                    if ($slot.data('built')) {
                        return;
                    }
                    $slot.data('built', true);
                    $slot.html(decisionHtml(String($slot.attr('data-decision') || ''), $slot.attr(
                        'data-due')));
                    paintResult($slot.closest('tr'));
                });

                $(scope).find('.pt-badge').each(function() {
                    const $badge = $(this);
                    if ($badge.data('decorated')) {
                        return;
                    }
                    $badge.data('decorated', true);

                    // Hàng đầu: nút thao tác (nếu sửa được) + người/thời gian sửa gần nhất
                    const $head = $('<div class="pt-badge-head"></div>');
                    if (canManage($badge)) {
                        $head.append(TOOLS_HTML);

                        // Sửa nội dung là đổi cho mọi mã đang dùng chung nó, trong đó có
                        // thể có mã của dược sĩ khác -> chỉ quyền tạo hàng loạt mới sửa được
                        if (!canManageAll && (parseInt($badge.attr('data-task-count'), 10) || 1) > 1) {
                            $head.find('.btn-edit-task')
                                .prop('disabled', true)
                                .attr('title',
                                    'Nội dung này dùng chung cho nhiều mã, chỉ người có quyền tạo nội dung theo dõi mới sửa được'
                                );
                        }
                    }
                    $head.append($('<span class="pt-badge-meta"></span>').text(metaText($badge)));
                    $badge.prepend($head);

                    paintMoved($badge, $badge.attr('data-moved-to'), $badge.attr('data-moved-by'));
                });

                // Nút lịch sử luôn có, kể cả khi chỉ xem; nút thêm còn cần đúng quyền
                $(scope).find('.pt-add-slot').each(function() {
                    if (!this.firstElementChild) {
                        $(this).html('<div class="btn-group btn-group-sm" role="group">' +
                            (canManage($(this)) ? ADD_HTML : '') + CELL_HISTORY_HTML + '</div>');
                    }
                });
            }

            Object.keys(tables).forEach(function(type) {
                dt[type] = $(tables[type]).DataTable({
                    "paging": true,
                    "pageLength": 25,
                    "lengthMenu": [
                        [25, 50, 100, 200, -1],
                        [25, 50, 100, 200, 'Tất cả']
                    ],
                    "lengthChange": true,
                    "searching": true,
                    "ordering": false,
                    "info": true,
                    "autoWidth": false,
                    "responsive": false,
                    "language": {
                        "search": "Tìm kiếm:",
                        "lengthMenu": "Hiển thị _MENU_ dòng",
                        "info": "Hiển thị _START_ - _END_ / _TOTAL_ mã",
                        "infoEmpty": "Không có mã nào",
                        "infoFiltered": "(lọc từ _MAX_ mã)",
                        "zeroRecords": "Không có mã nào hiệu lực trong kỳ này",
                        "paginate": {
                            "first": "Đầu",
                            "last": "Cuối",
                            "next": "Sau",
                            "previous": "Trước"
                        }
                    }
                });

                // Chuyển trang thì trả lại trạng thái tick của các dòng vừa được vẽ
                dt[type].on('draw', function() {
                    $(tables[type]).find('.pt-row-check').each(function() {
                        $(this).prop('checked', selected[type].has($(this).val()));
                    });
                    // Trang mới vừa được gắn vào DOM -> dựng nút cho các badge của trang đó
                    decorate(tables[type]);
                });

                decorate(tables[type]);
            });

            // ----- Lọc theo kế hoạch tháng -----
            // Lọc theo data-planned / data-additional của dòng chứ không theo chữ trong ô:
            // nhãn "KH" và "PS" nằm cùng ô với mã, tìm theo text sẽ dính nhầm cả nội dung
            // theo dõi của mã khác. Hai bộ lọc chồng nhau: chọn cả hai thì phải thoả cả hai.
            const rowFilters = [{
                selector: '.pt-filter-planned',
                attr: 'data-planned'
            }, {
                selector: '.pt-filter-additional',
                attr: 'data-additional'
            }];

            $.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
                const row = settings.aoData[dataIndex].nTr;

                return rowFilters.every(function(filter) {
                    const $select = $(filter.selector + '[data-table="' + settings.nTable.id + '"]');

                    // Bảng khác trên trang không có bộ lọc này thì không đụng tới
                    if (!$select.length || $select.val() === '') {
                        return true;
                    }

                    return $(row).attr(filter.attr) === $select.val();
                });
            });

            $(document).on('change', '.pt-filter-planned, .pt-filter-additional', function() {
                const selector = '#' + $(this).attr('data-table');

                Object.keys(tables).forEach(function(type) {
                    if (tables[type] === selector && dt[type]) {
                        dt[type].draw();
                    }
                });
            });

            // DataTables trong tab ẩn cần tính lại bề rộng khi tab được mở
            $('a[data-toggle="pill"]').on('shown.bs.tab', function() {
                $.fn.dataTable.tables({
                    visible: true,
                    api: true
                }).columns.adjust().draw();
            });

            function refreshCount(type) {
                $('#selected_count_' + type).text('Đã chọn: ' + selected[type].size);
            }

            $(document).on('change', '.pt-row-check', function() {
                const type = $(this).data('type');
                const id = $(this).val();

                if (this.checked) {
                    selected[type].add(id);
                } else {
                    selected[type].delete(id);
                }
                refreshCount(type);
            });

            // Chọn tất cả áp dụng cho mọi dòng đang khớp bộ lọc, kể cả trang chưa hiển thị
            $(document).on('change', '.pt-check-all', function() {
                const type = $(this).data('type');
                const check = this.checked;

                dt[type].rows({
                    search: 'applied'
                }).nodes().to$().find('.pt-row-check').each(function() {
                    if (check) {
                        selected[type].add($(this).val());
                    } else {
                        selected[type].delete($(this).val());
                    }
                    $(this).prop('checked', check);
                });
                refreshCount(type);
            });

            // ----- Tạo 1 nội dung theo dõi rồi gán cho nhiều mã -----
            // Toàn bộ mã của kỳ, để chọn ngay trong modal mà không cần tick sẵn ngoài bảng
            const allItems = @json($pickerItems);
            const MAX_RENDER = 200; // chỉ vẽ tối đa từng đó dòng, lọc tiếp để thấy phần còn lại
            const picked = new Set();

            function filteredItems() {
                const q = $('#task_search').val().trim().toLowerCase();
                const type = $('#task_filter_type').val();

                return allItems.filter(function(it) {
                    if (type && it.type !== type) {
                        return false;
                    }
                    if (!q) {
                        return true;
                    }
                    return (it.code || '').toLowerCase().indexOf(q) !== -1 ||
                        (it.name || '').toLowerCase().indexOf(q) !== -1;
                });
            }

            function escapeHtml(s) {
                return $('<div>').text(s == null ? '' : s).html();
            }

            function renderPicker() {
                const list = filteredItems();
                const shown = list.slice(0, MAX_RENDER);

                let html = '';
                shown.forEach(function(it) {
                    const isBmr = it.type === 'BTP';
                    const badge = isBmr ? 'badge-primary' : 'badge-success';
                    html += '<div class="custom-control custom-checkbox">' +
                        '<input type="checkbox" class="custom-control-input pt-pick" id="pick_' + it.id +
                        '" value="' + it.id + '"' + (picked.has(it.id) ? ' checked' : '') + '>' +
                        '<label class="custom-control-label" for="pick_' + it.id + '">' +
                        '<span class="badge ' + badge + ' mr-1">' + (isBmr ? 'BMR' : 'BPR') + '</span>' +
                        '<strong>' + escapeHtml(it.code) + '</strong> &mdash; ' + escapeHtml(it.name) +
                        '</label></div>';
                });

                $('#task_picker_list').html(html || '<div class="text-muted p-2">Không có mã nào khớp.</div>');
                $('#task_picker_hint').text(
                    list.length > MAX_RENDER ?
                    'Đang hiển thị ' + MAX_RENDER + '/' + list.length +
                    ' mã. Dùng ô tìm kiếm để thu hẹp, hoặc bấm "Chọn tất cả kết quả lọc" để chọn hết ' +
                    list.length + ' mã.' :
                    'Đang hiển thị ' + list.length + ' mã.'
                );
            }

            function refreshPickedCount() {
                $('#task_target_count').text(picked.size);
            }

            $('#btnOpenAddTask').on('click', function() {
                // Lấy sẵn những mã đã tick ngoài bảng làm lựa chọn ban đầu
                picked.clear();
                selected.BTP.forEach(id => picked.add(String(id)));
                selected.TP.forEach(id => picked.add(String(id)));

                $('#task_content').val('');
                $('#task_search').val('');
                $('#task_filter_type').val('');
                renderPicker();
                refreshPickedCount();
                $('#addTaskModal').modal('show');
            });

            $('#task_search, #task_filter_type').on('input change', renderPicker);

            $(document).on('change', '.pt-pick', function() {
                if (this.checked) {
                    picked.add(this.value);
                } else {
                    picked.delete(this.value);
                }
                refreshPickedCount();
            });

            // Áp cho toàn bộ kết quả lọc, không chỉ các dòng đang được vẽ
            $('#btnPickAll').on('click', function() {
                filteredItems().forEach(it => picked.add(it.id));
                renderPicker();
                refreshPickedCount();
            });

            $('#btnPickNone').on('click', function() {
                picked.clear();
                renderPicker();
                refreshPickedCount();
            });

            $('#btnSaveTask').on('click', function() {
                const content = $('#task_content').val().trim();

                if (!content) {
                    Swal.fire('Thiếu nội dung', 'Vui lòng nhập nội dung công việc.', 'warning');
                    return;
                }

                if (picked.size === 0) {
                    Swal.fire('Chưa chọn mã', 'Vui lòng chọn ít nhất 1 mã BTP/TP để gán nội dung này.',
                        'warning');
                    return;
                }

                const $btn = $(this).prop('disabled', true);

                $.ajax({
                    url: '{{ route('pages.category.publication_tracking.storeTask') }}',
                    method: 'POST',
                    data: {
                        _token: csrfToken,
                        period_id: periodId,
                        content: content,
                        // Gửi 1 chuỗi JSON thay vì mảng detail_ids[]: chọn cả nghìn mã
                        // sẽ vượt max_input_vars của PHP và request bị cắt mất dữ liệu
                        detail_ids: JSON.stringify(Array.from(picked))
                    },
                    success: function(res) {
                        $btn.prop('disabled', false);

                        if (!res.success) {
                            Swal.fire('Không lưu được', res.message, 'warning');
                            return;
                        }

                        addBadges(res);
                        $('#addTaskModal').modal('hide');
                        toast(res.message);
                    },
                    error: function(xhr) {
                        $btn.prop('disabled', false);
                        Swal.fire('Lỗi', firstError(xhr), 'error');
                    }
                });
            });

            // Chèn badge mới vào đúng các dòng vừa được gán nội dung
            function addBadges(res) {
                const $rows = allRows();

                res.items.forEach(function(item) {
                    const $row = $rows.filter('[data-detail-id="' + item.detail_id + '"]');
                    if (!$row.length) {
                        return;
                    }

                    const $cell = $row.find('.pt-add-slot').closest('td');
                    $cell.find('.pt-task-line.text-muted').remove(); // bỏ dòng "Chưa có nội dung"
                    $(badgeHtml(item.id, res.task_id, res.content, res.count, res.updated_by, res
                            .updated_at))
                        .insertBefore($cell.find('.pt-add-slot'));
                });

                decorate(document);
            }

            // ----- Gỡ công việc khỏi 1 mã -----
            $(document).on('click', '.btn-remove-task', function() {
                const $badge = $(this).closest('.pt-badge');
                const taskId = $badge.attr('data-task-id');
                const detailId = $badge.closest('tr').attr('data-detail-id');

                Swal.fire({
                    title: 'Xoá nội dung theo dõi?',
                    text: 'Nội dung này sẽ được gỡ khỏi mã đang chọn.',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Xoá',
                    cancelButtonText: 'Hủy'
                }).then(function(result) {
                    if (!result.isConfirmed) {
                        return;
                    }

                    $.ajax({
                        url: '{{ route('pages.category.publication_tracking.deleteTask') }}',
                        method: 'POST',
                        data: {
                            _token: csrfToken,
                            task_id: taskId,
                            detail_id: detailId
                        },
                        success: function(res) {
                            if (!res.success) {
                                Swal.fire('Không gỡ được', res.message, 'warning');
                                return;
                            }

                            removeBadge(res);
                            toast(res.message);
                        },
                        error: function(xhr) {
                            Swal.fire('Lỗi', firstError(xhr), 'error');
                        }
                    });
                });
            });

            function removeBadge(res) {
                const $rows = allRows();

                // Gỡ badge khỏi mã đã chọn; nếu server xoá hẳn công việc thì gỡ ở mọi mã
                const $target = res.task_deleted ?
                    $rows.find('.pt-badge[data-task-id="' + res.task_id + '"]') :
                    $rows.filter('[data-detail-id="' + res.detail_id + '"]')
                    .find('.pt-badge[data-task-id="' + res.task_id + '"]');

                const $cells = $target.closest('td');
                $target.closest('.pt-task-line').remove();

                // Số mã dùng chung nội dung này đã đổi -> cập nhật cảnh báo khi sửa
                if (!res.task_deleted) {
                    $rows.find('.pt-badge[data-task-id="' + res.task_id + '"]')
                        .attr('data-task-count', res.remaining);
                }

                // Ô nào hết badge thì hiện lại dòng "Chưa có nội dung theo dõi"
                $cells.each(function() {
                    const $cell = $(this);
                    if (!$cell.find('.pt-badge').length && !$cell.find('.pt-task-line.text-muted').length) {
                        $('<div class="pt-task-line text-muted font-italic">Chưa có nội dung theo dõi</div>')
                            .insertBefore($cell.find('.pt-add-slot'));
                    }
                });
            }

            // ----- Sửa nội dung của 1 badge -----
            $(document).on('click', '.btn-edit-task', function() {
                const $badge = $(this).closest('.pt-badge');
                const taskId = $badge.attr('data-task-id');
                const count = parseInt($badge.attr('data-task-count'), 10) || 1;
                const current = $badge.find('.pt-badge-text').text().trim();

                Swal.fire({
                    title: 'Sửa nội dung theo dõi',
                    input: 'textarea',
                    inputValue: current,
                    // Sửa là đổi cho mọi mã đang dùng chung nội dung này, phải nói trước
                    html: count > 1 ?
                        '<div class="text-danger">Nội dung này đang gắn cho <strong>' + count +
                        ' mã</strong>. Sửa sẽ áp dụng cho tất cả các mã đó.</div>' : '',
                    showCancelButton: true,
                    confirmButtonText: 'Lưu',
                    cancelButtonText: 'Hủy',
                    inputValidator: function(value) {
                        if (!value || !value.trim()) {
                            return 'Nội dung không được để trống.';
                        }
                    }
                }).then(function(result) {
                    if (!result.isConfirmed) {
                        return;
                    }

                    $.ajax({
                        url: '{{ route('pages.category.publication_tracking.updateTask') }}',
                        method: 'POST',
                        data: {
                            _token: csrfToken,
                            task_id: taskId,
                            content: result.value.trim()
                        },
                        success: function(res) {
                            if (!res.success) {
                                Swal.fire('Không sửa được', res.message, 'warning');
                                return;
                            }

                            // Nội dung dùng chung nên phải đổi ở mọi mã đang gắn nó
                            const $badges = allRows().find('.pt-badge[data-task-id="' + res
                                .task_id + '"]');
                            $badges.find('.pt-badge-text').text(res.content);
                            $badges.attr('data-updated-by', res.updated_by || '')
                                .attr('data-updated-at', res.updated_at || '')
                                .each(function() {
                                    $(this).find('.pt-badge-meta').text(metaText($(this)));
                                });
                            toast(res.message);
                        },
                        error: function(xhr) {
                            Swal.fire('Lỗi', firstError(xhr), 'error');
                        }
                    });
                });
            });

            // ----- Lịch sử thay đổi nội dung theo dõi -----
            function historyTableHtml(rows) {
                let html =
                    '<table class="table table-bordered table-sm mb-0"><thead><tr>' +
                    '<th style="width:150px">Thời điểm</th><th style="width:160px">Người thực hiện</th>' +
                    '<th style="width:120px">Thao tác</th><th>Chi tiết</th></tr></thead><tbody>';

                rows.forEach(function(r) {
                    let detail = '';

                    if (r.action === 'update') {
                        detail = '<div class="text-danger"><del>' + escapeHtml(r.old_content || '') +
                            '</del></div>' +
                            '<div class="text-success">' + escapeHtml(r.new_content || '') + '</div>';
                    } else if (r.action === 'create') {
                        detail = escapeHtml(r.new_content || '') +
                            (r.affected_count ? ' <span class="badge badge-primary">' + r
                                .affected_count + ' mã</span>' : '');
                    } else if (r.action === 'carry') {
                        // new_content của dòng này là nhãn kỳ đích, không phải nội dung mới
                        detail = escapeHtml(r.old_content || '') +
                            ' <span class="badge badge-warning">chuyển ' + escapeHtml(r.detail_code ||
                                'mã này') + ' sang ' + escapeHtml(r.new_content || 'kỳ sau') +
                            '</span>';
                    } else if (r.action === 'detach') {
                        detail = escapeHtml(r.old_content || '') +
                            ' <span class="badge badge-warning">gỡ khỏi ' + escapeHtml(r.detail_code ||
                                'mã này') + '</span>';
                    } else {
                        detail = escapeHtml(r.old_content || '');
                    }

                    html += '<tr><td>' + escapeHtml(r.changed_at || '') + '</td><td>' +
                        escapeHtml(r.changed_by || '') + '</td><td>' +
                        escapeHtml(r.action_label) + '</td><td>' + detail + '</td></tr>';
                });

                return html + '</tbody></table>';
            }

            function openHistory(title, params) {
                $('#taskHistoryTitle').text(title);
                $('#taskHistoryBody').html('<div class="text-center p-4">Đang tải...</div>');
                $('#taskHistoryModal').modal('show');

                const url = params.task_id ?
                    '{{ route('pages.category.publication_tracking.taskHistory') }}' :
                    '{{ route('pages.category.publication_tracking.detailHistory') }}';

                $.get(url, params, function(res) {
                    if (!res.success || !res.rows.length) {
                        $('#taskHistoryBody').html(
                            '<div class="text-center text-muted p-3">Chưa có thay đổi nào.</div>');
                        return;
                    }
                    $('#taskHistoryBody').html(historyTableHtml(res.rows));
                }).fail(function(xhr) {
                    $('#taskHistoryBody').html('<div class="text-danger p-3">' + firstError(xhr) +
                        '</div>');
                });
            }

            // Lịch sử của riêng 1 nội dung (nút trên badge)
            $(document).on('click', '.btn-task-history', function() {
                openHistory('Lịch sử thay đổi nội dung', {
                    task_id: $(this).closest('.pt-badge').attr('data-task-id')
                });
            });

            // Lịch sử chung của cả mã: mọi nội dung đã và đang gắn cho mã này
            $(document).on('click', '.btn-detail-history', function() {
                const $row = $(this).closest('tr');
                // Ô mã còn chứa dòng process_code, chỉ lấy dòng đầu làm tiêu đề
                const code = $row.find('td.pt-code-cell > div').first().text().trim();

                openHistory('Lịch sử nội dung theo dõi — ' + (code || 'mã này'), {
                    detail_id: $row.attr('data-detail-id')
                });
            });

            // ----- Chuyển nội dung của 1 mã sang chu kỳ tiếp theo -----
            $(document).on('click', '.btn-carry-task', function() {
                const $btn = $(this);
                const $badge = $btn.closest('.pt-badge');
                const $row = $badge.closest('tr');
                // Ô mã còn chứa dòng process_code, chỉ lấy dòng đầu
                const code = $row.find('td.pt-code-cell > div').first().text().trim();
                const content = $badge.find('.pt-badge-text').text().trim();

                Swal.fire({
                    title: 'Chuyển sang kỳ ' + nextPeriodLabel + '?',
                    html: '<div class="text-left small">Nội dung <strong>' + escapeHtml(content) +
                        '</strong><br>của mã <strong>' + escapeHtml(code || 'này') +
                        '</strong> sẽ được tạo lại ở kỳ <strong>' + escapeHtml(nextPeriodLabel) +
                        '</strong>.<br><span class="text-muted">Kỳ hiện tại vẫn giữ nội dung này để lưu vết.</span></div>',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Chuyển',
                    cancelButtonText: 'Hủy'
                }).then(function(result) {
                    if (!result.isConfirmed) {
                        return;
                    }

                    $btn.prop('disabled', true);

                    $.ajax({
                        url: '{{ route('pages.category.publication_tracking.carryTaskItem') }}',
                        method: 'POST',
                        data: {
                            _token: csrfToken,
                            id: $badge.attr('data-item-id')
                        },
                        success: function(res) {
                            if (!res.success) {
                                $btn.prop('disabled', false);
                                Swal.fire('Không chuyển được', res.message, 'warning');
                                return;
                            }

                            // Đánh dấu tại chỗ, không cần tải lại cả trang
                            paintMoved($badge, res.moved_to_label, res.moved_by);
                            toast(res.message);
                        },
                        error: function(xhr) {
                            $btn.prop('disabled', false);
                            Swal.fire('Lỗi', firstError(xhr), 'error');
                        }
                    });
                });
            });

            // ----- Thêm nội dung cho riêng 1 mã (nút + trong ô) -----
            $(document).on('click', '.btn-add-one', function() {
                const $row = $(this).closest('tr');
                const detailId = String($row.attr('data-detail-id'));
                // Ô mã còn chứa mã quy trình và version công thức, chỉ lấy dòng đầu
                const code = $row.find('td.pt-code-cell > div').first().text().trim();
                const name = $row.find('td.pt-name-cell').text().trim();

                Swal.fire({
                    title: 'Thêm nội dung theo dõi',
                    html: '<div class="text-left small">Áp dụng cho mã <strong>' + escapeHtml(code) +
                        '</strong><br><span class="text-muted">' + escapeHtml(name) + '</span></div>',
                    input: 'textarea',
                    inputPlaceholder: 'Nhập nội dung công việc...',
                    showCancelButton: true,
                    confirmButtonText: 'Lưu',
                    cancelButtonText: 'Hủy',
                    inputValidator: function(value) {
                        if (!value || !value.trim()) {
                            return 'Nội dung không được để trống.';
                        }
                    }
                }).then(function(result) {
                    if (!result.isConfirmed) {
                        return;
                    }

                    $.ajax({
                        url: '{{ route('pages.category.publication_tracking.storeTask') }}',
                        method: 'POST',
                        data: {
                            _token: csrfToken,
                            period_id: periodId,
                            content: result.value.trim(),
                            detail_ids: JSON.stringify([detailId])
                        },
                        success: function(res) {
                            if (!res.success) {
                                Swal.fire('Không lưu được', res.message, 'warning');
                                return;
                            }

                            addBadges(res);
                            toast('Đã thêm nội dung theo dõi.');
                        },
                        error: function(xhr) {
                            Swal.fire('Lỗi', firstError(xhr), 'error');
                        }
                    });
                });
            });

            // ----- Quyết định: 1 nút bật/tắt Có / Không thực hiện -----
            $(document).on('click', '.btn-decision', function() {
                if (!canDecide) {
                    return;
                }

                const $btn = $(this);
                const $cell = $btn.closest('.pt-detail');
                // Bấm là lật trạng thái hiện tại
                const isYes = String($btn.attr('data-decision')) !== '1';

                $btn.replaceWith(decisionBtnHtml(isYes, ''));

                // Chỉ "Có thực hiện" mới cần ngày hoàn thành dự kiến
                const $due = $cell.find('.pt-due-date');
                $due.toggleClass('d-none', !isYes);

                if (!isYes) {
                    $due.val('');
                    paintResult($cell.closest('tr'));
                    saveDetail($cell);
                    return;
                }

                paintResult($cell.closest('tr'));

                if (!$due.val()) {
                    $due.focus(); // chờ người dùng nhập ngày rồi mới lưu
                    return;
                }

                saveDetail($cell);
            });

            $(document).on('change', '.pt-due-date, .pt-completed-date', function() {
                const $cell = $(this).closest('.pt-detail');

                paintResult($cell.closest('tr'));
                saveDetail($cell);
            });

            // Ghi chú kết quả và ý kiến DSPT đều chỉ lưu khi rời ô và có thay đổi
            $(document).on('focus', '.pt-comment, .pt-opinion', function() {
                $(this).data('original', $(this).val());
            });

            $(document).on('blur', '.pt-comment, .pt-opinion', function() {
                if ($(this).val() === $(this).data('original')) {
                    return;
                }
                saveDetail($(this).closest('.pt-detail'));
            });

            function saveDetail($cell) {
                // Ý kiến DSPT, Quyết định và Kết quả nằm ở 3 ô khác nhau nhưng cùng 1 mã BTP/TP.
                // DataTables giữ dòng của trang khác ngoài document nên phải tìm trong allRows().
                const detailId = $cell.attr('data-detail-id');
                const $all = allRows().find('.pt-detail[data-detail-id="' + detailId + '"]');

                const $btn = $all.find('.btn-decision');
                const decision = $btn.length ? String($btn.attr('data-decision')) : '';

                const payload = {
                    _token: csrfToken,
                    id: detailId,
                    decision: decision,
                    due_date: $all.find('.pt-due-date').val() || '',
                    completed_date: $all.find('.pt-completed-date').val() || '',
                    comment: $all.find('.pt-comment').val() || '',
                    pharmacist_opinion: $all.find('.pt-opinion').val() || ''
                };

                $.ajax({
                    url: '{{ route('pages.category.publication_tracking.updateDetailDecision') }}',
                    method: 'POST',
                    data: payload,
                    success: function(res) {
                        if (res.success) {
                            $all.find('.pt-comment, .pt-opinion').each(function() {
                                $(this).data('original', $(this).val());
                            });
                            // Server chỉ đóng dấu ô nào thực sự đổi nên cứ nhận cả 3 nhãn về
                            $all.find('.pt-decision-meta').text(res.decision_meta || '');
                            $all.find('.pt-result-meta').text(res.result_meta || '');
                            $all.find('.pt-opinion-meta').text(res.opinion_meta || '');
                            flash($all);
                        } else {
                            Swal.fire('Không lưu được', res.message, 'warning');
                        }
                    },
                    error: function(xhr) {
                        Swal.fire('Lỗi', firstError(xhr), 'error');
                    }
                });
            }

            // Báo nhẹ ở góc màn hình thay cho hộp thoại chặn thao tác
            function toast(message) {
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'success',
                    title: message,
                    showConfirmButton: false,
                    timer: 1800,
                    timerProgressBar: true
                });
            }

            function flash($block) {
                $block.css('background-color', '#d4edda');
                setTimeout(function() {
                    $block.css('background-color', '');
                }, 700);
            }

            function firstError(xhr) {
                const res = xhr.responseJSON;
                if (res && res.errors) {
                    return Object.values(res.errors)[0][0];
                }
                if (res && res.message) {
                    return res.message;
                }
                // Không phải JSON (trang lỗi 500/419...) thì ít nhất cho thấy mã lỗi để còn lần ra
                if (xhr.status === 419) {
                    return 'Phiên làm việc đã hết hạn, vui lòng tải lại trang.';
                }
                return 'Không lưu được dữ liệu (HTTP ' + (xhr.status || 0) + ').';
            }

            // ----- Đồng bộ mã hiệu lực -----
            $('#btnSync').on('click', function() {
                const $btn = $(this).prop('disabled', true);

                $.ajax({
                    url: '{{ route('pages.category.publication_tracking.sync') }}',
                    method: 'POST',
                    data: {
                        _token: csrfToken,
                        period_id: periodId
                    },
                    success: function(res) {
                        if (res.success) {
                            location.reload();
                        } else {
                            $btn.prop('disabled', false);
                            Swal.fire('Không đồng bộ được', res.message, 'warning');
                        }
                    },
                    error: function() {
                        $btn.prop('disabled', false);
                        Swal.fire('Lỗi', 'Không đồng bộ được danh sách.', 'error');
                    }
                });
            });

        });
    </script>
@endsection
