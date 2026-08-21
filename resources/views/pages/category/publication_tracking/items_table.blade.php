{{-- Cột tích chọn chỉ phục vụ nút "Tạo nội dung theo dõi", không có quyền đó thì bỏ luôn --}}
@if ($showPicker)
    <div class="d-flex justify-content-between align-items-center mb-2">
        <div>
            <small class="text-muted">
                <i class="fas fa-info-circle"></i>
                Tích chọn các mã ở đây rồi bấm <strong>Tạo nội dung theo dõi</strong>, hoặc bấm thẳng nút đó
                rồi chọn mã trong bảng chọn.
            </small>
        </div>
        <div>
            <span class="badge badge-light border" id="selected_count_{{ $type }}">Đã chọn: 0</span>
        </div>
    </div>
@endif

{{-- Lọc theo kế hoạch tháng: chưa lập kế hoạch thì không có gì để lọc, ẩn luôn cho gọn --}}
@if ($hasMonthlyPlan)
    @php
        $plannedCount = $items
            ->filter(fn($i) => isset($plannedBatches[$i->category_type . '-' . $i->category_id]))
            ->count();

        // Mã có ít nhất 1 lô phát sinh so với kế hoạch dự kiến (plan_master.additional = 1)
        $additionalCount = $items
            ->filter(fn($i) => ($plannedBatches[$i->category_type . '-' . $i->category_id]['additional_count'] ?? 0) > 0)
            ->count();
    @endphp

    <div class="d-flex align-items-center flex-wrap mb-2">
        <label class="mb-0 mr-2" for="filter_planned_{{ $type }}">
            <i class="fas fa-calendar-check text-danger"></i> Kế hoạch {{ $planLabel }}:
        </label>
        <select class="form-control form-control-sm w-auto mr-3 pt-filter-planned"
            id="filter_planned_{{ $type }}" data-table="{{ $tableId }}">
            <option value="">Tất cả mã ({{ $items->count() }})</option>
            <option value="1">Có kế hoạch ({{ $plannedCount }})</option>
            <option value="0">Chưa có kế hoạch ({{ $items->count() - $plannedCount }})</option>
        </select>

        <label class="mb-0 mr-2" for="filter_additional_{{ $type }}">
            <i class="fas fa-bolt text-warning"></i> Lô phát sinh:
        </label>
        <select class="form-control form-control-sm w-auto pt-filter-additional"
            id="filter_additional_{{ $type }}" data-table="{{ $tableId }}">
            <option value="">Tất cả mã ({{ $items->count() }})</option>
            <option value="1">Có lô phát sinh ({{ $additionalCount }})</option>
            <option value="0">Không có lô phát sinh ({{ $items->count() - $additionalCount }})</option>
        </select>
    </div>

    <div class="mb-2">
        <small class="text-muted">
            <i class="fas fa-info-circle"></i>
            Mã có nhãn <span class="badge badge-danger"><i class="fas fa-calendar-check"></i> KH</span>
            sẽ được sản xuất trong {{ $planLabel }} &mdash;.
            Nhãn <span class="badge badge-warning"><i class="fas fa-bolt"></i> PS</span>
            là lô phát sinh so với kế hoạch dự kiến.
        </small>
    </div>
@else
    <div class="mb-2">
        <small class="text-muted font-italic">
            <i class="fas fa-info-circle"></i>
            Chưa có kế hoạch sản xuất {{ $planLabel }} cho phân xưởng này, chưa lọc được theo kế hoạch tháng.
        </small>
    </div>
@endif

{{-- Không bọc bảng trong khung cuộn riêng: để cả trang cuộn như các bảng Dữ Liệu Gốc,
     nhờ vậy bảng cao hết màn hình và thanh phân trang luôn nằm dưới bảng, không bị che. --}}
<table id="{{ $tableId }}" class="table table-bordered table-striped w-100 pt-items-table">
    <thead style="position: sticky; top: 60px; background-color: white; z-index: 1020;">
        <tr>
            @if ($showPicker)
                <th class="text-center align-middle" style="width: 38px;">
                    <input type="checkbox" class="pt-check-all" data-type="{{ $type }}" title="Chọn tất cả">
                </th>
            @endif
            <th class="text-center align-middle" style="width: 50px;">STT</th>
            <th class="text-center align-middle" style="width: 120px;">{{ $codeLabel }}</th>
            <th class="text-center align-middle" style="width: 15%;">Tên Sản Phẩm</th>
            <th class="text-center align-middle" style="width: 95px;">Cỡ Lô</th>
            @if ($showMarket)
                <th class="text-center align-middle" style="width: 130px;">Thị Trường / Qui Cách</th>
            @endif
            <th class="text-center align-middle" style="width: 110px;">Dạng Bào Chế</th>
            <th class="text-center align-middle" style="width: 130px;">Dược Sĩ Phụ Trách</th>
            <th class="text-center align-middle" style="width: 20%;">Nội Dung Theo Dõi</th>
            <th class="text-center align-middle" style="width: 150px;">Quyết Định</th>
            <th class="text-center align-middle" style="width: 20%;">Kết Quả Thực Hiện</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($items as $index => $item)
            @php
                // Sắp xếp công việc theo thứ tự tạo để 3 cột cuối luôn thẳng hàng nhau
                $taskItems = $item->taskItems->sortBy('id')->values();

                // Ai bấm quyết định / ai ghi nhận kết quả và lúc nào; 2 ô ghi vết riêng
                $decisionMeta = trim(
                    $item->decision_by . ($item->decision_at ? ' · ' . $item->decision_at->format('d/m/Y H:i') : ''),
                    ' ·',
                );
                $resultMeta = trim(
                    $item->result_by . ($item->result_at ? ' · ' . $item->result_at->format('d/m/Y H:i') : ''),
                    ' ·',
                );
                $opinionMeta = trim(
                    $item->opinion_by . ($item->opinion_at ? ' · ' . $item->opinion_at->format('d/m/Y H:i') : ''),
                    ' ·',
                );
                $readyMeta = trim(
                    $item->ready_by . ($item->ready_at ? ' · ' . $item->ready_at->format('d/m/Y H:i') : ''),
                    ' ·',
                );

                // Quyền tạo hàng loạt thao tác được trên mọi mã; quyền thêm từng mã
                // chỉ thao tác được trên mã do chính mình phụ trách
                $canManage =
                    $manageAll || ($manageOwn && $item->pharmacist_id && $item->pharmacist_id == $currentUserId);

                // Mã không có trong danh sách nghĩa là MMS chưa có công thức cho mã đó
                $bomRevision = $bomRevisions[trim($item->code)] ?? null;

                // Kế hoạch tháng của mã; null = tháng này không có lô nào của mã đó
                $planned = $plannedBatches[$item->category_type . '-' . $item->category_id] ?? null;
                $plannedLots = collect($planned['lots'] ?? []);

                // Phần lô phát sinh so với kế hoạch dự kiến, là tập con của số lô trên
                $additionalBatches = $planned['additional_count'] ?? 0;
                $additionalLots = collect($planned['additional_lots'] ?? []);
            @endphp
            <tr data-detail-id="{{ $item->id }}" data-can-manage="{{ $canManage ? 1 : 0 }}"
                data-planned="{{ $planned ? 1 : 0 }}" data-additional="{{ $additionalBatches ? 1 : 0 }}">
                @if ($showPicker)
                    <td class="text-center align-middle">
                        <input type="checkbox" class="pt-row-check" data-type="{{ $type }}"
                            value="{{ $item->id }}">
                    </td>
                @endif
                <td class="text-center align-middle">{{ $index + 1 }}</td>
                <td class="align-middle pt-code-cell">
                    <div class="font-weight-bold text-primary">{{ $item->code }}</div>
                    @if ($item->process_code)
                        <small class="text-muted">{{ $item->process_code }}</small>
                    @endif
                    {{-- Version công thức hiện hành trên MMS: mã chưa có công thức thì không có badge --}}
                    @if ($bomRevision !== null)
                        <div>
                            <span class="badge badge-light border border-secondary"
                                title="Version công thức hiện hành trên MMS">V{{ $bomRevision }}</span>
                        </div>
                    @endif

                    {{-- Mã đã có trong kế hoạch tháng: dấu hiệu để ưu tiên ra quyết định --}}
                    @if ($planned)
                        <div>
                            <span class="badge badge-danger pt-planned-badge"
                                title="Kế hoạch {{ $planLabel }}: {{ $planned['count'] }} lô{{ $plannedLots->isNotEmpty() ? ' — số lô ' . $plannedLots->implode(', ') : '' }}">
                                <i class="fas fa-calendar-check"></i> KH {{ $planned['count'] }} lô
                            </span>
                        </div>
                    @endif

                    {{-- Lô phát sinh ngoài kế hoạch dự kiến: nhãn riêng vì đây là phần chen
                         ngang kế hoạch, dược sĩ cần thấy tách bạch với số lô đã dự kiến --}}
                    @if ($additionalBatches)
                        <div>
                            <span class="badge badge-warning pt-additional-badge"
                                title="Lô phát sinh so với kế hoạch dự kiến: {{ $additionalBatches }} lô{{ $additionalLots->isNotEmpty() ? ' — số lô ' . $additionalLots->implode(', ') : '' }}">
                                <i class="fas fa-bolt"></i> PS {{ $additionalBatches }} lô
                            </span>
                        </div>
                    @endif
                </td>
                <td class="align-middle pt-name-cell">{{ $item->product_name }}</td>
                <td class="text-center align-middle">{{ $item->batch_size }}</td>
                @if ($showMarket)
                    <td class="text-center align-middle">
                        <div>{{ $item->market }}</div>
                        {{-- Qui cách nằm chung ô với thị trường, mã cũ chưa đồng bộ thì bỏ trống --}}
                        @if ($item->specification)
                            <small class="text-muted">{{ $item->specification }}</small>
                        @endif
                    </td>
                @endif
                <td class="text-center align-middle">{{ $item->dosage_name }}</td>
                <td class="align-middle">
                    @if ($item->pharmacist_name)
                        {{ $item->pharmacist_name }}
                    @else
                        <small class="text-muted font-italic">Chưa gán trong danh mục</small>
                    @endif
                </td>

                {{-- Nội dung theo dõi: mỗi công việc là 1 badge có thanh nút sửa / xoá / chấp nhận.
                     Ô này cũng mang class pt-detail để textarea "Ý kiến DSPT" bên dưới được lưu
                     chung 1 request với 2 ô Quyết Định / Kết Quả của cùng mã. --}}
                <td class="align-middle p-1 pt-detail" data-detail-id="{{ $item->id }}">
                    {{-- Hồ sơ sẵn sàng: cờ dược sĩ phụ trách tự tick, cùng quyền với nội dung
                         theo dõi của mã này. Mặc định false - mã chưa ai xác nhận vẫn coi là
                         hồ sơ chưa sẵn sàng bên trang Phản Hồi KHSX. --}}
                    <div class="pt-ready-box">
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" class="custom-control-input pt-ready"
                                id="pt_ready_{{ $item->id }}" {{ $item->ready ? 'checked' : '' }}
                                {{ $canManage ? '' : 'disabled' }}>
                            <label class="custom-control-label font-weight-bold text-success"
                                for="pt_ready_{{ $item->id }}">Hồ sơ sẵn sàng</label>
                        </div>
                        <div class="pt-meta pt-ready-meta" title="Người xác nhận gần nhất">{{ $readyMeta }}</div>
                    </div>

                    {{-- Thanh 3 nút và ghi chú "đã chấp nhận" do JS dựng cho các dòng đang hiển thị.
                         Bảng có tới cả nghìn mã, in sẵn markup nút cho mọi badge làm HTML phình lên
                         hàng MB chỉ vì thụt lề, trong khi DataTables mỗi lúc chỉ hiện 25 dòng. --}}
                    @forelse ($taskItems as $taskItem)
                        <div class="pt-task-line">
                            <div class="pt-badge{{ $taskItem->moved_to_item_id ? ' pt-badge-moved' : '' }}"
                                data-item-id="{{ $taskItem->id }}" data-task-id="{{ $taskItem->task_id }}"
                                data-task-count="{{ $taskItem->task->items_count ?? 1 }}"
                                data-moved-to="{{ optional($taskItem->movedToPeriod)->label }}"
                                data-moved-by="{{ $taskItem->moved_by }}"
                                data-updated-by="{{ $taskItem->task->updated_by ?: $taskItem->task->created_by }}"
                                data-updated-at="{{ optional($taskItem->task->updated_at ?: $taskItem->task->created_at)->format('d/m/Y H:i') }}">
                                <div class="pt-badge-text">{{ $taskItem->task->content }}</div>
                            </div>
                        </div>
                    @empty
                        <div class="pt-task-line text-muted font-italic">Chưa có nội dung theo dõi</div>
                    @endforelse

                    {{-- Luôn có: nút thêm nội dung (khi được sửa) và nút xem lịch sử chung của mã --}}
                    <div class="pt-add-slot text-center pt-1"></div>

                    {{-- Ý kiến của dược sĩ phụ trách về các nội dung theo dõi của mã này --}}
                    <div class="pt-opinion-box">
                        <label class="pt-opinion-label mb-0">Ý kiến DSPT</label>
                        <textarea class="form-control form-control-sm pt-opinion" rows="2" placeholder="Ý kiến của dược sĩ phụ trách..."
                            {{ $canManage ? '' : 'readonly' }}>{{ $item->pharmacist_opinion }}</textarea>
                        <div class="pt-meta pt-opinion-meta" title="Người ghi ý kiến gần nhất">{{ $opinionMeta }}
                        </div>
                    </div>
                </td>

                {{-- Quyết định: 1 ô cho cả mã, không phụ thuộc số nội dung theo dõi.
                     Nút bấm giống hệt nhau ở mọi dòng nên để JS dựng cho trang đang xem. --}}
                <td class="align-middle p-1 pt-detail" data-detail-id="{{ $item->id }}">
                    <div class="pt-decision-slot"
                        data-decision="{{ $item->decision === null ? '' : ($item->decision ? '1' : '0') }}"
                        data-due="{{ $item->due_date?->format('Y-m-d') }}"></div>
                    <div class="pt-meta pt-decision-meta" title="Người quyết định gần nhất">{{ $decisionMeta }}</div>
                </td>

                {{-- Kết quả thực hiện: 1 ô cho cả mã. Nhãn Đáp ứng / Không đáp ứng do JS
                     suy ra từ ngày hoàn thành so với ngày dự kiến bên cột Quyết Định.
                     Phân quyền giống cột Nội Dung Theo Dõi: theo dược sĩ phụ trách của mã. --}}
                <td class="align-middle p-1 pt-detail" data-detail-id="{{ $item->id }}">
                    <div class="pt-result-badge text-center mb-1"></div>
                    <input type="date" class="form-control form-control-sm pt-completed-date"
                        value="{{ $item->completed_date?->format('Y-m-d') }}" title="Ngày hoàn thành"
                        {{ $canManage ? '' : 'readonly' }}>
                    <textarea class="form-control form-control-sm mt-1 pt-comment" rows="2" placeholder="Ghi chú..."
                        {{ $canManage ? '' : 'readonly' }}>{{ $item->comment }}</textarea>
                    <div class="pt-meta pt-result-meta" title="Người ghi nhận kết quả gần nhất">{{ $resultMeta }}
                    </div>
                </td>
            </tr>
        @endforeach
    </tbody>
</table>
