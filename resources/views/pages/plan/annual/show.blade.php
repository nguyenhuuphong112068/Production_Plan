@extends ('layout.master')

@section('topNAV')
    @include('layout.topNAV')
@endsection

@section('leftNAV')
    @include('layout.leftNAV')
@endsection

@section('mainContent')
    <link href="https://cdn.jsdelivr.net/npm/handsontable@12.1.2/dist/handsontable.full.min.css" rel="stylesheet" media="screen">
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
    <div class="content-wrapper">
        <div class="card" style="min-height: 100vh">
            <div class="card-header mt-4">
                <h3 class="card-title">Bảng Tính Kế Hoạch Năm {{ $plan->year }}</h3>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <button class="btn btn-success btn-add mb-2" onclick="saveData()" style="width: 155px;">
                            <i class="fas fa-save"></i> Lưu thay đổi
                        </button>
                        <button class="btn btn-primary btn-add mb-2 ml-2" data-toggle="modal" data-target="#addProductsModal">
                            <i class="fas fa-plus"></i> Thêm sản phẩm
                        </button>
                    </div>
                </div>

                @if (session('success'))
                    <div class="alert alert-success mt-2">{{ session('success') }}</div>
                @endif

                <div class="row row-xs">
                    <div class="col-12">
                        <div id="hot-app" class="handsontable-container mt-3"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Thêm Sản Phẩm -->
    <div class="modal fade" id="addProductsModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-xl" role="document" style="max-width: 90%;">
            <div class="modal-content">
                <form action="{{ route('pages.plan.annual.add_products', $plan->id) }}" method="POST">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Chọn Sản Phẩm Vào Kế Hoạch Năm</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body" style="max-height: 70vh; overflow-y: auto;">
                        <table class="table table-bordered table-striped" id="productsTable">
                            <thead style="position: sticky; top: 0; background: white; z-index: 10;">
                                <tr>
                                    <th class="text-center" style="width: 50px;">
                                        <input type="checkbox" id="selectAllProducts">
                                    </th>
                                    <th>Mã BTP</th>
                                    <th>Mã TP</th>
                                    <th>Tên Sản Phẩm</th>
                                    <th>Cỡ Lô</th>
                                    <th>Thị Trường</th>
                                    <th>Qui Cách</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($allFinishedProducts as $fpc)
                                    @if (!in_array($fpc->id, $existingProductIds))
                                        <tr>
                                            <td class="text-center">
                                                <input type="checkbox" name="selected_products[]" value="{{ $fpc->id }}" class="product-checkbox">
                                            </td>
                                            <td>{{ $fpc->intermediate_code }}</td>
                                            <td>{{ $fpc->finished_product_code }}</td>
                                            <td>{{ $fpc->productName?->name ?? $fpc->name }}</td>
                                            <td>{{ $fpc->batch_qty }} {{ $fpc->unit_batch_qty }}</td>
                                            <td>{{ $fpc->market }}</td>
                                            <td>{{ $fpc->specification }}</td>
                                        </tr>
                                    @endif
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Hủy</button>
                        <button type="submit" class="btn btn-primary">Xác nhận Thêm</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('script')
    <script src="https://cdn.jsdelivr.net/npm/handsontable@12.1.2/dist/handsontable.full.min.js"></script>
    <script>
        window.onerror = function(msg, url, lineNo, columnNo, error) {
            alert('JS Error: ' + msg + '\nLine: ' + lineNo);
            return false;
        };
        const container = document.getElementById('hot-app');
        let hot;

        // Inject data from controller
        const data = @json($hotData);

        // Base headers and columns
        const colHeaders = [
            'Mã BTP', 'Tên sản phẩm', 'Phân loại', 'Khách hàng', 'Cỡ lô', 'BQ Bán/tháng'
        ];
        
        const columns = [
            { data: 'intermediate_code', readOnly: true },
            { data: 'product_name', readOnly: true },
            { data: 'classification', readOnly: false },
            { data: 'customer_type', readOnly: false },
            { data: 'batch_size', type: 'numeric', numericFormat: { pattern: '0,0' }, readOnly: true },
            { data: 'avg_sales', type: 'numeric', numericFormat: { pattern: '0,0' }, readOnly: false }
        ];

        // Generate 12 months dynamically
        const planYear = {{ $plan->year }};
        for (let m = 1; m <= 12; m++) {
            const monthStr = m.toString().padStart(2, '0');
            colHeaders.push(`KH T${monthStr}/${planYear} (Lô)`);
            columns.push({
                data: `m${m}_batches`,
                type: 'numeric',
                numericFormat: { pattern: '0,0' }
            });

            colHeaders.push(`Tồn kho T${monthStr}/${planYear}`);
            columns.push({
                data: `m${m}_expected_inventory`,
                readOnly: true
            });
        }

        hot = new Handsontable(container, {
            data: data,
            colHeaders: colHeaders,
            columns: columns,
            rowHeaders: true,
            height: 'auto',
            licenseKey: 'non-commercial-and-evaluation',
            fixedColumnsStart: 4,
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

        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('selectAllProducts').addEventListener('change', function() {
                var isChecked = this.checked;
                var checkboxes = document.querySelectorAll('.product-checkbox');
                checkboxes.forEach(function(cb) {
                    cb.checked = isChecked;
                });
            });

            var checkboxes = document.querySelectorAll('.product-checkbox');
            checkboxes.forEach(function(cb) {
                cb.addEventListener('change', function() {
                    var allChecked = document.querySelectorAll('.product-checkbox:checked').length === document.querySelectorAll('.product-checkbox').length;
                    document.getElementById('selectAllProducts').checked = allChecked;
                });
            });
        });
    </script>
@endsection
