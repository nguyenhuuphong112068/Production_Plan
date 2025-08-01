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

        <h4 class="modal-title w-100 text-center" id="productNameModalLabel" style="color: #CDC717">
             Danh mục sản phẩm
        </h4>

        <button type="button" class="close" data-dismiss="modal" aria-label="Đóng">
          <span aria-hidden="true">&times;</span>
        </button>

      </div>

      <div class="modal-body" style="max-height: 100%; overflow-x: auto;">
        <div class="card">
          <div class="card-header mt-4">
            {{-- Có thể thêm nội dung tại đây --}}
          </div>
          <div class="card-body">
            <div class="table-responsive">
              <table id="example1" class="table table-bordered table-striped w-100">
                <thead style="position: sticky; top: -1px; background-color: white; z-index: 1020">
                  <tr>
                    <th>STT</th>
                    <th>Mã sản phẩm</th>
                    <th>Tên sản phẩm</th>
                    <th>Chỉ tiêu</th>
                    <th>Số lượng mẫu yêu cầu</th>
                    <th>Thời gian thực hiện</th>
                    <th>Loại Thiết bị</th>
                    <th>Chọn</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach ($category as $data)
                    <tr>
                      <td>{{ $loop->iteration }}</td>
                      <td>{{ $data->code }}</td>
                      <td>{{ $data->name }}</td>
                      <td>{{ $data->testing }}</td>
                      <td>{{ $data->sample_Amout . ' ' . $data->unit }}</td>
                      <td>{{ $data->excution_time }} h</td>
                      <td>{{ $data->instrument_type }}</td>
                      <td class="text-center align-middle">
                        <button type="button" class="btn btn-success btn-plus" data-dismiss="modal"
                        
                          data-id="{{ $data->id }}"
                          data-code="{{ $data->code }}"
                          data-name="{{ $data->name }}"
                          data-testing="{{ $data->testing }}"
                          data-sample_amout="{{ $data->sample_Amout }}"
                          data-unit="{{ $data->unit }}"
                          data-excution_time="{{ $data->excution_time }}"
                          data-testing_code="{{ $data->testing_code }}">

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

<script>
    $(document).ready(function () {
      $('.btn-plus').click(function () {
          const button = $(this);
          const createModal = $('#createModal');
          const selectModal = $('#selectProductModal');
          const amount = button.data('sample_amout');
          createModal.modal('show');

          // Gán dữ liệu vào modal mới (nếu cần)
          createModal.find('input[name="testing_code"]').val(button.data('testing_code'));
          createModal.find('input[name="code"]').val(button.data('code'));
          createModal.find('input[name="name"]').val(button.data('name'));
          createModal.find('input[name="testing"]').val(button.data('testing'));
          createModal.find('input[name="sample_amout"]').val(amount);
          createModal.find('input[name="imoported_amount"]').attr('min', amount).val(amount);
          createModal.find('input[name="unit"]').val(button.data('unit'));
          createModal.find('input[name="excution_time"]').val(button.data('excution_time'));
        
      });
    });
</script>
