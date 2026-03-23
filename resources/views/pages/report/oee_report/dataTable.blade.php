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

    .time:focus {
        border: 1px solid #007bff;
        border-radius: 2px;
        background-color: #fff;
    }

    .stage-row-total {
        background-color: #f8f9fa;
        font-weight: bold;
    }
</style>

<div class="content-wrapper">
    <div class="card">
        @php
            $stage_name = [
                1 => 'Cân Nguyên Liệu',
                3 => 'Pha Chế',
                4 => 'Trộn Hoàn Tất',
                5 => 'Định Hình',
                6 => 'Bao Phim',
                7 => 'ĐGSC - ĐGTC',
            ];
        @endphp

        <div class="card-body mt-4">
            <div class="row mx-2 mb-3 mt-4">
                <form id="filterForm" method="GET" action="{{ route('pages.report.oee_report.index') }}" class="w-100">
                    <div class="row align-items-center">
                        <div class="col-md-2">
                            <label>Từ ngày:</label>
                            <input type="date" name="startDate" value="{{ $startDate }}" class="form-control"
                                onchange="this.form.submit()">
                        </div>
                        <div class="col-md-2">
                            <label>Đến ngày:</label>
                            <input type="date" name="endDate" value="{{ $endDate }}" class="form-control"
                                onchange="this.form.submit()">
                        </div>
                        <div class="col-md-6"> </div>
                        <div class="col-md-2">
                            <label>Tìm kiếm:</label>
                            <input type="text" id="customSearchInput" class="form-control"
                                placeholder="Nhập tên máy, mã phòng...">
                        </div>
                    </div>
                </form>
            </div>

            <div class="table-responsive">
                <table id="oee_table" class="table table-bordered table-striped" style="font-size: 16px">
                    <thead>
                        <tr>
                            <th rowspan="2" class="text-center">STT</th>
                            <th rowspan="2" class="text-center">Tên Máy</th>
                            <th rowspan="2" class="text-center">Công Suất (1)</th>
                            <th colspan="3" class="text-center">Thời Gian Làm Việc TT (2)</th>
                            <th rowspan="2" class="text-center">Sản lượng lý thuyết (3)</th>
                            <th rowspan="2" class="text-center">Sản lượng thực tế (4)</th>
                            <th rowspan="2" class="text-center">OEE %(5)</th>
                            <th colspan="3" class="text-center">Thời gian làm việc LT tối đa (6)</th>
                            <th rowspan="2" class="text-center">Loading % (7)</th>
                            <th rowspan="2" class="text-center">TEEP % (8)</th>
                        </tr>
                        <tr>
                            <th>Sản Xuất (a)</th>
                            <th>Vệ Sinh (b)</th>
                            <th>Tổng (c)</th>
                            <th>Ca (a)</th>
                            <th>Số ngày (b)</th>
                            <th>Tổng (c)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $current_Stage = null;
                            $stage_theory_total = 0;
                            $stage_actual_total = 0;
                        @endphp

                        @foreach ($datas as $index => $data)
                            @if ($current_Stage !== null && $current_Stage != $data->stage_code)
                                {{-- Summary row for previous stage --}}
                                <tr class="stage-row-total" data-stage="{{ $current_Stage }}">
                                    <td colspan="6" class="text-right">Tổng
                                        {{ $stage_name[$current_Stage] ?? 'Công đoạn ' . $current_Stage }}:</td>
                                    <td class="text-right">{{ number_format($stage_theory_total) }}</td>
                                    <td class="text-right">{{ number_format($stage_actual_total) }}</td>
                                    <td colspan="6"></td>
                                </tr>
                                @php
                                    $stage_theory_total = 0;
                                    $stage_actual_total = 0;
                                @endphp
                            @endif

                            @if ($current_Stage != $data->stage_code)
                                <tr style="background:#CDC717; color:#003A4F; font-weight:bold; cursor: pointer;"
                                    class="stage-header" data-stage="{{ $data->stage_code }}">
                                    <td colspan="14">
                                        <button type="button" class="btn btn-sm btn-info toggle-stage"
                                            style="width: 20px; height: 20px; padding: 0; line-height: 0; font-weight: bold; margin-right: 10px;"
                                            data-stage="{{ $data->stage_code }}">-</button>
                                        Công Đoạn {{ $stage_name[$data->stage_code] ?? $data->stage_code }}
                                    </td>
                                </tr>
                                @php $current_Stage = $data->stage_code; @endphp
                            @endif

                            <tr class="data-row" data-stage="{{ $data->stage_code }}">
                                <td>{{ $loop->iteration }}</td>
                                <td class="searchable">{{ $data->room_code . ' - ' . $data->room_name }}
                                    ({{ $data->main_equiment_name }})
                                </td>
                                <td>{{ number_format($data->capacity) }}</td>
                                <td>{{ number_format($data->work_hours, 2) }}</td>
                                <td>{{ number_format($data->cleaning_hours, 2) }}</td>
                                <td>{{ number_format($data->busy_hours, 2) }}</td>
                                <td class="text-right">{{ number_format($data->output_theory) }}</td>
                                <td class="text-right">{{ number_format($data->yield_actual) }}</td>
                                <td>{{ $data->OEE }}%</td>
                                <td>{{ (int) $data->shift }}</td>
                                <td>{{ $data->day_in_range }}</td>
                                <td>{{ number_format($data->H_total, 2) }}</td>
                                <td>{{ $data->loading }}%</td>
                                <td>{{ $data->TEEP }}%</td>
                            </tr>

                            @php
                                $stage_theory_total += $data->output_theory;
                                $stage_actual_total += $data->yield_actual;
                            @endphp

                            @if ($loop->last)
                                {{-- Final summary row --}}
                                <tr class="stage-row-total" data-stage="{{ $current_Stage }}">
                                    <td colspan="6" class="text-right">Tổng
                                        {{ $stage_name[$current_Stage] ?? 'Công đoạn ' . $current_Stage }}:</td>
                                    <td class="text-right">{{ number_format($stage_theory_total) }}</td>
                                    <td class="text-right">{{ number_format($stage_actual_total) }}</td>
                                    <td colspan="6"></td>
                                </tr>
                            @endif
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="{{ asset('js/vendor/jquery-1.12.4.min.js') }}"></script>
<script>
    $(document).ready(function() {
        document.body.style.overflowY = "auto";

        // Mặc định hiện tất cả các phòng khi load trang
        // (Không gọi .hide() ở đây)

        // Xử lý thu nhỏ/phóng to bằng cách click vào hàng tiêu đề công đoạn
        $(document).on("click", ".stage-header", function() {
            var stage = $(this).data("stage");
            var $btn = $(this).find(".toggle-stage");
            var isExpanding = $btn.text() === "+";

            $(".data-row[data-stage='" + stage + "'], .stage-row-total[data-stage='" + stage + "']").toggle(isExpanding);
            $btn.text(isExpanding ? "-" : "+");
        });

        // Xử lý tìm kiếm
        $("#customSearchInput").on("keyup", function() {
            var value = $(this).val().toLowerCase();

            if (value === "") {
                // Nếu xóa trắng ô tìm kiếm, quay về trạng thái dựa trên các nút +/- hiện thời
                $(".stage-header").each(function() {
                    var stage = $(this).data("stage");
                    var isExpanded = $(this).find(".toggle-stage").text() === "-";
                    $(".data-row[data-stage='" + stage + "'], .stage-row-total[data-stage='" + stage + "']").toggle(isExpanded);
                    $(this).show();
                });
                return;
            }
            
            // Lọc các dòng dữ liệu (data-row)
            $("#oee_table tbody .data-row").each(function() {
                var matches = $(this).text().toLowerCase().indexOf(value) > -1;
                $(this).toggle(matches);
            });

            // Sau khi lọc, hiện các tiêu đề công đoạn và dòng tổng nếu có dòng dữ liệu hiển thị
            $(".stage-header").each(function() {
                var stage = $(this).data("stage");
                var hasVisibleRows = $(".data-row[data-stage='" + stage + "']:visible").length > 0;
                $(this).toggle(hasVisibleRows);
                $(".stage-row-total[data-stage='" + stage + "']").toggle(hasVisibleRows);
                
                // Nếu có kết quả tìm kiếm, tự động đổi dấu thành "-" (đang mở)
                if (hasVisibleRows) {
                    $(this).find(".toggle-stage").text("-");
                }
            });
        });
    });
</script>
