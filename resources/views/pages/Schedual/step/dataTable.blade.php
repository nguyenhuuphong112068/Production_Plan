<style>
    /* Màu trạng thái step */
    .step-pending .bs-stepper-circle {
        background-color: #6c757d !important;
        /* Xám */
        color: white;
    }

    .step-scheduled .bs-stepper-circle {
        background-color: #28a745 !important;
        /* Xanh lá */
        color: white;
    }

    .step-finished .bs-stepper-circle {
        background-color: #007bff !important;
        /* Xanh dương */
        color: white;
    }

    .step-delay .bs-stepper-circle {
        background-color: #dc3545 !important;
        /* Đỏ */
        color: white;
    }

    .step-warning .bs-stepper-circle {
        background-color: #e39235 !important;
        /* Cam cảnh báo */
        color: white;
    }

    /* Custom badge warning colors */
    .badge-warning-light {
        background-color: #ffeeba !important;
        color: #856404 !important;
        border: 1px solid #ffeeba;
    }
    .badge-warning-dark {
        background-color: #e39235 !important;
        color: white !important;
    }

    /* Mũi tên pointer */
    .step.step-pointer .bs-stepper-circle::before {
        content: "";
        position: absolute;
        top: 0%;
        left: 50%;
        transform: translateX(-50%);
        width: 0;
        height: 0;
        border-left: 14px solid transparent;
        border-right: 14px solid transparent;
        border-top: 18px solid #007bff;
        filter: drop-shadow(0 2px 2px rgba(0, 0, 0, 0.4));
    }

    /* Style riêng cho dòng tổng kết 5-6-7 */
    .timeline-info div {
        font-size: 14px;
        margin-bottom: 2px;
    }

    .timeline-info .text-success {
        font-weight: 600;
    }

    .waiting-label {
        width: 10%;
        border-top: 2px solid;
        margin-top: 14px;
        font-size: 16px;
        color: rgb(0, 55, 255);
        font-weight: 500;
        text-align: center;
        white-space: nowrap;
        position: relative;
    }

    .waiting-label::before {
        content: "Thời Gian Biệt trữ";
        position: absolute;
        top: -24px;
        /* đẩy chữ lên trên border */
        left: 50%;
        transform: translateX(-50%);
        padding: 0 6px;
        font-size: 13px;
        font-weight: bold;
    }
</style>

<link rel="stylesheet" href="{{ asset('libs/bs-stepper/css/bs-stepper.min.css') }}">

<div class="content-wrapper mt-5">
    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="card">

                        <div class="card-body">
                            <div class="row">
                                <div class="col-12">
                                    @php
                                        use Carbon\Carbon;
                                        $defaultFrom = Carbon::now()->subMonth()->toDateString();
                                        $defaultTo = Carbon::now()->addMonth()->toDateString();
                                        $isFilterOverdue = request('filter_overdue') == '1';

                                        $wipDatas = collect();
                                        foreach ($datas as $plan_master_id => $stages) {
                                            $weighingFinished = false;
                                            $packagingFinished = false;
                                            foreach ($stages as $s) {
                                                if (in_array($s->stage_code, [1, 2]) && $s->finished == 1) {
                                                    $weighingFinished = true;
                                                }
                                                if ($s->stage_code >= 7 && $s->finished == 1) {
                                                    $packagingFinished = true;
                                                }
                                            }
                                            if ($weighingFinished && !$packagingFinished) {
                                                $wipDatas->put($plan_master_id, $stages);
                                            }
                                        }
                                    @endphp

                                    <form id="dateFilterForm" method="GET"
                                        action="{{ route('pages.Schedual.step.list') }}"
                                        class="d-flex flex-wrap gap-3 align-items-center w-100">
                                        <input type="hidden" name="filter_overdue" id="filter_overdue_input"
                                            value="{{ request('filter_overdue', '0') }}">

                                        <div class="form-group d-flex align-items-center mb-0">
                                            <label for="from_date" class="mr-2 mb-0 text-nowrap">From:</label>
                                            <input type="date" id="from_date" name="from_date"
                                                value="{{ request('from_date') ?? $defaultFrom }}" class="form-control"
                                                {{ $isFilterOverdue ? 'disabled' : '' }} />
                                        </div>

                                        <div class="form-group d-flex align-items-center mb-0">
                                            <label for="to_date" class="mr-2 mb-0 text-nowrap">To:</label>
                                            <input type="date" id="to_date" name="to_date"
                                                value="{{ request('to_date') ?? $defaultTo }}" class="form-control"
                                                {{ $isFilterOverdue ? 'disabled' : '' }} />
                                        </div>

                                        <button type="button" id="btnFilterOverdue"
                                            class="btn {{ $isFilterOverdue ? 'btn-danger' : 'btn-warning' }} mb-0 ml-auto" style="box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                                            @if ($isFilterOverdue)
                                                <i class="fas fa-times mr-1"></i> Bỏ Lọc Quá Hạn
                                            @else
                                                <i class="fas fa-exclamation-triangle mr-1"></i> Lọc Quá Hạn Biệt Trữ
                                            @endif
                                        </button>
                                    </form>
                                </div>
                            </div>

                            <div class="row mt-4">
                                <div class="col-12">
                                    <ul class="nav nav-tabs" id="schedualTab" role="tablist">
                                        <li class="nav-item">
                                            <a class="nav-link active" id="production-progress-tab" data-toggle="tab" href="#production-progress" role="tab" aria-controls="production-progress" aria-selected="true" style="font-size: 16px; font-weight: 600;">
                                                <i class="fas fa-tasks mr-2 text-primary"></i>Tiến Trình Sản Xuất
                                                <span class="badge badge-primary ml-1">{{ $datas->count() }}</span>
                                            </a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link" id="wip-step-tab" data-toggle="tab" href="#wip-step" role="tab" aria-controls="wip-step" aria-selected="false" style="font-size: 16px; font-weight: 600;">
                                                <i class="fas fa-hourglass-half mr-2 text-warning"></i>Bán Thành Phẩm Dở Dang
                                                <span class="badge badge-warning ml-1">{{ $wipDatas->count() }}</span>
                                            </a>
                                        </li>
                                    </ul>
                                </div>
                            </div>

                            <div class="tab-content mt-3" id="schedualTabContent">
                                <div class="tab-pane fade show active" id="production-progress" role="tabpanel" aria-labelledby="production-progress-tab">
                                    <div class="table-responsive shadow-sm rounded" style="overflow-x: auto; background: #fff;">
                                        @include('pages.Schedual.step.tableTemplate', ['tableId' => 'data_table_Schedual_step', 'tableData' => $datas])
                                    </div>
                                </div>
                                <div class="tab-pane fade" id="wip-step" role="tabpanel" aria-labelledby="wip-step-tab">
                                    <div class="table-responsive shadow-sm rounded" style="overflow-x: auto; background: #fff;">
                                        @include('pages.Schedual.step.tableTemplate', ['tableId' => 'data_table_wip_step', 'tableData' => $wipDatas])
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
    </section>
</div>

<script src="{{ asset('js/vendor/jquery-1.12.4.min.js') }}"></script>
<script src="{{ asset('js/popper.min.js') }}"></script>
<script src="{{ asset('js/bootstrap.min.js') }}"></script>
<script src="{{ asset('libs/bs-stepper/js/bs-stepper.min.js') }}"></script>

<script>
    $(document).ready(function() {
        document.body.style.overflowY = "auto";

        $('#data_table_Schedual_step').DataTable({
            paging: true,
            lengthChange: true,
            searching: true,
            ordering: true,
            info: true,
            autoWidth: false,
            pageLength: 10,
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
        });

        $('#data_table_wip_step').DataTable({
            paging: true,
            lengthChange: true,
            searching: true,
            ordering: true,
            info: true,
            autoWidth: false,
            pageLength: 10,
            lengthMenu: [
                [10, 25, 50, 100, -1],
                [10, 25, 50, 100, "Tất cả"]
            ],
            order: [[5, 'asc']], // Mặc định sắp xếp tăng dần theo cột "Số ngày còn HBT" (cột index 5)
            language: {
                search: "Tìm kiếm:",
                lengthMenu: "Hiển thị _MENU_ dòng",
                info: "Hiển thị _START_ đến _END_ của _TOTAL_ dòng",
                paginate: {
                    previous: "Trước",
                    next: "Sau"
                }
            },
        });

        // Tự động căn chỉnh lại độ rộng cột khi chuyển Tab để tránh vỡ giao diện
        $('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
            $($.fn.dataTable.tables(true)).DataTable().columns.adjust();
        });

        $('#btnFilterOverdue').on('click', function() {
            let input = $('#filter_overdue_input');
            if (input.val() == '1') {
                input.val('0');
            } else {
                input.val('1');
                // Bỏ disable các input date để form có thể gửi đi, dù backend sẽ ignore nó
                $('#from_date').prop('disabled', false);
                $('#to_date').prop('disabled', false);
            }
            $('#dateFilterForm').submit();
        });

    })

    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.bs-stepper').forEach(stepperEl => {
            new Stepper(stepperEl, {
                linear: false,
                animation: true
            });
        });
    });

    const form = document.getElementById('dateFilterForm');
    const fromInput = document.getElementById('from_date');
    const toInput = document.getElementById('to_date');

    [fromInput, toInput].forEach(input => {
        input.addEventListener('input', function() {
            form.submit();
        });
    });
</script>
