@extends ('layout.master')

@section('topNAV')
    @include('layout.topNAV')
@endsection

@section('leftNAV')
    @include('layout.leftNAV')
@endsection

@section('mainContent')
    @php
        $dmy = fn($date) => $date ? \Carbon\Carbon::parse($date)->format('d/m/Y') : '';
        $qty = fn($value) => number_format((float) $value, 0, ',', '.');
        // Lọc theo Ngày xuất kho thì khoảng tháng kế hoạch không áp dụng (xem CocTrackingController::index())
        $planIgnored = $outFrom !== '' || $outTo !== '';
    @endphp

    <div class="content-wrapper">
        <section class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1><i class="fas fa-globe-europe text-primary"></i> Theo Dõi Xuất Kho EU</h1>
                    </div>
                </div>
            </div>
        </section>

        <section class="content">
            <div class="container-fluid">
                <div class="card card-primary card-outline">
                    <div class="card-body">
                        <form method="GET" action="{{ route('pages.plan.coc_tracking.list') }}" class="coc-filter">
                            <div class="form-row align-items-end">
                                <div class="form-group col-sm-6 col-lg-3 col-xl-2">
                                    <label class="coc-filter-label"><i class="fas fa-industry text-primary"></i> Phân xưởng</label>
                                    <select class="form-control form-control-sm" name="department">
                                        <option value="">Tất cả phân xưởng</option>
                                        @foreach ($departments as $code => $name)
                                            <option value="{{ $code }}" {{ $code === $department ? 'selected' : '' }}>{{ $name }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="form-group col-sm-6 col-lg-4 col-xl-3">
                                    <label class="coc-filter-label {{ $planIgnored ? 'text-muted' : '' }}">
                                        <i class="far fa-calendar text-primary"></i> Tháng kế hoạch
                                        @if ($planIgnored)
                                            <span class="badge badge-light border font-weight-normal"
                                                title="Đang lọc theo Ngày xuất kho nên khoảng tháng kế hoạch không áp dụng: lô kế hoạch tháng khác vẫn hiện nếu có phiếu xuất kho trong khoảng đang chọn.">
                                                <i class="fas fa-ban"></i> không áp dụng
                                            </span>
                                        @endif
                                    </label>
                                    <div class="input-group input-group-sm {{ $planIgnored ? 'coc-filter-muted' : '' }}">
                                        <input type="month" class="form-control" name="from_month" value="{{ $fromMonth }}"
                                            title="Từ tháng kế hoạch">
                                        <div class="input-group-append">
                                            <span class="input-group-text">đến</span>
                                        </div>
                                        <input type="month" class="form-control" name="to_month" value="{{ $toMonth }}"
                                            title="Đến tháng kế hoạch">
                                    </div>
                                </div>

                                <div class="form-group col-sm-6 col-lg-5 col-xl-3">
                                    <label class="coc-filter-label"
                                        title="Lô có ít nhất một phiếu xuất kho thành phẩm (MMS, đã duyệt) trong khoảng này. Bỏ trống một đầu để lọc mở (từ ngày... / đến ngày...).">
                                        <i class="fas fa-truck text-success"></i> Ngày xuất kho
                                    </label>
                                    <div class="input-group input-group-sm">
                                        <input type="text" class="form-control coc-date" name="out_from" placeholder="dd/mm/yyyy"
                                            autocomplete="off" title="Từ ngày" value="{{ $dmy($outFrom) }}">
                                        <div class="input-group-append">
                                            <span class="input-group-text">đến</span>
                                        </div>
                                        <input type="text" class="form-control coc-date" name="out_to" placeholder="dd/mm/yyyy"
                                            autocomplete="off" title="Đến ngày" value="{{ $dmy($outTo) }}">
                                    </div>
                                </div>

                            </div>

                            <div class="form-row align-items-center">
                                <div class="form-group col-md-6 col-xl-5">
                                    <div class="input-group input-group-sm">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fas fa-search"></i></span>
                                        </div>
                                        <input type="text" class="form-control" name="keyword" value="{{ $keyword }}"
                                            placeholder="Tìm theo mã TP / mã BTP / tên sản phẩm / số lô / thị trường...">
                                    </div>
                                </div>

                                <div class="form-group col-md-6 col-xl-7 d-flex justify-content-md-end coc-filter-actions">
                                    <button type="submit" class="btn btn-sm btn-primary px-3">
                                        <i class="fas fa-filter"></i> Lọc
                                    </button>
                                    <a href="{{ route('pages.plan.coc_tracking.list') }}" class="btn btn-sm btn-outline-secondary"
                                        title="Bỏ mọi bộ lọc, về tháng kế hoạch hiện tại">
                                        <i class="fas fa-undo"></i> Đặt lại
                                    </a>
                                    <a href="{{ route('pages.plan.coc_tracking.export', [
                                        'department' => $department,
                                        'from_month' => $fromMonth,
                                        'to_month' => $toMonth,
                                        'out_from' => $dmy($outFrom),
                                        'out_to' => $dmy($outTo),
                                        'keyword' => $keyword,
                                    ]) }}" class="btn btn-sm btn-outline-success"
                                        title="Xuất Excel đúng danh sách lô đang lọc (theo bộ lọc đã bấm Lọc)">
                                        <i class="fas fa-file-excel"></i> Xuất Excel
                                    </a>
                                </div>
                            </div>
                        </form>

                        <div class="coc-filter-status">
                            <span>
                                <b>{{ number_format($datas->count(), 0, ',', '.') }}</b> lô thị trường Châu Âu
                                @if ($planIgnored)
                                    · có phiếu xuất kho từ <b>{{ $dmy($outFrom) ?: '...' }}</b> đến <b>{{ $dmy($outTo) ?: '...' }}</b>
                                @endif
                            </span>
                            <span class="text-muted">
                                <i class="fas fa-sort"></i> Bấm tiêu đề cột để sắp xếp · <kbd>Shift</kbd> + bấm để sắp thêm cột
                            </span>
                        </div>

                        @unless ($mmsAvailable)
                            <div class="alert alert-secondary py-2 small">
                                <i class="fas fa-plug"></i> Chưa kết nối được <b>MMS</b>: chưa lấy được
                                <b>Ngày KCS</b>, <b>Số CoA</b>, <b>Ngày / Số Lượng Nhập Kho</b> và <b>Ngày / Số Lượng Xuất Kho</b>
                                (Ngày KCS / Số CoA đã lưu ở trang Theo Dõi Hồ Sơ KCS vẫn hiện).
                            </div>
                        @endunless

                        <div class="table-responsive" id="coc_table_scroll">
                            <table class="table table-bordered table-sm table-hover mb-0" id="coc_tracking_table">
                                <thead class="text-center align-middle">
                                    <tr>
                                        <th class="coc-sticky coc-sticky-1" style="min-width: 50px;">STT</th>
                                        <th class="coc-sticky coc-sticky-2" style="min-width: 100px;">Số Lệnh</th>
                                        <th class="coc-sticky coc-sticky-3" style="min-width: 90px;">Số Lô</th>
                                        <th class="coc-sticky coc-sticky-4 coc-nosort" style="min-width: 80px;"
                                            title="Nguyên liệu (NL) và bao bì (BB) đã xuất cho lô, lấy từ MMS">NL / BB</th>
                                        <th class="coc-sticky coc-sticky-5" style="min-width: 200px;">Tên Sản Phẩm</th>
                                        <th style="min-width: 130px;">Mã BTP</th>
                                        <th style="min-width: 130px;">Mã TP</th>
                                        <th style="min-width: 110px;">Thị Trường</th>
                                        <th style="min-width: 150px;">Quy Cách</th>
                                        <th style="min-width: 100px;">Cỡ Lô</th>
                                        <th class="coc-mms" style="min-width: 100px;"
                                            title="Ngày sản xuất của lô trên phiếu nhập kho thành phẩm (MMS, FGGRN.mfgdate).">
                                            Ngày Sản Xuất</th>
                                        <th class="coc-mms" style="min-width: 100px;"
                                            title="Hạn dùng của lô trên phiếu nhập kho thành phẩm (MMS, FGGRN.Expirydate).">
                                            Hạn Dùng</th>
                                        <th class="coc-mms" style="min-width: 100px;"
                                            title="Ngày KCS duyệt trên MMS (bước QA Approval), cùng giá trị cột Ngày KCS của trang Theo Dõi Hồ Sơ KCS.">
                                            Ngày KCS</th>
                                        <th class="coc-mms" style="min-width: 110px;"
                                            title="Số phiếu CoA (COATP) trên MMS, cùng giá trị cột Số Phiếu COATP của trang Theo Dõi Hồ Sơ KCS.">
                                            Số CoA</th>
                                        <th class="coc-mms" style="min-width: 110px;"
                                            title="Ngày phiếu nhập kho thành phẩm đầu tiên trên MMS (FGGRN, ngày kho tạo phiếu). Lô nhập nhiều đợt: rê chuột để xem từng phiếu.">
                                            Ngày Nhập Kho</th>
                                        <th class="coc-mms" style="min-width: 120px;"
                                            title="Tổng số lượng các phiếu nhập kho thành phẩm của lô trên MMS.">
                                            Số Lượng Nhập Kho</th>
                                        <th class="coc-mms" style="min-width: 130px;"
                                            title="Ngày phiếu xuất kho thành phẩm đầu tiên trên MMS (đã duyệt). Lô xuất nhiều đợt: rê chuột để xem từng phiếu.">
                                            Ngày Xuất Kho</th>
                                        <th class="coc-mms" style="min-width: 130px;"
                                            title="Tổng số lượng các phiếu xuất kho đã duyệt trên MMS, theo đơn vị kho của MMS.">
                                            Số Lượng Xuất Kho</th>
                                        <th class="coc-user" style="min-width: 170px;"
                                            title="Ngày lập phiếu COC, người dùng nhập (dd/mm/yyyy), sửa xong ô tự lưu. Chỉ mở khi lô đã có ngày và số lượng xuất kho. Cần quyền Nhập Ngày Lập Phiếu COC. Bấm biểu tượng lịch sử để xem các lần khai báo.">
                                            Ngày Lập Phiếu COC</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($datas as $index => $data)
                                        @php
                                            $bom = $bomVersions->get($data->id);
                                            $out = $stockOuts->get($data->id);
                                            $in = $stockIns->get($data->id);
                                            $kcsRow = $kcs->get($data->id);
                                            $coc = $cocRecords->get($data->id);
                                            $inTitle = $in
                                                ? collect($in['receipts'])->map(fn($r) => 'Phiếu ' . $r['grn'] . ' - ' . $dmy($r['date'])
                                                    . ' - SL ' . $qty($r['qty']))->implode("\n")
                                                : 'Chưa có phiếu nhập kho trên MMS';
                                            $outTitle = $out
                                                ? collect($out['issues'])->map(fn($i) => 'Phiếu ' . $i['issue'] . ' - ' . $dmy($i['date'])
                                                    . ' - SL ' . $qty($i['qty']) . ($i['rem'] !== '' ? ' (' . $i['rem'] . ')' : ''))->implode("\n")
                                                : 'Chưa có phiếu xuất kho trên MMS';
                                        @endphp
                                        <tr data-row data-id="{{ $data->id }}" class="{{ $coc?->coc_date ? 'coc-done' : '' }}">
                                            <td class="text-center align-middle coc-sticky coc-sticky-1">{{ $index + 1 }}</td>
                                            <td class="text-center align-middle coc-sticky coc-sticky-2">
                                                {{ $data->order_number_R1 ?: '' }}
                                                @if ($data->order_number_R2)
                                                    <div class="text-muted small">{{ $data->order_number_R2 }}</div>
                                                @endif
                                            </td>
                                            <td class="text-center align-middle font-weight-bold coc-sticky coc-sticky-3">
                                                {{ $data->actual_batch ?: $data->batch }}
                                            </td>
                                            <td class="text-center align-middle coc-sticky coc-sticky-4">
                                                <button type="button" class="btn btn-xs btn-outline-primary btn-coc-materials"
                                                    data-tab="materials" title="Nguyên liệu sử dụng">NL</button>
                                                <button type="button" class="btn btn-xs btn-outline-success btn-coc-materials"
                                                    data-tab="packagings" title="Bao bì sử dụng">BB</button>
                                            </td>
                                            <td class="align-middle coc-sticky coc-sticky-5">{{ $data->product_name }}</td>
                                            <td class="text-center align-middle">
                                                {{ $data->intermediate_code }}
                                                @if ($bom?->btp_version)
                                                    <span class="badge badge-secondary" title="Version BOM lúc có số lệnh">v{{ $bom->btp_version }}</span>
                                                @endif
                                            </td>
                                            <td class="text-center align-middle">
                                                {{ $data->finished_product_code }}
                                                @if ($bom?->tp_version)
                                                    <span class="badge badge-secondary" title="Version BOM lúc có số lệnh">v{{ $bom->tp_version }}</span>
                                                @endif
                                            </td>
                                            <td class="align-middle">{{ $data->market }}</td>
                                            <td class="align-middle">{{ $data->specification }}</td>
                                            <td class="text-right align-middle" data-sort="{{ (float) $data->batch_qty }}">
                                                {{ $qty($data->batch_qty) }} {{ $data->unit_batch_qty }}
                                            </td>
                                            <td class="text-center align-middle" data-sort="{{ $in['mfg_date'] ?? '' }}">
                                                {{ $dmy(($in['mfg_date'] ?? '') ?: null) }}
                                            </td>
                                            <td class="text-center align-middle" data-sort="{{ $in['exp_date'] ?? '' }}">
                                                {{ $dmy(($in['exp_date'] ?? '') ?: null) }}
                                            </td>
                                            <td class="text-center align-middle" data-sort="{{ $kcsRow['kcs_date'] ?? '' }}">
                                                {{ $dmy($kcsRow['kcs_date'] ?? null) }}
                                            </td>
                                            <td class="text-center align-middle">{{ $kcsRow['coatp_number'] ?? '' }}</td>
                                            <td class="text-center align-middle" data-sort="{{ $in['date'] ?? '' }}"
                                                title="{{ $inTitle }}">
                                                {{ $dmy($in['date'] ?? null) }}
                                                @if ($in && count($in['receipts']) > 1)
                                                    <span class="badge badge-info">{{ count($in['receipts']) }} phiếu</span>
                                                @endif
                                            </td>
                                            <td class="text-right align-middle" data-sort="{{ $in ? $in['qty'] : '' }}"
                                                title="{{ $inTitle }}">
                                                {{ $in ? $qty($in['qty']) . ' ' . $in['uom'] : '' }}
                                            </td>
                                            <td class="text-center align-middle" data-sort="{{ $out['first_date'] ?? '' }}"
                                                title="{{ $outTitle }}">
                                                {{ $dmy($out['first_date'] ?? null) }}
                                                @if ($out && count($out['issues']) > 1)
                                                    <span class="badge badge-info">{{ count($out['issues']) }} phiếu</span>
                                                @endif
                                            </td>
                                            <td class="text-right align-middle" data-sort="{{ $out ? $out['qty'] : '' }}"
                                                title="{{ $outTitle }}">
                                                {{ $out ? $qty($out['qty']) . ' ' . ($in['uom'] ?? '') : '' }}
                                            </td>
                                            @php
                                                // Chỉ mở ô khi lô đã có ngày + số lượng xuất kho (server kiểm tra lại khi lưu)
                                                $hasStockOut = $out && $out['first_date'] && $out['qty'] > 0;
                                                $cocLockReason = !$canUpdate
                                                    ? 'Bạn chưa có quyền Nhập Ngày Lập Phiếu COC'
                                                    : (!$hasStockOut ? 'Lô chưa có ngày và số lượng xuất kho trên MMS' : '');
                                            @endphp
                                            <td class="align-middle" data-sort="{{ $coc?->coc_date ?? '' }}">
                                                <div class="d-flex align-items-center">
                                                    <input type="text" class="form-control form-control-sm coc-date coc-input"
                                                        name="coc_date" placeholder="dd/mm/yyyy" autocomplete="off"
                                                        value="{{ $dmy($coc?->coc_date) }}" title="{{ $cocLockReason }}"
                                                        {{ $cocLockReason !== '' ? 'disabled' : '' }}>
                                                    <button type="button" class="btn btn-xs btn-link px-1 btn-coc-history"
                                                        title="Lịch sử khai báo Ngày Lập Phiếu COC">
                                                        <i class="fas fa-history"></i>
                                                    </button>
                                                </div>
                                                <div class="coc-declared text-muted">
                                                    @if ($coc?->updated_by)
                                                        {{ $coc->updated_by }} - {{ \Carbon\Carbon::parse($coc->updated_at)->format('d/m/Y H:i') }}
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="19" class="text-center text-muted py-4">
                                                Không có lô thị trường Châu Âu nào trong khoảng đã chọn
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>

    <div class="modal fade" id="cocMaterialsModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-xl" role="document" style="max-width: 95%;">
            <div class="modal-content">
                <div class="modal-header py-2">
                    <h5 class="modal-title">
                        <i class="fas fa-boxes text-primary"></i> Nguyên Liệu &amp; Bao Bì Sử Dụng - Lô
                        <span id="cocMaterialsBatch"></span>
                        <small class="text-muted" id="cocMaterialsProduct"></small>
                    </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body p-0" id="cocMaterialsBody"></div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="cocHistoryModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header py-2">
                    <h5 class="modal-title">
                        <i class="fas fa-history text-primary"></i> Lịch Sử Khai Báo Ngày Lập Phiếu COC - Lô
                        <span id="cocHistoryBatch"></span>
                    </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body p-0" id="cocHistoryBody" style="max-height: calc(100vh - 200px); overflow-y: auto;"></div>
            </div>
        </div>
    </div>

    <style>
        .coc-filter {
            background-color: #f8f9fa;
            border: 1px solid #e3e6ea;
            border-radius: 6px;
            padding: 10px 12px 2px;
            margin-bottom: 8px;
        }

        .coc-filter .form-group {
            margin-bottom: 8px;
        }

        .coc-filter-label {
            display: flex;
            align-items: center;
            gap: 5px;
            font-size: 12px;
            font-weight: 600;
            color: #495057;
            margin-bottom: 3px;
            white-space: nowrap;
        }

        .coc-filter-muted {
            opacity: .5;
        }

        .coc-filter input.coc-date {
            text-align: center;
        }

        .coc-filter-actions {
            gap: 6px;
        }

        .coc-filter-status {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            gap: 6px;
            font-size: 12px;
            margin-bottom: 8px;
        }

        /* Layout chung đặt body { overflow-y: hidden } nên bảng phải tự cuộn */
        #coc_table_scroll {
            max-height: calc(100vh - 300px);
            overflow: auto;
        }

        #coc_tracking_table thead th {
            position: sticky;
            top: 0;
            z-index: 3;
            font-size: 12px;
            background-color: #f8f9fa;
            cursor: pointer;
            user-select: none;
        }

        #coc_tracking_table thead th.coc-mms {
            background-color: #d4edda;
        }

        #coc_tracking_table td {
            font-size: 12px;
            white-space: nowrap;
        }

        #coc_tracking_table .coc-sort-icon {
            opacity: .35;
            font-size: 10px;
        }

        #coc_tracking_table .coc-sort-icon.coc-sort-active {
            opacity: 1;
        }

        #coc_tracking_table .coc-sticky {
            position: sticky;
            background-color: #fff;
            z-index: 2;
        }

        #coc_tracking_table thead .coc-sticky {
            z-index: 4;
            background-color: #f8f9fa;
        }

        #coc_tracking_table .coc-sticky-1 { left: 0; }
        #coc_tracking_table .coc-sticky-2 { left: 50px; }
        #coc_tracking_table .coc-sticky-3 { left: 150px; }

        #coc_tracking_table .coc-sticky-4 { left: 240px; }

        #coc_tracking_table .coc-sticky-5 {
            left: 320px;
            box-shadow: 2px 0 3px -1px rgba(0, 0, 0, .2);
            white-space: normal;
        }

        #coc_tracking_table tr:hover .coc-sticky {
            background-color: #f2f2f2;
        }

        /* Lô đã có Ngày Lập Phiếu COC: tô xanh nhạt cả dòng (kể cả cột ghim), cùng màu file Excel */
        #coc_tracking_table tbody tr.coc-done td,
        #coc_tracking_table tbody tr.coc-done .coc-sticky {
            background-color: #e2f4e7;
        }

        #coc_tracking_table tbody tr.coc-done:hover td,
        #coc_tracking_table tbody tr.coc-done:hover .coc-sticky {
            background-color: #cdebd6;
        }

        #coc_tracking_table thead th.coc-user {
            background-color: #ffc107;
        }

        #coc_tracking_table input.coc-input {
            min-width: 100px;
            text-align: center;
            font-size: 12px;
            padding: 2px 4px;
            height: auto;
        }

        #coc_tracking_table .coc-declared {
            font-size: 10px;
            white-space: nowrap;
        }

        #coc_tracking_table input.coc-saving {
            background-color: #fff3cd;
        }

        #coc_tracking_table thead th.coc-nosort {
            cursor: default;
        }

        #coc_tracking_table .btn-coc-materials {
            padding: 0 6px;
            font-size: 11px;
            line-height: 1.6;
        }

        /* Modal NL / BB: bảng tự cuộn, tiêu đề cột ghim */
        #cocMaterialsBody .coc-mat-scroll {
            max-height: calc(100vh - 260px);
            overflow: auto;
        }

        #cocMaterialsBody .coc-mat-table th {
            position: sticky;
            top: 0;
            z-index: 2;
            background-color: #f8f9fa;
            font-size: 12px;
            white-space: nowrap;
        }

        #cocMaterialsBody .coc-mat-table td {
            font-size: 12px;
            white-space: nowrap;
        }

        #cocMaterialsBody .coc-mat-table td.coc-mat-name {
            white-space: normal;
            min-width: 260px;
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            function fitTableHeight() {
                const box = document.getElementById('coc_table_scroll');
                const footer = document.querySelector('.main-footer');
                const footerHeight = footer && footer.offsetParent !== null ? footer.getBoundingClientRect().height : 0;
                const available = window.innerHeight - box.getBoundingClientRect().top - footerHeight - 24;

                box.style.maxHeight = Math.max(available, 240) + 'px';
            }

            fitTableHeight();
            window.addEventListener('resize', fitTableHeight);
            $(document).on('collapsed.lte.pushmenu shown.lte.pushmenu', fitTableHeight);

            // ---- Sắp xếp theo cột phía trình duyệt (cùng cách trang Theo Dõi Hồ Sơ KCS) ----
            // Bấm: sắp theo cột đó (bấm lại để đảo chiều). Shift + bấm: thêm khoá sắp phụ.
            // Ô trống luôn xuống cuối. Bấm STT để về thứ tự gốc.
            const $table = $('#coc_tracking_table');
            let sortKeys = [];

            $table.find('thead th').each(function(index) {
                $(this).attr('data-col', index);

                if (!$(this).hasClass('coc-nosort')) {
                    $(this).append(' <i class="fas fa-sort coc-sort-icon"></i>');
                }
            });

            function sortValue(td) {
                if (!td) return '';
                if (td.dataset.sort !== undefined) return td.dataset.sort;

                return td.textContent.trim().replace(/\s+/g, ' ');
            }

            function compareValues(a, b) {
                const na = Number(a);
                const nb = Number(b);

                if (!isNaN(na) && !isNaN(nb)) return na - nb;

                return a.localeCompare(b, 'vi', { numeric: true, sensitivity: 'base' });
            }

            function applySort() {
                const $tbody = $table.find('tbody');
                const keyed = $tbody.children('tr[data-row]').get().map(function(tr, i) {
                    return { tr: tr, i: i, v: sortKeys.map(k => sortValue(tr.children[k.col])) };
                });

                keyed.sort(function(x, y) {
                    for (let k = 0; k < sortKeys.length; k++) {
                        const a = x.v[k];
                        const b = y.v[k];

                        if (a === b) continue;
                        if (a === '') return 1;
                        if (b === '') return -1;

                        const diff = compareValues(a, b) * sortKeys[k].dir;

                        if (diff !== 0) return diff;
                    }

                    return x.i - y.i;
                });

                $tbody.append(keyed.map(k => k.tr));

                $table.find('.coc-sort-icon').attr('class', 'fas fa-sort coc-sort-icon');
                sortKeys.forEach(function(key) {
                    $table.find('th[data-col="' + key.col + '"] .coc-sort-icon').attr('class',
                        'fas coc-sort-icon coc-sort-active ' + (key.dir === 1 ? 'fa-sort-up' : 'fa-sort-down'));
                });
            }

            $table.on('click', 'thead th:not(.coc-nosort)', function(e) {
                const col = Number($(this).attr('data-col'));
                const found = sortKeys.findIndex(k => k.col === col);

                if (e.shiftKey) {
                    if (found >= 0) {
                        sortKeys[found].dir = -sortKeys[found].dir;
                    } else {
                        sortKeys.push({ col: col, dir: 1 });
                    }
                } else if (sortKeys.length === 1 && found === 0) {
                    sortKeys[0].dir = -sortKeys[0].dir;
                } else {
                    sortKeys = [{ col: col, dir: 1 }];
                }

                applySort();
            });

            // ---- Ô ngày dd/mm/yyyy ở thanh lọc (cùng cách trang Theo Dõi Hồ Sơ KCS) ----
            function isValidDate(text) {
                text = (text || '').trim();

                if (text === '') return true;

                const m = text.match(/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/);

                if (!m) return false;

                const date = new Date(Number(m[3]), Number(m[2]) - 1, Number(m[1]));

                return date.getFullYear() === Number(m[3]) && date.getMonth() === Number(m[2]) - 1 &&
                    date.getDate() === Number(m[1]);
            }

            $(document).on('focus', 'input.coc-date', function() {
                const $input = $(this);

                if (this.disabled || $input.data('daterangepicker') || typeof $.fn.daterangepicker !== 'function') {
                    return;
                }

                $input.daterangepicker({
                    singleDatePicker: true,
                    autoUpdateInput: false, // để ô được phép trống
                    autoApply: true,
                    showDropdowns: true,
                    locale: {
                        format: 'DD/MM/YYYY',
                        daysOfWeek: ['CN', 'T2', 'T3', 'T4', 'T5', 'T6', 'T7'],
                        monthNames: ['Th1', 'Th2', 'Th3', 'Th4', 'Th5', 'Th6', 'Th7', 'Th8', 'Th9', 'Th10',
                            'Th11', 'Th12'
                        ],
                        firstDay: 1
                    }
                });

                $input.on('apply.daterangepicker', function(e, picker) {
                    const value = picker.startDate.format('DD/MM/YYYY');

                    if (this.value !== value) {
                        this.value = value;
                        $(this).trigger('change');
                    }
                });

                $input.data('daterangepicker').show();
            });

            // ---- Ô Ngày Lập Phiếu COC: tự lưu khi đổi giá trị ----
            $table.on('change', 'input.coc-input', function() {
                const input = this;
                const $cell = $(input).closest('td');
                const valid = isValidDate(input.value);

                $(input).toggleClass('is-invalid', !valid);

                if (!valid) {
                    toastr.error('Ngày "' + input.value + '" không hợp lệ, nhập theo dạng dd/mm/yyyy');
                    return;
                }

                // Gõ 5/1/2026 thì hiện lại đủ 2 chữ số
                const m = input.value.trim().match(/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/);
                if (m) {
                    input.value = m[1].padStart(2, '0') + '/' + m[2].padStart(2, '0') + '/' + m[3];
                }

                $(input).addClass('coc-saving');

                $.ajax({
                    url: '{{ route('pages.plan.coc_tracking.save') }}',
                    method: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        plan_master_id: $(input).closest('tr').data('id'),
                        coc_date: input.value.trim()
                    },
                    success: function(res) {
                        $(input).removeClass('coc-saving');

                        if (!res.success) {
                            toastr.error(res.message || 'Không lưu được');
                            return;
                        }

                        // data-sort theo Y-m-d để sắp xếp cột đúng thứ tự thời gian
                        const iso = m ? m[3] + '-' + m[2].padStart(2, '0') + '-' + m[1].padStart(2, '0') : '';
                        $cell[0].dataset.sort = iso;
                        $(input).closest('tr').toggleClass('coc-done', iso !== '');
                        $cell.find('.coc-declared').text(res.declared_by + ' - ' + res.declared_at);
                        toastr.success('Đã lưu lô ' + $(input).closest('tr').find('.coc-sticky-3').text().trim());
                    },
                    error: function(xhr) {
                        $(input).removeClass('coc-saving');
                        toastr.error(xhr.responseJSON && xhr.responseJSON.message ?
                            xhr.responseJSON.message : 'Lỗi khi lưu dữ liệu');
                    }
                });
            });

            // Chặn gửi ngày sai để bộ lọc không bị bỏ qua trong im lặng
            $('form.coc-filter').on('submit', function(e) {
                let ok = true;

                $(this).find('input.coc-date').each(function() {
                    const valid = isValidDate(this.value);

                    $(this).toggleClass('is-invalid', !valid);
                    ok = ok && valid;
                });

                if (!ok) {
                    e.preventDefault();
                    toastr.error('Ngày xuất kho không hợp lệ, nhập theo dạng dd/mm/yyyy');
                }
            });

            // ---- Lịch sử khai báo Ngày Lập Phiếu COC ----
            $table.on('click', '.btn-coc-history', function() {
                const $row = $(this).closest('tr');

                $('#cocHistoryBatch').text($row.find('.coc-sticky-3').text().trim());
                $('#cocHistoryBody').html('<div class="text-center p-4">Đang tải dữ liệu...</div>');
                $('#cocHistoryModal').modal('show');

                $.ajax({
                    url: '{{ route('pages.plan.coc_tracking.history') }}',
                    method: 'GET',
                    data: {
                        plan_master_id: $row.data('id')
                    },
                    success: function(res) {
                        $('#cocHistoryBody').html(res.html);
                    },
                    error: function() {
                        $('#cocHistoryBody').html('<div class="text-center text-danger p-3">Lỗi khi tải lịch sử</div>');
                    }
                });
            });

            // ---- Modal Nguyên Liệu / Bao Bì sử dụng ----
            // Mỗi lần bấm tải lại từ MMS (~1 giây một lô); nút NL / BB chỉ khác tab được mở sẵn.
            $table.on('click', '.btn-coc-materials', function() {
                const $row = $(this).closest('tr');
                const tab = $(this).data('tab');

                $('#cocMaterialsBatch').text($row.find('.coc-sticky-3').text().trim());
                $('#cocMaterialsProduct').text($row.find('.coc-sticky-5').text().trim());
                $('#cocMaterialsBody').html('<div class="text-center p-4">Đang tải dữ liệu từ MMS...</div>');
                $('#cocMaterialsModal').modal('show');

                $.ajax({
                    url: '{{ route('pages.plan.coc_tracking.materials') }}',
                    method: 'GET',
                    data: {
                        plan_master_id: $row.data('id')
                    },
                    success: function(res) {
                        $('#cocMaterialsBody').html(res.html);
                        $('#coc-mat-tab-' + tab).tab('show');
                    },
                    error: function(xhr) {
                        const message = xhr.responseJSON && xhr.responseJSON.message ?
                            xhr.responseJSON.message : 'Lỗi khi tải dữ liệu';

                        $('#cocMaterialsBody').html('<div class="text-center text-danger p-3"></div>')
                            .find('div').text(message);
                    }
                });
            });
        });
    </script>
@endsection
