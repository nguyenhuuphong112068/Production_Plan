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

                <button class="btn btn-success btn-create mb-2" data-toggle="modal" data-target="#createModal" style="width: 155px" >
                      <i class="fas fa-plus"></i> Thêm
                </button>

                <table id="example1" class="table table-bordered table-striped">

                  <thead style = "position: sticky; top: 60px; background-color: white; z-index: 1020" >
                
                    <tr>
                    <th>STT</th>
                    <th>Tên Đăng Nhập</th>
                    <th>Nhóm Người Dùng</th>
                    <th>Tên Người Dùng</th>
                    <th>Phòng Ban</th>
                    <th>Tổ</th>
                    <th>Mail</th>
                    <th>Người Tạo</th>
                    <th>Ngày Tạo</th>
                    <th>Edit</th>
                    <th>DeActive</th>
                  </tr>
                  </thead>
                  <tbody>
                 
                  @foreach ($datas as $data)
                    <tr>
                      <td>{{ $loop->iteration}} </td>
                      <td>{{ $data->userName}}</td>
                      <td>{{ $data->userGroup}}</td>
                      <td>{{ $data->fullName}}</td>
                      <td>{{ $data->deparment}}</td>
                      <td>{{ $data->groupName}}</td>
                      <td>{{ $data->mail}}</td>
                      <td>{{ $data->prepareBy}}</td>
                      <td>{{ \Carbon\Carbon::parse($data->created_at)->format('d/m/Y') }}</td>
                      
                      <td class="text-center align-middle">
                          <button type="button" class="btn btn-warning btn-edit"
                              data-id="{{ $data->id }}"
                              data-username="{{ $data->userName }}"
                              data-usergroup="{{ $data->userGroup }}"
                              data-fullname="{{ $data->fullName }}"
                              data-deparment="{{ $data->deparment }}"
                              data-groupname="{{ $data->groupName }}"
                              data-mail="{{ $data->mail }}"
                              data-toggle="modal"
                              data-target="#UpdateModal">
                              <i class="fas fa-edit"></i>
                          </button>
                      </td>


                      <td class="text-center align-middle">  

                        <form class="form-deActive" action="{{ route('pages.User.deActive', ['id' => $data->id]) }}" method="post">
                            @csrf
                            <button type="submit" class="btn btn-danger" data-name="{{ $data->userName }}">
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

      $('.btn-edit').click(function () {
          const button = $(this);
          const modal = $('#UpdateModal');
          // Gán dữ liệu vào input
          modal.find('input[name="id"]').val(button.data('id'));
          modal.find('input[name="userName"]').val(button.data('username'));
          modal.find('select[name="userGroup"]').val(button.data('usergroup'));
          modal.find('input[name="fullName"]').val(button.data('fullname'));
          modal.find('select[name="deparment"]').val(button.data('deparment'));
          modal.find('select[name="groupName"]').val(button.data('groupname'));
          modal.find('input[name="mail"]').val(button.data('mail'));

          const id = button.data('id');

          
        });

        $('.btn-create').click(function () {
          const modal = $('#Modal');
        });

        $('.form-deActive').on('submit', function (e) {
          e.preventDefault(); // chặn submit mặc định
          const form = this;
          const name = $(form).find('button[type="submit"]').data('userGroup');
         

          Swal.fire({
            title: 'Bạn chắc chắn muốn vô hiệu?',
            text: `User: ${name}`,
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

  });
</script>


