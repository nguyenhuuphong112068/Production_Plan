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
                <!-- Sản Lượng thực tế phòng sx tiêp theo -->
                <div class="card-body">
                    <table id="data_table_instrument" class="table table-bordered table-striped">
                        <thead style="position: sticky; top: 60px; background-color: white; z-index: 1020;">
                            <tr>
                                <th>STT</th>
                                <th>Phòng Sản Xuất</th>
                                <th>Tên Phòng</th>
                                <th>sản Lương Thực Tế Công Đoạn trước</th>
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
                                        {{ $data->stage_code <= 4 ? '(Kg)' : '(ĐVL)' }}
                                        </td>
                                        <td>{{ $stage_name[$data->next_stage] ?? '' }}</td>
                                        <td>
                                            {{ \Carbon\Carbon::parse($data->next_start)->format('d/m/Y H:i') }}
                                        </td>
                                    </tr>
                                @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Sản Lượng thực tế đang lưu ở từng phòng biệt trữ -->
                @foreach (collect($datas)->sortKeys() as $quarantine_room_code => $details)
                    <div class="card card-success mb-4">
                        <div class="card-header border-transparent">
                            <h3 class="card-title">
                                {{ $quarantine_room_code }}
                                - {{ $details['room_name'] }}:
                                Tổng Lượng Biệt Trữ {{ number_format($details['total_yields'], 2) }}
                                (Kg)
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
                                        <th>Công Đoạn Kế Tiếp</th>
                                        <th>Thời Gian SX Dự Kiến</th>
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
                                                {{ $data->stage_code <= 4 ? '(Kg)' : '(ĐVL)' }}
                                            </td>
                                            <td>{{ $stage_name[$data->next_stage] ?? '' }}</td>
                                            <td>
                                                {{ \Carbon\Carbon::parse($data->next_start)->format('d/m/Y H:i') }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endforeach

              </div>
            </div>
</div>
<script src="{{ asset('js/vendor/jquery-1.12.4.min.js') }}"></script>
<script src="{{ asset('js/popper.min.js') }}"></script>
<script src="{{ asset('js/bootstrap.min.js') }}"></script>


<script>

  $(document).ready(function () {
      document.body.style.overflowY = "auto";

  });
</script>


