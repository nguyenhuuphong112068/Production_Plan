<div class="content-wrapper" >
    <div class="card">
        <div class="card-body mt-5" style="height: 96vh; overflow-y: auto;">
            @php
                $auth_view_material = user_has_permission(session('user')['userId'], 'plan_production_view_material', 'disabled');
            @endphp
            @if (user_has_permission(session('user')['userId'], 'plan_production_create_plan_list', 'boolean'))
                <button class="btn btn-success btn-create mb-2" data-toggle="modal" data-target="#create_plan_list_modal"
                    style="width: 155px">
                    <i class="fas fa-plus"></i> Thêm
                </button>
            @endif
            <table id="example1" class="table table-bordered table-striped" style="font-size: 20px">
               <thead>
                    <tr>
                        <th rowspan="2">STT</th>
                        <th rowspan="2">Kế Hoạch</th>
                        <th rowspan="2">Phân Xưởng</th>
                        <th rowspan="2">Người Tạo</th>
                        <th rowspan="2">Ngày Tạo</th> <!-- ✅ SỬA -->
                        <th rowspan="2">Tình Trạng</th>
                        <th rowspan="2">Sản Lượng Lý Thuyết (ĐVL)</th>

                        <th colspan="8" style="text-align:center;">
                            Tình Trạng Sản Xuất
                        </th>

                        <th rowspan="2">Người Gửi</th>
                        <th rowspan="2">Ngày Gửi</th>
                        <th rowspan="2">Chi Tiết</th>
                        <th rowspan="2">Tạm tính NL/BB</th>
                    </tr>

                    <tr>
                        <th>Tổng Lô</th>
                        <th>Chưa Làm</th>
                        <th>Đã Cân</th>
                        <th>Đã PC</th>
                        <th>Đã THT</th>
                        <th>Đã ĐH</th>
                        <th>Đã BP</th>
                        <th>Đã ĐG</th>
                        <th>Hủy</th>
                    </tr>
                </thead>

                <tbody>

                    @foreach ($datas as $data)
                        <tr>
                            <td>{{ $loop->iteration }} 
                                
                            </td>
                            {{-- <td>{{ $data->code}}</td> --}}
                            <td>{{ $data->name }}</td>
                            <td>{{ $data->deparment_code }}
                                @if(session('user')['userGroup'] == "Admin") <div> {{ $data->id}} </div> @endif
                            </td>
                            <td>{{ $data->prepared_by?? "NA"}}</td>
                            <td>{{ $data->created_at ? \Carbon\Carbon::parse($data->created_at??now())->format('d/m/Y H:i'): '' }}</td>
                          
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
                                <span style="padding: 6px 15px; border-radius: 20px; {{ $colors[$data->send??1] ?? '' }}">
                                    {{ $status[$data->send??1] }}
                                </span>
                            </td>
                            <td>
                                {{ number_format($data->total_batch_qty) }} <br>
                                {{-- {{ number_format($data->batch_qty_pending) }} --}}
                            </td>

                            <td>{{ $data->tong_lo }}</td>
                            <td>{{ $data->status_counts['Chưa làm']??0 }}</td>
                            <td>{{ $data->status_counts['Đã Cân']??0 }}</td>
                            <td>{{ $data->status_counts['Đã Pha chế']??0 }}</td>
                            <td>{{ $data->status_counts['Đã THT']??0 }}</td>
                            <td>{{ $data->status_counts['Đã định hình']??0 }}</td>
                            <td>{{ $data->status_counts['Đã Bao phim']??0 }}</td>
                            <td>{{ $data->status_counts['Hoàn Tất ĐG']??0 }}</td>
                            <td>{{ $data->status_counts['Hủy']??0 }}</td>

  
                            <td>{{ $data->send_by??"NA" }}</td>

                            <td>{{ $data->send_date? \Carbon\Carbon::parse($data->send_date)->format('d/m/Y'): '' }}</td>


                            <td class="text-center align-middle">
                                <form action="{{ route('pages.plan.production.open') }}" method="get">
                                    @csrf
                                    <input type="hidden" name="plan_list_id" value="{{ $data->id }}">
                                    <input type="hidden" name="month" value="{{ $data->month }}">
                                    <input type="hidden" name="send" value="{{ $data->send }}">
                                    <input type="hidden" name="name" value="{{ $data->name }}">
                                    
                                    <button type="submit" class="btn btn-success">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </form>
                            </td>

                             <td class="text-center align-middle">
                                <form action="{{ route('pages.plan.production.open_stock') }}" method="get">
                                    @csrf
                                    <input type="hidden" name="plan_list_id" value="{{ $data->id }}">
                                    <input type="hidden" name="current_url" value="{{ url()->full() }}">
                                    <button type="submit" class="btn btn-success" {{ $auth_view_material }}>
                                        <i class="fas fa-table"></i>
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

@if (session('success'))
    <script>
        Swal.fire({
            title: 'Thành công!',
            text: '{{ session('success') }}',
            icon: 'success',
            timer: 1000, // tự đóng sau 2 giây
            showConfirmButton: false
        });



    </script>
@endif

<script>
        $(document).ready(function() {
            document.body.style.overflowY = "auto";
        });
</script>
