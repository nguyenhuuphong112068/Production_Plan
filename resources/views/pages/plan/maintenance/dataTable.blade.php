

<div class="content-wrapper">
    <!-- Main content -->
    <section class="content">
          <div class="col-12">
            <!-- /.card-header -->
            <div class="card">

              <div class="card-header mt-4">
                {{-- <h3 class="card-title">Ghi Chú Nếu Có</h3> --}}
              </div>

              <!-- /.card-Body -->
              <div class="card-body">
                @if(!$send)
                <div class="row" >
                    <div class="col-md-2">
                      <button class="btn btn-success btn-create mb-2" data-toggle="modal" data-target="#selectProductModal" style="width: 155px;">
                            <i class="fas fa-plus"></i> Thêm
                      </button>
                    </div>
                    <div class="col-md-8"></div> 
                    <div class="col-md-2" style="text-align: right;">

                      <form id = "send_form" action="{{ route('pages.plan.maintenance.send') }}" method="post">

                            @csrf
                            <input type="hidden" name="plan_list_id" value="{{$plan_list_id}}">
                            <input type="hidden" name="month" value="{{$month}}"> 
                           
                            <button class="btn btn-success btn-create mb-2 "  style="width: 177px;">
                                <i id = "send_btn" class="fas fa-paper-plane"></i> Gửi
                            </button>
                      </form>

                    </div>
                </div>
                @endif   
                <table id="data_table_plan_master" class="table table-bordered table-striped" style="font-size: 20px">

                  <thead style = "position: sticky; top: 60px; background-color: white; z-index: 1020" >
                
                    <tr>
                        <th>STT</th>
                        <th>Mã Thiết Bi</th>
                        <th>Tên Thiết Bị</th>
                        <th>Thực Hiện Trước Ngày</th>
                        <th>Phòng SX Liên Quan</th>
                        <th>Ghi Chú</th>
                        <th>Người Tạo/ Ngày Tạo</th>
                        <th style="width:1%">Cập Nhật</th>
                        <th style="width:1%">Vô Hiệu</th>
                        <th style="width:1%">Lịch Sữ</th>
                    </tr>
                  </thead>
                  <tbody>
                 
                  @foreach ($datas as $data)
                    <tr>
                      <td>{{ $loop->iteration}} </td>

                      @if (!$data->cancel)
                        <td class="text-success"> 
                            <div> {{ $data->code}} </div>
                        </td>
                      @else
                        <td class="text-danger"> 
                            <div> {{ $data->code}} </div>
                        </td>
                      @endif

                      <td>{{ $data->name}}</td>

                      <td>
                          <div> {{ \Carbon\Carbon::parse($data->expected_date)->format('d/m/Y') }} </div>
                      </td>

                      <td> {{ $data->rooms}} </td>
                       
                      <td> {{ $data->note}} </td>

                      <td>
                          <div> {{ $data->prepared_by}} </div>
                          <div>{{ \Carbon\Carbon::parse($data->created_at)->format('d/m/Y') }} </div>
                      </td>                     

  
                      
                      <td class="text-center align-middle">
                          <button type="button" class="btn btn-warning btn-edit" {{ $data->active?'':'disabled' }}

                              data-id="{{ $data->id }}"
                              data-name="{{ $data->name }}"
                              data-code="{{ $data->code }}"
                              data-expected_date="{{ $data->expected_date}}"
                              data-note="{{ $data->note }}"
                              data-toggle="modal"
                              data-target="#updateModal">
                              <i class="fas fa-edit"></i>
                          </button>
                      </td>
                      
                      <td class="text-center align-middle">  

                        <form class="form-deActive" action="{{ route('pages.plan.maintenance.deActive') }}" method="post">
                             @csrf
                            <input type="hidden"  name="id" value = "{{ $data->id }}">
                            <input type="hidden"  name="active" value="{{ $data->active }}">

                            @if ($data->active == true && $send == false)
                              <button type="submit" class="btn btn-danger" data-type="delete" data-name="{{  $data->name }}">
                                  <i class="fas fa-trash"></i>
                              </button>  
                            @elseif ($data->cancel == false && $send == true)
                              <button type="submit" class="btn btn-danger"   data-type="cancel"  data-name="{{  $data->name }}">
                                  <i class="fas fa-lock"></i>
                              </button>
                            @elseif ($data->cancel == true && $send == true)
                              <button type="submit" class="btn btn-success"  data-type="restore"  data-name="{{  $data->name }}">
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
                                {{ $data->history_count ?? 0 }}
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
            <!-- /.card -->
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
        timer: 1000, // tự đóng sau 2 giây
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

          // Gán dữ liệu vào input
          modal.find('input[name="id"]').val(button.data('id'));
          modal.find('input[name="name"]').val(button.data('name'));
          modal.find('input[name="intermediate_code"]').val(button.data('intermediate_code'));
          modal.find('input[name="finished_product_code"]').val(button.data('finished_product_code'));
          modal.find('input[name="batch"]').val(button.data('batch'));
          modal.find('input[name="material_source_id"]').val(button.data('material_source_id'));

          modal.find('textarea[name="source_material_name"]').val(button.data('source_material_name'));
          modal.find('input[name="after_weigth_date"]').val(button.data('after_weigth_date'));                            
          modal.find('input[name="before_weigth_date"]').val(button.data('before_weigth_date'));
          modal.find('input[name="after_parkaging_date"]').val(button.data('after_parkaging_date'));                            
          modal.find('input[name="before_parkaging_date"]').val(button.data('before_parkaging_date'));          
          modal.find('textarea[name="note"]').val(button.data('note')); 

          modal.find('input[name="batch_qty"]').val(button.data('batch_qty') + " - " + button.data('unit_batch_qty'));
          modal.find('input[name="specification"]').val(button.data('market') + " - " + button.data('specification'));
          modal.find('input[name="number_of_unit"]').attr('max', button.data('batch_qty'));
          modal.find('input[name="max_number_of_unit"]').val(button.data('batch_qty'));
          modal.find('input[name="number_of_unit"]').val(button.data('batch_qty'));
          modal.find('input[name="expected_date"]').val(button.data('expected_date'));
          modal.find('input[name="is_val"]').prop('checked', button.data('is_val')).val(button.data('is_val'));

          modal.find('input[name="level"][value="' + button.data('level') + '"]').prop('checked', true);

        });

        $('.btn-create').click(function () {
          const modal = $('#productNameModal');
        });

        $('.form-deActive').on('submit', function (e) {
            e.preventDefault(); // chặn submit mặc định
            const form = this;
            const productName = $(form).find('button[type="submit"]').data('name');
            const type = $(form).find('button[type="submit"]').data('type');
           
            if (type == "delete"){
              title = "Bạn chắc chắn muốn xóa kế hoạch?"
            }else if (type == "cancel"){
              title = "Bạn chắc chắn muốn hủy kế hoạch?"
            }else {
              title = "Bạn chắc chắn muốn phục hồi kế hoạch?"
            }

            Swal.fire({
                title: title,
                text: `Sản phẩm: ${productName}`,
                icon: 'warning',
                input: 'textarea',                   // ô nhập lý do
                inputPlaceholder: 'Nhập lý do hủy...',
                inputAttributes: {
                    'aria-label': 'Nhập lý do hủy'
                },
                showCancelButton: true,
                confirmButtonColor: '#28a745',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Đồng ý',
                cancelButtonText: 'Hủy',
                preConfirm: (reason) => {
                    if (!reason) {
                        Swal.showValidationMessage('Bạn phải nhập lý do');
                    }
                    return reason;
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    // Tạo 1 input hidden trong form để gửi lý do
                    $('<input>').attr({
                        type: 'hidden',
                        name: 'deactive_reason',
                        value: result.value
                    }).appendTo(form);
                    $('<input>').attr({
                        type: 'hidden',
                        name: 'type',
                        value: type
                    }).appendTo(form);

                    form.submit(); // submit sau khi xác nhận
                }
            });
        });

        $('.btn-history').on('click', function () {
            const planMasterId = $(this).data('id');
            const history_modal = $('#data_table_history_body')
            
            // Xóa dữ liệu cũ
            history_modal.empty();

            // Gọi Ajax lấy dữ liệu history
          $.ajax({
              url: "{{ route('pages.plan.maintenance.history') }}",
              type: 'post',
              data: {
                      id: planMasterId, 
                      _token: "{{ csrf_token() }}"
                  },
              success: function (res) {
                  if (res.length === 0) {
                      history_modal.append(
                          `<tr><td colspan="13" class="text-center">Không có lịch sử</td></tr>`
                      );
                  } else {
                      res.forEach((item, index) => {
                          // map màu level
                          const colors = {
                              1: 'background-color: #f44336; color: white;',   // đỏ
                              2: 'background-color: #ff9800; color: white;',   // cam
                              3: 'background-color: blue; color: white;',      // xanh dương
                              4: 'background-color: #4caf50; color: white;',   // xanh lá
                          };
                          history_modal.append(`
                              <tr>
                                  <td>${index + 1}</td>
                                  <td class="${index === 0 ? 'text-success' : 'text-danger'}""> 
                                      <div>${item.intermediate_code ?? ''}</div>
                                      <div>${item.finished_product_code ?? ''}</div>
                                  </td>

                                  <td>${item.name ?? ''} (${item.batch_qty ?? ''} ${item.unit_batch_qty ?? ''})</td>
                                  <td>${item.batch ?? ''}</td>
                                  <td>
                                      <div>${item.market ?? ''}</div>
                                      <div>${item.specification ?? ''}</div>
                                  </td>

                                  <td style="text-align: center; vertical-align: middle;">
                                      <span style="display: inline-block; padding: 6px 10px; width: 50px; border-radius: 40px; ${colors[item.level] ?? ''}">
                                          <b>${item.level ?? ''}</b>
                                      </span>
                                  </td>

                                  <td>
                                      <div>${item.expected_date ? moment(item.expected_date).format('DD/MM/YYYY') : ''}</div>
                                  </td>

                                  <td class="text-center align-middle">
                                      ${item.is_val ? '<i class="fas fa-check-circle text-primary fs-4"></i>' : ''}
                                  </td>

                                  <td>${item.source_material_name ?? ''}</td>

                                  <td>
                                      <div>${item.after_weigth_date ? moment(item.after_weigth_date).format('DD/MM/YYYY') : ''}</div>
                                      <div>${item.before_weigth_date ? moment(item.before_weigth_date).format('DD/MM/YYYY') : ''}</div>
                                  </td>
                                  <td>
                                      <div>${item.after_parkaging_date ? moment(item.after_parkaging_date).format('DD/MM/YYYY') : ''}</div>
                                      <div>${item.before_parkaging_date ? moment(item.before_parkaging_date).format('DD/MM/YYYY') : ''}</div>
                                  </td>

                                  <td>${item.note ?? ''}</td>
                                  <td>${item.version ?? ''}</td>
                                  <td >${item.reason ?? ''}</td>

                                  <td>
                                      <div>${item.prepared_by ?? ''}</div>
                                      <div>${item.created_at ? moment(item.created_at).format('DD/MM/YYYY') : ''}</div>
                                  </td>
                              </tr>
                          `);
                      });
                  }
              },
              error: function () {
                  history_modal.append(
                      `<tr><td colspan="13" class="text-center text-danger">Lỗi tải dữ liệu</td></tr>`
                  );
              }
          }); 
        });

        $('#data_table_plan_master').DataTable({
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


