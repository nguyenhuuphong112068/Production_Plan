{{--
| Bảng "màn hình / thao tác -> điều kiện -> nhóm quyền đang có" của một tài liệu, đọc thẳng CSDL lúc mở trang.
| Nguồn: App\Support\GuidePermissions::MAP[$guideKey]. Biến vào: $guideKey (có sẵn trong mọi section).
| Thêm / đổi quyền của một chức năng thì sửa GuidePermissions::MAP, không sửa file này.
--}}
@php
    $ugPermRows = \App\Support\GuidePermissions::rows($guideKey);
    $ugPermScreens = collect($ugPermRows)->groupBy('screen');
@endphp

@if (empty($ugPermRows))
    <div class="ug-callout is-warn">
        <i class="fas fa-exclamation-triangle"></i>
        <div>Chưa khai phân quyền cho tài liệu này.</div>
    </div>
@else
    <div class="table-responsive">
        <table class="table table-bordered ug-table ug-perm-table">
            <thead>
                <tr>
                    <th style="width: 190px">Màn hình</th>
                    <th style="width: 230px">Thao tác</th>
                    <th style="width: 260px">Điều kiện</th>
                    <th>Nhóm quyền đang được phép</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($ugPermScreens as $screen => $rows)
                    @foreach ($rows as $i => $row)
                        <tr>
                            @if ($i === 0)
                                <td rowspan="{{ count($rows) }}">{{ $screen }}</td>
                            @endif
                            <td style="font-weight: 400">{{ $row['action'] }}</td>
                            <td style="font-weight: 400">
                                @if ($row['perm'])
                                    Quyền <b>{{ $row['label'] }}</b>
                                    <div class="ug-perm-code">{{ $row['perm'] }}</div>
                                    @if ($row['missing'])
                                        <span class="ug-chip is-danger">Quyền chưa có trong CSDL</span>
                                    @endif
                                @else
                                    {{ $row['rule'] }}
                                @endif
                            </td>
                            <td style="font-weight: 400">
                                @if ($row['perm'])
                                    @forelse ($row['roles'] as $role)
                                        <span class="ug-chip {{ $role === 'Admin' ? 'is-warn' : '' }}">{{ $role }}</span>
                                    @empty
                                        <span class="text-muted">Chưa nhóm quyền nào</span>
                                    @endforelse
                                    @if ($row['allow'])
                                        <span class="ug-chip is-success" title="Tài khoản được cấp riêng quyền này ở Danh Sách User › Quyền">+ {{ $row['allow'] }} tài khoản cấp riêng</span>
                                    @endif
                                    @if ($row['deny'])
                                        <span class="ug-chip is-danger" title="Tài khoản bị từ chối riêng quyền này dù nhóm quyền có">− {{ $row['deny'] }} tài khoản bị từ chối</span>
                                    @endif
                                @elseif (!empty($row['roles']))
                                    @foreach ($row['roles'] as $role)
                                        <span class="ug-chip {{ $role === 'Admin' ? 'is-warn' : '' }}">{{ $role }}</span>
                                    @endforeach
                                    <div class="ug-perm-code">theo tên nhóm quyền chính, không tick ở Nhóm Phân Quyền</div>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                @endforeach
            </tbody>
        </table>
    </div>
@endif
