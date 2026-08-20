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
                        <b>Gửi Kế Hoạch Tháng</b>. Chỉ gồm lô <b>có chia lô</b> và
                        <b>không thuộc thị trường VN</b>.
                    </div>

                    <table id="example1" class="table table-bordered table-striped" style="font-size: 20px">
                        <thead style="position: sticky; top: 60px; background-color: white; z-index: 1020">
                            <tr>
                                <th>STT</th>
                                <th>Kế Hoạch</th>
                                <th>Phân Xưởng</th>
                                <th>Người Tạo</th>
                                <th>Ngày Tạo</th>
                                <th>Tình Trạng</th>
                                <th class="text-center">Số Lô</th>
                                <th class="text-center">Đã Nhập</th>
                                <th>Người Gửi</th>
                                <th>Ngày Gửi</th>
                                <th class="text-center">Chi Tiết</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($plans as $data)
                                @php
                                    $total = $rowCounts[$data->id] ?? 0;
                                    $filled = $filledCounts[$data->id] ?? 0;

                                    $colors = [
                                        0 => 'background-color: #ffeb3b; color: white;', // vàng
                                        1 => 'background-color: #4caf50; color: white;', // xanh lá
                                    ];
                                    $status = [
                                        0 => 'Pending', // vàng
                                        1 => 'Send', // xanh lá
                                    ];
                                @endphp
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>{{ $data->name }}</td>
                                    <td>{{ $data->deparment_code }}</td>
                                    <td>{{ $data->prepared_by ?? 'NA' }}</td>
                                    <td>
                                        {{ $data->created_at ? \Carbon\Carbon::parse($data->created_at)->format('d/m/Y H:i') : '' }}
                                    </td>

                                    <td style="text-align: center; vertical-align: middle;">
                                        <span
                                            style="padding: 6px 15px; border-radius: 20px; {{ $colors[$data->send ?? 1] ?? '' }}">
                                            {{ $status[$data->send ?? 1] }}
                                        </span>
                                    </td>

                                    <td class="text-center align-middle">
                                        @if ($total > 0)
                                            <span class="badge badge-info"
                                                style="font-size: 16px; padding: 6px 12px;">{{ $total }}</span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>

                                    <td class="text-center align-middle">
                                        @if ($total > 0)
                                            <span class="badge {{ $filled >= $total ? 'badge-success' : 'badge-warning' }}"
                                                style="font-size: 16px; padding: 6px 12px;">
                                                {{ $filled }}/{{ $total }}
                                            </span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>

                                    <td>{{ $data->send_by }}</td>
                                    <td>
                                        {{ $data->send_date ? \Carbon\Carbon::parse($data->send_date)->format('d/m/Y') : '' }}
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
            // Layout chung đặt body { overflow-y: hidden }; trang này cuộn bình thường như
            // trang Kế Hoạch Sản Xuất Tháng, tiêu đề cột đã được ghim bằng position: sticky.
            document.body.style.overflowY = "auto";
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
