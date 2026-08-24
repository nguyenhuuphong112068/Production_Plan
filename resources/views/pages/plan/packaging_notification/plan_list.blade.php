@extends ('layout.master')

@section('topNAV')
    @include('layout.topNAV')
@endsection

@section('leftNAV')
    @include('layout.leftNAV')
@endsection

@section('mainContent')
    <div class="content-wrapper">
        <div class="p-3">
            <div class="card card-success mt-5">
                <div class="card-header">
                    <h3 class="card-title">Danh Sách Kế Hoạch Tháng</h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-tool" data-card-widget="collapse">
                            <i class="fas fa-minus"></i>
                        </button>
                    </div>
                </div>

                <div class="card-body" style="min-height: 96vh">
                    <div class="alert alert-light border py-2 mb-3">
                        <i class="fas fa-info-circle text-info"></i>
                        Danh sách lô của thông báo đóng gói được tạo tự động khi
                        <b>Gửi Kế Hoạch Tháng</b>, gồm mọi lô còn hiệu lực, chia theo 3 tab
                        <b><i class="fas fa-globe-europe text-primary"></i> Châu Âu</b> /
                        <b><i class="fas fa-globe-asia text-success"></i> Ngoài Châu Âu</b> /
                        <b><i class="fas fa-flag text-danger"></i> Việt Nam</b>.
                    </div>

                    {{-- id riêng (không dùng "example1") để tự init DataTable với scrollY thay vì
                         theo cấu hình mặc định ở layout.master --}}
                    <table id="pkgPlanTable" class="table table-bordered table-striped" style="font-size: 20px">
                        <thead>
                            <tr>
                                <th>STT</th>
                                <th>Kế Hoạch</th>
                                <th>Phân Xưởng</th>
                                <th class="text-center">Số Lượng</th>
                                <th class="text-center">Đã Lấy Mẫu</th>
                                <th class="text-center">Chi Tiết</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($plans as $data)
                                @php
                                    $tab = $tabCounts[$data->id] ?? null;
                                    $euTotal = (int) ($tab->eu ?? 0);
                                    $nonEuTotal = (int) ($tab->non_eu ?? 0);
                                    $vnTotal = (int) ($tab->vn ?? 0);
                                    $total = $euTotal + $nonEuTotal + $vnTotal;

                                    $sampled = $sampledCounts[$data->id] ?? 0;
                                @endphp
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>{{ $data->name }}</td>
                                    <td>{{ $data->deparment_code }}</td>

                                    <td class="text-center align-middle">
                                        @if ($total > 0)
                                            <div class="d-flex flex-row justify-content-center align-items-center"
                                                style="gap: 6px;">
                                                <span class="badge badge-primary" style="font-size: 13px; padding: 4px 8px;"
                                                    title="Sản Phẩm Châu Âu">
                                                    <i class="fas fa-globe-europe"></i> {{ $euTotal }}
                                                </span>
                                                <span class="badge badge-success" style="font-size: 13px; padding: 4px 8px;"
                                                    title="Sản Phẩm Ngoài Châu Âu">
                                                    <i class="fas fa-globe-asia"></i> {{ $nonEuTotal }}
                                                </span>
                                                <span class="badge badge-danger" style="font-size: 13px; padding: 4px 8px;"
                                                    title="Sản Phẩm Việt Nam">
                                                    <i class="fas fa-flag"></i> {{ $vnTotal }}
                                                </span>
                                            </div>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>

                                    <td class="text-center align-middle">
                                        @if ($total > 0)
                                            <span class="badge {{ $sampled >= $total ? 'badge-success' : 'badge-warning' }}"
                                                style="font-size: 16px; padding: 6px 12px;">
                                                {{ $sampled }}/{{ $total }}
                                            </span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>

                                    <td class="text-center align-middle">
                                        <form action="{{ route('pages.plan.packaging_notification.open') }}" method="get">
                                            <input type="hidden" name="plan_list_id" value="{{ $data->id }}">
                                            <button type="submit" class="btn btn-success" title="Mở thông báo đóng gói">
                                                <i class="fas fa-link"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
        $(function() {
            // Bảng riêng của trang này, không phải "example1" mặc định của layout.master,
            // nên tự init để bật scrollY (cuộn dọc trong khung cố định thay vì cuộn cả trang)
            $('#pkgPlanTable').DataTable({
                responsive: true,
                autoWidth: false,
                scrollY: '65vh',
                scrollCollapse: true,
            });
        });
    </script>

    @if (session('error'))
        <script>
            Swal.fire({
                title: 'Lỗi!',
                text: '{{ session('error') }}',
                icon: 'error',
                timer: 2000,
                showConfirmButton: false
            });
        </script>
    @endif
@endsection
