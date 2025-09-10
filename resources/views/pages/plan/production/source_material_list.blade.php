<link href="{{ asset('css/bootstrap.min.css') }}" rel="stylesheet">
<style>
  .selectSourceModal-modal-size {
    max-width: 90% !important;
    width: 90% !important;
  }

</style>

<div class="modal fade" id="selectSourceModal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog selectSourceModal-modal-size" role="document">
    <div class="modal-content">

      <div class="modal-header">
        <a href="">
          <img src="{{ asset('img/iconstella.svg') }}" style="opacity: 0.8; max-width:45px;">
        </a>
        {{-- id="createModal" --}}
        <h4 class="modal-title w-100 text-center"  style="color: #CDC717; font-size: 30px">
             DANH MỤC NGUỒN NGUYÊN LIỆU
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
              <table id="source_material_list" class="table table-bordered table-striped w-100">
                <thead style="position: sticky; top: -1px; background-color: white; z-index: 1020">
                  <tr>
                    <th>STT</th>
                    <th>Mã BTP</th>
                    <th>Nguồn</th>
                    <th>Tên Sản Phẩm</th>
                    <th>Người tạo/ Ngày Tạo</th>
                    <th>Chọn</th>
                  </tr>
                </thead>
                <tbody id = "source_material_body" >

                  @foreach ($source_material_list as $data)
                    <tr>
                      <td>{{ $loop->iteration}} </td>
                      <td>{{ $data->intermediate_code}}</td>
                      <td>{{ $data->name }}</td>
                      <td> {{$data->product_name}}</td>
                      <td> 
                        <div>{{$data->prepared_by}}</div>
                        <div>{{$data->created_at}}</div>
                      </td>
                      
                      <td class="text-center align-middle">
                        <button type="summit" class="btn btn-success btn-plus" 

                          data-id="{{ $data->id }}"
                          data-name="{{ $data->name }}"

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
    //Khởi tạo DataTable

    $('#source_material_list').DataTable({
        paging: true,
        lengthChange: true,
        searching: true,
        ordering: true,
        info: true,
        autoWidth: true,
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
    $('#source_material_list').on('click', '.btn-plus', function () {
          const button = $(this);
          const parentModal = $('.modal.show').not('#selectSourceModal');
          parentModal.find('textarea[name="source_material_name"]').val(button.data('name'));
          parentModal.find('input[name="material_source_id"]').val(button.data('id'));
    });


});
</script>
