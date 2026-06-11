<link href="{{ asset('css/bootstrap.min.css') }}" rel="stylesheet">
<div class="content-wrapper">
    <div class="card">
        <div class="card-header mt-4">
            {{-- <h3 class="card-title">Ghi Chú Nếu Có</h3> --}}
        </div>
        <div class="card-body">


            <button class="btn btn-success btn-create mb-2" data-toggle="modal" data-target="#create_modal"
                style="width: 155px">
                <i class="fas fa-plus"></i> Thêm
            </button>


            <table id="data_tabale_blister_type" class="table table-bordered table-striped">
                <thead style = "position: sticky; top: 60px; background-color: white; z-index: 1020">
                    <tr>
                        <th>STT</th>
                        <th>Loại Máy Ép Vỉ</th>
                        <th>Mã Nhóm Máy</th>
                        <th>Người Tạo/ Ngày Tạo</th>
                        <th>Trạng Thái</th>
                        <th>Cập Nhật</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($datas as $data)
                        <tr>
                            <td>{{ $loop->iteration }} </td>
                            <td>{{ $data->name }}</td>
                            <td>
                                @php
                                    $groupNames = collect($datas)->where('code', $data->code)->pluck('name')->join(', ');
                                @endphp
                                {{ $data->code }} ({{ $groupNames }})
                            </td>
                            <td>
                                <div> {{ $data->created_by }} </div>
                                <div>{{ \Carbon\Carbon::parse($data->created_at)->format('d/m/Y') }} </div>
                            </td>
                            <td class="text-center align-middle">
                                <form action="{{ route('pages.materData.blister_type.deActive') }}" method="POST"
                                    style="display:inline;">
                                    @csrf
                                    <input type="hidden" name="id" value="{{ $data->id }}">
                                    @if ($data->active)
                                        <button type="submit" class="btn btn-success btn-sm"
                                            onclick="return confirm('Bạn có chắc muốn vô hiệu hóa Loại máy ép vỉ này?');">Đang
                                            Hoạt Động</button>
                                    @else
                                        <button type="submit" class="btn btn-secondary btn-sm"
                                            onclick="return confirm('Bạn có chắc muốn kích hoạt Loại máy ép vỉ này?');">Đã
                                            Vô Hiệu</button>
                                    @endif
                                </form>
                            </td>
                            <td class="text-center align-middle">
                                <button type="button" class="btn btn-warning btn-edit " data-id="{{ $data->id }}"
                                    data-name="{{ $data->name }}" data-code="{{ $data->code }}" data-toggle="modal" data-target="#update_modal">
                                    <i class="fas fa-edit"></i>
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
    $(document).ready(function() {
        document.body.style.overflowY = "auto";
        $('.btn-edit').click(function() {
            const button = $(this);
            const modal = $('#update_modal');



            // Gán dữ liệu vào input
            modal.find('input[name="id"]').val(button.data('id'));

            modal.find('input[name="name"]').val(button.data('name'));
            modal.find('input[name="code"]').val(button.data('code'));

            const id = button.data('id');

        });


        $('#data_tabale_blister_type').DataTable({
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
