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
                      <h1><i class="fas fa-tasks text-primary"></i> Theo Dõi Thẩm Định</h1>
                  </div>
              </div>
          </div>
      </section>

      <section class="content">
          <div class="container-fluid">
              <div class="card card-primary card-outline card-outline-tabs">
                  <div class="card-header p-0 border-bottom-0">
                      <ul class="nav nav-tabs" id="trackingTabs" role="tablist">
                          <li class="nav-item">
                              <a class="nav-link active font-weight-bold" id="tab-material-tab" data-toggle="pill" href="#tab-material" role="tab" aria-controls="tab-material" aria-selected="true"><i class="fas fa-box-open text-primary"></i> Theo Nguyên Liệu Thẩm Định</a>
                          </li>
                          <li class="nav-item">
                              <a class="nav-link font-weight-bold" id="tab-product-tab" data-toggle="pill" href="#tab-product" role="tab" aria-controls="tab-product" aria-selected="false"><i class="fas fa-pills text-success"></i> Thống Kê Theo Bán Thành Phẩm</a>
                          </li>
                          <li class="nav-item">
                              <a class="nav-link font-weight-bold" id="tab-in-progress-tab" data-toggle="pill" href="#tab-in-progress" role="tab" aria-controls="tab-in-progress" aria-selected="false"><i class="fas fa-spinner fa-spin text-warning"></i> Kế Hoạch Đang Sản Xuất</a>
                          </li>
                      </ul>
                  </div>
                  <div class="card-body">
                      <div class="tab-content" id="trackingTabsContent">
                          <div class="tab-pane fade show active" id="tab-material" role="tabpanel" aria-labelledby="tab-material-tab">
                              @include('pages.plan.validation_tracking.dataTable')
                          </div>
                          <div class="tab-pane fade" id="tab-product" role="tabpanel" aria-labelledby="tab-product-tab">
                              @include('pages.plan.validation_tracking.product_dataTable')
                          </div>
                          <div class="tab-pane fade" id="tab-in-progress" role="tabpanel" aria-labelledby="tab-in-progress-tab">
                              @include('pages.plan.validation_tracking.in_progress_dataTable')
                          </div>
                      </div>
                  </div>
              </div>
          </div>
      </section>
  </div>
@endsection

@section('model')
  @include('pages.plan.validation_tracking.create')
  @include('pages.plan.validation_tracking.update')
  @include('pages.plan.validation_tracking.select_intermediate_category')
@endsection
