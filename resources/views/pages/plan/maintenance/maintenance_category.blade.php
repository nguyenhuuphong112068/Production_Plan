
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
          <img src="{{ asset('img/iconstella.svg') }}" style="opacity: 0.8; max-width:35px;">
        </a>

        <h4 class="modal-title w-100 text-center" id="createModal" style="color: #CDC717; font-size: 24px">
             DANH MỤC HIỆU CHUẨN - BẢO TRÌ
        </h4>

        <button type="button" class="close" data-dismiss="modal" aria-label="Đóng">
          <span aria-hidden="true">&times;</span>
        </button>

      </div>

      <div class="modal-body" style="max-height: 100%; overflow-x: auto;">
        <div class="card">
          <div class="card-header mt-0">
              <button class="btn btn-success mb-0"  style="width: 177px;" id = "btn_get_multi_select"
                data-dismiss="modal"
                {{-- data-toggle="modal"
                data-target="#create_modal" --}}
                
              >
                <i class="fas fa-download"></i> Tải
              </button>            
          </div>
          <div class="card-body">
            <div class="table-responsive">
                <table id="maintenance_category" class="table table-bordered table-striped">
                  <thead >
                    <tr>
                    <th>Chọn</th>
                    <th>STT</th>
                    <th>Mã Thiết Bị</th>
                    <th>Tên Thiết Bị</th>
                    <th>Vị Trí Lắp Đặt</th>
                    <th>Thời gian Thực Hiện</th>
                    <th>HVAC</th>
                    <th>Ghi Chú</th>
                    <th>Người Tạo/Ngày Tạo</th>
                    
                    
                  </tr>
                  </thead>
                  <tbody>
                 
                  @foreach ($category as $data)
                    <tr>
                      <td class="text-center">
                        <input type="checkbox" class="row-checkbox" id="is_select" name="is_select"
                              value="{{ $data->maintenance_category_ids }}"
                              data-code="{{$data->code}}"
                              data-name="{{$data->name}}"
                              data-quota="{{$data->quota}}"
                              data-rooms="{{$data->rooms}}"
                              style="transform: scale(1.8); margin: 5px; cursor: pointer;">
                      </td>

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


      $('#btn_get_multi_select').on('click', function () {
          
          let selected = [];
          $('.row-checkbox:checked').each(function () {
              selected.push({
                  id: $(this).val(),
                  code: $(this).data('code'),
                  name: $(this).data('name'),
                  rooms: $(this).data('rooms'),
                  quota: $(this).data('quota')
              });
          });
          
          if (selected.length === 0) {
               Swal.fire({
                  title: 'Chọn Ít Nhất Một Thiết Bị!',
                  icon: 'warning',
                  timer: 1000,
                  showConfirmButton: false
              });
              return;
          }
          
          const container = $('#selected_instruments_container');
          container.empty(); // xóa cũ

          selected.forEach((item, index) => {
              const block = `
              <div class="row mb-2 border p-2 rounded">
                  <div class="col-md-1">
                      <div class="form-group">
                          <label>Mã Thiết Bị</label>
                          <input type="text" class="form-control" name="devices[${index}][code]" value="${item.code}" readonly />
                      </div>
                  </div>

                  <div class="col-md-2">
                      <div class="form-group">
                          <label>Tên Thiết Bị</label>
                          <input type="text" class="form-control" name="devices[${index}][name]" value="${item.name}" readonly />
                      </div>
                  </div>

                  <div class="col-md-5">
                      <div class="form-group">
                          <label>Phòng Sản Xuất Liên Quan</label>
                          <input type="text" class="form-control" name="devices[${index}][rooms]" value="${item.rooms}" readonly />
                      </div>
                  </div>

                  <div class="col-md-2">
                      <div class="form-group">
                          <label>Thực Hiện Trước Ngày</label>
                          <input type="date" class="form-control" name="devices[${index}][expected_date]" />
                      </div>
                  </div>

                  <div class="col-md-2">
                      <div class="form-group">
                          <label>Ghi chú</label>
                          <textarea class="form-control" name="devices[${index}][note]" rows="1"></textarea>
                      </div>
                  </div>

                  <input type="hidden" name="devices[${index}][maintenance_category_ids]" value="${item.id}" />
              </div>`;
              container.append(block);
          });
          $('#create_modal').modal('show');
      });  
  });
</script>
