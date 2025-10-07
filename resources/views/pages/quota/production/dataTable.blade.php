<link href="{{ asset('css/bootstrap.min.css') }}" rel="stylesheet">
<div class="content-wrapper">
    <!-- Main content -->
            <!-- /.card-header -->
            <div class="card">

              <div class="card-header mt-4">
                {{-- <h3 class="card-title">Ghi Chú Nếu Có</h3> --}}
              </div>
              <!-- /.card-Body -->
              <div class="card-body">
                   
                  @php
                      $auth_create = user_has_permission(session('user')['userId'], 'quota_production_create', 'disabled');
                      $auth_update = user_has_permission(session('user')['userId'], 'quota_production_update', 'disabled');
                      $auth_deActive = user_has_permission(session('user')['userId'], 'quota_production_deActive', 'disabled');
                  @endphp

                 <div class="row">

                    <div class="col-md-12"></div> 
                    <div class="col-md-12 d-flex justify-content-end">
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
                <table id="data_table_quota" class="table table-bordered table-striped" style="font-size: 20px">
                  <thead style = "position: sticky; top: 60px; background-color: white; z-index: 1020" >
                      <tr>
                          <th rowspan="2">STT</th>
                          <th rowspan="2">Mã Sản Phẩm</th>
                          <th rowspan="2">Tên Sản Phẩm</th>
                          <th rowspan="2">Cở Lô</th>
                          <th rowspan="2">Phòng Sản Xuất</th>

                          <th colspan="4" class="text-center">Thời Gian</th>

                          <th rowspan="2" style="width: 50px">Số Lô Chiến Dịch</th>
                          <th rowspan="2">Ghi Chú</th>
                          <th rowspan="2">Người Tạo/ Ngày Tạo</th>
                          <th rowspan="2" style="width:1%">Thêm</th>
                          <th rowspan="2" style="width:1%">Cập Nhật</th>
                          <th rowspan="2" style="width:1%">Vô Hiệu</th>
                          <th rowspan="2" style="width:1%">Lich Sữ</th>
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
                          <div> {{ $data->finished_product_code??''}} </div>
                      </td>
                      <td>{{ $data->product_name}} </td>
                      <td>{{ $data->batch_qty . " " .  $data->unit_batch_qty}}</td>

                      <td>
                          @if($data->room_name == null)
                              <span class="px-2 py-1 rounded-pill" style="background-color:red; color:white; font-size: 14px">
                                  Thiếu Định Mức
                              </span>
                          @else
                              {{ $data->room_name . " - " . $data->room_code }}
                          @endif
                      </td>

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
                          <button type="button" class="btn btn-success btn-plus"
                              {{ $auth_create }}
                              data-product_name="{{ $data->product_name }}"
                              data-intermediate_code="{{ $data->intermediate_code }}"
                              data-finished_product_code="{{ $data->finished_product_code}}"
                              data-stage_code="{{ $stage_code }}"
                              
                              data-toggle="modal"
                              data-target="#create_modal"
                              >
                              <i class="fas fa-plus"></i>
                          </button>
                      </td>

                      <td class="text-center align-middle">
                          <button type="button" class="btn btn-warning btn-edit" {{$data->room_name?'':'disabled'}}
                             {{ $auth_update }}  
                              data-id="{{ $data->id }}"
                              data-product_name="{{ $data->product_name }}"
                              data-intermediate_code="{{ $data->intermediate_code }}"
                              data-finished_product_code="{{ $data->finished_product_code }}"
                              data-stage_code="{{ $stage_code }}"
                              data-room_id="{{ $data->room_id?? ''}}"
                              data-room_name="{{ $data->room_name?? ''}}"
                              data-room_code="{{ $data->room_code?? ''}}"
                              data-p_time="{{ $data->p_time?? ''}}"
                              data-m_time="{{ $data->m_time?? ''}}"
                              data-c1_time="{{$data->C1_time?? ''}}"
                              data-c2_time="{{$data->C2_time?? ''}}"
                              data-maxofbatch_campaign="{{ $data->maxofbatch_campaign??''}}"
                              data-note="{{ $data->note??''}}"
                             
                              data-toggle="modal"
                              data-target="#update_modal">
                              <i class="fas fa-edit"></i>
                          </button>
                      </td>


                      <td class="text-center align-middle">  

                        <form class="form-deActive" action="{{ route('pages.quota.production.deActive') }}" method="post">
                            @csrf
                            <input type="hidden"  name="id" value = "{{ $data->id }}">
                            <input type="hidden"  name="active" value="{{ $data->active }}">

                            @if ($data->active)
                              <button type="submit"  {{ $auth_deActive }} class="btn btn-danger" {{$data->room_name?'':'disabled'}} data-type="{{ $data->active }}"  data-name="{{ $data->intermediate_code ."-". $data->finished_product_code ."-".  $data->product_name }}">
                                  <i class="fas fa-lock"></i>
                              </button>  
                            @else
                              <button type="submit" {{ $auth_deActive }} class="btn btn-success" {{$data->room_name?'':'disabled'}} data-type="{{ $data->active }}" data-name="{{ $data->intermediate_code ."-". $data->finished_product_code ."-". $data->product_name }}">
                                  <i class="fas fa-unlock"></i>
                              </button>
                            @endif
                        </form>
                      </td>
                        <td class="text-center align-middle">
                            <button type="button" class="btn btn-primary btn-history position-relative" 
                                data-id="{{ $data->id }}"
                                data-toggle="modal"
                                data-target="#historyModal">
                                <i class="fas fa-history"></i>
                                <span class="badge badge-danger" style="position: absolute; top: -5px;  right: -5px; border-radius: 50%;">
                                   1 {{-- {{ $data->history_count ?? 0 }} --}}
                                </span>
                            </button>
                        </td>
                    </tr>
                  @endforeach

                  </tbody>
                </table>
              </div>
              <!-- /.card-body -->
            </div>
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
      $('.btn-create').click(function () {
          const button = $(this);
          const modal = $(button.data('target'));
          modal.find('input[name="stage_code"]').val(button.data('stage_code'));
      });

      $('.btn-plus').click(function () {
          const button = $(this);
          const modal = $('#create_modal');
          // Gán dữ liệu vào input
          modal.find('input[name="stage_code"]').val(button.data('stage_code'));
          modal.find('input[name="intermediate_code"]').val(button.data('intermediate_code'));
          modal.find('input[name="finished_product_code"]').val(button.data('finished_product_code'));
          modal.find('input[name="product_name"]').val(button.data('product_name'));
          
          if (button.data('stage_code') <= 6) {
            modal.find('input[name="intermediate_code"]').show();
            modal.find('input[name="finished_product_code"]').hide();
           
          } else if (button.data('stage_code') === 7) {
              modal.find('input[name="intermediate_code"]').hide();
              modal.find('input[name="finished_product_code"]').show();
          }

      });



      $('.btn-edit').click(function () {
          const button = $(this);
          const modal = $('#update_modal');
          console.log (button.data('room_id'),button.data('p_time'), button.data('C1_time'),button.data('C2_time'))
          // Gán dữ liệu vào input
          modal.find('input[name="id"]').val(button.data('id'));
          modal.find('input[name="product_name"]').val(button.data('product_name'));
          modal.find('input[name="intermediate_code"]').val(button.data('intermediate_code'));
          modal.find('input[name="finished_product_code"]').val(button.data('finished_product_code'));
          modal.find('input[name="room_id"]').val(button.data('room_name') +" - "+ button.data('room_code'));
          modal.find('input[name="p_time"]').val(button.data('p_time'));
          modal.find('input[name="m_time"]').val(button.data('m_time'));
          modal.find('input[name="C1_time"]').val(button.data('c1_time'));
          modal.find('input[name="C2_time"]').val(button.data('c2_time'));
          modal.find('input[name="maxofbatch_campaign"]').val(button.data('maxofbatch_campaign'));
          modal.find('input[name="note"]').val(button.data('note'));
                              

          if (button.data('stage_code') <= 6) {
            modal.find('input[name="intermediate_code"]').show();
            modal.find('input[name="finished_product_code"]').hide();
           
          } else if (button.data('stage_code') === 7) {
              modal.find('input[name="intermediate_code"]').hide();
              modal.find('input[name="finished_product_code"]').show();
          }

        });

     

       $('.form-deActive').on('submit', function (e) {
          e.preventDefault(); // chặn submit mặc định
          const form = this;
          const productName = $(form).find('button[type="submit"]').data('name');
          const active = $(form).find('button[type="submit"]').data('type');
         
          let title = 'Bạn chắc chắn muốn vô hiệu hóa danh mục?'
          if (!active){title = 'Bạn chắc chắn muốn phục hồi danh mục?'}

          Swal.fire({
            title: title,
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

        $('#data_table_quota').DataTable({
          paging: true,
          lengthChange: true,
          searching: true,
          ordering: true,
          info: true,
          autoWidth: false,
          pageLength: 10,
          lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50,100, "Tất cả"]],
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
              let thieuDinhMuc = 0;
              let daDinhMuc = 0;

              settings.aoData.forEach(function(row) {
                  // Lấy ô Phòng Sản Xuất (ở cột thứ 5 — tính từ 0)
                  const roomCell = row.anCells[4];
                  const text = $(roomCell).text().trim();

                  if (text.includes('Thiếu Định Mức')) {
                      thieuDinhMuc++;
                  } else {
                      daDinhMuc++;
                  }
              });

              return pre + ` (Đã Định Mức: ${daDinhMuc}, Chưa Định Mức: ${thieuDinhMuc})`;
          }
        });

  });
</script>


