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
                    <th>Số lượng mẫu nhận</th>
                    <th>Ngày Trả Kết Quả</th>
                    <th>Đã Sắp lịch</th>
                    <th>Người Nhận</th>
                    <th>Ngày Nhận</th>
                    <th>Edit</th>
                    <th>Hủy mẫu</th>
                  </tr>
                  </thead>
                  <tbody>
                 
                  @foreach ($datas as $data)
                    <tr>
                      <td>{{ $loop->iteration}} </td>
                      {{-- <td>{{ $data->code}}</td> --}}
                      <td>{{ $data->code ." - ". $data->name}}</td>
                      <td>{{ $data->batch_no ." - ". $data->stage}}</td>
                      <td>{{ $data->testing}}</td>
                      <td>{{ $data->imoported_amount . " " . $data->unit}}</td>
                       <td>{{ \Carbon\Carbon::parse($data->experted_date)->format('d/m/Y') }}</td>
                      <td>
                        <div class="text-center">
                            <div class="icheck-primary d-inline"  >
                                <input type="checkbox" id="" {{ $data->scheduled !== 0 ? 'checked' : '' }} disabled >
                            </div>
                        </div>
                      </td>                     
                      <td>{{ $data->prepareBy}}</td>
                      <td>{{ \Carbon\Carbon::parse($data->created_at)->format('d/m/Y') }}</td>
                      
                      <td class="text-center align-middle">
                          <button type="button" class="btn btn-warning btn-edit"

                            data-id="{{ $data->id }}"
                            data-code="{{ $data->code }}"
                            data-name="{{ $data->name }}"
                            data-testing="{{ $data->testing }}"
                            data-unit="{{ $data->unit }}"
                            data-imoported_amount	="{{ $data->imoported_amount}}"
                            data-batch_no="{{ $data->batch_no}}"
                            data-stage="{{ $data->stage}}"
                            data-experted_date="{{ $data->experted_date}}"
                              
                              
                            data-toggle="modal"
                            data-target="#updateModal">
                            <i class="fas fa-edit"></i>
                          </button>
                      </td>


                      <td class="text-center align-middle">  

                        <form class="form-deActive" action="{{ route('pages.category.product.deActive', ['id' => $data->id]) }}" method="post">
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

      $('.btn-create').click(function () {
          const modal = $('#productNameModal');
      });

      $('.btn-edit').click(function () {

      const button = $(this);
      const updateModal = $('#updateModal');
      const amount = button.data('imoported_amount');

      updateModal.modal('show');
      // Gán dữ liệu vào modal mới (nếu cần)
      updateModal.find('input[name="id"]').val(button.data('id'));
      updateModal.find('input[name="code"]').val(button.data('code'));
      updateModal.find('input[name="name"]').val(button.data('name'));
      updateModal.find('input[name="testing"]').val(button.data('testing'));
      updateModal.find('input[name="unit"]').val(button.data('unit'));
      updateModal.find('input[name="experted_date"]').val(button.data('experted_date'));
      updateModal.find('input[name="imoported_amount"]').attr('min', amount).val(amount);
      updateModal.find('input[name="batch_no"]').val(button.data('batch_no'));
      updateModal.find('input[name="stage"]').val(button.data('stage'));


      });


      $('.form-deActive').on('submit', function (e) {
          e.preventDefault(); // chặn submit mặc định
           const form = this;
          const productName = $(form).find('button[type="submit"]').data('name');
         

          Swal.fire({
            title: 'Bạn chắc chắn muốn vô hiệu hóa?',
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

  });
</script>


