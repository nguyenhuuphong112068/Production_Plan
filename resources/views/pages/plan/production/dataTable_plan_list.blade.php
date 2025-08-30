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

                <table id="example1" class="table table-bordered table-striped" style="font-size: 20px">

                  <thead style = "position: sticky; top: 60px; background-color: white; z-index: 1020" >
                    <tr>
                    <th>STT</th>
                    <th>Kế Hoạch</th>
                    <th>Phân Xưởng</th>
                    <th>Người Tạo</th>
                    <th>Người Tạo</th>
                    <th>Tình Trạng</th>
                    <th>Người Gửi</th>
                    <th>Ngày Gửi</th>
                    <th>Xem</th>
                  </tr>
                  </thead>
                  <tbody>
                 
                  @foreach ($datas as $data)
                    <tr>
                      <td>{{ $loop->iteration}} </td>
                      {{-- <td>{{ $data->code}}</td> --}}
                      <td>{{ $data->title}}</td>
                      <td>{{ $data->deparment_code }}</td>
                      <td>{{ $data->prepared_by}}</td>
                      <td>{{ \Carbon\Carbon::parse($data->created_at)->format('d/m/Y H:i') }}</td>

                      @php
                          $colors = [
                              0 => 'background-color: #ffeb3b; color: black;',   // vàng
                              1 => 'background-color: #4caf50; color: white;',   // xanh lá
                          ];
                          $status = [
                              0 => 'Pending',   // vàng
                              1 => 'Send',   // xanh lá
                          ];
                      @endphp

                      <td style="text-align: center; vertical-align: middle;">
                          <span style="padding: 6px 15px; border-radius: 20px; {{ $colors[$data->send] ?? '' }}">
                              {{ $status[$data->send] }}
                          </span>
                      </td>

                      <td>{{ $data->send_by}}</td>
                      <td>{{ \Carbon\Carbon::parse($data->send_date)->format('d/m/Y H:i') }}</td>         
                      
    
                      <td class="text-center align-middle">  
                        <form action="{{ route('pages.plan.production.open') }}" method="get">
                            @csrf
                            <input type="hidden" name="plan_list_id" value="{{$data->id}}">
                            <input type="hidden" name="month" value="{{$data->month}}"> 
                            <input type="hidden" name="production" value="{{$data->deparment_code}}">     
                            <button type="submit" class="btn btn-success" >
                                <i class="fas fa-eye"></i>
                            </button>
                        </form>
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

{{-- <script>

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

      // $('.form-deActive').on('submit', function (e) {
        
      //     e.preventDefault(); // chặn submit mặc định
      //     const form = this;
      //     const productName = $(form).find('button[type="submit"]').data('name');
         

      //     Swal.fire({
      //       title: 'Bạn chắc chắn muốn hủy lịch?',
      //       text: `Sản phẩm: ${productName}`,
      //       icon: 'warning',
      //       showCancelButton: true,
      //       confirmButtonColor: '#28a745',
      //       cancelButtonColor: '#d33',
      //       confirmButtonText: 'Đồng ý',
      //       cancelButtonText: 'Hủy'
      //     }).then((result) => {
      //       if (result.isConfirmed) {
      //         form.submit(); // chỉ submit sau khi xác nhận
      //       }
      //     });
      //   });

      //   $('#finished').click(function () {
        
      //   const button = $(this);
      //   const createHistoryModal = $('#createHistoryModal');
      //   createHistoryModal.modal('show');

      //   // Gán dữ liệu vào modal 
      //   // createHistoryModal.find('input[name="schedual_id"]').val(button.data('id'));
      //   // createHistoryModal.find('input[name="code"]').val(button.data('code'));
      //   // createHistoryModal.find('input[name="name"]').val(button.data('name'));
      //   // createHistoryModal.find('input[name="excution_time"]').val(button.data('excution_time') + " h");
      //   // createHistoryModal.find('input[name="Batch_Testing_Stage"]').val(button.data('batch_no') + " - " + button.data('testing') + " - " +  button.data('stage'));
      //   // createHistoryModal.find('input[name="imoported_amount"]').val(button.data('imoported_amount') + " " + button.data('unit'));
      //   // createHistoryModal.find('input[name="imported_id"]').val(button.data('id'));
      //   // createHistoryModal.find('input[name="experted_date"]').val(button.data('experted_date'));
      //   // createHistoryModal.find('select[name="analyst"]').val(button.data('analyst'));
      //   // createHistoryModal.find('select[name="ins_Id"]').val(button.data('insid'));
      //   // createHistoryModal.find('input[name="startDate"]').val(button.data('startDate'));
      //   // createHistoryModal.find('input[name="endDate"]').val(button.data('endDate'));
      //   // createHistoryModal.find('input[name="note"]').val(button.data('note'));
        
      // });


     
  });
</script> --}}


