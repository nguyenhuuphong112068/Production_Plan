@php
    // Hai quyền nhập tách riêng nên khoá theo từng nhóm cột
    $disabledPo = $canUpdatePo ? '' : 'disabled';
    $disabledSampling = $canUpdateSampling ? '' : 'disabled';

    // Tab Sản Phẩm Việt Nam không hiển thị Số PO (không áp dụng cho thị trường nội địa)
    $showPo = $showPo ?? true;

    // Cùng bảng màu với cột Tình Trạng của lưới Kế Hoạch Sản Xuất Tháng
    $statusColors = [
        'Chưa làm' => 'background-color: green; color: white;',
        'Đã Cân' => 'background-color: #e3f2fd; color: #0d47a1;',
        'Đã Pha chế' => 'background-color: #bbdefb; color: #0d47a1;',
        'Đã THT' => 'background-color: #90caf9; color: #0d47a1;',
        'Đã định hình' => 'background-color: #64b5f6; color: white;',
        'Đã Bao phim' => 'background-color: #1e88e5; color: white;',
        'Hoàn Tất ĐG' => 'background-color: #0d47a1; color: white;',
        'Hoàn Tất' => 'background-color: #0d47a1; color: white;',
    ];
@endphp

@if ($datas->isEmpty())
    <div class="alert alert-secondary py-3 mb-0 text-center">
        <i class="fas fa-inbox"></i> {{ $emptyText }}
    </div>
@else
    <div class="table-responsive" id="{{ $tableId }}_scroll">
        <table class="table table-bordered table-striped table-hover mb-0 pkg-table" id="{{ $tableId }}">
            <thead class="text-center align-middle">
                <tr>
                    <th class="pkg-sticky pkg-sticky-1 bg-light" style="min-width: 50px;">STT</th>
                    <th class="pkg-sticky pkg-sticky-2 bg-light" style="min-width: 90px;">Số Lô</th>
                    <th class="pkg-sticky pkg-sticky-3 bg-light" style="min-width: 200px;">Tên Sản Phẩm</th>
                    <th class="bg-light" style="min-width: 130px;">Mã TP</th>
                    <th class="bg-light" style="min-width: 130px;">Mã BTP</th>
                    <th class="bg-light" style="min-width: 110px;">Thị Trường</th>
                    <th class="bg-light" style="min-width: 150px;">Quy Cách</th>
                    <th class="bg-light" style="min-width: 100px;">Cỡ Lô</th>
                    <th class="bg-light" style="min-width: 90px;">Tỉ Lệ ĐG</th>
                    <th class="bg-light" style="min-width: 200px;">
                        Thời Gian Đóng Gói Dự Kiến
                        <div class="mt-1">
                            <input type="date" class="form-control form-control-sm pkg-date-filter"
                                title="Lọc theo ngày đóng gói dự kiến">
                        </div>
                    </th>

                    @if ($showPo)
                        <th class="bg-warning" style="min-width: 130px;">Số PO</th>
                    @endif
                    <th class="bg-warning" style="min-width: 260px;">Mẫu Đóng Gói Sơ Cấp</th>
                    <th class="bg-warning" style="min-width: 260px;">Mẫu Đóng Gói Thứ Cấp</th>
                    <th class="bg-warning" style="min-width: 220px;">Lý Do</th>
                    <th class="bg-light" style="min-width: 120px;">Khoá/Mở Khoá</th>
                    <th class="bg-warning" style="min-width: 130px;">Xác Nhận Đã Lấy Mẫu</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($datas as $index => $data)
                    @php
                        $record = $records->get($data->id);
                        $stage = $stageInfo[$data->id] ?? null;
                        $status = $stage['status'] ?? 'Chưa làm';
                        $isLocked = (bool) ($record->is_locked ?? false);
                        // Mẫu Sơ Cấp/Thứ Cấp/Lý Do khoá riêng, cộng thêm điều kiện quyền lấy
                        // mẫu sẵn có - khoá thì disable dù có quyền, chưa khoá thì theo quyền
                        $lockDisabled = ($disabledSampling || $isLocked) ? 'disabled' : '';
                        $expectedDate = $stage && $stage['start'] ? \Carbon\Carbon::parse($stage['start'])->format('Y-m-d') : '';

                        // Chỉ xác nhận được khi đã nhập đủ Mẫu Sơ Cấp/Thứ Cấp/Lý Do VÀ dòng
                        // đang khoá (dữ liệu đã chốt) - khớp điều kiện kiểm tra ở server
                        $samplesFilled = trim((string) ($record->primary_sample ?? '')) !== ''
                            && trim((string) ($record->secondary_sample ?? '')) !== ''
                            && trim((string) ($record->Reason ?? '')) !== '';
                        $canConfirm = $samplesFilled && $isLocked;
                    @endphp
                    <tr data-plan-master-id="{{ $data->id }}" data-batch="{{ $data->batch }}"
                        data-expected-date="{{ $expectedDate }}" class="{{ $record ? '' : 'table-light' }}">
                        <td class="pkg-sticky pkg-sticky-1 text-center">{{ $index + 1 }}</td>
                        <td class="pkg-sticky pkg-sticky-2 text-center font-weight-bold">
                            {{ $data->batch }}
                            <button type="button" class="btn btn-link btn-sm p-0 ml-1 pkg-history"
                                title="Xem lịch sử nhập của lô này">
                                <i class="fas fa-history text-muted"></i>
                            </button>
                            @unless ($record)
                                <i class="fas fa-circle text-muted pkg-unsaved" style="font-size: 6px;"
                                    title="Lô chưa có dòng thông báo đóng gói - sẽ được tạo khi bạn nhập và lưu."></i>
                            @endunless
                            @if ($record && $record->is_manual)
                                <div class="mt-1">
                                    <span class="badge badge-info"
                                        title="Lô nằm ngoài quy tắc tự động, do người dùng tạo thêm">Thông báo
                                        khác</span>
                                    @if ($canAdd)
                                        <button type="button" class="btn btn-link btn-sm p-0 text-danger pkg-remove"
                                            title="Gỡ thông báo đóng gói của lô này">
                                            <i class="fas fa-times-circle"></i>
                                        </button>
                                    @endif
                                </div>
                            @endif
                        </td>
                        <td class="pkg-sticky pkg-sticky-3">{{ $data->finished_product_name }}</td>
                        <td class="text-center">{{ $data->finished_product_code }}</td>
                        <td class="text-center">{{ $data->intermediate_code }}</td>
                        <td class="text-center">{{ $data->market }}</td>
                        <td>{{ $data->specification }}</td>
                        <td class="text-center">
                            {{ $data->batch_qty ? number_format($data->batch_qty) : '' }}
                            {{ $data->unit_batch_qty }}
                        </td>
                        <td class="text-center">
                            {{ $data->percent_parkaging !== null ? round($data->percent_parkaging * 100, 2) . '%' : '' }}
                        </td>
                        <td class="text-center">
                            @if ($stage && $stage['start'])
                                <div>
                                    {{ \Carbon\Carbon::parse($stage['start'])->format('d/m/Y H:i') }}
                                    @if ($stage['end'])
                                        <span class="text-muted">→</span>
                                        {{ \Carbon\Carbon::parse($stage['end'])->format('d/m H:i') }}
                                    @endif
                                </div>
                            @else
                                <div class="text-muted font-italic">Chưa xếp lịch</div>
                            @endif
                            @if ($stage && $stage['actual_start'])
                                <div class="small text-success">
                                    TT: {{ \Carbon\Carbon::parse($stage['actual_start'])->format('d/m H:i') }}
                                    @if ($stage['actual_end'])
                                        → {{ \Carbon\Carbon::parse($stage['actual_end'])->format('d/m H:i') }}
                                    @endif
                                </div>
                            @endif
                            <span class="badge mt-1"
                                style="{{ $statusColors[$status] ?? 'background-color: #6c757d; color: white;' }}">
                                {{ $status }}
                            </span>
                        </td>

                        @if ($showPo)
                            <td>
                                <input type="text" class="form-control form-control-sm pkg-input" name="PO_no"
                                    maxlength="50" value="{{ $record->PO_no ?? '' }}" {{ $disabledPo }}>
                            </td>
                        @endif
                        <td>
                            <textarea class="form-control form-control-sm pkg-input" name="primary_sample" rows="3" maxlength="2000"
                                {{ $lockDisabled }}>{{ $record->primary_sample ?? '' }}</textarea>
                        </td>
                        <td>
                            <textarea class="form-control form-control-sm pkg-input" name="secondary_sample" rows="3" maxlength="2000"
                                {{ $lockDisabled }}>{{ $record->secondary_sample ?? '' }}</textarea>
                        </td>
                        <td>
                            <textarea class="form-control form-control-sm pkg-input" name="Reason" rows="3" maxlength="255"
                                {{ $lockDisabled }}>{{ $record->Reason ?? '' }}</textarea>
                        </td>
                        <td class="text-center">
                            @if (!$record)
                                <span class="text-muted small">Chưa có dữ liệu</span>
                            @else
                                @php
                                    $lockToggleClass = $isLocked ? 'btn-danger' : 'btn-outline-secondary';
                                    $lockToggleIcon = $isLocked ? 'fa-lock' : 'fa-lock-open';
                                    $lockToggleText = $isLocked ? 'Đã Khoá' : 'Mở Khoá';
                                    $lockToggleTitle = $isLocked
                                        ? 'Đã khoá Mẫu Đóng Gói Sơ Cấp, Thứ Cấp và Lý Do - bấm để mở khoá (yêu cầu nhập lý do)'
                                        : 'Đang mở, sửa được bình thường - bấm để khoá lại';
                                @endphp
                                <button type="button" class="btn btn-sm {{ $lockToggleClass }} pkg-lock-toggle"
                                    data-locked="{{ $isLocked ? 1 : 0 }}" title="{{ $lockToggleTitle }}"
                                    {{ $canLock ? '' : 'disabled' }}>
                                    <i class="fas {{ $lockToggleIcon }}"></i> {{ $lockToggleText }}
                                </button>
                            @endif
                        </td>
                        <td class="text-center">
                            <input type="checkbox" class="pkg-input pkg-confirm-check" name="sampled_confirmed"
                                style="width: 20px; height: 20px;"
                                {{ $record && $record->sampled_confirmed ? 'checked' : '' }}
                                {{ $disabledSampling || !$canConfirm ? 'disabled' : '' }}
                                @unless ($canConfirm)
                                    title="Chỉ xác nhận được khi đã nhập đủ Mẫu Đóng Gói Sơ Cấp, Thứ Cấp, Lý Do và dòng đang ở trạng thái khoá."
                                @endunless>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif
