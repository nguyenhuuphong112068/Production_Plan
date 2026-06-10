<link href="{{ asset('css/bootstrap.min.css') }}" rel="stylesheet">
<div class="content-wrapper">
    <div class="card">
        <div class="card-header mt-4">
            <h3 class="card-title">Danh sách Khuôn Mẫu</h3>
        </div>
        <div class="card-body">
            <button class="btn btn-success btn-create mb-2" data-toggle="modal" data-target="#createModal" style="width: 155px">
                <i class="fas fa-plus"></i> Thêm mới
            </button>

            <table id="data_table_blister_mold" class="table table-bordered table-striped">
                <thead style="position: sticky; top: 60px; background-color: white; z-index: 1020">
                    <tr>
                        <th>STT</th>
                        <th>Mã Khuôn Mẫu</th>
                        <th>Số Lượng</th>
                        <th>Trạng Thái</th>
                        <th>Người Tạo</th>
                        <th>Ngày Tạo</th>
                        <th>Thao Tác</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($datas as $data)
                        <tr>
                            <td>{{ $loop->iteration }} </td>
                             <td>{{ $data->code }}</td>
                             <td>{{ $data->amount }}</td>
                            <td class="text-center">
                                @if($data->active)
                                    <span class="badge badge-success">Hoạt động</span>
                                @else
                                    <span class="badge badge-danger">Tạm ngưng</span>
                                @endif
                            </td>
                            <td>{{ $data->created_by ?? '-' }}</td>
                            <td>{{ \Carbon\Carbon::parse($data->created_at)->format('d/m/Y') }}</td>
                            <td class="text-center align-middle">
                                <button type="button" class="btn btn-warning btn-edit mb-1" 
                                    data-id="{{ $data->id }}" 
                                     data-code="{{ $data->code }}"
                                     data-amount="{{ $data->amount }}"
                                    data-toggle="modal" 
                                    data-target="#updateModal">
                                    <i class="fas fa-edit"></i>
                                </button>

                                <form class="form-deActive d-inline" action="{{ route('pages.materData.blister_mold.deActive') }}" method="POST">
                                    @csrf
                                    <input type="hidden" name="id" value="{{ $data->id }}">
                                    <input type="hidden" name="active" value="{{ $data->active }}">
                                    <button type="submit" class="btn btn-{{ $data->active ? 'danger' : 'success' }} btn-deactive-confirm" 
                                         data-code="{{ $data->code }}">
                                        <i class="fas fa-{{ $data->active ? 'lock' : 'unlock' }}"></i>
                                    </button>
                                </form>
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
            modal.find('#update_code').val(button.data('code'));
            modal.find('#update_amount').val(button.data('amount'));
        });

        $('.form-deActive').on('submit', function(e) {
            e.preventDefault();
            const form = this;
            const code = $(form).find('button').data('code');
            const active = $(form).find('button').data('active');
            const actionText = active ? 'vô hiệu hóa' : 'kích hoạt';

            Swal.fire({
                title: `Xác nhận ${actionText}?`,
                text: `Bạn có chắc chắn muốn ${actionText} khuôn mẫu: ${code}?`,
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

        $('#data_table_blister_mold').DataTable({
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
