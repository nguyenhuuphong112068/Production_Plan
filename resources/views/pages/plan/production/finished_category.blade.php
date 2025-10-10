<link href="{{ asset('css/bootstrap.min.css') }}" rel="stylesheet">
<style>
  .selectProductModal-modal-size {
    max-width: 90% !important;
    width: 90% !important;
  }
</style>

<div class="modal fade" id="selectProductModal" tabindex="-1" role="dialog" aria-hidden="true">
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
              <table id="finished_category" class="table table-bordered table-striped w-100">
                <thead style="position: sticky; top: -1px; background-color: white; z-index: 1020">
                  <tr>
                    <th>STT</th>
                    <th>Mã BTP</th>
                    <th>Mã TP</th>
                    <th>Tên Sản Phẩm</th>
                    <th>Cở Lô</th>
                    <th>Thị Trường</th>
                    <th>Qui Cách</th>
                    <th>Chọn</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach ($finished_product_category as $data)
                    <tr>
                      <td>{{ $loop->iteration}} </td>
                      <td>{{ $data->intermediate_code}}</td>
                      <td>{{ $data->finished_product_code }}</td>
                      <td> {{$data->name}}</td>
                      <td>
                          <div> {{ $data->batch_qty  . " " .  $data->unit_batch_qty}} </div>
                      </td>
                      <td> {{$data->market}}</td>
                      <td> {{$data->specification}}</td>
                      
                      <td class="text-center align-middle">


                        <button type="summit" class="btn btn-success btn-plus" 
                          data-id="{{ $data->id }}"
                          data-intermediate_code="{{ $data->intermediate_code }}"
                          data-finished_product_code="{{ $data->finished_product_code }}"
                          data-name="{{ $data->name }}"
                          data-batch_qty="{{ $data->batch_qty }}"
                          data-unit_batch_qty="{{ $data->unit_batch_qty }}"
                          data-market="{{ $data->market }}"
                          data-specification="{{ $data->specification }}"
                          data-plan_list_id="{{ $plan_list_id }}" 
                        
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


<script>
  $(document).ready(function () {
      // Khởi tạo DataTable
      $('#finished_category').DataTable({
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
        $('#finished_category').on('click', '.btn-plus', function () {
          const button = $(this);
          const modal = $('#createModal');
          const modal_source = $('#create_soure_modal');
          

          modal.find('input[name="product_caterogy_id"]').val(button.data('id'));
          modal.find('input[name="plan_list_id"]').val(button.data('plan_list_id'));
          modal.find('input[name="intermediate_code"]').val(button.data('intermediate_code'));
          modal.find('input[name="finished_product_code"]').val(button.data('finished_product_code'));
          modal.find('input[name="batch_qty"]').val(button.data('batch_qty') + " - " + button.data('unit_batch_qty'));
          modal.find('input[name="name"]').val(button.data('name'));
          modal.find('input[name="specification"]').val(button.data('market') + " - " + button.data('specification'));
          modal.find('input[name="number_of_unit"]').attr('max', button.data('batch_qty'));
          modal.find('input[name="max_number_of_unit"]').val(button.data('batch_qty'));
          modal.find('input[name="number_of_unit"]').val(button.data('batch_qty'));
          
          modal.find("#add_source_material").data("intermediate_code", button.data('intermediate_code'));
          modal.modal('show');

          modal_source.find('input[name="intermediate_code"]').val(button.data('intermediate_code'));
          modal_source.find('input[name="product_name"]').val(button.data('name'));



          


      });

      // Mở modal nếu có query openModal
      @if (request()->get('openModal'))
          $('#createModal').modal('show');
      @endif
  });
</script>
