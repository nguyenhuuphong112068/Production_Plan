
<link href="{{ asset('css/bootstrap.min.css') }}" rel="stylesheet">
<div class="content-wrapper">
            <div class="card">

              <div class="card-header mt-4">
                {{-- <h3 class="card-title">Ghi Chú Nếu Có</h3> --}}

              </div>

              <!-- /.card-Body -->
              <div class="card-body">
                @if (user_has_permission(session('user')['userId'], 'materData_unit_store', 'boolean'))
                  <button class="btn btn-success btn-create mb-2" data-toggle="modal" data-target="#create_modal" style="width: 155px" >
                        <i class="fas fa-plus"></i> Thêm
                  </button>
                @endif

                @php
                    $auth_update = user_has_permission(session('user')['userId'], 'materData_unit_update', 'disabled');
                @endphp

                <table id="data_tabale_unit" class="table table-bordered table-striped">

                  <thead style = "position: sticky; top: 60px; background-color: white; z-index: 1020" >
                
                    <tr>
                    <th>STT</th>
                    <th>Đơn Vị</th>
                    <th>Viết Tắt</th>
                    <th>Người Tạo/ Ngày Tạo</th>
                    <th>Cập Nhật</th>
                        <th class="text-center align-middle">Lịch Sử</th>
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

                              data-id="{{ $data->id }}"
                              data-name="{{ $data->name }}"
                              data-code="{{ $data->code }}"
                              {{ $auth_update }}
                              data-toggle="modal"
                              data-target="#update_modal">
                              <i class="fas fa-edit"></i>
                          </button>
                      </td>
                            <td class="text-center align-middle">
                                <button class="btn btn-info btn-history mb-1 position-relative" data-id="{{ $data->id }}" title="Lịch sử thay đổi">
                                    <i class="fas fa-history"></i>
                                    @if(isset($historyCounts) && isset($historyCounts[$data->id]))
                                        <span class="badge badge-danger" style="position: absolute; top: -5px; right: -5px; padding: 4px 6px; border-radius: 50%; font-size: 10px;">{{ $historyCounts[$data->id]->total }}</span>
                                    @endif
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
      document.body.style.overflowY = "auto";
      $('.btn-edit').click(function () {
          const button = $(this);
          const modal = $('#update_modal');

           // Gán dữ liệu vào input
          modal.find('input[name="id"]').val(button.data('id'));
          modal.find('input[name="name"]').val(button.data('name'));
          modal.find('input[name="code"]').val(button.data('code'));

      });

    

      $('#data_tabale_unit').DataTable({
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










<script>
    $(document).ready(function() {
        $('.btn-history').off('click').on('click', function() {
            var id = $(this).data('id');
            $.ajax({
                url: "{{ route('pages.materData.Unit.history') }}",
                type: "GET",
                data: { id: id },
                success: function(res) {
                    var tbody = $('#data_table_history_body');
                    tbody.empty();
                    var current = res.current;
                    if (current) {
                        var modifier = current.created_by || current.prepareBy || current.prepared_by || '';
                        var html = '<tr style="background-color: #e8f4f8; font-weight: bold;">';
                        html += '<td class="text-center align-middle">Hiện Hành</td>';
                        html += '<td class="text-center align-middle">' + modifier + '</td>';
                        html += '<td class="text-center align-middle">' + (current.active !== null && current.active !== undefined ? current.active : '') + '</td>';
                        html += '<td class="text-center align-middle">' + (current.name !== null && current.name !== undefined ? current.name : '') + '</td>';
                        html += '<td class="text-center align-middle">' + (current.code !== null && current.code !== undefined ? current.code : '') + '</td>';
                        html += '</tr>';
                        tbody.append(html);
                    }

                    if(res.history.length === 0) {
                        tbody.append('<tr><td colspan="100%" class="text-center align-middle">Chưa có lịch sử thay đổi</td></tr>');
                    } else {
                        res.history.forEach(function(item) {
                            var modifier = item.created_by || item.prepareBy || item.prepared_by || '';
                            var html = '<tr>';
                            html += '<td class="text-center align-middle">' + (item.updated_at ? item.updated_at : item.created_at) + '</td>';
                            html += '<td class="text-center align-middle">' + modifier + '</td>';
                            html += '<td class="text-center align-middle">' + (item.active !== null && item.active !== undefined ? item.active : '') + '</td>';
                            html += '<td class="text-center align-middle">' + (item.name !== null && item.name !== undefined ? item.name : '') + '</td>';
                            html += '<td class="text-center align-middle">' + (item.code !== null && item.code !== undefined ? item.code : '') + '</td>';
                            html += '</tr>';
                            tbody.append(html);
                        });
                    }
                    $('#historyModal').modal('show');
                },
                error: function() {
                    Swal.fire('Lỗi', 'Không thể lấy lịch sử thay đổi', 'error');
                }
            });
        });
    });
</script>
