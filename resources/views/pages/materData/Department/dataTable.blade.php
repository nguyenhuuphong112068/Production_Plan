<link href="{{ asset('css/bootstrap.min.css') }}" rel="stylesheet">
<div class="content-wrapper">
    <div class="card">
        <div class="card-header mt-4">
            <h3 class="card-title">Danh sách Phòng Ban</h3>
        </div>
        <div class="card-body">
            <button class="btn btn-success btn-create mb-2" data-toggle="modal" data-target="#createModal" style="width: 155px">
                <i class="fas fa-plus"></i> Thêm mới
            </button>

            <table id="data_table_department" class="table table-bordered table-striped">
                <thead style="position: sticky; top: 60px; background-color: white; z-index: 1020">
                    <tr>
                        <th>STT</th>
                        <th>Tên Viết Tắt</th>
                        <th>Tên Phòng Ban</th>
                        <th>Trạng Thái</th>
                        <th>Người Tạo</th>
                        <th>Ngày Tạo</th>
                        <th>Thao Tác</th>
                        <th class="text-center align-middle">Lịch Sử</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($datas as $data)
                        <tr>
                            <td>{{ $loop->iteration }} </td>
                            <td>{{ $data->shortName }}</td>
                            <td>{{ $data->name }}</td>
                            <td class="text-center">
                                @if($data->active)
                                    <span class="badge badge-success">Hoạt động</span>
                                @else
                                    <span class="badge badge-danger" style="position: absolute; top: -5px; right: -5px; padding: 4px 6px; border-radius: 50%; font-size: 10px;">Tạm ngưng</span>
                                @endif
                            </td>
                            <td>{{ $data->prepareBy ?? '-' }}</td>
                            <td>{{ \Carbon\Carbon::parse($data->created_at)->format('d/m/Y') }}</td>
                            <td class="text-center align-middle">
                                <button type="button" class="btn btn-warning btn-edit mb-1" 
                                    data-id="{{ $data->id }}" 
                                    data-shortname="{{ $data->shortName }}" 
                                    data-name="{{ $data->name }}"
                                    data-toggle="modal" 
                                    data-target="#updateModal">
                                    <i class="fas fa-edit"></i>
                                </button>

                                <form class="form-deActive d-inline" action="{{ route('pages.materData.department.deActive') }}" method="POST">
                                    @csrf
                                    <input type="hidden" name="id" value="{{ $data->id }}">
                                    <input type="hidden" name="active" value="{{ $data->active }}">
                                    <button type="submit" class="btn btn-{{ $data->active ? 'danger' : 'success' }} btn-deactive-confirm" 
                                        data-name="{{ $data->name }}" 
                                        data-active="{{ $data->active }}">
                                        <i class="fas fa-{{ $data->active ? 'lock' : 'unlock' }}"></i>
                                    </button>
                                </form>
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
    </div>
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
            timer: 1500,
            showConfirmButton: false
        });
    </script>
@endif

<script>
    $(document).ready(function() {
        document.body.style.overflowY = "auto";
        $('.btn-edit').click(function() {
            const button = $(this);
            const modal = $('#updateModal');

            modal.find('#update_id').val(button.data('id'));
            modal.find('#update_shortName').val(button.data('shortname'));
            modal.find('#update_name').val(button.data('name'));
        });

        $('.form-deActive').on('submit', function(e) {
            e.preventDefault();
            const form = this;
            const name = $(form).find('button').data('name');
            const active = $(form).find('button').data('active');
            const actionText = active ? 'vô hiệu hóa' : 'kích hoạt';

            Swal.fire({
                title: `Xác nhận ${actionText}?`,
                text: `Bạn có chắc chắn muốn ${actionText} phòng ban: ${name}?`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#28a745',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Đồng ý',
                cancelButtonText: 'Hủy'
            }).then((result) => {
                if (result.isConfirmed) {
                    form.submit();
                }
            });
        });

        $('#data_table_department').DataTable({
            paging: true,
            lengthChange: true,
            searching: true,
            ordering: true,
            info: true,
            autoWidth: false,
            pageLength: 25,
            lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "Tất cả"]],
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
                url: "{{ route('pages.materData.Department.history') }}",
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
                        html += '<td class="text-center align-middle">' + (current.code !== null && current.code !== undefined ? current.code : '') + '</td>';
                        html += '<td class="text-center align-middle">' + (current.name !== null && current.name !== undefined ? current.name : '') + '</td>';
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
                            html += '<td class="text-center align-middle">' + (item.code !== null && item.code !== undefined ? item.code : '') + '</td>';
                            html += '<td class="text-center align-middle">' + (item.name !== null && item.name !== undefined ? item.name : '') + '</td>';
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
