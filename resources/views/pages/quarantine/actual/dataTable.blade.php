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
                <!-- Sản Lượng thực tế đang lưu ở từng phòng biệt trữ -->
                @foreach (collect($datas)->sortKeys() as $quarantine_room_code => $details)
                    <div class="card card-success mb-4">
                        <div class="card-header border-transparent">
                            <h3 class="card-title">
                                {{ $quarantine_room_code }}
                                - {{ $details['room_name'] }}:
                                Tổng Lượng Biệt Trữ {{ number_format($details['total_yields'], 0) }}
                                (Thùng)
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
                                        <th>Mã Sản Phẩm</th>
                                        <th>Tên Thiết Bị</th>
                                        <th>Số Lô</th>
                                        <th>Sản Lượng Thực Tế</th>
                                        <th>Công Đoạn Tiếp Theo</th>
                                        <th>Thời Gian SX Dự Kiến</th>
                                        <th>Người/Ngày Xác Nhận</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($details['details'] as $data)
                                        <tr>
                                            <td>{{ $loop->iteration }}</td>
                                            <td>
                                                {{ $data->stage_code <= 4 ? $data->intermediate_code : $data->finished_product_code }}
                                            </td>
                                            <td>{{ $data->product_name }}</td>
                                            <td>{{ $data->batch }}</td>
                                            <td>
                                                {{ number_format($data->yields, 2) }} 
                                                {{ $data->stage_code <= 4 ? '(Kg)' : '(ĐVL)' }} # {{$data->number_of_boxes ." (Thùng)" }}
                                            </td>
                                            <td>{{ $stage_name[$data->next_stage] ?? '' }}</td>
                                            <td>
                                                {{ $data->next_start? \Carbon\Carbon::parse($data->next_start)->format('d/m/Y H:i') :"Chưa có lịch sản xuất tiếp theo" }}
                                            </td>
                                             <td>
                                                {{$data->finished_by ." - ". \Carbon\Carbon::parse($data->finished_date)->format('d/m/Y H:i') }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody> 
                            </table>
                        </div>
                    </div>
                @endforeach

                <!-- Sản Lượng thực tế phòng sx tiêp theo -->
                <div class="card card-primary mb-4">
                        <div class="card-header border-transparent">
                            <h3 class="card-title">
                               Tồn Kho Phân Bổ Theo Phòng Sản Xuất Ở Công Đoạn Tiếp Theo
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
                                    @php $stage_code_current = null; @endphp
                                    @foreach ($sum_by_next_room as $key_room => $data)

                                        @if ($stage_code_current != $data->stage_code)
                                            <tr style="background:#CDC717; color:#003A4F; font-weight:bold;">
                                                <td class="text-end" colspan="6">

                                                <button type="button" class="btn btn-sm btn-info toggle-stage" 
                                                    style="width: 20px; height: 20px; padding: 0; line-height: 0;"
                                                    data-stage="{{ $data->stage_code }}">+</button>

                                                    Công Đoạn {{ $stage_name[$data->stage_code] }}
                                                </td>
                                            </tr>
                                             @php $stage_code_current = $data->stage_code; @endphp
                                        @endif
                                        
                                        <tr class="stage-child stage-{{ $data->stage_code }}">
                                            <td>{{ $loop->iteration }}</td>
                                            <td>{{ $data->next_room }}</td>
                                            <td>{{ $data->stage }}</td>
                                            <td>{{ $data->production_group }}</td>
                                            <td>
                                                {{ $data->stage_code <= 5 
                                                    ? number_format($data->sum_yields, 2) . ' Kg' 
                                                    : number_format($data->sum_yields, 0) . ' ĐVL' 
                                                }}
                                            </td>

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
  const stageNameMap = @json($stage_name);
  $(document).ready(function () {
    document.body.style.overflowY = "auto";
    $('.btn-detial').on('click', function() {

        const room_id = $(this).data('room_id');
       
        const history_modal = $('#data_table_detail_body')

                // Xóa dữ liệu cũ
                history_modal.empty();

                // Gọi Ajax lấy dữ liệu history
                $.ajax({
                    url: "{{ route('pages.quarantine.actual.detail') }}",
                    type: 'post',
                    data: {
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
                                  <td>${(item.pre_room ?? '')}</td>
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

<script>
    document.querySelectorAll('.toggle-stage').forEach(btn => {
        btn.addEventListener('click', function() {
            const stage = this.getAttribute('data-stage');
            const rows = document.querySelectorAll('.stage-' + stage);
            rows.forEach(row => {
                row.style.display = row.style.display === 'none' ? '' : 'none';
            });

            // đổi dấu + / -
            this.textContent = this.textContent === '+' ? '-' : '+';
        });
    });
</script>



