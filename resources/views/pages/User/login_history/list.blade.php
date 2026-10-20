@extends ('layout.master')

@section('topNAV')
    @include('layout.topNAV')
@endsection

@section('leftNAV')
    @include('layout.leftNAV')
@endsection

@section('mainContent')
    @php
        use App\Services\LoginHistory;
        use Illuminate\Support\Carbon;
    @endphp
    @include('pages.User.login_history._style')

    <div class="content-wrapper lh-page">
        <div class="d-flex justify-content-between align-items-start flex-wrap mt-4 mb-3">
            <div>
                <h4 class="mb-1">Lịch Sử Đăng Nhập</h4>
                <div class="text-muted">Mọi lượt đăng nhập (thành công, sai mật khẩu, bị khoá, phải đổi mật khẩu), thời lượng phiên và nguồn truy cập. Dữ liệu chỉ ghi thêm.</div>
            </div>
            <a href="{{ route('pages.User.login_history.list', request()->query() + ['export' => 'csv']) }}" class="btn btn-outline-success">
                <i class="fas fa-file-excel"></i> Xuất Excel
            </a>
        </div>

        <div class="row">
            <div class="col-md-6">
                <div class="info-box">
                    <span class="info-box-icon bg-success"><i class="fas fa-check-circle"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-number">{{ (int) $today->success }}</span>
                        <span class="info-box-text text-wrap">Đăng nhập thành công hôm nay · {{ (int) $today->users }} người</span>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="info-box">
                    <span class="info-box-icon bg-primary"><i class="fas fa-users"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-number">{{ $online->count() }}</span>
                        <span class="info-box-text text-wrap">Phiên đang mở</span>
                    </div>
                </div>
            </div>
        </div>

        @if ($online->isNotEmpty())
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h3 class="card-title"><i class="fas fa-users"></i> Đang trực tuyến</h3>
                            <small class="text-muted ml-auto">thao tác trong {{ LoginHistory::idleMinutes() }} phút gần đây</small>
                        </div>
                        <div class="card-body p-0 lh-scroll">
                            <table class="table table-sm mb-0">
                                <tbody>
                                    @forelse ($online as $s)
                                        <tr>
                                            <td><b>{{ $s->full_name ?? $s->userName }}</b><div class="lh-sub lh-mono">{{ $s->userName }}</div></td>
                                            <td>{{ $s->browser }} · {{ $s->platform }}<div class="lh-sub lh-mono">{{ $s->ip_address }}</div></td>
                                            <td class="text-right">Từ {{ Carbon::parse($s->login_at)->format('H:i') }}<div class="lh-sub">thao tác {{ Carbon::parse($s->last_seen_at)->locale('vi')->diffForHumans() }}</div></td>
                                        </tr>
                                    @empty
                                        <tr><td class="text-muted">Không có phiên nào đang mở.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <div class="card">
            <div class="card-header">
                <form method="GET" action="{{ route('pages.User.login_history.list') }}">
                    <div class="form-row align-items-end">
                        <div class="col-md-2 col-6 mb-2"><label class="mb-1">Từ ngày</label><input type="date" name="from" class="form-control" value="{{ $from }}"></div>
                        <div class="col-md-2 col-6 mb-2"><label class="mb-1">Đến ngày</label><input type="date" name="to" class="form-control" value="{{ $to }}"></div>
                        <div class="col-md-2 col-6 mb-2">
                            <label class="mb-1">Tài khoản</label>
                            <select name="user" class="form-control"><option value="">Tất cả</option>@foreach ($users as $u)<option @selected(request('user') === $u)>{{ $u }}</option>@endforeach</select>
                        </div>
                        <div class="col-md-1 col-6 mb-2">
                            <label class="mb-1">Bộ phận</label>
                            <select name="department" class="form-control"><option value="">Tất cả</option>@foreach ($departments as $d)<option @selected(request('department') === $d)>{{ $d }}</option>@endforeach</select>
                        </div>
                        <div class="col-md-2 col-6 mb-2">
                            <label class="mb-1">Kết quả</label>
                            <select name="status" class="form-control"><option value="">Tất cả</option>@foreach (LoginHistory::STATUSES as $k => $s)<option value="{{ $k }}" @selected(request('status') === $k)>{{ $s['label'] }}</option>@endforeach</select>
                        </div>
                        <div class="col-md-1 col-6 mb-2"><label class="mb-1">Địa chỉ IP</label><input type="text" name="ip" class="form-control" value="{{ request('ip') }}" placeholder="vd: 192.168.1."></div>
                        <div class="col-md-2 col-12 mb-2">
                            <button class="btn btn-primary"><i class="fas fa-filter"></i> Lọc</button>
                            <a href="{{ route('pages.User.login_history.list') }}" class="btn btn-secondary">Xoá lọc</a>
                        </div>
                    </div>
                </form>
            </div>

            <div class="card-body">
                <div class="mb-2">Tổng cộng <b>{{ number_format($logs->total()) }}</b> lượt đăng nhập</div>
                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead class="thead-light">
                            <tr><th>Thời điểm</th><th>Tài khoản</th><th>Kết quả</th><th>Địa chỉ IP</th><th>Trình duyệt / HĐH</th><th>Kết thúc phiên</th></tr>
                        </thead>
                        <tbody>
                            @forelse ($logs as $log)
                                <tr>
                                    <td class="text-nowrap">{{ Carbon::parse($log->login_at)->format('d/m/Y H:i:s') }}<div class="lh-sub">{{ Carbon::parse($log->login_at)->locale('vi')->diffForHumans() }}</div></td>
                                    <td>
                                        <a href="{{ route('pages.User.login_history.list', ['user' => $log->userName] + request()->only('from', 'to')) }}" class="lh-mono font-weight-bold">{{ $log->userName }}</a>
                                        <div class="lh-sub">{{ $log->full_name ?? 'Tài khoản không tồn tại' }}@if ($log->department_code) · {{ $log->department_code }}@endif</div>
                                    </td>
                                    <td>
                                        <span class="badge {{ LoginHistory::STATUSES[$log->status]['badge'] ?? 'badge-secondary' }}">{{ LoginHistory::STATUSES[$log->status]['label'] ?? $log->status }}</span>
                                        @if ($log->reason)<div class="lh-sub">{{ $log->reason }}</div>@endif
                                    </td>
                                    <td class="lh-mono">{{ $log->ip_address }}</td>
                                    <td title="{{ $log->user_agent }}">{{ $log->browser ?? '—' }}<div class="lh-sub">{{ $log->platform }}</div></td>
                                    <td>@include('pages.User.login_history._session', ['log' => $log])</td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="text-center text-muted py-4"><i class="fas fa-key fa-2x mb-2 d-block"></i>Không có lượt đăng nhập trong khoảng thời gian này.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                {{ $logs->links('pagination::bootstrap-4') }}
            </div>
        </div>
    </div>
@endsection
