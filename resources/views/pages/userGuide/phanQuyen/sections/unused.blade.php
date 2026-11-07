@php
    $ugPerms = \App\Support\GuidePermissions::permissions();
    $ugByPerm = \App\Support\GuidePermissions::rolesByPermission();
    $ugLeft = array_filter(\App\Support\GuidePermissions::UNUSED, fn ($reason, $name) => isset($ugPerms[$name]), ARRAY_FILTER_USE_BOTH);
@endphp

@if (empty($ugLeft))
    <p>Danh sách quyền hiện <b>không còn quyền thừa</b>: mọi quyền trên màn hình Nhóm Phân Quyền đều đang được phần mềm kiểm tra.
        Các quyền của chức năng đã bỏ (Thống Kê, Trạng Thái, tồn kho MMS, thêm danh mục giả định...) đã được gỡ khỏi danh sách
        ngày 06/11/2026.</p>
@else
    <p>Các quyền dưới đây còn trong danh sách quyền nhưng phần mềm <b>không còn kiểm tra</b> ở đâu: tick hay bỏ tick đều không
        có tác dụng, và sẽ được gỡ khỏi danh sách. Không cần cấp các quyền này cho nhóm quyền mới.</p>

    <div class="table-responsive">
        <table class="table table-bordered ug-table">
            <thead><tr><th style="width: 260px">Quyền</th><th>Lý do</th><th style="width: 260px">Nhóm quyền vẫn đang tick</th></tr></thead>
            <tbody>
                @foreach ($ugLeft as $name => $reason)
                    <tr>
                        <td>{{ $ugPerms[$name]->display_name ?: $name }}<div class="ug-perm-code">{{ $name }}</div></td>
                        <td style="font-weight: 400">{{ $reason }}</td>
                        <td style="font-weight: 400">
                            @forelse ($ugByPerm[$name] ?? [] as $role)
                                <span class="ug-chip">{{ $role }}</span>
                            @empty
                                <span class="text-muted">-</span>
                            @endforelse
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif
