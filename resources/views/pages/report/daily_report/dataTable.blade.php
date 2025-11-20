<div class="content-wrapper">
    <!-- Main content -->
          <div class="card">
              <div class="card-header mt-4"></div>
           
              @php
                 $stage_name = [
                      1 => "Cân Nguyên Liệu",
                      3 => "Pha Chế",
                      4 => "Trộn Hoàn Tất",
                      5 => "Định Hình",
                      6 => "Bao Phim",
                      7 => "ĐGSC - ĐGTC",
                  ]
              @endphp 
              <!-- /.card-Body -->
              <div class="card-body">
                 <!-- Tiêu đề -->
                <div class ="row mx-2">
                    <div class ="col-md-3">
                        <form id="filterForm" method="GET" action="{{ route('pages.report.daily_report.index') }}" class="d-flex flex-wrap gap-0">
                            @csrf
                            <div class="row w-100 align-items-center">
                                <!-- Filter From/To -->
                                <div class="col-md-4 d-flex gap-2">
                                    @php
                                        use Carbon\Carbon;
                                        $defaultFrom = $reportedDate
                                            ? Carbon::createFromFormat('!d/m/Y', trim($reportedDate))->format('Y-m-d')
                                            : Carbon::now()->format('Y-m-d');
                                    @endphp
                                    <div class="form-group d-flex align-items-center">
                                        <label for="reportedDate" class="mr-2 mb-0">Chọn Ngày:</label>
                                        <input type="date" id="reportedDate" name="reportedDate" value="{{ $defaultFrom }}" class="form-control"  max="{{ \Carbon\Carbon::yesterday()->format('Y-m-d') }}" />
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="col-md-6 text-center" style="font-size: 20px;color: #CDC717;">
                        <div>
                        Báo cáo được tính từ 06:00 ngày {{ Carbon::parse($defaultFrom)->subDays(1)->format('d/m/Y') }}
                        đến 06:00 ngày {{ Carbon::parse($defaultFrom)->format('d/m/Y') }}
                        </div>
                    </div>
                    <div class ="col-md-3">
                    </div>
                </div>


                <!-- Sản Lượng thực tế-->
                <div class="card card-primary mb-4">
              
                    <table id="data_table_yield" class="table table-bordered table-striped" style="font-size: 15px;">
                        <thead style="position: sticky; top: 60px; background-color: white; z-index: 1020;">
                            <tr style="background-color: #CDC717; color:#003A4F; font-size: 20px; font-weight: bold;">
                                <th class="text-center" style="min-width: 200px;">Phòng SX</th>
                                <th class="text-center">ĐV</th>

                                @foreach ($theory['yield_day'] as $date => $dayData)
                                    <th class="text-center">{{ \Carbon\Carbon::parse($date)->format('d/m/y') }}</th>
                                @endforeach

                                <th class="text-center">Tổng</th>
                                <th class="text-center">ĐV</th>
                            </tr>
                        </thead>

                        <tbody style="font-size: 20px;">

                            @foreach ($theory['yield_room'] as $index => $roomLT)
                                @php
                                    $resourceId = $roomLT->resourceId;
                                    $unit = $roomLT->unit;

                                    $roomTT = $actual['yield_room']->firstWhere('resourceId', $resourceId);
                                
                                    $allDates = $theory['yield_day']->keys()   // lấy tất cả key từ collection
                                    ->merge($actual['yield_day']->keys())  // merge với actual
                                    ->unique()
                                    ->sort();
                                @endphp

                                {{-- ------------------- LÝ THUYẾT ------------------- --}}
                                <tr >
                                    <td class="text-center align-middle" rowspan="2">{{ $roomLT->room_code . ' - ' . $roomLT->room_name }}</td>
                                    <td class="text-center align-middle" rowspan="2">{{ $unit }}</td>

                                    @php $sumLT = 0; @endphp
                                    @foreach ($allDates as $date)
                                        @php
                                            $dayLT = $theory['yield_day'][$date] ?? collect();
                                            $item = $dayLT->firstWhere('resourceId', $resourceId);
                                            $qty = $item['total_qty'] ?? 0;
                                            $sumLT += $qty;
                                        @endphp
                                        <td class="text-end" style="background:#93f486;" >{{ number_format($qty, 2) }}</td>
                                    @endforeach

                                    <td class="text-end fw-bold" style="background:#93f486;">{{ number_format($sumLT, 2) }}</td>
                                    <td class="text-center" style="background:#93f486;">{{ $unit }}</td>
                                </tr>

                                {{-- ------------------- THỰC TẾ ------------------- --}}
                                <tr >
                                    @php $sumTT = 0; @endphp
                                    @foreach ($allDates as $date)
                                        @php
                                            $dayTT = $actual['yield_day'][$date] ?? collect();
                                            $itemTT = $dayTT->firstWhere('resourceId', $resourceId);
                                            $qtyTT = $itemTT['total_qty'] ?? 0;
                                            $sumTT += $qtyTT;
                                        @endphp
                                        <td class="text-end" style="color:#003A4F; background:#69b8f4;">
                                            {{ number_format($qtyTT, 2) }}
                                        </td>
                                    @endforeach

                                    <td class="text-end fw-bold" style="background:#69b8f4;">{{ number_format($sumTT, 2) }}</td>
                                    <td class="text-center" style="background:#69b8f4;">{{ $unit }}</td>
                                </tr>

                                {{-- ------------- TỔNG THEO CÔNG ĐOẠN (LT + TT) --------------- --}}
                                @php
                                    $nextItem = $theory['yield_room'][$index + 1] ?? null;
                                    $nextStage = $nextItem->stage_code ?? null;
                                @endphp



                                @if ($nextStage != $roomLT->stage_code)
                                    @php
                                        $stage_code = $roomLT->stage_code;

                                        // Lấy allDates giống hàng chi tiết
                                        $allDates = $theory['yield_day']->keys()
                                            ->merge($actual['yield_day']->keys())
                                            ->unique()
                                            ->sort();

                                        // Tính tổng LT/TT theo công đoạn
                                        $stageLT = [];
                                        $stageTT = [];

                                        foreach ($allDates as $date) {
                                            $dayLT = $theory['yield_day'][$date] ?? collect();
                                            $stageLT[$date] = $dayLT->where('stage_code', $stage_code)->sum('total_qty');

                                            $dayTT = $actual['yield_day'][$date] ?? collect();
                                            $stageTT[$date] = $dayTT->where('stage_code', $stage_code)->sum('total_qty');
                                        }
                                    @endphp

                                    {{-- ⭐ Tổng LT --}}
                                    <tr style="background:#CDC717; color:#003A4F; font-weight:bold;">
                                        <td class="text-center align-middle" rowspan="2">{{ 'Công Đoạn ' . ($stage_name[$stage_code] ?? $stage_code) }}</td>
                                        <td class="text-center align-middle" rowspan="2">{{ $unit }}</td>

                                        @foreach ($allDates as $date)
                                            <td class="text-end" >{{ number_format($stageLT[$date], 2) }}</td>
                                        @endforeach

                                        <td class="text-end" >{{ number_format(array_sum($stageLT), 2) }}</td>
                                        <td class="text-center">{{ $unit }}</td>
                                    </tr>

                                    {{-- ⭐ Tổng TT --}}
                                    <tr style="background:#CDC717; color:#003A4F; font-weight:bold;">
                                        @foreach ($allDates as $date)
                                            <td class="text-end" >{{ number_format($stageTT[$date], 2) }}</td>
                                        @endforeach

                                        <td class="text-end" >{{ number_format(array_sum($stageTT), 2) }}</td>
                                        <td class="text-center">{{ $unit }}</td>
                                    </tr>
                                @endif
                            @endforeach
                        </tbody>
                    </table>

                </div>

                    

                <!-- Sản Lượng thực tế phòng sx tiêp theo -->
                <div class="card card-primary mb-4">
                        <div class="card-header border-transparent">
                            <h3 class="card-title">
                               {{"Tồn Kho Phân Bổ Theo Phòng Sản Xuất Ở Công Đoạn Tiếp Theo (Tính đến 06:00 ngày báo cáo)"}}
                            </h3>
                            <div class="card-tools">
                                <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                    <i class="fas fa-minus"></i>
                                </button>
                                <button type="button" class="btn btn-tool" data-card-widget="remove">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>

                    <div class="card-body">
                        <table id="data_table_instrument" class="table table-bordered table-striped">
                            <thead style="position: sticky; top: 60px; background-color: white; z-index: 1020;">
                                <tr>
                                    <th>STT</th>
                                    <th>Tên Phòng - Thiết Bị Chính</th>
                                    <th>Công Đoạn Tiếp Theo</th>
                                    <th>Tổ Quản Lý</th>
                                    <th>Tồn Thực Tế Công Đoạn trước</th>
                                    <th class ="text-center">Chi Tiết</th>
                                </tr>
                            </thead>
                            <tbody>
                                    @foreach ($sum_by_next_room as $key_room => $data)
                                        <tr>
                                            <td>{{ $loop->iteration }}</td>
                                            <td>{{ $data->next_room }}</td>
                                            <td>{{ $data->stage }}</td>
                                            <td>{{ $data->production_group }}</td>
                                            <td>{{ $data->sum_yields }} {{$data->stage_code<=5?"Kg":"ĐVL"}}  </td>

                                           <td class="text-center align-middle">
                                                <button type="button" class="btn btn-primary btn-detial"
                                                    data-room_id ="{{ $data->room_id }}" data-toggle="modal" data-target="#detailModal">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
              </div>
            </div>
</div>
<script src="{{ asset('js/vendor/jquery-1.12.4.min.js') }}"></script>
<script src="{{ asset('js/popper.min.js') }}"></script>
<script src="{{ asset('js/bootstrap.min.js') }}"></script>


<script>
    $(document).ready(function () {
        document.body.style.overflowY = "auto";

        const startDate = document.getElementById('reportedDate');
        const form = document.getElementById('filterForm');
        startDate.addEventListener('input', function () {
            form.submit();
        });
        const stageNameMap = @json($stage_name);

        $('.btn-detial').on('click', function() {
   
            const room_id = $(this).data('room_id');
        
            const history_modal = $('#data_table_detail_body')

                    // Xóa dữ liệu cũ
                    history_modal.empty();

                    // Gọi Ajax lấy dữ liệu history
                    $.ajax({
                        url: "{{ route('pages.report.daily_report.detail') }}",
                        type: 'post',
                        data: {
                            reportedDate: startDate.value,
                            room_id: room_id,
                            _token: "{{ csrf_token() }}"
                        },
                        success: function(res) {
                            if (res.length === 0) {
                                history_modal.append(
                                    `<tr><td colspan="8" class="text-center">Không có dữ liệu</td></tr>`
                                );
                            } else {
                                res.forEach((item, index) => {
                                // map màu level
                                    
                                history_modal.append(`
                                <tr>
                                    <td>${index + 1}</td>

                                    <td> 
                                        <div>${item.intermediate_code ?? ''}</div>
                                        <div>${item.finished_product_code ?? ''}</div>
                                    </td>

                                    <td>${item.product_name ?? ''} </td>
                                    <td>${item.batch ?? ''}</td>
                                    <td>${(item.pre_room ?? '') }</td>
                                    <td>${(item.yields ?? '') + (item.stage_code <= 4 ? " Kg" : " ĐVL")}</td>
                                    <td>${stageNameMap[item.next_stage] ?? ''}</td>
                                    
                                    <td>${moment(item.next_start).format('hh:mm DD/MM/YYYY') ?? ''}</td>
                                    <td>${item.quarantine_room_code ?? ''}</td>
                                </tr>
                            `);});
                            }
                        },
                        error: function() {
                            history_modal.append(
                                `<tr><td colspan="8" class="text-center text-danger">Lỗi tải dữ liệu</td></tr>`
                            );
                        }
                    });
        });


    });
</script>


