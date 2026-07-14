@extends ('layout.master')

@section('topNAV')
    @include('layout.topNAV')
@endsection

@section('leftNAV')
    @include('layout.leftNAV')
@endsection

@section('mainContent')
    <div class="content-wrapper">
        <div class="p-3">
            <div class="card card-success mt-5">
                <div class="card-header">
                    <h3 class="card-title">Danh sách Kế Hoạch Năm</h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-tool" data-card-widget="collapse">
                            <i class="fas fa-minus"></i>
                        </button>
                    </div>
                </div>
                
                <div class="card-body" style="max-height: 95vh; overflow-y: auto;">
                    <div class="d-flex flex-wrap align-items-center justify-content-between mb-4 p-3"
                        style="background-color: #f8f9fa; border: 1px solid #e9ecef; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.04);">
                        <div class="mb-2 mb-xl-0 pr-4">
                            <button class="btn btn-success btn-create" data-toggle="modal"
                                data-target="#modalCreatePlan"
                                style="height: 40px; border-radius: 6px; font-weight: 600; letter-spacing: 0.3px; padding: 0 20px; box-shadow: 0 2px 4px rgba(40,167,69,0.2);">
                                <i class="fas fa-plus-circle mr-2"></i>Thêm Kế Hoạch
                            </button>
                        </div>
                    </div>

                    @if (session('success'))
                        <div class="alert alert-success mb-3">{{ session('success') }}</div>
                    @endif

                    <table class="table table-bordered table-striped" style="font-size: 20px">
                        <thead>
                            <tr>
                                <th>Năm</th>
                                <th>Ghi chú</th>
                                <th>Người tạo</th>
                                <th>Ngày tạo</th>
                                <th class="text-center">Chi Tiết</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($plans as $plan)
                                <tr>
                                    <td>{{ $plan->year }}</td>
                                    <td>{{ $plan->description }}</td>
                                    <td>{{ $plan->created_by }}</td>
                                    <td>{{ $plan->created_at->format('d/m/Y') }}</td>
                                    <td class="text-center align-middle">
                                        <a href="{{ route('pages.plan.annual.show', $plan->id) }}"
                                            class="btn btn-success"><i class="fas fa-eye"></i></a>
                                    </td>
                                </tr>
                            @endforeach
                            @if ($plans->isEmpty())
                                <tr>
                                    <td colspan="5" class="text-center">Chưa có kế hoạch nào</td>
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
