{{-- Card "Sản Lượng" của Báo cáo ngày: công đoạn → phòng (lý thuyết, thực tế, % đáp ứng) và cột Chi tiết
     (giải trình / lý do theo công đoạn, diễn biến trong ngày theo phòng). --}}
@include('pages.report.daily_report._yield_styles')
@php
    $TL = \App\Services\DailyRoomTimelineService::class;
    $shiftStart = \Carbon\Carbon::createFromFormat('d/m/Y H:i:s', $reportedDate . ' 06:00:00');
    $shiftEnd = $shiftStart->copy()->addDay();
    $viewedUntil = now()->lt($shiftEnd) && now()->gt($shiftStart) ? now() : null;

    // Loại khoảng đã ghi nhận → [hậu tố class màu, icon, nhãn]
    $kinds = [
        'producing' => ['producing', 'fa-cog', 'Sản Xuất'],
        'preparing' => ['preparing', 'fa-clipboard-check', 'Chuẩn Bị'],
        'cleaning' => ['cleaning', 'fa-broom', 'Vệ Sinh'],
        'paused' => ['paused', 'fa-pause-circle', 'Tạm Dừng SX'],
        'activity' => ['activity', 'fa-bolt', 'Hoạt động khác'],
    ];

    // % đáp ứng → [class màu, chữ, độ dài thanh]; không có lý thuyết lẫn thực tế thì không đánh giá
    $pctView = fn($lt, $tt, $percent) => $lt == 0 && $tt == 0
        ? ['none', '—', 0]
        : [$percent < 90 ? 'low' : 'ok', number_format($percent, 2) . '%', min(100, max(0, $percent))];

    $dayLT = collect($yield_theoryl_detial['yield_day'] ?? []);
    $dayTT = collect($yield_actual_detial['yield_day'] ?? []);
    $actualDetail = collect($yield_actual_detial['actual_detail'] ?? []);
    $roomsByStage = $theory['yield_room']->groupBy('stage_code');
@endphp

<div class="card yc-card mb-4">
    <div class="card-header">
        <div class="yc-head">
            <span class="yc-head-icon"><i class="fas fa-chart-bar"></i></span>
            <div>
                <h3 class="card-title">Sản Lượng</h3>
                <div class="yc-head-sub">
                    06:00 {{ $shiftStart->format('d/m/Y') }} → 06:00 {{ $shiftEnd->format('d/m/Y') }}
                    @if ($viewedUntil)
                        · số liệu tính đến {{ $viewedUntil->format('H:i') }}
                    @endif
                </div>
            </div>
        </div>
        <div class="card-tools">
            <button type="button" class="btn btn-tool" data-card-widget="collapse" title="Thu gọn">
                <i class="fas fa-minus"></i>
            </button>
            <button type="button" class="btn btn-tool" data-card-widget="remove" title="Đóng">
                <i class="fas fa-times"></i>
            </button>
        </div>
    </div>

    <div class="card-body">
        {{-- Chú thích màu dòng thời gian ở cột Chi tiết --}}
        <div class="yc-legend">
            <div class="yc-legend-row">
                <span class="yc-legend-group">Chú thích</span>
                @foreach ($kinds as [$cls, $icon, $label])
                    <span class="yc-lg"><i class="yc-sw dr-seg-{{ $cls }}"></i>{{ $label }}</span>
                @endforeach
                {{-- Khoảng không có dữ liệu ghi nhận: 1 màu chung, lý do suy ra ghi ở từng dòng --}}
                <span class="yc-lg"><i class="yc-sw dr-idle"></i>Không hoạt động</span>
            </div>
        </div>

        <table id="data_table_yield" class="table yc-table">
            <thead>
                <tr>
                    <th class="yc-col-room">Phòng SX</th>
                    <th class="yc-col-unit">ĐV</th>
                    <th class="yc-col-lt">Sản lượng lý thuyết</th>
                    <th class="yc-col-tt">Sản lượng thực tế</th>
                    <th class="yc-col-pct">Đáp ứng</th>
                    <th class="yc-col-detail">Chi tiết</th>
                </tr>
            </thead>

            <tbody>
                @foreach ($roomsByStage as $stage_code => $rooms)
                    @php
                        $unit = $stage_code <= 4 ? 'Kg' : 'ĐVL';
                        $stageLT = $dayLT->where('stage_code', $stage_code)->sum('total_qty');
                        $stageTT = $dayTT->where('stage_code', $stage_code)->sum('total_qty');
                        $stagePercent = $stageLT == 0 ? ($stageTT > 0 ? 100 : 0) : ($stageTT / $stageLT) * 100;
                        [$pctCls, $pctText, $pctBar] = $pctView($stageLT, $stageTT, $stagePercent);

                        // Định hình: tách theo dạng bào chế như trước
                        $forms = ['tablet' => 'Tablet', 'coating' => 'Coating', 'capsule' => 'Capsule'];
                        $sumForm = fn($day, $type) => $day->where('stage_code', 5)->where('table_type', $type)->sum('total_qty');
                        $explain = $explanation[$stage_code] ?? null;
                    @endphp

                    {{-- Dòng tổng công đoạn --}}
                    <tr class="yc-stage stage-total" data-stage="{{ $stage_code }}">
                        <td>
                            <div class="yc-stage-head">
                                <button type="button" class="yc-toggle toggle-stage" data-stage="{{ $stage_code }}"
                                    title="Thu gọn / mở rộng các phòng"><i class="fas fa-chevron-down"></i></button>
                                <div>
                                    <div class="yc-stage-name">{{ $stage_name[$stage_code] ?? $stage_code }}</div>
                                    <div class="yc-stage-meta">{{ $rooms->count() }} phòng</div>
                                </div>
                            </div>
                        </td>
                        <td class="yc-unit">{{ $unit }}</td>
                        <td class="yc-right">
                            <div class="yc-num">{{ number_format($stageLT, 2) }}</div>
                            @if ($stage_code == 4)
                                <div class="yc-sub"># {{ number_format($dayLT->where('stage_code', 4)->sum('total_qty_unit'), 2) }} ĐVL</div>
                            @endif
                            @if ($stage_code == 5)
                                <div class="yc-break">
                                    @foreach ($forms as $type => $label)
                                        <span>{{ $label }}</span><b>{{ number_format($sumForm($dayLT, $type), 2) }}</b>
                                    @endforeach
                                </div>
                            @endif
                        </td>
                        <td class="yc-right">
                            <div class="yc-num yc-num-actual">{{ number_format($stageTT, 2) }}</div>
                            @if ($stage_code == 4)
                                <div class="yc-sub"># {{ number_format($dayTT->where('stage_code', 4)->sum('total_qty_unit'), 2) }} ĐVL</div>
                            @endif
                            @if ($stage_code == 5)
                                <div class="yc-break">
                                    @foreach ($forms as $type => $label)
                                        <span>{{ $label }}</span><b>{{ number_format($sumForm($dayTT, $type), 2) }}</b>
                                    @endforeach
                                </div>
                            @endif
                        </td>
                        <td class="yc-pct {{ $pctCls }}">
                            <span class="yc-pct-val">{{ $pctText }}</span>
                            <div class="yc-bar" title="Vạch mốc: 90%"><span style="width: {{ $pctBar }}%"></span></div>
                        </td>
                        <td>
                            <div class="yc-explain">
                                <div class="yc-explain-item">
                                    <span class="yc-explain-label">Giải trình</span>
                                    <span class="yc-explain-text {{ $explain && !empty($explain->content) ? '' : 'empty' }}">{{ $explain && !empty($explain->content) ? trim($explain->content) : '—' }}</span>
                                </div>
                                <div class="yc-explain-item">
                                    <span class="yc-explain-label">Lý do</span>
                                    <span class="yc-explain-text {{ $explain && !empty($explain->reason) ? '' : 'empty' }}">{{ $explain && !empty($explain->reason) ? trim($explain->reason) : '—' }}</span>
                                </div>
                                <button type="button" class="btn btn-explain" data-stage_code="{{ $stage_code }}"
                                    data-reported_date="{{ $defaultFrom }}" data-toggle="modal" data-target="#explanation"
                                    title="Sửa giải trình / lý do">
                                    <i class="fas fa-pen"></i>
                                </button>
                            </div>
                        </td>
                    </tr>

                    {{-- Các phòng trong công đoạn --}}
                    @foreach ($rooms as $roomLT)
                        @php
                            $resourceId = $roomLT->resourceId;
                            $itemLT = $dayLT->firstWhere('resourceId', $resourceId);
                            $itemTT = $dayTT->firstWhere('resourceId', $resourceId);
                            $qtyLT = $itemLT['total_qty'] ?? 0;
                            $qtyTT = $itemTT['total_qty'] ?? 0;

                            if ($qtyTT > 0 && $qtyLT > 0) {
                                $percent = ($qtyTT / $qtyLT) * 100;
                            } elseif ($qtyTT > 0 && $qtyLT == 0) {
                                $percent = 100;
                            } else {
                                $percent = 0;
                            }
                            [$pctCls, $pctText, $pctBar] = $pctView($qtyLT, $qtyTT, $percent);

                            $plans = collect($itemLT['theory_items'] ?? []);
                            $detail = $actualDetail->where('resourceId', $resourceId);
                            $tl = $roomTimeline[$resourceId] ?? null;
                        @endphp

                        <tr class="yc-room-row stage-child stage-{{ $stage_code }}">
                            <td>
                                <div class="yc-room">
                                    <span class="yc-code">{{ $roomLT->room_code }}</span>
                                    <span class="yc-room-name">{{ $roomLT->room_name }}</span>
                                </div>
                            </td>
                            <td class="yc-unit">{{ $unit }}</td>
                            <td class="yc-right">
                                <div class="yc-num">{{ number_format($qtyLT, 2) }}</div>
                                @if ($stage_code == 4)
                                    <div class="yc-sub"># {{ number_format($itemLT['total_qty_unit'] ?? 0, 2) }} ĐVL</div>
                                @endif
                                @if ($plans->isNotEmpty())
                                    <div class="yc-plans">
                                        @foreach ($plans->take(4) as $p)
                                            <div class="yc-plan">
                                                <span class="yc-plan-time">{{ $p->start }} – {{ $p->end }}</span>
                                                <span class="yc-plan-title">{{ $p->title }}</span>
                                                <span class="yc-plan-qty">{{ number_format($p->yields, $stage_code <= 4 ? 2 : 0) }}</span>
                                            </div>
                                        @endforeach
                                        @if ($plans->count() > 4)
                                            <details class="yc-more">
                                                <summary>+ {{ $plans->count() - 4 }} lô khác</summary>
                                                @foreach ($plans->slice(4) as $p)
                                                    <div class="yc-plan">
                                                        <span class="yc-plan-time">{{ $p->start }} – {{ $p->end }}</span>
                                                        <span class="yc-plan-title">{{ $p->title }}</span>
                                                        <span class="yc-plan-qty">{{ number_format($p->yields, $stage_code <= 4 ? 2 : 0) }}</span>
                                                    </div>
                                                @endforeach
                                            </details>
                                        @endif
                                    </div>
                                @endif
                            </td>
                            <td class="yc-right">
                                <div class="yc-num yc-num-actual">{{ number_format($qtyTT, 2) }}</div>
                                @if ($stage_code == 4)
                                    <div class="yc-sub"># {{ number_format($itemTT['total_qty_unit'] ?? 0, 2) }} ĐVL</div>
                                @endif
                            </td>
                            <td class="yc-pct {{ $pctCls }}">
                                <span class="yc-pct-val">{{ $pctText }}</span>
                                <div class="yc-bar" title="Vạch mốc: 90%"><span style="width: {{ $pctBar }}%"></span></div>
                            </td>
                            <td class="dr-detail-cell">
                                @include('pages.report.daily_report._yield_detail')
                            </td>
                        </tr>
                    @endforeach
                @endforeach
            </tbody>
        </table>
    </div>
</div>
