@if ($histories->isEmpty())
    <div class="text-center text-muted p-4">
        <i class="fas fa-inbox fa-2x mb-2 d-block"></i>
        Lô này chưa có lịch sử chỉnh sửa
    </div>
@else
    <table class="table table-bordered table-sm table-striped mb-0">
        <thead class="bg-light">
            <tr>
                <th class="text-center" style="width: 60px;">STT</th>
                <th style="width: 150px;">Thời Điểm</th>
                <th style="width: 170px;">Người Sửa</th>
                <th style="width: 100px;">Hành Động</th>
                <th>Nội Dung</th>
                <th style="width: 160px;">Giá Trị Cũ</th>
                <th style="width: 160px;">Giá Trị Mới</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($histories as $index => $history)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>{{ optional($history->created_at)->format('d/m/Y H:i') }}</td>
                    <td>{{ $names[$history->changed_by] ?? $history->changed_by }}</td>
                    <td>
                        <span class="badge {{ $history->action === 'create' ? 'badge-success' : 'badge-primary' }}">
                            {{ $history->action_label }}
                        </span>
                    </td>
                    <td class="font-weight-bold">{{ $history->field_label }}</td>
                    <td class="text-muted">{{ $history->old_value_text }}</td>
                    <td class="text-primary font-weight-bold">{{ $history->new_value_text }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endif
