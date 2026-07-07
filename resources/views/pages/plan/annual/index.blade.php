@extends ('layout.master')

@section('topNAV')
    @include('layout.topNAV')
@endsection

@section('leftNAV')
    @include('layout.leftNAV')
@endsection

@section('mainContent')
    <div class="content-wrapper">

        <div class="container-fluid pd-x-0 mt-5">
            <div class="d-sm-flex align-items-center justify-content-between mg-b-20 mg-lg-b-25 mg-xl-b-30">
                <div>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb breadcrumb-style1 mg-b-10">
                            <li class="breadcrumb-item"><a href="#">Kế Hoạch</a></li>
                            <li class="breadcrumb-item active" aria-current="page">Kế Hoạch Năm</li>
                        </ol>
                    </nav>

                </div>
                <div class="d-none d-md-block">
                    <button class="btn btn-sm pd-x-15 btn-primary btn-uppercase mg-l-5" data-toggle="modal"
                        data-target="#modalCreatePlan">
                        <i data-feather="plus" class="wd-10 mg-r-5"></i> Tạo kế hoạch mới
                    </button>
                </div>
            </div>

            @if (session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            <div class="row row-xs">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Năm</th>
                                        <th>Ghi chú</th>
                                        <th>Ngày tạo</th>
                                        <th>Thao tác</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($plans as $plan)
                                        <tr>
                                            <td>{{ $plan->year }}</td>
                                            <td>{{ $plan->description }}</td>
                                            <td>{{ $plan->created_at->format('d/m/Y') }}</td>
                                            <td>
                                                <a href="{{ route('pages.plan.annual.show', $plan->id) }}"
                                                    class="btn btn-sm btn-info">Chi tiết</a>
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
