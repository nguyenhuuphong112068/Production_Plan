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
                 <div class="row">
                    <div class="col-md-2">
                      <button class="btn btn-success btn-create mb-2" data-toggle="modal" data-target="#productNameModal" style="width: 155px" >
                            <i class="fas fa-plus"></i> Thêm
                      </button>
                    </div>
                    <div class="col-md-8"></div> 
                    <div class="col-md-2 d-flex justify-content-end">
                      <form id = "filterForm"  action="{{ route('pages.quota.production.list') }}" method="get">
                            @csrf
                           <div class="form-group" style="width: 177px">
                               <select class="form-control" name="stage_code" style="text-align-last: center;" onchange="document.getElementById('filterForm').submit();">
                                  <option  {{ $stage_code == 1 ? 'selected' : '' }} value= 1>Cân</option>
                                  <option  {{ $stage_code == 3 ? 'selected' : '' }} value= 3>Pha Chế</option>
                                  <option  {{ $stage_code == 4 ? 'selected' : '' }} value= 4>Trộn Hoàn Tất</option>
                                  <option  {{ $stage_code == 5 ? 'selected' : '' }} value= 5>Định Hình</option>
                                  <option  {{ $stage_code == 6 ? 'selected' : '' }} value= 6>Bao Phim</option>
                                  <option  {{ $stage_code == 7 ? 'selected' : '' }} value= 7>Đóng Gói</option>
                              </select>           
                            </div>
                      </form>
                    </div>
                </div>    
                <table id="example1" class="table table-bordered table-striped" style="font-size: 20px">

                  <thead style = "position: sticky; top: 60px; background-color: white; z-index: 1020" >
                
                 
                      <tr>
                          <th rowspan="2">STT</th>
                          <th rowspan="2">Mã Sản Phẩm</th>
                          <th rowspan="2">Sản Phẩm</th>
                          <th rowspan="2">Phòng Sản Xuất</th>

                          <th colspan="4" class="text-center">Thời Gian</th>

                          <th rowspan="2" style="width: 50px">Số Lô Chiến Dịch</th>
                          <th rowspan="2">Ghi Chú</th>
                          <th rowspan="2">Người Tạo/ Ngày Tạo</th>
                          <th rowspan="2">Cập Nhật</th>
                          <th rowspan="2">Vô Hiệu</th>
                      </tr>
                      <tr>
                          <th>Chuẩn Bị</th>
                          <th>Sản Xuất</th>
                          <th>Vệ Sinh Cấp I</th>
                          <th>Vệ Sinh Cấp II</th>
                      </tr>
                  </thead>
        
                  <tbody>
                 
                  @foreach ($datas as $data)
                    <tr>
                      <td>{{ $loop->iteration}} </td>
                      <td> 
                          <div> {{ $data->intermediate_code}} </div>
                          <div> {{ $data->finished_product_code === "NA"? '': $data->finished_product_code}} </div>
                      </td>
                      
                      <td>{{ $data->finished_product_name === null?$data->intermediate_name:$data->finished_product_name . "(" . $data->batch_qty . " " .  $data->unit_batch_qty}}</td>
                      <td> {{$data->room_name . " - " . $data->room_code }} </td>

                      <td> {{$data->p_time }} </td>
                      <td> {{$data->m_time }} </td>
                      <td> {{$data->C1_time }} </td>
                      <td> {{$data->C2_time }} </td>

                      <td> {{$data->maxofbatch_campaign }} </td>
                      <td> {{$data->note }} </td>
                      
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
                            <button type="submit" class="btn btn-danger" data-id="{{ $data->id }}">
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


