<style>
    /* Xám: chưa sắp lịch */
  .step-pending .bs-stepper-circle {
    background-color: #6c757d !important; /* Bootstrap gray */
    color: white;
  }

  /* Xanh lá: đã sắp lịch */
  .step-scheduled .bs-stepper-circle {
    background-color: #28a745 !important; /* Bootstrap green */
    color: white;
  }

  /* Xanh dương: đã hoàn thành */
  .step-finished .bs-stepper-circle {
    background-color: #007bff !important; /* Bootstrap blue */
    color: white;
  }

    /* Đỏ: Hoàn thành nhưng trễ hơn expected_date */
  .step-delay .bs-stepper-circle {
    background-color: #dc3545 !important; /* Bootstrap red */
    color: white;
  }

  .step-warning .bs-stepper-circle {
    background-color: #e39235 !important; /* Bootstrap red */
    color: white;
  }
</style>

<link rel="stylesheet" href="{{ asset('libs/bs-stepper/css/bs-stepper.min.css') }}">
<div class="content-wrapper">
    <!-- Main content -->
    <section class="content">
      <div class="container-fluid">
        <div class="row">
          <div class="col-12">
            <!-- /.card-header -->
            <div class="card">

              <div class="card-header mt-4">
                {{-- <h3 class="card-title">Ghi Chú Nếu Có</h3> --}}
              </div>
              <!-- /.card-Body -->
              <div class="card-body">
                 <div class="row">
                    <div class="col-md-4">
                       @php
                              use Carbon\Carbon;
                              $defaultFrom = Carbon::now()->subMonth()->toDateString();
                              $defaultTo   = Carbon::now()->toDateString();
                          @endphp

                          <form id="dateFilterForm" method="GET" action="{{ route('pages.Schedual.step.list') }}" class="d-flex gap-2">
                              <div class="form-group d-flex align-items-center mr-3">
                                  <label for="from_date" class="mr-2 mb-0">From:</label>
                                  <input type="date" id="from_date" name="from_date" value="{{ request('from_date') ?? $defaultFrom }}" class="form-control" />
                              </div>

                              <div class="form-group d-flex align-items-center mr-3">
                                  <label for="to_date" class="mr-2 mb-0">To:</label>
                                  <input type="date" id="to_date" name="to_date" value="{{ request('to_date') ?? $defaultTo }}" class="form-control" />
                              </div>
                          </form>

                    </div>

                    <div class="col-md-4 d-flex justify-content-center align-items-center">
                         
                    </div> 

                    <div class="col-md-4 d-flex justify-content-end">
                    </div>
                </div> 
                 
                
                <table id="example1" class="table table-bordered table-striped" style="font-size: 20px">
                  <thead style = "position: sticky; top: 60px; background-color: white; z-index: 1020" >
                    <tr>
                        <th>STT</th>
                        <th>Sản Phẩm</th>
                        <th>Dư Kiến KCS</th>
                        <th>Số lô</th>
                        <th>Tiến Trình</th>

                    </tr>
                  </thead>
                  <tbody>
                   
                      @foreach($datas as $plan_master_id => $stages)
                          @php $plan = $stages->first(); @endphp
                          <tr>
                              <td>{{ $loop->iteration }}</td>
                              <td>{{ $plan->product_name ."-". $plan->batch_qty . ' ' . $plan->unit_batch_qty}}</td>
                              <td>{{ \Carbon\Carbon::parse( $plan->expected_date)->format('d/m/Y') }}</td>
                              <td>{{ $plan->batch }}</td>
                            <td>
                               {{-- Stepper các stage --}}
                              <div id="stepper-{{ $plan_master_id }}" class="bs-stepper">
                                  <div class="bs-stepper-header" role="tablist">
                                      @foreach($stages as $stage)
                                          @php
                                              $stageKey = Str::slug($stage->stage_code, '-');

                                              // Xác định class theo trạng thái
                                              $statusClass = 'step-pending'; // mặc định: xám
                                              if ($stage->status == 'scheduled') {
                                                  $statusClass = 'step-scheduled'; // xanh lá
                                              } 

                                              if (!empty($stage->end) && \Carbon\Carbon::parse($stage->end)->gt(\Carbon\Carbon::parse($plan->expected_date))) {
                                                  $statusClass = 'step-delay';
                                              }

                                              // Điều kiện đặc biệt: stage_code = 1 và start KHÔNG nằm giữa after_weigth_date & before_weigth_date
                                              if ($stage->stage_code == 1 && $stage->start !== null && !(
                                                      !empty($stage->start) &&
                                                      !empty($plan->after_weigth_date) &&
                                                      !empty($plan->before_weigth_date) &&
                                                      \Carbon\Carbon::parse($stage->start)->between(
                                                          \Carbon\Carbon::parse($plan->after_weigth_date),
                                                          \Carbon\Carbon::parse($plan->before_weigth_date)
                                                      )
                                                  )
                                              ) {
                                                  $statusClass = 'step-warning'; // vàng
                                              }
                                              // Điều kiện đặc biệt: stage_code = 7 và start KHÔNG nằm giữa after_weigth_date & before_weigth_date
                                              if ($stage->stage_code >= 7 && $stage->start !== null && !(
                                                      !empty($stage->start) &&
                                                      !empty($plan->after_parkaging_date) &&
                                                      !empty($plan->before_parkaging_date) &&
                                                      \Carbon\Carbon::parse($stage->start)->between(
                                                          \Carbon\Carbon::parse($plan->after_parkaging_date),
                                                          \Carbon\Carbon::parse($plan->before_parkaging_date)
                                                      )
                                                  )
                                              ) {
                                                  $statusClass = 'step-warning'; // vàng
                                              }

                                              if ($stage->status == 'finished') {
                                                  $statusClass = 'step-finished'; // xanh dương
                                              }
                                          @endphp

                                          <div class="step {{ $loop->first ? 'active' : '' }} {{ $statusClass }}" 
                                              data-target="#step-{{ $plan_master_id }}-{{ $stageKey }}">

                                              <button type="button" class="step-trigger" role="tab" id="stepper-{{ $plan_master_id }}-trigger-{{ $stageKey }}">
                                                  <span class="bs-stepper-circle ">{{ $loop->iteration }} </span>
                                                  <span class="bs-stepper-label">
                                                      {{ $stage->stage_name}}
                                                      <small class="d-block">{{ \Carbon\Carbon::parse( $stage->start)->format('d/m/Y H:i')  }}</small>
                                                      <small class="d-block">{{ \Carbon\Carbon::parse( $stage->end)->format('d/m/Y H:i')  }}</small>
                                                      <small class="d-block">{{'yield: '. $stage->yields }} {{ $stage->stage_code<=4?"Kg":"ĐVL"}}</small>
                                                     
                                                  </span>
                                              </button>

                                          </div>

                                          @if(!$loop->last)
                                              <div class="line"></div>
                                          @endif
                                      @endforeach
                                  </div>
                              </div>

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
          <!-- /.col -->
        </div>
        <!-- /.row -->
      </div>
      <!-- /.container-fluid -->
    </section>
    <!-- /.content -->
  </div>


<script src="{{ asset('js/vendor/jquery-1.12.4.min.js') }}"></script>
<script src="{{ asset('js/popper.min.js') }}"></script>
<script src="{{ asset('js/bootstrap.min.js') }}"></script>
<script src="{{ asset('libs/bs-stepper/js/bs-stepper.min.js') }}"></script>

<script>
  document.addEventListener('DOMContentLoaded', function () {
      // Init tất cả stepper
      document.querySelectorAll('.bs-stepper').forEach(stepperEl => {
          new Stepper(stepperEl, { linear: false, animation: true });
      });
  });

</script>

<script>
    const form = document.getElementById('dateFilterForm');
    const fromInput = document.getElementById('from_date');
    const toInput = document.getElementById('to_date');

   [fromInput, toInput].forEach(input => {
        input.addEventListener('input', function () { 
            form.submit();
        });
    });
</script>
