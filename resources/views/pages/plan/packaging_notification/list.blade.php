@extends ('layout.master')

@section('topNAV')
    @include('layout.topNAV')
@endsection

@section('leftNAV')
    @include('layout.leftNAV')
@endsection

@section('mainContent')
    <div class="content-wrapper">
        <section class="content-header">
            <div class="container-fluid">
                <div class="row mb-2 align-items-center">
                    <div class="col-sm-8">
                        <h1>
                            <i class="fas fa-box-open text-primary"></i> Thông Báo Đóng Gói
                            <small class="text-muted">- {{ $plan->name }}</small>
                        </h1>
                    </div>
                    <div class="col-sm-4 text-right">
                        <a href="{{ route('pages.plan.packaging_notification.list') }}" class="btn btn-sm btn-secondary">
                            <i class="fas fa-arrow-left"></i> Danh Sách Kế Hoạch
                        </a>
                    </div>
                </div>
            </div>
        </section>

        <section class="content">
            <div class="container-fluid">
                <form method="GET" action="{{ route('pages.plan.packaging_notification.open') }}"
                    class="form-row align-items-end mb-3">
                    <input type="hidden" name="plan_list_id" value="{{ $plan->id }}">
                    <div class="col-md-3">
                        <label class="mb-1">Tìm kiếm</label>
                        <input type="text" class="form-control form-control-sm" name="keyword"
                            value="{{ $keyword }}" placeholder="Số lô / mã TP / mã BTP / tên SP...">
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-search"></i> Lọc</button>
                        <a href="{{ route('pages.plan.packaging_notification.open', ['plan_list_id' => $plan->id]) }}"
                            class="btn btn-sm btn-secondary">
                            <i class="fas fa-redo"></i> Mặc định
                        </a>
                    </div>
                    @if ($canAdd)
                        <div class="col-md-6 text-right">
                            <button type="button" class="btn btn-sm btn-success" data-toggle="modal"
                                data-target="#pkgAddModal">
                                <i class="fas fa-plus"></i> Tạo Thông Báo Khác
                            </button>
                        </div>
                    @endif
                </form>

                @if (!$canUpdatePo && !$canUpdateSampling)
                    <div class="alert alert-warning py-2">
                        <i class="fas fa-lock"></i> Bạn chỉ có quyền xem. Liên hệ quản trị để được cấp quyền
                        <b>Nhập Số PO Thông Báo Đóng Gói</b> hoặc
                        <b>Nhập Thông Tin Lấy Mẫu Thông Báo Đóng Gói</b>.
                    </div>
                @elseif (!$canUpdatePo)
                    <div class="alert alert-info py-2">
                        <i class="fas fa-info-circle"></i> Bạn không có quyền <b>Nhập Số PO Thông Báo Đóng Gói</b> nên
                        cột <b>Số PO</b> chỉ để xem.
                    </div>
                @elseif (!$canUpdateSampling)
                    <div class="alert alert-info py-2">
                        <i class="fas fa-info-circle"></i> Bạn không có quyền
                        <b>Nhập Thông Tin Lấy Mẫu Thông Báo Đóng Gói</b> nên chỉ nhập được cột <b>Số PO</b>.
                    </div>
                @endif

                <div class="card card-success card-outline card-outline-tabs">
                    <div class="card-header p-0 border-bottom-0">
                        <ul class="nav nav-tabs" id="packagingTabs" role="tablist">
                            <li class="nav-item">
                                <a class="nav-link active font-weight-bold" id="tab-eu-tab" data-toggle="pill"
                                    href="#tab-eu" role="tab" aria-controls="tab-eu" aria-selected="true">
                                    <i class="fas fa-globe-europe text-primary"></i> Sản Phẩm Châu Âu
                                    <span class="badge badge-primary ml-1">{{ $euDatas->count() }}</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link font-weight-bold" id="tab-non-eu-tab" data-toggle="pill"
                                    href="#tab-non-eu" role="tab" aria-controls="tab-non-eu" aria-selected="false">
                                    <i class="fas fa-globe-asia text-success"></i> Sản Phẩm Ngoài Châu Âu
                                    <span class="badge badge-success ml-1">{{ $nonEuDatas->count() }}</span>
                                </a>
                            </li>
                        </ul>
                    </div>
                    <div class="card-body">
                        <div class="tab-content" id="packagingTabsContent">
                            <div class="tab-pane fade show active" id="tab-eu" role="tabpanel"
                                aria-labelledby="tab-eu-tab">
                                @include('pages.plan.packaging_notification.dataTable', [
                                    'datas' => $euDatas,
                                    'tableId' => 'pkg_table_eu',
                                    'emptyText' => 'Không có lô sản phẩm Châu Âu nào trong kế hoạch này.',
                                ])
                            </div>
                            <div class="tab-pane fade" id="tab-non-eu" role="tabpanel"
                                aria-labelledby="tab-non-eu-tab">
                                @include('pages.plan.packaging_notification.dataTable', [
                                    'datas' => $nonEuDatas,
                                    'tableId' => 'pkg_table_non_eu',
                                    'emptyText' => 'Không có lô sản phẩm ngoài Châu Âu nào trong kế hoạch này.',
                                ])
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>

    @if ($canAdd)
        {{-- Lô nằm ngoài quy tắc tự động (không chia lô và thị trường nội địa) không được
             sinh sẵn khi gửi kế hoạch, nhưng vẫn có trường hợp cần thông báo đóng gói nên
             cho người dùng tự chọn thêm. --}}
        <div class="modal fade" id="pkgAddModal" tabindex="-1" role="dialog">
            <div class="modal-dialog pkg-add-modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header bg-success">
                        <h5 class="modal-title">
                            <i class="fas fa-plus"></i> Tạo Thông Báo Đóng Gói Cho Lô Khác
                        </h5>
                        <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                    </div>
                    <div class="modal-body">
                        <p class="text-muted mb-2">
                            Danh sách các lô của <b>{{ $plan->name }}</b> chưa có thông báo đóng gói vì không thuộc
                            quy tắc tự động (không chia lô và thuộc thị trường nội địa).
                        </p>
                        <div class="form-row align-items-end mb-2">
                            <div class="col-md-5">
                                <input type="text" class="form-control form-control-sm" id="pkgAddKeyword"
                                    placeholder="Số lô / mã TP / mã BTP / tên SP...">
                            </div>
                            <div class="col-md-7">
                                <button type="button" class="btn btn-sm btn-primary" id="pkgAddSearch">
                                    <i class="fas fa-search"></i> Tìm
                                </button>
                                <span class="ml-2 text-muted" id="pkgAddCount"></span>
                            </div>
                        </div>
                        <div style="max-height: 70vh; overflow: auto;">
                            <table class="table table-bordered table-sm table-hover mb-0" id="pkgAddTable">
                                <thead class="text-center bg-light" style="position: sticky; top: 0; z-index: 2;">
                                    <tr>
                                        <th style="width: 40px;">
                                            <input type="checkbox" id="pkgAddCheckAll">
                                        </th>
                                        <th style="width: 90px;">Số Lô</th>
                                        <th>Tên Sản Phẩm</th>
                                        <th style="width: 130px;">Mã TP</th>
                                        <th style="width: 160px;">Quy Cách</th>
                                        <th style="width: 110px;">Thị Trường</th>
                                        <th style="width: 100px;">Ngày DK</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-sm btn-secondary" data-dismiss="modal">Đóng</button>
                        <button type="button" class="btn btn-sm btn-success" id="pkgAddSubmit">
                            <i class="fas fa-save"></i> Tạo Thông Báo
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Lịch sử nhập liệu của một lô (Số PO, thông tin lấy mẫu, lý do), xem được kể cả
         khi không có quyền nhập - giống nút Lịch Sử của trang Theo Dõi Hồ Sơ KCS. --}}
    <div class="modal fade" id="pkgHistoryModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-xl" role="document" style="max-width: 90%;">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-history text-primary"></i> Lịch Sử Nhập Liệu - Lô
                        <span id="pkgHistoryBatch"></span>
                    </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body" id="pkgHistoryBody"></div>
            </div>
        </div>
    </div>

    <style>
        /* Modal chọn lô thêm tay: rộng 90% màn hình để thấy được nhiều cột cùng lúc */
        .pkg-add-modal-dialog {
            max-width: 90vw;
            width: 90vw;
        }

        /* border-collapse: collapse (mặc định của Bootstrap .table) làm nền của ô ghim
           (position: sticky) không phủ kín được, để lộ chữ của cột đã cuộn qua đè lên -
           đúng thứ bạn thấy khi kéo bảng sang phải. Đổi sang separate để mỗi ô có nền
           riêng, sticky mới che kín; border-spacing: 0 để không hở viền giữa các ô. */
        .pkg-table {
            border-collapse: separate;
            border-spacing: 0;
        }

        /* Cỡ chữ theo lưới Kế Hoạch Sản Xuất Tháng, thu nhỏ chút vì bảng này có
           thêm 6 cột nhập nên rộng hơn */
        .pkg-table th,
        .pkg-table td {
            font-size: 14px;
            white-space: nowrap;
            vertical-align: middle;
        }

        .pkg-table .form-control-sm {
            font-size: 14px;
            padding: 4px 6px;
            height: auto;
        }

        /* Mẫu Sơ Cấp/Thứ Cấp/Lý Do là ô nhiều dòng (VD "Đầu lô: ... / Cuối lô: ...");
           chỉ cho giãn theo chiều dọc để không phá layout ngang của bảng */
        .pkg-table textarea.pkg-input {
            resize: vertical;
            min-height: 60px;
            white-space: pre-wrap;
        }

        /* table-striped tô nền lẻ/chẵn, các ô ghim phải tô lại nếu không sẽ
           trong suốt và đè lên chữ khi cuộn ngang. PHẢI dùng màu đặc (không alpha):
           rgba(0,0,0,.05) chỉ đặc 5%, gần như trong suốt nên chữ của cột đã cuộn qua vẫn
           lộ ra đè lên - đúng lỗi "nền trong suốt" nhìn thấy khi kéo bảng sang phải. */
        .pkg-table tbody tr:nth-of-type(odd) .pkg-sticky {
            background-color: #f2f2f2;
        }

        .pkg-table tbody tr:nth-of-type(even) .pkg-sticky {
            background-color: #fff;
        }

        /* Giữ các cột định danh khi cuộn ngang: phần nhập nằm khá xa bên phải */
        .pkg-table .pkg-sticky {
            position: sticky;
            background-color: #fff;
            z-index: 2;
        }

        .pkg-table thead .pkg-sticky {
            z-index: 4;
            background-color: #f8f9fa;
        }

        .pkg-table .pkg-sticky-1 {
            left: 0;
        }

        .pkg-table .pkg-sticky-2 {
            left: 50px;
        }

        .pkg-table .pkg-sticky-3 {
            left: 140px;
            box-shadow: 2px 0 3px -1px rgba(0, 0, 0, .2);
            white-space: normal;
        }

        /* Phải đủ "nặng" bằng luật kẻ sọc ở trên (cùng 3 class + 2 thẻ) và đứng sau nó,
           nếu không dòng lẻ sẽ mất hiệu ứng hover ở các ô ghim */
        .pkg-table tbody tr:hover .pkg-sticky {
            background-color: #f2f2f2;
        }

        .pkg-table .pkg-saving {
            background-color: #fff3cd;
        }
    </style>

    {{-- Layout chung không nạp sẵn toastr (dùng để báo lưu/xoá thành công hay lỗi) nên
         trang này tự nạp riêng. jQuery của layout chung nằm ở cuối <body>, sau cả
         mainContent, nên nạp toastr.min.js bằng thẻ <script> thường ở đây sẽ chạy trước
         khi jQuery tồn tại - UMD wrapper của toastr chụp lại $ = undefined và vỡ ngay khi
         gọi (ví dụ toastr.success). Dùng $.getScript để nạp sau khi DOMContentLoaded chắc
         chắn đã có jQuery. --}}
    <link rel="stylesheet" href="{{ asset('dataTable/plugins/toastr/toastr.min.css') }}">

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            $.getScript('{{ asset('dataTable/plugins/toastr/toastr.min.js') }}');

            const saveUrl = '{{ route('pages.plan.packaging_notification.save') }}';

            // Layout chung đặt body { overflow-y: hidden } nên mỗi bảng phải tự cuộn.
            // Tab đang ẩn không đo được chiều cao, nên tính lại mỗi lần đổi tab.
            function fitBoxHeight(id) {
                const box = document.getElementById(id);

                if (!box || box.offsetParent === null) {
                    return;
                }

                const footer = document.querySelector('.main-footer');
                const footerHeight = footer && footer.offsetParent !== null ?
                    footer.getBoundingClientRect().height :
                    0;

                const available = window.innerHeight - box.getBoundingClientRect().top - footerHeight - 24;
                box.style.maxHeight = Math.max(available, 240) + 'px';
            }

            function fitTableHeight() {
                fitBoxHeight('pkg_table_eu_scroll');
                fitBoxHeight('pkg_table_non_eu_scroll');
            }

            fitTableHeight();
            window.addEventListener('resize', fitTableHeight);
            $('#tab-eu-tab, #tab-non-eu-tab').on('shown.bs.tab', fitTableHeight);
            $(document).on('collapsed.lte.pushmenu shown.lte.pushmenu', fitTableHeight);

            // Lưu cả dòng mỗi khi một ô đổi giá trị, giống trang Theo Dõi Hồ Sơ KCS:
            // người dùng không phải bấm nút lưu riêng cho từng lô.
            $('.pkg-table').on('change', '.pkg-input', function() {
                const $target = $(this);
                const $row = $target.closest('tr');
                const payload = {
                    _token: '{{ csrf_token() }}',
                    plan_master_id: $row.data('plan-master-id')
                };

                $row.find('.pkg-input').each(function() {
                    // Checkbox không gửi giá trị "chưa tick" theo form thường - phải tự quy
                    // ước 1/0, nếu không server sẽ không nhận được trạng thái bỏ tick
                    payload[this.name] = this.type === 'checkbox' ? (this.checked ? '1' : '0') : this.value;
                });

                $row.find('.pkg-input').addClass('pkg-saving');

                $.ajax({
                    url: saveUrl,
                    method: 'POST',
                    data: payload,
                    success: function(res) {
                        $row.find('.pkg-input').removeClass('pkg-saving');

                        if (!res.success) {
                            toastr.error(res.message || 'Không lưu được');
                            return;
                        }

                        toastr.success('Đã lưu lô ' + $row.data('batch'));
                    },
                    error: function(xhr) {
                        $row.find('.pkg-input').removeClass('pkg-saving');

                        // Server từ chối (VD chưa có Số PO) - trả tích của checkbox về trạng
                        // thái cũ, không thì ô hiện đã tích trong khi DB vẫn chưa lưu
                        if ($target.is('.pkg-confirm-check')) {
                            $target.prop('checked', !$target.prop('checked'));
                        }

                        const message = xhr.responseJSON && xhr.responseJSON.message ?
                            xhr.responseJSON.message :
                            'Lỗi khi lưu dữ liệu';
                        toastr.error(message);
                    }
                });
            });

            const historyUrl = '{{ route('pages.plan.packaging_notification.history') }}';

            $('.pkg-table').on('click', '.pkg-history', function() {
                const $row = $(this).closest('tr');

                $('#pkgHistoryBatch').text($row.data('batch'));
                $('#pkgHistoryBody').html('<div class="text-center p-4">Đang tải dữ liệu...</div>');
                $('#pkgHistoryModal').modal('show');

                $.get(historyUrl, {
                    plan_master_id: $row.data('plan-master-id')
                }, function(res) {
                    $('#pkgHistoryBody').html(res.html);
                }).fail(function() {
                    $('#pkgHistoryBody').html(
                        '<div class="text-center text-danger p-3">Lỗi khi tải lịch sử</div>');
                });
            });

            @if ($canAdd)
                const candidatesUrl = '{{ route('pages.plan.packaging_notification.candidates') }}';
                const storeUrl = '{{ route('pages.plan.packaging_notification.store') }}';
                const destroyUrl = '{{ route('pages.plan.packaging_notification.destroy') }}';
                const planListId = {{ $plan->id }};

                function loadCandidates() {
                    const $body = $('#pkgAddTable tbody');

                    $body.html(
                        '<tr><td colspan="7" class="text-center text-muted py-3">Đang tải...</td></tr>');
                    $('#pkgAddCheckAll').prop('checked', false);

                    $.get(candidatesUrl, {
                        plan_list_id: planListId,
                        keyword: $('#pkgAddKeyword').val()
                    }, function(res) {
                        const datas = (res && res.datas) || [];

                        $('#pkgAddCount').text(datas.length + ' lô');

                        if (!datas.length) {
                            $body.html(
                                '<tr><td colspan="7" class="text-center text-muted py-3">Không còn lô nào để thêm.</td></tr>'
                            );
                            return;
                        }

                        // Ghép chuỗi qua thẻ text() của jQuery để tên sản phẩm có ký tự đặc biệt
                        // không phá vỡ HTML
                        $body.empty();

                        datas.forEach(function(item) {
                            const $tr = $('<tr>');
                            const date = item.expected_date ?
                                item.expected_date.substring(0, 10).split('-').reverse().join('/') :
                                '';

                            $('<td class="text-center">').append(
                                $('<input type="checkbox" class="pkg-add-check">').val(item.id)
                            ).appendTo($tr);
                            $('<td class="text-center font-weight-bold">').text(item.batch || '').appendTo($tr);
                            $('<td>').text(item.finished_product_name || '').appendTo($tr);
                            $('<td class="text-center">').text(item.finished_product_code || '').appendTo($tr);
                            $('<td>').text(item.specification || '').appendTo($tr);
                            $('<td class="text-center">').text(item.market || '').appendTo($tr);
                            $('<td class="text-center">').text(date).appendTo($tr);

                            $body.append($tr);
                        });
                    }).fail(function() {
                        $body.html(
                            '<tr><td colspan="7" class="text-center text-danger py-3">Không tải được danh sách lô.</td></tr>'
                        );
                    });
                }

                $('#pkgAddModal').on('shown.bs.modal', loadCandidates);
                $('#pkgAddSearch').on('click', loadCandidates);
                $('#pkgAddKeyword').on('keydown', function(e) {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        loadCandidates();
                    }
                });

                $('#pkgAddCheckAll').on('change', function() {
                    $('#pkgAddTable .pkg-add-check').prop('checked', this.checked);
                });

                $('#pkgAddSubmit').on('click', function() {
                    const ids = $('#pkgAddTable .pkg-add-check:checked').map(function() {
                        return this.value;
                    }).get();

                    if (!ids.length) {
                        toastr.warning('Chưa chọn lô nào.');
                        return;
                    }

                    const $btn = $(this).prop('disabled', true);

                    $.post(storeUrl, {
                        _token: '{{ csrf_token() }}',
                        plan_list_id: planListId,
                        plan_master_ids: ids
                    }, function(res) {
                        toastr.success(res.message || 'Đã tạo thông báo.');
                        // Tải lại để lưới lấy đúng thứ tự và thông tin công đoạn của lô mới
                        window.location.reload();
                    }).fail(function(xhr) {
                        $btn.prop('disabled', false);
                        const message = xhr.responseJSON && xhr.responseJSON.message ?
                            xhr.responseJSON.message :
                            'Không tạo được thông báo đóng gói';
                        toastr.error(message);
                    });
                });

                $('.pkg-table').on('click', '.pkg-remove', function() {
                    const $row = $(this).closest('tr');

                    if (!confirm('Gỡ thông báo đóng gói của lô ' + $row.data('batch') +
                            '? Dữ liệu đã nhập của lô này sẽ bị xoá.')) {
                        return;
                    }

                    $.post(destroyUrl, {
                        _token: '{{ csrf_token() }}',
                        plan_master_id: $row.data('plan-master-id')
                    }, function() {
                        $row.remove();
                        toastr.success('Đã gỡ thông báo đóng gói.');
                    }).fail(function(xhr) {
                        const message = xhr.responseJSON && xhr.responseJSON.message ?
                            xhr.responseJSON.message :
                            'Không gỡ được thông báo đóng gói';
                        toastr.error(message);
                    });
                });
            @endif
        });
    </script>
@endsection
