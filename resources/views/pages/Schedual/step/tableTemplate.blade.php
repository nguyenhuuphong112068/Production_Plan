<table id="{{ $tableId }}" class="table table-bordered table-striped" style="font-size: 20px; min-width: 1500px;">
    <thead
        style="position: sticky; top: 0; background-color: white; z-index: 1020; box-shadow: 0 2px 2px -1px rgba(0, 0, 0, 0.4);">
        <tr>
            <th>STT</th>
            <th>Sản Phẩm</th>
            <th>Dự Kiến KCS</th>
            <th>Số lô</th>
            <th>Tiến Trình</th>
            @if (isset($isWip) && $isWip)
                <th style="min-width: 180px;">Số ngày còn HBT</th>
            @endif
            <th>Tổng kết</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($tableData as $plan_master_id => $stages)
            @php

                $plan = $stages->first();
                $lastFinished = collect($stages)->where('finished', '1')->sortByDesc('stage_code')->first();
                $sortedStages = $stages->sortBy('stage_code')->values();
                $firstStage = null;
                $lastStage = null;
                foreach ($sortedStages as $sortedStage) {
                    if ($sortedStage->start != null) {
                        $firstStage = $sortedStage;
                        break;
                    }
                }
                foreach ($sortedStages->reverse() as $sortedStage) {
                    if ($sortedStage->start != null) {
                        $lastStage = $sortedStage;
                        break;
                    }
                }

                if ($firstStage == null && $lastStage == null) {
                    $firstStage = $sortedStages->first();
                    $lastStage = $sortedStages->last();
                }

                $startTs = $firstStage->start ? strtotime($firstStage->start) : 0;
                $endTs = $lastStage->end ? strtotime($lastStage->end) : 0;
                $diffSecs = ($startTs && $endTs) ? max(0, $endTs - $startTs) : 0;
                $diffDays = floor($diffSecs / 86400);
                $diffHours = floor(($diffSecs % 86400) / 3600);
                $totalDuration = $diffDays . 'd-' . $diffHours . 'h';
                // Tổng thời gian sản xuất (tính giờ làm trong từng stage)
                $totalProductionHours = 0;
                // Tổng thời gian vệ sinh (khoảng trống giữa các stage)
                $totalCleaningHours = 0;
                //dd($sortedStages) ;
                foreach ($sortedStages as $index => $stage) {
                    $stageStartTs = strtotime($stage->start);
                    $stageEndTs = strtotime($stage->end);
                    $stageStart_clearningTs = strtotime($stage->start_clearning);
                    $stageEnd_clearningTs = strtotime($stage->end_clearning);

                    if ($stage->start && $stage->end) {
                        $totalProductionHours += ($stageEndTs - $stageStartTs) / 3600;
                    }
                    if ($stage->start_clearning && $stage->end_clearning) {
                        $totalCleaningHours += ($stageEnd_clearningTs - $stageStart_clearningTs) / 3600;
                    }
                }
                // Format gọn lại
                $totalProductionHours = round($totalProductionHours, 2);
                $totalCleaningHours = round($totalCleaningHours, 2);
            @endphp

            {{-- Hàng 1: Stepper --}}
            <tr>
                <td>{{ $loop->iteration }}</td>
                <td>{{ $plan->product_name . ($plan->intermediate_code ? ' (' . $plan->intermediate_code . ')' : '') . '-' . $plan->batch_qty . ' ' . $plan->unit_batch_qty }}
                </td>
                <td>{{ date('d/m/Y', strtotime($plan->expected_date)) }}</td>
                <td>{{ $plan->batch }}</td>
                <td>
                    @php
                        $mainStages = $stages
                            ->filter(function ($s) {
                                return $s->stage_code != 2;
                            })
                            ->values();
                        $branchStages = $stages
                            ->filter(function ($s) {
                                return $s->stage_code == 2;
                            })
                            ->values();
                    @endphp

                    <div id="stepper-{{ $plan_master_id }}-{{ $tableId }}" class="bs-stepper">
                        <div class="bs-stepper-header" role="tablist">

                            @foreach ($mainStages as $i => $stage)
                                @php

                                    $stageKey = Str::slug($stage->stage_code, '-');
                                    $statusClass = 'step-pending';
                                    if ($stage->status == 'scheduled') {
                                        $statusClass = 'step-scheduled';
                                    } elseif ($stage->status == 'finished') {
                                        $statusClass = 'step-finished';
                                    } elseif (
                                        !empty($stage->end) &&
                                        strtotime($stage->end) > strtotime($plan->expected_date)
                                    ) {
                                        $statusClass = 'step-delay';
                                    } elseif (
                                        $stage->stage_code == 1 &&
                                        $stage->start !== null &&
                                        !(
                                            !empty($plan->after_weigth_date) &&
                                            !empty($plan->before_weigth_date) &&
                                            strtotime($stage->start) >= strtotime($plan->after_weigth_date) &&
                                            strtotime($stage->start) <= strtotime($plan->before_weigth_date)
                                        )
                                    ) {
                                        $statusClass = 'step-warning';
                                    } elseif (
                                        $stage->stage_code >= 7 &&
                                        $stage->start !== null &&
                                        !(
                                            !empty($plan->after_parkaging_date) &&
                                            !empty($plan->before_parkaging_date) &&
                                            strtotime($stage->start) >= strtotime($plan->after_parkaging_date) &&
                                            strtotime($stage->start) <= strtotime($plan->before_parkaging_date)
                                        )
                                    ) {
                                        $statusClass = 'step-warning';
                                    }

                                    if ($lastFinished && $stage->id == $lastFinished->id) {
                                        $statusClass .= ' step-pointer';
                                    }
                                    // tính thời gian biệt trữ
                                    $waiting = null;
                                    $stdWaiting = null;
                                    $isWarning = false;

                                    // Tìm công đoạn tiếp theo dựa vào nextcessor_code
                                    $next = $stages->firstWhere('code', $stage->nextcessor_code);

                                    // Lấy thời gian biệt trữ chuẩn
                                    $stdValue = null;
                                    if (in_array($stage->stage_code, [1, 2])) {
                                        $stdValue = $stage->quarantine_weight;
                                    } elseif ($stage->stage_code == 3) {
                                        $stdValue = $stage->quarantine_preparing;
                                    } elseif ($stage->stage_code == 4) {
                                        $stdValue = $stage->quarantine_blending;
                                    } elseif ($stage->stage_code == 5) {
                                        $stdValue = $stage->quarantine_forming;
                                    } elseif ($stage->stage_code == 6) {
                                        $stdValue = $stage->quarantine_coating;
                                    }

                                    if ($stdValue !== null) {
                                        $unitStr = $stage->quarantine_time_unit == 1 ? 'd' : 'h';
                                        $stdWaiting = $stdValue . $unitStr;
                                    }

                                    $color_div = $next && $next->finished ? '#007bff' : '#28a745';
                                    $expirationDate = null;
                                    $stdInMinutes = null;

                                    if ($stage->end && $stdValue !== null && $stdValue > 0) {
                                        $endTsCopy = strtotime($stage->end);
                                        if ($stage->quarantine_time_unit == 1) {
                                            $expirationDateTs = strtotime("+$stdValue days", $endTsCopy);
                                        } else {
                                            $expirationDateTs = strtotime("+$stdValue hours", $endTsCopy);
                                        }
                                        $expirationDate = date('d/m H:i', $expirationDateTs);
                                        $stdInMinutes =
                                            $stage->quarantine_time_unit == 1 ? $stdValue * 24 * 60 : $stdValue * 60;
                                    }

                                    if ($next && $stage->end && $next->start) {
                                        $endTs = strtotime($stage->end);
                                        $startNextTs = strtotime($next->start);
                                        $diffSecs = $startNextTs - $endTs;
                                        $diffDays = floor($diffSecs / 86400);
                                        $diffHours = floor(($diffSecs % 86400) / 3600);
                                        $waiting = $diffDays . 'd - ' . $diffHours . 'h ';

                                        if ($stdInMinutes !== null && $stdInMinutes > 0) {
                                            $diffInMinutes = $diffSecs / 60;
                                            if ($diffInMinutes > $stdInMinutes) {
                                                $isWarning = true;
                                            }
                                        }
                                    } elseif ($stage->end && $stdInMinutes !== null && $stdInMinutes > 0) {
                                        // Chưa có next->start, kiểm tra xem hiện tại đã quá hạn biệt trữ chưa
                                        $endTs = strtotime($stage->end);
                                        $nowTs = time();
                                        $diffInMinutes = ($nowTs - $endTs) / 60;
                                        if ($diffInMinutes > $stdInMinutes) {
                                            $isWarning = true;
                                        }
                                    }
                                @endphp

                                <div class="step {{ $loop->first ? 'active' : '' }} {{ $statusClass }}"
                                    data-target="#step-{{ $plan_master_id }}-{{ $tableId }}-{{ $stageKey }}">
                                    <button type="button" class="step-trigger position-relative" role="tab"
                                        id="stepper-{{ $plan_master_id }}-{{ $tableId }}-trigger-{{ $stageKey }}">
                                        <span class="bs-stepper-circle">{{ $stage->stage_code }}</span>
                                        <span class="bs-stepper-label">
                                            {{ $stage->stage_name }}
                                            <small class="d-block">{{ $stage->room_name }}</small>
                                            <small
                                                class="d-block">{{ $stage->start == null ? '' : date('d/m/Y H:i', strtotime($stage->start)) }}</small>
                                            <small
                                                class="d-block">{{ $stage->start == null ? '' : date('d/m/Y H:i', strtotime($stage->end)) }}</small>

                                            @if (!is_null($stage->yields))
                                                <small class="d-block">Yield:
                                                    {{ $stage->yields }}
                                                    {{ $stage->stage_code <= 4 ? 'Kg' : 'ĐVL' }}</small>
                                            @endif
                                        </span>
                                    </button>
                                </div>

                                @if (!$loop->last)
                                    @if ($next)
                                        <div class="waiting-label"
                                            style="color: {{ $isWarning ? '#dc3545' : $color_div }} "
                                            title="Thực tế: {{ $waiting ?? 'Chưa xếp lịch' }} | Chuẩn: {{ $stdWaiting }}">
                                            <span style="font-size: 10px; color: #888;">→
                                                {{ $next->stage_name }}</span><br>
                                            @if ($waiting)
                                                {{ $waiting }}
                                            @else
                                                <span style="font-size: 11px; color: #aaa;">--
                                                    chờ xếp --</span>
                                            @endif
                                            @if ($stdWaiting)
                                                <br>
                                                <span
                                                    class="badge {{ $isWarning ? 'badge-danger' : 'badge-light border text-muted' }}"
                                                    style="font-size: 11px; font-weight: normal; margin-top: 2px;">
                                                    HBT: {{ $stdWaiting }}
                                                </span>
                                            @endif
                                            @if ($expirationDate)
                                                <br>
                                                <span
                                                    style="font-size: 10px; color: {{ $isWarning ? '#dc3545' : '#888' }};">Hạn:
                                                    {{ $expirationDate }}</span>
                                            @endif
                                        </div>
                                    @else
                                        <div class="waiting-label" style="border-top-color: transparent;">
                                        </div>
                                    @endif
                                @endif
                            @endforeach
                        </div>

                        @if ($branchStages->isNotEmpty())
                            <div class="bs-stepper-header" role="tablist"
                                style="margin-top: 20px; justify-content: flex-start; padding-left: 20px;">
                                @foreach ($branchStages as $i => $stage)
                                    @php

                                        $stageKey = Str::slug($stage->stage_code, '-');
                                        $statusClass = 'step-pending';
                                        if ($stage->status == 'scheduled') {
                                            $statusClass = 'step-scheduled';
                                        } elseif ($stage->status == 'finished') {
                                            $statusClass = 'step-finished';
                                        } elseif (
                                            !empty($stage->end) &&
                                            strtotime($stage->end) > strtotime($plan->expected_date)
                                        ) {
                                            $statusClass = 'step-delay';
                                        }

                                        if ($lastFinished && $stage->id == $lastFinished->id) {
                                            $statusClass .= ' step-pointer';
                                        }
                                        // tính thời gian biệt trữ
                                        $waiting = null;
                                        $stdWaiting = null;
                                        $isWarning = false;

                                        // Tìm công đoạn tiếp theo dựa vào nextcessor_code
                                        $next = $stages->firstWhere('code', $stage->nextcessor_code);

                                        // Lấy thời gian biệt trữ chuẩn (công đoạn 2 luôn là cân NL khác)
                                        $stdValue = $stage->quarantine_weight;
                                        $stdWaiting = null;

                                        if ($stdValue !== null) {
                                            $unitStr = $stage->quarantine_time_unit == 1 ? 'd' : 'h';
                                            $stdWaiting = $stdValue . $unitStr;
                                        }

                                        $color_div = $next && $next->finished ? '#007bff' : '#28a745';
                                        $expirationDate = null;
                                        $stdInMinutes = null;

                                        if ($stage->end && $stdValue !== null && $stdValue > 0) {
                                            $endTsCopy = strtotime($stage->end);
                                            if ($stage->quarantine_time_unit == 1) {
                                                $expirationDateTs = strtotime("+$stdValue days", $endTsCopy);
                                            } else {
                                                $expirationDateTs = strtotime("+$stdValue hours", $endTsCopy);
                                            }
                                            $expirationDate = date('d/m H:i', $expirationDateTs);
                                            $stdInMinutes =
                                                $stage->quarantine_time_unit == 1
                                                    ? $stdValue * 24 * 60
                                                    : $stdValue * 60;
                                        }

                                        if ($next && $stage->end && $next->start) {
                                            $endTs = strtotime($stage->end);
                                            $startNextTs = strtotime($next->start);
                                            $diffSecs = $startNextTs - $endTs;
                                            $diffDays = floor($diffSecs / 86400);
                                            $diffHours = floor(($diffSecs % 86400) / 3600);
                                            $waiting = $diffDays . 'd - ' . $diffHours . 'h ';

                                            if ($stdInMinutes !== null && $stdInMinutes > 0) {
                                                $diffInMinutes = $diffSecs / 60;
                                                if ($diffInMinutes > $stdInMinutes) {
                                                    $isWarning = true;
                                                }
                                            }
                                        } elseif ($stage->end && $stdInMinutes !== null && $stdInMinutes > 0) {
                                            // Chưa có next->start, kiểm tra xem hiện tại đã quá hạn biệt trữ chưa
                                            $endTs = strtotime($stage->end);
                                            $nowTs = time();
                                            $diffInMinutes = ($nowTs - $endTs) / 60;
                                            if ($diffInMinutes > $stdInMinutes) {
                                                $isWarning = true;
                                            }
                                        }
                                    @endphp

                                    <div class="step {{ $loop->first ? 'active' : '' }} {{ $statusClass }}"
                                        data-target="#step-{{ $plan_master_id }}-{{ $tableId }}-{{ $stageKey }}">
                                        <button type="button" class="step-trigger position-relative" role="tab"
                                            id="stepper-{{ $plan_master_id }}-{{ $tableId }}-trigger-{{ $stageKey }}">
                                            <span class="bs-stepper-circle">{{ $stage->stage_code }}</span>
                                            <span class="bs-stepper-label">
                                                {{ $stage->stage_name }}
                                                <small class="d-block">{{ $stage->room_name }}</small>
                                                <small
                                                    class="d-block">{{ $stage->start == null ? '' : date('d/m/Y H:i', strtotime($stage->start)) }}</small>
                                                <small
                                                    class="d-block">{{ $stage->start == null ? '' : date('d/m/Y H:i', strtotime($stage->end)) }}</small>

                                                @if (!is_null($stage->yields))
                                                    <small class="d-block">Yield:
                                                        {{ $stage->yields }}
                                                        {{ $stage->stage_code <= 4 ? 'Kg' : 'ĐVL' }}</small>
                                                @endif
                                            </span>
                                        </button>
                                    </div>

                                    @if ($next)
                                        <div class="waiting-label"
                                            style="color: {{ $isWarning ? '#dc3545' : $color_div }}; width: 150px; flex: 0 0 auto;"
                                            title="Thực tế: {{ $waiting ?? 'Chưa xếp lịch' }} | Chuẩn: {{ $stdWaiting }}">
                                            <span style="font-size: 10px; color: #888;">→
                                                {{ $next->stage_name }}</span><br>
                                            @if ($waiting)
                                                {{ $waiting }}
                                            @else
                                                <span style="font-size: 11px; color: #aaa;">--
                                                    chờ xếp --</span>
                                            @endif
                                            @if ($stdWaiting)
                                                <br>
                                                <span
                                                    class="badge {{ $isWarning ? 'badge-danger' : 'badge-light border text-muted' }}"
                                                    style="font-size: 11px; font-weight: normal; margin-top: 2px;">
                                                    HBT: {{ $stdWaiting }}
                                                </span>
                                            @endif
                                            @if ($expirationDate)
                                                <br>
                                                <span
                                                    style="font-size: 10px; color: {{ $isWarning ? '#dc3545' : '#888' }};">Hạn:
                                                    {{ $expirationDate }}</span>
                                            @endif
                                        </div>
                                    @else
                                        <div class="waiting-label"
                                            style="border-top-color: transparent; width: 150px; flex: 0 0 auto;">
                                        </div>
                                    @endif
                                @endforeach
                            </div>
                        @endif
                        <!-- Dummy content để tránh lỗi JS Cannot read properties of null -->
                        <div class="bs-stepper-content d-none">
                            @foreach ($stages as $i => $stage)
                                @php $stageKey = Str::slug($stage->stage_code, '-'); @endphp
                                <div id="step-{{ $plan_master_id }}-{{ $tableId }}-{{ $stageKey }}"
                                    class="content" role="tabpanel"></div>
                            @endforeach
                        </div>
                    </div>
                </td>
                @if (isset($isWip) && $isWip)
                    @php
                        $remainingDays = null;
                        $remainingText = '--';
                        $badgeClass = '';

                        if ($lastFinished) {
                            $stdValue = null;
                            if (in_array($lastFinished->stage_code, [1, 2])) {
                                $stdValue = $lastFinished->quarantine_weight;
                            } elseif ($lastFinished->stage_code == 3) {
                                $stdValue = $lastFinished->quarantine_preparing;
                            } elseif ($lastFinished->stage_code == 4) {
                                $stdValue = $lastFinished->quarantine_blending;
                            } elseif ($lastFinished->stage_code == 5) {
                                $stdValue = $lastFinished->quarantine_forming;
                            } elseif ($lastFinished->stage_code == 6) {
                                $stdValue = $lastFinished->quarantine_coating;
                            }

                            if ($lastFinished->end && $stdValue !== null && $stdValue > 0) {
                                $endTsCopy = strtotime($lastFinished->end);
                                if ($lastFinished->quarantine_time_unit == 1) {
                                    $expirationDateTs = strtotime("+$stdValue days", $endTsCopy);
                                } else {
                                    $expirationDateTs = strtotime("+$stdValue hours", $endTsCopy);
                                }

                                $nowTs = time();
                                $remainingSecs = $expirationDateTs - $nowTs;
                                $remainingDays = $remainingSecs / 86400; // float days

                                if ($remainingDays >= 0) {
                                    $remainingText = number_format($remainingDays, 1) . ' ngày';
                                } else {
                                    $remainingText = 'Quá hạn ' . number_format(abs($remainingDays), 1) . ' ngày';
                                }

                                if ($remainingDays >= 7) {
                                    $badgeClass = 'badge-success';
                                } elseif ($remainingDays >= 5) {
                                    $badgeClass = 'badge-warning-light';
                                } elseif ($remainingDays >= 3) {
                                    $badgeClass = 'badge-warning-dark';
                                } else {
                                    $badgeClass = 'badge-danger';
                                }
                            }
                        }
                    @endphp
                    <td data-order="{{ $remainingDays !== null ? $remainingDays : 999999 }}"
                        class="text-center align-middle" style="font-size: 18px;">
                        @if ($remainingDays !== null)
                            <span class="badge {{ $badgeClass }} p-2" style="font-size: 16px; border-radius: 4px;">
                                {{ $remainingText }}
                            </span>
                        @else
                            <span class="text-muted">{{ $remainingText }}</span>
                        @endif
                    </td>
                @endif
                <td style="min-width: 220px; font-size: 14px;">
                    <div class="timeline-info mt-2 p-2 border rounded bg-light"
                        style="box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="text-muted" style="font-size: 12px;"><i
                                    class="fas fa-play text-success mr-1"></i> Bắt
                                đầu:</span>
                            <span
                                class="font-weight-bold">{{ $firstStage->start ? date('d/m/Y H:i', strtotime($firstStage->start)) : '--' }}</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-2 pb-2 border-bottom">
                            <span class="text-muted" style="font-size: 12px;"><i
                                    class="fas fa-stop text-danger mr-1"></i> Kết
                                thúc:</span>
                            <span class="font-weight-bold">{{ $lastStage->end ? date('d/m/Y H:i', strtotime($lastStage->end)) : '--' }}</span>
                        </div>

                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="text-muted" style="font-size: 12px;"><i
                                    class="fas fa-cogs text-primary mr-1"></i>
                                TGSX:</span>
                            <span class="badge badge-primary">{{ $totalProductionHours }}h</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-2 pb-2 border-bottom">
                            <span class="text-muted" style="font-size: 12px;"><i
                                    class="fas fa-broom text-warning mr-1"></i>
                                TGVS:</span>
                            <span class="badge badge-warning">{{ $totalCleaningHours }}h</span>
                        </div>

                        <div class="d-flex justify-content-between align-items-center mt-2">
                            <strong class="text-success" style="font-size: 12px;"><i class="fas fa-clock mr-1"></i> Tổng
                                TGSX:</strong>
                            <strong class="text-success">{{ $totalDuration }}</strong>
                        </div>
                    </div>
                </td>
            </tr>
        @endforeach
    </tbody>
</table>
