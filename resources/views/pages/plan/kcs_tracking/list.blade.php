@extends ('layout.master')

@section('topNAV')
    @include('layout.topNAV')
@endsection

@section('leftNAV')
    @include('layout.leftNAV')
@endsection

@section('mainContent')
    <div class="content-wrapper">
        <section class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1><i class="fas fa-clipboard-check text-primary"></i> Theo Dõi Hồ Sơ KCS</h1>
                    </div>
                </div>
            </div>
        </section>

        <section class="content">
            <div class="container-fluid">
                <div class="card card-primary card-outline card-outline-tabs">
                    <div class="card-header p-0 border-bottom-0">
                        <ul class="nav nav-tabs" id="kcsTabs" role="tablist">
                            <li class="nav-item">
                                <a class="nav-link active font-weight-bold" id="tab-records-tab" data-toggle="pill"
                                    href="#tab-records" role="tab" aria-controls="tab-records" aria-selected="true">
                                    <i class="fas fa-list text-primary"></i> Theo Dõi Hồ Sơ
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link font-weight-bold" id="tab-summary-tab" data-toggle="pill"
                                    href="#tab-summary" role="tab" aria-controls="tab-summary" aria-selected="false">
                                    <i class="fas fa-chart-pie text-success"></i> Tổng Kết Tỉ Lệ
                                </a>
                            </li>
                        </ul>
                    </div>
                    <div class="card-body">
                        <div class="tab-content" id="kcsTabsContent">
                            <div class="tab-pane fade show active" id="tab-records" role="tabpanel"
                                aria-labelledby="tab-records-tab">
                                @include('pages.plan.kcs_tracking.dataTable')
                            </div>
                            <div class="tab-pane fade" id="tab-summary" role="tabpanel" aria-labelledby="tab-summary-tab">
                                <div id="summary_container">
                                    @include('pages.plan.kcs_tracking.summary')
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
@endsection
