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

        /* Custom tabs styling */
        .nav-tabs {
            border-bottom: 2px solid #e3f2fd;
            margin-bottom: 15px;
        }
        .nav-tabs .nav-link {
            border: none;
            color: #495057;
            font-weight: 600;
            padding: 10px 20px;
            transition: all 0.3s ease;
        }
        .nav-tabs .nav-link.active {
            color: #0d47a1;
            background-color: #e3f2fd;
            border-radius: 4px 4px 0 0;
            border-bottom: 3px solid #0d47a1;
        }
        .nav-tabs .nav-link:hover:not(.active) {
            background-color: #f8f9fa;
            color: #0d47a1;
        }
        .total-row-cell {
            font-weight: bold !important;
            background-color: #f8f9fa !important;
            color: #212529 !important;
        }

        /* Fix lỗi bảng bị trong suốt khi cuộn ngang */
        .handsontable td {
            background-color: #ffffff;
        }
        .handsontable .ht_clone_left {
            z-index: 105;
        }
        .handsontable .ht_clone_top_left_corner {
            z-index: 106;
        }
        .handsontable .ht_clone_top {
            z-index: 104;
        }
    </style>
    <div class="content-wrapper">
        <div class="card" style="min-height: 100vh">
            <div class="card-header mt-0">
                <h3 class="card-title">Bảng Tính Kế Hoạch Năm {{ $plan->year }}</h3>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-12 d-flex flex-wrap align-items-center mb-3">
                        <button class="btn btn-success btn-add mb-2" onclick="saveData()" style="width: 155px;">
                            <i class="fas fa-save"></i> Lưu thay đổi
                        </button>
                        <button class="btn btn-primary btn-add mb-2 ml-2" data-toggle="modal"
                            data-target="#addProductsModal">
                            <i class="fas fa-plus"></i> Thêm sản phẩm
                        </button>
                        <button class="btn btn-warning btn-add mb-2 ml-2 text-white" data-toggle="modal"
                            data-target="#pushToMonthlyPlanModal" style="background-color: #f39c12; border-color: #f39c12;">
                            <i class="fas fa-paper-plane"></i> Đẩy vào KH Tháng
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

                        <!-- Dropdown Tháng đang xét -->
                        <div class="form-group ml-4 mb-2 d-flex align-items-center" style="width: 250px;">
                            <label for="currentMonthSelect" class="mb-0 mr-2 font-weight-bold" style="white-space: nowrap; color: #d32f2f;">Tháng đang xét:</label>
                            <select id="currentMonthSelect" class="form-control form-control-sm" style="border-color: #d32f2f; color: #d32f2f; font-weight: bold;">
                                @for($m = 1; $m <= 12; $m++)
                                    <option value="{{ $m }}">Tháng {{ str_pad($m, 2, '0', STR_PAD_LEFT) }}</option>
                                @endfor
                            </select>
                        </div>
                    </div>
                </div>

                @if (session('success'))
                    <div class="alert alert-success mt-2">{{ session('success') }}</div>
                @endif

                <ul class="nav nav-tabs" id="annualPlanTabs" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active" id="forecast-tab" data-toggle="tab" href="#forecast-pane" role="tab" aria-controls="forecast-pane" aria-selected="true">
                            <i class="fas fa-calendar-alt mr-1"></i> Bảng dự kiến kế hoạch năm
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="ratio-tab" data-toggle="tab" href="#ratio-pane" role="tab" aria-controls="ratio-pane" aria-selected="false">
                            <i class="fas fa-chart-line mr-1"></i> Tỉ lệ thực xuất và dự trữ an toàn
                        </a>
                    </li>
                </ul>
                <div class="tab-content" id="annualPlanTabsContent">
                    <div class="tab-pane fade show active" id="forecast-pane" role="tabpanel" aria-labelledby="forecast-tab">
                        <div class="row row-xs">
                            <div class="col-12">
                                <div id="hot-app" class="handsontable-container mt-3"></div>
                            </div>
                        </div>
                    </div>
                    <div class="tab-pane fade" id="ratio-pane" role="tabpanel" aria-labelledby="ratio-tab">
                        <div class="row row-xs">
                            <div class="col-12">
                                <div id="hot-app-ratio" class="handsontable-container mt-3"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Đẩy vào KH Tháng -->
    <div class="modal fade" id="pushToMonthlyPlanModal" tabindex="-1" role="dialog" aria-labelledby="pushToMonthlyPlanModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content" style="border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.2);">
                <form id="pushToMonthlyPlanForm">
                    @csrf
                    <div class="modal-header bg-warning text-white" style="background-color: #f39c12 !important;">
                        <h5 class="modal-title" id="pushToMonthlyPlanModalLabel"><i class="fas fa-paper-plane mr-2"></i>Đẩy lô từ KH Năm vào KH Tháng</h5>
                        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label class="font-weight-bold" for="push_month">Tháng cần đẩy từ KH Năm:</label>
                            <select class="form-control" name="month" id="push_month" required>
                                @for($m = 1; $m <= 12; $m++)
                                    <option value="{{ $m }}">Tháng {{ $m }}</option>
                                @endfor
                            </select>
                        </div>
                        <div class="form-group mt-3">
                            <label class="font-weight-bold" for="push_target_plan">Kế hoạch tháng nhận (Pending):</label>
                            <select class="form-control" name="target_plan_list_id" id="push_target_plan" required>
                                @forelse($pendingPlans as $p)
                                    <option value="{{ $p->id }}">{{ $p->name }} (Tháng {{ $p->month }}/{{ $p->year }})</option>
                                @empty
                                    <option value="" disabled>Không có kế hoạch tháng nào đang chờ gửi (Pending)</option>
                                @endforelse
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Hủy</button>
                        <button type="submit" class="btn btn-warning text-white" style="background-color: #f39c12; border-color: #f39c12;" @if($pendingPlans->isEmpty()) disabled @endif>Xác nhận đẩy</button>
                    </div>
                </form>
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

    <!-- Modal Chi tiết BTP dở dang -->
    <div class="modal fade" id="wipDetailsModal" tabindex="-1" role="dialog"
        aria-labelledby="wipDetailsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl" role="document" style="max-width: 90%;">
            <div class="modal-content">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title font-weight-bold" id="wipDetailsModalLabel">
                        <i class="fas fa-hourglass-half mr-2"></i>Chi Tiết Các Lô BTP Dở Dang
                    </h5>
                    <button type="button" class="close text-dark" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <h5 id="wipDetailsPlanName" class="font-weight-bold mb-3 text-primary text-center"></h5>
                    <div class="table-responsive">
                        <table class="table table-striped table-bordered table-hover" id="wipDetailsTable">
                            <thead class="thead-dark">
                                <tr>
                                    <th class="text-center" style="width: 5%;">STT</th>
                                    <th class="text-center" style="width: 15%;">Số Lô</th>
                                    <th class="text-center" style="width: 15%;">Số Lệnh</th>
                                    <th class="text-center" style="width: 20%;">Ngày bắt đầu</th>
                                    <th class="text-center" style="width: 20%;">Ngày kết thúc</th>
                                    <th class="text-center" style="width: 15%;">Sản lượng (viên)</th>
                                    <th class="text-center" style="width: 10%;">Trạng thái</th>
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

    <!-- Modal Chi tiết Tồn kho -->
    <div class="modal fade" id="inventoryDetailsModal" tabindex="-1" role="dialog"
        aria-labelledby="inventoryDetailsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title font-weight-bold" id="inventoryDetailsModalLabel">
                        <i class="fas fa-warehouse mr-2"></i>Chi Tiết Các Lô Tồn Kho thực tế (MMS)
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <h5 id="inventoryDetailsPlanName" class="font-weight-bold mb-3 text-primary text-center"></h5>
                    <div class="table-responsive">
                        <table class="table table-striped table-bordered table-hover" id="inventoryDetailsTable">
                            <thead class="thead-dark">
                                <tr>
                                    <th class="text-center" style="width: 10%;">STT</th>
                                    <th class="text-center" style="width: 50%;">Số Lô</th>
                                    <th class="text-center" style="width: 40%;">Số lượng tồn (viên)</th>
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
        let hotRatio;
        let changedRowIds = new Set();
        let globalData;
        let lastClickTime = 0;
        let lastRow = -1;
        let lastCol = -1;

        // Helper to convert index to Excel column letter (0 -> A, 1 -> B...)
        function getExcelColumnName(colIndex) {
            let letter = '';
            while (colIndex >= 0) {
                letter = String.fromCharCode((colIndex % 26) + 65) + letter;
                colIndex = Math.floor(colIndex / 26) - 1;
            }
            return letter;
        }

        // Tính chiều cao tuyệt đối cho bảng = chiều cao cửa sổ - vị trí trên cùng của container - padding dưới
        function getTableHeight(targetContainer) {
            if (!targetContainer) return 300;
            var rect = targetContainer.getBoundingClientRect();
            return Math.max(window.innerHeight - rect.top - 20, 300);
        }

        function syncChanges(sourceHot, targetHot, changes, source) {
            if (source === 'sync' || source === 'loadData' || source === 'calc') {
                return;
            }
            let syncData = [];
            changes.forEach(([row, prop, oldValue, newValue]) => {
                if (oldValue !== newValue) {
                    const sharedProps = ['registration_expiry', 'classification', 'customer_type', 'shelf_life', 'packaging_spec'];
                    if (sharedProps.includes(prop)) {
                        syncData.push([row, prop, newValue]);
                    }
                }
            });
            if (syncData.length > 0) {
                targetHot.setDataAtRowProp(syncData, 'sync');
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            const container = document.getElementById('hot-app');
            const containerRatio = document.getElementById('hot-app-ratio');
            // Inject data from controller
            globalData = @json($hotData);
            const userPreferences = @json($userPreferences);
            const tab1Prefs = userPreferences.tab1 || {};
            const tab2Prefs = userPreferences.tab2 || {};

            let globalCurrentMonth = new Date().getMonth() + 2;
            if (globalCurrentMonth > 12) globalCurrentMonth = 1;
            $('#currentMonthSelect').val(globalCurrentMonth);
            
            let globalTopHeaders = [];
            let globalColHeaders = [];
            let globalExcelLetters = [];

            let savePrefsTimeout;
            function saveTablePreferences(tableName, hotInstance) {
                clearTimeout(savePrefsTimeout);
                savePrefsTimeout = setTimeout(() => {
                    if (!hotInstance) return;
                    let prefs = {};
                    prefs.fixedColumnsStart = hotInstance.getSettings().fixedColumnsStart;
                    const filtersPlugin = hotInstance.getPlugin('filters');
                    if (filtersPlugin && filtersPlugin.conditionCollection) {
                        prefs.filters = filtersPlugin.conditionCollection.exportAllConditions();
                    }
                    $.ajax({
                        url: '/user-table-preferences/save',
                        type: 'POST',
                        data: {
                            table_name: tableName,
                            preferences: prefs,
                            _token: '{{ csrf_token() }}'
                        }
                    });
                }, 1000);
            }

            let totalRow = {
                product_name: 'Tổng cộng',
                is_total_row: true
            };
            totalRow['registration_expiry'] = '';
            totalRow['classification'] = '';
            totalRow['customer_type'] = '';
            totalRow['market'] = '';
            totalRow['general_market'] = '';
            totalRow['dosage'] = '';
            totalRow['intermediate_code'] = '';
            totalRow['finished_product_code'] = '';
            totalRow['shelf_life'] = null;
            totalRow['packaging_spec'] = null;
            totalRow['batch_size'] = null;
            totalRow['avg_sales_box'] = null;
            totalRow['avg_sales_pill'] = null;
            totalRow['average_astimated_box'] = null;
            totalRow['average_astimated_pill'] = null;
            
            for (let m = 1; m <= 12; m++) {
                totalRow[`m${m}_batches`] = 0;
                totalRow[`m${m}_planned_quantity`] = 0;
                totalRow[`m${m}_wip_inventory`] = null;
                totalRow[`m${m}_expected_inventory`] = null;
                totalRow[`m${m}_months_sales`] = null;
            }
            globalData.push(totalRow);

            const data = globalData;

            // Tab 1: Base headers and columns
            let colHeaders = [
                'Mã BTP', 'Mã TP', 'Sản phẩm', 'Hết SĐK', 'Phân loại', 'Khách', 'Thị trường', 'Thị trường chung', 'Dạng bào chế', 'Hạn dùng',
                'Quy cách', 'Cỡ lô', 'BQ bán / Tháng (hộp)', 'BQ bán / tháng (viên)'
            ];

            const columns = [{
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
                data: 'general_market',
                readOnly: false
            },
            {
                data: 'dosage',
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

        let topHeaders = [
            { label: 'Thông tin chung', colspan: 14 }
        ];

        // Generate 12 months dynamically for Tab 1
        const planYear = {{ $plan->year }};
        function buildColHeaders() {
            let tempColHeaders = [
                'Mã BTP', 'Mã TP', 'Sản phẩm', 'Hết SĐK', 'Phân loại', 'Khách', 'Thị trường', 'Thị trường chung', 'Dạng bào chế', 'Hạn dùng',
                'Quy cách', 'Cỡ lô', 'BQ bán / Tháng (hộp)', 'BQ bán / tháng (viên)'
            ];
            
            for (let m = 1; m <= 12; m++) {
                tempColHeaders.push(`Số lô`);
                tempColHeaders.push(`Sản lượng`);
                tempColHeaders.push(`BTP dở dang`);
                tempColHeaders.push(`Tồn kho`);
                let formula = '';
                if (m > globalCurrentMonth) {
                    formula = `<br><small style="color: #666; font-size: 0.75rem;">([Tồn kho (T${globalCurrentMonth})] + [&Sigma; Sản lượng] - [Bán] * ${m - globalCurrentMonth}) / [Bán]</small>`;
                } else {
                    formula = `<br><small style="color: #666; font-size: 0.75rem;">([BTP dở dang] + [Tồn kho] + [Sản lượng]) / [Bán]</small>`;
                }
                tempColHeaders.push(`Số tháng bán${formula}`);
            }
            return tempColHeaders;
        }

        colHeaders = buildColHeaders();
        globalColHeaders = colHeaders;

        for (let m = 1; m <= 12; m++) {
            const monthStr = m.toString().padStart(2, '0');
            topHeaders.push({ label: `Tháng ${monthStr}/${planYear}`, colspan: 5 });

            columns.push({
                data: `m${m}_batches`,
                type: 'numeric',
                numericFormat: {
                    pattern: '0,0'
                },
                className: `month-bg-${m}`
            });

            columns.push({
                data: `m${m}_planned_quantity`,
                readOnly: true,
                type: 'numeric',
                numericFormat: {
                    pattern: '0,0'
                },
                className: `month-bg-${m}`
            });

            columns.push({
                data: `m${m}_wip_inventory`,
                readOnly: true,
                type: 'numeric',
                numericFormat: {
                    pattern: '0,0'
                },
                className: `month-bg-${m}`
            });

            columns.push({
                data: `m${m}_expected_inventory`,
                readOnly: true,
                type: 'numeric',
                numericFormat: {
                    pattern: '0,0'
                },
                className: `month-bg-${m}`
            });

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

        const excelLetters = colHeaders.map((_, index) => {
            return getExcelColumnName(index);
        });
        
        globalTopHeaders = topHeaders;
        globalExcelLetters = excelLetters;

        // Tab 2: Base headers and columns
        const colHeadersRatio = [
            'Mã BTP', 'Mã TP', 'Sản phẩm', 'Hết SĐK', 'Phân loại', 'Khách', 'Thị trường', 'Thị trường chung', 'Dạng bào chế', 'Hạn dùng',
            'Quy cách', 'Cỡ lô', 'Bình quân dự trù tháng (hộp)', 'Bình quân dự trù tháng (viên)'
        ];

        const columnsRatio = [{
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
            data: 'general_market',
            readOnly: false
        },
        {
            data: 'dosage',
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
            data: 'average_astimated_box',
            type: 'numeric',
            numericFormat: {
                pattern: '0,0'
            },
            readOnly: false
        },
        {
            data: 'average_astimated_pill',
            type: 'numeric',
            numericFormat: {
                pattern: '0,0'
            },
            readOnly: true
        }
        ];

        const topHeadersRatio = [
            { label: 'Thông tin chung', colspan: 14 }
        ];

        // Generate 12 months dynamically for Tab 2
        for (let m = 1; m <= 12; m++) {
            const monthStr = m.toString().padStart(2, '0');
            topHeadersRatio.push({ label: `Tháng ${monthStr}/${planYear}`, colspan: 3 });

            colHeadersRatio.push(`Thực xuất KD`);
            columnsRatio.push({
                data: `m${m}_kd_export`,
                readOnly: true,
                type: 'numeric',
                numericFormat: {
                    pattern: '0,0'
                },
                className: `month-bg-${m}`
            });

            colHeadersRatio.push(`Tỉ lệ thực xuất/dự trù`);
            columnsRatio.push({
                data: `m${m}_kd_ratio`,
                readOnly: true,
                type: 'numeric',
                numericFormat: {
                    pattern: '0.00%'
                },
                className: `month-bg-${m}`
            });

            colHeadersRatio.push(`Dự trữ an toàn`);
            columnsRatio.push({
                data: `m${m}_kd_safety_stock`,
                readOnly: true,
                type: 'numeric',
                numericFormat: {
                    pattern: '0.00'
                },
                className: `month-bg-${m}`
            });
        }

        const excelLettersRatio = colHeadersRatio.map((_, index) => {
            return getExcelColumnName(index);
        });

        function rebuildNestedHeaders(originalHeaders, fixedCols) {
            if (fixedCols <= 0 || !originalHeaders) return originalHeaders;
            let newHeaders = [];
            let currentCol = 0;
            for (let i = 0; i < originalHeaders.length; i++) {
                let header = originalHeaders[i];
                let colspan = header.colspan || 1;
                let nextCol = currentCol + colspan;
                if (currentCol < fixedCols && nextCol > fixedCols) {
                    newHeaders.push({ label: header.label || '', colspan: fixedCols - currentCol });
                    newHeaders.push({ label: header.label || '', colspan: nextCol - fixedCols });
                } else {
                    newHeaders.push(header);
                }
                currentCol = nextCol;
            }
            return newHeaders;
        }

        const customContextMenu = ['row_above', 'row_below', 'col_left', 'col_right', 'remove_row', 'remove_col', '---------', 'undo', 'redo', '---------', 'make_read_only', 'alignment', '---------', 'copy', 'cut', '---------', 
            {
                key: 'freeze_up_to_column',
                name: 'Đóng băng đến cột này (giống Excel)',
                callback: function(key, selection) {
                    var col = selection[0].start.col;
                    var fixedCols = col + 1;
                    
                    var isTab1 = this.rootElement.id === 'hot-app';
                    var origTopHeaders = isTab1 ? globalTopHeaders : topHeadersRatio;
                    var origColHeaders = isTab1 ? globalColHeaders : colHeadersRatio;
                    var origExcelLetters = isTab1 ? globalExcelLetters : excelLettersRatio;
                    
                    this.updateSettings({
                        fixedColumnsStart: fixedCols,
                        nestedHeaders: [
                            rebuildNestedHeaders(origTopHeaders, fixedCols),
                            origColHeaders,
                            origExcelLetters
                        ]
                    });
                    
                    if(isTab1) saveTablePreferences('annual_plan_tab1', this);
                    if(!isTab1) saveTablePreferences('annual_plan_tab2', this);
                }
            },
            {
                key: 'unfreeze_all',
                name: 'Bỏ đóng băng tất cả',
                callback: function(key, selection) {
                    var isTab1 = this.rootElement.id === 'hot-app';
                    var origTopHeaders = isTab1 ? globalTopHeaders : topHeadersRatio;
                    var origColHeaders = isTab1 ? globalColHeaders : colHeadersRatio;
                    var origExcelLetters = isTab1 ? globalExcelLetters : excelLettersRatio;
                    
                    this.updateSettings({
                        fixedColumnsStart: 0,
                        nestedHeaders: [
                            origTopHeaders,
                            origColHeaders,
                            origExcelLetters
                        ]
                    });
                    
                    if(isTab1) saveTablePreferences('annual_plan_tab1', this);
                    if(!isTab1) saveTablePreferences('annual_plan_tab2', this);
                }
            }
        ];

        // Initialize Tab 1 Handsontable
        let fixedColsTab1 = parseInt(tab1Prefs.fixedColumnsStart) || 0;
        hot = new Handsontable(container, {
            data: data,
            nestedHeaders: [
                rebuildNestedHeaders(globalTopHeaders, fixedColsTab1),
                globalColHeaders, // Hàng 2: Tên cột
                globalExcelLetters // Hàng 3: Chỉ số A, B, C...
            ],
            columns: columns,
            rowHeaders: true,
            height: getTableHeight(container),
            licenseKey: 'non-commercial-and-evaluation',
            fixedColumnsStart: fixedColsTab1,
            contextMenu: customContextMenu,
            manualColumnResize: true,
            manualColumnFreeze: false,
            persistentState: true,
            filters: true,
            dropdownMenu: true,
            afterColumnFreeze: function() { saveTablePreferences('annual_plan_tab1', this); },
            afterColumnUnfreeze: function() { saveTablePreferences('annual_plan_tab1', this); },
            afterFilter: function() { saveTablePreferences('annual_plan_tab1', this); },
            cells: function(row, col) {
                const cellProperties = {};
                const rowData = this.instance.getSourceDataAtRow(row);
                if (rowData && rowData.product_name === 'Tổng cộng') {
                    cellProperties.readOnly = true;
                    cellProperties.className = 'total-row-cell';
                }
                return cellProperties;
            },
            afterGetColHeader: function(col, TH) {
                if (col >= 14) {
                    let m = Math.floor((col - 14) / 5) + 1;
                    if (m >= 1 && m <= 12) {
                        TH.classList.add('month-bg-' + m);
                    }
                }
            },
            afterOnCellMouseDown: function(event, coords, TD) {
                const currentTime = new Date().getTime();
                const clickDelay = currentTime - lastClickTime;
                
                if (clickDelay < 300 && coords.row === lastRow && coords.col === lastCol) {
                    const row = coords.row;
                    const col = coords.col;
                    if (row >= 0 && col >= 14) {
                        const rowData = hot.getSourceDataAtRow(row);
                        if (rowData && rowData.product_name === 'Tổng cộng') {
                            return;
                        }
                        const productId = rowData.id;
                        const productName = rowData.product_name || 'N/A';
                        const month = Math.floor((col - 14) / 5) + 1;
                        const monthStr = String(month).padStart(2, '0');
                        
                        const colMod = (col - 14) % 5;
                        if (colMod === 2) {
                            showWipDetails(productId, month, productName, monthStr);
                        } else if (colMod === 3) {
                            showInventoryDetails(productId, month, productName, monthStr);
                        }
                    }
                }
                
                lastClickTime = currentTime;
                lastRow = coords.row;
                lastCol = coords.col;
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
                    
                    let cumulative_planned_qty = 0;
                    for (let m = 1; m <= 12; m++) {
                        // Tính Số lượng KH
                        let batches = parseFloat(String(rowData[`m${m}_batches`]).replace(/,/g, '')) || 0;
                        let planned_qty = batches * batch_size;
                        toUpdate.push([row, `m${m}_planned_quantity`, planned_qty]);
                        
                        if (m >= globalCurrentMonth) {
                            cumulative_planned_qty += planned_qty;
                        }
                        
                        // Tính Số tháng bán
                        let wip = parseFloat(String(rowData[`m${m}_wip_inventory`]).replace(/,/g, '')) || 0;
                        let fg = parseFloat(String(rowData[`m${m}_expected_inventory`]).replace(/,/g, '')) || 0;
                        let months_sales = 0;
                        if (avg_sales > 0) {
                            if (m > globalCurrentMonth) {
                                let currentMonthFg = parseFloat(String(rowData[`m${globalCurrentMonth}_expected_inventory`]).replace(/,/g, '')) || fg;
                                let currentMonthWip = parseFloat(String(rowData[`m${globalCurrentMonth}_wip_inventory`]).replace(/,/g, '')) || wip;
                                months_sales = (currentMonthWip + currentMonthFg + cumulative_planned_qty - avg_sales * (m - globalCurrentMonth)) / avg_sales;
                            } else {
                                months_sales = (wip + fg + planned_qty) / avg_sales;
                            }
                            months_sales = Math.round(months_sales * 100) / 100;
                        }
                        toUpdate.push([row, `m${m}_months_sales`, months_sales]);
                    }
                });
                
                if (toUpdate.length > 0) {
                    hot.setDataAtRowProp(toUpdate, 'calc');
                }

                calculateTotals();

                // Sync changes to hotRatio
                if (source !== 'sync' && hotRatio) {
                    syncChanges(hot, hotRatio, changes, source);
                }
            }
        });

        // Initialize Tab 2 Handsontable
        let fixedColsTab2 = parseInt(tab2Prefs.fixedColumnsStart) || 0;
        hotRatio = new Handsontable(containerRatio, {
            data: data,
            nestedHeaders: [
                rebuildNestedHeaders(topHeadersRatio, fixedColsTab2),
                colHeadersRatio, // Hàng 2: Tên cột
                excelLettersRatio // Hàng 3: Chỉ số A, B, C...
            ],
            columns: columnsRatio,
            rowHeaders: true,
            height: getTableHeight(containerRatio),
            licenseKey: 'non-commercial-and-evaluation',
            fixedColumnsStart: fixedColsTab2,
            contextMenu: customContextMenu,
            manualColumnResize: true,
            manualColumnFreeze: false,
            persistentState: true,
            filters: true,
            dropdownMenu: true,
            afterColumnFreeze: function() { saveTablePreferences('annual_plan_tab2', this); },
            afterColumnUnfreeze: function() { saveTablePreferences('annual_plan_tab2', this); },
            afterFilter: function() { saveTablePreferences('annual_plan_tab2', this); },
            cells: function(row, col) {
                const cellProperties = {};
                const rowData = this.instance.getSourceDataAtRow(row);
                if (rowData && rowData.product_name === 'Tổng cộng') {
                    cellProperties.readOnly = true;
                    cellProperties.className = 'total-row-cell';
                }
                return cellProperties;
            },
            afterGetColHeader: function(col, TH) {
                if (col >= 14) {
                    let m = Math.floor((col - 14) / 3) + 1;
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
                        const rowData = hotRatio.getSourceDataAtRow(row);
                        if (rowData && rowData.id) {
                            changedRowIds.add(rowData.id);
                        }
                    }
                });
                
                rowUpdates.forEach(row => {
                    const rowData = hotRatio.getSourceDataAtRow(row);
                    const packaging_spec = parseFloat(String(rowData.packaging_spec).replace(/,/g, '')) || 0;
                    const average_astimated_box = parseFloat(String(rowData.average_astimated_box).replace(/,/g, '')) || 0;
                    
                    const average_astimated_pill = average_astimated_box * packaging_spec;
                    toUpdate.push([row, 'average_astimated_pill', average_astimated_pill]);
                    
                    for (let m = 1; m <= 12; m++) {
                        let kd_export = parseFloat(String(rowData[`m${m}_kd_export`]).replace(/,/g, '')) || 0;
                        let fg = parseFloat(String(rowData[`m${m}_expected_inventory`]).replace(/,/g, '')) || 0;
                        
                        let ratio = 0;
                        let safety = 0;
                        if (average_astimated_pill > 0) {
                            ratio = Math.round((kd_export / average_astimated_pill) * 10000) / 10000;
                            safety = Math.round((fg / average_astimated_pill) * 100) / 100;
                        }
                        toUpdate.push([row, `m${m}_kd_ratio`, ratio]);
                        toUpdate.push([row, `m${m}_kd_safety_stock`, safety]);
                    }
                });
                
                if (toUpdate.length > 0) {
                    hotRatio.setDataAtRowProp(toUpdate, 'calc');
                }

                calculateTotals();

                // Sync changes to hot
                if (source !== 'sync' && hot) {
                    syncChanges(hotRatio, hot, changes, source);
                }
            }
        });

        calculateTotals();

        // Restore filters if present
        if (tab1Prefs.filters) {
            const filtersPlugin = hot.getPlugin('filters');
            if (filtersPlugin) {
                filtersPlugin.conditionCollection.importAllConditions(tab1Prefs.filters);
                filtersPlugin.filter();
            }
        }
        if (tab2Prefs.filters) {
            const filtersPlugin = hotRatio.getPlugin('filters');
            if (filtersPlugin) {
                filtersPlugin.conditionCollection.importAllConditions(tab2Prefs.filters);
                filtersPlugin.filter();
            }
        }

        $('#currentMonthSelect').change(function() {
            globalCurrentMonth = parseInt($(this).val());
            globalColHeaders = buildColHeaders();
            
            if (hot) {
                let fixedCols = hot.getSettings().fixedColumnsStart || 0;
                hot.updateSettings({
                    nestedHeaders: [
                        rebuildNestedHeaders(globalTopHeaders, fixedCols),
                        globalColHeaders,
                        globalExcelLetters
                    ]
                });
                
                // Cuộn ngang tới tháng đang xét
                let targetCol = 14 + (globalCurrentMonth - 1) * 5;
                if (targetCol >= 0 && targetCol < globalColHeaders.length) {
                    hot.scrollViewportTo(0, targetCol);
                }
            }
        });

        // Force fixedColumnsStart to override persistentState
        setTimeout(function() {
            if (hot) {
                let fs1 = parseInt(tab1Prefs.fixedColumnsStart) || 0;
                hot.updateSettings({ 
                    fixedColumnsStart: fs1,
                    nestedHeaders: [rebuildNestedHeaders(globalTopHeaders, fs1), globalColHeaders, globalExcelLetters]
                });
            }
            if (hotRatio) {
                let fs2 = parseInt(tab2Prefs.fixedColumnsStart) || 0;
                hotRatio.updateSettings({ 
                    fixedColumnsStart: fs2,
                    nestedHeaders: [rebuildNestedHeaders(topHeadersRatio, fs2), colHeadersRatio, excelLettersRatio]
                });
            }
        }, 100);

        // Cập nhật chiều cao khi thay đổi kích thước cửa sổ
        window.addEventListener('resize', function() {
            const container = document.getElementById('hot-app');
            const containerRatio = document.getElementById('hot-app-ratio');
            if (hot) {
                hot.updateSettings({ height: getTableHeight(container) });
            }
            if (hotRatio) {
                hotRatio.updateSettings({ height: getTableHeight(containerRatio) });
            }
        });

        // Trigger render on tab changes to layout properly
        $('a[data-toggle="tab"]').on('shown.bs.tab', function(e) {
            const container = document.getElementById('hot-app');
            const containerRatio = document.getElementById('hot-app-ratio');
            if (e.target.id === 'forecast-tab' && hot) {
                hot.updateSettings({ height: getTableHeight(container) });
                hot.render();
            } else if (e.target.id === 'ratio-tab' && hotRatio) {
                hotRatio.updateSettings({ height: getTableHeight(containerRatio) });
                hotRatio.render();
            }
        });
        });

        function calculateTotals() {
            if (!hot) return;
            const count = hot.countRows();
            if (count <= 1) return;
            
            let totalRowIndex = -1;
            for (let r = 0; r < count; r++) {
                if (hot.getDataAtRowProp(r, 'product_name') === 'Tổng cộng') {
                    totalRowIndex = r;
                    break;
                }
            }
            
            if (totalRowIndex === -1) return;
            
            let sums = {};
            for (let m = 1; m <= 12; m++) {
                sums[`m${m}_batches`] = 0;
                sums[`m${m}_planned_quantity`] = 0;
            }
            
            for (let r = 0; r < count; r++) {
                if (r === totalRowIndex) continue;
                
                for (let m = 1; m <= 12; m++) {
                    let batches = parseFloat(String(hot.getDataAtRowProp(r, `m${m}_batches`)).replace(/,/g, '')) || 0;
                    let qty = parseFloat(String(hot.getDataAtRowProp(r, `m${m}_planned_quantity`)).replace(/,/g, '')) || 0;
                    
                    sums[`m${m}_batches`] += batches;
                    sums[`m${m}_planned_quantity`] += qty;
                }
            }
            
            let updates = [];
            for (let m = 1; m <= 12; m++) {
                updates.push([totalRowIndex, `m${m}_batches`, sums[`m${m}_batches`]]);
                updates.push([totalRowIndex, `m${m}_planned_quantity`, sums[`m${m}_planned_quantity`]]);
            }
            
            hot.setDataAtRowProp(updates, 'calc');
        }

        function saveData() {
            if (changedRowIds.size === 0) {
                alert('Không có thay đổi nào để lưu.');
                return;
            }

            const dataToSave = globalData.filter(row => changedRowIds.has(row.id));

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

            // Form submit handler to push annual plan to monthly plan
            $('#pushToMonthlyPlanForm').on('submit', function(e) {
                e.preventDefault();
                
                let formData = $(this).serialize();
                let submitBtn = $(this).find('button[type="submit"]');
                let originalText = submitBtn.text();
                submitBtn.prop('disabled', true).text('Đang xử lý...');
                
                $.ajax({
                    url: "{{ route('pages.plan.annual.push_to_monthly', $plan->id) }}",
                    type: "POST",
                    data: formData,
                    success: function(response) {
                        submitBtn.prop('disabled', false).text(originalText);
                        if (response.success) {
                            $('#pushToMonthlyPlanModal').modal('hide');
                            alert(response.message || 'Đã đẩy các lô vào kế hoạch tháng thành công!');
                            if (response.redirect_url) {
                                window.location.href = response.redirect_url;
                            }
                        } else {
                            alert(response.message || 'Có lỗi xảy ra!');
                        }
                    },
                    error: function(xhr) {
                        submitBtn.prop('disabled', false).text(originalText);
                        let errMsg = 'Có lỗi xảy ra!';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            errMsg = xhr.responseJSON.message;
                        }
                        alert(errMsg);
                    }
                });
            });
        });

        function showWipDetails(productId, month, productName, monthStr) {
            $('#wipDetailsPlanName').text('Sản phẩm: ' + productName + ' - Tháng ' + monthStr + '/{{ $plan->year }}');
            $('#wipDetailsTable tbody').html(
                '<tr><td colspan="7" class="text-center py-4"><i class="fas fa-spinner fa-spin fa-2x text-warning"></i><br>Đang tải chi tiết các lô...</td></tr>'
            );
            $('#wipDetailsModal').modal('show');

            $.ajax({
                url: '{{ route("pages.plan.annual.wip_details", ["productId" => ":productId", "month" => ":month"]) }}'
                    .replace(':productId', productId)
                    .replace(':month', month),
                type: 'GET',
                success: function(response) {
                    if (response.success) {
                        let tbody = '';
                        if (response.data.length === 0) {
                            tbody = '<tr><td colspan="7" class="text-center py-4">Không có lô dở dang nào trong tháng này.</td></tr>';
                        } else {
                            response.data.forEach((item, index) => {
                                tbody += '<tr>' +
                                    '<td class="text-center align-middle">' + (index + 1) + '</td>' +
                                    '<td class="text-center font-weight-bold align-middle text-primary">' + (item.batch_code || 'Chưa có') + '</td>' +
                                    '<td class="text-center align-middle">' + (item.order_number || 'Chưa có') + '</td>' +
                                    '<td class="text-center align-middle">' + item.start_date + '</td>' +
                                    '<td class="text-center align-middle">' + item.end_date + '</td>' +
                                    '<td class="text-right align-middle font-weight-bold">' + item.batch_qty + '</td>' +
                                    '<td class="text-center align-middle"><span class="badge ' + (item.status.includes('hoàn thành') ? 'badge-success' : 'badge-warning') + '">' + item.status + '</span></td>' +
                                    '</tr>';
                            });
                        }
                        $('#wipDetailsTable tbody').html(tbody);
                    } else {
                        $('#wipDetailsTable tbody').html(
                            '<tr><td colspan="7" class="text-center text-danger py-4">Lỗi tải dữ liệu.</td></tr>'
                        );
                    }
                },
                error: function() {
                    $('#wipDetailsTable tbody').html(
                        '<tr><td colspan="7" class="text-center text-danger py-4">Lỗi kết nối máy chủ.</td></tr>'
                    );
                }
            });
        }

        function showInventoryDetails(productId, month, productName, monthStr) {
            $('#inventoryDetailsPlanName').text('Sản phẩm: ' + productName + ' - Cuối tháng ' + monthStr + '/{{ $plan->year }}');
            $('#inventoryDetailsTable tbody').html(
                '<tr><td colspan="3" class="text-center py-4"><i class="fas fa-spinner fa-spin fa-2x text-success"></i><br>Đang tải chi tiết tồn kho...</td></tr>'
            );
            $('#inventoryDetailsModal').modal('show');

            $.ajax({
                url: '{{ route("pages.plan.annual.inventory_details", ["productId" => ":productId", "month" => ":month"]) }}'
                    .replace(':productId', productId)
                    .replace(':month', month),
                type: 'GET',
                success: function(response) {
                    if (response.success) {
                        let tbody = '';
                        if (response.data.length === 0) {
                            tbody = '<tr><td colspan="3" class="text-center py-4">Không có tồn kho thực tế cho sản phẩm này tại thời điểm cuối tháng.</td></tr>';
                        } else {
                            response.data.forEach((item, index) => {
                                tbody += '<tr>' +
                                    '<td class="text-center align-middle">' + (index + 1) + '</td>' +
                                    '<td class="text-center font-weight-bold align-middle text-success">' + item.lot_number + '</td>' +
                                    '<td class="text-right align-middle font-weight-bold">' + item.quantity + '</td>' +
                                    '</tr>';
                            });
                        }
                        $('#inventoryDetailsTable tbody').html(tbody);
                    } else {
                        $('#inventoryDetailsTable tbody').html(
                            '<tr><td colspan="3" class="text-center text-danger py-4">Lỗi tải dữ liệu.</td></tr>'
                        );
                    }
                },
                error: function() {
                    $('#inventoryDetailsTable tbody').html(
                        '<tr><td colspan="3" class="text-center text-danger py-4">Lỗi kết nối máy chủ.</td></tr>'
                    );
                }
            });
        }
    </script>
@endsection
