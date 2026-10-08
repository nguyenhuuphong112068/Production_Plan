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

@if (isset($boards))
    {{-- Nhân viên EN / QA: mỗi phân xưởng 1 tab; chỉ phòng Sẵn Sàng / đang giữ bởi lịch bảo trì, có cả phòng chỉ bảo trì.
         Ô tìm phòng / thiết bị ở thanh trên (record.blade) lọc trên mọi tab. --}}
    @if ($boards->isEmpty())
        <div class="exec-empty">
            <i class="fas fa-users-slash fa-3x mb-3"></i>
            <div>Tổ <b>{{ $teamName ?: '(chưa có tổ)' }}</b> không phụ trách phân xưởng nào trên trang Nhận – Trả Phòng.</div>
            <div class="small mt-1">Liên hệ quản lý nếu bạn cần thao tác Nhận / Trả phòng bảo trì.</div>
        </div>
    @else
        <div class="rec-tabs">
            @foreach ($boards as $dept => $deptStages)
                <button type="button" class="rec-tab" data-dept-tab="{{ $dept }}">
                    <i class="fas fa-industry"></i> {{ $dept }} <span class="rec-tab-count">{{ $deptStages->flatten(1)->count() }}</span>
                </button>
            @endforeach
        </div>
        @foreach ($boards as $dept => $deptStages)
            <div class="rec-dept-pane" data-dept-pane="{{ $dept }}">
                <h5 class="rec-dept-title d-none"><i class="fas fa-industry"></i> Phân xưởng {{ $dept }}</h5>
                @include('pages.Schedual.execution._board', [
                    'production' => $dept,
                    'stages' => $deptStages,
                    'emptyText' => 'Phân xưởng ' . $dept . ' hiện không có phòng sẵn sàng hoặc đang bảo trì.',
                ])
            </div>
        @endforeach
        <div class="exec-empty rec-search-empty d-none">
            <i class="fas fa-search fa-3x mb-3"></i>
            <div>Không tìm thấy phòng / thiết bị phù hợp.</div>
            <div class="small mt-1">Phòng đang bận sản xuất không hiện ở trang này.</div>
        </div>
    @endif
@elseif ($stages->isNotEmpty())
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
