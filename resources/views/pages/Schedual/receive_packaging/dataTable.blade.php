<style>
    .chat-box::-webkit-scrollbar {
        width: 5px;
    }

    .chat-box::-webkit-scrollbar-thumb {
        background: #ccc;
        border-radius: 5px;
    }

    .chat-px {
        background-color: #d4edda;
    }

    .chat-other {
        background-color: #d1ecf1;
    }

    .chat-input-wrapper input {
        border-radius: 20px 0 0 20px;
    }

    .chat-input-wrapper button {
        border-radius: 0 20px 20px 0;
    }

    .chat-box::-webkit-scrollbar {
        width: 4px;
    }

    .chat-box::-webkit-scrollbar-thumb {
        background: #ccc;
        border-radius: 5px;
    }

    .updateInput {
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
    .updateInput:focus {
        border: 1px solid #007bff;
        border-radius: 2px;
        background-color: #fff;
    }

    /* Tùy chọn: nếu bạn muốn chữ canh giữa theo chiều dọc */
    td input.updateInput {
        display: block;
        margin: auto;
    }

    .highlight-row {
        background-color: #ebd9f0 !important;
        /* vàng nhạt */
    }
</style>

@php
    $receive_packaging_feedback = user_has_permission(
        session('user')['userId'],
        'receive_packaging_feedback',
        'disabled',
    );
    $Change_receive_packaging_date = user_has_permission(
        session('user')['userId'],
        'Change_receive_packaging_date',
        'disabled',
    );
    $confirm_receive_packaging = user_has_permission(
        session('user')['userId'],
        'confirm_receive_packaging',
        'disabled',
    );

    //dd ($receive_packaging_feedback,$Change_receive_packaging_date );

@endphp


<div class="content-wrapper">
    <!-- Main content -->
    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <!-- /.card-header -->
                    <div class="card">

                        <div class="card-header mt-4">
                            {{-- <h3 class="card-title">Ghi Chú Nếu Có</h3> --}}
                        </div>
                        <!-- /.card-Body -->
                        <div class="card-body">

                            <form id="filterForm" method="GET"
                                action="{{ route('pages.Schedual.receive_packaging.list') }}"
                                class="d-flex flex-wrap gap-2">
                                @csrf
                                <div class="row w-100 align-items-center">

                                    <!-- Filter From/To -->
                                    <div class="col-md-4 d-flex gap-2">
                                        @php
                                            use Carbon\Carbon;
                                            $defaultFrom = Carbon::now()->toDateString();
                                            $defaultTo = Carbon::now()->addDays(7)->toDateString();
                                        @endphp
                                        <div class="form-group d-flex align-items-center">
                                            <label for="from_date" class="mr-2 mb-0">From:</label>
                                            <input type="date" id="from_date" name="from_date"
                                                value="{{ request('from_date') ?? $defaultFrom }}"
                                                class="form-control" />
                                        </div>
                                        <div class="form-group d-flex align-items-center">
                                            <label for="to_date" class="mr-2 mb-0">To:</label>
                                            <input type="date" id="to_date" name="to_date"
                                                value="{{ request('to_date') ?? $defaultTo }}" class="form-control" />
                                        </div>
                                    </div>

                                    <!-- Stage Selector -->
                                    <div class="col-md-4 d-flex justify-content-center align-items-center"
                                        style="gap: 10px; height: 40px;">
                                        {{-- <input type="hidden" name="stage_code" id="stage_code" value="{{ $stageCode }}">
                            <button type="button" id="prevStage" class="btn btn-link stage-btn" style="font-size: 25px;">&laquo;</button>
                            <span id="stageName" class="fw-bold text-center" style="font-size: 25px;">
                                {{ optional($stages->firstWhere('stage_code', $stageCode))->stage ?? 'Không có công đoạn' }}
                            </span>
                            <button type="button" id="nextStage" class="btn btn-link stage-btn" style="font-size: 25px;">&raquo;</button> --}}
                                    </div>

                                    <!-- Optional Right Side -->
                                    <div class="col-md-4 d-flex justify-content-end">
                                        {{-- <div class="form-group " style="width: 200px">
                                <select class="form-control" name="stage_code" style="text-align-last: center;"
                                    onchange="document.getElementById('filterForm').submit();">
                                    <option {{ $stageCode == 1 ? 'selected' : '' }} value=1>Cân NL</option>
                                    <option {{ $stageCode == 2 ? 'selected' : '' }} value=2>Cân NL Khác</option>
                                    <option {{ $stageCode == 3 ? 'selected' : '' }} value=3>Pha Chế</option>
                                    <option {{ $stageCode == 4 ? 'selected' : '' }} value=4>Trộn Hoàn Tất</option>
                                    <option {{ $stageCode == 5 ? 'selected' : '' }} value=5>Định Hình</option>
                                    <option {{ $stageCode == 6 ? 'selected' : '' }} value=6>Bao Phim</option>
                                    <option {{ $stageCode == 7 ? 'selected' : '' }} value=7>ĐGSC-ĐGTC</option>
                                </select> 
                        </div> --}}

                                    </div>

                                </div>
                            </form>
                            <table id="data_table_Schedual_list" class="table table-bordered table-striped"
                                style="font-size: 20px">
                                <thead style = "position: sticky; top: 60px; background-color: white; z-index: 1020">
                                    <tr>
                                        <th>STT</th>
                                        <th>Mã Sản Phẩm</th>
                                        <th>Sản Phẩm</th>
                                        <th>Số Lô/ TĐ</th>
                                        <th>Ngày Dự Kiến KCS</th>
                                        <th>Qui Cách</th>
                                        <th>Phòng Sản Xuất</th>
                                        <th>Thới Gian Sản Xuất</th>
                                        {{-- <th>Thời Gian Vệ Sinh</th> --}}
                                        <th>Ngày Nhận Bao Bì</th>
                                        <th>Trao Đổi Thông tin</th>

                                    </tr>
                                </thead>
                                <tbody>

                                    @foreach ($datas->sortBy('receive_packaging_date') as $data)
                                        <tr>
                                            <td>{{ $loop->iteration }} <br>
                                                @if (session('user')['userGroup'] == 'Admin')
                                                    {{ $data->id }} <br>
                                                    {{ $data->plan_master_id }}
                                                @endif
                                            </td>
                                            <td>
                                                <div> {{ $data->intermediate_code }} </div>
                                                <div> {{ $data->finished_product_code }} </div>
                                            </td>
                                            <td>{{ $data->product_name }} -
                                                {{ $data->batch_qty . ' - ' . $data->unit_batch_qty . ' - ' . $data->market }}
                                            </td>
                                            <td>{{ $data->batch }}
                                                @if ($data->is_val)
                                                    <i class="fas fa-check-circle text-primary fs-4"></i>
                                                @endif
                                            </td>

                                            <td>
                                                <div>{{ \Carbon\Carbon::parse($data->expected_date)->format('d/m/Y') }}
                                                </div>
                                            </td>
                                            <td>
                                                <div>{{ $data->specification }} </div>
                                            </td>

                                            <td> {{ $data->room_name . ' - ' . $data->room_code }} </td>
                                            <td> {{ \Carbon\Carbon::parse($data->start)->format('d/m/Y H:i') . ' - ' . \Carbon\Carbon::parse($data->end)->format('d/m/Y H:i') }}
                                            </td>

                                            <td>
                                                <div style="display:flex; flex-direction:column; gap:6px;">
                                                    <div class ="{{ $data->received == 1 ? 'highlight-row' : '' }}"
                                                        style="display:flex; align-items:center; gap:6px; border-radius:15px; padding:6px;">
                                                        <span>BBSC:</span>
                                                        <button type="button"
                                                            class="btn btn-sm btn-success btn-received position-relative mt-0"
                                                            {{ $data->received == 1 ? 'disabled' : '' }}
                                                            style="padding:2px 6px; font-size:12px;"
                                                            data-id="{{ $data->id }}"
                                                            {{ $confirm_receive_packaging }}>
                                                            <i class="fas fa-check"></i>
                                                        </button>
                                                        @if ($data->received == 0)
                                                            <input {{ $Change_receive_packaging_date }} type="date"
                                                                class="updateInput" name="receive_packaging_date"
                                                                value="{{ $data->receive_packaging_date ? \Carbon\Carbon::parse($data->receive_packaging_date)->format('Y-m-d') : '' }}"
                                                                data-id="{{ $data->id }}">
                                                        @else
                                                            {{ $data->receive_packaging_date ? \Carbon\Carbon::parse($data->receive_packaging_date)->format('d/m/Y') : '' }}
                                                        @endif
                                                        <button type="button" class="btn btn-sm btn-outline-info show-packaging-history mt-0" data-id="{{ $data->id }}" data-type="0" title="Lịch sử thay đổi" style="padding:2px 6px; font-size:12px;">
                                                            <i class="fas fa-history"></i>
                                                        </button>
                                                    </div>
                                                    <div class="tc-confirm-info">
                                                        Người xác nhận: <span
                                                            class="confirm-by">{{ $data->packaging_confirm_by ?? '' }}</span>
                                                        <br>
                                                        Ngày xác nhận: <span class="confirm-date">
                                                            {{ $data->packaging_confirm_date ? \Carbon\Carbon::parse($data->packaging_confirm_date)->format('d/m/Y') : '' }}
                                                        </span>
                                                    </div>

                                                    <div class ="{{ $data->received_second_packaging == 1 ? 'highlight-row' : '' }}"
                                                        style="display:flex; align-items:center; gap:6px; border-radius:15px; padding:6px;">
                                                        <span>BBTC:</span>
                                                        <button type="button"
                                                            class="btn btn-sm btn-success btn-received-second position-relative mt-0"
                                                            {{ $data->received_second_packaging == 1 ? 'disabled' : '' }}
                                                            style="padding:2px 6px; font-size:12px;"
                                                            data-id="{{ $data->id }}"
                                                            {{ $confirm_receive_packaging }}>
                                                            <i class="fas fa-check"></i>
                                                        </button>
                                                        @if ($data->received_second_packaging == 0)
                                                            <input {{ $Change_receive_packaging_date }} type="date"
                                                                class="updateInput" name="receive_second_packaging_date"
                                                                value="{{ $data->receive_second_packaging_date ? \Carbon\Carbon::parse($data->receive_second_packaging_date)->format('Y-m-d') : '' }}"
                                                                data-id="{{ $data->id }}">
                                                        @else
                                                            {{ $data->receive_second_packaging_date ? \Carbon\Carbon::parse($data->receive_second_packaging_date)->format('d/m/Y') : '' }}
                                                        @endif
                                                        <button type="button" class="btn btn-sm btn-outline-info show-packaging-history mt-0" data-id="{{ $data->id }}" data-type="1" title="Lịch sử thay đổi" style="padding:2px 6px; font-size:12px;">
                                                            <i class="fas fa-history"></i>
                                                        </button>
                                                    </div>
                                                    <div class="tc-confirm-info">
                                                        Người xác nhận: <span
                                                            class="second-confirm-by">{{ $data->second_packaging_confirm_by ?? '' }}</span>
                                                        <br>
                                                        Ngày xác nhận: <span class="second-confirm-date">
                                                            {{ $data->second_packaging_confirm_date ? \Carbon\Carbon::parse($data->second_packaging_confirm_date)->format('d/m/Y') : '' }}
                                                        </span>
                                                    </div>


                                                </div>
                                            </td>
                                            <td style="min-width:350px">
                                                {{-- ===== LIST COMMENT ===== --}}
                                                <div class="chat-box"
                                                    style="max-height:150px; overflow-y:auto; font-size:14px">
                                                    @forelse ($data->comments as $comment)
                                                        <div class="mb-2 p-2 border rounded"
                                                            style="background-color: {{ \Illuminate\Support\Str::startsWith($comment->deparment, 'PX') ? '#d4edda' : '#d1ecf1' }}; border-radius:15px; padding:6px;">
                                                            <div style="font-weight:600">
                                                                {{ $comment->user_name }}
                                                                <small class="text-muted">
                                                                    {{ \Carbon\Carbon::parse($comment->created_at)->format('d/m H:i') }}
                                                                </small>
                                                            </div>

                                                            <div>
                                                                {!! preg_replace('/@(.*?)\[\d+\]/', '<b class="text-primary">@$1</b>', $comment->message) !!}
                                                            </div>
                                                        </div>

                                                    @empty
                                                        <div class="text-muted">Chưa có trao đổi</div>
                                                    @endforelse
                                                </div>
                                                {{-- ===== INPUT CHAT ===== --}}
                                                <div class="chat-input-wrapper d-flex">
                                                    <input type="text"
                                                        class="form-control form-control-sm chat-input"
                                                        data-row-id="{{ $data->plan_master_id }}"
                                                        placeholder="Nhập trao đổi..."
                                                        {{ $receive_packaging_feedback }}>
                                                    <button class="btn btn-sm btn-primary send-comment"
                                                        data-row-id="{{ $data->plan_master_id }}">
                                                        Gửi
                                                    </button>
                                                </div>

                                            </td>


                                        </tr>
                                    @endforeach

                                </tbody>
                            </table>
                        </div>
                        <!-- /.card-body -->
                    </div>
                    <!-- /.card -->
                </div>
                <!-- /.col -->
            </div>
            <!-- /.row -->
        </div>
        <!-- /.container-fluid -->
    </section>
    <!-- /.content -->
</div>

<!-- Modal Lịch sử -->
<div class="modal fade" id="packagingHistoryModal" tabindex="-1" role="dialog" aria-labelledby="historyModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title" id="historyModalLabel">Lịch Sử Thay Đổi Ngày Nhận Bao Bì</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body" style="font-size: 14px;">
                <table class="table table-sm table-bordered table-striped" id="historyTable">
                    <thead class="bg-light">
                        <tr>
                            <th>Stt</th>
                            <th>Ngày nhận</th>
                            <th>Version</th>
                            <th>Người tạo</th>
                            <th>Ngày tạo</th>
                        </tr>
                    </thead>
                    <tbody id="historyBody">
                        <!-- Dữ liệu AJAX sẽ đổ vào đây -->
                    </tbody>
                </table>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
            </div>
        </div>
    </div>
</div>

<script src="{{ asset('js/vendor/jquery-1.12.4.min.js') }}"></script>
<script src="{{ asset('js/popper.min.js') }}"></script>
<script src="{{ asset('js/bootstrap.min.js') }}"></script>
<script src="{{ asset('js/sweetalert2.all.min.js') }}"></script>



<script>
    $(document).ready(function() {
        document.body.style.overflowY = "auto";
        $('#data_table_Schedual_list').DataTable({
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
        });

        // --- TÍCH HỢP TRIBUTE.JS CHO @MENTION ---
        let tribute;

        function initMentions() {
            if (typeof Tribute === 'undefined') {
                setTimeout(initMentions, 200);
                return;
            }

            $.get("{{ route('chat.users') }}", function(users) {
                let mentionValues = users.map(u => ({
                    key: u.fullName,
                    value: `@${u.fullName}[${u.id}]`
                }));

                tribute = new Tribute({
                    values: mentionValues,
                    selectTemplate: function(item) {
                        return item.original.value;
                    }
                });

                // Gắn vào các ô input hiện có
                tribute.attach(document.querySelectorAll('.chat-input'));
            });
        }

        initMentions();

        // Re-attach khi DataTable vẽ lại
        $('#data_table_Schedual_list').on('draw.dt', function() {
            if (tribute) {
                tribute.attach(document.querySelectorAll('.chat-input'));
            }
        });

        $(document).on('focus', '.updateInput', function() {
            $(this).data('old-value', $(this).val());
        });

        $(document).on('blur', '.updateInput', function() {

            let id = $(this).data('id');
            let name = $(this).attr('name');
            let updateValue = $(this).val();
            let oldValue = $(this).data('old-value');

            if (updateValue === oldValue) return;

            if (id == '') {
                Swal.fire({
                    title: 'Cảnh Báo!',
                    text: 'id Không xác định',
                    icon: 'warning',
                    timer: 1000, // tự đóng sau 2 giây
                    showConfirmButton: false
                });
                $(this).val('');
                return
            }
            $.ajax({
                url: "{{ route('pages.Schedual.receive_packaging.updateInput') }}",
                type: 'POST',
                dataType: 'json',
                data: {
                    _token: '{{ csrf_token() }}',
                    stage_plan_id: id,
                    name: name,
                    updateValue: updateValue
                }
            });
        });

    });

    $(document).on('click', '.send-comment', function() {

        let button = $(this);
        let rowId = button.data('row-id');
        let input = $('.chat-input[data-row-id="' + rowId + '"]');
        let message = input.val().trim();

        if (!message) return;

        // 🚫 Nếu đang gửi thì không cho gửi tiếp
        if (button.prop('disabled')) return;

        // 🔄 Disable + loading
        button.prop('disabled', true);
        button.html('<span class="spinner-border spinner-border-sm"></span>');

        $.ajax({
            url: "{{ route('pages.Schedual.receive_packaging.store') }}",
            type: "POST",
            data: {
                plan_master_id: rowId,
                message: message,
                _token: "{{ csrf_token() }}"
            },
            success: function(res) {

                let bgColor = res.department?.startsWith('PX') ?
                    '#d4edda' :
                    '#d1ecf1';

                let newComment = `
                    <div class="mb-2 p-2 border rounded"
                        style="background-color: ${bgColor}">
                        <div style="font-weight:600">
                            ${res.user_name}
                            <small class="text-muted">${res.time}</small>
                        </div>
                        <div>${res.message.replace(/@(.*?)\[\d+\]/g, '<b class="text-primary">@$1</b>')}</div>
                    </div>
                `;

                input.closest('td').find('.chat-box').prepend(newComment);
                input.val('');
            },
            error: function() {
                alert('Gửi thất bại!');
            },
            complete: function() {
                // ✅ Mở lại nút
                button.prop('disabled', false);
                button.html('Gửi');
            }
        });

    });

    $(document).on('keypress', '.chat-input', function(e) {
        if (e.which === 13 && !e.shiftKey) {
            e.preventDefault();
            $(this).siblings('.send-comment').click();
        }
    });

    $(document).on('click', '.btn-received', function() {

        let button = $(this);
        let plan_master_id = button.data('id');
        let row = button.closest('tr');

        $.ajax({
            url: "{{ route('pages.Schedual.receive_packaging.received') }}",
            type: "POST",
            data: {
                plan_master_id: plan_master_id,
                btn: "received",
                confirm_by: 'packaging_confirm_by',
                confirm_date: 'packaging_confirm_date',
                _token: "{{ csrf_token() }}"
            },
            success: function(res) {

                Swal.fire({
                    icon: 'success',
                    title: 'Hoàn Thành',
                    timer: 1500,
                    showConfirmButton: false
                });

                // Lấy đúng div TC
                let tcDiv = $(row).find('.btn-received').closest('div');

                // Disable tất cả button + input trong div đó
                tcDiv.find('button, input')
                    .prop('disabled', true)
                    .addClass('disabled');

                // Highlight div
                tcDiv.addClass('highlight-row');

                // ✅ Cập nhật người xác nhận + ngày xác nhận
                $(row).find('.tc-confirm-info .confirm-by')
                    .text(res.confirm_by);

                $(row).find('.tc-confirm-info .confirm-date')
                    .text(res.confirm_date);


            },
            error: function() {
                alert('Gửi thất bại!');
            }
        });

    });

    $(document).on('click', '.btn-received-second', function() {

        let button = $(this);
        let plan_master_id = button.data('id');
        let row = button.closest('tr');

        $.ajax({
            url: "{{ route('pages.Schedual.receive_packaging.received') }}",
            type: "POST",
            data: {
                plan_master_id: plan_master_id,
                btn: "received_second_packaging",
                confirm_by: 'second_packaging_confirm_by',
                confirm_date: 'second_packaging_confirm_date',
                _token: "{{ csrf_token() }}"
            },
            success: function(res) {

                Swal.fire({
                    icon: 'success',
                    title: 'Hoàn Thành',
                    timer: 1500,
                    showConfirmButton: false
                });

                // Lấy đúng div TC
                let tcDiv = $(row).find('.btn-received-second').closest('div');

                // Disable tất cả button + input trong div đó
                tcDiv.find('button, input')
                    .prop('disabled', true)
                    .addClass('disabled');

                // Highlight div
                tcDiv.addClass('highlight-row');

                // ✅ Cập nhật người xác nhận + ngày xác nhận
                $(row).find('.tc-confirm-info .second-confirm-by')
                    .text(res.confirm_by);

                $(row).find('.tc-confirm-info .second-confirm-date')
                    .text(res.confirm_date);
            },
            error: function() {
                alert('Gửi thất bại!');
            }
        });

    });

    $(document).on('click', '.show-packaging-history', function() {
        let id = $(this).data('id');
        let type = $(this).data('type');

        $('#historyBody').html('<tr><td colspan="5" class="text-center"><i class="fas fa-spinner fa-spin"></i> Đang tải...</td></tr>');
        $('#packagingHistoryModal').modal('show');

        $.ajax({
            url: "{{ route('pages.Schedual.receive_packaging.getHistory') }}",
            type: "GET",
            data: {
                stage_plan_id: id,
                type_packaging: type
            },
            success: function(res) {
                let html = '';
                if (res.length > 0) {
                    res.forEach((item, index) => {
                        let dateText = item.receive_packaging_date ? moment(item.receive_packaging_date).format('DD/MM/YYYY') : '-';
                        let createdAtText = item.created_at ? moment(item.created_at).format('DD/MM/YYYY HH:mm') : '-';
                        html += `
                            <tr>
                                <td>${index + 1}</td>
                                <td>${dateText}</td>
                                <td>${item.ver}</td>
                                <td>${item.created_by}</td>
                                <td>${createdAtText}</td>
                            </tr>
                        `;
                    });
                } else {
                    html = '<tr><td colspan="5" class="text-center">Chưa có lịch sử thay đổi</td></tr>';
                }
                const historyBody = document.getElementById('historyBody');
                if (historyBody) {
                    historyBody.innerHTML = html;
                }
            },
            error: function() {
                const historyBody = document.getElementById('historyBody');
                if (historyBody) {
                    historyBody.innerHTML = '<tr><td colspan="5" class="text-center text-danger">Lỗi khi tải dữ liệu!</td></tr>';
                }
            }
        });
    });
</script>




<script>
    const form = document.getElementById('filterForm');
    const fromInput = document.getElementById('from_date');
    const toInput = document.getElementById('to_date');

    [fromInput, toInput].forEach(input => {
        input.addEventListener('input', function() {
            form.submit();
        });
    });
</script>
