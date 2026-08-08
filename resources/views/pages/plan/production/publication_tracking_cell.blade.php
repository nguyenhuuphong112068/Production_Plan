{{-- Nội dung "Theo dõi lên ấn bản" của kỳ trùng tháng kế hoạch, hiển thị lại trong
     cột Đảm Bảo Chất Lượng. Chỉ đọc: mọi thao tác sửa vẫn nằm ở trang theo dõi.
     $period: PublicationTrackingPeriod, $entries: [['type' => 'TP'|'BTP', 'detail' => ...]] --}}
<div class="pt-inline">
    <a href="{{ route('pages.category.publication_tracking.show', $period->id) }}" target="_blank"
        class="pt-inline-title" title="Mở trang Theo Dõi Lên Ấn Bản {{ $period->label }}">
        <i class="fas fa-file-alt"></i> Theo Dõi Lên Ấn Bản – {{ $period->label }}
        <i class="fas fa-external-link-alt small"></i>
    </a>

    @foreach ($entries as $entry)
        @php
            $detail = $entry['detail'];
            $due = $detail->due_date?->format('Y-m-d');
            $completed = $detail->completed_date?->format('Y-m-d');

            // Ở cột QA, hồ sơ mới là thứ người dùng quan tâm chứ không phải loại mã:
            // mã thành phẩm gắn với BPR, mã bán thành phẩm gắn với BMR
            $record_label = ['TP' => 'BPR', 'BTP' => 'BMR'][$entry['type']] ?? $entry['type'];
        @endphp

        <div class="pt-inline-item">
            <div class="pt-inline-code">
                <span class="badge badge-secondary">{{ $record_label }}</span> {{ $detail->code }}
            </div>

            @forelse ($detail->taskItems->sortBy('id') as $taskItem)
                <div class="pt-inline-task {{ $taskItem->moved_to_item_id ? 'text-muted' : '' }}">
                    • {{ $taskItem->task->content ?? '' }}
                    @if ($taskItem->moved_to_item_id)
                        <span class="badge badge-light border">đã chuyển kỳ sau</span>
                    @endif
                </div>
            @empty
                <div class="pt-inline-task text-muted font-italic">• Chưa có nội dung theo dõi</div>
            @endforelse

            <div class="pt-inline-meta">
                Quyết định:
                @if ($detail->decision)
                    <span class="text-success font-weight-bold">Có thực hiện</span>
                    @if ($due)
                        · dự kiến {{ $detail->due_date->format('d/m/Y') }}
                    @endif
                @else
                    <span class="text-muted">Không thực hiện</span>
                @endif
            </div>

            {{-- Chỉ nêu ngày hoàn thành; đáp ứng hay không đã thấy được khi so với
                 ngày dự kiến ở trên nên không lặp lại badge như trang theo dõi --}}
            @if ($detail->decision && $completed)
                <div class="pt-inline-meta">
                    Kết quả: {{ $detail->completed_date->format('d/m/Y') }}
                </div>
            @endif

            @if (filled($detail->comment))
                <div class="pt-inline-meta font-italic">Ghi chú: {{ $detail->comment }}</div>
            @endif
        </div>
    @endforeach
</div>
