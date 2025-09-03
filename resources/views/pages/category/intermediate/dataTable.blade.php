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

                <table id="table_data_intermediate" class="table table-bordered table-striped">

                  <thead style = "position: sticky; top: 60px; background-color: white; z-index: 1020" >
                
                    <tr>
                    <th>STT</th>
                    <th>Mã BTP</th>
                    <th>Tên Sản Phẩm</th>
                    <th>Cở Lô</th>
                    <th>Dạng Bào Chế</th>
                    <th>Cân</th>
                    <th>PC</th>
                    <th>THT</th>
                    <th>ĐH</th>
                    <th>BP</th>
                    <th>Phân Xưởng</th>
                    <th>Người Tạo/ Ngày Tạo</th>
                    <th>Cập Nhật</th>
                    <th>Vô Hiệu</th>
                  </tr>
                  </thead>
                  <tbody>
                 
                  @foreach ($datas as $data)
                    <tr>
                      <td>{{ $loop->iteration}} </td>
                      <td> {{$data->intermediate_code}}</td>
                      <td>{{ $data->name}}</td>
                      <td>
                          <div> {{ $data->batch_size  . " " .  $data->unit_batch_size . "#"}} </div>
                          <div> {{ $data->batch_qty  . " " .  $data->unit_batch_qty}} </div>
                      </td>
                      <td> {{$data->dosage}}</td>

                      <td class="text-center align-middle">
                          @if ($data->weight_1)
                            <i class="fas fa-check-circle text-primary fs-4"></i>
                          @endif
                      </td>

                      <td class="text-center align-middle">
                          @if ($data->weight_2)
                            <i class="fas fa-check-circle text-primary fs-4"></i>
                          @endif
                      </td>

                      <td class="text-center align-middle">
                          @if ($data->prepering)
                            <i class="fas fa-check-circle text-primary fs-4"></i>
                          @endif
                      </td>
                      <td class="text-center align-middle">
                          @if ($data->blending)
                            <i class="fas fa-check-circle text-primary fs-4"></i>
                          @endif
                      </td>
                      <td class="text-center align-middle">
                          @if ($data->forming)
                            <i class="fas fa-check-circle text-primary fs-4"></i>
                          @endif
                      </td>
                      <td class="text-center align-middle">
                          @if ($data->coating)
                            <i class="fas fa-check-circle text-primary fs-4"></i>
                          @endif
                      </td>
                      <td>{{ $data->deparment_code}}</td>
                      <td>
                          <div> {{ $data->prepared_by}} </div>
                          <div>{{ \Carbon\Carbon::parse($data->created_at)->format('d/m/Y') }} </div>
                      </td>                     

  
                      
                      <td class="text-center align-middle">
                          <button type="button" class="btn btn-warning btn-edit"

                              data-id="{{ $data->id }}"
                              {{-- data-name="{{ $data->name }}"
                              data-code="{{ $data->code }}"
                              data-testing="{{ $data->testing }}"
                              data-sample_amout="{{ $data->sample_Amout }}"
                              data-unit="{{ $data->unit }}"
                              data-excution-time="{{ $data->excution_time }}"
                              data-instrument="{{ $data->instrument_type }}"
                               --}}
                              
                              data-toggle="modal"
                              data-target="#updateModal">
                              <i class="fas fa-edit"></i>
                          </button>
                      </td>


                      <td class="text-center align-middle">  

                        <form class="form-deActive" action="{{ route('pages.category.product.deActive', ['id' => $data->id]) }}" method="post">
                            @csrf
                            <button type="submit" class="btn btn-danger" data-name="{{ $data->name }}">
                                <i class="fas fa-lock"></i>
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
          const modal = $('#updateModal');

          console.log ( button.data('code') )

          // Gán dữ liệu vào input
          modal.find('input[name="id"]').val(button.data('id'));
          modal.find('input[name="code"]').val(button.data('code'));
          modal.find('input[name="name"]').val(button.data('name'));
          modal.find('input[name="testing"]').val(button.data('testing'));
          modal.find('input[name="sample_Amout"]').val(button.data('sample_amout'));
          modal.find('input[name="unit"]').val(button.data('unit'));
          modal.find('input[name="excution_time"]').val(button.data('excution-time'));
          modal.find('input[name="instrument_type"]').val(button.data('instrument_type'));

          const id = button.data('id');

        });

        $('.btn-create').click(function () {
          const modal = $('#productNameModal');
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


