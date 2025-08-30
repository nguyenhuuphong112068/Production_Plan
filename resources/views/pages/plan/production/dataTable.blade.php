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
                      <button class="btn btn-success btn-create mb-2" data-toggle="modal" data-target="#selectProductModal" style="width: 155px" >
                            <i class="fas fa-plus"></i> Thêm
                      </button>
                    </div>
                    <div class="col-md-8"></div> 
                    <div class="col-md-2" style="text-align: right;">

                      <form id = "send_form" action="{{ route('pages.plan.production.send') }}" method="post">

                            @csrf
                            <input type="hidden" name="plan_list_id" value="{{$plan_list_id}}">
                            <input type="hidden" name="month" value="{{$month}}"> 
                            <input type="hidden" name="production" value="{{$production}}"> 
                            <button class="btn btn-success btn-create mb-2 "  style="width: 177px" >
                                <i id = "send_btn" class="fas fa-paper-plane"></i> Gửi
                            </button>
                      </form>

                    </div>
                </div>    
                <table id="example1" class="table table-bordered table-striped" style="font-size: 20px">

                  <thead style = "position: sticky; top: 60px; background-color: white; z-index: 1020" >
                
                    <tr>
                    <th>STT</th>
                    <th>Mã Sản Phẩm</th>
                    <th>Sản Phẩm</th>
                    <th>Số Lô</th>
                    <th>Thị Trường/ Qui Cách</th>
                    <th>Ưu Tiên</th>
                    <th>Ngày dự kiến KCS</th>
                    <th>Lô Thẩm định</th>
                    <th>Nguồn</th>
                    <th>Nguyên Liệu</th>
                    <th>Bao Bì</th>
                    <th>Ghi Chú</th>
                    <th>Người Tạo/ Ngày Tạo</th>
                    <th>Cập Nhật</th>
                    <th>Vô Hiệu</th>
                  </tr>
                  </thead>
                  <tbody>
                 
                  @foreach ($datas as $data)
                    <tr>
                      <td>{{ $loop->iteration}} </td>
                      <td> 
                          <div> {{ $data->intermediate_code}} </div>
                          <div> {{ $data->finished_product_code}} </div>
                      </td>
                      <td>{{ $data->name . "(" . $data->batch_qty . " " .  $data->unit_batch_qty}}</td>
                      <td> {{$data->batch}}  </td>
                      <td> 
                          <div> {{ $data->market}} </div>
                          <div> {{ $data->specification}} </div>
                      </td>

                      @php
                          $colors = [
                              1 => 'background-color: #f44336; color: white;',   // đỏ
                              2 => 'background-color: #ff9800; color: white;',   // cam
                              3 => 'background-color: blue; color: white;',   // vàng
                              4 => 'background-color: #4caf50; color: white;',   // xanh lá
                          ];
                      @endphp

                      <td style="text-align: center; vertical-align: middle;">
                          <span style="display: inline-block; padding: 6px 16py; width: 80px; border-radius: 40px; {{ $colors[$data->level] ?? '' }}">
                            <b>  {{ $data->level }} </b>
                          </span>
                      </td>

                      <td>
                          <div>{{ \Carbon\Carbon::parse($data->expected_date)->format('d/m/Y') }} </div>
                      </td>
                      <td class="text-center align-middle">
                          @if ($data->is_val)
                            <i class="fas fa-check-circle text-primary fs-4"></i>
                          @endif
                      </td>

                      <td>{{ $data->source_material_name}}</td>

                      <td>
                          <div>{{ \Carbon\Carbon::parse($data->after_weigth_date)->format('d/m/Y') }} </div>
                          <div>{{ \Carbon\Carbon::parse($data->before_weigth_date)->format('d/m/Y') }} </div>
                      </td>
                      <td>
                          <div>{{ \Carbon\Carbon::parse($data->after_parkaging_date)->format('d/m/Y') }} </div>
                          <div>{{ \Carbon\Carbon::parse($data->before_parkaging_date)->format('d/m/Y') }} </div>
                      </td>  
                      <td> {{ $data->note}} </td>

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
      preventDoubleSubmit("#send_form", "#send_btn");
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


