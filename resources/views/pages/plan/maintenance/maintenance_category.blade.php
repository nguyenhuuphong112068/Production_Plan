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
             DANH MỤC HIỆU CHUẨN - BẢO TRÌ
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
                <table id="maintenance_category" class="table table-bordered table-striped">
                  <thead >
                    <tr>
                    <th>STT</th>
                    <th>Mã Thiết Bị</th>
                    <th>Tên Thiết Bị</th>
                    <th>Vị Trí Lắp Đặt</th>
                    <th>Thời gian Thực Hiện</th>
                    <th>HVAC</th>
                    <th>Ghi Chú</th>
                    <th>Người Tạo/Ngày Tạo</th>
                    <th>Chọn</th>
                    
                  </tr>
                  </thead>
                  <tbody>
                 
                  @foreach ($category as $data)
                    <tr>
                      <td>{{ $loop->iteration}} </td>
                     
                      @if ($data->active)
                        <td class="text-success"> {{$data->code}}</td>
                      @else
                        <td class="text-danger"> {{$data->code}}</td>
                      @endif

                     
                      <td>{{ $data->name}}</td>
                      <td>{{ $data->rooms }}</td>
                      <td>{{ $data->quota}}</td>
                      <td class="text-center align-middle">
                            @if ($data->is_HVAC)
                              <i class="fas fa-check-circle text-primary fs-4"></i>
                            @endif
                      </td>
                      <td>{{ $data->note}}</td>
                      <td>
                          <div> {{ $data->created_by}} </div>
                          <div>{{ \Carbon\Carbon::parse($data->created_at)->format('d/m/Y') }} </div>
                      </td>    
                      
                      <td class="text-center align-middle">
                          <button type="button" class="btn btn-success btn-plus"
                              data-maintenance_category_ids="{{ $data->maintenance_category_ids }}"
                              data-code="{{$data->code}}"
                              data-name="{{$data->name}}"
                              data-quota="{{$data->quota}}"
                              data-rooms="{{$data->rooms}}"
                              
                             
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
      $('#maintenance_category').DataTable({
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
        $('#maintenance_category').on('click', '.btn-plus', function () {
          const button = $(this);
          const modal = $('#create_modal');
         
         
          modal.find('input[name="code"]').val(button.data('code'));
          modal.find('input[name="name"]').val(button.data('name'));
          modal.find('input[name="quota"]').val(button.data('quota'));
          modal.find('input[name="rooms"]').val(button.data('rooms'));
          modal.find('input[name="maintenance_category_ids"]').val(button.data('maintenance_category_ids'));
         
      });

      // Mở modal nếu có query openModal
      @if (request()->get('openModal'))
          $('#createModal').modal('show');
      @endif
  });
</script>
