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

        /* Màu nền cho từng tháng (Màu nhạt pastel) */
        .month-bg-1 { background-color: #f0f8ff !important; } /* AliceBlue */
        .month-bg-2 { background-color: #fff5e6 !important; } /* FloralWhite */
        .month-bg-3 { background-color: #f0fff0 !important; } /* Honeydew */
        .month-bg-4 { background-color: #fff0f5 !important; } /* LavenderBlush */
        .month-bg-5 { background-color: #f5fffa !important; } /* MintCream */
        .month-bg-6 { background-color: #fdf5e6 !important; } /* OldLace */
        .month-bg-7 { background-color: #f4fce3 !important; } /* Light Lime */
        .month-bg-8 { background-color: #ffffe0 !important; } /* LightYellow */
        .month-bg-9 { background-color: #f0ffff !important; } /* Azure */
        .month-bg-10 { background-color: #fffafa !important; } /* Snow */
        .month-bg-11 { background-color: #f5f5dc !important; } /* Beige */
        .month-bg-12 { background-color: #faf0e6 !important; } /* Linen */

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
                    <div class="col-md-6">
                        <button class="btn btn-success btn-add mb-2" onclick="saveData()" style="width: 155px;">
                            <i class="fas fa-save"></i> Lưu thay đổi
                        </button>
                        <button class="btn btn-primary btn-add mb-2 ml-2" data-toggle="modal"
                            data-target="#addProductsModal">
                            <i class="fas fa-plus"></i> Thêm sản phẩm
                        </button>
                        <div class="btn-group mb-2 ml-2">
                            <button type="button" class="btn btn-info dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <i class="fas fa-chart-pie"></i> Phân bổ thiết bị
                            </button>
                            <div class="dropdown-menu">
                                @for ($m = 1; $m <= 12; $m++)
                                    <a class="dropdown-item btn-equipment-allocation" href="#" data-month="{{ $m }}">Tháng {{ str_pad($m, 2, '0', STR_PAD_LEFT) }}/{{ $plan->year }}</a>
                                @endfor
                            </div>
                        </div>
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
                    <div class="modal-body" style="max-height: 70vh; overflow-y: auto;" id="unassignedProductsContainer">
                        <div class="text-center py-5">
                            <i class="fas fa-spinner fa-spin fa-3x text-primary"></i>
                            <p class="mt-2">Đang tải dữ liệu...</p>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Hủy</button>
                        <button type="submit" class="btn btn-primary">Xác nhận Thêm</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Phân bổ thiết bị -->
    <div class="modal fade" id="equipmentAllocationModal" tabindex="-1" role="dialog"
        aria-labelledby="equipmentAllocationModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl" role="document" style="max-width: 90%;">
            <div class="modal-content">
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title" id="equipmentAllocationModalLabel"><i class="fas fa-chart-pie mr-2"></i>Phân
                        bổ thiết bị cho kế hoạch</h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <h5 id="equipmentAllocationPlanName" class="font-weight-bold text-center mb-2 text-primary"></h5>
                    <div class="d-flex justify-content-center align-items-center mb-4 flex-wrap">
                        <div class="custom-control custom-switch mr-4">
                            <input type="checkbox" class="custom-control-input" id="groupByLineSwitch">
                            <label class="custom-control-label font-weight-bold text-secondary" style="cursor: pointer;"
                                for="groupByLineSwitch">Thống kê theo dòng máy</label>
                        </div>
                        <div class="form-group mb-0 d-flex align-items-center">
                            <label for="stageCodeSelect" class="font-weight-bold text-secondary mb-0 mr-2">Công
                                đoạn:</label>
                            <select id="stageCodeSelect" class="form-control form-control-sm"
                                style="width: auto; min-width: 150px;">
                                <option value="all">Tất cả</option>
                                <option value="3">Pha chế</option>
                                <option value="4">Trộn hoàn tất</option>
                                <option value="5">Định hình</option>
                                <option value="6">Bao phim</option>
                                <option value="7" selected>Đóng gói</option>
                            </select>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered table-hover" id="equipmentAllocationTable">
                            <thead class="thead-light">
                                <tr>
                                    <th class="text-center align-middle" style="width: 10%;">Mã Thiết bị</th>
                                    <th class="text-center align-middle" style="width: 25%;">Tên Thiết bị</th>
                                    <th class="text-center align-middle" style="width: 20%;">Loại Thiết bị</th>
                                    <th class="text-center align-middle" colspan="2" style="width: 45%;">So Sánh</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Dữ liệu sẽ được load qua AJAX -->
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
    <script src="{{ asset('libs/handsontable/handsontable.full.min.js') }}"></script>
    <script>
        window.onerror = function(msg, url, lineNo, columnNo, error) {
            alert('JS Error: ' + msg + '\nLine: ' + lineNo);
            return false;
        };
        let hot;
        let changedRowIds = new Set();

        // Helper to convert index to Excel column letter (0 -> A, 1 -> B...)
        function getExcelColumnName(colIndex) {
            let letter = '';
            while (colIndex >= 0) {
                letter = String.fromCharCode((colIndex % 26) + 65) + letter;
                colIndex = Math.floor(colIndex / 26) - 1;
            }
            return letter;
        }

        document.addEventListener('DOMContentLoaded', function() {
            const container = document.getElementById('hot-app');
            // Inject data from controller
            const data = @json($hotData);

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
                readOnly: true
            }
        ];

        const topHeaders = [
            { label: 'Thông tin chung', colspan: 13 }
        ];

        // Generate 12 months dynamically
        const planYear = {{ $plan->year }};
        for (let m = 1; m <= 12; m++) {
            const monthStr = m.toString().padStart(2, '0');
            topHeaders.push({ label: `Tháng ${monthStr}/${planYear}`, colspan: 5 });

            colHeaders.push(`Số lô`);
            columns.push({
                data: `m${m}_batches`,
                type: 'numeric',
                numericFormat: {
                    pattern: '0,0'
                },
                className: `month-bg-${m}`
            });

            colHeaders.push(`Số lượng`);
            columns.push({
                data: `m${m}_planned_quantity`,
                readOnly: true,
                type: 'numeric',
                numericFormat: {
                    pattern: '0,0'
                },
                className: `month-bg-${m}`
            });

            colHeaders.push(`BTP dở dang`);
            columns.push({
                data: `m${m}_wip_inventory`,
                readOnly: true,
                type: 'numeric',
                numericFormat: {
                    pattern: '0,0'
                },
                className: `month-bg-${m}`
            });

            colHeaders.push(`Tồn kho`);
            columns.push({
                data: `m${m}_expected_inventory`,
                readOnly: true,
                type: 'numeric',
                numericFormat: {
                    pattern: '0,0'
                },
                className: `month-bg-${m}`
            });

            colHeaders.push(`Số tháng bán`);
            columns.push({
                data: `m${m}_months_sales`,
                readOnly: true,
                type: 'numeric',
                numericFormat: {
                    pattern: '0,0.00'
                },
                className: `month-bg-${m}`
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

        // Tính chiều cao tuyệt đối cho bảng = chiều cao cửa sổ - vị trí trên cùng của container - padding dưới
        function getTableHeight() {
            var rect = container.getBoundingClientRect();
            return Math.max(window.innerHeight - rect.top - 20, 300);
        }

        hot = new Handsontable(container, {
            data: data,
            nestedHeaders: [
                topHeaders,
                finalColHeaders, // Hàng 2: Tên cột
                excelLetters // Hàng 3: Chỉ số A, B, C...
            ],
            columns: columns,
            rowHeaders: true,
            height: getTableHeight(),
            licenseKey: 'non-commercial-and-evaluation',
            fixedColumnsStart: 0,
            contextMenu: true,
            manualColumnResize: true,
            manualColumnFreeze: true,
            filters: true,
            dropdownMenu: true,
            afterGetColHeader: function(col, TH) {
                if (col >= 13) {
                    let m = Math.floor((col - 13) / 5) + 1;
                    if (m >= 1 && m <= 12) {
                        TH.classList.add('month-bg-' + m);
                    }
                }
            },
            afterChange: function(changes, source) {
                if (source === 'loadData' || source === 'calc') {
                    return;
                }
                
                let toUpdate = [];
                let rowUpdates = new Set();
                
                changes.forEach(([row, prop, oldValue, newValue]) => {
                    if (oldValue !== newValue) {
                        rowUpdates.add(row);
                        const rowData = hot.getSourceDataAtRow(row);
                        if (rowData && rowData.id) {
                            changedRowIds.add(rowData.id);
                        }
                    }
                });
                
                rowUpdates.forEach(row => {
                    const rowData = hot.getSourceDataAtRow(row);
                    const batch_size = parseFloat(String(rowData.batch_size).replace(/,/g, '')) || 0;
                    const packaging_spec = parseFloat(String(rowData.packaging_spec).replace(/,/g, '')) || 0;
                    const avg_sales_box = parseFloat(String(rowData.avg_sales_box).replace(/,/g, '')) || 0;
                    
                    // Tính BQ bán / tháng (viên)
                    const avg_sales = avg_sales_box * packaging_spec;
                    toUpdate.push([row, 'avg_sales_pill', avg_sales]);
                    
                    for (let m = 1; m <= 12; m++) {
                        // Tính Số lượng KH
                        let batches = parseFloat(String(rowData[`m${m}_batches`]).replace(/,/g, '')) || 0;
                        let planned_qty = batches * batch_size;
                        toUpdate.push([row, `m${m}_planned_quantity`, planned_qty]);
                        
                        // Tính Số tháng bán
                        let wip = parseFloat(String(rowData[`m${m}_wip_inventory`]).replace(/,/g, '')) || 0;
                        let fg = parseFloat(String(rowData[`m${m}_expected_inventory`]).replace(/,/g, '')) || 0;
                        let months_sales = 0;
                        if (avg_sales > 0) {
                            months_sales = (wip + fg) / avg_sales;
                            months_sales = Math.round(months_sales * 100) / 100;
                        }
                        toUpdate.push([row, `m${m}_months_sales`, months_sales]);
                    }
                });
                
                if (toUpdate.length > 0) {
                    hot.setDataAtRowProp(toUpdate, 'calc');
                }
            }
        });

        // Cập nhật chiều cao khi thay đổi kích thước cửa sổ
        window.addEventListener('resize', function() {
            if (hot) {
                hot.updateSettings({ height: getTableHeight() });
            }
        });
        });

        function saveData() {
            if (changedRowIds.size === 0) {
                alert('Không có thay đổi nào để lưu.');
                return;
            }

            const allData = hot.getSourceData();
            const dataToSave = allData.filter(row => changedRowIds.has(row.id));

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
                        changedRowIds.clear();
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

        let currentMonth = 8;
        
        function loadEquipmentAllocation() {
            var isGroupByLine = $('#groupByLineSwitch').is(':checked');
            var stageCode = $('#stageCodeSelect').val();
            var url = '{{ route("pages.plan.annual.equipment_allocation", $plan->id) }}' +
                '?month=' + currentMonth + '&stage_code=' + stageCode + '&department_code={{ session('user')['production_code'] }}';
            if (isGroupByLine) {
                url += '&group_by=line';
            }

            $('#equipmentAllocationTable tbody').html(
                '<tr><td colspan="5" class="text-center py-4"><i class="fas fa-spinner fa-spin fa-2x text-info"></i><br>Đang tải dữ liệu...</td></tr>'
            );

            $.ajax({
                url: url,
                type: 'GET',
                success: function(response) {
                    if (response.success) {
                        var tbody = '';
                        if (response.data.length === 0) {
                            tbody =
                                '<tr><td colspan="5" class="text-center py-4">Không có dữ liệu định mức thiết bị cho kế hoạch này.</td></tr>';
                        } else {
                            // Sort data: blister_type_code -> room_order_by -> total_batches
                            response.data.sort(function(a, b) {
                                // 1. Sort by blister_type_code
                                if (a.blister_type_code !== null && b.blister_type_code !== null) {
                                    if (a.blister_type_code !== b.blister_type_code) {
                                        return a.blister_type_code - b.blister_type_code;
                                    }
                                } else if (a.blister_type_code !== null) {
                                    return -1;
                                } else if (b.blister_type_code !== null) {
                                    return 1;
                                }

                                // 2. Sort by room_order_by
                                var orderA = (a.room_order_by !== null && a.room_order_by !== undefined) ? parseInt(a.room_order_by) : 9999;
                                var orderB = (b.room_order_by !== null && b.room_order_by !== undefined) ? parseInt(b.room_order_by) : 9999;
                                if (orderA !== orderB) {
                                    return orderA - orderB;
                                }

                                // 3. Fallback to total_batches descending
                                return b.total_batches - a.total_batches;
                            });

                            var maxBatches = 0;
                            var maxQty = 0;
                            response.data.forEach(function(item) {
                                if (item.total_batches > maxBatches) maxBatches = item.total_batches;
                                if (item.total_quantity > maxQty) maxQty = item.total_quantity;
                            });
                            if (maxBatches === 0) maxBatches = 1;
                            if (maxQty === 0) maxQty = 1;

                            response.data.forEach(function(item) {
                                var qty = (item.total_quantity || 0).toLocaleString('en-US');

                                var widthBatches = (item.total_batches / maxBatches) * 100;
                                var widthQty = (item.total_quantity / maxQty) * 100;

                                var batchBarHtml =
                                    '<div style="width: 100%; height: 24px; position: relative;">' +
                                    '<div style="background-color: #e83e8c; width: ' + Math.max(widthBatches, 5) +
                                    '%; height: 100%; border-radius: 6px; display: flex; align-items: center; justify-content: center; transition: width 0.5s ease; min-width: fit-content; padding: 0 10px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">' +
                                    '<span style="color: white; font-weight: bold; font-size: 0.85rem; white-space: nowrap;">' +
                                    item.total_batches + '</span>' +
                                    '</div>' +
                                    '</div>';

                                var qtyBarHtml =
                                    '<div style="width: 100%; height: 24px; position: relative;">' +
                                    '<div style="background-color: #28a745; width: ' + Math.max(widthQty, 5) +
                                    '%; height: 100%; border-radius: 6px; display: flex; align-items: center; justify-content: center; transition: width 0.5s ease; min-width: fit-content; padding: 0 10px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">' +
                                    '<span style="color: white; font-weight: bold; font-size: 0.85rem; white-space: nowrap;">' +
                                    qty + '</span>' +
                                    '</div>' +
                                    '</div>';

                                var rowspan = 2;

                                tbody += '<tr>' +
                                    '<td class="text-center font-weight-bold align-middle" rowspan="' + rowspan + '" style="border-bottom: 2px solid #dee2e6;">' + (item.equipment_code || 'NA') + '</td>' +
                                    '<td class="align-middle" rowspan="' + rowspan + '" style="border-bottom: 2px solid #dee2e6;">' + (item.equipment_name || 'NA') + '</td>' +
                                    '<td class="text-center align-middle" rowspan="' + rowspan + '" style="border-bottom: 2px solid #dee2e6;">' + (item.main_equipment_name || 'NA') + '</td>' +
                                    '<td class="text-right align-middle border-bottom-0 text-secondary pr-4 py-1" style="width: 15%; font-size: 0.9rem;">Tổng số Lô Có Thể Sắp</td>' +
                                    '<td class="align-middle border-bottom-0 p-1" style="width: 30%;">' + batchBarHtml + '</td>' +
                                    '</tr>' +
                                    '<tr>' +
                                    '<td class="text-right align-middle border-top-0 text-secondary pr-4 py-1" style="border-bottom: 2px solid #dee2e6; font-size: 0.9rem;">Sản lượng lý thuyết</td>' +
                                    '<td class="align-middle border-top-0 p-1" style="border-bottom: 2px solid #dee2e6;">' + qtyBarHtml + '</td>' +
                                    '</tr>';
                            });
                        }
                        $('#equipmentAllocationTable tbody').html(tbody);
                    } else {
                        $('#equipmentAllocationTable tbody').html(
                            '<tr><td colspan="5" class="text-center text-danger">Có lỗi xảy ra khi tải dữ liệu.</td></tr>'
                        );
                    }
                },
                error: function() {
                    $('#equipmentAllocationTable tbody').html(
                        '<tr><td colspan="5" class="text-center text-danger">Lỗi kết nối máy chủ.</td></tr>'
                    );
                }
            });
        }

        document.addEventListener('DOMContentLoaded', function() {
            // Lắng nghe sự kiện Ctrl + S
            document.addEventListener('keydown', function(e) {
                if ((e.ctrlKey || e.metaKey) && e.key === 's') {
                    e.preventDefault();
                    saveData();
                }
            });

            $('#addProductsModal').on('show.bs.modal', function() {
                const container = $('#unassignedProductsContainer');
                if (container.data('loaded')) return;

                $.get('{{ route('pages.plan.annual.unassigned_products', $plan->id) }}', function(html) {
                    container.html(html);
                    container.data('loaded', true);
                }).fail(function() {
                    container.html(
                        '<div class="alert alert-danger m-3">Có lỗi xảy ra khi tải dữ liệu. Vui lòng thử lại.</div>'
                    );
                });
            });

            $(document).on('click', '.btn-equipment-allocation', function(e) {
                e.preventDefault();
                currentMonth = $(this).data('month');
                var monthStr = String(currentMonth).padStart(2, '0');
                $('#equipmentAllocationPlanName').text('Kế hoạch phân bổ thiết bị: Tháng ' + monthStr + '/{{ $plan->year }}');
                $('#groupByLineSwitch').prop('checked', false);
                $('#equipmentAllocationModal').modal('show');
                loadEquipmentAllocation();
            });

            $('#groupByLineSwitch, #stageCodeSelect').change(function() {
                loadEquipmentAllocation();
            });
        });
    </script>
@endsection
