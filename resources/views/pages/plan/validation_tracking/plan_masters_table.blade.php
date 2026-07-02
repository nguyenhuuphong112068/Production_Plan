<div class="table-responsive">
<table class="table table-bordered table-striped" style="font-size: 0.9rem;">
    <thead>
        <tr>
            <th>STT</th>
            <th>Tình Trạng</th>
            <th>Mã Sản Phẩm</th>
            <th>Sản Phẩm</th>
            <th>Số Lô Dự Kiến<br>Số Lô Thực Tế<br>Số lượng ĐG</th>
            <th>Thị Trường/<br>Qui Cách</th>
            <th>Ngày dự kiến KCS</th>
            <th>Ưu Tiên</th>
            <th>Lô Thẩm định</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($datas as $data)
            <tr class="{{ $data->IsHypothesis ? 'highlight-row' : '' }}">
                <td>{{ $loop->iteration }}<br><small class="text-muted">{{ $data->id }}</small></td>
                <td>
                    @if ($data->IsHypothesis)
                        <div class ="text-center" style="display: inline-block; padding: 6px 10px; width: 100px; border-radius: 10px; background-color: #d40bf7; color: #ffffff;">Lô Giả Định</div>
                    @else
                        @php
                            $stutus_colors = [
                                'Chưa làm' => 'background-color: green; color: white;',
                                'Đã Cân' => 'background-color: #e3f2fd; color: #0d47a1;',
                                'Đã Pha chế' => 'background-color: #bbdefb; color: #0d47a1;',
                                'Đã THT' => 'background-color: #90caf9; color: #0d47a1;',
                                'Đã định hình' => 'background-color: #64b5f6; color: #0d47a1;',
                                'Đã Bao phim' => 'background-color: #42a5f5; color: #ffffff;',
                                'Hoàn Tất ĐG' => 'background-color: #1e88e5; color: #ffffff;',
                                'Hoàn Tất' => 'background-color: #1565c0; color: #ffffff;',
                                'Hủy' => 'background-color: red; color: white;',
                            ];
                        @endphp
                        <div class ="text-center" style="display: inline-block; padding: 6px 10px; width: 100px; border-radius: 10px; {{ $stutus_colors[$data->status] ?? '' }}">
                            {{ $data->status }}
                        </div>
                    @endif
                </td>
                <td class="{{ $data->cancel ? 'text-danger' : 'text-success' }}">
                    <div>{{ $data->intermediate_code }}</div>
                    <div>{{ $data->finished_product_code }}</div>
                </td>
                <td>
                    <div>{{ $data->intermediate_product_name }}</div>
                    <div>{{ $data->finished_product_name }} <br>({{ number_format($data->batch_qty) }} {{ $data->unit_batch_qty }})</div>
                </td>
                <td class="font-weight-bold">
                    <div class="text-dark">{{ $data->batch }}</div>
                    <div class="text-success">{{ $data->actual_batch }}</div>
                    <div class="text-white badge badge-success mt-1">{{ number_format($data->batch_qty) }} {{ $data->unit_batch_qty }}</div>
                </td>
                <td>
                    <div>{{ $data->market_name }}</div>
                    <div>{{ $data->specification }}</div>
                </td>
                <td>
                    {{ $data->expected_date ? date('d-M-Y', strtotime($data->expected_date)) : '' }}
                </td>
                <td>
                    <span class="badge badge-danger badge-pill">{{ $data->level }}</span>
                </td>
                <td>
                    @if($data->is_validation_tracking)
                        <div class="p-1 mb-1 bg-warning text-dark text-center font-weight-bold" style="border-radius: 5px; font-size: 0.8rem;">
                            <i class="fas fa-exclamation-triangle"></i> TĐNL
                        </div>
                    @endif
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="9" class="text-center">Không có lô sản xuất nào được gắn</td>
            </tr>
        @endforelse
    </tbody>
</table>
</div>
