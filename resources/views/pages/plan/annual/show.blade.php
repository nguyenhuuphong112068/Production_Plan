@extends ('layout.master')

@section('topNAV')
    @include('layout.topNAV')
@endsection

@section('leftNAV')
    @include('layout.leftNAV')
@endsection

@section('mainContent')
    <link href="{{ asset('libs/handsontable/handsontable.full.min.css') }}" rel="stylesheet" media="screen">
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

        /* Giao diện chuyên nghiệp cho tiêu đề */
        .handsontable th {
            background-color: #f8f9fa !important;
            color: #2c3e50 !important;
            font-weight: 600 !important;
            font-size: 13px !important;
            text-align: center !important;
            vertical-align: middle !important;
            border-right: 1px solid #dee2e6 !important;
            border-bottom: 1px solid #dee2e6 !important;
        }

        /* Hàng trên cùng (Tên cột) */
        .handsontable thead tr:first-child th {
            background-color: #e3f2fd !important;
            /* Xanh nhạt chuyên nghiệp */
            color: #0d47a1 !important;
            /* Chữ xanh đậm */
            padding: 8px 4px !important;
            font-size: 14px !important;
        }

        /* Hàng thứ 2 (Chỉ số cột A, B, C) */
        .handsontable thead tr:last-child th {
            background-color: #f1f3f5 !important;
            color: #6c757d !important;
            font-size: 12px !important;
            font-style: italic;
            padding: 4px !important;
            border-bottom: 2px solid #adb5bd !important;
        }

        /* Highlight khi click vào cột/hàng */
        .handsontable th.ht__active_highlight {
            background-color: #d0ebff !important;
            color: #0056b3 !important;
        }

        /* Cột số thứ tự hàng bên trái */
        .handsontable .ht_clone_top_left_corner th,
        .handsontable .ht_clone_left th {
            background-color: #f8f9fa !important;
            color: #495057 !important;
            font-weight: 600 !important;
            border-right: 2px solid #adb5bd !important;
        }

        /* Thêm chữ STT vào ô góc trên bên trái của cột chỉ số hàng */
        .handsontable .ht_clone_top_left_corner thead tr:first-child th:nth-child(1) .relative::after {
            content: 'STT';
            display: block;
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            left: 0;
            right: 0;
            text-align: center;
            font-weight: bold;
            color: #0d47a1;
        }
    </style>
    <div class="content-wrapper">
        <div class="card" style="min-height: 100vh">
            <div class="card-header mt-0">
                <h3 class="card-title">Bảng Tính Kế Hoạch Năm {{ $plan->year }}</h3>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <button class="btn btn-success btn-add mb-2" onclick="saveData()" style="width: 155px;">
                            <i class="fas fa-save"></i> Lưu thay đổi
                        </button>
                        <button class="btn btn-primary btn-add mb-2 ml-2" data-toggle="modal"
                            data-target="#addProductsModal">
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
                                                <input type="checkbox" name="selected_products[]"
                                                    value="{{ $fpc->id }}" class="product-checkbox">
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
    <script src="{{ asset('libs/handsontable/handsontable.full.min.js') }}"></script>
    <script src="{{ asset('libs/hyperformula/hyperformula.full.min.js') }}"></script>
    <script>
        window.onerror = function(msg, url, lineNo, columnNo, error) {
            alert('JS Error: ' + msg + '\nLine: ' + lineNo);
            return false;
        };
        const container = document.getElementById('hot-app');
        let hot;

        // Inject data from controller
        const data = @json($hotData);

        // Helper to convert index to Excel column letter (0 -> A, 1 -> B...)
        function getExcelColumnName(colIndex) {
            let letter = '';
            while (colIndex >= 0) {
                letter = String.fromCharCode((colIndex % 26) + 65) + letter;
                colIndex = Math.floor(colIndex / 26) - 1;
            }
            return letter;
        }

        // Base headers and columns
        const colHeaders = [
            'Hết SĐK', 'Phân loại', 'Khách', 'Thị trường', 'Dạng bào chế', 'Mã BTP', 'Mã TP', 'Sản phẩm', 'Hạn dùng',
            'Quy cách', 'Cỡ lô', 'BQ bán / Tháng (hộp)', 'BQ bán / tháng (viên)'
        ];

        const columns = [{
                data: 'registration_expiry',
                type: 'date',
                dateFormat: 'YYYY-MM-DD',
                readOnly: false
            },
            {
                data: 'classification',
                readOnly: false
            },
            {
                data: 'customer_type',
                readOnly: false
            },
            {
                data: 'market',
                readOnly: true
            },
            {
                data: 'dosage',
                readOnly: true
            },
            {
                data: 'intermediate_code',
                readOnly: true
            },
            {
                data: 'finished_product_code',
                readOnly: true
            },
            {
                data: 'product_name',
                readOnly: true
            },
            {
                data: 'shelf_life',
                type: 'numeric',
                readOnly: false
            },
            {
                data: 'packaging_spec',
                type: 'numeric',
                readOnly: false
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
                data: 'avg_sales_box',
                type: 'numeric',
                numericFormat: {
                    pattern: '0,0'
                },
                readOnly: false
            },
            {
                data: 'avg_sales_pill',
                type: 'numeric',
                numericFormat: {
                    pattern: '0,0'
                },
                readOnly: false
            }
        ];

        // Generate 12 months dynamically
        const planYear = {{ $plan->year }};
        for (let m = 1; m <= 12; m++) {
            const monthStr = m.toString().padStart(2, '0');
            colHeaders.push(`KH T${monthStr}/${planYear} (Lô)`);
            columns.push({
                data: `m${m}_batches`,
                type: 'numeric',
                numericFormat: {
                    pattern: '0,0'
                }
            });

            colHeaders.push(`Tồn kho T${monthStr}/${planYear}`);
            columns.push({
                data: `m${m}_expected_inventory`,
                readOnly: true
            });
        }

        // Map headers to include Excel letters
        const finalColHeaders = colHeaders.map((name, index) => {
            return name;
        });

        // Map headers to Excel letters for the second row
        const excelLetters = colHeaders.map((_, index) => {
            return getExcelColumnName(index);
        });

        hot = new Handsontable(container, {
            data: data,
            nestedHeaders: [
                finalColHeaders, // Hàng trên: Tên cột
                excelLetters // Hàng dưới: Chỉ số A, B, C...
            ],
            columns: columns,
            rowHeaders: true,
            height: 'auto',
            licenseKey: 'non-commercial-and-evaluation',
            fixedColumnsStart: 8,
            contextMenu: true,
            manualColumnResize: true,
            manualColumnFreeze: true,
            filters: true,
            dropdownMenu: true,
            formulas: {
                engine: HyperFormula,
            },
            afterChange: function(changes, source) {
                if (source === 'loadData') {
                    return;
                }
                // Add calculation logic here when KH Lô changes
            }
        });

        function saveData() {
            const dataToSave = hot.getSourceData();
            const btn = document.querySelector('.btn-success');
            const originalText = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang lưu...';
            btn.disabled = true;

            fetch('{{ route('pages.plan.annual.update_monthly_data') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        plan_id: {{ $plan->id }},
                        year: {{ $plan->year }},
                        data: dataToSave
                    })
                })
                .then(response => response.json())
                .then(data => {
                    btn.innerHTML = originalText;
                    btn.disabled = false;
                    if (data.success) {
                        alert(data.message);
                    } else {
                        alert('Lỗi: ' + data.message);
                    }
                })
                .catch(error => {
                    btn.innerHTML = originalText;
                    btn.disabled = false;
                    alert('Lỗi kết nối tới server.');
                    console.error(error);
                });
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
                    var allChecked = document.querySelectorAll('.product-checkbox:checked')
                        .length === document.querySelectorAll('.product-checkbox').length;
                    document.getElementById('selectAllProducts').checked = allChecked;
                });
            });
        });
    </script>
@endsection
