@extends ('layout.master')

@section('topNAV')
    @include('layout.topNAV')
@endsection

@section('leftNAV')
    @include('layout.leftNAV')
@endsection
 
@section('mainContent')
<link href="{{ asset('css/bootstrap.min.css') }}" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0 font-weight-bold text-success">Phân Công Bảo Trì</h1>
                </div>
                <div class="col-sm-6 text-right">
                    <form action="{{ route('pages.assignment.maintenance.index') }}" method="GET" class="form-inline float-right">
                        <label class="mr-2 h6 mb-0">Chọn ngày:</label>
                        <input type="date" name="reportedDate" value="{{ $reportedDate }}" class="form-control mr-2 shadow-sm" onchange="this.form.submit()">
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="card mx-2 shadow-sm">
        <div class="card-header bg-success py-2">
            <h3 class="card-title">Chi Tiết Bảo Trì - Hiệu Chuẩn Ngày {{ \Carbon\Carbon::parse($reportedDate)->format('d/m/Y') }}</h3>
            <div class="card-tools">
                <button type="button" class="btn btn-tool" data-card-widget="collapse">
                    <i class="fas fa-minus text-white"></i>
                </button>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive" style="max-height: 75vh; overflow-y: auto;">
                <table id="maintenance_assignment_table" class="table table-bordered table-striped table-hover mb-0">
                    <thead class="text-center font-weight-bold bg-light" style="position: sticky; top: 0; z-index: 10;">
                        <tr>
                            <th style="width: 40px">#</th>
                            <th style="width: 180px">Phòng SX / Khu Vực</th>
                            <th style="width: 120px">Ca Làm Việc</th>
                            <th>Nội Dung Công Việc</th>
                            <th style="width: 280px">Người Thực Hiện</th>
                            <th>Ghi Chú</th>
                            <th style="width: 80px">Lưu</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($tasks as $task)
                            <tr data-id="{{ $task->sp_id }}">
                                <td class="text-center align-middle">{{ $loop->iteration }}</td>
                                <td class="align-middle"><strong>{{ $task->room_name }}</strong></td>
                                <td class="align-middle text-center">
                                    @if($task->sp_id)
                                        <select class="form-control select-shift shadow-sm">
                                            <option value="Ca 1" {{ $task->current_shift == 'Ca 1' ? 'selected' : '' }}>Ca 1</option>
                                            <option value="Ca 2" {{ $task->current_shift == 'Ca 2' ? 'selected' : '' }}>Ca 2</option>
                                            <option value="Ca 3" {{ $task->current_shift == 'Ca 3' ? 'selected' : '' }}>Ca 3</option>
                                            <option value="HC" {{ $task->current_shift == 'HC' ? 'selected' : '' }}>HC</option>
                                            <option value="Tăng ca" {{ $task->current_shift == 'Tăng ca' ? 'selected' : '' }}>Tăng ca</option>
                                        </select>
                                    @else
                                        <span class="text-muted small">---</span>
                                    @endif
                                </td>
                                <td class="align-middle">
                                    @if($task->sp_id)
                                        <span class="badge badge-{{ $task->type_name == 'Hiệu chuẩn' ? 'info' : ($task->type_name == 'Tiện ích' ? 'success' : 'warning') }} mb-1">
                                            {{ $task->type_name }}
                                        </span>
                                        <div><strong>{{ $task->Eqp_name }}</strong></div>
                                        <div class="text-muted small">{{ $task->inst_name }} - Lô: {{ $task->batch }}</div>
                                    @else
                                        <div class="text-center text-muted italic small">Không có lịch sắp</div>
                                    @endif
                                </td>
                                <td class="align-middle">
                                    @if($task->sp_id)
                                        <select class="form-control select2-personnel shadow-sm" multiple="multiple" style="width: 100%">
                                            @foreach($personnel as $person)
                                                <option value="{{ $person->id }}" 
                                                    @if($task->assigned_personnel->contains('id', $person->id)) selected @endif>
                                                    {{ $person->code }} - {{ $person->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    @endif
                                </td>
                                <td class="align-middle text-center">
                                    @if($task->sp_id)
                                        <input type="text" class="form-control input-note shadow-sm" value="{{ $task->current_note }}" placeholder="Nhập ghi chú...">
                                    @endif
                                </td>
                                <td class="text-center align-middle">
                                    @if($task->sp_id)
                                        <button class="btn btn-primary btn-sm btn-save-assignment shadow" data-id="{{ $task->sp_id }}" title="Lưu phân công">
                                            <i class="fas fa-save"></i>
                                        </button>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">Không có lịch bảo trì cho ngày này.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="{{ asset('js/vendor/jquery-1.12.4.min.js') }}"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="{{ asset('js/sweetalert2.all.min.js') }}"></script>

<script>
    $(document).ready(function() {
        $('.select2-personnel').select2({
            placeholder: "Chọn nhân sự...",
            allowClear: true
        });

        $('.btn-save-assignment').click(function() {
            const btn = $(this);
            const taskId = btn.data('id');
            const row = btn.closest('tr');
            const personnelIds = row.find('.select2-personnel').val();
            const shift = row.find('.select-shift').val();
            const note = row.find('.input-note').val();

            btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');

            $.ajax({
                url: "{{ route('pages.assignment.maintenance.store') }}",
                method: "POST",
                data: {
                    _token: "{{ csrf_token() }}",
                    stage_plan_id: taskId,
                    personnel_ids: personnelIds,
                    shift: shift,
                    note: note
                },
                success: function(response) {
                    if (response.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Thành công',
                            text: response.message,
                            timer: 1000,
                            showConfirmButton: false,
                            toast: true,
                            position: 'top-end'
                        });
                    } else {
                        Swal.fire('Lỗi', response.message, 'error');
                    }
                },
                error: function(xhr) {
                    Swal.fire('Lỗi', 'Không thể lưu phân công.', 'error');
                },
                complete: function() {
                    btn.prop('disabled', false).html('<i class="fas fa-save"></i>');
                }
            });
        });
    });
</script>
@endsection
