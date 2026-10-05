{{-- Thân trang Ghi Nhận Sản Xuất: dải ca đang phân công + lưới phòng. Trả riêng khi tự làm mới (?partial=1). --}}
@php
    $hm = fn($t) => \Carbon\Carbon::parse($t)->format('H:i');
@endphp

@if ($assignments->isNotEmpty())
    <div class="rec-shifts">
        <span class="rec-shifts-label"><i class="fas fa-user-clock"></i> Đang phân công:</span>
        @foreach ($assignments as $a)
            <span class="rec-shift"><b>{{ $a->room_code }}</b> · {{ $a->shift }} · {{ $hm($a->start) }} – {{ $hm($a->end) }}</span>
        @endforeach
    </div>
@endif

@if ($stages->isNotEmpty())
    @include('pages.Schedual.execution._board')
@else
    <div class="exec-empty">
        <i class="fas fa-user-clock fa-3x mb-3"></i>
        @if (!$employee)
            <div>Tài khoản <b>{{ session('user')['userName'] }}</b> chưa khớp mã nhân viên (MSNV) nào trong danh sách nhân sự.</div>
            <div class="small mt-1">Liên hệ quản lý để kiểm tra tên đăng nhập.</div>
        @else
            <div>Bạn chưa được phân công phòng nào của {{ $production }} vào lúc này.</div>
            <div class="small mt-1">Phòng hiện ra khi tới giờ ca trên Lịch Công Tác → Sản Xuất. Trang tự làm mới mỗi phút.</div>
        @endif
    </div>
@endif
