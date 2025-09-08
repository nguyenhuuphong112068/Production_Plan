<div class="content-wrapper">
    <!-- Main content -->
    <section class="content">
      <div class="container-fluid">
        <div class="row">
          <div class="col-12">
            <!-- /.card-header -->
            <div class="card">

              <div class="card-header mt-4">
                {{-- <h3 class="card-title">Ghi Chú Nếu Có</h3> --}}

              </div>

              <!-- /.card-Body -->
              <div class="card-body">

                <button class="btn btn-success btn-create mb-2" data-toggle="modal" data-target="#create_modal" style="width: 155px" >
                      <i class="fas fa-plus"></i> Thêm
                </button>

                <table id="data_table_instrument" class="table table-bordered table-striped">

                  <thead style = "position: sticky; top: 60px; background-color: white; z-index: 1020" >
                
                    <tr>
                    <th>STT</th>
                    <th>Mã Thiết Bị</th>
                    <th>Tên Thiết Bị</th>
                    <th>Vị Trí Lắp Đặt</th>
                    <th>Thời gian Thực Hiện</th>
                    <th>Ghi Chú</th>
                    <th>Người Tạo/Ngày Tạo</th>
                    <th>Edit</th>
                    <th>DeActive</th>
                  </tr>
                  </thead>
                  <tbody>
                 
                  @foreach ($datas as $data)
                    <tr>
                      <td>{{ $loop->iteration}} </td>
                     
                      @if ($data->active)
                        <td class="text-success"> {{$data->code}}</td>
                      @else
                        <td class="text-danger"> {{$data->code}}</td>
                      @endif

                     
                      <td>{{ $data->name}}</td>
                      <td>{{ $data->room_name ."-". $data->room_code }}</td>
                      <td>{{ $data->quota}}</td>
                      <td>{{ $data->note}}</td>
                      <td>
                          <div> {{ $data->created_by}} </div>
                          <div>{{ \Carbon\Carbon::parse($data->created_at)->format('d/m/Y') }} </div>
                      </td>    
                      
                      <td class="text-center align-middle">
                          <button type="button" class="btn btn-warning btn-edit"
                          
                              data-id="{{$data->id}}"
                              data-code="{{$data->code}}"
                              data-name="{{$data->name}}"
                              data-room="{{$data->room_name ."-". $data->room_code}}"
                              data-quota="{{$data->quota}}"
                              data-note="{{$data->note}}"

                              data-toggle="modal"
                              data-target="#update_modal">
                              <i class="fas fa-edit"></i>
                          </button>
                      </td>


                      <td class="text-center align-middle">  

                        <form class="form-deActive" action="{{ route('pages.category.maintenance.deActive') }}" method="post">
                            @csrf
                            <input type="hidden"  name="id" value = "{{ $data->id }}">
                            <input type="hidden"  name="active" value="{{ $data->active }}">

                            @if ($data->active)
                              <button type="submit" class="btn btn-danger" data-type="{{ $data->active }}"  data-name="{{  $data->name }}">
                                  <i class="fas fa-lock"></i>
                              </button>  
                            @else
                              <button type="submit" class="btn btn-success" data-type="{{ $data->active }}" data-name="{{ $data->name }}">
                                  <i class="fas fa-unlock"></i>
                              </button>
                            @endif


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

<script>

  $(document).ready(function () {

      $('.btn-edit').click(function () {

          const button = $(this);
          const modal = $('#update_modal');

          // Gán dữ liệu vào input
          modal.find('input[name="id"]').val(button.data('id'));
          modal.find('input[name="code"]').val(button.data('code'));
          modal.find('input[name="name"]').val(button.data('name'));
          modal.find('input[name="room"]').val(button.data('room'));
          modal.find('input[name="quota"]').val(button.data('quota'));
          modal.find('input[name="note"]').val(button.data('note'));
         
       
        });



        $('.form-deActive').on('submit', function (e) {
          e.preventDefault(); // chặn submit mặc định
          const form = this;
          const productName = $(form).find('button[type="submit"]').data('name');
         

          Swal.fire({
            title: 'Bạn chắc chắn muốn vô hiệu hóa?',
            text: `Chỉ Tiêu: ${productName}`,
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


         $('#data_table_instrument').DataTable({
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
            },
            infoCallback: function (settings, start, end, max, total, pre) {
                let activeCount = 0;
                let inactiveCount = 0;

                settings.aoData.forEach(function(row){
                    // row.anCells là danh sách <td> của từng hàng
                    const lastTd = row.anCells[row.anCells.length - 1]; // cột cuối (Vô Hiệu)
                    const btn = $(lastTd).find('button[type="submit"]'); 
                    const status = btn.data('type'); // lấy 1 hoặc 0

                    if (status == 1) activeCount++;
                    else inactiveCount++;
                });

                return pre + ` (Đang hiệu lực: ${activeCount}, Vô hiệu: ${inactiveCount})`;
            }
            
        });

  });
</script>


