<link href="{{ asset('css/bootstrap.min.css') }}" rel="stylesheet">
<div class="content-wrapper">
    <!-- Main content -->
    <section class="content">
        <div class="col-12">
            <!-- /.card-header -->
            <div class="card">

                <div class="card-header mt-4">
                    {{-- <h3 class="card-title">Ghi Chú Nếu Có</h3> --}}
                </div>

                <!-- /.card-Body -->
                <div class="card-body">
                    @php
                        $auth_update = user_has_permission(
                            session('user')['userId'],
                            'plan_maintenance_update',
                            'disabled',
                        );
                        $auth_deActive = user_has_permission(
                            session('user')['userId'],
                            'plan_maintenance_deActive',
                            'disabled',
                        );
                    @endphp

                    @if (!$send)
                        <div class="row">
                            <div class="col-md-2">
                            </div>


                            <div class="col-md-8"></div>
                            <div class="col-md-2" style="text-align: right;">

                                <form id = "send_form" action="{{ route('pages.plan.maintenance.send') }}"
                                    method="post">

                                    @csrf
                                    <input type="hidden" name="plan_list_id" value="{{ $plan_list_id }}">
                                    @if (user_has_permission(session('user')['userId'], 'plan_maintenance_send', 'boolean'))
                                        <button class="btn btn-success btn-create mb-2 " style="width: 177px;">
                                            <i id = "send_btn" class="fas fa-paper-plane"></i> Gửi
                                        </button>
                                    @endif
                                </form>

                            </div>
                        </div>
                    @endif
                    <table id="dt_plan_master_maintenance" class="table table-bordered table-striped"
                        style="font-size: 20px">

                        <thead style = "position: sticky; top: 60px; background-color: white; z-index: 1020">

                            <tr>
                                <th>STT</th>
                                <th>Mã TB Chính</th>
                                <th>Mã Thiết Bi</th>
                                <th>Tên Thiết Bị</th>
                                <th>Loại BT-HC</th>
                                <th>Thực Hiện Trước Ngày</th>
                                <th>Phòng SX Liên Quan</th>
                                <th>Ghi Chú</th>
                                <th>Người Tạo/ Ngày Tạo</th>

                                <th style="width:1%">Bỏ Qua</th>

                            </tr>
                        </thead>
                        <tbody>

                            @foreach ($datas as $data)
                                <tr>
                                    <td>
                                        {{ $loop->iteration }}
                                        @if (session('user')['userGroup'] == 'Admin')
                                            {{ $data->id }}
                                        @endif
                                    </td>
                                    <td>{{ $data->parent_code ?? '' }}</td>

                                    @if (!$data->cancel)
                                        <td class="text-success">
                                            <div> {{ $data->code }} </div>
                                        </td>
                                    @else
                                        <td class="text-danger">
                                            <div> {{ $data->code }} </div>
                                        </td>
                                    @endif

                                    <td>{{ $data->name }}</td>
                                    <td>{{ $data->sch_type ?? '' }}</td>

                                    <td>
                                        <div> {{ \Carbon\Carbon::parse($data->expected_date)->format('d/m/Y') }} </div>
                                    </td>

                                    <td> {{ $data->rooms }} </td>

                                    <td> {{ $data->note }} </td>

                                    <td>
                                        <div> {{ $data->prepared_by }} </div>
                                        <div>{{ \Carbon\Carbon::parse($data->created_at)->format('d/m/Y') }} </div>
                                    </td>



                                    {{-- <td class="text-center align-middle">
                                        <button type="button" class="btn btn-warning btn-edit"
                                            {{ $data->active ? '' : 'disabled' }} {{ $auth_update }}
                                            data-id="{{ $data->id }}" data-name="{{ $data->name }}"
                                            data-code="{{ $data->code }}" data-rooms="{{ $data->rooms }}"
                                            data-expected_date="{{ $data->expected_date }}"
                                            data-note="{{ $data->note }}" data-toggle="modal"
                                            data-target="#update_modal">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                    </td> --}}

                                    <td class="text-center align-middle">
                                        <div class="d-flex justify-content-center gap-1">
                                            @if ($data->active == true && $send == false)
                                                <button type="button" {{ $auth_deActive }} 
                                                    class="btn btn-danger btn-deactive-ajax"
                                                    data-id="{{ $data->id }}" 
                                                    data-type="delete" 
                                                    data-name="{{ $data->name }}"
                                                    title="Bỏ qua">
                                                    <i class="fas fa-step-forward"></i>
                                                </button>
                                            @elseif ($data->cancel == false && $send == true)
                                                <button type="button" {{ $auth_deActive }} 
                                                    class="btn btn-danger btn-deactive-ajax"
                                                    data-id="{{ $data->id }}" 
                                                    data-type="cancel" 
                                                    data-name="{{ $data->name }}"
                                                    title="Hủy bỏ">
                                                    <i class="fas fa-step-forward"></i>
                                                </button>
                                            @elseif ($data->cancel == true && $send == true)
                                                <button type="button" {{ $auth_deActive }} 
                                                    class="btn btn-success btn-deactive-ajax"
                                                    data-id="{{ $data->id }}" 
                                                    data-type="restore" 
                                                    data-name="{{ $data->name }}"
                                                    title="Khôi phục">
                                                    <i class="fas fa-step-forward"></i>
                                                </button>
                                            @endif
                                        </div>
                                    </td>


                                    {{-- 
                                    <td class="text-center align-middle">
                                        <button type="button" class="btn btn-primary btn-history position-relative"
                                            data-id="{{ $data->id }}" data-toggle="modal"
                                            data-target="#historyModal">
                                            <i class="fas fa-history"></i>
                                            <span class="badge badge-danger"
                                                style="position: absolute; top: -5px;  right: -5px; border-radius: 50%;">
                                                {{ $data->history_count ?? 0 }}
                                            </span>
                                        </button>
                                    </td> --}}
                                </tr>
                            @endforeach

                        </tbody>
                    </table>
                </div>
                <!-- /.card-body -->
            </div>
            <!-- /.card -->
        </div>
        <!-- /.container-fluid -->
    </section>
    <!-- /.content -->
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
            timer: 1000, // tự đóng sau 2 giây
            showConfirmButton: false
        });
    </script>
@endif

<script>
    $(document).ready(function() {
        preventDoubleSubmit("#send_form", "#send_btn");
        document.body.style.overflowY = "auto";
        $('.btn-edit').click(function() {
            const button = $(this);
            const modal = $('#update_modal');

            // Gán dữ liệu vào input
            modal.find('input[name="id"]').val(button.data('id'));
            modal.find('input[name="code"]').val(button.data('code'));
            modal.find('input[name="name"]').val(button.data('name'));
            modal.find('input[name="expected_date"]').val(button.data('expected_date'));
            modal.find('input[name="rooms"]').val(button.data('rooms'));
            modal.find('textarea[name="note"]').val(button.data('note'));


        });



        $('.btn-deactive-ajax').on('click', function() {
            const btn = $(this);
            const id = btn.data('id');
            const type = btn.data('type');
            const productName = btn.data('name');
            let title = "";

            if (type == "delete") {
                title = "Bạn chắc chắn muốn bỏ qua thiết bị này?";
            } else if (type == "cancel") {
                title = "Bạn chắc chắn muốn hủy kế hoạch?";
            } else {
                title = "Bạn chắc chắn muốn phục hồi kế hoạch?";
            }

            Swal.fire({
                title: title,
                text: `Thiết bị: ${productName}`,
                icon: 'warning',
                input: (type === 'cancel' || type === 'delete') ? 'textarea' : null,
                inputPlaceholder: 'Nhập lý do...',
                showCancelButton: true,
                confirmButtonColor: '#28a745',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Đồng ý',
                cancelButtonText: 'Hủy',
                preConfirm: (reason) => {
                    if ((type === 'cancel' || type === 'delete') && !reason) {
                        Swal.showValidationMessage('Bạn phải nhập lý do');
                    }
                    return reason;
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: "{{ route('pages.plan.maintenance.deActive') }}",
                        type: 'POST',
                        data: {
                            _token: "{{ csrf_token() }}",
                            id: id,
                            type: type,
                            deactive_reason: result.value
                        },
                        success: function(res) {
                            if (res.success) {
                                Swal.fire({
                                    title: 'Thành công!',
                                    text: res.message,
                                    icon: 'success',
                                    timer: 1000,
                                    showConfirmButton: false
                                });
                                // Update UI without reload
                                if (type === 'delete') {
                                    btn.closest('tr').fadeOut(500, function() {
                                        $(this).remove();
                                    });
                                } else {
                                    // For cancel/restore, we might need more complex UI toggle, 
                                    // but for now, simple reload or hide is easiest if it changes multiple things.
                                    // Let's try to reload for cancel/restore as they change many things in the row
                                    location.reload(); 
                                }
                            }
                        },
                        error: function(xhr) {
                            Swal.fire('Lỗi!', 'Không thể cập nhật trạng thái.', 'error');
                        }
                    });
                }
            });
        });

        $('.btn-history').on('click', function() {
            const planMasterId = $(this).data('id');
            const history_modal = $('#data_table_history_body')

            // Xóa dữ liệu cũ
            history_modal.empty();

            // Gọi Ajax lấy dữ liệu history
            $.ajax({
                url: "{{ route('pages.plan.maintenance.history') }}",
                type: 'post',
                data: {
                    id: planMasterId,
                    _token: "{{ csrf_token() }}"
                },
                success: function(res) {
                    if (res.length === 0) {
                        history_modal.append(
                            `<tr><td colspan="13" class="text-center">Không có lịch sử</td></tr>`
                        );
                    } else {
                        res.forEach((item, index) => {
                            // map màu level

                            history_modal.append(`
                              <tr>
                                  <td>${index + 1}</td>
                                  <td class="${index === 0 ? 'text-success' : 'text-danger'}"">
                                      <div>${item.code ?? ''}</div>
                                  </td>
                                  <td>${item.name ?? ''} </td>
                                  <td>${item.sch_type ?? ''}</td>
                                  <td>
                                      <div>${item.expected_date ? moment(item.expected_date).format('DD/MM/YYYY') : ''}</div>
                                  </td>
                                  <td>${item.room ?? ''} </td>
                                  <td>${item.note ?? ''}</td>
                                  <td>${item.version ?? ''}</td>
                                  <td >${item.reason ?? ''}</td>

                                  <td>
                                      <div>${item.prepared_by ?? ''}</div>
                                      <div>${item.created_at ? moment(item.created_at).format('DD/MM/YYYY') : ''}</div>
                                  </td>
                              </tr>
                          `);
                        });
                    }
                },
                error: function() {
                    history_modal.append(
                        `<tr><td colspan="13" class="text-center text-danger">Lỗi tải dữ liệu</td></tr>`
                    );
                }
            });
        });

        $('#dt_plan_master_maintenance').DataTable({
            paging: true,
            lengthChange: true,
            searching: true,
            ordering: true,
            info: true,
            autoWidth: false,
            pageLength: 10,
            lengthMenu: [
                [10, 25, 50, 100, -1],
                [10, 25, 50, 100, "Tất cả"]
            ],
            language: {
                search: "Tìm kiếm:",
                lengthMenu: "Hiển thị _MENU_ dòng",
                info: "Hiển thị _START_ đến _END_ của _TOTAL_ dòng",
                paginate: {
                    previous: "Trước",
                    next: "Sau"
                }
            },
            infoCallback: function(settings, start, end, max, total, pre) {
                let activeCount = 0;
                let inactiveCount = 0;

                settings.aoData.forEach(function(row) {
                    // row.anCells là danh sách <td> của từng hàng
                    const lastTd = row.anCells[row.anCells.length -
                        1]; // cột cuối (Vô Hiệu)
                    const btn = $(lastTd).find('button[type="submit"]');
                    const status = btn.data('type'); // lấy 1 hoặc 0

                    if (status == 1) activeCount++;
                    else inactiveCount++;
                });

                return pre + ` (Đang hiệu lực: ${activeCount}, Vô hiệu: ${inactiveCount})`;
            }
        });

    });
</script>
