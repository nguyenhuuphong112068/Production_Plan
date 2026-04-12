<!-- Cần load CSS Fullcalendar -->
<link rel="stylesheet" href="{{ asset('dataTable/plugins/fullcalendar/main.min.css') }}">
<link rel="stylesheet" href="{{ asset('dataTable/plugins/fullcalendar-daygrid/main.min.css') }}">
<link rel="stylesheet" href="{{ asset('dataTable/plugins/fullcalendar-timegrid/main.min.css') }}">
<link rel="stylesheet" href="{{ asset('dataTable/plugins/fullcalendar-bootstrap/main.min.css') }}">

<div class="content-wrapper">
    <section class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1>Cập Nhật Ngày Nghỉ</h1>
          </div>
        </div>
      </div>
    </section>

    <!-- Main content -->
    <section class="content">
      <div class="container-fluid">
        <div class="row">
          <div class="col-md-3">
            <div class="sticky-top mb-3">
              <div class="card">
                <div class="card-header">
                  <h4 class="card-title">Cờ/Chú thích</h4>
                  <div class="card-tools">
                      <button type="button" class="btn btn-tool text-success" id="btn-add-flag" title="Tạo thêm cờ/ghi chú mới">
                          <i class="fas fa-plus"></i>
                      </button>
                  </div>
                </div>
                <div class="card-body">
                  <div id="external-events">
                    @if(isset($flags) && count($flags) > 0)
                        @foreach($flags as $flag)
                            <div class="external-event {{ $flag->color }} d-flex justify-content-between align-items-center" style="cursor: move;" id="flag-item-{{ $flag->id }}" data-id="{{ $flag->id }}" data-name="{{ $flag->name }}" data-color="{{ $flag->color }}">
                                <span class="flag-text">{{ $flag->name }}</span>
                                <a href="javascript:void(0)" class="text-white btn-edit-flag" title="Sửa/Xóa" style="opacity: 0.8;"><i class="fas fa-edit"></i></a>
                            </div>
                        @endforeach
                    @else
                        <div class="external-event bg-danger d-flex justify-content-between align-items-center" style="cursor: move;">
                            <span class="flag-text">Ngày Nghỉ Công Ty</span>
                        </div>
                    @endif
                  </div>
                </div>
              </div>

              <!-- Nút Thêm Mới -->
              @if (user_has_permission(session('user')['userId'], 'materData_productName_create', 'boolean'))
              <div class="card">
                <div class="card-header">
                  <h3 class="card-title">Hành Động</h3>
                </div>
                <div class="card-body">
                  <button type="button" class="btn btn-success btn-block btn-create" >
                    <i class="fas fa-plus"></i> Thêm Ngày Nghỉ
                  </button>
                </div>
              </div>
              @endif
            </div>
          </div>

          <!-- Khu Vực Hiển Thị Lịch -->
          <div class="col-md-9">
            <div class="card card-primary">
              <div class="card-body p-0">
                <!-- Nơi chứa Calendar -->
                <div id="calendar"></div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>
</div>

<!-- Modal Thêm/Sửa -->
<div class="modal fade" id="create_modal" tabindex="-1" role="dialog" aria-labelledby="createModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="createModalLabel">Cập Nhật Ngày Nghỉ</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="form_offdays">
                    <input type="hidden" name="id" id="modal_id">
                    
                    <div class="form-group row">
                        <label for="off_date" class="col-sm-3 col-form-label">Ngày Nghỉ <span class="text-danger">*</span></label>
                        <div class="col-sm-9">
                            <input type="date" class="form-control" name="off_date" id="modal_off_date" required>
                        </div>
                    </div>

                    <div class="form-group row">
                        <label for="reason" class="col-sm-3 col-form-label">Lý Do</label>
                        <div class="col-sm-9">
                            <input type="text" class="form-control" name="reason" id="modal_reason" placeholder="VD: Nghỉ Tết Dương Lịch">
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-danger" id="btn-delete-event" style="display:none; margin-right:auto;">Xóa</button>
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
                        <button type="submit" class="btn btn-success" id="btn-save-event">Lưu Thay Đổi</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modal Thêm/Sửa Cờ (Flag) -->
<div class="modal fade" id="modal_flag" tabindex="-1" role="dialog" aria-labelledby="flagModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="flagModalLabel">Cập Nhật Cờ / Chú Thích</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="form_flag">
                    <input type="hidden" name="id" id="flag_id">
                    <div class="form-group row">
                        <label for="flag_name" class="col-sm-3 col-form-label">Tên Cờ <span class="text-danger">*</span></label>
                        <div class="col-sm-9">
                            <input type="text" class="form-control" name="name" id="flag_name" required>
                        </div>
                    </div>
                    <div class="form-group row">
                        <label for="flag_color" class="col-sm-3 col-form-label">Màu Sắc</label>
                        <div class="col-sm-9">
                            <select class="form-control" name="color" id="flag_color">
                                <option value="bg-danger" class="bg-danger">Đỏ (Danger)</option>
                                <option value="bg-warning" class="bg-warning">Cam (Warning)</option>
                                <option value="bg-success" class="bg-success">Xanh Lá (Success)</option>
                                <option value="bg-info" class="bg-info">Xanh Da Trời (Info)</option>
                                <option value="bg-primary" class="bg-primary">Xanh Đậm (Primary)</option>
                                <option value="bg-secondary" class="bg-secondary">Xám (Secondary)</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-danger" id="btn-delete-flag" style="display:none; margin-right:auto;">Xóa</button>
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
                        <button type="submit" class="btn btn-success">Lưu</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Thư Viện jQuery, Bootstrap, FullCalendar, Sweetalert2 -->
<script src="{{ asset('js/vendor/jquery-1.12.4.min.js') }}"></script>
<script src="{{ asset('js/popper.min.js') }}"></script>
<script src="{{ asset('js/bootstrap.min.js') }}"></script>
<script src="{{ asset('js/sweetalert2.all.min.js') }}"></script>
<script src="{{ asset('dataTable/plugins/moment/moment.min.js') }}"></script>
<script src="{{ asset('dataTable/plugins/fullcalendar/main.min.js') }}"></script>
<script src="{{ asset('dataTable/plugins/fullcalendar-daygrid/main.min.js') }}"></script>
<script src="{{ asset('dataTable/plugins/fullcalendar-timegrid/main.min.js') }}"></script>
<script src="{{ asset('dataTable/plugins/fullcalendar-interaction/main.min.js') }}"></script>
<script src="{{ asset('dataTable/plugins/fullcalendar-bootstrap/main.min.js') }}"></script>

<script>
    $(document).ready(function() {
        document.body.style.overflowY = "auto";

        // Setup CSRF cho ngầm định AJAX
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            }
        });

        // Nút thêm cờ động bằng Modal
        $('#btn-add-flag').click(function() {
            $('#form_flag')[0].reset();
            $('#flag_id').val('');
            $('#btn-delete-flag').hide();
            $('#modal_flag').modal('show');
        });

        // Click sửa cờ
        $(document).on('click', '.btn-edit-flag', function(e) {
            e.preventDefault();
            e.stopPropagation();
            var parent = $(this).closest('.external-event');
            $('#flag_id').val(parent.data('id'));
            $('#flag_name').val(parent.data('name'));
            $('#flag_color').val(parent.data('color'));
            $('#btn-delete-flag').show();
            $('#modal_flag').modal('show');
        });

        // AJAX Lưu / Sửa Cờ
        $('#form_flag').on('submit', function(e) {
            e.preventDefault();
            var id = $('#flag_id').val();
            var name = $('#flag_name').val();
            var color = $('#flag_color').val();

            $.ajax({
                url: '{{ route("pages.materData.offdays.flags_store_ajax") }}',
                type: 'POST',
                data: { id: id, name: name, color: color },
                success: function(response) {
                    if(response.success) {
                        $('#modal_flag').modal('hide');
                        if (id) {
                            var flagDiv = $('#flag-item-' + id);
                            flagDiv.removeClass(flagDiv.data('color')).addClass(response.color);
                            flagDiv.find('.flag-text').text(response.name);
                            flagDiv.data('name', response.name);
                            flagDiv.data('color', response.color);
                        } else {
                            var flag = $('<div />')
                                .addClass('external-event d-flex justify-content-between align-items-center ' + response.color)
                                .attr('id', 'flag-item-' + response.id)
                                .attr('data-id', response.id)
                                .attr('data-name', response.name)
                                .attr('data-color', response.color)
                                .css('cursor', 'move')
                                .html('<span class="flag-text">' + response.name + '</span> <a href="javascript:void(0)" class="text-white btn-edit-flag" title="Sửa/Xóa" style="opacity: 0.8;"><i class="fas fa-edit"></i></a>');
                            
                            $('#external-events').append(flag);
                            
                            var MathDraggable = FullCalendarInteraction.Draggable;
                            new MathDraggable(flag[0], {
                                eventData: function(eventEl) {
                                    return {
                                        title: $(eventEl).find('.flag-text').text(),
                                        backgroundColor: window.getComputedStyle(eventEl, null).getPropertyValue('background-color'),
                                        borderColor: window.getComputedStyle(eventEl, null).getPropertyValue('background-color'),
                                        textColor: window.getComputedStyle(eventEl, null).getPropertyValue('color'),
                                    };
                                }
                            });
                        }
                        Swal.mixin({toast: true, position: "top-end", showConfirmButton: false, timer: 2000}).fire({ icon: "success", title: "Lưu cờ thành công" });
                    } else {
                        Swal.fire('Lỗi', response.message, 'error');
                    }
                }
            });
        });

        // AJAX Xóa Cờ
        $('#btn-delete-flag').click(function() {
            var id = $('#flag_id').val();
            if(!id) return;

            Swal.fire({
                title: 'Thực sự xóa cờ này?',
                text: "Xóa sẽ mất khỏi danh sách kéo thả.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Xóa ngay',
                cancelButtonText: 'Hủy'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: '{{ route("pages.materData.offdays.flags_delete_ajax") }}',
                        type: 'POST',
                        data: { id: id },
                        success: function(response) {
                            if(response.success) {
                                $('#modal_flag').modal('hide');
                                $('#flag-item-' + id).remove();
                                Swal.mixin({toast: true, position: "top-end", showConfirmButton: false, timer: 2000}).fire({ icon: "success", title: "Đã xóa cờ" });
                            } else {
                                Swal.fire('Lỗi', response.message, 'error');
                            }
                        }
                    });
                }
            });
        });

        // Mở Form tạo mới
        $('.btn-create').click(function() {
            $('#form_offdays')[0].reset();
            $('#modal_id').val('');
            $('#btn-delete-event').hide();
            $('#create_modal').modal('show');
        });

        /* Chuẩn bị Cờ/Ghi chú để kéo thả (Draggable) */
        var MathDraggable = FullCalendarInteraction.Draggable;
        var containerEl = document.getElementById('external-events');

        new MathDraggable(containerEl, {
            itemSelector: '.external-event',
            eventData: function(eventEl) {
                return {
                    title: $(eventEl).find('.flag-text').text(),
                    backgroundColor: window.getComputedStyle(eventEl, null).getPropertyValue('background-color'),
                    borderColor: window.getComputedStyle(eventEl, null).getPropertyValue('background-color'),
                    textColor: window.getComputedStyle(eventEl, null).getPropertyValue('color'),
                };
            }
        });

        /* Chuẩn bị dữ liệu cho Lịch */
        var Calendar = FullCalendar.Calendar;
        var calendarEl = document.getElementById('calendar');

        // Render dữ liệu JSON
        var calendarEvents = [
            @foreach($datas as $data)
            {
                id: '{{ $data->id }}',
                title: '{{ $data->reason ?: "Nghỉ" }}',
                start: new Date('{{ \Carbon\Carbon::parse($data->off_date)->format('Y/m/d') }}'),
                backgroundColor: '#dc3545', 
                borderColor    : '#dc3545', 
                allDay         : true,
                extendedProps: {
                    date_str: '{{ \Carbon\Carbon::parse($data->off_date)->format('Y-m-d') }}',
                    reason: '{{ $data->reason }}'
                }
            },
            @endforeach
        ];

        var calendar = new Calendar(calendarEl, {
            plugins: [ 'bootstrap', 'interaction', 'dayGrid', 'timeGrid' ],
            header    : {
                left  : 'prev,next today',
                center: 'title',
                right : 'dayGridMonth,timeGridWeek,timeGridDay'
            },
            'themeSystem': 'bootstrap',
            events    : calendarEvents,
            editable  : false,   
            droppable : true,    
            eventReceive: function(info) {
                var reason = info.event.title;
                var d = info.event.start;
                var off_date = d.getFullYear() + "-" + String(d.getMonth() + 1).padStart(2, '0') + "-" + String(d.getDate()).padStart(2, '0');

                $.ajax({
                    url: '{{ route("pages.materData.offdays.store_ajax") }}',
                    type: 'POST',
                    data: { off_date: off_date, reason: reason },
                    success: function(response) {
                        if(response.success) {
                            // Update the automatically auto-created event with server ID
                            info.event.setProp('id', response.id);
                            info.event.setExtendedProp('date_str', response.off_date);
                            info.event.setExtendedProp('reason', response.reason);
                            
                            const Toast = Swal.mixin({
                              toast: true, position: "top-end",
                              showConfirmButton: false, timer: 3000
                            });
                            Toast.fire({ icon: "success", title: "Đã thêm ngày nghỉ" });
                        } else {
                            info.event.remove(); // Rollback visually if failed
                            Swal.fire('Lỗi', response.message, 'error');
                        }
                    },
                    error: function() {
                         info.event.remove();
                         Swal.fire('Lỗi', 'Lỗi kết nối máy chủ', 'error');
                    }
                });
            },
            selectable : true,
            dateClick: function(info) {
                $('#form_offdays')[0].reset();
                $('#modal_id').val('');
                $('#modal_off_date').val(info.dateStr); 
                $('#btn-delete-event').hide();
                $('#create_modal').modal('show');
            },
            eventClick: function(info) {
                $('#modal_id').val(info.event.id);
                $('#modal_off_date').val(info.event.extendedProps.date_str);
                $('#modal_reason').val(info.event.extendedProps.reason);
                $('#btn-delete-event').show();
                $('#create_modal').modal('show');
            }
        });

        calendar.render();

        // Xử lý LƯU SỰ KIỆN bằng AJAX
        $('#form_offdays').on('submit', function(e) {
            e.preventDefault();
            var id = $('#modal_id').val();
            var off_date = $('#modal_off_date').val();
            var reason = $('#modal_reason').val();

            $.ajax({
                url: '{{ route("pages.materData.offdays.store_ajax") }}',
                type: 'POST',
                data: { id: id, off_date: off_date, reason: reason },
                success: function(response) {
                    if (response.success) {
                        $('#create_modal').modal('hide');

                        if (id) {
                            var event = calendar.getEventById(id);
                            if (event) {
                                event.setProp('title', response.reason);
                                event.setStart(new Date(response.off_date));
                                event.setExtendedProp('date_str', response.off_date);
                                event.setExtendedProp('reason', response.reason);
                            }
                        } else {
                            calendar.addEvent({
                                id: response.id,
                                title: response.reason,
                                start: new Date(response.off_date),
                                allDay: true,
                                backgroundColor: '#dc3545',
                                borderColor: '#dc3545',
                                extendedProps: {
                                    date_str: response.off_date,
                                    reason: response.reason
                                }
                            });
                        }

                        const Toast = Swal.mixin({
                              toast: true, position: "top-end",
                              showConfirmButton: false, timer: 3000
                        });
                        Toast.fire({ icon: "success", title: "Đã lưu thành công" });
                    } else {
                        Swal.fire('Lỗi', response.message, 'error');
                    }
                }
            });
        });

        // Xử lý XÓA SỰ KIỆN bằng AJAX
        $('#btn-delete-event').click(function() {
            var id = $('#modal_id').val();
            if(!id) return;

            Swal.fire({
                title: 'Bạn chắc chắn muốn xóa?',
                text: "Dữ liệu ngày nghỉ sẽ bị xóa",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Có, xóa ngay!',
                cancelButtonText: 'Hủy'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: '{{ route("pages.materData.offdays.delete_ajax") }}',
                        type: 'POST',
                        data: { id: id },
                        success: function(response) {
                            if(response.success) {
                                $('#create_modal').modal('hide');
                                var event = calendar.getEventById(id);
                                if (event) {
                                    event.remove();
                                }
                                Swal.fire('Đã xóa!', 'Sự kiện đã bị xóa khỏi lịch.', 'success');
                            } else {
                                Swal.fire('Lỗi!', response.message, 'error');
                            }
                        }
                    });
                }
            });
        });

    });
</script>
