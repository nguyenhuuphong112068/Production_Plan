@extends ('layout.master')

@section('topNAV')
    @include('layout.topNAV')
@endsection

@section('leftNAV')
    @include('layout.leftNAV')
@endsection

@section('mainContent')
    <div class="content-wrapper">
        <div class="card" style="min-height: 100vh">
            <div class="card-header mt-4">
                <h3 class="card-title">Danh sách Kế Hoạch Năm</h3>
            </div>
            
            <div class="card-body">
                <div class="row">
                    <div class="col-md-2">
                        <button class="btn btn-success btn-add mb-2" data-toggle="modal"
                            data-target="#modalCreatePlan" style="width: 155px;">
                            <i class="fas fa-plus"></i> Tạo kế hoạch mới
                        </button>
                    </div>
                </div>

                @if (session('success'))
                    <div class="alert alert-success mt-2">{{ session('success') }}</div>
                @endif

                <table class="table table-hover table-bordered mt-3">
                    <thead class="thead-light">
                        <tr>
                            <th>Năm</th>
                            <th>Ghi chú</th>
                            <th>Ngày tạo</th>
                            <th class="text-center">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($plans as $plan)
                            <tr>
                                <td>{{ $plan->year }}</td>
                                <td>{{ $plan->description }}</td>
                                <td>{{ $plan->created_at->format('d/m/Y') }}</td>
                                <td class="text-center">
                                    <a href="{{ route('pages.plan.annual.show', $plan->id) }}"
                                        class="btn btn-sm btn-info"><i class="fas fa-eye"></i> Chi tiết</a>
                                </td>
                            </tr>
                        @endforeach
                        @if ($plans->isEmpty())
                            <tr>
                                <td colspan="4" class="text-center">Chưa có kế hoạch nào</td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
    </div>

        <!-- Modal Create -->
        <div class="modal fade" id="modalCreatePlan" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered" role="document">
                <div class="modal-content">
                    <form action="{{ route('pages.plan.annual.store') }}" method="POST">
                        @csrf
                        <div class="modal-header">
                            <h6 class="modal-title">Tạo kế hoạch năm mới</h6>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <div class="form-group">
                                <label>Năm <span class="text-danger">*</span></label>
                                <input type="number" name="year" class="form-control" required min="2020"
                                    max="2100" value="{{ date('Y') + 1 }}">
                            </div>
                            <div class="form-group">
                                <label>Ghi chú</label>
                                <textarea name="description" class="form-control" rows="3"></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Hủy</button>
                            <button type="submit" class="btn btn-primary">Tạo mới</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
