@php
    use Carbon\Carbon;
    $defaultFrom = $selectedDate ? Carbon::parse($selectedDate)->format('Y-m-d') : Carbon::now()->format('Y-m-d');
    
    // Group rooms by stage for the table display
    // We'll use the 'stage' field we added to the query
    $groupedByStage = $groupedByRoom->groupBy(function($rooms) {
        return $rooms->first()->stage ?? 'Không xác định';
    });
@endphp

<div class="content-wrapper">
    <div class="card">
        <div class="card-header mt-4"></div>
        <div class="card-body">
            <!-- Filter & Title -->
            <div class="row mx-2">
                <div class="col-md-3">
                    <form id="filterForm" method="GET" action="{{ route('pages.report.weekly_production_schedule.index') }}">
                        @csrf
                        <div class="form-group d-flex align-items-center">
                            <label for="reportedDate" class="mr-2 mb-0">Chọn Tuần:</label>
                            <input type="week" id="reportedDate" name="reportedDate" value="{{ $selectedDate }}" class="form-control" />
                        </div>
                    </form>
                </div>
                <div class="col-md-6 text-center" style="font-size: 20px; color: #007bff;">
                    <div class="font-weight-bold">
                        {{ $displayWeek }}
                    </div>
                </div>
                <div class="col-md-3 text-right">
                    <button class="btn btn-primary" onclick="window.print()">
                        <i class="fas fa-print"></i> In Kế Hoạch
                    </button>
                    <button class="btn btn-secondary" onclick="toggleAllStages()">
                        <i class="fas fa-arrows-alt-v"></i> Thu/Phóng Tất Cả
                    </button>
                </div>
            </div>

            <!-- Table -->
            <div class="card card-primary mb-4 mt-3">
                <div class="card-header border-transparent" style="background-color: #007bff;">
                    <h3 class="card-title">Chi Tiết Kế Hoạch Sản Xuất Tuần</h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-tool" data-card-widget="collapse">
                            <i class="fas fa-minus"></i>
                        </button>
                    </div>
                </div>

                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table id="production_weekly_table" class="table table-bordered" style="font-size: 13px;">
                            <thead class="bg-light" style="position: sticky; top: 0; z-index: 1020;">
                                <tr style="color:#003A4F; font-size: 16px; font-weight: bold;">
                                    <th class="text-center align-middle" width="40" rowspan="2">#</th>
                                    <th class="text-center align-middle" style="min-width: 150px;" rowspan="2">Phòng SX / Khu Vực</th>
                                    <th class="text-center align-middle" colspan="7">Lịch Lý Thuyết</th>
                                </tr>
                                <tr style="color:#003A4F; font-size: 15px;">
                                    @foreach($weekDays as $day)
                                        <th class="text-center" width="13%">
                                            {{ $day['label'] }}<br>
                                            <small class="text-muted">{{ $day['display'] }}</small>
                                        </th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @php 
                                    $roomIndex = 1;
                                    $stageCount = 0;
                                @endphp
                                @forelse($groupedByStage as $stageName => $roomsInStage)
                                    @php $stageId = 'stage_' . (++$stageCount); @endphp
                                    <!-- Stage Header Row -->
                                    <tr class="stage-header" style="cursor: pointer; background-color: #d1b400; color: #003a4f; border-top: 2px solid #a89100;" data-target=".{{ $stageId }}">
                                        <td colspan="9" class="py-2 px-3">
                                            <div class="d-flex align-items-center">
                                                <i class="fas fa-minus-square mr-2 toggle-icon" style="color: #003a4f;"></i>
                                                <span class="font-weight-bold" style="font-size: 15px;">
                                                    Công đoạn: {{ $stageName }}
                                                </span>
                                                <span class="badge ml-2" style="background-color: rgba(0, 58, 79, 0.1); color: #003a4f; border: 1px solid #003a4f;">{{ count($roomsInStage) }} Phòng</span>
                                            </div>
                                        </td>
                                    </tr>

                                    <!-- Room Rows for this Stage -->
                                    @foreach($roomsInStage as $roomId => $events)
                                        @php 
                                            $firstEvent = $events->first(); 
                                            $validEventsFlat = $events->whereNotNull('sp_id');
                                        @endphp
                                        <tr class="{{ $stageId }}">
                                            <td class="text-center align-middle bg-light font-weight-bold">{{ $roomIndex++ }}</td>
                                            <td class="align-middle">
                                                <div class="font-weight-bold text-primary" style="font-size: 14px;">
                                                    {{ $firstEvent->room_code }} - {{ $firstEvent->room_name }}
                                                </div>
                                            </td>
                                            
                                            @foreach($weekDays as $day)
                                                <td class="align-top p-2" style="min-height: 80px; background-color: {{ Carbon::now()->format('Y-m-d') == $day['date'] ? '#e3f2fd' : 'transparent' }};">
                                                    @php 
                                                        $dayEvents = $validEventsFlat->where('day_key', $day['date']);
                                                    @endphp
                                                    
                                                    @if($dayEvents->count())
                                                        @php $eventIdx = 1; @endphp
                                                        @foreach($dayEvents as $e)
                                                            @php
                                                                $start = \Carbon\Carbon::parse($e->planned_start);
                                                                $end = \Carbon\Carbon::parse($e->planned_end);
                                                                $hours = $start->diffInHours($end);
                                                                $mins = $start->diffInMinutes($end) % 60;
                                                                $batchDisplay = $e->actual_batch ?? $e->batch;
                                                            @endphp
                                                            <div class="mb-2 p-1 border-bottom last-child-no-border" style="line-height: 1.3;">
                                                                <div style="font-size: 13px; color: #333;">
                                                                    <b>{{ $eventIdx++ }}.</b> 
                                                                    <span class="text-dark">{{ $e->product_name }}</span> - 
                                                                    <span class="text-danger font-weight-bold">{{ $batchDisplay }}</span>
                                                                </div>
                                                                <div class="text-primary font-weight-bold mt-1" style="font-size: 12px;">
                                                                    {{ $start->format('H:i') }} -> {{ $end->format('H:i') }}
                                                                    <span class="text-dark"> = {{ $hours }}h{{ $mins }}p</span>
                                                                </div>
                                                            </div>
                                                        @endforeach
                                                    @endif
                                                </td>
                                            @endforeach
                                        </tr>
                                    @endforeach
                                @empty
                                    <tr>
                                        <td colspan="9" class="text-center py-5">Dữ liệu không tồn tại.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .last-child-no-border:last-child { border-bottom: none !important; }
    #production_weekly_table th, #production_weekly_table td { border: 1px solid #dee2e6 !important; }
    #production_weekly_table td { padding: 4px !important; vertical-align: top !important; }
    .bg-gray-light:hover { background-color: #e9ecef !important; }
    .toggle-icon { transition: transform 0.2s; }
    .stage-collapsed .toggle-icon { transform: rotate(-90deg); }
    
    @media print {
        @page { size: landscape; margin: 5mm; }
        .card-header mt-4, .card-tools, #filterForm, .btn-primary, .btn-secondary, .toggle-icon { display: none !important; }
        .content-wrapper { margin: 0 !important; padding: 0 !important; }
        #production_weekly_table { width: 100% !important; font-size: 10px !important; }
        .stage-header { background-color: #f1f1f1 !important; color: black !important; -webkit-print-color-adjust: exact; }
    }
</style>

<script src="{{ asset('js/vendor/jquery-1.12.4.min.js') }}"></script>
<script>
    $(document).ready(function() { 
        document.body.style.overflowY = "auto";
        
        // Handle stage collapse/expand
        $('.stage-header').on('click', function() {
            var target = $(this).data('target');
            $(target).toggle();
            $(this).toggleClass('stage-collapsed');
            
            // Update icon
            var icon = $(this).find('.toggle-icon');
            if ($(target).is(':visible')) {
                icon.removeClass('fa-plus-square').addClass('fa-minus-square');
            } else {
                icon.removeClass('fa-minus-square').addClass('fa-plus-square');
            }
        });
    });
    
    function toggleAllStages() {
        var isHiding = $('.stage-header').first().find('.toggle-icon').hasClass('fa-minus-square');
        
        $('.stage-header').each(function() {
            var target = $(this).data('target');
            var icon = $(this).find('.toggle-icon');
            
            if (isHiding) {
                $(target).hide();
                $(this).addClass('stage-collapsed');
                icon.removeClass('fa-minus-square').addClass('fa-plus-square');
            } else {
                $(target).show();
                $(this).removeClass('stage-collapsed');
                icon.removeClass('fa-plus-square').addClass('fa-minus-square');
            }
        });
    }

    $(function() {
        $('#reportedDate').on('change', function() { $('#filterForm').submit(); });
    });
</script>
