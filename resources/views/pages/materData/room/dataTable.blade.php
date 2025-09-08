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
                    <th>Mã Phòng</th>
                    <th>Tên Phòng</th>
                    <th>Công Đoạn</th>
                    <th>Tổ Quản Lý</th>
                    <th>Phân Xưởng</th>
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
                      @if ($data->active)
                        <td class="text-success"> {{$data->code}}</td>
                      @else
                        <td class="text-danger"> {{$data->code}}</td>
                      @endif
                      <td>{{ $data->name}}</td>
                      <td>{{ $data->stage}}</td>
                      <td>{{ $data->production_group}}</td>
                      <td>{{ $data->deparment_code}}</td>
                  
                      <td>{{ $data->prepareBy}}</td>
                      <td>{{ \Carbon\Carbon::parse($data->created_at)->format('d/m/Y') }}</td>
                      
                      <td class="text-center align-middle">
                          <button type="button" class="btn btn-warning btn-edit"
                              data-id="{{ $data->id }}"
                              data-code="{{ $data->code }}"
                              data-name="{{ $data->name }}"
                              data-stage_code="{{ $data->stage_code }}"
                              data-production_group="{{ $data->production_group }}"
          
                             
                              data-toggle="modal"
                              data-target="#updateModal">
                              <i class="fas fa-edit"></i>
                          </button>
                      </td>


                      <td class="text-center align-middle">  

                        <form class="form-deActive" action="{{ route('pages.materData.room.deActive') }}" method="post">
                           @csrf
                            <input type="hidden"  name="id" value = "{{ $data->id }}">
                            <input type="hidden"  name="active" value="{{ $data->active }}">

                            @if ($data->active)
                              <button type="submit" class="btn btn-danger" data-active="{{ $data->active }}"  data-name="{{$data->code ." - ". $data->name}}">
                                  <i class="fas fa-lock"></i>
                              </button>  
                            @else
                              <button type="submit" class="btn btn-success" data-active="{{ $data->active }}" data-name="{{$data->code ." - ". $data->name}}">
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
          const modal = $('#updateModal');

          // Gán dữ liệu vào input
          modal.find('input[name="id"]').val(button.data('id'));
          modal.find('input[name="code"]').val(button.data('code'));
          modal.find('input[name="name"]').val(button.data('name'));
          modal.find('select[name="stage_code"]').val(button.data('stage_code'));
          modal.find('select[name="production_group"]').val(button.data('production_group'));
  

        });

        $('.btn-create').click(function () {
          const modal = $('#Modal');
        });

        $('.form-deActive').on('submit', function (e) {
          e.preventDefault(); // chặn submit mặc định
          const form = this;
          const productName = $(form).find('button[type="submit"]').data('name');
          
          const active = $(form).find('button[type="submit"]').data('active');
          let title = 'Bạn chắc chắn muốn vô hiệu hóa danh mục?'
          if (!active){title = 'Bạn chắc chắn muốn phục hồi phòng sản xuất?'}

          Swal.fire({
            title: title,
            text: ` ${productName}`,
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


