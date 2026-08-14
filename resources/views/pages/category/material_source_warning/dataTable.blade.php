<link href="{{ asset('css/bootstrap.min.css') }}" rel="stylesheet">
<link href="{{ asset('assets/vendor/select2/select2.min.css') }}" rel="stylesheet">
<link href="{{ asset('assets/vendor/select2/select2-bootstrap4.min.css') }}" rel="stylesheet">
<style>
    /* Bootstrap 4.1.3 chưa có .modal-xl nên phải tự khai báo bề rộng cho 2 modal ma trận */
    #msw_create_modal .modal-dialog,
    #msw_update_modal .modal-dialog {
        max-width: 1300px;
        width: 95%;
        margin: 1.5rem auto;
    }

    /* Giữ tiêu đề và nút Lưu luôn nhìn thấy khi ma trận có nhiều công đoạn */
    #msw_create_modal .modal-body,
    #msw_update_modal .modal-body {
        max-height: calc(100vh - 200px);
        overflow-y: auto;
    }

    /* ----- Khối định danh của ma trận: Mã BTP - Mã NL - Thị trường ----- */
    .msw-key-section {
        background: #f8f9fa;
        border: 1px solid #e3e6ea;
        border-left: 4px solid #007bff;
        border-radius: .35rem;
        padding: 1rem 1rem .5rem;
        margin-bottom: 1.25rem;
    }

    .msw-label {
        display: block;
        margin-bottom: .35rem;
        font-size: 11.5px;
        font-weight: 700;
        letter-spacing: .04em;
        text-transform: uppercase;
        color: #495057;
    }

    .msw-label .fas {
        color: #007bff;
    }

    /* Chừa sẵn chỗ cho dòng chú thích để layout không nhảy khi đang tải công thức */
    .msw-bom-message {
        display: block;
        min-height: 16px;
        margin-top: .3rem;
        margin-bottom: 0;
        font-size: 11.5px;
        font-style: italic;
    }

    .msw-bom-revision {
        margin-left: .25rem;
        padding: 3px 7px;
        background: #17a2b8;
        color: #fff;
        font-size: 10.5px;
        font-weight: 600;
        letter-spacing: 0;
        text-transform: none;
        vertical-align: middle;
    }

    /* Select2 mặc định thấp hơn .form-control của Bootstrap -> canh lại cho đều hàng */
    .msw-key-section .select2-container--default .select2-selection--single {
        height: calc(2.25rem + 2px);
        border: 1px solid #ced4da;
        border-radius: .25rem;
        background: #fff;
        transition: border-color .15s ease-in-out, box-shadow .15s ease-in-out;
    }

    .msw-key-section .select2-container--default .select2-selection--single .select2-selection__rendered {
        line-height: calc(2.25rem + 2px);
        padding-left: .75rem;
        padding-right: 2rem;
        color: #495057;
    }

    .msw-key-section .select2-container--default .select2-selection--single .select2-selection__placeholder {
        color: #adb5bd;
    }

    .msw-key-section .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: calc(2.25rem + 2px);
        right: .5rem;
    }

    .msw-key-section .select2-container--default.select2-container--focus .select2-selection--single,
    .msw-key-section .select2-container--default.select2-container--open .select2-selection--single {
        border-color: #80bdff;
        box-shadow: 0 0 0 .2rem rgba(0, 123, 255, .25);
    }

    .select2-container--default .select2-results__option--highlighted[aria-selected] {
        background-color: #007bff;
    }

    .select2-dropdown {
        border-color: #80bdff;
    }

    /* Ô hiển thị cỡ lô: dùng .form-control cho đều hàng nhưng nội dung là text tĩnh */
    .msw-batch-box {
        height: calc(2.25rem + 2px);
        background: #f1f3f5;
        line-height: calc(1.25rem);
        overflow: hidden;
        white-space: nowrap;
    }

    /* 1 dòng khai 1 thị trường được phép: mã thị trường + kênh/khách hàng */
    .msw-market-row {
        display: flex;
        gap: .5rem;
        align-items: flex-start;
        margin-bottom: .5rem;
    }

    .msw-market-row .msw-market-select {
        flex: 1 1 60%;
        min-width: 0;
    }

    .msw-market-row .msw-market-channel {
        flex: 1 1 40%;
        min-width: 0;
    }

    .msw-market-badge {
        display: inline-block;
        margin: 1px 2px;
        padding: 1px 7px;
        border-radius: 8px;
        background: #eaf7ee;
        border: 1px solid #28a745;
        color: #1c7430;
        font-size: 11px;
        font-weight: 600;
        white-space: nowrap;
    }

    .msw-stage-line {
        white-space: nowrap;
    }

    .msw-stage-line+.msw-stage-line {
        margin-top: 3px;
    }

    .msw-stage-name {
        display: inline-block;
        min-width: 120px;
        font-weight: 600;
        color: #6c757d;
    }

    .msw-room-badge {
        display: inline-block;
        margin: 1px 2px;
        padding: 1px 7px;
        border-radius: 8px;
        background: #e7f1ff;
        border: 1px solid #007bff;
        color: #0056b3;
        font-size: 11px;
        font-weight: 600;
    }

    /* Nhắc nhở khai riêng cho từng thiết bị */
    .msw-room-line+.msw-room-line {
        margin-top: 2px;
    }

    .msw-room-reminder-box {
        padding: 2px 0 4px 24px;
    }

    .msw-room-reminder {
        height: calc(1.5rem + 2px);
        font-size: 11.5px;
    }

    /* Thiết bị có nhắc nhở: gộp chung 1 badge với mã thiết bị */
    .msw-room-badge-reminder {
        background: #fff4e5;
        border-color: #fd7e14;
        color: #a85400;
    }

    .msw-room-reminder-text {
        margin-left: 4px;
        padding-left: 5px;
        border-left: 1px solid #fd7e14;
        font-weight: 500;
    }

    .msw-status-pill {
        display: inline-block;
        padding: 3px 10px;
        border-radius: 8px;
        border: 1px solid #999;
        font-size: 11px;
        white-space: nowrap;
    }

    .msw-status-active {
        background: #28a745;
        color: #fff;
        border-color: #28a745;
    }

    .msw-status-inactive {
        background: #f1f1f1;
        color: #666;
    }
</style>

<div class="content-wrapper">
    <div class="card mt-5">
        <div class="card-header mt-4"></div>

        <!-- /.card-Body -->
        <div class="card-body">

            @php
                $auth_create = user_has_permission(
                    session('user')['userId'],
                    'material_source_warning_create',
                    'boolean',
                );
                $auth_update = user_has_permission(
                    session('user')['userId'],
                    'material_source_warning_update',
                    'disabled',
                );
                $auth_deActive = user_has_permission(
                    session('user')['userId'],
                    'material_source_warning_deActive',
                    'disabled',
                );
            @endphp

            @if ($auth_create)
                <button class="btn btn-success mb-2" data-toggle="modal" data-target="#msw_create_modal"
                    style="width: 175px">
                    <i class="fas fa-plus"></i> Thêm Ma Trận
                </button>
            @endif

            {{-- <div class="alert alert-secondary py-2">
                Mỗi dòng khai báo: 1 <b>sản phẩm</b> (mã BTP - đã gắn sẵn cỡ lô) khi sử dụng 1 <b>nguồn nguyên liệu</b>
                (mã NL theo công thức ấn bản mới nhất trên MMS) thì chỉ được sản xuất cho các <b>thị trường được phép</b>,
                trên các <b>thiết bị</b> đã liệt kê ở từng công đoạn.
                <br>
                Mã NL, thị trường và thiết bị đều có thể bỏ trống: <b>phần nào bỏ trống thì không cảnh báo phần đó</b>
                (bỏ trống mã NL = áp dụng cho mọi nguồn NL của mã BTP).
            </div> --}}

            @php
                // Cột thiết bị dựng động theo công đoạn đang có khai báo -> phải tính lại
                // chỉ số cột cho DataTables (các cột không cho sắp xếp).
                $stageCount = count($stageColumns);
                $marketColumn = 6 + $stageCount;
                $noSortColumns = array_merge(range(6, 5 + $stageCount), [
                    $marketColumn,
                    $marketColumn + 4,
                    $marketColumn + 5,
                ]);
            @endphp

            <table id="data_table_material_source_warning" class="table table-bordered table-striped">

                <thead style="position: sticky; top: 60px; background-color: white; z-index: 1020">
                    <tr>
                        <th style="width: 15px">STT</th>
                        <th>Mã BTP</th>
                        <th>Tên Sản Phẩm</th>
                        <th>Cỡ Lô</th>
                        <th>Mã NL</th>
                        <th>Nguồn Nguyên Liệu</th>
                        @foreach ($stageColumns as $stageName)
                            <th>{{ $stageName }}</th>
                        @endforeach
                        <th>Thị Trường Được Phép</th>
                        <th>Ghi Chú</th>
                        <th>Trạng Thái</th>
                        <th>Người Tạo/ Ngày Tạo</th>
                        <th>Cập Nhật</th>
                        <th>Vô Hiệu</th>
                    </tr>
                </thead>

                <tbody>
                    @foreach ($datas as $data)
                        @php
                            $stages = $roomsByWarning[$data->id] ?? [];
                            $rowMarkets = $marketsByWarning[$data->id] ?? [];
                            // Ma trận thiết bị dạng {mã công đoạn: [mã thiết bị,...]} để đổ lại vào modal cập nhật
                            $roomsJson = collect($stages)->map(fn($stage) => collect($stage['rooms'])->pluck('code'));
                            // Nhắc nhở dạng {mã công đoạn: {mã thiết bị: nội dung}}
                            $remindersJson = collect($stages)->map(
                                fn($stage) => collect($stage['rooms'])
                                    ->filter(fn($room) => ($room['reminder'] ?? '') !== '')
                                    ->pluck('reminder', 'code'),
                            );
                            $marketsJson = collect($rowMarkets)->map(
                                fn($market) => ['market_id' => $market['market_id'], 'channel' => $market['channel']],
                            );
                        @endphp

                        <tr>
                            <td class="text-center align-middle">{{ $loop->iteration }}</td>
                            <td class="align-middle text-success">{{ $data->intermediate_code }}</td>
                            <td class="align-middle">{{ $data->product_name }}</td>
                            <td class="align-middle text-right">
                                @if ($data->batch_qty)
                                    {{ number_format($data->batch_qty, 0, ',', '.') }}
                                    <span class="text-muted">{{ $data->unit_batch_qty }}</span>
                                @endif
                                @if ($data->batch_size)
                                    <br>
                                    <span class="text-muted">
                                        {{ rtrim(rtrim(number_format($data->batch_size, 2, ',', '.'), '0'), ',') }}
                                        {{ $data->unit_batch_size }}
                                    </span>
                                @endif
                            </td>
                            <td class="align-middle">
                                {{ $data->material_code ?: '' }}
                                @unless ($data->material_code)
                                    <span class="text-muted font-italic">Mọi nguồn NL</span>
                                @endunless
                            </td>
                            <td class="align-middle">{{ $data->material_name }}</td>

                            {{-- Mỗi công đoạn 1 cột thiết bị, đúng dạng ma trận đang dùng --}}
                            @foreach ($stageColumns as $stageCode => $stageName)
                                <td class="align-middle">
                                    @forelse ($stages[$stageCode]['rooms'] ?? [] as $room)
                                        @php
                                            $reminder = $room['reminder'] ?? '';
                                            $roomTitle = trim(
                                                ($room['name'] ?? '') . ' ' . ($room['main_equiment_name'] ?? ''),
                                            );
                                        @endphp
                                        <div>
                                            <span
                                                class="msw-room-badge {{ $reminder !== '' ? 'msw-room-badge-reminder' : '' }}"
                                                title="{{ $roomTitle }}{{ $reminder !== '' ? ' | Nhắc nhở khi xếp lịch: ' . $reminder : '' }}">
                                                {{ $room['code'] }}
                                                @if ($reminder !== '')
                                                    <span class="msw-room-reminder-text">
                                                        <i class="fas fa-bell"></i> {{ $reminder }}
                                                    </span>
                                                @endif
                                            </span>
                                        </div>
                                    @empty
                                        <span class="text-muted">-</span>
                                    @endforelse
                                </td>
                            @endforeach

                            <td class="align-middle">
                                @forelse ($rowMarkets as $market)
                                    <span class="msw-market-badge" title="{{ $market['name'] }}">
                                        {{ $market['code'] }}{{ $market['channel'] !== '' ? ' (' . $market['channel'] . ')' : '' }}
                                    </span>
                                @empty
                                    <span class="text-muted font-italic">Không giới hạn</span>
                                @endforelse
                            </td>
                            <td class="align-middle">{{ $data->note }}</td>
                            <td class="text-center align-middle">
                                <span
                                    class="msw-status-pill {{ $data->active ? 'msw-status-active' : 'msw-status-inactive' }}">
                                    {{ $data->active ? 'Hiệu lực' : 'Hết hiệu lực' }}
                                </span>
                            </td>
                            <td class="align-middle">
                                {{ $data->prepared_by }}<br>
                                <span class="text-muted">{{ $data->created_at }}</span>
                            </td>

                            <td class="text-center align-middle">
                                <button type="button" class="btn btn-warning btn-msw-edit"
                                    data-id="{{ $data->id }}"
                                    data-intermediate_code="{{ $data->intermediate_code }}"
                                    data-material_code="{{ $data->material_code }}"
                                    data-material_name="{{ $data->material_name }}"
                                    data-bom_revision="{{ $data->bom_revision }}" data-note="{{ $data->note }}"
                                    data-markets="{{ $marketsJson->toJson() }}"
                                    data-rooms="{{ $roomsJson->toJson() }}"
                                    data-room_reminders="{{ $remindersJson->toJson() }}" {{ $auth_update }}>
                                    <i class="fas fa-edit"></i>
                                </button>
                            </td>

                            <td class="text-center align-middle">
                                <form class="form-msw-deActive"
                                    action="{{ route('pages.category.material_source_warning.deActive') }}"
                                    method="post">
                                    @csrf
                                    <input type="hidden" name="id" value="{{ $data->id }}">
                                    <input type="hidden" name="active" value="{{ $data->active }}">

                                    @if ($data->active)
                                        <button type="submit" class="btn btn-danger"
                                            data-name="{{ $data->intermediate_code . ' - ' . $data->material_code }}"
                                            {{ $auth_deActive }}>
                                            <i class="fas fa-lock"></i>
                                        </button>
                                    @else
                                        <button type="submit" class="btn btn-success"
                                            data-name="{{ $data->intermediate_code . ' - ' . $data->material_code }}"
                                            {{ $auth_deActive }}>
                                            <i class="fas fa-unlock"></i>
                                        </button>
                                    @endif
                                </form>
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

{{-- jQuery chỉ được nạp ở cuối layout nên các trang danh sách phải tự nạp trước khi dùng --}}
<script src="{{ asset('js/vendor/jquery-1.12.4.min.js') }}"></script>
<script src="{{ asset('js/popper.min.js') }}"></script>
<script src="{{ asset('js/bootstrap.min.js') }}"></script>
<script src="{{ asset('js/sweetalert2.all.min.js') }}"></script>
<script src="{{ asset('assets/vendor/select2/select2.min.js') }}"></script>

@if (session('success'))
    <script>
        $(document).ready(function() {
            Swal.fire({
                title: 'Thành công!',
                text: @json(session('success')),
                icon: 'success',
                timer: 2000,
                showConfirmButton: false
            });
        });
    </script>
@endif

@if (session('error'))
    <script>
        $(document).ready(function() {
            Swal.fire('Lỗi', @json(session('error')), 'error');
        });
    </script>
@endif

<script>
    const MSW_INTERMEDIATE_DATA_URL = "{{ route('pages.category.material_source_warning.intermediateData') }}";

    // Danh mục thị trường dùng để dựng động các dòng "thị trường được phép" trong 2 modal
    const MSW_MARKETS = @json($markets->map(fn($market) => ['id' => $market->id, 'code' => $market->code, 'name' => $market->name]));

    /** Hiển thị cỡ lô của mã BTP đang chọn (chỉ để tham chiếu, không lưu vào ma trận) */
    function mswFillBatchInfo(formId, batch) {
        const $box = $('#' + formId + '_batch_info').empty();

        if (!batch) {
            $box.append($('<span class="text-muted">').text('Không có dữ liệu cỡ lô'));
            return;
        }

        const qty = batch.batch_qty ?
            Number(batch.batch_qty).toLocaleString('vi-VN') + ' ' + (batch.unit_batch_qty || '') :
            '';
        const size = batch.batch_size ?
            Number(batch.batch_size).toLocaleString('vi-VN') + ' ' + (batch.unit_batch_size || '') :
            '';

        if (!qty && !size) {
            $box.append($('<span class="text-muted">').text('Không có dữ liệu cỡ lô'));
            return;
        }

        $box.append($('<b>').text(qty || size));

        if (qty && size) {
            $box.append($('<span class="text-muted">').text(' / ' + size));
        }
    }

    /**
     * Thêm 1 dòng thị trường được phép vào form.
     * Tên field dạng markets[<số thứ tự>][market_id|channel] để gửi lên cùng 1 mảng.
     */
    function mswAddMarketRow(formId, marketId, channel) {
        const $box = $('#' + formId + '_market_rows');
        const index = $box.children('.msw-market-row').length;

        const $row = $('<div class="msw-market-row">');

        const $select = $('<select class="form-control msw-market-select">')
            .attr('name', 'markets[' + index + '][market_id]')
            .append('<option value=""> --- Chọn Thị Trường --- </option>');

        MSW_MARKETS.forEach(function(market) {
            $('<option>').val(market.id).text(market.code + ' - ' + market.name).appendTo($select);
        });

        if (marketId) {
            $select.val(String(marketId));
        }

        const $channel = $('<input type="text" class="form-control msw-market-channel" maxlength="50">')
            .attr('name', 'markets[' + index + '][channel]')
            .attr('placeholder', 'Kênh/khách hàng (vd: Tender)')
            .val(channel || '');

        $row.append($select)
            .append($channel)
            .append(
                $('<button type="button" class="btn btn-outline-danger btn-msw-remove-market" title="Xoá dòng">')
                .append('<i class="fas fa-times"></i>')
            )
            .appendTo($box);

        $select.select2({
            width: '100%',
            dropdownParent: $('#msw_' + formId + '_modal')
        });
    }

    /** Đánh số lại name của các dòng thị trường sau khi xoá để mảng gửi lên không bị hụt chỉ số */
    function mswReindexMarketRows(formId) {
        $('#' + formId + '_market_rows').children('.msw-market-row').each(function(index) {
            $(this).find('.msw-market-select').attr('name', 'markets[' + index + '][market_id]');
            $(this).find('.msw-market-channel').attr('name', 'markets[' + index + '][channel]');
        });
    }

    /**
     * Dựng lại toàn bộ danh sách thị trường được phép của 1 form.
     *
     * @param {Array} markets [{market_id, channel}] - rỗng thì để sẵn 1 dòng trống
     */
    function mswFillMarketRows(formId, markets) {
        const $box = $('#' + formId + '_market_rows');

        // select2 phải huỷ trước khi xoá DOM, nếu không sẽ để lại phần tử rác
        $box.find('.msw-market-select').each(function() {
            if ($(this).data('select2')) {
                $(this).select2('destroy');
            }
        });

        $box.empty();

        const rows = markets || [];

        if (rows.length === 0) {
            mswAddMarketRow(formId, '', '');
            return;
        }

        rows.forEach(function(market) {
            mswAddMarketRow(formId, market.market_id, market.channel);
        });
    }

    /** Đổ danh sách mã NL của công thức MMS vào ô chọn */
    function mswFillMaterials(formId, res, selectedCode) {
        const $select = $('#' + formId + '_material_code');
        const $message = $('#msw_' + formId + '_modal').find('.msw-bom-message');
        const $revision = $('#msw_' + formId + '_modal').find('.msw-bom-revision');

        $select.empty();
        $message.removeClass('text-danger').text(res.material_message || '');

        if (!res.material_success) {
            $select.append('<option value=""> --- Không có dữ liệu --- </option>');
            $message.addClass('text-danger');
            $revision.addClass('d-none').text('');
            $select.trigger('change.select2');
            return;
        }

        $revision.removeClass('d-none').text('Ấn bản BOM: ' + res.revision);
        $('#' + formId + '_bom_revision').val(res.revision);

        // Bỏ trống = ma trận áp dụng cho mọi nguồn NL của mã BTP
        $select.append('<option value=""> --- Mọi nguồn NL (không giới hạn) --- </option>');

        res.materials.forEach(function(item) {
            $('<option>')
                .val(item.mat_id)
                .attr('data-name', item.mat_name)
                .text(item.mat_id + ' - ' + item.mat_name)
                .appendTo($select);
        });

        // Mã NL đã khai báo có thể không còn trong ấn bản hiện tại -> vẫn phải giữ để không mất dữ liệu
        if (selectedCode && $select.find('option').filter(function() {
                return this.value === selectedCode;
            }).length === 0) {
            $('<option>')
                .val(selectedCode)
                .attr('data-name', $('#' + formId + '_material_name').val() || '')
                .text(selectedCode + ' (không còn trong ấn bản hiện tại)')
                .appendTo($select);
        }

        if (selectedCode) {
            $select.val(selectedCode);
        }

        $select.trigger('change.select2');
    }

    /**
     * Dựng ma trận thiết bị theo công đoạn - gồm toàn bộ thiết bị của phân xưởng.
     *
     * @param {Array}  stages          [{stage_code, stage_name, rooms:[{code,name,main_equiment_name}]}]
     * @param {Object} selectedRooms   {mã công đoạn: [mã thiết bị,...]} cần tick sẵn
     * @param {Object} selectedReminders {mã công đoạn: {mã thiết bị: nhắc nhở}} đã khai trước đó
     */
    function mswFillRoomMatrix(formId, stages, selectedRooms, selectedReminders) {
        const $box = $('#' + formId + '_room_matrix').empty();
        const checked = selectedRooms || {};
        const reminders = selectedReminders || {};

        if (!stages || stages.length === 0) {
            $box.append(
                '<div class="alert alert-warning mb-0">' +
                'Phân xưởng chưa khai báo thiết bị nào trong danh mục thiết bị.' +
                '</div>'
            );
            return;
        }

        const $row = $('<div class="row">').appendTo($box);

        stages.forEach(function(stage) {
            const selected = (checked[stage.stage_code] || []).map(String);

            const $card = $('<div class="col-xl-4 col-lg-6 mb-3">').appendTo($row);
            const $inner = $('<div class="card card-outline card-primary mb-0">').appendTo($card);

            $('<div class="card-header py-2">')
                .append($('<h3 class="card-title" style="font-size:14px;font-weight:600">').text(stage
                    .stage_name))
                .append(
                    $('<div class="card-tools">').append(
                        $('<button type="button" class="btn btn-xs btn-outline-primary btn-toggle-stage">')
                        .attr('data-form', formId)
                        .attr('data-stage', stage.stage_code)
                        .text('Chọn tất cả')
                    )
                )
                .appendTo($inner);

            const $body = $('<div class="card-body py-2" style="max-height:260px;overflow-y:auto">').appendTo(
                $inner);

            stage.rooms.forEach(function(room, index) {
                const id = formId + '_room_' + stage.stage_code + '_' + index;
                const isChecked = selected.indexOf(room.code) !== -1;

                const $label = $('<label class="custom-control-label">').attr('for', id);
                $label.append($('<b>').text(room.code)).append(document.createTextNode(' - ' + room
                    .name));

                if (room.main_equiment_name) {
                    $label.append($('<span class="text-muted">').text(' (' + room.main_equiment_name +
                        ')'));
                }

                const $line = $('<div class="msw-room-line">').appendTo($body);

                $('<div class="custom-control custom-checkbox">')
                    .append(
                        $('<input type="checkbox" class="custom-control-input msw-room">')
                        .addClass('msw-room-' + formId)
                        .attr('id', id)
                        .attr('name', 'rooms[' + stage.stage_code + '][]')
                        .attr('data-stage', stage.stage_code)
                        .val(room.code)
                        .prop('checked', isChecked)
                    )
                    .append($label)
                    .appendTo($line);

                // Nhắc nhở riêng của thiết bị - chỉ hiện khi thiết bị được chọn
                $('<div class="msw-room-reminder-box">')
                    .toggle(isChecked)
                    .append(
                        $('<input type="text" class="form-control form-control-sm msw-room-reminder">')
                        .attr('name', 'room_reminders[' + stage.stage_code + '][' + room.code + ']')
                        .attr('maxlength', 255)
                        .attr('placeholder', 'Nhắc nhở khi xếp đúng thiết bị này (vd: Khử ẩm)')
                        .val((reminders[stage.stage_code] || {})[room.code] || '')
                    )
                    .appendTo($line);
            });
        });
    }

    /**
     * Nạp dữ liệu theo mã BTP: danh sách mã NL từ MMS + ma trận thiết bị đã định mức.
     *
     * @param {string} formId        'create' hoặc 'update'
     * @param {string} code          Mã BTP
     * @param {string} selectedCode  Mã NL cần chọn sẵn sau khi nạp xong (nếu có)
     * @param {Object} selectedRooms Thiết bị cần tick sẵn (nếu có)
     * @param {Object} selectedReminders Nhắc nhở đã khai của từng thiết bị (nếu có)
     */
    function mswLoadIntermediateData(formId, code, selectedCode, selectedRooms, selectedReminders) {
        const $select = $('#' + formId + '_material_code');
        const $message = $('#msw_' + formId + '_modal').find('.msw-bom-message');
        const $revision = $('#msw_' + formId + '_modal').find('.msw-bom-revision');

        $message.removeClass('text-danger').text('');
        $revision.addClass('d-none').text('');
        $select.empty();

        if (!code) {
            $select.append('<option value=""> --- Chọn Mã BTP trước --- </option>').trigger('change.select2');
            $('#' + formId + '_room_matrix').html(
                '<div class="alert alert-secondary mb-0">Chọn mã BTP để hiện danh sách thiết bị đã định mức.</div>'
            );
            $('#' + formId + '_batch_info').html('<span class="text-muted">Chọn mã BTP</span>');
            return;
        }

        $select.append('<option value=""> Đang lấy công thức từ MMS... </option>').trigger('change.select2');
        $('#' + formId + '_room_matrix').html(
            '<div class="alert alert-secondary mb-0">Đang tải thiết bị đã định mức...</div>');

        $.ajax({
            url: MSW_INTERMEDIATE_DATA_URL,
            type: 'GET',
            data: {
                intermediate_code: code
            },
            success: function(res) {
                mswFillMaterials(formId, res, selectedCode);
                mswFillRoomMatrix(formId, res.stages, selectedRooms, selectedReminders);
                mswFillBatchInfo(formId, res.batch);
            },
            error: function() {
                $select.empty().append('<option value=""> --- Không có dữ liệu --- </option>').trigger(
                    'change.select2');
                $message.addClass('text-danger').text('Không lấy được dữ liệu của mã BTP này.');
                $('#' + formId + '_room_matrix').html(
                    '<div class="alert alert-danger mb-0">Không lấy được danh sách thiết bị đã định mức.</div>'
                );
                $('#' + formId + '_batch_info').html(
                    '<span class="text-danger">Không lấy được cỡ lô</span>');
            }
        });
    }

    $(document).ready(function() {

        document.body.style.overflowY = "auto";

        $('#data_table_material_source_warning').DataTable({
            paging: true,
            lengthChange: true,
            searching: true,
            ordering: true,
            info: true,
            autoWidth: false,
            pageLength: 25,
            lengthMenu: [
                [10, 25, 50, 100, -1],
                [10, 25, 50, 100, "Tất cả"]
            ],
            columnDefs: [{
                orderable: false,
                targets: @json($noSortColumns)
            }],
            language: {
                search: "Tìm kiếm:",
                lengthMenu: "Hiển thị _MENU_ dòng",
                info: "Hiển thị _START_ đến _END_ của _TOTAL_ dòng",
                zeroRecords: "Không tìm thấy ma trận phù hợp",
                emptyTable: "Chưa khai báo ma trận cảnh báo nguồn NL nào",
                paginate: {
                    previous: "Trước",
                    next: "Sau"
                }
            }
        });

        // Select2 cần data-parent để hiển thị đúng bên trong modal của Bootstrap
        $('.msw-select2').each(function() {
            $(this).select2({
                width: '100%',
                dropdownParent: $($(this).data('parent'))
            });
        });

        // Đổi mã BTP thì nạp lại mã NL theo công thức MMS và thiết bị theo định mức của mã đó
        $('#create_intermediate_code, #update_intermediate_code').on('change', function() {
            const formId = this.id.startsWith('create') ? 'create' : 'update';
            $('#' + formId + '_material_name').val('');
            $('#' + formId + '_bom_revision').val('');
            mswLoadIntermediateData(formId, $(this).val(), '', {});
        });

        // Lưu tên NL kèm theo mã để còn hiển thị được khi MMS mất kết nối
        $('#create_material_code, #update_material_code').on('change', function() {
            const formId = this.id.startsWith('create') ? 'create' : 'update';
            $('#' + formId + '_material_name').val($(this).find('option:selected').data('name') || '');
        });

        // Chọn / bỏ chọn toàn bộ thiết bị của 1 công đoạn (ma trận dựng động nên phải uỷ quyền sự kiện)
        $(document).on('click', '.btn-toggle-stage', function() {
            const $boxes = $('.msw-room-' + $(this).data('form') +
                '[data-stage="' + $(this).data('stage') + '"]');
            const checkAll = $boxes.filter(':checked').length !== $boxes.length;

            $boxes.prop('checked', checkAll).trigger('change');
            $(this).text(checkAll ? 'Bỏ chọn tất cả' : 'Chọn tất cả');
        });

        // Ô nhắc nhở chỉ hiện khi thiết bị được chọn; bỏ chọn thì xoá luôn nội dung đã nhập
        $(document).on('change', '.msw-room', function() {
            const $box = $(this).closest('.msw-room-line').find('.msw-room-reminder-box');

            if (this.checked) {
                $box.show();
                return;
            }

            $box.hide().find('.msw-room-reminder').val('');
        });

        // Thêm / bớt dòng thị trường được phép (các dòng dựng động nên phải uỷ quyền sự kiện)
        $(document).on('click', '.btn-msw-add-market', function() {
            mswAddMarketRow($(this).data('form'), '', '');
        });

        $(document).on('click', '.btn-msw-remove-market', function() {
            const $row = $(this).closest('.msw-market-row');
            const $box = $row.parent();
            const formId = $box.attr('id').replace('_market_rows', '');

            $row.find('.msw-market-select').each(function() {
                if ($(this).data('select2')) {
                    $(this).select2('destroy');
                }
            });

            $row.remove();

            // Luôn chừa lại 1 dòng trống để người dùng khỏi phải bấm thêm
            if ($box.children('.msw-market-row').length === 0) {
                mswAddMarketRow(formId, '', '');
                return;
            }

            mswReindexMarketRows(formId);
        });

        // Mở modal thêm mới: dựng sẵn 1 dòng thị trường trống
        $('#msw_create_modal').on('show.bs.modal', function() {
            if ($('#create_market_rows').children('.msw-market-row').length === 0) {
                mswAddMarketRow('create', '', '');
            }
        });

        // Mở modal cập nhật và đổ lại ma trận đã khai báo
        $(document).on('click', '.btn-msw-edit', function() {
            const button = $(this);

            $('#update_id').val(button.data('id'));
            $('#update_note').val(button.data('note') || '');
            $('#update_material_name').val(button.data('material_name') || '');
            $('#update_bom_revision').val(button.data('bom_revision') || '');

            $('#update_intermediate_code').val(button.data('intermediate_code')).trigger(
                'change.select2');

            mswFillMarketRows('update', button.data('markets') || []);

            mswLoadIntermediateData(
                'update',
                button.data('intermediate_code'),
                button.data('material_code'),
                button.data('rooms') || {},
                button.data('room_reminders') || {}
            );

            $('#msw_update_modal').modal('show');
        });

        // Xác nhận trước khi đổi trạng thái hiệu lực
        $('.form-msw-deActive').on('submit', function(e) {
            e.preventDefault();

            const form = this;
            const isActive = $(form).find('input[name="active"]').val() == 1;
            const name = $(form).find('button[type="submit"]').data('name');

            Swal.fire({
                title: isActive ? 'Vô hiệu ma trận?' : 'Cho ma trận hiệu lực lại?',
                text: name,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Đồng ý',
                cancelButtonText: 'Huỷ'
            }).then(function(result) {
                if (result.isConfirmed) {
                    form.submit();
                }
            });
        });
    });
</script>
