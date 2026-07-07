@extends ('layout.master')

@section('topNAV')
    @include('layout.topNAV')
@endsection

@section('leftNAV')
    @include('layout.leftNAV')
@endsection

@section('custom_css')
    <link href="https://cdn.jsdelivr.net/npm/handsontable@12.1.2/dist/handsontable.full.min.css" rel="stylesheet"
        media="screen">
    <style>
        .handsontable-container {
            width: 100%;
            height: 70vh;
            overflow: hidden;
            margin-top: 20px;
        }

        .ht_master tr td {
            vertical-align: middle;
        }

        .warning-cell {
            background-color: #ffcccc !important;
        }
    </style>
@endsection

@section('mainContent')
    <div class="content-wrapper">
        <div class="container-fluid pd-x-0">
            <div class="d-sm-flex align-items-center justify-content-between mg-b-20 mg-lg-b-25 mg-xl-b-30">
                <div>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb breadcrumb-style1 mg-b-10">
                            <li class="breadcrumb-item"><a href="#">Kế Hoạch</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('pages.plan.annual.list') }}">Kế Hoạch Năm</a>
                            </li>
                            <li class="breadcrumb-item active" aria-current="page">Năm {{ $plan->year }}</li>
                        </ol>
                    </nav>
                    <h4 class="mg-b-0 tx-spacing--1">Bảng Tính Kế Hoạch Năm {{ $plan->year }}</h4>
                </div>
                <div class="d-none d-md-block">
                    <button class="btn btn-sm pd-x-15 btn-primary btn-uppercase mg-l-5" onclick="saveData()">
                        <i data-feather="save" class="wd-10 mg-r-5"></i> Lưu thay đổi
                    </button>
                </div>
            </div>

            <div class="row row-xs">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body p-0">
                            <div id="hot-app" class="handsontable-container"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('custom_js')
    <script src="https://cdn.jsdelivr.net/npm/handsontable@12.1.2/dist/handsontable.full.min.js"></script>
    <script>
        const container = document.getElementById('hot-app');
        let hot;

        // Inject data from controller
        const data = @json($hotData);

        hot = new Handsontable(container, {
            data: data,
            colHeaders: [
                'Tên sản phẩm', 'Phân loại', 'Khách hàng', 'Cỡ lô', 'BQ Bán/tháng', 'KH T05/2026 (Lô)',
                'Tồn kho dự kiến'
            ],
            columns: [{
                    data: 'product_name',
                    readOnly: true
                },
                {
                    data: 'classification',
                    readOnly: true
                },
                {
                    data: 'customer_type',
                    readOnly: true
                },
                {
                    data: 'batch_size',
                    type: 'numeric',
                    numericFormat: {
                        pattern: '0,0'
                    },
                    readOnly: true
                },
                {
                    data: 'avg_sales',
                    type: 'numeric',
                    numericFormat: {
                        pattern: '0,0'
                    },
                    readOnly: true
                },
                {
                    data: 'm5_batches',
                    type: 'numeric'
                },
                {
                    data: 'm5_expected_inventory',
                    readOnly: true
                }
            ],
            rowHeaders: true,
            height: 'auto',
            licenseKey: 'non-commercial-and-evaluation',
            fixedColumnsStart: 3,
            contextMenu: true,
            manualColumnResize: true,
            afterChange: function(changes, source) {
                if (source === 'loadData') {
                    return;
                }
                // Add calculation logic here when KH Lô changes
            }
        });

        function saveData() {
            // Collect changes and send via AJAX
            alert('Chức năng đang được xây dựng');
        }
    </script>
@endsection
