<style>
  /* Màu trạng thái step */
  .step-pending .bs-stepper-circle {
    background-color: #6c757d !important; /* Xám */
    color: white;
  }
  .step-scheduled .bs-stepper-circle {
    background-color: #28a745 !important; /* Xanh lá */
    color: white;
  }
  .step-finished .bs-stepper-circle {
    background-color: #007bff !important; /* Xanh dương */
    color: white;
  }
  .step-delay .bs-stepper-circle {
    background-color: #dc3545 !important; /* Đỏ */
    color: white;
  }
  .step-warning .bs-stepper-circle {
    background-color: #e39235 !important; /* Cam cảnh báo */
    color: white;
  }

  /* Mũi tên pointer */
  .step.step-pointer .bs-stepper-circle::before {
    content: "";
    position: absolute;
    top: 0%;
    left: 50%;
    transform: translateX(-50%);
    width: 0;
    height: 0;
    border-left: 14px solid transparent;
    border-right: 14px solid transparent;
    border-top: 18px solid #007bff;
    filter: drop-shadow(0 2px 2px rgba(0,0,0,0.4));
  }

  /* Style riêng cho dòng tổng kết 5-6-7 */
  .timeline-info div {
    font-size: 14px;
    margin-bottom: 2px;
  }
  .timeline-info .text-success {
    font-weight: 600;
  }

  .waiting-label {
    width: 10%;
    border-top: 2px solid  ;
    margin-top: 14px;
    font-size: 16px;
    color: rgb(0, 55, 255);
    font-weight: 500;
    text-align: center;
    white-space: nowrap;
    position: relative;
  }
  .waiting-label::before {
    content: "Thời Gian Biệt trữ";
    position: absolute;
    top: -24px;   /* đẩy chữ lên trên border */
    left: 50%;
    transform: translateX(-50%);
    padding: 0 6px;
    font-size: 13px;
    font-weight: bold;
}
</style>

<link rel="stylesheet" href="{{ asset('libs/bs-stepper/css/bs-stepper.min.css') }}">

<div class="content-wrapper">
  <section class="content">
    <div class="container-fluid">
      <div class="row">
        <div class="col-12">
          <div class="card">

            <div class="card-header mt-4"></div>

            <div class="card-body">
              <div class="row">
                <div class="col-md-4">
                  @php
                    use Carbon\Carbon;
                    $defaultFrom = Carbon::now()->subMonth()->toDateString();
                    $defaultTo   = Carbon::now()->addMonth()->toDateString();
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
              </div>

              <table id="data_table_Schedual_step" class="table table-bordered table-striped" style="font-size: 20px">
                <thead style="position: sticky; top: 60px; background-color: white; z-index: 1020">
                  <tr>
                    <th>STT</th>
                    <th>Sản Phẩm</th>
                    <th>Dự Kiến KCS</th>
                    <th>Số lô</th>
                    <th>Tiến Trình</th>
                    <th>Tổng kết</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach($datas as $plan_master_id => $stages)
                    @php
                      $plan = $stages->first();
                      $lastFinished = collect($stages)->where('finished', '1')->sortByDesc('stage_code')->first();
                      $sortedStages = $stages->sortBy('stage_code')->values();
                      $firstStage   = $sortedStages->first();
                      $lastStage    = $sortedStages->last();
                      $start = Carbon::parse($firstStage->start);
                      $end   = Carbon::parse($lastStage->end);
                      $diff = $start->diff($end);
                      $totalDuration = $diff->d . 'd-' . $diff->h . 'h' ;
                      // Tổng thời gian sản xuất (tính giờ làm trong từng stage)
                      $totalProductionHours = 0;
                      // Tổng thời gian vệ sinh (khoảng trống giữa các stage)
                      $totalCleaningHours   = 0;
                     //dd($sortedStages) ;
                      foreach ($sortedStages as $index => $stage) {
                          $stageStart = Carbon::parse($stage->start);
                          $stageEnd   = Carbon::parse($stage->end);
                          $stageStart_clearning = Carbon::parse($stage->start_clearning);
                          $stageEnd_clearning   = Carbon::parse($stage->end_clearning);
                          // Thêm thời gian sản xuất
                          $totalProductionHours += $stageStart->diffInMinutes($stageEnd) / 60;
                          $totalCleaningHours  += $stageStart_clearning->diffInMinutes($stageEnd_clearning) / 60;
                          // Nếu có stage trước đó thì tính khoảng trống (vệ sinh/biệt trữ)
                      }
                        // Format gọn lại
                        $totalProductionHours = round($totalProductionHours, 2);
                        $totalCleaningHours   = round($totalCleaningHours, 2);
                    @endphp

                    {{-- Hàng 1: Stepper --}}
                    <tr>
                      <td >{{ $loop->iteration }}</td>
                      <td >{{ $plan->product_name ."-". $plan->batch_qty . ' ' . $plan->unit_batch_qty }}</td>
                      <td >{{ Carbon::parse($plan->expected_date)->format('d/m/Y') }}</td>
                      <td >{{ $plan->batch }}</td>
                      <td>
                        <div id="stepper-{{ $plan_master_id }}" class="bs-stepper">
                          <div class="bs-stepper-header" role="tablist">
                            
                            @foreach($stages as $i => $stage)
                              @php

                                $stageKey = Str::slug($stage->stage_code, '-');
                                $statusClass = 'step-pending';
                                if ($stage->status == 'scheduled') {
                                  $statusClass = 'step-scheduled';
                                } elseif ($stage->status == 'finished') {
                                  $statusClass = 'step-finished';
                                } elseif (!empty($stage->end) && Carbon::parse($stage->end)->gt(Carbon::parse($plan->expected_date))) {
                                  $statusClass = 'step-delay';
                                } elseif ($stage->stage_code == 1 && $stage->start !== null && !(
                                    !empty($plan->after_weigth_date) &&
                                    !empty($plan->before_weigth_date) &&
                                    Carbon::parse($stage->start)->between(
                                      Carbon::parse($plan->after_weigth_date),
                                      Carbon::parse($plan->before_weigth_date)
                                    )
                                )) {
                                  $statusClass = 'step-warning';
                                } elseif ($stage->stage_code >= 7 && $stage->start !== null && !(
                                    !empty($plan->after_parkaging_date) &&
                                    !empty($plan->before_parkaging_date) &&
                                    Carbon::parse($stage->start)->between(
                                      Carbon::parse($plan->after_parkaging_date),
                                      Carbon::parse($plan->before_parkaging_date)
                                    )
                                )) {
                                  $statusClass = 'step-warning';
                                }

                                if ($lastFinished && $stage->id == $lastFinished->id) {
                                  $statusClass .= ' step-pointer';
                                }
                                  // tính thời gian biệt trữ
                                $waiting = null;
                                if ($i < $stages->count()-1) {
                                      $next = $sortedStages[$i + 1];
                                      $end  = Carbon::parse($stage->end);
                                      $startNext = Carbon::parse($next->start);
                                      $diff = $end->diff($startNext);
                                      // Format ra ngày, giờ, phút
                                      $waiting = $diff->d . 'd - ' . $diff->h . 'h ' ;
                                      // Màu sắc
                                      $color_div = $next->finished ? '#007bff' : '#28a745';
                                  }
                              @endphp

                              <div class="step {{ $loop->first ? 'active' : '' }} {{ $statusClass }}"
                                   data-target="#step-{{ $plan_master_id }}-{{ $stageKey }}">
                                <button type="button"
                                        class="step-trigger position-relative"
                                        role="tab"
                                        id="stepper-{{ $plan_master_id }}-trigger-{{ $stageKey }}">
                                  <span class="bs-stepper-circle">{{ $loop->iteration }}</span>
                                  <span class="bs-stepper-label">
                                    {{ $stage->stage_name }}
                                    <small class="d-block">{{ Carbon::parse($stage->start)->format('d/m/Y H:i') }}</small>
                                    <small class="d-block">{{ Carbon::parse($stage->end)->format('d/m/Y H:i') }}</small>

                                    @if(!is_null($stage->yields))
                                      <small class="d-block">Yield: {{ $stage->yields }} {{ $stage->stage_code<=4 ? "Kg" : "ĐVL" }}</small>
                                    @endif
                                  </span>
                                </button>
                              </div>

                              @if(!$loop->last)
                                @if($waiting)
                                    <div class="waiting-label" style="color: {{ $color_div }} ">
                                      {{ $waiting }}
                                    </div>
                                @endif
                              @endif
                              
                            @endforeach
                          </div>
                        </div>
                      </td>
                      <td>
                        <div class="timeline-info mt-2">
                          <div>Bắt đầu: {{ Carbon::parse($firstStage->start)->format('d/m/Y H:i') }}</div>
                          <div>Kết thúc: {{ Carbon::parse($lastStage->end)->format('d/m/Y H:i') }}</div>
                          <div>
                            <span>TGSX: {{ $totalProductionHours }}h</span> - 
                            <span>TGVS: {{ $totalCleaningHours }}h</span>
                          </div>
                          <div class="text-success">Tổng TGSX: {{ $totalDuration }}</span>
                        </div>
                      </td>
                    </tr>
                  @endforeach
                </tbody>
              </table>
            </div>

          </div>
        </div>
      </div>
    </div>
  </section>
</div>

<script src="{{ asset('js/vendor/jquery-1.12.4.min.js') }}"></script>
<script src="{{ asset('js/popper.min.js') }}"></script>
<script src="{{ asset('js/bootstrap.min.js') }}"></script>
<script src="{{ asset('libs/bs-stepper/js/bs-stepper.min.js') }}"></script>

<script>

$(document).ready(function () {
    document.body.style.overflowY = "auto";
    
     $('#data_table_Schedual_step').DataTable({
            paging: true,
            lengthChange: true,
            searching: true,
            ordering: true,
            info: true,
            autoWidth: false,
            pageLength: 10,
            lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50,100, "Tất cả"]],
            language: {
                search: "Tìm kiếm:",
                lengthMenu: "Hiển thị _MENU_ dòng",
                info: "Hiển thị _START_ đến _END_ của _TOTAL_ dòng",
                paginate: {
                    previous: "Trước",
                    next: "Sau"
                }
            },
        });


})

  document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.bs-stepper').forEach(stepperEl => {
      new Stepper(stepperEl, { linear: false, animation: true });
    });
  });

  const form = document.getElementById('dateFilterForm');
  const fromInput = document.getElementById('from_date');
  const toInput = document.getElementById('to_date');

  [fromInput, toInput].forEach(input => {
    input.addEventListener('input', function () {
      form.submit();
    });
  });
</script>
