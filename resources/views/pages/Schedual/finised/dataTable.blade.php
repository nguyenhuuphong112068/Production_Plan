<style>
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

    .time:disabled,
    .updateInput:disabled {
        cursor: not-allowed;
    }

    /* Công tắc "Xác nhận và điều chỉnh lịch theo thời gian thực" */
    .reroute-toggle-box {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 10px;
        padding: 8px 12px;
        border: 1px dashed #f0ad4e;
        border-radius: 6px;
        background: #fffaf0;
    }

    .reroute-switch {
        position: relative;
        display: inline-block;
        width: 42px;
        height: 22px;
        flex-shrink: 0;
    }

    .reroute-switch input {
        opacity: 0;
        width: 0;
        height: 0;
    }

    .reroute-slider {
        position: absolute;
        inset: 0;
        cursor: pointer;
        background: #ccc;
        border-radius: 22px;
        transition: background .2s;
    }

    .reroute-slider::before {
        content: "";
        position: absolute;
        width: 16px;
        height: 16px;
        left: 3px;
        top: 3px;
        background: #fff;
        border-radius: 50%;
        transition: transform .2s;
    }

    .reroute-switch input:checked + .reroute-slider {
        background: #dc3545;
    }

    .reroute-switch input:checked + .reroute-slider::before {
        transform: translateX(20px);
    }

    .reroute-switch input:focus-visible + .reroute-slider {
        box-shadow: 0 0 0 2px #80bdff;
    }

    /* Cột "Thời Gian Sản Xuất": nhãn BĐSX/BĐCM/KT */
    .col-time-label {
        min-width: 80px;
        max-width: 110px;
    }

    /* Cột "Thời Gian Sản Xuất": input datetime-local (BĐSX/BĐCM/KT) */
    .col-time-value {
        min-width: 220px;
        max-width: 260px;
    }

    /* Cột "Sản Lượng Thực Tế": input số lượng + lịch sử xác nhận + tổng */
    .col-yield {
        min-width: 160px;
        max-width: 260px;
        word-break: break-word;
    }

</style>

<div class="content-wrapper">

    <div class="card">

        <div class="card-header mt-4">
            {{-- <h3 class="card-title">Ghi Chú Nếu Có</h3> --}}
        </div>
        <!-- /.card-Body -->
        <div class="card-body">

            {{-- Tính năng thử nghiệm: bật/tắt lưu theo phân xưởng ở bảng schedule_reroute_settings (dùng chung với
                 trang Thực Thi Sản Xuất), chỉ user được phép mới thấy và đổi được.
                 Tắt: xác nhận hoàn thành không làm thay đổi lịch lý thuyết.
                 Không dùng .custom-switch vì layout nạp Bootstrap 4.1.3 (chưa có class này). --}}
            @if (\App\Services\ScheduleRerouteService::canUse())
                <div class="reroute-toggle-box mb-3">
                    <label class="reroute-switch mb-0" for="realtimeRerouteToggle">
                        <input type="checkbox" id="realtimeRerouteToggle"
                            {{ \App\Services\RealtimeRerouteSwitch::enabled(session('user')['production_code'] ?? null) ? 'checked' : '' }}>
                        <span class="reroute-slider"></span>
                    </label>
                    <label for="realtimeRerouteToggle" class="mb-0 font-weight-bold" style="cursor:pointer">
                        Xác nhận và điều chỉnh lịch theo thời gian thực
                        <span class="badge badge-warning ml-1">Thử nghiệm</span>
                    </label>
                    <small id="realtimeRerouteHint" class="d-block w-100 text-muted mt-1"
                        data-production="{{ session('user')['production_code'] ?? '' }}"
                        data-last-change="{{ \App\Services\RealtimeRerouteSwitch::lastChange(\App\Services\RealtimeRerouteSwitch::get(session('user')['production_code'] ?? null)) }}">
                    </small>
                </div>
            @endif


            <form id="filterForm" method="GET" action="{{ route('pages.Schedual.finised.index') }}"
                class="d-flex flex-wrap gap-2">
                @csrf
                {{-- <div class="row w-100 align-items-center">
                        <!-- Stage Selector -->
                        <div class="col-md-4 d-flex justify-content-center align-items-center"
                            style="gap: 10px; height: 40px;">
                            <input type="hidden" name="stage_code" id="stage_code" value="{{ $stageCode }}">
                            <button type="button" id="prevStage" class="btn btn-link stage-btn"
                                style="font-size: 25px;">&laquo;</button>
                            <span id="stageName" class="fw-bold text-center" style="font-size: 25px;">
                                {{ optional($stages->firstWhere('stage_code', $stageCode))->stage ?? 'Không có công đoạn' }}
                            </span>
                            <button type="button" id="nextStage" class="btn btn-link stage-btn"
                                style="font-size: 25px;">&raquo;</button>
                        </div>
                    </div> --}}

                <div class="form-group" style="width: 177px">
                    <select class="form-control" name="stage_code" style="text-align-last: center;"
                        onchange="document.getElementById('filterForm').submit();">
                        <option {{ $stageCode == 1 ? 'selected' : '' }} value=1>Cân NL</option>
                        <option {{ $stageCode == 2 ? 'selected' : '' }} value=2>Cân NL Khác</option>
                        <option {{ $stageCode == 3 ? 'selected' : '' }} value=3>Pha Chế</option>
                        <option {{ $stageCode == 4 ? 'selected' : '' }} value=4>Trộn Hoàn Tất</option>
                        <option {{ $stageCode == 5 ? 'selected' : '' }} value=5>Định Hình</option>
                        <option {{ $stageCode == 6 ? 'selected' : '' }} value=6>Bao Phim</option>
                        <option {{ $stageCode == 7 ? 'selected' : '' }} value=7>Đóng Gói</option>
                    </select>
                </div>
            </form>


            <table id="data_table_Schedual_list" class="table table-bordered table-striped" style="font-size: 20px">
                <thead style = "position: sticky; top: 60px; background-color: white; z-index: 1020">
                    <tr>
                        <th>STT</th>
                        <th>Mã Sản Phẩm</th>
                        <th>Sản Phẩm</th>
                        <th>Số lô</th>
                        <th>Phòng Sản Xuất</th>
                        <th colspan="2">
                            Thời Gian Sản Xuất <br>
                            <span style="color: red; font-style:italic;">
                                BĐSX: là thời gian bắt đầu sản xuất
                            </span> <br>
                            <span style="color: red; font-style:italic;">
                                BĐCM: là thời gian bắt đầu tạo ra sản lượng
                            </span> <br>
                            <span style="color: red; font-style:italic;">
                                KT: là thời gian kết thúc 1 ca làm việc /ngày làm việc/kết lô
                            </span>
                        </th>


                        <th class = "text-center col-yield">Sản Lượng Thực Tế
                            @if ($stageCode <= 4)
                                {{ '(Kg)' }}
                            @else
                                {{ '(ĐVL)' }}
                            @endif
                            <br>
                            <span style="color: red; font-style:italic;">
                                Lưu ý: trường hợp báo sl nhiều lần thì SL sẽ được tính cho khoảng thời gian BĐCM - KT.
                                Không cộng dồn SL khi xác nhận nhiều lần
                            </span>
                        </th>
                        {{-- <th class = "text-center">Đã Xác Nhận</th> --}}
                        <th class = "text-center" style = "width: 3%">Số Thùng</th>
                        <th>Ghi Chú</th>
                        <th style = "width: 3%">Xác Nhận Sản Xuất</th>

                        <th colspan="2">Thời Gian Vệ Sinh</th>
                        <th style = "width: 3%">Xác Nhận Toàn Bộ </th>

                    </tr>
                </thead>
                <tbody>

                    @foreach ($datas as $data)
                        @if ($data->yields && $data->quarantine_room_code)
                            @php $finisedRow = true @endphp
                        @else
                            @php $finisedRow = false @endphp
                        @endif

                        @if ($data->actual_start || $data->actual_start_clearning)
                            @php $semi_finished = 'disabled' @endphp
                        @else
                            @php $semi_finished = '' @endphp
                        @endif

                        {{-- Chưa có lịch lý thuyết (chưa sắp lịch) thì khóa toàn bộ dòng, trừ Cân NL / Cân NL Khác (stage 1, 2) --}}
                        @php
                            $unscheduled = !in_array((int) $data->stage_code, [1, 2], true)
                                && (empty($data->start) || empty($data->resourceId));
                            $locked = $unscheduled ? 'disabled' : '';
                        @endphp


                        <tr data-id="{{ $data->id }}">
                            <td data-order="{{ $loop->iteration }}">{{ $loop->iteration }}
                                @if (session('user')['userGroup'] == 'Admin')
                                    <div> {{ $data->id }} </div>
                                @endif
                            </td>
                            <td>
                                <div> {{ $data->intermediate_code }} </div>
                                <div> {{ $data->finished_product_code }} </div>
                            </td>
                            <td>
                                @if ($data->need_confirm)
                                    {{-- Lô đã quá giờ kết thúc lý thuyết mà chưa xác nhận hoàn thành --}}
                                    <strong>{{ $data->product_name }}{{ $data->market ? ' - ' . $data->market : '' }}</strong>
                                @else
                                    {{ $data->product_name }} {{ $stageCode == 7 ? '- ' . $data->market : '' }}
                                @endif
                                <input type="hidden" name="title"
                                    value = "{{ $data->product_name . '-' . $data->batch }}">

                                @if ($unscheduled)
                                    <div>
                                        <span class="badge badge-danger" style="white-space: normal; text-align: left;">
                                            Không được xác nhận hoàn thành do chưa sắp lịch
                                        </span>
                                    </div>
                                @endif

                            </td>


                            <td>
                                @if (!$data->actual_start && $stageCode == 1)
                                    <input style="color: red" type="text" class="time actual_batch {{ $data->need_confirm ? 'font-weight-bold' : '' }}"
                                        id = "actual_batch" name="actual_batch" value = "{{ $data->batch }}" {{ $locked }}>
                                @else
                                    @if ($data->actual_batch)
                                        <div style="color: blue" class = "text-center {{ $data->need_confirm ? 'font-weight-bold' : '' }}"> {{ $data->batch }} </div>
                                    @else
                                        <div style="color: rgb(0, 0, 0)" class = "text-center {{ $data->need_confirm ? 'font-weight-bold' : '' }}"> {{ $data->batch }}
                                        </div>
                                    @endif
                                @endif

                                @if ($data->is_val)
                                    <i class="fas fa-check-circle text-primary fs-4"></i>
                                @endif
                            </td>


                            <td>
                                @if ($data->actual_start)
                                    <span>
                                        {{ $data->room_name . ' - ' . $data->room_code }}
                                    </span>
                                    <input type="hidden" name="resourceId" value="{{ $data->resourceId }}">
                                @else
                                    <select class="form-control" name="resourceId" id ="room_id" {{ $locked }}>
                                        <option value="">-- Phòng Sản Xuất --</option>
                                        @foreach ($room_stages as $room_stage)
                                            <option value="{{ $room_stage->id }}"
                                                {{ ($data->resourceId ?? null) == $room_stage->id ? 'selected' : '' }}>
                                                {{ $room_stage->code . ' - ' . $room_stage->name }}</option>
                                        @endforeach
                                    </select>
                                @endif
                            </td>


                            <td class="col-time-label">
                                <div>BĐSX: </div>
                                <div>BĐCM: </div>
                                <div>KT: </div>
                            </td>
                            {{-- {{ $semi_finished }} --}}
                            <td class="col-time-value">
                                @if (!empty($data->actual_start) || !empty($data->start))
                                    <input type="datetime-local" class="time start" id = "start" name="start"
                                        {{ $semi_finished ?: $locked }}
                                        value = "{{ \Carbon\Carbon::parse($data->actual_start ?? $data->start)->format('Y-m-d\TH:i') }}">

                                    <input type="datetime-local" class="time start_yield" id="start_yield"
                                        name="start_yield" {{ $locked }}
                                        value="{{ $data->max_yield_end ? \Carbon\Carbon::parse($data->max_yield_end)->format('Y-m-d\TH:i') : \Carbon\Carbon::parse($data->actual_start ?? $data->start)->format('Y-m-d\TH:i') }}">
                                    <input type="hidden" class="max_yield_end" value="{{ $data->max_yield_end ? \Carbon\Carbon::parse($data->max_yield_end)->format('Y-m-d\TH:i') : '' }}">

                                    <input type="datetime-local" class="time" id = "end" name="end" {{ $locked }}
                                        value = "{{ \Carbon\Carbon::parse($data->actual_end ?? $data->end)->format('Y-m-d\TH:i') }}">
                                @else
                                    <input type="datetime-local" class="time start" id = "start" name="start"
                                        {{ $semi_finished ?: $locked }}>

                                    <input type="datetime-local" class="time start_yield" id="start_yield"
                                        name="start_yield" {{ $locked }}
                                        value="{{ $data->max_yield_end ? \Carbon\Carbon::parse($data->max_yield_end)->format('Y-m-d\TH:i') : \Carbon\Carbon::parse($data->actual_start ?? $data->start)->format('Y-m-d\TH:i') }}">
                                    <input type="hidden" class="max_yield_end" value="{{ $data->max_yield_end ? \Carbon\Carbon::parse($data->max_yield_end)->format('Y-m-d\TH:i') : '' }}">

                                    <input type="datetime-local" class="time" id = "end" name="end" {{ $locked }}>
                                @endif

                            </td>
                            <td class="col-yield">

                                <input type="text" class="time" name="yields" {{ $locked }}
                                    data-max="{{ $data->Theoretical_yields * 1.1 }}"
                                    value="{{ ($data->yields ? $data->Theoretical_yields - $data->total_confirmed : $data->Theoretical_yields) < 0 ? 0 : $data->Theoretical_yields - $data->total_confirmed }}"
                                    oninput="
                                            this.value = this.value
                                                .replace(',', '.')
                                                .replace(/[^0-9.]/g, '')
                                                .replace(/(\..*)\./g, '$1');

                                            const max = parseFloat(this.dataset.max);
                                            const val = parseFloat(this.value);
                                            if (!isNaN(val) && val > max) this.value = max;
                                        ">
                                <br>
                                {!! $data->confirmed !!} <br>
                                {{ 'Tổng: ' . $data->total_confirmed }}
                            </td>

                            <td>
                                <input type="text" class="time" name="number_of_boxes" {{ $locked }}
                                    value="{{ $data->number_of_boxes ?? 1 }}"
                                    oninput="
                                        // Chỉ cho nhập số nguyên
                                        this.value = this.value.replace(/[^0-9]/g, '');
                                        let val = parseInt(this.value);
                                        // Nếu nhỏ hơn 1 thì xóa
                                        if (!isNaN(val) && val <= 0) {
                                            this.value = '';
                                            return;
                                        }
                                      
                                    ">
                            </td>

                            <td>
                                <textarea class="updateInput text-left" name="note" {{ $locked }}> {{ $data->note }} </textarea>
                            </td>


                            <td class="text-center align-middle">
                                {{-- @if ($semi_finished == 'disabled' || $data->actual_start_clearning)
                                    <button type="button" class="btn btn-success" disabled>
                                        ✓ Đã hoàn thành
                                    </button>  
                                @else  {{ $finisedRow ? 'disabled' : '' }} --}}
                                <button type="button" class="btn btn-success btn-semi-finised position-relative"
                                    {{ $locked }}
                                    data-id="{{ $data->id }}" data-toggle="modal" data-target="#finisedModal">
                                    <i class="fas fa-check"></i>
                                </button>
                                {{-- @endif --}}
                            </td>

                            <td>
                                <div>BĐ: </div>
                                <div>KT: </div>
                            </td>
                            <td>
                                @if (!empty($data->actual_start_clearning) || !empty($data->start_clearning))
                                    <input type="datetime-local" class="time" id = "start_clearning"
                                        name="start_clearning" {{ $locked }}
                                        value="{{ \Carbon\Carbon::parse($data->start_clearning)->format('Y-m-d\TH:i') }}">
                                    <input type="datetime-local" class="time" id = "end_clearning"
                                        name="end_clearning" {{ $locked }}
                                        value = "{{ \Carbon\Carbon::parse($data->end_clearning)->format('Y-m-d\TH:i') }}">
                                @else
                                    <input type="datetime-local" class="time" id = "start_clearning"
                                        name="start_clearning" {{ $locked }}>
                                    <input type="datetime-local" class="time" id = "end_clearning"
                                        name="end_clearning" {{ $locked }}>
                                @endif

                            </td>


                            <td class="text-center align-middle">
                                <button type="button" class="btn btn-success btn-finised position-relative"
                                    {{ $data->actual_start_clearning || $unscheduled ? 'disabled' : '' }}
                                    data-id="{{ $data->id }}" data-toggle="modal" data-target="#finisedModal">
                                    <i class="fas fa-check"></i><i class="fas fa-check"
                                        style="margin-left:-6px;"></i>
                                </button>
                            </td>

                        </tr>
                    @endforeach

                </tbody>
            </table>
        </div>
        <!-- /.card-body -->
    </div>
</div>


<script src="{{ asset('js/vendor/jquery-1.12.4.min.js') }}"></script>
<script src="{{ asset('js/popper.min.js') }}"></script>
<script src="{{ asset('js/bootstrap.min.js') }}"></script>
<script src="{{ asset('js/sweetalert2.all.min.js') }}"></script>

<script>
    $(document).ready(function() {
        document.body.style.overflowY = "auto";

        // Công tắc lưu DB theo phân xưởng: bật thì mọi ✓✓ của phân xưởng (ai bấm cũng vậy) đều dịch lịch
        function renderRerouteHint(on, lastChange) {
            const $hint = $('#realtimeRerouteHint');
            const production = $hint.data('production');
            $hint.toggleClass('text-muted', !on)
                .toggleClass('text-danger', on)
                .text((on
                    ? 'Đang bật cho ' + production + ': bấm ✓✓ (xác nhận toàn bộ) ở trang này hoặc Kết thúc vệ sinh ở trang Thực Thi Sản Xuất sẽ tự dịch các lô liên quan trên lịch lý thuyết theo giờ vệ sinh thực tế, bất kể ai thao tác. Bấm ✓ không dịch lịch.'
                    : 'Đang tắt cho ' + production + ': xác nhận hoàn thành không ảnh hưởng đến lịch lý thuyết.')
                    + (lastChange ? ' (Đổi lần cuối: ' + lastChange + ')' : ''));
        }
        renderRerouteHint($('#realtimeRerouteToggle').is(':checked'), $('#realtimeRerouteHint').data('last-change'));

        $('#realtimeRerouteToggle').on('change', function() {
            const toggle = this;
            const on = toggle.checked;
            toggle.disabled = true;
            $.ajax({
                url: "{{ route('pages.Schedual.execution.reroute_switch') }}",
                type: 'post',
                data: { _token: "{{ csrf_token() }}", enabled: on ? 1 : 0 },
                success: function(res) {
                    renderRerouteHint(res.enabled, res.last_change);
                },
                error: function(xhr) {
                    toggle.checked = !on;
                    Swal.fire({
                        icon: 'warning',
                        title: 'Không lưu được công tắc',
                        text: (xhr.responseJSON && xhr.responseJSON.message) || 'Có lỗi xảy ra'
                    });
                },
                complete: function() {
                    toggle.disabled = false;
                }
            });
        });


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

    });

    $(document).on('input change', '.start', function() {
        const row = $(this).closest('tr');
        const maxYieldEnd = row.find('.max_yield_end').val();

        // Nếu đã có xác nhận sản lượng trước đó (maxYieldEnd không rỗng), không được tự động ghi đè BĐCM
        if (!maxYieldEnd) {
            row.find('.start_yield').val($(this).val());
        }
    });
</script>

<script>
    // Kiểm tra trùng giờ (overlap) giữa các lô cùng công đoạn/phòng khi nhập thời gian kết thúc
    function checkTimeOverlap(row, inputEl) {
        const resourceInput = row.querySelector('[name="resourceId"]');
        const resourceId = resourceInput ? resourceInput.value : null;
        const start = row.querySelector('[name="' + inputEl.dataset.pairStart + '"]')?.value;
        const end = inputEl.value;
        const id = row.dataset.id;

        // reset trạng thái cảnh báo trước đó của ô này
        $(inputEl).css('border', '');

        if (!resourceId || !start || !end) return;

        $.ajax({
            url: "{{ route('pages.Schedual.finised.check_overlap') }}",
            type: 'POST',
            data: {
                _token: "{{ csrf_token() }}",
                id: id,
                resourceId: resourceId,
                start: start,
                end: end
            },
            success: function(res) {
                if (res && res.overlap) {
                    $(inputEl).css('border', '2px solid red');

                    const c = res.conflict || {};
                    const fmtTime = v => v ? String(v).replace('T', ' ').substring(0, 16) : '?';
                    const cStart = fmtTime(c.actual_start);
                    const cEnd = fmtTime(c.actual_end_clearning || c.actual_end);

                    Swal.fire({
                        icon: 'warning',
                        title: 'Trùng giờ sản xuất',
                        html: 'Khoảng thời gian vừa nhập đang <b>trùng giờ</b> với lô <b>' +
                            (c.title || '') + '</b> (' + cStart + ' &rarr; ' + cEnd +
                            ') trên cùng phòng sản xuất!<br><br>Vui lòng kiểm tra lại.',
                        confirmButtonText: 'Đã hiểu',
                        confirmButtonColor: '#d33'
                    });
                }
            }
        });
    }

    document.addEventListener('DOMContentLoaded', function() {
        document.body.addEventListener('change', function(e) {
            if (e.target.matches('input[name="end"]')) {
                e.target.dataset.pairStart = 'start';
                checkTimeOverlap(e.target.closest('tr'), e.target);
            }

            if (e.target.matches('input[name="end_clearning"]')) {
                e.target.dataset.pairStart = 'start_clearning';
                checkTimeOverlap(e.target.closest('tr'), e.target);
            }
        });
    });
</script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Gắn sự kiện bằng delegation để không bị mất sau khi search/reload


        $(document).on('click', '.btn-finised, .btn-semi-finised', function(e) {
            e.preventDefault();
            const stage_code = $('#stage_code').val();
            const btn = this; // nút vừa click
            const row = btn.closest('tr');
            const id = row.dataset.id;
            const now = new Date();
            let actionType = "";

            if (btn.classList.contains('btn-finised')) {
                actionType = "finised";

                //const resourceInput = row.querySelector('[name="resourceId"]');
                const resourceInput = row.querySelector('[name="resourceId"]');
                const resourceId = resourceInput ? resourceInput.value : null;


                if (!resourceId || resourceId == "") {
                    Swal.fire({
                        icon: "warning",
                        title: "Phòng Sản Xuất không hợp lệ",
                        html: "Chọn Phòng Sản Xuất!<br><br><b>Vui lòng kiểm tra lại!</b>",
                        confirmButtonText: 'Kiểm tra lại',
                        confirmButtonColor: '#3085d6'
                    });
                    return;
                }

                const startProdInput = row.querySelector('#start');
                const endProdInput = row.querySelector('#end');

                const startCleanInput = row.querySelector('#start_clearning');
                const endCleanInput = row.querySelector('#end_clearning');

                if (
                    !startProdInput.value ||
                    !endProdInput.value ||
                    !startCleanInput.value ||
                    !endCleanInput.value
                ) {

                    Swal.fire({
                        icon: "warning",
                        title: "Thời gian không hợp lệ",
                        html: "Thời Gian Không Được Để Trống!<br><br><b>Vui lòng kiểm tra lại!</b>",
                        confirmButtonText: 'Kiểm tra lại',
                        confirmButtonColor: '#3085d6'
                    });

                    return;
                }

                const startYieldInput = row.querySelector('#start_yield');
                const maxYieldEndStr = row.querySelector('.max_yield_end')?.value;
                if (maxYieldEndStr && startYieldInput && startYieldInput.value) {
                    if (new Date(startYieldInput.value) < new Date(maxYieldEndStr)) {
                        Swal.fire({
                            icon: "warning",
                            title: "Thời gian BĐCM không hợp lệ",
                            html: "Thời gian BĐCM (Bắt đầu tạo ra sản lượng) phải lớn hơn hoặc bằng thời gian Kết thúc của lần xác nhận trước đó (" + maxYieldEndStr.replace('T', ' ') + ")!<br><br><b>Vui lòng kiểm tra lại!</b>",
                            confirmButtonText: 'Kiểm tra lại',
                            confirmButtonColor: '#3085d6'
                        });
                        return;
                    }
                }
                const startProd = new Date(startProdInput.value);
                const endProd = new Date(endProdInput.value);
                const startClean = new Date(startCleanInput.value);
                const endClean = new Date(endCleanInput.value);

                // 1️⃣ SX: start < end
                if (startProd >= endProd) {
                    Swal.fire({
                        icon: "warning",
                        title: "Thời gian không hợp lệ",
                        html: "Thời gian bắt đầu sản xuất phải nhỏ hơn thời gian kết thúc!<br><br><b>Vui lòng kiểm tra lại!</b>",
                        confirmButtonText: 'Kiểm tra lại',
                        confirmButtonColor: '#3085d6'
                    });
                    return;
                }

                // 2️⃣ Cleaning: start < end
                if (startClean >= endClean) {
                    Swal.fire({
                        icon: "warning",
                        title: "Thời gian không hợp lệ",
                        html: "Thời gian bắt đầu vệ sinh phải nhỏ hơn thời gian kết thúc vệ sinh!<br><br><b>Vui lòng kiểm tra lại!</b>",
                        confirmButtonText: 'Kiểm tra lại',
                        confirmButtonColor: '#3085d6'
                    });
                    return;
                }

                // 3️⃣ Cleaning phải sau SX
                if (startClean < endProd) {
                    Swal.fire({
                        icon: "warning",
                        title: "Thời gian không hợp lệ",
                        html: "Thời gian bắt đầu vệ sinh phải lớn hơn hoặc bằng thời gian kết thúc sản xuất!<br><br><b>Vui lòng kiểm tra lại!</b>",
                        confirmButtonText: 'Kiểm tra lại',
                        confirmButtonColor: '#3085d6'
                    });
                    return;
                }




                const inputs = [
                    ...row.querySelectorAll('#start'),
                    ...row.querySelectorAll('#end'),
                    ...row.querySelectorAll('#start_clearning'),
                    ...row.querySelectorAll('#end_clearning')
                ];

                for (let input of inputs) {
                    if (input && input.value) {
                        let valTime = new Date(input.value);

                        if (valTime > now || input.value == "" || input.value == null) {
                            Swal.fire({
                                icon: "warning",
                                title: "Thời gian không hợp lệ",
                                html: "Không được nhập thời gian hoàn thành lớn hơn hiện tại!<br><br><b>Vui lòng kiểm tra lại!</b>",
                                confirmButtonText: 'Kiểm tra lại',
                                confirmButtonColor: '#3085d6'
                            });
                            return;
                        }
                    }
                }
            }

            if (btn.classList.contains('btn-semi-finised')) {

                actionType = "semi-finised";

                const resourceInput = row.querySelector('[name="resourceId"]');
                const resourceId = resourceInput ? resourceInput.value : null;

                if (!resourceId || resourceId == "") {
                    Swal.fire({
                        icon: "warning",
                        title: "Phòng Sản Xuất không hợp lệ",
                        html: "Chọn Phòng Sản Xuất!<br><br><b>Vui lòng kiểm tra lại!</b>",
                        confirmButtonText: 'Kiểm tra lại',
                        confirmButtonColor: '#3085d6'
                    });
                    return;
                }

                const startYieldInput = row.querySelector('#start_yield');
                const maxYieldEndStr = row.querySelector('.max_yield_end')?.value;
                if (maxYieldEndStr && startYieldInput && startYieldInput.value) {
                    if (new Date(startYieldInput.value) < new Date(maxYieldEndStr)) {
                        Swal.fire({
                            icon: "warning",
                            title: "Thời gian BĐCM không hợp lệ",
                            html: "Thời gian BĐCM (Bắt đầu tạo ra sản lượng) phải lớn hơn hoặc bằng thời gian Kết thúc của lần xác nhận trước đó (" + maxYieldEndStr.replace('T', ' ') + ")!<br><br><b>Vui lòng kiểm tra lại!</b>",
                            confirmButtonText: 'Kiểm tra lại',
                            confirmButtonColor: '#3085d6'
                        });
                        return;
                    }
                }
                const start = row.querySelector('#start').value;
                const end = row.querySelector('#end').value;

                if (start && end && start >= end) {
                    Swal.fire({
                        icon: "warning",
                        title: "Thời gian không hợp lệ",
                        html: "Thời gian bắt đầu phải nhỏ hơn thời gian kết thúc!<br><br><b>Vui lòng kiểm tra lại!</b>",
                        confirmButtonText: 'Kiểm tra lại',
                        confirmButtonColor: '#3085d6'
                    });
                    return;
                }

                const inputs = [start, end];

                for (let input of inputs) {

                    if (input && input.value == "") {
                        let valTime = new Date(input.value);

                        if (valTime > now || input.value == "" || input.value == null) {
                            Swal.fire({
                                icon: "warning",
                                title: "Thời gian không hợp lệ",
                                html: "Không được nhập thời gian lớn hơn hiện tại!<br><br><b>Vui lòng kiểm tra lại!</b>",
                                confirmButtonText: 'Kiểm tra lại',
                                confirmButtonColor: '#3085d6'
                            });
                            return;
                        }
                    }
                }
            }



            // Lấy dữ liệu input trong dòng đó
            const data = {};
            row.querySelectorAll('input, select, textarea').forEach(input => {

                data[input.name] = input.value;
            });
            data['id'] = id;

            // KIỂM TRA THỜI GIAN BẤT THƯỜNG
            let warnings = [];
            const msInDay = 1000 * 60 * 60 * 24;

            if (actionType === "finised") {
                const sProd = new Date(row.querySelector('#start').value);
                const eProd = new Date(row.querySelector('#end').value);
                const sClean = new Date(row.querySelector('#start_clearning').value);
                const eClean = new Date(row.querySelector('#end_clearning').value);

                if (!isNaN(sProd) && !isNaN(eProd) && ((eProd - sProd) / msInDay) >= 30) {
                    warnings.push("Thời gian sản xuất đang lớn hơn hoặc bằng 30 ngày.");
                }

                if (!isNaN(sClean) && !isNaN(eClean) && ((eClean - sClean) / msInDay) > 7) {
                    warnings.push("Thời gian vệ sinh đang lớn hơn 7 ngày.");
                }
            } else if (actionType === "semi-finised") {
                const sProd = new Date(row.querySelector('#start').value);
                const eProd = new Date(row.querySelector('#end').value);

                if (!isNaN(sProd) && !isNaN(eProd) && ((eProd - sProd) / msInDay) >= 30) {
                    warnings.push("Thời gian sản xuất đang lớn hơn hoặc bằng 30 ngày.");
                }
            }

            const executeSubmit = () => {
                // Disable tạm thời nút trong lúc gửi request
                btn.disabled = true;

                $.ajax({
                    url: "{{ route('pages.Schedual.finised.store') }}",
                    type: 'post',
                    data: {
                        ...data,
                        _token: "{{ csrf_token() }}",
                        actionType: actionType,
                        stage_code: stage_code
                    },
                    success: function(res) {

                        const rerouteCount = (res && res.reroute_count) ? res.reroute_count : 0;
                        const cleaningMoved = !!(res && res.reroute_cleaning_moved);

                        if (rerouteCount > 0 || cleaningMoved) {
                            const delta = res.reroute_delta || 0;
                            const direction = delta < 0 ? 'sớm' : 'trễ';
                            // ✓ (semi-finised): lô chưa xong vệ sinh, chỉ báo đang trễ
                            const lead = actionType === 'finised' ? 'Lô hoàn thành' : 'Lô đang';
                            Swal.fire({
                                icon: 'success',
                                title: 'Hoàn Thành',
                                html: `${lead} ${direction} <b>${Math.abs(delta)} phút</b> so với lịch lý thuyết.<br>` +
                                    (cleaningMoved ? `Đã dời vệ sinh của lô ra sau giờ kết thúc sản xuất.<br>` : '') +
                                    `Đã tịnh tuyến <b>${rerouteCount}</b> lô liên quan.<br>` +
                                    `<small>Xem chi tiết: chuột phải lên lô trên Lịch Sản Xuất → "Lịch sử tịnh tuyến".</small>`,
                                confirmButtonText: 'Đóng',
                            });
                        } else {
                            Swal.fire({
                                icon: 'success',
                                title: 'Hoàn Thành',
                                timer: 1500,
                                showConfirmButton: false,
                            });
                        }

                        // Giữ nút ở trạng thái disabled sau khi hoàn thành
                        $(btn).addClass('disabled').text('✓ Đã hoàn thành');

                        if (actionType === 'finised') {
                            $(row).find('.btn-finised')
                                .addClass('disabled')
                                .text('✓ Đã hoàn thành');

                            $(row).find('.btn-semi-finised')
                                .addClass('disabled')
                                .text('✓ Đã hoàn thành');

                        }

                        if (actionType === 'semi-finised') {
                            $(row).find('.start_yield').val('');
                        }

                    },
                    error: function(xhr) {
                        btn.disabled = false;

                        let message = 'Có lỗi xảy ra';

                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            message = xhr.responseJSON.message;
                        }

                        Swal.fire({
                            icon: 'warning',
                            title: 'Không thể hoàn thành',
                            text: message
                        });
                    }
                });
            };

            if (warnings.length > 0) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Cảnh báo thời gian',
                    html: warnings.join('<br>') + '<br><br><b>Có nhầm lẫn gì không? Bạn vẫn muốn tiếp tục xác nhận?</b>',
                    showCancelButton: true,
                    confirmButtonText: 'Vẫn tiếp tục',
                    cancelButtonText: 'Kiểm tra lại',
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                }).then((result) => {
                    if (result.isConfirmed) {
                        executeSubmit();
                    }
                });
            } else {
                executeSubmit();
            }
        });

    });
</script>

{{-- <script>
    let stages = @json($stages);
    let currentIndex = stages.findIndex(s => s.stage_code == {{ $stageCode ?? 'null' }});
   
    const filterForm = document.getElementById("filterForm");
    const stageNameEl = document.getElementById("stageName");
    const stageCodeEl = document.getElementById("stage_code");

    
    function updateStage() {
        stageNameEl.textContent = stages[currentIndex].stage;
        stageCodeEl.value = stages[currentIndex].stage_code;
    }
   
    document.getElementById("prevStage").addEventListener("click", function() {
        currentIndex = (currentIndex > 0) ? currentIndex - 1 : stages.length - 1;
        updateStage();
        filterForm.submit();
    });

    document.getElementById("nextStage").addEventListener("click", function() {
        currentIndex = (currentIndex < stages.length - 1) ? currentIndex + 1 : 0;
        updateStage();
        filterForm.submit();
    });
</script> --}}


{{-- <script>
    document.addEventListener('DOMContentLoaded', function() {
      
        document.querySelectorAll('.bs-stepper').forEach(stepperEl => {
            new Stepper(stepperEl, {
                linear: false,
                animation: true
            });
        });
    });
</script> --}}
