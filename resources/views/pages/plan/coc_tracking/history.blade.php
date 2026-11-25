{{-- Lịch sử khai báo Ngày Lập Phiếu COC của một lô (CocTrackingController::history) --}}
@php
    $dmy = fn($date) => $date ? \Carbon\Carbon::parse($date)->format('d/m/Y') : '';
@endphp

<table class="table table-bordered table-sm table-hover mb-0">
    <thead class="text-center">
        <tr>
            <th style="width: 50px;">STT</th>
            <th>Thời Điểm Khai Báo</th>
            <th>Người Khai Báo</th>
            <th>Ngày Lập Phiếu COC Cũ</th>
            <th>Ngày Lập Phiếu COC Mới</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($histories as $index => $history)
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td class="text-center">{{ \Carbon\Carbon::parse($history->created_at)->format('d/m/Y H:i') }}</td>
                <td>{{ $history->changed_by }}</td>
                <td class="text-center text-muted">{{ $dmy($history->old_value) ?: '(trống)' }}</td>
                <td class="text-center font-weight-bold">{{ $dmy($history->new_value) ?: '(xoá)' }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="5" class="text-center text-muted py-4">Lô này chưa có lần khai báo Ngày Lập Phiếu COC nào</td>
            </tr>
        @endforelse
    </tbody>
</table>
