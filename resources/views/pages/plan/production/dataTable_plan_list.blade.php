<div class="content-wrapper" >
    <div class="card">
        <div class="card-body mt-5" style="min-height: 96vh">
            @if (user_has_permission(session('user')['userId'], 'plan_production_store', 'boolean'))
                <button class="btn btn-success btn-create mb-2" data-toggle="modal" data-target="#create_plan_list_modal"
                    style="width: 155px">
                    <i class="fas fa-plus"></i> Thêm
                </button>
            @endif
            <table id="example1" class="table table-bordered table-striped" style="font-size: 20px">
                <thead style = "position: sticky; top: 60px; background-color: white; z-index: 1020">
                    <tr>
                        <th>STT</th>
                        <th>Kế Hoạch</th>
                        <th>Phân Xưởng</th>
                        <th>Người Tạo</th>
                        <th>Người Tạo</th>
                        <th>Tình Trạng</th>
                        <th>Người Gửi</th>
                        <th>Ngày Gửi</th>
                        <th>Xem</th>
                    </tr>
                </thead>
                <tbody>

                    @foreach ($datas as $data)
                        <tr>
                            <td>{{ $loop->iteration }} </td>
                            {{-- <td>{{ $data->code}}</td> --}}
                            <td>{{ $data->name }}</td>
                            <td>{{ $data->deparment_code }}</td>
                            <td>{{ $data->prepared_by }}</td>
                            <td>{{ \Carbon\Carbon::parse($data->created_at)->format('d/m/Y H:i') }}</td>

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
                                <span style="padding: 6px 15px; border-radius: 20px; {{ $colors[$data->send] ?? '' }}">
                                    {{ $status[$data->send] }}
                                </span>
                            </td>

                            <td>{{ $data->send_by }}</td>
                            <td>{{ \Carbon\Carbon::parse($data->send_date)->format('d/m/Y H:i') }}</td>


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

                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- 
<script src="{{ asset('js/vendor/jquery-1.12.4.min.js') }}"></script>
<script src="{{ asset('js/popper.min.js') }}"></script>
<script src="{{ asset('js/bootstrap.min.js') }}"></script>
<script src="{{ asset('js/sweetalert2.all.min.js') }}"></script> --}}

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
