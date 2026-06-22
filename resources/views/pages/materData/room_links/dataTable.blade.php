<link href="{{ asset('css/bootstrap.min.css') }}" rel="stylesheet">
<div class="content-wrapper">
    <div class="card">
        <div class="card-header mt-4">
            <h3 class="card-title text-uppercase text-bold mb-0">Danh Sách Liên Kết Phòng</h3>
        </div>

        <div class="card-body">
            @if (session('success'))
                <div class="alert alert-success alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                    <h5><i class="icon fas fa-check"></i> Thông báo!</h5>
                    {{ session('success') }}
                </div>
            @endif
            @if (session('error'))
                <div class="alert alert-danger alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                    <h5><i class="icon fas fa-ban"></i> Lỗi!</h5>
                    {{ session('error') }}
                </div>
            @endif
            @if ($errors->hasBag('createErrors'))
                <div class="alert alert-danger alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                    <h5><i class="icon fas fa-ban"></i> Lỗi Thêm Mới!</h5>
                    <ul>
                        @foreach ($errors->createErrors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
            @if ($errors->hasBag('updateErrors'))
                <div class="alert alert-danger alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                    <h5><i class="icon fas fa-ban"></i> Lỗi Cập Nhật!</h5>
                    <ul>
                        @foreach ($errors->updateErrors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if (user_has_permission(session('user')['userId'], 'materData_room_links', 'boolean'))
                <button class="btn btn-success btn-create mb-2" data-toggle="modal" data-target="#modal-create"
                    style="width: 155px">
                    <i class="fas fa-plus"></i> Thêm mới
                </button>
            @endif

            <table id="example1" class="table table-bordered table-striped text-center">
                <thead style="position: sticky; top: 60px; background-color: white; z-index: 1020">
                    <tr>
                        <th style="width: 5%">STT</th>
                        <th>Phòng Nguồn (Pha Chế)</th>
                        <th>Phòng Đích (Trộn Hoàn Tất)</th>
                        <th>Trạng Thái</th>
                        <th>Hành Động</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($datas as $key => $data)
                        <tr>
                            <td>{{ $key + 1 }}</td>
                            <td><span class="badge bg-primary">{{ $data->sourceRoom->name ?? '' }}
                                    ({{ $data->sourceRoom->code ?? '' }})
                                </span></td>
                            <td><i class="fas fa-arrow-right mx-2 text-muted"></i><span
                                    class="badge bg-success">{{ $data->targetRoom->name ?? '' }}
                                    ({{ $data->targetRoom->code ?? '' }})</span></td>
                            <td class="text-center">
                                @if ($data->active)
                                    <span class="badge badge-success">Đang kích hoạt</span>
                                @else
                                    <span class="badge badge-danger"
                                        style="position: absolute; top: -5px; right: -5px; padding: 4px 6px; border-radius: 50%; font-size: 10px;">Vô
                                        hiệu hóa</span>
                                @endif
                            </td>
                            <td class="text-center align-middle">
                                @if (user_has_permission(session('user')['userId'], 'materData_room_links', 'boolean'))
                                    <button type="button" class="btn btn-warning btn-edit mb-1"
                                        onclick="editLink({{ $data->id }}, {{ $data->source_room_id }}, {{ $data->target_room_id }})">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <form action="{{ route('pages.materData.room_links.deActive') }}" method="POST"
                                        style="display:inline-block">
                                        @csrf
                                        <input type="hidden" name="id" value="{{ $data->id }}">
                                        <input type="hidden" name="active" value="{{ $data->active }}">
                                        <button type="submit"
                                            class="btn {{ $data->active ? 'btn-danger' : 'btn-success' }} btn-deactive-confirm mb-1"
                                            onclick="return confirm('Bạn có chắc chắn muốn {{ $data->active ? 'vô hiệu hóa' : 'kích hoạt' }} liên kết này?')">
                                            <i class="fas {{ $data->active ? 'fa-lock' : 'fa-unlock' }}"></i>
                                        </button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Create -->
<div class="modal fade" id="modal-create">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-navy">
                <h4 class="modal-title">THÊM MỚI LIÊN KẾT PHÒNG</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="{{ route('pages.materData.room_links.store') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="form-group">
                        <label>Phòng Nguồn (Pha Chế) <span class="text-danger">*</span></label>
                        <select name="source_room_id" class="form-control" required>
                            <option value="">-- Chọn phòng Pha Chế --</option>
                            @foreach ($sourceRooms as $room)
                                <option value="{{ $room->id }}">{{ $room->name }} ({{ $room->code }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Phòng Đích (Trộn Hoàn Tất) <span class="text-danger">*</span></label>
                        <select name="target_room_id" class="form-control" required>
                            <option value="">-- Chọn phòng Trộn Hoàn Tất --</option>
                            @foreach ($targetRooms as $room)
                                <option value="{{ $room->id }}">{{ $room->name }} ({{ $room->code }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="modal-footer justify-content-between">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Đóng</button>
                    <button type="submit" class="btn btn-primary">Lưu Lại</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Update -->
<div class="modal fade" id="modal-update">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-info">
                <h4 class="modal-title">CẬP NHẬT LIÊN KẾT PHÒNG</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="{{ route('pages.materData.room_links.update') }}" method="POST">
                @csrf
                <input type="hidden" name="id" id="update_id">
                <div class="modal-body">
                    <div class="form-group">
                        <label>Phòng Nguồn (Pha Chế) <span class="text-danger">*</span></label>
                        <select name="source_room_id" id="update_source_room_id" class="form-control" required>
                            <option value="">-- Chọn phòng Pha Chế --</option>
                            @foreach ($sourceRooms as $room)
                                <option value="{{ $room->id }}">{{ $room->name }} ({{ $room->code }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Phòng Đích (Trộn Hoàn Tất) <span class="text-danger">*</span></label>
                        <select name="target_room_id" id="update_target_room_id" class="form-control" required>
                            <option value="">-- Chọn phòng Trộn Hoàn Tất --</option>
                            @foreach ($targetRooms as $room)
                                <option value="{{ $room->id }}">{{ $room->name }} ({{ $room->code }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="modal-footer justify-content-between">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Đóng</button>
                    <button type="submit" class="btn btn-info">Cập Nhật</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function editLink(id, source_id, target_id) {
        document.getElementById('update_id').value = id;
        document.getElementById('update_source_room_id').value = source_id;
        document.getElementById('update_target_room_id').value = target_id;
        $('#modal-update').modal('show');
    }

    $(function() {
        $("#example1").DataTable({
            "responsive": true,
            "lengthChange": true,
            "autoWidth": false,
            "buttons": ["copy", "csv", "excel", "pdf", "print", "colvis"]
        }).buttons().container().appendTo('#example1_wrapper .col-md-6:eq(0)');
    });
</script>
