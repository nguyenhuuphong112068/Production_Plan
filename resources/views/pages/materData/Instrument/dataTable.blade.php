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
                    <th>Người Tạo/Ngày Tạo</th>
                    <th>Edit</th>
                    <th>DeActive</th>
                  </tr>
                  </thead>
                  <tbody>
                 
                  @foreach ($datas as $data)
                    <tr>
                      <td>{{ $loop->iteration}} </td>
                      <td>{{ $data->code}}</td>
                      <td>{{ $data->name}}</td>
                      <td>
                          <div> {{ $data->created_by}} </div>
                          <div>{{ \Carbon\Carbon::parse($data->created_at)->format('d/m/Y') }} </div>
                      </td>    
                      
                      <td class="text-center align-middle">
                          <button type="button" class="btn btn-warning btn-edit"
                              data-id="{{$data->id}}"
                              data-code="{{$data->code}}"
                              data-name="{{$data->name}}"               
                              data-toggle="modal"
                              data-target="#update_modal">
                              <i class="fas fa-edit"></i>
                          </button>
                      </td>


                      <td class="text-center align-middle">  

                        <form class="form-deActive" action="{{ route('pages.materData.Instrument.deActive', ['id' => $data->id]) }}" method="post">
                            @csrf
                            <button type="submit" class="btn btn-danger" data-name="{{ $data->name }}">
                                <i class="fas fa-trash"></i>
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

<script>

  $(document).ready(function () {
      document.body.style.overflowY = "auto";
      $('.btn-edit').click(function () {
          const button = $(this);
          const modal = $('#update_modal');

          // Gán dữ liệu vào input
          modal.find('input[name="code"]').val(button.data('code'));
          modal.find('input[name="name"]').val(button.data('name'));
          modal.find('input[name="id"]').val(button.data('id'));

        });

        $('.btn-create').click(function () {
          const modal = $('#update_modal');
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
            lengthMenu: [
                [10, 25, 50, 100, -1],
                [10, 25, 50, 100, "Tất cả"]
            ],
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

  });
</script>


