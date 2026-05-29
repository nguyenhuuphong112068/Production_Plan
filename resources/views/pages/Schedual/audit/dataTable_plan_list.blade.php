<div class="content-wrapper">
    <div class="card">
        <div class="card-body mt-5" style="height: 96vh; overflow-y: auto;">
            <table id="example1" class="table table-bordered table-striped" style="font-size: 20px">
                <thead>
                    <tr>
                        <th rowspan="2">STT</th>
                        <th rowspan="2">Kế Hoạch</th>
                        <th rowspan="2">Phân Xưởng</th>
                        <th rowspan="2">Người Tạo</th>
                        <th rowspan="2">Ngày Tạo</th>
                        <th rowspan="2">Tình Trạng</th>
                       
                        <th colspan="6" style="text-align:center;">
                            Tình Trạng thay đổi
                        </th>

                        <th rowspan="2">Người Gửi</th>
                        <th rowspan="2">Ngày Gửi</th>
                        <th rowspan="2">Chi Tiết</th>
                    </tr>

                    <tr>
                        <th>Đã Cân</th> 
                        <th>Đã PC</th>
                        <th>Đã THT</th>
                        <th>Đã ĐH</th>
                        <th>Đã BP</th>
                        <th>Đã ĐG</th>
                    </tr>
                </thead>

                <tbody>
                    @foreach ($datas as $data)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $data->name }}</td>
                            <td>{{ $data->deparment_code }}</td>
                            <td>{{ $data->prepared_by ?? 'NA' }}</td>
                            <td>{{ $data->created_at ? \Carbon\Carbon::parse($data->created_at ?? now())->format('d/m/Y H:i') : '' }}</td>

                            @php
                                $colors = [
                                    0 => 'background-color: #ffeb3b; color: white;', // vàng
                                    1 => 'background-color: #4caf50; color: white;', // xanh lá
                                ];
                                $status = [
                                    0 => 'Pending', // vàng
                                    1 => 'Send', // xanh lá
                                ];
                            @endphp

                            <td style="text-align: center; vertical-align: middle;">
                                <span style="padding: 6px 15px; border-radius: 20px; {{ $colors[$data->send ?? 1] ?? '' }}">
                                    {{ $status[$data->send ?? 1] }}
                                </span>
                            </td>

                            <td>{{ $data->status_counts['Đã Cân'] ?? 0 }}</td>
                            <td>{{ $data->status_counts['Đã Pha chế'] ?? 0 }}</td>
                            <td>{{ $data->status_counts['Đã THT'] ?? 0 }}</td>
                            <td>{{ $data->status_counts['Đã định hình'] ?? 0 }}</td>
                            <td>{{ $data->status_counts['Đã Bao phim'] ?? 0 }}</td>
                            <td>{{ $data->status_counts['Hoàn Tất ĐG'] ?? 0 }}</td>

                            <td>{{ $data->send_by ?? 'NA' }}</td>
                            <td>{{ $data->send_date ? \Carbon\Carbon::parse($data->send_date)->format('d/m/Y') : '' }}</td>

                            <td class="text-center align-middle">
                                <a href="{{ route('pages.Schedual.audit.open', ['plan_list_id' => $data->id]) }}" class="btn btn-success">
                                    <i class="fas fa-eye"></i>
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="{{ asset('js/vendor/jquery-1.12.4.min.js') }}"></script>
<script src="{{ asset('js/popper.min.js') }}"></script>
<script src="{{ asset('js/bootstrap.min.js') }}"></script>

<script>
    $(document).ready(function() {
        document.body.style.overflowY = "auto";
    });
</script>
