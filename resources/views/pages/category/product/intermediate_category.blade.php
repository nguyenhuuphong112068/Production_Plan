<link href="{{ asset('css/bootstrap.min.css') }}" rel="stylesheet">
<style>
  .selectProductModal-modal-size {
    max-width: 90% !important;
    width: 90% !important;
  }
</style>

<div class="modal fade" id="intermediate_category" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog selectProductModal-modal-size" role="document">
    <div class="modal-content">

      <div class="modal-header">
        <a href="{{ route('pages.general.home') }}">
          <img src="{{ asset('img/iconstella.svg') }}" style="opacity: 0.8; max-width:45px;">
        </a>

        <h4 class="modal-title w-100 text-center" id="createModal" style="color: #CDC717; font-size: 30px">
             DANH MỤC SẢN PHẨM
        </h4>

        <button type="button" class="close" data-dismiss="modal" aria-label="Đóng">
          <span aria-hidden="true">&times;</span>
        </button>

      </div>

      <div class="modal-body" style="max-height: 100%; overflow-x: auto;">
        <div class="card">
          {{-- <div class="card-header mt-4">
            Có thể thêm nội dung tại đây 
          </div> --}}
          <div class="card-body">
            <div class="table-responsive">
              
              <table id="intermediate_category_dt" class="table table-bordered table-striped w-100">

                  <thead style="position: sticky; top: -1px; background-color: white; z-index: 1020">
               
                    <tr>
                      <th rowspan="2">STT</th>
                      <th rowspan="2">Mã BTP</th>
                      <th rowspan="2">Tên Sản Phẩm</th>
                      <th rowspan="2">Cở Lô</th>
                      <th rowspan="2">Dạng Bào Chế</th>
                      
                      <!-- Gom nhóm 6 cột -->
                      <th colspan="6" class="text-center">Công Đoạn/Thời gian Biệt Trữ</th>
                      
                      <th rowspan="2">Phân Xưởng</th>
                      <th rowspan="2">Người Tạo/ Ngày Tạo</th>
                      <th rowspan="2">Chọn</th>
                     
                    </tr>
                    <tr>
                      <th>Cân NL</th>
                      <th>Cân NL Khác</th>
                      <th>PC</th>
                      <th>THT</th>
                      <th>ĐH</th>
                      <th>BP</th>
                    </tr>
                    
                  </thead>

                 
                  <tbody>
                 
                  @foreach ($intermediate_category as $data)

                    @php
                        $data->quarantine_time_unit == 1 ? $quarantine_time_unit = 'ngày': $quarantine_time_unit = 'giờ'
                    @endphp

                    <tr>
                      <td>{{ $loop->iteration}} </td>
                      @if ($data->active)
                        <td class="text-success"> {{$data->intermediate_code}}</td>
                      @else
                        <td class="text-danger"> {{$data->intermediate_code}}</td>
                      @endif
                      
                      <td>{{ $data->product_name}}</td>
                      <td>
                          <div> {{ $data->batch_size  . " " .  $data->unit_batch_size . "#"}} </div>
                          <div> {{ $data->batch_qty  . " " .  $data->unit_batch_qty}} </div>
                      </td>
                      <td> {{$data->dosage_name}}</td>

                      <td class="text-center align-middle">
                          <div class="d-flex flex-column align-items-center">
                              @if ($data->weight_1)
                                  <i class="fas fa-check-circle text-primary fs-4"></i>
                                  <span>
                                      @if ($data->quarantine_total == 0)
                                          {{ $data->quarantine_weight ." ". $quarantine_time_unit}}
                                      @else
                                          {{"total:". $data->quarantine_total ." ". $quarantine_time_unit}}
                                      @endif
                                  </span>
                              @endif
                          </div>
                      </td>

                      <td class="text-center align-middle">
                          <div class="d-flex flex-column align-items-center">
                              @if ($data->weight_2)
                                  <i class="fas fa-check-circle text-primary fs-4"></i>
                                  <span>
                                    @if ($data->quarantine_total == 0)
                                        {{ $data->quarantine_weight ." ". $quarantine_time_unit}}
                                    @endif
                                  </span>
                              @endif
                          </div>
                      </td>

                      <td class="text-center align-middle">
                          <div class="d-flex flex-column align-items-center">
                              @if ($data->prepering)
                                  <i class="fas fa-check-circle text-primary fs-4"></i>
                                  <span>
                                    @if ($data->quarantine_total == 0)
                                        {{ $data->quarantine_weight ." ". $quarantine_time_unit}}
                                    @endif
                                  </span>
                              @endif
                          </div>
                      </td>

                      <td class="text-center align-middle">
                          <div class="d-flex flex-column align-items-center">
                              @if ($data->blending)
                                  <i class="fas fa-check-circle text-primary fs-4"></i>
                                  <span>
                                    @if ($data->quarantine_total == 0)
                                        {{ $data->quarantine_weight ." ". $quarantine_time_unit}}
                                    @endif
                                  </span>
                              @endif
                              
                          </div>
                      </td>

                      <td class="text-center align-middle">
                          <div class="d-flex flex-column align-items-center">
                              @if ($data->forming)
                                  <i class="fas fa-check-circle text-primary fs-4"></i>
                                  <span>
                                    @if ($data->quarantine_total == 0)
                                        {{ $data->quarantine_weight ." ". $quarantine_time_unit}}
                                    @endif
                                  </span>
                              @endif
                              
                          </div>
                      </td>

                      <td class="text-center align-middle">
                          <div class="d-flex flex-column align-items-center">
                              @if ($data->coating)
                                  <i class="fas fa-check-circle text-primary fs-4"></i>
                                <span>
                                      @if ($data->quarantine_total == 0)
                                          {{ $data->quarantine_weight ." ". $quarantine_time_unit}}
                                      @else
                                          {{"total:". $data->quarantine_total ." ". $quarantine_time_unit}}
                                      @endif
                                </span>
                              @endif
                            
                          </div>
                      </td>

                      <td>{{ $data->deparment_code}}</td>
                      <td>
                          <div> {{ $data->prepared_by}} </div>
                          <div>{{ \Carbon\Carbon::parse($data->created_at)->format('d/m/Y') }} </div>
                      </td>              
                             
                      <td class="text-center align-middle">
                          <button type="button" class="btn btn-success btn-plus"

                              data-intermediate_code="{{ $data->intermediate_code }}"
                              data-batch_size="{{ $data->batch_size }}"
                              data-unit_batch_size="{{ $data->unit_batch_size }}"
                              data-batch_qty="{{ $data->batch_qty }}"
                              data-unit_batch_qty="{{ $data->unit_batch_qty }}"

                              data-dismiss="modal"

                              data-toggle="modal"
                              data-target="#create_modal">
                              <i class="fas fa-plus"></i>
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
  </div>
</div>
<!-- Scripts -->
<script src="{{ asset('js/vendor/jquery-1.12.4.min.js') }}"></script>
<script src="{{ asset('js/popper.min.js') }}"></script>
<script src="{{ asset('js/bootstrap.min.js') }}"></script>


<script>
  $(document).ready(function () {
      // Khởi tạo DataTable
      $('#intermediate_category_dt').DataTable({
          paging: true,
          lengthChange: true,
          searching: true,
          ordering: true,
          info: true,
          autoWidth: false,
          pageLength: 10,
          language: {
              search: "Tìm kiếm:",
              lengthMenu: "Hiển thị _MENU_ dòng",
              info: "Hiển thị _START_ đến _END_ của _TOTAL_ dòng",
              paginate: {
                  previous: "Trước",
                  next: "Sau"
              }
          }
      });

      // Click nút +
        $('#intermediate_category').on('click', '.btn-plus', function () {
          const button = $(this);
          const modal = $('#create_modal');

          modal.find('input[name="intermediate_code"]').val(button.data('intermediate_code'));
          modal.find('input[name="batch_size"]').val(button.data('batch_size'));
          modal.find('input[name="unit_batch_size"]').val(button.data('unit_batch_size'));
          modal.find('input[name="batch_qty"]').val(button.data('batch_qty'));
          modal.find('input[name="unit_batch_qty"]').val(button.data('unit_batch_qty'));
      });
      


  });
</script>
