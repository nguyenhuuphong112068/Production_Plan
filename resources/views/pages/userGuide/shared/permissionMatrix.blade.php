{{--
| Ma trận nhóm quyền × quyền của một tài liệu (một nhóm menu leftNAV), chỉ xem, đọc thẳng CSDL lúc mở trang.
| Chỉ hiện các nhóm quyền có ít nhất một quyền trong phần này. Biến vào: $moduleKey (khoá tài liệu trong
| UserGuideController::GUIDES). Nguồn: App\Support\GuidePermissions::matrix().
--}}
@php $ugMatrix = \App\Support\GuidePermissions::matrix($moduleKey); @endphp

@if (empty($ugMatrix['rows']))
    <p class="text-muted">Phần này không có quyền riêng: mọi tài khoản đăng nhập đều dùng được, hoặc chỉ kiểm tra theo tên
        nhóm quyền (xem bảng ở tài liệu tương ứng).</p>
@else
    <div class="ug-matrix-scroll">
        <table class="ug-matrix">
            <thead>
                <tr>
                    <th class="ug-mx-name">Quyền</th>
                    @foreach ($ugMatrix['roles'] as $role)
                        <th class="ug-mx-role" title="{{ $role->name }}{{ $role->display_name ? ' - ' . $role->display_name : '' }}">
                            <span>{{ $role->name }}</span>
                        </th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @php $ugLastScreen = null; @endphp
                @foreach ($ugMatrix['rows'] as $row)
                    @if ($row['screen'] !== $ugLastScreen)
                        <tr class="ug-mx-group">
                            <td class="ug-mx-name">{{ $row['screen'] }}</td>
                            <td colspan="{{ max(count($ugMatrix['roles']), 1) }}"></td>
                        </tr>
                        @php $ugLastScreen = $row['screen']; @endphp
                    @endif
                    <tr>
                        <td class="ug-mx-name ug-mx-child" title="{{ $row['perm'] }}">{{ $row['action'] }}</td>
                        @foreach ($ugMatrix['roles'] as $role)
                            <td class="ug-mx-cell">
                                @if (in_array($role->name, $row['roles'], true))
                                    <i class="fas fa-check ug-mx-on" title="{{ $role->name }}: {{ $row['label'] }}"></i>
                                @endif
                            </td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif
