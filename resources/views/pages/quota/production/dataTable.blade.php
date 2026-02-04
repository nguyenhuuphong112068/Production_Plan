<link href="{{ asset('css/bootstrap.min.css') }}" rel="stylesheet">
<style>
    .step-checkbox {
        width: 20px;
        height: 20px;
        cursor: pointer;
        accent-color: #007bff;
        /* màu xanh bootstrap */
    }

    .step-checkbox:checked {
        box-shadow: 0 0 5px #007bff;
    }

    .time {
        width: 100%;
        border: none;
        outline: none;
        background: transparent;
        text-align: center;
        height: 100%;
        padding: 2px 4px;
        box-sizing: border-box;
    }

    /* Khi focus thì chỉ có viền nhẹ để người dùng biết đang nhập */
    .time:focus {
        border: 1px solid #007bff;
        border-radius: 2px;
        background-color: #fff;
    }

    /* Tùy chọn: nếu bạn muốn chữ canh giữa theo chiều dọc */
    td input.time {
        display: block;
        margin: auto;
    }
</style>

<div class="content-wrapper">
    <div class="card">
        <div class="card-header mt-4">
        </div>
        <div class="card-body">
            @php
                $auth_create = user_has_permission(session('user')['userId'], 'quota_production_create', 'disabled');
                $auth_update = user_has_permission(session('user')['userId'], 'quota_production_update', 'disabled');
                $auth_deActive = user_has_permission(
                    session('user')['userId'],
                    'quota_production_deActive',
                    'disabled',
                );
            @endphp

            <div class="row">
                <div class="col-md-12"></div>
                <div class="col-md-12 d-flex justify-content-end">
                    <form id = "filterForm" action="{{ route('pages.quota.production.list') }}" method="get">
                        @csrf
                        <div class="form-group" style="width: 177px">
                            <select class="form-control" name="stage_code" style="text-align-last: center;"
                                onchange="document.getElementById('filterForm').submit();">
                                <option {{ $stage_code == 1 ? 'selected' : '' }} value=1>Cân NL</option>
                                <option {{ $stage_code == 2 ? 'selected' : '' }} value=2>Cân NL Khác</option>
                                <option {{ $stage_code == 3 ? 'selected' : '' }} value=3>Pha Chế</option>
                                <option {{ $stage_code == 4 ? 'selected' : '' }} value=4>Trộn Hoàn Tất</option>
                                <option {{ $stage_code == 5 ? 'selected' : '' }} value=5>Định Hình</option>
                                <option {{ $stage_code == 6 ? 'selected' : '' }} value=6>Bao Phim</option>
                                <option {{ $stage_code == 7 ? 'selected' : '' }} value=7>Đóng Gói</option>
                            </select>
                        </div>
                    </form>
                </div>
            </div>
            <table id="data_table_quota" class="table table-bordered table-striped" style="font-size: 20px">
                <thead style = "position: sticky; top: 60px; background-color: white; z-index: 1020">
                    <tr>
                        <th rowspan="2">STT</th>
                        <th rowspan="2">Mã Sản Phẩm</th>
                        <th rowspan="2">Tên Sản Phẩm</th>
                        <th rowspan="2">Cỡ Lô</th>
                        @if ($stage_code == 3 || $stage_code == 4)
                            <th rowspan="2" style="width:1%">Bồn LP</th>
                        @endif
                        @if ($stage_code == 7)
                            <th rowspan="2" style="width:3%">Khử Ẩm EV</th>
                        @endif

                        <th rowspan="2">Phòng Sản Xuất</th>

                        <th colspan="4" class="text-center">Thời Gian</th>

                        <th rowspan="2" style="width: 50px">Số Lô Chiến Dịch</th>

                        @if ($stage_code <= 2)
                            <th rowspan="2" style="width: 50px">Hệ Số CD</th>
                        @endif

                        <th rowspan="2">Ghi Chú</th>
                        <th rowspan="2">Người Tạo/ Ngày Tạo</th>
                        <th rowspan="2" style="width:1%">Thêm</th>
                        {{-- <th rowspan="2" style="width:1%">Cập Nhật</th> --}}
                        <th rowspan="2" style="width:1%">Vô Hiệu</th>
                        {{-- <th rowspan="2" style="width:1%">Lich Sữ</th> --}}
                    </tr>
                    <tr>
                        <th>Chuẩn Bị</th>
                        <th>Sản Xuất</th>
                        <th>Vệ Sinh Cấp I</th>
                        <th>Vệ Sinh Cấp II</th>
                    </tr>
                </thead>

                <tbody>
                    @foreach ($datas as $data)
                    @php
                        if ($data->active) {
                            $class_bkg="";  
                        }else {
                            $class_bkg="text-danger"; 
                        }
                    @endphp
                        <tr>

                            <td class = {{$class_bkg}}>  {{$loop->iteration  }}
                                @if (session('user')['userGroup'] == 'Admin')
                                    <div> {{ $data->id }} </div>
                                @endif
                            </td>
                            <td class = {{$class_bkg}}>
                                <div> {{ $data->intermediate_code }} </div>
                                <div> {{ $data->finished_product_code ?? '' }} </div>
                            </td>
                            <td class = {{$class_bkg}}>{{ $data->product_name }} </td>
                            <td class = {{$class_bkg}}>{{ $data->batch_qty . ' ' . $data->unit_batch_qty }}</td>

                            @php
                                $field =
                                    $stage_code == 3 || $stage_code == 4
                                        ? 'tank'
                                        : ($stage_code == 7
                                            ? 'keep_dry'
                                            : null);
                            @endphp

                            @if ($field)
                                <td class="text-center align-middle " >
                                    <div class="form-check form-switch text-center">
                                        <input class="form-check-input step-checkbox" type="checkbox" role="switch"
                                            data-id="{{ $data->id }}" data-stage_code="{{ $stage_code }}"
                                            id="{{ $data->id }}" {{ $data->$field ? 'checked' : '' }}>
                                    </div>
                                </td>
                            @endif

                            <td class = {{$class_bkg}}>
                                @if ($data->room_name == null)
                                    <span class="px-2 py-1 rounded-pill"
                                        style="background-color:red; color:white; font-size: 14px">
                                        Thiếu Định Mức
                                    </span>
                                    @php $typeInput = 'hidden'; @endphp
                                @else
                                    {{ $data->room_name . ' - ' . $data->room_code }}
                                    @php $typeInput = 'text'; @endphp
                                @endif
                            </td>

                            <td >
                                <input type= "{{ $typeInput }}" class="time {{$class_bkg}}" name="p_time"
                                    value = "{{ $data->p_time }}" data-id={{ $data->id }} {{ $auth_update }}>
                            </td>

                            <td >
                                <input type= "{{ $typeInput }}" class="time {{$class_bkg}}" name="m_time"
                                    value = "{{ $data->m_time }}" data-id={{ $data->id }} {{ $auth_update }}>
                            </td>
                            <td >
                                <input type= "{{ $typeInput }}" class="time {{$class_bkg}}" name="C1_time"
                                    value = "{{ $data->C1_time }}" data-id={{ $data->id }} {{ $auth_update }}>
                            </td>
                            <td >
                                <input type= "{{ $typeInput }}" class="time {{$class_bkg}}" name="C2_time"
                                    value = "{{ $data->C2_time }}" data-id={{ $data->id }} {{ $auth_update }}>
                            </td>
                            <td>
                                <input type= "{{ $typeInput }}" class="time {{$class_bkg}}" name="maxofbatch_campaign"
                                    value = "{{ $data->maxofbatch_campaign }}" data-id={{ $data->id }}
                                    {{ $auth_update }}>
                            </td>

                            @if ($stage_code <= 2)
                                <td >
                                    <input type= "{{ $typeInput }}" class="time {{$class_bkg}}" name="campaign_index"
                                        value = "{{ $data->campaign_index }}" data-id={{ $data->id }}
                                        {{ $auth_update }}>
                                </td>
                            @endif
                            <td class = {{$class_bkg}}>
                                <input type= "{{ $typeInput }}" class="time" name="note"
                                    value = "{{ $data->note }}" data-id={{ $data->id }} {{ $auth_update }}>
                            </td>

                            <td class = {{$class_bkg}}>

                            <div> {{ $data->prepared_by }} </div>

                            <div>
                                    {{ $data->created_at}}
                            </td>


                            <td class="text-center align-middle">
                                <button type="button" class="btn btn-success btn-plus" {{ $auth_create }}
                                    data-product_name="{{ $data->product_name }}"
                                    data-intermediate_code="{{ $data->intermediate_code }}"
                                    data-finished_product_code="{{ $data->finished_product_code }}"
                                    data-stage_code="{{ $stage_code }}" data-toggle="modal"
                                    data-target="#create_modal">
                                    <i class="fas fa-plus"></i>
                                </button>
                            </td>

                            <td class="text-center align-middle">
                                <button type="button"
                                    class="btn btn-toggle-active {{ $data->active ? 'btn-danger' : 'btn-success' }}"
                                    data-id="{{ $data->id }}" 
                                    data-active="{{ $data->active }}"
                                    {{-- data-name="{{ $data->intermediate_code . '-' . $data->finished_product_code . '-' . $data->product_name }}" --}}
                                    {{ $auth_deActive }} {{ $data->room_name ? '' : 'disabled' }}>
                                    <i class="fas {{ $data->active ? 'fa-lock' : 'fa-unlock' }}"></i>
                                </button>
                            </td>

                           
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>


<script src="{{ asset('js/vendor/jquery-1.12.4.min.js') }}"></script>
<script src="{{ asset('js/popper.min.js') }}"></script>
<script src="{{ asset('js/bootstrap.min.js') }}"></script>
<script src="{{ asset('js/sweetalert2.all.min.js') }}"></script>

@if (session('success'))
    <script>
        Swal.fire({
            title: 'Thành công!',
            text: '{{ session('success') }}',
            icon: 'success',
            timer: 2000, // tự đóng sau 2 giây
            showConfirmButton: false
        });
    </script>
@endif

<script>
    $(document).ready(function() {
        document.body.style.overflowY = "auto";
        $('.btn-create').click(function() {
            const button = $(this);
            const modal = $(button.data('target'));
            modal.find('input[name="stage_code"]').val(button.data('stage_code'));
        });

        $('.btn-plus').click(function() {
            const button = $(this);
            const modal = $('#create_modal');
            // Gán dữ liệu vào input
            modal.find('input[name="stage_code"]').val(button.data('stage_code'));
            modal.find('input[name="intermediate_code"]').val(button.data('intermediate_code'));
            modal.find('input[name="finished_product_code"]').val(button.data('finished_product_code'));
            modal.find('input[name="product_name"]').val(button.data('product_name'));

            if (button.data('stage_code') <= 6) {
                modal.find('input[name="intermediate_code"]').show();
                modal.find('input[name="finished_product_code"]').hide();

            } else if (button.data('stage_code') === 7) {
                modal.find('input[name="intermediate_code"]').hide();
                modal.find('input[name="finished_product_code"]').show();
            }

        });


        const table = $('#data_table_quota').DataTable({
            paging: true,
            deferRender: true,
            pageLength: 25,
            lengthMenu: [
                [10, 25, 50, 100, -1],
                [10, 25, 50, 100, "Tất cả"]
            ],
            searching: true,
            ordering: true,
            autoWidth: false,
            columnDefs: [
                {
                    targets: 0,        // cột STT
                    orderable: false,
                    searchable: false
                }
            ]
        });

        // ✅ ĐÁNH SỐ STT ĐÚNG CHUẨN
        // table.on('order.dt search.dt draw.dt', function () {
        //     const info = table.page.info();
        //     table.column(0, { search: 'applied', order: 'applied' })
        //         .nodes()
        //         .each(function (cell, i) {
        //             cell.innerHTML = info.start + i + 1;
        //         });
        // });
  
    });

    $(document).on('click', '.btn-toggle-active', function () {

        const btn = $(this);
        const url = "{{ route('pages.quota.production.deActive') }}";
        const id = btn.data('id');
        let active = parseInt(btn.data('active'));

        let title = active === 1
            ? 'Bạn chắc chắn muốn vô hiệu hóa định mức?'
            : 'Bạn chắc chắn muốn phục hồi định mức?';

        Swal.fire({
            title: title,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#28a745',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Đồng ý',
            cancelButtonText: 'Hủy'
        }).then((result) => {

            if (!result.isConfirmed) return;

            btn.prop('disabled', true);

            $.ajax({
                url: url,
                type: 'POST',
                dataType: 'json',
                data: {
                    _token: '{{ csrf_token() }}',
                    id: id,
                    active: active
                },
                success: function (res) {

                    if (!res.success) {
                        Swal.fire('Lỗi', res.message || 'Có lỗi xảy ra', 'error');
                        return;
                    }
                    
                    const newActive = res.active;
                    btn.data('active', !newActive);

                    if (newActive === 0) {
                        btn.removeClass('btn-success').addClass('btn-danger');
                        btn.html('<i class="fas fa-lock"></i>');
                    } else {
                        btn.removeClass('btn-danger').addClass('btn-success');
                        btn.html('<i class="fas fa-unlock"></i>');
                    }

                    Swal.fire('Thành công!', 'Cập nhật thành công', 'success');
                },
                error: function (xhr) {
                    console.error(xhr);
                    Swal.fire('Lỗi server', 'Không thể xử lý yêu cầu', 'error');
                },
                complete: function () {
                    btn.prop('disabled', false);
                }
            });
        });
    });

    $(document).on('change', '.step-checkbox', function() {

        let id = $(this).data('id');

        if (id == '') {
            Swal.fire({
                title: 'Cảnh Báo!',
                text: 'Sản Phẩm Chưa Định Mức',
                icon: 'warning',
                timer: 1000, // tự đóng sau 2 giây
                showConfirmButton: false
            });
            $(this).prop('checked', false);
            return
        }
        let stage_code = $(this).data('stage_code');
        let checked = $(this).is(':checked');
        //console.log (id, stage_code, checked)
        $.ajax({
            url: "{{ route('pages.quota.production.tank_keepDry') }}",
            type: 'POST',
            dataType: 'json',
            data: {
                _token: '{{ csrf_token() }}',
                id: id,
                stage_code: stage_code,
                checked: checked
            }
        });
    });

    $(document).on('focus', '.time', function() {
        $(this).data('old-value', $(this).val());
    });

    $(document).on('blur', '.time', function() {

        let id = $(this).data('id');
        let name = $(this).attr('name');
        let time = $(this).val();
        let oldValue = $(this).data('old-value');

        if (time === oldValue) return;

        if (id == '') {
            Swal.fire({
                title: 'Cảnh Báo!',
                text: 'Sản Phẩm Chưa Định Mức',
                icon: 'warning',
                timer: 1000, // tự đóng sau 2 giây
                showConfirmButton: false
            });
            $(this).val('');
            return
        }


        if (name == "maxofbatch_campaign") {
            const pattern = /^[1-9]\d*$/;
            if (time && !pattern.test(time)) {
                Swal.fire({
                    title: 'Lỗi định dạng!',
                    text: 'Thời gian phải có dạng hh:mm (phút là 00, 15, 30, 45)',
                    icon: 'error',
                    timer: 2000,
                    showConfirmButton: false
                });
                $(this).focus();
                $(this).css('border', '1px solid red');
                return;
            } else {
                $(this).css('border', '');
            }
        } else if (name == "campaign_index") {
            const pattern = /^\d+\.\d$/;
            if (time && !pattern.test(time)) {
                Swal.fire({
                    title: 'Lỗi định dạng!',
                    text: 'Hệ số tăng thời gian chiến dịch là 1 số thập phân 1 số lẻ.',
                    icon: 'error',
                    timer: 2000,
                    showConfirmButton: false
                });
                $(this).focus();
                $(this).css('border', '1px solid red');
                return;
            } else {
                $(this).css('border', '');
            }
        } else if (name != "note") {
            const pattern = /^(?:\d{1,2}|1\d{2}|200):(00|15|30|45)$/;
            if (time && !pattern.test(time)) {
                Swal.fire({
                    title: 'Lỗi định dạng!',
                    text: 'Thời gian phải có dạng hh:mm (phút là 00, 15, 30, 45)',
                    icon: 'error',
                    timer: 2000,
                    showConfirmButton: false
                });
                $(this).focus();
                $(this).css('border', '1px solid red');
                return;
            } else {
                $(this).css('border', '');
            }
        }

        $.ajax({
            url: "{{ route('pages.quota.production.updateTime') }}",
            type: 'POST',
            dataType: 'json',
            data: {
                _token: '{{ csrf_token() }}',
                id: id,
                name: name,
                time: time
            }
        });
    });
</script>
