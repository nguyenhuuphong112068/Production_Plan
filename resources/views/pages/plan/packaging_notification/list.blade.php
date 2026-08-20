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
                </form>

                @unless ($canUpdate)
                    <div class="alert alert-warning py-2">
                        <i class="fas fa-lock"></i> Bạn chỉ có quyền xem. Liên hệ quản trị để được cấp quyền
                        <b>Cập Nhật Thông Báo Đóng Gói</b>.
                    </div>
                @endunless

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

    <style>
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

        /* table-striped tô nền lẻ/chẵn, các ô ghim phải tô lại nếu không sẽ
           trong suốt và đè lên chữ khi cuộn ngang */
        .pkg-table tbody tr:nth-of-type(odd) .pkg-sticky {
            background-color: rgba(0, 0, 0, .05);
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

    <script>
        document.addEventListener('DOMContentLoaded', function() {
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
                const $row = $(this).closest('tr');
                const payload = {
                    _token: '{{ csrf_token() }}',
                    plan_master_id: $row.data('plan-master-id')
                };

                $row.find('.pkg-input').each(function() {
                    payload[this.name] = this.value;
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
                        const message = xhr.responseJSON && xhr.responseJSON.message ?
                            xhr.responseJSON.message :
                            'Lỗi khi lưu dữ liệu';
                        toastr.error(message);
                    }
                });
            });
        });
    </script>
@endsection
