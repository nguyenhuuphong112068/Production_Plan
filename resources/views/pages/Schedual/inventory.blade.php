<link href="{{ asset('css/bootstrap.min.css') }}" rel="stylesheet">
<style>
  .custom-modal-size {
    max-width: 90% !important;
    width: 90% !important;
  }
</style>

<div class="modal fade" id="selectProductModal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog custom-modal-size" role="document">
    <div class="modal-content">

      <div class="modal-header">
        <a href="{{ route('pages.general.home') }}">
          <img src="{{ asset('img/iconstella.svg') }}" style="opacity: 0.8; max-width:45px;">
        </a>

        <h4 class="modal-title w-100 text-center" id="createModal" style="color: #CDC717">
             Danh mục sản phẩm
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
              <table id="example1" class="table table-bordered table-striped w-100">
                <thead style="position: sticky; top: -1px; background-color: white; z-index: 1020">
                  <tr>
                    <th>STT</th>
                    <th>Mã sản phẩm</th>
                    <th>Tên sản phẩm</th>
                    <th>Chỉ tiêu -  Công Đoạn</th>
                    <th>Số lượng mẫu</th>
                    <th>Thời gian kiểm</th>
                    <th>Thiết bị</th>
                    <th>Ngày Trả Kết Quả</th>
                    <th>Chọn</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach ($imports as $data)
                    <tr>
                      <td>{{ $loop->iteration }}</td>
                      <td>{{ $data->code }}</td>
                      <td>{{ $data->name }}</td>
                      <td>{{ $data->testing . "-" . $data->stage }}</td>
                      <td>{{ $data->sample_Amout . ' ' . $data->unit }}</td>
                      <td>{{ $data->excution_time }} h</td>
                      <td>{{ $data->instrument_type }}</td>
                        @php
                            $today = new DateTime(); 
                            $dataDate = new DateTime($data->experted_date); 
                            $interval = $today->diff($dataDate); 
                        @endphp
                      <td>{{ \Carbon\Carbon::parse($data->experted_date)->format('d/m/Y') . " - (". (int)$interval->format('%r%a') + 1 .")"   }}  </td>
                      <td class="text-center align-middle">


                        <button type="summit" class="btn btn-success btn-plus" 
                        
                          data-id="{{ $data->id }}"
                          data-code="{{ $data->code }}"
                          data-name="{{ $data->name }}"
                          data-testing="{{ $data->testing }}"
                          data-excution_time="{{ $data->excution_time }}"
                          data-testing_code="{{ $data->testing_code }}"
                          data-experted_date="{{ $data->experted_date }}"
                          data-batch_no="{{ $data->batch_no }}"
                          data-testing="{{ $data->testing }}"
                          data-stage="{{ $data->stage }}"
                          data-imoported_amount="{{ $data->imoported_amount }}"
                          data-unit="{{ $data->unit }}"
                          data-instrument_type="{{ $data->instrument_type }}"
                         
                          

                          data-dismiss="modal"
                          >
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
      $('.btn-plus').click(function () {
          const button = $(this);
          
          const createModal = $('#createModal');
         
          const amount = button.data('sample_amout');

          createModal.modal('show');

          // Gán dữ liệu vào modal mới (nếu cần)
          createModal.find('input[name="testing_code"]').val(button.data('testing_code'));
          createModal.find('input[name="code"]').val(button.data('code'));
          createModal.find('input[name="name"]').val(button.data('name'));
          createModal.find('input[name="excution_time"]').val(button.data('excution_time') + " h");
          createModal.find('input[name="Batch_Testing_Stage"]').val(button.data('batch_no') + " - " + button.data('testing') + " - " +  button.data('stage'));
          createModal.find('input[name="imoported_amount"]').val(button.data('imoported_amount') + " " + button.data('unit'));
          createModal.find('input[name="imported_id"]').val(button.data('id'));
         

        	let rawDate = button.data('experted_date'); 
          if (rawDate) {
              let parts = rawDate.split('-'); 
              let formatted = `${parts[2]}/${parts[1]}/${parts[0]}`;
              
              createModal.find('input[name="experted_date"]').val(formatted);
          }

          let instrument_type = button.data('instrument_type')
          const newUrl = `/Schedual?instrument_type=${instrument_type}&openModal=1`;

          

          // window.history.pushState({}, '', newUrl);
          // // Gửi request để lấy dữ liệu
          // $.get('/Schedual', { instrument_type }, function (response) { });

  

      });
    });
</script>
<script>
    $(document).ready(function () {
        @if (request()->get('openModal'))
            $('#createModal').modal('show');
        @endif
    });
</script>

