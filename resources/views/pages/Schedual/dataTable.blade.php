<div class="content-wrapper">
    <!-- Main content -->
    <section class="content">
      <div class="container-fluid">
        <div class="row">
          <div class="col-12">
            <!-- /.card-header -->
            <div class="card">

       

              <!-- /.card-Body -->
              <div class="card-body mt-5">

                <button class="btn btn-success btn-create mb-2" data-toggle="modal" data-target="#selectProductModal" style="width: 155px" >
                      <i class="fas fa-plus"></i> Thêm
                </button>

                <table id="example1" class="table table-bordered table-striped">

                  <thead style = "position: sticky; top: 60px; background-color: white; z-index: 1020" >
                
                    <tr>
                    <th>STT</th>
                    <th>Sản Phẩm</th>
                    <th>Số lô - Công Đoạn</th>
                    <th>Chỉ tiêu</th>
                    <th>Thiết Bị</th>
                    <th>Ngày Kiểm</th>
                    <th>Kiểm Nghiệm Viên</th>
                    <th>Ghi Chú</th>
                    <th>Người Tạo</th>
                    <th>Ngày Tạo</th>
                    <th>Ngày Trả KQ</th>
                    <th>Edit</th>
                    <th>Hủy</th>
                    <th>HT</th>
                  </tr>
                  </thead>
                  <tbody>
                 
                  @foreach ($datas as $data)
                    <tr>
                      <td>{{ $loop->iteration}} </td>
                      {{-- <td>{{ $data->code}}</td> --}}
                      <td>{{ $data->name}}</td>
                      <td>{{ $data->batch_no ." - ". $data->stage}}</td>
                      <td>{{ $data->testing}}</td>
                      <td>{{ $data->ins_name}}</td>
                      <td><div> {{ \Carbon\Carbon::parse($data->startDate)->format('d/m/Y H:i') }}   </div> 
                           <div>  {{ \Carbon\Carbon::parse($data->endDate)->format('d/m/Y H:i')}} </div>  </td>
                      <td>{{ $data->analyst}}</td>
                      <td>{{ $data->note}}</td>
                      <td>{{ \Carbon\Carbon::parse($data->experted_date)->format('d/m/Y') }}</td>
                                        
                      <td>{{ $data->prepareBy}}</td>
                      <td>{{ \Carbon\Carbon::parse($data->created_at)->format('d/m/Y') }}</td>
                      
                      <td class="text-center align-middle">

                          <button type="button" class="btn btn-warning btn-edit"

                            data-id="{{ $data->id }}"
                            data-code="{{ $data->code }}"
                            data-name="{{ $data->name }}"
                            data-testing="{{ $data->testing }}"
                            data-excution_time="{{ $data->excution_time }}"
                            data-experted_date="{{ $data->experted_date }}"
                            data-batch_no="{{ $data->batch_no }}"
                            data-testing="{{ $data->testing }}"
                            data-stage="{{ $data->stage }}"
                            data-imoported_amount="{{ $data->imoported_amount }}"
                            data-unit="{{ $data->unit }}"
                            data-stage="{{ $data->unit }}"
                            data-analyst="{{ $data->analyst}}"
                            data-start-Date="{{$data->startDate}}"
                            data-end-Date="{{ $data->endDate}}"
                            data-insid="{{ $data->ins_Id }}"
                            data-note="{{ $data->note }}"


                            data-toggle="modal"
                            data-target="#updateModal">
                            <i class="fas fa-edit"></i>
                          </button>
                      </td>


                      <td class="text-center align-middle">  
                        <form class="form-deActive" action="{{ route('pages.Schedual.deActive', ['id' => $data->id]) }}" method="post">
                            @csrf
                            <input type="hidden" name="imported_id" value="{{$data->imported_id}}">
                            <button type="button" class="btn btn-danger" data-name="{{ $data->name }}">
                                <i class="fas fa-trash"></i>
                            </button>
                        </form>
                      </td>

                       <td class="text-center align-middle">

                          <button type="button" class="btn btn-success btn-success" id ="finished"

                            data-id="{{ $data->id }}"
                            data-code="{{ $data->code }}"
                            data-name="{{ $data->name }}"
                            data-testing="{{ $data->testing }}"
                            data-excution_time="{{ $data->excution_time }}"
                            data-experted_date="{{ $data->experted_date }}"
                            data-batch_no="{{ $data->batch_no }}"
                            data-testing="{{ $data->testing }}"
                            data-stage="{{ $data->stage }}"
                            data-imoported_amount="{{ $data->imoported_amount }}"
                            data-unit="{{ $data->unit }}"
                            data-stage="{{ $data->unit }}"
                            data-analyst="{{ $data->analyst}}"
                            data-start-Date="{{$data->startDate}}"
                            data-end-Date="{{ $data->endDate}}"
                            data-insid="{{ $data->ins_Id }}"
                            data-note="{{ $data->note }}"


                            data-toggle="modal"
                            data-target="#createHistoryModal">
                            <i class="fas fa-check"></i>
                          </button>
                      </td>

                    </tr>
                  @endforeach

                  </tbody>
                </table>
              </div>
              <!-- /.card-body -->
            </div>
            <!-- /.card -->
          </div>
          <!-- /.col -->
        </div>
        <!-- /.row -->
      </div>
      <!-- /.container-fluid -->
    </section>
    <!-- /.content -->
  </div>


<script src="{{ asset('js/vendor/jquery-1.12.4.min.js') }}"></script>
<script src="{{ asset('js/popper.min.js') }}"></script>
<script src="{{ asset('js/bootstrap.min.js') }}"></script>
<script src="{{ asset('js/sweetalert2.all.min.js') }}"></script>

@if (session('success'))
<script>
    Swal.fire({
        title: 'Thành công!',
        text: '{{ session('success') }}',
        icon: 'success',
        timer: 2000, // tự đóng sau 2 giây
        showConfirmButton: false
    });
</script>
@endif

<script>

  $(document).ready(function () {

      $('.btn-create').click(function () {
          const modal = $('#createModal');
      });

      $('.btn-edit').click(function () {
        
      const button = $(this);
      const updateModal = $('#updateModal');
      updateModal.modal('show');

      // Gán dữ liệu vào modal mới (nếu cần)

        updateModal.find('input[name="id"]').val(button.data('id'));
        updateModal.find('input[name="code"]').val(button.data('code'));
        updateModal.find('input[name="name"]').val(button.data('name'));
        updateModal.find('input[name="excution_time"]').val(button.data('excution_time') + " h");
        updateModal.find('input[name="Batch_Testing_Stage"]').val(button.data('batch_no') + " - " + button.data('testing') + " - " +  button.data('stage'));
        updateModal.find('input[name="imoported_amount"]').val(button.data('imoported_amount') + " " + button.data('unit'));
        updateModal.find('input[name="imported_id"]').val(button.data('id'));
        updateModal.find('input[name="experted_date"]').val(button.data('experted_date'));
        updateModal.find('select[name="analyst"]').val(button.data('analyst'));
        updateModal.find('select[name="ins_Id"]').val(button.data('insid'));

        updateModal.find('input[name="startDate"]').val(button.data('startDate'));
        updateModal.find('input[name="endDate"]').val(button.data('endDate'));
        updateModal.find('input[name="note"]').val(button.data('note'));
        
      });

      $('.form-deActive').on('submit', function (e) {
        
          e.preventDefault(); // chặn submit mặc định
          const form = this;
          const productName = $(form).find('button[type="submit"]').data('name');
         

          Swal.fire({
            title: 'Bạn chắc chắn muốn hủy lịch?',
            text: `Sản phẩm: ${productName}`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#28a745',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Đồng ý',
            cancelButtonText: 'Hủy'
          }).then((result) => {
            if (result.isConfirmed) {
              form.submit(); // chỉ submit sau khi xác nhận
            }
          });
        });

        $('#finished').click(function () {
        
        const button = $(this);
        const createHistoryModal = $('#createHistoryModal');
        createHistoryModal.modal('show');

        // Gán dữ liệu vào modal 
        createHistoryModal.find('input[name="schedual_id"]').val(button.data('id'));
        createHistoryModal.find('input[name="code"]').val(button.data('code'));
        createHistoryModal.find('input[name="name"]').val(button.data('name'));
        createHistoryModal.find('input[name="excution_time"]').val(button.data('excution_time') + " h");
        createHistoryModal.find('input[name="Batch_Testing_Stage"]').val(button.data('batch_no') + " - " + button.data('testing') + " - " +  button.data('stage'));
        createHistoryModal.find('input[name="imoported_amount"]').val(button.data('imoported_amount') + " " + button.data('unit'));
        createHistoryModal.find('input[name="imported_id"]').val(button.data('id'));
        createHistoryModal.find('input[name="experted_date"]').val(button.data('experted_date'));
        createHistoryModal.find('select[name="analyst"]').val(button.data('analyst'));
        createHistoryModal.find('select[name="ins_Id"]').val(button.data('insid'));
        createHistoryModal.find('input[name="startDate"]').val(button.data('startDate'));
        createHistoryModal.find('input[name="endDate"]').val(button.data('endDate'));
        createHistoryModal.find('input[name="note"]').val(button.data('note'));
        
      });


     
  });
</script>


