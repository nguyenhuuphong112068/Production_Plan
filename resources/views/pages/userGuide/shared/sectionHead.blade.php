{{--
| Đầu một mục hướng dẫn: icon + tên màn hình + đường dẫn trên menu + các nhãn đặc điểm
| + nút "Mở màn hình" (chỉ hiện khi người đang xem vào được màn hình đó).
|
| Biến vào:
| - $icon   : icon FontAwesome 5, dùng đúng icon của màn hình thật
| - $title  : tên màn hình
| - $path   : (tuỳ chọn) đường đi trên leftNAV, ví dụ 'Lịch Sản Xuất › Lịch Sản Xuất Chart'
| - $route  : (tuỳ chọn) tên route của màn hình để mở nhanh
| - $params : (tuỳ chọn) tham số route
| - $perm   : (tuỳ chọn) điều kiện xem màn hình, giống điều kiện @if trên leftNAV:
|               'layout_xxx'               = tên quyền, kiểm tra bằng user_has_permission()
|               'role:Admin,Schedualer'    = chỉ các nhóm quyền (session user.userGroup) này
| - $chips  : (tuỳ chọn) [['text', 'class'], ...] class là '' | 'is-warn' | 'is-danger' | 'is-success'
--}}
@php
    $ugCanOpen = false;
    if (!empty($route) && \Illuminate\Support\Facades\Route::has($route)) {
        $ugPerm = $perm ?? null;
        if ($ugPerm === null) {
            $ugCanOpen = true;
        } elseif (str_starts_with($ugPerm, 'role:')) {
            $ugCanOpen = in_array(session('user')['userGroup'] ?? '', explode(',', substr($ugPerm, 5)), true);
        } else {
            $ugCanOpen = user_has_permission(session('user')['userId'] ?? 0, $ugPerm, 'boolean');
        }
    }
@endphp
<div class="ug-section-head">
    <div class="ug-section-icon"><i class="{{ $icon }}"></i></div>
    <div>
        <h2>{{ $title }}</h2>
        @if (!empty($path))
            <div class="ug-section-path"><i class="fas fa-bars mr-1"></i>{{ $path }}</div>
        @endif
    </div>
    <div class="ug-section-tools">
        @foreach ($chips ?? [] as $chip)
            <span class="ug-chip {{ $chip[1] ?? '' }}">{{ $chip[0] }}</span>
        @endforeach
        @if ($ugCanOpen)
            <a href="{{ route($route, $params ?? []) }}" class="btn btn-sm btn-outline-primary ug-open" target="_blank">
                <i class="fas fa-external-link-alt mr-1"></i> Mở màn hình
            </a>
        @endif
    </div>
</div>
